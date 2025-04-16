<?php
// Importa a classe de conexão com o banco de dados
require_once __DIR__ . '/../models/Database.php';
// Importa a classe de integração com o gateway de pagamento
require_once __DIR__ . '/../gateways/PagcompletoGateway.php';

/**
 * Classe para processar os pagamentos pendentes
 */
class PaymentService
{
  private $db; // Instância do banco de dados
  private $gateway; // Instância do gateway de pagamento
  private $accessToken; // Token de acesso à API

  /**
   * Construtor da classe
   *
   * @param string $accessToken Token de acesso à API
   */
  public function __construct($accessToken)
  {
    $this->db = new Database(); // Cria conexão com o banco
    $this->accessToken = $accessToken; // Salva o token
  }

  /**
   * Busca os pedidos pendentes de pagamento
   *
   * @return array Pedidos pendentes de pagamento
   */
  public function getPendingOrders()
  {
    // Consulta SQL para buscar pedidos aguardando pagamento, de lojas PAGCOMPLETO e cartão de crédito
    $query = "
            SELECT
                p.id AS pedido_id,
                p.valor_total,
                p.valor_frete,
                p.data,
                p.id_cliente,
                p.id_loja,
                pp.id AS pagamento_id,
                pp.id_formapagto,
                pp.qtd_parcelas,
                pp.num_cartao,
                pp.nome_portador,
                pp.codigo_verificacao,
                pp.vencimento,
                c.nome AS cliente_nome,
                c.cpf_cnpj,
                c.email,
                c.tipo_pessoa,
                fg.descricao AS forma_pagamento,
                g.endpoint
            FROM pedidos p
            INNER JOIN pedidos_pagamentos pp ON p.id = pp.id_pedido
            INNER JOIN clientes c ON p.id_cliente = c.id
            INNER JOIN formas_pagamento fg ON pp.id_formapagto = fg.id
            INNER JOIN lojas_gateway lg ON p.id_loja = lg.id_loja
            INNER JOIN gateways g ON lg.id_gateway = g.id
            WHERE p.id_situacao = 1 -- Aguardando Pagamento
            AND g.id = 1 -- Gateway PAGCOMPLETO
            AND pp.id_formapagto = 3 -- Cartão de Crédito
        ";
    $result = $this->db->query($query); // Executa a query
    return $this->db->fetchAll($result); // Retorna todos os pedidos encontrados
  }

  /**
   * Valida os dados do pagamento antes de enviar à API
   * @param array $data Dados formatados do pedido
   * @return array|true Retorna true se válido, ou array com erro
   */
  protected function validatePaymentData($data)
  {
    // Campos obrigatórios de nível superior
    $required = ['external_order_id', 'amount', 'card_number', 'card_cvv', 'card_expiration_date', 'card_holder_name', 'customer'];
    foreach ($required as $field) {
      if (empty($data[$field])) {
        return ['error' => true, 'message' => "Campo obrigatório ausente: $field"];
      }
    }
    // Validação do número do cartão
    if (!preg_match('/^\d{13,19}$/', $data['card_number'])) {
      return ['error' => true, 'message' => 'Número do cartão inválido'];
    }
    // Validação do CVV
    if (!preg_match('/^\d{3,4}$/', $data['card_cvv'])) {
      return ['error' => true, 'message' => 'CVV inválido'];
    }
    // Validação da validade (MMYY)
    if (!preg_match('/^(0[1-9]|1[0-2])\d{2}$/', $data['card_expiration_date'])) {
      return ['error' => true, 'message' => 'Data de validade do cartão inválida'];
    }
    // Validação do e-mail
    if (!filter_var($data['customer']['email'], FILTER_VALIDATE_EMAIL)) {
      return ['error' => true, 'message' => 'E-mail do cliente inválido'];
    }
    // Validação do CPF
    if (!preg_match('/^\d{11}$/', $data['customer']['documents'][0]['number'])) {
      return ['error' => true, 'message' => 'CPF do cliente inválido'];
    }
    return true;
  }

  /**
   * Formata os dados do pedido para enviar à API
   *
   * @param array $order Dados do pedido
   * @return array Dados formatados para a API
   */
  protected function formatOrderData($order)
  {
    // Formata a data de validade do cartão (aceita MM/YYYY ou YYYY-MM e converte para MMYY)
    $expiryDate = '';
    if (isset($order['vencimento']) && !empty($order['vencimento'])) {
      $v = $order['vencimento'];
      // Se vier MM/YYYY, converte para MMYY
      if (preg_match('/^(\d{2})\/(\d{4})$/', $v, $m)) {
        $expiryDate = $m[1] . substr($m[2], -2);
      } elseif (preg_match('/^(\d{4})-(\d{2})$/', $v, $m)) {
        // Se vier YYYY-MM, converte para MMYY
        $expiryDate = $m[2] . substr($m[1], -2);
      }
    }
    // Limpa o número do cartão (deixa só números)
    $cardNumber = isset($order['num_cartao']) ? preg_replace('/[^0-9]/', '', $order['num_cartao']) : '';
    // Busca a data de nascimento do cliente
    $customerBirthday = '';
    $query = "SELECT data_nasc FROM clientes WHERE id = $1";
    $result = $this->db->query($query, [$order['id_cliente']]);
    $row = $this->db->fetch($result);
    if ($row && isset($row['data_nasc'])) {
      $customerBirthday = $row['data_nasc'];
    }
    // Monta o array no formato esperado pela API
    $formattedData = [
      'external_order_id' => (int)$order['pedido_id'],
      'amount' => (float)$order['valor_total'],
      'card_number' => $cardNumber,
      'card_cvv' => (string)($order['codigo_verificacao'] ?? ''),
      'card_expiration_date' => $expiryDate,
      'card_holder_name' => $order['nome_portador'] ?? '',
      'customer' => [
        'external_id' => (string)$order['id_cliente'],
        'name' => $order['cliente_nome'] ?? '',
        'type' => ($order['tipo_pessoa'] == 'F' ? 'individual' : 'corporation'),
        'email' => $order['email'] ?? '',
        'documents' => [
          [
            'type' => 'cpf',
            'number' => $order['cpf_cnpj'] ?? ''
          ]
        ],
        'birthday' => $customerBirthday
      ]
    ];
    // Validação dos dados antes de retornar
    $validation = $this->validatePaymentData($formattedData);
    if ($validation !== true) {
      // Retorna erro de validação
      return $validation;
    }
    return $formattedData;
  }

  /**
   * Mapeia o tipo de pagamento interno para o formato da API
   *
   * @param int $paymentTypeId ID do tipo de pagamento
   * @return string Tipo de pagamento para API
   */
  private function mapPaymentType($paymentTypeId)
  {
    switch ($paymentTypeId) {
      case 1:
        return 'boleto';
      case 2:
        return 'pix';
      case 3:
        return 'credit_card';
      default:
        return 'unknown';
    }
  }

  /**
   * Atualiza o status do pedido no banco de dados
   *
   * @param int $orderId ID do pedido
   * @param int $newStatus Novo status do pedido
   * @param string $responseData Dados da resposta da API
   * @return bool Resultado da atualização
   */
  private function updateOrderStatus($orderId, $newStatus, $responseData)
  {
    // Atualiza o status do pedido na tabela pedidos
    $queryOrder = "UPDATE pedidos SET id_situacao = $1 WHERE id = $2";
    $this->db->query($queryOrder, [$newStatus, $orderId]);
    // Atualiza a tabela de pagamentos com os dados do retorno
    $queryPayment = "
            UPDATE pedidos_pagamentos
            SET retorno_intermediador = $1, data_processamento = NOW()
            WHERE id_pedido = $2
        ";
    $result = $this->db->query($queryPayment, [json_encode($responseData), $orderId]);
    return $result !== false;
  }

  /**
   * Processa os pagamentos pendentes
   *
   * @return array Resultado do processamento
   */
  public function processPendingPayments()
  {
    $results = [
      'success' => [],
      'failures' => []
    ];
    try {
      $pendingOrders = $this->getPendingOrders(); // Busca pedidos pendentes
      if (empty($pendingOrders)) {
        return ['message' => 'Nenhum pedido pendente encontrado.', 'processed' => 0];
      }
      foreach ($pendingOrders as $order) {
        try {
          // Cria o gateway de pagamento
          $this->gateway = new PagcompletoGateway($order['endpoint'], $this->accessToken);
          // Formata os dados para a API
          $paymentData = $this->formatOrderData($order);
          // Se houver erro de validação, não processa
          if (isset($paymentData['error']) && $paymentData['error'] === true) {
            $results['failures'][] = [
              'order_id' => $order['pedido_id'],
              'message' => 'Erro de validação: ' . $paymentData['message']
            ];
            continue;
          }
          // Envia para a API
          $response = $this->gateway->processTransaction($paymentData);
          // Analisa o retorno da API
          if ($response) {
            if (isset($response['Transaction_code'])) {
              if ($response['Transaction_code'] === '00') {
                // Pagamento aprovado
                $this->updateOrderStatus($order['pedido_id'], 2, $response);
                $results['success'][] = [
                  'order_id' => $order['pedido_id'],
                  'message' => 'Pagamento aprovado'
                ];
              } elseif (in_array($response['Transaction_code'], ['03', '04'])) {
                // Pagamento recusado
                $this->updateOrderStatus($order['pedido_id'], 3, $response);
                $results['failures'][] = [
                  'order_id' => $order['pedido_id'],
                  'message' => 'Pagamento recusado: ' . ($response['Message'] ?? 'Motivo desconhecido')
                ];
              } else {
                // Outros códigos
                $this->updateOrderStatus($order['pedido_id'], 1, $response);
                $results['failures'][] = [
                  'order_id' => $order['pedido_id'],
                  'message' => 'Status indefinido: ' . ($response['Message'] ?? 'Motivo desconhecido')
                ];
              }
            } else {
              // Resposta inesperada
              $this->updateOrderStatus($order['pedido_id'], 1, $response);
              $results['failures'][] = [
                'order_id' => $order['pedido_id'],
                'message' => 'Erro: Resposta incompleta da API'
              ];
            }
          } else {
            throw new Exception("Resposta inválida da API para o pedido {$order['pedido_id']}");
          }
        } catch (Exception $e) {
          $results['failures'][] = [
            'order_id' => $order['pedido_id'],
            'message' => 'Erro: ' . $e->getMessage()
          ];
        }
      }
      $results['processed'] = count($pendingOrders);
      $results['successful'] = count($results['success']);
      $results['failed'] = count($results['failures']);
    } catch (Exception $e) {
      return ['error' => true, 'message' => 'Erro durante o processamento: ' . $e->getMessage()];
    }
    return $results;
  }

  /**
   * Busca os itens de um pedido
   *
   * @param int $orderId ID do pedido
   * @return array Itens do pedido
   */
  private function getOrderItems($orderId)
  {
    $query = "
      SELECT
        id_produto,
        descricao,
        quantidade,
        valor_unitario
      FROM
        pedidos_itens
      WHERE
        id_pedido = $1
    ";
    $result = $this->db->query($query, [$orderId]);
    return $this->db->fetchAll($result);
  }
}

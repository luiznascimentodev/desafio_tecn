<?php
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../gateways/PagcompletoGateway.php';

class PaymentService
{
  private $db;
  private $gateway;
  private $accessToken;

  public function __construct($accessToken)
  {
    $this->db = new Database();
    $this->accessToken = $accessToken;
  }

  public function getPendingOrders()
  {
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
            WHERE p.id_situacao = 1
            AND g.id = 1
            AND pp.id_formapagto = 3
        ";
    $result = $this->db->query($query);
    return $this->db->fetchAll($result);
  }

  protected function validatePaymentData($data)
  {
    $required = ['external_order_id', 'amount', 'card_number', 'card_cvv', 'card_expiration_date', 'card_holder_name', 'customer'];
    foreach ($required as $field) {
      if (empty($data[$field])) {
        return ['error' => true, 'message' => "Campo obrigatório ausente: $field"];
      }
    }
    if (!preg_match('/^\d{13,19}$/', $data['card_number'])) {
      return ['error' => true, 'message' => 'Número do cartão inválido'];
    }
    if (!preg_match('/^\d{3,4}$/', $data['card_cvv'])) {
      return ['error' => true, 'message' => 'CVV inválido'];
    }
    if (!preg_match('/^(0[1-9]|1[0-2])\d{2}$/', $data['card_expiration_date'])) {
      return ['error' => true, 'message' => 'Data de validade do cartão inválida'];
    }
    if (!filter_var($data['customer']['email'], FILTER_VALIDATE_EMAIL)) {
      return ['error' => true, 'message' => 'E-mail do cliente inválido'];
    }
    if (!preg_match('/^\d{11}$/', $data['customer']['documents'][0]['number'])) {
      return ['error' => true, 'message' => 'CPF do cliente inválido'];
    }
    return true;
  }

  protected function formatOrderData($order)
  {
    $expiryDate = '';
    if (isset($order['vencimento']) && !empty($order['vencimento'])) {
      $v = $order['vencimento'];
      if (preg_match('/^(\d{2})\/(\d{4})$/', $v, $m)) {
        $expiryDate = $m[1] . substr($m[2], -2);
      } elseif (preg_match('/^(\d{4})-(\d{2})$/', $v, $m)) {
        $expiryDate = $m[2] . substr($m[1], -2);
      }
    }
    $cardNumber = isset($order['num_cartao']) ? preg_replace('/[^0-9]/', '', $order['num_cartao']) : '';
    $customerBirthday = '';
    $query = "SELECT data_nasc FROM clientes WHERE id = $1";
    $result = $this->db->query($query, [$order['id_cliente']]);
    $row = $this->db->fetch($result);
    if ($row && isset($row['data_nasc'])) {
      $customerBirthday = $row['data_nasc'];
    }
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
    $validation = $this->validatePaymentData($formattedData);
    if ($validation !== true) {
      return $validation;
    }
    return $formattedData;
  }

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

  private function updateOrderStatus($orderId, $newStatus, $responseData)
  {
    $queryOrder = "UPDATE pedidos SET id_situacao = $1 WHERE id = $2";
    $this->db->query($queryOrder, [$newStatus, $orderId]);
    $queryPayment = "
            UPDATE pedidos_pagamentos
            SET retorno_intermediador = $1, data_processamento = NOW()
            WHERE id_pedido = $2
        ";
    $result = $this->db->query($queryPayment, [json_encode($responseData), $orderId]);
    return $result !== false;
  }

  public function processPendingPayments()
  {
    $results = [
      'success' => [],
      'failures' => []
    ];
    try {
      $pendingOrders = $this->getPendingOrders();
      if (empty($pendingOrders)) {
        return ['message' => 'Nenhum pedido pendente encontrado.', 'processed' => 0];
      }
      foreach ($pendingOrders as $order) {
        try {
          $this->gateway = new PagcompletoGateway($order['endpoint'], $this->accessToken);
          $paymentData = $this->formatOrderData($order);
          if (isset($paymentData['error']) && $paymentData['error'] === true) {
            $results['failures'][] = [
              'order_id' => $order['pedido_id'],
              'message' => 'Erro de validação: ' . $paymentData['message']
            ];
            continue;
          }
          $response = $this->gateway->processTransaction($paymentData);
          if ($response) {
            if (isset($response['Transaction_code'])) {
              if ($response['Transaction_code'] === '00') {
                $this->updateOrderStatus($order['pedido_id'], 2, $response);
                $results['success'][] = [
                  'order_id' => $order['pedido_id'],
                  'message' => 'Pagamento aprovado'
                ];
              } elseif (in_array($response['Transaction_code'], ['03', '04'])) {
                $this->updateOrderStatus($order['pedido_id'], 3, $response);
                $results['failures'][] = [
                  'order_id' => $order['pedido_id'],
                  'message' => 'Pagamento recusado: ' . ($response['Message'] ?? 'Motivo desconhecido')
                ];
              } else {
                $this->updateOrderStatus($order['pedido_id'], 1, $response);
                $results['failures'][] = [
                  'order_id' => $order['pedido_id'],
                  'message' => 'Status indefinido: ' . ($response['Message'] ?? 'Motivo desconhecido')
                ];
              }
            } else {
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

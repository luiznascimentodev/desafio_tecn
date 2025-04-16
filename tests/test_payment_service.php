<?php
// Testes simples para PaymentService
require_once __DIR__ . '/../services/PaymentService.php';

class PaymentServiceTest extends PaymentService
{
  // Torna o método de validação público para teste
  public function publicValidatePaymentData($data)
  {
    return $this->validatePaymentData($data);
  }
}

function assertEqual($a, $b, $msg)
{
  if ($a === $b) {
    echo "[OK] $msg\n";
  } else {
    echo "[FAIL] $msg\n";
    echo "Esperado: ";
    var_export($b);
    echo "\n";
    echo "Obtido: ";
    var_export($a);
    echo "\n";
  }
}

$service = new PaymentServiceTest('dummy_token');

// Teste 1: Validação de cartão válido
$validData = [
  'external_order_id' => 1,
  'amount' => 100.0,
  'card_number' => '4111111111111111',
  'card_cvv' => '123',
  'card_expiration_date' => '0525',
  'card_holder_name' => 'Teste Junior',
  'customer' => [
    'external_id' => '1',
    'name' => 'Cliente Teste',
    'type' => 'individual',
    'email' => 'teste@exemplo.com',
    'documents' => [
      ['type' => 'cpf', 'number' => '12345678901']
    ],
    'birthday' => '2000-01-01'
  ]
];
assertEqual($service->publicValidatePaymentData($validData), true, 'Validação de cartão válido');

// Teste 2: Validação de cartão inválido (número curto)
$invalidCard = $validData;
$invalidCard['card_number'] = '123';
$result = $service->publicValidatePaymentData($invalidCard);
assertEqual(isset($result['error']) && $result['error'] === true, true, 'Validação de cartão inválido (número curto)');

// Teste 3: Validação de e-mail inválido
$invalidEmail = $validData;
$invalidEmail['customer']['email'] = 'email_invalido';
$result = $service->publicValidatePaymentData($invalidEmail);
assertEqual(isset($result['error']) && $result['error'] === true, true, 'Validação de e-mail inválido');

// Teste 4: Quando não há pedidos pendentes
$result = $service->processPendingPayments();
assertEqual(isset($result['message']) && is_string($result['message']), true, 'Retorno quando não há pedidos pendentes');

echo "Testes finalizados.\n";

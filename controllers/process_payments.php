<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once __DIR__ . '/../services/PaymentService.php';
require_once __DIR__ . '/../views/json_response.php';

try {
  $accessToken = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VySWQiOjI2ODQsInN0b3JlSWQiOjE5NzksImlhdCI6MTc0NDY2MDgyOSwiZXhwIjoxNzQ1OTU2ODI5fQ.m9-SyVLC2o4J2yPp9E5EXt3wbQMjxyh0Rbz3wJrODcM";

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentProcessor = new PaymentService($accessToken);
    $result = $paymentProcessor->processPendingPayments();
    render_json_response($result);
  } else {
    http_response_code(405);
    echo json_encode([
      'error' => true,
      'message' => 'Método não permitido. Utilize POST para processar pagamentos.'
    ]);
  }
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode([
    'error' => true,
    'message' => 'Erro interno: ' . $e->getMessage()
  ]);
}

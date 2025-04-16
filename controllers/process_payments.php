<?php

// Habilita exibição de erros (remover em produção)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define o cabeçalho da resposta como JSON
header('Content-Type: application/json');

require_once __DIR__ . '/../services/PaymentService.php';
require_once __DIR__ . '/../views/json_response.php';

try {
  // Token de acesso à API fornecido
  $accessToken = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VySWQiOjI2ODQsInN0b3JlSWQiOjE5NzksImlhdCI6MTc0NDY2MDgyOSwiZXhwIjoxNzQ1OTU2ODI5fQ.m9-SyVLC2o4J2yPp9E5EXt3wbQMjxyh0Rbz3wJrODcM";

  // Verifica se é uma requisição POST
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Instancia o processador de pagamentos
    $paymentProcessor = new PaymentService($accessToken);

    // Processa os pagamentos pendentes
    $result = $paymentProcessor->processPendingPayments();

    // Exibe o resultado em formato JSON
    render_json_response($result);
  } else {
    // Se não for um método POST, retorna erro
    http_response_code(405); // Method Not Allowed
    echo json_encode([
      'error' => true,
      'message' => 'Método não permitido. Utilize POST para processar pagamentos.'
    ]);
  }
} catch (Exception $e) {
  // Em caso de erro, retorna uma resposta de erro
  http_response_code(500);
  echo json_encode([
    'error' => true,
    'message' => 'Erro interno: ' . $e->getMessage()
  ]);
}

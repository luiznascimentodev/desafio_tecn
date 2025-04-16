<?php

require_once __DIR__ . '/../utils/Logger.php';

/**
 * Classe para integração com o Gateway de Pagamento PAGCOMPLETO
 * Responsável por enviar transações e tratar respostas do gateway.
 */
class PagcompletoGateway
{
  private $endpoint;
  private $accessToken;


  public function __construct($endpoint = null, $accessToken = null)
  {
    $this->endpoint = $endpoint;
    $this->accessToken = $accessToken;
  }

  public function processTransaction($paymentData)
  {
    // Usa o endpoint correto e passa o token na query string
    $url = 'https://apiinterna.ecompleto.com.br/exams/processTransaction?accessToken=' . $this->accessToken;

    // Inicializa a requisição cURL
    $curl = curl_init();

    // Configura as opções da requisição
    curl_setopt_array($curl, [
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => json_encode($paymentData),
      CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "Accept: application/json"
      ],
      CURLOPT_SSL_VERIFYPEER => false, // Para testes
      CURLOPT_SSL_VERIFYHOST => false, // Para testes
    ]);

    // Executa a requisição e obtém a resposta
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);

    // Loga resposta bruta e código HTTP para debug
    log_message("[PagcompletoGateway] HTTP_CODE: $httpCode", 'DEBUG');
    log_message("[PagcompletoGateway] RAW_RESPONSE: " . var_export($response, true), 'DEBUG');

    // Fecha a conexão cURL
    curl_close($curl);

    // Verifica se ocorreu algum erro na requisição
    if ($error) {
      log_message("Erro na requisição cURL: " . $error, 'ERROR');
      return false;
    }

    // Decodifica a resposta JSON
    $responseData = json_decode($response, true);

    return $responseData;
  }
}

<?php

require_once __DIR__ . '/../utils/Logger.php';

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
    $url = 'https://apiinterna.ecompleto.com.br/exams/processTransaction?accessToken=' . $this->accessToken;

    $curl = curl_init();

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
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_SSL_VERIFYHOST => false,
    ]);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);

    log_message("[PagcompletoGateway] HTTP_CODE: $httpCode", 'DEBUG');
    log_message("[PagcompletoGateway] RAW_RESPONSE: " . var_export($response, true), 'DEBUG');

    curl_close($curl);

    if ($error) {
      log_message("Erro na requisição cURL: " . $error, 'ERROR');
      return false;
    }

    $responseData = json_decode($response, true);

    return $responseData;
  }
}

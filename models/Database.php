<?php

class Database
{
  private $host = 'localhost';
  private $db_name = 'seu_banco';
  private $username = 'usuario';
  private $password = 'senha';
  public $conn;

  public function getConnection()
  {
    $this->conn = null;
    try {
      $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
      $this->conn->exec("set names utf8");
    } catch (PDOException $exception) {
      echo "Erro de conexão: " . $exception->getMessage();
    }
    return $this->conn;
  }
}

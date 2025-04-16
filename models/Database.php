<?php

/**
 * Classe responsável por gerenciar a conexão com o banco de dados
 */
class Database
{
  private $connection;
  private $config;

  public function __construct()
  {
    // Carrega a configuração do banco de dados
    $this->config = require_once __DIR__ . '/../config/database.php';
    $this->connect();
  }

  /**
   * Estabelece a conexão com o banco de dados
   */
  private function connect()
  {
    $connectionString = sprintf(
      "host=%s port=%s dbname=%s user=%s password=%s",
      $this->config['host'],
      $this->config['port'],
      $this->config['database'],
      $this->config['username'],
      $this->config['password']
    );

    try {
      $this->connection = pg_connect($connectionString);
      if (!$this->connection) {
        throw new Exception("Falha ao conectar ao banco de dados PostgreSQL");
      }
    } catch (Exception $e) {
      die("Erro de conexão: " . $e->getMessage());
    }
  }

  /**
   * Executa uma consulta SQL
   *
   * @param string $query Query SQL a ser executada
   * @param array $params Parâmetros para a query
   * @return resource Resultado da consulta
   */
  public function query($query, $params = [])
  {
    if (!empty($params)) {
      // Prepara a query com os parâmetros
      $result = pg_query_params($this->connection, $query, $params) ?: null;
    } else {
      $result = pg_query($this->connection, $query) ?: null;
    }

    if (!$result) {
      die("Erro na execução da query: " . pg_last_error($this->connection));
    }

    return $result;
  }

  /**
   * Obtém um único registro do resultado da consulta
   *
   * @param resource $result Resultado da consulta
   * @return array Registro obtido ou null
   */
  public function fetch($result)
  {
    return $result instanceof \PgSql\Result ? pg_fetch_assoc($result) : null;
  }

  /**
   * Obtém todos os registros do resultado da consulta
   *
   * @param resource $result Resultado da consulta
   * @return array Array de registros
   */
  public function fetchAll($result)
  {
    $rows = [];
    while ($row = pg_fetch_assoc($result)) {
      $rows[] = $row;
    }
    return $rows;
  }

  /**
   * Fecha a conexão com o banco de dados
   */
  public function close()
  {
    if ($this->connection) {
      pg_close($this->connection);
    }
  }
}

<?php

/**
 * Função utilitária para logging padronizado
 * @param string $message Mensagem a ser logada
 * @param string $level Nível do log (INFO, ERROR, DEBUG, etc)
 */
function log_message($message, $level = 'INFO')
{
  $timestamp = date('Y-m-d H:i:s');
  error_log("[$timestamp][$level] $message");
}

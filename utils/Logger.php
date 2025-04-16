<?php

function log_message($message, $level = 'INFO')
{
  $date = date('Y-m-d H:i:s');
  $log = "[$date][$level] $message\n";
  file_put_contents(__DIR__ . '/../logs/app.log', $log, FILE_APPEND);
}

<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/Database.php';

$db = new Database();

echo "Servidor web está funcionando!";
echo "<br><br>";
echo "Diretório atual: " . __DIR__;

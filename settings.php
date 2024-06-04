<?php
require('env.php');
session_start();

// подключение к БД
$dbconnect = pg_connect($DB_CONNECTION_STRING);
if (!$dbconnect) {
  echo "Ошибка подключения к БД";
  http_response_code(500);
  exit;
}

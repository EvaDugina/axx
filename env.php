<?php
// Пример содержимого env.php
$host = getenv('POSTGRES_HOST');
$port = getenv('POSTGRES_INNER_PORT');
$user = getenv('POSTGRES_USER');
$password = getenv('POSTGRES_PASSWORD');
$dbname = getenv('POSTGRES_DB');

$DB_CONNECTION_STRING = "host=$host port=$port dbname=$dbname user=$user password=$password";

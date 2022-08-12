<?php
	$DB_CONNECTION_STRING = "host=localhost port=5434 dbname=accelerator user=accelerator password=123456"; 
		
	// подключение к БД
	$dbconnect = pg_connect($DB_CONNECTION_STRING);
	if (!$dbconnect) {
		echo "Ошибка подключения к БД";
		http_response_code(500);
		exit;
	}
?>
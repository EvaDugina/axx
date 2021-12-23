<?php
	$DB_CONNECTION_STRING = "host=localhost port=5432 dbname=accelerator user=postgres password=postgres"; 
		
	// подключение к БД
	$dbconnect = pg_connect($DB_CONNECTION_STRING);
	if (!$dbconnect) {
		echo "Ошибка подключения к БД";
		http_response_code(500);
		exit;
	}
?>
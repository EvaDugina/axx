<?php

	require_once("common.php");
	require_once("dbqueries.php");
	require_once("utilities.php");

	$query = update_discipline($_POST);
	$result = pg_query($dbconnect, $query);
	//var_dump($query);
	header('Location: http://localhost/accelerator/preptasks.php?page=-1#');
?>
<?php

	require_once("common.php");
	require_once("dbqueries.php");
	require_once("utilities.php");
	
	$query = update_discipline($_POST);
	$result = pg_query($dbconnect, $query);
	
	$query = delete_page_prep($_POST['id']);
	$result = pg_query($dbconnect, $query);
	
	$query = delete_page_group($_POST['id']);
	$result = pg_query($dbconnect, $query);

	foreach($_POST['teachers'] as $teacher)
	{
		$pos= strpos($teacher, ' ');
		$first_name = substr($teacher, 0, $pos);
		$middle_name = substr($teacher, $pos+1);
		$query = prep_ax_prep_page($_POST['id'], $first_name, $middle_name);
		pg_query($dbconnect, $query);
		var_dump($query);
	}
	
	foreach($_POST['groups'] as $group)
	{
		$query = update_ax_page_group($_POST['id'], $group);
		pg_query($dbconnect, $query);
		var_dump($query);
	}

	#var_dump($_POST);
	header('Location: http://localhost/accelerator/preptasks.php?page=-1#');
?>
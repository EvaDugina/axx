<?php

	require_once("common.php");
	require_once("dbqueries.php");
	require_once("utilities.php");
	
	$id = $_POST['id'];
	
	if ($_POST['id'] != 0) {
	
		$query = update_discipline($_POST);
		$result = pg_query($dbconnect, $query);
		
		$query = delete_page_prep($_POST['id']);
		$result = pg_query($dbconnect, $query);
		
		$query = delete_page_group($_POST['id']);
		$result = pg_query($dbconnect, $query);
	} else {
		
		$query = insert_discipline($_POST);
		$result = pg_query($dbconnect, $query);
		$id = pg_fetch_all($result)[0]['id'];
	}
	/*
	echo "<pre>";
	var_dump($_POST);
	echo "</pre>";
	echo "<pre>";
	var_dump($id);
	echo "</pre>";
	*/
	foreach($_POST['teachers'] as $teacher)
	{
		$pos= strpos($teacher, ' ');
		$first_name = substr($teacher, 0, $pos);
		$middle_name = substr($teacher, $pos+1);
		$query = prep_ax_prep_page($id, $first_name, $middle_name);
		pg_query($dbconnect, $query);
	}
	
	foreach($_POST['groups'] as $group)
	{
		$query = update_ax_page_group($id, $group);
		pg_query($dbconnect, $query);
	}

	var_dump($id);
	header('Location: mainpage.php');
?>

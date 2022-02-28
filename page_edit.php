<?php

	require_once("common.php");
	require_once("dbqueries.php");
	require_once("utilities.php");
	
	$id = $_POST['id'];

	$flag = true;

	$timestamp = $short_name = $year = "";
	$semester_Err = $short_name_Err = "";
	if($_SERVER['REQUEST_METHOD'] == 'POST') {	
		$timestamp = convert_timestamp_from_string($_POST['timestamp']);
    	$short_name = pg_escape_string($_POST['short_name']);
		$year = pg_escape_string($timestamp['year']);
	}
	
	if (empty($timestamp) || empty($year)) {
		//echo "<b>ВЫБЕРИТЕ СЕМЕСТР!!!</b><br>";
		$semester_Err = "ВЫБЕРИТЕ СЕМЕСТР!!!";
		$flag = false;
	}

	if (empty($short_name)) {
		//echo "<b>ЗАПОЛНИТЕ СТРОКУ КРАТКОГО НАЗВАНИЯ!!!</b><br>";
		$short_name_Err = "ЗАПОЛНИТЕ СТРОКУ КРАТКОГО НАЗВАНИЯ!!!";
		$flag = false;
	}

	if (!$flag) {
		include './pageedit.php';
	}

	else {
	
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
		foreach($_POST['teachers'] as $teacher) {
			$pos = strpos($teacher, ' ');
			$first_name = substr($teacher, 0, $pos);
			$middle_name = substr($teacher, $pos+1);
			$query = prep_ax_prep_page($id, $first_name, $middle_name);
			pg_query($dbconnect, $query);
		}
	
		foreach($_POST['groups'] as $group) {
			$query = update_ax_page_group($id, $group);
			pg_query($dbconnect, $query);
		}

		var_dump($id);
		header('Location: mainpage.php');
	}
?>

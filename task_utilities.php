<?php
	
	require_once("common.php");
	require_once("dbqueries.php");
	
	if($_POST['type'] == 1){
		$query = select_task_file(2, $_POST['task_id']);
		$result = pg_query($dbconnect, $query);
		$file = pg_fetch_all($result);
		if(empty($file))
			$query = insert_file(2, $_POST['task_id'], "test.cpp", $_POST['full_text_test']);
		else
			$query = update_file(2, $_POST['task_id'], $_POST['full_text_test']);
		
		$result = pg_query($dbconnect, $query);
		

		$query = select_task_file(3, $_POST['task_id']);
		$result = pg_query($dbconnect, $query);
		$file = pg_fetch_all($result);		
		if(empty($file))
			$query = insert_file(3, $_POST['task_id'], "checktest.cpp", $_POST['full_text_test_of_test']);
		else
			$query = update_file(3, $_POST['task_id'], $_POST['full_text_test_of_test']);
		
		$result = pg_query($dbconnect, $query);
	}
	
	$query = update_task($_POST['task_id'], $_POST['type'], $_POST['title'], $_POST['description']);
	$result = pg_query($dbconnect, $query);
	$file = pg_fetch_all($result);
	
	header('Location: index.php');
?>
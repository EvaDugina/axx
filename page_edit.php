<?php

require_once("common.php");
require_once("dbqueries.php");
require_once("utilities.php");

$au = new auth_ssh();
if ($au->isAdmin());
else if ($au->isTeacher());
else {
	$au->logout();
	header('Location:login.php');
}

function delete_discipline($discipline_id){
	return 'DELETE FROM ax_page WHERE id ='.$discipline_id;
}
	

if (isset($_POST['action'])) {
	$action = $_POST['action'];
	var_dump($_POST);
	switch($action){
		case 'save':
			if (isset($_POST['id']) && $_POST['id'] != 0) {
				$query = update_discipline($_POST);
				$result = pg_query($dbconnect, $query);
		
				$query = delete_page_prep($_POST['id']);
				$result = pg_query($dbconnect, $query);
		
				$query = delete_page_group($_POST['id']);
				$result = pg_query($dbconnect, $query);
			} 
			else {
				$query = insert_discipline($_POST);
				$result = pg_query($dbconnect, $query);
				$id = pg_fetch_all($result)[0]['id'];
			}

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
			break;
		case 'delete':
			var_dump($_POST['id']);
			$query = delete_page_prep($_POST['id']);
			$result = pg_query($dbconnect, $query);
		
			$query = delete_page_group($_POST['id']);
			$result = pg_query($dbconnect, $query);

			$query = delete_discipline($_POST['id']);
			$result = pg_query($dbconnect, $query);
			break;
		default:
			echo "Error: action";
		break;

	}
	header('Location: mainpage.php');
}
?>

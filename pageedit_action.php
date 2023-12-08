<?php

require_once("common.php");
require_once("dbqueries.php");
require_once("utilities.php");

$au = new auth_ssh();
if ($au->isAdminOrPrep());
else {
	$au->logout();
	header('Location:login.php');
}



function delete_discipline($discipline_id)
{
	return 'DELETE FROM ax.ax_page WHERE id =' . $discipline_id;
}

if (isset($_POST['flag-addColorTheme']) && isset($_POST['page_id']) && isset($_FILES['image-file'])) {
	addFileToColorTheme($_POST['page_id'], $_FILES['image-file']['name'], $_FILES['image-file']['tmp_name'], 22);
	exit;
}

if (isset($_POST['flag-deleteColorTheme']) && isset($_POST['color_theme_id'])) {
	deleteColorThemeFromDB($_POST['color_theme_id'], getColorThemeSrcUrlById($_POST['color_theme_id']));
	exit;
}

if (isset($_POST['flag-createPage'])) {
	$Page = new Page($au->getUserId(), null);
	$return_json = array("page_id" => $Page->id);
	echo json_encode($return_json);
	exit;
}

if (isset($_POST['action'])) {
	$action = $_POST['action'];
	var_dump($_POST);
	switch ($action) {
		case 'save':
			if (isset($_POST['id']) && $_POST['id'] != 0) {
				$id = $_POST['id'];

				$query = update_discipline($_POST);
				$result = pg_query($dbconnect, $query);

				$query = delete_page_prep($_POST['id']);
				$result = pg_query($dbconnect, $query);

				$query = delete_page_group($_POST['id']);
				$result = pg_query($dbconnect, $query);
			} else {
				$query = insert_page($_POST);
				$result = pg_query($dbconnect, $query);
				$id = pg_fetch_all($result)[0]['id'];
			}

			if (isset($_POST['teachers'])) {
				echo '</br></br>';
				print_r($_POST['teachers']);
				foreach ($_POST['teachers'] as $teacher) {
					// echo '</br>';
					// $pos = explode(" ", $teacher);
					// $first_name = $pos[0];
					// $middle_name = "";
					// if (isset($pos[1]))
					//   $middle_name = $pos[1];
					// //echo $first_name .' ' . $middle_name;  
					// //echo $teacher;
					// $query = prep_ax_prep_page($id, $first_name, $middle_name);
					$query = addTeacherToPage($id, (int)$teacher);
					pg_query($dbconnect, $query);
				}
			}

			if (isset($_POST['groups'])) {
				foreach ($_POST['groups'] as $group) {
					$query = addGroupToPage($id, (int)$group);
					pg_query($dbconnect, $query);
				}
			}

			break;

		case 'delete':
			var_dump($_POST['id']);
			$Page = new Page($_POST['id']);
			$Page->deleteFromDB();

			break;
		default:
			echo "Error: action";
			break;
	}
	if (isset($_POST['status-backLocation']) && $_POST['status-backLocation'] == "page")
		header('Location: pageedit.php?page=' . $_POST['id']);
	else
		header('Location: mainpage.php');
}

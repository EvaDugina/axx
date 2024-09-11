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

if (isset($_POST['flag-addColorTheme']) && isset($_POST['page_id']) && isset($_FILES['image-file'])) {
	addFileToColorTheme($_POST['page_id'], $_FILES['image-file']['name'], $_FILES['image-file']['tmp_name'], 22);
	exit;
}

if (isset($_POST['flag-deleteColorTheme']) && isset($_POST['color_theme_id'])) {
	$ColorTheme = new ColorTheme((int)$_POST['color_theme_id']);
	$ColorTheme->deleteFromDB();
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
	$status = False;
	switch ($action) {
		case 'save':
			if (isset($_POST['id']) && $_POST['id'] != 0) {
				$id = $_POST['id'];

				$query = update_discipline($_POST);
				$result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

				$query = delete_page_prep($_POST['id']);
				$result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

				$query = delete_page_group($_POST['id']);
				$result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
			} else {
				$query = insert_page($_POST);
				echo $query;
				$result = pg_query($dbconnect, $query);
				$id = pg_fetch_all($result)[0]['id'] or die('Ошибка запроса: ' . pg_last_error());
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
					pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
				}
			}

			if (isset($_POST['groups'])) {
				foreach ($_POST['groups'] as $group) {
					$query = addGroupToPage($id, (int)$group);
					pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
				}
			}

			$status = True;
			break;

		case "download":
			downloadPage((int)$_POST['id']);
			$status = True;
			break;

		case 'delete':
			var_dump($_POST['id']);
			$Page = new Page($_POST['id']);
			$Page->deleteFromDB();

			$status = True;
			break;
		default:
			echo "Error: Некорректный action";
			break;
	}

	if ($status) {
		if (isset($_POST['status-backLocation']) && $_POST['status-backLocation'] == "page")
			header('Location: pageedit.php?page=' . $_POST['id']);
		else
			header('Location: mainpage.php');
	}
}


// 
// FUNCTIONS
// FUNCTIONS
// 

function downloadPage($page_id)
{
	$tmp_file_dir = getUploadFileDir() . time() . "_";

	$Page = new Page($page_id);

	$zipPage = new ZipArchive();
	$zip_file_path = $tmp_file_dir . "$Page->id.zip";
	if ($zipPage->open($zip_file_path, ZipArchive::CREATE) !== TRUE) {
		exit("Невозможно открыть <$zip_file_path>");
	}

	// $directory_path = "Page_$Page->id/";
	// if ($zipPage->addEmptyDir($directory_path)) {
	$TaskFiles = getAllTasksAsFiles($tmp_file_dir, $Page);
	foreach ($TaskFiles as $TaskFileName) {
		$zipPage->addFile($tmp_file_dir . $TaskFileName, $TaskFileName);
	}
	// } else {
	// echo 'Could not create the directory';
	// }
	$zipPage->close();

	if (!file_exists($zip_file_path)) {
		exit("Архива не существует");
	}

	ob_clean();

	header('Content-Type: application/zip');
	header('Content-disposition: attachment; filename=Page_' . $Page->id . ".zip");
	header('Content-Length: ' . filesize($zip_file_path));
	readfile($zip_file_path);

	unlink($zip_file_path);

	foreach ($TaskFiles as $TaskFileName) {
		unlink($tmp_file_dir . $TaskFileName);
	}

	exit();
}

function getAllTasksAsFiles($tmp_file_dir, $Page)
{
	$comparison_file_name = "Соответствия.txt";
	$comparison_file = fopen($tmp_file_dir . $comparison_file_name, "w") or die("Unable to open file!");
	foreach ($Page->getTasks() as $Task) {
		$title = addslashes($Task->title);
		fwrite($comparison_file, "$Task->id - $title\r\n");
	}
	fclose($comparison_file);

	$TaskFiles = [$comparison_file_name];
	foreach ($Page->getTasks() as $Task) {
		$main_file_name = "Task_$Task->id.md";
		$main_file = fopen($tmp_file_dir . $main_file_name, "w") or die("Unable to open file!");
		$text = $Task->title . "\r\n\r\n" . $Task->description;
		fwrite($main_file, $text);
		fclose($main_file);

		array_push($TaskFiles, $main_file_name);

		foreach ($Task->getFiles() as $File) {
			$file_name = $File->name_without_prefix;
			$str_pos = strpos($file_name, "accel_");
			if (($File->isCodeTest() || $File->isCodeCheckTest()) && ($str_pos !== false)) {
				$file_name = str_replace("accel_", "", $file_name);
			}
			$file_code_name = "Task_$Task->id" . "_$file_name";
			$file_code = fopen($tmp_file_dir . $file_code_name, "w") or die("Unable to open file!");
			$text = $File->getFullText();
			fwrite($file_code, $text);
			fclose($file_code);
			array_push($TaskFiles, $file_code_name);
		}
	}
	return $TaskFiles;
}

function unlinkFiles() {}


function delete_discipline($discipline_id)
{
	return 'DELETE FROM ax.ax_page WHERE id =' . $discipline_id;
}

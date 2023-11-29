<?php
require_once("settings.php");

function check_file_uploaded_name($filename)
{
	$allowed_symbols  = ((preg_match("`^[-0-9A-Z_\.]+$`i", $filename)) ? true : false);
	if (!$allowed_symbols)
		echo 'File "' . $filename . '" name contains incorrect symbols<br>';

	$name_length = ((mb_strlen($filename, "UTF-8") < 225) ? true : false);
	if (!$name_length)
		echo 'File "' . $filename . '" name is too long<br>';

	$allowed_extension = in_array(pathinfo($filename)['extension'], array('c', 'cpp', 'h', 'hpp', '', 'make', 'qmake', 'res', 'json'));
	if (!$allowed_extension)
		echo 'File "' . $filename . '" extension is incorrect<br>';

	return (bool) ($allowed_symbols && $name_length && $allowed_extension);
}

if (count($_REQUEST) < 1) {
?>
	<html>
	<header>
		<title>Accelerator API</title>
	</header>

	<body>
		<h1>Accelerator API</h1>
		<form action="api.php" method="POST" enctype="multipart/form-data">
			Task <input type="text" name="task_id" value="0" /> (not supported yet)<br>
			Assignment <input type="text" name="assignment_id" value="0" /><br>
			<br>
			<input type="checkbox" name="fc" value="on" checked /> Compile<br>
			<input type="checkbox" name="fh" value="on" checked /> CppCheck<br>
			<input type="checkbox" name="ff" value="on" checked /> Clang<br>
			<input type="checkbox" name="fv" value="on" checked /> Valgrind<br>
			<input type="checkbox" name="ft" value="on" checked /> Autotest<br>
			<input type="checkbox" name="fp" value="on" checked /> CopyDetect (not supported yet)<br>
			<br>
			Config <input type="file" name="config" /><br>
			<br>
			Source1 <input type="file" name="sources1" /><br>
			Source2 <input type="file" name="sources2" /><br>
			Source3 <input type="file" name="sources3" /><br>
			Source4 <input type="file" name="sources4" /><br>
			Source5 <input type="file" name="sources5" /><br>
			<br>
			AutoTest1 <input type="file" name="tests1" /><br>
			AutoTest2 <input type="file" name="tests2" /><br>
			AutoTest3 <input type="file" name="tests3" /><br>
			AutoTest4 <input type="file" name="tests4" /><br>
			AutoTest5 <input type="file" name="tests5" /><br>
			<br>
			<input type="submit" />
		</form>
	</body>

	</html>
<?php
	http_response_code(400);
	exit;
}

$task_id = (array_key_exists('task_id', $_REQUEST)) ? $REQUEST['task_id'] : 0;
$assignment_id = (array_key_exists('assignment_id', $_REQUEST)) ? $REQUEST['assignment_id'] : 0;
$flag_compile = (array_key_exists('fc', $_REQUEST)) ? ($REQUEST['fc'] == 'on') : false;
$flag_cppcheck = (array_key_exists('fh', $_REQUEST)) ? ($REQUEST['fh'] == 'on') : false;
$flag_clang = (array_key_exists('ff', $_REQUEST)) ? ($REQUEST['ff'] == 'on') : false;
$flag_valgrind = (array_key_exists('fv', $_REQUEST)) ? ($REQUEST['fv'] == 'on') : false;
$flag_autotest = (array_key_exists('ft', $_REQUEST)) ? ($REQUEST['ft'] == 'on') : false;
$flag_plagiat = false;
// $flag_plagiat = (array_key_exists('fp', $_REQUEST)) ?($REQUEST['fp']=='on') :false;
$flag_all = (array_key_exists('fa', $_REQUEST)) ? ($REQUEST['fa'] == 'on') : false;
if ($flag_all)
	$flag_compile = $flag_cppcheck = $flag_clang = $flag_valgrind = $flag_autotest = $flag_plagiat = true;

foreach ($_REQUEST as $k => $v) {
	if (!in_array($k, array('task_id', 'assignment_id', 'fc', 'fh', 'ff', 'fv', 'ft', 'fp', 'fa'))) {
		echo "Unknown parameter: " . $k . "=" . $v;
		http_response_code(400);
		exit;
	}
}

if (count($_FILES) < 1) {
	echo "Input files missing";
	http_response_code(400);
	exit;
}

if (!array_key_exists('config', $_FILES)) {
	echo "Config file missing";
	http_response_code(400);
	exit;
}
if ($_FILES['config']['error'] != UPLOAD_ERR_OK) {
	echo 'Error uploading config file';
	http_response_code(400);
	exit;
}
if ($_FILES['config']['size'] > 1048576) {
	echo 'File "' . $_FILES['config']['name'] . '" is too big';
	http_response_code(400);
	exit;
}
if (!check_file_uploaded_name($_FILES['config']['name'])) {
	http_response_code(400);
	exit;
}
$checks = "";
$myfile = fopen($_FILES['config']['tmp_name'], "r");
if (!$myfile || !($checks = fread($myfile, filesize($_FILES['config']['tmp_name'])))) {
	echo "Unable to read config file";
	http_response_code(500);
	exit;
}
fclose($myfile);
$checks = json_decode($checks, true);
if (($checks == null)
	|| !array_key_exists('tools', $checks)
	|| !array_key_exists('build', $checks['tools'])
	|| !array_key_exists('cppcheck', $checks['tools'])
	|| !array_key_exists('clang-format', $checks['tools'])
	|| !array_key_exists('valgrind', $checks['tools'])
	|| !array_key_exists('autotests', $checks['tools'])
	|| !array_key_exists('copydetect', $checks['tools'])
) {
	echo "Incorrect data in config file: <br>";
	var_dump($checks);
	http_response_code(400);
	exit;
}

$checks['tools']['build']['enabled'] = $flag_compile;

$checks['tools']['cppcheck']['enabled'] = $flag_cppcheck;
$checks['tools']['cppcheck']['bin'] = "cppcheck";

$checks['tools']['clang-format']['enabled'] = $flag_clang;
$checks['tools']['clang-format']['bin'] = "clang-format";
$checks['tools']['clang-format']['check']['file'] = ".clang-format";

$checks['tools']['valgrind']['enabled'] = $flag_valgrind;
$checks['tools']['valgrind']['bin'] = "valgrind";

$checks['tools']['autotests']['enabled'] = $flag_autotest;

$checks['tools']['copydetect']['enabled'] = $flag_plagiat;
$checks['tools']['copydetect']['bin'] = "copydetect";
$checks['tools']['copydetect']['check']['reference_directory'] = "copydetect_input";

$sid = session_id();
$folder = "/var/app/share/" . (($sid == false) ? "unknown" : $sid);
if (!file_exists($folder))
	mkdir($folder, 0777, true);

//echo "Folder: $folder";

// autotest files
$test_input = array();
if (!$flag_autotest)
	$checks['tools']['autotests']['test_path'] = "";
else if (array_key_exists('tests1', $_FILES)) {
	for ($i = 1; $i < 100; $i++) {
		if (!array_key_exists('tests' . $i, $_FILES) || $_FILES['tests' . $i]['name'] == "")
			break;
		if ($_FILES['tests' . $i]['error'] != UPLOAD_ERR_OK) {
			echo 'Error uploading file "' . $_FILES['tests' . $i]['name'] . '"';
			http_response_code(400);
			exit;
		}
		if ($_FILES['tests' . $i]['size'] > 1048576) {
			echo 'File "' . $_FILES['tests' . $i]['name'] . '" is too big';
			http_response_code(400);
			exit;
		}
		if (!check_file_uploaded_name($_FILES['tests' . $i]['name'])) {
			http_response_code(400);
			exit;
		}
		// ����������� ����
		if (!move_uploaded_file($_FILES['tests' . $i]['tmp_name'], $folder . '/' . $_FILES['tests' . $i]['name'])) {
			echo 'Error retrieving file "' . $_FILES['tests' . $i]['name'] . '"';
			http_response_code(400);
			exit;
		}
		array_push($test_input, $_FILES['tests' . $i]['name']);
	}
	$checks['tools']['autotests']['test_path'] = implode(' ', $test_input);
} else {
	// ��������� ������ ������ �� ��
	if ($assignment_id == 0) {
		echo 'Autotests are not uploaded and assignment is not defined';
		http_response_code(400);
		exit;
	}

	$result = pg_query($dbconnect,  "SELECT f.* from ax.ax_file f INNER JOIN ax.ax_task_file ON ax.ax_task_file.file_id = f.id inner join ax.ax_assignment a on ax.ax_task_file.task_id = a.task_id where f.type = 2 and a.id = " . $assignment_id);
	$result = pg_fetch_assoc($result);
	if (!$result) {
		echo 'Error reading autotest files for assignment ' . $assignment_id . ' from db';
		http_response_code(400);
		exit;
	}
	do {
		$testfile = $result["file_name"];
		@unlink($folder . '/' . $result['file_name']);
		$myfile = fopen($folder . '/' . $result['file_name'], "w");
		if (!$myfile || !fwrite($myfile, $result['full_text'])) {
			echo 'Unable to write file "' . $result['file_name'] . '"!';
			http_response_code(500);
			exit;
		}
		fclose($myfile);
		array_push($test_input, $testfile);
	} while ($result = pg_fetch_assoc($result));
	//$test_input = "accel_autotest.cpp";
	$checks['tools']['autotests']['test_path'] = implode(' ', $test_input);
}

// config.json
$checks = json_encode($checks);
$myfile = fopen($folder . '/config.json', "w");
if (!$myfile || !fwrite($myfile, $checks)) {
	echo 'Unable to write file "config.json"!';
	http_response_code(500);
	exit;
}
fclose($myfile);

// source files
$src_input = array();
if (array_key_exists('sources1', $_FILES)) {
	for ($i = 1; $i < 100; $i++) {
		if (!array_key_exists('sources' . $i, $_FILES) || $_FILES['sources' . $i]['name'] == "")
			break;
		if ($_FILES['sources' . $i]['error'] != UPLOAD_ERR_OK) {
			echo 'Error uploading file "' . $_FILES['sources' . $i]['name'] . '"';
			http_response_code(400);
			exit;
		}
		if ($_FILES['sources' . $i]['size'] > 1048576) {
			echo 'File "' . $_FILES['sources' . $i]['name'] . '" is too big';
			http_response_code(400);
			exit;
		}
		if (!check_file_uploaded_name($_FILES['sources' . $i]['name'])) {
			http_response_code(400);
			exit;
		}
		// ����������� ����
		if (!move_uploaded_file($_FILES['sources' . $i]['tmp_name'], $folder . '/' . $_FILES['sources' . $i]['name'])) {
			echo 'Error retrieving file ' . $_FILES['sources' . $i]['tmp_name'] . ' -- "' . $_FILES['sources' . $i]['name'] . '"';
			http_response_code(400);
			exit;
		}
		array_push($src_input, $_FILES['sources' . $i]['name']);
	}
	$src_input = implode(' ', $src_input);
} else {
	echo 'No source files supplied';
	http_response_code(400);
	exit;
}

// copy detect
/*
	@unlink($folder.'/copydetect_input');
	mkdir($folder.'/copydetect_input', 0777, true);
	$prev_files = get_prev_files($assignment);
	foreach($prev_files as $pf) {
      $cyr = ['�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�', '�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�', '�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�', '�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�'
        ];
      $lat = ['a','b','v','g','d','e','io','zh','z','i','y','k','l','m','n','o','p', 'r','s','t','u','f','h','ts','ch','sh','sht','a','i','y','e','yu','ya', 'A','B','V','G','D','E','Io','Zh','Z','I','Y','K','L','M','N','O','P', 'R','S','T','U','F','H','Ts','Ch','Sh','Sht','A','I','Y','e','Yu','Ya'
        ];
      $transname = str_replace($cyr, $lat, $pf["name"]);	  
	  
	  $myfile = fopen($folder.'/copydetect_input/'.$transname, "w");
	  if (!$myfile) {
		echo "������ �������� ������ ��� ��������";
		http_response_code(500);
		exit;
	  }
	  fwrite($myfile, $pf["text"]);
	  fclose($myfile);
	}
	*/

@unlink($folder . '/output.json');

$output = null;
$retval = null;
exec('docker run --net=host --rm -v ' . $folder . ':/tmp -v /var/app/utility:/stable -w=/tmp nitori_sandbox codecheck -c config.json ' . $src_input . ' ' . $test_input . ' 2>&1', $output, $retval);

// ��������� ����������� �������� �� ����� 
$myfile = fopen($folder . '/output.json', "r");
if (!$myfile || !($responce = fread($myfile, filesize($folder . '/output.json')))) {
	echo "Unable to retrieve check results:<br>";
	var_dump($output);
	http_response_code(500);
	exit;
}
fclose($myfile);
echo $responce;

header('Content-Type: application/json');

?>
<!DOCTYPE html>
<html lang="en">

<?php
require_once("common.php");
require_once("dbqueries.php");
require_once("utilities.php");

$assignment_id = 0;
if (isset($_GET['assignment']) && isset($_GET['file'])) {
  $assignment_id = $_GET['assignment'];
  $file_id = $_GET['file'];
} else {
  //echo "Некорректное обращение";
  //http_response_code(400);
  header('Location: index.php');
  exit;
}

$result = pg_query($dbconnect, 'select file_name, full_text from ax_solution_file where id = '.$file_id);
$result = pg_fetch_all($result);
if (count($result) > 0)
{
  $filename = $result[0]['file_name'];
  $fulltext = $result[0]['full_text'];
}

file_put_contents('../plate/tested/'.$filename, $fulltext);
shell_exec('/var/bin/copydetect -t ../plate/tested -r ../plate/222 -a -O ../plate/report.html');
shell_exec('rm ../plate/tested/'.$filename);

header('Location: /plate/report.html');

?>
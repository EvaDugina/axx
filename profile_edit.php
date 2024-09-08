<?php
require_once("common.php");
require_once("dbqueries.php");
require_once("utilities.php");
require_once("POClasses/User.class.php");


if (isset($_POST['changeSubgroup'])) {
  $User = new User((int)$_POST['student_id']);
  $subgroup = (int)$_POST['subgroup'];
  $User->setSubgroup($subgroup);
  echo json_encode(array("subgroup" => $subgroup));
  exit;
}

$User = new User((int)$au->getUserId());
if (isset($_FILES['image-file']) && isset($_POST['set-image'])) {
  addFileToObject($User, $_FILES['image-file']['name'], $_FILES['image-file']['tmp_name'], 21);
  header('Location: profile.php');
  exit;
}

if (isset($_POST['email'])) {
  $User->email = $_POST['email'];
}

if (isset($_POST['github_url'])) {
  $User->github_url = $_POST['github_url'];
}

if (isset($_POST['checkbox_notify'])) {
  $User->notify_status = 1;
} else { // тк. если чекбокс не 'ON' он не передаётся методом POST
  $User->notify_status = 0;
}

$User->pushSettingChangesToDB();

header('Location: profile.php');

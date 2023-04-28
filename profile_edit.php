<?php
require_once("common.php");
require_once("dbqueries.php");
require_once("utilities.php");
require_once("POClasses/User.class.php");

$user = new User((int)$_SESSION['hash']);


if (isset($_POST['email'])){
  $user->email = $_POST['email'];
}


if (isset($_POST['checkbox_notify'])){
  $user->notify_status = 1;

} else { // тк. если чекбокс не 'ON' он не передаётся методом POST
  $user->notify_status = 0;
}

$user->pushSettingChangesToDB();

header('Location: profile.php');
?>


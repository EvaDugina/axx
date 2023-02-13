<?php
require_once("common.php");
require_once("dbqueries.php");
require_once("utilities.php");
require_once("POClasses\User.class.php");

$user = new User($_SESSION['hash']);


if (isset($_POST['email'])){
  $user->setEmail($_POST['email']);
}


if (isset($_POST['checkbox_notify'])){
  $user->setNotificationStatus(1);

} else { // тк. если чекбокс не 'ON' он не передаётся методом POST
  $user->setNotificationStatus(0);
}

header('Location: profile.php');
?>


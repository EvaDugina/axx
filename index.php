<?php
//session_start();
include_once('auth_ssh.class.php');
$au = new auth_ssh();
if ($au->isAdminOrPrep())
    header('Location:mainpage.php');
else if ($au->loggedIn())
    header('Location:mainpage_student.php');
else
    header('Location:login.php');

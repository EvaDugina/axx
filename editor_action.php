<?php
require_once("settings.php");
require_once("dbqueries.php");
require_once("utilities.php");
require_once("POClasses/Commit.class.php");

$au = new auth_ssh();
if (!$au->loggedIn()) {
    header('Location:login.php');
    exit();
}

if (isset($_POST['flag-deleteCommit'])) {
    if (isset($_POST['commit_id'])) {
        $Commit = new Commit($_POST['commit_id']);
        $Commit->deleteFromDB();
        exit();
    } else {
        exit();
    }
}

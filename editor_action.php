<?php
require_once("settings.php");
require_once("dbqueries.php");
require_once("utilities.php");
require("./resultparse.php");
require_once("POClasses/Commit.class.php");

$au = new auth_ssh();
if (!$au->loggedIn()) {
    header('Location:login.php');
    exit();
}

$User = new User((int)$au->getUserId());

if (isset($_POST['flag-deleteCommit'])) {
    if (isset($_POST['commit_id'])) {
        $Commit = new Commit($_POST['commit_id']);
        $Commit->deleteFromDB();
        exit();
    } else {
        exit();
    }
}

if (
    isset($_POST['flag']) && $_POST['flag'] == "flag-getToolsHtml"
    && isset($_POST['config-tools']) && isset($_POST['output-tools'])
) {
    $accord = getAccordionToolsHtml(json_decode($_POST['config-tools'], true), json_decode($_POST['output-tools'], true), $User);
    echo show_accordion('checkres', $accord, "5px");
    exit;
}

if (isset($_POST['flag']) && $_POST['flag'] == "flag-getEditorLanguages") {
    $json = json_encode(getEditorLanguages());
    echo $json;
    exit;
}

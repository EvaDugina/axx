<?php
require_once("./settings.php");
require_once("common.php");
require_once("utilities.php");
require_once("dbHandler.php");

$au = new auth_ssh();
checkAuLoggedIN($au);
checkAuIsNotStudent($au);

if (isset($_POST['flag-createDump'])) {
    $out = makeBackup();
    echo json_encode($out);
    exit;
}

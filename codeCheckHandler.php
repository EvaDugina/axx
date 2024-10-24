<?php
require("./utilities.php");
require("./resultparse.php");

$au = new auth_ssh();
checkAuLoggedIN($au);

$User = new User((int)$au->getUserId());

if (isset($_POST['flag']))
    $flag = $_POST['flag'];
else {
    echo "Некорректный запрос. Не известный тип операции";
    exit;
}

if ($flag == "flag-getToolsHtml" && isset($_POST['config-tools']) && isset($_POST['output-tools'])) {
    $accord = getAccordionToolsHtml(json_decode($_POST['config-tools'], true), json_decode($_POST['output-tools'], true), $User);
    echo show_accordion('checkres', $accord, "5px");
    exit;
}

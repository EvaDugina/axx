<?php
require_once("utilities.php");

$au = new auth_ssh();
checkAuLoggedIN($au);

if (isset($_POST['flag']))
  $flag = $_POST['flag'];
else {
  echo "Некорректный запрос. Не известный тип операции";
  exit;
}

if ($flag == "GetMarkMessage" && isset($_POST['mark'])) {
  echo getMessageAssignmentCompleted($_POST['mark']);
  exit;
}


// 
// 
// 
// 

function getMessageAssignmentCompleted($mark)
{
  if ($mark != "зачтено")
    return "Задание оценено! \nОценка: $mark";
  else
    return "Задание зачтено!";
}

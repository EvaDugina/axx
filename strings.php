<?php

function getMessageAssignmentCompleted($mark)
{
  if ($mark != "зачтено")
    return "Задание оценено! \nОценка: " . $mark;
  else
    return "Задание зачтено!";
}

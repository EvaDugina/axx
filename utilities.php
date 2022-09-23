<?php

//семестр по цифре
function convert_sem_from_id($id){
  if($id == 1) return 'Весна';
  else return 'Осень';
}


// Работа с TIMESTAMP
date_default_timezone_set('Europe/Moscow');

// год и номер семестра по названию
function convert_timestamp_from_string($str){
  $pos = strpos($str, "/");
  $year = substr($str, 0, $pos);
  $sem = substr($str, $pos+1);
  $sem_id = 0;

  if($sem == 'Весна') $sem_id = 1;
  else $sem_id = 2;

  return array('year' => $year, 'semester' => $sem_id);
}

function convert_timestamp_to_date($timestamp, $format = "d-m-Y") {
  return date($format, strtotime($timestamp));
}

function conver_calendar_to_timestamp($finish_limit) {
  $timestamp = strtotime($finish_limit);
  $timestamp = getdate($timestamp);
  $timestamp = date("Y-m-d H:i:s", mktime(23, 59, 59, $timestamp['mon'],$timestamp['mday'], $timestamp['year']));
  return $timestamp;
}

function get_now_date($format = "d-m-Y"){
  return date($format);
}

?>

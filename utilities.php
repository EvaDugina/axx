<?php

//семестр по цифре
function convert_sem_from_id($id){
if($id == 1) return 'Весна';
else return 'Осень';
}

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

?>

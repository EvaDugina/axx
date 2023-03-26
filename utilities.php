<?php
require_once("settings.php");
require_once("dbqueries.php");
require_once("messageHandler.php");
require_once("POClasses/Message.class.php");
require_once("POClasses/Task.class.php");


function getPGQuotationMarks() {
  return "\$antihype1\$";
}


// Работа с TIMESTAMP
date_default_timezone_set('Europe/Moscow');

function getNowTimestamp(){
  $timestamp = date("Y-m-d H:i", mktime(date("H"), date("i"), date("s"), date("m"), date("d"), date("Y")));
  return $timestamp;
}

function get_now_date($format = "d-m-Y"){
  return date($format);
}

// год и номер семестра по названию
function convert_timestamp_from_string($str){
  $pos = explode(" ", $str);
  $year = explode("/", $pos[0])[0];
  $sem = $pos[1];
  $sem_number = 0;

  if($sem == 'Осень') $sem_number = 1;
  else $sem_number = 2;
  // echo $sem_number;

  return array('year' => $year, 'semester' => $sem_number);
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

function convert_mtime($mtime){
  $time_date = explode(" ", $mtime);
  $date = explode("-", $time_date[0]);
  $time = explode(":", $time_date[1]);
  $time_date_output = $date[0] .".". $date[1] ." ". $time[0] .":". $time[1];
  return $time_date_output;
}


// [x]: Убрать, тк это есть в классе File
function add_random_prefix_to_file_name($real_file_name) {
  //return rand_prefix() . basename($real_file_name);
  return $real_file_name;
}
function delete_random_prefix_from_file_name($db_file_name) {
  // Декодирование названия файла
  // $split_array = preg_split('/_/', $db_file_name);
  // $decodedFileName = "";
  // for ($i = 1; $i < count($split_array); $i++) {
  //   $decodedFileName .= $split_array[$i];
  // }
  // return $decodedFileName;
  return $db_file_name;
}

// Генерация префикса для уникальности названий файлов, которые хранятся на сервере
function rand_prefix() {
  return time() . mt_rand(0, 9999) . mt_rand(0, 9999) . '_';
}
function delete_prefix($str) {
	return preg_replace('#[0-9]{0,}_#', '', $str, 1);
}



// ОБЩИЕ ФУНКЦИИ РАБОТЫ С ЗАДАНИЯМИ
function getTaskFiles($dbconnect, $task_id){
  $Task = new Task((int)$task_id);
  $Files = $Task->getFiles();
  
  $task_files = array();
  foreach ($Files as $File) {
    // Если текст файла лежит в БД
    if ($File->download_url == null) {
      $File->download_url = 'download_file.php?file_id=' . $File->id;
    }
    // Если файл лежит на сервере
    else if (!preg_match('#^http[s]{0,1}://#', $File->download_url)) {
      $File->download_url = 'download_file.php?file_path=' . $File->download_url;
    }
    $file_name = $File->name;
    array_push($task_files, array('id' => $File->id, 'type' => $File->type, 'file_name' => $file_name, 'download_url' => $File->download_url));
  }

  return $task_files;
}



function showAttachedFilesByMessageId($message_id){
  $message_files = get_message_attachments($message_id);
  $message_text = "</br>";
  if (count($message_files) > 0) { 
    foreach ($message_files as $file) { 
      $message_text .= "
      <a target='_blank' download href='" . $file['download_url'] . "'>
        <i class='fa fa-paperclip' aria-hidden='true'></i> " . 
        $file['file_name']. "
      </a><br/>";
    }
  }
  return $message_text;
}

  // Возвращает двумерный массив вложений для сообщения по message_id
  function get_message_attachments($message_id) {

    // TODO: ПРОВЕРИТЬ!
    $Message = new Message((int)$message_id);
    // $query = select_message_attachment($message_id);
    // $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    
    $files = [];
    foreach ($Message->getFiles() as $File) {
      // Если текст файла лежит в БД
      if ($File->download_url == null) {
        $File->download_url = 'download_file.php?file_id=' . $File->id;
      }
      // Если файл лежит на сервере
      else if (!preg_match('#^http[s]{0,1}://#', $File->download_url)) {
        if (strpos($File->download_url, 'editor.php') === false)
        $File->download_url = 'download_file.php?file_path=' . $File->download_url;
      }
      $files[] = ['id' => $File->id, 'file_name' => delete_prefix($File->name), 'download_url' => $File->download_url];
    }
    // for ($row = pg_fetch_assoc($result); $row; $row = pg_fetch_assoc($result)) {
    //   // Если текст файла лежит в БД
    //   if ($row['download_url'] == null) {
    //     $row['download_url'] = 'download_file.php?attachment_id=' . $row['id'];
    //   }
    //   // Если файл лежит на сервере
    //   else if (!preg_match('#^http[s]{0,1}://#', $row['download_url'])) {
    //     if (strpos($row['download_url'], 'editor.php') === false)
    //       $row['download_url'] = 'download_file.php?file_path=' . $row['download_url'] . '&with_prefix=';
    //   }
    //   $messages[] = ['id' => $row['id'], 'file_name' => delete_prefix($row['file_name']), 'download_url' => $row['download_url']];
    // }
    return $files;
  }
  

// Выводит прикрепленные к странице с заданием файлы
function show_task_files($task_files, $taskedit_page_status = false, $task_id = null, $page_id = null) {?>
    
    <?php $count_files = 0;
    foreach ($task_files as $f) {
        $count_files++; ?>
        <div class="btn btn-outline-primary d-inline-flex justify-content-between align-items-center my-1 px-3 div-task-file" style="cursor:unset;">
          <a id="a-file-<?=$f['id']?>" href="<?=$f['download_url']?>" target="_blank" class="d-inline-flex justify-content-between align-items-center">
            <?php if ($f['type'] == 0) {?>
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-fill" viewBox="0 0 16 16">
                <path d="M4 0h5.293A1 1 0 0 1 10 .293L13.707 4a1 1 0 0 1 .293.707V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2zm5.5 1.5v2a1 1 0 0 0 1 1h2l-3-3z"/>
              </svg>
            <?php } else if ($f['type'] == 1) {?>
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-filetype-md" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M14 4.5V14a2 2 0 0 1-2 2H9v-1h3a1 1 0 0 0 1-1V4.5h-2A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v9H2V2a2 2 0 0 1 2-2h5.5L14 4.5ZM.706 13.189v2.66H0V11.85h.806l1.14 2.596h.026l1.14-2.596h.8v3.999h-.716v-2.66h-.038l-.946 2.159h-.516l-.952-2.16H.706Zm3.919 2.66V11.85h1.459c.406 0 .741.078 1.005.234.263.157.46.383.589.68.13.297.196.655.196 1.075 0 .422-.066.784-.196 1.084-.131.301-.33.53-.595.689-.264.158-.597.237-1 .237H4.626Zm1.353-3.354h-.562v2.707h.562c.186 0 .347-.028.484-.082a.8.8 0 0 0 .334-.252 1.14 1.14 0 0 0 .196-.422c.045-.168.067-.365.067-.592a2.1 2.1 0 0 0-.117-.753.89.89 0 0 0-.354-.454c-.159-.102-.362-.152-.61-.152Z"/>
              </svg>
            <?php } else if ($f['type'] == 2) {?>
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-binary-fill" viewBox="0 0 16 16">
                <path d="M5.526 10.273c-.542 0-.832.563-.832 1.612 0 .088.003.173.006.252l1.559-1.143c-.126-.474-.375-.72-.733-.72zm-.732 2.508c.126.472.372.718.732.718.54 0 .83-.563.83-1.614 0-.085-.003-.17-.006-.25l-1.556 1.146z"/>
                <path d="M9.293 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.707A1 1 0 0 0 13.707 4L10 .293A1 1 0 0 0 9.293 0zM9.5 3.5v-2l3 3h-2a1 1 0 0 1-1-1zm-2.45 8.385c0 1.415-.548 2.206-1.524 2.206C4.548 14.09 4 13.3 4 11.885c0-1.412.548-2.203 1.526-2.203.976 0 1.524.79 1.524 2.203zm3.805 1.52V14h-3v-.595h1.181V10.5h-.05l-1.136.747v-.688l1.19-.786h.69v3.633h1.125z"/>
              </svg>
            <?php } else {?>
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-medical-fill" viewBox="0 0 16 16">
                <path d="M9.293 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.707A1 1 0 0 0 13.707 4L10 .293A1 1 0 0 0 9.293 0zM9.5 3.5v-2l3 3h-2a1 1 0 0 1-1-1zm-3 2v.634l.549-.317a.5.5 0 1 1 .5.866L7 7l.549.317a.5.5 0 1 1-.5.866L6.5 7.866V8.5a.5.5 0 0 1-1 0v-.634l-.549.317a.5.5 0 1 1-.5-.866L5 7l-.549-.317a.5.5 0 0 1 .5-.866l.549.317V5.5a.5.5 0 1 1 1 0zm-2 4.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1 0-1zm0 2h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1 0-1z"/>
              </svg>
            <?php }?>

            &nbsp;<?=$f['file_name']?>&nbsp;&nbsp;
          </a>
          <?php // Если запрос на отображение файлов приходит со страницы taskedit
          if ($taskedit_page_status) {
            special_for_taskedit($f, $task_id, $page_id);
          }?>
        </div>
        
    <?php }
    if ($count_files == 0) {
      //echo 'Файлы отсутсвуют<br>';
    }?>
<?php }


function special_for_taskedit($f, $task_id, $page_id){?>

  <form id="form-statusTaskFiles" action="taskedit_action.php" method="POST" enctype="multipart/form-data" 
  class="d-inline-flex justify-content-between align-items-center form-statusTaskFiles">
    <input type="hidden" name="task_id" value="<?=$task_id?>"></input>
    <input type="hidden" name="page_id" value="<?=$page_id?>"></input>
    <input type="hidden" name="file_id" value="<?=$f['id']?>"></input>
    <input type="hidden" name="flag-statusFile" value="true"></input>
    
    <select id="select-statusTaskFile" class="form-select me-2 select-statusTaskFile" id="select-statusFile">
      <?php 
	    $captions = array('вложение', 'исходный код');
	    for($i=0; $i<=1; $i++) {?>
        <option value="<?=$i?>" <?php if($i == $f['type']) echo 'selected';?>>
          <?=$captions[$i]?>
        </option>
      <?php }?>
    </select>
  </form>

  <form name="deleteTaskFiles" action="taskedit_action.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="task_id" value="<?=$task_id?>"></input>
    <input type="hidden" name="page_id" value="<?=$page_id?>"></input>
    <input type="hidden" name="file_id" value="<?=$f['id']?>"></input>
    <input type="hidden" name="flag-deleteFile" value="true"></input>

    <button class="btn btn-link bg-danger text-white me-0 p-1" type="submit">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-lg" viewBox="0 0 16 16">
      <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8 2.146 2.854Z"/>
    </svg>
    </button>
  </form>
<?php }


function createDownloadURL($File) {
  if ($File->download_url == null) {
    return 'download_file.php?file_id=' . $File->id;
  }

  // Если файл лежит на сервере
  else if (!preg_match('#^http[s]{0,1}://#', $File->download_url)) {
    if (strpos($File->download_url, 'editor.php') === false)
    return 'download_file.php?file_path=' . $File->download_url;
  }

  // Такого не может быть
  else {
    return null;
  }
}

function showFiles($Files, $taskedit_page_status = false, $task_id = null, $page_id = null) {
  $count_files = 0;
    foreach ($Files as $File) {
        $count_files++; ?>
        <div class="btn btn-outline-primary d-inline-flex justify-content-between align-items-center my-1 px-3 div-task-file" style="cursor:unset;">
          <a id="a-file-<?=$File->id?>" href="<?=createDownloadURL($File)?>" target="_blank" class="d-inline-flex justify-content-between align-items-center">
            <?php if ($File->type == 0) {?>
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-fill" viewBox="0 0 16 16">
                <path d="M4 0h5.293A1 1 0 0 1 10 .293L13.707 4a1 1 0 0 1 .293.707V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2zm5.5 1.5v2a1 1 0 0 0 1 1h2l-3-3z"/>
              </svg>
            <?php } else if ($File->type == 1) {?>
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-filetype-md" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M14 4.5V14a2 2 0 0 1-2 2H9v-1h3a1 1 0 0 0 1-1V4.5h-2A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v9H2V2a2 2 0 0 1 2-2h5.5L14 4.5ZM.706 13.189v2.66H0V11.85h.806l1.14 2.596h.026l1.14-2.596h.8v3.999h-.716v-2.66h-.038l-.946 2.159h-.516l-.952-2.16H.706Zm3.919 2.66V11.85h1.459c.406 0 .741.078 1.005.234.263.157.46.383.589.68.13.297.196.655.196 1.075 0 .422-.066.784-.196 1.084-.131.301-.33.53-.595.689-.264.158-.597.237-1 .237H4.626Zm1.353-3.354h-.562v2.707h.562c.186 0 .347-.028.484-.082a.8.8 0 0 0 .334-.252 1.14 1.14 0 0 0 .196-.422c.045-.168.067-.365.067-.592a2.1 2.1 0 0 0-.117-.753.89.89 0 0 0-.354-.454c-.159-.102-.362-.152-.61-.152Z"/>
              </svg>
            <?php } else if ($File->type == 2) {?>
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-binary-fill" viewBox="0 0 16 16">
                <path d="M5.526 10.273c-.542 0-.832.563-.832 1.612 0 .088.003.173.006.252l1.559-1.143c-.126-.474-.375-.72-.733-.72zm-.732 2.508c.126.472.372.718.732.718.54 0 .83-.563.83-1.614 0-.085-.003-.17-.006-.25l-1.556 1.146z"/>
                <path d="M9.293 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.707A1 1 0 0 0 13.707 4L10 .293A1 1 0 0 0 9.293 0zM9.5 3.5v-2l3 3h-2a1 1 0 0 1-1-1zm-2.45 8.385c0 1.415-.548 2.206-1.524 2.206C4.548 14.09 4 13.3 4 11.885c0-1.412.548-2.203 1.526-2.203.976 0 1.524.79 1.524 2.203zm3.805 1.52V14h-3v-.595h1.181V10.5h-.05l-1.136.747v-.688l1.19-.786h.69v3.633h1.125z"/>
              </svg>
            <?php } else if ($File->type == 3){?>
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-medical-fill" viewBox="0 0 16 16">
                <path d="M9.293 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.707A1 1 0 0 0 13.707 4L10 .293A1 1 0 0 0 9.293 0zM9.5 3.5v-2l3 3h-2a1 1 0 0 1-1-1zm-3 2v.634l.549-.317a.5.5 0 1 1 .5.866L7 7l.549.317a.5.5 0 1 1-.5.866L6.5 7.866V8.5a.5.5 0 0 1-1 0v-.634l-.549.317a.5.5 0 1 1-.5-.866L5 7l-.549-.317a.5.5 0 0 1 .5-.866l.549.317V5.5a.5.5 0 1 1 1 0zm-2 4.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1 0-1zm0 2h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1 0-1z"/>
              </svg>
            <?php } else if ($File->type == 10) { ?>
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-medical-fill" viewBox="0 0 16 16">
                <path d="M9.293 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.707A1 1 0 0 0 13.707 4L10 .293A1 1 0 0 0 9.293 0zM9.5 3.5v-2l3 3h-2a1 1 0 0 1-1-1zm-3 2v.634l.549-.317a.5.5 0 1 1 .5.866L7 7l.549.317a.5.5 0 1 1-.5.866L6.5 7.866V8.5a.5.5 0 0 1-1 0v-.634l-.549.317a.5.5 0 1 1-.5-.866L5 7l-.549-.317a.5.5 0 0 1 .5-.866l.549.317V5.5a.5.5 0 1 1 1 0zm-2 4.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1 0-1zm0 2h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1 0-1z"/>
              </svg>
            <?php } else if ($File->type == 11) { ?>
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-medical-fill" viewBox="0 0 16 16">
                <path d="M9.293 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.707A1 1 0 0 0 13.707 4L10 .293A1 1 0 0 0 9.293 0zM9.5 3.5v-2l3 3h-2a1 1 0 0 1-1-1zm-3 2v.634l.549-.317a.5.5 0 1 1 .5.866L7 7l.549.317a.5.5 0 1 1-.5.866L6.5 7.866V8.5a.5.5 0 0 1-1 0v-.634l-.549.317a.5.5 0 1 1-.5-.866L5 7l-.549-.317a.5.5 0 0 1 .5-.866l.549.317V5.5a.5.5 0 1 1 1 0zm-2 4.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1 0-1zm0 2h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1 0-1z"/>
              </svg>
            <?php }?>

            &nbsp;<?=$File->name_without_prefix?>&nbsp;&nbsp;
          </a>
          <?php // Если запрос на отображение файлов приходит со страницы taskedit
          if ($taskedit_page_status) {
            specialForTaskeditPage($File, $task_id);
          }?>
        </div>
        
    <?php }
    if ($count_files == 0) {
      //echo 'Файлы отсутсвуют<br>';
    }
}

function specialForTaskeditPage($File, $task_id){?>

  <form id="form-statusTaskFiles" action="taskedit_action.php" method="POST" enctype="multipart/form-data" 
  class="d-inline-flex justify-content-between align-items-center form-statusTaskFiles">
    <input type="hidden" name="task_id" value="<?=$task_id?>"></input>
    <input type="hidden" name="file_id" value="<?=$File->id?>"></input>
    <input type="hidden" name="flag-statusFile" value="true"></input>
    
    <select id="select-statusTaskFile" class="form-select me-2 select-statusTaskFile" id="select-statusFile">
      <?php 
	    $captions = array('вложение', 'исходный код');
	    for($i=0; $i<=1; $i++) {?>
        <option value="<?=$i?>" <?php if($i == $File->type) echo 'selected';?>>
          <?=$captions[$i]?>
        </option>
      <?php }?>
    </select>
  </form>

  <form id="form-deleteTaskFile" name="deleteTaskFiles" action="taskedit_action.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="task_id" value="<?=$task_id?>"></input>
    <input type="hidden" name="file_id" value="<?=$File->id?>"></input>
    <input type="hidden" name="flag-deleteFile" value="true"></input>

    <button class="btn btn-link bg-danger text-white me-0 p-1" type="submit">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-lg" viewBox="0 0 16 16">
      <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8 2.146 2.854Z"/>
    </svg>
    </button>
  </form>
<?php }


function checkTask($assignment_id, $mark) {
  global $dbconnect;
  $query = update_ax_assignment_mark($assignment_id, $mark);
	pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
}


// ПРОЧЕЕ
function convert_sem_from_number($id){
  if($id == 1) return 'Осень';
  else return 'Весна';
}

function show_accordion($name, $data, $labelshift = "0px")
{
			?>
	            <div id="main-accordion-<?=$name?>" class="accordion accordion-flush" style="overflow-y: auto; height: 100%px; width: 100%;">
				  <div class="accordion-item">
			<?php
				$i = 111;
				foreach($data as $d) {
			?>
					<div id="accordion-<?=$name?>-gheader-<?=$i?>" class="accordion-header border">
			<?php
					if (array_key_exists('label', $d)) {
			?>
					  <div style="position:absolute;z-index:2;margin-left:<?=$labelshift?>;">
					    <div style="position:relative;top:4px;">
					      <?=$d['label']?>
						</div>
					  </div>
			<?php
					}
			?>
					  <button class="accordion-button p-1 collapsed" type="button" data-mdb-toggle="collapse" data-mdb-target="#accordion-<?=$name?>-collapse-<?=$i?>" aria-expanded="true" aria-controls="accordion-<?=$name?>-collapse-<?=$i?>" style="z-index:1;">
                        <div class="form-check d-flex w-100">
						  <?=$d['header']?>
                        </div>                   
                      </button>
<!--
  					  <div style="position:relative;">
					    <input id="common_enabled" class="accordion-input-item form-check-input" type="checkbox" value="1" name="common_enabled" checked style="margin-left: 16.7em!important;">
					    <label class="form-check-label" for="common_enabled" style="color:#4f4f4f;">выполнять проверки</label>
					    <input id="common_show" class="accordion-input-item form-check-input ms-5" type="checkbox" value="1" name="common_show" checked>
					    <label class="form-check-label" for="common_show" style="color:#4f4f4f;">отображать студенту</label>
					  </div>
-->
                    </div>
					
	                <div id="accordion-<?=$name?>-collapse-<?=$i?>" class="accordion-collapse collapse" aria-labelledby="accordion-<?=$name?>-gheader-<?=$i?>" data-mdb-parent="#main-accordion-<?=$name?>">
                      <div class="accordion-body">
						<div id="group-accordion-<?=$name?>" class="accordion accordion-flush">
		  			      <div id="item-from-<?=$name?>-group-<?=$i?>" class="accordion-item">
							<div id="accordion-<?=$name?>-sheader-<?=$i?>" class="accordion-header">
                              <div class="d-flex justify-content-between" type="button">
								<div class="form-check ms-3" style="width:100%;">
                                  <?=$d['body']?>
								</div>
							  </div>
							</div>
						  </div>
						</div>
					  </div>
					</div>

			<?php
				  $i++;
				}				
			?>
			      </div>
                </div>
			<?php
}

?>


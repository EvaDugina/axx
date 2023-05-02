<?php
require_once("settings.php");
require_once("dbqueries.php");
require_once("messageHandler.php");
require_once("POClasses/Message.class.php");
require_once("POClasses/Task.class.php");
include_once('auth_ssh.class.php');


// защита от случайного перехода незарегистрированного пользователя
function checkAuLoggedIN($au) {
  if (!$au->loggedIn()) {
    header('Location:login.php');
    exit;
  }
}

// защита от случайного перехода студента
function checkAuIsNotStudent($au){
  if (!$au->isAdmin() && !$au->isTeacher()){
    $au->logout();
    header('Location:login.php');
  }
}


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

function checkPHPDateForDateFields($time_limit) {
  $defaultDate = date("Y-m-d", strtotime("1970-01-01"));
  if ($time_limit == $defaultDate) {
    return "";
  }
  return $time_limit;
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

// FIXME: Неверно конвертирует, прибавляет час
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
  $time_date_output = $time[0] .":". $time[1] . " " . $date[0] .".". $date[1] .".". $date[2];
  return $time_date_output;
}




function showAttachedFilesByMessageId($message_id){
  $Message = new Message((int)$message_id);
  $message_text = "";

  foreach ($Message->getFiles() as $File) {
    $message_text .= "
      <a target='_blank' download href='" . $File->getDownloadLink() . "'>
        <i class='fa fa-paperclip' aria-hidden='true'></i> " . 
        $File->name_without_prefix. "
      </a><br/>";
  }

  return $message_text;
}

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


function showFiles($Files, $taskedit_page_status = false, $task_id = null, $page_id = null) {
  $count_files = 0;
  $au = new auth_ssh();
    foreach ($Files as $File) {
      if($au->isAdminOrTeacher() || $File->isVisible()) {
        $count_files++; ?>
          <div class="btn btn-outline-primary d-inline-flex justify-content-between align-items-center my-1 px-3 div-task-file" style="cursor:unset;">
            <?php // Если запрос на отображение файлов приходит со страницы taskedit
            if ($taskedit_page_status) {
              visibilityFileButtons($File);
            }?>
            <a id="a-file-<?=$File->id?>" href="<?=$File->getDownloadLink()?>" target="_blank" class="d-inline-flex justify-content-between align-items-center">
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
    }
    if ($count_files == 0) {
      //echo 'Файлы отсутсвуют<br>';
    }
}?>

<?php
function visibilityFileButtons($File) {?>
  <form id="form-changeVisibilityTaskFile" name="changeVisibilityTaskFile" action="taskedit_action.php" 
  method="POST" enctype="multipart/form-data" class="me-2">
    <input type="hidden" name="file_id" value="<?=$File->id?>"></input>
    <input type="hidden" name="editFileVisibility" value="true"></input>
    <input type="hidden" name="new_visibility" value="<?=($File->visibility+1)%2?>"></input>

    <button class="btn btn-primary me-0 p-1" type="submit"
    data-toggle="tooltip" data-placement="down" 
    data-title="Изменение ВИДИМОСТИ файла">
      <?php getSVGByAssignmentVisibility($File->visibility*2);?>
    </button>
  </form>
<?php } ?>

<?php
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
<?php }?>


<?php
function getSVGByAssignmentVisibility($status) {
  switch($status){
		case 0:?>
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye-slash-fill" viewBox="0 0 16 16">
        <path d="m10.79 12.912-1.614-1.615a3.5 3.5 0 0 1-4.474-4.474l-2.06-2.06C.938 6.278 0 8 0 8s3 5.5 8 5.5a7.029 7.029 0 0 0 2.79-.588zM5.21 3.088A7.028 7.028 0 0 1 8 2.5c5 0 8 5.5 8 5.5s-.939 1.721-2.641 3.238l-2.062-2.062a3.5 3.5 0 0 0-4.474-4.474L5.21 3.089z"/>
        <path d="M5.525 7.646a2.5 2.5 0 0 0 2.829 2.829l-2.83-2.829zm4.95.708-2.829-2.83a2.5 2.5 0 0 1 2.829 2.829zm3.171 6-12-12 .708-.708 12 12-.708.708z"/>
      </svg>
      <?php 
      break;
    case 2:?>
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye-fill" viewBox="0 0 16 16">
        <path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0z"/>
        <path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8zm8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"/>
      </svg>
      <?php 
      break;
    case 4:?>
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-octagon-fill" viewBox="0 0 16 16">
        <path d="M11.46.146A.5.5 0 0 0 11.107 0H4.893a.5.5 0 0 0-.353.146L.146 4.54A.5.5 0 0 0 0 4.893v6.214a.5.5 0 0 0 .146.353l4.394 4.394a.5.5 0 0 0 .353.146h6.214a.5.5 0 0 0 .353-.146l4.394-4.394a.5.5 0 0 0 .146-.353V4.893a.5.5 0 0 0-.146-.353L11.46.146zm-6.106 4.5L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 1 1 .708-.708z"/>
      </svg>
      <?php 
      break;
    default:
      break;
  }
}
function getSVGByAssignmentVisibilityAsText($visibility) {
  switch($visibility){
    case 0:
      return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye-slash-fill" viewBox="0 0 16 16">
      <path d="m10.79 12.912-1.614-1.615a3.5 3.5 0 0 1-4.474-4.474l-2.06-2.06C.938 6.278 0 8 0 8s3 5.5 8 5.5a7.029 7.029 0 0 0 2.79-.588zM5.21 3.088A7.028 7.028 0 0 1 8 2.5c5 0 8 5.5 8 5.5s-.939 1.721-2.641 3.238l-2.062-2.062a3.5 3.5 0 0 0-4.474-4.474L5.21 3.089z"/>
      <path d="M5.525 7.646a2.5 2.5 0 0 0 2.829 2.829l-2.83-2.829zm4.95.708-2.829-2.83a2.5 2.5 0 0 1 2.829 2.829zm3.171 6-12-12 .708-.708 12 12-.708.708z"/>
    </svg>';
      break;
    case 2:
      return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye-fill" viewBox="0 0 16 16">
      <path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0z"/>
      <path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8zm8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"/>
    </svg>';
      break;
    case 4:
      return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-octagon-fill" viewBox="0 0 16 16">
      <path d="M11.46.146A.5.5 0 0 0 11.107 0H4.893a.5.5 0 0 0-.353.146L.146 4.54A.5.5 0 0 0 0 4.893v6.214a.5.5 0 0 0 .146.353l4.394 4.394a.5.5 0 0 0 .353.146h6.214a.5.5 0 0 0 .353-.146l4.394-4.394a.5.5 0 0 0 .146-.353V4.893a.5.5 0 0 0-.146-.353L11.46.146zm-6.106 4.5L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 1 1 .708-.708z"/>
    </svg>';
      break;
    default:
      break;
  }
}


function getSVGByAssignmentStatus ($status) {
  switch($status){
    case -1:?>
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-lock-fill" viewBox="0 0 16 16">
        <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/>
      </svg>
      <?php 
      break;
    case 0:?>
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-unlock-fill" viewBox="0 0 16 16">
        <path d="M11 1a2 2 0 0 0-2 2v4a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h5V3a3 3 0 0 1 6 0v4a.5.5 0 0 1-1 0V3a2 2 0 0 0-2-2z"/>
      </svg>
      <?php 
      break;
    case 1:?>
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clock-fill" viewBox="0 0 16 16">
        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
      </svg>
      <?php 
      break;
    case 2:?>
    
      <?php 
      break;
    case 3:?>
    
      <?php 
      break;
    case 4:?>
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-lg" viewBox="0 0 16 16">
        <path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425a.247.247 0 0 1 .02-.022Z"/>
      </svg>
      <?php 
      break;
    default:
      break;
  }
}
function getSVGByAssignmentStatusAsText($status) {
  switch($status){
    case -1:
      return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-lock-fill" viewBox="0 0 16 16">
        <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/>
      </svg>';
      break;
    case 0:
      return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-unlock-fill" viewBox="0 0 16 16">
        <path d="M11 1a2 2 0 0 0-2 2v4a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h5V3a3 3 0 0 1 6 0v4a.5.5 0 0 1-1 0V3a2 2 0 0 0-2-2z"/>
      </svg>';
      break;
    case 1:
      return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clock-fill" viewBox="0 0 16 16">
        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
      </svg>';
      break;
    case 2:
      break;
    case 3:
      break;
    case 4:
      return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-lg" viewBox="0 0 16 16">
        <path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425a.247.247 0 0 1 .02-.022Z"/>
      </svg>';
      break;
    default:
      break;
  }
}?>


<?php
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
                    <?=@$d['footer']?>
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

function str2bool($str = '')
{
	return ($str == "true") ? true : false;
}

function str2int($str = '')
{
	if (settype($str, "integer")) 
		return $str;
	else
		return 0;
}

?>
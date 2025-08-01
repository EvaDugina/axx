<?php
require_once("./settings.php");
require_once("dbqueries.php");
require_once("POClasses/Message.class.php");
require_once("POClasses/Task.class.php");
require_once('auth_ssh.class.php');


// защита от случайного перехода незарегистрированного пользователя
function checkAuLoggedIN($au)
{
  if (!$au->loggedIn()) {
    header('Location:login.php');
    exit;
  }
}

// защита от случайного перехода студента
function checkAuIsNotStudent($au)
{
  if (!$au->isAdminOrPrep()) {
    $au->logout();
    header('Location:login.php');
  }
}


function getPGQuotationMarks()
{
  return "\$antihype1\$";
}

function getEditorLanguages()
{
  return [
    "CPP" => [
      "cmd" => 'make',
      "exts" => ['cpp', 'h'],
      "name" => 'C++',
      "monaco_editor_name" => 'cpp'
    ],
    "C" => [
      "cmd" => 'make',
      "exts" => ['c'],
      "name" => 'C',
      "monaco_editor_name" => 'c'
    ],
    "PYTHON" => [
      "cmd" => 'python3 main.py',
      "exts" => ['py'],
      "name" => 'Python',
      "monaco_editor_name" => 'python'
    ],
    "DEFAULT" => [
      "cmd" => null,
      "exts" => [],
      "name" => 'Text',
      "monaco_editor_name" => 'plaintext'
    ]
  ];
}

function getLanguagesChecksParams()
{
  return [
    "CPP" => ["checks_preset" => getGNUChecksPreset()],
    "C" => ["checks_preset" => getGNUChecksPreset()],
    "PYTHON" => ["checks_preset" => getPYChecksPreset()],
    "DEFAULT" => ["checks_preset" => getDefaultChecksPreset()],
  ];
}



// Работа с TIMESTAMP

date_default_timezone_set('Europe/Moscow');

function getNowTimestamp()
{
  $timestamp = date("Y-m-d H:i", mktime(date("H"), date("i"), date("s"), date("m"), date("d"), date("Y")));
  return $timestamp;
}

function convertServerDateTimeToCurrent($date_time, $format = "Y-m-d H:i")
{
  // global $date_time_offset;
  if ($date_time == null)
    return null;
  // Смещал время на некоторое количество часов, тк на сервере время не совпадало с реальным
  // return date($format, strtotime($date_time . " - $date_time_offset hour"));

  $timestamp = new DateTime($date_time);

  // Конвертация в формат
  return $timestamp->format($format);
  // return $timestamp->getTimestamp();

  // return date($format, strtotime($date_time));
}

function convertCurrentDateTimeToServer($date_time, $format = "Y-m-d H:i")
{
  global $date_time_offset;
  return date($format, strtotime($date_time . " + $date_time_offset hour"));
}

function get_now_date($format = "d-m-Y")
{
  return date($format);
}

function checkIfDefaultDate($time_limit)
{
  $defaultDate = date("Y-m-d", strtotime("1970-01-01"));
  if ($time_limit == $defaultDate) {
    return "";
  }
  return $time_limit;
}

// год и номер семестра по названию
function convert_timestamp_from_string($str)
{
  $pos = explode(" ", $str);
  $year = explode("/", $pos[0])[0];
  $sem_number = convert_number_from_sem($pos[1]);
  // echo $sem_number;

  return array('year' => $year, 'semester' => $sem_number);
}

// FIXME: Неверно конвертирует, прибавляет час
function convert_timestamp_to_date($timestamp, $format = "d-m-Y")
{
  return date($format, strtotime($timestamp));
}

function conver_calendar_to_timestamp($finish_limit)
{
  $timestamp = strtotime($finish_limit);
  $timestamp = getdate($timestamp);
  $timestamp = date("Y-m-d H:i:s", mktime(23, 59, 59, $timestamp['mon'], $timestamp['mday'], $timestamp['year']));
  return $timestamp;
}

function convert_mtime($mtime)
{
  $time_date = explode(" ", $mtime);
  $date = explode("-", $time_date[0]);
  $time = explode(":", $time_date[1]);
  $time_date_output = "<strong>" . $time[0] . ":" . $time[1] . "</strong>" /*. ":" . $time[2]*/ . " " . $date[0] . "." . $date[1] . "." . $date[2];
  return $time_date_output;
}

function getConvertedDateTime($db_date)
{
  $date_time = explode(" ", $db_date);
  $date = explode("-", $date_time[0]);
  $time = explode(":", $date_time[1]);
  $date_time = $date[2] . "." . $date[1] . "." . $date[0] . " " . $time[0] . ":" . $time[1];
  return $date_time;
}

function getTextWithTagBrAfterLines($text)
{
  $array = preg_split("/\r\n|\n|\r/", $text);
  $text_with_br = "";
  for ($i = 0; $i < count($array) - 1; $i++) {
    $text_with_br .= $array[$i];
    if ($array[$i] != "")
      $text_with_br .= "</br>";
  }
  $text_with_br .= $array[count($array) - 1];
  return $text_with_br;
}




function showAttachedFilesByMessageId($message_id)
{
  $Message = new Message((int)$message_id);
  $message_text = "";

  foreach ($Message->getFiles() as $File) {
    $message_text .= "<a target='_blank' href='" . $File->getDownloadLink() . "'>";
    $message_text .= "<p class='p-0 m-0'>" . getStringSVGByFileType($File->type);
    $message_text .= $File->name_without_prefix . "</p>";
    $message_text .= "</a>";
  }

  return $message_text;
}

function showFiles($Files, $taskedit_page_status = false, $task_id = null, $page_id = null)
{
  $count_files = 0;
  $au = new auth_ssh();
  foreach ($Files as $File) {
    // if ($taskedit_page_status && ($File->isCodeTest() || $File->isCodeCheckTest()))
    //   continue;
    if ($au->isAdminOrPrep() || $File->isVisible()) {
      $count_files++; ?>
      <div id="div-taskFile-<?= $File->id ?>" class="btn btn-outline-primary d-inline-flex justify-content-between align-items-center my-1 mx-1 px-3 div-task-file" style="cursor:unset;">
        <?php // Если запрос на отображение файлов приходит со страницы taskedit
        if ($taskedit_page_status) {
          visibilityFileButtons($File, $task_id);
        } ?>
        <a id="a-file-<?= $File->id ?>" href="<?= $File->getDownloadLink() ?>" target="_blank" class="d-inline-flex justify-content-between align-items-center">
          <span id="span-fileType-<?= $File->id ?>">
            <?= getSVGByFileType($File->type) ?>
          </span>
          &nbsp;<span data-title="<?= $File->name_without_prefix ?>"><?= getCompressedFileName($File->name_without_prefix, 40) ?></span>&nbsp;&nbsp;
        </a>

        <?php // Если запрос на отображение файлов приходит со страницы taskedit
        if ($taskedit_page_status) {
          specialForTaskeditPage($File, $task_id);
        } ?>
      </div>

    <?php }
  }

  if ($count_files == 0) {
    //echo "Файлы отсутсвуют<br>";
  }
}

function showTaskchatFiles($Files)
{
  $count_files = 0;
  $au = new auth_ssh();
  foreach ($Files as $File) {
    if ($File->type == 0) {
      $count_files++; ?>
      <div class="btn btn-outline-primary d-inline-flex justify-content-between align-items-center my-1 ms-0 me-1 px-3 div-task-file" style="cursor:unset;">
        <a id="a-file-<?= $File->id ?>" href="<?= $File->getDownloadLink() ?>" target="_blank" class="d-inline-flex justify-content-between align-items-center">

          <?php getSVGByFileType($File->type) ?>

          &nbsp;<span data-title="<?= $File->name_without_prefix ?>"><?= getCompressedFileName($File->name_without_prefix, 40) ?></span>&nbsp;&nbsp;
        </a>
      </div>
    <?php } ?>
    <?php }
}



function showMessageFiles($Files, $isAuthor = false)
{
  $count_files = 0;
  $au = new auth_ssh();
  foreach ($Files as $File) {
    if ($au->isAdminOrPrep() || $File->isVisible()) {
      $count_files++; ?>
      <?php if (in_array($File->getExt(), getImageFileTypes())) { ?>
        <img src="<?= $File->download_url ?>" class="rounded w-100 mb-1" alt="...">
      <?php } else { ?>
        <div class="btn btn-outline-primary d-inline-flex justify-content-between align-items-center my-1 <?= ($isAuthor) ? "me-0 ms-1" : "me-1 ms-0" ?> px-3 div-task-file" style="cursor:unset;">
          <a id="a-file-<?= $File->id ?>" href="<?= $File->getDownloadLink() ?>" target="_blank" class="d-inline-flex justify-content-between align-items-center" onclick="event.stopPropagation()">
            <?= getSVGByFileType($File->type) ?>
            &nbsp;<span data-title="<?= $File->name_without_prefix ?>"><?= getCompressedFileName($File->name_without_prefix, 40) ?></span>&nbsp;&nbsp;
          </a>
        </div>
      <?php } ?>

  <?php }
  }
}

function visibilityFileButtons($File, $task_id)
{ ?>

  <div class="me-2">
    <button id="btn-chnageFileVisibility-<?= $File->id ?>" class="btn btn-primary me-0 p-1" type="submit" data-toggle="tooltip" data-placement="down" data-title="Изменение ВИДИМОСТИ файла" visibility="<?= $File->visibility ?>" <?= ($File->isCodeTest() || $File->isCodeCheckTest()) ? "disabled" : "" ?> onclick="changeFileVisibility(<?= $task_id ?>, <?= $File->id ?>)">
      <?php getSVGByAssignmentVisibility($File->visibility * 2); ?>
    </button>
  </div>

<?php } ?>

<?php
function specialForTaskeditPage($File, $task_id)
{ ?>

  <div class="d-inline-flex justify-content-between align-items-center form-statusTaskFiles">
    <select id="select-taskFileType-<?= $File->id ?>" class="form-select me-2 select-taskFileType" onchange="changeFileType(event, <?= $task_id ?>, <?= $File->id ?>, '<?= $File->name_without_prefix ?>')">
      <?php
      $captions = array("вложение", "исходный код", "код теста", "код проверки теста");
      for ($i = 0; $i < count($captions); $i++) { ?>
        <option value="<?= $i ?>" <?php if ($i == $File->type) echo "selected"; ?>>
          <?= $captions[$i] ?>
        </option>
      <?php } ?>
    </select>
  </div>

  <div>

    <button class="btn btn-link bg-danger text-white me-0 p-1" type="button" onclick="deleteFile(<?= $task_id ?>, <?= $File->id ?>, '<?= $File->name_without_prefix ?>')">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-lg" viewBox="0 0 16 16">
        <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8 2.146 2.854Z" />
      </svg>
    </button>
  </div>

<?php } ?>


<?php
function getSVGByAssignmentVisibility($status)
{
  switch ($status) {
    case 0: ?>
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye-slash-fill" viewBox="0 0 16 16">
        <path d="m10.79 12.912-1.614-1.615a3.5 3.5 0 0 1-4.474-4.474l-2.06-2.06C.938 6.278 0 8 0 8s3 5.5 8 5.5a7.029 7.029 0 0 0 2.79-.588zM5.21 3.088A7.028 7.028 0 0 1 8 2.5c5 0 8 5.5 8 5.5s-.939 1.721-2.641 3.238l-2.062-2.062a3.5 3.5 0 0 0-4.474-4.474L5.21 3.089z" />
        <path d="M5.525 7.646a2.5 2.5 0 0 0 2.829 2.829l-2.83-2.829zm4.95.708-2.829-2.83a2.5 2.5 0 0 1 2.829 2.829zm3.171 6-12-12 .708-.708 12 12-.708.708z" />
      </svg>
    <?php
      break;
    case 2: ?>
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye-fill" viewBox="0 0 16 16">
        <path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0z" />
        <path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8zm8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z" />
      </svg>
    <?php
      break;
    case 4: ?>
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-octagon-fill" viewBox="0 0 16 16">
        <path d="M11.46.146A.5.5 0 0 0 11.107 0H4.893a.5.5 0 0 0-.353.146L.146 4.54A.5.5 0 0 0 0 4.893v6.214a.5.5 0 0 0 .146.353l4.394 4.394a.5.5 0 0 0 .353.146h6.214a.5.5 0 0 0 .353-.146l4.394-4.394a.5.5 0 0 0 .146-.353V4.893a.5.5 0 0 0-.146-.353L11.46.146zm-6.106 4.5L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 1 1 .708-.708z" />
      </svg>
    <?php
      break;
    default:
      break;
  }
}
function getSVGByAssignmentVisibilityAsText($visibility)
{
  switch ($visibility) {
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


function getSVGByAssignmentStatus($status)
{
  switch ($status) {
    case -2: ?>
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x" viewBox="0 0 16 16">
        <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708" />
      </svg>
    <?php
      break;
    case -1: ?>
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-lock-fill" viewBox="0 0 16 16">
        <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z" />
      </svg>
    <?php
      break;
    case 0: ?>
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-unlock-fill" viewBox="0 0 16 16">
        <path d="M11 1a2 2 0 0 0-2 2v4a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h5V3a3 3 0 0 1 6 0v4a.5.5 0 0 1-1 0V3a2 2 0 0 0-2-2z" />
      </svg>
    <?php
      break;
    case 1: ?>
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clock-fill" viewBox="0 0 16 16">
        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z" />
      </svg>
    <?php
      break;
    case 2: ?>
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-card-text" viewBox="0 0 16 16">
        <path d="M14.5 3a.5.5 0 0 1 .5.5v9a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h13zm-13-1A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2h-13z" />
        <path d="M3 5.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5zM3 8a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9A.5.5 0 0 1 3 8zm0 2.5a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 0 1h-6a.5.5 0 0 1-.5-.5z" />
      </svg>
    <?php
      break;
    case 3: ?>
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clock-fill" viewBox="0 0 16 16">
        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z" />
      </svg>
    <?php
      break;
    case 4: ?>
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-lg" viewBox="0 0 16 16">
        <path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425a.247.247 0 0 1 .02-.022Z" />
      </svg>
<?php
      break;
    default:
      break;
  }
}
function getSVGByAssignmentStatusAsText($status)
{
  switch ($status) {
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
} ?>


<?php
function checkTask($assignment_id, $mark)
{
  global $dbconnect;
  $query = update_ax_assignment_mark($assignment_id, $mark);
  pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
}


// ПРОЧЕЕ

function show_accordion($name, $data, $labelshift = "0px")
{
?>
  <div id="main-accordion-<?= $name ?>" class="accordion accordion-flush border rounded" style="overflow-y: auto; height: 100%px; width: 100%;">
    <div class="accordion-item">
      <?php
      $i = 111;
      foreach ($data as $d) {
      ?>
        <div id="accordion-<?= $name ?>-gheader-<?= $i ?>" class="accordion-header border px-2">
          <?php
          if (array_key_exists('label', $d)) {
          ?>
            <div style="position:absolute;z-index:2;margin-left:<?= $labelshift ?>;">
              <div style="position:relative;top:4px;">
                <?= $d['label'] ?>
              </div>
            </div>
          <?php
          }
          ?>
          <button class="accordion-button p-1 collapsed" type="button" data-mdb-toggle="collapse" data-mdb-target="#accordion-<?= $name ?>-collapse-<?= $i ?>" aria-expanded="true" aria-controls="accordion-<?= $name ?>-collapse-<?= $i ?>" style="z-index:1;">
            <div class="form-check d-flex w-100">
              <?= $d['header'] ?>
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
        <div id="accordion-<?= $name ?>-collapse-<?= $i ?>" class="accordion-collapse collapse p-2" aria-labelledby="accordion-<?= $name ?>-gheader-<?= $i ?>" data-mdb-parent="#main-accordion-<?= $name ?>">
          <div class="accordion-body">
            <div id="group-accordion-<?= $name ?>" class="accordion accordion-flush">
              <div id="item-from-<?= $name ?>-group-<?= $i ?>" class="accordion-item">
                <div id="accordion-<?= $name ?>-sheader-<?= $i ?>" class="accordion-header">
                  <div class="d-flex justify-content-between" type="button">
                    <div class="form-check ms-0 ps-0" style="width:100%;">
                      <strong><?= $d['body'] ?></strong>
                    </div>
                  </div>
                  <?= trim(@$d['footer']) ?>
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

function bool2str(bool $bool): string
{
  return $bool ? 'true' : 'false';
}


function getChangeLogHtml()
{
  include "./parsedown-1.7.4/Parsedown.php";

  $change_log_file_path = "./CHANGELOG.md";
  $change_log_md = file_get_contents($change_log_file_path);

  $Parsedown = new Parsedown();
  $changelog_html = $Parsedown->text($change_log_md);
  return $changelog_html;
}


// 
// 
// 
// 

function getDefaultChecksPreset()
{
  return '{
  "tools": {
    "build": {
      "enabled": false,
      "show_to_student": false,
      "language": "C++",
      "check": {
        "autoreject": true,
        "full_output": "output_build.txt",
        "outcome": "pass"
      },
      "outcome": "undefined"
    },
    "valgrind": {
      "enabled": false,
      "show_to_student": false,
      "bin": "valgrind",
      "arguments": "",
      "compiler": "gcc",
      "checks": [
        {
          "check": "errors",
          "enabled": true,
          "limit": 0,
          "autoreject": true,
          "result": 0,
          "outcome": "pass"
        },
        {
          "check": "leaks",
          "enabled": true,
          "limit": 0,
          "autoreject": true,
          "result": 5,
          "outcome": "fail"
        }
      ],
      "full_output": "output_valgrind.xml",
      "outcome": "undefined"
    },
    "cppcheck": {
      "enabled": false,
      "show_t_student": false,
      "bin": "cppcheck",
      "arguments": "",
      "checks": [
        {
          "check": "error",
          "enabled": true,
          "limit": 0,
          "autoreject": false,
          "result": 0,
          "outcome": "pass"
        },
        {
          "check": "warning",
          "enabled": true,
          "imit": 3,
          "autoreject": false,
          "result": 0,
          "outcome": "pass"
        },
        {
          "check": "style",
          "enabled": true,
          "limit": 3,
          "autoreject": false,
          "result": 2,
          "outcome": "pass"
        },
        {
          "check": "performance",
          "enabled": true,
          "limit": 2,
          "autoreject": false,
          "result": 0,
          "outcome": "pass"
        },
        {
          "check": "portability",
          "enabled": true,
          "limit": 0,
          "autoreject": false,
          "result": 0,
          "outcome": "pass"
        },
        {
          "check": "information",
          "enabed": true,
          "limit": 0,
          "autoreject": false,
          "result": 1,
          "outcome": "fail"
        },
        {
          "check": "unusedFunction",
          "enabled": true,
          "limit": 0,
          "autoreject": false,
          "result": 0,
          "outcome": "pass"
        },
        {
          "check": "missingnclude",
          "enabled": true,
          "limit": 0,
          "autoreject": false,
          "result": 0,
          "outcome": "pass"
        }
      ],
      "full_output": "output_cppcheck.xml",
      "outcome": "undefined"
    },
    "clang-format": {
      "enabled": false,
      "show_to_student": false,
      "bin": "clang-format",
      "arguments": "",
      "check": {
        "level": "strict",
        ".comment": "canbediffrentchecks,suchasstrict,less,minimalandsoon",
        "file": ".clang-format",
        "limit": 5,
        "autoreject": true,
        "result": 8,
        "outcome": "fail"
      },
      "full_output": "output_format.xml",
      "outcome": "undefined"
    },
    "pylint": {
      "enabled": false,
      "show_to_student": false,
      "bin": "pylint",
      "arguments": "",
      "checks": [
        {
          "check": "error",
          "enabled": true,
          "limit": 0,
          "autoreject": false,
          "result": 0,
          "outcome": "pass"
        },
        {
          "check": "warning",
          "enabled": true,
          "limit": 0,
          "autoreject": false,
          "result": 0,
          "outcome": "pass"
        },
        {
          "check": "refactor",
          "enabled": true,
          "limit": 3,
          "autoreject": false,
          "result": 0,
          "outcome": "pass"
        },
        {
          "check": "convention",
          "enabled": true,
          "limit": 3,
          "autoreject": false,
          "result": 0,
          "outcome": "pass"
        }
      ],
      "full_output": "output_pylint.xml",
      "outcome": "undefined"
    },
    "pytest": {
      "enabled": false,
      "show_to_student": false,
      "bin": "pytest",
      "arguments": "",
      "test_path": "autotest.py",
      "check": {
        "limit": 0,
        "autoreject": true,
        "error": 0,
        "failed": 0,
        "passed": 0,
        "seconds": 0,
        "outcome": "fail"
      },
      "full_output": "output_pytest.txt",
      "outcome": "undefined"
    },
    "copydetect": {
      "enabled": false,
      "show_to_student": false,
      "bin": "copydetect",
      "arguments": "",
      "check": {
        "type": "with_all",
        "limit": 50,
        "reference_directory": "copydetect",
        "autoreject": true,
        "result": 44,
        "outcome": "skip"
      },
      "full_output": "output_copydetect.html",
      "outcome": "undefined"
    },
    "catch2": {
      "enabled": false,
      "show_to_student": false,
      "test_path": "test_example.cpp",
      "check": {
        "limit": 0,
        "autoreject": true,
        "outcome": "skip",
        "errors": 0,
        "failures": 3
      },
      "full_output": "output_tests.txt",
      "outcome": "undefined"
    }
  }
}';
}

function getGNUChecksPreset()
{
  return '{
    "tools": {
      "build": {
        "enabled": true,
        "show_to_student": false,
        "language": "C++",
        "check": {
          "autoreject": true
        }
      },
      "valgrind": {
        "enabled": "true",
        "show_to_student": "false",
        "bin": "valgrind",
        "arguments": "",
        "compiler": "gcc",
        "checks": [
          {
            "check": "errors",
            "enabled": "true",
            "limit": "0",
            "autoreject": "false"
          },
          {
            "check": "leaks",
            "enabled": "true",
            "limit": "0",
            "autoreject": "false"
          }
        ]
      },
      "cppcheck": {
        "enabled": "true",
        "show_to_student": "false",
        "bin": "cppcheck",
        "arguments": "",
        "checks": [
          {
            "check": "error",
            "enabled": "true",
            "limit": "0",
            "autoreject": "false"
          },
          {
            "check": "warning",
            "enabled": "true",
            "limit": "3",
            "autoreject": "false"
          },
          {
            "check": "style",
            "enabled": "true",
            "limit": "3",
            "autoreject": "false"
          },
          {
            "check": "performance",
            "enabled": "true",
            "limit": "2",
            "autoreject": "false"
          },
          {
            "check": "portability",
            "enabled": "true",
            "limit": "0",
            "autoreject": "false"
          },
          {
            "check": "information",
            "enabled": "true",
            "limit": "0",
            "autoreject": "false"
          },
          {
            "check": "unusedFunction",
            "enabled": "true",
            "limit": "0",
            "autoreject": "false"
          },
          {
            "check": "missingInclude",
            "enabled": "true",
            "limit": "0",
            "autoreject": "false"
          }
        ]
      },
      "clang-format": {
        "enabled": "true",
        "show_to_student": "false",
        "bin": "clang-format",
        "arguments": "",
        "check": {
          "level": "strict",
          "file": "",
          "limit": "5",
          "autoreject": "true"
        }
      },
      "pylint": {
        "enabled": "false",
        "show_to_student": "false",
        "bin": "pylint",
        "arguments": "",
        "checks": [
          {
            "check": "error",
            "enabled": "true",
            "limit": "0",
            "autoreject": "false",
            "disableErrorCodes": []
          },
          {
            "check": "warning",
            "enabled": "true",
            "limit": "6",
            "autoreject": "false",
            "disableErrorCodes": []
          },
          {
            "check": "refactor",
            "enabled": "false",
            "limit": "3",
            "autoreject": "false",
            "disableErrorCodes": []
          },
          {
            "check": "convention",
            "enabled": "true",
            "limit": "7",
            "autoreject": "false",
            "disableErrorCodes": ["C0111", "C0112"]
          }
        ]
      },
      "pytest": {
        "enabled": "false",
        "show_to_student": "false",
        "bin": "pytest",
        "test_path": "autotest.py",
        "arguments": "",
        "check": {
          "limit": "0",
          "autoreject": "true"
        }
      },
      "copydetect": {
        "enabled": "false",
        "show_to_student": "false",
        "bin": "copydetect",
        "arguments": "",
        "check": {
          "type": "with_all",
          "limit": "80",
          "autoreject": "false"
        }
      },
      "catch2": {
        "enabled": false,
        "show_to_student": false,
        "test_path": "autotest.cpp",
        "check": {
          "limit": 0,
          "autoreject": true
        }
      }
    }
  }';
}

function getPYChecksPreset()
{
  return '{
    "tools": {
      "build": {
        "enabled": false,
        "show_to_student": false,
        "language": "C++",
        "check": {
          "autoreject": true
        }
      },
      "valgrind": {
        "enabled": "false",
        "show_to_student": "false",
        "bin": "valgrind",
        "arguments": "",
        "compiler": "gcc",
        "checks": [
          {
            "check": "errors",
            "enabled": "true",
            "limit": "0",
            "autoreject": "false"
          },
          {
            "check": "leaks",
            "enabled": "true",
            "limit": "0",
            "autoreject": "false"
          }
        ]
      },
      "cppcheck": {
        "enabled": "false",
        "show_to_student": "false",
        "bin": "cppcheck",
        "arguments": "",
        "checks": [
          {
            "check": "error",
            "enabled": "true",
            "limit": "0",
            "autoreject": "false"
          },
          {
            "check": "warning",
            "enabled": "true",
            "limit": "3",
            "autoreject": "false"
          },
          {
            "check": "style",
            "enabled": "true",
            "limit": "3",
            "autoreject": "false"
          },
          {
            "check": "performance",
            "enabled": "true",
            "limit": "2",
            "autoreject": "false"
          },
          {
            "check": "portability",
            "enabled": "true",
            "limit": "0",
            "autoreject": "false"
          },
          {
            "check": "information",
            "enabled": "true",
            "limit": "0",
            "autoreject": "false"
          },
          {
            "check": "unusedFunction",
            "enabled": "true",
            "limit": "0",
            "autoreject": "false"
          },
          {
            "check": "missingInclude",
            "enabled": "true",
            "limit": "0",
            "autoreject": "false"
          }
        ]
      },
      "clang-format": {
        "enabled": "false",
        "show_to_student": "false",
        "bin": "clang-format",
        "arguments": "",
        "check": {
          "level": "strict",
          "file": "",
          "limit": "5",
          "autoreject": "true"
        }
      },
      "pylint": {
        "enabled": "true",
        "show_to_student": "false",
        "bin": "pylint",
        "arguments": "",
        "checks": [
          {
            "check": "error",
            "enabled": "true",
            "limit": "0",
            "autoreject": "false",
            "disableErrorCodes": []
          },
          {
            "check": "warning",
            "enabled": "true",
            "limit": "6",
            "autoreject": "false",
            "disableErrorCodes": []
          },
          {
            "check": "refactor",
            "enabled": "false",
            "limit": "3",
            "autoreject": "false",
            "disableErrorCodes": []
          },
          {
            "check": "convention",
            "enabled": "true",
            "limit": "7",
            "autoreject": "false",
            "disableErrorCodes": ["C0111", "C0112"]
          }
        ]
      },
      "pytest": {
        "enabled": "true",
        "show_to_student": "false",
        "bin": "pytest",
        "test_path": "autotest.py",
        "arguments": "",
        "check": {
          "limit": "0",
          "autoreject": "true"
        }
      },
      "copydetect": {
        "enabled": "false",
        "show_to_student": "false",
        "bin": "copydetect",
        "arguments": "",
        "check": {
          "type": "with_all",
          "limit": "80",
          "autoreject": "false"
        }
      },
      "catch2": {
        "enabled": false,
        "show_to_student": false,
        "test_path": "autotest.cpp",
        "check": {
          "limit": 0,
          "autoreject": true
        }
      }
    }
  }';
}



function checked($src)
{
  return ($src == "true") ? "checked" : "";
}

function selected($src, $option)
{
  return ($src == $option) ? "selected" : "";
}

// function add_check_params($group, $param, $checks)
// {
//   if (!isset($checks['tools'][$group][$param]))
//     return "";
//   $output = '';
//   foreach ($checks['tools'][$group][$param] as $check) {
//     $output .= add_check_param($group, $check['check'], $check['check'], $checks);
//   }
//   return $output;
// }

function add_check_param($group, $param, $caption, $checks)
{
  $enabled = "true";
  $limit = "0";
  $reject = "false";

  if ($group == 'clang') {
    $enabled = @$checks['tools']['clang-format']['enabled'];
    $limit = @$checks['tools']['clang-format']['check']['limit'];
    $reject = @$checks['tools']['clang-format']['check']['autoreject'];
  } else if ($group == 'plug') {
    $enabled = @$checks['tools']['copydetect']['enabled'];
    $limit = @$checks['tools']['copydetect']['check']['limit'];
    $reject = @$checks['tools']['copydetect']['check']['autoreject'];
  } else if ($group == 'catch2') {
    $enabled = @$checks['tools']['catch2']['enabled'];
    $limit = @$checks['tools']['catch2']['check']['limit'];
    $reject = @$checks['tools']['catch2']['check']['autoreject'];
  } else if ($group == 'pytest') {
    $enabled = @$checks['tools']['pytest']['enabled'];
    $limit = @$checks['tools']['pytest']['check']['limit'];
    $reject = @$checks['tools']['pytest']['check']['autoreject'];
  } else {
    if (isset($checks['tools'][$group])) {
      $arr = @$checks['tools'][$group]['checks'];
      foreach ($arr as $a) {
        if (@$a['check'] == $param) {
          $enabled = $a['enabled'];
          $limit = $a['limit'];
          $reject = $a['autoreject'];
        }
      }
    }
  }

  return  '<div><input id="' . $group . '_' . $param . '" name="' . $group . '_' . $param . '" ' . checked($enabled) .
    ' class="accordion-input-item form-check-input" type="checkbox" value="true">' .
    '<label class="form-check-label" for="' . $group . '_' . $param . '" style="width:20%;">' . $caption . '</label>' .
    '<label class="form-check-label me-3" for="' . $group . '_' . $param . '_limit">порог</label>' .
    '<input id="' . $group . '_' . $param . '_limit" name="' . $group . '_' . $param . '_limit" value="' . $limit .
    '" class="accordion-input-item mb-2" wrap="off" rows="1" style="width:10%;">' .
    '<input id="' . $group . '_' . $param . '_reject" name="' . $group . '_' . $param . '_reject" ' . checked($reject) .
    ' class="accordion-input-item form-check-input ms-5" type="checkbox" value="true" style="float: none; margin-left:56px!important;margin-top:6px;">' .
    '<label class="form-check-label" for="' . $group . '_' . $param . '_reject" style="width:40%;">автоматически отклонять при нарушении</label></div>';
}

function getChecksAccordion($checks)
{
  return array(
    array(
      'header' => '<b>' . $checks['tools']['build']['language'] . ' Build</b>',

      'label'   => '<input id="build_enabled" name="build_enabled" ' . checked(@$checks['tools']['build']['enabled']) .
        ' class="accordion-input-item form-check-input" type="checkbox" value="true">' .
        '<label class="form-check-label" for="build_enabled" style="color:#4f4f4f;">выполнять сборку</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' .
        '<input id="build_show" name="build_show" ' . checked(@$checks['tools']['build']['show_to_student']) .
        ' class="accordion-input-item form-check-input ms-5" type="checkbox" value="true" onclick="onInputShowClick(event)">' .
        '<label class="form-check-label" for="build_show" style="color:#4f4f4f;">отображать студенту</label>',

      'body'   => //'<div><label class="form-check-label" for="valgrind_arg" style="width:20%;">аргументы</label>'.
      //'<input id="valgrind_arg" name="valgrind_arg" value="'.@$checks['tools']['valgrind']['arguments'].
      //'" style="width:50%;" class="accordion-input-item mb-2" wrap="off" rows="1"></div>'.
      '<div><label class="form-check-label" for="build_language" style="width:20%;">Язык</label>' .
        '<select id="build_language" name="build_language"' .
        ' class="form-select mb-2" aria-label=".form-select" style="width:50%; display: inline-block;">' .
        '  <option value="C" ' . selected(@$checks['tools']['build']['language'], 'C') . '>C</option>' .
        '  <option value="C++" ' . selected(@$checks['tools']['build']['language'], 'C++') . '>C++</option>' .
        '</select></div>' .
        '<div><input id="build_autoreject" name="build_autoreject" ' . checked(@$checks['tools']['build']['check']['autoreject']) .
        ' class="accordion-input-item form-check-input" type="checkbox" value="true">' .
        '<label class="form-check-label" for="build_autoreject" style="color:#4f4f4f;">автоматически отклонять при нарушении</label></div>'
    ),
    array(
      'header' => '<b>Valgrind</b>',

      'label'   => '<input id="valgrind_enabled" name="valgrind_enabled" ' . checked(@$checks['tools']['valgrind']['enabled']) .
        ' class="accordion-input-item form-check-input" type="checkbox" value="true">' .
        '<label class="form-check-label" for="valgrind_enabled" style="color:#4f4f4f;">выполнять проверки</label>' .
        '<input id="valgrind_show" name="valgrind_show" ' . checked(@$checks['tools']['valgrind']['show_to_student']) .
        ' class="accordion-input-item form-check-input ms-5" type="checkbox" value="true" onclick="onInputShowClick(event)">' .
        '<label class="form-check-label" for="valgrind_show" style="color:#4f4f4f;">отображать студенту</label>',

      'body'   => '<div><label class="form-check-label" for="valgrind_arg" style="width:20%;">аргументы</label>' .
        '<input id="valgrind_arg" name="valgrind_arg" value="' . @$checks['tools']['valgrind']['arguments'] .
        '" style="width:50%;" class="accordion-input-item mb-2" wrap="off" rows="1"></div>' .
        '<div><label class="form-check-label" for="valgrind_compiler" style="width:20%;">компилятор</label>' .
        '<select id="valgrind_compiler" name="valgrind_compiler"' .
        ' class="form-select mb-2" aria-label=".form-select" style="width:50%; display: inline-block;">' .
        '  <option value="gcc" ' . selected(@$checks['tools']['valgrind']['compiler'], 'gcc') . '>gcc</option>' .
        '  <option value="g++" ' . selected(@$checks['tools']['valgrind']['compiler'], 'g++') . '>g++</option>' .
        '</select></div>' .
        add_check_param('valgrind', 'errors', 'ошибки памяти', $checks) .
        add_check_param('valgrind', 'leaks', 'утечки памяти', $checks)
    ),
    array(
      'header' => '<b>CppCheck</b>',

      'label'   => '<input id="cppcheck_enabled" name="cppcheck_enabled" ' . checked(@$checks['tools']['cppcheck']['enabled']) .
        ' class="accordion-input-item form-check-input" type="checkbox" value="true">' .
        '<label class="form-check-label" for="cppcheck_enabled" style="color:#4f4f4f;">выполнять проверки</label>' .
        '<input id="cppcheck_show" name="cppcheck_show" ' . checked(@$checks['tools']['cppcheck']['show_to_student']) .
        ' class="accordion-input-item form-check-input ms-5" type="checkbox" value="true" onclick="onInputShowClick(event)">' .
        '<label class="form-check-label" for="cppcheck_show" style="color:#4f4f4f;">отображать студенту</label>',

      'body'   => '<div><label class="form-check-label" for="cppcheck_arg" style="width:20%;">аргументы</label>' .
        '<input id="cppcheck_arg" name="cppcheck_arg" value="' . @$checks['tools']['cppcheck']['arguments'] .
        '" class="accordion-input-item mb-2" wrap="off" rows="1" style="width:50%;"></div>' .

        add_check_param('cppcheck', 'error', 'error', $checks) .
        add_check_param('cppcheck', 'warning', 'warnings', $checks) .
        add_check_param('cppcheck', 'style', 'style', $checks) .
        add_check_param('cppcheck', 'performance', 'performance', $checks) .
        add_check_param('cppcheck', 'portability', 'portability', $checks) .
        add_check_param('cppcheck', 'information', 'information', $checks) .
        add_check_param('cppcheck', 'unused', 'unused functions', $checks) .
        add_check_param('cppcheck', 'include', 'missing include', $checks)
    ),
    array(
      'header' => '<b>Clang-format</b>',
      'label'   => '<input id="clang_enabled" name="clang_enabled" ' . checked(@$checks['tools']['clang-format']['enabled']) .
        ' class="accordion-input-item form-check-input" type="checkbox" value="true">' .
        '<label class="form-check-label" for="clang_enabled" style="color:#4f4f4f;">выполнять проверки</label>' .
        '<input id="clang_show" name="clang_show" ' . checked(@$checks['tools']['clang-format']['show_to_student']) .
        ' class="accordion-input-item form-check-input ms-5" type="checkbox" value="true" onclick="onInputShowClick(event)">' .
        '<label class="form-check-label" for="clang_show" style="color:#4f4f4f;">отображать студенту</label>',

      'body'   => '<div><label class="form-check-label" for="clang_arg" style="width:20%;">аргументы</label>' .
        '<input id="clang_arg" name="clang_arg" value="' . @$checks['tools']['clang-format']['arguments'] .
        '" class="accordion-input-item mb-2" wrap="off" rows="1" style="width:50%;"></div>' .
        '<div><label class="form-check-label" for="clang_compiler" style="width:20%;">соответствие</label>' .
        '<select id="clang_config" name="clang-config" class="form-select mb-2" aria-label=".form-select" style="width:50%; display: inline-block;">' .
        '  <option value="strict" ' . selected(@$checks['tools']['clang-format']['level'], 'strict') . '>strict - need-to-comment</option>' .
        '  <option value="less" ' . selected(@$checks['tools']['clang-format']['level'], 'less') . '>less - need-to-comment</option>' .
        '  <option value="minimal" ' . selected(@$checks['tools']['clang-format']['level'], 'minimal') . '>minimal - need-to-comment</option>' .
        '  <option value="so on" ' . selected(@$checks['tools']['clang-format']['level'], 'so on') . '>so on - need-to-complete</option>' .
        '  <option value="specific" ' . selected(@$checks['tools']['clang-format']['level'], 'specific') . '>specific - укажите свой файл с правилами оформления</option>' .
        '</select></div>' .
        '<div><label class="form-check-label mb-2" for="clang_file" style="width:20%;">файл с правилами</label>' .
        '<input id="clang_file" name="clang_file" value="' . @$checks['tools']['clang-format']['file'] .
        '" class="accordion-input-item mb-2" wrap="off" rows="1" style="width:50%;"></div>' .
        add_check_param('clang', 'errors', 'нарушения', $checks)
    ),
    array(
      'header' => '<b>Pylint</b>',

      'label'   => '<input id="pylint_enabled" name="pylint_enabled" ' . checked(@$checks['tools']['pylint']['enabled']) .
        ' class="accordion-input-item form-check-input" type="checkbox" value="true">' .
        '<label class="form-check-label" for="pylint_enabled" style="color:#4f4f4f;">выполнять проверки</label>' .
        '<input id="pylint_show" name="pylint_show" ' . checked(@$checks['tools']['pylint']['show_to_student']) .
        ' class="accordion-input-item form-check-input ms-5" type="checkbox" value="true" onclick="onInputShowClick(event)">' .
        '<label class="form-check-label" for="pylint_show" style="color:#4f4f4f;">отображать студенту</label>',

      'body'   => '<div><label class="form-check-label" for="pylint_arg" style="width:20%;">аргументы</label>' .
        '<input id="pylint_arg" name="pylint_arg" value="' . @$checks['tools']['pylint']['arguments'] .
        '" class="accordion-input-item mb-2" wrap="off" rows="1" style="width:50%;"></div>' .

        add_check_param('pylint', 'error', 'errors', $checks) .
        add_check_param('pylint', 'warning', 'warnings', $checks) .
        add_check_param('pylint', 'refactor', 'refactor', $checks) .
        add_check_param('pylint', 'convention', 'convention', $checks)
    ),
    array(
      'header' => '<b>Pytest</b>',

      'label'   => '<input id="pytest_enabled" name="pytest_enabled" ' . checked(@$checks['tools']['pytest']['enabled']) .
        ' class="accordion-input-item form-check-input" type="checkbox" value="true">' .
        '<label class="form-check-label" for="pytest_enabled" style="color:#4f4f4f;">выполнять проверки</label>' .
        '<input id="pytest_show" name="pytest_show" ' . checked(@$checks['tools']['pytest']['show_to_student']) .
        ' class="accordion-input-item form-check-input ms-5" type="checkbox" value="true" onclick="onInputShowClick(event)">' .
        '<label class="form-check-label" for="pytest_show" style="color:#4f4f4f;">отображать студенту</label>',

      'body'   => '<div class="d-flex justify-content-between align-items-center mb-2"><label class="form-check-label" for="pytest_arg" style="width:20%;">аргументы</label>' .
        '<div class="d-flex align-items-center w-100">' .
        '<div class="badge badge-primary px-3 py-2 me-2"><span>-s</span></div>' .
        '<div class="badge badge-primary px-3 py-2 me-2"><span>-q</span></div>' .
        '<input id="pytest_arg" name="pytest_arg" value="' . @$checks['tools']['pytest']['arguments'] .
        '" class="accordion-input-item form-control w-75" wrap="off" rows="1" style="width:50%;"></div></div>' .
        add_check_param('pytest', 'check', 'проверять', $checks)
    ),
    array(
      'header' => '<b>Catch2</b>',
      'label'   => '<input id="test_enabled" name="test_enabled" ' . checked(@$checks['tools']['catch2']['enabled']) .
        ' class="accordion-input-item form-check-input" type="checkbox" value="true">' .
        '<label class="form-check-label" for="test_enabled" style="color:#4f4f4f;">выполнять проверки</label>' .
        '<input id="test_show" name="test_show" ' . checked(@$checks['tools']['catch2']['show_to_student']) .
        ' class="accordion-input-item form-check-input ms-5" type="checkbox" value="true" onclick="onInputShowClick(event)">' .
        '<label class="form-check-label" for="test_show" style="color:#4f4f4f;">отображать студенту</label>',

      'body'   => //'<div><label class="form-check-label" for="test_lang" style="width:20%;">сравнивать</label>'.
      //'<select id="test_lang" class="form-select mb-2" aria-label=".form-select" name="test_lang" style="width:50%; display: inline-block;">'.
      //'  <option value="С" '.selected(@$checks['tools']['catch2']['language'], 'C').'>C</option>'.
      //'  <option value="С++" '.selected(@$checks['tools']['catch2']['language'], 'C++').'>C++</option>'.
      //'  <option value="Python" '.selected(@$checks['tools']['catch2']['language'], 'Python').'>Python</option>'.
      //'</select></div>'.
      add_check_param('catch2', 'check', 'проверять', $checks)
    ),
    array(
      'header' => '<b>Антиплагиат</b>',
      'label'   => '<input id="plug_enabled" name="plug_enabled" ' . checked(@$checks['tools']['copydetect']['enabled']) .
        ' class="accordion-input-item form-check-input" type="checkbox" value="true">' .
        '<label class="form-check-label" for="plug_enabled" style="color:#4f4f4f;">выполнять проверки</label>' .
        '<input id="plug_show" name="plug_show" ' . checked(@$checks['tools']['copydetect']['show_to_student']) .
        ' class="accordion-input-item form-check-input ms-5" type="checkbox" value="true" onclick="onInputShowClick(event)">' .
        '<label class="form-check-label" for="plug_show" style="color:#4f4f4f;">отображать студенту</label>',

      'body'   => '<div><label class="form-check-label" for="plug_arg" style="width:20%;">аргументы</label>' .
        '<input id="plug_arg" name="plug_arg" value="' . @$checks['tools']['copydetect']['arguments'] .
        '" class="accordion-input-item mb-2" wrap="off" rows="1" style="width:50%;"></div>' .
        '<div><label class="form-check-label" for="plug_config" style="width:20%;">сравнивать</label>' .
        '<select id="plug_config" class="form-select mb-2" aria-label=".form-select" name="plug_config" style="width:50%; display: inline-block;">' .
        '  <option value="with_all" ' . selected(@$checks['tools']['copydetect']['with_all'], 'gcc') . '>со всеми ранее сданными работами</option>' .
        //'  <option value="group" '.selected(@$checks['tools']['copydetect']['group'], 'gcc').'>с работами студентов своей группы</option>'.
        '</select></div>' .
        add_check_param('plug', 'check', 'проверять', $checks)
    )

  );
}

// 
// For history url backup
// 

// function getBasePageNameFromUrl($url)
// {
//   $url_splitted = explode("/", $url);
//   if (count($url_splitted) <= 1)
//     return null;
//   $base_request = $url_splitted[count($url_splitted) - 1];
//   $request_splitted = explode(".php", $base_request);
//   if (count($request_splitted) <= 1)
//     return null;
//   return $request_splitted[count($request_splitted) - 1];
// }

// function getPageTitleByUrl($url)
// {
//   $page_name = getBasePageNameFromUrl($url);
//   if ($page_name == "editor") {
//     return getEditorPageTitle(null);
//   } else if ($page_name == "pageedit") {
//     return getPageeditPageTitle(null);
//   } else if ($page_name == "preptable") {
//     return getPreptablePageTitle(null);
//   } else if ($page_name == "preptasks") {
//     return getPreptasksPageTitle(null);
//   } else if ($page_name == "profile") {
//     return getProfilePageTitle(null);
//   } else if ($page_name == "studtasks") {
//     return getStudtasksPageTitle(null);
//   } else if ($page_name == "taskassign") {
//     return getTaskassignPageTitle(null, null);
//   } else if ($page_name == "taskchat") {
//     return getTaskchatPageTitle(null);
//   } else if ($page_name == "taskedit") {
//     return getTaskeditPageTitle(null);
//   }
//   return null;
// }

// function setCurrentPageUrl($url)
// {
//   $page_name = getBasePageNameFromUrl($url);
//   $previous_page_name = getBasePageNameFromUrl($_SESSION['urls'][0]);
//   if ($page_name == $previous_page_name)
//     return;
//   array_unshift($_SESSION['urls'], $url);
//   while (count($_SESSION['urls']) > 3)
//     array_pop($_SESSION['urls']);
// }

?>
<?php
require_once("common.php");
require_once("dbqueries.php");
require_once("settings.php");
require_once("utilities.php");


$au = new auth_ssh();
checkAuLoggedIN($au);

$User = new User((int)$au->getUserId());

echo "<script>var USER_ID=" . $User->id . ";</script>";
echo "<script>var AVAILABLE_FILE_EXT=" . json_encode(getSpecialFileTypes()) . ";</script>";

// TODO: Переписать c использованием POClasses
// Обработка некорректного перехода между страницами
if (!(isset($_REQUEST['task'], $_REQUEST['page'], $_REQUEST['id_student'])) && !isset($_REQUEST['assignment'])) {
  header('Location:index.php');
  exit;
}

$user_id = $User->id;

if (isset($_REQUEST['assignment'])) {
  $Assignment = new Assignment((int)$_REQUEST['assignment']);
  echo "<script>var ASSIGNMENT_ID=" . $Assignment->id . ";</script>";
} else {
  header('Location:index.php');
  exit;
}

$au = new auth_ssh();
if ($au->isAdmin() && isset($_REQUEST['id_student'])) {
  // Если на страницу чата зашёл АДМИН
  $student_id = $_REQUEST['id_student'];
  $sender_user_type = 1;
} else if ($User->isAdmin() && isset($_REQUEST['id_student'])) {
  // Если на страницу чата зашёл ПРЕПОД
  $student_id = $_REQUEST['id_student'];
  $sender_user_type = 2;
} else if ($au->loggedIn()) {
  // Если на страницу чата зашёл студент
  $student_id = $user_id;
  $sender_user_type = 3;
} else {
  header('Location:index.php');
  exit;
}

if (isset($_REQUEST['task'], $_REQUEST['page'], $_REQUEST['id_student'])) {
  $task_id = 0;
  if (isset($_REQUEST['task']))
    $task_id = $_REQUEST['task'];

  $page_id = 0;
  if (isset($_REQUEST['page']))
    $page_id = $_REQUEST['page'];

  $query = select_task_assignment_student_id($student_id, $task_id);
  $result = pg_query($dbconnect, $query);
  $row = pg_fetch_assoc($result);
  if ($row) {
    $assignment_id = $row['id'];
  } else {
    echo 'Не распознанный Assignment';
    http_response_code(404);
    exit;
  }
} else if (isset($_REQUEST['assignment'])) {
  $assignment_id = 0;
  if (isset($_REQUEST['assignment']))
    $assignment_id = $_REQUEST['assignment'];


  $result = pg_query($dbconnect, "select task_id, page_id from ax.ax_assignment a inner join ax.ax_task t on a.task_id = t.id where a.id = $assignment_id");
  $row = pg_fetch_assoc($result);
  if ($row) {
    $task_id = $row['task_id'];
    $page_id = $row['page_id'];
  } else {
    echo 'Не распознанные Page & Task';
    http_response_code(404);
    exit;
  }
}

$query = select_ax_page_short_name($page_id);
$result = pg_query($dbconnect, $query);
$page_name = pg_fetch_assoc($result)['short_name'];


$MAX_FILE_SIZE = getMaxFileSize();


$task_title = '';
$task_description = '';
$task_max_mark = 5;
$query = select_task($task_id);
$result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
$row = pg_fetch_assoc($result);
if ($row) {
  $task_title = $row['title'];
  $task_description = $row['description'];
  $task_max_mark = (int)$row['max_mark'];
  if ($task_max_mark == 0)
    $task_max_mark = 5;
}

$task_finish_limit = '';
$task_status_code = '';
$task_mark = '';
$query = select_task_assignment_with_limit($task_id, $student_id);
$result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
$row = pg_fetch_assoc($result);
if ($row) {
  $time_date = explode(" ", $row['finish_limit']);
  $task_finish_limit = "";
  if (count($time_date) >= 1 && $time_date[0]) {
    $date = explode("-", $time_date[0]);
    $task_finish_limit = $date[2] . "." . $date[1] . "." . $date[0];
  }
  if (count($time_date) > 1 && $time_date[1]) {
    $time = explode(":", $time_date[1]);
    $task_finish_limit .= " " . $time[0] . ":" . $time[1];
  }
  $task_status_code = $row['status'];
  $task_mark = $row['mark'];
}

$task_status_text = '';
if ($Assignment->visibility != '') {
  $task_status_text = visibility_to_text($Assignment->visibility);
}

$Task = new Task((int)$task_id);


$task_finish_date_time = '';
$query = "SELECT date_time from ax.ax_message where assignment_id = $assignment_id and type = 2";
$result = pg_query($dbconnect, $query);
$row = pg_fetch_assoc($result);
if ($row) {
  $message_time = explode(" ", $row['date_time']);
  $date = explode("-", $message_time[0]);
  $time = explode(":", $message_time[1]);
  $task_finish_date_time = $date[2] . "." . $date[1] . "." . $date[0] . " " . $time[0] . ":" . $time[1];
}

$task_number = explode('.', $task_title)[0];
//echo $task_number;
?>

<!DOCTYPE html>
<html lang="en">

<?php show_head('Чат с преподавателем', array('https://cdn.jsdelivr.net/npm/marked/marked.min.js')); ?>
<link rel="stylesheet" href="taskchat.css">


<body style="overflow-x:hidden;">
  <?php
  if ($au->isAdminOrPrep())
    show_header(
      $dbconnect,
      'Чат c перподавателем',
      array('Посылки по дисциплине: ' . $page_name => 'preptable.php?page=' . $page_id, $task_title => ''),
      $User
    );
  else
    show_header(
      $dbconnect,
      'Чат c перподавателем',
      array($page_name => 'studtasks.php?page=' . $page_id, $task_title => ''),
      $User
    );
  ?>

  <main class="container pt-3 mt-5">
    <div class="row mb-3">
      <h2><?= $task_title ?></h2>
      <div class="row">
        <div class="border rounded <?= $Task->isConversation() ? "col-12 me-0" : "col-9" ?>">
          <div class="d-flex justify-content-between align-self-start align-items-center mt-2">
            <b class="mb-0">Описание задания:</b>
            <?php if (!$User->isStudent() && !$Task->isConversation()) { ?>
              <a href="taskassign.php?assignment_id=<?= $Assignment->id ?>" class="btn btn-outline-primary d-flex" target="_blank">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">
                  <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z" />
                  <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z" />
                </svg>
                <span>&nbsp;&nbsp;Редактировать назначение</span>
              </a>
            <?php } ?>
          </div>

          <?php if ($task_description != "") { ?>
            <p id="TaskDescr" class="m-0 p-0" style="overflow: auto;"><?= $task_description ?></p>
          <?php } else { ?>
            <p class="m-0 p-0" style="color: grey;">Описание отсутствует.</p>
          <?php } ?>

          <script>
            document.getElementById('TaskDescr').innerHTML =
              marked.parse(document.getElementById('TaskDescr').innerHTML);
            $('#TaskDescr').children().addClass("m-0");
          </script>


          <?php
          if ($User->isTeacher() || $User->isAdmin())
            $task_files = $Task->getTeacherFilesToTaskchat();
          else
            $task_files = $Task->getStudentFilesToTaskchat();

          if (count($task_files) > 0) { ?>
            <p class="mx-0 mt-2 mb-0">
              <b>Файлы, приложенные к заданию:</b>
            </p>
            <?= showTaskchatFiles($task_files); ?>

          <?php }
          ?>

          <div class="d-flex justify-content-between align-self-end align-items-center mb-2">
            <?php if (!$Task->isConversation()) { ?>
              <div>
                <b>Срок выполнения: </b>
                &nbsp;<?= (!$Assignment->finish_limit) ? "бессрочно" : $Assignment->finish_limit ?>
              </div>
            <?php } ?>
            <div class="d-flex align-items-center">
              <div class="me-2 align-items-center" style="display: inline-block">
                <div class="d-flex">
                  <?php foreach ($Assignment->getStudents() as $i => $Student) { ?>
                    <div data-title="<?= $Student->getFI() ?>">
                      <button class="btn btn-floating shadow-none p-1 m-0 bg-image hover-overlay hover-zoom hover-shadow ripple" onclick="window.location='profile.php?user_id=<?= $Student->id ?>'" style="/*left: -<?= $i * 5 ?>%*/">
                        <?php if ($Student->getImageFile() != null) { ?>
                          <div class="embed-responsive embed-responsive-1by1" style="display: block;">
                            <div class="embed-responsive-item">
                              <img class="h-100 w-100 p-0 m-0 rounded-circle user-icon" style="vertical-align: unset; /*transform: translateX(-30%);*/" src="<?= $Student->getImageFile()->download_url ?>" />
                            </div>
                          </div>
                        <?php } else { ?>
                          <svg class="h-100 w-100" xmlns="http://www.w3.org/2000/svg" width="20" fill="black" class="bi bi-person-circle" viewBox="0 0 16 16">
                            <path fill-rule="nonzero" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z" />
                            <path fill-rule="nonzero" d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" />
                          </svg>
                        <?php } ?>
                        <a href="profile.php?user_id=<?= $Student->id ?>">
                          <div class="mask" style="background-color: rgba(var(--mdb-info-rgb), 0.2);"></div>
                        </a>
                      </button>
                    </div>
                  <?php } ?>
                </div>
              </div>
              <?php if (!$Task->isConversation()) { ?>
                <a href="download_file.php?download_task_files=&task_id=<?= $task_id ?>" style="height:fit-content;" class="btn btn-primary" target="_blank">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-download" viewBox="0 0 16 16">
                    <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z" />
                    <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z" />
                  </svg>
                  <span>&nbsp;&nbsp;Скачать задание</span>
                </a>
              <?php } ?>
            </div>
          </div>
        </div>

        <?php // FIXME: Посмотреть, доделать
        if (!$Task->isConversation()) { ?>
          <div class="task-status-wrapper me-0 ps-3 col-3 align-items-end">
            <div class="align-items-end w-100">
              <div class="d-flex align-items-center ps-0">
                <div class="text-primary me-2">
                  <?php if (!$Task->isConversation()) {
                    getSVGByAssignmentStatus($Assignment->status);
                  } ?>
                </div>
                <label id="label-task-status-text">
                  <?php $text_status = status_to_text($Assignment->status);
                  if ($Assignment->isMarked()) {
                    if ($Assignment->isWaitingCheck())
                      $text_status .= ' </br>(текущая оценка: <strong>' . $Assignment->mark . '</strong>)';
                    else if ($Assignment->mark != "зачтено")
                      $text_status .= ' (оценка: <strong>' . $Assignment->mark . '</strong>)';
                    else
                      $text_status .= ' (<strong>' . $Assignment->mark . '</strong>)';
                  }
                  echo $text_status; ?>
                </label>
              </div>
              <span id="span-answer-date"><?php if ($task_finish_date_time) echo $task_finish_date_time; ?></span>
              </br>
            </div>
            <div class="w-100">
              <div>
                <a href="editor.php?assignment=<?= $assignment_id ?>" class="btn btn-outline-primary my-1" style="width: 100%;" target="_blank">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-code-slash" viewBox="0 0 16 16">
                    <path d="M10.478 1.647a.5.5 0 1 0-.956-.294l-4 13a.5.5 0 0 0 .956.294l4-13zM4.854 4.146a.5.5 0 0 1 0 .708L1.707 8l3.147 3.146a.5.5 0 0 1-.708.708l-3.5-3.5a.5.5 0 0 1 0-.708l3.5-3.5a.5.5 0 0 1 .708 0zm6.292 0a.5.5 0 0 0 0 .708L14.293 8l-3.147 3.146a.5.5 0 0 0 .708.708l3.5-3.5a.5.5 0 0 0 0-.708l-3.5-3.5a.5.5 0 0 0-.708 0z" />
                  </svg>&nbsp;&nbsp;
                  Онлайн редактор кода
                </a>
              </div>

              <?php if ($au->isAdminOrPrep()) { // Оценить отправленное на проверку задание 
              ?>
                <?php if ($Task->isMarkNumber()) { ?>
                  <div id="div-mark" class="d-flex flex-row justify-content-end my-1">
                    <div class="file-input-wrapper me-1">
                      <select id="select-mark" class="form-select" aria-label=".form-select" style="width: auto;" name="mark">
                        <option hidden value="-1"></option>
                        <?php for ($i = 1; $i <= $task_max_mark; $i++) { ?>
                          <option value="<?= $i ?>"><?= $i ?></option>
                        <?php } ?>
                      </select>
                    </div>
                    <button id="button-check" class="btn btn-success" target="_blank" type="button" name="submit-check" style="width: 100%;" onclick="markAssignment($('#select-mark').val())">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clipboard-check-fill" viewBox="0 0 16 16">
                        <path d="M6.5 0A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3Zm3 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5h3Z" />
                        <path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1A2.5 2.5 0 0 1 9.5 5h-3A2.5 2.5 0 0 1 4 2.5v-1Zm6.854 7.354-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 0 1 .708-.708L7.5 10.793l2.646-2.647a.5.5 0 0 1 .708.708Z" />
                      </svg>&nbsp;&nbsp;Оценить ответ</button>
                  </div>
                <?php } else { ?>
                  <div id="div-check-word" class="d-flex flex-row justify-content-end my-1">
                    <button id="button-check-word" class="btn btn-primary d-flex justify-content-center" target="_blank" type="button" name="submit-check" style="width: 100%;" onclick="markAssignment('зачтено')">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clipboard-check-fill" viewBox="0 0 16 16">
                        <path d="M6.5 0A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3Zm3 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5h3Z" />
                        <path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1A2.5 2.5 0 0 1 9.5 5h-3A2.5 2.5 0 0 1 4 2.5v-1Zm6.854 7.354-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 0 1 .708-.708L7.5 10.793l2.646-2.647a.5.5 0 0 1 .708.708Z" />
                      </svg>
                      <div class="d-flex align-items-center">
                        &nbsp;&nbsp;Зачесть&nbsp;
                        <div id="spinner-check-word" class="spinner-border ms-2 d-none" role="status" style="width: 1rem; height: 1rem;">
                          <span class="sr-only">Loading...</span>
                        </div>
                      </div>
                    </button>
                  </div>
                <?php } ?>
                <?php if ($Assignment->isCompleted()) { ?>
                  <div id="div-reject-check" class="d-flex flex-row justify-content-end my-1">
                    <button id="button-reject-check" class="btn btn-danger d-flex justify-content-center" target="_blank" type="submit" name="reject-check" style="width: 100%;" onclick="markAssignment('')">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-counterclockwise" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M8 3a5 5 0 1 1-4.546 2.914.5.5 0 0 0-.908-.417A6 6 0 1 0 8 2z" />
                        <path d="M8 4.466V.534a.25.25 0 0 0-.41-.192L5.23 2.308a.25.25 0 0 0 0 .384l2.36 1.966A.25.25 0 0 0 8 4.466" />
                      </svg>
                      <div class="d-flex align-items-center">
                        &nbsp;&nbsp;Отменить оценку&nbsp;
                        <div id="spinner-reject-check" class="spinner-border ms-2 d-none" role="status" style="width: 1rem; height: 1rem;">
                          <span class="sr-only">Loading...</span>
                        </div>
                      </div>
                    </button>
                  </div>
                <?php } ?>
              <?php } else if ($Assignment->isCompleteable()) { // Отправить задание на проверку 
              ?>
                <form id="form-send-answer" action="taskchat_action.php" method="POST">
                  <div class="d-flex flex-row my-2 justify-content-end align-items-center">
                    <div class=" file-input-wrapper align-self-start me-2">
                      <input type="hidden" name="MAX_FILE_SIZE" value="<?= $MAX_FILE_SIZE ?>" />
                      <input id="user-answer-files" type="file" name="answer_files[]" class="input-files" multiple>
                      <label for="user-answer-files" class="p-1" <?php if ($task_status_code == 4) echo 'style="cursor: pointer;"'; ?>>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="bi bi-paperclip h-100 w-100" height="25" width="25" viewBox="0 0 16 16">
                          <path d="M4.5 3a2.5 2.5 0 0 1 5 0v9a1.5 1.5 0 0 1-3 0V5a.5.5 0 0 1 1 0v7a.5.5 0 0 0 1 0V3a1.5 1.5 0 1 0-3 0v9a2.5 2.5 0 0 0 5 0V5a.5.5 0 0 1 1 0v7a3.5 3.5 0 1 1-7 0V3z"></path>
                        </svg>
                        <span id="files-answer-count" class="text-success"></span>
                      </label>
                    </div>
                    <button id="submit-answer" class="btn btn-success submit-files w-75" target="_blank" type="submit" name="submit-answer">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-upload" viewBox="0 0 16 16">
                        <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z" />
                        <path d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V11.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708l3-3z" />
                      </svg>&nbsp;&nbsp;Загрузить ответ</button>
                  </div>
                  <div id="div-attachedAnswerFiles" class="d-flex flex-wrap justify-content-end mt-2">

                  </div>
                </form>
              <?php } ?>



            </div>
          </div>
        <?php } ?>
      </div>
    </div>

    <div class=" row w-100 mb-5">

      <div id="chat-box">
        <!-- Вывод сообщений на страницу -->
      </div>
      <div id="div-contextMessageMenu" class="list-group w-25 d-none" style="position: absolute; cursor: pointer;">
        <a class="list-group-item dropdown-item" role="button" onclick="copyMessageText()">
          Скопировать текст
        </a>
        <a class="list-group-item dropdown-item" role="button" onclick="selectMessageWithContextMenu()">
          Выделить
        </a>
        <a class="list-group-item dropdown-item" role="button" onclick="resendMessageToCurrentChat()">
          Переслать в текущий диалог
        </a>
        <?php
        $Page = new Page((int)getPageByAssignment((int)$Assignment->id));
        $Task = new Task((int)getTaskByAssignment((int)$Assignment->id));
        $conversationTask = $Page->getConversationTask();
        if ($conversationTask && !$Task->isConversation() && $conversationTask->getConversationAssignment() != null) { ?>
          <a class="list-group-item dropdown-item" onclick="resendMessageToConversationChat(<?= $conversationTask->getConversationAssignment()->id ?>)">
            Переслать в общую беседу
          </a>
        <?php } ?>
        <a id="a-deleteMessage" class="list-group-item dropdown-item align-items-center" role="button" onclick="deleteMessage()">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-lg me-1" viewBox="0 0 16 16">
            <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8 2.146 2.854Z" />
          </svg>
          Удалить
        </a>
      </div>

      <div class="d-flex align-items-center px-0">

        <div class="dropdown align-self-start d-none me-1" id="btn-group-more">
          <button class="btn btn-primary dropdown-toggle py-1 px-2" type="button" id="ul-dropdownMenu-more" data-mdb-toggle="dropdown" aria-expanded="false">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-three-dots-vertical" viewBox="0 0 16 16">
              <path d="M9.5 13a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z" />
            </svg>
          </button>
          <ul class="dropdown-menu" aria-labelledby="ul-dropdownMenu-more">
            <li>
              <!-- <a class="dropdown-item align-items-center" href="#">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left-right me-1" viewBox="0 0 16 16">
                  <path fill-rule="evenodd" d="M1 11.5a.5.5 0 0 0 .5.5h11.793l-3.147 3.146a.5.5 0 0 0 .708.708l4-4a.5.5 0 0 0 0-.708l-4-4a.5.5 0 0 0-.708.708L13.293 11H1.5a.5.5 0 0 0-.5.5zm14-7a.5.5 0 0 1-.5.5H2.707l3.147 3.146a.5.5 0 1 1-.708.708l-4-4a.5.5 0 0 1 0-.708l4-4a.5.5 0 1 1 .708.708L2.707 4H14.5a.5.5 0 0 1 .5.5z" />
                </svg>
                Переслать
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right-short" viewBox="0 0 16 16">
                  <path fill-rule="evenodd" d="M4 8a.5.5 0 0 1 .5-.5h5.793L8.146 5.354a.5.5 0 1 1 .708-.708l3 3a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708-.708L10.293 8.5H4.5A.5.5 0 0 1 4 8z" />
                </svg>
              </a> -->
              <?php
              $Page = new Page((int)getPageByAssignment((int)$Assignment->id));
              $conversationTask = $Page->getConversationTask();
              if ($conversationTask && !$Task->isConversation() && $conversationTask->getConversationAssignment() != null) { ?>
            <li>
              <a class="dropdown-item" role="button" onclick="resendMessages(<?= $conversationTask->getConversationAssignment()->id ?>, <?= $User->id ?>, false)">
                Переслать в общую беседу
              </a>
            </li>
          <?php } ?>
          <li>
            <a class="dropdown-item" role="button" onclick="resendMessages(<?= $Assignment->id ?>, <?= $User->id ?>, true)">
              Переслать в текущий диалог
            </a>
          </li>
          <!-- <ul class="dropdown-menu dropdown-submenu" style="cursor: pointer;">
                <?php
                $Page = new Page((int)getPageByAssignment((int)$Assignment->id));
                $conversationTask = $Page->getConversationTask();
                if ($conversationTask && !$Task->isConversation() && $conversationTask->getConversationAssignment() != null) { ?>
                  <li>
                    <a class="dropdown-item" onclick="resendMessages(<?= $conversationTask->getConversationAssignment()->id ?>, <?= $User->id ?>, false)">
                      В общую беседу
                    </a>
                  </li>
                <?php } ?>
                <li>
                  <a class="dropdown-item" onclick="resendMessages(<?= $Assignment->id ?>, <?= $User->id ?>, true)">
                    В текущий диалог
                  </a>
                </li>
              </ul> -->

          </li>
          <li>
            <a class="dropdown-item align-items-center" href="#" id="a-messages-delete" style="cursor: pointer;" onclick="deleteMessages(<?= $Assignment->id ?>, <?= $User->id ?>)">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-lg me-1" viewBox="0 0 16 16">
                <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8 2.146 2.854Z" />
              </svg>
              Удалить
            </a>
          </li>
          </ul>
        </div>

        <?php if ($Assignment->isCompleteable() || $Task->isConversation()) { ?>
          <form class="w-100 align-items-center d-flex flex-column m-0" action="taskchat_action.php" method="POST" enctype="multipart/form-data">
            <div class="message-input-wrapper h-100 align-items-center p-0 m-0 w-100">
              <div class="file-input-wrapper align-self-start h-auto p-1">
                <input type="hidden" name="MAX_FILE_SIZE" value="<?= $MAX_FILE_SIZE ?>" />
                <input id="user-files" type="file" name="user_files[]" class="input-files" onclick="this.value=null;" multiple>
                <!-- <label for="user-files"> -->
                <!-- <i class="fa-solid fa-paperclip"></i> -->
                <!-- <span id="files-count" class="label-files-count"></span> -->
                <!-- </label> -->
                <label for="user-files" class="p-1" style="cursor: pointer;">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="bi bi-paperclip h-100 w-100" height="25" width="25" viewBox="0 0 16 16">
                    <path d="M4.5 3a2.5 2.5 0 0 1 5 0v9a1.5 1.5 0 0 1-3 0V5a.5.5 0 0 1 1 0v7a.5.5 0 0 0 1 0V3a1.5 1.5 0 1 0-3 0v9a2.5 2.5 0 0 0 5 0V5a.5.5 0 0 1 1 0v7a3.5 3.5 0 1 1-7 0V3z"></path>
                  </svg>
                  <span id="files-count" class="text-success"></span>
                </label>
              </div>
              <textarea name="user-message" id="textarea-user-message" class="border rounded align-self-start w-100 p-1 mx-2" style="resize:none; overflow:hidden;" placeholder="Напишите сообщение..." rows="1"></textarea>
              <button class="align-self-start" type="submit" name="submit-message" id="submit-message">Отправить</button>
            </div>
            <div id="div-attachedFiles" class="d-flex flex-wrap mt-2 w-100 ">

            </div>
            <!-- <p id="p-ErrorFileName" class="error" style="display: none;">Ошибка! Файл с таким названием уже существует!</p> -->
          </form>
        <?php } ?>

      </div>

    </div>
  </main>


  <div class="modal fade" id="dialogErrorFileExt" tabindex="-1" aria-labelledby="dialogErrorFileExtLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 id="modalErrorFileExt-h5-title" class="modal-title">
            Недопустимое расширение
          </h5>
          <button type="button" class="btn-close me-2" data-mdb-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p id="modalErrorFileExt-p-text">
          </p>
        </div>
        <div class="modal-footer">
          <p id="modalAvailableFileExt-p-text" class="w-100">
          </p>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
  <script type="text/javascript" src="js/taskchat.js"></script>

  <script type="text/javascript">
    // После первой загрузки скролим страницу вниз
    $('body, html').scrollTop($('body, html').prop('scrollHeight'));

    var messageFiles = [];
    var answerFiles = [];

    function mouseX(evt) {
      if (evt.pageX) {
        return evt.pageX;
      } else if (evt.clientX) {
        return evt.clientX + (document.documentElement.scrollLeft ?
          document.documentElement.scrollLeft :
          document.body.scrollLeft);
      } else {
        return null;
      }
    }

    function mouseY(evt) {
      if (evt.pageY) {
        return evt.pageY;
      } else if (evt.clientY) {
        return evt.clientY + (document.documentElement.scrollTop ?
          document.documentElement.scrollTop :
          document.body.scrollTop);
      } else {
        return null;
      }
    }

    var isOpenContextMenu = false;
    var current_contextMenu_messageId = null;
    var isFromCurrentUserMessage = null;

    function onContextMenu(event, message_id, isFromCurrentUser) {
      console.log("onContextMenu()");
      event.preventDefault();
      isOpenContextMenu = true;
      current_contextMenu_messageId = message_id;
      isFromCurrentUserMessage = isFromCurrentUser;

      if (isFromCurrentUser)
        $('#a-deleteMessage').removeClass('d-none');
      else
        $('#a-deleteMessage').addClass('d-none');

      $("#div-contextMessageMenu").css("top", mouseY(event) + 'px');
      $("#div-contextMessageMenu").css("left", mouseX(event) + 'px');
      $("#div-contextMessageMenu").removeClass("d-none");

      window.event.returnValue = false;
    }

    $(document).ready(function() {

      let form_sendAnswer = document.getElementById('form-send-answer');
      // let form_check = document.getElementById('form-check-task');

      let button_check = document.getElementById('button-check');
      let button_answer = document.getElementById('submit-answer');

      // Отправка формы прикрепления ответа к заданию
      if (form_sendAnswer) {
        form_sendAnswer.addEventListener('submit', function(event) {
          event.preventDefault();
          // console.log("СРАБОТАЛА ФОРМА ЗАГРУЗКИ ОТВЕТА НА ЗАДАНИЕ");
          // console.log(userFiles);
          if (answerFiles.length < 1) {
            event.preventDefault();
            alert("Для отправки ответа задание необходимо прикрепить файлы!");
            return false;
          } else {
            // var userMessage = 'Ответ на <<?= $task_number ?>>:';
            var userMessage = '';
            if (sendMessage(userMessage, answerFiles, 1)) {
              // console.log("Сообщение было успешно отправлено");
            }

            $('#files-answer-count').html('');
            $('#div-attachedAnswerFiles').empty();
            answerFiles = [];

            button_answer.blur();

            loadChatLog(true);

            return false;
          }
        });
      }

      var textarea = document.getElementById('textarea-user-message');

      textarea.addEventListener('keydown', resize);

      function resize() {
        var el = this;
        setTimeout(function() {
          el.style.height = 'auto ';
          el.style.height = el.scrollHeight + 'px';
        }, 1);
      }


      // Отправка формы сообщения через FormData (с моментальным обновлением лога чата)
      $("#submit-message").click(function() {
        var userMessage = $("#textarea-user-message").val();

        if (sendMessage(userMessage, messageFiles, 0, null, true)) {
          event.preventDefault();
          // console.log("Сообщение было успешно отсправлено");
        } else {
          // console.log("Сообщение не было отправлено");
        }

        $("#textarea-user-message").val("");
        $("#textarea-user-message").css("height", 'auto');
        $("#user-files").val("");
        $('#files-count').html('');
        $('#div-attachedFiles').empty();
        messageFiles = [];

        loadChatLog(true);

        return false;
      });

      $('#textarea-user-message').on("keydown", function(event) {
        // console.log(event);
        if (event.key == "Enter" && !event.shiftKey)
          $("#submit-message").click();
      });


      // Первое обновление лога чата
      loadChatLog(true);
      // Обновление лога чата раз в 1 секунд
      setInterval(loadChatLog, 100000);


      // Показывает количество прикрепленных для отправки файлов
      $('#user-files').on('change', function() {
        // TODO: Сделать удаление числа, если оно 0
        if (this.files.length != 0) {
          let div = document.getElementById("div-attachedFiles");
          Object.entries(this.files).forEach(file => {
            if (checkIfFileIsExist(messageFiles, file[1].name)) {
              alert("Внимание! Файл с названием '" + file[1].name + "' уже прикреплён!")
            } else {
              add_element(div, file[1].name, "messageFiles[]", messageFiles.length);
              messageFiles.push(file[1]);
            }
          });
          $('#files-count').html(messageFiles.length);
        }
      });

      // Показывает количество прикрепленных для отправки файлов
      $('#user-answer-files').on('change', function() {
        // TODO: Сделать удаление числа, если оно 0

        if (this.files.length != 0) {
          let div = document.getElementById("div-attachedAnswerFiles");

          let permitted_file_names = [];
          Object.entries(this.files).forEach(file => {
            if (!isAvailableFileExt(file[1].name)) {
              // alert("Внимание! Файл с таким расширением не может быть прикреплён в качестве файла проекта!");
              permitted_file_names.push(file[1].name);
            } else {

              if (checkIfFileIsExist(answerFiles, file[1].name)) {
                // alert("Внимание! Файл с таким названием уже прикреплён!");
              } else {
                add_element(div, file[1].name, "answerFiles[]", answerFiles.length);
                answerFiles.push(file[1]);
              }
            }
          });
          $('#files-answer-count').html((answerFiles.length > 0) ? answerFiles.length : "");

          if (permitted_file_names.length > 0) {
            $('#modalErrorFileExt-h5-title').text("Внимание! Недопустимое расширение");
            if (permitted_file_names.length == 1)
              $('#modalErrorFileExt-p-text').html("Файлы: <strong>" + permitted_file_names.join(", ") + "</strong> не был добавлен. Файл с таким расширением нельзя прикрепить в качестве ответа");
            else
              $('#modalErrorFileExt-p-text').html("Файлы: <strong>" + permitted_file_names.join(", ") + "</strong> не были добавлены. Файлы с такими расширениями нельзя прикрепить в качестве ответа.");
            $('#modalAvailableFileExt-p-text').html("<strong>Допустимые расширения для файлов-ответа:</strong></br>" + getStringAvailableFileExts());
            $('#dialogErrorFileExt').modal("show");
          }
        }
      });

    });

    $(document).bind("click", function(event) {
      $("#div-contextMessageMenu").addClass("d-none");
      current_contextMenu_messageId = null;
      isFromCurrentUserMessage = null
    });

    function markAssignment(mark) {
      if (mark != -1) {
        var userMessage = ajaxGetMarkMessage(mark);
        if (sendMessage(userMessage, null, 2, mark)) {
          document.location.reload();
        }
        // loadChatLog(true);
      } else
        alert("Не выбрана оценка!");
    }

    function isAvailableFileExt(file_name) {
      let splitted = file_name.split(".");
      let ext = splitted.splice(-1)[0];
      // console.log(AVAILABLE_FILE_EXT);
      if (AVAILABLE_FILE_EXT.includes(ext)) {
        return true;
      }
      return false;
    }

    function getStringAvailableFileExts() {
      let output_exts = "";
      AVAILABLE_FILE_EXT.forEach(ext => {
        output_exts += "." + ext + " ";
      });
      return output_exts;
    }


    function checkIfFileIsExist(arrayFiles, file_name) {
      flag = false;
      arrayFiles.forEach(file => {
        if (file.name == file_name) {
          flag = true;
          return;
        }
      });
      return flag;
    }


    // Обновляет лог чата из БД
    function loadNewMessages() {
      // console.log("LOAD_CHAT_LOG!");

      var formData = new FormData();
      formData.append('assignment_id', <?= $assignment_id ?>);
      formData.append('user_id', <?= $user_id ?>);
      formData.append('load_status', 'new_only');
      $.ajax({
        type: "POST",
        url: 'taskchat_action.php#content',
        cache: false,
        contentType: false,
        processData: false,
        data: formData,
        dataType: 'html',
        success: function(response) {
          // console.log(response);
          $('#chat-box').innerHTML += response;
        },
        complete: function() {
          // Скролим чат вниз при появлении новых сообщений
          // $('#chat-box').scrollTop($('#chat-box').prop('scrollHeight'));
        }
      });
    }



    function loadChatLog(first_scroll = false) {
      console.log("loadChatLog");
      // TODO: Обращаться к обновлению чата только в случае, если добавлиось новое, ещё не прочитанное сообщение
      $('#chat-box').load('taskchat_action.php#content', {
        assignment_id: <?= $assignment_id ?>,
        user_id: <?= $user_id ?>,
        load_status: 'full'
      }, function() {

        selectedMessages.forEach(message_id => {
          $('#btn-message-' + message_id).addClass("bg-info");
        });

        // После первой загрузки страницы скролим чат вниз до новых сообщений или но самого низа
        if (first_scroll) {
          if ($('#new-messages').length == 0) {
            $('#chat-box').scrollTop($('#chat-box').prop('scrollHeight'));
          } else {
            $('#chat-box').scrollTop($('#new-messages').offset().top - $('#chat-box').offset().top - 10);
          }
        }
      })
    }


    function sendMessage(userMessage, userFiles, typeMessage, mark = null, defaultMessage = false) {

      if ($.trim(userMessage) == '' && userFiles.length < 1) {
        // console.log("ФАЙЛЫ НЕ ПРИКРЕПЛЕНЫ");
        if (defaultMessage)
          alert("Нельзя отправить пустое сообщение!");
        else
          alert("Для отправки ответа задание необходимо прикрепить файлы!");
        return false;
      }

      let flag = true;

      var formData = new FormData();
      formData.append('assignment_id', <?= $assignment_id ?>);
      formData.append('user_id', <?= $user_id ?>);
      formData.append('message_text', userMessage);
      formData.append('type', typeMessage);
      if (userFiles != null && userFiles.length > 0) {
        // console.log("EEEEEEEEEE");
        //formData.append('MAX_FILE_SIZE', 5242880); // TODO Максимальный размер загружаемых файлов менять тут. Сейчас 5мб
        $.each(userFiles, function(key, input) {
          // console.log(input.size);
          // console.log(<?= $MAX_FILE_SIZE ?>*0.8);
          if (input.size < <?= $MAX_FILE_SIZE ?> * 0.8) {
            formData.append('files[]', input);
          } else {
            alert("Размер отправленного файла превышает допустимый размер");
            flag = false;
          }
        });
      } else if (typeMessage == 2) {
        formData.append('mark', mark);
      }

      if (flag == false) {
        return false;
      } else {

        // console.log('message_text =' + userMessage);
        // console.log('type =' + typeMessage);

        $.ajax({
          type: "POST",
          url: 'taskchat_action.php#content',
          cache: false,
          contentType: false,
          processData: false,
          data: formData,
          dataType: 'html',
          success: function(response) {
            // $("#chat-box").html(response);
          },
          complete: function() {
            // Скролим чат вниз после отправки сообщения
            $('#chat-box').scrollTop($('#chat-box').prop('scrollHeight'));
          }
        });
      }


      return true;
    }


    function add_element(parent, name, tag, id) {
      let element = document.createElement("div");

      //element.classList.add("col-lg-2");
      element.setAttribute("class", "d-flex justify-content-between align-items-center p-2 me-2 mt-1 badge badge-light text-wrap teacher-element");
      element.id = "messageFile-" + id;

      //  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-fill" viewBox="0 0 16 16">
      //   <path d="M4 0h5.293A1 1 0 0 1 10 .293L13.707 4a1 1 0 0 1 .293.707V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2zm5.5 1.5v2a1 1 0 0 0 1 1h2l-3-3z"/>
      // </svg>

      let svg = document.createElementNS("http://www.w3.org/2000/svg", 'svg');
      svg.classList.add("bi", "bi-file-earmark-fill");
      svg.setAttribute("width", "16");
      svg.setAttribute("height", "16");
      svg.setAttribute("fill", "currentColor");
      svg.setAttribute("viewBox", "0 0 16 16");
      svg.setAttribute("xmlns", "http://www.w3.org/2000/svg");
      let path = document.createElementNS("http://www.w3.org/2000/svg", 'path');
      path.setAttribute("d", "M4 0h5.293A1 1 0 0 1 10 .293L13.707 4a1 1 0 0 1 .293.707V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2zm5.5 1.5v2a1 1 0 0 0 1 1h2l-3-3z");
      svg.appendChild(path);

      element.appendChild(svg);

      let text = document.createElement("span");
      text.classList.add("p-1", "me-1");
      text.setAttribute("style", "font-size: 13px; /*border-right: 1px solid; border-color: grey;*/");
      text.innerText = name;

      let button = document.createElement("button");
      button.classList.add("btn-close");

      button.setAttribute("aria-label", "Close");
      button.setAttribute("type", "button");
      button.setAttribute("style", "font-size: 10px;");

      element.append(text);
      element.append(button);
      parent.append(element);

      button.addEventListener('click', function(event) {
        if (tag == "answerFiles[]") {
          let index_file = answerFiles.findIndex((file) => file.name == name);
          if (index_file != -1) {
            answerFiles.splice(index_file, 1);
          }
          if (answerFiles.length > 0)
            $('#files-answer-count').html(answerFiles.length);
          else
            $('#files-answer-count').html("");
        } else if (tag == "messageFiles[]") {
          let index_file = messageFiles.findIndex((file) => file.name == name);
          if (index_file != -1) {
            messageFiles.splice(index_file, 1);
          }
          if (messageFiles.length > 0)
            $('#files-count').html(messageFiles.length);
          else
            $('#files-count').html("");
        }

        parent.removeChild(event.target.parentNode);


      });

    }


    var selectedMessages = [];
    var isFromCurrentUserSelectedMessages = [];

    function deselectMessage(message_id) {
      selectMessage(message_id, null);
    }

    function selectMessage(message_id, isFromCurrentUser) {
      if (selectedMessages.includes(message_id)) {
        let index = selectedMessages.indexOf(message_id);
        selectedMessages.splice(index, 1);
        isFromCurrentUserSelectedMessages.splice(index, 1);
        $('#btn-message-' + message_id).removeClass("bg-info");
        if (selectedMessages.length == 0)
          $('#btn-group-more').addClass("d-none");
      } else {
        selectedMessages.push(message_id);
        isFromCurrentUserSelectedMessages.push(isFromCurrentUser);
        $('#btn-message-' + message_id).addClass("bg-info");
        $('#btn-group-more').removeClass("d-none");
      }


      // Показывать кнопку "Удалить сособщение, если оно своё или нет, если не своё"
      let flag = true;
      selectedMessages.forEach((message_id, index) => {
        if (!isFromCurrentUserSelectedMessages[index]) {
          flag = false;
        }
      });
      if (flag)
        $('#a-messages-delete').removeClass("d-none");
      else
        $('#a-messages-delete').addClass("d-none");
    }

    function copyMessageText() {
      let message_text = $('#p-message-' + current_contextMenu_messageId + '-text').text().trim();
      alert("Текст успешно скопирован в буфер обмена!")
      navigator.clipboard.writeText(message_text).then(function() {}, function(err) {
        console.error('Произошла ошибка при копировании текста: ', err);
      });
    }

    function selectMessageWithContextMenu() {
      selectMessage(current_contextMenu_messageId, isFromCurrentUserMessage);
    }

    function resendMessageToCurrentChat() {
      resendMessages(ASSIGNMENT_ID, USER_ID, true, [current_contextMenu_messageId]);
    }

    function resendMessageToConversationChat(conversation_assignment_id) {
      resendMessages(conversation_assignment_id, USER_ID, false, [current_contextMenu_messageId]);
    }

    function deleteMessage() {
      deleteMessages(ASSIGNMENT_ID, USER_ID, [current_contextMenu_messageId]);
    }



    function resendMessages(assignment_id, user_id, this_chat, selected_messages = null) {

      var formData = new FormData();
      formData.append('assignment_id', assignment_id);
      formData.append('user_id', user_id);
      if (selected_messages == null)
        formData.append('selected_messages', JSON.stringify(selectedMessages));
      else
        formData.append('selected_messages', JSON.stringify(selected_messages));
      formData.append('resendMessages', true);

      $.ajax({
        type: "POST",
        url: 'taskchat_action.php#content',
        cache: false,
        contentType: false,
        processData: false,
        async: false,
        data: formData,
        dataType: 'html',
        success: function(response) {
          if (this_chat) {
            $("#chat-box").html(response);
            for (let i = 0; i < selectedMessages.length;) {
              deselectMessage(selectedMessages[i]);
            }
          }
        },
        complete: function() {
          // Скролим чат вниз после отправки сообщения
          if (this_chat) {
            $('#chat-box').scrollTop($('#chat-box').prop('scrollHeight'));
            console.log($('#chat-box').prop('scrollHeight'));
          }
        }
      });

      document.location.href = "taskchat.php?assignment=" + assignment_id;
    }

    function deleteMessages(assignment_id, user_id, selected_messages = null) {
      console.log("selected_messages", selected_messages);
      var formData = new FormData();
      formData.append('assignment_id', assignment_id);
      formData.append('user_id', user_id);
      if (selected_messages == null)
        formData.append('selected_messages', JSON.stringify(selectedMessages));
      else
        formData.append('selected_messages', JSON.stringify(selected_messages));

      formData.append('deleteMessages', true);

      $.ajax({
        type: "POST",
        url: 'taskchat_action.php#content',
        cache: false,
        contentType: false,
        processData: false,
        data: formData,
        dataType: 'html',
        success: function(response) {
          for (let i = 0; i < selectedMessages.length;) {
            deselectMessage(selectedMessages[i]);
          }
          // loadChatLog();
        },
        complete: function() {
          // Скролим чат вниз после отправки сообщения
          $('#chat-box').scrollTop($('#chat-box').prop('scrollHeight'));
        }
      });

      return true;
    }

    function ajaxGetMarkMessage(mark) {
      var formData = new FormData();
      formData.append('flag', "GetMarkMessage");
      formData.append('mark', mark);

      let message_text = "";
      $.ajax({
        type: "POST",
        url: 'messageHandler.php#content',
        cache: false,
        async: false,
        contentType: false,
        processData: false,
        data: formData,
        dataType: 'html',
        success: function(response) {
          message_text = response.trim();
        },
        complete: function() {}
      });

      return message_text;
    }
  </script>




  <style>
    .disabled {
      pointer-events: none;
      cursor: default;
      opacity: 0.6;
    }

    .dropdown-menu li {
      position: relative;
    }

    .dropdown-menu .dropdown-submenu {
      display: none;
      position: absolute;
      left: 100%;
      top: -7px;
    }

    .dropdown-menu .dropdown-submenu-left {
      right: 100%;
      left: auto;
    }

    .dropdown-menu>li:hover>.dropdown-submenu {
      display: block;
    }
  </style>

</body>

</html>
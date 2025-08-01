<!DOCTYPE html>
<html lang="en">

<?php
require_once("common.php");
require_once("dbqueries.php");
require_once("utilities.php");
require_once("resultparse.php");

$au = new auth_ssh();
checkAuLoggedIN($au);
$User = new User((int)$au->getUserId());
echo "<script>var user_role=" . $User->role . ";</script>";
$user_id = $User->id;

$MAX_FILE_SIZE = getMaxFileSize();

$assignment_id = 0;
if (array_key_exists('assignment', $_GET)) {
  $assignment_id = $_GET['assignment'];
  $Assignment = new Assignment((int)$assignment_id);
} else {
  //echo "Некорректное обращение";
  //http_response_code(400);
  header('Location: index.php');
  exit;
}
//echo $assignment_id. "<br>";

if ($au->isStudent() && (!$Assignment->isVisibleForStudent() || !$Assignment->isAccess())) {
  header('Location:index.php');
  exit;
}


$task_title = '';
$task_description = '';
$task_finish_limit = '';
$task_status_code = '';
$assignment_status = '';
$task_max_mark = 5;
// TODO: Проверить на наличие конфликата!
$query = select_ax_assignment_with_task_by_id($assignment_id);
$result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
$row = pg_fetch_assoc($result);
if ($row) {
  $task_id = $row['task_id'];
  $task_finish_limit = $row['finish_limit'];
  $task_title = $row['title'];
  $task_status_code = $row['status_code'];
  $assignment_status = $row['status'];
  $task_description = $row['description'];
  $task_max_mark = (int)$row['mark'];
} else {
  echo "Такого assignment не существует";
  //header('Location: index.php');
  exit;
}

$Task = new Task((int)$task_id);


// getTaskFiles($dbconnect, $task_id);

$last_commit_id = -1;
if (array_key_exists('commit', $_GET)) {
  $last_commit_id = $_GET['commit'];
  $lastCommit = new Commit($last_commit_id);
} else {
  if (!$au->isAdminOrPrep() && !$Assignment->checkStudent($User->id)) {
    header('Location: index.php');
    exit;
  }

  if ($au->isStudent())
    $lastCommit = $Assignment->getLastCommitForStudent();
  else
    $lastCommit = $Assignment->getLastCommitForTeacher();

  if ($lastCommit != null) {
    $last_commit_id = $lastCommit->id;
  } else {
    $last_commit_id = -1;
  }
}

$nowCommit = null;
if ($last_commit_id != -1) {
  $nowCommit = $lastCommit;
} else {
  $nowCommit = new Commit($Assignment->id, null, $au->getUserId(), 0, null);
  $Assignment->addCommit($nowCommit->id);
  $nowCommit->addFiles($Task->getInitialCodeFiles());
}

$nowCommitUser = new User($nowCommit->student_user_id);

$solutionFiles = $nowCommit->getFiles();

$readOnly = $nowCommit->isNotEdit($User->isStudent());
if ($readOnly)
  echo "<script>const IS_EDITABLE=true;</script>";
else
  echo "<script>const IS_EDITABLE=false;</script>";

$query = select_page_by_task_id($task_id);
$result = pg_query($dbconnect, $query);
$page_id = pg_fetch_assoc($result)['page_id'];

//$query = select_discipline_name_by_page($page_id, 1);
//$result = pg_query($dbconnect, $query);
//$page_name = pg_fetch_assoc($result)['name'];

$query = select_ax_page_short_name($page_id);
$result = pg_query($dbconnect, $query);
$page_name = pg_fetch_assoc($result)['short_name'];

$Page = new Page((int)getPageByAssignment((int)$Assignment->id));
$page_title = getEditorPageTitle();
$previous_page_title = getTaskchatPageTitle($Task);
$previous_page_url = 'taskchat.php?assignment=' . $assignment_id;
show_head($page_title, array('./src/npm/marked/marked.min.js'));
?>

<link rel="stylesheet" href="css/mdb/rdt.css" />

<link rel="stylesheet" href="./node_modules/xterm/css/xterm.css" />
<script src="./node_modules/xterm/lib/xterm.js"></script>
<script src="./node_modules/xterm-addon-attach/lib/xterm-addon-attach.js"></script>
<script src="./node_modules/xterm-addon-fit/lib/xterm-addon-fit.js"></script>
<!-- <script src="./node_modules/xterm-addon-canvas/lib/xterm-addon-canvas.js"></script> -->

<body id="body" style="overflow-x:hidden">

  <?php
  // XXX: ПРОВЕРИТЬ
  show_header(
    $dbconnect,
    $page_title,
    array(
      $previous_page_title => $previous_page_url,
      $page_title => $_SERVER['REQUEST_URI']
    ),
    $User
  );
  ?>

  <main class="container-fluid overflow-hidden mx-0 px-0 no-gutters">
    <div id="div-blur" class="blur-background d-none" onclick="makeElementDefaultScreen()"></div>
    <div id="div-body" class="pt-2 mx-0 w-100 row no-gutters">
      <div class="d-flex justify-content-between col-md-8 pe-1">
        <div class="w-25 d-flex flex-column me-2">

          <div class="d-none d-sm-block d-print-block" style="border-bottom: 1px solid;">
            <ul id="ul-files" class="tasks__list list-group-flush w-100 px-0" style="width: 100px;">
              <li class="list-group-item disabled px-0">Файлы</li>

              <?php if ($nowCommit->isNotEdit($User->isStudent())) { ?>
                <p id="p-no-files" class="d-none">Файлы отсутсвуют</p>
              <?php } ?>

              <?php
              foreach ($solutionFiles as $i => $File) { ?>
                <li id="openFile" class="tasks__item list-group-item w-100 d-flex justify-content-between align-items-center px-0" style="cursor: pointer;" data-orderId="<?= $i ?>">
                  <div class="px-1 align-items-center text-primary">
                    <?= getSVGByFileType($File->type) ?>
                  </div>
                  <div id="div-fileName" class="px-1" style="width: 55%;">
                    <input id="<?= $File->id ?>" type="button" class="form-control-plaintext form-control-sm validationCustom input-file not-editing" value="<?= $File->name_without_prefix ?>">
                  </div>
                  <!-- <button type="button" class="btn btn-sm ms-0 me-1 float-right" id="openFile">
                  getSVGByCommitType($nowCommit->type)
                </button> -->
                  <div class="dropdown align-items-center h-100 ms-1 me-1" id="btn-group-moreActionsWithFile">
                    <button class="btn btn-primary py-1 px-2" type="button" id="ul-dropdownMenu-moreActionsWithFile" data-mdb-toggle="dropdown" aria-expanded="false">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-three-dots-vertical" viewBox="0 0 16 16">
                        <path d="M9.5 13a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z" />
                      </svg>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="ul-dropdownMenu-moreActionsWithFile">
                      <?php if (!$nowCommit->isNotEdit($User->isStudent())) { ?>
                        <li>
                          <a type="button" class="dropdown-item align-items-center" id="a-renameFile">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pen-fill" viewBox="0 0 16 16">
                              <path d="m13.498.795.149-.149a1.207 1.207 0 1 1 1.707 1.708l-.149.148a1.5 1.5 0 0 1-.059 2.059L4.854 14.854a.5.5 0 0 1-.233.131l-4 1a.5.5 0 0 1-.606-.606l1-4a.5.5 0 0 1 .131-.232l9.642-9.642a.5.5 0 0 0-.642.056L6.854 4.854a.5.5 0 1 1-.708-.708L9.44.854A1.5 1.5 0 0 1 11.5.796a1.5 1.5 0 0 1 1.998-.001z" />
                            </svg>
                            &nbsp;
                            Переименовать
                          </a>
                        </li>
                      <?php } ?>
                      <li>
                        <a class="dropdown-item align-items-center" href="<?= $File->getDownloadLink() ?>" target="_blank">
                          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-download" viewBox="0 0 16 16">
                            <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z" />
                            <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z" />
                          </svg>
                          &nbsp;
                          Скачать
                        </a>
                      </li>
                    </ul>
                  </div>
                  <?php if (!$nowCommit->isNotEdit($User->isStudent())) { ?>
                    <button type="button" class="btn btn-link float-right mx-1 py-0 px-2" id="delFile"><i class="fas fa-times fa-lg"></i></button>
                  <?php } ?>
                </li>
              <?php } ?>

              <?php if (!$nowCommit->isNotEdit($User->isStudent())) { ?>
                <div id="div-add-new-file" class="input-group mt-2 mb-0">
                  <div class="input-group-prepend">
                    <span class="input-group-text w-100 h-100" id="basic-addon1">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark" viewBox="0 0 16 16">
                        <path d="M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h5.5L14 4.5zm-3 0A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V4.5h-2z" />
                      </svg>
                    </span>
                  </div>
                  <input id="input-name-newFile" type="text" class="form-control validationCustom" placeholder="Новый файл" required>
                  <button type="button" class="btn btn-outline-primary ms-1 px-3" id="btn-newFile">
                    <i class="far fa-plus-square fa-lg"></i>
                  </button>
                </div>
                <div id="div-name-newFile-error" class="mb-2 d-none text-danger" style="font-size: 80%;">
                  Не введено имя файла
                </div>

                <!-- <li id="li-new-file" class="list-group-item w-100 d-flex justify-content-between px-0">
              <div class="px-1 align-items-center"><i class="fas fa-file-code fa-lg"></i></div>
              <input type="text" class="form-control-plaintext form-control-sm validationCustom" id="x" value="Новый файл" required>
              <button type="button" class="btn btn-sm px-3" id="newFile"> <i class="far fa-plus-square fa-lg"></i></button>
            </li>   -->
              <?php } ?>
            </ul>
          </div>

          <?php if ($au->isAdminOrPrep() || $Assignment->checkStudent($User->id)) {

            if ($au->isStudent())
              $Commits = $Assignment->getCommitsForStudent();
            else
              $Commits = $Assignment->getCommitsForTeacher();
          ?>

            <div class="flex-column mt-3">
              <p><strong>История коммитов</strong></p>
              <div id="div-history-commit-btns" class="flex-column mb-5 pe-3" style="<?= (count($Commits) > 10) ? "overflow-y: scroll;" : "overflow-y: hidden;" ?> max-height: 700px; min-height: 0px;">

                <?php foreach ($Commits as $i => $Commit) {
                  $commitUser = new User($Commit->student_user_id); ?>
                  <div class="d-flex <?= ($i == count($Commits) - 1) ? "mb-4" : "mb-1" ?>">
                    <button <?php if ($Commit->id == $nowCommit->id) { ?> class="btn btn-<?php if ($Commit->isNotEdit($User->isStudent())) {
                                                                                            echo "success";
                                                                                          } else {
                                                                                            echo "primary";
                                                                                          } ?> 
                     d-flex align-items-center justify-content-between w-100 px-3 text-white" disabled <?php } else if ($Commit->isNotEdit($User->isStudent())) { ?> class="btn btn-outline-success
                      d-flex align-items-center justify-content-between w-100 px-3" <?php } else { ?> class="btn btn-outline-primary
                        d-flex align-items-center justify-content-between w-100 px-3" <?php } ?> onclick="window.location='editor.php?assignment=<?= $Assignment->id ?>&commit=<?= $Commit->id ?>'">

                      <div class="flex-column">
                        <p class="p-0 m-0"><?= $Commit->getConvertedDateTime() ?> </p>
                        <p class="p-0 m-0" style="font-weight: bold;"><?= $commitUser->getOfficialFIO() ?></p>
                      </div>
                      <?php
                      // if ($Commit->id == $last_commit_id)
                      //   echo '~ТЕКУЩИЙ~'; 
                      ?>
                      <?= getSVGByCommitType($Commit->type) ?>
                    </button>
                    <?php if (!$Commit->isNotEdit($User->isStudent()) && count($Commits) > 1 && $commitUser->id == $User->id) { ?>
                      <button class="btn btn-link bg-danger text-white ms-1 p-2" type="button" onclick="deleteCommit(<?= $Commit->id ?>)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-lg" viewBox="0 0 16 16">
                          <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8 2.146 2.854Z" />
                        </svg>
                      </button>
                    <?php } ?>
                  </div>
                <?php } ?>
              </div>
            </div>

          <?php } ?>

        </div>
        <div class="w-75 px-0">
          <div class="d-flex mb-1">
            <div class="w-100 me-1">
              <select class="form-select" aria-label=".form-select" id="language">
                <?php foreach (getEditorLanguages() as $language) { ?>
                  <option value="<?= $language["monaco_editor_name"] ?>"><?= $language["name"] ?></option>
                <?php } ?>
              </select>
            </div>
            <?php if ($au->isAdminOrPrep() || $Assignment->checkStudent($User->id)) { ?>
              <button id="btn-new-commit" class="btn btn-secondary w-100 align-items-center me-1 px-0" type="button">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus-lg" viewBox="0 0 16 16">
                  <path fill-rule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2Z" />
                </svg>
                &nbsp;&nbsp;
                Дублировать коммит
              </button>
              <button id="btn-save" class="btn btn-primary w-75 me-1 px-0" type="button" <?= ($nowCommit && $nowCommit->isNotEdit($User->isStudent())) ? "disabled" : "" ?>>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check2-all" viewBox="0 0 16 16">
                  <path d="M12.354 4.354a.5.5 0 0 0-.708-.708L5 10.293 1.854 7.146a.5.5 0 1 0-.708.708l3.5 3.5a.5.5 0 0 0 .708 0l7-7zm-4.208 7-.896-.897.707-.707.543.543 6.646-6.647a.5.5 0 0 1 .708.708l-7 7a.5.5 0 0 1-.708 0z" />
                  <path d="m5.354 7.146.896.897-.707.707-.897-.896a.5.5 0 1 1 .708-.708z" />
                </svg>
                &nbsp;&nbsp;
                Сохранить
                <div id="spinner-save" class="spinner-border d-none ms-3" role="status" style="width: 1rem; height: 1rem;">
                  <span class="sr-only">Loading...</span>
                </div>
              </button>
              <button id="btn-synch" class="btn btn-outline-primary w-50 px-0" type="button">
                <svg id="svg-btn-synch" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-repeat" viewBox="0 0 16 16">
                  <path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41m-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9" />
                  <path fill-rule="evenodd" d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5 5 0 0 0 8 3M3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9z" />
                </svg>
                &nbsp;&nbsp;
                Synch
              </button>
            <?php } ?>
          </div>

          <div class="w-100" style="position:relative;">
            <div id="spinner-load-editor" class="d-flex align-items-center justify-content-center bg-light border border-4 border-reset rounded w-100 h-100 line-pattern" style="position:absolute; z-index:1">
              <div class="d-flex align-items-center justify-content-center bg-light text-reset border border-2 border-reset rounded px-2" style="z-index:2;">
                <div class="spinner-border me-2" role="status" style="width: 1rem; height: 1rem;">
                </div>
                <span class="">Загрузка редактора кода...</span>
              </div>
            </div>
            <div id="div-shell-editor" class="embed-responsive embed-responsive-4by3 monaco-border-not-editable h-100">
              <div id="container" class="embed-responsive-item h-100"></div>
            </div>
          </div>

          <div class="d-flex justify-content-between mt-1">
            <!--<button type="button" class="btn btn-outline-primary" id="check" style="width: 50%;"> Отправить на проверку</button>-->
            <!--<form action="taskchat.php" method="POST" style="width:50%">-->
            <input type="hidden" name="assignment" value="<?= $assignment_id ?>">
            <?php
            if ($au->isAdminOrPrep()) {  // Оценить отправленное на проверку задание 
            ?>
              <button type="button" class="btn btn-success me-1" id="check" style="width: 100%;" assignment="<?= $assignment_id ?>" commit="<?= $nowCommit->id ?>" <?= (($Assignment->isWaitingCheck() && $nowCommit->isChecking() && count($solutionFiles) > 0) ? "" : "disabled") ?>>Отправить коммит с замечаниями</button>
            <?php
            } else if ($Assignment->checkStudent($User->id)) { // Отправить задание на проверку
            ?>
              <button type="button" class="btn btn-success" id="check" style="width: 100%;" assignment="<?= $assignment_id ?>" commit="<?= $nowCommit->id ?>" <?= (($assignment_status == -1 || count($solutionFiles) < 1 || !$nowCommit->isInProcess()) ? "disabled" : "") ?>>
                Отправить на проверку</button>
            <?php
            }
            ?>
            <!--</form>-->
            <button type="button" class="btn btn-outline-primary" id="run" style="width: 50%;">Запустить в консоли</button>

          </div>

        </div>

        <!--ТЕСТ РЕДАКТОРА -->
        <script src="./node_modules/monaco-editor/min/vs/loader.js"></script>

      </div>
      <div class="col-md-4 ps-1">
        <div class="d-none d-sm-block d-print-block border rounded mb-5">
          <div class="tab d-flex justify-content-between">
            <button id="defaultOpen" class="tablinks" onclick="openCity('Task')" data-tab-name="Task">Задание</button>
            <button class="tablinks" onclick="openCity('Console')" data-tab-name="Console">Консоль</button>
            <button class="tablinks" onclick="openCity('Test')" data-tab-name="Test">Проверки</button>
            <?php if ($au->isAdminOrPrep() || $Assignment->checkStudent($User->id)) { ?>
              <button class="tablinks" onclick="openCity('Chat')" data-tab-name="Chat">Чат</button>
            <?php } ?>
          </div>

          <div id="Task" class="tabcontent overflow-auto fs-8" style="height: 88%;">
            <div class="d-flex flex-column">

              <div class="d-flex flex-column align-items-start h-auto mb-2">
                <?php foreach (array_merge($Page->getTeachers(), $Assignment->getStudents()) as $i => $assignmentUser) { ?>
                  <a href="profile.php?user_id=<?= $assignmentUser->id ?>"
                    class="h-auto badge <?= ($assignmentUser->isTeacher()) ? "badge-success" : "badge-primary" ?> border-0 rounded-3 py-0 px-2 me-2 mb-2 hover-shadow d-flex align-items-center">
                    <div class="shadow-none p-1 m-0 bg-image me-2"
                      style="/*left: -<?= $i * 5 ?>%*/">
                      <?php if ($assignmentUser->getImageFile() != null) { ?>
                        <div class="embed-responsive embed-responsive-1by1" style="display: block;">
                          <div class="embed-responsive-item">
                            <img class="h-100 w-100 p-0 m-0 rounded-circle user-icon" style="vertical-align: unset; /*transform: translateX(-30%);*/" src="<?= $assignmentUser->getImageFile()->download_url ?>" />
                          </div>
                        </div>
                      <?php } else { ?>
                        <div>
                          <svg class="h-100 w-100" xmlns="http://www.w3.org/2000/svg" width="20" fill="white" class="bi bi-person-circle" viewBox="0 0 16 16">
                            <path fill-rule="nonzero" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z" />
                            <path fill-rule="nonzero" d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" />
                          </svg>
                        </div>
                      <?php } ?>
                    </div>
                    <span><?= $assignmentUser->getFIO() ?></span>
                  </a>
                <?php } ?>
              </div>

              <div>
                <?php if ($task_description != "") { ?>
                  <p id="TaskDescr"><?= $task_description ?></p>
                  <script>
                    document.getElementById('TaskDescr').innerHTML =
                      marked.parse(document.getElementById('TaskDescr').innerHTML);
                  </script>
                <?php } else { ?>
                  <h6 class="mt-2">Описание задания отсутствует</h6>
                <?php } ?>
                <div>
                  <?php
                  if ($User->isTeacher() || $User->isAdmin())
                    $task_files = $Task->getVisibleFiles();
                  else
                    $task_files = $Task->getStudentFilesToTaskchat();

                  if ($task_files) { ?>
                    <p class="mb-1"><strong>Файлы, приложенные к заданию:</strong></p>
                    <?= showFiles($task_files); ?>
                  <?php }
                  ?>
                </div>
              </div>
            </div>

          </div>

          <div id="Console" class="tabcontent mx-0 px-0">
            <div class="d-flex justify-content-between align-items-cemter px-2">
              <h3>Консоль</h3>
              <div class="d-flex align-items-center ">
                <button class="btn btn-outline-primary py-1 px-2 d-flex align-items-center" onclick="makeElementFullScreen('terminal')">
                  <span class="me-2">Развернуть</span>
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrows-fullscreen" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M5.828 10.172a.5.5 0 0 0-.707 0l-4.096 4.096V11.5a.5.5 0 0 0-1 0v3.975a.5.5 0 0 0 .5.5H4.5a.5.5 0 0 0 0-1H1.732l4.096-4.096a.5.5 0 0 0 0-.707m4.344 0a.5.5 0 0 1 .707 0l4.096 4.096V11.5a.5.5 0 1 1 1 0v3.975a.5.5 0 0 1-.5.5H11.5a.5.5 0 0 1 0-1h2.768l-4.096-4.096a.5.5 0 0 1 0-.707m0-4.344a.5.5 0 0 0 .707 0l4.096-4.096V4.5a.5.5 0 1 0 1 0V.525a.5.5 0 0 0-.5-.5H11.5a.5.5 0 0 0 0 1h2.768l-4.096 4.096a.5.5 0 0 0 0 .707m-4.344 0a.5.5 0 0 1-.707 0L1.025 1.732V4.5a.5.5 0 0 1-1 0V.525a.5.5 0 0 1 .5-.5H4.5a.5.5 0 0 1 0 1H1.732l4.096 4.096a.5.5 0 0 1 0 .707" />
                  </svg>
                </button>
              </div>
            </div>
            <div id="terminal" class="mx-0 px-0"></div>
          </div>

          <div id="Test" class="tabcontent">
            <div class="d-flex flex-column">
              <?php if (!$au->isStudent()) { ?>
                <div class="d-flex justify-content-between mb-2 w-100">
                  <a href="taskedit.php?task=<?= $Task->id ?>" class="btn btn-outline-danger d-flex justify-content-center me-2" target="_blank">
                    <?php getSVGByFileType(2); ?>
                    <span>&nbsp;Настроить автотесты</span>
                  </a>
                  <a href="taskassign.php?assignment_id=<?= $Assignment->id ?>" class="btn btn-outline-danger d-flex justify-content-center" target="_blank">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-gear" viewBox="0 0 16 16">
                      <path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492M5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0" />
                      <path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 0 0 1.116 2.692l.318.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.184l-.291-.159a1.873 1.873 0 0 0-2.693 1.116l-.094.318c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.692-1.115l-.292.16c-.764.415-1.6-.42-1.184-1.185l.159-.291A1.873 1.873 0 0 0 1.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 0 0 3.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.292.159a1.873 1.873 0 0 0 2.692-1.115z" />
                    </svg>
                    <span>&nbsp;Изменить конфигурацию</span>
                  </a>
                </div>
              <?php } ?>

              <?php


              $hasCheckResults = false;

              if (!$last_commit_id || $last_commit_id == "") {
                $resAC = pg_query($dbconnect, select_last_commit_id_by_assignment_id($assignment_id));
                $last_commit_id = pg_fetch_assoc($resAC)['id'];
              }

              $checkres = null;
              if ($last_commit_id && $last_commit_id != "") {
                $resultC = pg_query($dbconnect, "select autotest_results res from ax.ax_solution_commit where id = " . $last_commit_id);
                if ($resultC && pg_num_rows($resultC) > 0) {
                  $rowC = pg_fetch_assoc($resultC);
                  if (array_key_exists('res', $rowC) && $rowC['res'] != "null" && $rowC['res'] != null) {
                    $checkres = json_decode($rowC['res'], true);
                    $hasCheckResults = true;
                  }
                }
              }

              $result = pg_query($dbconnect,  "select ax.ax_assignment.id aid, ax.ax_task.id tid, ax.ax_assignment.checks achecks, ax.ax_task.checks tchecks " .
                " from ax.ax_assignment inner join ax.ax_task on ax.ax_assignment.task_id = ax.ax_task.id where ax.ax_assignment.id = " . $assignment_id);
              $row = pg_fetch_assoc($result);
              $checks = $row['achecks'];
              if ($checks == null)
                $checks = $row['tchecks'];
              if ($checks == null)
                $checks = getDefaultChecksPreset();
              $checks = json_decode($checks, true);

              $tools_not_enabled = [];
              foreach ($checks['tools'] as $key => $tool_json) {
                if (!$tool_json['enabled']) {
                  array_push($tools_not_enabled, $key);
                }
              }
              foreach ($tools_not_enabled as $key) {
                unset($checks['tools'][$key]);
              }

              ?>

              <!-- <div>
              Плашка над проверками о том, что результаты неактуальны (активна при изменении кода)
              <span id="span-checks-old" class="btn-danger <?= ($hasCheckResults) ? "" : "d-none" ?>">Обратите внимание! Результаты проверки неактуальны.</span>
            </div> -->

              <div id="div-check-results">
                <?php
                echo "<script>var CONFIG_TOOLS=" . json_encode($checks) . "; </script>";
                if ($checkres)
                  echo "<script>var RESULT_TOOLS=" . json_encode($checkres) . "; </script>";
                $accord = getAutotestsAccordionHtml($checks, $checks, $checkres, $au->isStudent());
                if (count($accord) > 0) {
                  echo show_accordion('checkres', $accord, "5px");
                } else { ?>
                  <p class="h6 my-2"><strong>Отсутствуют проверки, доступные для запуска.</strong></p>
                <?php } ?>
              </div>
            </div>

            <input type="hidden" name="commit" value="<?= $last_commit_id ?>">

            <div class="d-flex flex-row justify-content-between my-1 w-100 align-items-start">

              <?php if (count($accord) > 0) { ?>
                <button id="startTools" type="button" class="btn btn-outline-primary mt-1 mb-2" name="startTools">Запустить проверки</button>
              <?php } else { ?>
                <div class="w-50"></div>
              <?php } ?>

              <?php if ($au->isAdminOrPrep()) { ?>
                <div class="w-50 flex-column">
                  <?php if ($Task->isMarkNumber()) { ?>
                    <div class="d-flex flex-row">
                      <div class="file-input-wrapper me-1" style="height: fit-content;font-size: small;font-weight: bold;z-index:0;">
                        <select id="checkTask-select-mark" class="form-select" aria-label=".form-select" style="width: auto;" name="mark">
                          <option hidden value="-1"></option>
                          <?php for ($i = 1; $i <= $Task->max_mark; $i++) { ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                          <?php } ?>
                        </select>
                      </div>
                      <button id="button-check" class="btn btn-success d-flex justify-content-center" target="_blank" type="submit" name="submit-check" style="width: 100%; height: fit-content;font-size: small;" onclick="markAssignmentWithoutReload(<?= $Assignment->id ?>, <?= $User->id ?>, $('#checkTask-select-mark').val())">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clipboard-check-fill" viewBox="0 0 16 16">
                          <path d="M6.5 0A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3Zm3 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5h3Z" />
                          <path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1A2.5 2.5 0 0 1 9.5 5h-3A2.5 2.5 0 0 1 4 2.5v-1Zm6.854 7.354-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 0 1 .708-.708L7.5 10.793l2.646-2.647a.5.5 0 0 1 .708.708Z" />
                        </svg>
                        <div class="d-flex align-items-center">
                          &nbsp;&nbsp;Оценить&nbsp;
                          <div id="spinner-mark" class="spinner-border ms-2 d-none" role="status" style="width: 1rem; height: 1rem;">
                            <span class="sr-only">Loading...</span>
                          </div>
                        </div>
                      </button>
                    </div>
                  <?php } else { ?>
                    <div class="d-flex flex-row justify-content-end my-1">
                      <button id="button-check-word" class="btn btn-primary d-flex justify-content-center" target="_blank" type="submit" name="submit-check" style="width: 100%;" onclick="markAssignmentWithoutReload(<?= $Assignment->id ?>, <?= $User->id ?>, 'зачтено')">
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
                      <button id="button-reject-check" class="btn btn-danger d-flex justify-content-center" target="_blank" type="submit" name="reject-check" style="width: 100%;" onclick="markAssignmentWithoutReload(<?= $Assignment->id ?>, <?= $User->id ?>, '')">
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
                </div>

              <?php } ?>
            </div>
          </div>

          <div id="Chat" class="tabcontent">

            <div class="chat-wrapper mb-1">

              <div id="chat-box" style="overflow-y: scroll; max-height: 55%">
                <!-- Вывод сообщений на страницу -->
              </div>


              <div class="d-flex align-items-center">

                <div class="dropdown d-none me-1" id="btn-group-more">
                  <button class="btn btn-primary dropdown-toggle py-1 px-2" type="button" id="ul-dropdownMenu-more" data-mdb-toggle="dropdown" aria-expanded="false">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-three-dots-vertical" viewBox="0 0 16 16">
                      <path d="M9.5 13a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z" />
                    </svg>
                  </button>
                  <ul class="dropdown-menu" aria-labelledby="ul-dropdownMenu-more">
                    <li>
                      <a class="dropdown-item align-items-center" href="#">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left-right me-1" viewBox="0 0 16 16">
                          <path fill-rule="evenodd" d="M1 11.5a.5.5 0 0 0 .5.5h11.793l-3.147 3.146a.5.5 0 0 0 .708.708l4-4a.5.5 0 0 0 0-.708l-4-4a.5.5 0 0 0-.708.708L13.293 11H1.5a.5.5 0 0 0-.5.5zm14-7a.5.5 0 0 1-.5.5H2.707l3.147 3.146a.5.5 0 1 1-.708.708l-4-4a.5.5 0 0 1 0-.708l4-4a.5.5 0 1 1 .708.708L2.707 4H14.5a.5.5 0 0 1 .5.5z" />
                        </svg>
                        Переслать
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right-short" viewBox="0 0 16 16">
                          <path fill-rule="evenodd" d="M4 8a.5.5 0 0 1 .5-.5h5.793L8.146 5.354a.5.5 0 1 1 .708-.708l3 3a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708-.708L10.293 8.5H4.5A.5.5 0 0 1 4 8z" />
                        </svg>
                      </a>
                      <ul class="dropdown-menu dropdown-submenu" style="cursor: pointer;">
                        <?php
                        $conversationTask = $Page->getConversationTask();
                        if ($conversationTask && $conversationTask->getConversationAssignment() != null) { ?>
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

                      </ul>
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
                  <form class="w-100 align-items-center m-0" action="taskchat_action.php" method="POST" enctype="multipart/form-data">
                    <div class="message-input-wrapper h-100 align-items-center p-0 m-0">
                      <div class="file-input-wrapper">
                        <input type="hidden" name="MAX_FILE_SIZE" value="<?= $MAX_FILE_SIZE ?>" />
                        <input id="user-files" type="file" name="user_files[]" class="input-files" multiple>
                        <!-- <label for="user-files"> -->
                        <!-- <i class="fa-solid fa-paperclip"></i> -->
                        <!-- <span id="files-count" class="label-files-count"></span> -->
                        <!-- </label> -->
                        <label for="user-files" class="p-1" style="cursor: pointer;">
                          <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="bi bi-paperclip h-100 w-100" height="30" width="30" viewBox="0 0 16 16">
                            <path d="M4.5 3a2.5 2.5 0 0 1 5 0v9a1.5 1.5 0 0 1-3 0V5a.5.5 0 0 1 1 0v7a.5.5 0 0 0 1 0V3a1.5 1.5 0 1 0-3 0v9a2.5 2.5 0 0 0 5 0V5a.5.5 0 0 1 1 0v7a3.5 3.5 0 1 1-7 0V3z"></path>
                          </svg>
                          <span id="files-count" class="text-success"></span>
                        </label>
                      </div>
                      <textarea name="user-message" id="textarea-user-message" class="border rounded w-100 p-1 mx-2" style="resize:none; overflow:hidden;" placeholder="Напишите сообщение..." rows="1"></textarea>
                      <button type="submit" name="submit-message" id="submit-message">Отправить</button>
                    </div>
                    <div id="div-attachedFiles" class="d-flex flex-wrap mt-2">

                    </div>
                    <!-- <p id="p-errorFileName" class="error" style="display: none;">Ошибка! Файл с таким названием уже существует!</p> -->
                  </form>
                <?php } ?>

              </div>

            </div>

          </div>




          <?php
          if ($Assignment->finish_limit && checkIfDefaultDate(convert_timestamp_to_date($Assignment->finish_limit, "Y-m-d")) != "") { ?>

            <div id="deadline-message" class="deadline-message m-3">
              Время выполнения истекло!
            </div>

            <div id="countdown" class="countdown">
              <div class="countdown-number">
                <span class="days countdown-time"></span>
              </div>
              <div class="countdown-number">
                <span class="hours countdown-time"></span>
              </div>
              <div class="countdown-number">
                <span class="minutes countdown-time"></span>
              </div>
              <div class="countdown-number">
                <span class="seconds countdown-time"></span>
              </div>
            </div>

            <script>
              function getTimeRemaining(endtime) {
                let dateEndTime = Date.parse(endtime);
                let dateNow = Date.parse(new Date());
                var deltaTime = dateEndTime - dateNow;
                var seconds = Math.floor((deltaTime / 1000) % 60);
                var minutes = Math.floor((deltaTime / 1000 / 60) % 60);
                var hours = Math.floor((deltaTime / (1000 * 60 * 60)) % 24);
                var days = Math.floor(deltaTime / (1000 * 60 * 60 * 24));
                return {
                  total: deltaTime,
                  days: days,
                  hours: hours,
                  minutes: minutes,
                  seconds: seconds
                };
              }

              function initializeClock(id, endtime) {
                var clock = document.getElementById(id);
                var daysSpan = clock.querySelector(".days");
                var hoursSpan = clock.querySelector(".hours");
                var minutesSpan = clock.querySelector(".minutes");
                var secondsSpan = clock.querySelector(".seconds");

                function updateClock() {
                  var t = getTimeRemaining(endtime);

                  if (t.total <= 0) {
                    document.getElementById("countdown").className = "hidden";
                    document.getElementById("deadline-message").classList.remove("deadline-message");
                    document.getElementById("deadline-message").classList.add("visible");
                    clearInterval(timeinterval);
                    return true;
                  }

                  refresh_clock_element(daysSpan, null, t.days, t.days + "д.");
                  refresh_clock_element(hoursSpan, daysSpan, t.hours, ("0" + t.hours).slice(-2) + "ч.");
                  refresh_clock_element(minutesSpan, hoursSpan, t.minutes, ("0" + t.minutes).slice(-2) + "м.");
                  refresh_clock_element(secondsSpan, minutesSpan, t.seconds, ("0" + t.seconds).slice(-2) + "с.");


                  // hoursSpan.innerHTML = ("0" + t.hours).slice(-2) + "ч.";
                  // minutesSpan.innerHTML = ("0" + t.minutes).slice(-2) + "м.";
                  // secondsSpan.innerHTML = ("0" + t.seconds).slice(-2) + "с.";

                  function refresh_clock_element(span, previous_span, value, value_str) {
                    if (value <= 0) {
                      if (previous_span === null || previous_span.parentElement.classList.contains('d-none')) {
                        span.parentElement.classList.add("d-none");
                      }
                    } else {
                      span.parentElement.classList.remove("d-none");
                    }
                    span.innerHTML = value_str;
                  }
                }

                updateClock();
                var timeinterval = setInterval(updateClock, 1000);
              }

              function fun() {
                var deadline = "<?= $Assignment->getFinishLimit("m-d-Y H:i:s") ?>"; // for endless timer
                initializeClock("countdown", deadline);
              }
              fun();
            </script>

          <?php
          } else { ?>
            <div class="m-3">
              Время окончания приема работы не задано!
            </div>
          <?php } ?>

        </div>
      </div>

    </div>
    </div>
  </main>

  <div class="modal fade" id="dialogSuccess" tabindex="-1" aria-labelledby="dialogSuccessLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            <?php if ($au->isAdminOrPrep()) { ?>
              Задание проверено!
            <?php } else { ?>
              Задание решено!
            <?php } ?>
          </h5>
          <button type="button" class="btn-close me-2" data-mdb-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p>
            <?php if ($au->isAdminOrPrep()) { ?>
              Коммит с замечаниями отправлен студенту.
            <?php } else { ?>
              Код отправлен преподавателю на проверку.
            <?php } ?>
          </p>
        </div>

        <?php if ($au->isAdminOrPrep()) { ?>
          <div class="modal-footer">
            <?php if ($Task->isMarkNumber()) { ?>
              <div class="d-flex flex-row justify-content-end my-1">
                <div class="file-input-wrapper align-self-center me-1">
                  <select id="dialogCheckTask-select-mark" class="form-select" aria-label=".form-select" style="width: auto;" name="mark">
                    <option hidden value="-1"></option>
                    <?php for ($i = 1; $i <= $Task->max_mark; $i++) { ?>
                      <option value="<?= $i ?>"><?= $i ?></option>
                    <?php } ?>
                  </select>
                </div>
                <button id="button-check" class="btn btn-success d-flex justify-content-center align-self-center me-2" target="_blank" type="submit" name="submit-check" style="width: 100%; height: fit-content !important;" onclick="markAssignmentWithReload(<?= $Assignment->id ?>, <?= $User->id ?>, $('#dialogCheckTask-select-mark').val())">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clipboard-check-fill" viewBox="0 0 16 16">
                    <path d="M6.5 0A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3Zm3 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5h3Z" />
                    <path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1A2.5 2.5 0 0 1 9.5 5h-3A2.5 2.5 0 0 1 4 2.5v-1Zm6.854 7.354-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 0 1 .708-.708L7.5 10.793l2.646-2.647a.5.5 0 0 1 .708.708Z" />
                  </svg>
                  <div class="d-flex align-items-center">
                    &nbsp;&nbsp;Оценить&nbsp;
                    <div id="dialogCheckTask-spinner-mark" class="spinner-border ms-2 d-none" role="status" style="width: 1rem; height: 1rem;">
                      <span class="sr-only">Loading...</span>
                    </div>
                  </div>
                </button>
              </div>
            <?php } else { ?>
              <div class="d-flex flex-row justify-content-end my-1">
                <button id="button-check-word" class="btn btn-primary d-flex justify-content-center" target="_blank" type="submit" name="submit-check" style="width: 100%;" onclick="markAssignmentWithReload(<?= $Assignment->id ?>, <?= $User->id ?>, 'зачтено')">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clipboard-check-fill" viewBox="0 0 16 16">
                    <path d="M6.5 0A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3Zm3 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5h3Z" />
                    <path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1A2.5 2.5 0 0 1 9.5 5h-3A2.5 2.5 0 0 1 4 2.5v-1Zm6.854 7.354-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 0 1 .708-.708L7.5 10.793l2.646-2.647a.5.5 0 0 1 .708.708Z" />
                  </svg>
                  <div class="align-self-center align-items-center">
                    &nbsp;&nbsp;Зачесть&nbsp;
                    <div id="spinner-check-word" class="spinner-border ms-2 d-none" role="status" style="width: 1rem; height: 1rem;">
                      <span class="sr-only">Loading...</span>
                    </div>
                  </div>
                </button>
              </div>
            <?php } ?>
            <div id="div-reject-check" class="d-flex flex-row justify-content-end my-1 <?= ($Assignment->isCompleted()) ? "" : "d-none" ?>">
              <button id="button-reject-check" class="btn btn-danger d-flex justify-content-center" target="_blank" type="submit" name="reject-check" style="width: 100%;" onclick="markAssignmentWithReload(<?= $Assignment->id ?>, <?= $User->id ?>, '')">
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
          </div>
        <?php } ?>
      </div>
    </div>
  </div>

  <!-- <script type="module" src="./js/sandbox.js"></script>-->

  <!-- <script src="js/edior/drag.js"></script> -->
  <script src="js/tab.js"></script>
  <!-- <script src="dist/bundle.js"></script> -->

  <script type="module" src="./js/dist/index.js"></script>
  <!-- <script src="./node_modules/monaco-editor/min/vs/loader.js"></script> -->
  <!-- <script src="js/editorloader.js" type="module"></script> -->

  <!-- Handlers -->
  <script type="text/javascript" src="js/AssignmentHandler.js"></script>
  <script type="text/javascript" src="js/CommitHandler.js"></script>

  <!-- Custom scripts -->
  <script type="text/javascript">
    var ELEMENT_FULL_SCREEN = null;

    function makeElementFullScreen(element_id) {
      ELEMENT_FULL_SCREEN = document.getElementById(element_id);
      document.getElementById("div-blur").classList.remove("d-none");
      window.scrollTo(0, 0);
      document.getElementById("body").classList.add("overflow-hidden");
      ELEMENT_FULL_SCREEN.classList.add("full-screen");
    }

    function onKeyFunction(keyCode, func) {
      if (event.code === keyCode) {
        func();
      }
    }

    function makeElementDefaultScreen() {
      console.log("makeElementDefaultScreen()")
      if (ELEMENT_FULL_SCREEN == null)
        return;
      document.getElementById("div-blur").classList.add("d-none");
      document.getElementById("body").classList.remove("overflow-hidden");
      ELEMENT_FULL_SCREEN.classList.remove("full-screen");
      ELEMENT_FULL_SCREEN = null;
    }

    function showBorders() {
      if (document.querySelector("TaskDescr") == null)
        return;

      var list = document.querySelector("TaskDescr").querySelectorAll("table");
      for (var i = 0; i < list.length; i++) list[i].classList.add("mdtable");
      list = document.querySelector("TaskDescr").querySelectorAll("th");
      for (var i = 0; i < list.length; i++) list[i].classList.add("mdtable");
      list = document.querySelector("TaskDescr").querySelectorAll("td");
      for (var i = 0; i < list.length; i++) list[i].classList.add("mdtable");
    }
    showBorders();

    function markAssignmentWithoutReload(assignment_id, user_id, mark) {
      $('#spinner-mark').removeClass("d-none");
      let status = markAssignment(assignment_id, user_id, mark);
      if (mark != null && mark != "") {
        $('#div-reject-check').removeClass("d-none");
      } else
        $('#div-reject-check').addClass("d-none");
      $('#spinner-mark').addClass("d-none");
      if (status) {
        loadChatLog(true);
        openCity("Chat");
      }
    }

    function markAssignmentWithReload(assignment_id, user_id, mark) {
      $('#dialogCheckTask-spinner-mark').removeClass("d-none");
      let status = markAssignment(assignment_id, user_id, mark);
      $('#dialogCheckTask-spinner-mark').addClass("d-none");
      if (status) {
        $('#dialogSuccess').modal("hide");
      }
    }

    function markAssignment(assignment_id, user_id, mark) {

      // let mark = $('#dialogCheckTask-select-mark').val();
      if (mark == -1) {
        alert("Не выбрана оценка!");
        return false;
      }

      if (assignment_id != null) {
        let ajaxResponse = ajaxAssignmentMark(assignment_id, mark, user_id);
        if (ajaxResponse != null) {
          return true;
        } else {
          alert("Неудалось оценить задание.");
          return false;
        }
      } else {
        alert("Произошла ошибка!");
        return false;
      }
    }

    function deleteCommit(commit_id) {
      let ajaxResponse = ajaxCommitDelete(commit_id);
      if (ajaxResponse != null) {
        if (commit_id != parseInt(document.getElementById('check').getAttribute('commit')))
          document.location.reload();
        else
          document.location.href = "editor.php?assignment=" + document.getElementById('check').getAttribute('assignment');
      } else {
        alert("Не удалось удалить коммит.");
      }
    }
  </script>

  <script type="text/javascript" src="js/taskchat.js"></script>

  <script type="text/javascript">
    // После первой загрузки скролим страницу вниз
    // $('body, html').scrollTop($('body, html').prop('scrollHeight'));

    var messageFiles = [];

    $(document).ready(function() {

      let button_check = document.getElementById('button-check');
      let button_answer = document.getElementById('submit-answer');

      $('#div-history-commit-btns').scrollTop($('#div-history-commit-btns').prop('scrollHeight'));
      // $('#chat-box').scrollTop($('#chat-box').prop('scrollHeight'));

      $('#dialogSuccess').on('hidden.bs.modal', function() {
        document.location.href = "editor.php?assignment=" + document.getElementById('check').getAttribute('assignment');
      })

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
        // $("#user-message").css('height', '37.6px');
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

    });


    // Показывает количество прикрепленных для отправки файлов
    $('#user-files').on('change', function() {
      // TODO: Сделать удаление числа, если оно 0
      if (this.files.length != 0) {
        let div = document.getElementById("div-attachedFiles");
        Object.entries(this.files).forEach(file => {
          if (checkIfFileIsExist(messageFiles, file[1].name)) {
            alert("Внимание! Файл с таким названием уже прикреплён!")
          } else {
            add_element(div, file[1].name, "messageFiles[]", messageFiles.length);
            messageFiles.push(file[1]);
          }
        });
        $('#files-count').html(messageFiles.length);
      }
    });


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

    function add_element(parent, name, tag, id) {
      let element = document.createElement("div");

      //element.classList.add("col-lg-2");
      element.setAttribute("class", "d-flex justify-content-between align-items-center p-2 me-2 mt-1 badge badge-light text-wrap teacher-element");
      element.id = "messageFile-" + id;

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



    function loadChatLog($first_scroll = false) {
      // console.log("loadChatLog");
      // TODO: Обращаться к обновлению чата только в случае, если добавлиось новое, ещё не прочитанное сообщение
      $('#chat-box').load('taskchat_action.php#content', {
        assignment_id: <?= $assignment_id ?>,
        user_id: <?= $user_id ?>,
        selected_messages: JSON.stringify(selectedMessages),
        load_status: 'full'
      }, function() {
        // После первой загрузки страницы скролим чат вниз до новых сообщений или но самого низа
        if ($first_scroll) {
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
      } else if (typeMessage == 2 && mark) {
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
            $("#chat-box").html(response);
            if (typeMessage == 1) {
              let now = new Date();
              $("#label-task-status-text").text("Ожидает проверки");
              $("#span-answer-date").text(formatDate(now));
            } else if (typeMessage == 2) {
              let now = new Date();
              $("#label-task-status-text").text("Выполнено");
              $("#flexCheckDisabled").prop("checked", true);
              $("#span-answer-date").text(formatDate(now));
              $("#span-text-mark").html("Оценка: " + '<b id="b-mark">' + mark + '</b>');
              console.log("Оценка: " + '<b id="b-mark">' + mark + '</b>');
            }
          },
          complete: function() {
            // Скролим чат вниз после отправки сообщения
            $('#chat-box').scrollTop($('#chat-box').prop('scrollHeight'));
          }
        });
      }


      return true;
    }


    var selectedMessages = [];
    var senderUserTypes = [];

    function selectMessage(message_id, sender_user_type) {
      if (selectedMessages.includes(message_id)) {
        let index = selectedMessages.indexOf(message_id);
        selectedMessages.splice(index, 1);
        senderUserTypes.splice(index, 1);
        $('#btn-message-' + message_id).removeClass("bg-info");
        if (selectedMessages.length == 0)
          $('#btn-group-more').addClass("d-none");
      } else {
        selectedMessages.push(message_id);
        senderUserTypes.push(sender_user_type);
        $('#btn-message-' + message_id).addClass("bg-info");
        $('#btn-group-more').removeClass("d-none");
      }


      // Показывать кнопку "Удалить сособщение, если оно своё или нет, если не своё"
      let flag = true;
      selectedMessages.forEach((message_id, index) => {
        if (senderUserTypes[index] != user_role) {
          flag = false;
        }
      });
      if (flag)
        $('#a-messages-delete').removeClass("d-none");
      else
        $('#a-messages-delete').addClass("d-none");
    }

    function resendMessages(assignment_id, user_id, this_chat) {

      var formData = new FormData();
      formData.append('assignment_id', assignment_id);
      formData.append('user_id', user_id);
      formData.append('selected_messages', JSON.stringify(selectedMessages));
      formData.append('resendMessages', true);

      $.ajax({
        type: "POST",
        url: 'taskchat_action.php#content',
        cache: false,
        contentType: false,
        processData: false,
        data: formData,
        dataType: 'html',
        success: function(response) {
          if (this_chat) {
            $("#chat-box").html(response);
            for (let i = 0; i < selectedMessages.length;) {
              selectMessage(selectedMessages[i], null);
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


      return true;
    }

    function deleteMessages(assignment_id, user_id) {
      var formData = new FormData();
      formData.append('assignment_id', assignment_id);
      formData.append('user_id', user_id);
      formData.append('selected_messages', JSON.stringify(selectedMessages));
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
          $("#chat-box").html(response);
          for (let i = 0; i < selectedMessages.length;) {
            selectMessage(selectedMessages[i], null);
          }
        },
        complete: function() {
          // Скролим чат вниз после отправки сообщения
          $('#chat-box').scrollTop($('#chat-box').prop('scrollHeight'));
          console.log($('#chat-box').prop('scrollHeight'));
        }
      });


      return true;
    }
  </script>

  <style>
    .blur-background {
      backdrop-filter: blur(10px);
      position: absolute;
      width: 100%;
      height: 100%;
      z-index: 3;
    }

    .full-screen {
      height: 75% !important;
      width: 75% !important;
      position: absolute;
      left: 50px;
      right: 50px;
      top: 100px;
      bottom: 50px;
      justify-self: center;
      z-index: 4;
      box-shadow: 0px 0px 50px black;
    }

    .line-pattern {
      overflow: hidden;
    }

    .line-pattern::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: repeating-linear-gradient(45deg,
          transparent,
          transparent 15px,
          #000 1px,
          #000 16px);
    }

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

    .chat-wrapper {
      width: 100%;
      padding-top: 20px;
      padding-bottom: 5px;
    }

    #chat-box {
      border: 1px solid #00000020;
      border-radius: 5px;
      padding: 10px;
      margin-bottom: 15px;
      height: 600px;
      width: 100%;
      overflow-y: auto;
    }

    .chat-box-message {
      float: left;
      min-width: 400px;
      max-width: 600px;
      margin: 10px 0;
    }

    .chat-box-message-wrapper {
      border: 1px solid #00000020;
      border-radius: 5px;
      padding: 10px;
    }

    .chat-box-message-date {
      margin-left: 15px;
      font-size: 14px;
    }

    .message-input-wrapper {
      display: flex;
      align-items: flex-start;
      margin-bottom: 10px;
    }

    #user-message {
      flex-grow: 1;
      margin-left: 5px;
      margin-right: 5px;
      padding: 5px 0 5px 3px;
      border: 1px solid #00000020;
      border-radius: 5px;
      resize: none;
      height: 37.6px;

      font-family: Arial, Helvetica, sans-serif;
      font-size: 16px;
      font-weight: 300;
    }


    #submit-message {
      background: #fff;
      border: 1px solid #00000020;
      border-radius: 5px;
      padding: 5px 15px;
      transition: all 0.2s ease;

      font-family: Arial, Helvetica, sans-serif;
      font-size: 16px;
      font-weight: 300;
    }

    #submit-message:hover {
      background: #fffbfb;
    }

    .file-input-wrapper {
      position: relative;
      z-index: 10;
      display: flex;
      align-items: center;
      height: 100%;
    }

    #user-files,
    #user-answer-files {
      width: 0.1px;
      height: 0.1px;
      opacity: 0;
      position: absolute;
      z-index: -10;
    }

    .file-input-wrapper label {
      display: inline-block;
      position: relative;
      padding: 0 8px 0 5px;
      transition: all 0.1s ease;

      font-weight: 300;
      font-size: 23px;
      color: #4f4f4fc6;
    }

    .file-input-wrapper label:hover {
      cursor: pointer;
      color: #4f4f4f;
    }

    #files-count,
    #files-answer-count {
      display: inline-block;
      position: absolute;
      bottom: -4px;
      right: 0px;

      font-weight: 700;
      color: #2e73e3;
      font-size: 14px;
    }

    .float-right {
      float: right;
    }

    .clear {
      clear: both;
    }

    .background-color-blue {
      background-color: #d9f4fa7b;
    }

    .pretty-text {
      white-space: pre-line;
      word-wrap: break-word;
    }

    @media screen and (max-width: 900px) {
      .task-wrapper>div {
        flex-flow: column;
      }

      .task-desc-wrapper {
        margin-right: 0;
      }

      .task-status-wrapper {
        flex-direction: row;
        padding: 10px 5px 0 15px;
      }
    }

    @media screen and (max-width: 600px) {
      .task-desc-wrapper>div {
        flex-direction: column;
      }
    }
  </style>
  <?php
  // show_footer();
  ?>
<!DOCTYPE html>
<html lang="en">

<?php
require_once("common.php");
require_once("dbqueries.php");
require_once("utilities.php");
require_once("./POClasses/User.class.php");
$scripts = null;

$au = new auth_ssh();
checkAuLoggedIN($au);
checkAuIsNotStudent($au);

$User = new User((int)$au->getUserId());


// Обработка некорректного перехода между страницами
if (!isset($_GET['page']) || !is_numeric($_GET['page'])){
	header('Location:mainpage.php');
  exit;
}

$Page = new Page((int)$_GET['page']);

// получение параметров запроса
$user_id = $_SESSION['hash'];
$page_id = 0;

if (array_key_exists('page', $_REQUEST) && isset($_GET['page']))
  $page_id = $_REQUEST['page'];
else if (isset($_POST['select-discipline'])) {
  $page_id = $_POST['select-discipline'];
  header('Location:preptable.php?page=' . $page_id);
} else {
  header('Location:mainpage.php');
  exit;
}

// отправка сообщения
/*if (array_key_exists('message', $_REQUEST) && array_key_exists('text', $_REQUEST)) {
  $mark = (array_key_exists('mark', $_REQUEST)) ? $_REQUEST['mark'] : null;
  $query = insert_message_reply($_REQUEST['message'], $_REQUEST['text'], $user_id, $mark);
  $result = pg_query($dbconnect, $query);
  echo pg_result_error($result);
  if (!$result) {
    echo "Ошибка запроса";
    http_response_code(500);
    exit;
  }
  $scripts .= "<script>window.history.replaceState(null, document.title, '" . $_SERVER['PHP_SELF'] . "?page=" . $page_id . "');</script>\n";
  // OR remove &message=N in $_SERVER['REQUEST_URI'] 
}*/

// TODO: check prep access rights to page

$query = select_page_name($page_id);
$result = pg_query($dbconnect, $query);
// echo select_page_name($page_id);
$row = [];
if (!$result || pg_num_rows($result) < 1) {
  echo 'Неверно указана дисциплина';
  http_response_code(400);
  exit;
}
$row = pg_fetch_row($result);

show_head("Посылки по дисциплине: " . $row[1]);
if ($scripts) echo $scripts; 

?>



<body>

<?php show_header($dbconnect, 'Посылки по дисциплине', array($row[1]  => 'preptable.php?page=' . $page_id), $User);?>

<main class="pt-2">
  <div class="container-fluid overflow-hidden">
    <div class="row gy-5">
      <div class="col-8">

        <div class="pt-3">

          <h2 class="text-nowrap">
            Посылки по дисциплине
          </h2>
          <div style="padding-top:10px; padding-bottom:10px; ">
            <select class="form-select" aria-label=".form-select" name="select-discipline" id="selectCourse">
              <?php $i = 1;
              $query = select_page_names(1);
              $result = pg_query($dbconnect, $query);
              $page_names = pg_fetch_all($result);

              foreach ($page_names as $page_name) {
                if ($row[0] == $page_name['id'])
                  echo '<option selected value="' . $page_name['id'] . '">' . $page_name['names'] . '</option>';
                else
                  echo '<option value="' . $page_name['id'] . '">' . $page_name['names'] . '</option>';
                $i++;
              } ?>
            </select>
          </div>

          <div class="form-outline">
            <i class="fas fa-search trailing"></i>
            <input type="text" id="form1" class="form-control form-icon-trailing" oninput="filterTable(this.value)" />
            <label class="form-label" for="form1">Фильтр по группам, студентам, заданиям, комментариям</label>
            <div class="form-notch">
              <div class="form-notch-leading" style="width: 9px;"></div>
              <div class="form-notch-middle" style="width: 114.4px;"></div>
              <div class="form-notch-trailing"></div>
            </div>
          </div>


          <?php
          $query = select_page_tasks($page_id, 1);
          $result = pg_query($dbconnect, $query);
          $tasks = array();
          if (!$result || pg_num_rows($result) < 1){?>
          <div class="pt-3">
            <h5>Задания по этой дисциплине отсутствуют</h5>
          </div>
          
          <?php 
          } else {
            $tasks = pg_fetch_all_assoc($result, PGSQL_ASSOC);
            $group = null;

            $query = select_page_students_grouped($page_id, 1);
            $result = pg_query($dbconnect, $query);
            $students = pg_fetch_all_assoc($result, PGSQL_ASSOC);

            $query = select_preptable_messages($page_id);
            $result = pg_query($dbconnect, $query);
            $messages = pg_fetch_all_assoc($result, PGSQL_ASSOC);
          ?>

          <?php 
          if (!$tasks) {?>
            <div class="pt-3">
              <h5>Отсутсвуют задания</h5>
            </div>
          <?php } else {?>

          <div>
            <table class="table table-status" id="table-status-id" style="text-align: center;">
              <thead>
                <tr class="table-row-header" style="text-align:center;">
                  <th scope="col" colspan="1">Студенты и группы</th>
                  <th scope="col" colspan="<?= count($tasks) + 1 ?>">
                    <div class="d-flex justify-content-between align-items-center">
                      <span style="font-size: large;"> Задания </span>
                      <button type="submit" class="btn" onclick="window.location='preptasks.php?page=<?=$page_id?>';" style="">
                        <i class="fas fa-pencil-alt" aria-hidden="true"></i>
                      </button>
                    </div>
                  </th>
                </tr>
                <tr>
                  <th scope="col" colspan="1"> </th>
                  <!-- <th scope="col" data-mdb-toggle="tooltip" data-title="Номер варианта">#</th> -->
                  <?php
                  for ($t = 0; $t < count($tasks); $t++) {
                  ?>
                    <td scope="col" data-mdb-toggle="tooltip" data-title="<?= $tasks[$t]['title'] ?>"><?= $t + 1 ?></td>
                  <?php
                  }
                  ?>
                </tr>
              </thead>
              <tbody>
                <?php

                foreach ($Page->getGroups() as $Group) { ?>
                  <tr class="table-row-header">
                    <th scope="row" colspan="1"><?=$Group->name?></th>
                    <!-- <th colspan="1"> </th> -->
                    <td colspan="<?=count($Page->getActiveTasks())?>" style="background: var(--mdb-gray-200);"> </td>
                  </tr>
                  <?php 
                  foreach ($Group->getStudents() as $count => $Student) {?>
                    <tr>
                      <th scope="row" data-group="<?=$Group->id?>"><?=$count+1?>. <?=$Student->getFI()?></th>
                      <?php 
                      foreach($Page->getActiveTasks() as $Task) {
                        $Assignment = $Task->getLastAssignmentByStudent((int)$Student->id);?>
                        <?php
                        if ($Assignment != null) {
                          
                          // TODO: Изменить логику отрисовки таблицы в зависимости от того, стоит ли оценка или нет
                          if ($Assignment->visibility == 0 || $Assignment->visibility == 1) {?>
                            <td onclick="unblockAssignment(<?=$Assignment->id?>)"
                            style="background: var(--mdb-gray-100);">
                              <!-- <button id="btn-assignment-visibility-<?=$Assignment->id?>" class="btn px-3 me-1 btn-assignment-visibility-<?=$Task->id?>" 
                              onclick="ajaxChangeVisibility(<?=$Assignment->id?>, <?=$Assignment->getNextAssignmentVisibility()?>)"
                              style="cursor: pointer;" data-toggle="tooltip" data-placement="down" 
                              data-title="<?='Изменить ВИДМОСТЬ назначения на:'?> '<?=visibility_to_text($Assignment->getNextAssignmentVisibility())?>'">
                                  <?php getSVGByAssignmentVisibility($Assignment->visibility);?>
                              </button>
                              <button id="btn-assignment-status-<?=$Assignment->id?>" class="btn px-3 me-1 btn-assignment-status-<?=$Task->id?>" 
                              onclick="ajaxChangeStatus(<?=$Assignment->id?>, <?=$Assignment->getNextAssignmentStatus()?>)"
                              style="cursor: pointer;" data-toggle="tooltip" data-placement="down" 
                              data-title="<?='Изменить СТАТУС назначения на:'?> '<?=status_to_text($Assignment->getNextAssignmentStatus())?>'"
                              <?=($Assignment->status == -1 || $Assignment->status == 0) ? "" : "disabled"?>>
                                  <?php getSVGByAssignmentStatus($Assignment->status);?>
                              </button> -->
                            </td>
                          <?php }

                          else if ($Assignment->status == 1) {
                            $last_Message = $Assignment->getLastAnswerMessage();
                            $last_message_Student = new User((int)$last_Message->sender_user_id);
                            ?>
                            <td tabindex="0" onclick="showPopover(this)" 
                            data-title="<?=$last_message_Student->getFI()?> <?=convert_mtime($last_Message->date_time)?>" 
                            data-mdb-content="<?=getPopoverContent($last_Message, $Task, $Assignment->id, $user_id)?>">
                              <?php if ($Assignment->mark != null) {?>
                                <?=$Assignment->mark?>
                                <span class="badge rounded-pill badge-notification text-danger m-0" style="font-size:.5rem">
                                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="currentColor" class="bi bi-arrow-clockwise" viewBox="0 0 16 16">
                                  <path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/>
                                  <path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/>
                                </svg>
                                </span>
                              <?php } else {?>
                                <span class="text-danger" style="font-size: larger;">
                                  ?
                                </span>
                              <?php }?>
                            </td>
                          <?php } 
                          
                          else {?>
                              <td onclick="answerPress(2,null,<?=$Assignment->id?>,<?=$user_id?>,<?=$Task->max_mark?>)">
                                <?php if ($Assignment->mark != null) {
                                  echo $Assignment->mark;
                                } ?>
                              </td>
                          <?php }
                        
                        } else {?>
                          <td style="background: var(--mdb-gray-100);">
                          </td>
                        <?php } 

                      }?>
                    </tr>
                  <?php
                  }
                } ?>
              </tbody>
            </table>
          </div>

          <?php } ?>

          <?php
          // $query = select_unchecked_by_page($_SESSION['hash'], $page_id);
          // $result = pg_query($dbconnect, $query);
					// $array_notify = pg_fetch_all($result);
          ?>

            <div class="my-4 pt-2">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="mx-3" style="color: black; font-style:normal; font-size: larger; font-weight: bold;">ИСТОРИЯ СООБЩЕНИЙ</span>
                <?php if($Page->getConversationTask() == null) {?>
                  <form id="form-createGeneralConversation" name="createGeneralConversation" action="taskassign_action.php" 
                  method="POST" enctype="multipart/form-data" class="me-2">
                    <input type="hidden" name="page_id" value="<?=$Page->id?>"></input>
                    <input type="hidden" name="createGeneralConversation" value="true"></input>

                    <button class="btn btn-outline-primary px-3" type="submit">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chat-square-text-fill" viewBox="0 0 16 16">
                        <path d="M0 2a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-2.5a1 1 0 0 0-.8.4l-1.9 2.533a1 1 0 0 1-1.6 0L5.3 12.4a1 1 0 0 0-.8-.4H2a2 2 0 0 1-2-2V2zm3.5 1a.5.5 0 0 0 0 1h9a.5.5 0 0 0 0-1h-9zm0 2.5a.5.5 0 0 0 0 1h9a.5.5 0 0 0 0-1h-9zm0 2.5a.5.5 0 0 0 0 1h5a.5.5 0 0 0 0-1h-5z"/>
                      </svg>
                      &nbsp; СОЗДАТЬ ОБЩУЮ БЕСЕДУ
                    </button>
                  </form>
                <?php } else {?>
                  <button class="btn btn-outline-primary px-3" 
                  onclick="window.location='taskchat.php?assignment=<?=$Page->getConversationTask()->getConversationAssignment()->id?>'">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-people-fill" viewBox="0 0 16 16">
                      <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7Zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm-5.784 6A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216ZM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"/>
                    </svg>
                    &nbsp; БЕСЕДА ПРЕДМЕТА
                  </button>
                <?php }?>
              </div>
              <ul class="accordion list-group" style="margin-bottom: 40px;" id="accordion-student-list">
                <?php
                // Составление аккордеона-списка студентов с возможностью перехода на страницы taskchat по каждому отдельному заданию 

                $key = 0;
                foreach ($Page->getGroups() as $Group) {
                  foreach ($Group->getStudents() as $Student) {?>
                  <div class="student-item">
                    <li id="<?=$key++?>" class="li-1 list-group-item noselect toggle-accordion" style="cursor: pointer;" href="javascript:void(0);">
                      <div class="row">
                        <div class="d-flex justify-content-between align-items-center">
                          <div class="div-accordion-student-fio">
                            <!--<i id="icon-down-right-<?=$key?>" class="fa fa-caret-right" aria-hidden="true"></i>-->
                            <strong class="strong-accordion-student-fio"><?=$Student->getFI();?></strong>
                          </div>
                          <?php
                          if ($Page->hasUncheckedTasks($Student->id)) {?>
                            <span class="badge badge-primary badge-pill bg-warning text-white">
                              Задания ожидают выполнения
                            </span>
                          <?php }?>
                        </div>
                      </div> 
                    </li>
                    <div class="inner-accordion noselect" style="display: none;">
                      <?php 
                      foreach ($Page->getTasks() as $Task) {
                        $Assignment = $Task->getLastAssignmentByStudent($Student->id);
                        if ($Assignment != null) {?>
                          <?php 
                          //XXX: Проверить?>
                          <a href="taskchat.php?assignment=<?=$Assignment->id?>">
                            <li class="list-group-item" >
                              <div class="row">
                                <div class="d-flex justify-content-between align-items-center">
                                  &nbsp;&nbsp;&nbsp;<?=$Task->title?>
                                  <?php 
                                  $count_unreaded = $Assignment->getCountUnreadedMessages($Student->id);
                                  if($Assignment->status == 1) { ?>
                                    <span class="badge badge-primary badge-pill bg-warning text-white">
                                      Ожидает проверки
                                    <span>
                                  <?php } else if ($Assignment->status == 4) {?>
                                    <span class="badge badge-primary badge-pill bg-success text-white">
                                      Выполнено
                                    <span>
                                  <?php } ?>
                                </div>
                              </div>
                            </li> 
                          </a>
                        <?php }?>
                      <?php }?>
                    </div>
                  </div>
                  <?php
                  } 
                }?>
           
              </ul>
            </div>
        </div>
      </div>

      <?php if ($messages && count($messages) > 0) {?>
        <div class="col-4 bg-light p-3" style="z-index: 999;">
          <h5>История посылок и оценок</h5>
          <div id="list-messages" class="bg-light" style="/*overflow-y: scroll; height: calc(100vh - 80px); max-height: calc(100vh - 80px);*/">
            <div id="list-messages-id">
              <?php
              foreach ($messages as $message) {
                if ($message['mtype'] != null && $message['type'])?>
                  <?php if ($message['mreply_id'] != null) {?>
                    <div>
                      <?php $query = pg_query($dbconnect, select_message_with_all_relations($message['mreply_id']));
                      $message_reply = pg_fetch_assoc($query);
                      show_preptable_message($message_reply, true);?>
                    </div>
                  <?php }
                  $float_class = $message['mtype'] == 2 ? 'd-flex justify-content-end' : ''; ?>
                  <div class="<?=$float_class?>">
                    <?php show_preptable_message($message, false);?>
                </div>
              <?php }?>
            </div>
          </div>
        </div>
      <?php }?>

    </div>

</main>



<!-- Modal dialog answer -->
<div class="modal fade" id="dialogAnswer" tabindex="-1" aria-labelledby="dialogAnswerLabel" aria-hidden="true">
  <form id="form-answer" class="needs-validation">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="dialogAnswerLabel">Ответить студенту</h5>
          <button type="button" class="btn-close" data-mdb-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="form-outline">
            <textarea class="form-control" id="dialogAnswerText" rows="4" name="text" required></textarea>
            <label class="form-label" for="dialogAnswerText">Текст ответа</label>
            <div class="form-notch">
              <div class="form-notch-leading" style="width: 9px;"></div>
              <div class="form-notch-middle" style="width: 114.4px;"></div>
              <div class="form-notch-trailing"></div>
            </div>
          </div>
          <input type="hidden" id="dialogAnswerMessageId" name="message" />
          <input type="hidden" name="page" value="<?= $page_id ?>" />
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Закрыть</button>
          <button type="submit" class="btn btn-primary">Ответить</button>
        </div>
      </div>
    </div>
  </form>
</div>



<!-- Modal dialog mark -->
<div class="modal fade" id="dialogMark" tabindex="-1" aria-labelledby="dialogMarkLabel" aria-hidden="true">
  <form id="form-mark" class="needs-validation">
    
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="dialogMarkLabel">Зачесть задание</h5>
          <button type="button" class="btn-close" data-mdb-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="form-outline">
            <input type="number" id="dialogMarkMarkInput" name="mark" class="form-control" required min="1" max="5"/>
            <label class="form-label" for="typeNumber" id="dialogMarkMarkLabel">Оценка</label>
            <div class="form-notch">
              <div class="form-notch-leading" style="width: 9px;"></div>
              <div class="form-notch-middle" style="width: 114.4px;"></div>
              <div class="form-notch-trailing"></div>
            </div>
          </div>
          <span id="error-input-mark" class="error-input" aria-live="polite"></span>
          <br/>
          <div class="form-outline">
            <input class="form-control" id="dialogMarkText" rows="4" name="text"/>
            <label id="label-dialogMarkText" class="form-label" for="dialogMarkText">Текст ответа</label>
            <div class="form-notch">
              <div class="form-notch-leading" style="width: 9px;"></div>
              <div class="form-notch-middle" style="width: 114.4px;"></div>
              <div class="form-notch-trailing"></div>
            </div>
          </div>
          <input type="hidden" id="dialogMarkMessageId" name="message" />
          <input type="hidden" name="page" value="<?= $page_id ?>" />
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Закрыть</button>
          <button type="submit" class="btn btn-primary">Ответить</button>
        </div>
      </div>
    </div>
  </form>
</div>


<div class="modal fade" id="dialogAssignment" tabindex="-1" aria-labelledby="dialogMarkLabel" aria-hidden="true">
  <form id="form-mark" class="needs-validation">
    
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="dialogMarkLabel">Назначить задание</h5>
          <button type="button" class="btn-close" data-mdb-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Закрыть</button>
          <button type="submit" class="btn btn-primary">Назначить</button>
        </div>
      </div>
    </div>
  </form>
</div>

<?php }?>

<?php
function getPopoverContent($Message, $Task, $assignment_id, $user_id) {
  $data_mdb_content = "";

  $data_mdb_content .= generate_message_for_student_task_commit($Task->title);
  $data_mdb_content .= showAttachedFilesByMessageId($Message->id);

  $data_mdb_content .= "
  <a href='javascript:answerPress(2,". $Message->id.", " . $assignment_id . ", " . $user_id .", ". $Task->max_mark.")'
  type='message' class='btn btn-outline-primary'>
    Зачесть
  </a> 
  <a href='javascript:answerPress(0,". $Message->id.", " . $assignment_id . ", " . $user_id.")' 
  type='message' class='btn btn-outline-primary'>
    Ответить
  </a>";

  return $data_mdb_content;
} 

function show_preptable_message($message, $flag_marked_message = false) {
  if ($message == null || $message['type'] == 0) 
    return;
  
  $message_style = ($message['mtype'] == 2) ? 'message-prep' : 'message-stud';
    
  $message_text = "";
    
  if ($message['mreply_id'] != null){ // is reply message, add citing
    $message_text .= "<p class='note note-light'>";
    $message_text .= generate_message_for_student_task_commit($message['task']);
    if($message['type'] != 1){
      $message_text .= $message['mtext'];
    }
    $message_text .= showAttachedFilesByMessageId($message['mreply_id']);
    $message_text .= "</p>";
  } else if ($message['status'] == 1 && $message['type'] == 1 && !$flag_marked_message) { 
    // is student message need to be checked
    $Message = new Message((int)$message['mid']);
    $Task = new Task((int)$message['tid']);
    $message_text .= getPopoverContent($Message, $Task, $message['aid'], $_SESSION['hash']);
  } else {
    $message_text .= generate_message_for_student_task_commit($message['task']);
    if($message['type'] != 1){
      $message_text .= $message['mtext'];
    }
    $message_text .= showAttachedFilesByMessageId($message['mid']); 
  }?>

  <div class="popover message <?=$message_style?> w-100" role="listitem">
    <div class="popover-arrow"></div>
    <div class="p-3 popover-header" style="background-color: #80E08040;">
      <h6 style="margin-bottom: 0px;" data-title="<?=$message['grp']. "\nЗадание: " . $message['task']?>">
        <?=$message['fio']. '<br>'?></h6>
      <p style="text-align: right; font-size: 8pt; margin-bottom: 0px;"><?=convert_mtime($message['mtime'])?></p>
    </div>
    <div class="popover-body"><?=$message_text?></div>
  </div>

<?php        
} 

function getPopoverHtml($message_fio, $message_group, $message_task_title, $message_time, $message_text){
  $popover_html = "
  <div class='popover message role='listitem'>
    <div class='popover-arrow'></div>
    <div class='p-3 popover-header' style='background-color: #80E08040;'>
      <h6 style='margin-bottom: 0px;' data-title='".$message_group. "\nЗадание: " . $message_task_title ."'>".
        $message_fio. "<br></h6>
      <p style='text-align: right; font-size: 8pt; margin-bottom: 0px;'>".convert_mtime($message_time)."</p>
    </div>
    <div class='popover-body'>".$message_text."</div>
  </div>";
  return $popover_html;
}

function generate_message_for_student_task_commit($task_title){
  $message_text = "<strong>".$task_title."</strong> </br>";
  return $message_text;
}
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" 
  integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>

<!-- Custom scripts -->
<script type="text/javascript" src="js/preptable.js"></script>

<script type="text/javascript">
  function ajaxChangeVisibility(assignment_id, new_visibility) {

    var formData = new FormData();

    formData.append('assignment_id', assignment_id);
    formData.append('changeVisibility', new_visibility);

    $.ajax({
      type: "POST",
      url: 'taskassign_action.php#content',
      cache: false,
      contentType: false,
      processData: false,
      data: formData,
      dataType : 'html',
      success: function(response) {
        response = JSON.parse(response);
        $('#btn-assignment-visibility-' + assignment_id).html(response[0].svg);
        $('#btn-assignment-visibility-' + assignment_id).prop('title', "Изменить СТАТУС назначения на: " + response[0].visibility_to_text);
        $('#btn-assignment-visibility-' + assignment_id).attr("onclick", 'ajaxChangeVisibility(' + assignment_id + ', ' + response[0].next_visibility + ')');
      },
      complete: function() {
      }
    });   
  }

  function ajaxChangeStatus(assignment_id, new_status) {

    console.log("CLICK!");

    var formData = new FormData();

    formData.append('assignment_id', assignment_id);
    formData.append('changeStatus', new_status);

    $.ajax({
      type: "POST",
      url: 'taskassign_action.php#content',
      cache: false,
      contentType: false,
      processData: false,
      data: formData,
      dataType : 'html',
      success: function(response) {
        response = JSON.parse(response);
        $('#btn-assignment-status-' + assignment_id).html(response[0].svg);
        $('#btn-assignment-status-' + assignment_id).prop('title', "Изменить СТАТУС назначения на: " + response[0].status_to_text);
        $('#btn-assignment-status-' + assignment_id).attr("onclick", 'ajaxChangeStatus(' + assignment_id + ', ' + response[0].next_status + ')');
      },
      complete: function() {
      }
    });   
  }

</script>


<!-- End your project here-->
</body>
</html>
<!DOCTYPE html>
<html lang="en">

<?php
require_once("common.php");
require_once("dbqueries.php");
require_once("utilities.php");
$scripts = null;

// защита от случайного перехода
$au = new auth_ssh();
if (!$au->isAdmin() && !$au->isTeacher()){
	$au->logout();
	header('Location:login.php');
}

// Обработка некорректного перехода между страницами
if (!isset($_GET['page']) || !is_numeric($_GET['page'])){
	header('Location:mainpage.php');
  exit;
}

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
} else {
  $row = pg_fetch_row($result);
  show_head("Посылки по дисциплине: " . $row[1]);
  show_header($dbconnect, 'Посылки по дисциплине', array($row[1]  => 'preptable.php?page=' . $page_id));
}

if ($scripts) echo $scripts; ?>

<body>

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
                  <th scope="col" colspan="<?= count($tasks) + 1 ?>">Задания <button type="submit" class="btn" onclick="window.location='preptasks.php?page=<?=$page_id?>';" style="">
                            <i class="fas fa-pencil-alt" aria-hidden="true"></i>
                          </button></th>
                </tr>
                <tr>
                  <th scope="col" colspan="1"> </th>
                  <th scope="col" data-mdb-toggle="tooltip" title="Номер варианта">#</th>
                  <?php
                  for ($t = 0; $t < count($tasks); $t++) {
                  ?>
                    <td scope="col" data-mdb-toggle="tooltip" title="<?= $tasks[$t]['title'] ?>"><?= $t + 1 ?></td>
                  <?php
                  }
                  ?>
                </tr>
              </thead>
              <tbody>
                <?php
                $student_count = 0;
                if ($students){
                  foreach ($students as $student) {
                    $query = select_page_tasks_with_assignment($page_id, 1, $student['id']);
                    $result = pg_query($dbconnect, $query);
                    $array_student_tasks = pg_fetch_all($result); 

                    if ($group != $student['grp']) {
                      $group = $student['grp']; ?>
                      <tr class="table-row-header">
                        <th scope="row" colspan="1"><?= $group ?></th>
                        <th colspan="1"> </th>
                        <td colspan="<?= count($tasks) ?>" style="background: var(--mdb-gray-200);"> </td>
                      </tr>
                    <?php
                    } ?>

                    <tr>
                      <th scope="row" data-group="<?= $student['grp'] ?>"><?= $student_count + 1 ?>. <?= $student['fio'] ?></th>
                      <th data-mdb-toggle="tooltip" data-mdb-html="true" title="<?= $student['vtext'] ?>"><?= $student['vnum'] ?></th>
                      <?php
                      $now_index = 0;
                      foreach ($tasks as $key => $task) { // tasks cycle
                        $task_message = null;

                        $query = pg_query($dbconnect, select_assignment_with_task($student['id'], $task['id']));
                        $assignment = pg_fetch_assoc($query);
                        
                        if ($messages) {
                          foreach ($messages as $message) { // search for last student+task message
                            if ($message['tid'] == $task['id'] && $message['sid'] == $student['id'] && $message['type'] == 1) {
                              $task_message = $message;
                            }
                          }
                        }

                        // пометить клетки серыми, если задание недоступно для выполнения
                        if (!isset($array_student_tasks[$now_index]) || 
                        (isset($array_student_tasks[$now_index]) && $array_student_tasks[$now_index]['id'] != $task['id'])) { ?>
                          <td tabindex="-1" style="background: var(--mdb-gray-100);"></td>
                          <?php
                          continue;
                        } 

                        // Задание требует проверки
                        if ($array_student_tasks[$now_index]['status_code'] == 5 && $task_message) { ?>
                          <td tabindex="0" onclick="showPopover(this)" 
                          title="<?=$task_message['fio']?> <?=convert_mtime($task_message['mtime'])?>" 
                          data-mdb-content="<?=getPopoverContent($task_message, $user_id)?>">
                            <?php if ($array_student_tasks[$now_index]['mark'] != null) {?>
                              <?=$array_student_tasks[$now_index]['mark']?>
                              <span class="badge rounded-pill badge-notification text-danger m-0" style="font-size:.5rem">
                              <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="currentColor" class="bi bi-arrow-clockwise" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/>
                                <path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/>
                              </svg>
                              </span>
                            <?php } else {?>
                              ?
                            <?php }?>
                          </td>
                        <?php 
                        } else {?>
                            <td onclick="answerPress(2,null,<?=$assignment['id']?>,<?=$user_id?>,<?=$assignment['max_mark']?>)">
                              <?php if ($array_student_tasks[$now_index]['mark'] != null) {
                                echo $array_student_tasks[$now_index]['mark'];
                              } ?>
                            </td>
                        <?php }

                        $now_index++;
                        
                      } // $tasks cycle ?>
                    </tr>
                  <?php 
                  $student_count ++;
                  } // students cycle
                }
                ?>
              </tbody>
            </table>
          </div>

          <?php } ?>

          <?php
          $query = select_unchecked_by_page($_SESSION['hash'], $page_id);
          $result = pg_query($dbconnect, $query);
					$array_notify = pg_fetch_all($result);

          if ($students && $tasks) {?>

            <div class="my-4 pt-2">
              <h4 class="mx-3" style="color: black; font-style:normal;">История сообщений</h4>
              <ul class="accordion list-group" style="margin-bottom: 40px;">
                <?php
                // Составление аккордеона-списка студентов с возможностью перехода на страницы taskchat по каждому отдельному заданию 
                foreach ($students as $key => $student) { 
                  $array_messages_count = array();
                  $sum_unreaded_message_count = 0;
                  for($i = 0; $i < count($tasks); $i++){
                    $query = select_count_unreaded_messages_by_task_for_teacher($student['id'], $tasks[$i]['id']);
                    $result = pg_query($dbconnect, $query);
                    array_push($array_messages_count, pg_fetch_assoc($result));
                    $sum_unreaded_message_count += $array_messages_count[$i]['count'];
                  }

                  $query = select_page_tasks_with_assignment($page_id,1, $student['id']);
                  $result = pg_query($dbconnect, $query);
                  $array_student_tasks = pg_fetch_all($result); 
                  ?>

                  <div class="student-item">
                    <li id="<?=$key+1?>" class="li-1 list-group-item noselect toggle-accordion" style="cursor: pointer;" href="javascript:void(0);">
                      <div class="row">
                        <div class="d-flex justify-content-between align-items-center">
                          <div>
                            <!--<i id="icon-down-right-<?=$key+1?>" class="fa fa-caret-right" aria-hidden="true"></i>-->
                            <strong><?= $student['fio']?></strong>
                          </div>
                          <span class="badge badge-primary badge-pill" 
                            <?php /* if($array_notify && in_array($student['id'], array_column($array_notify, 'student_user_id'))) { */
                            if($array_notify && in_array($student['id'], array_map(function($element){
                            return $element['student_user_id'];}, $array_notify))) {?> 
                              style="color: white; background: #dc3545;"> 
                              <?php 
                              //echo $sum_unreaded_message_count + count(array_keys(array_column($array_notify, 'student_user_id'), $student['id']));
                              echo count(array_keys(array_map(function($element){
                              return $element['student_user_id'];}, $array_notify), $student['id']));
                            } else {?> >
                              <?=$sum_unreaded_message_count?> 
                            <?php } ?>
                          </span>
                        </div>
                      </div> 
                    </li>
                    <div class="inner-accordion noselect" style="display: none;">
                      <?php $i=0;
                      if($array_student_tasks) {
                      foreach ($array_student_tasks as $task) {?>
                        <a href="taskchat.php?task=<?=$task['id']?>&page=<?=$task['page_id']?>&id_student=<?=$student['id']?>">
                          <li class="list-group-item" >
                            <div class="row">
                              <div class="d-flex justify-content-between align-items-center">
                                &nbsp;&nbsp;&nbsp;<?=$task['title']?>
                                <span class="badge badge-primary badge-pill"
                                <?php 
                                // if($array_notify && in_array($task['assignment_id'], array_column($array_notify, 'assignment_id'))) {
                                if($array_notify && in_array($task['assignment_id'], array_map(function($element){
                                return $element['assignment_id']; }, $array_notify))) {?>
                                  style="color: white; background: #dc3545;">

                                  <?php if($array_messages_count[$i]['count'] == 0 || !$array_messages_count[$i]['count']) 
                                    $array_messages_count[$i]['count'] = 1; 
                                  echo $array_messages_count[$i]['count'];
                                } else {
                                  echo ">".$array_messages_count[$i]['count'];
                                }?>
                                </span>
                              </div>
                            </div>
                          </li> 
                        </a>  
                      <?php $i++; } }?>
                    </div>
                  </div>
                <?php }?>            
              </ul>
            </div>
          <?php } ?>
        </div>
      </div>

      <?php if ($messages && count($messages) > 0) {?>
        <div class="col-4 bg-light p-3">
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
              <?php }
              // for ($m = 0; $m < count($messages); $m++) { // list all messages
              //   if ($messages[$m]['mtype'] != null)
              //     show_preptable_message($messages[$m]);
              // } ?>
            </div>
            <!--<div class="pt-1 pb-1"><button type="button" class="btn btn-outline-primary" data-mdb-toggle="modal" data-mdb-target="#dialogAnswer"><i class="fas fa-paperclip fa-lg"></i> Что-то сделать</button></div> -->
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

<?php }?>

<?php
function getPopoverContent($message, $user_id) {

  // $message_files = get_message_attachments($task_message['mid']);
  $data_mdb_content = "";

  $data_mdb_content .= generate_message_for_student_task_commit($message['task']);
  $data_mdb_content .= showAttachedFilesByMessageId($message['mid']);

  $data_mdb_content .= "
  <a href='javascript:answerPress(2,". $message['mid'].", " . $message['aid'] . ", " . $user_id .", ". $message['max_mark'].")'
  type='message' class='btn btn-outline-primary'>
    Зачесть
  </a> 
  <a href='javascript:answerPress(0,". $message['mid'].", " . $message['aid'] . ", " . $user_id.")' 
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
  if($message['type'] != 1){
    $message_text = $message['mtext'];
  }
    
  if ($message['mreply_id'] != null){ // is reply message, add citing
    $message_text .= "<p class='note note-light'>";
    $message_text .= generate_message_for_student_task_commit($message['task']);
    $message_text .= showAttachedFilesByMessageId($message['mreply_id']);
    $message_text .= "</p>";
  } else if ($message['status_code'] == 5 && $message['type'] == 1 && !$flag_marked_message) { 
    // is student message need to be checked
    $message_text .= getPopoverContent($message, $_SESSION['hash']);
  } else {
    $message_text .= generate_message_for_student_task_commit($message['task']);
    $message_text .= showAttachedFilesByMessageId($message['mid']); 
  }?>

  <div class="popover message <?=$message_style?> w-100" role="listitem">
    <div class="popover-arrow"></div>
    <div class="p-3 popover-header" style="background-color: #80E08040;">
      <h6 style="margin-bottom: 0px;" title="<?=$message['grp']. "\nЗадание: " . $message['task']?>">
        <?=$message['mfio']. '<br>'?></h6>
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
      <h6 style='margin-bottom: 0px;' title='".$message_group. "\nЗадание: " . $message_task_title ."'>".
        $message_fio. "<br></h6>
      <p style='text-align: right; font-size: 8pt; margin-bottom: 0px;'>".convert_mtime($message_time)."</p>
    </div>
    <div class='popover-body'>".$message_text."</div>
  </div>";
  return $popover_html;
}

function generate_message_for_student_task_commit($task_title){
  $message_text = "<strong>".$task_title."</strong>";
  return $message_text;
}
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" 
  integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>

<!-- Custom scripts -->
<script type="text/javascript" src="js/preptable.js"></script>


<!-- End your project here-->
</body>
</html>
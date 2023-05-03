<?php
require_once("common.php");
require_once("dbqueries.php");
require_once("settings.php");
require_once("utilities.php");


$au = new auth_ssh();
checkAuLoggedIN($au);

$User = new User((int)$au->getUserId());

// TODO: Переписать c использованием POClasses
// Обработка некорректного перехода между страницами
if (!(isset($_REQUEST['task'], $_REQUEST['page'], $_REQUEST['id_student'])) && !isset($_REQUEST['assignment'])) {
	header('Location:index.php');
  exit;
}

$user_id = $User->id;

if (isset($_REQUEST['assignment'])) {
  $Assignment = new Assignment((int)$_REQUEST['assignment']);
}

$au = new auth_ssh();
if ($au->isAdmin() && isset($_REQUEST['id_student'])){
	// Если на страницу чата зашёл АДМИН
	$student_id = $_REQUEST['id_student'];
  $sender_user_type = 1;
} else if ($au->isTeacher() && isset($_REQUEST['id_student'])) {
  // Если на страницу чата зашёл ПРЕПОД
	$student_id = $_REQUEST['id_student'];
  $sender_user_type = 2;
} else if ($au->loggedIn()){
	// Если на страницу чата зашёл студент
	$student_id = $user_id;
  $sender_user_type = 3;
} else {
  header('Location:index.php');
  exit;
}

if (isset($_REQUEST['task'], $_REQUEST['page'], $_REQUEST['id_student'])){
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
    echo 'TASK&PAGE НЕ НАЙДЕНЫ';
		http_response_code(404);
		exit;
  }
  
} else if (isset($_REQUEST['assignment'])) {
  $assignment_id = 0;
  if (isset($_REQUEST['assignment']))
    $assignment_id = $_REQUEST['assignment'];


  $result = pg_query($dbconnect, "select task_id, page_id from ax_assignment a inner join ax_task t on a.task_id = t.id where a.id = $assignment_id");
	$row = pg_fetch_assoc($result);
	if ($row) {
    $task_id = $row['task_id'];
    $page_id = $row['page_id'];
  } else {
		echo 'ASSIGNMENT НЕ НАЙДЕН';
		http_response_code(404);
		exit;
	}

}

$query = select_ax_page_short_name($page_id);
$result = pg_query($dbconnect, $query);
$page_name = pg_fetch_assoc($result)['short_name'];


$MAX_FILE_SIZE = 5242880;


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
$query = "SELECT date_time from ax_message where assignment_id = $assignment_id and type = 2";
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


<body>
	<?php 
	if ($au->isTeacher() || $au->isAdmin()) 
		show_header($dbconnect, 'Чат c перподавателем', 
			array('Посылки по дисциплине: ' . $page_name => 'preptable.php?page=' . $page_id, $task_title => ''),
    $User); 
	else 
		show_header($dbconnect, 'Чат c перподавателем', 
			array($page_name => 'studtasks.php?page=' . $page_id, $task_title => ''),
    $User); 
	?>

	<main>
		<div class="task-wrapper">
			<h2><?= $task_title ?></h2>
			<div>
				<div class="task-desc-wrapper <?=$Task->isConversation() ? "me-0" : ""?>">
          <div class="d-flex justify-content-between align-self-start align-items-center">
            <b class="mb-0">Описание задания:</b>
            <?php if(!$User->isStudent()) {?>
              <a href="taskassign.php?assignment_id=<?=$Assignment->id?>" 
              class="btn btn-outline-primary d-flex" target="_blank">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">
                  <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
                  <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z"/>
                </svg>
                <span>&nbsp;&nbsp;Редактировать назначение</span>
              </a>
            <?php }?>
          </div>
          <p id="TaskDescr" class="m-0 p-0" style="overflow: auto;"><?= $task_description ?></p>
          <script>
            document.getElementById('TaskDescr').innerHTML =
              marked.parse(document.getElementById('TaskDescr').innerHTML);
            $('#TaskDescr').children().addClass("m-0");
          </script>
					<p style="line-height: 0.5em;">
          <?php
			      if ($User->isTeacher() || $User->isAdmin())
              $task_files = $Task->getFiles();
			      else
              $task_files = $Task->getVisibleFiles();

            if ($task_files) {
              echo '<b>Файлы, приложенные к заданию:</b>';
              showFiles($task_files);
            }
			    ?> 
          </p>
          
          
          
          <div class="d-flex justify-content-between align-self-end align-items-center">
            <?php if (!$Task->isConversation()) {?>
              <div>
                <b>Срок выполнения: </b> 
                &nbsp;<?=(!$Assignment->finish_limit) ? "бессрочно" : $Assignment->finish_limit?>
              </div>
            <?php }?>
            <div class="d-flex align-items-center">
              <div class="me-2 align-items-center" style="display: inline-block">
                  <div class="d-flex">
                    <?php foreach($Assignment->getStudents() as $i => $Student) {?>
                      <div data-title="<?=$Student->getFI()?>">
                        <button class="btn btn-floating shadow-none p-1 m-0 bg-image hover-overlay hover-zoom hover-shadow ripple" 
                        onclick="window.location='profile.php?user_id=<?=$Student->id?>'" style="/*left: -<?=$i*5?>%*/">
                          <?php if($Student->getImageFile() != null) {?>
                              <div class="embed-responsive embed-responsive-1by1" style="display: block;">
                                  <div class="embed-responsive-item">
                                    <img class="h-100 w-100 p-0 m-0 rounded-circle user-icon" style="vertical-align: unset; /*transform: translateX(-30%);*/" src="<?=$Student->getImageFile()->download_url?>"/>
                                  </div>
                              </div>
                          <?php } else { ?>
                              <svg class="h-100 w-100" xmlns="http://www.w3.org/2000/svg" width="20" fill="black" class="bi bi-person-circle" 
                              viewBox="0 0 16 16">
                              <path fill-rule="nonzero" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/>
                              <path fill-rule="nonzero" d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                              </svg>
                          <?php }?>
                          <a href="">
                            <div class="mask" style="background-color: rgba(var(--mdb-info-rgb), 0.2);"></div>
                          </a>
                        </button>
                      </div>
                    <?php }?>
                  </div>
                </div>
              <?php if(!$Task->isConversation()) {?>
                <a href="download_file.php?download_task_files=&task_id=<?=$task_id?>" style="height:fit-content;"
                class="btn btn-primary" target="_blank"><i class="fa-solid fa-file-arrow-down"></i>
                  <span>&nbsp;&nbsp;Скачать задание</span>
                </a>
              <?php }?>
            </div>
          </div>
				</div>

        <?php // FIXME: Посмотреть, доделать
        if(!$Task->isConversation()) {?>
          <div class="task-status-wrapper me-0">
            <div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="flexCheckDisabled" 
                  <?php if ($Assignment->isCompleted()) echo 'checked'; ?> disabled>
                  <?php //XXX: Проверить?>
                <label id="label-task-status-text"><?=status_to_text($Assignment->status)?></label>
              </div>
              <span id="span-answer-date"><?php if ($task_finish_date_time ) echo $task_finish_date_time;?></span><br>
              <span id="span-text-mark"><?php if($task_status_code == 4){?> 
                Оценка: <b id="b-mark"><?=$task_mark?></b>
              <?php }?>
              </span>
            </div>
            <div>
              <div>
                <a href="editor.php?assignment=<?=$assignment_id?>" class="btn btn-outline-primary my-1" style="width: 100%;" target="_blank">
                  <i class="fa-solid fa-file-pen"></i>&nbsp;&nbsp;
                  Онлайн редактор кода
                </a>
              </div>

              <?php if($au->isAdminOrTeacher()) { // Оценить отправленное на проверку задание ?>
                <form id="form-check-task" action="taskchat_action.php" method="POST">
                  <div class="d-flex flex-row my-1">
                    <div class="file-input-wrapper me-1">
                      <select id="select-mark" class="form-select" aria-label=".form-select" style="width: auto;" name="mark">
                        <option hidden value="-1"></option>
                        <?php for($i=1; $i<=$task_max_mark; $i++) {?>
                          <option value="<?=$i?>"><?=$i?></option>
                        <?php }?>
                      </select>
                    </div>
                    <button id="button-check" class="btn btn-success" target="_blank" type="submit"
                    name="submit-check" style="width: 100%;">
                      <i class="fa fa-check" aria-hidden="true"></i>&nbsp;&nbsp;Оценить ответ</button>
                  </div>
                </form>
              <?php } else if ($Assignment->isCompleteable()){ // Отправить задание на проверку ?>
                <form id="form-send-answer" action="taskchat_action.php" method="POST">
                  <div class="d-flex flex-row my-2">
                    <div class="file-input-wrapper me-1">
                      <input type="hidden" name="MAX_FILE_SIZE" value="<?=$MAX_FILE_SIZE?>" />
                      <input id="user-answer-files" type="file" name="answer_files[]" class="input-files" multiple>
                      <label for="user-answer-files" <?php if($task_status_code == 4) echo 'style="cursor: default;"';?>>
                        <i class="fa-solid fa-paperclip"></i>
                        <span id="files-answer-count" class="text-success"></span>
                      </label>
                    </div>
                    <button id="submit-answer" class="btn btn-success submit-files" target="_blank" type="submit" 
                    name="submit-answer">
                      <i class="fa-sharp fa-solid fa-file-import"></i>&nbsp;&nbsp;Загрузить ответ</button>
                  </div>
                </form>
              <?php }?>



            </div>
          </div>
        <?php }?>
			</div>
		</div>

		<div class="chat-wrapper mb-5">

			<div id="chat-box">
				<!-- Вывод сообщений на страницу -->
			</div>

      <div class="d-flex align-items-center">

      <div class="btn-group d-none" id="btn-group-more">
          <button type="button" class="btn btn-primary dropdown-toggle p-1 me-1" data-bs-toggle="dropdown" aria-expanded="false">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-three-dots-vertical" viewBox="0 0 16 16">
              <path d="M9.5 13a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
            </svg>
          </button>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="#">Действие</a></li>
            <li><a class="dropdown-item" href="#">Другое действие</a></li>
            <li><a class="dropdown-item" href="#">Что-то еще здесь</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="#">Отделенная ссылка</a></li>
          </ul>
        </div>

        <?php if($Assignment->isCompleteable() || $Task->isConversation()) {?>
          <form class="w-100  align-items-center" action="taskchat_action.php" method="POST" enctype="multipart/form-data">
            <div class="message-input-wrapper h-100 align-items-center p-0 m-0">
              <div class="file-input-wrapper">
                <input type="hidden" name="MAX_FILE_SIZE" value="<?=$MAX_FILE_SIZE?>" />
                <input id="user-files" type="file" name="user_files[]" class="input-files" multiple>
                <label for="user-files">
                  <i class="fa-solid fa-paperclip"></i><span id="files-count" class="label-files-count"></span>
                </label>
              </div>
              <textarea name="user-message" id="user-message" placeholder="Напишите сообщение..."></textarea>
              <button type="submit" name="submit-message" id="submit-message">Отправить</button>
            </div>
          </form>
        <?php }?>

      </div>

		</div>
	</main>
	
  <!-- <script type="text/javascript" src="js/messageHandler.js"></script> -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
	<script type="text/javascript" src="js/taskchat.js"></script>

  <script type="text/javascript">

    $(document).ready(function() {

      let form_sendAnswer = document.getElementById('form-send-answer');
      let form_check = document.getElementById('form-check-task');

      let button_check = document.getElementById('button-check');
      let button_answer = document.getElementById('submit-answer');

      // Отправка формы прикрепления ответа к заданию
      if (form_sendAnswer){
        form_sendAnswer.addEventListener('submit', function (event) {
          event.preventDefault();
          // console.log("СРАБОТАЛА ФОРМА ЗАГРУЗКИ ОТВЕТА НА ЗАДАНИЕ");
          var userFiles = $("#user-answer-files");
          // console.log(userFiles);
          if (userFiles.val() == '' || userFiles.length <= 0) {
            event.preventDefault();
            return false;
          } else {
            // var userMessage = 'Ответ на <<?=$task_number?>>:';
            var userMessage = '';
            if(sendMessage(userMessage, userFiles, 1)) {
              // console.log("Сообщение было успешно отправлено");
            }

            userFiles.val("");
            $('#files-answer-count').html('');
            button_answer.blur();

            loadChatLog(true);

            return false;
          }
        });
      } else if (form_check) {
        form_check.addEventListener('submit', function (event) {
        event.preventDefault();
        // console.log("СРАБОТАЛА ФОРМА ОЦЕНИВАНИЯ ЗАДАНИЕ");
        var selector_mark = $("#select-mark");
        var mark = selector_mark.val();
        // console.log(selector_mark);
        if (mark == -1){
          // console.log("ОЦЕНКА НЕ ВЫБРАНА");
          return false;
        } else {
          var userMessage = "Задание проверено.\nОценка: " + mark;
          if(sendMessage(userMessage, null, 2, parseInt(mark))) {
            // console.log("Сообщение было успешно отправлено");
          }
          // selector_mark.prop('disabled', 'disabled');
          // button_check.setAttribute('disabled', '');
          button_check.blur();
          
          loadChatLog(true);

          return false;
        }
        });
      }


      // Отправка формы сообщения через FormData (с моментальным обновлением лога чата)
      $("#submit-message").click(function() {
        var userMessage = $("#user-message").val();
        var userFiles = $("#user-files");

        if(!sendMessage(userMessage, userFiles, 0)) {
          event.preventDefault();
          // console.log("Сообщение было успешно отсправлено");
        } else {
          // console.log("Сообщение не было отправлено");
        }

        $("#user-message").val("");
        $("#user-message").css('height', '37.6px');
        $("#user-files").val("");
        $('#files-count').html('');

        loadChatLog(true);

        return false;
      });

      
      // Первое обновление лога чата
      loadChatLog(true);
      // Обновление лога чата раз в 1 секунд
      setInterval(loadChatLog, 100000);

      
    });
    


    // Обновляет лог чата из БД
    function loadNewMessages() {
      // console.log("LOAD_CHAT_LOG!");
      
      var formData = new FormData();
      formData.append('assignment_id', <?=$assignment_id?>);
      formData.append('user_id', <?=$user_id?>);
      formData.append('load_status', 'new_only');
      $.ajax({
        type: "POST",
        url: 'taskchat_action.php#content',
        cache: false,
        contentType: false,
        processData: false,
        data: formData,
        dataType : 'html',
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
      console.log("loadChatLog");
      // TODO: Обращаться к обновлению чата только в случае, если добавлиось новое, ещё не прочитанное сообщение
      $('#chat-box').load('taskchat_action.php#content', {assignment_id: <?=$assignment_id?>, user_id: <?=$user_id?>,  
      load_status: 'full'}, function() {
        // После первой загрузки страницы скролим чат вниз до новых сообщений или но самого низа
        if ($first_scroll) {
          if ($('#new-messages').length == 0) {
            $('#chat-box').scrollTop($('#chat-box').prop('scrollHeight'));
          }
          else {
            $('#chat-box').scrollTop($('#new-messages').offset().top - $('#chat-box').offset().top - 10);
          }
        }	
      })
    }


    function sendMessage(userMessage, userFiles, typeMessage, mark=null) {
      if ($.trim(userMessage) == '' && userFiles.val() == '') { 
        // console.log("ФАЙЛЫ НЕ ПРИКРЕПЛЕНЫ");
        return false; 
      }
      
      let flag = true;
      
      var formData = new FormData();
      formData.append('assignment_id', <?=$assignment_id?>);
      formData.append('user_id', <?=$user_id?>);
      formData.append('message_text', userMessage);
      formData.append('type', typeMessage);
      if(userFiles){
        // console.log("EEEEEEEEEE");
        //formData.append('MAX_FILE_SIZE', 5242880); // TODO Максимальный размер загружаемых файлов менять тут. Сейчас 5мб
        $.each(userFiles[0].files, function(key, input) {
          // console.log(input.size);
          // console.log(<?=$MAX_FILE_SIZE?>*0.8);
          if (input.size < <?=$MAX_FILE_SIZE?>*0.8){
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
          dataType : 'html',
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
              $("#span-text-mark").html("Оценка: "+'<b id="b-mark">'+mark+'</b>');
              console.log("Оценка: "+'<b id="b-mark">'+mark+'</b>');
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

    function selectMessage(message_id){
      if(selectedMessages.includes(message_id)) {
        let index = selectedMessages.indexOf(message_id);
        selectedMessages.splice(index, 1); 
        $('#btn-message-' + message_id).removeClass("bg-info");
        if(selectedMessages.length == 0)
          $('#btn-group-more').addClass("d-none");
      } else {
        selectedMessages.push(message_id);
        $('#btn-message-' + message_id).addClass("bg-info");
        $('#btn-group-more').removeClass("d-none");
      }
    }

    function moreActions(){

    }

  </script>

</body>

</html>
<?php
require_once("common.php");
require_once("dbqueries.php");
require_once("settings.php");

// Обработка некорректного перехода между страницами
if (!isset($_GET['task']) || !isset($_GET['page']) || !is_numeric($_GET['task']) || !is_numeric($_GET['page'])){
	header('Location:index.php');
  	exit;
}

$task_id = $_GET['task'];
$page_id = $_GET['page'];
$user_id = $_SESSION['hash'];

$query = select_discipline_name_by_page($page_id, 1);
$result = pg_query($dbconnect, $query);
$page_name = pg_fetch_assoc($result);

$au = new auth_ssh();
if ($au->isAdminOrTeacher() && isset($_GET['id_student'])){
	// Если на страницу чата зашёл преподаватель
	$student_id = $_GET['id_student'];

} else if ($au->loggedIn()){
	// Если на страницу чата зашёл студент
	$student_id = $user_id;
}

$query = select_task_assignment_student_id($student_id, $task_id);
$result = pg_query($dbconnect, $query);
$row = pg_fetch_assoc($result);
if ($row) {
	$assignment_id = $row['id'];
} else {
	echo '<p style="color:#f00">У страницы нет assignment_id в таблице</p>';
	exit;
}

$task_title = '';
$task_description = '';
$query = select_task($task_id);
$result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
$row = pg_fetch_assoc($result);
if ($row) {
	$task_title = $row['title'];
	$task_description = $row['description'];
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
	$task_status_code = $row['status_code'];
	$task_mark = $row['mark'];
}

$task_status_texts = ['Недоступно для просмотра', 'Недоступно для выполнения', 'Активно', 'Выполнено', 'Отменено', 'Ожидает проверки'];
$task_status_text = '';
if ($task_status_code != '') {
	$task_status_text = $task_status_texts[$task_status_code];
}

// $task_files - массив прикрепленных к странице с заданием файлов из ax_task_file
$query = "SELECT id, type, file_name, download_url from ax_task_file where task_id = $task_id";
$result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
$task_files = [];
for ($row = pg_fetch_assoc($result); $row; $row = pg_fetch_assoc($result)) {
	// Если текст файла лежит в БД
	if ($row['download_url'] == null) {
		$row['download_url'] = 'download_file.php?task_file_id=' . $row['id'];
	}
	// Если файл лежит на сервере
	else if (!preg_match('#^http[s]{0,1}://#', $row['download_url'])) {
		$row['download_url'] = 'download_file.php?file_path=' . $row['download_url'];
	}
	$task_files[] = ['type' => $row['type'], 'file_name' => $row['file_name'], 'download_url' => $row['download_url']];
}

// Выводит прикрепленные к странице с заданием файлы
function show_task_files() {
	global $task_files;
	foreach ($task_files as $f) {
		echo '<a href="' . $f['download_url'] . '" target="_blank" class="task-desc-wrapper-a"><i class="fa-solid fa-file"></i>' . $f['file_name'] . '</a> <br>';
	}
	if (count($task_files) == 0) {
		echo 'Файлы временно не доступны<br>';
	}
}

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
?>

<!DOCTYPE html>
<html lang="en">

<?php show_head('Чат с преподавателем'); ?>
<link rel="stylesheet" href="taskchat.css">
<!-- jquery -->
<script src="js/jquery/jquery-3.5.1.min.js"></script>
<!-- Font Awesome -->
<script src="https://kit.fontawesome.com/1dec1ea41f.js" crossorigin="anonymous"></script>

<body>
	<?php 
	if ($au->isTeacher()) 
		show_header_2($dbconnect, 'Чат c перподавателем', 
			array('Посылки по дисциплине: ' . $page_name['name'] => 'preptable.php?page=' . $page_id, $task_title => '')); 
	else 
		show_header_2($dbconnect, 'Чат c перподавателем', 
			array($page_name['name'] => 'studtasks.php?page=' . $page_id, $task_title => '')); 
	?>

	<main>
		<div class="task-wrapper">
			<h2><?= $task_title ?></h2>
			<div>
				<div class="task-desc-wrapper">
					<p><?= $task_description ?></p>
					<p><b>Требования к выполнению и результату:</b><br> <?php show_task_files(); ?></p>
					<div>
						<p><b>Срок выполнения: </b> <?= $task_finish_limit ?></p>
						<a href="download_file.php?download_task_files=&task_id=<?=$task_id?>" 
            class="btn btn-primary" target="_blank"><i class="fa-solid fa-file-arrow-down"></i>
              <span>&nbsp;&nbsp;Скачать задание</span></a>
					</div>
				</div>
				<div class="task-status-wrapper">
					<div>
						<div class="form-check">
							<input class="form-check-input" type="checkbox" value="" id="flexCheckDisabled" 
								<?php if ($task_status_code == 3) echo 'checked'; ?> disabled>
							<label><?= $task_status_text ?></label>
						</div>
						<?php  
						if ($task_status_code == 3) {
							if ($task_finish_date_time ) echo "<br> $task_finish_date_time <br>";
							echo "Оценка: <b>$task_mark</b>";
						}?>
					</div>
          <div>
            <div>
              <a href="editor.php?assignment=<?=$assignment_id?>" class="btn btn-outline-primary" target="_blank" style="margin-top: 10px;">
                <i class="fa-solid fa-file-pen"></i>&nbsp;&nbsp;Онлайн редактор кода</a>
            </div>

            <?php if($au->isAdminOrTeacher()) { // Отправить задание на проверку ?>
              <form id="form-send-answer" action="taskchat_action.php" method="POST">
                <div class="d-flex flex-row my-2">
                  <div class="file-input-wrapper me-1">
                    <input id="user-answer-files" type="file" name="answer_files[]" class="input-files" multiple>
                    <label for="user-answer-files">
                      <i class="fa-solid fa-paperclip"></i><span id="files-answer-count" class="text-success"></span>
                    </label>
                  </div>
                  <button id="submit-answer" class="btn btn-outline-success submit-files" 
                  target="_blank" type="submit" name="submit-answer">
                    <i class="fa-sharp fa-solid fa-file-import"></i>&nbsp;&nbsp;Загрузить ответ</button>
                </div>
              </form>
            <?php } else { // Оценить отправленное на проверку задание?>
              <form id="form-send-answer" action="taskchat_action.php" method="POST">
                <div class="d-flex flex-row my-2">
                  <div class="file-input-wrapper me-1">
                    <input id="user-answer-files" type="file" name="answer_files[]" class="input-files" multiple>
                    <label for="user-answer-files">
                      <i class="fa-solid fa-paperclip"></i><span id="files-answer-count" class="text-success"></span>
                    </label>
                  </div>
                  <button id="submit-answer" class="btn btn-outline-success submit-files" 
                  target="_blank" type="submit" name="submit-answer">
                    <i class="fa-sharp fa-solid fa-file-import"></i>&nbsp;&nbsp;Загрузить ответ</button>
                </div>
              </form>
            <?php }?>



          </div>
				</div>
			</div>
		</div>

		<div class="chat-wrapper">

			<div id="chat-box">
				<!-- Вывод сообщений на страницу -->
			</div>

			<form action="taskchat_action.php" method="POST" enctype="multipart/form-data">
				<div class="message-input-wrapper">
					<div class="file-input-wrapper">
						<input id="user-files" type="file" name="user_files[]" class="input-files" multiple>
						<label for="user-files">
              <i class="fa-solid fa-paperclip"></i><span id="files-count" class="label-files-count"></span>
            </label>
					</div>
					<textarea name="user-message" id="user-message" placeholder="Напишите сообщение..."></textarea>
					<button type="submit" name="submit-message" id="submit-message">Отправить</button>
				</div>
			</form>

		</div>
	</main>
	
  
	<script type="text/javascript">
		
		// После первой загрузки скролим страницу вниз
		$('body, html').scrollTop($('body, html').prop('scrollHeight'));

		$('#user-message').on('input', function() {
   		 	if ($(this).val() != '') {
				$(this).css('height', '88.8px');
				$('body, html').scrollTop($('body, html').prop('scrollHeight'));
			}
			else {
				$(this).css('height', '37.6px');
			}
		});


		/* Логика скрола на странице
		Открываем страницу - страница скролится вниз, чат скролится до последнего непрочитанного сообщения
		Отправляем сообщение - чат скролится вниз
		Приходит сообщение от собеседника - появляется плашка "Новые сообщения"
		*/


    // Показывает количество прикрепленных для отправки файлов
		$('#user-files').on('change', function() {
      // TODO: Сделать удаление числа, если оно 0
      if (this.files.length != 0)
			  $('#files-count').html(this.files.length);
      else
        $('#files-count').html(this.files.length);
		});

    // Показывает количество прикрепленных для отправки файлов
		$('#user-answer-files').on('change', function() {
      // TODO: Сделать удаление числа, если оно 0
			$('#files-answer-count').html(this.files.length);
		});


		$(document).ready(function() {

      let form_sendAnswer  = document.getElementById('form-send-answer');

      // Отправка формы прикрепления ответа на задание
      form_sendAnswer.addEventListener('submit', function (event) {
        event.preventDefault();
        console.log("СРАБОТАЛА ФОРМА ЗАГРУЗКИ ОТВЕТА НА ЗАДАНИЕ");
        var userFiles = $("#user-answer-files");
        console.log(userFiles);
        if (userFiles.val() == '' || userFiles.length <= 0) {
          event.preventDefault();
          return false;
        } else {
          var userMessage = "Ответ на задание:";
          if(sendMessage(userMessage, userFiles, true)) {
            console.log("Сообщение было успешно отправлено");
          }

          userFiles.val("");
          $('#files-answer-count').html('');

          // Первое обновление лога чата
          loadChatLog(true);
          // Обновление лога чата раз в 5 секунд
          setInterval(loadChatLog, 5000);

          return false;
        }
      });

			// Отправка формы сообщения через FormData (с моментальным обновлением лога чата)
			$("#submit-message").click(function() {
				var userMessage = $("#user-message").val();
				var userFiles = $("#user-files");

        if(!sendMessage(userMessage, userFiles, false)) {
          event.preventDefault();
          console.log("Сообщение было успешно отсправлено");
        } else {
          console.log("Сообщение не было отправлено");
        }

				$("#user-message").val("");
				$("#user-message").css('height', '37.6px');
				$("#user-files").val("");
				$('#files-count').html('');
				return false;
			});
			// Первое обновление лога чата
			loadChatLog(true);
			// Обновление лога чата раз в 5 секунд
			setInterval(loadChatLog, 5000);
		});


    // Обновляет лог чата из БД
		function loadChatLog($first_scroll = false) {
			$('#chat-box').load('taskchat_action.php #content', {assignment_id: <?=$assignment_id?>, user_id: <?=$user_id?>}, function() {
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


    function sendMessage(userMessage, userFiles, isAnswer) {
      if ($.trim(userMessage) == '' && userFiles.val() == '') { 
        console.log("ФАЙЛЫ НЕ ПРИКРЕПЛЕНЫ");
        return false; 
      }
      
      var formData = new FormData();
      formData.append('message_text', userMessage);
      formData.append('assignment_id', <?=$assignment_id?>);
      formData.append('user_id', <?=$user_id?>);
      formData.append('answer', isAnswer);
      formData.append('MAX_FILE_SIZE', 5242880); // TODO Максимальный размер загружаемых файлов менять тут. Сейчас 5мб
      $.each(userFiles[0].files, function(key, input) {
        if (!isAnswer)
          formData.append('message-files[]', input);
        else 
          formData.append('answer-files[]', input);
      });

      $.ajax({
        type: "POST",
        url: 'taskchat_action.php #content',
        cache: false,
        contentType: false,
        processData: false,
        data: formData,
        dataType : 'html',
        success: function(response) {
          $("#chat-box").html(response);
        },
        complete: function() {
          // Скролим чат вниз после отправки сообщения
          $('#chat-box').scrollTop($('#chat-box').prop('scrollHeight'));
        }
      });
      return true;
		}

	</script>
</body>

</html>
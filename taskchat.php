<?php
require_once("common.php");
require_once("dbqueries.php");
require_once("settings.php");

if (!isset($_GET['task']) || !isset($_GET['page']) || !isset($_SESSION['hash'])) {
	echo '<p style="color:#f00">Некорректное обращение</p>';
	exit;
}

$task_id = $_GET['task'];
$page_id = $_GET['page'];
$user_id = $_SESSION['hash'];

$au = new auth_ssh();

if ($au->isAdminOrTeacher() && isset($_GET['id_student'])){
	// Если на страницу чата зашёл преподаватель
	$student_id = $_GET['id_student'];
} else {
	// Если на страницу чата зашёл студент
	$student_id = $user_id;
}

$discipline_short_name = '';
$query = select_disc_short_name($page_id);
$result = pg_query($dbconnect, $query);
$row = pg_fetch_assoc($result);
if ($row) {
	$discipline_short_name = $row['short_name'];
}

$query = select_task_assignment_id($student_id, $task_id);
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
$query = select_task_assignment($task_id, $student_id);
$result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
$row = pg_fetch_assoc($result);
if ($row) {
	$time_date = explode(" ", $row['finish_limit']);
	$date = explode("-", $time_date[0]);
	$time = explode(":", $time_date[1]);
	$task_finish_limit = $date[2] . "." . $date[1] . "." . $date[0] . " " . $time[0] . ":" . $time[1];
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
<script src="js/jquery-3.5.1.min.js"></script>
<!-- Font Awesome -->
<script src="https://kit.fontawesome.com/1dec1ea41f.js" crossorigin="anonymous"></script>

<body>
	<?php show_header_2($dbconnect, 'Чат c перподавателем', 
		array('Дэшборд студента' => 'mainpage_student.php', $discipline_short_name => 'studtasks.php?page=' . $page_id, $task_title => '')); ?>

	<main>
		<div class="task-wrapper">
			<h2><?= $task_title ?></h2>
			<div>
				<div class="task-desc-wrapper">
					<p><?= $task_description ?></p>
					<p><b>Требования к выполнению и результату:</b><br> <?php show_task_files(); ?></p>
					<div>
						<p><b>Срок выполнения: </b> <?= $task_finish_limit ?></p>
						<a href="download_file.php?download_task_files=&task_id=<?=$task_id?>" class="task-download-button" target="_blank"><i class="fa-solid fa-file-arrow-down"></i><span>Скачать задание</span></a>
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
					<a href="editor.php?assignment=<?=$assignment_id?>" class="code-redactor-button" target="_blank"><i class="fa-solid fa-file-pen"></i>Онлайн редактор кода</a>
				</div>
			</div>
		</div>

		<div class="chat-wrapper">

			<div id="chat-box">
				<!-- Вывод сообщений на страницу -->
			</div>

			<form action="message_requires.php" method="POST" enctype="multipart/form-data">
				<div class="message-input-wrapper">
					<div class="file-input-wrapper">
						<input type="hidden" name="MAX_FILE_SIZE" value="5242880"> <!-- 5mb максимальный размер файла -->
						<input type="file" name="user_files[]" id="user-files" multiple>
						<label for="user-files"><i class="fa-solid fa-paperclip"></i><span id="files-count"></span></label>
					</div>
					<input type="text" name="user-message" id="user-message" placeholder="Напишите сообщение...">
					<button type="submit" name="submit-message" id="submit-message">Отправить</button>
				</div>
			</form>
		</div>
	</main>
	
	<script type="text/javascript">
		
		// После первой загрузки скролим страницу вниз
		$('body, html').scrollTop($('body, html').prop('scrollHeight'));

		// Показывает количество прикрепленных для отправки файлов
		$('#user-files').on('change', function() {
			$('#files-count').html(this.files.length);
		});

		/*
		Открываем страницу - страница скролится вниз, чат скролится до последнего непрочитанного сообщения
		Отправляем сообщение - чат скролится вниз
		Приходит сообщение от собеседника - появляется плашка "Новые сообщения"
		*/

		// Обновляет лог чата из БД
		function loadChatLog($first_scroll = false) {
			$('#chat-box').load('message_requires.php #content', {assignment_id: <?=$assignment_id?>, user_id: <?=$user_id?>}, function() {
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

		$(document).ready(function() {
			// Отправка формы сообщения через FormData (с моментальным обновлением лога чата)
			$("#submit-message").click(function() {
				var userMessage = $("#user-message").val();
				var userFiles = $("#user-files");
				if ($.trim(userMessage) == '' && userFiles.val() == '') { return false; }
				var formData = new FormData();
				formData.append('message_text', userMessage);
				formData.append('assignment_id', <?=$assignment_id?>);
				formData.append('user_id', <?=$user_id?>);
				formData.append('MAX_FILE_SIZE', 50000);
				$.each(userFiles[0].files, function(key, input) {
					formData.append('files[]', input);
				});
 
				$.ajax({
					type: "POST",
					url: 'message_requires.php #content',
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
				$("#user-message").val("");
				$("#user-files").val("");
				$('#files-count').html('');
				return false;
			});
			// Первое обновление лога чата
			loadChatLog(true);
			// Обновление лога чата раз в 5 секунд
			setInterval(loadChatLog, 5000);
		});
	</script>
</body>

</html>

<?php
/* Select запросы, надо в dbqueries.php, но пока тут лежат */

function select_task_assignment_id($user_id, $task_id) {
	return "SELECT ax_assignment.id from ax_assignment
	    inner join ax_assignment_student on ax_assignment.id = ax_assignment_student.assignment_id
	    where ax_assignment_student.student_user_id = $user_id and ax_assignment.task_id = $task_id;";
}

function select_disc_short_name($page_id) {
	return "SELECT short_name from ax_page where id = $page_id";
}

function select_messages($assignment_id) {
    return "SELECT ax_message.id, students.first_name, students.middle_name, ax_message.type, ax_message.full_text, ax_message.date_time, 
        ax_message.sender_user_type, ax_message.sender_user_id, ax_message.id as \"message_id\", ax_message.status
        from ax_message
        inner join students on ax_message.sender_user_id = students.id
        where ax_message.assignment_id = $assignment_id order by date_time";
}

function select_message_attachment($message_id) {
	return "SELECT ax_message_attachment.file_name, ax_message_attachment.download_url, ax_message_attachment.full_text from ax_message_attachment
	inner join ax_message on ax_message.id = ax_message_attachment.message_id
	where ax_message_attachment.message_id = $message_id";
}
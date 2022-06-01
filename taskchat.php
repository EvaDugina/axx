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

$discipline_short_name = '';
$query = select_disc_short_name($page_id);
$result = pg_query($dbconnect, $query);
$row = pg_fetch_assoc($result);
if ($row) {
	$discipline_short_name = $row['short_name'];
}

// TODO: реализовать добавление ссылок для раздела "Требования к выполнению и результату" через БД
$task_requirements_links = [
	'Гайдлайн по оформлению программного кода.pdf' => 'https://vega.fcyb.mirea.ru/',
	'Инструкция по подготовке кода к автотестам.pdf' => 'https://vega.fcyb.mirea.ru/'
];
function show_task_requirements_links() {
	global $task_requirements_links;
	foreach ($task_requirements_links as $link_text => $link_value) {
		echo "<a href=\"$link_value\" target=\"_blank\" class=\"task-desc-wrapper-a\"><i class=\"fa-solid fa-file\"></i>$link_text</a><br>";
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

<script type="text/javascript">
	// должна возвращать (id?) последнего сообщения
	function lastReadedMessage() {

	}
	function scrollToLastReadMessage() {
		var div = $("#chat-box");

		// получение последнего, прочитанного элемента
		var message = $("#message-" + 12);

		div.scrollTop(div.prop('scrollHeight'));
	}

	function scrollDown() {
		var div = $("#chat-box");
		div.scrollTop(div.prop('scrollHeight'));
	}
</script>

<body onload="scrollDown();">
	<?php show_header_2($dbconnect, 'Чат c перподавателем', 
		array('Дэшборд студента' => 'mainpage_student.php', $discipline_short_name => 'studtasks.php?page=' . $page_id, $task_title => '')); ?>

	<main>
		<div class="task-wrapper">
			<h2><?= $task_title ?></h2>
			<div>
				<div class="task-desc-wrapper">
					<p><?= $task_description ?></p>
					<p><b>Требования к выполнению и результату:</b><br> <?php show_task_requirements_links(); ?></p>
					<div>
						<p><b>Срок выполнения: </b> <?= $task_finish_limit ?></p>
						<a href="https://vega.fcyb.mirea.ru/" class="task-download-button" target="_blank"><i class="fa-solid fa-file-arrow-down"></i><span>Скачать задание</span></a>
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
					<span>Сообщение:</span>
					<input type="text" name="user-message" id="user-message" placeholder="Напишите сообщение...">
					<button type="submit" name="submit-message" id="submit-message">Отправить</button>
				</div>
				<div class="file-input-wrapper">
					<span>Вложения:</span>
					<input type="file" multiple name="user-files" id="user-files">
				</div>
			</form>
		</div>
	</main>
	
	<script type="text/javascript">
		// Обновление лога чата
		function loadChatLog() {
			$('#chat-box').load('message_requires.php #content', {assignment_id: <?= $assignment_id ?>, user_id: <?= $user_id ?>});
		}

		$(document).ready(function() {

			// Отправка сообщения (с моментальным обновлением лога чата)
			$("#submit-message").click(function() {
				var userMessage = $("#user-message").val();
				if ($.trim(userMessage) == '') { return false; }
				var userFiles = $('#user-files').val();
				$('#chat-box').load('message_requires.php #content', 
					{message_text: userMessage, assignment_id: <?= $assignment_id ?>, user_id: <?= $user_id ?>});
				$("#user-message").val("");
				return false;
			});

			loadChatLog();
			setInterval(loadChatLog, 2500);
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
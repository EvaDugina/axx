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

$query = select_task_assignment_id($user_id, $task_id);
$result = pg_query($dbconnect, $query);
$row = pg_fetch_assoc($result);
if ($row) {
	$assignment_id = $row['id'];
}

if (!isset($assignment_id)) {
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
$task_status_texts = ['недоступно для просмотра', 'недоступно для выполнения', 'активно', 'выполнено', 'отменено', 'ожидает проверки'];
$task_status_text = '';
$task_mark = '';
$query = select_task_assignment($task_id, $user_id);
$result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
$row = pg_fetch_assoc($result);
if ($row) {
	$time_date = explode(" ", $row['finish_limit']);
    $date = explode("-", $time_date[0]);
    $time = explode(":", $time_date[1]);
    $task_finish_limit = $date[2] .".". $date[1] .".". $date[0] ." ". $time[0] .":". $time[1];
	$task_status_code = $row['status_code'];
	$task_mark = $row['mark'];
}
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

// Сюда добавлять ссылки для раздела "Требования к выполнению и результату"
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

// Если в ax_assignment status_code = 3, значит задание проверено и в ax_message есть сообщение от препода со статусом 2, в котором он ставит оценку
$task_finish_date_time = '';

// Возвращает двумерный массив сообщений для текущей страницы по ax_assignment
function get_messages() {
	global $dbconnect, $assignment_id, $task_finish_date_time;
	$query = select_messages($assignment_id);
	$result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
	$row = pg_fetch_assoc($result);
	$ret = array();
	while ($row) {
		$username = $row['first_name'] . ' ' . $row['middle_name'];
		$message_time = explode(" ", $row['date_time']);
		$date = explode("-", $message_time[0]);
		$time = explode(":", $message_time[1]);
		$date_time = $date[2] .".". $date[1] .".". $date[0] ." ". $time[0] .":". $time[1];
		$ret[] = array('username' => $username, 'full_text' => $row['full_text'], 'date_time' => $date_time);

		if ($row['type'] == 2) { $task_finish_date_time = $date_time; }
		$row = pg_fetch_assoc($result);
	}
	return $ret;
}

$user_messages = get_messages();
?>

<!DOCTYPE html>
<html lang="en">

<?php show_head('Чат с преподавателем'); ?>
<link rel="stylesheet" href="taskchat.css">
<!-- Font Awesome -->
<script src="https://kit.fontawesome.com/1dec1ea41f.js" crossorigin="anonymous"></script>

<body>
	<?php show_header_2($dbconnect, 'Чат c перподавателем', 
		array('Дэшборд студента' => 'mainpage_student.php', $discipline_short_name => 'studtasks.php?page='. $page_id, $task_title => '')); ?>
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
						<div style="display:flex; align-items:center">
							<div class="check-mark" style="<?= $task_status_code == 3 ? 'background:#0f0' : '' ?>">
								<i class="fa-solid fa-check"></i>
							</div>
							<b><?= $task_status_text ?></b>
						</div>
						<?php if ($task_status_code == 3 || $task_status_code == 5): ?>
							<br><br>
							<?= $task_finish_date_time ?> <br>
							Оценка: <b><?= $task_mark ?></b>
						<?php endif; ?>
					</div>
					<a href="https://vega.fcyb.mirea.ru/" class="code-redactor-button" target="_blank"><i class="fa-solid fa-file-pen"></i>Онлайн редактор кода</a>
				</div>
			</div>
		</div>
		<div class="chat-wrapper">
			<div id="chat-box">
				<?php
				// if (file_exists("log.html") && filesize("log.html") > 0) {
				// 	$handle = fopen("log.html", "r");
				// 	$contents = fread($handle, filesize("log.html"));
				// 	fclose($handle);
				// 	echo $contents;}
				// Вывод сообщений на страницу
				foreach ($user_messages as $m) {
					echo '
					<div class="chat-box-message">
						<div class="chat-box-message-wrapper">
							<b>' . $m['username'] . '</b><br>'
							. stripslashes(htmlspecialchars($m['full_text'])) . '
						</div>
						<div class="chat-box-message-date">
							' . $m['date_time'] . '
						</div>
					</div>
					';
				}
				?>
			</div>
			<form action="" method="POST" class="message-form">
				<span>Сообщение:</span>
				<input type="text" name="user-message" id="user-message" placeholder="Напишите сообщение...">
				<button type="submit" name="submit-message" id="submit-message">Отправить</button>
			</form>
			<form action="post.php" method="POST" enctype="multipart/form-data">
				<span>Вложения:</span>
				<input id="file-input" class="input-file" type="file" name="filename">
			</form>	
		</div>
		<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.3/jquery.min.js"></script>
		<script type="text/javascript">
		// jQuery Document
		$(document).ready(function(){
			
			//If user submits the form
			$("#submit-message").click(function(){	
				var userMessage = $("#user-message").val();
				$.post("post.php", {text: userMessage});				
				$("#user-message").attr("value", "");
				return false;
			});
			
			//Load the file containing the chat log
			// function loadLog() {		
			// 	var oldscrollHeight = $("#chat-box").attr("scrollHeight") - 20;
			// 	$.ajax ({
			// 		url: "log.html",
			// 		cache: false,
			// 		success: function(html) {		
			// 			$("#chat-box").html(html); //Insert chat log into the #chat-box
			// 		// for ($m = 0; $m < count($messages); $m++) { // list all messages
			// 		//   if ($messages[$m]['mtype'] != null)
			// 		//     show_message($messages[$m]);
			// 		// }
			// 			var newscrollHeight = $("#chat-box").attr("scrollHeight") - 20;
			// 			if(newscrollHeight > oldscrollHeight) {
			// 				$("#chat-box").animate({ scrollTop: newscrollHeight }, 'normal'); //Autoscroll to bottom of div
			// 			}				
			// 		},
			// 	});
			// }
			setInterval (select_chat_messages($user_id), 2000);	//Reload ax_messages every 2 seconds
		});
		</script>
	</main>
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
    return "SELECT students.first_name, students.middle_name, ax_message.type, ax_message.full_text, ax_message.date_time 
        from ax_message 
        inner join students on ax_message.sender_user_id = students.id
        where ax_message.assignment_id = $assignment_id order by date_time";
}
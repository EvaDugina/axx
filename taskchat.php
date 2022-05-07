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

$query = select_all_disciplines();
$result = pg_query($dbconnect, $query);
$disciplines = pg_fetch_all($result);

$task_title = '';
$task_description = '';
if (isset($task_id)) {
	$query = "select title, description from ax_task where id = $task_id;";
	$result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
	$row = pg_fetch_assoc($result);
	if ($row) {
		$task_title = $row['title'];
		$task_description = $row['description'];
	}
}

$task_finish_limit = '';
$task_status_code = '';
$task_status_texts = ['недоступно для просмотра', 'недоступно для выполнения', 'активно', 'выполнено', 'отменено', 'ожидает проверки'];
$task_status_text = '';
$task_mark = '';
// $_SESSION['hash'] is a user id
if (isset($task_id) && isset($_SESSION['hash'])) {
	$query = select_task_assignment($task_id, $_SESSION['hash']);
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
}
if ($task_status_code != '') {
	$task_status_text = $task_status_texts[$task_status_code];
}

$discipline_name = '';
if ($page_id) {
	$query = "select d.name as \"discipline\" from ax_page p inner join discipline d on p.disc_id = d.id where p.id = $page_id;";
	$result = pg_query($dbconnect, $query);
	$row = pg_fetch_assoc($result);
	if ($row) {
		$discipline_name = $row['discipline'];
	}
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

show_header('Чат', array($discipline_name => 'studtasks.php?page='. $page_id));
?>
<link rel="stylesheet" href="taskchat.css">
<!-- Font Awesome -->
<script src="https://kit.fontawesome.com/1dec1ea41f.js" crossorigin="anonymous"></script>
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
						21.10.2021 17:34 <br>
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
			if (file_exists("log.html") && filesize("log.html") > 0) {
				$handle = fopen("log.html", "r");
				$contents = fread($handle, filesize("log.html"));
				fclose($handle);
				echo $contents;
			}?>
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
		function loadLog() {		
			var oldscrollHeight = $("#chat-box").attr("scrollHeight") - 20;
			$.ajax ({
				url: "log.html",
				cache: false,
				success: function(html) {		
					$("#chat-box").html(html); //Insert chat log into the #chat-box
				// for ($m = 0; $m < count($messages); $m++) { // list all messages
				//   if ($messages[$m]['mtype'] != null)
				//     show_message($messages[$m]);
				// }
					var newscrollHeight = $("#chat-box").attr("scrollHeight") - 20;
					if(newscrollHeight > oldscrollHeight) {
						$("#chat-box").animate({ scrollTop: newscrollHeight }, 'normal'); //Autoscroll to bottom of div
					}				
				},
			});
		}
		setInterval (loadLog, 2000);	//Reload file every 2 seconds
	});
	</script>
</main>

<?php show_footer(); ?>

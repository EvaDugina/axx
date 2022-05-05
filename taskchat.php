<?php
require_once("common.php");
require_once("dbqueries.php");
require_once("settings.php");

$task_id = $_GET['task_id'];
$page_id = $_GET['page_id'];

$query = select_all_disciplines();
$result = pg_query($dbconnect, $query);
$disciplines = pg_fetch_all($result);

//////////////////////////////OLD VERSIONS////////////////////////////

/*
В БД есть данные для:
user ivan (id = -7)
http://localhost/accelerator/mainpage_student.php -> ПТ 21/22 ч1 -> Задания 1-5
*/

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

if(isset($_GET['logout'])){	
	
	//Simple exit message
	$fp = fopen("log.html", 'a');
	fwrite($fp, "<div class='msgln'><i>User ". $_SESSION['username'] ." has left the chat session.</i><br></div>");
	fclose($fp);
	
	session_destroy();
	header("Location: index.php"); //Redirect the user
}

function loginForm(){
	echo'
	<div id="loginform">
	<form action="index.php" method="post">
		<p>Please login in to continue</p>

	</form>
	</div>
	';
}

if(isset($_POST['enter'])){
	if($_POST['username'] != ""){
		$_SESSION['login'] = stripslashes(htmlspecialchars($_POST['username']));
	}
	else{
		echo '<span class="error">Please type in a name</span>';
	}
}
?>

<?php show_header('Чат', array($discipline_name => 'studtasks.php?page='. $page_id)); ?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="style.css">
	<!-- Font Awesome -->
	<script src="https://kit.fontawesome.com/1dec1ea41f.js" crossorigin="anonymous"></script>
</head>

<body>
	<div class="base-container">
        <main>
            <section class="task-block">
                <h2 class="title-2"><?= $task_title ?></h2>
                <div class="task-block--wrapper">
                    <div><?= $task_description ?></div>
                    <div>
                        <b>Требования к выполнению и результату:</b><br>
                        <a href="" class="task-block--ref"><i class="fa-solid fa-file"></i>Гайдлайн по оформлению программного кода.pdf</a><br> 
                        <a href="" class="task-block--ref"><i class="fa-solid fa-file"></i>Инструкция по подготовке кода к автотестам.pdf</a>
					</div>
                    <div class="task-block--wrapper-2">
						<div><b>Срок выполения:</b> <?php echo $task_finish_limit; ?></div>
						<a href="" class="download-button"><i class="fa-solid fa-file-arrow-down"></i>Скачать задание</a>
					</div>
                </div>
                <div class="status">
                    <div>
						<div class="check-mark" style="<?= $task_status_code == 3 ? 'background:#0f0' : '' ?>"><i class="fa-solid fa-check"></i></div>
						<b><?= $task_status_text ?></b> 
						<?php if ($task_status_code == 3 || $task_status_code == 5): ?>
							<br><br>
							21.10.2021 17:34 <br>
							Оценка: <b><?= $task_mark ?></b>
						<?php endif; ?>
                    </div>
                    <a href="" class="code-redactor-button"><i class="fa-solid fa-file-pen"></i>Онлайн редактор кода</a>
                </div>
                <div class="clear"></div>
            </section>
            <section>
                <?php
if(!isset($_SESSION['login'])){
	loginForm();
}
else{
?>
<div id="wrapper">	
	<div id="chatbox"><?php
	if (file_exists("log.html") && filesize("log.html") > 0) {
		$handle = fopen("log.html", "r");
		$contents = fread($handle, filesize("log.html"));
		fclose($handle);
		echo $contents;
	}
}?>
	</div>
	

	<form name="message" action="" class="msg-form">
		<span>Сообщение:</span>
		<input name="usermsg" type="text" id="usermsg" size="63" placeholder="Напишите сообщение...">
		<input name="submitmsg" type="submit" id="submitmsg" value="Отправить">
	</form>
 <!-- ОБРАБОТКА ОТПРАВКИ ФАЙЛА -->
	<form method="POST" action="post.php" id="upload-container" enctype="multipart/form-data">
		<div>
			<span>Вложения:</span>
			<input id="file-input" class="input-file" type="file" name="filename">
			<!-- <span>или перетащите его сюда</span> -->
		</div>
	</form>		
</div>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.3/jquery.min.js"></script>
<script type="text/javascript">

// jQuery Document
$(document).ready(function(){
	
	//If user submits the form
	$("#submitmsg").click(function(){	
		var clientmsg = $("#usermsg").val();
		$.post("post.php", {text: clientmsg});				
		$("#usermsg").attr("value", "");
		return false;
	});
	
	//Load the file containing the chat log
	function loadLog(){		
		var oldscrollHeight = $("#chatbox").attr("scrollHeight") - 20;
		$.ajax({
			url: "log.html",
			cache: false,
			success: function(html){		
				$("#chatbox").html(html); //Insert chat log into the #chatbox div
		
   
            // for ($m = 0; $m < count($messages); $m++) { // list all messages
            //   if ($messages[$m]['mtype'] != null)
            //     show_message($messages[$m]);
            // }
       
 				
				var newscrollHeight = $("#chatbox").attr("scrollHeight") - 20;
				if(newscrollHeight > oldscrollHeight){
					$("#chatbox").animate({ scrollTop: newscrollHeight }, 'normal'); //Autoscroll to bottom of div
				}				
		  	},
		});
	}
	setInterval (loadLog, 2000);	//Reload file every 2 seconds
	
	//If user wants to end session
	$("#exit").click(function(){
		var exit = confirm("Are you sure you want to end the session?");
		if(exit==true){window.location = 'index.php?logout=true';}		
	});
});

	

</script>
<p class="logout"><a id="exit" href="#">Sign Out</a></p><!--временный костыль -->
            </section>

        </main>
    </div>
</body>
</html>
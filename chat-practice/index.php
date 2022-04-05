<?php
session_start();

if(isset($_GET['logout'])){	
	
	//Simple exit message
	$fp = fopen("log.html", 'a');
	fwrite($fp, "<div class='msgln'><i>User ". $_SESSION['name'] ." has left the chat session.</i><br></div>");
	fclose($fp);
	
	session_destroy();
	header("Location: index.php"); //Redirect the user
}

function loginForm(){
	echo'
	<div id="loginform">
	<form action="index.php" method="post">
		<p>Please enter your name to continue:</p>
		<label for="name">Name:</label>
		<input type="text" name="name" id="name" />
		<input type="submit" name="enter" id="enter" value="Enter" />
	</form>
	</div>
	';
}

if(isset($_POST['enter'])){
	if($_POST['name'] != ""){
		$_SESSION['name'] = stripslashes(htmlspecialchars($_POST['name']));
	}
	else{
		echo '<span class="error">Please type in a name</span>';
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Chat - KOSMOS</title>
<link type="text/css" rel="stylesheet" href="style.css" />
</head>


<body>
<div class="base-container">
        <header id="base-header">
            <div class="return-back">
                <a href="" class="return-button">🠔</a> <span>Введение в разработку ПО</span>
            </div>
            <div class="user-data">
                <a href=""><img src="images/bell.png"></a>
                <a href=""><img src="images/profile.png"></a>
                <span>Сергей Иванов</span>
            </div>
            <div class="clear"></div>
        </header>
        <main>
            <section class="task-block">
                <h2 class="title-2">Задание #3. Работа с файлами</h2>
                <div class="requirements">
                    <p class="paragraph">Разработать программу для чтения файла с диска, выбора нечетных строк и сохранения их в новый файл.<br>Новый файл должен получить тоже самое имя</p>
                    <p class="paragraph">
                        <b>Требования к выполнению и результату:</b><br>
                        📃 <a href="">Гайдлайн по оформлению программного кода.pdf</a><br> 
                        📃 <a href="">Инструкция по подготовке кода к автотестам.pdf</a>
                    </p>
                    <p class="paragraph inline-block">
                        <b>Срок выполения:</b> 18.04.2021 23:59
                    </p>
                    <a href="" class="download-button">🡇 Скачать задание</a>
                    <div class="clear"></div>
                </div>
                <div class="status">
                    <div>
                        ✅ <b>Выполнено</b> <br><br>
                        21.10.2021 17:34 <br>
                        Оценка: 3
                    </div>
                    <a href="" class="code-redactor-button">📝 Онлайн редактор кода</a>
                </div>
                <div class="clear"></div>
            </section>
            <section>
                <?php
if(!isset($_SESSION['name'])){
	loginForm();
}
else{
?>
<div id="wrapper">
	<div id="menu">
		<p class="welcome">Welcome, <b><?php echo $_SESSION['name']; ?></b></p>
		<p class="logout"><a id="exit" href="#">Exit Chat</a></p>
		<div style="clear:both"></div>
	</div>	
	<div id="chatbox"><?php
	if(file_exists("log.html") && filesize("log.html") > 0){
		$handle = fopen("log.html", "r");
		$contents = fread($handle, filesize("log.html"));
		fclose($handle);
		
		echo $contents;
	}
	?></div>
	


	<!-- <form id="upload-container" method="POST" action="post.php">
		<img id="upload-image" src="upload.svg">
		<div>
			<input id="file-input" type="file" name="file" multiple>
			<label for="file-input">Выберите файл</label>
			<span>или перетащите его сюда</span>
		</div>
	</form> -->



	<!-- <form name="message" action="">
		<input name="usermsg" type="text" id="usermsg" size="63" />
		<input name="submitmsg" type="submit"  id="submitmsg" value="Send" />
	</form> -->


	<form name="message" action="" class="msg-form">
				<span>Сообщение:</span>
				<input name="usermsg" type="text" id="usermsg" size="63" placeholder="Напишите сообщение...">
				<input name="submitmsg" type="submit" id="submitmsg" value="Отправить">
			</form>

			<form method="POST" action="post.php" id="upload-container" class="upload-form">
				<div>
					<span>Вложения:</span>
					<input id="file-input" class="input-file" type="file" name="file" multiple>
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
<?php
}
?>
            </section>

        </main>
    </div>
</body>
</html>
<?php
session_start();
if(isset($_SESSION['login'])){
	$text = $_POST['text'];
	$fp = fopen("log.html", 'a');
	fwrite($fp, '
		<div class="chat-box-message">
			<!-- В этом диве имя пользователя, сообщение и файлы прикрепленные -->
			<div class="chat-box-message-wrapper"> 
				<b>' . $_SESSION['username'] . '</b><br>'
				. stripslashes(htmlspecialchars($text)) . '
			</div>
			<div class="chat-box-message-date">
				' . date('d.m.Y H:i', time()) . '
			</div>
		</div>
	');
fclose($fp);
}
?> 
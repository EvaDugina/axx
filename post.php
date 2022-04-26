<?php
session_start();
if(isset($_SESSION['login'])){
	$text = $_POST['text'];
	
	
$fp = fopen("log.html", 'a');
	fwrite($fp, "<div class='msgln'> 
	<div
 	class='msgln-message'><b>".$_SESSION['username']."</b>
	<br> ".stripslashes(htmlspecialchars($text))."</div>
	<div class='file-input'>
	</div>
	<div
	 	class='msgln-date'>".date('d.m.Y H:i')."
	</div>
	</div>");
fclose($fp);
}
?> 
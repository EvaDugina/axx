<?php
session_start();
if(isset($_SESSION['name'])){
	$text = $_POST['text'];
	
	
	$fp = fopen("log.html", 'a');
	fwrite($fp, "<div class='msgln'> <div class='msgln-message'><b>".$_SESSION['name']."</b> <br><br> ".stripslashes(htmlspecialchars($text))."</div><div class='file-input'><div class='msgln-date'>(".date("g:i A").")</div></div>");
	fclose($fp);
}
?>
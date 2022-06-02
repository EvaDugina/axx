<?php
require_once('settings.php'); 

ob_end_clean();
 
// Получаем GET-методом attachment_id из taskchat.php
// Достаем название и полный текст файла по attachment_id
$query = "SELECT file_name, full_text from ax_message_attachment where id = {$_GET['attachment_id']}";
$result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
$row = pg_fetch_assoc($result);

// Создаем файл в папке сервера 'upload_files'
$file_dir = 'upload_files/';
$file_path = $file_dir . $row['file_name'];
file_put_contents($file_path, $row['full_text']);

// Даем пользователю скачать файл и удаляем его с сервера
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename=' . basename($file_path));
header('Content-Transfer-Encoding: binary');
header('Content-Length: ' . filesize($file_path));
 
readfile($file_path);
unlink($file_path);
exit();
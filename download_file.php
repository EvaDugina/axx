<?php
require_once('settings.php'); 

ob_end_clean();

// Скачивание архива всех файлов к странице с заданием
if (isset($_GET['download_task_files'])) {
    $query = "SELECT file_name, download_url, full_text from ax_task_file where task_id = {$_GET['task_id']}";
    $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

    $zip = new ZipArchive();
    $file_dir = 'upload_files/';
    $file_path = $file_dir . time() . '.zip';
    if ($zip->open($file_path, ZipArchive::CREATE) !== TRUE) {
        exit("Невозможно открыть <$file_path>");
    }

    for ($row = pg_fetch_assoc($result); $row; $row = pg_fetch_assoc($result)) {
        // Если текст файла лежит в БД
		if ($row['download_url'] == null) {
			$zip->addFromString($row['file_name'], $row['full_text']);
		}
		// Если файл лежит на сервере
		else if (!preg_match('#^http[s]{0,1}://#', $row['download_url'])) {
			$zip->addFile($row['download_url'], $row['file_name']);
		}
    }
    $zip->close();
    if (!file_exists($file_path)) {
        exit("Архива не существует");
    }
    // Даем пользователю скачать архив и удаляем его с сервера
    header('Content-type: application/zip');
    header('Content-Disposition: attachment; filename=' . basename($file_path));

    readfile($file_path);
    unlink($file_path);
    exit();
}

else if (isset($_GET['attachment_id']) || isset($_GET['task_file_id'])) {

    // Скачивание файла из БД по attachment_id из ax_message_attachment
    if (isset($_GET['attachment_id'])) {
        // Достаем название и полный текст файла по attachment_id
        $query = "SELECT file_name, full_text from ax_message_attachment where id = {$_GET['attachment_id']}";
        $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
        $row = pg_fetch_assoc($result);
    }

    // Скачивание файла из БД по task_file_id из ax_task_file
    else if (isset($_GET['task_file_id'])) {
        // Достаем название и полный текст файла по task_file_id
        $query = "SELECT file_name, full_text from ax_task_file where id = {$_GET['task_file_id']}";
        $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
        $row = pg_fetch_assoc($result);
    }

    if (!$row) { exit("Файл не существует"); }

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
}

// Скачивание файла с сервера по file_path
else if (isset($_GET['file_path'])) {
    $file_path = $_GET['file_path'];
    if (!file_exists($file_path)) {
        exit("Файл не существует");
    }
    // Даем пользователю скачать файл
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=' . basename($file_path));
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: ' . filesize($file_path));
    
    readfile($file_path);
    exit();
}
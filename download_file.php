<?php
require_once('settings.php');
require_once('utilities.php');
require_once('POClasses/File.class.php');
require_once('POClasses/Task.class.php');

if (ob_get_level()) {
    ob_end_clean();
}

// Скачивание архива всех файлов к странице с заданием
if (isset($_GET['download_task_files'])) {
    $Task = new Task((int)$_GET['task_id']);

    $zip = new ZipArchive();
    $file_dir = getUploadFileDir();
    $file_path = $file_dir . time() . '.zip';
    if ($zip->open($file_path, ZipArchive::CREATE) !== TRUE) {
        exit("Невозможно открыть <$file_path>");
    }

    foreach ($Task->getStudentFilesToTaskchat() as $File) {
        // Если текст файла лежит в БД
        if ($File->download_url == null) {
            $zip->addFromString($File->name_without_prefix, $File->full_text);
        }
        // Если файл лежит на сервере
        else if (!preg_match('#^http[s]{0,1}://#', $File->download_url)) {
            $zip->addFile($File->download_url, $File->name_without_prefix);
        }
    }

    $zip->close();
    if (!file_exists($file_path)) {
        exit("Архива не существует");
    }
    // Даем пользователю скачать архив и удаляем его с сервера
    header('Content-Description: File Transfer');
    header('Content-type: application/zip');
    header('Content-Disposition: attachment; filename=' . basename($file_path));
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: ' . filesize($file_path));

    readfile($file_path);
    unlink($file_path);
    exit();
} else if (isset($_GET['file_id']) /*|| isset($_GET['task_file_id'])*/) {

    $File = null;

    // Скачивание файла из БД по attachment_id из ax.ax_message_attachment
    if (isset($_GET['file_id'])) {
        // Достаем название и полный текст файла по attachment_id
        $File = new File((int)$_GET['file_id']);
    }

    if ($File == null) {
        exit("Файл не существует");
    }

    if ($File->download_url == null) {
        // Создаем файл в папке сервера 'upload_files'
        $file_dir = 'upload_files/';
        $file_path = $file_dir . $File->name;
        file_put_contents($file_path, $File->full_text);
    }

    // Если файл лежит на сервере
    else if (!preg_match('#^http[s]{0,1}://#', $File->download_url)) {
        $file_path = $File->download_url;
    }


    // Даем пользователю скачать файл и удаляем его с сервера
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=' . $File->name_without_prefix);
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: ' . filesize($file_path));

    readfile($file_path);
    unlink($file_path);
    exit();
}

// Скачивание файла с сервера по file_path
else if (isset($_GET['file_path'])) {
    $file_path = $_GET['file_path'];
    $file_ext = strtolower(preg_replace('#.{0,}[.]#', '', basename($file_path)));
    if (!file_exists($file_path)) {
        exit("Файл не существует");
    }

    $mime_type = '';
    switch ($file_ext) {
        case "jpeg":
        case "jpg":
            $mime_type = "image/jpg";
            break;
        case "png":
            $mime_type = "image/png";
            break;
        case "gif":
            $mime_type = "image/gif";
            break;
        case "txt":
            $mime_type = "text/plain";
            break;
        case "doc":
            $mime_type = "application/msword";
            break;
        case "docx":
            $mime_type = "application/vnd.openxmlformats-officedocument.wordprocessingml.document";
            break;
        case "xls":
            $mime_type = "application/vnd.ms-excel";
            break;
        case "xlsx":
            $mime_type = "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";
            break;
        case "ppt":
            $mime_type = "application/vnd.ms-powerpoint";
            break;
        case "pptx":
            $mime_type = "application/vnd.openxmlformats-officedocument.presentationml.presentation";
            break;
        case "pdf":
            $mime_type = "application/pdf";
            break;
        case "zip":
            $mime_type = "application/zip";
            break;
        case "7z":
            $mime_type = "application/x-7z-compressed";
            break;
        case "rar":
            $mime_type = "application/vnd.rar";
            break;
        case "gzip":
            $mime_type = "application/gzip";
            break;
        case "exe":
            $mime_type = "application/octet-stream";
            break;
        default:
            $mime_type = "application/force-download";
    }

    // Даем пользователю скачать файл
    $file_name = basename($file_path);
    $file_name = deleteRandomPrefix($file_name);

    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mime_type);
    header('Content-Disposition: attachment; filename=' . $file_name);
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: ' . filesize($file_path));

    readfile($file_path);
    exit();
}

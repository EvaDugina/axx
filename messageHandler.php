<?php
require_once("settings.php");
require_once("dbqueries.php");
require_once("utilities.php");

require_once("POClasses/File.class.php");
require_once("POClasses/Commit.class.php");

// В этом файле реализована вся общая логика отправки сообщения, 
// отправки ответа на сообщения и тд.

// ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ДЛЯ ИМПОРТИРОВАНИЯ

class messageHandler {

  public $dbconnect;
  public $assignment_id; 
  public $user_id;
  public $sender_user_type;

  function __construct($assignment_id, $user_id) {
    global $dbconnect;
    $this->dbconnect = $dbconnect;
    $this->assignment_id = $assignment_id;
    $this->user_id = $user_id;
    $this->sender_user_type = $_SESSION['role'];
  }


  function set_message($type, $visibility, $full_text, $commit_id = null, $reply_id = null) {
  
    $full_text = preg_replace('#\'#', '\'\'', $full_text);
    $query = insert_message($this->assignment_id, $type, $visibility, $this->sender_user_type, $this->user_id, $full_text, $commit_id, $reply_id);
  
    $result = pg_query($this->dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    $row = pg_fetch_assoc($result);
    return $row['id'];
  }

  function set_message_only_for_teacher ($link){
    return $this->set_message(3, 2, $link);
  }
  
  
  function add_files_to_message($commit_id, $message_id, $files, $type){
    // Файлы с этими расширениями надо хранить в БД
    for ($i = 0; $i < count($files); $i++) {
      $this->work_with_file($commit_id, $files[$i]['name'], $files[$i]['tmp_name'], $message_id, $type);
    }
  }
  
  function work_with_file($commit_id, $file_name, $file_tmp_name, $message_id, $type) {
  
    //echo "WORKING WITH FILE <br>";
  
    //echo "ASSIGNMENT_ID: ".$this->assignment_id;
    //echo "<br>";
  
    $store_in_db = getSpecialFileTypes();

    // TODO: Проверить!
    $File = new File($type, $file_name);
    
    $file_ext = $File->getFileExt();
    $file_dir = getPathForUploadFiles();
    $file_path = $file_dir . $File->name;
  
    // Перемещаем файл пользователя из временной директории сервера в директорию $file_dir
    if (move_uploaded_file($file_tmp_name, $file_path)) {
      $Message = new Message((int)$message_id);

      // Если файлы такого расширения надо хранить на сервере, добавляем в БД путь к файлу на сервере
      if (!in_array($file_ext, $store_in_db)) {
        
        $File->setDownloadUrl($file_path);
        $Message->addFile($File->id);

      } else { // Если файлы такого расширения надо хранить в БД, добавляем в БД полный текст файла
        
        $file_full_text = getFileContentByPath($file_path);
        $File->setFullText($file_full_text);
        $Message->addFile($File->id);
        unlink($file_path);

      }

      // Добавление файла в ax_solution_file, если сообщение - ответ на задание
      if ($commit_id != null) {
        $Commit = new Commit((int)$commit_id);
        $Commit->addFile($File->id);
        $Message->setCommit($Commit->id);
      }

    } else {
      exit("Ошибка загрузки файла");
    }
  }


}


?>
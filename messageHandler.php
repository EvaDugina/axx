<?php
require_once("dbqueries.php");
require_once("utilities.php");

// В этом файле реализована вся общая логика отправки сообщения, 
// отправки ответа на сообщения и тд.

function getSpecialFileTypes(){
  return array('cpp', 'c', 'h', 'txt');
}

function getPathForUploadFiles(){
  return 'upload_files/';
}

// ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ДЛЯ ИМПОРТИРОВАНИЯ

class messageHandler {

  public $dbconnect;
  public $assignment_id; 
  public $user_id;
  public $sender_user_type;

  function __construct($assignment_id, $user_id) {
    require("settings.php");
    $this->dbconnect = $dbconnect;
    $this->assignment_id = $assignment_id;
    $this->user_id = $user_id;
    $this->sender_user_type = $this->getSenderUserType($user_id, $assignment_id);
  }

  function getSenderUserType($user_id, $assignment_id){
    $query = select_student_role($user_id);
    $result = pg_query($this->dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    $row = pg_fetch_assoc($result);
    if(count($row) > 1) {
      $query = isPrepByAssignmentId($assignment_id, $user_id);
      $result = pg_query($this->dbconnect, $query);
      if(count(pg_fetch_all($result)) > 0)
        $sender_user_type = 1;
      else 
        $sender_user_type = 0;
    } else {
      if ($row['role'] == 3)
        $sender_user_type = 0;
      else 
        $sender_user_type = 1;
    }
    return $sender_user_type;
  }


  function set_message($type, $full_text, $commit_id = null, $reply_id = null) {
  
    $full_text = preg_replace('#\'#', '\'\'', $full_text);
    $query = insert_message($this->assignment_id, $type, $this->sender_user_type, $this->user_id, $full_text, $commit_id, $reply_id);
  
    $result = pg_query($this->dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    $row = pg_fetch_assoc($result);
    return $row['id'];
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
    
    $file_name = add_random_prefix_to_file_name($file_name);
    $file_ext = strtolower(preg_replace('#.{0,}[.]#', '', $file_name));
    $file_dir = getPathForUploadFiles();
    $file_path = $file_dir . $file_name;
  
    /*echo "Добавление файла в ax_solution_file: ".$file_name;
    echo "<br>";*/
  
    // Перемещаем файл пользователя из временной директории сервера в директорию $file_dir
    if (move_uploaded_file($file_tmp_name, $file_path)) {
      // Если файлы такого расширения надо хранить на сервере, добавляем в БД путь к файлу на сервере
      if (!in_array($file_ext, $store_in_db)) {
        //echo "Добавление download_url<br>";
        $query = insert_ax_message_attachment_with_url($message_id, $file_name, $file_path);
        pg_query($this->dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
        if ($type == 1) {
          // Добавление файлаа в ax_solution_file, если сообщение - ответ на задание
          $query = insert_ax_solution_file($this->assignment_id, $commit_id, $file_name, $file_path, 0);
          pg_query($this->dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
        }
      } else { // Если файлы такого расширения надо хранить в БД, добавляем в БД полный текст файла
        // echo "Добавление file_text<br>";
        $file_name_without_prefix = delete_random_prefix_from_file_name($file_name);
        $file_full_text = file_get_contents($file_path);
        $file_full_text = preg_replace('#\'#', '\'\'', $file_full_text);
        // echo $file_full_text;
        $query = insert_ax_message_attachment_with_full_file_text($message_id, $file_name_without_prefix, $file_full_text);
        pg_query($this->dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
        unlink($file_path);
        if ($type == 1) {
          // Добавление файла в ax_solution_file, если сообщение - ответ на задание
          // echo "ДОБАВЛЕНИЕ ФАЙЛА В ax_solution_file";
          $query = insert_ax_solution_file($this->assignment_id, $commit_id, $file_name, $file_full_text, 1);
          pg_query($this->dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
        }
      }
    } else {
      exit("Ошибка загрузки файла");
    }
  }


}


?>
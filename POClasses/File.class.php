<?php 
require_once("./settings.php");

class File {

  public $id = null;
  public $type = null;  // тип файла (0 - просто файл, 1 - шаблон проекта, 2 - код теста, 3 - код проверки теста, 
                        // 10 - просто файл с результатами, 11 - файл проекта)
  public $name = null, $download_url = null, $full_text = null;

  public $name_without_prefix = null;

          
  public function __construct() {
    global $dbconnect;

    $count_args = func_num_args();
    $args = func_get_args();

    // Перегружаем конструктор по количеству подданых параметров

    if ($count_args == 1 && is_int($args[0])) {
      $this->id = $args[0];
  
      $query = queryGetFileInfo($this->id);
      $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
      $file = pg_fetch_assoc($result);

      if ($file) {
        $this->name = $file['file_name'];
        $this->name_without_prefix = deleteRandomPrefix($this->name);
        $this->download_url = $file['download_url'];
        $this->full_text = $file['full_text'];
        $this->type = $file['type'];
      }
      
    } 
    
    // TODO: Доделать
    else if ($count_args == 2) {
      $this->type = $args[0];
      $this->name_without_prefix = $args[1];
      $this->name = addRandomPrefix($this->name_without_prefix);

      $this->pushNewToDB();
    }
    
    else if ($count_args == 4) {
      $this->type = $args[0];
      $this->name_without_prefix = $args[1];
      $this->name = addRandomPrefix($this->name_without_prefix);

      $this->download_url = $args[2];      
      $this->full_text = $args[3];

      $this->pushNewToDB();

    } 
    
    else {
      die('Неверные аргументы в конструкторе File');
    }


  }


// GETTERS

public function getFileExt() {
  return strtolower(preg_replace('#.{0,}[.]#', '', $this->name_without_prefix));
}

// -- END GETTERS

// SETTERS

  public function setDownloadUrl($download_url) {
    global $dbconnect;

    $this->download_url = $download_url;
    $query = "UPDATE ax_file SET download_url = '$this->download_url'
                WHERE id = $this->id;
    ";

    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  public function setFullText($full_text) {
    global $dbconnect;

    $this->full_text = $full_text;
    $query = "UPDATE ax_file SET full_text = '$this->full_text'
                WHERE id = $this->id;
    ";
    
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  public function setType($type) {
    global $dbconnect;

    $this->type = $type;

    $query = "UPDATE ax_file SET type = $this->type
              WHERE id = $this->id;
    ";

    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

// -- END SETTERS




// WORK WITH FILE

  public function pushNewToDB() {
    global $dbconnect;

    if ($this->full_text == null && $this->download_url != null) {
      $query = "INSERT INTO ax_file (type, file_name, download_url) 
                VALUES ($this->type, '$this->name', '$this->download_url') 
                RETURNING id;
      ";
    } else if ($this->full_text != null && $this->download_url == null) {
      $query = "INSERT INTO ax_file (type, file_name, full_text) 
                VALUES ($this->type, '$this->name', \$antihype1\$$this->full_text\$antihype1\$) 
                RETURNING id;
      ";
    } else {
      $query = "INSERT INTO ax_file (type, file_name) 
                VALUES ($this->type, '$this->name') 
                RETURNING id;
      ";
    }

    $pg_query = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    $result = pg_fetch_assoc($pg_query);

    $this->id = $result['id'];
  }
  public function pushChangesToDB() {
    global $dbconnect;

    if (isset($this->download_url)) {
      $query = "UPDATE ax_file SET type = $this->type, file_name = '$this->name', 
                download_url = '$this->download_url'
                WHERE id = $this->id;
      ";
    } else if (isset($this->full_text)) {
      $query = "UPDATE ax_file SET type = $this->type, file_name = '$this->name',  
                full_text = \$antihype1\$$this->full_text\$antihype1\$
                WHERE id = $this->id;
      ";
    } else {
      exit("Incorrect File->pushChangesToDB()");
    }

    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  public function deleteFromDB() {
    global $dbconnect;
  
    $query = "DELETE FROM ax_file WHERE id = $this->id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  
  }

// -- END WORK WITH FILE


}


// Добавление рандомного префикса к названию файла, чтобы избежать оибки добавления файлов с одинаковым названием
function addRandomPrefix($file_name) {
  return randPrefix() .  $file_name;
}

// Декодирование префиксного названия файла
function deleteRandomPrefix($db_file_name) {
  return preg_replace('#[0-9]{0,}_#', '', $db_file_name, 1);
}

// Генерация префикса для уникальности названий файлов, которые хранятся на сервере
function randPrefix() {
  return time() . mt_rand(0, 9999) . mt_rand(0, 9999) . '_';
}

function getPathForUploadFiles(){
  return 'upload_files/';
}

function getSpecialFileTypes(){
  return array('cpp', 'c', 'h', 'txt');
}

function getImageFileTypes() {
  return array('img', 'png', 'jpeg', 'jpg', 'gif');
}

function getFileContentByPath($file_path) {
  $file_full_text = file_get_contents($file_path);
  $file_full_text = preg_replace('#\'#', '\'\'', $file_full_text);
  return $file_full_text;
}




function addFilesToMessage($commit_id, $message_id, $files, $type){
  // Файлы с этими расширениями надо хранить в БД
  for ($i = 0; $i < count($files); $i++) {
    addFileToMessage($commit_id, $files[$i]['name'], $files[$i]['tmp_name'], $message_id, $type);
  }
}
function addFileToMessage($commit_id, $file_name, $file_tmp_name, $message_id, $type) {

  //echo "WORKING WITH FILE <br>";

  //echo "ASSIGNMENT_ID: ".$this->assignment_id;
  //echo "<br>";

  $store_in_db = getSpecialFileTypes();
  
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




// ФУНКЦИИ ЗАПРОСОВ К БД

function queryGetFileInfo($file_id) {
  return "SELECT * FROM ax_file WHERE id = $file_id;
  ";
}

?>
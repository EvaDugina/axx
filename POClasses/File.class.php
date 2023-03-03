<?php 
require_once("./settings.php");

class File {

  private $id = null;
  private $type = null;  // тип файла (0 - просто файл, 1 - шаблон проекта, 2 - код теста, 3 - код проверки теста, 
  // 10 - просто файл с результатами, 11 - файл проекта)
  private $name = null, $download_url = null, $full_text = null;

          
  function __construct() {
    global $dbconnect;

    $count_args = func_num_args();
    $args = func_get_args();

    // Перегружаем конструктор по количеству подданых параметров

    if ($count_args == 1 && is_int($args[0])) {
      $this->id = $args[0];
  
      $query = queryGetFileInfo($this->id);
      $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
      $file = pg_fetch_assoc($result);
  
      $this->name = $file['name'];
      $this->download_url = $file['download_url'];
      $this->full_text = $file['full_text'];
      $this->type = $file['type'];
      
    } 
    
    else if ($count_args == 4) {
      $this->type = $args[0];
      $this->name = $args[1];
      $this->download_url = $args[2];
      $this->full_text = $args[3];

      $this->pushNewToDB();

    } 
    
    else {
      die('Неверное число аргументов, или неверный id файла');
    }


  }
  


// GETTERS:

  public function getId() {
    return $this->id;
  }

  public function getType() {
    return $this->type;
  }

  public function getName() {
    return $this->name;
  }

  public function getDownloadUrl() {
    return $this->download_url;
  }

  public function getFullText() {
    return $this->full_text;
  }


  // public function getAll() {
  //   return array(
  //     'id' => $this->id, 
  //     'name' => $this->name,
  //     'download_url' => $this->download_url, 
  //     'full_text' => $this->full_text, 
  //     'type' => $this->type
  //   );
  // }

// -- END GETTERS


// SETTERS:

  public function setType($type) {
    $this->type = $type;
  } 

  public function setName($name) {
    $this->name = $name;
  } 

  public function setDownloadUrl($download_url) {
    $this->download_url = $download_url;
  } 

  public function setFullText($full_text) {
    $this->full_text = $full_text;
  } 

  public function setAll($name, $download_url, $full_text, $type) {
    $this->name = $name;
    $this->download_url = $download_url;
    $this->full_text = $full_text;
    $this->type = $type;
  }

// -- END SETTERS


  public function pushChangesToDB() {
    global $dbconnect;

    // $query = "UPDATE ax_file SET name = '$this->name', download_url = '$this->download_url', full_text = '$this->full_text', type = $this->type WHERE id = $this->id;";
    // $pg_query = pg_query($dbconnect, $query);

    // if (!$pg_query) {
    //   $query = "INSERT INTO ax_file (id, type, name, download_url, full_text) 
    //   VALUES ($this->id, $this->type, '$this->name', '$this->download_url', '$this->full_text');
    //   ";
    // }

    $query = "UPDATE ax_file SET type = $this->type, name = '$this->name', download_url = '$this->download_url', full_text = '$this->full_text' 
              WHERE id = $this->id;
    ";

    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  public function pushNewToDB() {
    global $dbconnect;

    $query = "INSERT INTO ax_file (type, name, download_url, full_text) 
              VALUES ($this->type, '$this->name', '$this->download_url', '$this->full_text') 
              RETURNING id;
    ";

    $pg_query = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    $result = pg_fetch_assoc($pg_query);

    $this->id = $result['id'];
  }

  public function deleteFromDB() {
    global $dbconnect;
  
    $query = "DELETE FROM ax_file WHERE file_id = $this->id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  
  }


}


// Добавление рандомного префикса к названию файла, чтобы избежать оибки добавления файлов с одинаковым названием
function addRandomPrefix($file_name) {
  return rand_prefix() . '_' .  $file_name;
}

// Декодирование префиксного названия файла
function deleteRandomPrefix($db_file_name) {
  return preg_replace('#[0-9]{0,}_#', '', $db_file_name, 1);

  // Второй вариант:
  // $split_array = preg_split('/_/', $db_file_name);
  // $decoded_file_name = "";
  // for ($i = 1; $i < count($split_array); $i++) {
  //   if ($i != 1) $decoded_file_name .= "_";
  //   $decoded_file_name .= $split_array[$i];
  // }
  // return $decoded_file_name;
}

// Генерация префикса для уникальности названий файлов, которые хранятся на сервере
function rand_prefix() {
  return time() . mt_rand(0, 9999) . mt_rand(0, 9999);
}



// ФУНКЦИИ ЗАПРОСОВ К БД

function queryGetFileInfo($file_id) {
  return "SELECT * FROM ax_file WHERE id = $file_id;
  ";
}

?>
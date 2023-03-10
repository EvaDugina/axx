<?php 
require_once("./settings.php");

class File {

  public $id = null;
  public $type = null;  // тип файла (0 - просто файл, 1 - шаблон проекта, 2 - код теста, 3 - код проверки теста, 
                        // 10 - просто файл с результатами, 11 - файл проекта)
  public $name = null, $download_url = null, $full_text = null;
  public $name_with_prefix = null;

          
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
        $this->name = deleteRandomPrefix($file['file_name']);
        $this->name_with_prefix = $file['file_name'];
        $this->download_url = $file['download_url'];
        $this->full_text = $file['full_text'];
        $this->type = $file['type'];
      }
      
    } 
    
    // TODO: Доделать
    else if ($count_args == 2) {
      $this->type = $args[0];
      $this->name = $args[1];
      $this->name_with_prefix = addRandomPrefix($this->name);

      $this->pushNewToDB();
    }
    
    else if ($count_args == 4) {
      $this->type = $args[0];
      $this->name = $args[1];
      $this->name_with_prefix = addRandomPrefix($this->name);

      $this->download_url = $args[2];      
      $this->full_text = $args[3];

      $this->pushNewToDB();

    } 
    
    else {
      return null;
    }


  }
  

// WORK WITH FILE

  public function pushNewToDB() {
    global $dbconnect;

    if ($this->full_text == null && $this->download_url != null) {
      $query = "INSERT INTO ax_file (type, file_name, download_url) 
                VALUES ($this->type, '$this->name_with_prefix', '$this->download_url') 
                RETURNING id;
      ";
    } else if ($this->full_text != null && $this->download_url == null) {
      $query = "INSERT INTO ax_file (type, file_name, full_text) 
                VALUES ($this->type, '$this->name_with_prefix', \$antihype1\$$this->full_text\$antihype1\$) 
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

    if ($this->full_text == null && $this->download_url != null) {
      $query = "UPDATE ax_file SET type = $this->type, file_name = '$this->name_with_prefix', 
                download_url = '$this->download_url'
                WHERE id = $this->id;
      ";
    } else if ($this->full_text != null && $this->download_url == null) {
      $query = "UPDATE ax_file SET type = $this->type, file_name = '$this->name_with_prefix',  
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
  
    $query = "DELETE FROM ax_file WHERE file_id = $this->id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  
  }

// -- END WORK WITH FILE


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
function randPrefix() {
  return time() . mt_rand(0, 9999) . mt_rand(0, 9999);
}



// ФУНКЦИИ ЗАПРОСОВ К БД

function queryGetFileInfo($file_id) {
  return "SELECT * FROM ax_file WHERE id = $file_id;
  ";
}

?>
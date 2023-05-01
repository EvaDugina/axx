<?php 
require_once("./settings.php");
require_once("./utilities.php");
require_once("File.class.php");
require_once("Commit.class.php");


class Message {

  public $id = null;
  public $type = null, $sender_user_id = null, $sender_user_type = null;
  public $date_time = null, $reply_to_id = null, $full_text = null;
  public $status = null, $visibility = null;

  private $Commit = null;
  private $Files = array();


  function __construct() {
    global $dbconnect;

    
    $count_args = func_num_args();
    $args = func_get_args();
    
    // Перегружаем конструктор по количеству подданых параметров
    
    if ($count_args == 1 && is_int($args[0])) { 
      $this->id = $args[0];
  
      $query = queryGetMessageInfo($this->id);
      $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
      $message = pg_fetch_assoc($result);

      $this->type = $message['type'];
      $this->sender_user_id = $message['sender_user_id'];
      $this->sender_user_type = $message['sender_user_type'];

      $this->date_time = $message['date_time'];
      $this->reply_to_id = $message['reply_to_id'];

      // FIXME: Исправить на просто text
      $this->full_text = $message['full_text'];

      $this->status = $message['status'];
      $this->visibility = $message['visibility'];

      $this->Commit = new Commit((int)$message['commit_id']);
      $this->Files = getFilesByMessage($this->id);

    }

    else if ($count_args == 8) { // всё, кроме commit_id + assignment_id
      $assignment_id = $args[0];

      $this->type = $args[1];
      $this->sender_user_id = $args[2];
      $this->sender_user_type = $args[3];

      $this->reply_to_id = $args[4];
      $this->full_text = $args[5];

      $this->status = $args[6];
      $this->visibility = $args[7];

      $this->pushNewToDB($assignment_id);
    }

    else {
      die('Неверные аргументы в конструкторе Message');
    }

  }


// GETTERS:

  public function getCommit() {
    return $this->Commit;
  }
  public function getFiles() {
    return $this->Files;
  }

  public function getConvertedDateTime () {
    $message_time = explode(" ", $this->date_time);
    $date = explode("-", $message_time[0]);
    $time = explode(":", $message_time[1]);
    $date_time = $date[2] . "." . $date[1] . "." . $date[0] . " " . $time[0] . ":" . $time[1];
    return $date_time;
  }

  
// -- END GETTERS
  


// SETTERS:

    public function setStatus($status) {
      global $dbconnect;

      $this->status = $status;

      $query = "UPDATE ax_message SET status = $this->status WHERE ax_message.id = $this->id";
      pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    }

// -- END SETTERS



// WORK WITH MESSAGE

  public function pushNewToDB($assignment_id) {
    global $dbconnect;

    // $this->date_time = getNowTimestamp();

    if ($this->reply_to_id != null) {
      $query = "INSERT INTO ax_message (assignment_id, type, sender_user_id, sender_user_type, 
                date_time, reply_to_id, full_text, status, visibility) 
                VALUES ($assignment_id, $this->type, $this->sender_user_id, $this->sender_user_type, 
                now(), $this->reply_to_id, \$antihype1\$$this->full_text\$antihype1\$, $this->status, $this->visibility)
                RETURNING id, date_time;";
    } else {
      $query = "INSERT INTO ax_message (assignment_id, type, sender_user_id, sender_user_type, 
                date_time, full_text, status, visibility) 
                VALUES ($assignment_id, $this->type, $this->sender_user_id, $this->sender_user_type, 
                now(), \$antihype1\$$this->full_text\$antihype1\$, $this->status, $this->visibility)
                RETURNING id, date_time;";
    }

    $pg_query = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    $result = pg_fetch_assoc($pg_query);
    $this->id = $result['id'];
    $this->date_time = $result['date_time'];

    // $this->pushSelfToDeliveryDB();

  }
  public function pushChangesToDB() {
    global $dbconnect;

    $query = "UPDATE ax_message SET type = $this->type, sender_user_id = $this->sender_user_id, 
      sender_user_type = $this->sender_user_type, date_time = '$this->date_time', 
      reply_to_id = $this->reply_to_id, full_text = \$antihype1\$$this->full_text\$antihype1\$, status = $this->status, 
      visibility = $this->visibility WHERE id = $this->id;
    ";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  public function deleteFromDB() {
    global $dbconnect;

    $this->deleteFilesFromMessageDB();

    // $commit_file_ids = array();
    // if ($this->Commit != null)
    //   $commit_file_ids = $this->Commit->getFileIds();

    // // Удаляем файл из БД только в том случае, если он не входит в состав коммита
    // foreach($this->Files as $File) {
    //   if (!in_array($File->id, $commit_file_ids))
    //     $File->deleteFromDB();
    // }
    foreach($this->Files as $File) {
      // Удаляем файл совсем, если он - не файл коммита
      if (!$File->type == 0)
        $File->deleteFromDB();
    }
    
    $query = "DELETE FROM ax_message WHERE id = $this->id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

// -- END WORK WITH MESSAGE


// WORK WITH DELIVERY

  public function isReadedByTeacher() {
    if (!isTeacher($this->sender_user_type) && $this->status == 1)
      return true;
    else if (isTeacher($this->sender_user_type))
      return true; 
    return false;
  }
  public function isReadedByStudent() {
    if (!isStudent($this->sender_user_type) && $this->status == 1)
      return true;
    else if (isStudent($this->sender_user_type))
      return true; 
    return false;
  }

  public function getDeliveryStatus($user_id) {
    global $dbconnect;

    $query = "SELECT status FROM ax_message_delivery WHERE recipient_user_id = $user_id AND message_id = $this->id";
    $pg_query = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    $status = pg_fetch_assoc($pg_query)['status'];

    return $status;
  }
  public function isReadedAtLeastByOne() {
    global $dbconnect; 

    $query = "SELECT COUNT(status) as count FROM ax_message_delivery WHERE message_id = $this->id";
    $pg_query = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    $count = pg_fetch_assoc($pg_query)['count'];

    if ($count > 1)
      return true;

    return false;
  }
  // public function isFirstUnreaded($user_id) {
  //   global $dbconnect; 

  //   $query = "SELECT min(message_id) as min_message_id FROM ax_message_delivery WHERE recipient_user_id = $user_id 
  //             AND status = 0 AND assignment_id = ... LIMIT 1;";
  //   $pg_query = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  //   $min_new_message_id = pg_fetch_assoc($pg_query)['min_message_id'];

  //   if ($min_new_message_id == $this->id)
  //     return true;

  //   return false;
  // }
  public function setReadedDeliveryStatus($user_id) {
    global $dbconnect;

    $query = "UPDATE ax_message_delivery SET status = 1 WHERE recipient_user_id = $user_id AND message_id = $this->id";
    $pg_query = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    $status = pg_fetch_assoc($pg_query)['status'];

    return $status;
  }

  // public function pushSelfToDeliveryDB() {
  //   global $dbconnect;

  //   $query = "INSERT INTO ax_message_delivery (message_id, recipient_user_id, status)
  //             VALUES ($this->id, $this->sender_user_id, 1)";

  //   pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  // }

// -- END WORK WITH DELIVERY


// WORK WITH FILE

  public function addFile($file_id) {
    $File = new File((int)$file_id);
    $this->pushFileToMessageDB($file_id);
    array_push($this->Files, $File);
  }
  public function addFiles($Files) {
    $this->pushFilesToMessageDB($Files);
    foreach ($Files as $File) {
      array_push($this->Files, $File);
    }
  }
  public function deleteFile($file_id) {
    $index = $this->findFileById($file_id);
    if ($index != -1) {
      $this->deleteFileFromMessageDB($file_id);
      $this->Files[$index]->deleteFromDB();
      unset($this->Files[$index]);
    }
  }
  private function findFileById($file_id) {
    $index = 0;
    foreach($this->Files as $File) {
      if ($File->id == $file_id)
        return $index;
      $index++;
    }
    return -1;
  }
  public function getFileById($file_id) {
    foreach($this->Files as $File) {
      if ($File->id == $file_id)
        return $File;
    }
    return null;
  }

  private function pushFileToMessageDB($file_id) {
    global $dbconnect;

    $query = "INSERT INTO ax_message_file (message_id, file_id) VALUES ($this->id, $file_id);";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  private function pushFilesToMessageDB($Files) {
    global $dbconnect;

    $query = "";
    if (!empty($Files)) {
      foreach($Files as $File) {
        $query .= "INSERT INTO ax_message_file (message_id, file_id) VALUES ($this->id, $File->id);";
      }
    }
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  private function deleteFileFromMessageDB($file_id) {
    global $dbconnect;

    $query = "DELETE FROM ax_message_file WHERE file_id = $file_id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  private function synchFilesToMessageDB() {
    global $dbconnect;

    $this->deleteFilesFromMessageDB();

    $query = "";
    if (!empty($this->Files)) {
      foreach($this->Files as $File) {
        $query .= "INSERT INTO ax_message_file (message_id, file_id) VALUES ($this->id, $File->id);";
      }
    }
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  private function deleteFilesFromMessageDB() {
    global $dbconnect;
  
    // Удаляем предыдущие прикрепления файлов
    $query = "DELETE FROM ax_message_file WHERE message_id = $this->id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

// -- END WORK WITH FILE



// WORK WITH COMMIT

  public function setCommit($commit_id) {
    $this->Commit = new Commit((int)$commit_id);
    $this->pushCommitToDB();
  }
  private function pushCommitToDB() {
    global $dbconnect;

    if ($this->Commit != null) {
      $commit_id = $this->Commit->id;
      $query = "UPDATE ax_message SET commit_id = $commit_id WHERE id = $this->id;";
      pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    } 
  }

// -- END WORK WITH COMMIT


  
}






function getCommitByMessage($message_id) {
  global $dbconnect;

  $query = queryGetCommitByMessage($message_id);
  $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  $commit_row = pg_fetch_assoc($result);

  if($commit_row)
    return new Commit((int)$commit_row['id']);
  else 
    return null;
}

function getFilesByMessage($message_id) {
  global $dbconnect;

  $files = array();

  $query = queryGetFilesByMessage($message_id);
  $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

  while($file_row = pg_fetch_assoc($result)){
    array_push($files, new File((int)$file_row['id']));
  }

  return $files;
}



// ФУНКЦИИ ЗАПРОСОВ К БД 

function queryGetMessageInfo($message_id) {
  return "SELECT * FROM ax_message WHERE id = $message_id;
  ";
}

function queryGetCommitByMessage($message_id) {
  return "SELECT commit_id as id FROM ax_message WHERE id = $message_id";
}

function queryGetFilesByMessage($message_id) {
  return "SELECT file_id as id FROM ax_message_file WHERE message_id = $message_id";
}





?>
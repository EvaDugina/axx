<?php 
require_once("./settings.php");
require_once("File.class.php");
require_once("Commit.class.php");


class Message {

  private $id = null;
  private $type = null, $sender_user_id = null, $sender_user_type = null;
  private $date_time = null, $reply_to_id = null, $full_text = null;
  private $status = null, $visibility = null;

  private $Commit = null;
  private $Files = array();


  function __construct($message_id) {
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

      $this->Commit = new Commit($message['commit_id']);
      $this->Files = getFilesByMessage($this->id);

    }

    else if ($count_args == 9) { // всё, кроме commit_id + assignment_id
      $assignment_id = $args[0];

      $this->type = $args[1];
      $this->sender_user_id = $args[2];
      $this->sender_user_type = $args[3];

      $this->date_time = $args[4];
      $this->reply_to_id = $args[5];
      $this->full_text = $args[6];

      $this->status = $args[7];
      $this->visibility = $args[8];

    }

    else if ($count_args == 10) {
      $assignment_id = $args[0];

      $this->type = $args[1];
      $this->sender_user_id = $args[2];
      $this->sender_user_type = $args[3];

      $this->date_time = $args[4];
      $this->reply_to_id = $args[5];
      $this->full_text = $args[6];

      $this->status = $args[7];
      $this->visibility = $args[8];

      $commit_id = $args[9];

      $this->Commit = new Commit($commit_id);
      $this->Files = getFilesByMessage($this->id);
    }
    


  }


// GETTERS:

  public function getId() {
    return $this->id;
  }

  public function getType() {
    return $this->type;
  }

  public function getSenderUserId() {
    return $this->sender_user_id;
  }

  public function getSenderUserType() {
    return $this->sender_user_type;
  }

  public function getDateTime() {
    return $this->date_time;
  }

  public function getReplyToId() {
    return $this->reply_to_id;
  }

  public function getFullText() {
    return $this->full_text;
  }

  public function getStatus() {
    return $this->status;
  }

  public function getVisibility() {
    return $this->visibility;
  }

  public function getCommit() {
    return $this->Commit;
  }

  public function getFiles() {
    return $this->Files;
  }

// -- END GETTERS


// SETTERS: 

  public function setType($type) {
    global $dbconnect;

    $query = "UPDATE ax_message SET type = $type WHERE id = $this->id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    
    $this->type = $type;
  }

  public function setSenderUserId($sender_user_id) {
    global $dbconnect;
    
    $query = "UPDATE ax_message SET sender_user_id = $sender_user_id WHERE id = $this->id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

    $this->sender_user_id = $sender_user_id;
  }

  public function setSenderUserType($sender_user_type) {
    global $dbconnect;
    
    $query = "UPDATE ax_message SET sender_user_type = $sender_user_type WHERE id = $this->id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

    $this->sender_user_type = $sender_user_type;
  }

  public function setDateTime($date_time) {
    global $dbconnect;

    $query = "UPDATE ax_message SET date_time = '$date_time' WHERE id = $this->id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

    $this->date_time = $date_time;
  }

  public function setReplyToId($reply_to_id) {
    global $dbconnect;
    
    $query = "UPDATE ax_message SET reply_to_id = $reply_to_id WHERE id = $this->id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

    $this->reply_to_id = $reply_to_id;
  }

  public function setFullText($full_text) {
    global $dbconnect;

    $query = "UPDATE ax_message SET full_text = '$full_text' WHERE id = $this->id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

    $this->full_text = $full_text;
  }

  public function setStatus($status) {
    global $dbconnect;
    
    $query = "UPDATE ax_message SET status = $status WHERE id = $this->id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

    $this->status = $status;
  }

  public function setVisibility($visibility) {
    global $dbconnect;
    
    $query = "UPDATE ax_message SET visibility = $visibility WHERE id = $this->id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

    $this->visibility = $visibility;
  }

  public function setAll($type, $sender_user_id, $sender_user_type, $date_time, $reply_to_id, $full_text, $status, $visibility){
    global $dbconnect;

    //TODO: ПРОВЕРИТЬ, КАК РАБОТАЕТ, КОГДА НЕТ reply_to_id
    $query = "UPDATE ax_message SET type = $type, sender_user_id = $sender_user_id, sender_user_type = $sender_user_type,
    date_time = $date_time, reply_to_id = $reply_to_id, full_text = $full_text, status = $status, visibility = $visibility 
    WHERE id = $this->id;";

    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

    $this->type = $type;
    $this->sender_user_id = $sender_user_id;
    $this->sender_user_type = $sender_user_type;
    
    $this->date_time = $date_time;
    $this->reply_to_id = $reply_to_id;
    $this->full_text = $full_text;
    
    $this->status = $status;
    $this->visibility = $visibility;
  }

  public function setCommit($Commit) {

  }

  public function setFiles($Files) {

  }

// -- END SETTERS

  public function pushChangesToDB() {
    global $dbconnect;

    $query = "UPDATE ax_message SET type = $this->type, sender_user_id = $this->sender_user_id, 
      sender_user_type = $this->sender_user_type, date_time = '$this->date_time', 
      reply_to_id = $this->reply_to_id, full_text = '$this->full_text', status = $this->status, 
      visibility = $this->visibility WHERE id = $this->id;
    ";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  public function pushNewToDB($assignment_id) {
    global $dbconnect;

    $query = "INSERT INTO ax_message (assignment_id, type, sender_user_id, sender_user_type, 
    date_time, reply_to_id, full_text, status, visibility) 
    VALUES ($assignment_id, $this->type, $this->sender_user_id, $this->sender_user_type, 
    '$this->date_time', $this->reply_to_id, '$this->full_text', $this->status, $this->visibility)
    RETURNING id;";

    $pg_query = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    $result = pg_fetch_assoc($pg_query);
    return $result['id'];

  }
  
}


function getCommitByMessage($message_id) {
  global $dbconnect;

  $query = queryGetFilesByMessage($message_id);
  $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  $commit_row = pg_fetch_assoc($result);

  if($commit_row)
    return new Commit($commit_row['id']);
  else 
    return null;
}

function getFilesByMessage($message_id) {
  global $dbconnect;

  $files = array();

  $query = queryGetFilesByMessage($message_id);
  $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

  while($file_row = pg_fetch_assoc($result)){
    array_push($files, new File($file_row['id']));
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
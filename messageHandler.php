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
  
  
  


}


?>
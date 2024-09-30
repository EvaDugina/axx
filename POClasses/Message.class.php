<?php
require_once("./settings.php");
require_once("./utilities.php");
require_once("File.class.php");
require_once("Commit.class.php");


class Message
{

  public $id = null;
  public $type = null; // 0 - обычное сообщение (в т. ч. с приложениями), 1 - коммит, 2 - оценка, 3 - ссылка
  public $sender_user_id = null, $sender_user_type = null;
  public $date_time = null, $reply_to_id = null, $full_text = null;
  public $status = null; // 0 - активное, 2 - удаленное
  public $visibility = null; // 0 - видимо всем, 1 - видимо только админу, 2 - видимо только преподавателю, 3 - видимо только студенту
  public $resended_from_id = null;

  private $Commit = null;
  private $Files = array();


  function __construct()
  {
    global $dbconnect;


    $count_args = func_num_args();
    $args = func_get_args();

    // Перегружаем конструктор по количеству подданых параметров

    if ($count_args == 1) {
      $this->id = (int)$args[0];

      $query = queryGetMessageInfo($this->id);
      $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
      $message = pg_fetch_assoc($result);

      $this->type = $message['type'];
      $this->sender_user_id = $message['sender_user_id'];
      $this->sender_user_type = $message['sender_user_type'];

      $this->date_time = convertServerDateTimeToCurrent($message['date_time']);
      $this->reply_to_id = $message['reply_to_id'];
      $this->resended_from_id = $message['resended_from_id'];

      // TODO: Исправить на просто text
      $this->full_text = $message['full_text'];

      $this->status = $message['status'];
      $this->visibility = $message['visibility'];

      $this->Commit = new Commit((int)$message['commit_id']);
      $this->Files = getFilesByMessage($this->id);
    } else if ($count_args == 4) {
      $assignment_id = $args[0];

      $this->type = $args[1];
      $this->sender_user_id = $args[2];
      $this->sender_user_type = $args[3];

      $this->full_text = "";

      $this->visibility = 0;
      $this->status = 0;

      $this->pushNewToDB($assignment_id);
    } else if ($count_args == 5) {
      $assignment_id = $args[0];

      $this->type = $args[1];
      $this->sender_user_id = $args[2];
      $this->sender_user_type = $args[3];

      $this->full_text = $args[4];

      $this->visibility = 0;
      $this->status = 0;

      $this->pushNewToDB($assignment_id);
    } else if ($count_args == 6) {
      $assignment_id = $args[0];

      $this->type = $args[1];
      $this->sender_user_id = $args[2];
      $this->sender_user_type = $args[3];

      $this->reply_to_id = $args[4];
      $this->full_text = $args[5];

      $this->visibility = 0;
      $this->status = 0;

      $this->pushNewToDB($assignment_id);
    } else if ($count_args == 7) { // всё, кроме commit_id + assignment_id
      $assignment_id = $args[0];

      // TODO: убрать отсюда status, тк новое сообщение по умоляанию со статусом = 0

      $this->type = $args[1];
      $this->sender_user_id = $args[2];
      $this->sender_user_type = $args[3];

      $this->reply_to_id = $args[4];
      $this->full_text = $args[5];

      $this->visibility = $args[6];
      $this->status = 0;

      $this->pushNewToDB($assignment_id);
    } else {
      die('Неверные аргументы в конструкторе Message');
    }
  }


  // GETTERS:

  public function getCommit()
  {
    return $this->Commit;
  }
  public function getFiles()
  {
    return $this->Files;
  }

  public function getConvertedDateTime()
  {
    return getConvertedDateTime($this->date_time);
  }

  public function getMainResendedMessage()
  {
    return getMainResendedMessage($this);
  }


  // -- END GETTERS



  // SETTERS:

  public function setStatus($status)
  {
    global $dbconnect;

    $this->status = $status;

    $query = "UPDATE ax.ax_message SET status = $this->status WHERE ax.ax_message.id = $this->id";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  public function setResendedFromId($message_id)
  {
    global $dbconnect;

    $this->resended_from_id = $message_id;

    $query = "UPDATE ax.ax_message SET resended_from_id = $this->resended_from_id WHERE ax.ax_message.id = $this->id";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  public function setFullText($full_text)
  {
    global $dbconnect;

    $this->full_text = $full_text;

    $query = "UPDATE ax.ax_message SET full_text = \$accel\$$this->full_text\$accel\$ WHERE ax.ax_message.id = $this->id";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  // -- END SETTERS



  // WORK WITH MESSAGE

  public function pushNewToDB($assignment_id)
  {
    global $dbconnect;

    if ($this->reply_to_id != null) {
      $query = "INSERT INTO ax.ax_message (assignment_id, type, sender_user_id, sender_user_type, 
                date_time, reply_to_id, full_text, status, visibility) 
                VALUES ($assignment_id, $this->type, $this->sender_user_id, $this->sender_user_type, 
                now(), $this->reply_to_id, \$antihype1\$$this->full_text\$antihype1\$, $this->status, $this->visibility)
                RETURNING id, date_time;";
    } else {
      $query = "INSERT INTO ax.ax_message (assignment_id, type, sender_user_id, sender_user_type, 
                date_time, full_text, status, visibility) 
                VALUES ($assignment_id, $this->type, $this->sender_user_id, $this->sender_user_type, 
                now(), \$antihype1\$$this->full_text\$antihype1\$, $this->status, $this->visibility)
                RETURNING id, date_time;";
    }

    $pg_query = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    $result = pg_fetch_assoc($pg_query);
    $this->id = $result['id'];
    $this->date_time = $result['date_time'];
  }
  public function pushChangesToDB()
  {
    global $dbconnect;

    $query = "UPDATE ax.ax_message SET type = $this->type, sender_user_id = $this->sender_user_id, 
      sender_user_type = $this->sender_user_type, date_time = '$this->date_time', 
      reply_to_id = $this->reply_to_id, full_text = \$antihype1\$$this->full_text\$antihype1\$, status = $this->status, 
      visibility = $this->visibility WHERE id = $this->id;
    ";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  public function deleteFromDB()
  {
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
    foreach ($this->Files as $File) {
      // Удаляем файл совсем, если он - не файл коммита
      if (!$File->type == 0)
        $File->deleteFromDB();
    }

    $query = "UPDATE ax.ax_message SET status = 2 WHERE id = $this->id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }


  function isResended()
  {
    return $this->resended_from_id != null;
  }
  function isReply()
  {
    return $this->reply_to_id != null;
  }
  function isVisible($user_role)
  {
    return $this->visibility == 0 || $this->visibility == $user_role;
  }

  public function isReadedByUser($user_id)
  {
    global $dbconnect;
    $query = "SELECT status FROM ax.ax_message_delivery WHERE message_id = $this->id AND recipient_user_id = $user_id;";
    $pg_query = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    $result = pg_fetch_assoc($pg_query);
    if ($result)
      return $result['status'] == 1;
    return false;
  }

  public function isReadedBySombody()
  {
    global $dbconnect;

    $query = "SELECT COUNT(status) as count FROM ax.ax_message_delivery WHERE message_id = $this->id AND status = 1;";
    $pg_query = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    $count = pg_fetch_assoc($pg_query)['count'];

    if ($count > 1)
      return true;

    return false;
  }


  // -- END WORK WITH MESSAGE


  // WORK WITH DELIVERY


  public function setReadedDeliveryStatus($user_id)
  {
    global $dbconnect;

    $query = "SELECT COUNT(*) as count FROM ax.ax_message_delivery WHERE recipient_user_id = $user_id AND message_id = $this->id;";
    $pg_query = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    $count = pg_fetch_assoc($pg_query)['count'];

    if ($count < 1)
      $query = "INSERT INTO ax.ax_message_delivery (message_id, recipient_user_id, status) 
      VALUES($this->id, $user_id, 1);";
    else
      $query = "UPDATE ax.ax_message_delivery SET status = 1 WHERE recipient_user_id = $user_id AND message_id = $this->id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }


  // public function isFirstUnreaded($user_id) {
  //   global $dbconnect; 

  //   $query = "SELECT min(message_id) as min_message_id FROM ax.ax_message_delivery WHERE recipient_user_id = $user_id 
  //             AND status = 0 AND assignment_id = ... LIMIT 1;";
  //   $pg_query = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  //   $min_new_message_id = pg_fetch_assoc($pg_query)['min_message_id'];

  //   if ($min_new_message_id == $this->id)
  //     return true;

  //   return false;
  // }

  // public function pushSelfToDeliveryDB() {
  //   global $dbconnect;

  //   $query = "INSERT INTO ax.ax_message_delivery (message_id, recipient_user_id, status)
  //             VALUES ($this->id, $this->sender_user_id, 1)";

  //   pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  // }

  // -- END WORK WITH DELIVERY


  // WORK WITH FILE

  public function addFile($file_id)
  {
    $File = new File((int)$file_id);
    $this->pushFileToMessageDB($file_id);
    array_push($this->Files, $File);
  }
  public function addFiles($Files)
  {
    $this->pushFilesToMessageDB($Files);
    foreach ($Files as $File) {
      array_push($this->Files, $File);
    }
  }
  public function deleteFile($file_id)
  {
    $index = $this->findFileById($file_id);
    if ($index != -1) {
      $this->deleteFileFromMessageDB($file_id);
      $this->Files[$index]->deleteFromDB();
      unset($this->Files[$index]);
      $this->Files = array_values($this->Files);
    }
  }
  private function findFileById($file_id)
  {
    $index = 0;
    foreach ($this->Files as $File) {
      if ($File->id == $file_id)
        return $index;
      $index++;
    }
    return -1;
  }
  public function getFileById($file_id)
  {
    foreach ($this->Files as $File) {
      if ($File->id == $file_id)
        return $File;
    }
    return null;
  }

  private function pushFileToMessageDB($file_id)
  {
    global $dbconnect;

    $query = "INSERT INTO ax.ax_message_file (message_id, file_id) VALUES ($this->id, $file_id);";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  private function pushFilesToMessageDB($Files)
  {
    global $dbconnect;

    if (!empty($Files)) {
      $query = "";
      foreach ($Files as $File) {
        $query .= "INSERT INTO ax.ax_message_file (message_id, file_id) VALUES ($this->id, $File->id);";
      }
      pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    }
  }
  private function deleteFileFromMessageDB($file_id)
  {
    global $dbconnect;

    $query = "DELETE FROM ax.ax_message_file WHERE file_id = $file_id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  private function synchFilesToMessageDB()
  {
    global $dbconnect;

    $this->deleteFilesFromMessageDB();

    if (!empty($this->Files)) {
      $query = "";
      foreach ($this->Files as $File) {
        $query .= "INSERT INTO ax.ax_message_file (message_id, file_id) VALUES ($this->id, $File->id);";
      }
      pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    }
  }
  private function deleteFilesFromMessageDB()
  {
    global $dbconnect;

    // Удаляем предыдущие прикрепления файлов
    $query = "DELETE FROM ax.ax_message_file WHERE message_id = $this->id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  // -- END WORK WITH FILE



  // WORK WITH COMMIT

  public function setCommit($commit_id)
  {
    $this->Commit = new Commit((int)$commit_id);
    $this->pushCommitToDB();
  }
  private function pushCommitToDB()
  {
    global $dbconnect;

    if ($this->Commit != null) {
      $commit_id = $this->Commit->id;
      $query = "UPDATE ax.ax_message SET commit_id = $commit_id WHERE id = $this->id;";
      pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    }
  }

  // -- END WORK WITH COMMIT
}



// 
// 
// 
// 

function getMainResendedMessage($Message)
{
  if (!$Message->isResended() || $Message->resended_from_id == null)
    return $Message;
  $resendedMessage = new Message((int)$Message->resended_from_id);
  return getMainResendedMessage($resendedMessage);
}

function getCountUnreadedMessagesByUser($user_id)
{
  global $dbconnect;
  $query = "SELECT COUNT(*) FROM ax.ax_message_delivery WHERE recipient_user_id = $user_id;";
  pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
}

function getMessageAssignmentCompleted($mark)
{
  if ($mark == "зачтено")
    return "Задание зачтено!";
  else if ($mark != "" && $mark != null)
    return "Задание оценено! \nОценка: $mark";
  else
    return "Оценка отменена!";
}


function getCommitByMessage($message_id)
{
  global $dbconnect;

  $query = queryGetCommitByMessage($message_id);
  $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  $commit_row = pg_fetch_assoc($result);

  if ($commit_row)
    return new Commit((int)$commit_row['id']);
  else
    return null;
}

function getFilesByMessage($message_id)
{
  global $dbconnect;

  $files = array();

  $query = queryGetFilesByMessage($message_id);
  $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

  while ($file_row = pg_fetch_assoc($result)) {
    array_push($files, new File((int)$file_row['id']));
  }

  return $files;
}



// ФУНКЦИИ ЗАПРОСОВ К БД 

function queryGetMessageInfo($message_id)
{
  return "SELECT * FROM ax.ax_message WHERE id = $message_id;
  ";
}

function queryGetCommitByMessage($message_id)
{
  return "SELECT commit_id as id FROM ax.ax_message WHERE id = $message_id";
}

function queryGetFilesByMessage($message_id)
{
  return "SELECT file_id as id FROM ax.ax_message_file WHERE message_id = $message_id";
}

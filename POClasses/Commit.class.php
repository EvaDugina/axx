<?php 
require_once("./settings.php");
require_once("File.class.php");

class Commit {

  private $id;
  private $session_id, $student_user_id, $type, $autotest_result;
  //private $comment; можно реализовать
  
  private $Files = array();


  function __construct() {
    global $dbconnect;

    $count_args = func_num_args();
    $args = func_get_args();

    // Перегружаем конструктор по количеству подданых параметров

    if ($count_args == 1 && is_int($args[0])) {
      $this->id = $args[0];
  
      $query = queryGetCommitInfo($this->id);
      $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
      $file = pg_fetch_assoc($result);
  
      $this->session_id = $file['session_id'];
      $this->student_user_id = $file['student_user_id'];
      $this->type = $file['type'];
      $this->autotest_result = $file['autotest_result'];
      //$this->comment = $file[''];

      $this->Files = getFilesByCommit($this->id);
      
    } 
    
    else if ($count_args == 5) {
      $assignment_id = $args[0];

      $this->session_id = $args[1];
      $this->student_user_id = $args[2];
      $this->type = $args[3];
      $this->autotest_result = $args[4];

      $this->pushNewToDB($assignment_id);

    } 
    
    else {
      die('Неверное число аргументов, или неверный id файла');
    }

  }

  
// GETTERS:

  public function getId(){
    return $this->id;
  }

  public function getSessionId(){
    return $this->session_id;
  }

  public function getStudentUserId(){
    return $this->student_user_id;
  }

  public function getType(){
    return $this->type;
  }

  public function getAutoTestResult(){
    return $this->autotest_result;
  }

  // public function getComment(){
  //   return $this->comment;
  // }

  public function getFiles(){
    return $this->Files;
  }

// -- END GETTERS



// SETTERS:

  public function setSessionId($session_id){
    $this->session_id = $session_id;
  }

  public function setStudentUserId($student_user_id){
    $this->student_user_id = $student_user_id;
  }

  public function setType($type){
    $this->type = $type;
  }

  public function setAutoTestResult($autotest_result){
    $this->autotest_result = $autotest_result;
  }

  // public function setComment($comment){
  //   $this->comment = $comment;
  // }

// -- END SETTERS



  public function addFile($file_id) {
    $File = new File($file_id);
    $this->pushFileToCommitDB($file_id);
    array_push($this->Files, $File);
  }
  public function deleteFile($file_id) {
    $index = $this->findFileById($file_id);
    if ($index != -1) {
      $this->deleteFileFromCommitDB($file_id);
      $this->Files[$index]->deleteFromDB();
      unset($this->Files[$index]);
    }
  }
  private function findFileById($file_id) {
    $index = 0;
    foreach($this->Files as $File) {
      if ($File->getId() == $file_id)
        return $index;
      $index++;
    }
    return -1;
  }



  public function pushNewToDB($assignment_id) {
    global $dbconnect;

    $query = "INSERT INT ax_solution_commit (assignment_id, session_id, student_user_id, type, autotest_result)
              VALUES ($assignment_id, $this->session_id, $this->student_user_id, $this->type, $this->autotest_result)
              RETURNING id";

    $pg_query = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    $result = pg_fetch_assoc($pg_query);

    $this->id = $result['id'];
  }
  public function deleteFromDB() {
    global $dbconnect;
  
    foreach($this->Files as $File) {
      $File->deleteFromDB();
    }
    
    $query = "DELETE FROM ax_commit_file WHERE commit_id = $this->id;";
    $query .= "DELETE FROM ax_solution_commit WHERE id = $this->id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  public function pushCommitChangesToDB() {
    global $dbconnect;

    $query = "UPDATE ax_solution_commit SET session_id = $this->session_id, student_user_id = $this->student_user_id, 
      type = $this->type, autotest_result = '$this->autotest_result' WHERE id = $this->id;
    ";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  public function pushFileChangesToDB() {
    global $dbconnect;

    $this->deleteFilesFromDB();

    $query = "";
    if (!empty($this->Files)) {
      foreach($this->Files as $File) {
        $query .= "INSERT INTO ax_commit_file (commit_id, file_id) VALUES ($this->id, $File->getId());";
      }
    }
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  public function pushFileToCommitDB($file_id) {
    global $dbconnect;

    $query = "INSERT INTO ax_commit_file (commit_id, file_id) VALUES ($this->id, $file_id);";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  public function deleteFileFromCommitDB($file_id) {
    global $dbconnect;

    $query = "DELETE FROM ax_commit_file WHERE file_id = $file_id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }


  // КАК УДАЛЯТЬ?!
  public function deleteFilesFromDB() {
    global $dbconnect;

    // Удаляем предыдущие прикрепления файлов
    $query = "DELETE FROM ax_file USING ax_commit_file WHERE commit_id = $this->id;";
    $query .= "DELETE FROM ax_commit_file WHERE commit_id = $this->id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

}


function getFilesByCommit($commit_id) {
  global $dbconnect;

  $files = array();

  $query = queryGetFilesByCommit($commit_id);
  $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

  while($file_row = pg_fetch_assoc($result)){
    array_push($files, new File($file_row['id']));
  }

  return $files;
}



function queryGetFilesByCommit($commit_id) {
  return "SELECT file_id as id FROM ax_commit_file WHERE commit_id = $commit_id";
}

function queryGetCommitInfo($commit_id){
  return "SELECT * FROM ax_solution_commit WHERE id = $commit_id";
}

?>
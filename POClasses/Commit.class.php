<?php 
require_once("./settings.php");
require_once("File.class.php");

class Commit {

  public $id = null;
  public $session_id = null, $student_user_id = null, $type = null, $autotest_results = null;
  //private $comment; можно реализовать
  
  private $Files = array();


  public function __construct() {
    global $dbconnect;

    $count_args = func_num_args();
    $args = func_get_args();

    // Перегружаем конструктор по количеству подданых параметров

    if ($count_args == 1 && is_int($args[0])) {
      $this->id = $args[0];
  
      $query = queryGetCommitInfo($this->id);
      $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
      $commit = pg_fetch_assoc($result);

      if (isset($commit['session_id']) )
        $this->session_id = $commit['session_id'];
      if (isset($commit['student_user_id']) )
        $this->student_user_id = $commit['student_user_id'];
      if (isset($commit['type']) )
        $this->type = $commit['type'];
      if (isset($commit['autotest_results']) )
        $this->autotest_results = $commit['autotest_results'];
      //$this->comment = $file[''];

      $this->Files = getFilesByCommit($this->id);
      
    } 
    
    else if ($count_args == 5) {
      $assignment_id = $args[0];

      if ($args[1] == null)
        $this->session_id = "null";
      else
        $this->session_id = $args[1];

      $this->student_user_id = $args[2];
    
      if ($args[3] == null)
        $this->type = 1;
      else
        $this->type = $args[3];

      if ($args[4] == null)
        $this->autotest_results = "null";
      else
        $this->autotest_results = $args[4];

      $this->pushNewToDB($assignment_id);

    } 
    
    else {
      die('Неверные аргументы в конструкторе Commit');
    }

  }


  public function getFiles() {
    return $this->Files;
  }
  public function getFileIds() {
    $file_ids = array();
    foreach ($this->Files as $File)
      array_push($file_ids, $File->id);
    return $file_ids;
  }


  public function setType($type) {
    global $dbconnect;

    $this->type = $type;

    $query = "UPDATE ax_solution_commit SET type = $this->type
              WHERE id = $this->id;
    ";

    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }



// WORK WITH COMMIT 

  public function pushNewToDB($assignment_id) {
    global $dbconnect;

    $query = "INSERT INTO ax_solution_commit (assignment_id, session_id, student_user_id, type, autotest_results)
              VALUES ($assignment_id, $this->session_id, $this->student_user_id, $this->type, $this->autotest_results)
              RETURNING id";

    $pg_query = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    $result = pg_fetch_assoc($pg_query);

    $this->id = $result['id'];
  }
  public function pushAllChangesToDB($assignment_id) {
    global $dbconnect;

    $query = "UPDATE ax_solution_commit SET assignment_id=$assignment_id, session_id=$this->session_id, 
              student_user_id=$this->student_user_id, type=$this->type, autotest_results=$this->autotest_results
              WHERE id = $this->id";

    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  public function deleteFromDB() {
    global $dbconnect;
  
    $this->deleteFilesFromCommitDB();

    foreach($this->Files as $File) {
      $File->deleteFromDB();
    }
    
    $query = "DELETE FROM ax_solution_commit WHERE id = $this->id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  public function pushChangesToDB() {
    global $dbconnect;

    $query = "UPDATE ax_solution_commit SET session_id = $this->session_id, student_user_id = $this->student_user_id, 
      type = $this->type, autotest_results = '$this->autotest_results' WHERE id = $this->id;
    ";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }


  public function copy($commit_id) {
    $Commit = new Commit((int)$commit_id);

    if ($Commit->session_id == null)
      $this->session_id = "null";
    else
      $this->session_id = $Commit->session_id;

    $this->student_user_id = $Commit->student_user_id;
    $this->type = $Commit->type;

    if ($Commit->autotest_results == null)
      $this->autotest_results = "null";
    else
      $this->autotest_results = $Commit->autotest_results;

    $this->pushAllChangesToDB(getAssignmentByCommit($Commit->id));

    $this->deleteFilesFromCommitDB();
    $this->addFiles($Commit->getFiles());
  }

// -- END WORK WITH COMMIT 



// WORK WITH FILE 

  public function addFile($file_id) {
    $File = new File((int)$file_id);
    $this->pushFileToCommitDB($file_id);
    array_push($this->Files, $File);
  }
  public function addFiles($Files) {
    $copyFiles = array();
    foreach ($Files as $File) {
      $copiedFile = new File($File->type, $File->name_without_prefix);
      $copiedFile->copy($File->id);
      array_push($copyFiles, $copiedFile);
    }
    $this->pushFilesToCommitDB($copyFiles);

    foreach ($copyFiles as $File) {
      array_push($this->Files, $File);
    }
  }
  public function copyFiles() {

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

  private function pushFileToCommitDB($file_id) {
    global $dbconnect;

    $query = "INSERT INTO ax_commit_file (commit_id, file_id) VALUES ($this->id, $file_id);";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  private function pushFilesToCommitDB($Files) {
    global $dbconnect;

    $query = "";
    if (!empty($Files)) {
      foreach($Files as $File) {
        $query .= "INSERT INTO ax_commit_file (commit_id, file_id) VALUES ($this->id, $File->id);";
      }
    }
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  private function deleteFileFromCommitDB($file_id) {
    global $dbconnect;

    $query = "DELETE FROM ax_commit_file WHERE file_id = $file_id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  private function deleteFilesFromCommitDB() {
    global $dbconnect;
  
    // Удаляем предыдущие прикрепления файлов
    $query = "DELETE FROM ax_commit_file WHERE commit_id = $this->id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

// -- END WORK WITH FILE 



}


function getFilesByCommit($commit_id) {
  global $dbconnect;

  $files = array();

  $query = queryGetFilesByCommit($commit_id);
  $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

  while($file_row = pg_fetch_assoc($result)){
    array_push($files, new File((int)$file_row['id']));
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
<?php
require_once("./settings.php");
require_once("File.class.php");

class Commit
{

  public $id = null;
  public $session_id = null, $student_user_id = null;
  public $type = null;  // 0 - промежуточный (редактирует только отправляющий), 1 - отправлен на проверку (не редактирует никто), 2 - проверяется (редактирует только препод), 3 - проверенный (не редактирует никто)
  public $autotest_results = null;
  public $date_time;
  //private $comment; можно реализовать

  private $Files = array();


  public function __construct()
  {
    global $dbconnect;

    $count_args = func_num_args();
    $args = func_get_args();

    // Перегружаем конструктор по количеству подданых параметров

    if ($count_args == 1) {
      $this->id = (int)$args[0];

      $query = queryGetCommitInfo($this->id);
      $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
      $commit = pg_fetch_assoc($result);

      if (isset($commit['session_id']))
        $this->session_id = $commit['session_id'];
      if (isset($commit['student_user_id']))
        $this->student_user_id = $commit['student_user_id'];
      if (isset($commit['type']))
        $this->type = $commit['type'];
      if (isset($commit['autotest_results']))
        $this->autotest_results = $commit['autotest_results'];
      if (isset($commit['date_time']))
        $this->date_time = convertServerDateTimeToCurrent($commit['date_time']);
      //$this->comment = $file[''];

      $this->Files = getFilesByCommit($this->id);
    } else if ($count_args == 5) {
      $assignment_id = $args[0];

      if ($args[1] == null)
        $this->session_id = "null";
      else
        $this->session_id = $args[1];

      $this->student_user_id = $args[2];

      if ($args[3] === null)
        $this->type = 0;
      else
        $this->type = $args[3];

      if ($args[4] === null)
        $this->autotest_results = "null";
      else
        $this->autotest_results = $args[4];

      $this->pushNewToDB($assignment_id);
    } else {
      die('Неверные аргументы в конструкторе Commit');
    }
  }


  public function getFiles()
  {
    return $this->Files;
  }
  public function getFileIds()
  {
    $file_ids = array();
    foreach ($this->Files as $File)
      array_push($file_ids, $File->id);
    return $file_ids;
  }


  public function setType($type)
  {
    global $dbconnect;

    $this->type = $type;

    $query = "UPDATE ax.ax_solution_commit SET type = $this->type
              WHERE id = $this->id;
    ";

    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  public function setStudentUserId($student_user_id)
  {
    global $dbconnect;

    $this->student_user_id = $student_user_id;

    $query = "UPDATE ax.ax_solution_commit SET student_user_id = $this->student_user_id
              WHERE id = $this->id;
    ";

    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  public function getConvertedDateTime()
  {
    if ($this->date_time != null)
      return getConvertedDateTime($this->date_time);
    return "";
  }





  // WORK WITH COMMIT 

  public function pushNewToDB($assignment_id)
  {
    global $dbconnect;

    $query = "INSERT INTO ax.ax_solution_commit (assignment_id, session_id, student_user_id, date_time, type, autotest_results)
              VALUES ($assignment_id, $this->session_id, $this->student_user_id, now(), $this->type, \$antihype1\$$this->autotest_results\$antihype1\$)
              RETURNING id, date_time";

    $pg_query = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    $result = pg_fetch_assoc($pg_query);

    $this->id = $result['id'];
    $this->date_time = convertServerDateTimeToCurrent($result['date_time']);
  }
  public function pushAllChangesToDB($assignment_id)
  {
    global $dbconnect;

    $query = "UPDATE ax.ax_solution_commit SET assignment_id=$assignment_id, session_id=$this->session_id, 
              student_user_id=$this->student_user_id, type=$this->type, autotest_results=\$antihype1\$$this->autotest_results\$antihype1\$
              WHERE id = $this->id 
              RETURNING date_time;";

    $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  public function deleteFromDB()
  {
    global $dbconnect;

    $this->deleteFilesFromCommitDB();

    foreach ($this->Files as $File) {
      $File->deleteFromDB();
    }

    $query = "DELETE FROM ax.ax_solution_commit WHERE id = $this->id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  public function pushChangesToDB()
  {
    global $dbconnect;

    $query = "UPDATE ax.ax_solution_commit SET session_id = $this->session_id, student_user_id = $this->student_user_id, 
      date_time=$this->date_time, type = $this->type, autotest_results = \$antihype1\$$this->autotest_results\$antihype1\$ WHERE id = $this->id;
    ";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }


  public function copy($targetCommit)
  {

    if ($targetCommit->session_id == null)
      $this->session_id = "null";
    else
      $this->session_id = $targetCommit->session_id;

    $this->student_user_id = $targetCommit->student_user_id;
    $this->type = $targetCommit->type;

    if ($targetCommit->autotest_results == null)
      $this->autotest_results = "null";
    else
      $this->autotest_results = $targetCommit->autotest_results;

    $this->pushAllChangesToDB(getAssignmentByCommit($targetCommit->id));

    $this->deleteFilesFromCommitDB();
    $this->addFiles($targetCommit->getFiles());
  }

  public function isInProcess()
  {
    return $this->type == 0;
  }
  public function isSendedForCheck()
  {
    return $this->type == 1;
  }
  public function isChecking()
  {
    return $this->type == 2;
  }
  public function isMarked()
  {
    return $this->type == 3;
  }

  public function isEditByTeacher()
  {
    $commitUser = new User($this->student_user_id);
    return $this->isChecking() || (($commitUser->isTeacher() || $commitUser->isAdmin()) && $this->isInProcess());
  }
  public function isEditByStudent()
  {
    $commitUser = new User($this->student_user_id);
    return $this->isInProcess() && $commitUser->isStudent();
  }
  public function isNotEdit()
  {
    return $this->isSendedForCheck() || $this->isMarked();
  }

  // -- END WORK WITH COMMIT 



  // WORK WITH FILE 

  public function addFile($file_id)
  {
    $File = new File((int)$file_id);
    $this->pushFileToCommitDB($file_id);
    array_push($this->Files, $File);
  }
  public function addFiles($Files)
  {
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
  public function copyFiles() {}
  public function deleteFile($file_id)
  {
    $index = $this->findFileById($file_id);
    if ($index != -1) {
      $this->deleteFileFromCommitDB($file_id);
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
  public function getFileByName($file_name)
  {
    foreach ($this->Files as $File) {
      if ($File->name_without_prefix == $file_name)
        return $File;
    }
    return null;
  }

  private function pushFileToCommitDB($file_id)
  {
    global $dbconnect;

    $query = "INSERT INTO ax.ax_commit_file (commit_id, file_id) VALUES ($this->id, $file_id);";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  private function pushFilesToCommitDB($Files)
  {
    global $dbconnect;

    if (!empty($Files)) {
      $query = "";
      foreach ($Files as $File) {
        $query .= "INSERT INTO ax.ax_commit_file (commit_id, file_id) VALUES ($this->id, $File->id);";
      }
      pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    }
  }
  private function deleteFileFromCommitDB($file_id)
  {
    global $dbconnect;

    $query = "DELETE FROM ax.ax_commit_file WHERE file_id = $file_id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  private function deleteFilesFromCommitDB()
  {
    global $dbconnect;

    // Удаляем предыдущие прикрепления файлов
    $query = "DELETE FROM ax.ax_commit_file WHERE commit_id = $this->id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  // -- END WORK WITH FILE 

}

function getCommitCopy($assignment_id, $user_id, $targetCommit)
{
  $Assignment = new Assignment($assignment_id);
  $copyCommit = new Commit($assignment_id, null, $user_id, 0, null);
  $copyCommit->copy($targetCommit);
  $copyCommit->setStudentUserId($user_id);
  $Assignment->addCommit($copyCommit->id);
  return $copyCommit;
}


function getFilesByCommit($commit_id)
{
  global $dbconnect;

  $files = array();

  $query = queryGetFilesByCommit($commit_id);
  $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

  while ($file_row = pg_fetch_assoc($result)) {
    array_push($files, new File((int)$file_row['id']));
  }

  return $files;
}


function getSVGByCommitType($type)
{
  if ($type == 0 || $type == 2) { ?>
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">
      <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z" />
      <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z" />
    </svg>
  <?php } else { ?>
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye-fill" viewBox="0 0 16 16">
      <path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0z" />
      <path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8zm8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z" />
    </svg>
<?php }
}



function queryGetFilesByCommit($commit_id)
{
  return "SELECT file_id as id FROM ax.ax_commit_file WHERE commit_id = $commit_id";
}

function queryGetCommitInfo($commit_id)
{
  return "SELECT * FROM ax.ax_solution_commit WHERE id = $commit_id";
}

?>
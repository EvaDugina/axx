<?php
require_once("./settings.php");
require_once("File.class.php");

class Commit
{

  public $id = null;
  public $session_id = null, $student_user_id = null;
  public $type = null;  // 0 - промежуточный (редактирует только отправляющий), 1 - отправлен на проверку (не редактирует никто), 2 - проверяется (редактирует только препод), 3 - проверенный (не редактирует никто), 4 - коммит с исходным кодом
  public $status = null; // 0 - видимый автору, 1 - видимый всем
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
        $this->type = (int)$commit['type'];
      if (isset($commit['status']))
        $this->status = (int)$commit['status'];
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
        $this->type = (int)$args[3];

      $this->status = getCommitStatusByType($this->type);

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

    $this->type = (int)$type;
    $this->status = getCommitStatusByType($this->type);

    $query = "UPDATE ax.ax_solution_commit SET type = $this->type, status = $this->status
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

    $now = get_now_date("Y-m-d H:i:s");

    $query = "INSERT INTO ax.ax_solution_commit (assignment_id, session_id, student_user_id, date_time, type, status, autotest_results)
              VALUES ($assignment_id, $this->session_id, $this->student_user_id, to_timestamp('$now', 'YYYY-MM-DD HH24:MI:SS'), $this->type, $this->status, \$antihype1\$$this->autotest_results\$antihype1\$)
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
              student_user_id=$this->student_user_id, type=$this->type, status=$this->status, autotest_results=\$antihype1\$$this->autotest_results\$antihype1\$
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
      date_time=$this->date_time, type = $this->type, status=$this->status, autotest_results = \$antihype1\$$this->autotest_results\$antihype1\$ WHERE id = $this->id;
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
    $this->status = $targetCommit->status;

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
  public function isTemplate()
  {
    return $this->type == 4;
  }

  public function isVisibleToAll()
  {
    return $this->status == 1;
  }

  public function isEditByTeacher()
  {
    $commitUser = new User($this->student_user_id);
    return (($commitUser->isTeacher() || $commitUser->isAdmin()) && $this->isInProcess()) || $this->isChecking();
  }
  public function isEditByStudent()
  {
    $commitUser = new User($this->student_user_id);
    return $commitUser->isStudent() && $this->isInProcess();
  }
  public function isNotEdit($isStudent)
  {
    if ($isStudent)
      return !$this->isEditByStudent();
    return !$this->isEditByTeacher();
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
  public function deleteAllFiles()
  {
    foreach ($this->Files as $File) {
      $this->deleteFile($File->id);
    }
  }
  public function deleteFile($file_id)
  {
    $index = $this->findFileById($file_id);
    if ($index != -1) {
      $this->Files[$index]->deleteFromDB();
    }
    $this->deleteFileFromCommitSoft($file_id);
  }
  public function deleteFileFromCommitSoft($file_id)
  {
    $index = $this->findFileById($file_id);
    if ($index != -1) {
      $this->deleteFileFromCommitDB($file_id);
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


function getCommitStatusByType($commit_type)
{
  switch ($commit_type) {
    case 0:
      return 0;
    case 1:
      return 1;
    case 2:
      return 0;
    case 3:
      return 1;
    case 4:
      return 1;
  }
  return null;
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
  <?php } else if ($type == 1) { ?>
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-envelope-fill" viewBox="0 0 16 16">
      <path d="M.05 3.555A2 2 0 0 1 2 2h12a2 2 0 0 1 1.95 1.555L8 8.414zM0 4.697v7.104l5.803-3.558zM6.761 8.83l-6.57 4.027A2 2 0 0 0 2 14h12a2 2 0 0 0 1.808-1.144l-6.57-4.027L8 9.586zm3.436-.586L16 11.801V4.697z" />
    </svg>
  <?php } else if ($type == 3) { ?>
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-reply-fill" viewBox="0 0 16 16">
      <path d="M5.921 11.9 1.353 8.62a.72.72 0 0 1 0-1.238L5.921 4.1A.716.716 0 0 1 7 4.719V6c1.5 0 6 0 7 8-2.5-4.5-7-4-7-4v1.281c0 .56-.606.898-1.079.62z" />
    </svg>
  <?php } else if ($type == 4) { ?>
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-code-slash" viewBox="0 0 16 16">
      <path d="M10.478 1.647a.5.5 0 1 0-.956-.294l-4 13a.5.5 0 0 0 .956.294zM4.854 4.146a.5.5 0 0 1 0 .708L1.707 8l3.147 3.146a.5.5 0 0 1-.708.708l-3.5-3.5a.5.5 0 0 1 0-.708l3.5-3.5a.5.5 0 0 1 .708 0m6.292 0a.5.5 0 0 0 0 .708L14.293 8l-3.147 3.146a.5.5 0 0 0 .708.708l3.5-3.5a.5.5 0 0 0 0-.708l-3.5-3.5a.5.5 0 0 0-.708 0" />
    </svg>
  <?php } else { ?>
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-question-circle" viewBox="0 0 16 16">
      <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16" />
      <path d="M5.255 5.786a.237.237 0 0 0 .241.247h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286m1.557 5.763c0 .533.425.927 1.01.927.609 0 1.028-.394 1.028-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94" />
    </svg>
<?php }
}



function queryGetFilesByCommit($commit_id)
{
  return "SELECT file_id as id FROM ax.ax_commit_file WHERE commit_id = $commit_id ORDER BY id;";
}

function queryGetCommitInfo($commit_id)
{
  return "SELECT * FROM ax.ax_solution_commit WHERE id = $commit_id";
}

?>
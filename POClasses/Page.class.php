<?php
require_once("./settings.php");

require_once("Task.class.php");
require_once("User.class.php");
require_once("Group.class.php");
require_once("ColorTheme.class.php");


class Page
{

  public $id;
  public $disc_id, $disc_name;
  public $name, $year, $semester;
  public $ColorTheme;
  public $creator_id, $creation_date;
  public $description;
  public $type; // 0 - обычное, 1 - внесеместровое
  public $status; // 0 - удалённое, 1 - активное
  // public $subgroup = null;

  private $Tasks = array(); // Массив Task
  private $Groups = array(); // Массив Group
  private $Teachers = array(); // Массив User

  function __construct($page_id)
  {
    global $dbconnect;

    $count_args = func_num_args();
    $args = func_get_args();

    // Перегружаем конструктор по количеству подданых параметров

    if ($count_args == 1) {
      $this->id = (int)$args[0];

      $query = queryGetPageInfo($this->id);
      $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
      $page = pg_fetch_assoc($result);

      if (isset($page['disc_id'])) {
        $this->disc_id = $page['disc_id'];
        $this->disc_name = $page['disc_name'];
      } else {
        $this->disc_id = null;
        $this->disc_name = "ДРУГОЕ";
      }

      $this->name = $page['short_name'];
      $this->year = $page['year'];
      $this->semester = $page['semester'];

      $this->ColorTheme = new ColorTheme((int)$page['color_theme_id']);

      $this->creator_id = $page['creator_id'];
      $this->creation_date = $page['creation_date'];

      $this->description = $page['description'];

      // if(array_key_exists('subgroup_id', $page))
      //   $this->subgroup = $page['subgroup_id'];

      // $this->src_url = $page['src_url'];
      $this->type = $page['type'];
      $this->status = $page['status'];

      $this->Tasks = getTasksByPage($this->id);
      $this->Groups = getGroupsByPage($this->id);
      $this->Teachers = getTeachersByPage($this->id);
    } else if ($count_args == 2) {

      $this->disc_id = null;
      $this->name = null;

      $this->ColorTheme = new ColorTheme(-1);
      $this->creator_id = $args[0];
      $this->type = 1;
      $this->status = 1;

      $this->pushNewToDB();
    } else if ($count_args == 4) {

      $this->disc_id = $args[0];
      $this->name = $args[1];

      $this->ColorTheme = new ColorTheme($args[2]);
      $this->creator_id = $args[3];
      $this->type = 1;
      $this->status = 1;

      $this->pushNewToDB();
    } else if ($count_args == 7) {
      $this->disc_id = $args[0];

      $this->name = $args[1];
      $this->year = $args[2];
      $this->semester = $args[3];

      $this->ColorTheme = new ColorTheme($args[4]);
      $this->creator_id = $args[5];

      $this->description = $args[6];

      $this->type = 0;
      $this->status = 1;

      $this->pushNewToDB();
    } else {
      die('Неверные аргументы в конструкторе Page');
    }
  }

  public function getAllTasks()
  {
    return $this->Tasks;
  }
  public function getTasks()
  {
    $Tasks = array();
    foreach ($this->Tasks as $Task) {
      if (!$Task->isConversation())
        array_push($Tasks, $Task);
    }
    return $Tasks;
  }
  public function getAllCompilingAssignmentsByStudent($student_id)
  {
    $studentAssignments = [];
    foreach ($this->Tasks as $Task) {
      if (!$Task->isConversation() && $Task->isActive()) {
        $Assignment = $Task->getLastAssignmentByStudent($student_id);
        if ($Assignment != null)
          array_push($studentAssignments, $Assignment);
      }
    }
    return $studentAssignments;
  }
  public function getCountCompletedAssignmentsByStudent($student_id)
  {
    $count_success = 0;
    foreach ($this->Tasks as $Task) {
      if ($Task->isActive())
        $count_success += $Task->getCountCompletedAssignments($student_id);
    }
    return $count_success;
  }
  public function hasAssignmentsByStudent($student_id)
  {
    foreach ($this->Tasks as $Task) {
      $Assignment = $Task->getLastAssignmentByStudent($student_id);
      if ($Assignment != null)
        return true;
    }
    return false;
  }

  public function isOutsideSemester()
  {
    return $this->type == 1;
  }

  public function getGroups()
  {
    return $this->Groups;
  }
  public function getTeachers()
  {
    return $this->Teachers;
  }

  public function getDisciplineName()
  {
    if ($this->disc_id == null)
      return "ДРУГОЕ";
    else
      return $this->disc_name;
  }

  public function getMainInfoAsTextForDowload()
  {

    //     DECLARE rowId integer;
    // begin
    //     INSERT INTO test_table (session_id)
    // 	VALUES ('adsvasv') RETURNING id INTO rowId; 

    // 	INSERT INTO test_table (step_num)
    // 	VALUES (rowId); 
    // end;
    $query_page = "
    CREATE procedure downloadPage$this->id() language plpgsql AS E'
    DECLARE 
      pageId  integer;
      current_task_id  integer;
      current_file_id  integer;  
    BEGIN\n";

    $query_page .= queryInsertMainPage($this);

    $query_tasks = "";
    foreach ($this->getTasks() as $Task) {
      $query_tasks .= "\n" . $Task->getMainInfoAsTextForDowload();
    }
    $query_page .= $query_tasks;

    $query_page .= "
    END;
    ';
    CALL downloadPage$this->id();
    DROP PROCEDURE downloadPage$this->id();";

    return $query_page;
  }




  // WORK WITH PAGE

  public function pushNewToDB()
  {
    global $dbconnect;

    $query = queryInsertPage($this);

    $pg_query = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    $result = pg_fetch_assoc($pg_query);

    $this->id = $result['id'];
  }
  public function pushPageChangesToDB()
  {
    global $dbconnect;

    $disc_sql = "";
    if ($this->disc_id == null) {
      $disc_sql = "disc_id = null";
    } else {
      $disc_sql = "disc_id = $this->disc_id";
    }

    $color_theme_id = $this->ColorTheme->id;

    $query = "UPDATE ax.ax_page SET short_name =\$antihype1\$$this->name\$antihype1\$, " . $disc_sql . ", year=$this->year, semester=$this->semester,
              color_theme_id=$color_theme_id, description=$this->description, type = $this->type, status=$this->status
              WHERE id =$this->id;
    ";

    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  public function deleteFromDB()
  {
    global $dbconnect;

    foreach ($this->Tasks as $Task) {
      $Task->deleteFromDB();
    }

    $query = "DELETE FROM ax.ax_page_prep WHERE page_id = $this->id;";
    $query .= "DELETE FROM ax.ax_page_group WHERE page_id = $this->id;";

    $this->ColorTheme->deleteFromDB();

    $query .= "DELETE FROM ax.ax_page WHERE id = $this->id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  function getColorThemeSrcUrl()
  {
    return $this->ColorTheme->getSrcUrl();
  }

  // -- END WORK WITH PAGE



  // WORK WITH TASKS

  public function addTask($task_id)
  {
    $Task = new Task((int)$task_id);
    $this->pushTaskToDB($task_id);
    array_push($this->Tasks, $Task);
  }
  public function deleteTask($task_id)
  {
    $index = $this->findTaskById($task_id);
    if ($index != -1) {
      $this->Tasks[$index]->deleteFromDB();
      unset($this->Tasks[$index]);
      $this->Tasks = array_values($this->Tasks);
    }
  }
  private function findTaskById($task_id)
  {
    $index = 0;
    foreach ($this->Tasks as $Task) {
      if ($Task->id == $task_id)
        return $index;
      $index++;
    }
    return -1;
  }
  public function getTaskById($task_id)
  {
    foreach ($this->Tasks as $Task) {
      if ($Task->id == $task_id)
        return $Task;
    }
    return null;
  }
  public function hasUncheckedTasks($student_id)
  {
    foreach ($this->getTasks() as $Task) {
      if ($Task->isActive() && $Task->hasUncheckedAssignments($student_id))
        return true;
    }
    return false;
  }

  public function getActiveTasks()
  {
    $return_Tasks = array();
    foreach ($this->getTasks() as $Task) {
      if ($Task->isActive())
        array_push($return_Tasks, $Task);
    }
    return $return_Tasks;
  }
  public function getActiveTasksWithConversation()
  {
    $return_Tasks = array();
    foreach ($this->Tasks as $Task) {
      if ($Task->isActive())
        array_push($return_Tasks, $Task);
    }
    return $return_Tasks;
  }
  public function getArchivedTasks()
  {
    $return_Tasks = array();
    foreach ($this->Tasks as $Task) {
      if ($Task->isArchived())
        array_push($return_Tasks, $Task);
    }
    return $return_Tasks;
  }


  public function getCountCompletedAssignments($student_id)
  {
    $count_success = 0;
    foreach ($this->getTasks() as $Task) {
      $count_success += $Task->getCountCompletedAssignments($student_id);
    }
    return $count_success;
  }
  public function getCountActiveAssignments($student_id)
  {
    $count = 0;
    foreach ($this->getTasks() as $Task) {
      if ($Task->status == 1) {
        $count += count($Task->getVisibleAssignmemntsByStudent($student_id));
      }
    }
    return $count;
  }

  function pushTaskToDB($task_id)
  {
    global $dbconnect;

    $query = "UPDATE ax.ax_task SET page_id = $this->id WHERE id = $task_id";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  // -- END WORK WITH TASKS



  // WORK WITH GROUPS

  public function addGroup($group_id)
  {
    $Group = new Group((int)$group_id);
    $this->pushGroupToPageDB($group_id);
    array_push($this->Groups, $Group);
  }
  public function deleteGroup($group_id)
  {
    $index = $this->findGroupById($group_id);
    if ($index != -1) {
      $this->deleteGroupFromPageDB($group_id);
      unset($this->Groups[$index]);
      $this->Groups = array_values($this->Groups);
    }
  }
  private function findGroupById($group_id)
  {
    $index = 0;
    foreach ($this->Groups as $Group) {
      if ($Group->id == $group_id)
        return $index;
      $index++;
    }
    return -1;
  }
  public function getGroupById($group_id)
  {
    foreach ($this->Groups as $Group) {
      if ($Group->id == $group_id)
        return $Group;
    }
    return null;
  }

  public function pushGroupToPageDB($group_id)
  {
    global $dbconnect;

    $query = "INSERT INTO ax.ax_page_group(page_id, group_id)
              VALUES ($this->id, $group_id)";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  public function deleteGroupFromPageDB($group_id)
  {
    global $dbconnect;

    $query = "DELETE FROM ax.ax_page_group WHERE group_id = $group_id";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  public function synchGroupsToPageDB()
  {
    global $dbconnect;

    $this->deleteGroupsFromPageDB();

    if (!empty($this->Groups)) {
      $query = "";
      foreach ($this->Groups as $Group) {
        $query .= "INSERT INTO ax.ax_page_group (page_id, group_id) VALUES ($this->id, $Group->id);";
      }
      pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    }
  }
  public function deleteGroupsFromPageDB()
  {
    global $dbconnect;

    $query = "DELETE FROM ax.ax_page_group WHERE page_id = $this->id";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  // -- END WORK WITH GROUPS




  // WORK WITH TEACHERS

  public function addTeacher($teacher_id)
  {
    $Teacher = new User((int)$teacher_id);
    $this->pushTeacherToPageDB($teacher_id);
    array_push($this->Teachers, $Teacher);
  }
  public function deleteTeacher($teacher_id)
  {
    $index = $this->findTeacherById($teacher_id);
    if ($index != -1) {
      $this->deleteTeacherFromPageDB($teacher_id);
      unset($this->Teachers[$index]);
      $this->Teachers = array_values($this->Teachers);
    }
  }
  private function findTeacherById($teacher_id)
  {
    $index = 0;
    foreach ($this->Teachers as $Teacher) {
      if ($Teacher->id == $teacher_id)
        return $index;
      $index++;
    }
    return -1;
  }
  public function getTeacherById($teacher_id)
  {
    foreach ($this->Teachers as $Teacher) {
      if ($Teacher->id == $teacher_id)
        return $Teacher;
    }
    return null;
  }

  public function pushTeacherToPageDB($teacher_id)
  {
    global $dbconnect;

    $query = "INSERT INTO ax.ax_page_prep(page_id, prep_user_id)
              VALUES ($this->id, $teacher_id)";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  public function deleteTeacherFromPageDB($teacher_id)
  {
    global $dbconnect;

    $query = "DELETE FROM ax.ax_page_prep WHERE prep_user_id = $teacher_id";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  public function synchTeachersToPageDB()
  {
    global $dbconnect;

    $this->deleteTeachersFromPageDB();

    if (!empty($this->Teachers)) {
      $query = "";
      foreach ($this->Teachers as $Teacher) {
        $query .= "INSERT INTO ax.ax_page_prep (page_id, prep_user_id) VALUES ($this->id, $Teacher->id);";
      }
      pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    }
  }
  public function deleteTeachersFromPageDB()
  {
    global $dbconnect;

    $query = "DELETE FROM ax.ax_page_prep WHERE page_id = $this->id";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  // -- END WORK WITH TEACHERS




  // ELSE

  public function getSemesterName()
  {
    if ($this->semester == 1)
      return 'Осень';
    return 'Весна';
  }

  public function createGeneralConversation($group_ids = null)
  {
    $conversationTask = new Task($this->id, 2, 1);
    $conversationTask->setTitle("ОБЩАЯ БЕСЕДА");
    $Students = array();
    if ($group_ids == null) {
      foreach ($this->getGroups() as $Group) {
        foreach ($Group->getStudents() as $Student) {
          array_push($Students, $Student);
        }
      }
    } else {
      foreach ($group_ids as $group_id) {
        $Group = new Group((int)$group_id);
        foreach ($Group->getStudents() as $Student) {
          array_push($Students, $Student);
        }
      }
    }
    $conversationTask->createConversationAssignment($Students);
    $this->addTask($conversationTask->id);
    return $conversationTask;
  }
  public function getConversationTask()
  {
    foreach ($this->Tasks as $Task) {
      if ($Task->isConversation())
        return $Task;
    }
    return null;
  }
}


// TODO: Протестировать!
function getTasksByPage($page_id)
{
  global $dbconnect;

  $tasks = array();

  $query = queryGetTasksByPage($page_id);
  $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

  while ($task_row = pg_fetch_assoc($result)) {
    array_push($tasks, new Task((int)$task_row['id']));
  }

  return $tasks;
}

function getGroupsByPage($page_id)
{
  global $dbconnect;

  $groups = array();

  $query = queryGetGroupsByPage($page_id);
  $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

  while ($group_row = pg_fetch_assoc($result)) {
    array_push($groups, new Group((int)$group_row['id']));
  }

  return $groups;
}

function getTeachersByPage($page_id)
{
  global $dbconnect;

  $teachers = array();

  $query = queryGetTeachersByPage($page_id);
  $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

  while ($teacher_row = pg_fetch_assoc($result)) {
    array_push($teachers, new User((int)$teacher_row['id']));
  }

  return $teachers;
}


function getSemesterNumberByGroup($page_year, $page_semester, $group_year)
{
  return 2 * ((int)$page_year - $group_year) + $page_semester;
}


function getPageByAssignment($assignment_id)
{
  global $dbconnect;

  $query = queryGetPageByAssignment($assignment_id);
  $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  $page_id = pg_fetch_assoc($result)['page_id'];

  return $page_id;
}

function getPageByTask($task_id)
{
  global $dbconnect;

  $query = queryGetPageByTask($task_id);
  $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  $page_id = pg_fetch_assoc($result)['page_id'];

  return $page_id;
}




// ФУНКЦИИ ЗАПРОСОВ К БД 

function queryGetPageInfo($page_id)
{
  return "SELECT p.*, d.name as disc_name
          FROM ax.ax_page as p
          LEFT JOIN discipline d ON d.id = p.disc_id
          WHERE p.id = $page_id;
  ";
}

function queryGetAllPagesByGroup($group_id)
{
  return "SELECT p.id FROM ax.ax_page p
          INNER JOIN ax.ax_page_group ON ax.ax_page_group.page_id = p.id
          WHERE ax.ax_page_group.group_id = $group_id
          ORDER BY p.year DESC, p.semester DESC";
}

function queryGetPageByTask($task_id)
{
  return "SELECT page_id FROM ax.ax_task WHERE id =$task_id;
  ";
}
function queryGetPageByAssignment($assignment_id)
{
  return "SELECT page_id FROM ax.ax_task WHERE ax.ax_task.id = (SELECT task_id FROM ax.ax_assignment WHERE ax.ax_assignment.id = $assignment_id)";
}

function queryGetTasksByPage($page_id)
{
  return "SELECT id FROM ax.ax_task WHERE page_id = $page_id ORDER BY id";
}

function queryGetGroupsByPage($page_id)
{
  return "SELECT ax.ax_page_group.group_id as id FROM ax.ax_page_group 
          INNER JOIN groups ON groups.id = ax.ax_page_group.group_id 
          WHERE page_id = $page_id ORDER BY groups.name";
}

function queryGetTeachersByPage($page_id)
{
  return "SELECT ax.ax_page_prep.prep_user_id as id FROM ax.ax_page_prep WHERE page_id = $page_id ORDER BY prep_user_id";
}

function querySetDiscId($page_id, $disc_id)
{
  return "UPDATE ax.ax_page SET disc_id = $disc_id WHERE id = $page_id;
  SELECT name FROM discipline WHERE id = $disc_id;";
}

function queryGetPagesByTeacher($teacher_id)
{
  return "SELECT p.id FROM ax.ax_page p
          INNER JOIN ax.ax_page_prep ON ax.ax_page_prep.page_id = p.id
          WHERE ax.ax_page_prep.prep_user_id = $teacher_id
  ";
}

function queryGetAllPages()
{
  return "SELECT ax.ax_page.id FROM ax.ax_page";
}


function queryInsertPage($Page)
{

  $disc_sql = "";
  if ($Page->disc_id == null) {
    $disc_sql = "null";
  } else {
    $disc_sql = "$Page->disc_id";
  }

  $name_sql = "";
  if ($Page->name == null) {
    $name_sql = "null";
  } else {
    $name_sql = "\$antihype1\$$Page->name\$antihype1\$";
  }

  $year_sql = "";
  if ($Page->year == null) {
    $year_sql = "null";
  } else {
    $year_sql = "$Page->year";
  }

  $semester_sql = "";
  if ($Page->semester == null) {
    $semester_sql = "null";
  } else {
    $semester_sql = "$Page->semester";
  }

  $color_theme_id = $Page->ColorTheme->id;

  $description_sql = "";
  if ($Page->description == null) {
    $description_sql = "null";
  } else {
    $description_sql = "\$antihype1\$$Page->description\$antihype1\$";
  }

  return "INSERT INTO ax.ax_page (disc_id, short_name, year, semester, color_theme_id, 
            creator_id, creation_date, description, type, status) 
            VALUES ($disc_sql, $name_sql, $year_sql, $semester_sql, $color_theme_id, 
            $Page->creator_id, now(), $description_sql, $Page->type, $Page->status) 
            RETURNING id;";
}

function queryInsertMainPage($Page)
{
  $name_sql = "";
  if ($Page->name == null) {
    $name_sql = "null";
  } else {
    $name_sql = addslashes("\$antihype1\$$Page->name (copy)\$antihype1\$");
  }

  $year_sql = "";
  if ($Page->year == null) {
    $year_sql = "null";
  } else {
    $year_sql = "$Page->year";
  }

  $semester_sql = "";
  if ($Page->semester == null) {
    $semester_sql = "null";
  } else {
    $semester_sql = "$Page->semester";
  }

  $description_sql = "";
  if ($Page->description == null) {
    $description_sql = "null";
  } else {
    $description_sql = "\$antihype1\$$Page->description\$antihype1\$";
  }

  return "INSERT INTO ax.ax_page (disc_id, short_name, year, semester, color_theme_id, 
  creator_id, creation_date, description, type, status)
  VALUES (null, $name_sql, $year_sql, $semester_sql, 0, $Page->creator_id, now(), $description_sql, $Page->type, $Page->status) 
  RETURNING id INTO pageId;";
}

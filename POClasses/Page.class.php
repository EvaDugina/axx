<?php 
require_once("./settings.php");

require_once("Task.class.php");
require_once("User.class.php");
require_once("Group.class.php");


class Page {

  public $id;
  public $disc_id;
  public $name, $year, $semester;
  public $color_theme_id;
  public $creator_id, $creation_date;
  public $status;
  
  public $Tasks = array(); // Массив Task
  public $Groups = array(); // Массив Group
  public $Teachers = array(); // Массив User

  function __construct($page_id) {
    global $dbconnect;

    $count_args = func_num_args();
    $args = func_get_args();

    // Перегружаем конструктор по количеству подданых параметров

    if ($count_args == 1 && is_int($args[0])) {
      $this->id = $args[0];

      $query = queryGetPageInfo($this->id);
      $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
      $page = pg_fetch_assoc($result);

      $this->disc_id = $page['disc_id'];
      
      $this->name = $page['short_name'];
      $this->year = $page['year'];
      $this->semester = $page['semster'];

      $this->color_theme_id = $page['color_theme_id'];
      
      $this->creator_id = $page['creator_id'];
      $this->creation_date = $page['creation_date'];
      
      // $this->src_url = $page['src_url'];
      $this->status = $page['status'];

      $this->Tasks = getTasksByPage($this->id);
      $this->Groups = getGroupsByPage($this->id);
      $this->Teachers = getTeachersByPage($this->id);
    }

    else if($count_args == 8) {

      $this->disc_id = $args[0];

      $this->name = $args[1];
      $this->year = $args[2];
      $this->semester = $args[3];

      $this->color_theme_id = $args[4];
      $this->creator_id = $args[5];
      $this->creation_date = $args[6];
      $this->status = $args[7];

      $this->pushNewToDB();

    }

    else {
      die('Неверные аргументы в конструкторе');
    }

  }


  public function pushNewToDB() {
    global $dbconnect;

    $query = "INSERT INTO ax_page (disc_id, short_name, year, semester, color_theme_id, 
              creator_id, creation_date, status) 
              VALUES ($this->disc_id, '$this->name', $this->year, $this->semester, $this->color_theme_id, 
              $this->creator_id, '$this->creation_date', $this->status) 
              RETURNING id";

    $pg_query = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    $result = pg_fetch_assoc($pg_query);

    $this->id = $result['id'];
  }
  public function pushPageChangesToDB() {
    global $dbconnect;

    $query = "UPDATE ax_page SET short_name ='$this->name', disc_id=$this->disc_id, year=$this->year, semester=$this->semester,
              color_theme_id=$this->color_theme_id, creator_id=$this->creator_id, creation_date='$this->creation_date', 
              status=$this->status
              WHERE id =$this->id;
    ";

    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  public function deleteFromDB() {
    global $dbconnect;

    foreach($this->Tasks as $Task) {
      $Task->deleteFromDB();
    }
    
    $query = "DELETE FROM ax_page_prep WHERE page_id = $this->id;";
    $query .= "DELETE FROM ax_page_group WHERE page_id = $this->id;";

    $query .= "DELETE FROM ax_page WHERE id = $this->id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  

// WORK WITH TASKS

public function addTask($task_id) {
  $Task = new Task($task_id);
  array_push($this->Tasks, $Task);
}
public function deleteTask($task_id) {
  $index = $this->findTaskById($task_id);
  if ($index != -1) {
    $this->Tasks[$index]->deleteFromDB();
    unset($this->Tasks[$index]);
  }
}
private function findTaskById($task_id) {
  $index = 0;
  foreach($this->Tasks as $Task) {
    if ($Task->getId() == $task_id)
      return $index;
    $index++;
  }
  return -1;
}

// -- END WORK WITH TASKS


// WORK WITH GROUPS

public function addGroup($group_id) {
  $Group = new Group($group_id);
  $this->pushGroupToPageDB($group_id);
  array_push($this->Groups, $Group);
}
public function deleteGroup($group_id) {
  $index = $this->findGroupById($group_id);
  if ($index != -1) {
    $this->deleteGroupFromPageDB($group_id);
    unset($this->Groups[$index]);
  }
}
private function findGroupById($group_id) {
  $index = 0;
  foreach($this->Groups as $Student) {
    if ($Student->getId() == $group_id)
      return $index;
    $index++;
  }
  return -1;
}

public function pushGroupToPageDB($group_id) {
  global $dbconnect;

  $query = "INSERT INTO ax_page_group(page_id, group_id)
            VALUES ($this->id, $group_id)";
  pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
}
public function deleteGroupFromPageDB($group_id) {
  global $dbconnect;

  $query = "DELETE FROM ax_page_group WHERE group_id = $group_id";
  pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
}
public function pushGroupsToPageDB() {
  global $dbconnect;

  $query = "";
    if (!empty($this->Groups)) {
      foreach($this->Groups as $Group) {
        $query .= "INSERT INTO ax_page_group (page_id, group_id) VALUES ($this->id, $Group->id);";
      }
    }
    
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
}
public function deleteGroupsFromPageDB() {
  global $dbconnect;

  $query = "DELETE FROM ax_page_group WHERE page_id = $this->id";
  pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
}

// -- END WORK WITH GROUPS

// WORK WITH TEACHERS



public function pushTeacherToPageDB() {

}

// -- END WORK WITH TEACHERS







  public function getTaskById($task_id) {
    foreach($this->Tasks as $Task) {
      if ($Task->getId == $task_id)
        return $Task;
    }
    return null;
  }

  public function getStudentsByGroupId() {
    // TODO
  }

  public function getStudents() {
    // TODO
  }

  public function getSemesterName() {
    if ($this->semester == 1)
      return 'Осень';
    return 'Весна';
  }

  //
  public function getSemesterNumber() {
    // TODO: Реализовать возвращение порядкового номера семестра (от 1 до 8)
    /*if ($this->semester == 1)
      return 2*((int)date('Y')-(int)$this->year + 1);
    return 2*((int)date('Y')-(int)$this->year + 1)-1;*/
  }


}


// TODO: Протестировать!
function getTasksByPage($page_id) {
  global $dbconnect;

  $tasks = array();

  $query = queryGetTasksByPage($page_id);
  $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

  while($task_row = pg_fetch_assoc($result)) {
    array_push($tasks, new Task($task_row['row']));
  }

  return $tasks;
}

function getGroupsByPage($page_id) {
  global $dbconnect;

  $groups = array();

  $query = queryGetGroupsByPage($page_id);
  $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

  while($group_row = pg_fetch_assoc($result)){
    array_push($tasks, new Group($group_row['id']));
  }

  return $groups;
}

function getTeachersByPage($page_id) {
  global $dbconnect;

  $teachers = array();

  $query = queryGetTeachersByPage($page_id);
  $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

  while($teacher_row = pg_fetch_assoc($result)){
    array_push($tasks, new User($teacher_row['id']));
  }

  return $teachers;
}




// ФУНКЦИИ ЗАПРОСОВ К БД 

function queryGetPageInfo($page_id) {
  return "SELECT p.*, ax_ct.bg_color, ax_ct.src_url, d.name as disc_name
          FROM ax_page as p
          INNER JOIN ax_color_theme ax_ct ON ax_ct.id = p.color_theme_id
          INNER JOIN discipline d ON d.id = p.disc_id
          WHERE p.id = $page_id;
  ";
}

function queryGetTasksByPage($page_id) {
  return "SELECT ax_task.id as id FROM ax_task WHERE page_id = $page_id ORDER BY id";
} 

function queryGetGroupsByPage($page_id) {
  return "SELECT ax_page_group.group_id as id FROM ax_page_group WHERE page_id = $page_id ORDER BY group_id";
} 

function queryGetTeachersByPage($page_id) {
  return "SELECT ax_page_prep.prep_user_id as id FROM ax_page_prep WHERE page_id = $page_id ORDER BY prep_user_id";
} 


function querySetColorThemeId($page_id, $color_theme_id) {
  return "UPDATE ax_page SET color_theme_id = $color_theme_id WHERE id = $page_id;
          SELECT src_url FROM ax_color_theme WHERE id = $color_theme_id;";
}

function querySetDiscId($page_id, $disc_id) {
  return "UPDATE ax_page SET disc_id = $disc_id WHERE id = $page_id;
  SELECT name FROM discipline WHERE id = $disc_id;";
}

function queryCreateColorTheme($src_url) {
  return "INSERT INTO ax_color_theme (disc_id, name, src_url)
          VALUES (null, null, $src_url) RETURNING id;
  ";
}



?>
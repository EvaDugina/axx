<?php 
require_once("./settings.php");

require_once("Task.class.php");
require_once("User.class.php");
require_once("Group.class.php");


class Page {

  private $id;
  private $name, $year, $semester;
  private $disc_name, $disc_id;
  private $color_theme_id, $status;
  private $creator_id, $creation_date;
  private $src_url;

  private $Tasks = array(); // Массив Task
  private $Groups = array(); // Массив Group
  private $Teachers = array(); // Массив User

  function __construct($page_id) {
    global $dbconnect;

    $this->id = $page_id;

    $query = queryGetPageInfo($this->id);
    $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    $page = pg_fetch_assoc($result);

    $this->name = $page['short_name'];
    $this->year = $page['year'];
    $this->semester = $page['semster'];

    $this->disc_id = $page['disc_id'];
    $this->disc_name = $page['disc_name'];

    $this->color_theme_id = $page['color_theme_id'];
    $this->status = $page['status'];

    $this->creator_id = $page['creator_id'];
    $this->creation_date = $page['creation_date'];

    $this->src_url = $page['src_url'];


    $this->Tasks = getTasksByPage($this->id);
    $this->Groups = getGroupsByPage($this->id);
    $this->Teachers = getTeachersByPage($this->id);

  }




  // GETTERS --------------------

  public function getId() {
    return $this->id;
  }

  public function getName() {
    return $this->name;
  }

  public function getYear() {
    return $this->year;
  }

  public function getSemester() {
    return $this->semester;
  }

  public function getDiscId() {
    return $this->disc_id;
  }

  public function getDiscName() {
    return $this->disc_name;
  }

  public function getStatus() {
    return $this->status;
  }

  public function getCreatorId() {
    return $this->creator_id;
  }
  
  public function getCreationDate() {
    return $this->creation_date;
  }

  public function getColorThemeId() {
    return $this->color_theme_id;
  }

  public function getSrcUrl() {
    return $this->src_url;
  }


  public function getTasks() {
    return $this->Tasks;
  }

  public function getGroups() {
    return $this->Groups;
  }

  public function getTeachers() {
    return $this->Teachers;
  }

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






  // SETTERS --------------------

  public function setName($name) {
    global $dbconnect;

    $this->name = $name;

    $query = "UPDATE ax_page SET short_name = '$this->name' WHERE id = $this->id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  public function setYear($year) {
    global $dbconnect;

    $this->year = $year;

    $query = "UPDATE ax_page SET year = $this->year WHERE id = $this->id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  public function setSemester($semester) {
    global $dbconnect;

    $this->semester = $semester;

    $query = "UPDATE ax_page SET semester = $this->semester WHERE id = $this->id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  // TODO: Протестировать!
  public function setDiscId($disc_id) {
    global $dbconnect;

    $this->disc_id = $disc_id;
    
    $query = querySetDiscId($this->id, $this->disc_id);
    $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

    $this->disc_name = pg_fetch_assoc($result)['name'];
  }

  public function setStatus($status) {
    global $dbconnect;

    $this->status = $status;

    $query = "UPDATE ax_page SET status = $this->status WHERE id = $this->id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  public function setCreatorId($user_id) {
    global $dbconnect;

    $this->creator_id = $user_id;

    $query = "UPDATE ax_page SET creator_id = $this->creator_id WHERE id = $this->id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  
  public function setCreationDate($creation_date) {
    global $dbconnect;

    $this->creation_date = $creation_date;

    $query = "UPDATE ax_page SET creation_date = $this->creation_date WHERE id = $this->id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  // TODO: Протестировать!
  public function setColorThemeId($color_theme_id) {
    global $dbconnect;

    $this->color_theme_id = $color_theme_id;

    $query = querySetColorThemeId($this->id, $this->color_theme_id);
    $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    
    $this->src_url = pg_fetch_assoc($result)['src_url'];
  } 
  
  // TODO: Протестировать!
  public function createAxColorTheme($src_url) {
    global $dbconnect;
    
    $query = queryCreateColorTheme($src_url);
    $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

    // Пользователь загрузил фотографию для дисциплины и мы её сразу же прикрепляем к странице
    $color_theme_id = pg_fetch_assoc($result)['id'];
    $this->setColorThemeId($color_theme_id);
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
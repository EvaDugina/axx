<?php
require_once("./settings.php");

class Group
{

  public $id;
  public $name, $year;
  public $type; // 0 - Бакалавриат, 1 - Магистратура, 2 - Аспирантура, 3 - Другое
  public $status; // "old" - выпущена, "active" - актуальная

  private $Students = array();


  function __construct()
  {
    global $dbconnect;

    $count_args = func_num_args();
    $args = func_get_args();

    // Перегружаем конструктор по количеству подданых параметров

    if ($count_args == 1) {
      $this->id = (int)$args[0];

      $query = queryGetGroupInfo($this->id);
      $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
      $group = pg_fetch_assoc($result);

      $group_name = $group['name'];
      $array = explode(" (", $group_name);

      if (strpos($array[0], "КМБ") !== false) {
        $this->type = 0;
      } else if (strpos($array[0], "КММ") !== false) {
        $this->type = 1;
      } else if (strpos($array[0], "Аспирант") !== false) {
        $this->type = 2;
      } else {
        $this->type = 3;
      }

      if (count($array) > 1 && strpos($array[1], "выпуск") !== false) {
        $this->status = "old";
      } else {
        $this->status = "active";
      }


      $this->name = $array[0];
      $this->year = $group['year'];

      $this->Students = getStudentsByGroup($this->id);
    } else {
      die('Неверные аргументы в конструкторе Group');
    }
  }


  public function getTextStatus()
  {
    if ($this->status == "old")
      return "Выпущена";
    return "Текущая";
  }

  public function getTextType()
  {
    if ($this->type == 0)
      return "Бакалавриат";
    else if ($this->type == 1)
      return "Магистратура";
    else if ($this->type == 2)
      return "Аспирантура";
    else
      return "Другое";
  }

  public function getStudents()
  {
    return $this->Students;
  }


  public function isOld()
  {
    if ($this->status == "old")
      return true;
    return false;
  }
  public function isElseType()
  {
    if ($this->type == 3)
      return true;
    return false;
  }
}


function getStudentsByGroup($group_id)
{
  global $dbconnect;

  $students = array();

  $query = queryGetStudentsByGroup($group_id);
  $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

  while ($student = pg_fetch_assoc($result)) {
    array_push($students, new User((int)$student['id']));
  }

  return $students;
}


function getAllGroups()
{
  global $dbconnect;

  $query = querySelectGroups();
  $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  $groups = pg_fetch_all($result);

  $Groups = [];
  foreach ($groups as $group) {
    array_push($Groups, new Group($group['id']));
  }

  return $Groups;
}


function querySelectGroups()
{
  return 'SELECT * FROM groups';
}

function queryGetGroupInfo($group_id)
{
  return "SELECT * FROM groups WHERE id = $group_id;";
}

function queryGetStudentsByGroup($group_id)
{
  return "SELECT student_id as id FROM students_to_groups WHERE group_id = $group_id;";
}

<?php 
require_once("../settings.php");
require_once("../dbqueries.php");
require_once("../utilities.php");

class Group {

  public $id;
  public $name, $year;

  private $Students = array();


  function __construct() {
    global $dbconnect;

    $count_args = func_num_args();
    $args = func_get_args();

    // Перегружаем конструктор по количеству подданых параметров

    if ($count_args == 1 && is_int($args[0])) {
      $this->id = $args[0];

      $query = queryGetGroupInfo($this->id);
      $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
      $group = pg_fetch_assoc($result);

      $this->name = $group['name'];
      $this->year = $group['year'];
      
      $this->Students = getStudentsByGroup($this->id);
    }

    else {
      die('Неверное число аргументов, или неверный id');
    }

  }

  public function getStudents() {
    return $this->Students;
  }

}


function getStudentsByGroup($group_id) {
  global $dbconnect;

  $students = array();

  $query = queryGetStudentsByGroup($group_id);
  $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  $students_id = pg_fetch_all($result);

  foreach($students_id as $student_id) {
    array_push($students, new User($student_id));
  }

  return $students;
}





function queryGetGroupInfo($group_id){
  return "SELECT * FROM groups WHERE id = $group_id;";
}

function queryGetStudentsByGroup($group_id) {
  return "SELECT student_id FROM students_to_groups WHERE group_id = $group_id;";
}

?>
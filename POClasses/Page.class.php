<?php 
require_once("../settings.php");
require_once("../dbqueries.php");
require_once("../utilities.php");

class Page {

  private $id;
  private $disc_name, $disc_id;
  private $name, $year, $semster;
  private $color_theme_id, $status;
  private $creator_id, $creation_date;

  private $Tasks; // Массив Task
  private $Groups; // Массив Group
  private $Teachers; // Массив User

  function __construct($page_id) {
    global $dbconnect;

    $query = getBasePageInfo($page_id);
    $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    $page = pg_fetch_assoc($result);



  }


  //TODO: getTaskById
  //TODO: getStudentsByGroupId
  //TODO: getStudents
  //TODO: getTeachers
  // and else...


}

function getBasePageInfo($page_id) {
  return "SELECT p.*, ax_ct.bg_color, ax_ct.src_url
          FROM ax_page as p
          INNER JOIN ax_color_theme ax_ct ON ax_ct.id = p.color_theme_id
          INNER JOIN discipline d ON d.id = p.disc_id
          WHERE p.id = $page_id;
  ";
}

function getGroupsByPage($page_id) {
  return "SELECT * FROM ax_page_group
          WHERE page_id = $page_id;
  ";
}

function getTasksByPage($page_id) {
  return "SELECT ax_task.id FROM ax_task 
          WHERE page_id = $page_id;
  ";
}

function getTeachersByPage($page_id) {

}

?>
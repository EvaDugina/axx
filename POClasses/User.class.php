<?php 
require_once("../settings.php");
require_once("../dbqueries.php");
require_once("../utilities.php");

class User {

  private $id; 
  private $first_name, $middle_name, $last_name;
  private $login, $role;
  private $group_id;
  private $email, $notify_status;

  
  function __construct($user_id) {
    global $dbconnect;

    $this->id = $user_id;

    $query = getUserInfo($this->id);
    $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    $user = pg_fetch_assoc($result);

    $this->first_name = $user['first_name'];
    $this->middle_name = $user['middle_name'];
    $this->last_name = $user['last_name'];

    $this->login = $user['login'];
    $this->role = $user['role'];

    $this->group_id = $user['group_id'];

    $this->email = $user['email'];
    $this->notify_status = $user['notification_type'];

  }


  // GETTERS

  public function getFI() {
    return $this->first_name + $this->middle_name;
  }

  public function getFIO() {
    return $this->first_name + $this->middle_name + $this->last_name; 
  }

  public function getLogin() {
    return $this->login;
  }

  public function getRole() {
    return $this->role;
  }

  public function getGroupId() {
    return $this->group_id;
  }

  public function getEmail() {
    return $this->email;
  }

  public function getNotifyStatus() {
    return $this->notify_status;
  }
  
  public function getNotifications() {
    // TODO: получить список уведомлений пользователя

  }


  // SETTERS

  public function setFIO($first_name, $middle_name, $last_name) {
    global $dbconnect;

    $this->first_name = $first_name;
    $this->middle_name = $middle_name;
    $this->last_name = $last_name;

    $query = "UPDATE students SET first_name = $first_name, middle_name = $middle_name, last_name = $last_name WHERE id = $this->id";
    $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  public function setLogin($login) {
    global $dbconnect;

    $this->login = $login;

    $query = "UPDATE students SET login = $login WHERE id = $this->id";
    $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  public function setGroupId($group_id) {
    global $dbconnect;

    $this->group_id = $group_id;

    $query = "UPDATE students_to_groups SET group_id = $group_id WHERE student_id = $this->id";
    $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  public function setEmail($email) {
    global $dbconnect;

    $this->email = $email;

    $query = "UPDATE ax_settings SET email = $email WHERE user_id = $this->id";
    $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  public function setNotificationStatus($notify_status) {
    global $dbconnect;

    $this->notify_status = $notify_status;

    $query = "UPDATE ax_settings SET notification_type = $notify_status WHERE user_id = $this->id";
    $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  
  
  
  
  
}


function getUserInfo($user_id){
  return "SELECT s.*, students_to_groups.group_id, ax_settings.email, ax_settings.notification_type, ax_settings.monaco_dark
          FROM students as s
          LEFT JOIN students_to_groups ON students_to_groups.student_id = s.id
          LEFT JOIN ax_settings ON ax_settings.user_id = s.id
          WHERE s.id = $user_id;
  ";
}


?>
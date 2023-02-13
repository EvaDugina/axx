<?php 
require_once("./settings.php");
require_once("./dbqueries.php");
require_once("./utilities.php");

class User {

  private $id; 
  private $first_name, $middle_name, $last_name;
  private $login, $role;
  private $group_id, $group_name;
  private $email, $notify_status;

  
  function __construct($user_id) {
    global $dbconnect;

    $this->id = $user_id;

    $query = queryGetUserInfo($this->id);
    $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    $user = pg_fetch_assoc($result);

    $this->first_name = $user['first_name'];
    $this->middle_name = $user['middle_name'];
    $this->last_name = $user['last_name'];

    $this->login = $user['login'];
    $this->role = $user['role'];

    $this->group_id = $user['group_id'];
    $this->group_name = $user['group_name'];

    $this->email = $user['email'];
    $this->notify_status = $user['notification_type'];

  }


  // GETTERS

  public function getFI() {
    if (empty($this->first_name))
      return $this->middle_name;
    else
      return $this->first_name + " " + $this->middle_name;
  }

  public function getFIO() {
    if (empty($this->first_name) && empty($this->middle_name))
      return $this->last_name;
    if (empty($this->first_name))
        return $this->middle_name . " " . $this->last_name;
    if (empty($this->middle_name))
      return $this->first_name . " " . $this->last_name;
    return $this->first_name . " " . $this->middle_name . " " . $this->last_name; 
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
  
  public function getGroupName() {
    return $this->group_name;
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
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  public function setLogin($login) {
    global $dbconnect;

    $this->login = $login;

    $query = "UPDATE students SET login = $login WHERE id = $this->id";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  public function setGroupId($group_id) {
    global $dbconnect;

    $this->group_id = $group_id;

    $query = "UPDATE students_to_groups SET group_id = $group_id WHERE student_id = $this->id";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  public function setEmail($email) {
    global $dbconnect;

    if($this->email != $email) {
      $this->email = $email;

      $query = querySetEmail($this->id, $this->email);
      pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    }
  }

  public function setNotificationStatus($notify_status) {
    global $dbconnect;

    if (intval($this->notify_status) != intval($notify_status)) {
      $this->notify_status = $notify_status;

      $query = querySetNotifyStatus($this->id, $this->notify_status);
      pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    }
  }

  
  
  
  
  
}


function queryGetUserInfo($id){
  return "SELECT first_name, middle_name, last_name, login, role, groups.id as group_id,
      groups.name as group_name, ax_settings.email, ax_settings.notification_type, ax_settings.monaco_dark
      FROM students
      LEFT JOIN students_to_groups ON students_to_groups.student_id = students.id
      LEFT JOIN groups ON groups.id = students_to_groups.group_id
      LEFT JOIN ax_settings ON ax_settings.user_id = students.id
      WHERE students.id = $id;
  ";
}

function querySetNotifyStatus($id, $notify_type) {
  return "INSERT INTO ax_settings (user_id, email, notification_type, monaco_dark) 
          VALUES ($id, null, $notify_type, 'TRUE')
          ON CONFLICT (user_id) DO UPDATE 
          SET notification_type = $notify_type;
  ";
}

function querySetEmail($id, $email) {
  return "INSERT INTO ax_settings (user_id, email, notification_type, monaco_dark) 
      VALUES ('$id', '$email', null, 'TRUE')
      ON CONFLICT (user_id) DO UPDATE
      SET email = '$email';
  ";
}

?>
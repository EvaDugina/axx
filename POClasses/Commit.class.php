<?php 
require_once("../settings.php");
require_once("../dbqueries.php");
require_once("../utilities.php");

class Commit {

  private $id;
  private $session_id, $student_user_id, $type, $auto_test_result;
  private $comment;
  
  private $Files = array();

  
// GETTERS:

public function getId(){
  return $this->id;
}

}

?>
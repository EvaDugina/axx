<?php 
require_once("../settings.php");
require_once("../dbqueries.php");
require_once("../utilities.php");

class Assignment {

  private $id;
  private $variant_comment;
  private $start_limit, $finish_limit;
  private $status_code, $status_text;
  private $delay; // не понятно, зачем нужно
  private $mark;
  private $checks;

  private $Students;
  private $Messages;
  private $Commits;
  
  
}

?>
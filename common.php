<?php
session_start();

require_once("settings.php");
require_once("dbqueries.php");
require_once("messageHandler.php");

$pageurl = explode('/', $_SERVER['REQUEST_URI']);
$pageurl = $pageurl[count($pageurl) - 1];
$_SESSION['username'] = '';

if ($pageurl != 'login.php') {
  include_once('auth_ssh.class.php');
  $au = new auth_ssh();  
  if (!$au->loggedIn()) {
    header('Location:login.php');
    exit;
  }
  else {
    $query = get_user_name($au->getUserId());
    $result = pg_query($query);
    if ($row = pg_fetch_assoc($result))
      $_SESSION['username'] = $row['first_name'];
      if (isset($row['middle_name']))
        $_SESSION .= " " . $row['middle_name'];
  }
} 

function show_breadcrumbs(&$breadcrumbs) {
  if (count($breadcrumbs) < 1)
    return; 
?>
  <ul class="navbar-nav me-auto mb-2 mb-lg-0">
    <div class="container-fluid">
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
          <?php foreach($breadcrumbs as $name => $link) {?>
            <li class="" style="font-size: 1.10rem; padding-left: 20px; padding-right: 20px; border-left: 1px solid;">
              <a class="text-reset" href="<?php echo $link; ?>"><?php echo $name ?></a>
            </li>
          <?php 
          } ?>
        </ol>
      </nav>
    </div>
  </ul>
<?php
} 
function show_head($page_title = '', $js = array(), $css = array())
{ 
?>
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />

    <title>536 Акселератор - <?=$page_title?></title>

    <!-- MDB icon -->
    <link rel="icon" href="src/img/mdb-favicon.ico" type="image/x-icon" />

    <!-- Fonts & Icons -->
    <link rel="stylesheet" type="text/css" href="src/fonts-icons/all.css"/>
    <link rel="stylesheet" href="src/fonts-icons/font-awesome.min.css"/>
    <script src="https://kit.fontawesome.com/b9b9878a35.js" crossorigin="anonymous"></script>

    <!-- Extra -->
    <link rel="stylesheet" href="css/accelerator.css" /> 
    
    <!-- MDB -->
    <link rel="stylesheet" href="css/mdb/mdb.min.css" />
    <script type="text/javascript" src="js/mdb.min.js"></script>

    <!-- jQuery -->
    <script type="text/javascript" src="js/jquery/jquery-3.5.1.min.js"></script>

	<!-- Page-specific JS/CSS -->
<?php 
    foreach($js as $url) {
?>
    <script type="text/javascript" src="<?=$url?>"></script>
<?php 
    } 
?>
<?php 
    foreach($css as $url) {
?>
    <link rel="stylesheet" href="<?=$url?>"/>
<?php 
    } 
?>
  </head>
<?php 
} 

function show_header($dbconnect, $page_title = '', $breadcrumbs = array()) { 
?>
  <header>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg bg-warning navbar-light">
      <!-- Container wrapper -->
      <div class="container-fluid">
        <!-- Navbar brand -->
        <a class="navbar-brand" href="index.php"><b>536 Акселератор</b></a>

        <!-- Toggle button -->
        <button
          class="navbar-toggler"
          type="button"
          data-mdb-toggle="collapse"
          data-mdb-target="#navbarSupportedContent"
          aria-controls="navbarSupportedContent"
          aria-expanded="false"
          aria-label="Toggle navigation">
          <i class="fas fa-bars"></i>
        </button>

        <!-- Collapsible wrapper -->
        <div class="collapse navbar-collapse row" id="navbarSupportedContent">
          <div class="d-none d-sm-block col-sm-8 col-md-8 col-xl-10">

<?php
          show_breadcrumbs($breadcrumbs);
          if (count($breadcrumbs) < 1) echo '</div>';

          if ($page_title != "Вход в систему"){ 

            if (array_key_exists('username', $_SESSION) && $_SESSION['username'] != '') {

              // Подгрузка уведомления для разных групп пользователей
              $au = new auth_ssh();
              $array_notify = array();
              if ($au->isAdmin());
              else if ($au->isTeacher()) {
                $query_undone_tasks = select_notify_for_teacher_header($_SESSION['hash']);
                $result_undone_tasks = pg_query($dbconnect, $query_undone_tasks);
                $array_notify = pg_fetch_all($result_undone_tasks);
              }
              else {
                $query_undone_tasks = select_notify_for_student_header($_SESSION['hash']);
                $result_undone_tasks = pg_query($dbconnect, $query_undone_tasks);
                $array_notify = pg_fetch_all($result_undone_tasks);
              }

            } 
?>
            </div>

            <div class="col-xs-12 col-sm-4 col-md-4 col-xl-2 d-flex flex-row align-items-center justify-content-end">
              <!-- Icons -->
              <ul class="navbar-nav me-1">
                <!-- Notifications -->
                <a class="text-reset me-3 dropdown-toggle hidden-arrow" href="#" id="navbarDropdownMenuLink1" role="button" data-mdb-toggle="dropdown" aria-expanded="false">
                  <i class="fas fa-bell fa-lg"></i>
                  <span class="badge rounded-pill badge-notification <?php if(!$array_notify || ($array_notify && count($array_notify) < 1)) echo 'd-none';?>" 
                  style="background: #dc3545;"><?php if($array_notify) echo count($array_notify);?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownMenuLink1" style="z-index:99999999; ">
                  <?php $i = 0;
                  if ($array_notify){
                    foreach ($array_notify as $notify) { $i++; 
                      if($au->isTeacher()){
                        $query = select_count_unreaded_messages_by_task_for_teacher($notify['student_user_id'], $notify['task_id']);
                      } else {
                        $query = select_count_unreaded_messages_by_task_for_student($_SESSION['hash'], $notify['task_id']);
                      }
                      $result = pg_query($query);
                      $count_unreaded_messages_by_notify = pg_fetch_assoc($result);?>
                      <a <?php 
                      if($au->isTeacher()){ echo 'style="color: black;"';?>
                        href="taskchat.php?task=<?php echo $notify['task_id']?>&page=<?php echo $notify['page_id'];?>&id_student=<?php echo $notify['student_user_id'];?>" > 
                      <?php
                      } else if ($au->isAdmin());
                      else {?> 
                        href="taskchat.php?task=<?=$notify['task_id']?>&page=<?=$notify['page_id'];?>" > 
                      <?php } ?>
                          <li class="dropdown-item" <?php if($i != count($array_notify)) echo 'style="border-bottom: 1px solid;"'?>>
                            <div class="d-flex justify-content-between align-items-center">
                              <div style="margin-right: 10px;">
                                <?php if ($au->isTeacher()) {
                                  echo '<span style="border-bottom: 1px solid;">'. $notify['middle_name']. " " .$notify['first_name']. " (". $notify['short_name']. ")" .'</span>';?>
                                  <br><?php echo $notify['title'];
                                } else {
                                  echo '<span style="border-bottom: 1px solid;">'.$notify['short_name'] .'</span>';?><br><?php echo $notify['title']; 
                                }?>
                              </div>
                              <span class="badge badge-primary badge-pill"
                                <?php if ($au->isTeacher() && $notify['status_code'] == 5) {?> 
                                  style="background: #dc3545; color: white;"
                                <?php }?>><?=$count_unreaded_messages_by_notify['count']?>
                              </span>
                            </div>
                          </li>
                      </a>
                    <?php }
                  }?>
                </ul>
              </ul>

              <ul class="navbar-nav d-flex flex-row me-1">
                <!-- Avatar -->
                <a class="dropdown-toggle d-flex align-items-center hidden-arrow text-reset" href="#" id="navbarDropdownMenuLink2" role="button" data-mdb-toggle="dropdown" aria-expanded="false">
                  <!-- <img src="img/user-24.png" class="rounded-circle" height="25" alt="" loading="lazy"/>--> 
                  <button type="button" class="btn btn-floating"><i class="fas fa-user-alt fa-lg"></i></button> <span class="text-reset ms-2"><?=$_SESSION['username']?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownMenuLink2" style="z-index:99999999; ">
                  <li><a class="dropdown-item" href="profile.php">Профиль</a></li>
                  <li><a class="dropdown-item" href="login.php?action=logout">Выйти</a></li>
                </ul>
              </ul>
            </div>
          <?php } 

          if (count($breadcrumbs) >= 1) echo '</div>'; ?>
      </div>

    </nav>
  </header>

<?php
} 

function show_footer() 
{ 
?> 
  <!-- MDB -->
  <script type="text/javascript" src="js/mdb.min.js"></script>

  <!-- Custom scripts -->
  <script type="text/javascript"></script>

    </body>
  </html>
<?php
} ?>

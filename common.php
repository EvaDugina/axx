<?php
session_start();

require_once("settings.php");
require_once("dbqueries.php");

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
    $_SESSION['username'] = $row['fio'];
  }
} ?>


<?php
function show_breadcrumbs(&$breadcrumbs) {
  if (count($breadcrumbs) < 1)
    return; ?>
  
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
} ?>


<?php
function show_head($page_title = ''){ ?>

  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />

    <title>536 Акселератор - <?=$page_title?></title>

    <!-- MDB icon -->
    <link rel="icon" href="img/mdb-favicon.ico" type="image/x-icon" />

    <!-- Fonts & Icons -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.2/css/all.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    
    <!-- Google Fonts Roboto -->
    <link rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap"/>

    <!-- extra -->
    <link rel="stylesheet" href="css/accelerator.css" /> 
    
    <!-- MDB -->
    <link rel="stylesheet" href="css/mdb.min.css" />
    <script type="text/javascript" src="js/mdb.min.js"></script>

    <!-- jQuery -->
    <script type="text/javascript" src="js/jquery-3.5.1.min.js"></script>

  </head>

<?php 
} ?>



<?php
function show_header_2($dbconnect, $page_title = '', $breadcrumbs = array()) { ?>
     
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
          aria-label="Toggle navigation"
        >
          <i class="fas fa-bars"></i>
        </button>

        <!-- Collapsible wrapper -->
        <div class="collapse navbar-collapse" id="navbarSupportedContent">

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

            } ?>
            
            <!-- Icons -->
            <ul class="navbar-nav d-flex flex-row me-1">
              <!-- Notifications -->
              <a class="text-reset me-3 dropdown-toggle hidden-arrow" href="#" id="navbarDropdownMenuLink1" role="button" data-mdb-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-bell fa-lg"></i>
                <span class="badge rounded-pill badge-notification bg-danger"><?php echo count($array_notify);?></span>
              </a>
              <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownMenuLink1" style="z-index:99999999; ">
                <?php $i=0;
                foreach ($array_notify as $notify) { $i++; ?>
                  <li class="dropdown-item" <?php if($i != count($array_notify)) echo 'style="border-bottom: 1px solid;"'?>> 
                    <a <?php 
                      if($au->isTeacher()){ echo 'style="color: black;"';?>
                        href="taskchat.php?task=<?php echo $notify['id']?>&page=<?php echo $notify['page_id'];?>&id_student=<?php echo $notify['student_user_id'];?>" > 
                      <?php
                      } else if ($au->isAdmin());
                      else {  
                        if($notify['status_code'] == 2) echo 'style="color: red;"';
                        else if($notify['status_code'] == 3) echo 'style="color: green;"';
                        else echo 'style="color: black;"';?>
                        href="taskchat.php?task=<?php echo $notify['id']?>&page=<?php echo $notify['page_id'];?>" > <?php // TODO: дать ссылку на чат
                      } ?>

                      <?php 
                      if ($au->isTeacher()) {
                        echo '<span style="border-bottom: 1px solid;">'. $notify['middle_name']. " " .$notify['first_name']. " (". $notify['short_name']. ")" .'</span>';?><br><?php echo $notify['title'];
                      } else {
                        echo '<span style="border-bottom: 1px solid;">'.$notify['short_name'] .'</span>';?><br><?php echo $notify['title']; 
                      }?>
                    </a>
                  </li>
                <?php }?>
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
          <?php } 

          if (count($breadcrumbs) >= 1) echo '</div>'; ?>


      </div>
    </nav>
  </header>

<?php
} ?>
  

  
<?php 
function show_only_header($page_title = '', $breadcrumbs = array()){?>

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
          aria-label="Toggle navigation"
        >
          <i class="fas fa-bars"></i>
        </button>

        <!-- Collapsible wrapper -->
        <div class="collapse navbar-collapse" id="navbarSupportedContent">

          <?php
          show_breadcrumbs($breadcrumbs);
          if (count($breadcrumbs) < 1) echo '</div>';
          if (array_key_exists('username', $_SESSION) && $_SESSION['username'] != '') {

            // Подгрузка уведомления для разных групп пользователей
            $au = new auth_ssh();
            if ($au->isAdmin());
            else if ($au->isTeacher()) {

            }
            else {
              // Подсчёт количества невыполненных заданий
              $count_succes_tasks = 0;
              $count_tasks = 0;

              $query_student_disciplines = select_all_disciplines();
              //$result_student_disciplines = pg_query($dbconnect, $query_student_disciplines);

              //while ($row_discipline = pg_fetch_assoc($result_student_disciplines)) {
                // сформировать запрос для не выполненных заданий со их названиями

              //}

            }

          } ?>

          <?php 
          if ($page_title != "Вход в систему"){ ?>
            <!-- Icons -->
            <ul class="navbar-nav d-flex flex-row me-1">
              <!-- Notifications -->
              <a class="text-reset me-3 dropdown-toggle hidden-arrow" href="#" id="navbarDropdownMenuLink1" role="button" data-mdb-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-bell fa-lg"></i>
                <span class="badge rounded-pill badge-notification bg-danger">4</span>
              </a>
              <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownMenuLink1">
                <li><a class="dropdown-item" href="#">Введение в РПО 21 - Руслан Одегов<br>Задание 4. Классы<button class="" type="button" style="float:right;line-height:12px;"><i class="fas fa-times"></i></button></a></li>
                <li><a class="dropdown-item" href="#">Введение в РПО 21 - Руслан Одегов<br>Задание 3. Классы<button class="" type="button" style="float:right;line-height:12px;"><i class="fas fa-times"></i></button></a></li>
                <li><a class="dropdown-item" href="#">Введение в РПО 21 - Руслан Одегов<br>Задание 2. Классы<button class="" type="button" style="float:right;line-height:12px;"><i class="fas fa-times"></i></button></a></li>
                <li><a class="dropdown-item" href="#">Введение в РПО 21 - Руслан Одегов<br>Задание 1. Классы<button class="" type="button" style="float:right;line-height:12px;"><i class="fas fa-times"></i></button></a></li>
              </ul>
            </ul>

            <ul class="navbar-nav d-flex flex-row me-1">
              <!-- Avatar -->
              <a class="dropdown-toggle d-flex align-items-center hidden-arrow text-reset" href="#" id="navbarDropdownMenuLink2" role="button" data-mdb-toggle="dropdown" aria-expanded="false">
                <!-- <img src="img/user-24.png" class="rounded-circle" height="25" alt="" loading="lazy"/>--> 
                <button type="button" class="btn btn-floating"><i class="fas fa-user-alt fa-lg"></i></button> <span class="text-reset ms-2"><?=$_SESSION['username']?></span>
              </a>
              <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownMenuLink2">
                <li><a class="dropdown-item" href="profile.php">Профиль</a></li>
                <li><a class="dropdown-item" href="login.php?action=logout">Выйти</a></li>
              </ul>
            </ul>
          <?php } 

          if (count($breadcrumbs) >= 1) echo '</div>'; ?>


      </div>
    </nav>
  </header>

<?php
} ?>


<?php // TODO: ИЗМЕНИТЬ НА РАБОТУ С ФУНКЦИЕЙ show_header_2
function show_header($page_title = '', $breadcrumbs = array()) { ?>

<!DOCTYPE html>
<html lang="en">

<?php 
show_head($page_title); ?>

<body style="overflow-x: hidden;">

  <?php
  show_only_header($page_title, $breadcrumbs); ?>

<?php
}?>

<?php
function show_footer() { ?> 
  <!-- MDB -->
  <script type="text/javascript" src="js/mdb.min.js"></script>

  <!-- Custom scripts -->
  <script type="text/javascript"></script>

    </body>
  </html>
<?php
} ?>
  
<?php 
function show_message($message) {
  if ($message == null || $message['mtype'] == null || $message['type'] < 2) return;
    $message_style = ($message['mtype'] == 1) ? 'message-prep' : 'message-stud';
    $message_text = $message['mtext'];
    if ($message['mfile'] != null) { // have file attachment
      // to be usefull image preview need: 1) scale to max message size 2) make big preview or link to big image and
      //if (preg_match('/\.(gif|png|jpg|jpeg|bmp|tif|tiff|ico|svg)$/i', $message['mfile'])) // file is image, inline it
      //    $message_text = "<img src='" . $message['murl'] . "' alt='" . $message['mfile']. "'/><br/>";
      //else 
      if ($message['murl'] != null) // file is attachment, add link to open in new window
          $message_text = "<a target='_blank' download href='" . $message['murl'] . "'><i class='fa fa-paperclip' aria-hidden='true'></i> " . $message['mfile']. "</a><br/>" . $message_text;
      else // file is text content, add link to open in new window
          $message_text = "<a target='_blank' download href='message_file.php?id=" . $message['fid'] . "'><i class='fa fa-paperclip' aria-hidden='true'></i> " . $message['mfile'] . "</a><br/>" . $message_text;
    }
    if ($message['mreply'] != null) // is reply message, add citing
      $message_text = "<p class='note note-light'>" . $message['mreply'] . "</p>" . $message_text;
    if ($message['amark'] == null && $message['mtype'] == 0 && $message['mstatus'] == 0) // is student message not viewed/answered, no mark, add buttons answer/mark
      $message_text = $message_text . "<br/><a href='javascript:answerPress(2," . $message['mid'] . "," . $message['max_mark'] . ")' type='message' class='btn btn-outline-primary'>Зачесть</a> <a href='javascript:answerPress(0," . $message['mid'] . ")' type='message' class='btn btn-outline-primary'>Ответить</a>";
    $time_date = explode(" ", $message['mtime']);
    $date = explode("-", $time_date[0]);
    $time = explode(":", $time_date[1]);
    $time_date_output = $date[0] .".". $date[1] ." ". $time[0] .":". $time[1]; ?>

    <div class="popover message <?=$message_style?>" role="listitem">
      <div class="popover-arrow"></div>
      <div class="p-3 popover-header">
        <h6 style="margin-bottom: 0px;" title="<?=$message['grp']. "\nЗадание: " . $message['task']?>">
          <?=$message['fio']. '<br>'?></h6>
        <p style="text-align: right; font-size: 8pt; margin-bottom: 0px;"><?=$time_date_output?></p>
      </div>
      <div class="popover-body"><?=$message_text?></div>
    </div>
<?php        
  } ?>

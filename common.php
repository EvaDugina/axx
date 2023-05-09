<?php
//session_start();

require_once("settings.php");
require_once("dbqueries.php");
require_once("messageHandler.php");
require_once("POClasses/User.class.php");

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
    $result = pg_query($dbconnect, $query);
    if ($row = pg_fetch_assoc($result))
      $_SESSION['username'] = $row['first_name'];
      if (isset($row['middle_name']))
        $_SESSION['username'] .= " " . $row['middle_name'];
  }
} 

function show_breadcrumbs(&$breadcrumbs) {
  if (count($breadcrumbs) < 1)
    return; 
?>
  <ul class="navbar-nav me-auto mb-2 mb-lg-0">
    <div class="container-fluid ps-2">
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
          <?php
          foreach($breadcrumbs as $name => $link) {?>
            <svg style="height: inherit;" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-arrow-right-short" viewBox="0 0 16 16">
              <path fill-rule="evenodd" d="M4 8a.5.5 0 0 1 .5-.5h5.793L8.146 5.354a.5.5 0 1 1 .708-.708l3 3a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708-.708L10.293 8.5H4.5A.5.5 0 0 1 4 8z"/>
            </svg>
            <li class="px-2" style="font-size: 1.10rem;">
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

    <title><?=$page_title?></title>

    <!-- MDB icon -->
    <link rel="icon" href="src/img/mdb-favicon.ico" type="image/x-icon" />

    <!-- Fonts & Icons -->
    <link rel="stylesheet" type="text/css" href="src/fonts-icons/all.css"/>
    <link rel="stylesheet" href="src/fonts-icons/font-awesome.min.css"/>
    <script src="https://kit.fontawesome.com/b9b9878a35.js" crossorigin="anonymous"></script>

    <!-- Extra -->
    <link rel="stylesheet" href="css/accelerator.css" /> 
    <link rel="stylesheet" href="css/styles.css" /> 
    
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

function show_header(/* [x]: Убрать */ $dbconnect, $page_title = '', $breadcrumbs = array(), $user = null) { 
?>
  <script type="text/javascript">
    $(document).ready(function() {
      $('main').css("margin-top", parseFloat($('#header').css("height")) + parseFloat($('main').css("margin-top")));
      $('main').addClass("pt-1");
    });
  </script>
  <header id="header" class="header header--fixed js-header is-show">
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
          <div class="d-none d-sm-block col-sm-8 col-md-8 col-xl-10 ps-0">

            <?php show_breadcrumbs($breadcrumbs);
            if (count($breadcrumbs) < 1) echo '</div>';

            if ($page_title != "Вход в систему") { 
              if ($user != null) {
                $array_notify = $user->getNotifications();
              }?>
          </div>

          <div class="col-xs-12 col-sm-4 col-md-4 col-xl-2 d-flex flex-row align-items-center justify-content-end">
            <!-- Icons -->
            <ul class="navbar-nav me-1">
              <!-- Notifications -->
              <a class="text-reset me-3 dropdown-toggle hidden-arrow" href="#" id="navbarDropdownMenuLink1" role="button" data-mdb-toggle="dropdown" aria-expanded="false">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bell-fill" viewBox="0 0 16 16">
                  <path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2zm.995-14.901a1 1 0 1 0-1.99 0A5.002 5.002 0 0 0 3 6c0 1.098-.5 6-2 7h14c-1.5-1-2-5.902-2-7 0-2.42-1.72-4.44-4.005-4.901z"/>
                </svg>
                <?php // FIXME: Скачет иконка уведомлений ?>
                <span class="badge rounded-pill badge-notification <?php if(!$array_notify || ($array_notify && count($array_notify) < 1)) echo 'd-none';?>" 
                style="background: #dc3545;">
                  <?php if($array_notify) echo count($array_notify);?>
                </span>
              </a>
              <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownMenuLink1" style="z-index:99999999; ">
                <?php $i = 0;
                foreach ($array_notify as $notify) { 
                  $i++;?>
                  <a href="taskchat.php?assignment=<?=$notify['assignment_id']?>"> 
                      <li class="dropdown-item" <?php if($i != count($array_notify)) echo 'style="border-bottom: 1px solid;"'?>>
                        <div class="d-flex justify-content-between align-items-center">
                          <div class="me-2">
                            <span style="border-bottom: 1px solid;">
                              <?php if ($user->isTeacher()) {
                                foreach($notify['students'] as $i => $Student) {?>
                                    <?=$Student->getFI()?> <?=($i + 1 < count($notify['students'])) ? "| " : ""?>
                                <?php 
                                } 
                              } else {?>
                                <?php foreach($notify['teachers'] as $i => $Teacher) {?>
                                  <?=$Teacher->getFIOspecial()?>  <?=($i + 1 < count($notify['teachers'])) ? "| " : ""?>
                                <?php }
                              }?>
                              (<?=$notify['page_name']?>)
                            </span>
                            <br><?php echo $notify['taskTitle']; ?>
                          </div>
                          <span class="badge badge-primary badge-pill"
                            <?php if ($user->isTeacher() && $notify['needToCheck']) {?> 
                              style="background: red; color: white;"
                            <?php } else if($user->isStudent() && $notify['completed']) {?>
                              style="background: green; color: white;"
                            <?php }?>
                          >
                            <?=$notify['countUnreaded']?>
                          </span>
                        </div>
                      </li>
                  </a>
                <?php }?>
              </ul>
            </ul>

            <ul class="navbar-nav d-flex flex-row me-1">
              <!-- Avatar -->
              <a class="dropdown-toggle d-flex align-items-center hidden-arrow text-reset" href="#" id="navbarDropdownMenuLink2" role="button" data-mdb-toggle="dropdown" aria-expanded="false">
                <button type="button" class="btn btn-floating shadow-none p-1">
                  <?php if($user != null && $user->getImageFile() != null) {?>
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="embed-responsive embed-responsive-1by1 text-center">
                                <div class="embed-responsive-item">
                                  <img class="w-100 h-100 p-0 m-0 rounded-circle user-icon"  src="<?=$user->getImageFile()->download_url?>"/>
                                </div>
                            </div>
                        </div>
                    </div>
                  <?php } else { ?>
                    <svg class="w-100 h-100" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-circle" viewBox="0 0 16 16">
                      <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                      <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/>
                    </svg>
                  <?php }?>
                </button> 
                <span class="text-reset ms-2">
                  <?php // [x]: убрать // TODO: Проверить
                  if($user != null) echo $user->getFIOspecial(); 
                  else echo $_SESSION['username'];?>
                </span>
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
} 
?>
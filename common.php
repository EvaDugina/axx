<?php
	session_start();
	
	require_once("settings.php");
  require_once("dbqueries.php");

  $pageurl = explode('/', $_SERVER['REQUEST_URI']);
  $pageurl = $pageurl[count($pageurl) - 1];
  $_SESSION['username'] = '';

  if ($pageurl != 'login.php')
  {
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
  }

	function show_breadcrumbs(&$breadcrumbs)
	{
		if (count($breadcrumbs) < 1)
			return;
		
		echo '<ul class="navbar-nav me-auto mb-2 mb-lg-0">';
		echo '<div class="container-fluid">';
		echo '<nav aria-label="breadcrumb">';
		echo '<ol class="breadcrumb">';
		foreach($breadcrumbs as $name => $link) {
			echo '<li class="breadcrumb-item">';
			echo '<a href="'.$link.'">'.$name.'</a>';
			echo '</li>';
		}
		echo '</ol>';
		echo '</nav>';
		echo '</div>';
		echo '</ul>';
	}

	function show_header($page_title = '', $breadcrumbs = array())
	{
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />
    <title>536 Акселератор - <?=$page_title?></title>
    <!-- MDB icon -->
    <link rel="icon" href="img/mdb-favicon.ico" type="image/x-icon" />
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.2/css/all.css" />
    <!-- Google Fonts Roboto -->
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap"
    />
    <!-- MDB -->
    <link rel="stylesheet" href="css/mdb.min.css" />
    <!-- extra -->
    <link rel="stylesheet" href="css/accelerator.css" />
  </head>
  <body>
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
    
    if (array_key_exists('username', $_SESSION) && $_SESSION['username'] != '') {
?>			
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
<?php 
    }
?>
          </div>
        </div>
        <!-- Container wrapper -->
      </nav>
      <!-- Navbar -->
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
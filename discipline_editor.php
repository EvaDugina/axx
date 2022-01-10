<!DOCTYPE html>

	<?php
		
		require_once("common.php");
		require_once("dbqueries.php");
		require_once("utilities.php");
		
		if (array_key_exists('page', $_REQUEST))
			$page_id = $_REQUEST['page'];
		else {
			echo "Некорректное обращение";
			http_response_code(400);
			exit;
		}
		
		$query = select_all_disciplines();
		$result = pg_query($dbconnect, $query);
		$disciplines = pg_fetch_all($result);
		
		$name = "";
		
		$query = select_discipline_page($page_id);
		$result = pg_query($dbconnect, $query);
		$page = pg_fetch_all($result)[0];
		
		
		foreach($disciplines as $key => $discipline){
			if($discipline['id'] == $page['disc_id'])
				$name = $discipline['name'];
		}
		
		$query = select_discipline_timestamps();
		$result = pg_query($dbconnect, $query);
		$timestamps = pg_fetch_all($result);
		
		#echo "<pre>";
		#var_dump($timestamps);
		#echo "</pre>";
	?>

<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />
    <title>536 Акселератор - Добавление/редактирование дисциплины</title>
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
  </head>
  <body>
    <header>
      <!-- Navbar -->
      <nav class="navbar navbar-expand-lg bg-warning navbar-light">
        <!-- Container wrapper -->
        <div class="container-fluid">
          <!-- Navbar brand -->
          <a class="navbar-brand" href="#"><b>536 Акселератор</b></a>

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

            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
              <!-- Link -->
              <li class="nav-item">Добавление/редактирование дисциплины</li>
            </ul>

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
                <button type="button" class="btn btn-floating"><i class="fas fa-user-alt fa-lg"></i></button> <span class="text-reset ms-2">Иван Сергеевич</span>
              </a>
              <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownMenuLink2">
                <li><a class="dropdown-item" href="#">Профиль</a></li>
                <li><a class="dropdown-item" href="#">Выйти</a></li>
              </ul>
            </ul>
          </div>
        </div>
        <!-- Container wrapper -->
      </nav>
      <!-- Navbar -->
    </header>




    <main class="pt-2">
	<form class="container-fluid overflow-hidden" action="page_edit.php" name="page_edit" id="page_edit" method = "post">
		
		
		<div class="row gy-5">
			<div class="col-12">
				<h2>Добавление/редактирование дисциплины</h2>
			</div>
		</div>
		<input type = "hidden" name = "disc_id" value = "<?=$page['disc_id']?>"></input>
		<input type = "hidden" name = "id" value = "<?=$page['id']?>"></input>
		
		<div class="row align-items-center m-3" style="height: 40px;">
			<div class="col-2 row justify-content-left">Полное название</div>
			<div class="col-4">
				<div class="btn-group shadow-0">
				  <button
					type="button"
					class="btn btn-light dropdown-toggle"
					data-mdb-toggle="dropdown"
					aria-expanded="false"
				  >
					<?=$name?>
				  </button>
				  <ul class="dropdown-menu">
					<?php
						foreach($disciplines as $discipline){
							echo "<li><a class='dropdown-item' href='#'>".$discipline['name']."</a></li>";
						}
					?>
				  </ul>
				</div>
			</div>
		</div>
		
		<div class="row align-items-center m-3" style="height: 40px;">
			<div class="col-2 row justify-content-left">Семестр</div>
			<div class="col-4">
				<div class="btn-group shadow-0">
					  <select class="form-select" name = "timestamp">
						<option selected>
							<?=$page['year']."/".convert_sem_from_id($page['semester'])?>
						</option>
						<?php
							foreach($timestamps as $timestamp){
								if($timestamp['year'] == $page['year'] and $timestamp['semester'] == $page['semester'])
									continue;
								echo "<option>".$timestamp['year']."/".convert_sem_from_id($timestamp['semester'])."</option>";
							}
						?>
					  </select>
				</div>
			</div>
		</div>
		
		<div class="row align-items-center m-3" style="height: 40px;">
			<div class="col-2 row justify-content-left">Краткое название</div>
			<div class="col-4">
				<div class="form-outline" style="width:250px;">
					<input type="text" id="form12" class="form-control" value = "<?=$page['short_name']?>" name = "short_name"/>
				</div>
			</div>
		</div>
		
		<div class="row align-items-center m-3" style="height: 40px;">
			<div class="col-2 row justify-content-left">Преподаватели</div>
			<div class="col-2">Иван Сергеевич<button type="button" class="btn-close" aria-label="Close"></button></div>
			<div class="col-1">
				<button type="button" class="btn btn-outline-primary" data-mdb-ripple-color="dark" style="width:120px;">
				  Добавить 
				</button>
			</div>
		</div>
		
		<div class="row align-items-center m-3" style="height: 40px;">
			<div class="col-2 row justify-content-left">Учебные группы</div>
			<div class="col-2">КММО-02-20<button type="button" class="btn-close" aria-label="Close"></button></div>
			<div class="col-1">
				<button type="button" class="btn btn-outline-primary" data-mdb-ripple-color="dark" style="width:120px;">
				  Добавить 
				</button>
			</div>
		</div>
		
		<div class="row align-items-left m-3" style="height: 40px;">
			<div class="col-2 row justify-content-left">Оформление</div>
			<div class="col-10">
				<button type="button" class="btn btn-outline-primary mx-2" data-mdb-ripple-color="dark" style="width:120px;">
				  Синий
				</button>
				<button type="button" class="btn btn-outline-secondary mx-2" data-mdb-ripple-color="dark" style="width:120px;">
				  Фиолетовый
				</button>
				<button type="button" class="btn btn-outline-success mx-2" data-mdb-ripple-color="dark" style="width:120px;">
				  Зеленый 
				</button>
				<button type="button" class="btn btn-outline-danger mx-2" data-mdb-ripple-color="dark" style="width:120px;">
				  Красный
				</button>
				<button type="button" class="btn btn-outline-warning mx-2" data-mdb-ripple-color="dark" style="width:120px;">
				  Желтый
				</button>
			</div>
		</div>
		
			<div class="row align-items-center mx-2" style="height: 40px;">
				<div class="col-2 row justify-content-left">
					<button type="submit" class="btn btn-outline-primary" data-mdb-ripple-color="dark" style="width:120px;">
						Сохранить
					</button>
				</div>
			</div>
	</form>	
    </main>
    <!-- End your project here-->

    <!-- MDB -->
    <script type="text/javascript" src="js/mdb.min.js"></script>
    <!-- Custom scripts -->
    <script type="text/javascript"></script>
  </body>
</html>

<!DOCTYPE html>

	<?php
		
		require_once("common.php");
		require_once("dbqueries.php");
		require_once("utilities.php");
		
		
		$id = 0;
		$name = "";
		$disc_id = 0;
		$semester = "";
		$short_name = "";

		$actual_teachers = [];
		$page_groups = [];
		
		$query = select_all_disciplines();
		$result = pg_query($dbconnect, $query);
		$disciplines = pg_fetch_all($result);
		
		$query = select_discipline_timestamps();
		$result = pg_query($dbconnect, $query);
		$timestamps = pg_fetch_all($result);
		
		$query = select_teacher_name();
		$result = pg_query($dbconnect, $query);
		$teachers = pg_fetch_all($result);
		
		$query = select_groups();
		$result = pg_query($dbconnect, $query);
		$groups = pg_fetch_all($result);
		
		if (array_key_exists('page', $_REQUEST)) {
			$page_id = $_REQUEST['page'];
			
			$query = select_discipline_page($page_id);
			$result = pg_query($dbconnect, $query);
			$page = pg_fetch_all($result)[0];
			$disc_id = $page['disc_id'];
			
			foreach($disciplines as $key => $discipline){
				if($discipline['id'] == $page['disc_id'])
					$name = $discipline['name'];
			}

			$semester = $page['year']."/".convert_sem_from_id($page['semester']);
			$short_name = $page['short_name'];

			$query = select_page_prep_name($page_id);
			$result = pg_query($dbconnect, $query);
			$actual_teachers = pg_fetch_all($result);

			$query = select_discipline_groups($page_id);
			$result = pg_query($dbconnect, $query);
			$page_groups = pg_fetch_all($result);
		} else {
			$page_id = 0;
		}

		
		
		#echo "<pre>";
		#var_dump(json_decode($page_groups_json));
		#echo "</pre>";
		show_header('Добавление/редактирование дисциплины', 
			array('Дисциплины' => 'mainpageSt.php')
		);
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


    <main class="pt-2">
	<form class="container-fluid overflow-hidden" action="page_edit.php" name="page_edit" id="page_edit" method = "post">
		
		
		<div class="row gy-5">
			<div class="col-12">
				<h2>Добавление/редактирование дисциплины</h2>
			</div>
		</div>
		<input type = "hidden" name = "id" value = "<?=$page_id?>"></input>
		
		<div class="row align-items-center m-3" style="height: 40px;">
			<div class="col-2 row justify-content-left">Полное название</div>
			<div class="col-4">
				<div class="btn-group shadow-0">
				  <select class="form-select" name = "disc_id">
					<option selected value="<?=$disc_id?>">
						<?=$name?>
					</option>
					<?php
						foreach($disciplines as $discipline){
							if($discipline['name'] == $name)
								continue;
							echo "<option value=".$discipline['id'].">".$discipline['name']."</option>";
						}
					?>
				  </select>
				</div>
			</div>
		</div>
		
		<div class="row align-items-center m-3" style="height: 40px;">
			<div class="col-2 row justify-content-left">Семестр</div>
			<div class="col-4">
				<div class="btn-group shadow-0">
					  <select class="form-select" name = "timestamp">           
						<option selected>
							<?=$semester?>
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
		
		<div class="row align-items-center m-3">
			<div class="col-2 row justify-content-left">Краткое название</div>
			<div class="col-4">
				<div class="form-outline" style="width:250px;">
					<input type="text" id="form12" class="form-control" value = "<?=$short_name?>" name = "short_name"/>
				</div>
			</div>
		</div>
		
		<div class="row align-items-center m-3" id="teachers_container">
			<div class="col-2 row justify-content-left">Преподаватели</div>
		</div>
		
		<div class="row align-items-center m-3">
			<div class="col-2 row justify-content-left"></div>
			
			<div class="col-2">
				<div class="btn-group shadow-0">
					  <select class="form-select" id = "select_teacher">
						<?php
							foreach($teachers as $teacher){
								echo "<option>".$teacher['first_name'].' '.$teacher['middle_name']."</option>";
							}
						?>
					  </select>
				</div>
			</div>
		
			<div class="col-1">
				<button type="button" class="btn btn-outline-primary" data-mdb-ripple-color="dark" style="width:120px;" id = "add_teachers">
				  Добавить 
				</button>
			</div>
		</div>
		
		<div class="row align-items-center m-3" id = "groups_container">
			<div class="col-2 row justify-content-left">Учебные группы</div>
		</div>
		
		<div class="row align-items-center m-3">
			<div class="col-2 row justify-content-left"></div>
			
			<div class="col-2">
				<div class="btn-group shadow-0">
					  <select class="form-select" name = "page_group" id = "select_groups">
						<?php
							foreach($groups as $page_group){
								echo "<option>".$page_group['name']."</option>";
							}
						?>
					  </select>
				</div>
			</div>
		
			<div class="col-1">
				<button type="button" class="btn btn-outline-primary" data-mdb-ripple-color="dark" style="width:120px;" id = "add_groups">
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
    <script type="text/javascript">

		let teachers = new Set();
		let groups = new Set();
		
		var actual_teachers_json = <?php echo json_encode($actual_teachers); ?>;
		var page_groups_json = <?php echo json_encode($page_groups); ?>;
		
		actual_teachers_json.forEach(function(r){
			let name = r.first_name + ' ' + r.middle_name;
			add_element(document.getElementById("teachers_container"), name, "teachers[]", teachers);
			teachers.add(name);
		});
		
		page_groups_json.forEach(function(r){
			let name = r.name;
			add_element(document.getElementById("groups_container"), name, "groups[]", groups);
			groups.add(name);
		});

		let add_teachers_button = document.getElementById("add_teachers");
		add_teachers_button.addEventListener('click', add_teacher);
		
		let add_groups_button = document.getElementById("add_groups");
		add_groups_button.addEventListener('click', add_groups);
		
		
		function add_teacher()
		{
			let name = document.getElementById("select_teacher").value;
			
			if(teachers.has(name))
				return;
			
			add_element(document.getElementById("teachers_container"), name, "teachers[]", teachers);

			teachers.add(name);
		}
		
		function add_groups()
		{
			let name = document.getElementById("select_groups").value;
			
			if(groups.has(name))
				return;
			
			add_element(document.getElementById("groups_container"), name, "groups[]", groups);

			groups.add(name);
		}
		
		function add_element(parent, name, tag, set)
		{
			let element = document.createElement("div");

			element.classList.add("col-2");
			element.innerText = name;
			
			let button = document.createElement("button");
			button.classList.add("btn-close");

			button.setAttribute("aria-label","Close");
			button.setAttribute("type","button");
			
			element.append(button);
			parent.append(element);
			
			button.addEventListener('click', function (event){

				let name = event.target.parentNode.textContent;
				set.delete(name);
				parent.removeChild(event.target.parentNode);
			});
			
			let input = document.createElement("input");
			input.setAttribute("type","hidden");
			input.setAttribute("value", name);
			input.setAttribute("name", tag);
			element.append(input);
		}
		
		

	
	</script>
  </body>
</html>

<!DOCTYPE html>

<?php

require_once("common.php");
require_once("dbqueries.php");
require_once("utilities.php");

// защита от случайного перехода
$au = new auth_ssh();
if (!$au->isAdmin() && !$au->isTeacher()){
	$au->logout();
	header('Location:login.php');
}

// Обработка некорректного перехода между страницами
if ((!isset($_GET['page']) || !is_numeric($_GET['page'])) && !array_key_exists('add-page', $_REQUEST)){
	header('Location:mainpage.php');
	exit;
}

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

$query = select_discipline_years();
$result = pg_query($dbconnect, $query);
$years = pg_fetch_all($result);

$query = select_teacher_name();
$result = pg_query($dbconnect, $query);
$teachers = pg_fetch_all($result);

$query = select_groups();
$result = pg_query($dbconnect, $query);
$groups = pg_fetch_all($result);

$page = null;

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
//show_head($page_title='');
?>

<html lang="en">

	<?php
	show_head("Добавление/Редактирование предмета");
	show_header_2($dbconnect, 'Добавление/Редактирование предмета', array('Редактор карточки предмета' => $_SERVER['REQUEST_URI'])); ?>

	<body>
		<main class="pt-2" aria-hidden="true">
			<form class="container-fluid overflow-hidden" action="pageedit_action.php"  id="pageedit_action" name="action" method = "post"> 
				<div class="row gy-5">
					<div class="col-12">
						<?php
						if ($page_id == 0)
							echo '<h2>Добавление предмета</h2>';
						else 
							echo '<h2>Редактирование предмета</h2>';
						?>
					</div>
				</div>


				<input type = "hidden" name = "id" value = "<?=$page_id?>"></input>

				<div class="row align-items-center m-3">
					<div class="col-3 row justify-content-left">Краткое название предмета:</div>
					<div class="col-2">
						<input type="text" id="form12" class="form-control" value = "<?=$short_name?>" name="short_name"/>
					</div>
				</div>
				
				<div class="row align-items-center m-3" style="height: 40px;">
					<div class="col-3 row justify-content-left">Полное название дисциплины:</div>
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
					<div class="col-3 row justify-content-left">Семестр:</div>
					<div class="col-4">
						<div class="btn-group shadow-0">
							<select class="form-select" name="timestamp">           
								<option selected>
									<?=$semester?>
								</option>
								<?php foreach($years as $year){
									if ($page && ($year['year'] != $page['year'] or $page['semester']%2 != 1))
										echo "<option>".$year['year']."/".convert_sem_from_id(1)."</option>";
									if ($page && ($year['year'] != $page['year'] or $page['semester']%2 != 0))
										echo "<option>".$year['year']."/".convert_sem_from_id(0)."</option>";
									else {
										echo "<option>".$year['year']."/".convert_sem_from_id(1)."</option>";
										echo "<option>".$year['year']."/".convert_sem_from_id(0)."</option>";
									}
								} ?>
							</select>
						</div>
					</div>
				</div>

				
				<div class="row align-items-center m-3">
					<div class="col-2 row justify-content-left">Преподаватели:</div>

					<div class="col-1">
						<button id="add_teachers" type="button" class="btn btn-outline-primary" data-mdb-ripple-color="dark" style="width:120px;">
							Добавить 
						</button>
					</div>
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
				
				</div>
				
				<div class="row align-items-center m-3">
					<div class="col-2 row"></div>
					<div id="teachers_container" class="col-10" style="display: flex; flex-direction: row; justify-content: flex-start;"></div>
				</div>
				
				<div class="row align-items-center m-3">
					<div class="col-2 row justify-content-left">Учебные группы:</div>

					<div class="col-1">
						<button type="button" class="btn btn-outline-primary" data-mdb-ripple-color="dark" style="width:120px;" id="add_groups">
							Добавить 
						</button>
					</div>
					<div class="col-2">
						<div class="btn-group shadow-0">
							<select class="form-select" name="page_group" id="select_groups">
								<?php
									foreach($groups as $page_group){
										echo "<option>".$page_group['name']."</option>";
									}
									echo "<option>Нет учебной группы</option>";
								?>
							</select>
						</div>
					</div>
				
				</div>
				
				<div class="row align-items-center m-3">
					<div class="col-2 row"></div>
					<div id="groups_container" class="col-10" style="display: flex; flex-direction: row; justify-content: flex-start;">
					</div>
				</div>
					
				
				<div class="row align-items-left m-3">
					<div class="col-2 row justify-content-left">Оформление:</div>
					<div class="col-10 row container-fluid">
						<?php 
						$query = select_color_theme();
						$result = pg_query($dbconnect, $query);
						$thems = pg_fetch_all($result);
						//<?php if($key) echo "display: none;";      background-color:<?= $thema['bg_color']
						foreach($thems as $key => $thema){ ?>
							
								<label class="label-theme col-2 me-3" style="padding: 0px;">
									<div class="card theme-check-button" data-mdb-ripple-color="light" style="position: relative; cursor: pointer;">
										<div class="bg-image hover-zoom" style="border-radius: 10px;" onclick="">
												<img src="<?=$thema['src_url']?>" style="transition: all .1s linear; min-width: 100%; max-height: 120px;">
												<div class="mask" style="background: <?=$thema['bg_color']?>; transition: all .1s linear;"></div>
										</div>
									</div>
									<input type="radio" <?php if($page_id == 0 && !$key) echo 'checked="checked"'; 
									else if ($page != 0 && $key == $page['color_theme_id']) echo 'checked="checked"';?> 
										name="color_theme_id" style="display: none;" value="<?=$thema['id']?>">
									<div class="checkmark" style="background-color:<?= $thema['bg_color']?>"></div>
								</label>
							
						<?php }?>
					</div>
				</div>

				<input name="creator_id" value="<?=$_SESSION['hash']?>" style="display: none;">
				
				<div class="row mx-2" style="height: 40px;">
					<div class="col-2 row justify-content-left">
						<button type="submit" name="action" value="save" class="btn btn-outline-primary" data-mdb-ripple-color="dark" style="width:120px;">
							Сохранить
						</button>
					</div>

					<?php if ($page_id != 0) {?>
						<div class="col-3">
							<button class="btn btn-outline-danger" style="color: red;" type="submit" name="action" value="delete">
								Удалить дисциплину
							</button>
						</div>
					<?php }?>
					

				</div>
			</form> 	
		</main>
	</body>
    <!-- End your project here-->

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
		
		
		function add_teacher() {
			let name = document.getElementById("select_teacher").value;
			
			if(teachers.has(name))
				return;
			
			add_element(document.getElementById("teachers_container"), name, "teachers[]", teachers);

			teachers.add(name);
		}
		
		function add_groups(){
			let name = document.getElementById("select_groups").value;
			
			if (groups.has(name))
				return;
			if (groups.size != 0 && name =="Нет учебной группы"){
				//console.log("Нет учебной группы");
				//console.log(groups.size);
				return;
			}
			
			add_element(document.getElementById("groups_container"), name, "groups[]", groups);

			groups.add(name);
		}
		
		function add_element(parent, name, tag, set) {
			let element = document.createElement("div");

			//element.classList.add("col-2");
			element.setAttribute("style", "margin-right: 30px;");

			let text = document.createElement("span");
			text.classList.add("badge", "badge-primary", "text-wrap");
			text.setAttribute("style", "padding: 10px 10px; font-size: 15px; margin-right: 10px;");
			text.innerText = name;
			
			let button = document.createElement("button");
			button.classList.add("btn-close");

			button.setAttribute("aria-label","Close");
			button.setAttribute("type","button");
			button.setAttribute("style", "font-size: 10px;")
			
			element.append(text);
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

	<script type="text/javascript">
	// Скрипт выбора темы для карточек


	</script>

</html>

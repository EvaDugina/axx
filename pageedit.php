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
if ((!isset($_GET['page']) || !is_numeric($_GET['page'])) && !array_key_exists('addpage', $_REQUEST)){
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
$years = pg_fetch_assoc($result);

$query = select_all_teachers();
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

	$semester = $page['year']."/".convert_sem_from_number($page['semester']);
	$short_name = $page['short_name'];

	$query = select_page_prep_name($page_id);
	$result = pg_query($dbconnect, $query);
	$actual_teachers = pg_fetch_all($result);

	$query = select_discipline_groups($page_id);
	$result = pg_query($dbconnect, $query);
	$page_groups = pg_fetch_all($result);
} else {
	$page_id = 0;
	
	if (array_key_exists('year', $_REQUEST) && array_key_exists('sem', $_REQUEST))	
		$semester = $_REQUEST['year']."/".convert_sem_from_number($_REQUEST['sem']);
}

#echo "<pre>";
#var_dump(json_decode($page_groups_json));
#echo "</pre>";
//show_head($page_title='');
?>

<html lang="en">

	<?php
	show_head("Добавление/Редактирование предмета");
  if ($short_name) show_header($dbconnect, 'Добавление/Редактирование предмета', array('Редактор карточки предмета: '.$short_name  => $_SERVER['REQUEST_URI'])); 
  else show_header($dbconnect, 'Добавление/Редактирование предмета', array('Редактор карточки предмета' => $_SERVER['REQUEST_URI'])); 
  ?>

	<body>
		<main class="pt-2" aria-hidden="true">
			<form class="container-fluid overflow-hidden" action="pageedit_action.php"  id="pageedit_action" name="action" method = "post"> 
				<div class="row gy-5">
					<div class="col-lg-12">
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
					<div class="col-lg-3 row justify-content-left">Краткое название предмета:</div>
					<div class="col-lg-2">
						<input type="text" id="form12" class="form-control" maxlength="14" value="<?=$short_name?>" name="short_name" autocomplete="off"/>
					</div>
				</div>
				
				<div class="row align-items-center m-3" style="height: 40px;">
					<div class="col-lg-3 row justify-content-left">Полное название дисциплины:</div>
					<div class="col-lg-4">
						<div class="btn-group shadow-0">
						<select id="selectDiscipline" class="form-select" name="disc_id">
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
					<div class="col-lg-3 row justify-content-left">Семестр:</div>
					<div class="col-lg-4">
						<div class="btn-group shadow-0">
							<select class="form-select" name="timestamp">           
								<?php // echo $page['year'] . " " . $page['semester'];
								for($year = $years['max']+1; $year >= $years['max']-4; $year--){
                  echo "<option ";
                  if ($page && $year == $page['year'] && $page['semester'] == 1)
                    echo "selected";
                  echo ">".($year-1)."/".$year." Весна</option>";

                  echo "<option ";
                  if ($page && $year == $page['year']+1 && $page['semester'] == 2)
                    echo "selected";
                  echo ">".($year-1)."/".$year." Осень</option>";
								}?>
							</select>
						</div>
					</div>
				</div>

				
				<div class="row align-items-center m-3">
					<div class="col-lg-2 row justify-content-left">Преподаватели:</div>

					<div class="col-lg-1">
						<button id="add_teachers" type="button" class="btn btn-outline-primary" data-mdb-ripple-color="dark" style="width:120px;">
							Добавить 
						</button>
					</div>
					<div class="col-lg-2">
						<div class="btn-group shadow-0">
							<select class="form-select" id = "select_teacher">
								<?php
									foreach($teachers as $teacher){?>
										<option><?=$teacher['first_name'].' '.$teacher['middle_name']?></option>;
									<?php }
								?>
							</select>
						</div>
					</div>
				
				</div>
				
				<div class="row align-items-center m-3">
					<div class="col-lg-2 row"></div>
					<div id="teachers_container" class="col-lg-10" style="display: flex; flex-direction: row; justify-content: flex-start;"></div>
				</div>
				
				<div class="row align-items-center m-3">
					<div class="col-lg-2 row justify-content-left">Учебные группы:</div>

					<div class="col-lg-1">
						<button type="button" class="btn btn-outline-primary" data-mdb-ripple-color="dark" style="width:120px;" id="add_groups">
							Добавить 
						</button>
					</div>
					<div class="col-lg-2">
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
					<div class="col-lg-2 row"></div>
					<div id="groups_container" class="col-lg-10" style="display: flex; flex-direction: row; justify-content: flex-start;">
					</div>
				</div>
					
				
				<div class="row align-items-left m-3">
					<div class="col-lg-2 row justify-content-left">Оформление:</div>
					<div class="col-lg-10 row container-fluid">
						<?php 
						$query = select_color_theme();
						$result = pg_query($dbconnect, $query);
						$thems = pg_fetch_all($result);
						
						foreach($thems as $key => $thema){ ?>
							
								<label class="label-theme col-lg-2 me-3" style="padding: 0px;">
									<div class="card theme-check-button" data-mdb-ripple-color="light" style="position: relative; cursor: pointer;">
										<div class="bg-image hover-zoom" style="border-radius: 10px;">
												<img src="<?=$thema['src_url']?>" style="transition: all .1s linear; min-width: 100%; max-height: 120px;">
												<div id="mask-<?=$thema['disc_id']?>" class="mask" style="background: <?=$thema['bg_color']?>; transition: all .1s linear;"></div>
										</div>
									</div>
									<input id="input-<?=$thema['disc_id']?>" type="radio" class="input-thema"
									<?php if($page_id == 0 && !$key) echo 'checked="checked"'; 
									else if ($page != 0 && $key == $page['color_theme_id']) echo 'checked="checked"';?> 
										name="color_theme_id" style="display: none;" value="<?=$thema['id']?>">
									<div class="checkmark" style="background-color:<?= $thema['bg_color']?>"></div>
								</label>
							
						<?php }?>
					</div>
				</div>

				<input name="creator_id" value="<?=$_SESSION['hash']?>" style="display: none;">
				
				<div class="row mx-2">
					<div class="col-lg-2 row justify-content-left">
						<button type="submit" name="action" value="save" class="btn btn-outline-primary" data-mdb-ripple-color="dark">
							Сохранить
						</button>
					</div>

					<?php if ($page_id != 0) {?>
						<div class="col-lg-2">
							<button class="btn btn-outline-danger" style="color: red;" type="submit" name="action" value="delete">
								Удалить предмет
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
		
		if(actual_teachers_json){
      actual_teachers_json.forEach(function(r){
        let name = r.first_name + ' ' + r.middle_name;
        add_element(document.getElementById("teachers_container"), name, "teachers[]", teachers);
        teachers.add(name);
      });
    }
		
    if(page_groups_json){
      page_groups_json.forEach(function(r){
        let name = r.name;
        add_element(document.getElementById("groups_container"), name, "groups[]", groups);
        groups.add(name);
      });
    }

		let add_teachers_button = document.getElementById("add_teachers");
		add_teachers_button.addEventListener('click', add_teacher);
		
		let add_groups_button = document.getElementById("add_groups");
		add_groups_button.addEventListener('click', add_groups);
		
		
		function add_teacher() {
			let name = document.getElementById("select_teacher").value;
      if (teachers.has(name))
				return;
      console.log(name);
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

			//element.classList.add("col-lg-2");
      element.setAttribute("class", "d-flex justify-content-between align-items-center p-2 me-4 badge badge-primary text-wrap");

			let text = document.createElement("span");
			text.classList.add("p-1", "me-1");
			text.setAttribute("style", "font-size: 15px; /*border-right: 1px solid; border-color: grey;*/");
			text.innerText = name;
			
			let button = document.createElement("button");
			button.classList.add("btn-close");

			button.setAttribute("aria-label","Close");
			button.setAttribute("type","button");
			button.setAttribute("style", "font-size: 10px;");
			
			element.append(text);
			element.append(button);
			parent.append(element);
			
			button.addEventListener('click', function (event){
				let name = event.target.parentNode.textContent;
				set.delete(name);
				parent.removeChild(event.target.parentNode);
			});
			
			let input = document.createElement("input");
			input.setAttribute("type", "hidden");
			input.setAttribute("value", name);
			input.setAttribute("name", tag);
      //console.log(input);
			element.append(input);
		}
		
	</script>

	<script type="text/javascript">
		// Скрипт синхронизации выбора дисциплины и оформления для предмета
		var select = document.querySelector('#selectDiscipline');
		select.addEventListener('click', function(){

    		console.log("input-" + select.value);
			var input = document.getElementById("input-" + select.value);

			var divLabels = input.parentElement.parentElement;
			var labelList = divLabels.children;
			for (let i = 0; i<labelList.length; i++){
				labelList[i].children[1].checked = false;
			}

			input.checked = true;
		});


	</script>

</html>

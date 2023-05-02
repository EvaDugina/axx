<!DOCTYPE html>

<?php

require_once("common.php");
require_once("dbqueries.php");
require_once("utilities.php");

$au = new auth_ssh();
checkAuLoggedIN($au);
checkAuIsNotStudent($au);

$User = new User((int)$au->getUserId());

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
  $Page = new Page((int)$page_id);
	
	$query = select_discipline_page($page_id);
	$result = pg_query($dbconnect, $query);
	$page = pg_fetch_all($result)[0];
	$disc_id = $page['disc_id'];
	
	foreach($disciplines as $key => $discipline){
		if($discipline['id'] == $page['disc_id'])
			$name = $discipline['name'];
	}
	$short_name = $Page->name;

	$query = select_page_prep_name($page_id);
	$result = pg_query($dbconnect, $query);
	$actual_teachers = pg_fetch_all($result);

	$query = select_discipline_groups($page_id);
	$result = pg_query($dbconnect, $query);
	$page_groups = pg_fetch_all($result);
  echo "<script>var isNewPage=false;</script>";
} else {
	$page_id = 0;
  // TODO: Сделать создание страницы
  echo "<script>var isNewPage=true;</script>";

	
	if (array_key_exists('year', $_REQUEST) && array_key_exists('sem', $_REQUEST))	
		$semester = $_REQUEST['year']."/".convert_sem_from_number($_REQUEST['sem']);
}


echo "<script>var user_id=".$au->getUserId().";</script>";
if ($au->isAdmin())
  echo "<script>var isAdmin=true;</script>";
else 
  echo "<script>var isAdmin=false;</script>";

#echo "<pre>";
#var_dump(json_decode($page_groups_json));
#echo "</pre>";
//show_head($page_title='');
?>

<html lang="en">

	<?php show_head("Добавление/Редактирование раздела");?>

	<body>
		
		<?php if ($short_name) 
			show_header($dbconnect, 'Добавление/Редактирование раздела', array('Редактор раздела: '.$short_name  => $_SERVER['REQUEST_URI']), $User); 
  		else 
			show_header($dbconnect, 'Добавление/Редактирование раздела', array('Редактор раздела' => $_SERVER['REQUEST_URI']), $User); ?>

		<main class="pt-2" aria-hidden="true">
			<form class="container-fluid overflow-hidden" action="pageedit_action.php"  id="pageedit_action" name="action" method = "post"> 
				<div class="row gy-5">
					<div class="col-lg-12">
						<?php
						if ($page_id == 0)
							echo '<h2>Добавление раздела</h2>';
						else 
							echo '<h2>Редактирование раздела</h2>';
						?>
					</div>
				</div>


				<input type = "hidden" name = "id" value = "<?=$page_id?>"></input>

				<div class="row align-items-center m-3">
					<div class="col-lg-2 row justify-content-left">Название раздела:</div>
					<div class="col-lg-2">
						<input type="text" id="input-name" class="form-control" maxlength="19" value="<?=$short_name?>" name="short_name" autocomplete="off"
            data-container="body" data-placement="right" title="Это поле должно быть заполнено!"/>
					</div>
				</div>
				
				<div class="row align-items-center m-3" style="height: 40px;">
					<div class="col-lg-2 row justify-content-left">Полное название дисциплины:</div>
					<div class="col-lg-4">
						<div id="div-popover-select" class="btn-group shadow-0" data-container="body" data-placement="right" title="Это поле должно быть заполнено!">
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
					<div class="col-lg-2 row justify-content-left">Семестр:</div>
					<div class="col-lg-4">
						<div class="btn-group shadow-0">
							<select class="form-select" name="timestamp">           
							<?php // echo $page['year'] . " " . $page['semester'];
								for($year = $years['max']-4; $year <= $years['max']; $year++) {
								  echo "<option ";
								  if ($page && $year == $page['year'] && $page['semester'] == 1)
									echo "selected";
								  echo ">".$year."/".($year+1)." Осень</option>";

								  echo "<option ";
								  if ($page && $year == $page['year'] && $page['semester'] == 2)
									echo "selected";
								  echo ">".$year."/".($year+1)." Весна</option>";
								}
							?>

								<!-- Добавление в новый, не существующий в бд семестр -->
								<option class="text-info"><?=($years['max']+1)."/".($years['max']+2)." Осень"?></option>;
								<option class="text-info"><?=($years['max']+1)."/".($years['max']+2)." Весна"?></option>;
							</select>
						</div>
					</div>
				</div>

				
				<div class="row align-items-center mx-3 pe-group-upper">
					<div class="col-lg-2 row justify-content-left">Преподаватели:</div>
					<div id="teachers_container" class="col-lg-10" style="display: flex; flex-direction: row; justify-content: flex-start; background: #fff8e0;"></div>
				</div>
				
				<div class="row align-items-center mx-3 pe-group-lower">
					<div class="col-lg-2 row"></div>
					<div class="col-lg-10">
						<div class="btn-group shadow-0">
							<select class="form-select" id = "select_teacher">
								<?php
									foreach($teachers as $teacher){?>
										<option value="<?=$teacher['id']?>"><?=$teacher['first_name'].' '.$teacher['middle_name']?></option>;
									<?php }
								?>
							</select>&nbsp;
							<button id="add_teachers" type="button" class="btn btn-outline-primary" data-mdb-ripple-color="dark" style="width:120px;">
								Добавить 
							</button>
						</div>
					</div>
					<div class="col-lg-1">
					</div>
				</div>
				
				<div class="row align-items-center mx-3 pe-group-upper">
					<div class="col-lg-2 row justify-content-left">Учебные группы:</div>
					<div id="groups_container" class="col-lg-10" style="display: flex; flex-direction: row; justify-content: flex-start; background: #fff8e0;"></div>
				</div>
				
				<div class="row align-items-center mx-3 pe-group-lower">
					<div class="col-lg-2 row"></div>
					<div class="col-lg-10">
						<div class="btn-group shadow-0">
							<select class="form-select" name="page_group" id="select_groups">
								<?php
									foreach($groups as $page_group){?>
										<option value="<?=$page_group['id']?>"><?=$page_group['name']?></option>
									<?php }
									echo "<option>Нет учебной группы</option>";
								?>
							</select>&nbsp;
							<button type="button" class="btn btn-outline-primary" data-mdb-ripple-color="dark" style="width:120px;" id="add_groups">
								Добавить 
							</button>
						</div>
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
						<button id="btn-save" type="submit" name="action" value="save" class="btn btn-outline-primary" data-mdb-ripple-color="dark">
							Сохранить
						</button>
					</div>

					<?php if ($page_id != 0) {?>
						<div class="col-lg-2">
							<button id="btn-delete" class="btn btn-outline-danger" style="color: red;" type="submit" name="action" value="delete">
								Удалить раздел
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
        add_element(document.getElementById("teachers_container"), name, "teachers[]", "t", r.id);
        teachers.add(r.id);
      });
    }
		
    if(page_groups_json){
      page_groups_json.forEach(function(r){
        let name = r.name;
        add_element(document.getElementById("groups_container"), name, "groups[]", "g", r.id);
        groups.add(r.id);
      });
    }

    if(isNewPage && !isAdmin) {
      add_teacher(user_id);
    }

		let add_teachers_button = document.getElementById("add_teachers");
		add_teachers_button.addEventListener('click', addElementTeacher);
		
		let add_groups_button = document.getElementById("add_groups");
		add_groups_button.addEventListener('click', add_groups);
		

    function addElementTeacher() {
      teacher_id = $('#select_teacher').val();
      add_teacher(teacher_id);
    }
		
		function add_teacher(teacher_id) {
			var name;
      Array.from($('#select_teacher').children()).forEach((option) => {
        if(option.value == teacher_id) {
          name = option.innerText;
          return;
        }
      });

      if (teachers.has(teacher_id))
				return;
      console.log(name);
			add_element(document.getElementById("teachers_container"), name, "teachers[]", "t", teacher_id);
			teachers.add(teacher_id);
		}
		
		function add_groups(){
			let group_id = $('#select_groups').val();
			var name;
      Array.from($('#select_groups').children()).forEach((option) => {
        if(option.value == group_id) {
          name = option.innerText;
          return;
        }
      });
			
			if (groups.has(group_id))
				return;
			if (groups.size != 0 && name =="Нет учебной группы"){
				//console.log("Нет учебной группы");
				//console.log(groups.size);
				return;
			}
			
			add_element(document.getElementById("groups_container"), name, "groups[]", "g", group_id);

			groups.add(group_id);
		}
		
		function add_element(parent, name, tag, set, id) {
			let element = document.createElement("div");

			//element.classList.add("col-lg-2");
      element.setAttribute("class", "d-flex justify-content-between align-items-center p-2 me-4 badge badge-primary text-wrap teacher-element");
      element.id = "t-" + id;

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
				let name = event.target.parentNode.value;
        if (tag == "groups[]")
          groups.delete(id);
        else 
          teachers.delete(id);
				parent.removeChild(event.target.parentNode);
			});
			
			let input = document.createElement("input");
			input.setAttribute("type", "hidden");
			input.setAttribute("value", id);
			input.setAttribute("name", tag);
      //console.log(input);
			element.append(input);
		}


    $('#btn-save').click(function(event) {
      event.preventDefault();
      if (checkFiledsBeforeSave()) {
        $('#pageedit_action').append('<input type="text" name="action" value="save" hidden>');
        $('#pageedit_action').submit();
      }
    });

    $('#input-name').change(function() {
      if($('#input-name').val() != "") {
        $('#input-name').popover('enable');
        $('#input-name').popover('hide');
        $('#input-name').popover('disable');
      }
    });

    $('#selectDiscipline').change(function() {
      if ($('#selectDiscipline').val()!="0" && $('#selectDiscipline').val()!=""){
        $('#div-popover-select').popover('enable');
        $('#div-popover-select').popover('hide');
        $('#div-popover-select').popover('disable');
      } else {

      }
    });

    function checkFiledsBeforeSave() {
      flag = true;
      console.log($('#selectDiscipline').val());
      if ($('#selectDiscipline').val()=="0" || $('#selectDiscipline').val()=="") {
        $('#div-popover-select').popover('enable');
        $('#div-popover-select').popover('show');
        $('#div-popover-select').popover('disable');
        flag = false;
      }

      
      if (!$('#input-name').val()) {
        $('#input-name').popover('enable');
        $('#input-name').popover('show');
        $('#input-name').popover('disable');
        flag = false;
      }
      
      if (!checkTeachersList() && !isAdmin) {
        let answer_confirm = confirm("Вы не выбрали себя в качестве преподавателя! Дисциплина будет недоступна вам для просмотра. Вы уверены, что хотите продолжить?");
        if(!answer_confirm)
          flag = false;
      }
      
      return flag;

    }

    // TODO: сделать добавление своей карточки в кач-ве препода по умолчанию
    function checkTeachersList() {
      var flag = false;
      Array.from($('#teachers_container').children()).forEach((teacher) => {
        if(teacher.id == "t-" + user_id) {
          flag = true;
          return;
        }
      });
      return flag;
    }
		
	</script>



	<script type="text/javascript">
		// Скрипт синхронизации выбора дисциплины и оформления для раздела
		var select = document.querySelector('#selectDiscipline');
		select.addEventListener('click', function(){

      if ($('#selectDiscipline').val()!="0") {
        console.log("input-" + select.value);
        var input = document.getElementById("input-" + select.value);

        var divLabels = input.parentElement.parentElement;
        var labelList = divLabels.children;
        for (let i = 0; i<labelList.length; i++){
          labelList[i].children[1].checked = false;
        }

        input.checked = true;
      }
		});


	</script>

</html>

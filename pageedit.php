<!DOCTYPE html>

<?php

require_once("common.php");
require_once("dbqueries.php");
require_once("utilities.php");

function generatePairOptionsSemester($choosedYear, $year, $page_year, $page_semester)
{
	$options = "<option ";
	if ($choosedYear && $year == $page_year && $page_semester == 1)
		$options .= "selected";
	$options .= ">" . $year . "/" . ($year + 1) . " Осень</option>";

	$options .= "<option ";
	if ($choosedYear && $year == $page_year && $page_semester == 2)
		$options .= "selected";
	$options .= ">" . $year . "/" . ($year + 1) . " Весна</option>";

	return $options;
}

$au = new auth_ssh();
checkAuLoggedIN($au);
checkAuIsNotStudent($au);

$User = new User((int)$au->getUserId());

// Обработка некорректного перехода между страницами
if ((!isset($_GET['page']) || !is_numeric($_GET['page'])) && !array_key_exists('addpage', $_REQUEST)) {
	header('Location:mainpage.php');
	exit;
}

$isNewPage = array_key_exists('addpage', $_REQUEST);
$new_page_year = null;
$new_page_semester = null;
if ($isNewPage) {
	if (isset($_GET['year']) && isset($_GET['sem'])) {
		$new_page_year = $_GET['year'];
		$new_page_semester = $_GET['sem'];
	} else {
		header('Location:mainpage.php');
		exit;
	}
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
$Page = null;

if (array_key_exists('page', $_REQUEST)) {
	$page_id = $_REQUEST['page'];
	$Page = new Page((int)$page_id);

	$query = select_discipline_page($page_id);
	$result = pg_query($dbconnect, $query);
	$page = pg_fetch_all($result)[0];

	$name = $Page->getDisciplineName();
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
		$semester = $_REQUEST['year'] . "/" . convert_sem_from_number($_REQUEST['sem']);
}


echo "<script>var user_id=" . $User->id . ";</script>";
if ($User->isAdmin())
	echo "<script>var isAdmin=true;</script>";
else
	echo "<script>var isAdmin=false;</script>";

#echo "<pre>";
#var_dump(json_decode($page_groups_json));
#echo "</pre>";
//show_head($page_title='');
?>

<html lang="en">

<?php show_head("Добавление/Редактирование раздела", array('https://unpkg.com/easymde/dist/easymde.min.js'), array('https://unpkg.com/easymde/dist/easymde.min.css')); ?>

<body>

	<?php if ($short_name)
		show_header($dbconnect, 'Добавление/Редактирование раздела', array('Редактор раздела: ' . $short_name  => $_SERVER['REQUEST_URI']), $User);
	else
		show_header($dbconnect, 'Добавление/Редактирование раздела', array('Редактор раздела' => $_SERVER['REQUEST_URI']), $User); ?>

	<main class="px-5 pt-3 pb-5" aria-hidden="true">
		<form class="container-fluid overflow-hidden" action="pageedit_action.php" id="pageedit_action" name="action" method="post">
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


			<input type="hidden" name="id" value="<?= $page_id ?>"></input>

			<div class="row align-items-center m-3 mb-4">
				<div class="col-lg-2 row justify-content-left">Название раздела:</div>
				<div class="col-lg-2 px-0">
					<input type="text" id="input-name" class="form-control" maxlength="19" value="<?= $short_name ?>" name="short_name" autocomplete="off" data-container="body" data-placement="right" data-title="Это поле должно быть заполнено!" />
				</div>
				<p id="p-errorPageName" class="error p-0 d-none">Ошибка! Незаполненное поле!</p>
			</div>

			<div class="row align-items-center m-3 mb-4" style="height: 40px;">
				<div class="col-lg-2 row justify-content-left">Полное название дисциплины:</div>
				<div class="col-lg-4 px-0">
					<div id="div-popover-select" class="btn-group shadow-0" data-container="body" data-placement="right">
						<select id="selectDiscipline" class="form-select" name="disc_id">
							<option selected value="<?= ($disc_id == null) ? "null" : $disc_id ?>">
								<?= $name ?>
							</option>
							<?php
							foreach ($disciplines as $discipline) {
								if ($discipline['name'] == $name)
									continue; ?>
								<option value="<?= $discipline['id'] ?>"><?= $discipline['name'] ?></option>
							<?php }
							if ($Page->disc_id != null) { ?>
								<option value="null">ДРУГОЕ</option>
							<?php } ?>
						</select>
					</div>
				</div>
				<p id="p-errorDisciplineName" class="error p-0 d-none">Ошибка! Не выбранная дисциплина!</p>
			</div>


			<div class="row align-items-center m-3 mb-4" style="height: 40px;">
				<div class="col-lg-2 row justify-content-left">Семестр:</div>
				<div class="col-lg-4  px-0">
					<div class="btn-group shadow-0">
						<select id="select-semester" class="form-select" name="timestamp">
							<?php // echo $page['year'] . " " . $page['semester'];
							for ($year = $years['max'] - 4; $year <= $years['max']; $year++) {
								if ($isNewPage) {
									echo generatePairOptionsSemester(true, $year, $new_page_year, $new_page_semester);
								} else {
									echo generatePairOptionsSemester($page, $year, $page['year'], $page['semester']);
								}
							}
							?>

							<!-- Добавление в новый, не существующий в бд семестр -->
							<option class="text-info"><?= ($years['max'] + 1) . "/" . ($years['max'] + 2) . " Осень" ?></option>;
							<option class="text-info"><?= ($years['max'] + 1) . "/" . ($years['max'] + 2) . " Весна" ?></option>;

							<!-- Добавление вне семестровых разделов -->
							<option value="ВНЕ CЕМЕСТРА" class="text-secondary" <?= (($Page && $Page->isOutsideSemester()) || ($new_page_year == -1 && $new_page_semester == -1)) ? "selected" : "" ?>>ВНЕ CЕМЕСТРА</option>;
						</select>
					</div>
				</div>
			</div>


			<div class="row align-items-center mx-3 pe-group-upper">
				<div class="col-lg-2 row justify-content-left">Преподаватели:</div>
				<div id="teachers_container" class="col-lg-10  px-0" style="display: flex; flex-direction: row; justify-content: flex-start; background: #fff8e0;"></div>
			</div>

			<div class="row align-items-center mx-3 pe-group-lower mb-4">
				<div class="col-lg-2 row"></div>
				<div class="col-lg-10  px-0">
					<div class="btn-group shadow-0">
						<select class="form-select" id="select_teacher">
							<?php
							foreach ($teachers as $teacher) { ?>
								<option value="<?= $teacher['id'] ?>"><?= $teacher['first_name'] . ' ' . $teacher['middle_name'] ?></option>;
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

			<div id="div-students-groups" class="mb-4 <?= ($Page && $Page->isOutsideSemester()) ? "d-none" : "" ?>">
				<div class="row align-items-center mx-3 pe-group-upper">
					<div class="col-lg-2 row justify-content-left">Учебные группы:</div>
					<div id="groups_container" class="col-lg-10 px-0" style="display: flex; flex-direction: row; justify-content: flex-start; background: #fff8e0;"></div>
				</div>

				<div class="row align-items-center mx-3 pe-group-lower mb-4">
					<div class="col-lg-2 row"></div>
					<div class="col-lg-10 px-0">
						<div class="btn-group shadow-0">
							<select class="form-select" name="page_group" id="select_groups">
								<?php
								foreach ($groups as $page_group) { ?>
									<option value="<?= $page_group['id'] ?>"><?= $page_group['name'] ?></option>
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
			</div>

			<div class="row align-items-center mx-3 pe-group-upper mb-4">
				<div class="col-lg-2 row justify-content-left">Описание раздела:</div>
				<div class="col-lg-10 px-0">
					<div id="form-description" class="form-outline" onkeyup="descriptionChange()">
						<textarea id="textArea-description" class="form-control <?= 'active' ?>" rows="5" name="page-description" style="resize: none;"><?= ($Page) ? $Page->description : "" ?></textarea>
						<label id="label-textArea-description" class="form-label" for="textArea-description">Описание раздела</label>
						<script>
							const easyMDE = new EasyMDE({
								element: document.getElementById('textArea-description')
							});
						</script>
						<div class="form-notch">
							<div class="form-notch-leading" style="width: 9px;"></div>
							<div class="form-notch-middle" style="width: 114.4px;"></div>
							<div class="form-notch-trailing"></div>
						</div>
					</div>
					<span id="error-textArea-description" class="error-input" aria-live="polite"></span>
				</div>
			</div>


			<div class="row align-items-left m-3 mb-4">
				<div class="col-lg-2 row justify-content-left">Оформление:</div>
				<div class="col-lg-10 row container-fluid">
					<?php
					$query = select_color_theme();
					$result = pg_query($dbconnect, $query);
					$thems = pg_fetch_all($result);

					foreach ($thems as $key => $thema) {
						$bg_color = ($thema['bg_color'] != null) ? $thema['bg_color'] : "#000000";
						$disc_id = ($thema['disc_id'] != null) ? $thema['disc_id'] : -1; ?>

						<label class="label-theme col-lg-2 me-3 mb-3 " style="padding: 0px;">
							<div class="card theme-check-button" data-mdb-ripple-color="light" style="position: relative; cursor: pointer;">
								<div class="bg-image hover-zoom" style="border-radius: 10px;">
									<img src="<?= $thema['src_url'] ?>" style="transition: all .1s linear; min-width: 100%; max-height: 120px;">
									<div id="mask-<?= $disc_id ?>" class="mask" style="background: <?= $bg_color ?>; transition: all .1s linear;"></div>
								</div>
							</div>
							<input id="input-<?= $disc_id ?>" type="radio" class="input-thema" <?php if ($page_id == 0 && !$key) echo 'checked="checked"';
																								else if ($page != 0 && $key == $page['color_theme_id']) echo 'checked="checked"'; ?> name="color_theme_id" style="display: none;" value="<?= $thema['id'] ?>">
							<div class="checkmark" style="background-color:<?= $bg_color ?>"></div>
						</label>

					<?php } ?>

					<label class="label-theme col-lg-2 me-3 mb-3" style="padding: 0px;">
						<div class="card theme-check-button w-100 h-100" data-mdb-ripple-color="light" style="position: relative; cursor: pointer;">
							<div class="btn btn-outline-primary py-2 px-4 w-100 h-100">
								<input id="input-image" type="file" name="image-file" style="display: none;">
								<div class="py-1">
									<div class="row py-2">
										<div class="d-flex justify-content-center align-self-center">
											<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-upload w-50 h-50" viewBox="0 0 16 16">
												<path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z" />
												<path d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V11.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708l3-3z" />
											</svg>
										</div>
									</div>
								</div>
							</div>

						</div>
					</label>

					<!-- <div class="col-2 align-self-center popover-message-message-stud" style="cursor: pointer; padding: 0px;" onclick="window.location='pageedit.php?addpage'">
						<a class="btn btn-link" href="pageedit.php?addpage" type="button" style="width: 100%; height: 100%; padding-top: 20%;">
							<div class="row">
								<i class="fas fa-plus-circle mb-2 align-self-center" style="font-size: 30px;"></i><br>
								<span class="align-self-center">Добавить новый раздел</span>
							</div>
						</a>
					</div> -->

				</div>
			</div>

			<input name="creator_id" value="<?= $_SESSION['hash'] ?>" style="display: none;">

			<div class="row mx-2">
				<div class="col-lg-2 row justify-content-left">
					<button id="btn-save" type="submit" name="action" value="save" class="btn btn-outline-primary" data-mdb-ripple-color="dark">
						Сохранить
					</button>
				</div>

				<?php if ($page_id != 0) { ?>
					<div class="col-lg-2">
						<button id="btn-delete" class="btn btn-outline-danger" style="color: red;" type="submit" name="action" value="delete">
							Удалить раздел
						</button>
					</div>
				<?php } ?>


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


	let easyMDE_value = easyMDE.value();
	var original_description = easyMDE_value.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&");

	function descriptionChange() {
		if (checkDescription()) {
			$('.editor-statusbar').addClass("rounded-bottom bg-primary text-white");
			$('.editor-statusbar > .autosave').text("(имеются несохранённые изменения)");
		} else {
			$('.editor-statusbar').removeClass("rounded-bottom bg-primary text-white");
			$('.editor-statusbar > .autosave').text("");
		}
	};

	function checkDescription() {
		let easyMDE_value = easyMDE.value();
		let now_description = easyMDE_value.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&");
		if (original_description != now_description)
			return true;

		return false;
	}

	$(document).ready(function() {

		if (actual_teachers_json) {
			actual_teachers_json.forEach(function(r) {
				let name = r.first_name + ' ' + r.middle_name;
				add_element(document.getElementById("teachers_container"), name, "teachers[]", "t", r.id);
				teachers.add(r.id);
			});
		}

		if (page_groups_json) {
			page_groups_json.forEach(function(r) {
				let name = r.name;
				add_element(document.getElementById("groups_container"), name, "groups[]", "g", r.id);
				groups.add(r.id);
			});
		}

		if (isNewPage && !isAdmin) {
			add_teacher(user_id);
		}

	});

	$('#input-image').on("change", function(event) {
		if (!event || !event.target || !event.target.files || event.target.files.length === 0) {
			return;
		}

		let new_file = event.target.files[0];
		// console.log("newImage", new_file);

		// Получаем расширение файла и сравниваем точно ли оно png или jpeg или jpg
		// let ext = new_file.name.split('.').pop();
		if (new_file.type == "image/png" || new_file.type == "image/jpg" || new_file.type == "image/jpeg" || new_file.type == "image/gif") {
			ajaxAddColorTheme(new_file);
			document.location.reload();
		} else {
			alert("Ошибка загрузки файла! Файл должен быть с расширением PNG, JPG или JPEG");
		}

	});

	// function addColorTheme(file) {

	// 	let files = document.getElementById("input-image").files;
	// 	if (files.length > 0) {
	// 		addFiles(files[1]);
	// 		document.location.reload();
	// 	}
	// }


	function ajaxAddColorTheme(file) {

		var formData = new FormData();

		formData.append('flag-addColorTheme', true);
		formData.append('image-file', file);

		ajaxResponse = null;

		$.ajax({
			type: "POST",
			url: 'pageedit_action.php#content',
			cache: false,
			contentType: false,
			processData: false,
			async: false,
			data: formData,
			dataType: 'html',
			success: function(response) {
				response = response.replace(/(\r\n|\n|\r)/gm, "").trim();
				ajaxResponse['response'] = response;
			},
			complete: function() {}
		});

		return ajaxResponse;
	}




	var add_teachers_button = document.getElementById("add_teachers");
	add_teachers_button.addEventListener('click', addElementTeacher);

	var add_groups_button = document.getElementById("add_groups");
	add_groups_button.addEventListener('click', add_groups);


	function addElementTeacher() {
		teacher_id = $('#select_teacher').val();
		add_teacher(teacher_id);
	}

	function add_teacher(teacher_id) {

		teacher_id = parseInt(teacher_id);

		if (teachers.has(parseInt(teacher_id)))
			return;

		var name;
		Array.from($('#select_teacher').children()).forEach((option) => {
			if (option.value == teacher_id) {
				name = option.innerText;
				return;
			}
		});

		console.log(name);
		add_element(document.getElementById("teachers_container"), name, "teachers[]", "t", teacher_id);
		teachers.add(teacher_id);
	}

	function add_groups() {
		let group_id = $('#select_groups').val();
		var name;
		Array.from($('#select_groups').children()).forEach((option) => {
			if (option.value == group_id) {
				name = option.innerText;
				return;
			}
		});

		if (groups.has(group_id))
			return;
		if (groups.size != 0 && name == "Нет учебной группы") {
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

		button.setAttribute("aria-label", "Close");
		button.setAttribute("type", "button");
		button.setAttribute("style", "font-size: 10px;");

		element.append(text);
		element.append(button);
		parent.append(element);

		button.addEventListener('click', function(event) {
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

	function checkFiledsBeforeSave() {
		flag = true;

		if (!$('#input-name').val()) {
			// $('#input-name').popover('enable');
			// $('#input-name').popover('show');
			// $('#input-name').popover('disable');
			$('#p-errorPageName').removeClass("d-none");
			flag = false;
		} else {
			$('#p-errorPageName').addClass("d-none");
		}

		if ($('#selectDiscipline').val() == "0" || $('#selectDiscipline').val() == "") {
			// $('#div-popover-select').popover('enable');
			// $('#div-popover-select').popover('show');
			// $('#div-popover-select').popover('disable');
			$('#p-errorDisciplineName').removeClass("d-none");
			flag = false;
		} else {
			$('#p-errorDisciplineName').addClass("d-none");
		}

		if (!checkTeachersList() && !isAdmin) {
			let answer_confirm = confirm("Вы не выбрали себя в качестве преподавателя! Дисциплина будет недоступна вам для просмотра. Вы уверены, что хотите продолжить?");
			if (!answer_confirm)
				flag = false;
		}

		return flag;

	}

	// TODO: сделать добавление своей карточки в кач-ве препода по умолчанию
	function checkTeachersList() {
		var flag = false;
		Array.from($('#teachers_container').children()).forEach((teacher) => {
			if (teacher.id == "t-" + user_id) {
				flag = true;
				return;
			}
		});
		return flag;
	}
</script>



<script type="text/javascript">
	// Скрипт синхронизации выбора дисциплины и оформления для раздела
	// var select = document.querySelector('#selectDiscipline');
	// select.addEventListener('click', function() {

	// 	if ($('#selectDiscipline').val() != "0") {
	// 		console.log("input-" + select.value);
	// 		var input = document.getElementById("input-" + select.value);

	// 		var divLabels = input.parentElement.parentElement;
	// 		var labelList = divLabels.children;
	// 		for (let i = 0; i < labelList.length; i++) {
	// 			labelList[i].children[1].checked = false;
	// 		}

	// 		input.checked = true;
	// 	}
	// });

	$('#select-semester').on('change', function() {
		console.log($(this).val());
		if ($(this).val() == "ВНЕ СЕМЕСТРА") {
			$('#div-students-groups').addClass("d-none");
		} else {
			$('#div-students-groups').removeClass("d-none");
		}
	});
</script>

</html>
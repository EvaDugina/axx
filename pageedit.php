<!DOCTYPE html>

<?php

require_once("common.php");
require_once("dbqueries.php");
require_once("utilities.php");

function generatePairOptionsSemester($choosedYear, $year, $page_year, $page_semester, $class = null)
{
	$options = "<option";
	if ($class != null)
		$options .= ' class="' . $class . '"';
	if ($choosedYear && $year == $page_year && $page_semester == 1)
		$options .= " selected";
	$options .= ">";
	$options .= getTextSemester($year, 1);
	$options .= "</option>";

	$options .= "<option";
	if ($class != null)
		$options .= ' class="' . $class . '"';
	if ($choosedYear && $year == $page_year && $page_semester == 2)
		$options .= " selected";
	$options .= ">";
	$options .= getTextSemester($year, 2);
	$options .= "</option>";

	return $options;
}

function isOutsideSemesterPage($Page, $new_page_year, $new_page_semester)
{
	return ($Page && $Page->isOutsideSemester()) || ($new_page_year == -1 && $new_page_semester == -1);
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

$query = select_all_disciplines();
$result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
$disciplines = pg_fetch_all($result);

$query = select_discipline_timestamps();
$result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
$timestamps = pg_fetch_all($result);

$query = select_discipline_years();
$result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
$years = pg_fetch_assoc($result);

$Teachers = getAllTeachers();
$all_teachers = [];
foreach ($Teachers as $Teacher) {
	array_push($all_teachers, array('id' => $Teacher->id, 'fio' => $Teacher->getOfficialFIO()));
}

$Groups = getAllGroups();

$page = null;
$Page = null;

$page_teachers = [];
$page_groups = [];
if (array_key_exists('page', $_REQUEST)) {
	$page_id = $_REQUEST['page'];
	$Page = new Page((int)$page_id);

	$query = select_discipline_page($page_id);
	$result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
	$page = pg_fetch_all($result)[0];

	$name = $Page->getDisciplineName();
	$short_name = $Page->name;

	$pageTeachers = $Page->getTeachers();
	foreach ($pageTeachers as $Teacher) {
		array_push($page_teachers, array('id' => $Teacher->id, 'fio' => $Teacher->getOfficialFIO()));
	}

	$pageGroups = $Page->getGroups();
	foreach ($pageGroups as $Group) {
		array_push($page_groups, array('id' => $Group->id, 'name' => $Group->name, 'countAssignments' => $Page->getCountActiveAssignmentsByGroup($Group->id)));
	}

	echo "<script>var isNewPage=false;</script>";
	echo "<script>var PAGE_ID=$Page->id;</script>";
} else {
	$page_id = 0;

	if ($au->isAdminOrPrep() && !$au->isAdmin())
		$page_teachers = [array('id' => $User->id, 'fio' => $User->getOfficialFIO())];

	if (array_key_exists('year', $_REQUEST) && array_key_exists('sem', $_REQUEST))
		$semester = $_REQUEST['year'] . "/" . convert_sem_from_number($_REQUEST['sem']);

	// TODO: Сделать создание страницы
	echo "<script>var isNewPage=true;</script>";
	echo "<script>var PAGE_ID=null;</script>";
}

$page_title = getPageeditPageTitle($Page);

echo "<script>console.log(" . json_encode($page_teachers) . ");</script>";
echo "<script>var actual_teachers_json = " . json_encode($page_teachers) . ";</script>";
echo "<script>var page_groups_json = " . json_encode($page_groups) . ";</script>";


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

<?php
show_head($page_title, array('./src/easymde.min.js'), array('./src/easymde.min.css'));

?>

<body>

	<?php
	show_header($dbconnect, $page_title, array($page_title  => $_SERVER['REQUEST_URI']), $User);
	?>

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


			<input id="input-pageId" type="hidden" name="id" value="<?= $page_id ?>"></input>

			<div class="row align-items-start m-3 mb-4" style="height: 40px;">
				<div class="col-lg-3 row justify-content-left py-2">Полное название раздела:</div>
				<div class="col-lg-4 px-0">
					<div id="div-popover-select" class="btn-group shadow-0" data-container="body" data-placement="right">
						<select id="selectDiscipline" class="form-select" name="disc_id">
							<?php
							foreach ($disciplines as $discipline) { ?>
								<option <?= ($Page != null && $discipline['id'] == $Page->disc_id) ? "selected" : "" ?>
									value="<?= $discipline['id'] ?>"><?= $discipline['name'] ?></option>
							<?php } ?>
							<option <?= ($Page == null || $discipline['id'] == $Page->disc_id) ? "selected" : "" ?> value="null">ДРУГОЕ</option>
						</select>
					</div>
				</div>
				<p id="p-errorDisciplineName" class="error p-0 d-none">Ошибка! Не выбранная дисциплина!</p>
			</div>

			<div class="row align-items-start m-3 mb-4">
				<div class="col-lg-3 row justify-content-left py-2">Краткое название раздела:</div>
				<div class="col-lg-3 d-flex px-0">
					<input type="text" id="input-name" class="form-control me-2" value="<?= $short_name ?>"
						name="short_name" style="width: 25*8px;" autocomplete="off" data-container="body" data-placement="right"
						data-title="Это поле должно быть заполнено!" />
					<?php if ($short_name) { ?>
						<button id="btn-name-undo" class="btn btn-outline-primary px-2 d-none" title="Вернуть по-умолчанию: <?= $short_name ?>">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-return-left" viewBox="0 0 16 16">
								<path fill-rule="evenodd" d="M14.5 1.5a.5.5 0 0 1 .5.5v4.8a2.5 2.5 0 0 1-2.5 2.5H2.707l3.347 3.346a.5.5 0 0 1-.708.708l-4.2-4.2a.5.5 0 0 1 0-.708l4-4a.5.5 0 1 1 .708.708L2.707 8.3H12.5A1.5 1.5 0 0 0 14 6.8V2a.5.5 0 0 1 .5-.5" />
							</svg>
						</button>
					<?php } ?>
				</div>
				<p id="p-errorPageName" class="error p-0 d-none">Ошибка! Незаполненное поле!</p>
				<p id="p-errorPageNameLength" class="error p-0 d-none">Достигнуто ограничение по количеству символов!</p>
			</div>

			<div class="row align-items-start m-3 mb-4" style="height: 40px;">
				<div class="col-lg-3 row justify-content-left py-2">Семестр:</div>
				<div class="col-lg-4  px-0">
					<div class="btn-group shadow-0">
						<select id="select-semester" class="form-select" name="timestamp">
							<?php
							$future_year = getFutureGroupYear();
							$current_year = getCurrentStudyYear();
							for ($year = $current_year - 4; $year < $future_year; $year++) {
								if ($isNewPage) {
									echo generatePairOptionsSemester(true, $year, $new_page_year, $new_page_semester);
								} else {
									echo generatePairOptionsSemester($page, $year, $page['year'], $page['semester']);
								}
							}

							// Добавление в новый, не существующий в бд семестр
							if ($isNewPage) {
								echo generatePairOptionsSemester(true, $future_year, $new_page_year, $new_page_semester, "text-info");
							} else {
								echo generatePairOptionsSemester($page, $future_year, $page['year'], $page['semester'], "text-info");
							}
							?>

							<!-- Добавление вне семестровых разделов -->
							<option value="ВНЕ CЕМЕСТРА" class="text-secondary" <?= (isOutsideSemesterPage($Page, $new_page_year, $new_page_semester)) ? "selected" : "" ?>>ВНЕ CЕМЕСТРА</option>;
						</select>
					</div>
				</div>
			</div>


			<div class="row align-items-start mx-3 pe-group-upper">
				<div class="col-lg-3 row justify-content-left py-2">Преподаватели:</div>
				<div class="col-lg-9 d-flex flex-column px-0" style="gap: 10px;">
					<div id="teachers_container" class="d-flex flex-row justify-content-start flex-wrap">

					</div>
					<div class="align-items-start pe-group-lower mb-4">
						<!-- <div class="col-lg-3 row py-2"></div> -->
						<div class="col-lg-9 px-0">
							<div class="btn-group shadow-0">
								<select class="form-select" id="select_teacher">
									<option value="null">Выберите преподавателя</option>;
									<?php
									foreach ($all_teachers as $teacher) { ?>
										<option value="<?= $teacher['id'] ?>">
											<?= $teacher['fio'] ?>
										</option>;
									<?php }
									?>
								</select>
							</div>
						</div>
						<div class="col-lg-1">
						</div>
					</div>
				</div>
			</div>

			<div id="div-students-groups" class="row align-items-start mx-3 pe-group-upper  <?= (isOutsideSemesterPage($Page, $new_page_year, $new_page_semester)) ? "d-none" : "" ?>">
				<div class="col-lg-3 row justify-content-left py-2">Учебные группы:</div>
				<div class="col-lg-9 d-flex flex-column px-0" style="gap: 10px;">
					<div id="groups_container" class="d-flex flex-row flex-wrap justify-content-start"></div>
					<div class="align-items-start pe-group-lower mb-4">
						<div class="col-lg-9 px-0">
							<div class="btn-group shadow-0">
								<select class="form-select" name="page_group" id="select_groups">
									<option value="null">Выберите группу</option>;
									<?php
									foreach ($Groups as $Group) {
										$countAssignments = 0;
										if ($Page != null)
											$countAssignments = $Page->getCountActiveAssignmentsByGroup($Group->id); ?>
										<option value="<?= $Group->id ?>" class="d-flex justify-content-between" data-countAssignments="<?= $countAssignments ?>">
											<?= $Group->getTitle() ?>
										</option>
									<?php }
									echo "<option>Нет учебной группы</option>";
									?>
								</select>
							</div>
						</div>
						<div class="col-lg-1">
						</div>
					</div>
				</div>
			</div>

			<!-- <div id="div-students-groups" class="mb-4 <?= (isOutsideSemesterPage($Page, $new_page_year, $new_page_semester)) ? "d-none" : "" ?>">
				<div class="row align-items-start mx-3 pe-group-upper">
					<div class="col-lg-3 row justify-content-left py-2">Учебные группы:</div>
					<div id="groups_container" class="col-lg-9 d-flex align-items-center flex-wrap px-0" style="background: #fff8e0;"></div>
				</div>
				<div class="row align-items-start mx-3 pe-group-lower mb-4">
					<div class="col-lg-3 row py-2"></div>
					<div class="col-lg-9 px-0">
						<div class="btn-group shadow-0">
							<select class="form-select" name="page_group" id="select_groups">
								<option value="null">Выберите группу</option>;
								<?php
								foreach ($Groups as $Group) { ?>
									<option value="<?= $Group->id ?>" class="d-flex justify-content-between">
										<?= $Group->getTitle() ?>
									</option>
								<?php }
								echo "<option>Нет учебной группы</option>";
								?>
							</select>
						</div>
					</div>
				</div>
			</div> -->

			<div class="row align-items-start mx-3 pe-group-upper mb-4">
				<div class="col-lg-3 row justify-content-left py-2">Описание раздела:</div>
				<div class="col-lg-9 px-0">
					<div id="form-description" class="form-outline" onkeyup="descriptionChange()">
						<textarea id="textArea-description" class="form-control <?= 'active' ?>" rows="5" name="page-description" style="resize: none;"><?= ($Page) ? $Page->description : "" ?></textarea>
						<label id="label-textArea-description" class="form-label" for="textArea-description">Описание раздела</label>
						<script>
							const easyMDE = new EasyMDE({
								element: document.getElementById('textArea-description'),
								toolbar: ["bold", "italic", "heading-bigger", "heading-smaller",
									"|", "code", "quote", "|", "unordered-list", "ordered-list",
									"|", "link", "image", "|", "preview", "side-by-side",
									"|", "undo", "redo", "|", "guide"
								],
								previewImagesInEditor: true,
								sideBySideFullscreen: false,
								toggleFullscreen: false,
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
				<div class="col-lg-3 row justify-content-left py-2">Оформление:</div>
				<div class="col-lg-9 row container-fluid">
					<?php

					$query = select_color_theme($page_id);
					$result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
					$thems = pg_fetch_all($result);

					foreach ($thems as $key => $thema) {
						$bg_color = ($thema['bg_color'] != null) ? $thema['bg_color'] : "#000000";
						$disc_id = ($thema['disc_id'] != null) ? $thema['disc_id'] : -1; ?>

						<label id="label-colorTheme-<?= $thema['id'] ?>" class="label-theme col-lg-2 me-3 mb-3 " style="padding: 0px;">
							<div class="card theme-check-button" data-mdb-ripple-color="light" style="position: relative; cursor: pointer;">
								<div class="bg-image hover-zoom" style="border-radius: 10px;" onclick="event.preventDefault(); choosePageImage(<?= $thema['id'] ?>);">
									<img src="<?= $thema['src_url'] ?>" style="transition: all .1s linear; min-width: 100%; max-height: 120px;">
									<div id="mask-<?= $disc_id ?>" class="mask" style="background: <?= $bg_color ?>; transition: all .1s linear;"></div>
								</div>
								<?php if ($thema['status'] == 0) { ?>
									<div class="card_image_content" style="bottom:unset; top:0%; background: unset; z-index: 1; cursor: pointer;">
										<div class="d-flex justify-content-between" style="z-index: 2;">
											<div>
												<button class="d-none" onclick="event.preventDefault(); choosePageImage(<?= $thema['id'] ?>);"></button>
											</div>
											<div class="bg-white p-0" style="border-radius: 0px 10px 0px 10px!important; opacity: 0.8;">
												<button type="button" class="btn btn-white h-100 text-danger" style="box-shadow: unset; border-top-right-radius: 0px;" onclick="event.stopPropagation(); deletePageImage(<?= $thema['id'] ?>);">
													<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash-fill" viewBox="0 0 16 16">
														<path d="M2.5 1a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1H3v9a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V4h.5a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H10a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1zm3 4a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 .5-.5M8 5a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7A.5.5 0 0 1 8 5m3 .5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 1 0" />
													</svg>
												</button>
											</div>
										</div>
									</div>
								<?php } ?>
							</div>
							<input id="input-radio-<?= $thema['id'] ?>" type="radio" class="input-thema" name="color_theme_id" style="display: none;" value="<?= $thema['id'] ?>" <?php if (($Page == null && $key == 0) || ($Page != null && $thema['id'] == $page['color_theme_id'])) echo 'checked="checked"'; ?>>
							<div id="div-lineChoosed-<?= $thema['id'] ?>" class="checkmark <?php if (($Page == null && $key == 0) || ($Page != null && $thema['id'] == $page['color_theme_id'])) echo '';
																							else echo 'd-none'; ?>" style="background-color:<?= $bg_color ?>"></div>
						</label>

					<?php } ?>

					<label class="label-theme col-lg-2 me-3 mb-3" style="padding: 0px;">
						<div class="card theme-check-button w-100 h-100" data-mdb-ripple-color="light" style="position: relative; cursor: pointer;">
							<div class="btn btn-outline-primary py-2 px-4 w-100 h-100">
								<input id="input-image" type="file" name="image-file" style="display: none;">
								<div class="py-1">
									<div class="row py-2">
										<div class="d-flex justify-content-center align-self-center">
											<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-upload" viewBox="0 0 16 16" style="width:40%; height:40%;">
												<path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z" />
												<path d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V11.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708l3-3z" />
											</svg>
										</div>
									</div>
									<p class="text-black mb-0" style="font-size:xx-small;">Рекомендуемая пропорция <strong>4 к 3</strong></p>
								</div>
							</div>

						</div>
					</label>

				</div>
			</div>

			<?php if ($isNewPage) { ?>
				<input name="creator_id" value="<?= $au->getUserId() ?>" style="display: none;">
			<?php } ?>

			<div class="row mx-2">
				<div class="col-lg-3 row justify-content-left">
					<button id="btn-save" type="submit" name="action" value="save" class="btn btn-primary" data-mdb-ripple-color="dark">
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-all" viewBox="0 0 16 16">
							<path d="M8.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L2.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093L8.95 4.992zm-.92 5.14.92.92a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 1 0-1.091-1.028L9.477 9.417l-.485-.486z" />
						</svg>&nbsp;
						Сохранить
					</button>
				</div>

				<div class="col-lg-5">
					<button id="btn-save" type="submit" name="action" value="download" class="btn btn-outline-primary" data-mdb-ripple-color="dark">
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-download" viewBox="0 0 16 16">
							<path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5" />
							<path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z" />
						</svg>&nbsp;
						Скачать
					</button>

					<?php if ($page_id != 0) { ?>
						<button id="btn-delete" class="btn btn-outline-danger" style="color: red;" type="submit" name="action" value="delete">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash3-fill" viewBox="0 0 16 16">
								<path d="M11 1.5v1h3.5a.5.5 0 0 1 0 1h-.538l-.853 10.66A2 2 0 0 1 11.115 16h-6.23a2 2 0 0 1-1.994-1.84L2.038 3.5H1.5a.5.5 0 0 1 0-1H5v-1A1.5 1.5 0 0 1 6.5 0h3A1.5 1.5 0 0 1 11 1.5m-5 0v1h4v-1a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5M4.5 5.029l.5 8.5a.5.5 0 1 0 .998-.06l-.5-8.5a.5.5 0 1 0-.998.06m6.53-.528a.5.5 0 0 0-.528.47l-.5 8.5a.5.5 0 0 0 .998.058l.5-8.5a.5.5 0 0 0-.47-.528M8 4.5a.5.5 0 0 0-.5.5v8.5a.5.5 0 0 0 1 0V5a.5.5 0 0 0-.5-.5" />
							</svg>&nbsp;
							Удалить раздел
						</button>
					<?php } ?>
				</div>

			</div>
		</form>
	</main>
</body>
<!-- End your project here-->

<script type="text/javascript" src="js/PageHandler.js"></script>

<!-- Custom scripts -->
<script type="text/javascript">
	let teachers = new Set();
	let groups = new Set();

	var MAX_FILE_SIZE = null;
	fetch('config.json') // Путь к JSON-файлу
		.then(response => response.json())
		.then(data => {
			MAX_FILE_SIZE = data.MAX_FILE_SIZE
		})
		.catch(error => console.error('Ошибка:', error));


	let easyMDE_value = easyMDE.value();
	var original_description = easyMDE_value.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&");


	function getPageId() {
		if (PAGE_ID != null)
			return PAGE_ID;
		else {
			PAGE_ID = ajaxPageCreate(PAGE_ID);
			if (PAGE_ID == null)
				alert("Не удалось сохранить раздел!");
			return PAGE_ID;
		}
	}

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

	function timeout(ms) {
		return new Promise(resolve => setTimeout(resolve, ms));
	}
	async function sleep(ms, fn, ...args) {
		await timeout(ms);
		return fn(...args);
	}

	$(document).ready(function() {

		if (actual_teachers_json) {
			actual_teachers_json.forEach(function(r) {
				let fio = r.fio;
				add_element(document.getElementById("teachers_container"), fio, "teachers[]", "t", r.id);
				teachers.add(r.id);
			});
		}

		if (page_groups_json) {
			page_groups_json.forEach(function(r) {
				let name = r.name;
				add_element(document.getElementById("groups_container"), name, "groups[]", "g", r.id, r.countAssignments);
				groups.add(r.id);
			});
		}

		if (isNewPage && !isAdmin) {
			add_teacher(user_id);
		}

		const MAX_SHORT_NAME_LENGTH = 25;
		var inputName = document.getElementById('input-name');
		const originalName = inputName.value;
		if (document.getElementById('btn-name-undo')) {
			var btnNameUndo = document.getElementById('btn-name-undo');
			btnNameUndo.onclick = function(event) {
				event.preventDefault();
				inputName.value = originalName;
				btnNameUndo.classList.add("d-none");
			};
		}

		function showNameUndo() {
			if (btnNameUndo)
				btnNameUndo.classList.remove("d-none");
		}

		function hideNameUndo() {
			if (btnNameUndo)
				btnNameUndo.classList.add("d-none");
		}

		inputName.oninput = async function(event) {

			if (inputName.value.length > MAX_SHORT_NAME_LENGTH) {

				inputName.value = inputName.value.slice(0, MAX_SHORT_NAME_LENGTH);
				document.getElementById("p-errorPageNameLength").classList.remove("d-none");
				sleep(2000,
					async () => {
						document.getElementById("p-errorPageNameLength").classList.add("d-none");
					}, );
			}

			if (inputName.value != originalName)
				showNameUndo()
			else
				hideNameUndo()
		}

		inputName.onblur = function(event) {
			inputName.value = inputName.value.trim();
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
			if (new_file.size >= MAX_FILE_SIZE * 1024 * 1024) {
				alert(`Ошибка загрузки файла! Файл слишком большой (${(new_file.size / (1024 * 1024)).toFixed(2)} MB). Максимум: ${MAX_FILE_SIZE} MB.`);
				return;
			}
			// var img = new Image();
			// img.onload = function() {
			// 	var width = this.width;
			// 	var hight = this.height;
			// }
			// img.src = new_file;
			let _URL = window.URL || window.webkitURL;
			let img = new Image();
			var objectUrl = _URL.createObjectURL(new_file);
			img.onload = function() {
				page_id = getPageId();
				$('#input-pageId').val(page_id);
				saveFieldsWithoutChecking();
				ajaxAddColorTheme(new_file);
				// if (0.7 <= size && size <= 0.9) {
				// } else {
				// 	alert("Некорректные размеры картинки!");
				// }
			};
			img.src = objectUrl;


			// document.location.reload();
		} else {
			alert("Ошибка загрузки файла! Файл должен быть с расширением PNG, JPG, JPEG или GIF!");
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
		formData.append('page_id', getPageId());
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


	function choosePageImage(ax_color_theme_id) {
		$.each($('.input-thema'), function(index, elem) {
			elem.checked = false;
		});
		$('.checkmark').addClass("d-none");

		$('#input-radio-' + ax_color_theme_id)[0].checked = true;
		console.log($('#input-radio-' + ax_color_theme_id)[0]);

		$('#div-lineChoosed-' + ax_color_theme_id).removeClass("d-none");
	}

	function deletePageImage(ax_color_theme_id) {
		ajaxDeleteColorTheme(ax_color_theme_id);
		document.location.reload();
	}

	function ajaxDeleteColorTheme(color_theme_id) {
		var formData = new FormData();

		formData.append('flag-deleteColorTheme', true);
		formData.append('color_theme_id', color_theme_id);
		formData.append('page_id', getPageId());

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

	// 
	// 
	// 

	var selectGroup = document.getElementById("select_groups");
	selectGroup.addEventListener('change', function(event) {
		event.preventDefault();
		add_groups(this.options[this.selectedIndex].dataset.countassignments);
		sleep(400,
			async () => {
				selectGroup.value = "null";
			}, );
	});

	var selectTeacher = document.getElementById("select_teacher");
	selectTeacher.onchange = function(event) {
		event.preventDefault();
		add_teacher();
		sleep(400,
			async () => {
				selectTeacher.value = "null";
			}, );

	};

	function add_teacher(teacher_id = null) {
		if (teacher_id == null)
			teacher_id = parseInt($('#select_teacher').val());
		if (teacher_id == "null")
			return;

		if (teachers.has(teacher_id))
			return;

		var name;
		Array.from($('#select_teacher').children()).forEach((option) => {
			if (option.value == teacher_id) {
				name = option.innerText.trim();
				return;
			}
		});

		console.log(name);
		add_element(document.getElementById("teachers_container"), name, "teachers[]", "t", teacher_id);
		teachers.add(teacher_id);
	}

	function add_groups(countAssignments) {
		let group_id = parseInt($('#select_groups').val());
		if (group_id == "null")
			return;

		var name;
		Array.from($('#select_groups').children()).forEach((option) => {
			if (option.value == group_id) {
				name = option.innerText.trim();
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

		add_element(document.getElementById("groups_container"), name, "groups[]", "g", group_id, countAssignments);

		groups.add(group_id);
	}

	function add_element(parent, name, tag, set, id, countAssignments = null) {
		id = parseInt(id)

		let element = document.createElement("div");

		element.setAttribute("class", "d-flex justify-content-between align-items-center p-2 me-4 my-1 badge badge-primary text-wrap teacher-element");
		element.id = "t-" + id;

		let text = document.createElement("span");
		text.classList.add("p-1", "me-1");
		text.setAttribute("style", "font-size: 15px; /*border-right: 1px solid; border-color: grey;*/");
		text.innerText = name;

		if (countAssignments != null && countAssignments > 0) {
			let span_countAssignments = document.createElement("span");
			span_countAssignments.classList.add("p-1", "ms-1", "badge", "badge-danger");
			span_countAssignments.setAttribute("style", "font-size: 15px; cursor: help;");
			// span_countAssignments.setAttribute("title", "Количество активных Назначений")
			span_countAssignments.innerText = countAssignments;

			var tooltip = document.createElement('div');
			tooltip.className = 'badge badge-danger';
			tooltip.setAttribute("style", "font-size: 13px; position: absolute;");
			element.appendChild(tooltip);

			span_countAssignments.addEventListener('mouseenter', (e) => {
				tooltip.textContent = "Количество активных Назначений";
				tooltip.style.display = 'block';

				let rect = e.target.getBoundingClientRect();
				tooltip.style.left = `${rect.left + window.scrollX}px`;
				tooltip.style.top = `${rect.bottom + window.scrollY}px`;
			});

			span_countAssignments.addEventListener('mouseleave', () => {
				tooltip.style.display = 'none';
			});

			text.append(span_countAssignments);
		}

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
			if (tag == "groups[]") {
				if (countAssignments != null && countAssignments > 0) {
					let flag = confirm("Вы уверены что хотите открепить группу от данного раздела? Для неё существует ещё " + countAssignments + " активных назначений!");
					if (flag) {
						groups.delete(id);
					}
				} else {
					groups.delete(id);
				}
			} else
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
			$.each($('.input-thema'), function(index, elem) {
				console.log(elem.id + " " + elem.checked);
			});
			$('#pageedit_action').submit();
		}
	});

	function saveFieldsWithoutChecking() {
		$('#pageedit_action').append('<input type="text" name="action" value="save" hidden>');
		$('#pageedit_action').append('<input type="text" name="status-backLocation" value="page" hidden>');
		$('#pageedit_action').submit();
	}

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
		if ($(this).val() == "ВНЕ CЕМЕСТРА") {
			$('#div-students-groups').addClass("d-none");
		} else {
			$('#div-students-groups').removeClass("d-none");
		}
	});
</script>

</html>
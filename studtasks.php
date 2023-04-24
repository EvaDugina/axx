<!DOCTYPE html>

<?php
require_once("common.php");
require_once("dbqueries.php");
require_once("utilities.php");

// защита от случайного перехода
$au = new auth_ssh();
if ($au->isTeacher()) {
	echo "Некорректное обращение";
	http_response_code(400);
	exit;
}

// Обработка некорректного перехода между страницами
if (isset($_GET['page']) && is_numeric($_GET['page'])){
  $page_id = $_GET['page'];

  $query = select_discipline_page($page_id);
	$result = pg_query($dbconnect, $query);
	$page = pg_fetch_all($result)[0];
	$disc_id = $page['disc_id'];

	$query = select_discipline_name($disc_id);
	$result = pg_fetch_assoc(pg_query($dbconnect, $query))['name'];
	$discipline_name = strtoupper((string) "$result");

	$semester = $page['year'] . "/" . ($page['year']+1).convert_sem_from_number($page['semester']);
	$short_name = $page['short_name'];
} else {
	header('Location:mainpage_student.php');
  exit;
}

$student_id = $_SESSION['hash'];

$actual_teachers = [];
$page_groups = [];


// Подсчёт количества выполненных заданий
$count_succes_tasks = 0;
$count_tasks = 0;
$query_tasks = select_page_tasks_with_assignment($page_id, 1, $student_id);
$result_tasks = pg_query($dbconnect, $query_tasks);
$tasks = pg_fetch_all($result_tasks);
if (!$result_tasks || pg_num_rows($result_tasks) < 1);
else {
  foreach($tasks as $key => $task) { 
      if ($task['status'] == 4) $count_succes_tasks++;
      $count_tasks++;
    }
}

?>

<html lang="en">

<?php 
show_head("Страница предмета ". $page['short_name']);
show_header($dbconnect, 'Задания по дисциплине', 
		array($page['short_name'] => 'studtasks.php?page=' . $page_id)); ?>

<body style="overflow-x: hidden;">

	<main class="container-fluid overflow-hidden">
		<div class="pt-5 px-4">
			<div class="row">
				<div class="col-md-6 d-flex">
					<h3><?php echo $short_name; ?></h4>
					<p style="color: grey; margin-left: 10px; margin-top:17px; "><?php echo $count_succes_tasks . "/" . $count_tasks; ?></p>
				</div>
			</div>
			<div class="pt-4 px-5">
				<div class="row">
					<div class="col-md-offset-2 col-md-5">
						<?php if (!$result_tasks || pg_num_rows($result_tasks) < 1)
							echo '<h5>Задания по этой дисциплине отсутствуют</h5>'; 
							else  echo '<h5>Название задания</h5>';?>
					</div>
				</div>

				<div class="row pt-3">
					<div class="col-md-11 col-md-push-1 w-100">
						<div class="list-group list-group-flush" id="list-tab" role="tablist">
							<?php
							if (!$result_tasks || pg_num_rows($result_tasks) < 1);
							else {
                foreach($tasks as $key => $task) { 
									$query_assignment = select_task_assignment_with_limit($task['id'], $_SESSION['hash']);
									$result_assignment = pg_query($dbconnect, $query_assignment);
									$row_assignment = pg_fetch_assoc($result_assignment);

									$status = false;
									if ($result_assignment && $row_assignment && $row_assignment['status_code']!=0) {
										$date_finish = "";
										$text_status = $row_assignment['status_text'];
										if ($row_assignment['finish_limit'] != null)
											if ($row_assignment['status_code'] == 1 || $row_assignment['status_code'] == 4)
												$date_finish = "(". visibility_to_text($row_assignment['status_code']) . ")";
											else 
												$date_finish = "до " . date('d.m.y', strtotime($row_assignment['finish_limit']));
										if ($row_assignment['status'] == 4){
											// подтянуть информацию об оценке или изменить таблицу
											$text_status = 'Проверено (оценка: '. $row_assignment['mark'] .')';
											$status = true;
										} else if ($row_assignment['status'] == 1)
											$text_status = 'Отправлено на проверку';?>
										
										<button class="list-group-item list-group-item-action d-flex justify-content-between mb-3" 
										<?php if($row_assignment['status_code'] == 1 || $row_assignment['status_code'] == 4) echo "disabled"; ?>
										onclick="window.location='<?='taskchat.php?assignment='. $row_assignment['id']?>';"
										style="cursor: pointer; border-width: 1px; padding: 0px; border-radius: 5px;"
										id="studtasks-elem-<?php echo $key + 1; ?>">
											<p class="col-md-5" style="margin: 10px; margin-left: 15px;"> <?php echo $task['title']; ?></p>
											<p class="col-md-2" style="margin: 10px; text-align: center;"><?php echo $date_finish;?></p>
											<p class="col-md-2" style="margin: 10px; text-align: center;"><?php echo $text_status;?></p>
											<div class="form-check" style="margin: 10px;">
												<input class="form-check-input" type="checkbox" value="" id="flexCheckChecked" <?php if($status) echo 'checked'; else echo 'unchecked';?> disabled>
												<label class="form-check-label" for="flexCheckChecked"></label>
											</div>
											<!-- ВОЗМОЖНЫЕ ДОРАБОТКИ ПО МАКЕТУ
											<button type="button" style="color:crimson; border-width: 0px; background: none;"> <i class="fas fa-file-download fa-lg"></i></button>
											<button type="button" class="btn btn-outline-primary" style="color: darkcyan; background: white; border-color: darkcyan; margin-top: 0px; margin-bottom: 0px;"> Загрузить </button>
											-->
									</button>
									<?php
									}
								}
							} ?>
						</div>
					</div>
				</div>



			</div>
		</div>
	</main>
	

</body>

</html>
<!DOCTYPE html>

<?php
require_once("common.php");
require_once("dbqueries.php");
require_once("utilities.php");

// защита от случайного перехода
$au = new auth_ssh();
if ($au->isTeacher() || !$au->loggedIn()) {
	echo "Некорректное обращение";
	http_response_code(400);
	exit;
}

$student_id = $au->getUserId();

// Обработка некорректного перехода между страницами
if (isset($_GET['page']) && is_numeric($_GET['page'])){
  $page_id = $_GET['page'];
  $Page = new Page((int)$page_id);
} else {
	header('Location:mainpage_student.php');
  exit;
}

$count_succes_tasks = $Page->getCountSuccessAssignments($student_id);
$count_tasks = $Page->getCountActiveAssignments($student_id);


?>

<html lang="en">

<?php 
show_head("Страница предмета ". $Page->name);
show_header($dbconnect, 'Задания по дисциплине', 
		array($Page->name => 'studtasks.php?page=' . $page_id)); ?>

<body style="overflow-x: hidden;">

	<main class="container-fluid overflow-hidden">
		<div class="pt-5 px-4">
			<div class="row">
				<div class="col-md-6 d-flex">
					<h3><?php echo $Page->name; ?></h4>
					<p style="color: grey; margin-left: 10px; margin-top:17px; "><?php echo $count_succes_tasks . "/" . $count_tasks; ?></p>
				</div>
			</div>
			<div class="pt-4 px-5">
				<div class="row">
					<div class="col-md-offset-2 col-md-5">
						<?php if ($count_tasks == 0)
							echo '<h5>Задания по этой дисциплине отсутствуют</h5>'; 
						else  echo '<h5>Название задания</h5>';?>
					</div>
				</div>

				<div class="row pt-3">
					<div class="col-md-11 col-md-push-1 w-100">
						<div class="list-group list-group-flush" id="list-tab" role="tablist">
							<?php
							$key = 0;
							foreach($Page->getActiveTasks() as $Task) {
								foreach($Task->getVisibleAssignmemntsByStudent($student_id) as $Assignment) {
									if ($Assignment->finish_limit != null){
										$date_finish = "до " . convert_mtime($Assignment->finish_limit);
									} else
										$date_finish = "";
									if ($Assignment->isCompleted()){
										$text_status = 'Проверено (оценка: '. $Assignment->mark .')';
									} else {
										$text_status = status_to_text($Assignment->status);
									}
									?>
									<button class="list-group-item list-group-item-action d-flex justify-content-between mb-3"
									onclick="window.location='taskchat.php?assignment=<?=$Assignment->id?>'"
									style="cursor: pointer; border-width: 1px; padding: 0px; border-radius: 5px;"
									id="studtasks-elem-<?php echo $key + 1; ?>">
											<p class="col-md-5" style="margin: 10px; margin-left: 15px;"> <?=$Task->title; ?></p>
											<p class="col-md-2" style="margin: 10px; text-align: center;"><?=$date_finish;?></p>
											<p class="col-md-2" style="margin: 10px; text-align: center;"><?=$text_status;?></p>
											<div class="form-check" style="margin: 10px;">
												<input class="form-check-input" type="checkbox" value="" id="flexCheckChecked" 
												<?=($Assignment->isCompleted()) ? 'checked' : 'unchecked'?> disabled>
												<label class="form-check-label" for="flexCheckChecked"></label>
											</div>
											<!-- ВОЗМОЖНЫЕ ДОРАБОТКИ ПО МАКЕТУ
											<button type="button" style="color:crimson; border-width: 0px; background: none;"> <i class="fas fa-file-download fa-lg"></i></button>
											<button type="button" class="btn btn-outline-primary" style="color: darkcyan; background: white; border-color: darkcyan; margin-top: 0px; margin-bottom: 0px;"> Загрузить </button>
											-->
									</button>
									<?php $key++;
								}
							}?>
						</div>
					</div>
				</div>



			</div>
		</div>
	</main>
	

</body>

</html>
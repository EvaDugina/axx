<!DOCTYPE html>

<?php
require_once("common.php");
require_once("dbqueries.php");
require_once("utilities.php");


$page_id = 0;
$discipline_name = "";
$disc_id = 0;
$semester = "";
$short_name = "";

$actual_teachers = [];
$page_groups = [];

if (array_key_exists('page', $_REQUEST)) {
	$page_id = $_REQUEST['page'];

	$query = select_discipline_page($page_id);
	$result = pg_query($dbconnect, $query);
	$page = pg_fetch_all($result)[0];
	$disc_id = $page['disc_id'];

	$query = select_all_disciplines();
	$result = pg_query($dbconnect, $query);
	$disciplines = pg_fetch_all($result);

	foreach ($disciplines as $key => $discipline) {
		if ($discipline['id'] == $page['disc_id']){
			$discipline_name = $discipline['name'];
			$discipline_name = strtoupper((string) "$discipline_name");
			break;
		}
	}

	$semester = $page['year'] . "/" . convert_sem_from_id($page['semester']);
	$short_name = $page['short_name'];

	// Подсчёт количества выполненных заданий
	$count_succes_tasks = 0;
	$count_tasks = 0;
	$query_tasks = select_page_tasks($page_id, 1);
	$result_tasks = pg_query($dbconnect, $query_tasks);
	if (!$result_tasks || pg_num_rows($result_tasks) < 1);
	else {
		$i = 0;
		while ($row_task = pg_fetch_assoc($result_tasks)) {
			$count_tasks++; 
			$query_assignment = select_task_assignment($row_task['id'], $_SESSION['hash']);
			$result_assignment = pg_query($dbconnect, $query_assignment);
			if ($result_assignment && pg_num_rows($result_assignment) >= 1) {
				$row_task_assignment = pg_fetch_assoc($result_assignment);
				if ($row_task_assignment['status_code'] == 3) $count_succes_tasks++;
			}
		}
	}

} else {
	$page_id = 0;
	echo "Некорректное обращение";
	http_response_code(400);
	exit;
}
?>

<html lang="en">

<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
	<meta http-equiv="x-ua-compatible" content="ie=edge" />
	<title>536 Акселератор</title>
	<!-- MDB icon -->
	<link rel="icon" href="img/mdb-favicon.ico" type="image/x-icon" />
	<!-- Font Awesome -->
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.2/css/all.css" />
	<!-- Google Fonts Roboto -->
	<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" />
	<!-- MDB -->
	<link rel="stylesheet" href="css/mdb.min.css" />
	<link rel="stylesheet" href="css/rdt.css" />
</head>

<?php 
show_header_2($dbconnect, 'Задания по дисциплине', 
		array($page['short_name'] => 'studtask.php?page=' . $page_id)); ?>

<body style="overflow-x: hidden;">

	<main class="container-fluid overflow-hidden">
		<div class="pt-5 px-4">
			<div class="row">
				<div class="col-md-6 d-flex">
					<h3><?php echo $discipline_name; ?></h4>
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
					<div class="col-md-11 col-md-push-1">
						<div class="list-group list-group-flush" id="list-tab" role="tablist">
							<?php
							$query_tasks = select_page_tasks($page_id, 1);
							$result_tasks = pg_query($dbconnect, $query_tasks);
							if (!$result_tasks || pg_num_rows($result_tasks) < 1);
							else {
								$i = 0;
								while ($row_task = pg_fetch_assoc($result_tasks)) { 
									$query_assignment = select_task_assignment($row_task['id'], $_SESSION['hash']);
									$result_assignment = pg_query($dbconnect, $query_assignment);

									$date_finish = "";
									$text_status = 'Ответ не загружен';
									$status = false;
									if ($result_assignment && pg_num_rows($result_assignment) >= 1) {
										$row_assignment = pg_fetch_assoc($result_assignment);
										if ($row_assignment['finish_limit'] != null)
											$date_finish = "до " . date('d.m.y', strtotime($row_assignment['finish_limit']));
										if ($row_assignment['status_code'] == 3){
											// подтянуть информацию об оценке или изменить таблицу
											$text_status = 'Проверено (оценка: '. $row_assignment['mark'] .')';
											$status = true;
										} else if ($row_assignment['status_code'] == 5)
											$text_status = 'Отправлено на проверку';
									}
									?>
									
									<div class="list-group-item list-group-item-action d-flex justify-content-between bd-highlight mb-3" onclick="window.location='<?='taskchat.php?id='. $row_task['id'] . '&page=' . $page_id?>';"
									style="cursor: pointer; margin-top: 10px 0; border-width: 1px; padding: 0px; padding-right: 0px; border-radius: 5px;"
									id="studtasks-elem-<?php echo $i + 1; ?>" data-mdb-toggle="list" href="<?='taskchat.php?id='. $row_task['id']?>" role="tab" aria-controls="list-<?php echo $i + 1; ?>">
										<p class="col-md-5" style="margin: 10px; margin-left: 15px;"> <?php echo $row_task['title']; ?></p>
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
									</div>

							<?php $i++;
								}
							} ?>
						</div>
					</div>
				</div>



			</div>
		</div>
	</main>
	<!-- End your project here-->

	<!-- MDB -->
	<script type="text/javascript" src="js/mdb.min.js"></script>
	<!-- Custom scripts -->
	<script type="text/javascript"></script>

</body>

</html>
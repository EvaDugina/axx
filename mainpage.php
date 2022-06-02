<?php 
require_once("common.php");
require_once("dbqueries.php");
require_once("settings.php");

// в ax_page disc_id у эргономики должно быть -4;

//show_header('Дэшборд преподавателя', array('Дэшборд преподавателя' => 'mainpage.php'));
//show_header_2($dbconnect, 'Дэшборд преподавателя', array('Дэшборд преподавателя' => 'mainpage.php'));

$result = pg_query($dbconnect, 'select id, short_name, disc_id, year from ax_page');
$disciplines=pg_fetch_all($result);
$result1=pg_query($dbconnect, 'select count(id) from ax_page');
$disc_count=pg_fetch_all($result1);

function full_name($discipline_id, $dbconnect) {
	$query = 'select name from discipline where id =' .$discipline_id;
	return pg_query($dbconnect, $query);
}
?>

<html> 
	<head>
		<title>Дашборд преподавателя</title>
		<meta charset="UTF-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
		<meta http-equiv="x-ua-compatible" content="ie=edge" />
		<link rel="stylesheet" href="css/main.css">
		<!-- MDB -->
		<link rel="stylesheet" href="css/mdb.min.css" />
		<!-- extra -->
		<link rel="stylesheet" href="css/accelerator.css" />
		<!-- MDB icon -->
		<link rel="icon" href="img/mdb-favicon.ico" type="image/x-icon" />
		<!-- Font Awesome -->
		<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.2/css/all.css" />
		<!-- Google Fonts Roboto -->
		<link
			rel="stylesheet"
			href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap"
		/>
	</head>

	<?php 
	show_head($page_titile='');
	show_header_2($dbconnect, 'Дэшборд преподавателя', array('Дэшборд преподавателя' => 'mainpage.php')); ?>
	<body>
		<main class="justify-content-start" style="margin-bottom: 30px;">
			<?php
				array_multisort(array_column($disciplines, 'year'), SORT_DESC, $disciplines);
				$now_year = $disciplines[0]['year']; // first year in database after sort function
			?>
			<h2 class="row" style="margin-top: 30px; margin-left: 50px;"> <?php echo $now_year; ?> год </h2><br>
			<div class="container">
				<div class="row g-5 container-fluid">
					<?php 
						foreach($disciplines as $discipline) {
							$query_notify = select_notify_by_page($discipline['id']);
                                        		$result_notify = pg_query($dbconnect, $query_notify);
							$notify_count = pg_fetch_assoc($result_notify);

							if ($now_year != $discipline['year']) { ?>
								<div class="col-2 align-self-center popover-message-message-stud">
									<a href="pageedit.php?add-page" type="button" class="btn btn-link"><i class="fas fa-plus-circle" style="font-size: 30px;"></i></a><br>
									<a href="pageedit.php?add-page">Добавить новый предмет</a>
								</div>
								</div>
								</div>
								<?php $now_year = $discipline['year'];?>
								<h2 class="row" style="margin-top: 30px; margin-left: 50px;"> <?php echo $now_year; ?> год </h2><br>
								<div class="container">
									<div class="row g-5 container-fluid">
							<?php } ?>
							<div class="col-3">
								<div class="popover-message-message-stud" role="listitem">
									<div class="popover-body">
										<div class="d-flex justify-content-end">
											<?php $page_id = $discipline['id']; ?>   
											<a href="pageedit.php?page=<?php echo $page_id; ?>">   
											<button type="button" class="btn btn-link"><i class="fas fa-pencil-alt"></i></button>
											</a>
										</div>
										<?php 
										$result = full_name($discipline['disc_id'], $dbconnect);
										$full_name = pg_fetch_all($result);
										?>
										<div class="p-3 popover-header">
											<a href="preptable.php?page=<?php echo $page_id; ?>"><?php echo $discipline['short_name']; ?></a><br>
										</div>
										<div class="d-flex justify-content-between" style="margin-top: 30px;">
											<span>Сообщение</span>
											<button class="btn btn-link btn-sm" style="width: 55px"><i class="fas fa-bell fa-lg"></i><span 
												<?php if($notify_count['count'] > 0){?> class="badge rounded-pill badge-notification bg-danger"><?php } 
												else { ?> class="badge rounded-pill badge-notification" style="background-color: green;"><?php } echo $notify_count['count'];?></span>
											</button>
										</div>
									</div>
								</div>
							</div>
						<?php } ?>
						<div class="col-2 align-self-center popover-message-message-stud">
							<a href="pageedit.php?add-page" type="button" class="btn btn-link"><i class="fas fa-plus-circle" style="font-size: 30px;"></i></a><br>
							<a href="pageedit.php?add-page">Добавить новый предмет</a>
						</div>
		</main>
	</body>
</html>
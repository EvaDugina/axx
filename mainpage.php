<?php 
require_once("common.php");
require_once("dbqueries.php");
require_once("settings.php");

// защита от случайного перехода
$au = new auth_ssh();
if (!$au->isAdmin() && !$au->isTeacher()){
	$au->logout();
	header('Location:login.php');
}

$result = pg_query($dbconnect, 'select id, short_name, disc_id, year from ax_page');
$pages=pg_fetch_all($result);
$result1=pg_query($dbconnect, 'select count(id) from ax_page');
$disc_count=pg_fetch_all($result1);

function full_name($discipline_id, $dbconnect) {
	$query = 'select name from discipline where id =' .$discipline_id;
	return pg_query($dbconnect, $query);
}
?>

<html> 
	
	<link rel="stylesheet" href="css/main.css">

	<?php 
	show_head("Дашборд преподавателя");
	show_header($dbconnect, 'Дэшборд преподавателя', array('Дэшборд преподавателя' => 'mainpage.php')); ?>
  <body>
		<main class="justify-content-start" style="margin-bottom: 30px;">
			<?php
				array_multisort(array_column($pages, 'year'), SORT_DESC, $pages);
				$now_year = $pages[0]['year']; // first year in database after sort function
			?>
			<h2 class="row" style="margin-top: 30px; margin-left: 50px;"> <?php echo $now_year; ?> год </h2><br>
			<div class="container">
				<div class="row g-5 container-fluid">
					<?php 
						foreach($pages as $page) {
							$query = select_notify_count_by_page_for_mainpage($page['id']);
                                        		$result = pg_query($dbconnect, $query);
							$notify_count = pg_fetch_assoc($result);

							$query = select_notify_by_page_for_mainpage($_SESSION['hash'], $page['id']);
                                        		$result = pg_query($dbconnect, $query);
							$array_notify = pg_fetch_all($result);

							if ($now_year != $page['year']) { ?>
								<div class="col-2 align-self-center popover-message-message-stud" 
								style="cursor: pointer; padding: 0px;" onclick="window.location='pageedit.php?add-page'">
									<a class="btn btn-link" href="pageedit.php?add-page" type="button" 
									style="width: 100%; height: 100%; padding-top: 20%;">
										<div class="row">
											<i class="fas fa-plus-circle mb-2 align-self-center" style="font-size: 30px;"></i><br>
											<span class="align-self-center">Добавить новый предмет</span>
										</div>
									</a>
								</div>
								</div>
								</div>
								<?php $now_year = $page['year'];?>
								<h2 class="row" style="margin-top: 30px; margin-left: 50px;"> <?php echo $now_year; ?> год </h2><br>
								<div class="container">
									<div class="row g-5 container-fluid">
							<?php } ?>
							<div class="col-3">
								<div class="popover-message-message-stud" role="listitem">
									<div class="popover-body">
										<div class="d-flex justify-content-between">
											<?php $page_id = $page['id']; ?>   
											<a href="pageedit.php?page=<?php echo $page_id; ?>">   
                        <button type="button" class="btn btn-link">
                          <i class="fas fa-pencil-alt"></i>
                        </button>
											</a>
                      <a href="preptasks.php?page=<?php echo $page_id; ?>">   
                        <button type="button" class="btn btn-link">
                          <i class="fa-solid fa-file-pen"></i>
                        </button>
											</a>
										</div>
										<?php 
										$result = full_name($page['disc_id'], $dbconnect);
										$full_name = pg_fetch_all($result);
										?>
										<div class="p-3 popover-header">
											<a href="preptable.php?page=<?php echo $page_id; ?>"><?php echo $page['short_name']; ?></a><br>
										</div>
										<div class="d-flex justify-content-between" style="margin-top: 30px;">
											<span>Посылки студентов</span>
											<button class="btn btn-link btn-sm" style="width: 55px" href="#" id="navbarDropdownMenuLink1" role="button" data-mdb-toggle="dropdown" aria-expanded="false">
												<i class="fas fa-bell fa-lg"></i><span <?php if ($notify_count['count'] > 0) {?> 
													class="badge rounded-pill badge-notification bg-danger"><?php } 
												else { ?> class="badge rounded-pill badge-notification" style="background-color: green;"><?php } echo $notify_count['count'];?></span>
											</button>
											<ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownMenuLink1" style="z-index:99999999; ">
												<?php if ($notify_count > 0 && $array_notify) { $i=0;
													foreach ($array_notify as $notify) { $i++; ?>
														<li class="dropdown-item" <?php if($i != $notify_count) echo 'style="border-bottom: 1px solid;"'?>> 
														<a <?php 
														if($au->isTeacher()){ echo 'style="color: black;"';?>
															href="taskchat.php?task=<?php echo $notify['id']?>&page=<?php echo $notify['page_id'];?>&id_student=<?php echo $notify['student_user_id'];?>" > 
														<?php
														} else if ($au->isAdmin());
														else {  
															if($notify['status_code'] == 2) echo 'style="color: red;"';
															else if($notify['status_code'] == 3) echo 'style="color: green;"';
															else echo 'style="color: black;"';?>
															href="studtasks.php?page=<?php echo $notify['page_id'];?>" > <?php
														} ?>

														<?php 
														if ($au->isTeacher()) {
															echo '<span style="border-bottom: 1px solid;">'. $notify['middle_name']. " " .$notify['first_name']. " (". $notify['short_name']. ")" .'</span>';?><br><?php echo $notify['title'];
														} else {
															echo '<span style="border-bottom: 1px solid;">'.$notify['short_name'] .'</span>';?><br><?php echo $notify['title']; 
														}?>
														</a>
														</li>
													<?php } 
												}?>
											</ul>
										</div>
									</div>
								</div>
							</div>
						<?php } ?>
						<div class="col-2 align-self-center popover-message-message-stud" 
						style="cursor: pointer; padding: 0px;" onclick="window.location='pageedit.php?add-page'">
							<a class="btn btn-link" href="pageedit.php?add-page" type="button" 
							style="width: 100%; height: 100%; padding-top: 20%;">
								<div class="row">
									<i class="fas fa-plus-circle mb-2 align-self-center" style="font-size: 30px;"></i><br>
									<span class="align-self-center">Добавить новый предмет</span>
								</div>
							</a>
						</div>
		</main>
	</body>
</html>
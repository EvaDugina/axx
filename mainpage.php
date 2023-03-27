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

// $result = pg_query($dbconnect, 'select id, short_name, disc_id, get_semester(year, semester) sem, year y, semester s from ax_page order by y desc, s desc');

if ($au->isTeacher())
  $result = pg_query($dbconnect, select_pages_for_teacher($_SESSION['hash']));
else 
  $result = pg_query($dbconnect, select_pages_for_admin());

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
				// array_multisort(array_column($pages, 'y'), SORT_DESC, $pages);
				//array_multisort(array_map(function($element){return $element['y'];}, $pages), SORT_DESC, $pages);
				$curr_sem = $pages[0]['sem']; // first sem 
				$curr_y = $pages[0]['y'];
				$curr_s = $pages[0]['s'];
			?>
			<h2 class="row" style="margin-top: 30px; margin-left: 50px;"> <?php echo $curr_sem; ?></h2><br>
			<div class="container">
				<div class="row g-5 container-fluid">
					<?php foreach($pages as $page) {
            $query = select_notify_count_by_page_for_mainpage($page['id']);
                                          $result = pg_query($dbconnect, $query);
            $notify_count = pg_fetch_assoc($result);

            $query = select_notify_by_page_for_mainpage($_SESSION['hash'], $page['id']);
                                          $result = pg_query($dbconnect, $query);
            $array_notify = pg_fetch_all($result);

            $result = full_name($page['disc_id'], $dbconnect);
            $full_name = pg_fetch_all($result)[0]['name'];

            $page_id = $page['id'];

            if ($curr_sem != $page['sem']) { ?>
              <div class="col-2 align-self-center popover-message-message-stud" 
              style="cursor: pointer; padding: 0px;" onclick="window.location='pageedit.php?addpage'">
                <a class="btn btn-link" href="pageedit.php?addpage=1&year=<?=$curr_y?>&sem=<?=$curr_s?>" type="button" 
                style="width: 100%; height: 100%; padding-top: 20%;">
                  <div class="row">
                    <i class="fas fa-plus-circle mb-2 align-self-center" style="font-size: 30px;"></i><br>
                    <span class="align-self-center">Добавить новый раздел</span>
                  </div>
                </a>
              </div>
              </div>
              </div>
              <?php 
                $curr_sem = $page['sem'];
                $curr_y = $page['y'];
                $curr_s = $page['s'];
              ?>
              <h2 class="row" style="margin-top: 30px; margin-left: 50px;"> <?php echo $curr_sem; ?></h2><br>
              <div class="container">
                <div class="row g-5 container-fluid">
            <?php } ?>


            <div class="col-xs-12 col-sm-12 col-md-6 col-xl-3">
              <div id="card_subject" class="card" style="border-radius: 0px 0px 10px 10px!important;">
                  <div data-mdb-ripple-color="light" style="position: relative;">
                      <div class="bg-image" style="cursor: pointer;" onclick="window.location='preptable.php?page=<?=$page_id?>'">
                          <img src="<?=$page['src_url']?>" alt="ИНФОРМАТИКА" style="transition: all .1s linear; height: 200px;">
                          <div class="mask" style="transition: all .1s linear;"></div>
                      </div>
                      <div class="card_image_content" style="bottom:unset; top:0%; background: unset; z-index: 1; cursor: pointer;"
                       onclick="window.location='preptable.php?page=<?=$page_id?>'">
                        <div class="d-flex justify-content-between" style="z-index: 2;"> 
                          <a class="bg-white p-0" style="border-radius: 10px 0px 10px 0px!important; opacity: 0.8;" 
                          href="pageedit.php?page=<?php echo $page_id; ?>">   
                            <button type="button" class="btn btn-white h-100 text-primary" style="box-shadow: unset; border-top-left-radius: 0px;">
                              <i class="fas fa-pencil-alt"></i>
                            </button>
                          </a>
                          <a class="bg-white p-0" style="border-radius: 0px 10px 0px 10px!important; opacity: 0.8;"
                          href="preptasks.php?page=<?php echo $page_id; ?>">   
                            <button type="button" class="btn btn-white h-100 text-primary" style="box-shadow: unset; border-top-right-radius: 0px;">
                              <i class="fa-solid fa-file-pen"></i>&nbsp;задания
                            </button>
                          </a>
                        </div>
                      </div>
                      <div class="card_image_content" style="cursor: pointer;" onclick="window.location='preptable.php?page=<?=$page_id?>'">
                        <div class="p-2" style="text-align: left;">
                            <a class="text-white" href="preptable.php?page=<?=$page_id?>" style="font-weight: bold;"><?php echo $page['short_name']; ?></a>
                            <br><a><?php echo $full_name; ?></a>
                        </div>
                      </div>
                  </div>
                  <div class="card-body">
                    <div class="d-flex justify-content-between">
                      <span>Посылки студентов </span>
                      <button class="btn btn-link btn-sm" style="background-color: unset;" href="#" id="navbarDropdownMenuLink1" role="button" data-mdb-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell fa-lg"></i>
                        <span  class="badge rounded-pill badge-notification 
                          <?php if ($notify_count['count'] > 0) echo "bg-danger";
                          else echo "bg-success";?>"><?=$notify_count['count'];?></span>
                      </button>
                      <ul class="dropdown-menu dropdown-menu-end dropup" aria-labelledby="navbarDropdownMenuLink1" style="z-index:99999999; ">
                        <?php if ($notify_count > 0 && $array_notify) { $i=0;
                          foreach ($array_notify as $notify) { $i++; ?>
                            <li class="dropdown-item bg-primary" <?php if($i != $notify_count) echo 'style="border-bottom: 1px solid;"'?>>
                            <a href="taskchat.php?assignment=<?=$notify['assignment_id'];?>"> 
                            <span class="text-white" style="border-bottom: 1px solid;"><?=$notify['middle_name']. " " .$notify['first_name']. " (". $notify['short_name']. ")"?></span>
                            <br><span class="text-white"><?=$notify['title']?></span>
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
						style="cursor: pointer; padding: 0px;" onclick="window.location='pageedit.php?addpage'">
							<a class="btn btn-link" href="pageedit.php?addpage" type="button" 
							style="width: 100%; height: 100%; padding-top: 20%;">
								<div class="row">
									<i class="fas fa-plus-circle mb-2 align-self-center" style="font-size: 30px;"></i><br>
									<span class="align-self-center">Добавить новый раздел</span>
								</div>
							</a>
						</div>
		</main>
	</body>
</html>
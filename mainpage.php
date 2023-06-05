<?php 
require_once("common.php");
require_once("dbqueries.php");
require_once("utilities.php");
require_once("settings.php");

function getTextSemester($year, $sem){
  $semester = $year."/".($year+1). " ";
  if ($sem == 1) 
    $semester .= "Осень";
  else
    $semester .= "Весна"; 
  return $semester;
}

$au = new auth_ssh();
checkAuLoggedIN($au);
checkAuIsNotStudent($au);

$User = new User((int)$au->getUserId());

// $result = pg_query($dbconnect, 'select id, short_name, disc_id, get_semester(year, semester) sem, year y, semester s from ax_page order by y desc, s desc');

if ($User->isTeacher())
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
  
	<?php show_head("Дашборд преподавателя");?>

  <body>

	<?php show_header($dbconnect, 'Дашборд преподавателя', array(), $User); ?>

	<main>

    <?php if($pages) { ?>

      <div class="d-flex flex-column">

      <div class="justify-content-start" style="margin-bottom: 30px;">
			  <?php
				// array_multisort(array_column($pages, 'y'), SORT_DESC, $pages);
				//array_multisort(array_map(function($element){return $element['y'];}, $pages), SORT_DESC, $pages);
          $curr_sem = $pages[0]['sem']; // first sem 
          $year = $pages[0]['y'];
          $sem = $pages[0]['s']; ?>
          <h2 class="row" style="margin-top: 30px; margin-left: 50px;"> <?php echo getTextSemester($year, $sem); ?></h2><br>
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
                $Page = new Page((int)$page_id);

                if ($curr_sem != $page['sem']) { ?>
                  <div class="col-2 align-self-center popover-message-message-stud" 
                  style="cursor: pointer; padding: 0px;" onclick="window.location='pageedit.php?addpage'">
                    <a class="btn btn-link" href="pageedit.php?addpage=1&year=<?=$year?>&sem=<?=$sem?>" type="button" 
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
                $year = $page['y'];
                $sem = $page['s'];
              ?>
              <h2 class="row" style="margin-top: 30px; margin-left: 50px;"> <?php echo getTextSemester($year, $sem); ?></h2><br>
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
                          <div class="card_image_content p-2" style="cursor: pointer;" onclick="window.location='preptable.php?page=<?=$page_id?>'">
                            <div class="" style="text-align: left; overflow:hidden;">
                                <a class="text-white" href="preptable.php?page=<?=$page_id?>" 
                                style="font-weight: bold; white-space: nowrap;"><?php echo $page['short_name']; ?></a>
                                <br><a><?php echo $full_name; ?></a>
                            </div>
                          </div>
                      </div>
                      <div class="card-body">
                        <div class="d-flex justify-content-between">
                          <span>Посылки студентов </span>
                          <button class="btn btn-link btn-sm" style="background-color: unset;" href="#" id="navbarDropdownMenuLink<?=$Page->id?>" role="button" data-mdb-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell fa-lg"></i>
                            <span  class="badge rounded-pill badge-notification 
                              <?php if ($notify_count['count'] > 0) echo "bg-danger";
                              else echo "bg-success";?>"><?=$notify_count['count'];?></span>
                          </button>
                          <ul class="dropdown-menu dropdown-menu-end dropup" aria-labelledby="navbarDropdownMenuLink<?=$Page->id?>" style="z-index:99999999; ">
                            <?php foreach($Page->getActiveTasks() as $Task) {
                              foreach($Task->getAllUncheckedAssignments() as $Assignment) {
                                $studentF = "";
                                foreach($Assignment->getStudents() as $Student) {
                                  $studentF .= $Student->middle_name . " ";
                                }?>
                                <li class="dropdown-item bg-primary">
                                  <a href="taskchat.php?assignment=<?=$Assignment->id;?>">
                                    <div class="d-flex justify-content-between align-items-center"> 
                                      <div class="d-flex flex-column ">
                                        <span class="text-white" style="border-bottom: 1px solid;"><?=$studentF?></span>
                                        <span class="text-white"><?=$Task->title?></span>
                                      </div>
                                      <div class="text-white">
                                        <?php getSVGByAssignmentStatus($Assignment->status)?>
                                      </div>
                                    </div>
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
      </div>
    </div>

    <?php } else {?>
          
          <div class="container">
            <div class="d-flex align-items-center justify-content-center h-75">

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

            </div>
          </div>

        <?php }?>
	</main>
	</body>
</html>
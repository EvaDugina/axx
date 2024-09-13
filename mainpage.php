<?php
require_once("common.php");
require_once("dbqueries.php");
require_once("utilities.php");
require_once("settings.php");

function getTextSemester($year, $sem)
{
  $semester = $year . "/" . ($year + 1) . " ";
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

// $result = pg_query($dbconnect, 'select id, short_name, disc_id, get_semester(year, semester) sem, year y, semester s from ax.ax_page order by y desc, s desc');

if ($User->isTeacher()) {
  $query1 = pg_query($dbconnect, select_pages_for_teacher($User->id));
  $query2 = pg_query($dbconnect, select_inside_semester_pages_for_teacher($User->id));
} else {
  $query1 = pg_query($dbconnect, select_pages_for_admin());
  $query2 = pg_query($dbconnect, select_inside_semester_pages_for_admin());
}

$pages = pg_fetch_all($query1);
$inside_semester_pages = pg_fetch_all($query2);

$result1 = pg_query($dbconnect, 'select count(id) from ax.ax_page');
$disc_count = pg_fetch_all($result1);


// function full_name($discipline_id, $dbconnect)
// {
//   $query = 'select name from discipline where id =' . $discipline_id;
//   return pg_query($dbconnect, $query);
// }
?>

<html>

<link rel="stylesheet" href="css/main.css">

<?php show_head("Дашборд преподавателя"); ?>

<body>

  <?php show_header($dbconnect, 'Дашборд преподавателя', array(), $User); ?>

  <main>

    <div class="d-flex flex-column">

      <div class="justify-content-start" style="margin-bottom: 30px;">

        <h2 class="row" style="margin-top: 30px; margin-left: 50px;"> Внесеместровые Разделы</h2><br>
        <div class="container">
          <div class="row g-5 container-fluid">

            <?php foreach ($inside_semester_pages as $page) {

              $page_id = $page['id'];
              $Page = new Page((int)$page_id);
              // $result = full_name($page['disc_id'], $dbconnect);
              $discipline_name = $Page->getDisciplineName();

              $link_on_image = "preptable.php?page=$page_id";
              if ($Page->isOutsideSemester()) {
                $link_on_image = "preptasks.php?page=$Page->id";
              } ?>

              <div class="col-xs-12 col-sm-12 col-md-6 col-xl-3">
                <div id="card_subject" class="card" style="border-radius: 0px 0px 10px 10px!important;">
                  <div data-mdb-ripple-color="light" style="position: relative;">
                    <button class="w-100 bg-image border p-0" onclick="window.location='<?= $link_on_image ?>'">
                      <img src="<?= $Page->getColorThemeSrcUrl() ?>" alt="ИНФОРМАТИКА" style="transition: all .1s linear; height: 200px;">
                      <div class="mask" style="transition: all .1s linear;"></div>
                    </button>
                    <div class="card_image_content" style="bottom:unset; top:0%; background: unset; z-index: 1;" onclick="window.location='<?= $link_on_image ?>'">
                      <div class="d-flex justify-content-between" style="z-index: 2;">
                        <a class="bg-white p-0" style="border-radius: 10px 0px 10px 0px!important; opacity: 0.8;" href="pageedit.php?page=<?php echo $Page->id; ?>">
                          <button type="button" class="btn btn-white h-100 text-primary" style="box-shadow: unset; border-top-left-radius: 0px;">
                            <i class="fas fa-pencil-alt"></i>
                          </button>
                        </a>

                        <?php if (!$Page->isOutsideSemester()) { ?>
                          <a class="bg-white p-0" style="border-radius: 0px 10px 0px 10px!important; opacity: 0.8;" href="preptasks.php?page=<?php echo $Page->id; ?>">
                            <button type="button" class="btn btn-white h-100 text-primary" style="box-shadow: unset; border-top-right-radius: 0px;">
                              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-list-task" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M2 2.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5V3a.5.5 0 0 0-.5-.5H2zM3 3H2v1h1V3z" />
                                <path d="M5 3.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5zM5.5 7a.5.5 0 0 0 0 1h9a.5.5 0 0 0 0-1h-9zm0 4a.5.5 0 0 0 0 1h9a.5.5 0 0 0 0-1h-9z" />
                                <path fill-rule="evenodd" d="M1.5 7a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5H2a.5.5 0 0 1-.5-.5V7zM2 7h1v1H2V7zm0 3.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5H2zm1 .5H2v1h1v-1z" />
                              </svg>&nbsp;задания
                            </button>
                          </a>
                        <?php } ?>

                      </div>
                    </div>
                    <div class="card_image_content p-2" style="cursor: pointer;" onclick="window.location='<?= $link_on_image ?>'">
                      <div class="" style="text-align: left; overflow:hidden;">
                        <a class="text-white" style="font-weight: bold; white-space: nowrap;"><?php echo $Page->name; ?></a>
                        <br><a><?php echo $discipline_name; ?></a>
                      </div>
                    </div>
                  </div>
                  <div class="card-body">
                    <div class="d-flex justify-content-between">
                    </div>
                  </div>
                </div>
              </div>
            <?php } ?>


            <div class="col-2 align-self-center popover-message-message-stud" style="cursor: pointer; padding: 0px;" onclick="window.location='pageedit.php?addpage=1&year=-1&sem=-1'">
              <a class="btn btn-link" href="pageedit.php?addpage=1&year=-1&sem=-1" type="button" style="width: 100%; height: 100%; padding-top: 20%;">
                <div class="row">
                  <i class="fas fa-plus-circle mb-2 align-self-center" style="font-size: 30px;"></i><br>
                  <span class="align-self-center">Добавить новый раздел</span>
                </div>
              </a>
            </div>
          </div>

        </div>
      </div>
    </div>


    <?php if ($pages) { ?>

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
              <?php foreach ($pages as $page) {
                $query = select_notify_count_by_page_for_mainpage($page['id']);
                $result = pg_query($dbconnect, $query);
                $notify_count = pg_fetch_assoc($result);

                $query = select_notify_by_page_for_mainpage($au->getUserId(), $page['id']);
                $result = pg_query($dbconnect, $query);
                $array_notify = pg_fetch_all($result);

                $page_id = $page['id'];
                $Page = new Page((int)$page_id);


                // $result = full_name($page['disc_id'], $dbconnect);
                $full_name = $Page->getDisciplineName();

                if ($curr_sem != $page['sem']) { ?>
                  <div class="col-2 align-self-center popover-message-message-stud" style="cursor: pointer; padding: 0px;" onclick="window.location='pageedit.php?addpage=1&year=<?= $year ?>&sem=<?= $sem ?>'">
                    <a class="btn btn-link" href="pageedit.php?addpage=1&year=<?= $year ?>&sem=<?= $sem ?>" type="button" style="width: 100%; height: 100%; padding-top: 20%;">
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

            <?php
                $link_on_image = "preptable.php?page=$page_id";
                if ($Page->isOutsideSemester()) {
                  $link_on_image = "preptasks.php?page=$Page->id";
                } ?>

            <div class="col-xs-12 col-sm-12 col-md-6 col-xl-3">
              <div id="card_subject" class="card" style="border-radius: 0px 0px 10px 10px!important;">
                <div data-mdb-ripple-color="light" style="position: relative;">
                  <button class="w-100 bg-image border p-0" onclick="window.location='<?= $link_on_image ?>'">
                    <img src="<?= $Page->getColorThemeSrcUrl() ?>" alt="ИНФОРМАТИКА" style="transition: all .1s linear; height: 200px;">
                    <div class="mask" style="transition: all .1s linear;"></div>
                  </button>
                  <div class="card_image_content" style="bottom:unset; top:0%; background: unset; z-index: 1; cursor: pointer;" onclick="window.location='<?= $link_on_image ?>'">
                    <div class="d-flex justify-content-between" style="z-index: 2;">
                      <a class="bg-white p-0" style="border-radius: 10px 0px 10px 0px!important; opacity: 0.8;" href="pageedit.php?page=<?php echo $page_id; ?>">
                        <button type="button" class="btn btn-white h-100 text-primary" style="box-shadow: unset; border-top-left-radius: 0px;">
                          <i class="fas fa-pencil-alt"></i>
                        </button>
                      </a>
                      <a class="bg-white p-0" style="border-radius: 0px 10px 0px 10px!important; opacity: 0.8;" href="preptasks.php?page=<?php echo $page_id; ?>">
                        <button type="button" class="btn btn-white h-100 text-primary" style="box-shadow: unset; border-top-right-radius: 0px;">
                          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-list-task" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M2 2.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5V3a.5.5 0 0 0-.5-.5H2zM3 3H2v1h1V3z" />
                            <path d="M5 3.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5zM5.5 7a.5.5 0 0 0 0 1h9a.5.5 0 0 0 0-1h-9zm0 4a.5.5 0 0 0 0 1h9a.5.5 0 0 0 0-1h-9z" />
                            <path fill-rule="evenodd" d="M1.5 7a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5H2a.5.5 0 0 1-.5-.5V7zM2 7h1v1H2V7zm0 3.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5H2zm1 .5H2v1h1v-1z" />
                          </svg>&nbsp;задания
                        </button>
                      </a>
                    </div>
                  </div>
                  <div class="card_image_content p-2" style="cursor: pointer;" onclick="window.location='<?= $link_on_image ?>'">
                    <div class="" style="text-align: left; overflow:hidden;">
                      <a class="text-white" href="preptable.php?page=<?= $page_id ?>" style="font-weight: bold; white-space: nowrap;"><?php echo $page['short_name']; ?></a>
                      <br><a><?php echo $full_name; ?></a>
                    </div>
                  </div>
                </div>
                <div class="card-body">
                  <div class="d-flex justify-content-between">
                    <span>Посылки студентов </span>
                    <button class="btn btn-link btn-sm" style="background-color: unset;" href="#" id="navbarDropdownMenuLink<?= $Page->id ?>" role="button" data-mdb-toggle="dropdown" aria-expanded="false">
                      <i class="fas fa-bell fa-lg"></i>
                      <span class="badge rounded-pill badge-notification 
                              <?php if ($notify_count['count'] > 0) echo "bg-danger";
                              else echo "bg-success"; ?>"><?= $notify_count['count']; ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end dropup" aria-labelledby="navbarDropdownMenuLink<?= $Page->id ?>" style="z-index:99999999; ">
                      <?php foreach ($Page->getActiveTasks() as $Task) {
                        foreach ($Task->getAllUncheckedAssignments() as $Assignment) {
                          $studentF = "";
                          foreach ($Assignment->getStudents() as $Student) {
                            $studentF .= $Student->middle_name . " ";
                          } ?>
                          <li class="dropdown-item bg-primary">
                            <a href="taskchat.php?assignment=<?= $Assignment->id; ?>">
                              <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex flex-column ">
                                  <span class="text-white" style="border-bottom: 1px solid;"><?= $studentF ?></span>
                                  <span class="text-white"><?= $Task->title ?></span>
                                </div>
                                <div class="text-white">
                                  <?php getSVGByAssignmentStatus($Assignment->status) ?>
                                </div>
                              </div>
                            </a>
                          </li>
                      <?php }
                      } ?>
                    </ul>
                  </div>
                </div>
              </div>
            </div>

          <?php } ?>

          <div class="col-2 align-self-center popover-message-message-stud" style="cursor: pointer; padding: 0px;" onclick="window.location='pageedit.php?addpage=1&year=<?= $year ?>&sem=<?= $sem ?>'">
            <a class="btn btn-link" href="pageedit.php?addpage=1&year=<?= $year ?>&sem=<?= $sem ?>" type="button" style="width: 100%; height: 100%; padding-top: 20%;">
              <div class="row">
                <i class="fas fa-plus-circle mb-2 align-self-center" style="font-size: 30px;"></i><br>
                <span class="align-self-center">Добавить новый раздел</span>
              </div>
            </a>
          </div>
            </div>
          </div>

        <?php } ?>
  </main>
</body>

</html>
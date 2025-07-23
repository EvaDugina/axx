<?php
require_once("common.php");
require_once("dbqueries.php");
require_once("utilities.php");
require_once("settings.php");

$au = new auth_ssh();
checkAuLoggedIN($au);
checkAuIsNotStudent($au);

$User = new User((int)$au->getUserId());

// $result = pg_query($dbconnect, 'select id, short_name, disc_id, get_semester(year, semester) sem, year y, semester s from ax.ax_page order by y desc, s desc');

if ($User->isTeacher()) {
  $query1 = pg_query($dbconnect, select_pages_for_teacher($User->id));
  $query2 = pg_query($dbconnect, select_outside_semester_pages_for_teacher($User->id));
} else {
  $query1 = pg_query($dbconnect, select_pages_for_admin());
  $query2 = pg_query($dbconnect, select_outside_semester_pages_for_admin());
}

$pages = pg_fetch_all($query1);
$outside_semester_pages = pg_fetch_all($query2);

$result1 = pg_query($dbconnect, 'select count(id) from ax.ax_page');
$disc_count = pg_fetch_all($result1);


$page_title = 'Дашборд Преподавателя';

// function full_name($discipline_id, $dbconnect)
// {
//   $query = 'select name from discipline where id =' . $discipline_id;
//   return pg_query($dbconnect, $query);
// }
?>

<html>

<link rel="stylesheet" href="css/main.css">

<?php show_head($page_title); ?>

<body>

  <?php show_header($dbconnect, $page_title, array($page_title  => $_SERVER['REQUEST_URI']), $User); ?>

  <main>

    <div class="d-flex flex-column">

      <div class="justify-content-start" style="margin-bottom: 30px;">

        <h2 class="row" style="margin-top: 30px; margin-left: 50px;"> Внесеместровые Разделы</h2><br>
        <div class="container">
          <div class="row g-5 container-fluid">

            <?php foreach ($outside_semester_pages as $page) {

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
                      <?php if ($notify_count['count'] > 0) { ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-envelope-paper-fill" viewBox="0 0 16 16">
                          <path fill-rule="evenodd" d="M6.5 9.5 3 7.5v-6A1.5 1.5 0 0 1 4.5 0h7A1.5 1.5 0 0 1 13 1.5v6l-3.5 2L8 8.75zM1.059 3.635 2 3.133v3.753L0 5.713V5.4a2 2 0 0 1 1.059-1.765M16 5.713l-2 1.173V3.133l.941.502A2 2 0 0 1 16 5.4zm0 1.16-5.693 3.337L16 13.372v-6.5Zm-8 3.199 7.941 4.412A2 2 0 0 1 14 16H2a2 2 0 0 1-1.941-1.516zm-8 3.3 5.693-3.162L0 6.873v6.5Z" />
                        </svg>
                      <?php } else { ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-envelope-paper" viewBox="0 0 16 16">
                          <path d="M4 0a2 2 0 0 0-2 2v1.133l-.941.502A2 2 0 0 0 0 5.4V14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V5.4a2 2 0 0 0-1.059-1.765L14 3.133V2a2 2 0 0 0-2-2zm10 4.267.47.25A1 1 0 0 1 15 5.4v.817l-1 .6zm-1 3.15-3.75 2.25L8 8.917l-1.25.75L3 7.417V2a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1zm-11-.6-1-.6V5.4a1 1 0 0 1 .53-.882L2 4.267zm13 .566v5.734l-4.778-2.867zm-.035 6.88A1 1 0 0 1 14 15H2a1 1 0 0 1-.965-.738L8 10.083zM1 13.116V7.383l4.778 2.867L1 13.117Z" />
                        </svg>
                      <?php } ?>
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
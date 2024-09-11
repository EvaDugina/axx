<?php

require_once("common.php");
require_once("dbqueries.php");
require_once("settings.php");
require_once("utilities.php");
require_once("POClasses/Page.class.php");


$au = new auth_ssh();
if ($au->isAdminOrPrep()) {
  header('Location:mainpage.php');
  exit;
} else if ($au->loggedIn()) {
  $student_id = $au->getUserId();
  $result = pg_query($dbconnect, select_group_id_by_student_id($student_id));
  $group_id = pg_fetch_assoc($result)['group_id'];
} else {
  $au->logout();
  header('Location:login.php');
  exit;
}

$result = pg_query($dbconnect, select_pages_for_student($group_id));
$disciplines = pg_fetch_all($result);
$result1 = pg_query($dbconnect, 'select count(id) from ax.ax_page');
$disc_count = pg_fetch_all($result1);

$query = pg_query($dbconnect, queryGetAllPagesByGroup($group_id));
$pages = pg_fetch_all($query);

$User = new User((int)$au->getUserId());
$Group = new Group((int)$group_id);
?>

<!DOCTYPE html>
<html lang="en">
<html>

<?php show_head($pge_title = 'Дашборд студента'); ?>

<body>
  <?php show_header($dbconnect, 'Дашборд студента', array(), $User); ?>
  <main class="justify-content-start overflow-hidden mb-5 pt-3">
    <?php

    if (count($pages) < 1) { ?>
      <div class="d-flex justify-content-center align-items-center pt-5 mt-5">
        <h3 class="mt-5 pt-5">Пока здесь пусто, но скоро здесь появятся доступные дисциплины!</h3>
      </div>
      <?php }

    $array_year = 0;
    $array_semester = 0;
    foreach ($pages as $page_id) {
      $Page = new Page((int)$page_id['id']);
      $semester_number = getSemesterNumberByGroup($Page->year, $Page->semester, $Group->year);
      if ($semester_number > 0) {
        if ($Page->year != $array_year || $Page->semester != $array_semester) {

          if ($array_year != 0) { ?>
            </div>
            </div>
          <?php } ?>

          <h2 class="row" style="margin-top: 30px; margin-left: 50px;">
            <?= $semester_number ?> семестр
          </h2>
          <br>
          <div class="container">
            <div class="row g-5 container-fluid">
            <?php
            $array_year = $Page->year;
            $array_semester = $Page->semester;
          } ?>

            <div class="col-xs-12 col-sm-12 col-md-6 col-xl-3">
              <div id="card_subject" class="card" style="border-radius: 0px 0px 10px 10px;">
                <?php

                $count_succes_tasks = $Page->getCountCompletedAssignments($User->id);
                $count_tasks = $Page->getCountActiveAssignments($User->id);
                $full_name = $Page->disc_name;
                ?>
                <div data-mdb-ripple-color="light" style="position: relative;">
                  <div class="bg-image hover-zoom" style="cursor: pointer;" onclick="window.location='studtasks.php?page=<?= $Page->id ?>'">
                    <img src="<?= $Page->getColorThemeSrcUrl() ?>" alt="ИНФОРМАТИКА" style="transition: all .1s linear; height: 200px;">
                    <div class="mask" style="transition: all .1s linear;"></div>
                  </div>
                  <div class="card_image_content" style="cursor: pointer;" onclick="window.location='studtasks.php?page=<?= $Page->id ?>'">
                    <div class="p-2" style="text-align: left;">
                      <a style="color: white; font-weight: bold;"><?= $Page->name ?></a>
                      <br><a><?php echo $full_name/*[0]['name']*/; ?></a>
                    </div>
                  </div>
                </div>
                <div class="card-body">
                  <?php if ($count_tasks == 0) { ?>
                    <div class="popover-footer text-muted">
                      <span>Задания временно отсутствуют</span>
                    </div>
                  <?php } else {
                    $page_progress = $count_succes_tasks / $count_tasks * 100;
                    if ($page_progress < 30)
                      $bg_progress_color = "bg-danger";
                    else if ($page_progress < 100)
                      $bg_progress_color = "bg-warning";
                    else
                      $bg_progress_color = "bg-success"; ?>

                    <div class="d-flex justify-content-between text-muted" style="width: 100%">
                      <span>Выполнено</span>
                      <span><?php echo $count_succes_tasks; ?>/<?php echo $count_tasks; ?></span>
                    </div>
                    <div class="progress" style="width: 100%; height: 1px; border-radius: 5px; margin-bottom: 5px;">
                      <div class="progress-bar <?= $bg_progress_color ?>" role="progressbar" style="width: <?= $page_progress ?>%" aria-valuenow="<?= $count_succes_tasks ?>" aria-valuemin="0" aria-valuemax="<?= $count_tasks ?>">
                      </div>
                    </div>
                    <div class="progress" style="width: 100%; height: 20px; border-radius: 5px;">
                      <div class="progress-bar <?= $bg_progress_color ?>" role="progressbar" style="width: <?= $page_progress ?>%" aria-valuenow="<?= $count_succes_tasks ?>" aria-valuemin="0" aria-valuemax="<?= $count_tasks ?>">
                        <?= round($count_succes_tasks / $count_tasks * 100, 0) ?>%
                      </div>
                    </div>
                  <?php } ?>

                </div>

              </div>
            </div>


        <?php }
    } ?>

            </div>
          </div>
  </main>

</body>



</html>
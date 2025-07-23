<!DOCTYPE html>

<?php
require_once("common.php");
require_once("dbqueries.php");
require_once("utilities.php");

$au = new auth_ssh();
checkAuLoggedIN($au);
$User = new User((int)$au->getUserId());
$student_id = $au->getUserId();

// Обработка некорректного перехода между страницами
if (isset($_GET['page']) && is_numeric($_GET['page'])) {
  $page_id = $_GET['page'];
  $Page = new Page((int)$page_id);
  $page_title = getStudtasksPageTitle($Page);
} else {
  header('Location:mainpage_student.php');
  exit;
}

$count_succes_tasks = $Page->getCountCompletedAssignments($student_id);
$count_tasks = $Page->getCountActiveAssignments($student_id);


?>

<html lang="en">

<?php
show_head($page_title, array('./src/npm/marked/marked.min.js'));
?>

<body style="overflow-x: hidden;">

  <?php
  show_header($dbconnect, $page_title, array($page_title => 'studtasks.php?page=' . $Page->id), $User);
  ?>

  <main class="container-fluid overflow-hidden">
    <div class="pt-5 px-4">
      <div class="row">
        <div class="col-md-12 d-flex">
          <div class="d-flex justify-content-between w-100 h-auto">
            <div class="d-flex flex-column w-100">
              <p class="h3 me-2"><strong><?= $Page->name; ?></strong> (<?= $Page->getDisciplineName() ?>)</p>
              <p>
                Выполнено заданий: <strong><?= $count_succes_tasks ?>&nbsp;/&nbsp;<?= $count_tasks; ?></strong>
              </p>
            </div>
            <div class="h-auto d-flex flex-wrap align-items-start justify-content-end w-75">
              <?php foreach ($Page->getTeachers() as $i => $Teacher) { ?>
                <a href="profile.php?user_id=<?= $Teacher->id ?>" class="h-auto badge badge-primary border-0 rounded-3 p-1 hover-shadow d-flex align-items-center px-2 me-3">
                  <div class="shadow-none p-1 m-0 bg-image me-2"
                    style="/*left: -<?= $i * 5 ?>%*/">
                    <?php if ($Teacher->getImageFile() != null) { ?>
                      <div class="embed-responsive embed-responsive-1by1" style="display: block;">
                        <div class="embed-responsive-item">
                          <img class="h-100 w-100 p-0 m-0 rounded-circle user-icon" style="vertical-align: unset; /*transform: translateX(-30%);*/" src="<?= $Teacher->getImageFile()->download_url ?>" />
                        </div>
                      </div>
                    <?php } else { ?>
                      <div>
                        <svg class="h-100 w-100" xmlns="http://www.w3.org/2000/svg" width="20" fill="white" class="bi bi-person-circle" viewBox="0 0 16 16">
                          <path fill-rule="nonzero" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z" />
                          <path fill-rule="nonzero" d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" />
                        </svg>
                      </div>
                    <?php } ?>
                  </div>
                  <span><?= $Teacher->getFIO() ?></span>
                </a>
              <?php } ?>
            </div>

          </div>
        </div>
      </div>
    </div>
    <div class="pt-4 px-5">
      <div class="row">

        <?php if ($Page->description != "") { ?>
          <h5 class="px-0">Описание раздела</h5>
          <div class="rounded border p-3 mt-3 mb-5">
            <p id="p-pageDescription" class="m-0 p-0" style="overflow: auto;"><?= $Page->description ?></p>
          </div>

          <script>
            document.getElementById('p-pageDescription').innerHTML =
              marked.parse(document.getElementById('p-pageDescription').innerHTML);
            $('#p-pageDescription').children().addClass("m-0");
          </script>
        <?php } ?>



        <div class="col-md-offset-2 col-md-5 mt-3 px-0">
          <?php if ($count_tasks == 0)
            echo '<h5>Задания по этой дисциплине отсутствуют</h5>';
          else  echo '<h5>Название задания</h5>'; ?>
        </div>

      </div>

      <div class="row pt-3 px-0">
        <div class="col-md-11 col-md-push-1 w-100 px-0">
          <div class="list-group list-group-flush" id="list-tab" role="tablist">
            <?php
            $conversationTask = null;
            foreach ($Page->getActiveTasksWithConversation() as $Task) {
              if ($Task->isConversation()) {
                $conversationTask = $Task;
                continue;
              }
              generateTaskLine($Task, $student_id);
            }
            if ($conversationTask != null)
              generateTaskLine($conversationTask, $student_id); ?>
          </div>
        </div>
      </div>
    </div>
    </div>
  </main>

  <?php function generateTaskLine($Task, $student_id)
  {

    foreach ($Task->getVisibleAssignmemntsByStudent((int)$student_id) as $Assignment) {
      $unreadedMessages = $Assignment->getUnreadedMessage($student_id);
      if (checkIfDefaultDate(convert_timestamp_to_date($Assignment->finish_limit, "Y-m-d")) != "")
        $date_finish = "до $Assignment->finish_limit";
      else
        $date_finish = "";

      $text_status = status_to_text($Assignment->status);
      if ($Assignment->isMarked()) {
        if ($Assignment->isWaitingCheck())
          $text_status .= ' </br>(текущая оценка: <strong>' . $Assignment->mark . '</strong>)';
        else if ($Assignment->mark != "зачтено")
          $text_status .= ' (оценка: <strong>' . $Assignment->mark . '</strong>)';
        else
          $text_status .= ' (<strong>' . $Assignment->mark . '</strong>)';
      }
  ?>
      <button class="list-group-item list-group-item-action d-flex justify-content-between mb-3 align-items-center <?= ($Task->isConversation()) ? "bg-primary text-white" : "" ?>" onclick="window.location='taskchat.php?assignment=<?= $Assignment->id ?>'" style="cursor: pointer; border-width: 1px; padding: 0px; border-radius: 5px;">
        <p class="col-md-5" style="margin: 10px; margin-left: 15px;"> <?= $Task->title; ?></p>
        <p class="col-md-2" style="margin: 10px; text-align: center;"><?= $date_finish; ?></p>
        <?php if (!$Task->isConversation()) { ?>
          <p class="col-md-2" style="margin: 10px; text-align: center;"><?= $text_status; ?></p>
        <?php } ?>
        <div class="d-flex align-items-center ps-0">
          <div class="text-primary me-2">
            <?php if (!$Task->isConversation()) {
              getSVGByAssignmentStatus($Assignment->status);
            } ?>
          </div>
        </div>
        <!-- ВОЗМОЖНЫЕ ДОРАБОТКИ ПО МАКЕТУ
											<button type="button" style="color:crimson; border-width: 0px; background: none;"> <i class="fas fa-file-download fa-lg"></i></button>
											<button type="button" class="btn btn-outline-primary" style="color: darkcyan; background: white; border-color: darkcyan; margin-top: 0px; margin-bottom: 0px;"> Загрузить </button>
											-->
        <span class="badge badge-pill me-2 <?= (count($unreadedMessages) > 0) ? "badge-info" : "badge-light" ?>">
          <?= count($unreadedMessages) ?>
        </span>
      </button>
  <?php
    }
  } ?>


</body>

</html>
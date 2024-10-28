<!DOCTYPE html>
<html lang="en">


<?php
require_once("common.php");
require_once("dbqueries.php");
require_once("utilities.php");

$au = new auth_ssh();
checkAuLoggedIN($au);
checkAuIsNotStudent($au);

$User = new User((int)$au->getUserId());

// Обработка некорректного перехода между страницами
if ((!isset($_GET['assignment_id']) || !is_numeric($_GET['assignment_id']))
  && (!isset($_GET['task_id']) || !is_numeric($_GET['task_id']))
) {
  header('Location:mainpage.php');
  exit;
}


$isNewAssignment = false;
if (isset($_GET['assignment_id'])) {
  $isNewAssignment = false;
  $Assignment = new Assignment((int)$_GET['assignment_id']);
  $Task = new Task((int)getTaskByAssignment($Assignment->id));
} else {
  $isNewAssignment = true;
  $Task = new Task((int)$_GET['task_id']);
  $Assignment = new Assignment();
}




$Page = new Page((int)getPageByTask($Task->id));


show_head("Назначение задания", array('https://cdn.jsdelivr.net/npm/marked/marked.min.js'));

?>

<body>

  <?php
  show_header(
    $dbconnect,
    'Редактор заданий',
    array(
      "Задания по дисциплине: " . $Page->disc_name  => 'preptasks.php?page=' . $Page->id,
      "Редактор заданий" => $_SERVER['REQUEST_URI']
    ),
    $User
  );
  ?>

  <main class="pt-2 mb-5">
    <div class="container-fluid overflow-hidden">
      <div class="row gy-5">
        <div class="col-8">

          <div class="row ms-5 mt-5 mb-3">
            <h2 class="col-9 text-nowrap">
              <?php if ($Task->type == 1) { ?>
                <i class="fas fa-code fa-lg"></i>
              <?php } else { ?>
                <i class="fas fa-file fa-lg" style="padding: 0px 5px 0px 5px;"></i>
              <?php } ?>
              <?= $Task->title ?>
            </h2>
          </div>


          <div class="pt-3 offset-1">


            <div class="d-flex justify-content-between">
              <?php if ($isNewAssignment) { ?>
                <h4> Новое назначение </h4>
              <?php } else { ?>
                <h4> Текущее назначение: </h4>
              <?php } ?>


              <!-- <div class="d-flex">
                <div class="me-2 pe-2" style="border-right: 1px solid; border-color:var(--mdb-gray-400); cursor: help;">
                  <button class="btn btn-outline-<?= !$Assignment->isCompleteable() ? 'primary' : 'light' ?> px-3 me-1 btn-assignment-status" id="btn-assignment-status--1" onclick="ajaxChangeStatus(-1)" <?= $Assignment->isCompleteable() ? 'style="color: var(--mdb-gray-400);"' : '' ?> data-title="<?= !$Assignment->isCompleteable() ? '' : 'Изменить статус назначения на:' ?> <?= status_to_text(-1) ?>">
                    <?php getSVGByAssignmentStatus(-1); ?>
                  </button>
                  <?php if ($Assignment->isCompleteable()) { ?>
                    <button class="btn btn-outline-primary px-3 me-1 btn-assignment-status" id="btn-assignment-status-<?= $Assignment->status ?>" data-toggle="tooltip" data-placement="down" data-title="<?= status_to_text($Assignment->status) ?>">
                      <?php getSVGByAssignmentStatus($Assignment->status); ?>
                    </button>
                  <?php } else { ?>
                    <button class="btn btn-outline-<?= $Assignment->isCompleteable() ? 'primary' : 'light' ?> px-3 me-1 btn-assignment-status" id="btn-assignment-status-0" onclick="ajaxChangeStatus(0)" style="color: var(--mdb-gray-400);" data-title="<?= $Assignment->isCompleteable() ? '' : 'Изменить статус назначения на:' ?> <?= status_to_text(0) ?>">
                      <?php getSVGByAssignmentStatus(0); ?>
                    </button>
                  <?php } ?>
                </div>
                <div>
                  <button id="btn-assignment-visibility-0" class="btn btn-outline-<?= $Assignment->visibility == 0 ? 'primary' : 'light' ?> px-3 me-1 btn-assignment-visibility" onclick="ajaxChangeVisibility(0)" <?= $Assignment->visibility == 0 ?  '' : 'style="color: var(--mdb-gray-400);"' ?> data-toggle="tooltip" data-placement="down" data-title="<?= $Assignment->visibility == 0 ? '' : 'Изменить видимость назначения на:' ?> '<?= strtoupper(visibility_to_text(0)) ?>'">
                    <?php getSVGByAssignmentVisibility(0); ?>
                  </button>
                  <button id="btn-assignment-visibility-2" class="btn btn-outline-<?= $Assignment->visibility == 2 ? 'primary' : 'light' ?> px-3 me-1 btn-assignment-visibility" onclick="ajaxChangeVisibility(2)" <?= $Assignment->visibility == 2 ?  '' : 'style="color: var(--mdb-gray-400);"' ?> data-toggle="tooltip" data-placement="down" data-title="<?= $Assignment->visibility == 2 ? '' : 'Изменить видимость назначения на:' ?> '<?= strtoupper(visibility_to_text(2)) ?>'">
                    <?php getSVGByAssignmentVisibility(2); ?>
                  </button>
                  <button id="btn-assignment-visibility-4" class="btn btn-outline-<?= $Assignment->visibility == 4 ? 'primary' : 'light' ?> px-3 me-1 btn-assignment-visibility" onclick="ajaxChangeVisibility(4)" <?= $Assignment->visibility == 4 ?  '' : 'style="color: var(--mdb-gray-400);"' ?> data-toggle="tooltip" data-placement="down" data-title="<?= $Assignment->visibility == 4 ? '' : 'Изменить видимость назначения на:' ?> '<?= strtoupper(visibility_to_text(4)) ?>'">
                    <?php getSVGByAssignmentVisibility(4); ?>
                  </button>
                </div>
              </div> -->

            </div>

            <?php foreach ($Assignment->getStudents() as $Student) { ?>
              <div class="d-flex align-items-center">
                <span><?= $Student->getFI() ?>
                  <?php if ($Assignment->finish_limit != null && checkIfDefaultDate(convert_timestamp_to_date($Assignment->finish_limit, "Y-m-d")) != "")
                    echo " (до $Assignment->finish_limit)";
                  else
                    echo " (бессрочно)"; ?>
                </span>
              </div>
            <?php }
            ?>
            </br>


            <form id="checkparam" name="checkparam" class="" action="taskassign_action.php" method="POST" enctype="multipart/form-data">
              <?php if ($Assignment->id != null) { ?>
                <input type="hidden" name="assignment_id" value="<?= $Assignment->id ?>">
              <?php } else { ?>
                <input type="hidden" name="flag-createAssignment" value="true">
              <?php } ?>

              <input type="hidden" name="task_id" value="<?= $Task->id ?>">
              <input type="hidden" name="page_id" value="<?= $Page->id ?>">

              <input type="hidden" name="action" value="save">
              <!-- <input type="hidden" name="from" value="<?= $_SERVER['HTTP_REFERER'] ?>"> -->

              <h5><i class="fas fa-users fa-lg" aria-hidden="true"></i> Исполнители</h5>

              <section class="w-100 d-flex mb-4" style="height: 50%;">
                <div id="main-accordion-students" class="accordion w-100" style="overflow-y: auto; height: 100%; width: 100%;">

                  <?php
                  /*if ($isNewAssignment) {
                    foreach($Page->getGroups() as $Group) { ?>
                      <div>
                      <h6><?=$Group->name?></h6>
                      <?php foreach($Group->getStudents() as $Student) {?>
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" name="students[]" 
                          value="<?=$Student->id?>" id="flexCheck<?=$Student->id?>" <?=in_array($Student->id, $studids) ? "checked" : "" ?>>
                          <label class="form-check-label" for="flexCheck<?=$Student->id?>"><?=$Student->getFI()?></label>
                        </div>
                      <?php } ?>
                      </div>
                    <?php } 
                  } else {*/
                  $key = 0;
                  foreach ($Page->getGroups() as $key => $Group) { ?>
                    <div class="accordion-item">
                      <div id="accordion-gheader-<?= $Group->id ?>" class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-mdb-toggle="collapse" data-mdb-target="#accordion-collapse-<?= $key ?>" aria-expanded="false" aria-controls="accordion-collapse-<?= $key ?>">
                          <h6>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-people-fill p-0 h-100" viewBox="0 0 16 16" style="vertical-align: top;">
                              <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7Zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm-5.784 6A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216ZM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" />
                            </svg>
                            <?= $Group->name ?>
                          </h6>
                        </button>
                      </div>
                      <div id="accordion-collapse-<?= $key ?>" class="accordion-collapse collapse" aria-labelledby="accordion-gheader-<?= $key ?>" data-mdb-parent="#main-accordion-students">
                        <div class="accordion-body py-1">
                          <div id="group-accordion-students" class="accordion accordion-flush">
                            <?php
                            if (count($Group->getStudents()) > 0) {
                              foreach ($Group->getStudents() as $Student) { ?>
                                <div id="item-from-group-<?= $Group->id ?>" class="accordion-item p-2" onclick="$('#student-<?= $Student->id ?>').prop('checked', !$('#student-<?= $Student->id ?>').prop('checked'));">
                                  <div id="accordion-sheader-<?= $Student->id ?>" class="accordion-header">
                                    <div class="d-flex justify-content-between" type="button">
                                      <div class="form-check ms-3">
                                        <input id="student-<?= $Student->id ?>" class="accordion-input-item form-check-input input-student" type="checkbox" value="<?= $Student->id ?>" name="students[]" <?php if ($Assignment->getStudentById($Student->id) != null) echo 'checked'; ?> onclick="this.checked = !this.checked;">
                                        <label class="form-check-label" for="flexCheck1"><?= $Student->getFI() ?></label>
                                      </div>
                                      <div class="">
                                        &nbsp;&nbsp;<?= $Student->subgroup ?> подгруппа
                                      </div>
                                    </div>
                                  </div>
                                </div>
                              <?php
                              }
                            } else { ?>
                              (студенты отсутствуют)
                            <?php } ?>
                          </div>
                        </div>
                      </div>
                    </div>
                  <?php }
                  //}
                  ?>


                </div>
              </section>



              <!-- <div class="ps-5 pb-3">
				<section class="w-100 d-flex border">
                  <div class="w-100 h-100 d-flex" style="margin:10px; height:250px; text-align: left;">
                    <div id="demo-example-1" style="overflow-y: auto; height:250px; width: 100%;"> -->
              <?php
              /*foreach($Page->getGroups() as $Group) { ?>
                        <div>
                        <h6><?=$Group->name?></h6>
                        <?php foreach($Group->getStudents() as $Student) {?>
                          <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="students[]" 
                            value="<?=$Student->id?>" id="flexCheck<?=$Student->id?>" <?=in_array($Student->id, $studids) ? "checked" : "" ?>>
                            <label class="form-check-label" for="flexCheck<?=$Student->id?>"><?=$Student->getFI()?></label>
                          </div>
                        <?php } ?>
                        </div>
                      <?php } */
              ?>

              <!-- $query = select_page_students($page_id);
                      $resultP = pg_query($dbconnect, $query);


                      while($rowP = pg_fetch_assoc($resultP)) {
                        echo '<div class="form-check">';
                        echo '  <input class="form-check-input" type="checkbox" name="students[]" value="'.$rowP['id'].'" id="flexCheck'.$rowP['id'].'" '.(in_array($rowP['id'], $studids) ?"checked" :"").'>';
                        echo '  <label class="form-check-label" for="flexCheck'.$rowP['id'].'">'.$rowP['fio'].'</label>';
                        echo '</div>';
                      }
            ?> -->
              <!-- </div>
                  </div>
				</section>
			  </div> -->

              <h5><i class="fas fa-calendar fa-lg" aria-hidden="true"></i> Сроки выполения</h5>
              <div class="ps-5 mb-4">
                <section class="w-100 py-2 d-flex justify-content-center">
                  <div class="w-100">

                    <div class="form-outline datetimepicker me-3">
                      <input id="input-startDate" type="date" class="form-control active" name="fromtime" style="margin-bottom: 0px;" value="<?= ($Assignment->start_limit != null) ? checkIfDefaultDate(convert_timestamp_to_date($Assignment->start_limit, "Y-m-d")) : "" ?>" onblur="$(this).addClass('active');$(this).css('opacity', '1');">
                      <label for="fromtime" class="form-label" style="margin-left: 0px;">Дата начала</label>
                      <div class="form-notch">
                        <div class="form-notch-leading" style="width: 9px;"></div>
                        <div class="form-notch-middle" style="width: 80px;"></div>
                        <div class="form-notch-trailing"></div>
                      </div>
                    </div>

                    <div class="form-outline datetimepicker mt-3 me-3">
                      <input id="input-startTime" type="time" name="start_time" class="form-control active" style="margin-bottom: 0px;" value="<?= ($Assignment->start_limit == null || (checkIfDefaultDate(convert_timestamp_to_date($Assignment->start_limit, "Y-m-d")) == "")) ? "00:00" : convert_timestamp_to_date($Assignment->getStartTime(), "H:i") ?>">
                      <label for="input-startTime" class="form-label" style="margin-left: 0px;">Время начала</label>
                      <div class="form-notch">
                        <div class="form-notch-leading" style="width: 9px;"></div>
                        <div class="form-notch-middle" style="width: 92px;"></div>
                        <div class="form-notch-trailing"></div>
                      </div>
                    </div>

                  </div>

                  <div class="w-100">
                    <div class="form-outline datetimepicker">
                      <input id="input-endDate" type="date" class="form-control active" name="tilltime" style="margin-bottom: 0px;" value="<?= ($Assignment->finish_limit != null) ? checkIfDefaultDate(convert_timestamp_to_date($Assignment->finish_limit, "Y-m-d")) : "" ?>" onblur="$(this).addClass('active');$(this).css('opacity', '1');">
                      <label for="tilltime" class="form-label" style="margin-left: 0px;">Дата окончания</label>
                      <div class="form-notch">
                        <div class="form-notch-leading" style="width: 9px;"></div>
                        <div class="form-notch-middle" style="width: 99px;"></div>
                        <div class="form-notch-trailing"></div>
                      </div>
                    </div>

                    <div class="form-outline datetimepicker mt-3">
                      <input id="input-endTime" type="time" name="end_time" class="form-control active" style="margin-bottom: 0px;" value="<?= ($Assignment->finish_limit == null || (checkIfDefaultDate(convert_timestamp_to_date($Assignment->finish_limit, "Y-m-d")) == "")) ? "23:59" : convert_timestamp_to_date($Assignment->getEndTime(), "H:i") ?>">
                      <label for="input-endTime" class="form-label" style="margin-left: 0px;">Время окончания</label>
                      <div class="form-notch">
                        <div class="form-notch-leading" style="width: 9px;"></div>
                        <div class="form-notch-middle" style="width: 108px;"></div>
                        <div class="form-notch-trailing"></div>
                      </div>
                    </div>

                  </div>

                </section>
              </div>

              <h5><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-list-ol" viewBox="0 0 16 16">
                  <path fill-rule="evenodd" d="M5 11.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5z" />
                  <path d="M1.713 11.865v-.474H2c.217 0 .363-.137.363-.317 0-.185-.158-.31-.361-.31-.223 0-.367.152-.373.31h-.59c.016-.467.373-.787.986-.787.588-.002.954.291.957.703a.595.595 0 0 1-.492.594v.033a.615.615 0 0 1 .569.631c.003.533-.502.8-1.051.8-.656 0-1-.37-1.008-.794h.582c.008.178.186.306.422.309.254 0 .424-.145.422-.35-.002-.195-.155-.348-.414-.348h-.3zm-.004-4.699h-.604v-.035c0-.408.295-.844.958-.844.583 0 .96.326.96.756 0 .389-.257.617-.476.848l-.537.572v.03h1.054V9H1.143v-.395l.957-.99c.138-.142.293-.304.293-.508 0-.18-.147-.32-.342-.32a.33.33 0 0 0-.342.338v.041zM2.564 5h-.635V2.924h-.031l-.598.42v-.567l.629-.443h.635V5z" />
                </svg> Вариант</h5>
              <div class="ps-5 mb-4">
                <input id="variant" name="variant" class="w-100" value="<?= ($Assignment->variant_number != null) ? $Assignment->variant_number : "" ?>" wrap="off" rows="1">
              </div>

              <div class="d-flex justify-content-between align-items-center mb-2">

                <div class="w-50">
                  <h5>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-sliders2" viewBox="0 0 16 16">
                      <path fill-rule="evenodd" d="M10.5 1a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-1 0V4H1.5a.5.5 0 0 1 0-1H10V1.5a.5.5 0 0 1 .5-.5ZM12 3.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5Zm-6.5 2A.5.5 0 0 1 6 6v1.5h8.5a.5.5 0 0 1 0 1H6V10a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5ZM1 8a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2A.5.5 0 0 1 1 8Zm9.5 2a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-1 0V13H1.5a.5.5 0 0 1 0-1H10v-1.5a.5.5 0 0 1 .5-.5Zm1.5 2.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5Z" />
                    </svg>
                    Параметры проверки
                  </h5>
                </div>

                <?php
                $current_checks = $Assignment->checks;
                if ($current_checks == null)
                  $current_checks = $Task->checks;
                if ($current_checks == null)
                  $current_checks = getDefaultChecksPreset();
                ?>

                <div class="w-25">
                  <select class="form-select" aria-label="Default select example" onchange="loadLanguageConfigPreset($(this).val(), <?= htmlspecialchars($current_checks) ?>)">
                    <option value="current" selected>Выберите язык проекта</option>
                    <?php foreach (getEditorLanguages() as $key => $language) { ?>
                      <option value="<?= $key ?>"><?= $language["name"] ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>

              <div id="div-accordion-checks">
                <?php
                $current_checks = json_decode($current_checks, true);
                $accord = getChecksAccordion($current_checks);
                show_accordion('checks', $accord, "310px");
                ?>
              </div>

              <button id="checks-save" type="button" class="btn btn-outline-primary mt-5" name="action" value="save" style="" onclick="checkFields()">Сохранить</button>

            </form>
          </div>
        </div>

        <div class="col-4">
          <div class="p-3 border ">

            <h6>Задание</h6>
            <div id="Task" class="tabcontent border bg-light p-2 small" style="overflow-y: auto; width: 100%; height: 100%;">
              <p id="TaskDescr"><?= $Task->description ?></p>
              <script>
                document.getElementById('TaskDescr').innerHTML = marked.parse(document.getElementById('TaskDescr').innerHTML);
              </script>
              <div>
                <p class="mb-1"><strong>Файлы, приложенные к заданию:</strong></p>
                <?php
                // $task_files = getTaskFiles($dbconnect, $Task->id);
                showFiles($Task->getFiles());
                ?>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  </main>


  <div class="modal" id="dialogMark" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">ВНИМАНИЕ!</h5>
          <button type="button" class="close" data-mdb-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <p>Внимание! Если отменить назначение, соответсвующие посылки от студента будут утеряны!</p>
        </div>
        <div class="modal-footer">
          <button id="modal-btn-continue" type="button" class="btn btn-danger" data-mdb-dismiss="modal">Продолжить</button>
          <button id="modal-btn-escape" type="button" class="btn btn-primary">Отмена</button>
        </div>
      </div>
    </div>
  </div>

  <script type="text/javascript">
    $('#input-endTime').on("blur", function() {
      $(this).addClass("active");
    });
    $('#input-startTime').on("blur", function() {
      $(this).addClass("active");
    });

    $('#input-startDate').on("change", function() {});
    $('#input-endDate').on("change", function() {});

    function loadLanguageConfigPreset(language_key, current_checks) {
      var formData = new FormData();
      formData.append("flag-loadChecksPreset", true);
      formData.append('language_key', language_key);
      formData.append('current_checks', JSON.stringify(current_checks));

      $.ajax({
        type: "POST",
        url: 'taskassign_action.php#content',
        cache: false,
        contentType: false,
        processData: false,
        data: formData,
        dataType: 'html',
        success: function(response) {
          response = response.trim();
          if (response == "")
            return;
          $('#div-accordion-checks').html(response);
        },
        complete: function() {}
      });
    }

    function onInputShowClick(event) {
      if (!event.target.checked)
        return;

      var parentNode = event.target.parentNode;

      previousInputEnabled = parentNode.querySelector("#build_enabled");
      if (previousInputEnabled == null)
        previousInputEnabled = parentNode.querySelector("#valgrind_enabled");
      if (previousInputEnabled == null)
        previousInputEnabled = parentNode.querySelector("#cppcheck_enabled");
      if (previousInputEnabled == null)
        previousInputEnabled = parentNode.querySelector("#clang_enabled");
      if (previousInputEnabled == null)
        previousInputEnabled = parentNode.querySelector("#test_enabled");
      if (previousInputEnabled == null)
        previousInputEnabled = parentNode.querySelector("#plug_enabled");
      if (previousInputEnabled == null)
        return;

      if (previousInputEnabled && !previousInputEnabled.checked) {
        previousInputEnabled.checked = true;
      }
    }

    function checkFields() {
      let start_date = new Date($('#input-startDate').val());
      let start_time = $('#input-startTime').val();
      let end_date = new Date($('#input-endDate').val());
      let end_time = $('#input-endTime').val();

      if (start_date > end_date) {
        alert("Неверно указаны даты!");
        return;
      } else if (start_date == end_date) {
        let split_start_time = start_time.split(":");
        let split_end_time = end_time.split(":");
        if (parseInt(split_start_time[0]) > parseInt(split_end_time[0])) {
          alert("Неверно указано время!");
          return;
        } else if (parseInt(split_start_time[0]) == parseInt(split_end_time[0]) && parseInt(split_start_time[1]) >= parseInt(split_end_time[1])) {
          alert("Неверно указано время!");
          return;
        }
      }

      $('#checkparam').submit();
    }


    function confirmRejectAssignment(form_id) {
      $('#dialogMark').modal('show');

      $('#modal-btn-continue').click(function() {
        let form_reject = document.getElementById(form_id);
        form_reject.submit();
      });

      $('#modal-btn-escape').click(function() {
        $('#dialogMark').modal('hide');
      });
    }

    function ajaxChangeVisibility(new_status) {

      var formData = new FormData();

      formData.append('assignment_id', <?= $Assignment->id ?>);
      formData.append('changeVisibility', new_status);

      $.ajax({
        type: "POST",
        url: 'taskassign_action.php#content',
        cache: false,
        contentType: false,
        processData: false,
        data: formData,
        dataType: 'html',
        success: function(response) {},
        complete: function() {
          if (new_status != "delete") {
            $('.btn-assignment-visibility').removeClass('btn-outline-primary');
            $('.btn-assignment-visibility').addClass('btn-outline-light');
            $('.btn-assignment-visibility').css('color', 'var(--mdb-gray-400)');
            $('#btn-assignment-visibility-' + new_status).css('color', 'var(--mdb-primary)');
            $('#btn-assignment-visibility-' + new_status).removeClass('btn-outline-light');
            $('#btn-assignment-visibility-' + new_status).addClass('btn-outline-primary');
          }
        }
      });
    }

    function ajaxChangeStatus(new_status) {

      var formData = new FormData();

      formData.append('assignment_id', <?= $Assignment->id ?>);
      formData.append('changeStatus', new_status);

      $.ajax({
        type: "POST",
        url: 'taskassign_action.php#content',
        cache: false,
        contentType: false,
        processData: false,
        data: formData,
        dataType: 'html',
        success: function(response) {},
        complete: function() {
          if (new_status != "delete") {
            $('.btn-assignment-status').removeClass('btn-outline-primary');
            $('.btn-assignment-status').addClass('btn-outline-light');
            $('.btn-assignment-status').css('color', 'var(--mdb-gray-400)');
            $('#btn-assignment-status-' + new_status).css('color', 'var(--mdb-primary)');
            $('#btn-assignment-status-' + new_status).removeClass('btn-outline-light');
            $('#btn-assignment-status-' + new_status).addClass('btn-outline-primary');
          }
        }
      });
    }
  </script>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>

</body>

</html>
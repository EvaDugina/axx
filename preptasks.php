<!DOCTYPE html>
<html lang="en">

<?php
require_once("common.php");
require_once("dbqueries.php");
require_once("utilities.php");

require_once("./POClasses/Assignment.class.php");
require_once("./POClasses/Task.class.php");
require_once("./POClasses/User.class.php");

$au = new auth_ssh();
checkAuLoggedIN($au);
checkAuIsNotStudent($au);

$User = new User((int)$au->getUserId());

// Обработка некорректного перехода между страницами
if (!isset($_GET['page']) || !is_numeric($_GET['page'])) {
  header('Location:mainpage.php');
  exit;
}

// получение параметров запроса
$page_id = 0;
if (isset($_GET['page']))
  $page_id = $_GET['page'];

echo "<script>var page_id=" . $page_id . ";</script>";


$query = select_discipline_page($page_id);
$result = pg_query($dbconnect, $query);
$row = [];
if (!$result || pg_num_rows($result) < 1) {
  echo 'Неверно указана дисциплина';
  http_response_code(400);
  exit;
}
$row = pg_fetch_assoc($result);

show_head("Задания по дисциплине: " . $row['disc_name'], array('js/preptasks.js'));
?>

<body onload="enableOps(false);">
  <?php show_header($dbconnect, 'Задания по дисциплине', array("Задания по дисциплине: " . $row['disc_name']  => $_SERVER['REQUEST_URI']), $User); ?>
  <main class="pt-2">
    <div class="container-fluid overflow-hidden">
      <div class="row gy-5">
        <div class="col-8">
          <div class="pt-3">

            <div class="row">
              <h2 class="col-9 text-nowrap"> Задания по дисциплине</h2>
              <div class="col-3">
                <button type="submit" class="btn btn-outline-primary px-3" style="display: inline; float: right;" onclick="window.location='taskedit.php?page=<?= $page_id ?>';">
                  <i class="fas fa-plus-square fa-lg"></i> Новое задание
                </button>
              </div>
            </div>

            <?php
            $Page = new Page((int)$page_id);
            $Tasks = $Page->getActiveTasksWithConversation();

            if (count($Tasks) < 1)
              echo 'Задания по этой дисциплине отсутствуют';
            else { ?>
              <div id="checkActiveForm">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th scope="col">
                        <div class="form-check"><input class="form-check-input" type="checkbox" value="" id="checkAllActive" onChange="$('#checkActiveForm').find('input:checkbox').not(this).prop('checked', this.checked);updateOps();" /></div>
                      </th>
                      <th scope="col" style="width:100%;">Название</th>
                      <th scope="col"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    $conversationTask = null;
                    foreach ($Tasks as $Task) {
                      if ($Task->isConversation()) {
                        $conversationTask = $Task;
                        continue;
                      }
                      generateTaskLine($Task);
                    }
                    if ($conversationTask != null)
                      generateTaskLine($conversationTask); ?>
                  </tbody>
                </table>
              </div>
            <?php } ?>

            <div class="my-5">
              <h2 class="pt-5 text-secondary"><i class="fas fa-ban"></i> Архив заданий</h2>
              <?php
              // $query = select_page_tasks($page_id, 0);
              // $result = pg_query($dbconnect, $query);

              $archivedTasks = $Page->getArchivedTasks();
              if (count($archivedTasks) < 1)
                echo 'Архивированные задания по этой дисциплине отсутствуют';
              else { ?>

                <table class="table table-secondary table-hover">
                  <thead>
                    <tr>
                      <!-- <th scope="col"><div class="form-check"><input class="form-check-input" type="checkbox" value="" id="flexCheckDefault"/></div></th> -->
                      <th scope="col" style="width:100%;">Название</th>
                      <th scope="col"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    foreach ($archivedTasks as $archivedTask) { ?>
                      <tr>
                        <!-- <td scope="row"><div class="form-check"><input class="form-check-input" type="checkbox" value="" id="flexCheckDefault"/></div></td> -->
                        <td style="--mdb-table-accent-bg:unset;">
                          <?= getSVGByTaskType($archivedTask->type) ?>&nbsp;
                          <?= $archivedTask->title ?>
                        </td>
                        <td class="text-nowrap" style="--mdb-table-accent-bg:unset;">
                          <div class="d-flex flex-row">
                            <form method="get" action="preptasks_edit.php">
                              <input type="hidden" name="action" value="recover" />
                              <input type="hidden" name="page" value="<?= $Page->id ?>" />
                              <input type="hidden" name="tasknum" id="tasknum" value="<?= $archivedTask->id ?>" />
                              <button type="submit" class="btn btn-outline-primary px-3" data-title="Вернуть из архива">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-down-up" viewBox="0 0 16 16">
                                  <path fill-rule="evenodd" d="M11.5 15a.5.5 0 0 0 .5-.5V2.707l3.146 3.147a.5.5 0 0 0 .708-.708l-4-4a.5.5 0 0 0-.708 0l-4 4a.5.5 0 1 0 .708.708L11 2.707V14.5a.5.5 0 0 0 .5.5zm-7-14a.5.5 0 0 1 .5.5v11.793l3.146-3.147a.5.5 0 0 1 .708.708l-4 4a.5.5 0 0 1-.708 0l-4-4a.5.5 0 0 1 .708-.708L4 13.293V1.5a.5.5 0 0 1 .5-.5z" />
                                </svg></button>&nbsp;
                            </form>
                            <form id="form-deleteTask" name="form-deleteTask" action="taskedit_action.php" method="POST" enctype="multipart/form-data">
                              <input type="hidden" name="action" value="delete">
                              <input type="hidden" name="task_id" value="<?= $archivedTask->id ?>">
                              <button type="submit" class="btn btn-outline-danger px-3" data-title="Удалить навсегда">
                                <i class="fas fa-trash fa-lg"></i>
                              </button>
                            </form>
                            <button type="button" class="btn btn-sm px-3" disabled data-title="Скачать задание">
                              <i class="fas fa-download fa-lg"></i></button>
                          </div>
                        </td>
                      </tr>
                    <?php
                    }  ?>
                  </tbody>
                </table>
              <?php } ?>

            </div>
          </div>
        </div>

        <div class="col-4">
          <div class="p-3 border bg-light" id="mutable">
            <h6>Массовые операции <span id="hint" class="hint">Выберите задания</span></h6>
            <form method="POST" action="preptasks_edit.php" name="linkFileForm" id="linkFileForm" enctype="multipart/form-data">
              <input type="hidden" name="action" value="linkFile" />
              <input type="hidden" name="page" value="<?= $page_id ?>" />
              <input type="hidden" name="tasknum" id="tasknum" value="" />
              <div class="pt-1 pb-1">
                <label><i class="fas fa-paperclip fa-lg"></i> <small>ПРИЛОЖИТЬ ФАЙЛ</small></label>
              </div>
              <div class="pt-1 pb-1 ps-5">
                <input type="file" class="form-control" id="customFile" name="customFile" onChange="setTaskNum(); $('#linkFileForm').trigger('submit')" />
              </div>
            </form>
            <form method="get" action="preptasks_edit.php" name="assignForm" id="assignForm" enctype="multipart/form-data">
              <input type="hidden" name="action" value="assign" />
              <input type="hidden" name="page" value="<?= $page_id ?>" />
              <input type="hidden" name="tasknum" id="tasknum" value="" />
              <input type="hidden" name="groupped" id="tasknum" value="0" />
              <div class="pt-1 pb-1">
                <label><i class="fas fa-users fa-lg"></i> <small>НАЗНАЧИТЬ ИСПОЛНИТЕЛЕЙ</small></label>
              </div>
              <div class="ps-5">
                <section class="w-100 d-flex border">
                  <div class="w-100 h-100 d-flex" style="margin:10px; height:250px; text-align: left;">
                    <div id="demo-example-1" style="overflow-y: auto; height:250px; width: 100%;">
                      <?php
                      $query = select_page_students($page_id);
                      $result2 = pg_query($dbconnect, $query);

                      while ($row2 = pg_fetch_assoc($result2)) { ?>
                        <div class="form-check d-flex justify-content-between">
                          <div>
                            <input class="form-check-input" type="checkbox" name="students[]" value="<?= $row2['id'] ?>" id="flexCheck<?= $row2['id'] ?>">
                            <label class="form-check-label" for="flexCheck<?= $row2['id'] ?>"><?= $row2['fio'] ?> </label>
                          </div>
                          <div style="color: var(--mdb-gray-500);">
                            &nbsp;&nbsp;<?= ($row2['subgroup'] == "") ? "(подгруппа не задана)" : $row2['subgroup'] . " подгруппа" ?>
                          </div>
                        </div>
                      <?php }

                      $query = select_timestamp('3 months');
                      $result2 = pg_query($dbconnect, $query);

                      if ($row2 = pg_fetch_assoc($result2))
                        $timetill = $row2['val'];
                      ?>
                    </div>
                  </div>
                </section>
                <section class="w-100 py-2 d-flex justify-content-center">
                  <div class="form-outline datetimepicker w-100">
                    <input type="date" class="form-control active" name="fromtime" id="datetimepickerExample" style="margin-bottom: 0px;">
                    <label for="datetimepickerExample" class="form-label" style="margin-left: 0px;">Дата начала</label>
                    <div class="form-notch">
                      <div class="form-notch-leading" style="width: 9px;"></div>
                      <div class="form-notch-middle" style="width: 114.4px;"></div>
                      <div class="form-notch-trailing"></div>
                    </div>
                  </div>
                  <div class="form-outline datetimepicker w-100 ms-2">
                    <input id="input-startTime" type="time" name="start_time" class="form-control active" style="margin-bottom: 0px;" value="00:00">
                    <label for="input-startTime" class="form-label" style="margin-left: 0px;">Время начала</label>
                    <div class="form-notch">
                      <div class="form-notch-leading" style="width: 9px;"></div>
                      <div class="form-notch-middle" style="width: 108px;"></div>
                      <div class="form-notch-trailing"></div>
                    </div>
                  </div>
                </section>
                <section class="w-100 py-2 d-flex justify-content-center">
                  <div class="form-outline datetimepicker w-100">
                    <input type="date" class="form-control active" name="tilltime" id="datetimepickerExample" style="margin-bottom: 0px;">
                    <label for="datetimepickerExample" class="form-label" style="margin-left: 0px;">Дата окончания</label>
                    <div class="form-notch">
                      <div class="form-notch-leading" style="width: 9px;"></div>
                      <div class="form-notch-middle" style="width: 114.4px;"></div>
                      <div class="form-notch-trailing"></div>
                    </div>
                  </div>
                  <div class="form-outline datetimepicker w-100 ms-2">
                    <input id="input-endTime" type="time" name="end_time" class="form-control active" style="margin-bottom: 0px;" value="23:59">
                    <label for="input-endTime" class="form-label" style="margin-left: 0px;">Время окончания</label>
                    <div class="form-notch">
                      <div class="form-notch-leading" style="width: 9px;"></div>
                      <div class="form-notch-middle" style="width: 108px;"></div>
                      <div class="form-notch-trailing"></div>
                    </div>
                  </div>
                </section>
                <button type="submit" class="btn btn-outline-primary me-2" onclick="$(assignForm).find(tasknum).val($(checkActiveForm).find('#checkActive:checked:enabled').map(function(){return $(this).val();}).get());
                          $(assignForm).find(groupped).val(0);" onChange="$(assignForm).trigger('submit')">
                  <i class="fas fa-user fa-lg"></i> Назначить индивидуально
                </button>
                <button type="submit" class="btn btn-outline-primary" onclick="$(assignForm).find(tasknum).val($(checkActiveForm).find('#checkActive:checked:enabled').map(function(){return $(this).val();}).get());
                          $(assignForm).find(groupped).val(1);" onChange="$(assignForm).trigger('submit')">
                  <i class="fas fa-users fa-lg"></i> Назначить группой
                </button>
              </div>
            </form>
            <div class="pt-1 pb-1">
              <label><i class="fas fa-copy fa-lg"></i> <small>КОПИРОВАТЬ В ДИСЦИПЛИНУ</small></label>
            </div>

            <div class="pt-1 pb-1 align-items-center ps-5">
              <select id="select-copyToDiscipline" class="form-select" aria-label=".form-select">
                <option value="null" selected>Выберите дисциплину</option>

                <?php
                if ($User->isAdmin())
                  $query = queryGetAllPages();
                else if ($User->isTeacher())
                  $query = queryGetPagesByTeacher($au->getUserId());
                $result = pg_query($dbconnect, $query);

                while ($page = pg_fetch_assoc($result)) {
                  $Page = new Page((int)$page['id']); ?>
                  <option value="<?= $Page->id ?>"><?= $Page->name ?></option>
                <?php } ?>

              </select>
            </div>

            <form method="GET" action="preptasks_edit.php" name="copyToDiscipline" id="form-copyToDiscipline">
              <input type="hidden" id="input-copyToDiscipline-page" name="page" value="" />
              <input type="hidden" name="action" value="copyToDiscipline" />
              <input type="hidden" name="tasknum" id="tasknum" value="" />

              <div class="pt-1 pb-1 align-items-center ps-5">
                <button id="btn-copyToDiscipline" type="button" class="btn btn-outline-primary always-disabled" disabled>
                  <i class="fas fa-copy fa-lg"></i>
                  Копировать
                </button>
              </div>

              <div class="pt-1 pb-1">
                <button id="btn-copyToThisDiscipline" type="button" class="btn btn-outline-primary" disabled>
                  <i class="fas fa-clone fa-lg"></i>
                  Клонировать в этой дисциплине
                </button>
              </div>
            </form>


            <form method="get" action="preptasks_edit.php" name="archiveForm" id="archiveForm">
              <input type="hidden" name="action" value="archive" />
              <input type="hidden" name="page" value="<?= $page_id ?>" />
              <input type="hidden" name="tasknum" id="tasknum" value="" />
              <div class="pt-1 pb-1">
                <button type="submit" class="btn btn-outline-secondary" onclick="$(archiveForm).find(tasknum).val($(checkActiveForm).find('#checkActive:checked:enabled').map(function(){return $(this).val();}).get());
                        $(archiveForm).find(groupped).val(0);" onChange="$(archiveForm).trigger('submit')">
                  <i class="fas fa-ban fa-lg"></i>&nbsp;Перенести в архив</button>
              </div>
            </form>
            <form method="get" action="preptasks_edit.php" name="deleteForm" id="deleteForm">
              <input type="hidden" name="action" value="delete" />
              <input type="hidden" name="page" value="<?= $page_id ?>" />
              <input type="hidden" name="tasknum" id="tasknum" value="" />
              <div class="pt-1 pb-1"><button type="button" class="btn btn-outline-danger" disabled onclick="deleteTasks()">
                  <i class="fas fa-trash fa-lg"></i> Удалить
                </button>
              </div>
            </form>
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
          <p id="dialogMark-text">Внимание! Если отменить назначение, соответсвующие посылки от студента будут утеряны!</p>
        </div>
        <div class="modal-footer">
          <button id="modal-btn-continue" type="button" class="btn btn-danger" data-mdb-dismiss="modal">Продолжить</button>
          <button id="modal-btn-escape" type="button" class="btn btn-primary">Отмена</button>
        </div>
      </div>
    </div>
  </div>


  <?php
  function generateTaskLine($Task)
  { ?>
    <tr role="button" onclick="chooseTaskLine($(this))">
      <div class="row">
        <td class="col-1" scope="row" style="--mdb-table-accent-bg:unset;">
          <div class="form-check"><input class="form-check-input enabler" type="checkbox" value="<?= $Task->id ?>" name="activeTasks[]" id="checkActive" onclick="event.stopPropagation();" onChange="updateOps();" />
          </div>
        </td>
        <td class="col-3" style="--mdb-table-accent-bg:unset;">
          <h6 class="d-flex">
            <?php getSVGByTaskType($Task->type); ?>
            &nbsp;&nbsp;<?= $Task->title ?>
          </h6>

          <?php if (count($Task->getAssignments()) > 0) { ?>
            <div class="small">Назначения:</div>
            <div id="student_container">
              <?php

              foreach ($Task->getAssignments() as $Assignment) {
                $stud_list = "";
                $countStudents = 0;
                foreach ($Assignment->getStudents() as $i => $Student) {
                  if ($i != 0)
                    $stud_list .= ", ";
                  $stud_list .= $Student->getFI();
                  $countStudents++;
                }
                $icon_multiusers = false;
                if ($countStudents > 0)
                  $icon_multiusers = true;
              ?>
                <form id="form-rejectAssignment-<?= $Assignment->id ?>" name="deleteTaskFiles" action="taskedit_action.php" method="POST" enctype="multipart/form-data" class="py-1" onclick="event.stopPropagation();">
                  <input type="hidden" name="task_id" value="<?= $Task->id ?>"></input>
                  <input type="hidden" name="assignment_id" value="<?= $Assignment->id ?>"></input>
                  <input type="hidden" name="action" value="reject"></input>

                  <div class="d-flex justify-content-between align-items-center me-2 badge-primary 
                                <?php if ($stud_list == "") echo "bg-warning"; ?> text-wrap small">
                    <span class="mx-1">
                      <?php if ($stud_list == "") { ?>
                        ~СТУДЕНТЫ ОТСУТСТВУЮТ~
                      <?php } else { ?>
                        <span id="span-assignmentVisibility-<?= $Assignment->id ?>" class="p-0 m-0">
                          <?php getSVGByAssignmentVisibility($Assignment->visibility); ?> &nbsp;
                        </span>
                        <span class="span-assignmentStatus-<?= $Task->id ?>" class="p-0 m-0">
                          <?php getSVGByAssignmentStatus($Assignment->status); ?> &nbsp;
                        </span>
                        <i class="fas fa-user<?= (($icon_multiusers) ? "s" : "") ?>"></i> <?= $stud_list ?>
                      <?php }
                      if (checkIfDefaultDate(convert_timestamp_to_date($Assignment->finish_limit, "Y-m-d")) != "")
                        echo " (до $Assignment->finish_limit)";
                      else
                        echo " (бессрочно)"; ?>
                    </span>
                    <span>
                      <button class="btn btn-link me-0 p-1" type="button" onclick="event.stopPropagation(); window.location='taskassign.php?assignment_id=<?= $Assignment->id ?>';" data-title="Редактировать">
                        <i class="fas fa-pen fa-lg"></i>
                      </button>
                      <button class="btn btn-link me-0 p-1" type="button" onclick="event.stopPropagation(); confirmRejectAssignment(<?= $Assignment->id ?>, 'delete')" data-title="Удалить">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-lg" viewBox="0 0 16 16">
                          <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8 2.146 2.854Z" />
                        </svg>
                      </button>
                    </span>
                  </div>
                </form>
              <?php //$i++;
              } ?>
            </div>

          <?php } ?>



        </td>
        <td class="col-8 text-nowrap" style="--mdb-table-accent-bg:unset;">

          <div class="d-flex justify-content-end mb-3">
            <form name="form-archTask" action="taskedit_action.php" method="POST" enctype="multipart/form-data">
              <input type="hidden" name="action" value="archive">
              <input type="hidden" name="task_id" value="<?= $Task->id ?>">
              <button type="submit" class="btn btn-outline-secondary px-3 me-1" data-title="Перенести в архив">
                <i class="fas fa-ban"></i>
              </button>
            </form>
            <button type="submit" class="btn btn-outline-warning px-3 me-1" onclick="event.stopPropagation(); window.location='taskedit.php?task=<?= $Task->id ?>';" data-title="Редактировать">
              <i class="fas fa-pen fa-lg"></i>
            </button>
            <button type="submit" class="btn btn-outline-warning px-3 me-1" onclick="event.stopPropagation(); window.location='taskassign.php?task_id=<?= $Task->id ?>';" data-title="Назначить">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-people-fill" viewBox="0 0 16 16">
                <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7Zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm-5.784 6A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216ZM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" />
              </svg>
            </button>
            <button type="button" class="btn btn-primary px-3 d-none" data-title="Скачать задание" disabled>
              <i class="fas fa-download fa-lg"></i>
            </button>
          </div>

          <?php
          if (count($Task->getAssignments()) > 0) { ?>
            <section class="d-flex justify-content-end" onclick="event.stopPropagation();">
              <button id="btn-assignment-visibility-<?= $Task->id ?>-0" class="btn btn-outline-light px-3 me-1 btn-assignment-visibility-<?= $Task->id ?>" onclick="ajaxChangeVisibilityAllAssignmentsByTask(0, <?= $Task->id ?>)" style="color: var(--mdb-gray-400); border-color: var(--mdb-gray-400);" data-title="Сделать невидимыми все назначения">
                <?php getSVGByAssignmentVisibility(0); ?>
              </button>
              <button id="btn-assignment-visibility-<?= $Task->id ?>-2" class="btn btn-outline-light px-3 me-1 btn-assignment-visibility-<?= $Task->id ?>" onclick="ajaxChangeVisibilityAllAssignmentsByTask(2, <?= $Task->id ?>)" style="color: var(--mdb-gray-400); border-color: var(--mdb-gray-400);" data-title="Сделдать видимыми все назначения">
                <?php getSVGByAssignmentVisibility(2); ?>
              </button>
              <button id="btn-assignment-visibility-<?= $Task->id ?>-4" class="btn btn-outline-light px-3 me-3 btn-assignment-visibility-<?= $Task->id ?>" onclick="ajaxChangeVisibilityAllAssignmentsByTask(4, <?= $Task->id ?>)" style="color: var(--mdb-gray-400); border-color: var(--mdb-gray-400);" data-title="Отменить все назначения">
                <?php getSVGByAssignmentVisibility(4); ?>
              </button>

              <button id="btn-assignment-status-<?= $Task->id ?>--1" class="btn btn-outline-light px-3 me-1 btn-assignment-status-<?= $Task->id ?>" onclick="ajaxChangeStatusAllAssignmentsByTask(-1, <?= $Task->id ?>)" style="color: var(--mdb-gray-400); border-color: var(--mdb-gray-400);" data-title="Сделать недоступными все назначения">
                <?php getSVGByAssignmentStatus(-1); ?>
              </button>
              <button id="btn-assignment-status-<?= $Task->id ?>-0" class="btn btn-outline-light px-3 me-1 btn-assignment-status-<?= $Task->id ?>" onclick="ajaxChangeStatusAllAssignmentsByTask(0, <?= $Task->id ?>)" style="color: var(--mdb-gray-400); border-color: var(--mdb-gray-400);" data-title="Сделать доступными все назначения">
                <?php getSVGByAssignmentStatus(0); ?>
              </button>
            </section>
          <?php } ?>

        </td>
    </tr>
  <?php } ?>


  <script type="text/javascript">
    function getSelectedTasks() {
      return $(checkActiveForm).find('#checkActive:checked:enabled').map(function() {
        return $(this).val();
      }).get();
    }

    $('#form-deleteTask').submit(function(event) {
      $('#dialogMark-text').text("Внимание! Если удалить задание все его назначения и посылки будут утеряны. Вы хотите продолжить?");
      $('#dialogMark').modal('show');

      event.preventDefault();

      $('#modal-btn-continue').click(function() {
        // let form_reject = document.getElementById(form_id);
        // form_reject.submit();
        // ajaxChangeAssignmentStatus(assignment_id, new_status);
        // $('#form-deleteTask').submit();
        $('#form-deleteTask').unbind('submit').submit()

      });

      $('#modal-btn-escape').click(function() {
        $('#dialogMark').modal('hide');
      });

      // if(confirm_deleteTask) {
      //   this.submit();
      // } else {
      //   event.preventDefault();
      // }
    });

    function confirmRejectAssignment(assignment_id, new_status) {
      $('#dialogMark-text').text("Внимание! Если отменить назначение, соответсвующие посылки от студента будут утеряны!");
      $('#dialogMark').modal('show');

      $('#modal-btn-continue').click(function() {
        // let form_reject = document.getElementById(form_id);
        // form_reject.submit();
        ajaxChangeAssignmentStatus(assignment_id, new_status);
      });

      $('#modal-btn-escape').click(function() {
        $('#dialogMark').modal('hide');
      });
    }

    function chooseTaskLine(tr) {
      tr.find('#checkActive').click();
      // if (tr.hasClass('border-primary')) {
      //   tr.removeClass('border-primary');
      // } else {
      //   tr.addClass('border-primary');
      // }
    }

    function ajaxChangeAssignmentStatus(assignment_id, new_status) {
      var formData = new FormData();
      formData.append('assignment_id', assignment_id);
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
          location.reload();
        }
      });
    }

    function setTaskNum() {
      $('#linkFileForm').find('#tasknum').val($('#checkActiveForm').find('#checkActive:checked:enabled').map(function() {
        return $(this).val();
      }).get());
    }


    function ajaxChangeVisibilityAllAssignmentsByTask(new_status, task_id) {

      var confirm_answer = confirm("Это действие изменит ВИДИМОСТЬ ВСЕХ НАЗНАЧЕНИЙ, прикреплённых к данному заданию. Вы уверены, что хотите продолжить?");
      if (!confirm_answer)
        return;

      var formData = new FormData();

      formData.append('task_id', task_id);
      formData.append('visibility', new_status);
      formData.append('action', 'editVisibility');

      $.ajax({
        type: "POST",
        url: 'taskedit_action.php#content',
        cache: false,
        contentType: false,
        processData: false,
        data: formData,
        dataType: 'html',
        success: function(response) {
          console.log(response);
          response = JSON.parse(response);
          response.forEach((element) => {
            $('#span-assignmentVisibility-' + element.assignment_id).html(element.svg);
          });
        },
        complete: function() {
          if (new_status != "delete") {
            $('.btn-assignment-visibility-' + task_id).removeClass('btn-outline-primary');
            $('.btn-assignment-visibility-' + task_id).addClass('btn-outline-light');
            $('.btn-assignment-visibility-' + task_id).css('color', 'var(--mdb-gray-400)');
            $('.btn-assignment-visibility-' + task_id).css('border-color', 'var(--mdb-gray-400)');
            $('#btn-assignment-visibility-' + task_id + '-' + new_status).css('color', 'var(--mdb-primary)');
            $('#btn-assignment-visibility-' + task_id + '-' + new_status).css('border-color', 'var(--mdb-primary)');
            $('#btn-assignment-visibility-' + task_id + '-' + new_status).removeClass('btn-outline-light');
            $('#btn-assignment-visibility-' + task_id + '-' + new_status).addClass('btn-outline-primary');

          }
        }
      });
    }

    function ajaxChangeStatusAllAssignmentsByTask(new_status, task_id) {

      var confirm_answer = confirm("Это действие изменит СТАТУС ВСЕХ НАЗНАЧЕНИЙ, прикреплённых к данному заданию. Вы уверены, что хотите продолжить?");
      if (!confirm_answer)
        return;

      var formData = new FormData();

      formData.append('task_id', task_id);
      formData.append('status', new_status);
      formData.append('action', 'editStatus');

      $.ajax({
        type: "POST",
        url: 'taskedit_action.php#content',
        cache: false,
        contentType: false,
        processData: false,
        data: formData,
        dataType: 'html',
        success: function(response) {
          console.log(response);
          // response = JSON.parse(response);
          // response.forEach((element) => {
          //   $('#span-assignmentStatus-' + element.assignment_id).html(element.svg);
          // });
          $('.span-assignmentStatus-' + task_id).html(response);
        },
        complete: function() {
          if (new_status != "delete") {
            $('.btn-assignment-status-' + task_id).removeClass('btn-outline-primary');
            $('.btn-assignment-status-' + task_id).addClass('btn-outline-light');
            $('.btn-assignment-status-' + task_id).css('color', 'var(--mdb-gray-400)');
            $('.btn-assignment-status-' + task_id).css('border-color', 'var(--mdb-gray-400)');
            $('#btn-assignment-status-' + task_id + '-' + new_status).css('color', 'var(--mdb-primary)');
            $('#btn-assignment-status-' + task_id + '-' + new_status).css('border-color', 'var(--mdb-primary)');
            $('#btn-assignment-status-' + task_id + '-' + new_status).removeClass('btn-outline-light');
            $('#btn-assignment-status-' + task_id + '-' + new_status).addClass('btn-outline-primary');

          }
        }
      });
    }


    // Отключение статуса disabled кнопке клонирования
    $('#select-copyToDiscipline').on("change", function() {
      if ($(this).val() != "null") {
        // $('#btn-copyToDiscipline').prop("disabled", false);
        $('#btn-copyToDiscipline').removeClass('always-disabled');
        $('#btn-copyToDiscipline').prop('disabled', false);
        $('#btn-copyToDiscipline').removeAttr('disabled');
      } else {
        $('#btn-copyToDiscipline').addClass('always-disabled');
        $('#btn-copyToDiscipline').prop('disabled', true);
        $('#btn-copyToDiscipline').prop("disabled", true);
      }
    });

    // Обработка нажатия кнопок клонировать
    $('#btn-copyToDiscipline').on("click", function() {
      $('#form-copyToDiscipline').find("#tasknum").val(getSelectedTasks());
      // console.log($('#form-copyToDiscipline').find("#tasknum").val());
      $('#input-copyToDiscipline-page').val($('#select-copyToDiscipline').val());
      // console.log($('#input-copyToDiscipline-page').val());
      // $('#form-copyToDiscipline').unbind('submit');
      $('#form-copyToDiscipline').submit();
    });
    $('#btn-copyToThisDiscipline').on("click", function() {
      $('#form-copyToDiscipline').find("#tasknum").val(getSelectedTasks());
      $('#input-copyToDiscipline-page').val(page_id);
      $('#form-copyToDiscipline').submit();
    });

    function deleteTasks() {
      let answer_confirm = confirm("Все выбранные задания будут безвозвратно удалены. Продолжить?");
      if (answer_confirm) {
        $('#deleteForm').find('#tasknum').val(
          $('#checkActiveForm').find('#checkActive:checked:enabled').map(function() {
            return $(this).val();
          }).get()
        );
        $('#deleteForm').submit();
      }
    }
  </script>

</body>

</html>
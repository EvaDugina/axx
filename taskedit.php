<!DOCTYPE html>
<html lang="en">

<?php
require_once("common.php");
require_once("dbqueries.php");
require_once("dbqueries.php");
require_once("utilities.php");
require_once("POClasses/Page.class.php");

$au = new auth_ssh();
checkAuLoggedIN($au);
checkAuIsNotStudent($au);

$User = new User((int)$au->getUserId());

// Обработка некорректного перехода между страницами
if ((!isset($_GET['task']) || !is_numeric($_GET['task']))
  && (!isset($_GET['page']) || !is_numeric($_GET['page']))
) {
  header('Location:mainpage.php');
  exit;
}

$MAX_FILE_SIZE = getMaxFileSize();

// получение параметров запроса
if (isset($_GET['task'])) {
  // Изменение текущего задания

  $Task = new Task((int)$_REQUEST['task']);

  $user_id = $au->getUserId();
  $Page = new Page((int)getPageByTask($Task->id));

  $TestFiles = $Task->getFilesByType(2);
  $TestOfTestFiles = $Task->getFilesByType(3);

  echo "<script>var TASK_ID=" . $Task->id . ";</script>";
  echo "<script>var PAGE_ID=null;</script>";
} else if (isset($_GET['page'])) {
  // Добавление новго задания

  $Page = new Page((int)$_REQUEST['page']);
  // $Task = new Task($Page->id, 0, 1);
  $Task = new Task();
  // $Task->title = "Задание " . (count($Page->getTasks()) + 1) . ".";
  echo "<script>var TASK_ID=null;</script>";
  echo "<script>var PAGE_ID=" . $Page->id . ";</script>";
} else {
  header('Location:mainpage.php');
  exit;
}

show_head("Добавление\Редактирование задания", array('https://unpkg.com/easymde/dist/easymde.min.js'), array('https://unpkg.com/easymde/dist/easymde.min.css'));
?>

<main class="pt-2">

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

  <div class="container-fluid overflow-hidden mb-5">
    <div class="pt-3">
      <div class="row gy-5">
        <div class="col-8">
          <table class="table table-hover">

            <div class="pt-3">
              <div class="form-outline">
                <input id="input-title" class="form-control <?= ($Task->title != "") ? 'active' : ''; ?>" wrap="off" rows="1" style="resize: none; white-space:normal;" name="task-title" value="<?= $Task->title ?>" onkeyup="titleChange()" placeholder="Задание <?= (count($Page->getTasks()) + 1) ?>."></input>
                <label id=" label-input-title" class="form-label" for="input-title">Название задания</label>
                <div id="div-border-title" class="form-notch">
                  <div class="form-notch-leading" style="width: 9px;"></div>
                  <div class="form-notch-middle" style="width: 114.4px;"></div>
                  <div class="form-notch-trailing"></div>
                </div>
              </div>
              <span id="error-input-title" class="error-input" aria-live="polite"></span>
            </div>

            <div class="pt-3 d-flex">
              <div class="w-25 me-3">
                <label>Тип задания:</label>
                <select id="task-type" class="form-select" aria-label=".form-select" name="task-type" <?= $Task->isConversation() ? "disabled" : "" ?>>
                  <option value="0" <?= (($Task->isDefault()) ? "selected" : "") ?>>Обычное</option>
                  <option value="1" <?= (($Task->isProgramming()) ? "selected" : "") ?>>Программирование</option>
                  <option value="2" <?= (($Task->isConversation()) ? "selected" : "") ?>>Беседа</option>
                </select>
              </div>

              <?php
              $markType = "0";
              ?>

              <div id="div-mark-type" class="w-100 d-flex <?= (($Task->isConversation()) ? "d-none" : "") ?>">
                <div>
                  <label>Форма оценивания:</label>
                  <div class="d-flex">
                    <select id="select-markType" name="task-mark-type" class="form-select me-3" aria-label=".form-select" name="select-markType">
                      <option value="оценка" <?= (($Task->isMarkNumber()) ? "selected" : "") ?>>Оценка</option>
                      <option value="зачёт" <?= ((!$Task->isMarkNumber()) ? "selected" : "") ?>>Зачёт</option>
                    </select>
                    <div id="div-markRange" class="d-flex align-items-center <?= ($Task->isMarkNumber()) ? "" : "d-none" ?> w-100">
                      <div class="input-group">
                        <div class="input-group-prepend">
                          <span class="input-group-text" id="inputGroup-sizing-default">Максимальный балл</span>
                        </div>
                        <input id="input-maxMark" type="text" name="task-mark-max" class="form-control" value="<?= $Task->max_mark ?>" aria-label="Default" aria-describedby="inputGroup-sizing-default" placeholder="5...">
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="pt-3">
              <div id="form-description" class="form-outline" onkeyup="descriptionChange()">
                <textarea id="textArea-description" class="form-control <?= 'active' ?>" rows="5" name="task-description" style="resize: none;"><?= $Task->description ?></textarea>
                <label id="label-textArea-description" class="form-label" for="textArea-description">Описание задания</label>
                <script>
                  const easyMDE = new EasyMDE({
                    element: document.getElementById('textArea-description')
                  });
                </script>
                <div class="form-notch">
                  <div class="form-notch-leading" style="width: 9px;"></div>
                  <div class="form-notch-middle" style="width: 114.4px;"></div>
                  <div class="form-notch-trailing"></div>
                </div>
              </div>
              <span id="error-textArea-description" class="error-input" aria-live="polite"></span>
            </div>

            <div class="pt-3 d-flex" id="tools">

              <?php $textArea_codeTest = "";
              if ($Task->type == 1 && isset($TestFiles[0]))
                $textArea_codeTest = $TestFiles[0]->getFullText();
              ?>

              <div class="col-5">
                <select id="select-codeTestFiles-ext" class="form-select" aria-label=".form-select">
                  <?php foreach (getAvailableCodeTestsExtsWithNames() as $ext => $language) { ?>
                    <option value="<?= $ext ?>" <?= (isset($TestFiles[0]) && $TestFiles[0]->getExt() == $ext) ? "selected" : "" ?>><?= $language ?></option>
                  <?php } ?>
                </select>
                <div class="form-outline mt-2">
                  <textarea id="textArea-codeTest" class="form-control <?= ($textArea_codeTest != "") ? "active" : "" ?>" rows="5" name="full_text_test" style="resize: none;" onkeyup="codeTestChange()"><?= $textArea_codeTest ?></textarea>
                  <label id="label-codeTest" class="form-label" for="textArea-codeTest">Код теста</label>
                  <div id="div-border-codeTest" class="form-notch">
                    <div class="form-notch-leading" style="width: 9px;"></div>
                    <div class="form-notch-middle" style="width: 69px;"></div>
                    <div class="form-notch-trailing"></div>
                  </div>
                </div>
              </div>

              <div class="col-1"></div>

              <?php $textArea_codeCheck = "";
              if ($Task->type == 1 && isset($TestOfTestFiles[0]))
                $textArea_codeCheck = $TestOfTestFiles[0]->getFullText();
              ?>

              <div class="form-outline col-6">
                <textarea id="textArea-codeCheck" class="form-control <?= ($textArea_codeCheck != "") ? "active" : "" ?>" rows="5" name="full_text_test_of_test" style="resize: none;" onkeyup="codeCheckTestChange()"><?= $textArea_codeCheck ?></textarea>
                <label id="label-codeCheck" class="form-label" for="textArea-codeCheck">Код проверки</label>
                <div id="div-border-codeCheck" class="form-notch">
                  <div class="form-notch-leading" style="width: 9px;"></div>
                  <div class="form-notch-middle" style="width: 114.4px;"></div>
                  <div class="form-notch-trailing"></div>
                </div>
              </div>

            </div>
          </table>

          <div class="d-flex">
            <button class="btn btn-outline-success d-flex align-items-center" onclick="saveTask(true);">
              Сохранить &nbsp;
              <div id="spinner-save" class="spinner-border d-none" role="status" style="width: 1rem; height: 1rem;">
                <span class="sr-only">Loading...</span>
              </div>
            </button>

            <button id="submit-archive" class="btn btn-outline-secondary ms-2 <?= ($Task->id == null || $Task->status == 1) ? "" : "d-none" ?>" onclick="archiveTask()">
              Архивировать задание &nbsp;
              <div id="spinner-archive" class="spinner-border d-none" role="status" style="width: 1rem; height: 1rem;">
                <span class="sr-only">Loading...</span>
              </div>
            </button>
            <button id="submit-rearchive" class="btn btn-outline-primary ms-2 <?= ($Task->status == 0) ? "" : "d-none" ?>" onclick="reArchiveTask()">
              Разархивировать задание &nbsp;
              <div id="spinner-rearchive" class="spinner-border d-none" role="status" style="width: 1rem; height: 1rem;">
                <span class="sr-only">Loading...</span>
              </div>
            </button>

            <button type="button" class="btn btn-outline-primary" style="display: none;">Проверить сборку</button>

          </div>

        </div>


        <div class="col-4">
          <div class="p-3 border bg-light" style="max-height: calc(100vh - 80px);">

            <div class="pt-1 pb-1">
              <label><i class="fas fa-users fa-lg"></i><small>&nbsp;&nbsp;РЕДАКТОР ВСЕХ НАЗНАЧЕНИЙ</small></label>
            </div>

            <!-- <section class="w-100 py-2 d-flex">
                <div class="form-outline datetimepicker me-3" style="width: 65%;">
                  <input id="input-finishLimit" type="date" class="form-control active" name="finish-limit" onchange="finishLimitChange()">
                  <label id="label-finishLimit" for="input-finishLimit" class="form-label" style="margin-left: 0px;">Срок выполения всех назначений</label>
                  <div id="div-border-finishLimit" class="form-notch">
                    <div class="form-notch-leading" style="width: 9px;"></div>
                    <div class="form-notch-middle" style="width: 114.4px;"></div>
                    <div class="form-notch-trailing"></div>
                  </div>
                </div>

                <div class="d-flex align-items-center">
                  <button onclick="ajaxSetFinishLimit()" type="submit" class="btn btn-outline-primary me-1">Применить</button>
                  <div id="spinner-finishLimit" class="spinner-border d-none" role="status" style="width: 1rem; height: 1rem;">
                    <span class="sr-only">Loading...</span>
                  </div>
                </div>

              </section> -->

          </div>

          <div class="p-3 border bg-light">
            <div class="pt-1 pb-1">
              <label><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bookmark-fill" viewBox="0 0 16 16">
                  <path d="M2 2v13.5a.5.5 0 0 0 .74.439L8 13.069l5.26 2.87A.5.5 0 0 0 14 15.5V2a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2z" />
                </svg>
                <small>&nbsp;&nbsp;ПРИЛОЖЕННЫЕ ФАЙЛЫ</small></label>
            </div>
            <div class="pt-1 pb-1">
              <!-- <input type="hidden" name="MAX_FILE_SIZE" value="3000000" /> -->
              <div id="div-task-files" class="mb-3">
                <?php showFiles($Task->getFiles(), true, $Task->id, $Page->id); ?>
              </div>

              <!-- <form id="form-addTaskFiles" name="taskFiles"> -->
              <div id="div-addTaskFiles">
                <label id="button-addFiles" class="btn btn-outline-default py-2 px-4">
                  <input id="task-files" type="file" name="add-files[]" style="display: none;" multiple>
                  <i class="fas fa-paperclip fa-lg"></i>
                  <span id="files-count" class="text-info"></span>&nbsp; Приложить файлы
                </label>
              </div>
              <!-- </form> -->
            </div>

          </div>
        </div>
      </div>

    </div>
</main>


<div class="modal fade" id="dialogErrorFileName" tabindex="-1" aria-labelledby="dialogErrorFileNameLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 id="modalErrorFileName-h5-title" class="modal-title">

        </h5>
        <button type="button" class="btn-close me-2" data-mdb-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p id="modalErrorFileName-p-text">
        </p>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="dialogErrorFileSize" tabindex="-1" aria-labelledby="dialogErrorFileSizeLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 id="modalErrorFileSize-h5-title" class="modal-title">

        </h5>
        <button type="button" class="btn-close me-2" data-mdb-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p id="modalErrorFileSize-p-text">
        </p>
      </div>
    </div>
  </div>
</div>

<!-- End your project here-->

<!-- СКРИПТ "СОЗДАНИЯ ЗАДАНИЯ" -->
<script type="text/javascript" src="js/taskedit.js"></script>
<script type="text/javascript" src="js/TaskHandler.js"></script>

<script type="text/javascript">
  // window.onbeforeunload = function() {
  //   let unsaved_fields = checkFields();
  //   if (unsaved_fields != "") {
  //     return "Сохранить изменения?";
  //   }
  // };

  var task_files = <?= json_encode($Task->getFiles()) ?>;
  // var task_files_name = [];
  // var previous_file_types = [];
  var array_files = [];

  var original_title = $('#input-title').val();
  var original_type = $('#task-type').val();
  var original_markType = $('#select-markType').val();
  var original_maxMark = $('#input-maxMark').val();
  let easyMDE_value = easyMDE.value();
  var original_description = easyMDE_value.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&");
  var original_extCodeTest = $('#select-codeTestFiles-ext').val();
  var original_codeTest = $('#textArea-codeTest').val();
  var original_codeCheck = $('#textArea-codeCheck').val();
  var original_finishLimit = $('#input-finishLimit').val();


  $(document).ready(function() {
    task_files.forEach((file) => {
      array_files.push({
        "name": file['name_without_prefix'],
        "type": file['type']
      });
      // task_files_name.push(file['name_without_prefix']);
      // previous_file_types[file['id']] = file['type'];
    });
    delete task_files;
  });


  function titleChange() {
    if (isTitleChanged()) {
      $('#div-border-title').children().css({
        "border-width": "4px"
      });
      $('#div-border-title').children().addClass("border-primary");
      $('#label-input-title').addClass("text-primary");
    } else {
      $('#div-border-title').children().css({
        "border-width": "1px"
      });
      $('#div-border-title').children().removeClass("border-primary");
      $('#label-input-title').removeClass("text-primary");
    }
  }

  function typeChange() {
    if (isTypeChanged()) {
      $('#task-type').addClass("rounded-bottom bg-primary text-white");
      $('#task-type').children().css({
        "border-width": "4px"
      });
    } else {
      $('#task-type').removeClass("rounded-bottom bg-primary text-white");
      $('#task-type').children().css({
        "border-width": "1px"
      });
    }
  }

  function descriptionChange() {
    if (isDescriptionChanged()) {
      $('.editor-statusbar').addClass("rounded-bottom bg-primary text-white");
      $('.editor-statusbar > .autosave').text("(имеются несохранённые изменения)");
    } else {
      $('.editor-statusbar').removeClass("rounded-bottom bg-primary text-white");
      $('.editor-statusbar > .autosave').text("");
    }
  };

  function codeTestChange() {
    if (isCodeTestChanged()) {
      $('#div-border-codeTest').children().css({
        "border-width": "4px"
      });
      $('#div-border-codeTest').children().addClass("border-primary");
      $('#label-codeTest').addClass("text-primary");
    } else {
      $('#div-border-codeTest').children().css({
        "border-width": "1px"
      });
      $('#div-border-codeTest').children().removeClass("border-primary");
      $('#label-codeTest').removeClass("text-primary");
    }
  }

  function codeCheckTestChange() {
    if (isCodeCheckChanged()) {
      $('#div-border-codeCheck').children().css({
        "border-width": "4px"
      });
      $('#div-border-codeCheck').children().addClass("border-primary");
      $('#label-codeCheck').addClass("text-primary");
    } else {
      $('#div-border-codeCheck').children().css({
        "border-width": "1px"
      });
      $('#div-border-codeCheck').children().removeClass("border-primary");
      $('#label-codeCheck').removeClass("text-primary");
    }
  }

  function finishLimitChange() {
    if (isFinishLimitChanged()) {
      $('#div-border-finishLimit').children().css({
        "border-width": "4px"
      });
      $('#div-border-finishLimit').children().addClass("border-primary");
      $('#label-finishLimit').addClass("text-primary");
    } else {
      $('#div-border-finishLimit').children().css({
        "border-width": "1px"
      });
      $('#div-border-finishLimit').children().removeClass("border-primary");
      $('#label-finishLimit').removeClass("text-primary");
    }
  }


  // Показывает количество прикрепленных для отправки файлов
  $('#task-files').on('input', function() {
    //$('#files-count').html(this.files.length);

    let new_files = document.getElementById("task-files").files;
    addFiles(new_files);
    $('#task-files').val("");

  });

  $('#select-markType').on('change', function() {
    if ($(this).val() == 1) {
      $('#div-markRange').addClass("d-none");
    } else {
      $('#div-markRange').removeClass("d-none");
    }

  });


  $(document).on('show.bs.modal', '.modal', function() {
    const zIndex = 1040 + 10 * $('.modal:visible').length;
    $(this).css('z-index', zIndex);
    setTimeout(() => $('.modal-backdrop').not('.modal-stack').css('z-index', zIndex - 1).addClass('modal-stack'));
  });


  function checkFields() {
    let now_title = $('#input-title').val();
    let now_type = $('#task-type').val();
    let easyMDE_value = easyMDE.value();
    let now_description = easyMDE_value.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&");;
    let now_extCodeTest = $('#select-codeTestFiles-ext').val();
    let now_codeTest = $('#textArea-codeTest').val();
    let now_codeCheck = $('#textArea-codeCheck').val();

    let name_unsaveFields = "";
    let flag = false;
    if (original_title != now_title) {
      name_unsaveFields += "'Название задания' ";
      flag = true;
    }
    if (original_type != now_type) {
      name_unsaveFields += "'Тип задания' ";
      flag = true;
    }
    if (original_description != now_description) {
      name_unsaveFields += "'Описание задания' ";
      flag = true;
    }
    if (original_extCodeTest != now_extCodeTest) {
      name_unsaveFields += "'Язык файла кода теста' ";
      flag = true;
    }
    if (original_codeTest != now_codeTest) {
      name_unsaveFields += "'Код теста' ";
      flag = true;
    }
    if (original_codeCheck != now_codeCheck) {
      name_unsaveFields += "'Код проверки' ";
      flag = true;
    }

    if (flag) {
      return name_unsaveFields;
    }
    return "";
  }


  function file_name_check(file) {
    // console.log(file['name_without_prefix']);
    if (file['name_without_prefix'] == this.name) {
      return true;
    }
    return false;
  }
</script>

<script type="text/javascript">
  // document.querySelectorAll("#div-task-files div").forEach(function(div) {
  //   let form = div.getElementsByClassName("form-statusTaskFiles")[0];
  //   let select = form.getElementsByClassName("select-taskFileType")[0];
  //   var previous = 0;
  //   select.addEventListener('focus', function() {
  //     previous = this.value;
  //   });
  //   select.addEventListener('change', function(e) {
  //     let unsaved_fields = checkFields();
  //     if (unsaved_fields != "") {
  //       e.preventDefault();
  //       var confirm_answer = confirm("Изменения в полях: " + unsaved_fields + " - остались не сохранёнными. Продолжить без сохранения?");
  //       if (!confirm_answer) {
  //         select.value = previous;
  //         return;
  //       }
  //     }
  //     console.log("SELECT CHANGED!");
  //     var value = e.target.value;
  //     console.log("OPTION: " + value);;
  //     console.log("FORM-StatusTaskFiles: " + form);

  //     let input = document.createElement("input");
  //     input.setAttribute("type", "hidden");
  //     input.setAttribute("value", value);
  //     input.setAttribute("name", 'task-file-status');
  //     console.log(input);

  //     form.append(input);
  //     form.submit();
  //   });
  // });

  // document.querySelectorAll("#form-deleteTaskFile").forEach(function(form) {
  //   form.addEventListener("submit", function(e) {
  //     let unsaved_fields = checkFields();
  //     if (unsaved_fields != "") {
  //       e.preventDefault();
  //       var confirm_answer = confirm("Изменения в полях: " + unsaved_fields + " - остались не сохранёнными. Продолжить без сохранения?");
  //       if (!confirm_answer)
  //         return;
  //     }
  //     form.submit();
  //   })
  // });

  // document.querySelectorAll("#form-changeVisibilityTaskFile").forEach(function(form) {
  //   form.addEventListener("submit", function(e) {
  //     let unsaved_fields = checkFields();
  //     if (unsaved_fields != "") {
  //       e.preventDefault();
  //       var confirm_answer = confirm("Изменения в полях: " + unsaved_fields + " - остались не сохранёнными. Продолжить без сохранения?");
  //       if (!confirm_answer)
  //         return;
  //     }
  //     form.submit();
  //   })
  // });



  // function ajaxSetFinishLimit() {
  //   var formData = new FormData();

  //   let finish_limit = $('#input-finishLimit').val();

  //   if (finish_limit == "")
  //     return;

  //   formData.append('task_id', <?= $Task->id ?>);
  //   formData.append('finish_limit', finish_limit);
  //   formData.append('action', 'editFinishLimit');

  //   $('#spinner-finishLimit').removeClass("d-none");

  //   $.ajax({
  //     type: "POST",
  //     url: 'taskedit_action.php#content',
  //     cache: false,
  //     contentType: false,
  //     processData: false,
  //     data: formData,
  //     dataType: 'html',
  //     success: function(response) {},
  //     complete: function() {
  //       $('#spinner-finishLimit').addClass("d-none");
  //     }
  //   });
  // }

  function getTaskId(initiator = "") {
    if (TASK_ID != null)
      return TASK_ID;
    else if (PAGE_ID != null) {
      TASK_ID = ajaxTaskCreate(PAGE_ID);
      if (TASK_ID == null)
        alert("Не удалось сохранить задание!");
      return TASK_ID;
    } else
      return -1;
  }

  function checkTaskExist() {
    return TASK_ID != null && TASK_ID != -1;
  }

  function saveTask(flagButtonSave = false) {

    let task_id = getTaskId("saveTask()");
    if (task_id == -1)
      return;

    let new_title = new_type = new_mark_type = new_mark_max = new_description = newExtCodeTest = new_codeTest = new_codeCheck = null;

    if (isTitleChanged()) {
      new_title = $('#input-title').val();
      original_title = new_title;
    }
    if (isTypeChanged()) {
      new_type = $('#task-type').val();
      original_type = new_type;
    }
    if (isMarkTypeChanged()) {
      new_mark_type = $('#select-markType').val();
      original_mark_type = new_mark_type;
    }
    if (isMaxMarkChanged()) {
      new_mark_max = $('#input-maxMark').val();
      original_mark_max = new_mark_max;
    }
    if (isDescriptionChanged()) {
      new_description = easyMDE.value();
      original_description = new_description.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&");
    }

    if (isExtCodeTestChanged()) {
      newExtCodeTest = $('#select-codeTestFiles-ext').val();
      original_extCodeTest = newExtCodeTest;
    }
    if (isCodeTestChanged()) {
      new_codeTest = $('#textArea-codeTest').val();
      original_codeTest = new_codeTest;
    }
    if (isCodeCheckChanged()) {
      new_codeCheck = $('#textArea-codeCheck').val();
      original_codeCheck = new_codeCheck;
    }

    // if (new_title == null && new_type == null && new_description == null && new_codeTest == null && new_codeCheck == null) {
    //   if (flagButtonSave)
    //     document.location.href = 'preptasks.php?page=<?= $Page->id ?>';
    //   return "EMPTY";
    // }

    $('#spinner-save').removeClass("d-none");
    let ajaxResponse = ajaxTaskSave(task_id, new_title, new_type, new_mark_type, new_mark_max, new_description, newExtCodeTest, new_codeTest, new_codeCheck);
    $('#spinner-save').addClass("d-none");

    if (ajaxResponse != null) {
      if (flagButtonSave)
        document.location.href = 'preptasks.php?page=<?= $Page->id ?>';
      else if (ajaxResponse != "EMPTY")
        document.location.href = "taskedit.php?task=" + task_id;
      return;
    } else {
      alert("Не удалось сохранить изменения. Попробуйте ещё раз.")
      return;
    }

    if (ajaxResponse != "EMPTY") {
      titleChange();
      typeChange();
      descriptionChange();
      codeTestChange();
      codeCheckTestChange();
    }

  }

  function archiveTask() {

    let task_id = getTaskId("archiveTask()");
    if (task_id == -1)
      return;

    $('#spinner-archive').removeClass("d-none");
    let ajaxResponse = ajaxTaskArchive(task_id);
    $('#spinner-archive').addClass("d-none");

    saveTask();

    if (ajaxResponse != null) {} else {
      alert("Не удалость заархивировать задание.");
      return;
    }

    $('#submit-archive').addClass("d-none");
    $('#submit-rearchive').removeClass("d-none");
  }

  function reArchiveTask() {

    let task_id = getTaskId("reArchiveTask()");
    if (task_id == -1)
      return;

    $('#spinner-rearchive').removeClass("d-none");
    let ajaxResponse = ajaxTaskReArchive(task_id);
    $('#spinner-rearchive').addClass("d-none");

    if (ajaxResponse != null) {} else {
      alert("Не удалость разархивировать задание.");
      return;
    }

    saveTask();

    $('#submit-rearchive').addClass("d-none");
    $('#submit-archive').removeClass("d-none");



  }


  function addFiles(new_files) {

    let task_id = getTaskId();
    if (task_id == -1)
      return;

    let files = [];
    let permitted_file_names = [];
    Object.entries(new_files).forEach((file) => {
      if (!array_files.find((task_file) => task_file.name == file[1].name)) {
        files.push(file[1]);
      } else {
        permitted_file_names.push(file[1]['name']);
      }
    });

    if (permitted_file_names.length > 0) {
      $('#modalErrorFileName-h5-title').text("Внимание!");
      if (permitted_file_names.length == 1)
        $('#modalErrorFileName-p-text').html("<strong>" + permitted_file_names.join(", ") + "</strong> не был добавлен. Файл с таким названием уже присутствует.");
      else
        $('#modalErrorFileName-p-text').html("<strong>" + permitted_file_names.join(", ") + "</strong> не были добавлены. Файлы с такими названиями уже присутствуют.");

      $('#dialogErrorFileName').modal("show");
    }

    if (files.length < 1) {
      return;
    }

    let ajaxResponse = ajaxTaskAddFiles(task_id, files);
    if ("response" in ajaxResponse && ajaxResponse['response'] != null) {
      $('#div-task-files').html(ajaxResponse['response']);
    }
    if ("permitted_file_names" in ajaxResponse && ajaxResponse['permitted_file_names'].length > 0) {
      ajaxResponse['permitted_file_names'].forEach((file_name) => {
        let index = files.findIndex((file) => file.name == file_name);
        files.splice(index, 1);
      });

      $('#modalErrorFileSize-h5-title').text("Внимание!");
      if (ajaxResponse['permitted_file_names'].length == 1)
        $('#modalErrorFileSize-p-text').html("<strong>" + ajaxResponse['permitted_file_names'].join(", ") + "</strong> не был добавлен. Файл превышает допустимый размер.");
      else
        $('#modalErrorFileSize-p-text').html("<strong>" + ajaxResponse['permitted_file_names'].join(", ") + "</strong> не были добавлены. Файлы превышают допустимый размер.");

      $('#dialogErrorFileSize').modal("show");
    }

    if (files.length > 0) {
      files.forEach((file) => {
        array_files.push({
          "name": file.name,
          "type": 0
        });
      });
    } else {
      return;
    }

    $(document).on('hidden.bs.modal', '.modal', function() {
      if ($('.modal:visible').length < 1)
        saveTask();
    });

  }

  function deleteFile(task_id, file_id, file_name) {

    let response = ajaxTaskDeleteFile(task_id, file_id);
    if (response != null) {
      let index = array_files.findIndex((file) => file.name == file_name);
      if (array_files[index].type == "2" || array_files[index].type == "3") {
        document.location.href = "taskedit.php?task=" + task_id;
        return;
      }

      $('#div-taskFile-' + file_id).remove();
      array_files.splice(index, 1);
      // previous_file_types.splice(file_id, 1);
    } else {
      alert("Не удалость удалить файл.");
      return;
    }
  }

  function changeFileVisibility(task_id, file_id) {
    let visibility = parseInt($('#btn-chnageFileVisibility-' + file_id).attr("visibility"));
    let new_visibility = (visibility + 1) % 2;
    var response = ajaxTaskChangeFileVisibility(task_id, file_id, new_visibility);
    if (response != null) {
      if ("svg" in response) {
        $('#btn-chnageFileVisibility-' + file_id).html(response['svg']);
        $('#btn-chnageFileVisibility-' + file_id).attr("visibility", (visibility + 1) % 2);
      }
    } else {
      alert("Не удалость сменить видимость файла.");
      return;
    }
  }

  function changeFileType(event, task_id, file_id, file_name) {
    let new_type = $('#select-taskFileType-' + file_id).val();
    let index = array_files.findIndex((file) => file.name == file_name);
    var response = ajaxTaskChangeFileType(task_id, file_id, new_type);
    $('#select-taskFileType-' + file_id).blur();
    if (response != null) {
      if ("svg" in response) {
        if (new_type == 2 || new_type == 3) {
          let empty = saveTask();
          if (empty == "EMPTY")
            document.location.reload();
        } else if (array_files[index].type == 2 || array_files[index].type == 3) {
          let empty = saveTask();
          if (empty == "EMPTY")
            document.location.reload();
        }

        $('#span-fileType-' + file_id).html(response['svg']);

        array_files[index].type = new_type;
      } else {
        $('#select-taskFileType-' + file_id).val(array_files[index].type);

        if ("error" in response) {
          if (response["error"] == "EXT_FOR_CODE_TEST") {
            if (new_type == 2)
              alert("Файл с таким расширением не может быть прикреплён в качестве кода теста.");
            else
              alert("Файл с таким расширением не может быть прикреплён в качестве кода проверки теста.");
          } else if (response["error"] == "EXT_FOR_CODE_PROJECT")
            alert("Файл с таким расширением не может быть прикреплён в качестве исходного кода.");
          else if (response["error"] == "NO_MORE_FILES_CODE") {
            if (new_type == 2)
              alert("В задании не может быть двух файлов Кода теста.");
            else
              alert("В задании не может быть двух файлов Кода проверки.");
          }
        }
      }
    } else {
      alert("Не удалость сменить тип файла.");
      $('#select-taskFileType-' + file_id).val(array_files[index].type);
      return;
    }
  }



  function isTitleChanged() {
    let now_title = $('#input-title').val();
    if (original_title != now_title)
      return true
    return false;
  }

  function isTypeChanged() {
    let now_type = $('#task-type').val();
    if (original_type != now_type)
      return true;
    return false;
  }

  function isDescriptionChanged() {
    let easyMDE_value = easyMDE.value();
    let now_description = easyMDE_value.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&");
    if (original_description != now_description)
      return true;

    return false;
  }

  function isExtCodeTestChanged() {
    let now_extCodeTest = $('#select-codeTestFiles-ext').val();
    if (original_extCodeTest != now_extCodeTest) {
      return true;
    }
    return false;
  }

  function isCodeTestChanged() {
    let now_codeTest = $('#textArea-codeTest').val();
    if (original_codeTest != now_codeTest) {
      return true;
    }
    return false;
  }

  function isCodeCheckChanged() {
    let now_codeCheck = $('#textArea-codeCheck').val();
    if (original_codeCheck != now_codeCheck) {
      return true;
    }
    return false;
  }

  function isMarkTypeChanged() {
    let now_markType = $('#select-markType').val();
    if (original_markType != now_markType) {
      return true;
    }
    return false;
  }

  function isMaxMarkChanged() {
    let now_maxMark = $('#input-maxMark').val();
    if (original_maxMark != now_maxMark) {
      return true;
    }
    return false;
  }

  function isFinishLimitChanged() {
    let now_finishLimit = $('#input-finishLimit').val();
    if (original_finishLimit != now_finishLimit) {
      return true;
    }
    return false;
  }
</script>



</body>

</html>
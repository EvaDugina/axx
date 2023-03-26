<!DOCTYPE html>
<html lang="en">

<?php
require_once("common.php");
require_once("dbqueries.php");
require_once("dbqueries.php");
require_once("POClasses/Page.class.php");

// защита от случайного перехода
$au = new auth_ssh();
if (!$au->isAdmin() && !$au->isTeacher()){
	$au->logout();
	header('Location:login.php');
  exit;
}

// Обработка некорректного перехода между страницами
if ((!isset($_GET['task']) || !is_numeric($_GET['task'])) 
&& (!isset($_GET['page']) || !is_numeric($_GET['page']))){
	header('Location:mainpage.php');
	exit;
}

// получение параметров запроса
if (isset($_GET['task'])){
	// Изменение текущего задания

  $Task = new Task((int)$_REQUEST['task']);

  $user_id = $au->getUserId();
  $Page = new Page((int)getPageByTask($Task->id));

  $TestFiles = $Task->getFilesByType(2);
  $TestOfTestFiles = $Task->getFilesByType(3);

} else if (isset($_GET['page'])){
	// Добавление новго задания

  $Page = new Page((int)$_REQUEST['page']);
  $Task = new Task($Page->id, 0, 0);
  $Task->setTitle("Задание " . (count($Page->getTasks()) + 1) . ".");

} else {
  header('Location:mainpage.php');
	exit;
}

show_head("Добавление\Редактирование задания", array('https://unpkg.com/easymde/dist/easymde.min.js'), array('https://unpkg.com/easymde/dist/easymde.min.css'));
show_header($dbconnect, 'Редактор заданий', 
	array("Задания по дисциплине: " . $Page->disc_name  => 'preptasks.php?page='. $Page->id,
	"Редактор заданий" => $_SERVER['REQUEST_URI'])
); ?>

<main class="pt-2">
  <div class="container-fluid overflow-hidden">
      
    <div class="pt-3">
      <div class="row gy-5">
        <div class="col-8">
          <table class="table table-hover">
    
            <div class="pt-3">
              <div class="form-outline">
                <input id="input-title" class="form-control <?php echo 'active';?>" wrap="off" rows="1" 
                style="resize: none; white-space:normal;" name="task-title" value="<?=$Task->title?>">
                </input>
                <label id="label-input-title" class="form-label" for="input-title">Название задания</label>
                <div class="form-notch">
                  <div class="form-notch-leading" style="width: 9px;"></div>
                  <div class="form-notch-middle" style="width: 114.4px;"></div>
                  <div class="form-notch-trailing"></div>
                </div>
              </div>
              <span id="error-input-title" class="error-input" aria-live="polite"></span>
            </div>
				
            <div class="pt-3">
              <label>Тип задания:</label>
              <select id = "task-type" class="form-select" aria-label=".form-select" name="task-type">
                <option value = "0" <?=(($Task->type==0) ? "selected" : "")?> >Обычное</option>
                <option value = "1" <?=(($Task->type==1) ? "selected" : "")?>>Программирование</option>
              </select>
            </div>

            <div class="pt-3">
              <div class="form-outline">
                <textarea id="textArea-description" class="form-control <?php echo 'active';?>" 
                rows="5" name="task-description" style="resize: none;"><?=$Task->description?></textarea>
                <label id="label-textArea-description" class="form-label" for="textArea-description">Описание задания</label>
                <script>
                  const easyMDE = new EasyMDE({element: document.getElementById('textArea-description')});
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
              if($Task->type == 1 && isset($TestFiles[0])) 
                $textArea_codeTest = $TestFiles[0]->full_text;
              ?>
              
              <div class="form-outline col-5">
                <textarea id="textArea-codeTest" class="form-control" rows="5" name="full_text_test" 
                style="resize: none;"><?=$textArea_codeTest?></textarea>
                <label class="form-label" for="textArea-codeTest">Код теста</label>
                <div class="form-notch">
                  <div class="form-notch-leading" style="width: 9px;"></div>
                  <div class="form-notch-middle" style="width: 114.4px;"></div>
                  <div class="form-notch-trailing"></div>
                </div>
              </div>

              <div class="col-1"></div>

              <?php $textArea_codeCheck = "";
              if($Task->type == 1 && isset($TestOfTestFiles[0])) 
                $textArea_codeCheck = $TestOfTestFiles[0]->full_text;
              ?>

              <div class="form-outline col-6">
                <textarea id="textArea-codeCheck" class="form-control" rows="5" name="full_text_test_of_test" 
                style="resize: none;"><?=$textArea_codeCheck?></textarea>
                <label class="form-label" for="textArea-codeCheck">Код проверки</label>
                <div class="form-notch">
                  <div class="form-notch-leading" style="width: 9px;"></div>
                  <div class="form-notch-middle" style="width: 114.4px;"></div>
                  <div class="form-notch-trailing"></div>
                </div>
              </div>

            </div>
          </table>

          <div class="d-flex">
            <button id="submit-save" class="btn btn-outline-success me-2 d-flex align-items-center" onclick="saveFields()">
              Сохранить &nbsp;
              <div id="spinner-save" class="spinner-border d-none" role="status" style="width: 1rem; height: 1rem;">
                <span class="sr-only">Loading...</span>
              </div>
            </button>

              <button id="submit-archive" class="btn btn-outline-secondary <?=($Task->id != -1 && $Task->status == 0) ? 'd-none' : ''?>"
              onclick="archiveTask()">
                Архивировать задание &nbsp;
                <div id="spinner-archive" class="spinner-border d-none" role="status" style="width: 1rem; height: 1rem;">
                  <span class="sr-only">Loading...</span>
                </div>  
              </button>
              <button id="submit-rearchive" class="btn btn-outline-primary <?=($Task->id != -1 && $Task->status == 1) ? 'd-none' : ''?>"
              onclick="reArchiveTask()">
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

            <section class="w-100 py-2 d-flex">
              <div class="form-outline datetimepicker me-3" style="width: 65%;">
                <input id="input-finishLimit" type="date" class="form-control active" name="finish-limit" 
                <?php /*if()*/?>>
                <label for="input-finishLimit" class="form-label" style="margin-left: 0px;">Срок выполения всех назначений</label>
                <div class="form-notch">
                  <div class="form-notch-leading" style="width: 9px;"></div>
                  <div class="form-notch-middle" style="width: 114.4px;"></div>
                  <div class="form-notch-trailing"></div>
                </div>
              </div>

              <div class="d-flex align-items-center">
                <button onclick="setFinishLimit()" type="submit" class="btn btn-outline-primary me-1">Применить</button>
                <div id="spinner-finishLimit" class="spinner-border d-none" role="status" style="width: 1rem; height: 1rem;">
                  <span class="sr-only">Loading...</span>
                </div>
              </div> 

            </section>

            <?php
            // Получение студентов, прикреплённызх к заданию
            // $query = select_students_by_group_by_page_by_task($Page->id, $Task->id);
            // $result = pg_query($dbconnect, $query);
            // $students = pg_fetch_all_assoc($result);  ?>

            <!-- <section class="w-100 d-flex border" style="height: 50%;">
              <div class="w-100 h-100 d-flex" style="margin:10px; height: 100%; text-align: left;">
                <div id="main-accordion-students" class="accordion accordion-flush" style="overflow-y: auto; height: 100%; width: 100%;">

                  <?php
                  // $now_group_id = -1;
                  // $count_chosen_students = 0;
                  // if ($students){
                  //   foreach ($students as $key => $student) {
                  //     if($student['group_id'] != $now_group_id) {
                  //       $count_chosen_students=0;

                  //       $query = pg_query($dbconnect, select_group_students_count($student['group_id']));
                  //       $group_students_count = pg_fetch_assoc($query)['count'];

                  //       // Обработка полностью выбранных групп
                  //       $flag_full_group = true;
                  //       if ($Task->id != -1) {
                  //         for($i=$key; $i < count($students); $i++){
                  //           if($students[$i]['group_id'] != $student['group_id'])
                  //             break;
                  //           if($students[$i]['task_id'] != $Task->id)
                  //             $flag_full_group = false;
                  //           else $count_chosen_students++;
                  //         }
                  //       } else
                  //         $flag_full_group = false;
                  //       if($key > 0) { ?>
                  //             </div>
                  //           </div>
                  //         </div>
                  //       </div>
                  //       <?php 
                      //}?>
                        <div class="accordion-item">
                          <div id="accordion-gheader-<?=$student['group_id']?>" class="accordion-header">
                            <button class="accordion-button" type="button"
                            data-mdb-toggle="collapse" data-mdb-target="#accordion-collapse-<?=$key?>" aria-expanded="true"
                            aria-controls="accordion-collapse-<?=$key?>">
                              <div class="form-check d-flex">
                                <input id="group-<?=$student['group_id']?>" class="accordion-input-item form-check-input input-group" type="checkbox" 
                                value="g<?=$student['group_id']?>" id="flexCheck1" onclick="markStudentElements(<?=$student['group_id']?>)" 
                                name="checkboxStudents[]" <?php if($flag_full_group) echo 'checked';?>>
                                <span id="group-<?=$student['group_id']?>-stat" class="badge badge-primary align-self-center" style="color: black;">
                                  <?=$count_chosen_students?> / <?=$group_students_count?>
                                </span>   
                                <label class="ms-1 form-check-label" for="flexCheck1" style="font-weight: bold;"><?=$student['group_name']?></label>
                              </div>                   
                            </button>
                          </div>
                          <div id="accordion-collapse-<?=$key?>" class="accordion-collapse collapse" aria-labelledby="accordion-gheader-<?=$key?>"
                          data-mdb-parent="#main-accordion-students">
                            <div class="accordion-body">
                              <div id="group-accordion-students" class="accordion accordion-flush">
                      <?php 
                    // }?>
                      <div id="item-from-group-<?=$student['group_id']?>" class="accordion-item">
                        <div id="accordion-sheader-<?=$student['id']?>" class="accordion-header">
                          <div d-flex justify-content-between" type="button">
                            <div class="form-check ms-3">
                              <input id="student-<?=$student['id']?>" class="accordion-input-item form-check-input input-student" 
                              type="checkbox" value="s<?=$student['id']?>" name="checkboxStudents[]" 
                              <?php 
                              // if($Task->id != -1 && isset($student['task_id']) && $student['task_id'] == $Task->id) echo 'checked';?>>
                              <label class="form-check-label" for="flexCheck1"><?=$student['fi']?></label>
                            </div>
                          </div>
                        </div>
                      </div>
                      <?php 
                  //     $now_group_id = $student['group_id'];
                  //   }
                  // } else {?>
                    <strong>СТУДЕНТЫ ОТСУТСТВУЮТ</strong>
                  <?php 
                // }?>

                </div>
              </div>
            </section> -->

            <!-- <div class="p-1 border bg-light">
              <div class="d-flex" data-toggle="buttons">
                <label class="btn btn-outline-default py-2 px-4 me-2">
                  <input id="input-deligate-by-individual" type="radio" name="task-status-deligate" style="display: none;" value="by-individual">
                    <i class="fas fa-user fa-lg"></i>&nbsp; Назначить <br> индивидуально
                </label>  
                <label class="btn btn-outline-default py-2 px-4">
                  <input id="input-deligate-by-group" type="radio" name="task-status-deligate" style="display: none;" value="by-group">
                    <i class="fas fa-users fa-lg"></i>&nbsp; Назначить <br> группе
                </label>  
              </div>
            </div> -->

            <!-- <span id="error-choose-executor" class="error-input" aria-live="polite"></span> -->
          </div>

          <div class="p-3 border bg-light">
            <div class="pt-1 pb-1">
              <label><i class="fa fa-files-o fa-lg"></i><small>&nbsp;&nbsp;ПРИЛОЖЕННЫЕ ФАЙЛЫ</small></label>
            </div>
            <div class="pt-1 pb-1">
              <!-- <input type="hidden" name="MAX_FILE_SIZE" value="3000000" /> -->
                <?php if ($Task->id != -1) {?>
                  <div id="div-task-files" class="mb-3">
                    <?php showFiles($Task->getFiles(), true, $Task->id, $Page->id);?>
                  </div>
                <?php }?>
              
              <form id="form-addTaskFiles" name="taskFiles" action="taskedit_action.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="task_id" value="<?=$Task->id?>"></input>
                <input type="hidden" name="page_id" value="<?=$Page->id?>"></input>
                <input type="hidden" name="flag-addFiles" value="true"></input>
                <label class="btn btn-outline-default py-2 px-4">
                  <input id="task-files" type="file" name="add-files[]" style="display: none;" multiple>
                    <i class="fa-solid fa-paperclip"></i>
                    <span id="files-count" class="text-info"></span>&nbsp; Приложить файлы
                </label> 
              </form>
            </div>
                
          </div>
      </div>
    </div>

  </div>
</main>
<!-- End your project here-->

    <!-- СКРИПТ "СОЗДАНИЯ ЗАДАНИЯ" -->
  <script type="text/javascript" src="js/taskedit.js"></script>
  
  <script type="text/javascript">

    window.onbeforeunload = function () {
      let unsaved_fields = checkFields();
      if (unsaved_fields != "") {
        return "Сохранить изменения?";
      }
    };

    let form_addFiles  = document.getElementById('form-addTaskFiles');
    var added_files = <?=json_encode($Task->getFiles())?>;

    let original_title = "<?=$Task->title?>";
    let original_type = "<?=$Task->type?>";
    let original_description = "<?=$Task->description?>";
    let original_codeTest = "<?=$textArea_codeTest?>";
    let original_codeCheck = "<?=$textArea_codeCheck?>";

    // Показывает количество прикрепленных для отправки файлов
    $('#task-files').on('change', function() {
      //$('#files-count').html(this.files.length);
      
      let new_file = document.getElementById("task-files").files[0];
      
      if (added_files.find(file_name_check, new_file)){
        alert("ФАЙЛ С ТАКИМ НАЗВАНИЕМ УЖЕ СУЩЕСТВУЕТ. ПЕРЕИМЕНУЙТЕ ПРИКРЕПЛЯЕМЫЙ ИЛИ ВЫБЕРИТЕ ДРУГОЙ");
        return;
      }

      let unsaved_fields = checkFields();
      if (unsaved_fields != "") {
        let confirm = confirm("Изменения в полях: " + unsaved_fields + " - остались не сохранёнными. Продолжить без сохранения?");
        if (!confirm)
          return;
      }

      window.onbeforeunload = null;
      form_addFiles.submit();
      
      // Реализовать через ajax, чтобы быстрее было
      // var formData = new FormData();
      // formData.append('task_id', <?=$Task->id?>);
      // formData.append('page_id', <?=$Page->id?>);
      // $.each($("#task-files")[0].files, function(key, input){
      //   formData.append('add-files[]', input);
      // });

      // console.log(formData);

      // $.ajax({
      //   type: "POST",
      //   url: 'taskedit_action.php#content',
      //   cache: false,
      //   contentType: false,
      //   processData: false,
      //   data: formData,
      //   dataType : 'html',
      //   success: function (response){
      //     $('#div-task-files').innerHTML = response;
      //   },
      //   complete: function() {}
      // });
      
    });


  function checkFields() {
    let now_title = $('#input-title').val();
    let now_type = $('#task-type').val();
    let now_description = easyMDE.value();
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
      if (file['name_without_prefix'] == this.name){
        return true;
      }
      return false;
    }

  </script>

<script type="text/javascript">


  document.querySelectorAll("#div-task-files div").forEach(function (div) {
    let form = div.getElementsByClassName("form-statusTaskFiles")[0];
    let select = form.getElementsByClassName("select-statusTaskFile")[0];
    var previous = 0;
    select.addEventListener('focus', function () {
        previous = this.value;
    });
    select.addEventListener('change', function (e) {
      if (checkFields()) {
        console.log("SELECT CHANGED!");
        var value = e.target.value;
        console.log("OPTION: " + value);;
        console.log("FORM-StatusTaskFiles: " + form);

        let input = document.createElement("input");
        input.setAttribute("type", "hidden");
        input.setAttribute("value", value);
        input.setAttribute("name", 'task-file-status');
        console.log(input);
        
        form.append(input);
        form.submit();  
      } else {
        select.value = previous;
      }
    });
  });

  document.querySelectorAll("#form-deleteTaskFile").forEach(function (form) {
    form.addEventListener("submit", function (e) {
      if (checkFields()) {
        form.submit();
      } else {
        e.preventDefault();
      }
    })
  });


  function setFinishLimit() {
    var formData = new FormData();

    let finish_limit = $('#input-finishLimit').val();

    if(finish_limit == "")
      return;

    formData.append('task_id', <?=$Task->id?>);
    formData.append('finish_limit', finish_limit);
    formData.append('action', 'editFinishLimit');

    $('#spinner-finishLimit').removeClass("d-none");

    $.ajax({
      type: "POST",
      url: 'taskedit_action.php#content',
      cache: false,
      contentType: false,
      processData: false,
      data: formData,
      dataType : 'html',
      success: function(response) {
      },
      complete: function() {
        $('#spinner-finishLimit').addClass("d-none");
      }
    });
  }

  function saveFields() {
    var formData = new FormData();

    formData.append('task_id', <?=$Task->id?>);
    formData.append('action', 'save');
    
    if (checkTitle()) {
      let now_title = $('#input-title').val();
      formData.append('title', now_title);
      original_title = now_title;
    }
    if (checkType()) {
      let now_type = $('#task-type').val();
      formData.append('type', now_type);
      original_type = now_type;
    }
    if (checkDescription()) {
      let now_description = easyMDE.value();
      formData.append('description', now_description);
      original_description = now_description;
    }
    
    if (checkCodeTest()) {
      let now_codeTest = $('#textArea-codeTest').val();
      formData.append('codeTest', now_codeTest);
      original_codeTest = now_codeTest;
    }
    if (checkCodeCheck()) {
      let now_codeCheck = $('#textArea-codeCheck').val();
      formData.append('codeCheck', now_codeCheck);
      original_codeCheck = now_codeCheck;
    }

    $('#spinner-save').removeClass("d-none");

    $.ajax({
      type: "POST",
      url: 'taskedit_action.php#content',
      cache: false,
      contentType: false,
      processData: false,
      data: formData,
      dataType : 'html',
      success: function(response) {
        $('#div-task-files').html(response);
      },
      complete: function() {
        console.log("COMPLETED!");
        $('#spinner-save').addClass("d-none");
      }
    });   
  }

  function archiveTask() {
    var formData = new FormData();

    formData.append('task_id', <?=$Task->id?>);
    formData.append('action', 'archive');

    $('#spinner-archive').removeClass("d-none");

    $.ajax({
      type: "POST",
      url: 'taskedit_action.php#content',
      cache: false,
      contentType: false,
      processData: false,
      data: formData,
      dataType : 'html',
      success: function(response) {
      },
      complete: function() {
        $('#spinner-archive').addClass("d-none");
        $('#submit-archive').addClass("d-none");
        $('#submit-rearchive').removeClass("d-none");
      }
    });
  }

  function reArchiveTask() {
    var formData = new FormData();

    formData.append('task_id', <?=$Task->id?>);
    formData.append('action', 'rearchive');

    $('#spinner-rearchive').removeClass("d-none");

    $.ajax({
      type: "POST",
      url: 'taskedit_action.php#content',
      cache: false,
      contentType: false,
      processData: false,
      data: formData,
      dataType : 'html',
      success: function(response) {
      },
      complete: function() {
        $('#spinner-rearchive').addClass("d-none");
        $('#submit-rearchive').addClass("d-none");
        $('#submit-archive').removeClass("d-none");
      }
    });
  }





  function checkTitle() {
    let now_title = $('#input-title').val();
    if (original_title != now_title)
      return true
    return false;
  }
  function checkType() {
    let now_type = $('#task-type').val();
    if (original_type != now_type)
      return true;
    return false;
  }
  function checkDescription() {
    let now_description = easyMDE.value();
    if (original_description != now_description)
      return true;

    return false;
  }
  function checkCodeTest() {
    let now_codeTest = $('#textArea-codeTest').val();
    if (original_codeTest != now_codeTest) {
      return true;
    }
    return false;
  }
  function checkCodeCheck() {
    let now_codeCheck = $('#textArea-codeCheck').val();
    if (original_codeCheck != now_codeCheck) {
      return true;
    }
    return false;
  }


</script>



</body>

</html>

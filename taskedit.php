<!DOCTYPE html>
<html lang="en">

<?php
require_once("common.php");
require_once("dbqueries.php");

// защита от случайного перехода
$au = new auth_ssh();
if (!$au->isAdmin() && !$au->isTeacher()){
	$au->logout();
	header('Location:login.php');
}

// Обработка некорректного перехода между страницами
if ((!isset($_GET['task']) || !is_numeric($_GET['task'])) 
&& (!isset($_GET['page']) || !is_numeric($_GET['page']))){
	header('Location:mainpage.php');
	exit;
}

// получение параметров запроса
$task_id = -1;
$page_id = -1;
if (isset($_GET['task'])){
	// Изменение текущего задания
	$task_id = $_REQUEST['task'];
	$query = select_task($task_id);
	$result = pg_query($dbconnect, $query);
	$task = pg_fetch_assoc($result);

	if (!$result)
		echo 'ОШИБКА! ТАКОГО ЗАДАНИЯ НЕТ В БД!!';

	$page_id = $task['page_id'];
	$query = select_discipline_page($page_id);
	$result = pg_query($dbconnect, $query);
	$page = pg_fetch_assoc($result);

	$query = select_task_file(2, $task_id);
	$result = pg_query($dbconnect, $query);
	$test = pg_fetch_all($result);

	$query = select_task_file(3, $task_id);
	$result = pg_query($dbconnect, $query);
	$test_of_test = pg_fetch_all($result);

} else if (isset($_GET['page'])){
	// Добавление новго задания
	$page_id = $_REQUEST['page'];
	$query = select_discipline_page($page_id);
	$result = pg_query($dbconnect, $query);
	$page = pg_fetch_assoc($result);

  $query = select_count_page_tasks($page_id);
  $result = pg_query($dbconnect, $query);
  $count_tasks = pg_fetch_assoc($result)['count'];

  $task = array(
    'title' => "",
    'description' => "",
    'type' => ""
  );

}

show_head("Добавление\Редактирование задания");
show_header_2($dbconnect, 'Редактор заданий', 
	array("Задания по дисциплине: " . $page['disc_name']  => 'preptasks.php?page='. $page_id,
	"Редактор заданий" => $_SERVER['REQUEST_URI'])
); ?>

<!--<script type="text/javascript" src="js/taskedit.js"></script>-->

<main class="pt-2">
  <div class="container-fluid overflow-hidden">
      
    <form id="form-taskEdit" name="form-taskEdit" class="pt-3" action="taskedit_action.php?page=<?=$page_id?>" method="post">
      <div class="row gy-5">
        <div class="col-8">
          <input type="hidden" name="task-id" value="<?=$task_id?>"></input>
          <table class="table table-hover">
    
            <div class="pt-3">
              <div class="form-outline">
                <input id="input-title" class="form-control <?php /*if ($task['title'])*/ echo 'active';?>" wrap="off" rows="1" 
                style="resize: none; white-space:normal;" name="task-title"
                <?php if($task['title']) echo 'value="'.$task['title'].'"'; 
                else echo 'value="Задание '. ++$count_tasks .'."';?>>
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
                <option selected value = "0">Обычное</option>
                <option value = "1">Программирование</option>
              </select>
            </div>

            <div class="pt-3">
              <div class="form-outline">
                <textarea id="textArea-description" class="form-control <?php /*if ($task['description'])*/ echo 'active';?>" 
                rows="5" name="task-description" style="resize: none;"><?=$task['description']?></textarea>
                <label id="label-textArea-description" class="form-label" for="textArea-description">Описание задания</label>
                <div class="form-notch">
                  <div class="form-notch-leading" style="width: 9px;"></div>
                  <div class="form-notch-middle" style="width: 114.4px;"></div>
                  <div class="form-notch-trailing"></div>
                </div>
              </div>
              <span id="error-textArea-description" class="error-input" aria-live="polite"></span>
            </div>
				
            <div class="pt-3 d-flex" id="tools">
              
              <div class="form-outline col-5">
                  <textarea id="textArea-testCode" class="form-control" rows="5" name="full_text_test" style="resize: none;">
                  <?php if($task['type'] == 1) echo $test[0]['full_text'];?>
                </textarea>
                  <label class="form-label" for="textArea-testCode">Код теста</label>
                <div class="form-notch">
                  <div class="form-notch-leading" style="width: 9px;"></div>
                  <div class="form-notch-middle" style="width: 114.4px;"></div>
                  <div class="form-notch-trailing"></div>
                </div>
              </div>

              <div class="col-1"></div>

              <div class="form-outline col-6">
                <textarea id="textArea-checkCode" class="form-control" rows="5" name="full_text_test_of_test" style="resize: none;">
                  <?php if($task['type'] == 1) echo $test_of_test[0]['full_text'];?>
                </textarea>
                  <label class="form-label" for="textArea-checkCode">Код проверки</label>
                <div class="form-notch">
                  <div class="form-notch-leading" style="width: 9px;"></div>
                  <div class="form-notch-middle" style="width: 114.4px;"></div>
                  <div class="form-notch-trailing"></div>
                </div>
              </div>

            </div>
          </table>
          <button id="submit" type="submit" class="btn btn-outline-primary">Сохранить</button>
          <button type="button" class="btn btn-outline-primary" style="display: none;">Проверить сборку</button>
        </div>

        <div class="col-4">
        <div class="p-3 border bg-light" style="max-height: calc(100vh - 80px);">
          <!--<form id="form-choose-executors" class="p-2 border bg-light" name="form-taskEdit" 
          action="taskedit_action.php?page=<?=$page_id?>" method="post">-->
            <input type="hidden" name="task-id" value="<?=$task_id?>"></input>

            <div class="pt-1 pb-1">
              <label><i class="fas fa-users fa-lg"></i><small>&nbsp;&nbsp;НАЗНАЧИТЬ ИСПОЛНИТЕЛЕЙ</small></label>
            </div>

            <section class="w-100 py-2 d-flex justify-content-center">
              <div class="form-outline datetimepicker w-100" style="width: 22rem">
                <input id="datetimepickerExample" type="date" class="form-control active" name="finish-limit">
                <label for="datetimepickerExample" class="form-label" style="margin-left: 0px;">Срок выполения</label>
                <div class="form-notch">
                  <div class="form-notch-leading" style="width: 9px;"></div>
                  <div class="form-notch-middle" style="width: 114.4px;"></div>
                  <div class="form-notch-trailing"></div>
                </div>
              </div>
            </section>

            <?php
            $query = select_students_by_group_by_page($page_id);
            $result = pg_query($dbconnect, $query);
            $students = pg_fetch_all_assoc($result); ?>

            <section class="w-100 d-flex border" style="height: 50%;">
              <div class="w-100 h-100 d-flex" style="margin:10px; height: 100%; text-align: left;">
                <div id="accordion-students" class="accordion js-accordion" style="overflow-y: auto; height: 100%; width: 100%;">

                  <?php $now_group_id = -1;
                  if ($students){
                    foreach ($students as $key => $student) {
                      if($student['group_id'] != $now_group_id) {
                        if($key > 0) {?>
                          </div>
                        </div>
                        <?php }?>
                        <div class="accordion__item js-accordion-item">
                          <div class="accordion-header js-accordion-header">
                            <div class="form-check">
                              <input id="group-<?=$student['group_id']?>" class="accordion-input-item form-check-input" type="checkbox" 
                              value="g<?=$student['group_id']?>" id="flexCheck1" onclick="markStudentElements(<?=$student['group_id']?>)" 
                              name="checkboxStudents[]">
                              <label class="form-check-label" for="flexCheck1"><?=$student['group_name']?></label>
                            </div>
                          </div>
                          <div class="accordion-body js-accordion-body">
                      <?php }?>
                      <div class="accordion__item js-accordion-item">
                        <div class="form-check">
                          <input id="student-<?=$student['id']?>" class="accordion-input-item form-check-input" 
                          type="checkbox" value="s<?=$student['id']?>" id="" name="checkboxStudents[]">
                          <label class="form-check-label" for="flexCheck1"><?=$student['fi']?></label>
                        </div>
                      </div>
                      <?php $now_group_id = $student['group_id'];
                    }
                  } else {?>
                    <strong>СТУДЕНТЫ ОТСУТСТВУЮТ</strong>
                  <?php }?>

                </div>
              </div>
            </section>

            <div class="p-1 border bg-light">
              <div class="d-flex">
                <label class="label-theme btn btn-outline-primary py-2 px-4 me-2" checked>
                  <input type="radio" name="deligate" style="display: none;" value="deligate-by-individual">
                    <i class="fas fa-user fa-lg"></i> Назначить индивидуально
                </label>  
                <label class="label-theme btn btn-outline-primary py-2 px-4">
                  <input type="radio" name="deligate" style="display: none;" value="deligate-by-group">
                    <i class="fas fa-user fa-lg"></i> Назначить индивидуально
                </label>  
              </div>
            </div>

            <!--<div class="pt-1 pb-1">
              <button id="button-executor-by-individual" type="submit" class="btn btn-outline-primary">
                <i class="fas fa-user fa-lg" onclick="checkExecutorsChoose()"></i> Назначить индивидуально</button>
              <button id="button-executor-by-group" type="submit" class="btn btn-outline-primary" onclick="checkExecutorsChoose()">
                <i class="fas fa-users fa-lg"></i> Назначить группе</button>
            </div>-->
            <span id="error-choose-executor" class="error-input" aria-live="polite"></span>
          <!-- </form> -->

          <div class="p-1 border bg-light" >
            <div class="pt-1 pb-1">
              <button type="button" class="btn btn-outline-primary">
                <i class="fas fa-paperclip fa-lg"></i> Приложить файл</button>
            </div>
          </div>
                
          </div>
        </div>
      </div>
    </form>

  </div>
</main>
<!-- End your project here-->

    <!-- СКРИПТ "СОЗДАНИЯ ЗАДАНИЯ" -->
  <script type="text/javascript" src="js/taskedit.js"></script>

  </body>
</html>

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
    && (!isset($_GET['task_id']) || !is_numeric($_GET['task_id']))) {
	header('Location:mainpage.php');
	exit;
}


$isNewAssignment = false;
if (isset($_GET['assignment_id'])) {
  $isNewAssignment = false;
  echo("<script>var isNewAssignment=false</script>");
  $Assignment = new Assignment((int)$_GET['assignment_id']);
  $Task = new Task((int)getTaskByAssignment($Assignment->id));
} else {
  $isNewAssignment = true;
  echo("<script>var isNewAssignment=true;</script>");
  $Task = new Task((int)$_GET['task_id']);
  $Assignment = new Assignment($Task->id, 0);
}




$Page = new Page((int)getPageByTask($Task->id));


  show_head("Назначение задания", array('https://cdn.jsdelivr.net/npm/marked/marked.min.js'));

?>

<body>

  <?php 
  show_header($dbconnect, 'Редактор заданий', 
    array("Задания по дисциплине: " . $Page->disc_name  => 'preptasks.php?page='. $Page->id,
          "Редактор заданий" => $_SERVER['REQUEST_URI']),
    $User
  );
  ?>
  
  <main class="pt-2">
    <div class="container-fluid overflow-hidden">
      <div class="row gy-5">
        <div class="col-8">
          
          <div class="row ms-5 mt-5 mb-3">
            <h2 class="col-9 text-nowrap">
              <?php if ($Task->type == 1) {?>
                <i class="fas fa-code fa-lg"></i>
              <?php } else { ?>
                <i class="fas fa-file fa-lg" style="padding: 0px 5px 0px 5px;"></i>
              <?php } ?>
              <?=$Task->title?>
            </h2>
          </div>    
              
              
          <div class="pt-3 offset-1">
			
      
      <div class="d-flex justify-content-between">
        <?php if ($isNewAssignment) { ?>
          <h4> Новое назначение </h4>
        <?php } else {?>
          <h4> Текущее назначение: </h4>
        <?php } ?>



        <div class="d-flex">
          <div class="me-2 pe-2" style="border-right: 1px solid; border-color:var(--mdb-gray-400); cursor: help;">
            <button class="btn btn-outline-<?=!$Assignment->isCompleteable() ? 'primary' : 'light'?> px-3 me-1 btn-assignment-status"
            id="btn-assignment-status--1" onclick="ajaxChangeStatus(-1)" <?=$Assignment->isCompleteable() ? 'style="color: var(--mdb-gray-400);"':''?>
            data-title="<?=!$Assignment->isCompleteable() ? '' : 'Изменить статус назначения на:'?> <?=status_to_text(-1)?>">
              <?php getSVGByAssignmentStatus(-1);?>
            </button>
            <?php if($Assignment->isCompleteable()) {?>
              <button class="btn btn-outline-primary px-3 me-1 btn-assignment-status" 
              id="btn-assignment-status-<?=$Assignment->status?>"
              data-toggle="tooltip" data-placement="down" data-title="<?=status_to_text($Assignment->status)?>">
                <?php getSVGByAssignmentStatus($Assignment->status);?>
              </button>
            <?php } else {?>
              <button class="btn btn-outline-<?=$Assignment->isCompleteable() ? 'primary' : 'light'?> px-3 me-1 btn-assignment-status" 
              id="btn-assignment-status-0" onclick="ajaxChangeStatus(0)" style="color: var(--mdb-gray-400);"
              data-title="<?=$Assignment->isCompleteable() ? '' : 'Изменить статус назначения на:'?> <?=status_to_text(0)?>">
                <?php getSVGByAssignmentStatus(0);?>
              </button>
            <?php }?>
          </div>
          <div>
            <button id="btn-assignment-visibility-0" class="btn btn-outline-<?=$Assignment->visibility == 0 ? 'primary' : 'light'?> px-3 me-1 btn-assignment-visibility" 
            onclick="ajaxChangeVisibility(0)" <?=$Assignment->visibility == 0 ?  '': 'style="color: var(--mdb-gray-400);"'?>
            data-toggle="tooltip" data-placement="down" 
            data-title="<?=$Assignment->visibility == 0 ? '' : 'Изменить видимость назначения на:'?> '<?=strtoupper(visibility_to_text(0))?>'">
                <?php getSVGByAssignmentVisibility(0);?>
            </button>
            <button id="btn-assignment-visibility-2" class="btn btn-outline-<?=$Assignment->visibility == 2 ? 'primary' : 'light'?> px-3 me-1 btn-assignment-visibility" 
            onclick="ajaxChangeVisibility(2)" <?=$Assignment->visibility == 2 ?  '': 'style="color: var(--mdb-gray-400);"'?>
            data-toggle="tooltip" data-placement="down" 
            data-title="<?=$Assignment->visibility == 2 ? '' : 'Изменить видимость назначения на:'?> '<?=strtoupper(visibility_to_text(2))?>'">
              <?php getSVGByAssignmentVisibility(2);?>
            </button>
            <button id="btn-assignment-visibility-4" class="btn btn-outline-<?=$Assignment->visibility == 4 ? 'primary' : 'light'?> px-3 me-1 btn-assignment-visibility" 
            onclick="ajaxChangeVisibility(4)" <?=$Assignment->visibility == 4 ?  '': 'style="color: var(--mdb-gray-400);"'?>
            data-toggle="tooltip" data-placement="down" 
            data-title="<?=$Assignment->visibility == 4 ? '' : 'Изменить видимость назначения на:'?> '<?=strtoupper(visibility_to_text(4))?>'">
              <?php getSVGByAssignmentVisibility(4);?>
            </button>
          </div>
        </div>
      </div>
      
      <?php foreach ($Assignment->getStudents() as $Student) { ?>
        <div class="d-flex align-items-center">
          <span><?=$Student->getFI()?> 
            <?php if(checkPHPDateForDateFields(convert_timestamp_to_date($Assignment->finish_limit, "Y-m-d")) != "") 
              echo " (до $Assignment->finish_limit)";
            else 
              echo " (бессрочно)";?>
          </span>
        </div>
      <?php }
      ?>
      </br>


			<form id="checkparam" name="checkparam" class="" action="taskassign_action.php" method="POST" enctype="multipart/form-data">			
			  <input type="hidden" name="assignment_id" value="<?=$Assignment->id?>">
			  <input type="hidden" name="from" value="<?=$_SERVER['HTTP_REFERER']?>">

			  <h5><i class="fas fa-users fa-lg" aria-hidden="true"></i> Исполнители</h5>

        <section class="w-100 d-flex border mb-4" style="height: 50%;">
              <div class="w-100 h-100 d-flex" style="margin:10px; height: 100%; text-align: left;">
                <div id="main-accordion-students" class="accordion accordion-flush" style="overflow-y: auto; height: 100%; width: 100%;">

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
                    $key=0;
                    foreach($Page->getGroups() as $Group) { ?>
                      <div class="accordion-item">
                        <div id="accordion-gheader-<?=$Group->id?>" class="accordion-header">
                          <button class="accordion-button" type="button"
                          data-mdb-toggle="collapse" data-mdb-target="#accordion-collapse-<?=$key?>" aria-expanded="true"
                          aria-controls="accordion-collapse-<?=$key?>">
                          <h6>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" 
                            class="bi bi-people-fill p-0 h-100" viewBox="0 0 16 16"
                            style="vertical-align: top;">
                              <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7Zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm-5.784 6A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216ZM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"/>
                            </svg> 
                            <?=$Group->name?>
                          </h6>
                            <!-- <div class="form-check d-flex">
                              <span id="group-<?=$Group->id?>-stat" class="badge badge-primary align-self-center" style="color: black;">
                              <input id="group-<?=$Group->id?>" class="accordion-input-item form-check-input input-group" type="checkbox" 
                              value="g<?=$Group->id?>" id="flexCheck1" onclick="markStudentElements(<?=$Group->id?>)" 
                              name="checkboxStudents[]">
                                <?=$count_chosen_students?> / <?=$group_students_count?>
                              </span>
                              <label class="ms-1 form-check-label" for="flexCheck1" style="font-weight: bold;"><?=$Group->name?></label>
                            </div> -->
                          </button>
                        </div>
                        <div id="accordion-collapse-<?=$key?>" class="accordion-collapse collapse" aria-labelledby="accordion-gheader-<?=$key?>"
                        data-mdb-parent="#main-accordion-students">
                          <div class="accordion-body">
                            <div id="group-accordion-students" class="accordion accordion-flush">
                              <?php 
                              foreach($Group->getStudents() as $Student) {?>
                                <div id="item-from-group-<?=$Group->id?>" class="accordion-item">
                                  <div id="accordion-sheader-<?=$Student->id?>" class="accordion-header">
                                    <div class="d-flex justify-content-between" type="button">
                                      <div class="form-check ms-3">
                                        <input id="student-<?=$Student->id?>" class="accordion-input-item form-check-input input-student" 
                                        type="checkbox" value="<?=$Student->id?>" name="students[]" 
                                        <?php if($Assignment->getStudentById($Student->id) != null) echo 'checked';?>>
                                        <label class="form-check-label" for="flexCheck1"><?=$Student->getFI()?></label>
                                      </div>
                                    </div>
                                  </div>
                                </div>
                                <?php 
                                $key++;
                              } ?>
                              </div>
                            </div>
                          </div>
                        </div>
                    <?php } 
                  //}
                  ?>

                  
                </div>
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
                  <div class="form-outline datetimepicker w-100 me-3">
                    <input type="date" class="form-control active" name="fromtime" id="fromtime" style="margin-bottom: 0px;" 
                    value="<?=checkPHPDateForDateFields(convert_timestamp_to_date($Assignment->start_limit, "Y-m-d"))?>">
				    <label for="fromtime" class="form-label" style="margin-left: 0px;">Начало</label>
				    <div class="form-notch">
					  <div class="form-notch-leading" style="width: 9px;"></div>
					  <div class="form-notch-middle" style="width: 54.4px;"></div>
					  <div class="form-notch-trailing"></div>
				    </div>
                  </div>
                  <div class="form-outline datetimepicker w-100">
                    <input type="date" class="form-control active" name="tilltime" id="tilltime" style="margin-bottom: 0px;" 
                    value="<?=checkPHPDateForDateFields(convert_timestamp_to_date($Assignment->finish_limit, "Y-m-d"))?>">
				    <label for="tilltime" class="form-label" style="margin-left: 0px;">Окончание</label>
				    <div class="form-notch">
					  <div class="form-notch-leading" style="width: 9px;"></div>
					  <div class="form-notch-middle" style="width: 74.4px;"></div>
					  <div class="form-notch-trailing"></div>
				    </div>
                  </div>
                </section>
              </div>

			  <h5><i class="fa fa-ticket" aria-hidden="true"></i> Вариант</h5>
			  <div class="ps-5 mb-4">
			    <input id="variant" name="variant" class="w-100" value="<?=$Assignment->variant_number?>" wrap="off" rows="1">
			  </div>

			  <h5><i class="fa fa-check-circle fa-lg" aria-hidden="true"></i> Параметры проверки</h5>

			  
			<?php
			  $checks = $Assignment->checks;
			  if ($checks == null)
				$checks = $Task->checks;
			  if ($checks == null)
			    $checks = '{"tools":{"build":{"enabled":true,"show_to_student":false,"language":"C++","check":{"autoreject":true}},"valgrind":{"enabled":"false","show_to_student":"false","bin":"valgrind","arguments":"","compiler":"gcc","checks":[{"check":"errors","enabled":"true","limit":"0","autoreject":"false"},{"check":"leaks","enabled":"true","limit":"0","autoreject":"false"}]},"cppcheck":{"enabled":"false","show_to_student":"false","bin":"cppcheck","arguments":"","checks":[{"check":"error","enabled":"true","limit":"0","autoreject":"false"},{"check":"warning","enabled":"true","limit":"3","autoreject":"false"},{"check":"style","enabled":"true","limit":"3","autoreject":"false"},{"check":"performance","enabled":"true","limit":"2","autoreject":"false"},{"check":"portability","enabled":"true","limit":"0","autoreject":"false"},{"check":"information","enabled":"true","limit":"0","autoreject":"false"},{"check":"unusedFunction","enabled":"true","limit":"0","autoreject":"false"},{"check":"missingInclude","enabled":"true","limit":"0","autoreject":"false"}]},"clang-format":{"enabled":"false","show_to_student":"false","bin":"clang-format","arguments":"","check":{"level":"strict","file":"","limit":"5","autoreject":"true"}},"copydetect":{"enabled":"false","show_to_student":"false","bin":"copydetect","arguments":"","check":{"type":"with_all","limit":"80","autoreject":"false"}},"autotests": {"enabled": false,"show_to_student": false,"test_path": "accel_autotest.cpp","check": {"limit": 0,"autoreject": true}}}}';
		      
			  $checks = json_decode($checks, true);
			  
			  function checked($src)
			  { 
				return ($src == "true") ?"checked" :"";
			  }
			
			  function selected($src, $option)
			  { 
				return ($src == $option) ?"selected" :"";
			  }
			
			  function add_check_param($group, $param, $caption, $checks)
			  {
				$enabled = "true";
				$limit = "0";
				$reject = "false";
				
				if ($group == 'clang') {
				  $enabled = @$checks['tools']['clang-format']['enabled'];
				  $limit = @$checks['tools']['clang-format']['check']['limit'];
				  $reject = @$checks['tools']['clang-format']['check']['autoreject'];
				} else if ($group == 'plug') {
					$enabled = @$checks['tools']['copydetect']['enabled'];
					$limit = @$checks['tools']['copydetect']['check']['limit'];
					$reject = @$checks['tools']['copydetect']['check']['autoreject'];
				} else if ($group == 'test') {
					$enabled = @$checks['tools']['autotests']['enabled'];
					$limit = @$checks['tools']['autotests']['check']['limit'];
					$reject = @$checks['tools']['autotests']['check']['autoreject'];
				} else {
				  $arr = @$checks['tools'][$group]['checks'];
				  foreach($arr as $a) {
					if (@$a['check'] == $param) {
						$enabled = $a['enabled'];
						$limit = $a['limit'];
						$reject = $a['autoreject'];
					}
				  }
				}
			  
				return  '<div><input id="'.$group.'_'.$param.'" name="'.$group.'_'.$param.'" '.checked($enabled).
						' class="accordion-input-item form-check-input" type="checkbox" value="true">'.
						'<label class="form-check-label" for="'.$group.'_'.$param.'" style="width:20%;">'.$caption.'</label>'.
						'<label class="form-check-label me-3" for="'.$group.'_'.$param.'_limit">порог</label>'.
						'<input id="'.$group.'_'.$param.'_limit" name="'.$group.'_'.$param.'_limit" value="'.$limit.
						'" class="accordion-input-item mb-2" wrap="off" rows="1" style="width:10%;">'.
						'<input id="'.$group.'_'.$param.'_reject" name="'.$group.'_'.$param.'_reject" '.checked($reject).
						' class="accordion-input-item form-check-input ms-5" type="checkbox" value="true" style="float: none; margin-left:56px!important;margin-top:6px;">'.
						'<label class="form-check-label" for="'.$group.'_'.$param.'_reject" style="width:40%;">автоматически отклонять при нарушении</label></div>';
			  }
			  
			  $accord = array(array('header' => '<b>Сборка</b>',
			  
									'label'	 => '<input id="build_enabled" name="build_enabled" '.checked(@$checks['tools']['build']['enabled']).
												' class="accordion-input-item form-check-input" type="checkbox" value="true">'.
												'<label class="form-check-label" for="build_enabled" style="color:#4f4f4f;">выполнять сборку</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.
												'<input id="build_show" name="build_show" '.checked(@$checks['tools']['build']['show_to_student']).
												' class="accordion-input-item form-check-input ms-5" type="checkbox" value="true">'.
												'<label class="form-check-label" for="build_show" style="color:#4f4f4f;">отображать студенту</label>',
												
									'body'   => //'<div><label class="form-check-label" for="valgrind_arg" style="width:20%;">аргументы</label>'.
												//'<input id="valgrind_arg" name="valgrind_arg" value="'.@$checks['tools']['valgrind']['arguments'].
												//'" style="width:50%;" class="accordion-input-item mb-2" wrap="off" rows="1"></div>'.
												'<div><label class="form-check-label" for="build_language" style="width:20%;">Язык</label>'.
												'<select id="build_language" name="build_language"'.
												' class="form-select mb-2" aria-label=".form-select" style="width:50%; display: inline-block;">'.
												'  <option value="C" '.selected(@$checks['tools']['build']['language'], 'C').'>C</option>'.
												'  <option value="C++" '.selected(@$checks['tools']['build']['language'], 'C++').'>C++</option>'.
												'  <option value="Python" '.selected(@$checks['tools']['build']['language'], 'Python').'>Python</option>'.
												'</select></div>'.
												'<div><input id="build_autoreject" name="build_autoreject" '.checked(@$checks['tools']['build']['check']['autoreject']).
												' class="accordion-input-item form-check-input" type="checkbox" value="true">'.
												'<label class="form-check-label" for="build_autoreject" style="color:#4f4f4f;">автоматически отклонять при нарушении</label></div>'
									),
							  array('header' => '<b>Valgrind</b>',
			  
									'label'	 => '<input id="valgrind_enabled" name="valgrind_enabled" '.checked(@$checks['tools']['valgrind']['enabled']).
												' class="accordion-input-item form-check-input" type="checkbox" value="true">'.
												'<label class="form-check-label" for="valgrind_enabled" style="color:#4f4f4f;">выполнять проверки</label>'.
												'<input id="valgrind_show" name="valgrind_show" '.checked(@$checks['tools']['valgrind']['show_to_student']).
												' class="accordion-input-item form-check-input ms-5" type="checkbox" value="true">'.
												'<label class="form-check-label" for="valgrind_show" style="color:#4f4f4f;">отображать студенту</label>',
												
									'body'   => '<div><label class="form-check-label" for="valgrind_arg" style="width:20%;">аргументы</label>'.
												'<input id="valgrind_arg" name="valgrind_arg" value="'.@$checks['tools']['valgrind']['arguments'].
												'" style="width:50%;" class="accordion-input-item mb-2" wrap="off" rows="1"></div>'.
												'<div><label class="form-check-label" for="valgrind_compiler" style="width:20%;">компилятор</label>'.
												'<select id="valgrind_compiler" name="valgrind_compiler"'.
												' class="form-select mb-2" aria-label=".form-select" style="width:50%; display: inline-block;">'.
												'  <option value="gcc" '.selected(@$checks['tools']['valgrind']['compiler'], 'gcc').'>gcc</option>'.
												'  <option value="g++" '.selected(@$checks['tools']['valgrind']['compiler'], 'g++').'>g++</option>'.
												'</select></div>'.
												add_check_param('valgrind', 'errors', 'ошибки памяти', $checks).
												add_check_param('valgrind', 'leaks', 'утечки памяти', $checks)
									),
							  array('header' => '<b>CppCheck</b>',
							  
									'label'	 => '<input id="cppcheck_enabled" name="cppcheck_enabled" '.checked(@$checks['tools']['cppcheck']['enabled']).
												' class="accordion-input-item form-check-input" type="checkbox" value="true">'.
												'<label class="form-check-label" for="cppcheck_enabled" style="color:#4f4f4f;">выполнять проверки</label>'.
												'<input id="cppcheck_show" name="cppcheck_show" '.checked(@$checks['tools']['cppcheck']['show_to_student']).
												' class="accordion-input-item form-check-input ms-5" type="checkbox" value="true">'.
												'<label class="form-check-label" for="cppcheck_show" style="color:#4f4f4f;">отображать студенту</label>',												
												
									'body'   => '<div><label class="form-check-label" for="cppcheck_arg" style="width:20%;">аргументы</label>'.
												'<input id="cppcheck_arg" name="cppcheck_arg" value="'.@$checks['tools']['cppcheck']['arguments'].
												'" class="accordion-input-item mb-2" wrap="off" rows="1" style="width:50%;"></div>'.
												
												add_check_param('cppcheck', 'error', 'error', $checks).
												add_check_param('cppcheck', 'warning', 'warnings', $checks).
												add_check_param('cppcheck', 'style', 'style', $checks).
												add_check_param('cppcheck', 'performance', 'performance', $checks).
												add_check_param('cppcheck', 'portability', 'portability', $checks).
												add_check_param('cppcheck', 'information', 'information', $checks).
												add_check_param('cppcheck', 'unused', 'unused functions', $checks).
												add_check_param('cppcheck', 'include', 'missing include', $checks)
									),
							  array('header' => '<b>Clang-format</b>',
									'label'	 => '<input id="clang_enabled" name="clang_enabled" '.checked(@$checks['tools']['clang-format']['enabled']).
												' class="accordion-input-item form-check-input" type="checkbox" value="true">'.
												'<label class="form-check-label" for="clang_enabled" style="color:#4f4f4f;">выполнять проверки</label>'.
												'<input id="clang_show" name="clang_show" '.checked(@$checks['tools']['clang-format']['show_to_student']).
												' class="accordion-input-item form-check-input ms-5" type="checkbox" value="true">'.
												'<label class="form-check-label" for="clang_show" style="color:#4f4f4f;">отображать студенту</label>',
												
									'body'   => '<div><label class="form-check-label" for="clang_arg" style="width:20%;">аргументы</label>'.
												'<input id="clang_arg" name="clang_arg" value="'.@$checks['tools']['clang-format']['arguments'].
												'" class="accordion-input-item mb-2" wrap="off" rows="1" style="width:50%;"></div>'.
												'<div><label class="form-check-label" for="clang_compiler" style="width:20%;">соответствие</label>'.
												'<select id="clang_config" name="clang-config" class="form-select mb-2" aria-label=".form-select" style="width:50%; display: inline-block;">'.
												'  <option value="strict" '.selected(@$checks['tools']['clang-format']['level'], 'strict').'>strict - need-to-comment</option>'.
												'  <option value="less" '.selected(@$checks['tools']['clang-format']['level'], 'less').'>less - need-to-comment</option>'.
												'  <option value="minimal" '.selected(@$checks['tools']['clang-format']['level'], 'minimal').'>minimal - need-to-comment</option>'.
												'  <option value="so on" '.selected(@$checks['tools']['clang-format']['level'], 'so on').'>so on - need-to-complete</option>'.
												'  <option value="specific" '.selected(@$checks['tools']['clang-format']['level'], 'specific').'>specific - укажите свой файл с правилами оформления</option>'.
												'</select></div>'.
												'<div><label class="form-check-label mb-2" for="clang_file" style="width:20%;">файл с правилами</label>'.
												'<input id="clang_file" name="clang_file" value="'.@$checks['tools']['clang-format']['file'].
												'" class="accordion-input-item mb-2" wrap="off" rows="1" style="width:50%;"></div>'.
												add_check_param('clang', 'errors', 'нарушения', $checks)
									),
							  array('header' => '<b>Автотесты</b>',
									'label'	 => '<input id="test_enabled" name="test_enabled" '.checked(@$checks['tools']['autotests']['enabled']).
												' class="accordion-input-item form-check-input" type="checkbox" value="true">'.
												'<label class="form-check-label" for="test_enabled" style="color:#4f4f4f;">выполнять проверки</label>'.
												'<input id="test_show" name="test_show" '.checked(@$checks['tools']['autotests']['show_to_student']).
												' class="accordion-input-item form-check-input ms-5" type="checkbox" value="true" >'.
												'<label class="form-check-label" for="test_show" style="color:#4f4f4f;">отображать студенту</label>',
												
									'body'   => //'<div><label class="form-check-label" for="test_lang" style="width:20%;">сравнивать</label>'.
												//'<select id="test_lang" class="form-select mb-2" aria-label=".form-select" name="test_lang" style="width:50%; display: inline-block;">'.
												//'  <option value="С" '.selected(@$checks['tools']['autotests']['language'], 'C').'>C</option>'.
												//'  <option value="С++" '.selected(@$checks['tools']['autotests']['language'], 'C++').'>C++</option>'.
												//'  <option value="Python" '.selected(@$checks['tools']['autotests']['language'], 'Python').'>Python</option>'.
												//'</select></div>'.
												add_check_param('test', 'check', 'проверять', $checks)
										),
							  array('header' => '<b>Антиплагиат</b>',
									'label'	 => '<input id="plug_enabled" name="plug_enabled" '.checked(@$checks['tools']['copydetect']['enabled']).
												' class="accordion-input-item form-check-input" type="checkbox" value="true">'.
												'<label class="form-check-label" for="plug_enabled" style="color:#4f4f4f;">выполнять проверки</label>'.
												'<input id="plug_show" name="plug_show" '.checked(@$checks['tools']['copydetect']['show_to_student']).
												' class="accordion-input-item form-check-input ms-5" type="checkbox" value="true" >'.
												'<label class="form-check-label" for="plug_show" style="color:#4f4f4f;">отображать студенту</label>',
												
									'body'   => '<div><label class="form-check-label" for="plug_arg" style="width:20%;">аргументы</label>'.
												'<input id="plug_arg" name="plug_arg" value="'.@$checks['tools']['copydetect']['arguments'].
												'" class="accordion-input-item mb-2" wrap="off" rows="1" style="width:50%;"></div>'.
												'<div><label class="form-check-label" for="plug_config" style="width:20%;">сравнивать</label>'.
												'<select id="plug_config" class="form-select mb-2" aria-label=".form-select" name="plug_config" style="width:50%; display: inline-block;">'.
												'  <option value="with_all" '.selected(@$checks['tools']['copydetect']['with_all'], 'gcc').'>со всеми ранее сданными работами</option>'.
												//'  <option value="group" '.selected(@$checks['tools']['copydetect']['group'], 'gcc').'>с работами студентов своей группы</option>'.
												'</select></div>'.
												add_check_param('plug', 'check', 'проверять', $checks)
									)
												
							 ); 
							 
			  
			  show_accordion('checks', $accord, "310px");
			?>
			  <button id="checks-save" type="submit" class="btn btn-outline-primary mt-5" name="action" value="save" style="">Сохранить</button>
			</form>
          </div>
        </div>

        <div class="col-4">
          <div class="p-3 border ">

			<h6>Задание</h6>
			<div id="Task" class="tabcontent border bg-light p-2 small" style="overflow-y: auto; width: 100%; height: 100%;">
			  <p id="TaskDescr"><?=$Task->description?></p>
			  <script>
				document.getElementById('TaskDescr').innerHTML = marked.parse(document.getElementById('TaskDescr').innerHTML);
			  </script>
			  <p>
				<b>Файлы, приложенные к заданию:</b> 
				<br>
				<?php 
				  // $task_files = getTaskFiles($dbconnect, $Task->id);
				  showFiles($Task->getFiles()); 
				?>
			  </p>
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

    // BUG: Не срабатывает, если не сделать на странице никакого действия, даже прокрутки
    if (isNewAssignment) {
      // Автоматически удаляем Assignment
      window.onbeforeunload = function(event) {
        deleteAssignment();
        return "";
      };
    }

    $('#checks-save').click(function () {
      window.onbeforeunload = null;
    });
    

    async function deleteAssignment() {
      const res = await ajaxChangeVisibility('delete');
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

      formData.append('assignment_id', <?=$Assignment->id?>);
      formData.append('changeVisibility', new_status);

      $.ajax({
        type: "POST",
        url: 'taskassign_action.php#content',
        cache: false,
        contentType: false,
        processData: false,
        data: formData,
        dataType : 'html',
        success: function(response) {
        },
        complete: function() {
          if(new_status != "delete") {
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

      formData.append('assignment_id', <?=$Assignment->id?>);
      formData.append('changeStatus', new_status);

      $.ajax({
        type: "POST",
        url: 'taskassign_action.php#content',
        cache: false,
        contentType: false,
        processData: false,
        data: formData,
        dataType : 'html',
        success: function(response) {
        },
        complete: function() {
          if(new_status != "delete") {
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

  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" 
    integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
  
</body>
</html>
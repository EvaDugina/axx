<!DOCTYPE html>
<html lang="en">

<?php
require_once("common.php");
require_once("dbqueries.php");
require_once("utilities.php");

// защита от случайного перехода
$au = new auth_ssh();
if (!$au->isAdmin() && !$au->isTeacher()){
	$au->logout();
	header('Location:login.php');
}

// Обработка некорректного перехода между страницами
if ((!isset($_GET['assignment_id']) || !is_numeric($_GET['assignment_id'])) 
    && (!isset($_GET['task_id']) || !is_numeric($_GET['task_id']))) {
	header('Location:mainpage.php');
	exit;
}

// получение параметров запроса
$page_id = 0;
$timetill = "";
$timefrom = "";
$variant = "";

$task_id = 0;
if (isset($_GET['task_id']))
  $task_id = $_GET['task_id'];

$assignment_id = 0;
$aname = "Новое задание";
if (isset($_GET['assignment_id']))
{
  $assignment_id = $_GET['assignment_id'];
  $result = pg_query($dbconnect, "select ax_assignment.id aid, ax_task.id tid, ax_assignment.checks achecks, ax_task.checks tchecks, ".
								 " to_char(ax_assignment.start_limit, 'YYYY-MM-DD') tss, to_char(ax_assignment.finish_limit, 'YYYY-MM-DD') tsf, * ".
								 " from ax_assignment inner join ax_task on ax_assignment.task_id = ax_task.id where ax_assignment.id = ".$assignment_id);
  $row = pg_fetch_assoc($result);
  if (!$result || pg_num_rows($result) < 1 || !$row)  {
	http_response_code(400);
    echo 'Неверный запрос';
    exit;
  }
  
  $aname = $row['title'];
  $ttype = $row['type'];
  
  if ($task_id == 0)
	  $task_id = $row['tid'];
  
  $page_id = $row['page_id'];
  $timefrom = $row['tss'];
  $timetill = $row['tsf'];
  $variant = $row['variant_comment'];
}

  show_head("Назначение задания", array('https://cdn.jsdelivr.net/npm/marked/marked.min.js'));
  show_header($dbconnect, 'Назначение задания');


/*******
$query = select_discipline_page($page_id);
$result = pg_query($dbconnect, $query);
$row = [];
if (!$result || pg_num_rows($result) < 1) {
  echo 'Неверно указана дисциплина';
  http_response_code(400);
  exit;
} else {
  $row = pg_fetch_assoc($result);
  show_head("Задания по дисциплине: " . $row['disc_name']);
  show_header($dbconnect, 'Задания по дисциплине', array("Задания по дисциплине: " . $row['disc_name']  => $_SERVER['REQUEST_URI']));
} 
*******/
?>

<body>
  <main class="pt-2">
    <div class="container-fluid overflow-hidden">
      <div class="row gy-5">
        <div class="col-8">
          <div class="pt-3">

            <div class="row">
              <h2 class="col-9 text-nowrap">
				<?php if ($ttype == 1) {?>
				  <i class="fas fa-code fa-lg"></i>
				<?php } else { ?>
				  <i class="fas fa-file fa-lg" style="padding: 0px 5px 0px 5px;"></i>
				<?php } ?>

			    <?=$aname?>
			  </h2>
<!--
              <div class="col-3">
                <button type="submit" class="btn btn-outline-primary px-3" style="display: inline; float: right;" 
                onclick="window.location='taskedit.php?page=<?=$page_id?>';">
                  <i class="fas fa-plus-square fa-lg"></i> Новое задание
                </button>
              </div>
-->

            </div>    

            <?php
				$query = "SELECT students.id as sid, students.middle_name || ' ' || students.first_name fio, ax_task.id as tid,".
							" ax_assignment.id aid, to_char(ax_assignment.finish_limit, 'DD-MM-YYYY HH24:MI:SS') ts ".
							" FROM ax_task INNER JOIN ax_assignment ON ax_task.id = ax_assignment.task_id INNER JOIN ax_assignment_student ON ax_assignment.id = ax_assignment_student.assignment_id ".
							" INNER JOIN students ON students.id = ax_assignment_student.student_user_id ".
							" WHERE ax_assignment.id = ".$assignment_id;
		
				$result2 = pg_query($dbconnect, $query);
				if (!$result2)
				{
					echo 'Ошибка получения данных о назначении';
					exit;
				}

				$studids = array();
				$studlist = "";
				$adate = "";
				while ($student_task = pg_fetch_assoc($result2)) { 
					if ($studlist == "") {
						$studlist = $student_task['fio'];
						$adate = $student_task['ts'];
					} else {
						$studlist = $studlist.', '.$student_task['fio'];
					}
					array_push($studids, $student_task['sid']);
                } 
				echo $studlist." (до ".$adate.")</br></br>";
			?>

			<form id="checkparam" name="checkparam" action="taskassign_action.php" method="POST" enctype="multipart/form-data">			
			  <input type="hidden" name="assignment_id" value="<?=$assignment_id?>">
			  <input type="hidden" name="from" value="<?=$_SERVER['HTTP_REFERER']?>">

			  <h5><i class="fas fa-users fa-lg" aria-hidden="true"></i> Исполнители</h5>
              <div class="ps-5 pb-3">
				<section class="w-100 d-flex border">
                  <div class="w-100 h-100 d-flex" style="margin:10px; height:250px; text-align: left;">
                    <div id="demo-example-1" style="overflow-y: auto; height:250px; width: 100%;">
            <?php
                      $query = select_page_students($page_id);
                      $resultP = pg_query($dbconnect, $query);

                      while($rowP = pg_fetch_assoc($resultP)) {
                        echo '<div class="form-check">';
                        echo '  <input class="form-check-input" type="checkbox" name="students[]" value="'.$rowP['id'].'" id="flexCheck'.$rowP['id'].'" '.(in_array($rowP['id'], $studids) ?"checked" :"").'>';
                        echo '  <label class="form-check-label" for="flexCheck'.$rowP['id'].'">'.$rowP['fio'].'</label>';
                        echo '</div>';
                      }
            ?>
                    </div>
                  </div>
				</section>
			  </div>

			  <h5><i class="fas fa-calendar fa-lg" aria-hidden="true"></i> Сроки выполения</h5>
			  <div class="ps-5 pb-3">
                <section class="w-100 py-2 d-flex justify-content-center">
                  <div class="form-outline datetimepicker w-100 me-3">
                    <input type="date" class="form-control active" name="fromtime" id="fromtime" style="margin-bottom: 0px;" value="<?=$timefrom?>">
				    <label for="fromtime" class="form-label" style="margin-left: 0px;">Начало</label>
				    <div class="form-notch">
					  <div class="form-notch-leading" style="width: 9px;"></div>
					  <div class="form-notch-middle" style="width: 54.4px;"></div>
					  <div class="form-notch-trailing"></div>
				    </div>
                  </div>
                  <div class="form-outline datetimepicker w-100">
                    <input type="date" class="form-control active" name="tilltime" id="tilltime" style="margin-bottom: 0px;" value="<?=$timetill?>">
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
			  <div class="ps-5 pb-3">
			    <input id="variant" name="variant" class="w-100" value="<?=$variant?>" wrap="off" rows="1">
			  </div>

			  <h5><i class="fa fa-check-circle fa-lg" aria-hidden="true"></i> Параметры проверки</h5>

			  
			<?php
			  $checks = $row['achecks'];
			  if ($checks == null)
				$checks = $row['tchecks'];
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
												' class="accordion-input-item form-check-input ms-5" type="checkbox" value="false">'.
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
			  <button id="checks-save" type="submit" class="btn btn-outline-primary mt-3" name="action" value="save" style="">Сохранить</button>
			</form>
          </div>
        </div>

        <div class="col-4">
          <div class="p-3 border ">

			<h6>Задание</h6>
			<div id="Task" class="tabcontent border bg-light p-2 small" style="overflow: auto; width: 100%; height: 100%;">
			  <p id="TaskDescr"><?=$row['description']?></p>
			  <script>
				document.getElementById('TaskDescr').innerHTML = marked.parse(document.getElementById('TaskDescr').innerHTML);
			  </script>
			  <p>
			  <?php
				$task_files = getTaskFiles($dbconnect, $task_id);
				if ($task_files)
				{
				  echo '<b>Файлы, приложенные к заданию:</b><br>';
				  show_task_files($task_files);
				}
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


  </script>
  
</body>
</html>
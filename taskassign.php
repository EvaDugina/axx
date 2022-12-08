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
$task_id = 0;
if (isset($_GET['task_id']))
  $task_id = $_GET['task_id'];

$assignment_id = 0;
$aname = "Новое задание";
if (isset($_GET['assignment_id']))
{
  $assignment_id = $_GET['assignment_id'];
  $result = pg_query($dbconnect, "select ax_assignment.id aid, ax_task.id tid, * from ax_assignment inner join ax_task on ax_assignment.task_id = ax_task.id where ax_assignment.id = ".$assignment_id);
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

				$studlist = "";
				$adate = "";
				while ($student_task = pg_fetch_assoc($result2)) { 
					if ($studlist == "") {
						$studlist = $student_task['fio'];
						$adate = $student_task['ts'];
					} else {
						$studlist = $studlist.', '.$student_task['fio'];
					}
                } 
				echo $studlist." (до ".$adate.")</br></br>";
			?>

			<h6>Параметры проверки</h6>
			
			<?php
			
			  $accord = array(array('header' => 'Общие параметры',
									'body'   => '<div><input id="common_enabled" class="accordion-input-item form-check-input" type="checkbox" value="1" name="common_enabled" checked>'.
												'<label class="form-check-label" for="common_enabled">выполнять проверки</label></div>'.
												'<div><input id="common_show" class="accordion-input-item form-check-input" type="checkbox" value="1" name="common_show" checked>'.
												'<label class="form-check-label" for="common_show">отображать результаты студенту</label></div>'
												),
							  array('header' => 'Valgrind',
									'body'   => '<div><label class="form-check-label" for="valgrind_bin" style="width:20%;">команда</label>'.
												'<input id="valgrind_bin" class="accordion-input-item mb-2" wrap="off" rows="1" name="valgrind_bin" value="valgrind" style="width:50%;"></div>'.
												'<div><label class="form-check-label" for="valgrind_compiler" style="width:20%;">компилятор</label>'.
												'<input id="valgrind_bin" class="accordion-input-item mb-2" wrap="off" rows="1" name="valgrind_compiler" value="gcc" style="width:50%;"></div>'.
												'<div><input id="valgrind_errors" class="accordion-input-item form-check-input" type="checkbox" value="1" name="valgrind_errors" checked>'.
												'<label class="form-check-label" for="valgrind_errors" style="width:20%;">ошибки памяти</label>'.
												'<label class="form-check-label me-3" for="valgrind_error_limit">порог</label>'.
												'<input id="valgrind_error_limit" class="accordion-input-item mb-2" wrap="off" rows="1" name="valgrind_error_limit" value="0" style="width:10%;"></div>'.
												'<div><input id="valgrind_leaks" class="accordion-input-item form-check-input" type="checkbox" value="1" name="valgrind_leaks" checked>'.
												'<label class="form-check-label" for="valgrind_leaks" style="width:20%;">утечки памяти</label>'.
												'<label class="form-check-label me-3" for="valgrind_leak_limit">порог</label>'.
												'<input id="valgrind_leak_limit" class="accordion-input-item mb-2" wrap="off" rows="1" name="valgrind_leak_limit" value="0" style="width:10%;"></div>'
												),
							  array('header' => 'CppCheck',
									'body'   => '<div><label class="form-check-label" for="cppcheck_bin" style="width:20%;">команда</label>'.
												'<input id="cppcheck_bin" class="accordion-input-item mb-2" wrap="off" rows="1" name="cppcheck_bin" value="cppcheck" style="width:50%;"></div>'.
												
												'<div><input id="cppcheck_errors" class="accordion-input-item form-check-input" type="checkbox" value="1" name="cppcheck_errors" checked>'.
												'<label class="form-check-label" for="cppcheck_errors" style="width:20%;">errors</label>'.
												'<label class="form-check-label me-3" for="cppcheck_error_limit">порог</label>'.
												'<input id="cppcheck_error_limit" class="accordion-input-item mb-2" wrap="off" rows="1" name="cppcheck_error_limit" value="0" style="width:10%;"></div>'.
												
												'<div><input id="cppcheck_warnings" class="accordion-input-item form-check-input" type="checkbox" value="1" name="cppcheck_warnings" checked>'.
												'<label class="form-check-label" for="cppcheck_warnings" style="width:20%;">warnings</label>'.
												'<label class="form-check-label me-3" for="cppcheck_warning_limit">порог</label>'.
												'<input id="cppcheck_warning_limit" class="accordion-input-item mb-2" wrap="off" rows="1" name="cppcheck_warning_limit" value="0" style="width:10%;"></div>'.
												
												'<div><input id="cppcheck_style" class="accordion-input-item form-check-input" type="checkbox" value="1" name="cppcheck_style" checked>'.
												'<label class="form-check-label" for="cppcheck_style" style="width:20%;">style</label>'.
												'<label class="form-check-label me-3" for="cppcheck_style_limit">порог</label>'.
												'<input id="cppcheck_style_limit" class="accordion-input-item mb-2" wrap="off" rows="1" name="cppcheck_style_limit" value="0" style="width:10%;"></div>'.
												
												'<div><input id="cppcheck_performance" class="accordion-input-item form-check-input" type="checkbox" value="1" name="cppcheck_performance" checked>'.
												'<label class="form-check-label" for="cppcheck_performance" style="width:20%;">performance</label>'.
												'<label class="form-check-label me-3" for="cppcheck_performance_limit">порог</label>'.
												'<input id="cppcheck_leak_limit" class="accordion-input-item mb-2" wrap="off" rows="1" name="cppcheck_leak_limit" value="0" style="width:10%;"></div>'.
												
												'<div><input id="cppcheck_portability" class="accordion-input-item form-check-input" type="checkbox" value="1" name="cppcheck_portability" checked>'.
												'<label class="form-check-label" for="cppcheck_portability" style="width:20%;">portability</label>'.
												'<label class="form-check-label me-3" for="cppcheck_portability_limit">порог</label>'.
												'<input id="cppcheck_portability_limit" class="accordion-input-item mb-2" wrap="off" rows="1" name="cppcheck_portability_limit" value="0" style="width:10%;"></div>'.
												
												'<div><input id="cppcheck_info" class="accordion-input-item form-check-input" type="checkbox" value="1" name="cppcheck_info" checked>'.
												'<label class="form-check-label" for="cppcheck_info" style="width:20%;">information</label>'.
												'<label class="form-check-label me-3" for="cppcheck_info_limit">порог</label>'.
												'<input id="cppcheck_info_limit" class="accordion-input-item mb-2" wrap="off" rows="1" name="cppcheck_info_limit" value="0" style="width:10%;"></div>'.
												
												'<div><input id="cppcheck_unused" class="accordion-input-item form-check-input" type="checkbox" value="1" name="cppcheck_unused" checked>'.
												'<label class="form-check-label" for="cppcheck_unused" style="width:20%;">unused functions</label>'.
												'<label class="form-check-label me-3" for="cppcheck_unused_limit">порог</label>'.
												'<input id="cppcheck_include_limit" class="accordion-input-item mb-2" wrap="off" rows="1" name="cppcheck_include_limit" value="0" style="width:10%;"></div>'.
												
												'<div><input id="cppcheck_include" class="accordion-input-item form-check-input" type="checkbox" value="1" name="cppcheck_include" checked>'.
												'<label class="form-check-label" for="cppcheck_include" style="width:20%;">missing include</label>'.
												'<label class="form-check-label me-3" for="cppcheck_include_limit">порог</label>'.
												'<input id="cppcheck_include_limit" class="accordion-input-item mb-2" wrap="off" rows="1" name="cppcheck_include_limit" value="0" style="width:10%;"></div>'
												),
							  array('header' => 'Clang-format',
									'body'   => '<div><label class="form-check-label" for="clang_bin" style="width:20%;">команда</label>'.
												'<input id="clang_bin" class="accordion-input-item mb-2" wrap="off" rows="1" name="clang_bin" value="clang-format" style="width:50%;"></div>'.
												'<div><label class="form-check-label" for="clang_compiler" style="width:20%;">соответствие</label>'.
												'<select id="clang_config" class="form-select mb-2" aria-label=".form-select" name="clang-config" style="width:50%; display: inline-block;">'.
												'  <option value="strict" selected>strict - can be diffrent checks, such as strict, less, minimal and so on</option>'.
												'</select></div>'.
												'<div><label class="form-check-label mb-2" for="clang_file" style="width:20%;">правила оформления</label>'.
												'<select id="clang_file" class="form-select mb-2" aria-label=".form-select" name="clang-file" style="width:50%; display: inline-block;">'.
												'  <option value="1" selected>.clang-format</option>'.
												'</select></div>'.
												'<div><input id="clang_errors" class="accordion-input-item form-check-input mb-2" type="checkbox" value="1" name="clang_errors" checked>'.
												'<label class="form-check-label" for="clang_errors" style="width:20%;">нарушения</label>'.
												'<label class="form-check-label me-3" for="clang_error_limit">порог</label>'.
												'<input id="clang_error_limit" class="accordion-input-item mb-2" wrap="off" rows="1" name="clang_error_limit" value="0" style="width:10%;"></div>'
												),
							  array('header' => 'Антиплагиат',
									'body'   => '<div><label class="form-check-label" for="plug_bin" style="width:20%;">команда</label>'.
												'<input id="plug_bin" class="accordion-input-item mb-2" wrap="off" rows="1" name="plug_bin" value="copydetect" style="width:50%;"></div>'.
												'<div><label class="form-check-label" for="plug_compiler" style="width:20%;">сравнивать</label>'.
												'<select id="plug_config" class="form-select mb-2" aria-label=".form-select" name="plug-config" style="width:50%; display: inline-block;">'.
												'  <option value="all" selected>со всеми ранее сданными работами</option>'.
												'</select></div>'.
												'<div><input id="plug_enable" class="accordion-input-item form-check-input mb-2" type="checkbox" value="1" name="plug_enable" checked>'.
												'<label class="form-check-label" for="plug_enable" style="width:20%;">проверять</label>'.
												'<label class="form-check-label me-3" for="plug_limit">порог</label>'.
												'<input id="plug_limit" class="accordion-input-item mb-2" wrap="off" rows="1" name="plug_limit" value="0" style="width:10%;"></div>'
												)
												
							 ); 
							 
			  
			  show_accordion('students', $accord);
			?>
			<button id="checks-save" type="submit" class="btn btn-outline-primary mt-3" name="action" value="save" style="">Сохранить</button>
          </div>
        </div>

        <div class="col-4">
          <div class="p-3 border ">

			<h6>Задание</h6>
			<div id="Task" class="tabcontent border bg-light p-2 small" style="overflow-y: auto; width: 100%; height: 100%;">
			  <p id="TaskDescr"><?=$row['description']?></p>
			  <script>
				document.getElementById('TaskDescr').innerHTML = marked.parse(document.getElementById('TaskDescr').innerHTML);
			  </script>
			  <p>
				<b>Файлы, приложенные к заданию:</b> 
				<br>
				<?php 
				  $task_files = getTaskFiles($dbconnect, $task_id);
				  show_task_files($task_files); 
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
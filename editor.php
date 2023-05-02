<!DOCTYPE html>
<html lang="en">

<?php
require_once("common.php");
require_once("dbqueries.php");
require_once("utilities.php");
require_once("resultparse.php");

$au = new auth_ssh();
checkAuLoggedIN($au);
$User = new User((int)$au->getUserId());

$assignment_id = 0;
if ( array_key_exists('assignment', $_GET)) {
  $assignment_id = $_GET['assignment'];
  // $Assignment = new Assignment((int)$assignment_id);
} else {
  //echo "Некорректное обращение";
  //http_response_code(400);
  header('Location: index.php');
  exit;
}
//echo $assignment_id. "<br>";


$task_title = '';
$task_description = '';
$task_finish_limit = '';
$task_status_code = '';
$assignment_status = '';
$task_max_mark = 5;
// TODO: Проверить на наличие конфликата!
$query = select_ax_assignment_with_task_by_id($assignment_id);
$result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
$row = pg_fetch_assoc($result);
if ($row) {
  $task_id = $row['task_id'];
	$task_finish_limit = $row['finish_limit'];
  $task_title = $row['title'];
	$task_status_code = $row['status_code'];
	$assignment_status = $row['status'];
  $task_description = $row['description'];
	$task_max_mark = (int)$row['mark'];
} else {
  echo "Такого assignment не существует";
  //header('Location: index.php');
  exit;
}

$Task = new Task((int)$task_id);

$task_files = $Task->getFiles();
// getTaskFiles($dbconnect, $task_id);

$last_commit_id = NULL;
if ( array_key_exists('commit', $_GET))
  $last_commit_id = $_GET['commit'];
else {
  $result = pg_query($dbconnect, select_last_commit_id_by_assignment_id($assignment_id));
  $last_commit_id = pg_fetch_assoc($result)['id'];
  //echo select_last_commit_id_by_assignment_id($assignment_id) . "<br>";
}

$solution_files = array();

if ($last_commit_id) {
  $result = pg_query($dbconnect, select_last_ax_solution_file_by_commit_id($last_commit_id));
  $file_rows = pg_fetch_all_assoc($result);
  //echo select_last_ax_solution_file_by_commit_id($last_commit_id);
  if ($file_rows) {
    foreach($file_rows as $file_row) {
		$File = new File((int)$file_row['id']);
      $file_full_text = "";
      if (isset($file_row['download_url'])) {
        $file_path = $file_row['download_url'];
        $file_full_text = file_get_contents($file_path);
        $file_full_text = preg_replace('#\'#', '\'\'', $file_full_text);
      } else if (isset($file_row['full_text']))
          $file_full_text = $file_row['full_text'];
      $solution_file = array('id'=>$file_row['id'], 'file_name'=>$File->name_without_prefix, 'text'=>$file_full_text);
      array_push($solution_files, $solution_file);
    }
  }
}


$query = select_page_by_task_id($task_id);
$result = pg_query($dbconnect, $query);
$page_id = pg_fetch_assoc($result)['page_id'];

//$query = select_discipline_name_by_page($page_id, 1);
//$result = pg_query($dbconnect, $query);
//$page_name = pg_fetch_assoc($result)['name'];

$query = select_ax_page_short_name($page_id);
$result = pg_query($dbconnect, $query);
$page_name = pg_fetch_assoc($result)['short_name'];


$page_title = "Онлайн редактор кода";
show_head($page_title, array('https://cdn.jsdelivr.net/npm/marked/marked.min.js')); 
?>

<link rel="stylesheet" href="css/mdb/rdt.css" />

<link rel="stylesheet" href="https://vega.fcyb.mirea.ru/sandbox/node_modules/xterm/css/xterm.css" />
<script src="https://vega.fcyb.mirea.ru/sandbox/node_modules/xterm/lib/xterm.js"></script>
<script src="https://vega.fcyb.mirea.ru/sandbox/node_modules/xterm-addon-attach/lib/xterm-addon-attach.js"></script>
<script src="https://vega.fcyb.mirea.ru/sandbox/node_modules/xterm-addon-fit/lib/xterm-addon-fit.js"></script>

<body style="overflow-x: hidden;">

<?php 
if ($au->isTeacher()) 
// XXX: ПРОВЕРИТЬ
	show_header($dbconnect, $page_title, 
		array('Посылки по дисциплине: '.$page_name => 'preptable.php?page='.$page_id, 
			$task_title => 'taskchat.php?assignment='.$assignment_id, $page_title => ''), 
		$User
	); 
else
	show_header($dbconnect, $page_title, 
		array($page_name => 'studtasks.php?page='.$page_id, 
			$task_title => "taskchat.php?assignment=$assignment_id"), 
		$User
	); 
?>

<main class="container-fluid overflow-hidden">
	<div class="pt-2">
	<div class="row d-flex justify-content-between">
		<div class="col-md-2 ">
		<div class="d-none d-sm-block d-print-block">

		<ul class="tasks__list list-group-flush w-100 px-0" style="width: 100px;">
      <li class="list-group-item disabled px-0">Файлы</li>

      <?php foreach($solution_files as $file) { 
		$File = new File((int)$file['id']);?>
          
      <li class="tasks__item list-group-item w-100 d-flex justify-content-between px-0">
        <div class="px-1 align-items-center" style="cursor: move;"><a href="plug.php?assignment=<?=$assignment_id?>&file=<?=$File->id?>" target="_blank"><i class="fas fa-file-code fa-lg"></i></a></div>
        <input type="text" class="form-control-plaintext form-control-sm validationCustom" id="<?=$File->id?>" value="<?=$File->name_without_prefix?>" disabled>
        <button type="button" class="btn btn-sm mx-0 float-right" id="openFile"><i class="fas fa-edit fa-lg"></i></button>
        <button type="button" class="btn btn-sm float-right" id="delFile"><i class="fas fa-times fa-lg"></i></button>
      </li>
  <?php } ?>

    	<li class="list-group-item w-100 d-flex justify-content-between px-0">
    		<div class="px-1 align-items-center"><i class="fas fa-file-code fa-lg"></i></div>
      	<input type="text" class="form-control-plaintext form-control-sm validationCustom" id="x" value="Новый файл" required>
        <button type="button" class="btn btn-sm px-3" id="newFile"> <i class="far fa-plus-square fa-lg"></i></button>
      </li>  
	</ul>
	</div>
	</div>
	<div class="col-md-6 px-0">
		<div>
      <select class="form-select" aria-label=".form-select" id="language">
        <option value="cpp" selected>C++</option>
        <option value="c">C</option>
        <option value="python">Python</option>
        <option value="java">Java</option>
      </select>
    </div>
    <div class="embed-responsive embed-responsive-4by3" style="border: 1px solid grey">
		<div id="container" class="embed-responsive-item"></div>
		</div>

			<div class="d-flex justify-content-between">
			  <!--<button type="button" class="btn btn-outline-primary" id="check" style="width: 50%;"> Отправить на проверку</button>-->
			  <!--<form action="taskchat.php" method="POST" style="width:50%">-->
				<input type="hidden" name="assignment" value="<?=$assignment_id?>">
        <?php 
		if($au->isAdminOrTeacher()) { // Отправить задание на проверку 
?>
            <button type="button" class="btn btn-success" id="check" style="width: 100%;" assignment="<?=$assignment_id?>" <?=(($task_status_code == 4) ?"disabled" :"")?> >Завершить проверку</button>
<?php 
		} else { // Оценить отправленное на проверку задание 
// TODO: Проверить!?>	
            <button type="button" class="btn btn-primary" id="check" style="width: 100%;" assignment="<?=$assignment_id?>" <?=(($assignment_status == -1) ?"disabled" :"")?> >Отправить на проверку</button>
<?php 
		}
?>
			  <!--</form>-->
			  <button type="button" class="btn btn-outline-primary" id="run" style="width: 50%;">Запустить в консоли</button>

	    </div>
	</div>
	<div class="col-md-4">
		<div class="d-none d-sm-block d-print-block">
		<div class="tab d-flex justify-content-between">
		  <button id="defaultOpen" class="tablinks" onclick="openCity(event, 'Task')">Задание</button>
		  <button class="tablinks" onclick="openCity(event, 'Console')">Консоль</button>
		  <button class="tablinks" onclick="openCity(event, 'Test')">Проверки</button>
		  <button class="tablinks" onclick="openCity(event, 'Chat')">Чат</button>
		</div>

		<div id="Task" class="tabcontent overflow-auto fs-8" style="height: 88%;">
		  <small>
		  <p id="TaskDescr"><?=$task_description?></p>
		  <script>
			document.getElementById('TaskDescr').innerHTML =
				marked.parse(document.getElementById('TaskDescr').innerHTML);
		  </script>
			<p>
			  <?php
			    if ($task_files)
			    {
			      echo '<b>Файлы, приложенные к заданию:</b>';
			      showFiles($task_files);
			    }
			  ?>
			</p>
	      </small>
		</div>

		<div id="Console" class="tabcontent">
		  <h3>Консоль</h3>
		  <div id="terminal"></div>
		</div>

		<div id="Test" class="tabcontent">
		<?php
		
			  $checkres = json_decode('{"tools":{"build":{"enabled":true,"show_to_student":false,"language":"C++","check":{"autoreject":true,"full_output":"output_build.txt","outcome":"pass"},"outcome":"pass"},"valgrind":{"enabled":true,"show_to_student":false,"bin":"valgrind","arguments":"","compiler":"gcc","checks":[{"check":"errors","enabled":true,"limit":0,"autoreject":true,"result":0,"outcome":"pss"},{"check":"leaks","enabled":true,"limit":0,"autoreject":true,"result":5,"outcome":"fail"}],"full_output":"output_valgrind.xml","outcome":"pass"},"cppcheck":{"enabled":true,"show_t_student":false,"bin":"cppcheck","arguments":"","checks":[{"check":"error","enabled":true,"limit":0,"autoreject":false,"result":0,"outcome":"pass"},{"check":"warning","enabled":true,"imit":3,"autoreject":false,"result":0,"outcome":"pass"},{"check":"style","enabled":true,"limit":3,"autoreject":false,"result":2,"outcome":"pass"},{"check":"performance","enabled":true,"limit":2,"autoreject":false,"result":0,"outcome":"pass"},{"check":"portability","enabled":true,"limit":0,"autoreject":false,"result":0,"outcome":"pass"},{"check":"information","enabed":true,"limit":0,"autoreject":false,"result":1,"outcome":"fail"},{"check":"unusedFunction","enabled":true,"limit":0,"autoreject":false,"result":0,"outcome":"pass"},{"check":"missingnclude","enabled":true,"limit":0,"autoreject":false,"result":0,"outcome":"pass"}],"full_output":"output_cppcheck.xml","outcome":"pass"},"clang-format":{"enabled":true,"show_to_student:false,"bin":"clang-format","arguments":"","check":{"level":"strict",".comment":"canbediffrentchecks,suchasstrict,less,minimalandsoon","file":".clang-format","limit":5,"autoreject":tre,"result":8,"outcome":"fail"},"full_output":"output_format.xml","outcome":"pass"},"copydetect":{"enabled":true,"show_to_student":false,"bin":"copydetect","arguments":"","check":{"type":"with_all","limit":50,"reference_directory":"copydetect","autoreject":true,"result":44,"outcome":"skipped"},"full_output":"output_copydetect.html","outcome":"pass"},"autotests":{"enabled":true,"show_to_student":fase,"test_path":"test_example.cpp","check":{"limit":0,"autoreject":true,"outcome":"fail","errors":0,"failures":3},"full_output":"output_tests.txt","outcome":"pass"}}}', true);
			  
			  if (!$last_commit_id || $last_commit_id == "") {
			    $resAC = pg_query($dbconnect, select_last_commit_id_by_assignment_id($assignment_id));
				$last_commit_id = pg_fetch_assoc($resAC)['id'];
			  }

			  if ($last_commit_id && $last_commit_id != "") {
			    $resultC = pg_query($dbconnect, "select autotest_results res from ax_solution_commit where id = ".$last_commit_id);
			    if ($resultC && pg_num_rows($resultC) > 0) {
				  $rowC = pg_fetch_assoc($resultC);
				  if (array_key_exists('res', $rowC) && $rowC['res'] != null)
					$checkres = json_decode($rowC['res'], true);
			    }
			  }
			  
			  $result = pg_query($dbconnect,  "select ax_assignment.id aid, ax_task.id tid, ax_assignment.checks achecks, ax_task.checks tchecks ".
									" from ax_assignment inner join ax_task on ax_assignment.task_id = ax_task.id where ax_assignment.id = ".$assignment_id);
			  $row = pg_fetch_assoc($result);
			  $checks = $row['achecks'];
			  if ($checks == null)
				$checks = $row['tchecks'];
			  if ($checks == null)
				$checks = json_encode($checkres);
			  $checks = json_decode($checks, true);
			  
//  line-height: 20px; color: #fff; text-align: center;
			  $accord = array(parseBuildCheck(@$checkres['tools']['build'], $checks), 
			  				  parseCppCheck(@$checkres['tools']['cppcheck'], $checks), 
							  parseClangFormat(@$checkres['tools']['clang-format'], $checks),
							  parseValgrind(@$checkres['tools']['valgrind'], $checks), 
							  parseAutoTests(@$checkres['tools']['autotests'], $checks),
							  parseCopyDetect(@$checkres['tools']['copydetect'], $checks)
							 );
			  show_accordion('checkres', $accord, "5px");
		?>
		  <input type="hidden" name="commit" value="<?=$last_commit_id?>">
		  <button id="startTools" type="button" class="btn btn-outline-primary mt-1 mb-2" name="startTools">Запустить проверки</button>
		</div>

		<div id="Chat" class="tabcontent">
		  <h3>Чат</h3>
		  <p>чат</p>
			</div>
			<div id="deadline-message" class="deadline-message">
	  Время вышло!
			</div>
			<div id="countdown" class="countdown">
			  <div class="countdown-number">
			    <span class="days countdown-time"></span>
			  </div>
			  <div class="countdown-number">
			    <span class="hours countdown-time"></span>
			  </div>
			  <div class="countdown-number">
			    <span class="minutes countdown-time"></span>
			  </div>
			  <div class="countdown-number">
			    <span class="seconds countdown-time"></span>
			  </div>
			</div>
	</div>


<?php 
if ($task_finish_limit){
echo '
<script>
function getTimeRemaining(endtime) {
  var t = Date.parse(endtime) - Date.parse(new Date());
  var seconds = Math.floor((t / 1000) % 60);
  var minutes = Math.floor((t / 1000 / 60) % 60);
  var hours = Math.floor((t / (1000 * 60 * 60)) % 24);
  var days = Math.floor(t / (1000 * 60 * 60 * 24));
  return {
    total: t,
    days: days,
    hours: hours,
    minutes: minutes,
    seconds: seconds
  };
}

function initializeClock(id, endtime) {
  var clock = document.getElementById(id);
  var daysSpan = clock.querySelector(".days");
  var hoursSpan = clock.querySelector(".hours");
  var minutesSpan = clock.querySelector(".minutes");
  var secondsSpan = clock.querySelector(".seconds");

  function updateClock() {
    var t = getTimeRemaining(endtime);

    if (t.total <= 0) {
      document.getElementById("countdown").className = "hidden";
      document.getElementById("deadline-message").className = "visible";
      clearInterval(timeinterval);
      return true;
    }

    daysSpan.innerHTML = t.days+"д.";
    hoursSpan.innerHTML = ("0" + t.hours).slice(-2)+"ч.";
    minutesSpan.innerHTML = ("0" + t.minutes).slice(-2)+"м.";
    secondsSpan.innerHTML = ("0" + t.seconds).slice(-2)+"с.";
  }

  updateClock();
  var timeinterval = setInterval(updateClock, 1000);
}
function fun() { 
	var deadline = "'.$task_finish_limit.'"; // for endless timer
	initializeClock("countdown", deadline);
} 
fun();
</script>'
;}
?>
	</div>
</div>	
</div>	
</main> 

<script type="module" src="./src/js/sandbox.js"></script>
<script src="js/drag.js"></script>
<script src="js/tab.js"></script>
<script src="../node_modules/monaco-editor/min/vs/loader.js"></script>
<script src="js/editorloader.js" type="module"></script>
<script type="text/javascript" src="js/mdb.min.js"></script>
<!-- Custom scripts -->
<script type="text/javascript">
function showBorders()
{
	var list = document.querySelector("#TaskDescr").querySelectorAll("table");
	for (var i = 0; i < list.length; i++) list[i].classList.add("mdtable");
	list = document.querySelector("#TaskDescr").querySelectorAll("th");
	for (var i = 0; i < list.length; i++) list[i].classList.add("mdtable");
	list = document.querySelector("#TaskDescr").querySelectorAll("td");
	for (var i = 0; i < list.length; i++) list[i].classList.add("mdtable");
}
showBorders();
</script>
<?php
  show_footer();
?>
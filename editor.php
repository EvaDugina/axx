<!DOCTYPE html>
<html lang="en">

<?php
require_once("common.php");
require_once("dbqueries.php");

//show_header('Редактор', array('Дисциплины' => 'index.php'));

$user_id = $_SESSION['hash'];


$assignment_id = 0;
if (isset($_GET['assignment']))
  $assignment_id = $_GET['assignment'];
else {
  echo "Некорректное обращение";
  //http_response_code(400);
  //header('Location: index.php');
  exit;
}


$result3 = pg_query($dbconnect, "SELECT id, task_id, finish_limit FROM ax_assignment WHERE id = ". $assignment_id);
$result2 = pg_query($dbconnect, 'SELECT id, assignment_id, full_text, file_name from ax_solution_file order by id');
$result1 = pg_query($dbconnect, 'SELECT id, description from ax_task');
$result_assig = pg_fetch_all($result3);
$result_file = pg_fetch_all($result2);
$result_task = pg_fetch_all($result1);



$files = [];
$descr = "";
$task_id= 0;
$time= 0;

foreach($result_file as $item) {
  if($item['assignment_id'] == $assignment_id) {
    $files[] = $item;
  }
}
foreach($result_assig as $item) {
  if($item['id'] == $assignment_id) {
    $task_id= $item['task_id'];
    $time= $item['finish_limit'];
  }
}
foreach($result_task as $item) {
  if($item['id'] == $task_id) {
    $descr= $item['description'];
  }
}

$query = select_page_by_task_id($task_id);
$result = pg_query($dbconnect, $query);
$page_id = pg_fetch_assoc($result)['page_id'];

$query = select_discipline_name_by_page($page_id, 1);
$result = pg_query($dbconnect, $query);
$page_name = pg_fetch_assoc($result)['name'];

$task_title = '';
$task_description = '';
$task_max_mark = 5;
$query = select_task($task_id);
$result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
$row = pg_fetch_assoc($result);
if ($row) {
	$task_title = $row['title'];
	$task_description = $row['description'];
  $task_max_mark = (int)$row['max_mark'];
}

$page_title = "Онлайн редактор кода";
show_head($page_title); 
?>

<body>
	<?php 
	if ($au->isTeacher()) 
		show_header($dbconnect, $page_title, 
			array('Посылки по дисциплине: '.$page_name => 'preptable.php?page='.$page_id, 
      $task_title => 'taskchat.php?task='.$task_id.'&page='.$page_id, $page_title => '')
    ); 
	else 
		show_header($dbconnect, $page_title, 
			array($page_name => 'studtasks.php?page='.$page_id, 
      $task_title => 'taskchat.php?task='.$task_id.'&page='.$page_id, $page_title => '')
    ); 
	?>

<link rel="stylesheet" href="css/mdb/rdt.css" />

<link rel="stylesheet" href="https://vega.fcyb.mirea.ru/sandbox/node_modules/xterm/css/xterm.css" />
<script src="https://vega.fcyb.mirea.ru/sandbox/node_modules/xterm/lib/xterm.js"></script>
<script src="https://vega.fcyb.mirea.ru/sandbox/node_modules/xterm-addon-attach/lib/xterm-addon-attach.js"></script>
<script src="https://vega.fcyb.mirea.ru/sandbox/node_modules/xterm-addon-fit/lib/xterm-addon-fit.js"></script>

<body style="overflow-x: hidden;">

<main class="container-fluid overflow-hidden">
	<div class="pt-2">
	<div class="row d-flex justify-content-between">
		<div class="col-md-2 ">
		<div class="d-none d-sm-block d-print-block">

		<ul class="tasks__list list-group-flush w-100 px-0" style="width: 100px;">
      <li class="list-group-item disabled px-0">Файлы</li>

      <?php foreach($files as $item) { ?>
          
      <li class="tasks__item list-group-item w-100 d-flex justify-content-between px-0">
        <div class="px-1 align-items-center" style="cursor: move;"><i class="fas fa-file-code fa-lg"></i></div>
        <input type="text" class="form-control-plaintext form-control-sm validationCustom" id="<?=$item['id']?>" value="<?=$item['file_name']?>" required>
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
			<button type="button" class="btn btn-outline-primary" id="check" style="width: 50%;"> Отправить на проверку</button>
			<button type="button" class="btn btn-outline-primary" id="run" style="width: 50%;"> Запустить в консоли</button>


	</div>
	</div>
	<div class="col-md-4 ">
		<div class="d-none d-sm-block d-print-block">
		<div class="tab d-flex justify-content-between">
		  <button id="defaultOpen" class="tablinks" onclick="openCity(event, 'Task')">Задача</button>
		  <button class="tablinks" onclick="openCity(event, 'Console')">Консоль</button>
		  <button class="tablinks" onclick="openCity(event, 'Test')">Тесты</button>
		  <button class="tablinks" onclick="openCity(event, 'Chat')">Чат</button>
		</div>

		<div id="Task" class="tabcontent" style="height: 88%;">
		  <p><?=$descr?></p>
		</div>

		<div id="Console" class="tabcontent">
		  <h3>Консоль</h3>
		  <div id="terminal"></div>
		</div>

		<div id="Test" class="tabcontent">
		  <h3>Результаты тестов</h3>
		  <p>Результаты тестов</p>
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
if ($time){
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
	var deadline = "'.$time.'"; // for endless timer
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
<script type="text/javascript"></script>
<script type="module" src="./src/js/sandbox.js"></script>
<script src="js/drag.js"></script>
<script src="js/tab.js"></script>
<script src="../node_modules/monaco-editor/min/vs/loader.js"></script>
<script src="js/editorloader.js" type="module"></script>
<script type="text/javascript" src="js/mdb.min.js"></script>
<!-- Custom scripts -->

<?php
  show_footer();
?>
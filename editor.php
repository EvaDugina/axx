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
echo "<script>var user_role=".$User->role.";</script>";
$user_id = $User->id;

$MAX_FILE_SIZE = getMaxFileSize();

$assignment_id = 0;
if ( array_key_exists('assignment', $_GET)) {
  $assignment_id = $_GET['assignment'];
  $Assignment = new Assignment((int)$assignment_id);
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
  if($au->isStudent())
    $lastCommit = $Assignment->getLastCommitForStudent();
  else 
    $lastCommit = $Assignment->getLastCommitForTeacher();
  if($lastCommit == null) {
    $lastCommit = new Commit($Assignment->id, null, $au->getUserId(), 0, null);
    $Assignment->addCommit($lastCommit->id);
  }
  $last_commit_id = $lastCommit->id;
}


$solution_files = array();

$nowCommit = null;
$readOnly = "false";
if ($last_commit_id) {
  $nowCommit = new Commit((int)$last_commit_id);
  foreach($nowCommit->getFiles() as $File) {
    $solution_file = array('id'=>$File->id, 'file_name'=>$File->name_without_prefix, 'text'=>$File->getFullText());
    array_push($solution_files, $solution_file);
  }

  if ($nowCommit->isNotEdit())
    $readOnly="true";
} else {
  // if($au->isStudent()) {
  //   $nowCommit = new Commit($Assignment->id, null, $au->getUserId(), 0, null);
  //   header("Location:editor.php?assignment=" . $Assignment->id . "&commit=" . $nowCommit->id);
  // } 
}
echo "<script>var read_only=$readOnly;</script>";


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

<body style="overflow-x:hidden">

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
		<div class="col-md-2 d-flex flex-column">

      <div class="d-none d-sm-block d-print-block" style="border-bottom: 1px solid;">
        <ul id="ul-files" class="tasks__list list-group-flush w-100 px-0" style="width: 100px;">
          <li class="list-group-item disabled px-0">Файлы</li>

          <?php if ($nowCommit->isNotEdit()) {?>
            <p id="p-no-files" class="d-none">Файлы отсутсвуют</p>
          <?php }?>

          <?php 
          if(count($solution_files) > 0) {
            foreach($solution_files as $i => $file) { 
              $File = new File((int)$file['id']);?>
              <li id="openFile" class="tasks__item list-group-item w-100 d-flex justify-content-between align-items-center px-0" 
              style="cursor: pointer;" data-orderId="<?=$i?>">
                <div class="px-1 align-items-center text-primary">
                  <?=getSVGByFileType($File->type)?>
                </div>
                <input type="button" class="form-control-plaintext form-control-sm validationCustom" 
                id="<?=$File->id?>" value="<?=$File->name_without_prefix?>" style="cursor: pointer;">
                <!-- <button type="button" class="btn btn-sm ms-0 me-1 float-right" id="openFile">
                  getSVGByCommitType($nowCommit->type)
                </button> -->
                <div class="dropdown align-items-center h-100 ms-1 me-1" id="btn-group-moreActionsWithFile">
                  <button class="btn btn-primary py-1 px-2" type="button" id="ul-dropdownMenu-moreActionsWithFile"
                  data-mdb-toggle="dropdown" aria-expanded="false">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-three-dots-vertical" viewBox="0 0 16 16">
                      <path d="M9.5 13a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                    </svg>
                  </button>
                  <ul class="dropdown-menu" aria-labelledby="ul-dropdownMenu-moreActionsWithFile">
                    <?php if(!$nowCommit->isNotEdit()) {?>
                      <li>
                        <a type="button" class="dropdown-item align-items-center" id="a-renameFile">
                          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pen-fill" viewBox="0 0 16 16">
                            <path d="m13.498.795.149-.149a1.207 1.207 0 1 1 1.707 1.708l-.149.148a1.5 1.5 0 0 1-.059 2.059L4.854 14.854a.5.5 0 0 1-.233.131l-4 1a.5.5 0 0 1-.606-.606l1-4a.5.5 0 0 1 .131-.232l9.642-9.642a.5.5 0 0 0-.642.056L6.854 4.854a.5.5 0 1 1-.708-.708L9.44.854A1.5 1.5 0 0 1 11.5.796a1.5 1.5 0 0 1 1.998-.001z"/>
                          </svg>
                          &nbsp;
                          Переименовать
                        </a>
                      </li>
                    <?php }?>
                    <li>
                      <a class="dropdown-item align-items-center" href="<?=$File->getDownloadLink()?>" target="_blank">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-download" viewBox="0 0 16 16">
                          <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                          <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                        </svg>
                        &nbsp;
                        Скачать
                      </a>
                    </li>
                  </ul>
                </div>
                <?php if(!$nowCommit->isNotEdit()) {?>
                  <button type="button" class="btn btn-link float-right mx-1 py-0 px-2" id="delFile"><i class="fas fa-times fa-lg"></i></button>
                <?php }?>
              </li>
            <?php } 
          }?>

          <?php if(!$nowCommit->isNotEdit()) {?>
            <div id="div-add-new-file" class="input-group mt-2 mb-0">
              <div class="input-group-prepend">
                <span class="input-group-text w-100 h-100" id="basic-addon1">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark" viewBox="0 0 16 16">
                    <path d="M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h5.5L14 4.5zm-3 0A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V4.5h-2z"/>
                  </svg>
                </span>
              </div>
              <input id="input-name-newFile" type="text" class="form-control validationCustom" placeholder="Новый файл" required>
              <button type="button" class="btn btn-outline-primary ms-1 px-3" id="btn-newFile"> 
                <i class="far fa-plus-square fa-lg"></i>
              </button>
            </div>
            <div id="div-name-newFile-error" class="mb-2 d-none text-danger" style="font-size: 80%;">
              Не введено имя файла
            </div>

            <!-- <li id="li-new-file" class="list-group-item w-100 d-flex justify-content-between px-0">
              <div class="px-1 align-items-center"><i class="fas fa-file-code fa-lg"></i></div>
              <input type="text" class="form-control-plaintext form-control-sm validationCustom" id="x" value="Новый файл" required>
              <button type="button" class="btn btn-sm px-3" id="newFile"> <i class="far fa-plus-square fa-lg"></i></button>
            </li>   -->
          <?php }?>
      </ul>
    </div>

    
    <div class="d-flex flex-column mt-3">
      <p><strong>История коммитов</strong></p>
      <div id="div-history-commit-btns" class="d-flex flex-column">
        <?php if($au->isStudent()) 
          $Commits = $Assignment->getCommitsForStudent();
        else 
          $Commits = $Assignment->getCommitsForTeacher();?>
        <?php foreach($Commits as $i => $Commit) {?>
          <button class="btn btn-<?=($Commit->isNotEdit()) ? "success" : "primary"?> mb-1 d-flex align-items-center justify-content-between" 
          onclick="window.location='editor.php?assignment=<?=$Assignment->id?>&commit=<?=$Commit->id?>'"
          <?=($Commit->id == $last_commit_id) ? "disabled" : ""?>>
            <?=$i+1?> &nbsp;&nbsp;
            <?php 
            if($Commit->id == $last_commit_id) 
              echo '~ТЕКУЩИЙ~';?>  
            <?=getSVGByCommitType($Commit->type)?>
          </button>
        <?php }?>
      </div>
    </div>

	</div>
	<div class="col-md-6 px-0">
    <div class="d-flex mb-1">
      <div class="w-100 me-1">
        <select class="form-select" aria-label=".form-select" id="language">
          <option value="cpp" selected>C++</option>
          <option value="c">C</option>
          <option value="python">Python</option>
          <option value="java">Java</option>
        </select>
      </div>
      <button id="btn-commit" class="btn btn-secondary w-100 align-items-center me-1" type="button">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus-lg" viewBox="0 0 16 16">
          <path fill-rule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2Z"/>
        </svg>
        &nbsp;&nbsp;
        Клонировать коммит
      </button>
      <button id="btn-save" class="btn btn-primary w-100" type="button"
      <?=($nowCommit && $nowCommit->isNotEdit()) ? "disabled" : ""?>>
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check2-all" viewBox="0 0 16 16">
          <path d="M12.354 4.354a.5.5 0 0 0-.708-.708L5 10.293 1.854 7.146a.5.5 0 1 0-.708.708l3.5 3.5a.5.5 0 0 0 .708 0l7-7zm-4.208 7-.896-.897.707-.707.543.543 6.646-6.647a.5.5 0 0 1 .708.708l-7 7a.5.5 0 0 1-.708 0z"/>
          <path d="m5.354 7.146.896.897-.707.707-.897-.896a.5.5 0 1 1 .708-.708z"/>
        </svg>
        &nbsp;&nbsp;
        Сохранить
      </button>
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
            <button type="button" class="btn btn-success me-1" id="check" style="width: 100%;" 
            assignment="<?=$assignment_id?>" <?=(($assignment_status == -1) ?"disabled" :"")?> >
            Отправить на проверку</button>
<?php 
		}
?>
			  <!--</form>-->
			  <button type="button" class="btn btn-primary" id="run" style="width: 50%;">Запустить в консоли</button>

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
		
			  $checkres = json_decode('{
          "tools": {
            "build": {
              "enabled": true,
              "show_to_student": false,
              "language": "C++",
              "check": {
                "autoreject": true,
                "full_output": "output_build.txt",
                "outcome": "pass"
              },
              "outcome": "pass"
            },
            "valgrind": {
              "enabled": true,
              "show_to_student": false,
              "bin": "valgrind",
              "arguments": "",
              "compiler": "gcc",
              "checks": [
                {
                  "check": "errors",
                  "enabled": true,
                  "limit": 0,
                  "autoreject": true,
                  "result": 0,
                  "outcome": "pass"
                },
                {
                  "check": "leaks",
                  "enabled": true,
                  "limit": 0,
                  "autoreject": true,
                  "result": 5,
                  "outcome": "fail"
                }
              ],
              "full_output": "output_valgrind.xml",
              "outcome": "pass"
            },
            "cppcheck": {
              "enabled": true,
              "show_t_student": false,
              "bin": "cppcheck",
              "arguments": "",
              "checks": [
                {
                  "check": "error",
                  "enabled": true,
                  "limit": 0,
                  "autoreject": false,
                  "result": 0,
                  "outcome": "pass"
                },
                {
                  "check": "warning",
                  "enabled": true,
                  "imit": 3,
                  "autoreject": false,
                  "result": 0,
                  "outcome": "pass"
                },
                {
                  "check": "style",
                  "enabled": true,
                  "limit": 3,
                  "autoreject": false,
                  "result": 2,
                  "outcome": "pass"
                },
                {
                  "check": "performance",
                  "enabled": true,
                  "limit": 2,
                  "autoreject": false,
                  "result": 0,
                  "outcome": "pass"
                },
                {
                  "check": "portability",
                  "enabled": true,
                  "limit": 0,
                  "autoreject": false,
                  "result": 0,
                  "outcome": "pass"
                },
                {
                  "check": "information",
                  "enabed": true,
                  "limit": 0,
                  "autoreject": false,
                  "result": 1,
                  "outcome": "fail"
                },
                {
                  "check": "unusedFunction",
                  "enabled": true,
                  "limit": 0,
                  "autoreject": false,
                  "result": 0,
                  "outcome": "pass"
                },
                {
                  "check": "missingnclude",
                  "enabled": true,
                  "limit": 0,
                  "autoreject": false,
                  "result": 0,
                  "outcome": "pass"
                }
              ],
              "full_output": "output_cppcheck.xml",
              "outcome": "pass"
            },
            "clang-format": {
              "enabled": true,
              "show_to_student": false,
              "bin": "clang-format",
              "arguments": "",
              "check": {
                "level": "strict",
                ".comment": "canbediffrentchecks,suchasstrict,less,minimalandsoon",
                "file": ".clang-format",
                "limit": 5,
                "autoreject": true,
                "result": 8,
                "outcome": "fail"
              },
              "full_output": "output_format.xml",
              "outcome": "pass"
            },
            "copydetect": {
              "enabled": true,
              "show_to_student": false,
              "bin": "copydetect",
              "arguments": "",
              "check": {
                "type": "with_all",
                "limit": 50,
                "reference_directory": "copydetect",
                "autoreject": true,
                "result": 44,
                "outcome": "skipped"
              },
              "full_output": "output_copydetect.html",
              "outcome": "pass"
            },
            "autotests": {
              "enabled": true,
              "show_to_student": false,
              "test_path": "test_example.cpp",
              "check": {
                "limit": 0,
                "autoreject": true,
                "outcome": "fail",
                "errors": 0,
                "failures": 3
              },
              "full_output": "output_tests.txt",
              "outcome": "pass"
            }
          }
        }', true);
			  
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
      
      <div class="chat-wrapper mb-1">

        <div id="chat-box" style="overflow-y: scroll; max-height: 55%">
          <!-- Вывод сообщений на страницу -->
        </div>
      

        <div class="d-flex align-items-center" >

          <div class="dropdown d-none me-1" id="btn-group-more">
            <button class="btn btn-primary dropdown-toggle py-1 px-2" type="button" id="ul-dropdownMenu-more"
            data-mdb-toggle="dropdown" aria-expanded="false">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-three-dots-vertical" viewBox="0 0 16 16">
                <path d="M9.5 13a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
              </svg>
            </button>
            <ul class="dropdown-menu" aria-labelledby="ul-dropdownMenu-more">
              <li>
                <a class="dropdown-item align-items-center" href="#">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left-right me-1" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M1 11.5a.5.5 0 0 0 .5.5h11.793l-3.147 3.146a.5.5 0 0 0 .708.708l4-4a.5.5 0 0 0 0-.708l-4-4a.5.5 0 0 0-.708.708L13.293 11H1.5a.5.5 0 0 0-.5.5zm14-7a.5.5 0 0 1-.5.5H2.707l3.147 3.146a.5.5 0 1 1-.708.708l-4-4a.5.5 0 0 1 0-.708l4-4a.5.5 0 1 1 .708.708L2.707 4H14.5a.5.5 0 0 1 .5.5z"/>
                  </svg>
                  Переслать
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right-short" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M4 8a.5.5 0 0 1 .5-.5h5.793L8.146 5.354a.5.5 0 1 1 .708-.708l3 3a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708-.708L10.293 8.5H4.5A.5.5 0 0 1 4 8z"/>
                  </svg>
                </a>
                <ul class="dropdown-menu dropdown-submenu" style="cursor: pointer;">
                  <?php 
                  $Page = new Page((int)getPageByAssignment((int)$Assignment->id));
                  $conversationTask = $Page->getConversationTask();
                  if($conversationTask) {?>
                    <li>
                      <a class="dropdown-item" onclick="resendMessages(<?=$conversationTask->getConversationAssignment()->id?>, <?=$User->id?>, false)">
                        В общую беседу
                      </a>
                    </li>
                  <?php }?>
                  <li>
                    <a class="dropdown-item" onclick="resendMessages(<?=$Assignment->id?>, <?=$User->id?>, true)">
                      В текущий диалог
                    </a>
                  </li>
                  
                </ul>
              </li>
              <li> 
                <a class="dropdown-item align-items-center" href="#" id="a-messages-delete" style="cursor: pointer;" onclick="deleteMessages(<?=$Assignment->id?>, <?=$User->id?>)">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-lg me-1" viewBox="0 0 16 16">
                    <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8 2.146 2.854Z"/>
                  </svg>
                  Удалить
                </a>
              </li>
            </ul>
          </div>

          <?php if($Assignment->isCompleteable() || $Task->isConversation()) {?>
            <form class="w-100 align-items-center m-0" action="taskchat_action.php" method="POST" enctype="multipart/form-data">
              <div class="message-input-wrapper h-100 align-items-center p-0 m-0">
                <div class="file-input-wrapper">
                  <input type="hidden" name="MAX_FILE_SIZE" value="<?=$MAX_FILE_SIZE?>" />
                  <input id="user-files" type="file" name="user_files[]" class="input-files" multiple>
                  <label for="user-files">
                    <i class="fa-solid fa-paperclip"></i><span id="files-count" class="label-files-count"></span>
                  </label>
                </div>
                <textarea name="user-message" id="user-message" placeholder="Напишите сообщение..."></textarea>
                <button type="submit" name="submit-message" id="submit-message">Отправить</button>
              </div>
            </form>
          <?php }?>

        </div>

      </div>

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

<script type="text/javascript" src="js/taskchat.js"></script>

  <script type="text/javascript">

    // После первой загрузки скролим страницу вниз
    // $('body, html').scrollTop($('body, html').prop('scrollHeight'));

    $(document).ready(function() {

      let button_check = document.getElementById('button-check');
      let button_answer = document.getElementById('submit-answer');

      // Отправка формы сообщения через FormData (с моментальным обновлением лога чата)
      $("#submit-message").click(function() {
        var userMessage = $("#user-message").val();
        var userFiles = $("#user-files");

        if(!sendMessage(userMessage, userFiles, 0)) {
          event.preventDefault();
          // console.log("Сообщение было успешно отсправлено");
        } else {
          // console.log("Сообщение не было отправлено");
        }

        $("#user-message").val("");
        $("#user-message").css('height', '37.6px');
        $("#user-files").val("");
        $('#files-count').html('');

        loadChatLog(true);

        return false;
      });

      
      // Первое обновление лога чата
      loadChatLog(true);
      // Обновление лога чата раз в 1 секунд
      setInterval(loadChatLog, 100000);

      
    });
    


    // Обновляет лог чата из БД
    function loadNewMessages() {
      // console.log("LOAD_CHAT_LOG!");
      
      var formData = new FormData();
      formData.append('assignment_id', <?=$assignment_id?>);
      formData.append('user_id', <?=$user_id?>);
      formData.append('load_status', 'new_only');
      $.ajax({
        type: "POST",
        url: 'taskchat_action.php#content',
        cache: false,
        contentType: false,
        processData: false,
        data: formData,
        dataType : 'html',
        success: function(response) {
          // console.log(response);
          $('#chat-box').innerHTML += response;
        },
        complete: function() {
          // Скролим чат вниз при появлении новых сообщений
          // $('#chat-box').scrollTop($('#chat-box').prop('scrollHeight'));
        }
      });
    }



    function loadChatLog($first_scroll = false) {
      console.log("loadChatLog");
      // TODO: Обращаться к обновлению чата только в случае, если добавлиось новое, ещё не прочитанное сообщение
      $('#chat-box').load('taskchat_action.php#content', {assignment_id: <?=$assignment_id?>, user_id: <?=$user_id?>, 
      selected_messages: JSON.stringify(selectedMessages), load_status: 'full'}, function() {
        // После первой загрузки страницы скролим чат вниз до новых сообщений или но самого низа
        if ($first_scroll) {
          if ($('#new-messages').length == 0) {
            $('#chat-box').scrollTop($('#chat-box').prop('scrollHeight'));
          }
          else {
            $('#chat-box').scrollTop($('#new-messages').offset().top - $('#chat-box').offset().top - 10);
          }
        }	
      })
    }


    function sendMessage(userMessage, userFiles, typeMessage, mark=null) {
      if ($.trim(userMessage) == '' && userFiles.val() == '') { 
        // console.log("ФАЙЛЫ НЕ ПРИКРЕПЛЕНЫ");
        return false; 
      }
      
      let flag = true;
      
      var formData = new FormData();
      formData.append('assignment_id', <?=$assignment_id?>);
      formData.append('user_id', <?=$user_id?>);
      formData.append('message_text', userMessage);
      formData.append('type', typeMessage);
      if(userFiles){
        // console.log("EEEEEEEEEE");
        //formData.append('MAX_FILE_SIZE', 5242880); // TODO Максимальный размер загружаемых файлов менять тут. Сейчас 5мб
        $.each(userFiles[0].files, function(key, input) {
          // console.log(input.size);
          // console.log(<?=$MAX_FILE_SIZE?>*0.8);
          if (input.size < <?=$MAX_FILE_SIZE?>*0.8){
            formData.append('files[]', input);
          } else {
            alert("Размер отправленного файла превышает допустимый размер");
            flag = false;
          }
        });
      } else if (typeMessage == 2 && mark) {
        formData.append('mark', mark);
      }

      if (flag == false) {
        return false;
      } else {

        // console.log('message_text =' + userMessage);
        // console.log('type =' + typeMessage);

        $.ajax({
          type: "POST",
          url: 'taskchat_action.php#content',
          cache: false,
          contentType: false,
          processData: false,
          data: formData,
          dataType : 'html',
          success: function(response) {
            $("#chat-box").html(response);
            if (typeMessage == 1) {
              let now = new Date();
              $("#label-task-status-text").text("Ожидает проверки");
              $("#span-answer-date").text(formatDate(now));
            } else if (typeMessage == 2) {
              let now = new Date();
              $("#label-task-status-text").text("Выполнено");
              $("#flexCheckDisabled").prop("checked", true);
              $("#span-answer-date").text(formatDate(now));
              $("#span-text-mark").html("Оценка: "+'<b id="b-mark">'+mark+'</b>');
              console.log("Оценка: "+'<b id="b-mark">'+mark+'</b>');
            }
          },
          complete: function() {
            // Скролим чат вниз после отправки сообщения
            $('#chat-box').scrollTop($('#chat-box').prop('scrollHeight'));
          }
        });
      }


      return true;
    }


    var selectedMessages = [];
    var senderUserTypes = [];

    function selectMessage(message_id, sender_user_type){
      if(selectedMessages.includes(message_id)) {
        let index = selectedMessages.indexOf(message_id);
        selectedMessages.splice(index, 1); 
        senderUserTypes.splice(index, 1);
        $('#btn-message-' + message_id).removeClass("bg-info");
        if(selectedMessages.length == 0)
          $('#btn-group-more').addClass("d-none");
      } else {
        selectedMessages.push(message_id);
        senderUserTypes.push(sender_user_type);
        $('#btn-message-' + message_id).addClass("bg-info");
        $('#btn-group-more').removeClass("d-none");
      }


      // Показывать кнопку "Удалить сособщение, если оно своё или нет, если не своё"
      let flag = true;
      selectedMessages.forEach((message_id, index) => {
        if(senderUserTypes[index] != user_role) {
          flag = false;
        }
      });
      if(flag)
        $('#a-messages-delete').removeClass("disabled");
      else 
        $('#a-messages-delete').addClass("disabled");
    }

    function resendMessages(assignment_id, user_id, this_chat) {
      
      var formData = new FormData();
      formData.append('assignment_id', assignment_id);
      formData.append('user_id', user_id);
      formData.append('selected_messages', JSON.stringify(selectedMessages));
      formData.append('resendMessages', true);

      $.ajax({
        type: "POST",
        url: 'taskchat_action.php#content',
        cache: false,
        contentType: false,
        processData: false,
        data: formData,
        dataType : 'html',
        success: function(response) {
          if(this_chat) {
            $("#chat-box").html(response);
            for (let i = 0; i < selectedMessages.length; ) {
              selectMessage(selectedMessages[i], null);
            }
          }
        },
        complete: function() {
          // Скролим чат вниз после отправки сообщения
          if(this_chat){
            $('#chat-box').scrollTop($('#chat-box').prop('scrollHeight'));
            console.log($('#chat-box').prop('scrollHeight'));
          }
        }
      });


      return true;
    } 

    function deleteMessages(assignment_id, user_id) {
      var formData = new FormData();
      formData.append('assignment_id', assignment_id);
      formData.append('user_id', user_id);
      formData.append('selected_messages', JSON.stringify(selectedMessages));
      formData.append('deleteMessages', true);

      $.ajax({
        type: "POST",
        url: 'taskchat_action.php#content',
        cache: false,
        contentType: false,
        processData: false,
        data: formData,
        dataType : 'html',
        success: function(response) {
          $("#chat-box").html(response);
          for (let i = 0; i < selectedMessages.length; ) {
              selectMessage(selectedMessages[i], null);
            }
        },
        complete: function() {
          // Скролим чат вниз после отправки сообщения
          $('#chat-box').scrollTop($('#chat-box').prop('scrollHeight'));
          console.log($('#chat-box').prop('scrollHeight'));
        }
      });


      return true;
    }

  </script>

  <style>
    .disabled {
      pointer-events: none;
      cursor: default;
      opacity: 0.6;
    }
    .dropdown-menu li {
      position: relative;
    }
    .dropdown-menu .dropdown-submenu {
      display: none;
      position: absolute;
      left: 100%;
      top: -7px;
    }
    .dropdown-menu .dropdown-submenu-left {
      right: 100%;
      left: auto;
    }
    .dropdown-menu > li:hover > .dropdown-submenu {
      display: block;
    }

    .chat-wrapper {
    width: 100%;
    padding-top: 20px;
    padding-bottom: 5px;
}

#chat-box {
    border: 1px solid #00000020;
    border-radius: 5px;
    padding: 10px;
    margin-bottom: 15px;
    height: 600px;
    width: 100%;
    overflow-y: auto;
}

.chat-box-message {
    float: left;
    min-width: 400px;
    max-width: 600px;
    margin: 10px 0;
}

.chat-box-message-wrapper {
    border: 1px solid #00000020;
    border-radius: 5px;
    padding: 10px;
}

.chat-box-message-date {
    margin-left: 15px;
    font-size: 14px;
}

.message-input-wrapper {
    display: flex;
    align-items: flex-start;
    margin-bottom: 10px;
}

#user-message {
    flex-grow: 1;
    margin-left: 5px;
    margin-right: 5px;
    padding: 5px 0 5px 3px;
    border: 1px solid #00000020;
    border-radius: 5px;
    resize: none;
    height: 37.6px;
    
    font-family: Arial, Helvetica, sans-serif;
    font-size: 16px;
    font-weight: 300;
}


#submit-message {
    background: #fff;
    border: 1px solid #00000020;
    border-radius: 5px;
    padding: 5px 15px;
    transition: all 0.2s ease;

    font-family: Arial, Helvetica, sans-serif;
    font-size: 16px;
    font-weight: 300;
}
#submit-message:hover {
    background: #fffbfb;
}

.file-input-wrapper {
    position: relative;
    z-index: 10;
    display: flex;
    align-items: center;
    height: 100%;
}

#user-files, #user-answer-files {
    width: 0.1px;
    height: 0.1px;
    opacity: 0;
    position: absolute;
    z-index: -10;
}

.file-input-wrapper label {
    display: inline-block;
    position: relative;
    padding: 0 8px 0 5px;
    transition: all 0.1s ease;
    
    font-weight: 300;
    font-size: 23px;
    color: #4f4f4fc6;
}
.file-input-wrapper label:hover {
    cursor: pointer;
    color: #4f4f4f;
}

#files-count, #files-answer-count {
    display: inline-block;
    position: absolute;
    bottom: -4px;
    right: 0px;

    font-weight: 700;
    color: #2e73e3;
    font-size: 14px;
}

.float-right {
    float: right;
}

.clear {
    clear: both;
}

.background-color-blue {
    background-color: #d9f4fa7b;
}

.pretty-text {
    white-space: pre-line;
    word-wrap: break-word;
}

@media screen and (max-width: 900px) {
    .task-wrapper > div {
        flex-flow: column;
    }
    .task-desc-wrapper {
        margin-right: 0;
    }
    .task-status-wrapper {
        flex-direction: row;
        padding: 10px 5px 0 15px;
    }
}

@media screen and (max-width: 600px) {
    .task-desc-wrapper > div {
        flex-direction: column;
    }
}
  </style>
<?php
  // show_footer();
?>
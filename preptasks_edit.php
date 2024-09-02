<?php

require_once("common.php");
require_once("dbqueries.php");
require_once("utilities.php");

// защита от случайного перехода
$au = new auth_ssh();
if (!$au->isAdminOrPrep()) {
  $au->logout();
  header('Location:login.php');
}

if (isset($_GET['task_id']) && isset($_GET['page_id'])) {
  // TODO: ПРОВЕРИТь!
  $Task = new Task((int)$_GET['task_id']);
  $Task->deleteFromDB();
  // $query = delete_task($_GET['task_id']);
  // $result = pg_query($dbconnect, $query);

  echo "УДАЛЕНИЕ ЗАДАНИЯ";
  header('Location: preptasks.php?page=' . $_GET['page_id']);
  exit();
}

if (!array_key_exists('page', $_REQUEST)) {
  echo "Некорректное обращение, отсутствует ключ 'page'";
  http_response_code(400);
  exit;
}

if (isset($_POST['action']) && $_POST['action'] == "linkFile") {
  if (count($_FILES) != 1) {
    echo "Некорректное обращение, отсутствуют файлы";
    http_response_code(400);
    exit;
  }

  $page_id = $_POST['page'];
  $filename = $_FILES['customFile']['name'];
  $filetype = $_FILES['customFile']['type']; // text/plain

  $uploaddir = 'upload_files/';
  $uploadfile = $uploaddir . basename($_FILES['customFile']['name']);


  if (!move_uploaded_file($_FILES['customFile']['tmp_name'], $uploadfile)) {
    echo "Ошибка загрузки файла";
    http_response_code(500);
    exit;
  }

  // TODO: ПРОВЕРИТЬ!
  $tasknums = explode(',', @$_POST['tasknum']);
  if (count($tasknums) > 0) {
    // $query = 'insert into ax.ax_task_file (type, task_id, file_name, download_url, full_text) VALUES ';

    // $items = array();
    foreach ($tasknums as $tn) {
      // array_push($items, '(0, '.$tn.', \''.$filename.'\', \''.$uploadfile.'\', null)');
      $Task = new Task((int)$tn);
      $File = new File(0, $filename, $uploadfile, null);
      $Task->addFile($File->id);
    }
    // $query .= implode(',', $items);
    // $result = pg_query($dbconnect, $query);
  }

  /*
      ["name"]=> string(6) "db.txt" 
      ["type"]=> string(10) "text/plain" 
      ["tmp_name"]=> string(24) "C:\xampp\tmp\phpB638.tmp" 
      ["error"]=> int(0)
      ["size"]=> int(2030)
  */

  header('Location:preptasks.php?page=' . $_POST['page']);
  exit();
}

$action = @$_REQUEST['action'];
switch ($action) {
  case "linkFile": {
      if (count($_FILES) != 1) {
        echo "Некорректное обращение, отсутствуют файлы";
        http_response_code(400);
        exit;
      }

      $page_id = $_REQUEST['page'];
      $filename = $_FILES['customFile']['name'];
      $filetype = $_FILES['customFile']['type']; // text/plain

      $uploaddir = 'upload_files/';
      $uploadfile = $uploaddir . basename($_FILES['customFile']['name']);


      if (!move_uploaded_file($_FILES['customFile']['tmp_name'], $uploadfile)) {
        echo "Ошибка загрузки файла";
        http_response_code(500);
        exit;
      }

      // TODO: ПРОВЕРИТЬ!
      $tasknums = explode(',', @$_REQUEST['tasknum']);
      if (count($tasknums) > 0) {
        // $query = 'insert into ax.ax_task_file (type, task_id, file_name, download_url, full_text) VALUES ';

        // $items = array();
        foreach ($tasknums as $tn) {
          // array_push($items, '(0, '.$tn.', \''.$filename.'\', \''.$uploadfile.'\', null)');
          $Task = new Task((int)$tn);
          $File = new File(0, $filename, $uploadfile, null);
          $Task->addFile($File->id);
        }
        // $query .= implode(',', $items);
        // $result = pg_query($dbconnect, $query);
      }

      /*
            ["name"]=> string(6) "db.txt" 
            ["type"]=> string(10) "text/plain" 
            ["tmp_name"]=> string(24) "C:\xampp\tmp\phpB638.tmp" 
            ["error"]=> int(0)
            ["size"]=> int(2030)
        */

      header('Location:preptasks.php?page=' . $_REQUEST['page']);
      break;
    }
  case "copyToDiscipline": {
      $tasknums = explode(',', @$_REQUEST['tasknum']);
      if (!$tasknums || count($tasknums) < 1 || @$_REQUEST['tasknum'] == "") {
        echo "Не выбраны задания";
        http_response_code(400);
        exit;
      }

      $Page = new Page((int)$_REQUEST['page']);
      foreach ($tasknums as $task_id) {
        $Task = new Task($Page->id, 0, 1);
        $Task->copy($task_id);
        // $Page->addTask((int)$Task->id);
      }

      header('Location:preptasks.php?page=' . $_REQUEST['page']);
      break;
    }
  case "assign": {
      $students = @$_REQUEST['students'];
      if (!$students || count($students) < 1) {
        echo "Не выбраны студенты";
        http_response_code(400);
        exit;
      }

      $flag_startDate = false;
      if (isset($_REQUEST['fromtime']) && $_REQUEST['fromtime'] != "") {
        $date = strtotime($_REQUEST['fromtime']);
        if (isset($_REQUEST['start_time'])) {
          $str = $_REQUEST['fromtime'] . " " . $_REQUEST['start_time'] . ":00";
          $date = strtotime($str);
        }
        $start_date = date("Y-m-d H:i:s", $date);
        // $Assignment->setStartLimit($date_start);
        $flag_startDate = true;
      }

      $flag_endDate = false;
      if (isset($_REQUEST['tilltime']) && $_REQUEST['tilltime'] != "") {
        $date = strtotime($_REQUEST['tilltime']);
        if (isset($_REQUEST['end_time'])) {
          $str = $_REQUEST['tilltime'] . " " . $_REQUEST['end_time'] . ":00";
          $date = strtotime($str);
        }
        // $Assignment->setFinishLimit(date("Y-m-d H:i:s", $date));
        $end_date = date("Y-m-d H:i:s", $date);
        $flag_endDate = true;
      }

      // $tilltime = "now() + '1 year'";
      // if (array_key_exists('tilltime', $_REQUEST) && ($_REQUEST['tilltime'] != "")) {
      //   $tilltime = $_REQUEST['tilltime'];
      //   $tilltime = conver_calendar_to_timestamp($tilltime);

      //   $query = select_check_timestamp($tilltime);
      //   $level = error_reporting();
      //   error_reporting(E_ERROR);
      //   $result = pg_query($dbconnect, $query);
      //   error_reporting($level);
      //   if (!$result && ($tilltime != "")) {
      //     echo "Неверный формат даты и времени";
      //     http_response_code(400);
      //     exit;
      //   }
      //   $tilltime = "to_timestamp('" . $tilltime . "', 'YYYY-MM-DD HH24:MI:SS')";
      // }


      $group = 0;
      $group = @$_REQUEST['groupped'];

      $tasknums = explode(',', @$_REQUEST['tasknum']);
      if (!$tasknums || count($tasknums) < 1 || @$_REQUEST['tasknum'] == "") {
        echo "Не выбраны задания";
        http_response_code(400);
        exit;
      }

      if ($group == "1") {
        // $assignnums = array();

        // TODO: Проверить!
        foreach ($tasknums as $tn) {

          // $query = 'insert into ax.ax_assignment(task_id, variant_number, start_limit, finish_limit, ' .
          //   ' status_code, status, delay, status_text, mark) values ' .
          //   ' (' . $tn . ', null, null, null, 2, 0, 0, \'Доступно для просмотра\', null) returning id;';
          // $result = pg_query($dbconnect, $query);

          // if ($row = pg_fetch_assoc($result))
          //   array_push($assignnums, $row['id']);

          $Assignment = new Assignment($tn, 2);

          if ($flag_startDate) $Assignment->setStartLimit($start_date);
          if ($flag_endDate) $Assignment->setFinishLimit($end_date);


          foreach ($students as $s) {
            $Assignment->addStudent($s);
            // $query = 'insert into ax.ax_assignment_student (assignment_id, student_user_id) VALUES (' . $a . ', ' . $s . ')';
            // $result = pg_query($dbconnect, $query);
          }
        }

        // foreach ($assignnums as $a) {
        //   $Assignment = new Assignment($a);

        //   if ($flag_startDate) $Assignment->setStartLimit($start_date);
        //   if ($flag_endDate) $Assignment->setFinishLimit($end_date);


        //   foreach ($students as $s) {
        //     $Assignment->addStudent($s);
        //     // $query = 'insert into ax.ax_assignment_student (assignment_id, student_user_id) VALUES (' . $a . ', ' . $s . ')';
        //     // $result = pg_query($dbconnect, $query);
        //   }
        // }
      } else {

        foreach ($students as $s) {
          foreach ($tasknums as $tn) {

            $Assignment = new Assignment($tn, 2);

            if ($flag_startDate) $Assignment->setStartLimit($start_date);
            if ($flag_endDate) $Assignment->setFinishLimit($end_date);

            $Assignment->addStudent($s);
          }
        }





        // TODO: Проверить!
        // $assignnum = 0;
        // foreach ($students as $s) {
        //   foreach ($tasknums as $tn) {
        //     $query = 'insert into ax.ax_assignment(task_id, variant_number, start_limit, finish_limit, ' .
        //       ' status_code, status, delay, status_text, mark) values ' .
        //       ' (' . $tn . ', null, null, ' . (($tilltime == "") ? 'null' : $tilltime) .
        //       ' , 2, 0, 0, \'Доступно для просмотра\', null) returning id;';
        //     $result = pg_query($dbconnect, $query);


        //     if ($row = pg_fetch_assoc($result)) {
        //       $assignnum = $row['id'];
        //       $query = 'insert into ax.ax_assignment_student (assignment_id, student_user_id) VALUES (' . $assignnum . ', ' . $s . ')';
        //       $result = pg_query($dbconnect, $query);
        //     }
        //   }
        // }
      }

      header('Location:preptasks.php?page=' . $_REQUEST['page']);
      break;
    }
  case "archive": {
      $page_id = $_REQUEST['page'];

      $tasknums = explode(',', @$_REQUEST['tasknum']);
      if (!$tasknums || count($tasknums) < 1 || @$_REQUEST['tasknum'] == "") {
        echo "Не выбраны задания";
        http_response_code(400);
        exit;
      }

      $query = 'update ax.ax_task set status = 0 where id in (' . implode(',', $tasknums) . ')';
      $result = pg_query($dbconnect, $query);

      header('Location:preptasks.php?page=' . $_REQUEST['page']);
      break;
    }
  case "delete": {
      $page_id = $_REQUEST['page'];

      $tasknums = explode(',', @$_REQUEST['tasknum']);
      if (!$tasknums || count($tasknums) < 1 || @$_REQUEST['tasknum'] == "") {
        echo "Не выбраны задания";
        http_response_code(400);
        exit;
      }

      foreach ($tasknums as $task_id) {
        $Task = new Task((int)$task_id);
        $Task->deleteFromDB();
      }

      header('Location:preptasks.php?page=' . $_REQUEST['page']);
      break;
    }
  case "recover": {
      $page_id = $_REQUEST['page'];

      $tasknums = explode(',', @$_REQUEST['tasknum']);
      if (!$tasknums || count($tasknums) < 1 || @$_REQUEST['tasknum'] == "") {
        echo "Не выбраны задания";
        http_response_code(400);
        exit;
      }

      $query = 'update ax.ax_task set status = 1 where id in (' . implode(',', $tasknums) . ')';
      $result = pg_query($dbconnect, $query);

      header('Location:preptasks.php?page=' . $_REQUEST['page']);
      break;
    }
  default: {
      echo "Некорректное обращение, не существующая операция";
      http_response_code(400);
      exit;
    }
}

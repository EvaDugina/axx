<?php
/*	var_dump($_POST);
	exit; */

require_once("common.php");
require_once("utilities.php");

if (isset($_POST['changeVisibility'])) {

  $return_values = array();

  // changeVisibility для одного Assignment
  if (isset($_POST['assignment_id'])) {
    $Assignment = new Assignment((int)$_POST['assignment_id']);
    if ($_POST['changeVisibility'] == 'delete')
      $Assignment->deleteFromDB();
    else
      $Assignment->setVisibility((int)$_POST['changeVisibility']);

    $return_value = array(
      "assignment_id" => $Assignment->id,
      "svg" => getSVGByAssignmentVisibilityAsText($Assignment->visibility),
      "next_visibility" => $Assignment->getNextAssignmentVisibility(),
      "visibility_to_text" => visibility_to_text($Assignment->getNextAssignmentVisibility())
    );

    array_push($return_values, $return_value);
  }

  // changeVisibility для всех Assignments
  else if (isset($_POST['task_id'])) {
    $Task = new Task((int)$_POST['task_id']);
    foreach ($Task->getAssignments() as $Assignment) {
      if ($_POST['changeVisibility'] == 'delete')
        $Assignment->deleteFromDB();
      else
        $Assignment->setVisibility((int)$_POST['changeVisibility']);

      $return_value = array(
        "assignment_id" => $Assignment->id,
        "svg" => getSVGByAssignmentVisibilityAsText($Assignment->visibility),
        "next_visibility" => $Assignment->getNextAssignmentVisibility(),
        "visibility_to_text" => visibility_to_text($Assignment->getNextAssignmentVisibility())
      );

      array_push($return_values, $return_value);
    }
  }

  echo json_encode($return_values);
  exit;
}

if (isset($_POST['changeStatus'])) {

  $return_values = array();

  // changeStatus для одного Assignment
  if (isset($_POST['assignment_id'])) {
    $Assignment = new Assignment((int)$_POST['assignment_id']);
    if ($_POST['changeStatus'] == 'delete')
      $Assignment->deleteFromDB();
    else {
      if ($Assignment->mark != null)
        $Assignment->setStatus(2);
      else
        $Assignment->setStatus((int)$_POST['changeStatus']);
    }
    $return_value = array(
      "assignment_id" => $Assignment->id,
      "svg" => getSVGByAssignmentStatusAsText($Assignment->status),
      "next_status" => $Assignment->getNextAssignmentStatus(),
      "status_to_text" => status_to_text($Assignment->getNextAssignmentStatus())
    );

    array_push($return_values, $return_value);
  }

  // changeStatus для всех Assignments
  else if (isset($_POST['task_id'])) {
    $Task = new Task((int)$_POST['task_id']);
    foreach ($Task->getAssignments() as $Assignment) {
      if ($_POST['changeStatus'] == 'delete')
        $Assignment->deleteFromDB();
      else {
        if ($Assignment->mark == null) {
          $Assignment->setStatus((int)$_POST['changeStatus']);
          $return_value = array(
            "assignment_id" => $Assignment->id,
            "svg" => getSVGByAssignmentStatusAsText($Assignment->status),
            "next_status" => $Assignment->getNextAssignmentStatus(),
            "status_to_text" => status_to_text($Assignment->getNextAssignmentStatus())
          );
          array_push($return_values, $return_value);
        }
      }
    }
  }

  echo json_encode($return_values);
  exit;
}

if (isset($_POST['createGeneralConversation'])) {
  $Page = new Page((int)$_POST['page_id']);
  $Task = $Page->createGeneralConversation();
  $Task->setTitle("Беседа со всеми пользователями курса");
  header("Location: taskchat.php?assignment=" . $Task->getConversationAssignment()->id);
  exit();
}


if ((isset($_POST['action']) && $_POST['action'] == "save")) {
  // if (!array_key_exists("assignment_id", $_POST) || !array_key_exists("from", $_POST)) {
  //   http_response_code(401);
  //   die("Неверное обращение");
  // }

  if (isset($_POST['flag-createAssignment']) && isset($_POST['task_id'])) {
    $Assignment = new Assignment($_POST['task_id'], 2);
  } else if (isset($_POST['assignment_id'])) {
    $Assignment = new Assignment((int)$_POST['assignment_id']);
  } else {
    echo "Некорректное обращение, не распознан Assignment";
    http_response_code(401);
    exit();
  }

  $params = array(
    "tools" => array(
      "build" => array(
        "enabled" => str2bool(@$_POST["build_enabled"]),
        "show_to_student" => str2bool(@$_POST["build_show"]),
        "language" => @$_POST["build_language"],
        "check" => array("autoreject" => str2bool(@$_POST["build_autoreject"]))
      ),
      "valgrind" => array(
        "enabled" => str2bool(@$_POST["valgrind_enabled"]),
        "show_to_student" => str2bool(@$_POST["valgrind_show"]),
        "bin" => "valgrind",
        "arguments" => @$_POST["valgrind_arg"],
        "compiler" => @$_POST["valgrind_compiler"],
        "checks" => array(
          array(
            "check" => "errors",
            "enabled" => str2bool(@$_POST["valgrind_errors"]),
            "limit" => str2int(@$_POST["valgrind_errors_limit"]),
            "autoreject" => str2bool(@$_POST["valgrind_errors_reject"])
          ),
          array(
            "check" => "leaks",
            "enabled" => str2bool(@$_POST["valgrind_leaks"]),
            "limit" => str2int(@$_POST["valgrind_leaks_limit"]),
            "autoreject" => str2bool(@$_POST["valgrind_leaks_reject"])
          )
        )
      ),
      "cppcheck" => array(
        "enabled" => str2bool(@$_POST["cppcheck_enabled"]),
        "show_to_student" => str2bool(@$_POST["cppcheck_show"]),
        "bin" => "cppcheck",
        "arguments" => @$_POST["cppcheck_arg"],
        "checks" => array(
          array(
            "check" => "error",
            "enabled" => str2bool(@$_POST["cppcheck_error"]),
            "limit" => str2int(@$_POST["cppcheck_error_limit"]),
            "autoreject" => str2bool(@$_POST["cppcheck_error_reject"])
          ),
          array(
            "check" => "warning",
            "enabled" => str2bool(@$_POST["cppcheck_warning"]),
            "limit" => str2int(@$_POST["cppcheck_warning_limit"]),
            "autoreject" => str2bool(@$_POST["cppcheck_warning_reject"])
          ),
          array(
            "check" => "style",
            "enabled" => str2bool(@$_POST["cppcheck_style"]),
            "limit" => str2int(@$_POST["cppcheck_style_limit"]),
            "autoreject" => str2bool(@$_POST["cppcheck_style_reject"])
          ),
          array(
            "check" => "performance",
            "enabled" => str2bool(@$_POST["cppcheck_performance"]),
            "limit" => str2int(@$_POST["cppcheck_performance_limit"]),
            "autoreject" => str2bool(@$_POST["cppcheck_performance_reject"])
          ),
          array(
            "check" => "portability",
            "enabled" => str2bool(@$_POST["cppcheck_portability"]),
            "limit" => str2int(@$_POST["cppcheck_portability_limit"]),
            "autoreject" => str2bool(@$_POST["cppcheck_portability_reject"])
          ),
          array(
            "check" => "information",
            "enabled" => str2bool(@$_POST["cppcheck_information"]),
            "limit" => str2int(@$_POST["cppcheck_information_limit"]),
            "autoreject" => str2bool(@$_POST["cppcheck_information_reject"])
          ),
          array(
            "check" => "unusedFunction",
            "enabled" => str2bool(@$_POST["cppcheck_unused"]),
            "limit" => str2int(@$_POST["cppcheck_unused_limit"]),
            "autoreject" => str2bool(@$_POST["cppcheck_unused_reject"])
          ),
          array(
            "check" => "missingInclude",
            "enabled" => str2bool(@$_POST["cppcheck_include"]),
            "limit" => str2int(@$_POST["cppcheck_include_limit"]),
            "autoreject" => str2bool(@$_POST["cppcheck_include_reject"])
          )
        )
      ),
      "clang-format" => array(
        "enabled" => str2bool(@$_POST["clang_enabled"]),
        "show_to_student" => str2bool(@$_POST["clang_show"]),
        "bin" => "clang-format",
        "arguments" => @$_POST["clang_arg"],
        "check" => array(
          "level" => @$_POST["clang-config"],
          "file" => "",
          "limit" => str2int(@$_POST["clang_errors_limit"]),
          "autoreject" => str2bool(@$_POST["clang_errors_reject"])
        )
      ),
      "pylint" => array(
        "enabled" => str2bool(@$_POST["pylint_enabled"]),
        "show_to_student" => str2bool(@$_POST["pylint_show"]),
        "bin" => "pylint",
        "arguments" => @$_POST["pylint_arg"],
        "checks" => array(
          array(
            "check" => "error",
            "enabled" => str2bool(@$_POST["pylint_error"]),
            "limit" => str2int(@$_POST["pylint_error_limit"]),
            "autoreject" => str2bool(@$_POST["pylint_error_reject"])
          ),
          array(
            "check" => "warning",
            "enabled" => str2bool(@$_POST["pylint_warning"]),
            "limit" => str2int(@$_POST["pylint_warning_limit"]),
            "autoreject" => str2bool(@$_POST["pylint_warning_reject"])
          ),
          array(
            "check" => "refactor",
            "enabled" => str2bool(@$_POST["pylint_refactor"]),
            "limit" => str2int(@$_POST["pylint_refactor_limit"]),
            "autoreject" => str2bool(@$_POST["pylint_refactor_reject"])
          ),
          array(
            "check" => "convention",
            "enabled" => str2bool(@$_POST["pylint_convention"]),
            "limit" => str2int(@$_POST["pylint_convention_limit"]),
            "autoreject" => str2bool(@$_POST["pylint_convention_reject"])
          )
        )
      ),
      "pytest" => array(
        "enabled" => str2bool(@$_POST["pytest_enabled"]),
        "show_to_student" => str2bool(@$_POST["pytest_show"]),
        "test_path" => "autotest.py",
        "bin" => "pytest",
        "arguments" => @$_POST["pytest_arg"],
        "check" => array(
          "limit" => str2int(@$_POST["pytest_check_limit"]),
          "autoreject" => str2bool(@$_POST["pytest_check_reject"])
        )
      ),
      "autotests" => array(
        "enabled" => str2bool(@$_POST["test_enabled"]),
        "show_to_student" => str2bool(@$_POST["test_show"]),
        "test_path" => "autotest.cpp",
        "check" => array(
          "limit" => str2int(@$_POST["test_check_limit"]),
          "autoreject" => str2bool(@$_POST["test_check_reject"])
        )
      ),
      "copydetect" => array(
        "enabled" => str2bool(@$_POST["plug_enabled"]),
        "show_to_student" => str2bool(@$_POST["plug_show"]),
        "bin" => "copydetect",
        "arguments" => @$_POST["plug_arg"],
        "check" => array(
          "type" => @$_POST["plug_config"],
          "limit" => str2int(@$_POST["plug_check_limit"]),
          "autoreject" => str2bool(@$_POST["plug_check_reject"])
        )
      )
    )
  );

  $json = json_encode($params);
  //header('Content-Type: application/json');
  //echo $json;

  // $query = 'update ax.ax_assignment set start_limit = '.
  // ($_POST['fromtime'] == "" ?"null" :"to_timestamp('".$_POST['fromtime']." 00:00:00', 'YYYY-MM-DD HH24:MI:SS')").
  // " , finish_limit = ".($_POST['tilltime'] == "" ?"null" :"to_timestamp('".$_POST['tilltime']." 23:59:59', 'YYYY-MM-DD HH24:MI:SS')").
  // ' , variant_number=$accel$'.$_POST['variant'].'$accel$ '.
  // " where id = ".$_POST['assignment_id'];
  //   $result = pg_query($dbconnect, $query);


  if (isset($_POST['fromtime']) && $_POST['fromtime'] != "") {
    $date = strtotime($_POST['fromtime']);
    if (isset($_POST['start_time'])) {
      $str = $_POST['fromtime'] . " " . $_POST['start_time'] . ":00";
      $date = strtotime($str);
    }
    $date_start = date("Y-m-d H:i:s", $date);
    $Assignment->setStartLimit($date_start);
  }


  if (isset($_POST['tilltime']) && $_POST['tilltime'] != "") {
    $date = strtotime($_POST['tilltime']);
    if (isset($_POST['end_time'])) {
      $str = $_POST['tilltime'] . " " . $_POST['end_time'] . ":00";
      $date = strtotime($str);
    }
    $Assignment->setFinishLimit(date("Y-m-d H:i:s", $date));
  }

  if (isset($_POST['variant']) && $_POST['variant'] != "")
    $Assignment->setVariantNumber($_POST['variant']);

  $query = "UPDATE ax.ax_assignment SET checks = \$accel\$$json\$accel\$ WHERE id = $Assignment->id";
  $result = pg_query($dbconnect, $query);

  $result = pg_query($dbconnect, "delete from ax.ax_assignment_student where assignment_id=" . $Assignment->id);

  foreach ($_POST['students'] as $sid)
    $result = pg_query($dbconnect, "insert into ax.ax_assignment_student (assignment_id, student_user_id) values (" . $Assignment->id . ", " . $sid . ")");


  if (isset($_POST['from']))
    header('Location:' . $_POST['from']);
  else if (isset($_POST['page_id']))
    header('Location: preptasks.php?page=' . $_POST['page_id']);
  else
    header('Location: taskassign.php?assignment_id=' . $Assignment->id);
  exit();
}

if (isset($_POST['flag-markAssignment']) && isset($_POST['assignment_id']) && isset($_POST['mark']) && isset($_POST['user_id'])) {
  $User = new User($_POST['user_id']);
  $Assignment = new Assignment($_POST['assignment_id']);

  $mark = $_POST['mark'];
  $Assignment->setMark($mark);

  $message_text = getMessageAssignmentCompleted($mark);
  if (isset($_POST['message_text']) && $_POST['message_text'] != "") {
    $message_text = "Комментарий проеподавателя: \n" + $_POST['message_text'];
  }

  $Message = new Message((int)$Assignment->id, 2, $User->id, $User->role);
  $Message->setFullText($message_text);
  $Assignment->addMessage($Message->id);

  echo getSVGByAssignmentStatus($Assignment->status);
  exit();
}

if (isset($_POST['flag-loadChecksPreset']) && isset($_POST['language_key']) && isset($_POST['current_checks'])) {
  $language_key = $_POST['language_key'];

  $checkParams = getLanguagesChecksParams();
  if ($language_key == "current") {
    $checks = $_POST['current_checks'];
  } else if (isset($checkParams[$language_key])) {
    $checks = $checkParams[$language_key]['checks_preset'];
  } else
    exit;

  $checks = json_decode($checks, true);
  $accord = getChecksAccordion($checks);
  show_accordion('checks', $accord, "310px");
  exit;
}

header('Location:' . $_SERVER['HTTP_REFERER']);
exit();

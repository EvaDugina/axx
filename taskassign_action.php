<?php
/*	var_dump($_POST);
	exit; */
	
	require_once("common.php");	
	require_once("utilities.php");	
	
	
  if(isset($_POST['changeStatus'])) {

	// changeStatus для одного Assignment
	if(isset($_POST['assignment_id'])) {
		$Assignment = new Assignment((int)$_POST['assignment_id']);
		if ($_POST['changeStatus'] == 'delete')
			$Assignment->deleteFromDB();
		else 
			$Assignment->setVisibility((int)$_POST['changeStatus']);
	} 
	
	// changeStatus для всех Assignments
	else if (isset($_POST['task_id'])) {
		$Task = new Task((int)$_POST['task_id']);
		foreach($Task->getAssignments() as $Assignment) {
			if ($_POST['changeStatus'] == 'delete')
				$Assignment->deleteFromDB();
			else 
				$Assignment->setVisibility((int)$_POST['changeStatus']);
		}
	}
    exit;
  }

  
	if (!array_key_exists("assignment_id", $_POST) || !array_key_exists("from", $_POST)) {
		http_response_code(401);
		die("Неверное обращение");
	}
	
	$params = array("tools" => array("build" => array("enabled" => str2bool(@$_POST["build_enabled"]),
													  "show_to_student" => str2bool(@$_POST["build_show"]),
													  "language" => @$_POST["build_language"],
													  "check" => array("autoreject" => str2bool(@$_POST["build_autoreject"]))
													 ),
									 "valgrind" => array("enabled" => str2bool(@$_POST["valgrind_enabled"]),
														 "show_to_student" => str2bool(@$_POST["valgrind_show"]),
														 "bin" => "valgrind", 
														 "arguments" => @$_POST["valgrind_arg"],
														 "compiler" => @$_POST["valgrind_compiler"], 
														 "checks" => array(array("check" => "errors",
																				 "enabled" => str2bool(@$_POST["valgrind_errors"]),
																				 "limit" => str2int(@$_POST["valgrind_errors_limit"]),
																				 "autoreject" => str2bool(@$_POST["valgrind_errors_reject"])
																				),
																		   array("check" => "leaks",
																				 "enabled" => str2bool(@$_POST["valgrind_leaks"]),
																				 "limit" => str2int(@$_POST["valgrind_leaks_limit"]),
																				 "autoreject" => str2bool(@$_POST["valgrind_leaks_reject"])
																				)	
																		  )
														),
									 "cppcheck" => array("enabled" => str2bool(@$_POST["cppcheck_enabled"]),
														 "show_to_student" => str2bool(@$_POST["cppcheck_show"]),
														 "bin" => "cppcheck", 
														 "arguments" => @$_POST["cppcheck_arg"],
														 "checks" => array(array("check" => "error",
																				 "enabled" => str2bool(@$_POST["cppcheck_error"]),
																				 "limit" => str2int(@$_POST["cppcheck_error_limit"]),
																				 "autoreject" => str2bool(@$_POST["cppcheck_error_reject"])
																				),
																		   array("check" => "warning",
																				 "enabled" => str2bool(@$_POST["cppcheck_warning"]),
																				 "limit" => str2int(@$_POST["cppcheck_warning_limit"]),
																				 "autoreject" => str2bool(@$_POST["cppcheck_warning_reject"])
																				),
																			array("check" => "style",
																				 "enabled" => str2bool(@$_POST["cppcheck_style"]),
																				 "limit" => str2int(@$_POST["cppcheck_style_limit"]),
																				 "autoreject" => str2bool(@$_POST["cppcheck_style_reject"])
																				),																				
																			array("check" => "performance",
																				 "enabled" => str2bool(@$_POST["cppcheck_performance"]),
																				 "limit" => str2int(@$_POST["cppcheck_performance_limit"]),
																				 "autoreject" => str2bool(@$_POST["cppcheck_performance_reject"])
																				),
																			array("check" => "portability",
																				 "enabled" => str2bool(@$_POST["cppcheck_portability"]),
																				 "limit" => str2int(@$_POST["cppcheck_portability_limit"]),
																				 "autoreject" => str2bool(@$_POST["cppcheck_portability_reject"])
																				),
																			array("check" => "information",
																				 "enabled" => str2bool(@$_POST["cppcheck_information"]),
																				 "limit" => str2int(@$_POST["cppcheck_information_limit"]),
																				 "autoreject" => str2bool(@$_POST["cppcheck_information_reject"])
																				),
																			array("check" => "unusedFunction",
																				 "enabled" => str2bool(@$_POST["cppcheck_unused"]),
																				 "limit" => str2int(@$_POST["cppcheck_unused_limit"]),
																				 "autoreject" => str2bool(@$_POST["cppcheck_unused_reject"])
																				),
																			array("check" => "missingInclude",
																				 "enabled" => str2bool(@$_POST["cppcheck_include"]),
																				 "limit" => str2int(@$_POST["cppcheck_include_limit"]),
																				 "autoreject" => str2bool(@$_POST["cppcheck_include_reject"])
																				)
																		  )
														),
									 "clang-format" => array("enabled" => str2bool(@$_POST["clang_enabled"]),
															 "show_to_student" => str2bool(@$_POST["clang_show"]),
															 "bin" => "clang-format", 
															 "arguments" => @$_POST["clang_arg"],
															 "check" => array("level" => @$_POST["clang-config"],
																			  "file" => "",
																			  "limit" => str2int(@$_POST["clang_errors_limit"]),
																			  "autoreject" => str2bool(@$_POST["clang_errors_reject"])
																			 )
															),
									 "autotests" => array("enabled" => str2bool(@$_POST["test_enabled"]),
															"show_to_student" => str2bool(@$_POST["test_show"]),
															"test_path" => "accel_autotest.cpp",
															"check" => array("limit" => str2int(@$_POST["test_check_limit"]),
																			 "autoreject" => str2bool(@$_POST["test_check_reject"])
																			)
														   ),
									"copydetect" => array("enabled" => str2bool(@$_POST["plug_enabled"]),
															 "show_to_student" => str2bool(@$_POST["plug_show"]),
															 "bin" => "copydetect", 
															 "arguments" => @$_POST["plug_arg"],
															 "check" => array("type" => @$_POST["plug_config"],
																			  "limit" => str2int(@$_POST["plug_check_limit"]),
																			  "autoreject" => str2bool(@$_POST["plug_check_reject"])
																			 )
														  )
									)
					);

	$json = json_encode($params);
	//header('Content-Type: application/json');
	//echo $json;

  // $query = 'update ax_assignment set start_limit = '.
  // ($_POST['fromtime'] == "" ?"null" :"to_timestamp('".$_POST['fromtime']." 00:00:00', 'YYYY-MM-DD HH24:MI:SS')").
  // " , finish_limit = ".($_POST['tilltime'] == "" ?"null" :"to_timestamp('".$_POST['tilltime']." 23:59:59', 'YYYY-MM-DD HH24:MI:SS')").
  // ' , variant_number=$accel$'.$_POST['variant'].'$accel$ '.
  // " where id = ".$_POST['assignment_id'];
  //   $result = pg_query($dbconnect, $query);
  
  $Assignment = new Assignment((int)$_POST['assignment_id']);

  if (isset($_POST['fromtime']) && $_POST['fromtime'] != "")
    $Assignment->setStartLimit($_POST['fromtime']);

  if (isset($_POST['tilltime']) && $_POST['tilltime'] != "")
    $Assignment->setFinishLimit($_POST['tilltime']);
  
  if (isset($_POST['variant']) && $_POST['variant'] != "")
    $Assignment->setVariantNumber($_POST['variant']);

  $query = 'update ax_assignment set checks = $accel$'.$json.'$accel$';
  $result = pg_query($dbconnect, $query);

	$result = pg_query($dbconnect, "delete from ax_assignment_student where assignment_id=".$_POST['assignment_id']);

	foreach ($_POST['students'] as $sid)
		$result = pg_query($dbconnect, "insert into ax_assignment_student (assignment_id, student_user_id) values (".$_POST['assignment_id'].", ".$sid.")");
	
	
	header('Location:'.$_POST['from']);
?>
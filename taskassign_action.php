<?php
/*	var_dump($_POST);
	exit; */
	
	require_once("common.php");	
	
	if (!array_key_exists("assignment_id", $_POST) || !array_key_exists("from", $_POST)) {
		http_response_code(401);
		die("Неверное обращение");
	}
	
	$params = array("tools" => array("valgrind" => array("enabled" => (@$_POST["valgrind_enabled"] == "true") ?"true" :"false",
														 "show_to_student" => (@$_POST["valgrind_show"] == "true") ?"true" :"false",
														 "bin" => "valgrind", 
														 "arguments" => @$_POST["valgrind_arg"],
														 "compiler" => @$_POST["valgrind_compiler"], 
														 "checks" => array(array("check" => "errors",
																				 "enabled" => (@$_POST["valgrind_errors"] == "true") ?"true" :"false",
																				 "limit" => @$_POST["valgrind_errors_limit"],
																				 "autoreject" => (@$_POST["valgrind_errors_reject"] == "true") ?"true" :"false"
																				),
																		   array("check" => "leaks",
																				 "enabled" => (@$_POST["valgrind_leaks"] == "true") ?"true" :"false",
																				 "limit" => @$_POST["valgrind_leaks_limit"],
																				 "autoreject" => (@$_POST["valgrind_leaks_reject"] == "true") ?"true" :"false"
																				)	
																		  )
														),
									 "cppcheck" => array("enabled" => (@$_POST["cppcheck_enabled"] == "true") ?"true" :"false",
														 "show_to_student" => (@$_POST["cppcheck_show"] == "true") ?"true" :"false",
														 "bin" => "cppcheck", 
														 "arguments" => @$_POST["cppcheck_arg"],
														 "checks" => array(array("check" => "error",
																				 "enabled" => (@$_POST["cppcheck_error"] == "true") ?"true" :"false",
																				 "limit" => @$_POST["cppcheck_error_limit"],
																				 "autoreject" => (@$_POST["cppcheck_error_reject"] == "true") ?"true" :"false"
																				),
																		   array("check" => "warning",
																				 "enabled" => (@$_POST["cppcheck_warning"] == "true") ?"true" :"false",
																				 "limit" => @$_POST["cppcheck_warning_limit"],
																				 "autoreject" => (@$_POST["cppcheck_warning_reject"] == "true") ?"true" :"false"
																				),
																			array("check" => "style",
																				 "enabled" => (@$_POST["cppcheck_style"] == "true") ?"true" :"false",
																				 "limit" => @$_POST["cppcheck_style_limit"],
																				 "autoreject" => (@$_POST["cppcheck_style_reject"] == "true") ?"true" :"false"
																				),																				
																			array("check" => "performance",
																				 "enabled" => (@$_POST["cppcheck_performance"] == "true") ?"true" :"false",
																				 "limit" => @$_POST["cppcheck_performance_limit"],
																				 "autoreject" => (@$_POST["cppcheck_performance_reject"] == "true") ?"true" :"false"
																				),
																			array("check" => "portability",
																				 "enabled" => (@$_POST["cppcheck_portability"] == "true") ?"true" :"false",
																				 "limit" => @$_POST["cppcheck_portability_limit"],
																				 "autoreject" => (@$_POST["cppcheck_portability_reject"] == "true") ?"true" :"false"
																				),
																			array("check" => "information",
																				 "enabled" => (@$_POST["cppcheck_information"] == "true") ?"true" :"false",
																				 "limit" => @$_POST["cppcheck_information_limit"],
																				 "autoreject" => (@$_POST["cppcheck_information_reject"] == "true") ?"true" :"false"
																				),
																			array("check" => "unusedFunction",
																				 "enabled" => (@$_POST["cppcheck_unused"] == "true") ?"true" :"false",
																				 "limit" => @$_POST["cppcheck_unused_limit"],
																				 "autoreject" => (@$_POST["cppcheck_unused_reject"] == "true") ?"true" :"false"
																				),
																			array("check" => "missingInclude",
																				 "enabled" => (@$_POST["cppcheck_include"] == "true") ?"true" :"false",
																				 "limit" => @$_POST["cppcheck_include_limit"],
																				 "autoreject" => (@$_POST["cppcheck_include_reject"] == "true") ?"true" :"false"
																				)
																		  )
														),
									 "clang-format" => array("enabled" => (@$_POST["clang_enabled"] == "true") ?"true" :"false",
															 "show_to_student" => (@$_POST["clang_show"] == "true") ?"true" :"false",
															 "bin" => "clang-format", 
															 "arguments" => @$_POST["clang_arg"],
															 "check" => array("level" => @$_POST["clang-config"],
																			  "file" => "",
																			  "limit" => @$_POST["clang_errors_limit"],
																			  "autoreject" => (@$_POST["clang_errors_reject"] == "true") ?"true" :"false"
																			 )
															),
									 "autotests" => array("enabled" => (@$_POST["test_enabled"] == "true") ?"true" :"false",
															"show_to_student" => (@$_POST["test_show"] == "true") ?"true" :"false",
															"language" => @$_POST["language"],
															"test_path" => "accel_autotest.cpp",
															"check" => array("limit" => @$_POST["test_check_limit"],
																			 "autoreject" => (@$_POST["test_check_reject"] == "true") ?"true" :"false"
																			)
														   ),
									"copydetect" => array("enabled" => (@$_POST["plug_enabled"] == "true") ?"true" :"false",
															 "show_to_student" => (@$_POST["plug_show"] == "true") ?"true" :"false",
															 "bin" => "copydetect", 
															 "arguments" => @$_POST["plug_arg"],
															 "check" => array("type" => @$_POST["plug_config"],
																			  "limit" => @$_POST["plug_check_limit"],
																			  "autoreject" => (@$_POST["plug_check_reject"] == "true") ?"true" :"false"
																			 )
														  )
									)
					);

	$json = json_encode($params);
	//header('Content-Type: application/json');
	//echo $json;

    $result = pg_query($dbconnect, 'update ax_assignment set checks = $accel$'.$json.'$accel$, start_limit = '.
									($_POST['fromtime'] == "" ?"null" :"to_timestamp('".$_POST['fromtime']." 00:00:00', 'YYYY-MM-DD HH24:MI:SS')").
									" , finish_limit = ".($_POST['tilltime'] == "" ?"null" :"to_timestamp('".$_POST['tilltime']." 23:59:59', 'YYYY-MM-DD HH24:MI:SS')").
									' , variant_comment=$accel$'.$_POST['variant'].'$accel$ '.
									" where id = ".$_POST['assignment_id']);

	$result = pg_query($dbconnect, "delete from ax_assignment_student where assignment_id=".$_POST['assignment_id']);

	foreach ($_POST['students'] as $sid)
		$result = pg_query($dbconnect, "insert into ax_assignment_student (assignment_id, student_user_id) values (".$_POST['assignment_id'].", ".$sid.")");
	
	
	header('Location:'.$_POST['from']);
?>
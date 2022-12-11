<?php
//	var_dump($_POST);
	
	if (!array_key_exists("assignment_id", $_POST) || !array_key_exists("from", $_POST)) {
		http_response_code(401);
		die("Неверное обращение");
	}
	
	$params = array("tools" => array("valgrind" => array("enabled" => (@$_POST["valgrind_enabled"] == "1") ?"true" :"false",
														 "show_to_student" => (@$_POST["valgrind_show"] == "1") ?"true" :"false",
														 "bin" => "valgrind", 
														 "arguments" => @$_POST["valgrind_arg"],
														 "compiler" => @$_POST["valgrind_compiler"], 
														 "checks" => array(array("check" => "errors",
																				 "enabled" => (@$_POST["valgrind_errors"] == "1") ?"true" :"false",
																				 "limit" => @$_POST["valgrind_errors_limit"],
																				 "autoreject" => (@$_POST["valgrind_errors_reject"] == "1") ?"true" :"false"
																				),
																		   array("check" => "leaks",
																				 "enabled" => (@$_POST["valgrind_leaks"] == "1") ?"true" :"false",
																				 "limit" => @$_POST["valgrind_leaks_limit"],
																				 "autoreject" => (@$_POST["valgrind_leaks_reject"] == "1") ?"true" :"false"
																				)	
																		  )
														),
									 "cppcheck" => array("enabled" => (@$_POST["cppcheck_enabled"] == "1") ?"true" :"false",
														 "show_to_student" => (@$_POST["cppcheck_show"] == "1") ?"true" :"false",
														 "bin" => "cppcheck", 
														 "arguments" => @$_POST["cppcheck_arg"],
														 "checks" => array(array("check" => "error",
																				 "enabled" => (@$_POST["cppcheck_error"] == "1") ?"true" :"false",
																				 "limit" => @$_POST["cppcheck_error_limit"],
																				 "autoreject" => (@$_POST["cppcheck_error_reject"] == "1") ?"true" :"false"
																				),
																		   array("check" => "warning",
																				 "enabled" => (@$_POST["cppcheck_warning"] == "1") ?"true" :"false",
																				 "limit" => @$_POST["cppcheck_warning_limit"],
																				 "autoreject" => (@$_POST["cppcheck_warning_reject"] == "1") ?"true" :"false"
																				),
																			array("check" => "style",
																				 "enabled" => (@$_POST["cppcheck_style"] == "1") ?"true" :"false",
																				 "limit" => @$_POST["cppcheck_style_limit"],
																				 "autoreject" => (@$_POST["cppcheck_style_reject"] == "1") ?"true" :"false"
																				),																				
																			array("check" => "performance",
																				 "enabled" => (@$_POST["cppcheck_performance"] == "1") ?"true" :"false",
																				 "limit" => @$_POST["cppcheck_performance_limit"],
																				 "autoreject" => (@$_POST["cppcheck_performance_reject"] == "1") ?"true" :"false"
																				),
																			array("check" => "portability",
																				 "enabled" => (@$_POST["cppcheck_portability"] == "1") ?"true" :"false",
																				 "limit" => @$_POST["cppcheck_portability_limit"],
																				 "autoreject" => (@$_POST["cppcheck_portability_reject"] == "1") ?"true" :"false"
																				),
																			array("check" => "information",
																				 "enabled" => (@$_POST["cppcheck_information"] == "1") ?"true" :"false",
																				 "limit" => @$_POST["cppcheck_information_limit"],
																				 "autoreject" => (@$_POST["cppcheck_information_reject"] == "1") ?"true" :"false"
																				),
																			array("check" => "unusedFunction",
																				 "enabled" => (@$_POST["cppcheck_unused"] == "1") ?"true" :"false",
																				 "limit" => @$_POST["cppcheck_unused_limit"],
																				 "autoreject" => (@$_POST["cppcheck_unused_reject"] == "1") ?"true" :"false"
																				),
																			array("check" => "missingInclude",
																				 "enabled" => (@$_POST["cppcheck_include"] == "1") ?"true" :"false",
																				 "limit" => @$_POST["cppcheck_include_limit"],
																				 "autoreject" => (@$_POST["cppcheck_include_reject"] == "1") ?"true" :"false"
																				)
																		  )
														),
									 "clang-format" => array("enabled" => (@$_POST["clang_enabled"] == "1") ?"true" :"false",
															 "show_to_student" => (@$_POST["clang_show"] == "1") ?"true" :"false",
															 "bin" => "clang-format", 
															 "arguments" => @$_POST["clang_arg"],
															 "check" => array("level" => @$_POST["clang-config"],
																			  "file" => "",
																			  "limit" => @$_POST["clang_errors_limit"],
																			  "autoreject" => (@$_POST["clang_errors_reject"] == "1") ?"true" :"false"
																			 )
															),
									 "copydetect" => array("enabled" => (@$_POST["plug_enabled"] == "1") ?"true" :"false",
															 "show_to_student" => (@$_POST["plug_show"] == "1") ?"true" :"false",
															 "bin" => "copydetect", 
															 "arguments" => @$_POST["plug_arg"],
															 "check" => array("type" => @$_POST["plug_config"],
																			  "limit" => @$_POST["plug_check_limit"],
																			  "autoreject" => (@$_POST["plug_check_reject"] == "1") ?"true" :"false"
																			 )
														  )
									)
					);

	$json = json_encode($params);
	
	//header('Content-Type: application/json');
	//echo $json;
	
	header('Location:'.$_POST['from']);
?>
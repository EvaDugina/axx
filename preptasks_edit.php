<?php

require_once("common.php");
require_once("dbqueries.php");

if (!array_key_exists('page', $_REQUEST))
{
    echo "Некорректное обращение";
    http_response_code(400);
    exit;
}

/*
var_dump($_REQUEST);
exit;
*/

$action = @$_REQUEST['action'];
switch($action)
{
    case "linkFile":
    {
        if (count($_FILES) != 1)
        {
            echo "Некорректное обращение";
            http_response_code(400);
            exit;            
        }

        $page_id = $_REQUEST['page'];
        $filename = $_FILES['customFile']['name'];
        $filetype = $_FILES['customFile']['type']; // text/plain
        
        $uploaddir = 'upload/';
        $uploadfile = $uploaddir . basename($_FILES['customFile']['name']);
        

        if (!move_uploaded_file($_FILES['customFile']['tmp_name'], $uploadfile))
        {
            echo "Ошибка загрузки файла";
            http_response_code(500);
            exit;   
        }

        $tasknums = explode(',', @$_REQUEST['tasknum']);
        if (count($tasknums) > 0) {
            $query = 'insert into ax_task_file (type, task_id, file_name, download_url, full_text) VALUES ';

            $items = array();
            foreach($tasknums as $tn)
                array_push($items, '(0, '.$tn.', \''.$filename.'\', \''.$uploadfile.'\', null)');
            $query .= implode(',', $items);
		    $result = pg_query($dbconnect, $query);
        }

        /*
            ["name"]=> string(6) "db.txt" 
            ["type"]=> string(10) "text/plain" 
            ["tmp_name"]=> string(24) "C:\xampp\tmp\phpB638.tmp" 
            ["error"]=> int(0)
            ["size"]=> int(2030)
        */

        header('Location:preptasks.php?page='.$_REQUEST['page']);
        break;
    }
    case "assign":
    {
        $students = @$_REQUEST['students'];
        if (!$students || count($students) < 1)
        {
            echo "Не выбраны студенты";
            http_response_code(400);
            exit;            
        }        

        $tilltime = @$_REQUEST['tilltime'];
        $query = select_check_timestamp($tilltime);
        $level = error_reporting();
        error_reporting(E_ERROR);
        $result = pg_query($dbconnect, $query);
        error_reporting($level);
        if (!$result && ($tilltime != ""))
        {
            echo "Неверный формат даты и времени";
            http_response_code(400);
            exit;  
        }

        $group = 0;
        $group = @$_REQUEST['groupped'];

        $tasknums = explode(',', @$_REQUEST['tasknum']);
        if (!$tasknums || count($tasknums) < 1 || @$_REQUEST['tasknum']=="")         
        {
            echo "Не выбраны задания";
            http_response_code(400);
            exit;    
        }

        if ($group == "1") {
            $assignnums = array();

            foreach($tasknums as $tn) {
                $query = 'insert into ax_assignment(task_id, variant_comment, start_limit, finish_limit, '.
                            ' status_code, delay, status_text, mark) values '.
                            ' ('.$tn.', null, null, '.(($tilltime=="") ?'null' :'to_timestamp(\''.$tilltime.'\', \'YYYY-MM-DD HH24:MI:SS\')').
                            ' , 2, 0, \'ожидает выполнения\', null) returning id;';
                $result = pg_query($dbconnect, $query);

                if ($row = pg_fetch_assoc($result))
                    array_push($assignnums, $row['id']);
            }

            foreach($assignnums as $a) {
                foreach($students as $s) {
                    $query = 'insert into ax_assignment_student (assignment_id, student_user_id) VALUES ('.$a.', '.$s.')';
                    $result = pg_query($dbconnect, $query);
                }
            }
        }
        else
        {
            $assignnum = 0;
            foreach($students as $s) {
                foreach($tasknums as $tn) {
                    $query = 'insert into ax_assignment(task_id, variant_comment, start_limit, finish_limit, '.
                                ' status_code, delay, status_text, mark) values '.
                                ' ('.$tn.', null, null, '.(($tilltime=="") ?'null' :'to_timestamp(\''.$tilltime.'\', \'YYYY-MM-DD HH24:MI:SS\')').
                                ' , 2, 0, \'ожидает выполнения\', null) returning id;';
                    $result = pg_query($dbconnect, $query);


                    if ($row = pg_fetch_assoc($result))
                    {
                        $assignnum = $row['id'];
                        $query = 'insert into ax_assignment_student (assignment_id, student_user_id) VALUES ('.$assignnum.', '.$s.')';
                        $result = pg_query($dbconnect, $query);
                    }
                }
            }
        }


        header('Location:preptasks.php?page='.$_REQUEST['page']);
    }
    case "delete":
    {
        $page_id = $_REQUEST['page'];

        $tasknums = explode(',', @$_REQUEST['tasknum']);
        if (!$tasknums || count($tasknums) < 1 || @$_REQUEST['tasknum']=="")         
        {
            echo "Не выбраны задания";
            http_response_code(400);
            exit;    
        }

        $query = 'update ax_task set status = 0 where id in ('.implode(',', $tasknums).')';
        $result = pg_query($dbconnect, $query);
        
        header('Location:preptasks.php?page='.$_REQUEST['page']);
        break;
    }
    case "recover":
    {
        $page_id = $_REQUEST['page'];

        $tasknums = explode(',', @$_REQUEST['tasknum']);
        if (!$tasknums || count($tasknums) < 1 || @$_REQUEST['tasknum']=="")         
        {
            echo "Не выбраны задания";
            http_response_code(400);
            exit;    
        }

        $query = 'update ax_task set status = 1 where id in ('.implode(',', $tasknums).')';
        $result = pg_query($dbconnect, $query);
        
        header('Location:preptasks.php?page='.$_REQUEST['page']);
        break;
    }
    default:
    {
        echo "Некорректное обращение";
        http_response_code(400);
        exit;
    }
}

?>
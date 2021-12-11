<?php

require_once("common.php");

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
		    $result2 = pg_query($dbconnect, $query);
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
    default:
        echo "Некорректное обращение";
        http_response_code(400);
        exit;
}

?>
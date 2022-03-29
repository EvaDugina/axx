<?php 

require_once("../common.php");
require_once("../dbqueries.php");

show_header('Дэшборд студента', array('Дэшборд студента' => 'mainpageSt.php'));

$DB_CONNECTION_STRING = "host=localhost port=5432 dbname=accelerator user=accelerator password=123456"; 
		
// подключение к БД
$dbconnect = pg_connect($DB_CONNECTION_STRING);
//require_once("../common.php");
//require_once("../dbqueries.php");
$result = pg_query($dbconnect, 'select id, short_name, year, semester from ax_page');
$disciplines=pg_fetch_all($result);
$result1=pg_query($dbconnect, 'select count(id) from ax_page');
$disc_count=pg_fetch_all($result1);
$result2=pg_query('select page_id from ax_task');
$task=pg_fetch_all($result2);
$result3=pg_query($dbconnect, 'select count(page_id) from ax_task');
$task_count=pg_fetch_all($result3);
?>

<html> 
    <head>
        <title>Дашборд студента</title>
        <link rel="stylesheet" href="./e.css">
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta http-equiv="x-ua-compatible" content="ie=edge" />
        <!-- MDB -->
    <link rel="stylesheet" href="../css/mdb.min.css" />
    <!-- extra -->
    <link rel="stylesheet" href="../css/accelerator.css" />
        <!-- MDB icon -->
        <link rel="icon" href="img/mdb-favicon.ico" type="image/x-icon" />
        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.2/css/all.css" />
        <!-- Google Fonts Roboto -->
        <link
         rel="stylesheet"
         href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap"
        />
    </head>
    <body>
        <main class="justify-content-start">
            <?php $k = 0;
            $semesters = array(); // key = semester, value = number of disciplines in semester
            while ($k < $disc_count[0]['count']) {
                if (array_key_exists($disciplines[$k]['semester'], $semesters)) {
                    ++$semesters[$disciplines[$k]['semester']][0];
                }
                else {
                    $semesters[$disciplines[$k]['semester']] = [1, array()];
                }
                $semesters[$disciplines[$k]['semester']][1][] = array($disciplines[$k]['short_name'], $disciplines[$k]['id']);
                ++$k;
            }
            $k = 0;
            $tasks = array(); // key = page_id, value = number of tasks
            while($k < $task_count[0]['count']) {
                if (array_key_exists($task[$k]['page_id'], $tasks)) {
                    ++$tasks[$task[$k]['page_id']];
                }
                else {
                    $tasks[$task[$k]['page_id']] = 1;
                }
                ++$k;
            }
            krsort($semesters);
            foreach($semesters as $key => $value) {?>
                <h2 class="row" style="margin-top: 30px; margin-left: 50px;"> <?php echo $key; ?> семестр</h2>
                <div class="container">
                    <div class="row">
                        <?php $k = 0;
                        while ($k < $value[0]) { ?>
                            <div class="col-3">
                                <button onclick="window.open('./d.php')" class="button" >
                                    <span class="discipline"><?php echo $value[1][$k][0];  ?></span><br>
                                    <?php foreach($tasks as $id => $count) {
                                            if ((int)$value[1][$k][1] == $id) { ?>
                                                <span class="text-in-button">Выполнено 1/<?php echo $count; ?></span><br>
                                                <progress class="progress-bar" value="1" max=<?php echo $count; ?> >
                                            <?php }
                                        } ?>
                                </button>
                            </div>
                            <?php ++$k;
                        }?>
                    </div>
                </div>
            <?php } ?>
        </main>
        <script src="./e.js"></script>
    </body>
</html>
<?php 

require_once("../common.php");
require_once("../dbqueries.php");

show_header('Дэшборд студента', array('Дэшборд студента' => 'mainpageSt.php'));

$DB_CONNECTION_STRING = "host=localhost port=5432 dbname=accelerator user=accelerator password=123456"; 
		
// подключение к БД
$dbconnect = pg_connect($DB_CONNECTION_STRING);

$result = pg_query($dbconnect, 'select id, short_name, disc_id, semester from ax_page');
$disciplines=pg_fetch_all($result);
$result1=pg_query($dbconnect, 'select count(id) from ax_page');
$disc_count=pg_fetch_all($result1);

function task_count($discipline_id, $dbconnect) {
    $query = 'select count(page_id) from ax_task where page_id =' .$discipline_id;
    return pg_query($dbconnect, $query);
}

function full_name($discipline_id, $dbconnect) {
    $query = 'select name from discipline where id =' .$discipline_id;
    return pg_query($dbconnect, $query);
}

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
             <?php
                array_multisort(array_column($disciplines, 'semester'), SORT_ASC, $disciplines);
                $now_semester = $disciplines[0]['semester']; // first semster in database after sort function
            ?>
            <h2 class="row" style="margin-top: 30px; margin-left: 50px;"> <?php echo $now_semester; ?> семестр</h2><br>
            <div class="container">
                <div class="row g-5">
                    <?php 
                        $now_semester = 1;
                        foreach($disciplines as $key => $massiv) {
                            if ($now_semester != $disciplines[$key]['semester']) { ?>
                                </div>
                                </div>
                                <?php $now_semester = $disciplines[$key]['semester']; ?>
                                <h2 class="row" style="margin-top: 30px; margin-left: 50px;"> <?php echo $now_semester; ?> семестр</h2><br>
                                <div class="container">
                                <div class="row g-5">
                            <?php } ?>
                            <div class="col-3">
                                <?php 
                                $result = task_count($disciplines[$key]['id'], $dbconnect);
                                $task_count = pg_fetch_all($result);
                                $result = full_name($disciplines[$key]['disc_id'], $dbconnect);
                                $full_name = pg_fetch_all($result);
                                ?>
                                <a href="./d.php"><?php echo $disciplines[$key]['short_name']; ?></a><br>
                                <a><?php echo $full_name[0]['name']; ?></a>
                                <div class="d-flex justify-content-between" style="margin-top: 60px;">
                                    <span>Выполнено</span>
                                    <span>1/<?php echo $task_count[0]['count']; ?></span>
                                </div>
                                <progress class="progress-bar" value="1" max=<?php echo $task_count[0]['count']; ?> >
                            </div>
                        <?php } ?>
        </main>
        <script src="./e.js"></script>
    </body>
</html>
<?php 

require_once("common.php");
require_once("dbqueries.php");
require_once("settings.php");

// в ax_page disc_id у эргономики должно быть -4;

show_header('Дэшборд студента', array('Дэшборд студента' => 'mainpageSt.php'));

$result = pg_query($dbconnect, 'select id, short_name, disc_id, semester from ax_page');
$disciplines=pg_fetch_all($result);
$result1=pg_query($dbconnect, 'select count(id) from ax_page');
$disc_count=pg_fetch_all($result1);

/*function task_count($discipline_id, $dbconnect) {
    $query = 'select count(page_id) from ax_task where page_id =' .$discipline_id;
    return pg_query($dbconnect, $query);
}*/

function full_name($discipline_id, $dbconnect) {
    $query = 'SELECT name from discipline where id =' .$discipline_id;
    return pg_query($dbconnect, $query);
}

function select_task_assignments($task_id, $student_id, $dbconnect) {
    $query = "SELECT ax_assignment.finish_limit, ax_assignment.status_code, ax_assignment.mark from ax_assignment 
    inner join ax_assignment_student on ax_assignment.id = ax_assignment_student.assignment_id 
    where ax_assignment_student.student_user_id = ". $student_id ." and ax_assignment.task_id = ". $task_id ." LIMIT 1;";
    return pg_query($dbconnect, $query);
}
?>

<html> 
    <head>
        <title>Дашборд студента</title>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta http-equiv="x-ua-compatible" content="ie=edge" />
        <link rel="stylesheet" href="css/main.css">
        <!-- MDB -->
        <link rel="stylesheet" href="css/mdb.min.css" />
        <!-- extra -->
        <link rel="stylesheet" href="css/accelerator.css" />
        <!-- MDB icon -->
        <link rel="icon" href="img/mdb-favicon.ico" type="image/x-icon" />
        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.2/css/all.css" />
        <!-- Google Fonts Roboto -->
        <link
            rel="stylesheet"
            href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap"
        />
        <style>
            
        </style>
    </head>
    <body>
        <main class="justify-content-start" style="margin-bottom: 30px;">
            <?php
                array_multisort(array_column($disciplines, 'semester'), SORT_DESC, $disciplines);
                $now_semester = $disciplines[0]['semester']; // first semster in database after sort function
            ?>
            <h2 class="row" style="margin-top: 30px; margin-left: 50px;"> <?php echo $now_semester; ?> семестр</h2><br>
            <div class="container">
                <div class="row g-5 container-fluid">
                    <?php 
                        foreach($disciplines as $key => $massiv) {
                            if ($now_semester != $disciplines[$key]['semester']) { ?>
                                </div>
                                </div>
                                <?php $now_semester = $disciplines[$key]['semester'];?>
                                <h2 class="row" style="margin-top: 30px; margin-left: 50px;"> <?php echo $now_semester; ?> семестр</h2><br>
                                <div class="container">
                                    <div class="row g-5 container-fluid">
                            <?php } ?>
                            <div class="col-3" >
                                <div class="popover-message-message-stud" role="listitem">
                                    <div class="popover-arrow"></div>
                                    <div class="popover-body">
                                        <?php 
                                        $count_succes_tasks = 0;
                                        $count_tasks = 0;
                                        $query_tasks = select_page_tasks($disciplines[$key]['id'], 1);
                                        $result_tasks = pg_query($dbconnect, $query_tasks);
                                        if (!$result_tasks || pg_num_rows($result_tasks) < 1);
                                         else {
                                            $i = 0;
                                            while ($row_task = pg_fetch_assoc($result_tasks)) {
                                                $count_tasks++; 
                                                $result_assignment = select_task_assignments($row_task['id'], $_SESSION['hash'], $dbconnect);
                                                if ($result_assignment && pg_num_rows($result_assignment) >= 1) {
                                                    $row_task_assignment = pg_fetch_assoc($result_assignment);
                                                    if ($row_task_assignment['status_code'] == 3) $count_succes_tasks++;
                                                }
                                            }
                                        }
                                        $result = full_name($disciplines[$key]['disc_id'], $dbconnect);
                                        $full_name = pg_fetch_all($result);
                                        ?>
                                        <div class="p-3 popover-header">
                                            <?php $page_id = $disciplines[$key]['id']; ?>
                                            <a href="studtasks.php?page=<?php echo $page_id; ?>"><?php echo $disciplines[$key]['short_name']; ?></a><br>
                                        </div>
                                        <div class="d-flex align-items-start flex-column" style="height: 140px;">
                                            <div class="mb-auto"><a><?php echo $full_name[0]['name']; ?></a></div>
                                        <?php if ($count_tasks == 0) { ?>
                                            <div class="popover-footer">
                                                    <span>В текущей дисциплине пока нет заданий</span>
                                            </div>
                                        </div>
                                        <?php }
                                        else {?>
                                            <div class="d-flex-justify-content-between">
                                                <span>Выполнено</span>
                                                <span><?php echo $count_succes_tasks; ?>/<?php echo $count_tasks; ?></span>
                                            </div>
                                            <progress value=<?php echo $count_succes_tasks; ?> max=<?php echo $count_tasks; ?> ></progress>
                                        </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
        </main>
    </body>
</html>
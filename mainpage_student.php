<?php 

require_once("common.php");
require_once("dbqueries.php");
require_once("settings.php");

$result = pg_query($dbconnect, select_page_with_thema());
$disciplines=pg_fetch_all($result);
$result1=pg_query($dbconnect, 'select count(id) from ax_page');
$disc_count=pg_fetch_all($result1); ?>

<!DOCTYPE html>
<html lang="en">
<html> 

    <?php
    show_head($pge_title='Дашборд студента');
    show_header_2($dbconnect, 'Дашборд студента', array('Дашборд студента' => 'mainpage_student.php')); ?>

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
                    foreach($disciplines as $discipline) {
                        $page_id = $discipline['id'];
                        if ($now_semester != $discipline['semester']) { ?>
                </div>
            </div>
                            <?php $now_semester = $discipline['semester'];?>
            <h2 class="row" style="margin-top: 30px; margin-left: 50px;"> <?=$now_semester?> семестр</h2><br>
            <div class="container">
                <div class="row g-5 container-fluid">
                        <?php } ?>
                            
                    <div class="col-3">
                        <div id="card_subject" class="card" style="border-radius: 0px 0px 10px 10px;" >
                            <?php 
                            $count_succes_tasks = 0;
                            $count_unsucces_tasks = 0;
                            $query_tasks = select_page_tasks($discipline['id'], 1);
                            $result_tasks = pg_query($dbconnect, $query_tasks);
                            if (!$result_tasks || pg_num_rows($result_tasks) < 1);
                            else {
                                $i = 0;
                                while ($row_task = pg_fetch_assoc($result_tasks)) {
                                    $result_assignment = pg_query($dbconnect, select_task_assignment_with_limit($row_task['id'], $_SESSION['hash']));
                                    if ($result_assignment && pg_num_rows($result_assignment) >= 1) {
                                        $row_task_assignment = pg_fetch_assoc($result_assignment);
                                        if ($row_task_assignment['status_code'] == 3) $count_succes_tasks++;
                                        if($row_task_assignment['status_code'] == 2 || $row_task_assignment['status_code'] == 5) $count_unsucces_tasks++;
                                    }
                                }
                            }
                            $count_tasks = $count_succes_tasks + $count_unsucces_tasks;
                            $result = pg_query($dbconnect, select_discipline_name($discipline['disc_id']));
                            $full_name = pg_fetch_all($result);
                            ?>
                            <div data-mdb-ripple-color="light" style="position: relative;">
                                <div class="bg-image hover-zoom" style="cursor: pointer;" onclick="window.location='studtasks.php?page=<?=$page_id?>'">
                                    <img src="<?=$discipline['src_url']?>" alt="ИНФОРМАТИКА" style="transition: all .1s linear; height: 200px;">
                                    <div class="mask" style="background: <?=$discipline['bg_color']?>; transition: all .1s linear;"></div>
                                </div>
                                <div class="card_image_content">
                                    <div class="p-2" style="text-align: left;">
                                        <a style="color: white; font-weight: bold;"><?php echo $discipline['short_name']; ?></a>
                                        <br><a><?php echo $full_name[0]['name']; ?></a>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                            <?php if ($count_tasks == 0) { ?>
                                <div class="popover-footer text-muted">
                                    <span>Задания временно отсутствуют</span>
                                </div>
                            <?php } else {?>
                                <div class="d-flex justify-content-between text-muted" style="width: 100%">
                                    <span>Выполнено</span>
                                    <span><?php echo $count_succes_tasks; ?>/<?php echo $count_tasks; ?></span>
                                </div>
                                <div class="progress" style="width: 100%; height: 1px; border-radius: 5px; margin-bottom: 5px;">
                                    <div class="progress-bar" role="progressbar" style="width: <?=$count_succes_tasks/$count_tasks*100?>%;
                                    background-color:<?= $discipline['bg_color']?>;" 
                                    aria-valuenow="<?=$count_succes_tasks?>" aria-valuemin="0" aria-valuemax="<?=$count_tasks?>">
                                    </div>
                                </div>
                                <div class="progress" style="width: 100%; height: 20px; border-radius: 5px;">
                                    <div class="progress-bar" role="progressbar" style="width: <?=$count_succes_tasks/$count_tasks*100?>%; 
                                    background-color:<?= $discipline['bg_color']?>;" 
                                    aria-valuenow="<?=$count_succes_tasks?>" aria-valuemin="0" aria-valuemax="<?=$count_tasks?>">
                                        <?=round($count_succes_tasks/$count_tasks*100, 0)?>%
                                    </div>
                                </div>
                            <?php } ?>
                            </div>
                            
                        </div>
                    </div>
                                        
                <?php } ?>
                </div>
            </div>
        </main>

    </body>

    

</html>
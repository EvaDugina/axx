<?php
	require_once("common.php");
	require_once("dbqueries.php");

	// получение параметров запроса
	$page_id = 0;
	if (array_key_exists('page', $_REQUEST))
		$page_id = $_REQUEST['page'];
	else {
		echo "Некорректное обращение";
		http_response_code(400);
		exit;
	}

    // TODO: check prep access rights to page

	$query = select_page_name($page_id, 1);
	$result = pg_query($dbconnect, $query);
	if (!@result || pg_num_rows($result) < 1)
    {
	    echo 'Неверно указана дисциплина';
		http_response_code(400);
		exit;
    }
	else
    {
        $row = pg_fetch_row($result);
        show_header('Посылки по дисциплине', 
				array(	
                    $row[0] => 'preptasks.php?page='.$page_id
					//, 'Посылки по дисциплине' => 'prep_table.php?page='.$page_id
                )
        );
    }
?>
    <!-- MDB -->
    <script type="text/javascript" src="js/mdb.min.js"></script>
    <!-- jQuery -->
    <script type="text/javascript" src="js/jquery-3.5.1.min.js"></script>
    
    <main class="pt-2">
      <div class="container-fluid overflow-hidden">
        <div class="row gy-5">
          <div class="col-8">
            <div class="pt-3">

              <h2 class="text-nowrap">
                  Посылки по дисциплине
              </h2>

              <div class="form-outline">
                <i class="fas fa-search trailing"></i>
                <input type="text" id="form1" class="form-control form-icon-trailing" oninput="filterTable(this.value)" />
                <label class="form-label" for="form1">Фильтр по группам, студентам, заданиям, комментариям</label>
              </div>

              
<?php
	$query = select_page_tasks($page_id, 1);
	$result = pg_query($dbconnect, $query);
    $tasks = array();
	if (!@result || pg_num_rows($result) < 1)
	  echo 'Задания по этой дисциплине отсутствуют';
	else {
        $tasks = pg_fetch_all_assoc($result, PGSQL_ASSOC);
        
        $query = select_page_groups($page_id, 1);
        $result = pg_query($dbconnect, $query);
        $groups = pg_fetch_all_assoc($result, PGSQL_ASSOC);
        
        $query = select_page_students_grouped($page_id, 1);
        $result = pg_query($dbconnect, $query);
        $students = pg_fetch_all_assoc($result, PGSQL_ASSOC);
        
        $query = select_page_messages_table($page_id);
        $result = pg_query($dbconnect, $query);
        $messages = pg_fetch_all_assoc($result, PGSQL_ASSOC);
?>
              <div>
                  <table class="table table-status" id="table-status-id">
                    <thead>
                      <tr class="table-row-header" style="text-align:center;">
                        <th scope="col" colspan="1">Студенты и группы</th>
                        <th scope="col" colspan="<?= count($tasks)+1 ?>">Задания</th>
                      </tr>
                      <tr>
                        <th scope="col" colspan="1"> </th>
                        <th scope="col" data-mdb-toggle="tooltip" title="Номер варианта">#</th>
<?php
		for ( $t = 0; $t < count($tasks); $t++) {
?>
                        <td scope="col" data-mdb-toggle="tooltip" title="<?=$tasks[$t]['title']?>"><?=$t+1?></td>
<?php
		}
?>
                      </tr>
                    </thead>
                    <tbody>
<?php
		for ( $g = 0; $g < count($groups); $g++) {
?>
                      <tr class="table-row-header">
                        <th scope="row" colspan="1"><?=$groups[$g]['grp']?></th>
                        <th colspan="1"> </th>
                        <td colspan="<?=count($tasks)?>"> </td>
                      </tr>
<?php
            for ( $s = 0; $s < count($students); $s++) {
                if ($students[$s]['gid'] != $groups[$g]['id']) continue;
?>
                      <tr>
                        <th scope="row" data-group="<?=$students[$s]['grp']?>"><?=$s+1?>. <?= $students[$s]['fio']?></th>
                        <th data-mdb-toggle="tooltip" title="TODO: добавить в БД описание варианта отдельным полем"><?=$students[$s]['var']?></th>
<?php
                    for ( $t = 0; $t < count($tasks); $t++) {
                        $task_message = null;
                        for ( $m = 0; $m < count($messages); $m++)
                            if ($messages[$m]['tid'] == $tasks[$t]['id'] && $messages[$m]['sid'] == $students[$s]['id'])
                            {
                                $task_message = $messages[$m];
                                break;
                            }
                        if ($task_message == null || $task_message['amark'] == null && $task_message['mtime'] == null) { // no assignment or no answer
?>
                        <td tabindex="-1"> </td>
<?php
                        } else if ($task_message['mtime'] != null && $task_message['amark'] == null && $task_message['mfile'] != null) { // have message without mark and with file, can answer
?>
                        <td tabindex="0" onclick="showPopover(this,'<?=$task_message['mid']?>')" title="<?=$task_message['mtime']?>" data-mdb-content="<a target='_blank' href='<?=$task_message['murl']?>'>FILE: <?=$task_message['mfile']?></a><br/> <?=$task_message['mtext']?><br/> <a href='#' type='button' class='btn btn-outline-primary'>Зачесть</a> <a href='#' type='button' class='btn btn-outline-primary'>Ответить</a>"><?=$task_message['val']?></td>
<?php
                        } else if ($task_message['mtime'] != null && $task_message['amark'] == null) { // have message without mark, can answer
?>
                        <td tabindex="0" onclick="showPopover(this,'<?=$task_message['mid']?>')" title="<?=$task_message['mtime']?>" data-mdb-content="<?=$task_message['mtext']?><br/> <a href='#' type='button' class='btn btn-outline-primary'>Зачесть</a> <a href='#' type='button' class='btn btn-outline-primary'>Ответить</a>"><?=$task_message['val']?></td>
<?php
                        } else if ($task_message['amark'] != null) { // have mark, not need to answer
?>
                        <td tabindex="0" onclick="showPopover(this)" title="<?=$task_message['mtime']?>" data-mdb-content="<?=$task_message['mtext']?>"><?=$task_message['val']?></td>
<?php
                        }
?>
<?php
                    }
?>                        
                      </tr>
<?php
            }
?>
<?php
		}
?>
                    </tbody>
                </table>
            </div>
<?php
	}
?>

<?php
/*
  $query = select_page_students_grouped($page_id);
  $result2 = pg_query($dbconnect, $query);
  while($row2 = pg_fetch_assoc($result2))
  {
    echo '<div class="form-check">';
    echo '  <input class="form-check-input" type="checkbox" name="students[]" value="'.$row2['id'].'" id="flexCheck'.$row2['id'].'">';
    echo '  <label class="form-check-label" for="flexCheck'.$row2['id'].'">'.$row2['fio'].', группа '.$row2['grp'].', вариант №'.$row2['var'].'</label>';
    echo '</div>';
  }
*/  
?>


        </div>
      </div>
      
      <div class="col-4">
        <div class="p-3 border bg-light">
          <h5>История посылок</h5>
          <div class="popover" style="position: relative; margin: 10px; background-color: #8080E040;" role="tooltip"><div class="arrow"></div><h3 class="popover-header" style="background-color: #8080E0A0;">@avz 12-12-2021 10:30:11</h3><div class="popover-body">Проверка связи</div></div>
          <div class="popover" style="position: relative; margin: 10px 10px 10px auto; background-color: #E0E0E040;" role="tooltip"><div class="arrow"></div><h3 class="popover-header" style="background-color: #E0E0E0A0;">@ruslan 12-12-2021 10:32:22</h3><div class="popover-body">Все работает ок, прием!</div></div>
          <div class="pt-1 pb-1"><button type="button" class="btn btn-outline-primary"><i class="fas fa-paperclip fa-lg"></i> Что-то сделать</button></div>
        </div>
      </div>
      
    </main>
    
    
    <!-- Custom scripts -->
    <script type="text/javascript">
    $(function () {
      //$('[data-toggle="tooltip"]').tooltip();
      //$('[data-toggle="popover"]').popover();      
    });

    $( document ).ready(function() {
        //$("#table-status-id>a").click(function(sender){alert(sender)});
        //console.log( "ready!" );
    });    
    
    function filterTable(value) {
        if (value.trim() === '')
            $('#table-status-id').find('tbody>tr').show();
        else
            $('#table-status-id').find('tbody>tr').each(function(){
                $(this).toggle($(this).html().toLowerCase().indexOf(value.toLowerCase()) >= 0);
            });
    }
    
    function showPopover(element,message_id) {
        //console.log(element);
        $(element).popover( { 
            html: true, 
            delay: 250,
            trigger: 'focus',
            placement: 'bottom',
            title: element.getAttribute('title'),
            content: element.getAttribute('data-mdb-content')
        } ).popover('show');
        $('.popover-dismiss').popover({
            trigger: 'focus'
        });
        $(".popover-body>a").click(function(args){answerPress(args.currentTarget.innerText,message_id); $(element).popover('dispose');});
    }
    
    function answerPress(answer_type, message_id)
    {
        console.log(answer_type,message_id);
        if (answer_type.toUpperCase() == 'ЗАЧЕСТЬ') {
            //prompt('Введите оценку');
        }
    }
    </script>
    
    <!-- End your project here-->
<?php
	show_footer();
?>
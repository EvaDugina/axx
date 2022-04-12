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
						
	show_header('Задания по дисциплине', 
				array(	'Введение в разработку ПО 2021' => 'preptasks.php?page='.$page_id, 
						'Задания по дисциплине' => 'preptasks.php?page='.$page_id
					)
				);
?>
    <script src="js/jquery-3.5.1.min.js"></script>
    <main class="pt-2">
      <div class="container-fluid overflow-hidden">
        <div class="row gy-5">
          <div class="col-8">
            <div class="pt-3">

              <h2 class="text-nowrap">
                <form id="addTask" method="post" action="taskedit.php">
                  <input type="hidden" name="page" value="<?=$page_id?>" />
                  Задания по дисциплине<button type="submit" class="btn btn-outline-primary px-3" style="display: inline; float: right;"><i class="fas fa-plus-square fa-lg"></i> Новое задание</button>
                </form>
              </h2>
              
<?php
	$query = select_page_tasks($page_id, 1);
	$result = pg_query($dbconnect, $query);
	
	if (!$result || pg_num_rows($result) < 1)
	  echo 'Задания по этой дисциплине отсутствуют';
	else {
?>
              <div id="checkActiveForm">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th scope="col"><div class="form-check"><input class="form-check-input" type="checkbox" value="" id="checkAllActive"
                        onChange="$('#checkActiveForm').find('input:checkbox').not(this).prop('checked', this.checked);"/></div></th>
                      <th scope="col" style="width:100%;">Название</th>
                      <th scope="col"></th>
                    </tr>
                  </thead>
                  <tbody>
<?php
		while ( $row = pg_fetch_assoc($result) ) {
?>
                   <tr>
                      <td scope="row"><div class="form-check"><input class="form-check-input" type="checkbox" value="<?=$row['id']?>" name="activeTasks[]" id="checkActive" /></div></td>
                     <td>
<?php
			if ($row['type'] == 1) {
?>
						<i class="fas fa-code fa-lg"></i>
<?php
			}
			else {
?>
						<i class="fas fa-file fa-lg" style="padding: 0px 5px 0px 5px;"></i>
<?php
			}
?>
						
						<?=$row['title']?>
						
<?php
			$query = select_assigned_students($row['id']);
			$result2 = pg_query($dbconnect, $query);
			
			if ($result2 && pg_num_rows($result2) > 0) {
				echo '<div class="small">Назначено:<ul><li style="display:none;">';
				$prev_assign = 0;
				while ($row2 = pg_fetch_assoc($result2))
				{
					if ($row2['aid'] == $prev_assign)
					  echo ', '.$row2['fio'];
					else
					  echo '</li><li>'.($row2['ts'] != '' ? '(до '.$row2['ts'].') ' :'').$row2['fio'];
          $prev_assign = $row2['aid'];
				}
				echo '</li></ul></div>';
			}

			$query = select_task_files($row['id']);
			$result2 = pg_query($dbconnect, $query);
			
			if ($result2 && pg_num_rows($result2) > 0) {
				echo '<div class="small">Приложения:<ul>';
				while ($row2 = pg_fetch_assoc($result2))
          echo '<li>'.$row2['file_name'].'</li>';
				echo '</ul></div>';
			}
?>
				            	</td>
                      <td class="text-nowrap">
<!--
                        <form  class="text-nowrap" method="post" action="preptasks_edit.php" name="delete1Form" id="delete1Form">
                          <input type="hidden" name="action" value="delete" />
                          <input type="hidden" name="page" value="<?=$page_id?>" />
                          <input type="hidden" name="tasknum" id="tasknum" value="<?=$row['id']?>" />
                          <button type="submit" class="btn btn-sm px-3"><i class="fas fa-pen fa-lg"></i></button>&nbsp;
                          <button type="button" class="btn btn-sm px-3" disabled><i class="fas fa-download fa-lg"></i></button>
                        </form>
-->
                        <form  class="text-nowrap" method="post" action="taskedit.php" name="editForm" id="editForm">
                          <input type="hidden" name="page" value="<?=$page_id?>" />
                          <input type="hidden" name="tasknum" id="tasknum" value="<?=$row['id']?>" />
                          <button type="submit" class="btn btn-sm px-3"><i class="fas fa-pen fa-lg"></i></button>&nbsp;
                          <button type="button" class="btn btn-sm px-3" disabled><i class="fas fa-download fa-lg"></i></button>
                        </form>
                      </td>
                    </tr>
<?php
		}	
?>
                  </tbody>
                </table>
              </div>
<?php
	}
?>

              <h2 class="pt-5 text-secondary"><i class="fas fa-ban"></i> Архив заданий</h2>
<?php
	$query = select_page_tasks($page_id, 0);
	$result = pg_query($dbconnect, $query);
	
	if (!$result || pg_num_rows($result) < 1)
	  echo 'Архивные задания по этой дисциплине отсутствуют';
	else {
?>
			  
              <table class="table table-secondary table-hover">
                <thead>
                  <tr>
                    <!-- <th scope="col"><div class="form-check"><input class="form-check-input" type="checkbox" value="" id="flexCheckDefault"/></div></th> -->
                    <th scope="col" style="width:100%;">Название</th>
                    <th scope="col"></th>
                  </tr>
                </thead>
                <tbody>
<?php
		while ( $row = pg_fetch_assoc($result) ) {
?>
                  <tr>
                    <!-- <td scope="row"><div class="form-check"><input class="form-check-input" type="checkbox" value="" id="flexCheckDefault"/></div></td> -->
                    <td>
<?php
			if ($row['type'] == 1) {
?>
						<i class="fas fa-code fa-lg"></i>
<?php
			}
			else {
?>
						<i class="fas fa-file fa-lg" style="padding: 0px 5px 0px 5px;"></i>
<?php
			}
?>
						
						<?=$row['title']?>
					          </td>
                    <td class="text-nowrap">
                      <form class="text-nowrap" method="post" action="preptasks_edit.php" name="recover1Form" id="recover1Form">
                        <input type="hidden" name="action" value="recover" />
                        <input type="hidden" name="page" value="<?=$page_id?>" />
                        <input type="hidden" name="tasknum" id="tasknum" value="<?=$row['id']?>" />
                        <button type="submit" class="btn btn-sm px-3"><i class="fas fa-undo fa-lg"></i></button>&nbsp;
                        <button type="button" class="btn btn-sm px-3" disabled><i class="fas fa-download fa-lg"></i></button>
                      </form>                      
                    </td>
                  </tr>
<?php
		}	
?>				  
                </tbody>
              </table>
<?php
	}
?>
            </div>
          </div>
          <div class="col-4">
            <div class="p-3 border bg-light">
              <h6>Массовые операции</h6>
              <form method="post" action="preptasks_edit.php" name="linkFileForm" id="linkFileForm" enctype="multipart/form-data" >
                <input type="hidden" name="action" value="linkFile" />
                <input type="hidden" name="page" value="<?=$page_id?>" />
                <input type="hidden" name="tasknum" id="tasknum" value="" />
                <div class="pt-1 pb-1">
                  <label><i class="fas fa-paperclip fa-lg"></i> <small>ПРИЛОЖИТЬ ФАЙЛ</small></label>
                </div>
                <div class="pt-1 pb-1 ps-5">
                  <input type="file" class="form-control" id="customFile" name="customFile" 
                    onclick="$(linkFileForm).find(tasknum).val($(checkActiveForm).find('#checkActive:checked:enabled').map(function(){return $(this).val();}).get())"
                    onChange="$(linkFileForm).trigger('submit')" />
                </div>
              </form>
              <form method="post" action="preptasks_edit.php" name="assignForm" id="assignForm" enctype="multipart/form-data" >
                <input type="hidden" name="action" value="assign" />
                <input type="hidden" name="page" value="<?=$page_id?>" />
                <input type="hidden" name="tasknum" id="tasknum" value="" />
                <input type="hidden" name="groupped" id="tasknum" value="0" />
                <div class="pt-1 pb-1">
                  <label><i class="fas fa-users fa-lg"></i> <small>НАЗНАЧИТЬ ИСПОЛНИТЕЛЕЙ</small></label>
                </div>
                <div class="ps-5">
                  <section class="w-100 d-flex border">
                    <div class="w-100 h-100 d-flex" style="margin:10px; height:250px; text-align: left;">
                      <div id="demo-example-1" style="overflow-y: auto; height:250px; width: 100%;">
<?php
  $query = select_page_students($page_id);
  $result2 = pg_query($dbconnect, $query);

  while($row2 = pg_fetch_assoc($result2))
  {
    echo '<div class="form-check">';
    echo '  <input class="form-check-input" type="checkbox" name="students[]" value="'.$row2['id'].'" id="flexCheck'.$row2['id'].'">';
    echo '  <label class="form-check-label" for="flexCheck'.$row2['id'].'">'.$row2['fio'].'</label>';
    echo '</div>';
  }

  $query = select_timestamp('3 months');
  $result2 = pg_query($dbconnect, $query);

  if ($row2 = pg_fetch_assoc($result2))
  $timetill = $row2['val'];
?>
                      </div>
                    </div>
                  </section>
                  <section class="w-100 py-2 d-flex justify-content-center">
                    <div class="form-outline datetimepicker w-100" style="width: 22rem">
                      <input type="text" class="form-control active" name="tilltime" value="<?=$timetill?>" id="datetimepickerExample" style="margin-bottom: 0px;">
                      <label for="datetimepickerExample" class="form-label" style="margin-left: 0px;">Срок выполения</label>
                    </div>
                  </section>
                  <button type="submit" class="btn btn-outline-primary"
                    onclick="$(assignForm).find(tasknum).val($(checkActiveForm).find('#checkActive:checked:enabled').map(function(){return $(this).val();}).get());
                            $(assignForm).find(groupped).val(0);"
                    onChange="$(assignForm).trigger('submit')">
                      <i class="fas fa-user fa-lg"></i> Назначить индивидуально
                  </button>
                  <button type="submit" class="btn btn-outline-primary"
                    onclick="$(assignForm).find(tasknum).val($(checkActiveForm).find('#checkActive:checked:enabled').map(function(){return $(this).val();}).get());
                            $(assignForm).find(groupped).val(1);"
                    onChange="$(assignForm).trigger('submit')">
                    <i class="fas fa-users fa-lg"></i> Назначить группой
                  </button>
                </div>
              </form>
              <div class="pt-1 pb-1">
                <label><i class="fas fa-copy fa-lg"></i> <small>КОПИРОВАТЬ В ДИСЦИПЛИНУ</small></label>
              </div>
              <div class="pt-1 pb-1 align-items-center ps-5">
                <select class="form-select" aria-label=".form-select">
                  <option selected>Выберите дисциплину</option>
                  <option value="1">Программирование в ЗРЛ</option>
                  <option value="2">Методы и стандарты программирования</option>
                  <option value="3">Компьютерная графика</option>
                </select>
              </div>
              <div class="pt-1 pb-1 align-items-center ps-5">
                  <button type="button" class="btn btn-outline-primary" disabled><i class="fas fa-copy fa-lg"></i> Копировать</button> 
              </div>
              <div class="pt-1 pb-1"><button type="button" class="btn btn-outline-primary" disabled><i class="fas fa-clone fa-lg"></i> Клонировать в этой дисциплине</button></div>
              <div class="pt-1 pb-1"><button type="button" class="btn btn-outline-primary" disabled><i class="fas fa-ban fa-lg"></i> Перенести в архив</button></div>
              <form method="post" action="preptasks_edit.php" name="deleteForm" id="deleteForm">
                <input type="hidden" name="action" value="delete" />
                <input type="hidden" name="page" value="<?=$page_id?>" />
                <input type="hidden" name="tasknum" id="tasknum" value="" />
                <div class="pt-1 pb-1"><button type="submit" class="btn btn-outline-primary"
                      onclick="$(deleteForm).find(tasknum).val($(checkActiveForm).find('#checkActive:checked:enabled').map(function(){return $(this).val();}).get());
                                $(deleteForm).find(groupped).val(0);"
                      onChange="$(deleteForm).trigger('submit')">
                    <i class="fas fa-trash fa-lg"></i> Удалить
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </main>
    <!-- End your project here-->
<?php
	show_footer();
?>
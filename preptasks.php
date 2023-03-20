<!DOCTYPE html>
<html lang="en">

<?php
require_once("common.php");
require_once("dbqueries.php");
require_once("utilities.php");

// защита от случайного перехода
$au = new auth_ssh();
if (!$au->isAdmin() && !$au->isTeacher()){
	$au->logout();
	header('Location:login.php');
}

// Обработка некорректного перехода между страницами
if (!isset($_GET['page']) || !is_numeric($_GET['page'])){
	header('Location:mainpage.php');
	exit;
}

// получение параметров запроса
$page_id = 0;
if (isset($_GET['page']))
  $page_id = $_GET['page'];


$query = select_discipline_page($page_id);
$result = pg_query($dbconnect, $query);
$row = [];
if (!$result || pg_num_rows($result) < 1) {
  echo 'Неверно указана дисциплина';
  http_response_code(400);
  exit;
} else {
  $row = pg_fetch_assoc($result);
  show_head("Задания по дисциплине: " . $row['disc_name']);
  show_header($dbconnect, 'Задания по дисциплине', array("Задания по дисциплине: " . $row['disc_name']  => $_SERVER['REQUEST_URI']));
} ?>

<body>
  <main class="pt-2">
    <div class="container-fluid overflow-hidden">
      <div class="row gy-5">
        <div class="col-8">
          <div class="pt-3">

            <div class="row">
              <h2 class="col-9 text-nowrap"> Задания по дисциплине</h2>
              <div class="col-3">
                <button type="submit" class="btn btn-outline-primary px-3" style="display: inline; float: right;" 
                onclick="window.location='taskedit.php?page=<?=$page_id?>';">
                  <i class="fas fa-plus-square fa-lg"></i> Новое задание
                </button>
              </div>
            </div>    

            <?php
            $query = select_page_tasks($page_id, 1);
            $result = pg_query($dbconnect, $query);
            
            if (!$result || pg_num_rows($result) < 1)
              echo 'Задания по этой дисциплине отсутствуют';
            else {?>
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
                    while ($task = pg_fetch_assoc($result)) {?>
                      <tr>
                        <td scope="row" style="--mdb-table-accent-bg:unset;"><div class="form-check"><input class="form-check-input" type="checkbox" value="<?=$task['id']?>" name="activeTasks[]" id="checkActive" /></div></td>
                        <td style="--mdb-table-accent-bg:unset;">
                          <h6>
                            <?php if ($task['type'] == 1) {?>
                              <i class="fas fa-code fa-lg"></i>
                            <?php } else { ?>
                              <i class="fas fa-file fa-lg" style="padding: 0px 5px 0px 5px;"></i>
                            <?php } ?>
                            <?=$task['title']?>
                          </h6>

                          
                          <?php
                          $query = select_assigned_students($task['id']);
                          $result2 = pg_query($dbconnect, $query);
                          if ($result2 && pg_num_rows($result2) > 0) {
                            $i=0;?> 
							
							
                            <div class="small">Назначения:</div>
                            <div id="student_container">
                              <?php 
							  
							  $aarray = array();
							  $prev_assign = 0;
							  $studlist = "";
							  $adate = "";
							  while ($student_task = pg_fetch_assoc($result2)) {
								if ($student_task['aid'] == $prev_assign)
								  $studlist = $studlist.', '.$student_task['fio'];
								else {
								  if ($prev_assign != 0)
									array_push($aarray, array('id' => $prev_assign, 'studlist' => $studlist, 'date' => $adate));
									
								  $prev_assign = $student_task['aid'];
								  $studlist = $student_task['fio'];
								  $adate = $student_task['ts'];
								}
							  }
							  if ($prev_assign != 0)
								array_push($aarray, array('id' => $prev_assign, 'studlist' => $studlist, 'date' => $adate));
							  
							  foreach($aarray as $a) { ?>
                                <form id="form-rejectAssignment-<?=$a['id']?>" name="deleteTaskFiles" action="taskedit_action.php" method="POST" enctype="multipart/form-data" class="py-1">
                                  <input type="hidden" name="task_id" value="<?=$student_task['tid']?>"></input>
                                  <!-- <input type="hidden" name="student_id" value ="<?=$student_task['sid']?>"></input> -->
                                  <input type="hidden" name="assignment_id" value ="<?=$a['id']?>"></input>
                                  <input type="hidden" name="action" value="reject"></input>

                                  <div class="d-flex justify-content-between align-items-center me-2 mx-5 badge-primary text-wrap small">
                                    <span class="mx-1"><?=$a['studlist']?><?=($a['date']=="" ?"" :" (до ".$a['date'].")")?></span>
									<span>
										<button class="btn btn-link me-0 p-1" type="button" onclick="window.location='taskassign.php?assignment_id=<?=$a['id']?>';">
											<i class="fas fa-pen fa-lg"></i>
										</button>
										<button class="btn btn-link me-0 p-1" type="button" onclick="confirmRejectAssignment('form-rejectAssignment-<?=$a['id']?>')">
										<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-lg" viewBox="0 0 16 16">
										  <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8 2.146 2.854Z"/>
										</svg>
										</button>
									</span>
                                  </div>
                                </form>
                              <?php $i++;
                              }?>
                            </div>
                          <?php }?>

                           
                        
                          <!-- <div id="div-task-files" class="mb-3"> -->
                            <?php 
							/* 
							$task_files = getTaskFiles($dbconnect, $task['id']);
                            if (count($task_files) > 0) { 
							*/
							?>
                            <!--  <div class="small"><strong>Приложения:</strong></div> -->
                            <?php 
							/*
							show_task_files($task_files);
                            }
							*/ 
							?>
                          <!-- </div> -->

                        </td>
                        <td class="text-nowrap" style="--mdb-table-accent-bg:unset;">
                          <div class="d-flex flex-row">
                          <form name="form-archTask" action="taskedit_action.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="archive">
                            <input type="hidden" name="task_id" value="<?=$task['id']?>">
                            <button type="submit" class="btn btn-secondary px-3 me-1">
                              <i class="fas fa-ban"></i>
                            </button>
                          </form>
                          <button type="submit" class="btn btn-warning px-3 me-1" onclick="window.location='taskedit.php?task=<?=$task['id']?>';">
                            <i class="fas fa-pen fa-lg"></i>
                          </button>
                          <button type="submit" class="btn btn-warning px-3 me-1" onclick="window.location='taskassign.php?task_id=<?=$task['id']?>';">
                            <i class="fas fa-person fa-lg"></i>
                          </button>
                          <button type="button" class="btn btn-primary px-3" disabled>
                              <i class="fas fa-download fa-lg"></i>
                          </button>
                          </div>
                        </td>
                      </tr>
                    <?php }	?>
                  </tbody>
                </table>
              </div>
            <?php } ?>

            <div class="my-5">
              <h2 class="pt-5 text-secondary"><i class="fas fa-ban"></i> Архив заданий</h2>
              <?php
              $query = select_page_tasks($page_id, 0);
              $result = pg_query($dbconnect, $query);
              
              if (!$result || pg_num_rows($result) < 1)
                echo 'Архивные задания по этой дисциплине отсутствуют';
              else {?>
            
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
                    while ( $row = pg_fetch_assoc($result) ) { ?>
                      <tr>
                        <!-- <td scope="row"><div class="form-check"><input class="form-check-input" type="checkbox" value="" id="flexCheckDefault"/></div></td> -->
                        <td style="--mdb-table-accent-bg:unset;">
                          <?php
                          if ($row['type'] == 1) {?>
                            <i class="fas fa-code fa-lg"></i>
                          <?php } else { ?>
                            <i class="fas fa-file fa-lg" style="padding: 0px 5px 0px 5px;"></i>
                          <?php } ?>
                          <?=$row['title']?>
                        </td>
                        <td class="text-nowrap" style="--mdb-table-accent-bg:unset;">
                            <div class="d-flex flex-row">
                              <form method="get" action="preptasks_edit.php">
                                <input type="hidden" name="action" value="recover" />
                                <input type="hidden" name="page" value="<?=$page_id?>" />
                                <input type="hidden" name="tasknum" id="tasknum" value="<?=$row['id']?>" />
                                <button type="submit" class="btn btn-outline-primary px-3"><i class="fas fa-undo fa-lg"></i></button>&nbsp;
                              </form>
                              <form name="form-deleteTask" action="taskedit_action.php" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="task_id" value="<?=$row['id']?>">
                                <button type="submit" class="btn btn-outline-danger px-3">
                                  <i class="fas fa-trash fa-lg"></i>
                                </button>
                              </form>
                              <button type="button" class="btn btn-sm px-3" disabled><i class="fas fa-download fa-lg"></i></button>
                            </div>
                        </td>
                      </tr>
                    <?php }	?>				  
                  </tbody>
                </table>
              <?php } ?>

            </div>
          </div>
        </div>

        <div class="col-4">
          <div class="p-3 border bg-light">
            <h6>Массовые операции</h6>
            <form method="get" action="preptasks_edit.php" name="linkFileForm" id="linkFileForm" enctype="multipart/form-data" >
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
            <form method="get" action="preptasks_edit.php" name="assignForm" id="assignForm" enctype="multipart/form-data" >
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

                      while($row2 = pg_fetch_assoc($result2)) {
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
                    <input type="date" class="form-control active" name="tilltime" id="datetimepickerExample" style="margin-bottom: 0px;">
                    <label for="datetimepickerExample" class="form-label" style="margin-left: 0px;">Срок выполения</label>
					<div class="form-notch">
					  <div class="form-notch-leading" style="width: 9px;"></div>
					  <div class="form-notch-middle" style="width: 114.4px;"></div>
					  <div class="form-notch-trailing"></div>
					</div>

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
            <form method="get" action="preptasks_edit.php" name="deleteForm" id="deleteForm">
              <input type="hidden" name="action" value="delete"/>
              <input type="hidden" name="page" value="<?=$page_id?>" />
              <input type="hidden" name="tasknum" id="tasknum" value="" />
              <div class="pt-1 pb-1">
                <button type="submit" class="btn btn-outline-secondary"
                onclick="$(deleteForm).find(tasknum).val($(checkActiveForm).find('#checkActive:checked:enabled').map(function(){return $(this).val();}).get());
                        $(deleteForm).find(groupped).val(0);"
                onChange="$(deleteForm).trigger('submit')">
                  <i class="fas fa-ban fa-lg"></i>&nbsp;Перенести в архив</button>
              </div>
            </form>
            <form method="get" action="preptasks_edit.php" name="deleteForm" id="deleteForm">
              <input type="hidden" name="action" value="delete"/>
              <input type="hidden" name="page" value="<?=$page_id?>" />
              <input type="hidden" name="tasknum" id="tasknum" value="" />
              <div class="pt-1 pb-1"><button type="submit" class="btn btn-outline-danger" disabled
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


  <div class="modal" id="dialogMark" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">ВНИМАНИЕ!</h5>
          <button type="button" class="close" data-mdb-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <p>Внимание! Если отменить назначение, соответсвующие посылки от студента будут утеряны!</p>
        </div>
        <div class="modal-footer">
          <button id="modal-btn-continue" type="button" class="btn btn-danger" data-mdb-dismiss="modal">Продолжить</button>
          <button id="modal-btn-escape" type="button" class="btn btn-primary">Отмена</button>
        </div>
      </div>
    </div>
  </div>

  <script type="text/javascript">
    function confirmRejectAssignment(form_id) {
      $('#dialogMark').modal('show');

      $('#modal-btn-continue').click(function() {
          let form_reject = document.getElementById(form_id);
          form_reject.submit();
      });
      
      $('#modal-btn-escape').click(function() {
        $('#dialogMark').modal('hide');
      });
    }


  </script>
  
</body>
</html>
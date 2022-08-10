<!DOCTYPE html>

<?php
require_once("common.php");
require_once("dbqueries.php");

// защита от случайного перехода
$au = new auth_ssh();
if (!$au->isAdmin() && !$au->isTeacher()){
	$au->logout();
	header('Location:login.php');
}

// получение параметров запроса
$page_id = 0;
if (array_key_exists('page', $_REQUEST))
	$page_id = $_REQUEST['page'];
else {
	echo "Некорректное обращение";
	http_response_code(400);
	exit;
}

$query = select_task($page_id);
$result = pg_query($dbconnect, $query);
$task = pg_fetch_assoc($result);

if (!$result)
	echo 'Ошибка';

$query = select_task_file(2, $page_id);
$result = pg_query($dbconnect, $query);
$test = pg_fetch_all($result);

$query = select_task_file(3, $page_id);
$result = pg_query($dbconnect, $query);
$test_of_test = pg_fetch_all($result);

#echo "<pre>";
#var_dump($task);
#echo "</pre>";

show_header('Задания по дисциплине', 
	array(	'Введение в разработку ПО 2021' => 'preptasks.php?page='.$page_id, 
			'Задания по дисциплине' => 'preptasks.php?page='.$page_id
		)
	);
?>


<html lang="en">

    <main class="pt-2">
      <div class="container-fluid overflow-hidden">
        <div class="row gy-5">
          <div class="col-8">
            <form class="pt-3" action="task_utilities.php" name="task_utilities" id="task_utilities" method = "post">
			  <input type = "hidden" name = "task_id" value = "<?=$page_id?>"></input>
              <!--<h2><i class="fas fa-arrow-left"></i> Введение в разработку ПО</h2>-->
              <table class="table table-hover">
			  
				<div class="pt-3">
					<div class="form-outline">
						<input type="text" id="form12" class="form-control" value = "<?=$task['title']?>" name = "title"/>
						<label class="form-label" for="form12">Название задания</label>
					</div>
				</div>
				
				<div class="pt-3">

					<label>Тип задания:</label>

					<select id = "task_type" class="form-select" aria-label=".form-select" name = "type">
						<option selected value = "0">Обычное</option>
						<option value = "1">Программирование</option>
					</select>
					<!-- 
					<label>Максимальный балл:</label>

					<div class="form-outline" > 
						<input type="text" id="form12" class="form-control" value="5"/>
					</div>
					-->
				</div>

				<div class="pt-3">
					<div class="form-outline">
					  <textarea class="form-control" id="textAreaExample" rows="5" name = "description"><?=$task['description']?></textarea>
					  <label class="form-label" for="textAreaExample">Описание</label>
					</div>
				</div>
				
				<div class="pt-3 d-flex d-none" id = "tools">
					
					<div class="form-outline col-6">
					  <textarea class="form-control" id="textAreaExample" rows="5" name = "full_text_test"><?php if($task['type'] == 1) echo $test[0]['full_text'];?></textarea>
					  <label class="form-label" for="textAreaExample">Код теста</label>
					</div>

					<div class="form-outline col-6">
					  <textarea class="form-control" id="textAreaExample" rows="5" name = "full_text_test_of_test"><?php if($task['type'] == 1) echo $test_of_test[0]['full_text'];?></textarea>
					  <label class="form-label" for="textAreaExample">Код проверки</label>
					</div>

				</div>
              </table>

			  <button type="submit" class="btn btn-outline-primary">Сохранить</button>
			  <button type="button" class="btn btn-outline-primary">Проверить сборку</button>
            </form>
          </div>
		  
		  

		  
          <div class="col-4">
            <div class="p-3">
 
			  <div class="p-1 border bg-light">
			  <div class="pt-1 pb-1">
                <label><i class="fas fa-users fa-lg"></i> <small>НАЗНАЧИТЬ ИСПОЛНИТЕЛЕЙ</small></label>
              </div>
				<section class="w-100 py-2 d-flex justify-content-center">
					<div class="form-outline datetimepicker w-100" style="width: 22rem">
						<input type="text" class="form-control active" value="2021-12-31 14:12:56" id="datetimepickerExample" style="margin-bottom: 0px;">
						<label for="datetimepickerExample" class="form-label" style="margin-left: 0px;">Срок выполения</label>
					</div>
                </section>
                <section class="w-100 d-flex border">
                  <div class="w-100 h-100 d-flex" style="margin:10px; height:150px; text-align: left;">
                    <div id="demo-example-1" style="overflow-y: auto; height: 150px; width: 100%;">

					  <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="" id="flexCheck1">
                        <label class="form-check-label" for="flexCheck1">Иванов А.А.</label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="" id="flexCheck2">
                        <label class="form-check-label" for="flexCheck2">Петров Б.Б.</label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="" id="flexCheck3">
                        <label class="form-check-label" for="flexCheck3">Сидоров В.В.</label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="" id="flexCheck4">
                        <label class="form-check-label" for="flexCheck4">Иванова Г.Г.</label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="" id="flexCheck5">
                        <label class="form-check-label" for="flexCheck5">Сидоров В.В.</label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="" id="flexCheck6">
                        <label class="form-check-label" for="flexCheck6">Иванова Г.Г.</label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="" id="flexCheck7">
                        <label class="form-check-label" for="flexCheck7">Сидоров В.В.</label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="" id="flexCheck8">
                        <label class="form-check-label" for="flexCheck8">Иванова Г.Г.</label>
                      </div>                                            
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="" id="flexCheck9">
                        <label class="form-check-label" for="flexCheck9">Сидоров В.В.</label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="" id="flexCheck10">
                        <label class="form-check-label" for="flexCheck10">Иванова Г.Г.</label>
                      </div>                                            
                    </div>
                  </div>
				  
                </section>

				<div class="pt-1 pb-1">
					<button type="button" class="btn btn-outline-primary"><i class="fas fa-user fa-lg"></i> Назначить индивидуально</button>
					<button type="button" class="btn btn-outline-primary"><i class="fas fa-users fa-lg"></i> Назначить группе</button>
				</div>
				
				</div>
				<div class="p-1 border bg-light" >
					<div class="pt-1 pb-1"><button type="button" class="btn btn-outline-primary"><i class="fas fa-paperclip fa-lg"></i> Приложить файл</button></div>
				</div>
			</div>
          </div>
        </div>
      </div>
    </main>
    <!-- End your project here-->

    <!-- MDB -->
    <script type="text/javascript" src="js/mdb.min.js"></script>
    <!-- Custom scripts -->
    <script type="text/javascript">
	
		let tools = document.getElementById("tools");
		let task_select = document.getElementById("task_type");
		
		
		let select_change = function(){
			
			//alert(task_select.value);
			if(task_select.value == 0)
				tools.classList.add("d-none");
			else 
				tools.classList.remove("d-none");
		}
		
		task_select.addEventListener("change", select_change);
		
		var type = <?php echo json_encode($task["type"]); ?>;
		//alert(type);

		task_select.selectedIndex = parseInt(type);
		select_change();
		//alert(type);
		
		
	</script>
  </body>
</html>

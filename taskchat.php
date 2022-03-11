<?php
require_once("common.php");
require_once("dbqueries.php");
require_once("utilities.php");

// получение параметров запроса
$page_id = 0;
$task_id = 0;
$discipline_name = "";
if (array_key_exists('page', $_REQUEST) && array_key_exists('id', $_REQUEST)) {
	$page_id = $_REQUEST['page'];
    $task_id = $_REQUEST['id'];

	$query = select_discipline_page($page_id);
	$result = pg_query($dbconnect, $query);
	$page = pg_fetch_all($result)[0];

	$query = select_all_disciplines();
	$result = pg_query($dbconnect, $query);
	$disciplines = pg_fetch_all($result);

	foreach ($disciplines as $key => $discipline) {
		if ($discipline['id'] == $page['disc_id']){
			$discipline_name = $discipline['name'];
			$discipline_name = strtoupper((string) "$discipline_name");
			break;
		}
	}

    $query_task = select_task($task_id);
	$result_task = pg_query($dbconnect, $query_task);
    $row_task = pg_fetch_assoc($result_task);

} else {
	$page_id = 0;
	echo "Некорректное обращение";
	http_response_code(400);
	exit;
}
						
	show_header('Задания по дисциплине', array());
?>

<!DOCTYPE html>
<html lang="en">

  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />
    <title>536 Акселератор - Посылки по дисциплине</title>
    <!-- MDB icon -->
    <link rel="icon" href="img/mdb-favicon.ico" type="image/x-icon" />
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.2/css/all.css" />
    <!-- Google Fonts Roboto -->
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap"
    />
    <!-- MDB -->
    <link rel="stylesheet" href="css/mdb.min.css" />
    <!-- extra -->
    <link rel="stylesheet" href="css/accelerator.css" />
  </head>


  <body>


    <main class="pt-2 p-2">
        <div style="display: flex; align-items: flex-start;">
    	    <button type="button" class="btn" onclick="window.location='<?='studtasks.php?page=' . $page_id?>';"><i class="fas fa-arrow-left"></i></button>
    	    <span class="ms-2" style="align-self: center; font-size:larger;"><?php echo $discipline_name; ?></span>
        </div>

        <br>

    	<h1><?php echo $row_task['title']; ?></h1>
    	<div class="row">
	    	<div class="col-md-6" style="margin-left: 10px;">
	    			<button type="button" class="btn" style="display: block; margin-left: auto;"><i class="fas fa-pencil-alt"></i></button>
		    		<p>Разработать программу для чтения файла с диска, выбора нечетных строк и сохранения их в новый файл.<br>Новый файл должен получить то же самое имя.</p>
		    		<p>Требования к результату:</p>
		    		<a href="URL">Гайдлайн по оформлению программного кода.pdf</a><br>
		    		<a href="URL">Инструкция по подготовке к автотестам.pdf</a><br>
		    		<span>Срок выполнения: 18.04.2021 23:59<button type="button" class="btn float-right" style="display:block; margin-left: auto;">Скачать задание</button></span>
	    	</div>
	    	<div class="col-md-5 float-right">
	    		<span>выполнено</span><br>
	    		<span class="time-right">21.10.2021 17:34</span>
	    		<br>
	    		<button type="button" class="btn" style="display:block; margin-top: 170px;">Онлайн-редактор кода</button>
	    	</div>
	    </div><br>
      
	    <div class="chat-history">
        <h2>Чат по заданию</h2>
	    	  <div class="m-b-0">
                        <div class="clearfix col-md-6">
                            <div class="fio">
                                <span class="message-data-time">Сергей Иванов</span>
                            </div>
                            <div class="message other-message float-right"> А до какога нужно сдать? </div>
                            <div class="message-data">18.04.2021 17:17</div>
                        </div>
                        <div class="row">
							<div class="col-md-6"></div>
							<div class="col-md-6">
                            <div class="fio">
                                <span class="message-data-time">Иван Сергеевич</span>
                            </div>
                            <div class="message other-message float-left">До вечера</div>
                            <div class="message-data">19.04.2021 09:08</div>
							</div>
                        </div>
                        <div class="clearfix">
                            <div class="fio">
                                <span class="message-data-time">Сергей Иванов</span>
                            </div>
                            <div class="message other-message float-right">Я болел у меня справка</div>
                            <a href="URL">Малява для серго последняя.psd</a>
							<br>
                        </div>
                        <div class="clearfix">
                            <div class="fio">
                                <span class="message-data-time">Сергей Иванов</span>
                            </div>
                            <div class="message other-message float-right">Ппроверьте пожалуйсто очень надо</div>
                            <div>
                            	<button type="button" class="btn">Просмотр версии</button>
                            </div>
							<div class="message-data">20.09.2021 12:49</div>
							<br>
                            <div>
                            	<div class="compile row">
                            		<span class="col-2">Компиляция</span>
                            		<span class="col-2">Успешно</span>
                            		<span class="col-2"><a href="URL">вывод компилятора</a></span>
                            	</div>
                            	<div class="compile row">
                            	    <span class="col-2">Функциональный тест 1</span>
                            		<span class="col-2">Успешно</span>
                            		<span class="col-2"><a href="URL">вывод консоли</a></span>
                            	</div>
                            	<div class="compile row">                            	   
                            		<span class="col-2">Функциональный тест 2</span>
                            		<span class="col-2">Неудачно</span>
                            		<span class="col-2"><a href="URL">вывод консоли</a></span>
                            	</div>
                            	<div class="compile row">                            	   
                            		<span class="col-2">Нагрузочный тест</span>
                            		<span class="col-2">Неудачно</span>
                            		<span class="col-2"><a href="URL">вывод консоли</a></span>
                            	</div>
                            </div>
                            <div class="message-data">02.11.2021 20:04</div>
                        </div>
                        <div class="row">
							<div class="col-md-6"></div>
							<div class="col-md-6">
                            <div class="fio">
                                <span class="message-data-time">Аннна Иоанновна</span>
                            </div>
                            <div class="message other-message float-right">Содержание выходного файла не
                            	соответствует заданию
                            </div>
                            <div>Оценка: 3</div>
                            <div class="message-data">04.11.2021 19:18</div>
                        </div>
                    </div>
                </div>
                <div class="chat-message clearfix">
                    <div class="input-group mb-1">
                        <span>Сообщение:
                            </span>
                        <input type="text" style="margin-left: 10px;" class="form-control" placeholder="Введите текст"></input>
        
                    <button type="button" class="btn" style="margin-left: 10px;">
                            Отправить
                        </button>
	    </div>




        <div class = "uploadFile" style="margin-top: 10px;">
            <span> Вложения: 
                <button type="button" class="btn">
                    Добавить файл ...
                </button>
            </span>
        </div><br>
        <!--div class="container"!-->
            <div class="row">
            <div class="col-md-1">Оценка:</div>
                <div class="col-md-1"><input type="text" style="margin-left: 10px;" class="form-control" placeholder="Введите текст"></input>
                </div>
                <div class="col-md-1"><button type="button" class="btn">
                            Отправить
                        </button>
                    </div>
                <div class="col-md-1"><button type="button" class="btn">
                            Отправить
                    </button>
                </div>
                <div class="col-md-1"><button type="button" class="btn">
                        Отправить
                    </button>
                </div>
				</div>
        <!--/div!-->
        </div>
    
    </main>
  </body>
</html>


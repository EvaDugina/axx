<!DOCTYPE html>

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

<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />
    <title>536 Акселератор - список заданий</title>
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
    <link rel="stylesheet" href="css/rdt.css" />
  </head>
  
  <body>


    <main class="container-fluid overflow-hidden">
			<div class="pt-3">
				<div class="row">
				  <div class="col-4">
				    <div class="list-group" id="list-tab" role="tablist">
                    <?php 
                    $query = select_page_tasks($page_id, 1);
                    $result = pg_query($dbconnect, $query);
                        if (!$result || pg_num_rows($result) < 1)
                            echo 'Задания по этой дисциплине отсутствуют';
                        else {
                            $i = 0;
                            while ($row = pg_fetch_assoc($result)) {?>
				        <a
                        <?php if ($i == 0){?> 
				        class="list-group-item list-group-item-action active"
                        <?php } else {?>
                        class="list-group-item list-group-item-action"
                        <?php }?> 

				        id="list-<?php echo $i+1;?>-list"
				        data-mdb-toggle="list"
				        href="#list-<?php echo $i+1;?>"
				        role="tab"
				        aria-controls="list-<?php echo $i+1;?>"
				        ><?php echo $row['title'];?></a>
				            <?php $i++;}}?>
				    </div>
				  </div>

				  <div class="col-8">
				    <div class="tab-content" id="nav-tabContent">
                        <?php 
                        $query = select_page_tasks($page_id, 1);
                        $result = pg_query($dbconnect, $query);
				        if (!$result || pg_num_rows($result) < 1);
                        else {
                            $i=0;
                            while ($row = pg_fetch_assoc($result)) {?>
				        <div

                        <?php if ($i == 0){?> 
                        class="tab-pane fade show active"
                        <?php } else {?>
                        class="tab-pane fade show"
                        <?php }?>

				        id="list-<?php echo $i+1;?>"
				        role="tabpanel"
				        aria-labelledby="list-<?php echo $i+1;?>-list">
                        
				        <table class="table table-bordered">
								  <thead>
								    <tr>
								      <?php echo $row['title'];?>
								    </tr>
								  </thead>
								  <tbody>
								    <tr>
								      <th>Описание</th>	      
								      <td><?php echo $row['description'];?></td>
								    </tr>
								    <tr>
								      <th>Оценка</th>	      
								      <td>-</td>
								    </tr>
								    <tr>
								      <th>Время на выполнение</th>	      
								      <td>5 часов</td>
								    </tr>
								    <tr>
								      <td>
												<button type="button" class="btn btn-outline-primary"> Открыть&nbsp; <i class="fas fa-external-link-alt fa-lg"></i></button>
											</td>	      
								      <td>
												<button type="button" class="btn btn-outline-primary"> Скачать&nbsp; <i class="fas fa-file-download fa-lg"></i></button>
											</td>
								    </tr>
								  </tbody>
								</table>
				      </div>
                        <?php $i++;}}?>
				    </div>
                    <div class="tab-content" id="nav-tabContent">
                    
						<button type="button" class="btn btn-outline-primary" style ="border-color: orange; color: orange;"> Загрузить Ответ <i class="fas fa-lg"></i></button>

                    </div>
				  </div>



				</div>
			</div>	
    </main>
    <!-- End your project here-->

    <!-- MDB -->
    <script type="text/javascript" src="js/mdb.min.js"></script>
    <!-- Custom scripts -->
    <script type="text/javascript"></script>

  </body>
</html>
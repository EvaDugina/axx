<?php

require_once("common.php");
require_once("dbqueries.php");

  show_header('Дэшборд студента', array('Дэшборд студента' => 'mainpageSt.php'));

$result2 = pg_query($dbconnect, 'select year, short_name,semester, id, status from ax_page');
$result = pg_fetch_all($result2);


?>
<?php
$first_semestr = [];
$second_semestr = [];

foreach($result as $item) {
 if($item['semester'] == 1) {
  $first_semestr[]= $item;
 } else {
  $second_semestr[]= $item;
 }
}
?>

<main class="pt-2 justify-content-between">
      <h2 class= "row" style="margin-top: 30px; margin-left: 50px">1 семестр</h2>
 <div class="container">
 <div class="row d-flex justify-content-between">
 <?php foreach($first_semestr as $item) { ?>
          
          <div class="col-3 border border-dark">
            <a href="<?='accel/task4students.html?page='. $item['id']?>"><?=$item['short_name']?></a>
                <div class="d-flex justify-content-between" style="margin-top: 60px;">
                    <span>Выполнено</span>
                    <span>10/12</span>
                  </div>
              <div class="progress" style="height: 20px">
                <div class="progress-bar" role="progressbar" style="width: 80%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">80%</div>
              </div>
              <div><small id="" class="form-text text-muted">Проектирование пользовательских<br> интерфейсов</small></div>
          </div>
  <?php } ?>
</div>
 </div>
 
 <h2 class= "row" style="margin-top: 30px; margin-left: 50px">2 семестр</h2>
<div class="container">
<div class="row d-flex justify-content-between">
 <?php foreach($second_semestr as $item) { ?>
          <div class="col-3 border border-dark">
            <a href=""><?=$item['short_name']?></a>
                <div class="d-flex justify-content-between" style="margin-top: 60px;">
                    <span>Выполнено</span>
                    <span>10/12</span>
                  </div>
              <div class="progress" style="height: 20px">
                <div class="progress-bar" role="progressbar" style="width: 80%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">80%</div>
              </div>
              <div><small id="" class="form-text text-muted">Проектирование пользовательских<br> интерфейсов</small>
              </div>
          </div>
  <?php } ?>
</div>
</div>
</main>

<?php
	show_footer();
?>

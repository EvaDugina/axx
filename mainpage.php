<?php

require_once("common.php");
require_once("dbqueries.php");

  show_header('Дэшборд преподавателя', array('Дэшборд преподавателя' => 'mainpage.php'));

$result2 = pg_query($dbconnect, 'select year, short_name,semester,name, status,disc_id from (ax_page inner join discipline ON ax_page.disc_id = discipline.id) ORDER BY year DESC');

$result = pg_fetch_all($result2);

$year = 2022;
$result_years = [];
foreach($result as $item) {
                $result_years[$item['year']][] = $item;
}

?>
<main class="pt-2 justify-content-between">
<?php
foreach($result_years as $key => $item) { ?>
                <h2 class= "row justify-content-md-center" style="margin-top: 30px;"><?=$key?></h2>
                <div class="container">
                        <div class="row">
                <?php foreach($item as $itm) { ?>
                        <div class="col-3">
                                <div class="d-flex justify-content-end">
                                        <button type="button" class="btn btn-link"><i class="fas fa-pencil-alt"></i></button>
                                </div>
                                <a class="d-flex justify-content-md-center" href=""><?=$itm['name']?></a>
                                <div class="border-top border-dark d-flex justify-content-between">
                                        <span>Сообщение</span>
                                        <span class="justify-content-md-center"><button class="btn btn-link btn-sm" style="width: 55px"><i class="fas fa-bell fa-lg"></i><span class="badge rounded-pill badge-notification bg-danger">4</span></button></span>
                                </div>
                        </div>
                <?php }?>
                                </div>
                        <div class="d-flex justify-content-end">
                                <img src="https://img.icons8.com/ios/50/000000/plus--v1.png"/></br>
                                </br><a class="justify-content-md-center" href="">Добавить новый предмет</a>
                        </div>
                </div>
        <?php } ?>
</main>

<?php
	show_footer();
?>

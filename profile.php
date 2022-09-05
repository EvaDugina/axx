<!DOCTYPE html>

<?php
require_once("common.php");
require_once("dbqueries.php");
require_once("utilities.php"); 

$query = get_user_info($_SESSION['hash']);
$result = pg_query($dbconnect, $query);
$student_info = pg_fetch_assoc($result);
?>

<html lang="en">

<?php 
show_head(); ?>

<body>
        <?php
        $au = new auth_ssh();
        show_header_2($dbconnect, 'Профиль', ($au->isAdminOrTeacher()) ? array('Профиль' => 'profile.php') 
                : array('Дэшборд студента' => 'mainpage_student.php', 'Профиль' => 'profile.php')); ?>
	<main style="max-width: 1000px; width:100%; margin: 0 auto;">
		<div class="pt-5 px-4">
			<div class="row">
        <div class="pt-5 px-5 d-flex">
          <div class="col-md-3">
            <div style="height: 150px;">
              <i class="fas fa-user-circle fa-lg" style="font-size: 10em;"></i>
            </div>
          </div>
          <form clacc="col-md-6 form-check" action="profile_edit.php" method="POST">
            <p> <span class="font-weight-bold">ФИО: </span> <span class="font-weight-normal"><?=$student_info['fio']?></span> </p>
            <p> <span class="font-weight-bold">ЛОГИН: </span> <span class="font-weight-normal"><?=$_SESSION['login']?></span> </p>
            <p> <span class="font-weight-bold">ГРУППА: </span> <span class="font-weight-normal"><?=$student_info['group_name']?></span> </p>
            <p> <span class="font-weight-bold">ПОЧТА: </span> 
                    <input type="email" name="email" class="form-control" id="exampleFormControlInput1" placeholder="name@example.com" value="<?=$student_info['email']?>">        
            </p>
            
            <p> <input class="form-check-input" type="checkbox" id="profile_checkbox" name="checkbox_notify" 
                    <?php if($student_info['notification_type']==1) echo "checked"; ?>> <span class="font-weight-normal">
                      Получать уведомления на почту</span> </p>

            <button type="submit" class="btn btn-primary">CОХРАНИТЬ</button>
                  
          </form>
        </div>
      </div>
    </div>
  </main>

</body>




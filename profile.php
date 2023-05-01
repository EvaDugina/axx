<!DOCTYPE html>

<?php
require_once("settings.php");
require_once("common.php");
require_once("POClasses/User.class.php");
require_once("POClasses/Group.class.php");

$au = new auth_ssh();
checkAuLoggedIN($au);

if (isset($_GET['user_id'])) {
  $User = new User((int)$_GET['user_id']);
} else {
  $User = new User((int)$au->getUserId());
}
$group = new Group((int)$User->group_id);

?>

<html lang="en">

<?php 
show_head('Профиль'); ?>

<body>
  
  <?php 
  show_header($dbconnect, 'Профиль', ($au->isAdminOrTeacher()) ? array('Профиль' => 'profile.php') 
    : array('Профиль' => 'profile.php')); 
  ?>

	<main style="max-width: 1000px; width:100%; margin: 0 auto;">
		<div class="pt-5 px-4">
			<div class="row">
        <div class="pt-5 px-5 d-flex">
          <div class="col-md-3">
            <div class="p-4">
            <svg class="w-100 h-100" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-circle" viewBox="0 0 16 16">
              <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
              <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/>
            </svg>
            </div>
          </div>

          <form clacc="col-md-4 form-check" style="width:inherit;" action="profile_edit.php" method="POST">
            <p> <span class="font-weight-bold">ФИО: </span> <span class="font-weight-normal"><?=$User->getFIO()?></span> </p>
            <p> <span class="font-weight-bold">ЛОГИН: </span> <span class="font-weight-normal"><?=$User->login?></span> </p>
            <p> <span class="font-weight-bold">ГРУППА: </span> <span class="font-weight-normal"><?=$group->name?></span> </p>

            <?php if ((int)$au->getUserId() == (int)$User->id) {?>
              <p class="d-flex align-items-center mb-0"> 
                <span class="font-weight-bold">ПОЧТА:</span> &nbsp; &nbsp;
                <input type="email" name="email" class="form-control" id="exampleFormControlInput1" placeholder="name@example.com" value="<?=$User->email?>">        
              </p>
              <p class="d-flex align-items-center mb-0"> 
                <span class="font-weight-bold">GITHUB:</span> &nbsp; &nbsp;
                <input type="url" name="github_url" class="form-control" id="exampleFormControlInput1" placeholder="https://github.com/ВАШ_ЛОГИН" value="<?=$User->github_url?>">        
              </p>
              <p> <input class="form-check-input" type="checkbox" id="profile_checkbox" name="checkbox_notify" 
                    <?php if($User->notify_status==1) echo "checked"; ?>> <span class="font-weight-normal">
                      Получать уведомления на почту</span> </p>

              <button type="submit" class="btn btn-primary">CОХРАНИТЬ</button>

            <?php } else {?>

              <?php if($au->isAdminOrTeacher() && $User->email != null) {?>
                <p> <span class="font-weight-bold">ПОЧТА: </span> <span class="font-weight-normal"><?=$User->email?></span> </p>
              <?php }?>

              <?php if($User->github_url != null) {?>
                <div> 
                  <span class="font-weight-bold">GITHUB: </span> 
                  <a href="<?=$User->github_url?>" target="_blank" rel="noopener noreferrer">
                    <?=$User->github_url?>
                  </a> 
                </div>
              <?php }?>

            <?php }?>
                  
          </form>
        </div>
      </div>
    </div>
  </main>

</body>




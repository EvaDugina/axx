<?php
	require_once("common.php");
	require_once("dbqueries.php");

  include_once('auth_ssh.class.php');
  $au = new auth_ssh();
  $au->logout();

	// получение параметров запроса
	$page_id = 0;
	if (array_key_exists('pageaddr', $_REQUEST))
		$page_addr = $_REQUEST['pageaddr'];
	else 
    $page_addr = '';
						
  show_head();
  show_header_2($dbconnect, 'Вход в систему', array('Вход в систему' => 'login.php'));
?>
  <body style="overflow-x: hidden;">
    <main class="pt-2">
      <div class="container-fluid overflow-hidden">
        <div class="row gy-5">
          <div class="col-3">
            <h2>Вход в систему</h2>

            <?php if (isset($_GET['error']) && $_GET['error'] == 1) { ?>
              <h2>ОШИБКА ВХОДА!</h2>
            <?php }?>

            <form class="text-nowrap" method="post" action="auth.php">
              <input type="hidden" name="action" value="login" />
              <div class="form-outline m-3">
                <input type="text" id="login" name="login" class="form-control" />
                <label class="form-label" for="login">Логин</label>
              </div>
              <div class="form-outline m-3">
                <input type="password" id="pass" name="password" class="form-control" />
                <label class="form-label" for="pass">Пароль</label>
              </div>
              <button type="submit" class="btn my-2 mx-3"><i class="fas fa-signin-alt fa-lg"></i> Войти</button>
            </form>    
          </div>
        </div>
      </div>
    </main>
    <!-- End your project here-->
<?php
	show_footer();
?>
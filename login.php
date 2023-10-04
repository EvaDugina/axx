<?php
	require_once("common.php");
	require_once("dbqueries.php");

  include_once('auth_ssh.class.php');
  $au = new auth_ssh();

  if(isset($_GET['action']) && $_GET['action'] == "logout") {
    $au->logout();
  }

  $errorAuth = false;
  if (isset($_GET['authStatus'])) {
    $errorAuth = true;
  }

	// получение параметров запроса
	$page_id = 0;
	if (array_key_exists('pageaddr', $_REQUEST))
		$page_addr = $_REQUEST['pageaddr'];
	else 
    $page_addr = '';
						
  show_head("Страница Авторизации");
?>
  <body style="overflow-x: hidden;">
    <?php show_header($dbconnect, 'Вход в систему', array('Вход в систему' => 'login.php'));?>
    <main class="pt-2">
      <div class="container-fluid overflow-hidden">
        <div class="row gy-5">
          <div class="col-12 col-xl-4"></div>
          <div class="col-12 col-xl-4 pt-4">
            <div class="pt-1 px-3" style="border: 1px solid #dee2e6; border-radius: 5px;">
              <h2 class="my-2">Авторизация</h2>

              <form id="form-auth" class="text-nowrap form-horizontal" method="post" action="auth.php" onsubmit="return validateForm()">
                <input type="hidden" name="action" value="login" />
                
                  <div class="form-outline my-3">
                    <input type="text" id="input-login" name="login" class="form-control" />
                    <label class="form-label" for="login">Логин</label>
                  </div>
                
                  <div class="form-outline my-3">
                    <input type="password" id="input-password" name="password" class="form-control" />
                    <label class="form-label" for="pass">Пароль</label>
                  </div>
                  
                  <?php if($errorAuth) {?>
                    <strong><p id="error-authorization" class="error">Ошибка: Неверный Логин или Пароль</p></strong>
                  <?php }?>

                  <strong><p id="error-field-filled" class="error d-none">Ошибка: Незаполненные поля!</p></strong>
                
                  <button type="submit" class="btn my-2 col-xl-3">
                    <i class="fas fa-signin-alt fa-lg"></i>Войти
                  </button>
              </form>
            </div>

          </div>
        </div>
      </div>
    </main>
    
    <script type="text/javascript">

      function validateForm () {
        let inputLogin = $('#input-login').val();
        let inputPassword = $('#input-password').val();

        if(inputLogin == "" /*|| inputPassword == ""*/) {
          $('#error-field-filled').removeClass("d-none");
          return false;
        } else{
          $('#error-field-filled').addClass("d-none");
          return true;
        }
      }

        
    </script>

<?php
	show_footer();
?>
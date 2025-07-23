<?php
//session_start();

require_once("settings.php");
require_once("dbqueries.php");
require_once("utilities.php");
require_once("POClasses/User.class.php");

$pageurl = explode('/', $_SERVER['REQUEST_URI']);
$pageurl = $pageurl[count($pageurl) - 1];
$_SESSION['username'] = '';

if ($pageurl != 'login.php') {
  include_once('auth_ssh.class.php');
  $au = new auth_ssh();
  if (!$au->loggedIn()) {
    header('Location:login.php');
    exit;
  } else {
    $query = get_user_name($au->getUserId());
    $result = pg_query($dbconnect, $query);
    if ($row = pg_fetch_assoc($result))
      $_SESSION['username'] = $row['first_name'];
    if (isset($row['middle_name']))
      $_SESSION['username'] .= " " . $row['middle_name'];
  }
}

function getCurrentVersion()
{
  $commitizen_config_file_path = "./.cz.json";
  $config_json = json_decode(file_get_contents($commitizen_config_file_path));
  if (!isset($config_json->commitizen))
    return null;
  return $config_json->commitizen->version;
}

function show_breadcrumbs(&$breadcrumbs)
{
  if (count($breadcrumbs) < 1)
    return;
?>
  <ul class="navbar-nav me-auto mb-2 mb-lg-0">
    <div class="container-fluid ps-2">
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
          <?php
          foreach ($breadcrumbs as $name => $link) { ?>
            <svg style="height: inherit;" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-arrow-right-short" viewBox="0 0 16 16">
              <path fill-rule="evenodd" d="M4 8a.5.5 0 0 1 .5-.5h5.793L8.146 5.354a.5.5 0 1 1 .708-.708l3 3a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708-.708L10.293 8.5H4.5A.5.5 0 0 1 4 8z" />
            </svg>
            <li class="d-flex justify-content-between align-items-center px-2" style="">
              <a class="text-reset" href="<?php echo $link; ?>"><?php echo $name ?></a>
            </li>
          <?php
          } ?>
        </ol>
      </nav>
    </div>
  </ul>
<?php
}
function show_head($page_title = '', $js = array(), $css = array())
{
?>

  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />

    <title><?= $page_title ?></title>

    <!-- MDB icon -->
    <link rel="icon" href="src/img/mdb-favicon.ico" type="image/x-icon" />

    <!-- Fonts & Icons -->
    <link rel="stylesheet" type="text/css" href="src/fonts-icons/all.css" />
    <link rel="stylesheet" href="src/fonts-icons/font-awesome.min.css" />

    <!-- Extra -->
    <link rel="stylesheet" href="css/accelerator.css" />
    <link rel="stylesheet" href="css/styles.css" />

    <!-- MDB -->
    <link rel="stylesheet" href="css/mdb/mdb.min.css" />
    <script type="text/javascript" src="js/mdb.min.js"></script>

    <!-- jQuery -->
    <script type="text/javascript" src="js/jquery/jquery-3.5.1.min.js"></script>

    <!-- Page-specific JS/CSS -->
    <?php
    foreach ($js as $url) {
    ?>
      <script type="text/javascript" src="<?= $url ?>"></script>
    <?php
    }
    ?>
    <?php
    foreach ($css as $url) {
    ?>
      <link rel="stylesheet" href="<?= $url ?>" />
    <?php
    }
    ?>
  </head>
<?php
}

function show_header(/* [x]: Убрать */$dbconnect, $page_title = '', $breadcrumbs = array(), $user = null)
{
  $au = new auth_ssh();
?>
  <script type="text/javascript">
    $(document).ready(function() {
      $('main').css("margin-top", parseFloat($('#header').css("height")) + parseFloat($('main').css("margin-top")));
    });

    function showChangeLogModal() {
      $('#div-dialog-changelog').modal('show');
    }
  </script>
  <header id="header" class="header header--fixed js-header is-show">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg background-light-grey navbar-light" style="height:75px;">
      <!-- Container wrapper -->
      <div class="container-fluid">
        <!-- Navbar brand -->
        <div class="d-flex align-items-center me-3">
          <a class="navbar-brand p-0 me-0 text-reset" href="index.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="44.996" height="45" viewBox="0 0 44.996 45">
              <path id="Logo" data-name="Logo" d="M149.546,195.5a22.506,22.506,0,0,0-22.1,18.244,54.633,54.633,0,0,1,9.445-2.678,55.249,55.249,0,0,1,18.59-.3,54.567,54.567,0,0,0-28.435,7.519,22.527,22.527,0,0,0,.674,5.206,40.427,40.427,0,0,1,29.827-10.734,37.33,37.33,0,0,0-11.988,3.283,38.273,38.273,0,0,0-15.447,13.3,22.594,22.594,0,0,0,3.914,4.945,37.942,37.942,0,0,1,8.877-11.167,38.443,38.443,0,0,1,17.365-8.412l.008-.551-5.975,1.918,4.869-3.836-6.418-4.131,7.451,2.139v-4.131l2.066,3.541,2.434-1.475-.664,2.8,3.91.885-3.91,1.254,4.426,7.008-6.049-5.533-2.213,4.943.054-3.912c-5.48,3.747-11.053,7.675-13.849,13.428a21.07,21.07,0,0,0-1.908,10.866,22.5,22.5,0,1,0,5.044-44.432Z" transform="translate(-127.05 -195.5)" fill="#4f4f4f"></path>
            </svg>
          </a>
          <a class="navbar-brand p-0 ms-3 me-0 text-reset" href="index.php">
            <div class="d-flex me-2 align-items-start">
              <span><strong>Акселератор</strong></span><span class="badge badge-light text-reset p-1 m-0"><small>536</small></span>
            </div>
          </a>
          <?php $version = getCurrentVersion(); ?>
          <span class="text-muted mt-1">v<?= (($version != null)) ? $version : "?" ?></span>
          &nbsp;
          <button type="button" class="btn text-muted mt-1 p-0" onclick="showChangeLogModal()" style="zoom: 75%;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-question-square" viewBox="0 0 16 16">
              <path d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z"></path>
              <path d="M5.255 5.786a.237.237 0 0 0 .241.247h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286m1.557 5.763c0 .533.425.927 1.01.927.609 0 1.028-.394 1.028-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94"></path>
            </svg>
          </button>
        </div>

        <!-- Toggle button -->
        <button class="navbar-toggler" type="button" data-mdb-toggle="collapse" data-mdb-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
          <i class="fas fa-bars"></i>
        </button>

        <!-- Collapsible wrapper -->
        <div class="collapse navbar-collapse row" id="navbarSupportedContent">
          <div class="d-flex justify-content-between align-items-center">
            <div class="">

              <?php show_breadcrumbs($breadcrumbs);
              if (count($breadcrumbs) < 1) echo '</div>';

              if ($page_title != "Вход в систему") {
                if ($user != null) {
                  $array_notify = $user->getNotifications();
                } ?>
            </div>

            <div class="d-flex flex-row align-items-center justify-content-end">

              <!-- <?php if (hasSecondRole($user->login)) { ?>
                <form action="auth.php" method="POST" class="me-4 mb-0">
                  <input type="hidden" name="action" value="login">
                  <input type="hidden" name="login" value="<?= $user->login ?>">
                  <input type="hidden" name="password" value="<?= $user->password ?>">
                  <input type="hidden" name="role" value="<?= ($user->isTeacher()) ? 3 : 2 ?>">
                  <button class="btn btn-outline-primary bg-white" type="submit">
                    Зайти как <?= ($user->isTeacher()) ? "студент" : "преподаватель" ?>
                  </button>
                </form>
              <?php } ?> -->

              <!-- Icons -->
              <ul class="navbar-nav me-2">
                <?php if ($au->isAdmin()) { ?>
                  <!-- Admin Panel -->
                  <a class="me-3 dropdown-toggle hidden-arrow badge bg-secondary text-light" href="adminpanel.php">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-code-slash" viewBox="0 0 16 16">
                      <path d="M10.478 1.647a.5.5 0 1 0-.956-.294l-4 13a.5.5 0 0 0 .956.294zM4.854 4.146a.5.5 0 0 1 0 .708L1.707 8l3.147 3.146a.5.5 0 0 1-.708.708l-3.5-3.5a.5.5 0 0 1 0-.708l3.5-3.5a.5.5 0 0 1 .708 0m6.292 0a.5.5 0 0 0 0 .708L14.293 8l-3.147 3.146a.5.5 0 0 0 .708.708l3.5-3.5a.5.5 0 0 0 0-.708l-3.5-3.5a.5.5 0 0 0-.708 0" />
                    </svg>
                  </a>
                <?php } ?>
                <!-- Notifications -->
                <a class="dropdown-toggle hidden-arrow badge <?= ($array_notify && count($array_notify) > 0) ? "bg-danger text-light" : "bg-light text-reset" ?>" href="#" id="navbarDropdownMenuLink1" role="button" data-mdb-toggle="dropdown" aria-expanded="false">
                  <?php if ($array_notify && count($array_notify) > 0) { ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chat-right-text-fill" viewBox="0 0 16 16">
                      <path d="M16 2a2 2 0 0 0-2-2H2a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h9.586a1 1 0 0 1 .707.293l2.853 2.853a.5.5 0 0 0 .854-.353zM3.5 3h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1 0-1m0 2.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1 0-1m0 2.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1 0-1" />
                    </svg>
                  <?php } else { ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chat-right-dots-fill" viewBox="0 0 16 16">
                      <path d="M16 2a2 2 0 0 0-2-2H2a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h9.586a1 1 0 0 1 .707.293l2.853 2.853a.5.5 0 0 0 .854-.353zM5 6a1 1 0 1 1-2 0 1 1 0 0 1 2 0m4 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0m3 1a1 1 0 1 1 0-2 1 1 0 0 1 0 2" />
                    </svg>
                  <?php } ?>
                  <?php // FIXME: Скачет иконка уведомлений 
                  ?>
                  <span class="badge rounded-pill badge-notification <?= (!$array_notify || ($array_notify && count($array_notify) < 1)) ? "bg-success" : "bg-danger" ?>">
                    <?php if ($array_notify) echo count($array_notify);
                    else echo 0; ?>
                  </span>
                </a>
                <ul class=" dropdown-menu dropdown-menu-end me-2" aria-labelledby="navbarDropdownMenuLink1" style="z-index:99999999; ">
                  <?php
                  if ($array_notify) {
                    $i = 0;
                    foreach ($array_notify as $notify) {
                      $i++; ?>
                      <a href="taskchat.php?assignment=<?= $notify['assignment_id'] ?>">
                        <li class="dropdown-item" <?php if ($i != count($array_notify)) echo 'style="border-bottom: 1px solid;"' ?>>
                          <div class="d-flex justify-content-between align-items-center">
                            <div class="me-2">
                              <span style="border-bottom: 1px solid;">
                                <?php /*if ($user->isTeacher()) {
                                foreach ($notify['students'] as $i => $Student) { ?>
                                  <?= $Student->getFI() ?> <?= ($i + 1 < count($notify['students'])) ? "| " : "" ?>
                                <?php
                                }
                              } else { ?>
                                <?php foreach ($notify['teachers'] as $i => $Teacher) { ?>
                                  <?= $Teacher->getOfficialFIO() ?> <?= ($i + 1 < count($notify['teachers'])) ? "| " : "" ?>
                              <?php }
                              } */ ?>
                                <?= $notify['page_name'] ?>
                              </span>
                              <br><?php echo $notify['taskTitle']; ?>
                            </div>
                            <span class="badge badge-primary badge-pill"
                              <?php if ($user->isTeacher() && $notify['needToCheck']) { ?>
                              style="background: red; color: white;"
                              <?php } ?>>
                              <?= $notify['countUnreaded'] ?>
                            </span>
                          </div>
                        </li>
                      </a>
                  <?php }
                  } ?>
                </ul>
              </ul>

              <ul class="navbar-nav d-flex flex-row me-1">
                <!-- Avatar -->
                <a class="dropdown-toggle d-flex align-items-center hidden-arrow text-reset" href="#" id="navbarDropdownMenuLink2" role="button" data-mdb-toggle="dropdown" aria-expanded="false">
                  <button type="button" class="btn btn-floating shadow-none p-1">
                    <?php if ($user != null && $user->getImageFile() != null) { ?>
                      <div class="row mb-3">
                        <div class="col-12">
                          <div class="embed-responsive embed-responsive-1by1 text-center">
                            <div class="embed-responsive-item">
                              <img class="w-100 h-100 p-0 m-0 rounded-circle user-icon" src="<?= $user->getImageFile()->download_url ?>" />
                            </div>
                          </div>
                        </div>
                      </div>
                    <?php } else { ?>
                      <svg class="w-100 h-100" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-circle" viewBox="0 0 16 16">
                        <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" />
                        <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z" />
                      </svg>
                    <?php } ?>
                  </button>
                  <span class="text-reset ms-2">
                    <?php // [x]: убрать // TODO: Проверить
                    if ($user != null) echo $user->getOfficialFIO();
                    else echo $_SESSION['username']; ?>
                  </span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end me-2" aria-labelledby="navbarDropdownMenuLink2" style="z-index:99999999; ">
                  <li><a class="dropdown-item" href="profile.php">Профиль</a></li>
                  <li><a class="dropdown-item" href="login.php?action=logout">Выйти</a></li>
                </ul>
              </ul>
            </div>
          <?php }

              if (count($breadcrumbs) >= 1) echo '</div>'; ?>
          </div>
        </div>

    </nav>

  </header>

  <div class="modal" id="div-dialog-changelog" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">ИСТОРИЯ ИЗМЕНЕНИЙ</h5>
          <button type="button" class="close" data-mdb-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <?= getChangeLogHtml() ?>
        </div>
      </div>
    </div>
  </div>

<?php
}

function show_footer()
{
?>
  <!-- MDB -->
  <script type="text/javascript" src="js/mdb.min.js"></script>

  <!-- Custom scripts -->
  <script type="text/javascript"></script>

  </body>

  </html>
<?php
}
?>
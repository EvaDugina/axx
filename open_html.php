<?php
require_once("common.php");

$au = new auth_ssh();
checkAuLoggedIN($au);
checkAuIsNotStudent($au);

if (isset($_GET['file_path'])) {
    $file_path = $_GET['file_path'];
    $file_ext = strtolower(preg_replace('#.{0,}[.]#', '', basename($file_path)));
    if (!file_exists($file_path)) {
        exit("Файл не существует");
    }

    $page_title = 'Антиплагиат';
    $previous_page_title = getEditorPageTitle();

    show_head($page_title); ?>

    <body>

        <?php show_header($dbconnect, $page_title, array(
            $previous_page_title => $previous_page_url,
            $page_title  => $_SERVER['REQUEST_URI']
        ), $User); ?>

        <main>
            <?= file_get_contents($file_path); ?>
        </main>
    </body>

<?php }

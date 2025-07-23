<?php
require_once("common.php");
require_once("settings.php");
require_once("dbHandler.php");

$au = new auth_ssh();
checkAuLoggedIN($au);
checkAuIsNotStudent($au);

$User = new User((int)$au->getUserId());

$page_title = 'Панель Администратора';
?>

<html>

<link rel="stylesheet" href="css/main.css">

<?php show_head($page_title); ?>

<body>

    <?php show_header($dbconnect, $page_title, array($page_title  => $_SERVER['REQUEST_URI']), $User); ?>

    <main>
        <div class="container">
            <div class="row">
                <div class="col-5">
                    <div class="d-flex flex-column border border-primary rounded mt-5 p-3">
                        <p class="h3"><strong>Управление Базой Данных</strong></p>
                        <div class="d-flex flex-column mt-2 gap-3">
                            <div class="d-flex justify-content-start">
                                <button id="btn-create-dump" class="btn btn-primary w-75 align-items-center" onclick="createDump()">
                                    <span>
                                        Создать бэкап
                                    </span>
                                    <div id="spinner-create-dump" class="spinner-border d-none ms-3" role="status" style="width: .75rem; height: .75rem;">
                                        <span class="sr-only">Ожидание...</span>
                                    </div>
                                </button>
                            </div>
                            <div class="d-flex flex-column gap-1 w-100">
                                <p class="h6"><strong>Существующие бэкапы</strong></p>
                                <div id="div-backups" class="d-flex flex-wrap gap-1">
                                    <?php
                                    $backups = getAllBackups();
                                    if (count($backups) > 0) {
                                        foreach ($backups as $backup) { ?>
                                            <div class="badge badge-primary px-4"><?= $backup['file_name'] ?></div>
                                        <?php }
                                    } else { ?>
                                        <p class="h6">Бэкапы отсутствуют</p>
                                    <?php
                                    } ?>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                <div class="col-6"></div>
            </div>
        </div>
    </main>
</body>

<script type="text/javascript" src="js/utilities.js"></script>
<script type="text/javascript">
    async function createDump() {

        document.getElementById("btn-create-dump").disabled = true;
        document.getElementById("spinner-create-dump").classList.remove("d-none");

        let ajaxResponse = await ajaxCreateDump();
        if (ajaxResponse == null) {
            alert("Ошибка! Неудалось сделать дамп.");
            return;
        }

        //  Пауза чтобы нельзя было прокликивать на кнопку
        sleep(3000,
            async () => {
                document.getElementById("spinner-create-dump").classList.add("d-none");
                document.getElementById("btn-create-dump").disabled = false;
                document.location.reload();
            }, );

    }

    async function ajaxCreateDump() {
        var formData = new FormData();

        formData.append('flag-createDump', true);

        let response = null;

        try {
            response = await $.ajax({
                type: "POST",
                url: 'adminpanel_action.php#content',
                cache: false,
                contentType: false,
                processData: false,
                data: formData,
                dataType: 'html'
            });
            response = response.trim()
            console.log('Данные получены:', response);
            response = JSON.parse(response);
            if (response.error) {
                console.log(response);
                response = null;
            }
        } catch (error) {
            console.error('Ошибка запроса:', error);
            return null;
        }

        return response;
    }
</script>

</html>
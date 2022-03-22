<html> 
    <head>
        <meta charset="utf-8">
        <title>example</title>
        <link rel="stylesheet" href="./e.css">
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta http-equiv="x-ua-compatible" content="ie=edge" />
        <!-- MDB icon -->
        <link rel="icon" href="img/mdb-favicon.ico" type="image/x-icon" />
        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.2/css/all.css" />
        <!-- Google Fonts Roboto -->
        <link
         rel="stylesheet"
         href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap"
        />
    </head>
    <body>
        <header>
            <div class="container-fluid">
                <a class="text-in-header"><b>Акселератор</b></a>
            </div>
        </header>
        <main class="justify-content-between">
            <h2 style="text-align: center">8 Семестр</h2>
            <div class="container">
                <div class="row">
                    <?php $i = 1;
                    $semestr = 0;
                    while($semestr < 6) { ?>
                        <div class="col-3">
                            <button onclick="window.open('./d.php')" class="button" id="button-<?php  echo $i; ?>">    
                            <span class="text">Дисциплина <?php echo $i; ?></span><br>
                            <progress class="progressbar" id="progressbar-<?php  echo $i; ?>" value="4" max="12"></progress>
                            </button>
                        </div>
                        <?php  
                        ++$semestr;
                        ++$i;
                    } ?>
                </div>
            </div>
        
            <h2 style="text-align: center">7 Семестр</h2>
            <div class="container">
                <div class="row">
                    <?php 
                    while($semestr < 12) { ?>
                        <div class="col-3">
                            <button onclick="r()" class="button" id="button-<?php  echo $i; ?>" href="#">
                            <span class="text">Проектирование<br>пользовательских<br>интерфейсов</span><br>
                            <progress class="progressbar" id="progressbar-<?php  echo $i; ?>" value="10" max="10"></progress>
                        </button>
                        </div>
                        <?php  
                        ++$semestr;
                        ++$i;
                    } ?>
                </div>
            </div>
        </main>
        <script src="./e.js"></script>
    </body>
</html>
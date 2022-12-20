<?php

function getcheckinfo($checkarr, $checkname)
{
    foreach ($checkarr as $c)
    if (@$c['check'] == $checkname)
    return $c;
}

// Генерация цветного квадрата для элементов с проверками
function generateColorBox($color, $val, $tag)
{
return '<span id='.$tag.' class="rightbadge rb-'.$color.'">'.$val.'</span>';
}

// Разбор и преобразования результата проверки сборки в элемент массива для генерации аккордеона
function parseBuildCheck($data)
{
    $result = 'Успешно';
    $resBody = '';

    $resColorBox = generateColorBox('green', $result, 'build_result').generateColorBox('yellow', "Не реализовано", 'build_msg');

    $resArr = array('header' => '<div class="w-100"><b>Сборка</b>'.$resColorBox.'</div>',
                    
                    'label'	 => '<input id="buildcheck_enabled" name="buildcheck_enabled" checked'. // checked(@$checks['tools']['build']['enabled']).
                                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                    'body'   => $resBody
                    );

    return $resArr;
}

// Разбор и преобразования результата проверки статическим анализатором кода в элемент массива для генерации аккордеона
function parseCppCheck($data)
{
    $resBody = 'Результаты проверок: <br><br>';

    foreach ($data['checks'] as $check)
    {
        switch ($check['outcome'])
        {
            case 'pass':
                $resBody .= 'Было обнаружено '.@$check['result'].' замечаний типа '.@$check['check'].'.<br>';
                $resBody .= 'Количество замечаний типа '.@$check['check'].' не превышает допустимого значения.<br><br>';
                break;	
            case 'fail':
                $resBody .= 'Было обнаружено '.@$check['result'].' замечаний типа '.@$check['check'].'.<br>';
                $resBody .= 'Количество замечаний типа '.@$check['check'].' превышает допустимое значение.<br><br>';
                break;	
            case 'reject':
                $resBody .= 'Не удалось выполнить проверку замечаний типа '.@$check['check'].'.<br><br>';
                break;	
            case 'skipped':
                $resBody .= 'Проверка была замечаний типа '.@$check['check'].' была пропущена.<br><br>';
                break;		
        }
    }

    $boxColor = 'green';
    $boxText = 'Успех';

    foreach ($data['checks'] as $check)
    {
        switch ($check['outcome'])
        {
            case 'fail':
                $boxColor = 'red';
                $boxText = 'Провал проверки на замечания типа '.$check['check'];
                break;	
            case 'reject':
                $boxColor = 'yellow';
                $boxText = 'Не удалось выполнить некоторые проверки';
                break;		
        }
        if ($check['outcome'] == 'fail')
        {
            break;
        }
    }

    $resColorBox = generateColorBox($boxColor, $boxText, 'cppcheck_result');

    $resArr = array('header' => '<div class="w-100"><b>CppCheck</b>'.$resColorBox.'</div>',

                    'label'	 => '<input id="cppcheck_enabled" name="cppcheck_enabled" checked'. // checked(@$checks['tools']['valgrind']['enabled']).
                                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                
                    'body'   => $resBody
                    );

    return $resArr;
}

// Разбор и преобразования результата проверки корректного форматирования кода в элемент массива для генерации аккордеона
function parseClangFormat($data)
{
    $resBody = $data['output'];

    $check = $data['check']; 

    switch ($check['outcome'])
    {
        case 'pass':
            $resBody .= 'Количество замечаний линтера не превышает допустимого значения.<br>';
            $boxColor = 'green';
            $boxText = 'Успех';
            break;	
        case 'fail':
            $resBody .= 'Количество замечаний линтера превышает допустимое значение.<br>';
            $boxColor = 'red';
            $boxText = 'Провал';
            break;	
        case 'reject':
            $resBody .= 'Не удалось выполнить проверку.<br>';
            $boxColor = 'yellow';
            $boxText = 'Не удалось';
            break;	
        case 'skipped':
            $resBody .= 'Проверка была пропущена.<br>';
            $boxColor = 'yellow';
            $boxText = 'Пропущен';
            break;		
    }

    $resColorBox = generateColorBox($boxColor, $boxText, 'clangformat_result');

    $resBody .= 'Замечаний линтера: '.$check['result'].'<br>';

    $resArr = array('header' => '<div class="w-100"><b>Clang-format</b>'.$resColorBox.'</div>',

                    'label'	 => '<input id="clangformat_enabled" name="clangformat_enabled" checked'. // checked(@$checks['tools']['valgrind']['enabled']).
                                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                
                    'body'   => $resBody
                    );

    return $resArr;
}

// Разбор и преобразования результата проверки ошибок работы с памятью в элемент массива для генерации аккордеона
function parseValgrind($data)
{
    $leaks = getcheckinfo($data['checks'], 'leaks');
    $errors = getcheckinfo($data['checks'], 'errors');

    $resBody = '';

    switch ($leaks['outcome'])
    {
        case 'pass':
            $resBody .= 'Количество утечек памяти не превышает допустимого значения.<br>';
            $leaksColor = 'green';
            break;	
        case 'fail':
            $resBody .= 'Количество утечек памяти превышает допустимое значение.<br>';
            $leaksColor = 'red';
            break;	
        case 'reject':
            $resBody .= 'Не удалось выполнить проверку на утечки памяти.<br>';
            $leaksColor = 'yellow';
            break;	
        case 'skipped':
            $resBody .= 'Этап проверки на утечки памяти был пропущен.<br>';
            $leaksColor = 'yellow';
            break;		
    }

    switch ($errors['outcome'])
    {
        case 'pass':
            $resBody .= 'Количество ошибок памяти не превышает допустимого значения.<br>';
            $errorsColor = 'green';
            break;	
        case 'fail':
            $resBody .= 'Количество ошибок памяти превышает допустимое значение.<br>';
            $errorsColor = 'red';
            break;	
        case 'reject':
            $resBody .= 'Не удалось выполнить проверку на ошибки памяти.<br>';
            $errorsColor = 'yellow';
            break;	
        case 'skipped':
            $resBody .= 'Этап проверки на ошибки памяти был пропущен.<br>';
            $errorsColor = 'yellow';
            break;		
    }

    $resBody .= '<br>Утечки памяти: '.$leaks['result'].'<br>';
    $resBody .= 'Ошибки памяти: '.$errors['result'].'<br>';
    $resBody .= '<br>Вывод Valgrind: <br>'.$data['output'];

    $resColorBox = generateColorBox($errorsColor, $errors['result'].' errors', 'valgrind_errors').
                    generateColorBox($leaksColor, $leaks['result'].' leaks', 'valgrind_leaks');

    $resArr = array('header' => '<div class="w-100"><b>Valgrind</b>'.$resColorBox.'</div>',

                    'label'	 => '<input id="valgrind_enabled" name="valgrind_enabled" checked'. // checked(@$checks['tools']['valgrind']['enabled']).
                                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                
                    'body'   => $resBody
                    );

    return $resArr;
}

// Разбор и преобразования результата вывода автотестов в элемент массива для генерации аккордеона
function parseAutoTests($data)
{
    $result = 'Успех';
    $resBody = 'Not implemented yet';

    $resColorBox = generateColorBox('green', 4, 'autotest_passed').generateColorBox('red', 12, 'autotest_failed').generateColorBox('yellow', "Не реализовано", 'autotest_msg');

    $resArr = array('header' => '<div class="w-100"><b>Автотесты</b>'.$resColorBox.'</div>',

                    'label'	 => '<input id="autotest_enabled" name="autotest_enabled" checked'. // checked(@$checks['tools']['valgrind']['enabled']).
                                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                
                    'body'   => $resBody
                    );

    return $resArr;
}

// Разбор и преобразования результата проверки антиплагиатом в элемент массива для генерации аккордеона
function parseCopyDetect($data)
{
    $result = $data['check']['result'].'%';


    switch ($data['check']['outcome'])
    {
        case 'pass':
            $resBody = 'Проверка пройдена успешно.';
            $resColorBox = generateColorBox('green', $result, 'copydetect_result');
            break;	
        case 'fail':
            $resBody = 'Проверка провалена.';
            $resColorBox = generateColorBox('red', $result, 'copydetect_result');
            break;	
        case 'reject':
            $resBody = 'Не удалось выполнить проверку.';
            $resColorBox = generateColorBox('yellow', 'Не удалось', 'copydetect_result');
            break;	
        case 'skipped':
            $resBody = 'Проверка пропущена.';
            $resColorBox = generateColorBox('yellow', 'Пропущен', 'copydetect_result');
            break;		
    }

    $resBody .=  '<br><br>'.$data['output'];

    $resArr = array('header' => '<div class="w-100"><b>Антиплагиат</b>'.$resColorBox.'</div>',

                    'label'	 => '<input id="copydetect_enabled" name="copydetect_enabled" checked'. // checked(@$checks['tools']['valgrind']['enabled']).
                                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                
                    'body'   => $resBody
                    );

    return $resArr;
}
?>
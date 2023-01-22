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

function generateTaggedValue($tag, $val)
{
    return '<span id='.$tag.'>'.$val.'</span>';
}

// Разбор и преобразования результата проверки сборки в элемент массива для генерации аккордеона
function parseBuildCheck($data, $checks)
{
    $result = 0;
    $resBody = '';

    $resBody .= '<label for="build" id="buildlabel" class="switchcon">+ показать полный вывод</label>'.
                '<pre id="build" class="axconsole">Загрузка...</pre>';

    $resColorBox = generateColorBox('green', $result, 'build_result');

    $resArr = array('header' => '<div class="w-100"><b>Сборка</b>'.$resColorBox.'</div>',
                    
                    'label'	 => '<input id="buildcheck_enabled" name="buildcheck_enabled" '.((@$checks['tools']['build']['enabled'] == 'true') ? 'checked' : '').
                                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                    'body'   => $resBody
                    );

    return $resArr;
}

// Разбор и преобразования результата проверки статическим анализатором кода в элемент массива для генерации аккордеона
function parseCppCheck($data, $checks)
{
    $resBody = '';
    $sumOfErrors = 0;

    foreach ($data['checks'] as $check)
    {
        switch ($check['outcome'])
        {
            case 'pass':
            case 'fail':
                $resBody .= @$check['check'].' : '.generateTaggedValue('cppcheck_'.@$check['check'] , @$check['result']).'<br>';
                break;	
            case 'reject':
            case 'skipped':
                $resBody .= 'Проверка была замечаний типа '.@$check['check'].' была пропущена.<br><br>';
                break;		
        }
        $sumOfErrors += @$check['result'];
    }

    $resBody .= '<label for="cppcheck" id="cppchecklabel" class="switchcon">+ показать полный вывод</label>'.
                '<pre id="cppcheck" class="axconsole">Загрузка...</pre>';

    $boxColor = 'green';
    $boxText = $sumOfErrors;

    foreach ($data['checks'] as $check)
    {
        switch ($check['outcome'])
        {
            case 'fail':
                $boxColor = 'red';
                break;	
            case 'reject':
                $boxColor = 'yellow';
                break;		
        }
        if ($check['outcome'] == 'fail')
        {
            break;
        }
    }

    $resColorBox = generateColorBox($boxColor, $boxText, 'cppcheck_result');

    $resArr = array('header' => '<div class="w-100"><b>CppCheck</b>'.$resColorBox.'</div>',

                    'label'	 => '<input id="cppcheck_enabled" name="cppcheck_enabled" '. ((@$checks['tools']['cppcheck']['enabled'] == 'true') ? 'checked' : '').
                                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                
                    'body'   => $resBody
                    );

    return $resArr;
}

// Разбор и преобразования результата проверки корректного форматирования кода в элемент массива для генерации аккордеона
function parseClangFormat($data, $checks)
{
    $resBody = $data['output'];
    $check = $data['check']; 
    $boxText = $check['result'];

    switch ($check['outcome'])
    {
        case 'pass':
            $boxColor = 'green';
            break;
        case 'reject':
        case 'fail':
            $boxColor = 'red';
            break;	
        case 'skipped':
            $resBody .= 'Проверка была пропущена.<br>';
            $boxColor = 'yellow';
            break;		
    }

    $resColorBox = generateColorBox($boxColor, $boxText, 'clangformat_result');

    $resBody .= 'Замечаний линтера: '.generateTaggedValue('clangformat_result_inner', @$check['result']).'<br>';

    $resBody .= '<label for="format" id="formatlabel" class="switchcon">+ показать полный вывод</label>'.
                '<pre id="format" class="axconsole">Загрузка...</pre>';

    $resArr = array('header' => '<div class="w-100"><b>Clang-format</b>'.$resColorBox.'</div>',

                    'label'	 => '<input id="clangformat_enabled" name="clangformat_enabled" '.((@$checks['tools']['clang-format']['enabled'] == 'true') ? 'checked' : '').
                                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                
                    'body'   => $resBody
                    );

    return $resArr;
}

// Разбор и преобразования результата проверки ошибок работы с памятью в элемент массива для генерации аккордеона
function parseValgrind($data, $checks)
{
    $leaks = getcheckinfo($data['checks'], 'leaks');
    $errors = getcheckinfo($data['checks'], 'errors');

    $resBody = '';

    switch ($leaks['outcome'])
    {
        case 'pass':
            $leaksColor = 'green';
            break;
        case 'reject':	
        case 'fail':
            $leaksColor = 'red';
            break;	
        case 'skipped':
            $resBody .= 'Этап проверки на утечки памяти был пропущен.<br>';
            $leaksColor = 'yellow';
            break;		
    }

    switch ($errors['outcome'])
    {
        case 'pass':
            $errorsColor = 'green';
            break;	
        case 'reject':
        case 'fail':
            $errorsColor = 'red';
            break;	
        case 'skipped':
            $resBody .= 'Этап проверки на ошибки памяти был пропущен.<br>';
            $errorsColor = 'yellow';
            break;		
    }

    $resBody .= 'Утечки памяти: '.generateTaggedValue('valgrind_leaks_inner', @$leaks['result']).'<br>';
    $resBody .= 'Ошибки памяти: '.generateTaggedValue('valgrind_errors_inner', @$errors['result']).'<br>';
    //$resBody .= '<br>Вывод Valgrind: <br>'.$data['output'];
    $resBody .= '<label for="valgrind" id="valgrindlabel" class="switchcon">+ показать полный вывод</label>'.
                '<pre id="valgrind" class="axconsole">Загрузка...</pre>';

    $resColorBox = generateColorBox($errorsColor, $errors['result'], 'valgrind_errors').
                    generateColorBox($leaksColor, $leaks['result'], 'valgrind_leaks');

    $resArr = array('header' => '<div class="w-100"><b>Valgrind</b>'.$resColorBox.'</div>',

                    'label'	 => '<input id="valgrind_enabled" name="valgrind_enabled" '.((@$checks['tools']['valgrind']['enabled'] == 'true') ? 'checked' : '').
                                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                
                    'body'   => $resBody
                    );

    return $resArr;
}

// Разбор и преобразования результата вывода автотестов в элемент массива для генерации аккордеона
function parseAutoTests($data, $checks)
{
    $result = 0;
    $check = $data['check']; 
    $resBody = 'Проверок провалено: '.generateTaggedValue('autotest_result_inner', $check['failures']).'<br>';;
    $resBody .= '<label for="tests" id="testslabel" class="switchcon">+ показать полный вывод</label>'.
                '<pre id="tests" class="axconsole">Загрузка...</pre>';
    
    $resColorBox = generateColorBox('green', "Passed", 'autotest_result');

    $resArr = array('header' => '<div class="w-100"><b>Автотесты</b>'.$resColorBox.'</div>',

                    'label'	 => '<input id="autotest_enabled" name="autotest_enabled" '. ((@$checks['tools']['autotest']['enabled'] == 'true') ? 'checked' : '').
                                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                
                    'body'   => $resBody
                    );

    return $resArr;
}

// Разбор и преобразования результата проверки антиплагиатом в элемент массива для генерации аккордеона
function parseCopyDetect($data, $checks)
{
    $result = $data['check']['result'].'%';
    $resBody = '';


    switch ($data['check']['outcome'])
    {
        case 'pass':
            $resColorBox = generateColorBox('green', $result, 'copydetect_result');
            break;	
        case 'fail':
            $resColorBox = generateColorBox('red', $result, 'copydetect_result');
            break;	
        case 'reject':
            $resColorBox = generateColorBox('yellow', $result, 'copydetect_result');
            break;	
        case 'skipped':
            $resBody .= 'Проверка пропущена<br>';
            $resColorBox = generateColorBox('yellow', 0, 'copydetect_result');
            break;		
    }

    $resBody .= generateTaggedValue('copydetect_result_inner', $data['output']);

    $resArr = array('header' => '<div class="w-100"><b>Антиплагиат</b>'.$resColorBox.'</div>',

                    'label'	 => '<input id="copydetect_enabled" name="copydetect_enabled" '. ((@$checks['tools']['copydetect']['enabled'] == 'true') ? 'checked' : '').
                                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                
                    'body'   => $resBody
                    );

    return $resArr;
}
?>
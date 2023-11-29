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
    return '<span id=' . $tag . ' class="rightbadge rb-' . $color . '">' . $val . '</span>';
}

function generateTaggedValue($tag, $val)
{
    return '<span id=' . $tag . '>' . $val . '</span>';
}

// Разбор и преобразования результата проверки сборки в элемент массива для генерации аккордеона
function parseBuildCheck($data, $checks)
{
    $resFooter = '<label for="build" id="buildlabel" class="switchcon">+ показать полный вывод</label>' .
        '<pre id="build" class="axconsole">Загрузка...</pre>';

    switch ($data['outcome']) {
        case 'pass':
            break;
        case 'fail':
            return array(
                'header' => '<div class="w-100"><b>Сборка</b>' . generateColorBox('red', 'Ошибка исполнения', 'build_result') . '</div>',
                'label'     => '<input id="buildcheck_enabled" name="buildcheck_enabled" ' . ((@$checks['tools']['build']['enabled'] == 'true') ? 'checked' : '') .
                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                'body'   => generateTaggedValue("build_body", "При выполнении проверки произошла критическая ошибка."),
                'footer' => $resFooter
            );
        case 'skipped':
            return array(
                'header' => '<div class="w-100"><b>Сборка</b><span id="build_result" class="rightbadge"></span></div>',
                'label'     => '<input id="buildcheck_enabled" name="buildcheck_enabled" ' . ((@$checks['tools']['build']['enabled'] == 'true') ? 'checked' : '') .
                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                'body'   => generateTaggedValue("build_body", "Проверка пропущена или инструмент проверки не установлен."),
                'footer' => $resFooter
            );
            break;
    }

    $resBody = '';
    $check = $data['check'];

    switch ($check['outcome']) {
        case 'pass':
            $boxColor = 'green';
            $boxText = 'Успех';
            break;
        case 'reject':
            $boxColor = 'red';
            $boxText = 'Неудача';
            break;
        case 'fail':
            $boxColor = 'yellow';
            $boxText = 'Неудача';
            break;
    }

    $resColorBox = generateColorBox($boxColor, $boxText, 'build_result');
    $resArr = array(
        'header' => '<div class="w-100"><b>Сборка</b>' . $resColorBox . '</div>',

        'label'     => '<input id="buildcheck_enabled" name="buildcheck_enabled" ' . ((@$checks['tools']['build']['enabled'] == 'true') ? 'checked' : '') .
            ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
        'body'   => generateTaggedValue("build_body", "Проект был собран успешно."),
        'footer' => $resFooter
    );

    return $resArr;
}

// Разбор и преобразования результата проверки статическим анализатором кода в элемент массива для генерации аккордеона
function parseCppCheck($data, $checks)
{
    $resFooter = '<label for="cppcheck" id="cppchecklabel" class="switchcon">+ показать полный вывод</label>' .
        '<pre id="cppcheck" class="axconsole">Загрузка...</pre>';

    switch ($data['outcome']) {
        case 'pass':
            break;
        case 'fail':
            return array(
                'header' => '<div class="w-100"><b>CppCheck</b>' . generateColorBox('red', 'Ошибка исполнения', 'cppcheck_result') . '</div>',
                'label'     => '<input id="cppcheck_enabled" name="cppcheck_enabled" ' . ((@$checks['tools']['cppcheck']['enabled'] == 'true') ? 'checked' : '') .
                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                'body'   => generateTaggedValue("cppcheck_body", "При выполнении проверки произошла критическая ошибка."),
                'footer' => $resFooter
            );
        case 'skipped':
            return array(
                'header' => '<div class="w-100"><b>CppCheck</b><span id="cppcheck_result" class="rightbadge"></span></div>',
                'label'     => '<input id="cppcheck_enabled" name="cppcheck_enabled" ' . ((@$checks['tools']['cppcheck']['enabled'] == 'true') ? 'checked' : '') .
                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                'body'   => generateTaggedValue("cppcheck_body", "Проверка пропущена или инструмент проверки не установлен."),
                'footer' => $resFooter
            );
            break;
    }

    $resBody = '';
    $sumOfErrors = 0;

    foreach ($data['checks'] as $check) {
        $resBody .= @$check['check'] . ' : ' . @$check['result'] . '<br>';
        $sumOfErrors += @$check['result'];
    }

    $boxColor = 'green';
    $boxText = $sumOfErrors;

    foreach ($data['checks'] as $check) {
        switch ($check['outcome']) {
            case 'fail':
                $boxColor = 'yellow';
                break;
            case 'reject':
                $boxColor = 'red';
                break;
        }
        if ($check['outcome'] == 'reject') {
            break;
        }
    }

    $resColorBox = generateColorBox($boxColor, $boxText, 'cppcheck_result');

    $resArr = array(
        'header' => '<div class="w-100"><b>CppCheck</b>' . $resColorBox . '</div>',

        'label'     => '<input id="cppcheck_enabled" name="cppcheck_enabled" ' . ((@$checks['tools']['cppcheck']['enabled'] == 'true') ? 'checked' : '') .
            ' class="accordion-input-item form-check-input" type="checkbox" value="true">',

        'body'   => generateTaggedValue("cppcheck_body", $resBody),
        'footer' => $resFooter
    );

    return $resArr;
}

// Разбор и преобразования результата проверки корректного форматирования кода в элемент массива для генерации аккордеона
function parseClangFormat($data, $checks)
{
    $resFooter = '<label for="format" id="formatlabel" class="switchcon">+ показать полный вывод</label>' .
        '<pre id="format" class="axconsole">Загрузка...</pre>';

    switch ($data['outcome']) {
        case 'pass':
            break;
        case 'fail':
            return array(
                'header' => '<div class="w-100"><b>Clang-format</b>' . generateColorBox('red', 'Ошибка исполнения', 'clangformat_result') . '</div>',
                'label'     => '<input id="clangformat_enabled" name="clangformat_enabled" ' . ((@$checks['tools']['clang-format']['enabled'] == 'true') ? 'checked' : '') .
                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                'body'   => generateTaggedValue("clangformat_body", "При выполнении проверки произошла критическая ошибка."),
                'footer' => $resFooter
            );
        case 'skipped':
            return array(
                'header' => '<div class="w-100"><b>Clang-format</b><span id="clangformat_result" class="rightbadge"></span></div>',
                'label'     => '<input id="clangformat_enabled" name="clangformat_enabled" ' . ((@$checks['tools']['clang-format']['enabled'] == 'true') ? 'checked' : '') .
                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                'body'   => generateTaggedValue("clangformat_body", "Проверка пропущена или инструмент проверки не установлен."),
                'footer' => $resFooter
            );
            break;
    }

    $resBody = $data['outcome'];
    $check = $data['check'];
    $boxText = $check['result'];

    switch ($check['outcome']) {
        case 'pass':
            $boxColor = 'green';
            $resBody = "Проверка пройдена!";
            break;
        case 'reject':
            $boxColor = 'red';
            $resBody = "Проверка отменена!";
            break;
        case 'fail':
            $boxColor = 'yellow';
            $resBody = "Проверка не пройдена!";
            break;
    }

    $resColorBox = generateColorBox($boxColor, $boxText, 'clangformat_result');
    $resBody .= '</br>Замечаний линтера: ' . @$check['result'] . '<br>';

    $resArr = array(
        'header' => '<div class="w-100"><b>Clang-format</b>' . $resColorBox . '</div>',

        'label'     => '<input id="clangformat_enabled" name="clangformat_enabled" ' . ((@$checks['tools']['clang-format']['enabled'] == 'true') ? 'checked' : '') .
            ' class="accordion-input-item form-check-input" type="checkbox" value="true">',

        'body'   => generateTaggedValue('clangformat_body', $resBody),
        'footer' => $resFooter
    );

    return $resArr;
}

// Разбор и преобразования результата проверки ошибок работы с памятью в элемент массива для генерации аккордеона
function parseValgrind($data, $checks)
{
    $resFooter = '<label for="valgrind" id="valgrindlabel" class="switchcon">+ показать полный вывод</label>' .
        '<pre id="valgrind" class="axconsole">Загрузка...</pre>';

    switch ($data['outcome']) {
        case 'pass':
            break;
        case 'fail':
            return array(
                'header' => '<div class="w-100"><b>Valgrind</b>' . generateColorBox('red', 'Ошибка исполнения', 'valgrind_errors') . generateColorBox('red', 'Ошибка исполнения', 'valgrind_leaks') . '</div>',
                'label'     => '<input id="valgrind_enabled" name="valgrind_enabled" ' . ((@$checks['tools']['valgrind']['enabled'] == 'true') ? 'checked' : '') .
                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                'body'   => generateTaggedValue("valgrind_body", "При выполнении проверки произошла критическая ошибка."),
                'footer' => $resFooter
            );
        case 'skipped':
            return array(
                'header' => '<div class="w-100"><b>Valgrind</b><span id="valgrind_errors" class="rightbadge"></span><span id="valgrind_leaks" class="rightbadge"></span></div>',
                'label'     => '<input id="valgrind_enabled" name="valgrind_enabled" ' . ((@$checks['tools']['valgrind']['enabled'] == 'true') ? 'checked' : '') .
                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                'body'   => generateTaggedValue("valgrind_body", "Проверка пропущена или инструмент проверки не установлен."),
                'footer' => $resFooter
            );
            break;
    }


    $leaks = getcheckinfo($data['checks'], 'leaks');
    $errors = getcheckinfo($data['checks'], 'errors');

    $resBody = '';

    switch ($leaks['outcome']) {
        case 'pass':
            $leaksColor = 'green';
            break;
        case 'reject':
            $leaksColor = 'red';
            break;
        case 'fail':
            $leaksColor = 'yellow';
            break;
    }

    switch ($errors['outcome']) {
        case 'pass':
            $errorsColor = 'green';
            break;
        case 'reject':
            $errorsColor = 'red';
            break;
        case 'fail':
            $errorsColor = 'yellow';
            break;
    }

    $resBody .= 'Утечки памяти: ' . @$leaks['result'] . '<br>';
    $resBody .= 'Ошибки памяти: ' . @$errors['result'] . '<br>';
    //$resBody .= '<br>Вывод Valgrind: <br>'.$data['output'];

    $resColorBox = generateColorBox($errorsColor, $errors['result'], 'valgrind_errors') .
        generateColorBox($leaksColor, $leaks['result'], 'valgrind_leaks');

    $resArr = array(
        'header' => '<div class="w-100"><b>Valgrind</b>' . $resColorBox . '</div>',

        'label'     => '<input id="valgrind_enabled" name="valgrind_enabled" ' . ((@$checks['tools']['valgrind']['enabled'] == 'true') ? 'checked' : '') .
            ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
        'body'   => generateTaggedValue("valgrind_body", $resBody),
        'footer' => $resFooter
    );

    return $resArr;
}

// Разбор и преобразования результата вывода автотестов в элемент массива для генерации аккордеона
function parseAutoTests($data, $checks)
{
    $resFooter = '<label for="tests" id="testslabel" class="switchcon">+ показать полный вывод</label>' .
        '<pre id="tests" class="axconsole">Загрузка...</pre>';

    switch ($data['outcome']) {
        case 'pass':
            break;
        case 'fail':
            return array(
                'header' => '<div class="w-100"><b>Автотесты</b>' . generateColorBox('red', 'Ошибка исполнения', 'autotests_result') . '</div>',
                'label'     => '<input id="autotests_enabled" name="autotests_enabled" ' . ((@$checks['tools']['autotests']['enabled'] == 'true') ? 'checked' : '') .
                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                'body'   => generateTaggedValue("autotests_body", "При выполнении проверки произошла критическая ошибка."),
                'footer' => $resFooter
            );
        case 'skipped':
            return array(
                'header' => '<div class="w-100"><b>Автотесты</b><span id="autotests_result" class="rightbadge"></span></div>',
                'label'     => '<input id="autotests_enabled" name="autotests_enabled" ' . ((@$checks['tools']['autotests']['enabled'] == 'true') ? 'checked' : '') .
                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                'body'   => generateTaggedValue("autotests_body", "Проверка пропущена или инструмент проверки не установлен."),
                'footer' => $resFooter
            );
            break;
    }

    $result = 0;
    $check = $data['check'];

    switch ($check['outcome']) {
        case 'pass':
            $boxColor = 'green';
            $boxText = 'Успех';
            break;
        case 'reject':
            $boxColor = 'red';
            $boxText = 'Неудача';
            break;
        case 'fail':
            $boxColor = 'yellow';
            $boxText = 'Неудача';
            break;
    }

    $resBody = 'Тестов провалено: ' . $check['errors'] . '<br>';
    $resBody .= 'Проверок провалено: ' . $check['failures'] . '<br>';
    $resColorBox = generateColorBox($boxColor, $boxText, 'autotests_result');

    $resArr = array(
        'header' => '<div class="w-100"><b>Автотесты</b>' . $resColorBox . '</div>',

        'label'     => '<input id="autotests_enabled" name="autotests_enabled" ' . ((@$checks['tools']['autotests']['enabled'] == 'true') ? 'checked' : '') .
            ' class="accordion-input-item form-check-input" type="checkbox" value="true">',

        'body'   => generateTaggedValue("autotests_body", $resBody),
        'footer' => $resFooter
    );

    return $resArr;
}

// Разбор и преобразования результата проверки антиплагиатом в элемент массива для генерации аккордеона
function parseCopyDetect($data, $checks)
{
    switch ($data['outcome']) {
        case 'pass':
            break;
        case 'fail':
            return array(
                'header' => '<div class="w-100"><b>Антиплагиат</b>' . generateColorBox('red', 'Ошибка исполнения', 'copydetect_result') . '</div>',
                'label'     => '<input id="copydetect_enabled" name="copydetect_enabled" ' . ((@$checks['tools']['copydetect']['enabled'] == 'true') ? 'checked' : '') .
                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                'body'   => generateTaggedValue("copydetect_body", "При выполнении проверки произошла критическая ошибка."),
                'footer' => ''
            );
        case 'skipped':
            return array(
                'header' => '<div class="w-100"><b>Антиплагиат</b><span id="copydetect_result" class="rightbadge"></span></div>',
                'label'     => '<input id="copydetect_enabled" name="copydetect_enabled" ' . ((@$checks['tools']['copydetect']['enabled'] == 'true') ? 'checked' : '') .
                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                'body'   => generateTaggedValue("copydetect_body", "Проверка пропущена или инструмент проверки не установлен."),
                'footer' => ''
            );
            break;
    }

    $check = $data['check'];
    $result = $check['result'] . '%';
    $resBody = '';

    switch ($data['check']['outcome']) {
        case 'pass':
            $boxColor = 'green';
            break;
        case 'fail':
            $boxColor = 'yellow';
            break;
        case 'reject':
            $boxColor = 'red';
            break;
        case 'skipped':
            $boxColor = 'grey';
            break;
    }

    $resArr = array(
        'header' => '<div class="w-100"><b>Антиплагиат</b>' . generateColorBox($boxColor, $result, 'copydetect_result') . '</div>',
        'label'     => '<input id="copydetect_enabled" name="copydetect_enabled" ' . ((@$checks['tools']['copydetect']['enabled'] == 'true') ? 'checked' : '') .
            ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
        'body'   => generateTaggedValue("copydetect_body", $resBody),
        'footer' => ''
    );

    return $resArr;
}

<?php

function getAccordionToolsHtml($checks, $checkres, $User)
{
    $accord = array();
    if (!$User->isStudent()) {
        if (isset($checks['tools']['build']))
            array_push($accord, parseBuildCheck(@$checkres['tools']['build'], @$checkres['tools']['build']['enabled']));
        if (isset($checks['tools']['cppcheck']))
            array_push($accord, parseCppCheck(@$checkres['tools']['cppcheck'], @$checkres['tools']['cppcheck']['enabled']));
        if (isset($checks['tools']['clang-format']))
            array_push($accord, parseClangFormat(@$checkres['tools']['clang-format'], @$checkres['tools']['clang-format']['enabled']));
        if (isset($checks['tools']['valgrind']))
            array_push($accord, parseValgrind(@$checkres['tools']['valgrind'], @$checkres['tools']['valgrind']['enabled']));
        if (isset($checks['tools']['pylint']))
            array_push($accord, parsePylint(@$checkres['tools']['pylint'], @$checkres['tools']['pylint']['enabled']));
        if (isset($checks['tools']['pytest']))
            array_push($accord, parsePytest(@$checkres['tools']['pytest'], @$checkres['tools']['pytest']['enabled']));
        if (isset($checks['tools']['autotests']))
            array_push($accord, parseAutoTests(@$checkres['tools']['autotests'], @$checkres['tools']['autotests']['enabled']));
        if (isset($checks['tools']['copydetect']))
            array_push($accord, parseCopyDetect(@$checkres['tools']['copydetect'], @$checkres['tools']['copydetect']['enabled']));
    } else {
        if (isset($checks['tools']['build']) && $checks['tools']['build']['show_to_student'])
            array_push($accord, parseBuildCheck(@$checkres['tools']['build'], @$checkres['tools']['build']['enabled']));
        if (isset($checks['tools']['cppcheck']) && $checks['tools']['cppcheck']['show_to_student'])
            array_push($accord, parseCppCheck(@$checkres['tools']['cppcheck'], @$checkres['tools']['cppcheck']['enabled']));
        if (isset($checks['tools']['clang-format']) && $checks['tools']['clang-format']['show_to_student'])
            array_push($accord, parseClangFormat(@$checkres['tools']['clang-format'], @$checkres['tools']['clang-format']['enabled']));
        if (isset($checks['tools']['valgrind']) && $checks['tools']['valgrind']['show_to_student'])
            array_push($accord, parseValgrind(@$checkres['tools']['valgrind'], @$checkres['tools']['valgrind']['enabled']));
        if (isset($checks['tools']['pylint']) && $checks['tools']['pylint']['show_to_student'])
            array_push($accord, parsePylint(@$checkres['tools']['pylint'], @$checkres['tools']['pylint']['enabled']));
        if (isset($checks['tools']['pytest']) && $checks['tools']['pytest']['show_to_student'])
            array_push($accord, parsePytest(@$checkres['tools']['pytest'], @$checkres['tools']['pytest']['enabled']));
        if (isset($checks['tools']['autotests']) && $checks['tools']['autotests']['show_to_student'])
            array_push($accord, parseAutoTests(@$checkres['tools']['autotests'], @$checkres['tools']['autotests']['enabled']));
        if (isset($checks['tools']['copydetect']) && $checks['tools']['copydetect']['show_to_student'])
            array_push($accord, parseCopyDetect(@$checkres['tools']['copydetect'], @$checkres['tools']['copydetect']['enabled']));
    }
    return $accord;
}

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
function parseBuildCheck($data, $enabled)
{
    $resFooter = '<label for="build" id="buildlabel" class="switchcon" style="cursor: pointer;">+ показать полный вывод</label>' .
        '<pre id="build" class="axconsole">Загрузка...</pre>';

    if (!array_key_exists('outcome', $data)) {
        return array(
            'header' => '<div class="w-100"><b>' . $data['language'] . ' Build</b>' . generateColorBox('gray', 'Build не удался', 'build_result') . '</div>',
            'label'     => '<input id="buildcheck_enabled" name="buildcheck_enabled" ' . ((@$enabled == 'true') ? 'checked' : '') .
                ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
            'body'   => generateTaggedValue("build_body", "При выполнении проверки произошла критическая ошибка."),
            'footer' => $resFooter
        );
    }

    switch ($data['outcome']) {
        case 'pass':
            break;
        case 'fail':
            return array(
                'header' => '<div class="w-100"><b>' . $data['language'] . ' Build</b>' . generateColorBox('red', 'Проверка не пройдена', 'build_result') . '</div>',
                'label'     => '<input id="buildcheck_enabled" name="buildcheck_enabled" ' . ((@$enabled == 'true') ? 'checked' : '') .
                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                'body'   => generateTaggedValue("build_body", "При выполнении проверки произошла критическая ошибка."),
                'footer' => $resFooter
            );
        case 'skip':
            return array(
                'header' => '<div class="w-100"><b>' . $data['language'] . ' Build</b><span id="build_result" class="rightbadge"></span></div>',
                'label'     => '<input id="buildcheck_enabled" name="buildcheck_enabled" ' . ((@$enabled == 'true') ? 'checked' : '') .
                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                'body'   => generateTaggedValue("build_body", "Проверка пропущена или инструмент проверки не установлен."),
                'footer' => $resFooter
            );
            break;
        case 'undefined':
            return array(
                'header' => '<div class="w-100"><b>' . $data['language'] . ' Build</b><span id="build_result" class="rightbadge"></span></div>',
                'label'     => '<input id="buildcheck_enabled" name="buildcheck_enabled" ' . ((@$enabled == 'true') ? 'checked' : '') .
                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                'body'   => generateTaggedValue("build_body", "Не проверено."),
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
        'header' => '<div class="w-100"><b>' . $data['language'] . ' Build</b>' . $resColorBox . '</div>',

        'label'     => '<input id="buildcheck_enabled" name="buildcheck_enabled" ' . ((@$enabled == 'true') ? 'checked' : '') .
            ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
        'body'   => generateTaggedValue("build_body", "Проект был собран успешно."),
        'footer' => $resFooter
    );

    return $resArr;
}

// Разбор и преобразования результата проверки статическим анализатором кода в элемент массива для генерации аккордеона
function parseCppCheck($data, $enabled)
{
    $resFooter = '<label for="cppcheck" id="cppchecklabel" class="switchcon" style="cursor: pointer;">+ показать полный вывод</label>' .
        '<pre id="cppcheck" class="axconsole">Загрузка...</pre>';

    if (!array_key_exists('outcome', $data)) {
        return array(
            'header' => '<div class="w-100"><b>CppCheck</b>' . generateColorBox('gray', 'Build не удался', 'cppcheck_result') . '</div>',
            'label'     => '<input id="cppcheck_enabled" name="cppcheck_enabled" ' . ((@$enabled == 'true') ? 'checked' : '') .
                ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
            'body'   => generateTaggedValue("cppcheck_body", "При выполнении проверки произошла критическая ошибка."),
            'footer' => $resFooter
        );
    }

    switch ($data['outcome']) {
        case 'pass':
            break;
        case 'fail':
            return array(
                'header' => '<div class="w-100"><b>CppCheck</b>' . generateColorBox('red', 'Проверка не пройдена', 'cppcheck_result') . '</div>',
                'label'     => '<input id="cppcheck_enabled" name="cppcheck_enabled" ' . ((@$enabled == 'true') ? 'checked' : '') .
                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                'body'   => generateTaggedValue("cppcheck_body", "При выполнении проверки произошла критическая ошибка."),
                'footer' => $resFooter
            );
        case 'skip':
            return array(
                'header' => '<div class="w-100"><b>CppCheck</b><span id="cppcheck_result" class="rightbadge"></span></div>',
                'label'     => '<input id="cppcheck_enabled" name="cppcheck_enabled" ' . ((@$enabled == 'true') ? 'checked' : '') .
                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                'body'   => generateTaggedValue("cppcheck_body", "Проверка пропущена или инструмент проверки не установлен."),
                'footer' => $resFooter
            );
            break;
        case 'undefined':
            return array(
                'header' => '<div class="w-100"><b>CppCheck</b><span id="cppcheck_result" class="rightbadge"></span></div>',
                'label'     => '<input id="cppcheck_enabled" name="cppcheck_enabled" ' . ((@$enabled == 'true') ? 'checked' : '') .
                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                'body'   => generateTaggedValue("cppcheck_body", "Не проверено."),
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

        'label'     => '<input id="cppcheck_enabled" name="cppcheck_enabled" ' . ((@$enabled == 'true') ? 'checked' : '') .
            ' class="accordion-input-item form-check-input" type="checkbox" value="true">',

        'body'   => generateTaggedValue("cppcheck_body", $resBody),
        'footer' => $resFooter
    );

    return $resArr;
}

// Разбор и преобразования результата проверки корректного форматирования кода в элемент массива для генерации аккордеона
function parseClangFormat($data, $enabled)
{
    $resFooter = '<label for="format" id="formatlabel" class="switchcon" style="cursor: pointer;">+ показать полный вывод</label>' .
        '<pre id="format" class="axconsole">Загрузка...</pre>';

    if (!array_key_exists('outcome', $data)) {
        return array(
            'header' => '<div class="w-100"><b>Clang-format</b>' . generateColorBox('gray', 'Build не удался', 'clangformat_result') . '</div>',
            'label'     => '<input id="clangformat_enabled" name="clangformat_enabled" ' . ((@$enabled == 'true') ? 'checked' : '') .
                ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
            'body'   => generateTaggedValue("clangformat_body", "При выполнении проверки произошла критическая ошибка."),
            'footer' => $resFooter
        );
    }

    switch ($data['outcome']) {
        case 'pass':
            break;
        case 'fail':
            return array(
                'header' => '<div class="w-100"><b>Clang-format</b>' . generateColorBox('red', 'Проверка не пройдена', 'clangformat_result') . '</div>',
                'label'     => '<input id="clangformat_enabled" name="clangformat_enabled" ' . ((@$enabled == 'true') ? 'checked' : '') .
                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                'body'   => generateTaggedValue("clangformat_body", "При выполнении проверки произошла критическая ошибка."),
                'footer' => $resFooter
            );
        case 'skip':
            return array(
                'header' => '<div class="w-100"><b>Clang-format</b><span id="clangformat_result" class="rightbadge"></span></div>',
                'label'     => '<input id="clangformat_enabled" name="clangformat_enabled" ' . ((@$enabled == 'true') ? 'checked' : '') .
                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                'body'   => generateTaggedValue("clangformat_body", "Проверка пропущена или инструмент проверки не установлен."),
                'footer' => $resFooter
            );
            break;
        case 'undefined':
            return array(
                'header' => '<div class="w-100"><b>Clang-format</b><span id="clangformat_result" class="rightbadge"></span></div>',
                'label'     => '<input id="clangformat_enabled" name="clangformat_enabled" ' . ((@$enabled == 'true') ? 'checked' : '') .
                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                'body'   => generateTaggedValue("clangformat_body", "Не проверено."),
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

        'label'     => '<input id="clangformat_enabled" name="clangformat_enabled" ' . ((@$enabled == 'true') ? 'checked' : '') .
            ' class="accordion-input-item form-check-input" type="checkbox" value="true">',

        'body'   => generateTaggedValue('clangformat_body', $resBody),
        'footer' => $resFooter
    );

    return $resArr;
}

// Разбор и преобразования результата проверки ошибок работы с памятью в элемент массива для генерации аккордеона
function parseValgrind($data, $enabled)
{
    $resFooter = '<label for="valgrind" id="valgrindlabel" class="switchcon" style="cursor: pointer;">+ показать полный вывод</label>' .
        '<pre id="valgrind" class="axconsole">Загрузка...</pre>';

    if (!array_key_exists('outcome', $data)) {
        return array(
            'header' => '<div class="w-100"><b>Valgrind</b>' . generateColorBox('gray', 'Build не удался', 'valgrind_errors') . generateColorBox('red', '', 'valgrind_leaks') . '</div>',
            'label'     => '<input id="valgrind_enabled" name="valgrind_enabled" ' . ((@$enabled == 'true') ? 'checked' : '') .
                ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
            'body'   => generateTaggedValue("valgrind_body", "При выполнении проверки произошла критическая ошибка."),
            'footer' => $resFooter
        );
    }

    switch ($data['outcome']) {
        case 'pass':
            break;
        case 'fail':
            return array(
                'header' => '<div class="w-100"><b>Valgrind</b>' . generateColorBox('red', 'Проверка не пройдена', 'valgrind_errors') . generateColorBox('red', 'Проверка не пройдена', 'valgrind_leaks') . '</div>',
                'label'     => '<input id="valgrind_enabled" name="valgrind_enabled" ' . ((@$enabled == 'true') ? 'checked' : '') .
                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                'body'   => generateTaggedValue("valgrind_body", "При выполнении проверки произошла критическая ошибка."),
                'footer' => $resFooter
            );
        case 'skip':
            return array(
                'header' => '<div class="w-100"><b>Valgrind</b><span id="valgrind_errors" class="rightbadge"></span><span id="valgrind_leaks" class="rightbadge"></span></div>',
                'label'     => '<input id="valgrind_enabled" name="valgrind_enabled" ' . ((@$enabled == 'true') ? 'checked' : '') .
                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                'body'   => generateTaggedValue("valgrind_body", "Проверка пропущена или инструмент проверки не установлен."),
                'footer' => $resFooter
            );
            break;
        case 'undefined':
            return array(
                'header' => '<div class="w-100"><b>Valgrind</b><span id="valgrind_errors" class="rightbadge"></span><span id="valgrind_leaks" class="rightbadge"></span></div>',
                'label'     => '<input id="valgrind_enabled" name="valgrind_enabled" ' . ((@$enabled == 'true') ? 'checked' : '') .
                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                'body'   => generateTaggedValue("valgrind_body", "Не проверено."),
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

        'label'     => '<input id="valgrind_enabled" name="valgrind_enabled" ' . ((@$enabled == 'true') ? 'checked' : '') .
            ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
        'body'   => generateTaggedValue("valgrind_body", $resBody),
        'footer' => $resFooter
    );

    return $resArr;
}

// Разбор и преобразования результата проверки статическим анализатором кода в элемент массива для генерации аккордеона
function parsePylint($data, $enabled)
{
    $resFooter = '<label for="pylint" id="pylintlabel" class="switchcon" style="cursor: pointer;">+ показать полный вывод</label>' .
        '<pre id="pylint" class="axconsole">Загрузка...</pre>';

    if (!$enabled) {
        return array(
            'header' => '<div class="w-100"><b>Pylint</b><span id="pylint_result" class="rightbadge"></span></div>',
            'label'     => '<input id="pylint_enabled" name="pylint_enabled" ' . ((@$enabled == 'true') ? 'checked' : '') .
                ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
            'body'   => generateTaggedValue("pylint_body", "Проверка пропущена или инструмент проверки не установлен."),
            'footer' => $resFooter
        );
    }

    if (!checkPylintOutputDataStructure($data)) {
        return array(
            'header' => '<div class="w-100"><b>pylint</b>' . generateColorBox('gray', 'Внутрення ошибка!', 'pylint_result') . '</div>',
            'label'     => '<input id="pylint_enabled" name="pylint_enabled" ' . ((@$enabled == 'true') ? 'checked' : '') .
                ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
            'body'   => generateTaggedValue("pylint_body", "При выполнении проверки произошла критическая ошибка."),
            'footer' => $resFooter
        );
    }

    $resColorBox = "";

    switch ($data['outcome']) {
        case 'pass':
            $resColorBox .= generateColorBox('green', 'Проверка пройдена', 'pylint_result');
            break;
        case 'fail':
            $resColorBox .= generateColorBox('red', 'Проверка не пройдена', 'pylint_result');
            break;
        case 'reject':
            $resColorBox .= generateColorBox('red', 'Проверка не пройдена', 'pylint_result');
            break;
        case 'skip':
            return array(
                'header' => '<div class="w-100"><b>Pylint</b>' . generateColorBox('gray', 'Проверка пропущена', 'pylint_result') . '</div>',
                'label'     => '<input id="pylint_enabled" name="pylint_enabled" ' . ((@$enabled == 'true') ? 'checked' : '') .
                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                'body'   => generateTaggedValue("pylint_body", "Проверка пропущена или инструмент проверки не установлен."),
                'footer' => $resFooter
            );
            break;
    }

    $resBody = '';

    $errors = getcheckinfo($data['checks'], 'error');
    if ($errors['enabled'] && $errors['outcome'] != "skip") {
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
            case 'skip':
                $errorsColor = 'gray';
                break;
        }
        $resBody .= 'Ошибки: ' . @$errors['result'] . '<br>';
        $resColorBox = generateColorBox($errorsColor, $errors['result'], 'pylint_errors') . $resColorBox;
    }

    $warnings = getcheckinfo($data['checks'], 'warning');
    if ($warnings['enabled'] && $warnings['outcome'] != "skip") {
        switch ($warnings['outcome']) {
            case 'pass':
                $warningsColor = 'green';
                break;
            case 'reject':
                $warningsColor = 'red';
                break;
            case 'fail':
                $warningsColor = 'yellow';
                break;
            case 'skip':
                $warningsColor = 'gray';
                break;
        }
        $resBody .= 'Предупреждения: ' . @$warnings['result'] . '<br>';
        $resColorBox = generateColorBox($warningsColor, $warnings['result'], 'pylint_warnings') . $resColorBox;
    }

    $refactors = getcheckinfo($data['checks'], 'refactor');
    if ($refactors['enabled'] && $refactors['outcome'] != "skip") {
        switch ($refactors['outcome']) {
            case 'pass':
                $refactorsColor = 'green';
                break;
            case 'reject':
                $refactorsColor = 'red';
                break;
            case 'fail':
                $refactorsColor = 'yellow';
                break;
            case 'skip':
                $refactorsColor = 'gray';
                break;
        }
        $resBody .= 'Предложения по оформлению: ' . @$refactors['result'] . '<br>';
        $resColorBox = generateColorBox($refactorsColor, $refactors['result'], 'pylint_refactors') . $resColorBox;
    }

    $conventions = getcheckinfo($data['checks'], 'convention');
    if ($conventions['enabled'] && $conventions['outcome'] != "skip") {
        switch ($conventions['outcome']) {
            case 'pass':
                $conventionsColor = 'green';
                break;
            case 'reject':
                $conventionsColor = 'red';
                break;
            case 'fail':
                $conventionsColor = 'yellow';
                break;
            case 'skip':
                $conventionsColor = 'gray';
                break;
        }
        $resBody .= 'Нарушения соглашений: ' . @$conventions['result'] . '<br>';
        $resColorBox = generateColorBox($conventionsColor, $conventions['result'], 'pylint_conventions') . $resColorBox;
    }

    $resArr = array(
        'header' => '<div class="w-100"><b>Pylint</b>' . $resColorBox . '</div>',

        'label'     => '<input id="pylint_enabled" name="pylint_enabled" ' . ((@$enabled == 'true') ? 'checked' : '') .
            ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
        'body'   => generateTaggedValue("pylint_body", $resBody),
        'footer' => $resFooter
    );

    return $resArr;
}
function checkPylintOutputDataStructure($pylintOutputData)
{
    if (!array_key_exists('outcome', $pylintOutputData))
        return false;
    else if (!array_key_exists('checks', $pylintOutputData))
        return false;
    foreach ($pylintOutputData['checks'] as $check) {
        if (
            $check['outcome'] != "skip" &&
            (!array_key_exists('outcome', $check) || !array_key_exists('enabled', $check)
                || !array_key_exists('result', $check))
        )
            return false;
    }
    return true;
}

function parsePytest($data, $enabled)
{
    $resFooter = '<label for="pytest" id="pytestlabel" class="switchcon" style="cursor: pointer;">+ показать полный вывод</label>' .
        '<pre id="pytest" class="axconsole">Загрузка...</pre>';

    if (!$enabled) {
        return array(
            'header' => '<div class="w-100"><b>Pytest</b><span id="pytest_result" class="rightbadge"></span></div>',
            'label'     => '<input id="pytest_enabled" name="pytest_enabled" ' . ((@$enabled == 'true') ? 'checked' : '') .
                ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
            'body'   => generateTaggedValue("pytest_body", "Проверка пропущена или инструмент проверки не установлен."),
            'footer' => $resFooter
        );
    }

    if (!checkPytestOutputDataStructure($data)) {
        return array(
            'header' => '<div class="w-100"><b>Pytest</b>' . generateColorBox('gray', 'Внутрення ошибка!', 'pytest_result') . '</div>',
            'label'     => '<input id="pytest_enabled" name="pytest_enabled" ' . ((@$enabled == 'true') ? 'checked' : '') .
                ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
            'body'   => generateTaggedValue("pytest_body", "При выполнении проверки произошла критическая ошибка."),
            'footer' => $resFooter
        );
    }

    $resColorBox = "";

    switch ($data['outcome']) {
        case 'pass':
            $resColorBox .= generateColorBox('green', 'Проверка пройдена', 'pytest_result');
            break;
        case 'fail':
            $resColorBox .= generateColorBox('red', 'Проверки не пройдены', 'pytest_result');
            break;
        case 'reject':
            $resColorBox .= generateColorBox('red', 'Ошибка', 'pytest_result');
            break;
        case 'skip':
            return array(
                'header' => '<div class="w-100"><b>Pytest</b>' . generateColorBox('gray', 'Проверка пропущена', 'pytest_result') . '</div>',
                'label'     => '<input id="pytest_enabled" name="pytest_enabled" ' . ((@$enabled == 'true') ? 'checked' : '') .
                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                'body'   => generateTaggedValue("pytest_body", "Проверка пропущена или инструмент проверки не установлен."),
                'footer' => ""
            );
            break;
        case 'undefined':
            return array(
                'header' => '<div class="w-100"><b>Pytest</b><span id="pytest_result" class="rightbadge"></span></div>',
                'label'     => '<input id="pytest_enabled" name="pytest_enabled" ' . ((@$enabled == 'true') ? 'checked' : '') .
                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                'body'   => generateTaggedValue("pytest_body", "Не проверено."),
                'footer' => $resFooter
            );
            break;
    }

    $check = $data['check'];

    $resBody = '';

    $errors = $check['error'];
    if ($errors > 0) {
        $resBody .= 'Ошибки во время выполнения: ' . $errors . '<br>';
        $resColorBox = generateColorBox('red', $errors, 'pytest_error') . $resColorBox;
    }

    $failed = $check['failed'];
    $resBody .= 'Тестов провалено: ' . $failed . '<br>';
    $resColorBox = generateColorBox('yellow', $failed, 'pytest_failed') . $resColorBox;

    $passed = $check['passed'];
    $resBody .= 'Тестов пройдено: ' . $passed . '<br>';
    $resColorBox = generateColorBox('green', $passed, 'pytest_passed') . $resColorBox;

    $seconds = $check['seconds'];
    $resBody .= 'Время выполнения: ' . $seconds . 's<br>';
    $resColorBox = generateColorBox('gray', $seconds . "s", 'pytest_seconds') . $resColorBox;


    $resArr = array(
        'header' => '<div class="w-100"><b>Pytest</b>' . $resColorBox . '</div>',

        'label'     => '<input id="pytest_enabled" name="pytest_enabled" ' . ((@$enabled == 'true') ? 'checked' : '') .
            ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
        'body'   => generateTaggedValue("pytest_body", $resBody),
        'footer' => $resFooter
    );

    return $resArr;
}
function checkPytestOutputDataStructure($pytestOutputData)
{
    if (!array_key_exists('outcome', $pytestOutputData))
        return false;
    else if (!array_key_exists('check', $pytestOutputData))
        return false;
    $check = $pytestOutputData['check'];
    if (
        $pytestOutputData['outcome'] != "skip" && (
            !array_key_exists('error', $check) || !array_key_exists('failed', $check)
            || !array_key_exists('passed', $check) || !array_key_exists('seconds', $check))
    )
        return false;
    return true;
}


// Разбор и преобразования результата вывода автотестов в элемент массива для генерации аккордеона
function parseAutoTests($data, $enabled)
{
    $resFooter = '<label for="tests" id="testslabel" class="switchcon" style="cursor: pointer;">+ показать полный вывод</label>' .
        '<pre id="tests" class="axconsole">Загрузка...</pre>';

    if (!array_key_exists('outcome', $data)) {
        return array(
            'header' => '<div class="w-100"><b>Автотесты</b>' . generateColorBox('gray', 'Build не удался', 'autotests_result') . '</div>',
            'label'     => '<input id="autotests_enabled" name="autotests_enabled" ' . ((@$enabled == 'true') ? 'checked' : '') .
                ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
            'body'   => generateTaggedValue("autotests_body", "При выполнении проверки произошла критическая ошибка."),
            'footer' => $resFooter
        );
    }

    switch ($data['outcome']) {
        case 'pass':
            break;
        case 'fail':
            return array(
                'header' => '<div class="w-100"><b>Автотесты</b>' . generateColorBox('red', 'Проверка не пройдена', 'autotests_result') . '</div>',
                'label'     => '<input id="autotests_enabled" name="autotests_enabled" ' . ((@$enabled == 'true') ? 'checked' : '') .
                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                'body'   => generateTaggedValue("autotests_body", "При выполнении проверки произошла критическая ошибка."),
                'footer' => $resFooter
            );
        case 'skip':
            return array(
                'header' => '<div class="w-100"><b>Автотесты</b><span id="autotests_result" class="rightbadge"></span></div>',
                'label'     => '<input id="autotests_enabled" name="autotests_enabled" ' . ((@$enabled == 'true') ? 'checked' : '') .
                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                'body'   => generateTaggedValue("autotests_body", "Проверка пропущена или инструмент проверки не установлен."),
                'footer' => $resFooter
            );
            break;
        case 'undefined':
            return array(
                'header' => '<div class="w-100"><b>Автотесты</b><span id="autotests_result" class="rightbadge"></span></div>',
                'label'     => '<input id="autotests_enabled" name="autotests_enabled" ' . ((@$enabled == 'true') ? 'checked' : '') .
                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                'body'   => generateTaggedValue("autotests_body", "Не проверено."),
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

        'label'     => '<input id="autotests_enabled" name="autotests_enabled" ' . ((@$enabled == 'true') ? 'checked' : '') .
            ' class="accordion-input-item form-check-input" type="checkbox" value="true">',

        'body'   => generateTaggedValue("autotests_body", $resBody),
        'footer' => $resFooter
    );

    return $resArr;
}

// Разбор и преобразования результата проверки антиплагиатом в элемент массива для генерации аккордеона
function parseCopyDetect($data, $enabled)
{
    if (!array_key_exists('outcome', $data)) {
        return array(
            'header' => '<div class="w-100"><b>Антиплагиат</b>' . generateColorBox('gray', 'Build не удался', 'copydetect_result') . '</div>',
            'label'     => '<input id="copydetect_enabled" name="copydetect_enabled" ' . ((@$enabled == 'true') ? 'checked' : '') .
                ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
            'body'   => generateTaggedValue("copydetect_body", "При выполнении проверки произошла критическая ошибка."),
            'footer' => ''
        );
    }

    switch ($data['outcome']) {
        case 'pass':
            break;
        case 'fail':
            return array(
                'header' => '<div class="w-100"><b>Антиплагиат</b>' . generateColorBox('red', 'Проверка не пройдена', 'copydetect_result') . '</div>',
                'label'     => '<input id="copydetect_enabled" name="copydetect_enabled" ' . ((@$enabled == 'true') ? 'checked' : '') .
                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                'body'   => generateTaggedValue("copydetect_body", "При выполнении проверки произошла критическая ошибка."),
                'footer' => ''
            );
        case 'skip':
            return array(
                'header' => '<div class="w-100"><b>Антиплагиат</b><span id="copydetect_result" class="rightbadge"></span></div>',
                'label'     => '<input id="copydetect_enabled" name="copydetect_enabled" ' . ((@$enabled == 'true') ? 'checked' : '') .
                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                'body'   => generateTaggedValue("copydetect_body", "Проверка пропущена или инструмент проверки не установлен."),
                'footer' => ''
            );
            break;
        case 'undefined':
            return array(
                'header' => '<div class="w-100"><b>Антиплагиат</b><span id="copydetect_result" class="rightbadge"></span></div>',
                'label'     => '<input id="copydetect_enabled" name="copydetect_enabled" ' . ((@$enabled == 'true') ? 'checked' : '') .
                    ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
                'body'   => generateTaggedValue("copydetect_body", "Не проверено."),
                'footer' => ""
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
        case 'skip':
            $boxColor = 'gray';
            break;
    }

    $resArr = array(
        'header' => '<div class="w-100"><b>Антиплагиат</b>' . generateColorBox($boxColor, $result, 'copydetect_result') . '</div>',
        'label'     => '<input id="copydetect_enabled" name="copydetect_enabled" ' . ((@$enabled == 'true') ? 'checked' : '') .
            ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
        'body'   => generateTaggedValue("copydetect_body", $resBody),
        'footer' => ''
    );

    return $resArr;
}

<?php
require_once("DataClasses/ToolResult.class.php");

// защита от случайного перехода
$au = new auth_ssh();
checkAuLoggedIN($au);

function createConsoleFullOuput($isTextarea, $elementId)
{
    $html = "";
    $html .= "<button id='button-full-screen-$elementId' class='w-100 btn btn-outline-primary mb-2 d-none' onclick='makeElementFullScreen(&#39;$elementId&#39;)'>Развернуть окно вывода</button>";
    if ($isTextarea)
        $html .= "<textarea id='$elementId' class='axconsole w-100' readonly>";
    else
        $html .= "<pre id='$elementId' class='axconsole'>";

    $html .= "Загрузка...";

    if ($isTextarea)
        $html .= "</textarea>";
    else
        $html .= "</pre>";

    return $html;
}

function is_valid_tool_config(mixed $tool_config): bool
{
    // Конвертируем в ассоциативный массив
    $tool_config = json_decode(json_encode($tool_config), $assoc = true);
    return isset($tool_config['enabled']) && isset($tool_config['show_to_student']);
}

function is_valid_tool_result(mixed $tool_result): bool
{
    // Конвертируем в ассоциативный массив
    $tool_result = json_decode(json_encode($tool_result), $assoc = true);
    return isset($tool_result['full_output']) && isset($tool_result['outcome']);
}

function getAutotestsAccordionHtml(array $config_base, array $config_temp, array | null $result, bool $isStudent)
{

    $accord = array();
    foreach (get_all_tools() as $Tool) {
        $key = $Tool->value;

        if (!isset($config_base['tools'][$key]) || !is_valid_tool_config($config_base['tools'][$key])) {
            // throw new InvalidArgumentException("Не верная конфигурация Config: " . json_encode($config_base['tools'][$key]));
            continue;
        }
        if (!isset($config_temp['tools'][$key]) || !is_valid_tool_config($config_temp['tools'][$key])) {
            // throw new InvalidArgumentException("Не верная конфигурация Config_Temp: " . json_encode($config_temp['tools'][$key]));
            continue;
        }

        if (!$config_base['tools'][$key]['enabled'])
            continue;
        if ($isStudent && !$config_base['tools'][$key]['show_to_student']) {
            continue;
        }

        $tool_result = null;
        if ($result && isset($result['tools']) && isset($result['tools'][$key]))
            $tool_result = $result['tools'][$key];
        array_push($accord, parseToolResult($Tool, $config_temp['tools'][$key], $tool_result));
    }

    return $accord;
}

function parseToolResult(Tool $Tool, $tool_config, $tool_result)
{
    $enabled = $tool_config['enabled'];

    $Outcome = null;
    if (!$enabled || !isset($tool_result)) {
        $Outcome = Outcome::SKIP;
    } else {
        $ToolResult = $Tool->get_tool_result($tool_result);
        $ToolConfig = $Tool->get_tool_result($tool_result);

        if (!$ToolResult->is_valid()) {
            $Outcome = Outcome::UNDEFINED;
        } else {
            $Outcome = $ToolResult->get_param(Param::OUTCOME);
        }
    }

    $body = "";
    if ($Outcome === Outcome::PASS || $Outcome === Outcome::FAIL) {

        if ($Tool == Tool::BUILD)
            $body = get_body_build($ToolResult->get_checks()[0]);
        else if ($Tool == Tool::CPPCHECK)
            $body = "<br>" . get_body_cppcheck($ToolResult->get_checks());
        else if ($Tool == Tool::CLANG_FORMAT)
            $body = "<br>" . get_body_clangformat($ToolResult->get_checks()[0]);
        else if ($Tool == Tool::VALGRIND)
            $body = "<br>" . get_body_valgrind($ToolResult->get_checks());
        else if ($Tool == Tool::CATCH2)
            $body = "<br>" . get_body_catch2($ToolResult->get_checks()[0]);

        else if ($Tool == Tool::PYLINT)
            $body = "<br>" . get_body_pylint($ToolResult->get_checks());
        else if ($Tool == Tool::PYTEST)
            $body = "<br>" . get_body_pytest($ToolResult->get_checks()[0]);

        else if ($Tool == Tool::COPYDETECT)
            $body = "<br>" . get_body_copydetect($ToolResult->get_checks()[0]);
    }

    $color_box = "";
    $footer = "";
    if ($Outcome === Outcome::PASS || $Outcome === Outcome::FAIL || $Outcome === Outcome::REJECT) {
        $color_box = generateColorBox($Outcome->color(), $Outcome->short_description(), $Tool->name() . '_result');
        if ($Tool == Tool::COPYDETECT) {
            $folder_for_docker = getenv('HOST_DIR');
            if ($folder_for_docker === false) {
                die('Переменная HOST_DIR не задана');
            }
            $folder_docker_share = "$folder_for_docker/share";
            $sid = session_id();
            $folder = "$folder_docker_share/" . (($sid == false) ? "unknown" : $sid);
            $folder_to_copydetect = "$folder/output_copydetect.html";
            if (file_exists($folder_to_copydetect) && !is_dir($folder_to_copydetect)) {
                $footer = '<a target="_blank" class="btn btn-outline-danger mt-2" style="cursor: pointer;" href="open_html.php?file_path=' . $folder_to_copydetect . '">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-text" viewBox="0 0 16 16">
    <path d="M5 4a.5.5 0 0 0 0 1h6a.5.5 0 0 0 0-1zm-.5 2.5A.5.5 0 0 1 5 6h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5M5 8a.5.5 0 0 0 0 1h6a.5.5 0 0 0 0-1zm0 2a.5.5 0 0 0 0 1h3a.5.5 0 0 0 0-1z"/>
    <path d="M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2zm10-1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1"/>
    </svg> &nbsp; Открыть подробный отчет</a>';
            } else {
                $footer = "Для получения подробных результатов перезапустите проверку.";
            }
        } else
            $footer = '<label for="' . $Tool->name() . '" id="' . $Tool->name() . 'label" class="switchcon" style="cursor: pointer;">+ показать полный вывод</label>' .
                createConsoleFullOuput(true, $Tool->name());
    }

    $status_check = ($enabled) ? 'checked' : '';
    return array(
        'header' => '<div class="w-100"><b>' . $Tool->name_official() . '</b>' . $color_box . '</div>',
        'label'     => '<input id="' . $Tool->name() . '_enabled" name="' . $Tool->name() . '_enabled" ' . $status_check .
            ' class="accordion-input-item form-check-input" type="checkbox" value="true">',
        'body'   => generateTaggedValue($Tool->name() . "_body", $Outcome->long_description() . $body),
        'footer' => $footer
    );
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

// 
// GET_BODY_*()
// 

function get_body_build(CheckResult $CheckResult): string
{
    return "";
}

function get_body_cppcheck(array $array_CheckResult): string
{
    $body = '';

    foreach ($array_CheckResult as $CheckResult) {
        $body .= $CheckResult->get_param(Param::CHECK_NAME) . ' : ' . $CheckResult->get_param(Param::RESULT) . '<br>';
    }

    return $body;
}

function get_body_clangformat(CheckResult $CheckResult): string
{
    return 'Замечаний линтера: ' . $CheckResult->get_param(Param::RESULT) . '<br>';
}

function get_body_valgrind(array $array_CheckResult): string
{
    $body = "";
    foreach ($array_CheckResult as $CheckResult) {
        $check_name = $CheckResult->get_param(Param::CHECK_NAME);
        if ($check_name == "errors")
            $body .= 'Ошибки памяти: ' . $CheckResult->get_param(Param::RESULT) . '<br>';
        else if ($check_name == "leaks")
            $body .= 'Утечки памяти: ' . $CheckResult->get_param(Param::RESULT) . '<br>';
    }
    return $body;
}

function get_body_catch2(CheckResult $CheckResult)
{
    $body = 'Ошибок: ' . $CheckResult->get_param(Param::ERROR) . '<br>';
    $body .= 'Проверок провалено: ' . $CheckResult->get_param(Param::FAILED) . '<br>';

    return $body;
}

function get_body_pylint(array $array_CheckResult)
{
    $array_text = array(
        "error" => "Ошибки",
        "warning" => "Предупреждения",
        "refactor" => "Предложения по оформлению",
        "convention" => "Нарушения соглашений"
    );

    $body = "";
    foreach ($array_CheckResult as $index => $CheckResult) {
        $check_name = $CheckResult->get_param(Param::CHECK_NAME);
        $Outcome = $CheckResult->get_param(Param::OUTCOME);
        if ($Outcome != Outcome::SKIP) {
            $text = $array_text[$check_name];
            $body .= "$text: " . $CheckResult->get_param(Param::RESULT) . '<br>';
        }
    }

    return $body;
}

function get_body_pytest(CheckResult $CheckResult)
{
    $body = '';

    $errors = $CheckResult->get_param(Param::ERROR);
    $body .= 'Ошибки во время выполнения: ' . $errors . '<br>';

    $failed = $CheckResult->get_param(Param::FAILED);
    $body .= 'Тестов провалено: ' . $failed . '<br>';

    $passed = $CheckResult->get_param(Param::PASSED);
    $body .= 'Тестов пройдено: ' . $passed . '<br>';

    $seconds = $CheckResult->get_param(Param::SECONDS);
    $body .= 'Время выполнения: ' . $seconds . 's<br>';

    return $body;
}

function get_body_copydetect(CheckResult $CheckResult)
{
    $body = '';

    if ($CheckResult->get_param(Param::RESULT))
        $body .= 'Процент оригинальности: ' . $CheckResult->get_param(Param::RESULT) . '%';

    return $body;
}

<?php
require_once("ToolResult.class.php");

function get_all_tools(): array
{
    return Tool::cases();
}

enum Tool: string
{

    // для Си и Си++
    case BUILD = "build";
    case CPPCHECK = "cppcheck";
    case CLANG_FORMAT = "clang-format";
    case VALGRIND = "valgrind";
    case CATCH2 = "catch2";

        // для Python
    case PYLINT = "pylint";
    case PYTEST = "pytest";

        // мультиязычные
    case COPYDETECT = "copydetect";

    public function name(): string
    {
        return $this->value;
    }

    public function name_official(): string
    {
        $name = $this->value;
        $name[0] = strtoupper($name[0]);
        return $name;
    }

    function get_tool_result($tool_result): ToolResult
    {
        return match ($this) {
            self::BUILD => new CBuildToolResult($tool_result),
            self::CPPCHECK => new CppcheckToolResult($tool_result),
            self::CLANG_FORMAT => new ClangFormatToolResult($tool_result),
            self::VALGRIND => new ValgrindToolResult($tool_result),
            self::CATCH2 => new Catch2ToolResult($tool_result),
            self::PYLINT => new PylintToolResult($tool_result),
            self::PYTEST => new PytestToolResult($tool_result),
            self::COPYDETECT => new CopydetectToolResult($tool_result)
        };
    }
}

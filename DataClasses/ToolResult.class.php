<?php
require_once("Param.class.php");
require_once("Outcome.class.php");
require_once("CheckResult.class.php");
require_once("Tool.class.php");

abstract class ToolResult
{
    private Tool $tool;
    private array $tool_result = [];
    private array $checks = [];

    function __construct(Tool $tool, array $tool_result)
    {
        $this->tool = $tool;
        $this->tool_result = $tool_result;

        $checks = [];
        if (isset($this->tool_result['check']))
            $checks = [$this->tool_result['check']];
        if (isset($this->tool_result['checks']))
            $checks = $this->tool_result['checks'];

        foreach ($checks as $check_json) {
            array_push($this->checks, new CheckResult($check_json));
        }
    }

    // 
    // GETTERS
    // 

    public function get_param(Param $param)
    {
        if ($this->has_param($param)) {
            if ($param == Param::OUTCOME)
                return Outcome::from($this->tool_result[$param->value]);
            return $this->tool_result[$param->value];
        }
        return null;
    }

    public function get_checks(): array
    {
        return $this->checks;
    }

    public function get_json()
    {
        return $this->tool_result;
    }
    // 
    // ELSE
    // 

    public function has_param(Param $param): bool
    {
        return array_key_exists($param->value, $this->tool_result);
    }

    // 
    // PROTECTED
    // 

    protected function is_valid_base(): bool
    {
        return $this->has_param(Param::FULL_OUTPUT) && $this->has_param(Param::OUTCOME);
    }

    // 
    // ABSTRACT
    // 

    abstract public function is_valid(): bool;
}

// 
// C and C++
// 

class CBuildToolResult extends ToolResult
{

    function __construct(array $tool_result)
    {
        parent::__construct(Tool::BUILD, $tool_result);
    }

    // 
    // ELSE
    // 

    public function is_valid(): bool
    {
        return parent::is_valid_base();
    }
}

class CppcheckToolResult extends ToolResult
{

    function __construct(array $tool_result)
    {
        parent::__construct(Tool::CPPCHECK, $tool_result);
    }

    // 
    // ELSE
    // 

    public function is_valid(): bool
    {
        return parent::is_valid_base();
    }
}

class ClangFormatToolResult extends ToolResult
{

    function __construct(array $tool_result)
    {
        parent::__construct(Tool::CLANG_FORMAT, $tool_result);
    }

    // 
    // ELSE
    // 

    public function is_valid(): bool
    {
        return parent::is_valid_base();
    }
}

class ValgrindToolResult extends ToolResult
{

    function __construct(array $tool_result)
    {
        parent::__construct(Tool::VALGRIND, $tool_result);
    }

    // 
    // ELSE
    // 

    public function is_valid(): bool
    {
        return parent::is_valid_base();
    }
}

class Catch2ToolResult extends ToolResult
{

    function __construct(array $tool_result)
    {
        parent::__construct(Tool::CATCH2, $tool_result);
    }

    // 
    // ELSE
    // 

    public function is_valid(): bool
    {
        return parent::is_valid_base();
    }
}


// 
// PYTHON
// 

class PylintToolResult extends ToolResult
{

    function __construct(array $tool_result)
    {
        parent::__construct(Tool::PYLINT, $tool_result);
    }

    // 
    // ELSE
    // 

    public function is_valid(): bool
    {
        return parent::is_valid_base();
    }
}

class PytestToolResult extends ToolResult
{

    function __construct(array $tool_result)
    {
        parent::__construct(Tool::PYTEST, $tool_result);
    }

    // 
    // ELSE
    // 

    public function is_valid(): bool
    {
        return parent::is_valid_base();
    }
}

// 
// MULTI
// 

class CopydetectToolResult extends ToolResult
{

    function __construct(array $tool_result)
    {
        parent::__construct(Tool::COPYDETECT, $tool_result);
    }

    // 
    // ELSE
    // 

    public function is_valid(): bool
    {
        return parent::is_valid_base();
    }
}

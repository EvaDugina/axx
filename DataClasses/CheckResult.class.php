<?php

class CheckResult
{
    private array $check_result = [];

    function __construct(array $check_result)
    {
        $this->check_result = $check_result;
    }

    // 
    // GETTERS
    // 

    public function get_param(Param $param)
    {
        if ($this->has_param($param)) {
            if ($param == Param::OUTCOME)
                return Outcome::from($this->check_result[$param->value]);
            return $this->check_result[$param->value];
        }
        return null;
    }

    public function get_json()
    {
        return $this->check_result;
    }
    // 
    // ELSE
    // 

    public function has_param(Param $param): bool
    {
        return array_key_exists($param->value, $this->check_result);
    }
}

<?php

class CheckConfig
{
    private array $check_config = [];

    function __construct(array $check_config)
    {
        $this->check_config = $check_config;
    }

    // 
    // GETTERS
    // 

    public function get_param(Param $param)
    {
        if ($this->has_param($param)) {
            return $this->check_config[$param->value];
        }
        return null;
    }

    public function get_json()
    {
        return $this->check_config;
    }
    // 
    // ELSE
    // 

    public function has_param(Param $param): bool
    {
        return array_key_exists($param->value, $this->check_config);
    }
}

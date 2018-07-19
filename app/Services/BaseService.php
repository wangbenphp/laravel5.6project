<?php

namespace App\Services;

/**
 * Service 基类
 * @author wangben
 */
class BaseService
{
    public static function getInstance()
    {
        $class = get_called_class();
        return app($class);
    }
}
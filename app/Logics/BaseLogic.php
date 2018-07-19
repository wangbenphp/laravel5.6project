<?php

namespace App\Logics;

/**
 * Logics 基类
 * @author wangben
 */
class BaseLogic
{
    public static function getInstance()
    {
        $class = get_called_class();
        return app($class);
    }
}
<?php
/**
 * 全局助手函数
 * @author wangben
 * @date 2018-07-19
 */

/**
 * 获取logic
 * @author wangben
 */
if (!function_exists('logic')) {
    function logic($name)
    {
        $LogicName = ucfirst($name);
        $class = '\\App\Logics\\' . $LogicName . 'Logic';
        if (class_exists($class)) {
            $logic = $class::getInstance();
        } else {
            $logic = class_exists($class) ? $class::getInstance() : \App\Logics\BaseLogic::getInstance();
        }
        return $logic;
    }
}

/**
 * 获取service
 * @author wangben
 */
if (!function_exists('service')) {
    function service($name)
    {
        $LogicName = ucfirst($name);
        $class = '\\App\Services\\' . $LogicName . 'Service';
        if (class_exists($class)) {
            $logic = $class::getInstance();
        } else {
            $logic = class_exists($class) ? $class::getInstance() : \App\Logics\BaseLogic::getInstance();
        }
        return $logic;
    }
}

/**
 * 获取model
 * @author wangben
 */
if (!function_exists('model')) {
    function model($name)
    {
        $ModelDir = '';
        $ModelName = ucfirst($name);
        $sign = '';
        if (strpos($name, '/') !== false) {
            $arr = explode('/', $name);
            $ModelDir = ucfirst($arr[0]);
            $ModelName = ucfirst($arr[1]);
            $sign = '\\';
        }
        $class = 'App\Models\\' . $ModelDir . $sign . $ModelName;
        if (class_exists($class)) {
            return app($class);
        }
        return false;
    }
}

/**
 * Redis操作类
 * @author wangben
 */
if (!function_exists('redis')) {
    function redis($config = [])
    {
        $config = empty($config) ? (config('database.redis.default') ?: '') : $config;
        return \WbPHPLibraryPackage\Service\Redis::getInstance($config);
    }
}

/**
 * Log
 * @author wangben
 */
if (!function_exists('write_log')) {
    function write_log($message = '', $fileName = 'info', $desc = 'info')
    {
        return \WbPHPLibraryPackage\Service\Log::$fileName($message, $desc);
    }
}

/**
 * successReturn
 * @author wangben
 */
if (!function_exists('successReturn')) {
    function successReturn($data = [], $msg = 'Success')
    {
        return ['code' => 0, 'message' => $msg, 'data' => $data];
    }
}

/**
 * failReturn
 * @author wangben
 */
if (!function_exists('failReturn')) {
    function failReturn($code = 10000, $msg = 'Fail')
    {
        return ['code' => (int) $code, 'message' => $msg, 'data' => []];
    }
}

/**
 * ObjectToArray
 * @author wangben
 */
if (!function_exists('o2a')) {
    function o2a($d)
    {
        if (is_object($d)) {
            if (method_exists($d, 'getArrayCopy')) {
                $d = $d->getArrayCopy();
            } elseif (method_exists($d, 'getArrayIterator')) {
                $d = $d->getArrayIterator()->getArrayCopy();
            } elseif (method_exists($d, 'toArray')) {
                $d = $d->toArray();
            } else {
                $d = get_object_vars($d);
            }
        }
        if (is_array($d)) {
            return array_map(__FUNCTION__, $d);
        }
        return $d;
    }
}

/**
 * 获取分表后缀
 * @author wangben
 */
if (!function_exists('get_tb_num')) {
    function get_tb_num($value, $tbNum = 10)
    {
        if (!$value || is_object($value)) {
            return '';
        }
        if (is_numeric($value)) {
            $num = intval(substr($value, -2));
        } else if (is_string($value)) {
            $num = sprintf("%u", crc32($value));
        }
        return $num % $tbNum;
    }
}
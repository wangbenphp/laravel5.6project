<?php

namespace App\Http\Controllers\Wechat;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

/**
 * 微信公众号分享控制器
 */
class ShareController extends Controller
{
    public function index()
    {
        $res = logic('wechat')->share();
        echo '<pre>';
        print_r($res);
    }
}

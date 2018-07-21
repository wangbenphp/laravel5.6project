<?php

namespace App\Http\Controllers\Wechat;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

/**
 * 微信公众号控制器
 * @author wangben
 * @date 2017-07-20
 */
class IndexController extends Controller
{
    /**
     * 微信公众号接口入口&接口验证
     */
    public function index(Request $request)
    {
        $echostr   = $request->input('echostr') ?: false;
        $signature = $request->input('signature') ?: false;
        $timestamp = $request->input('timestamp') ?: false;
        $nonce     = $request->input('nonce') ?: false;
        $poststr   = file_get_contents('php://input') ?: false;
        $wechat    = logic('wechat');

        if ($echostr) {
            //step1:接口配置地址验证
            $wechat->valid($echostr, $signature, $timestamp, $nonce, []);
        } else {
            //step2:事件处理
            $wechat->init($poststr, env('WECHAT_APP_ID'), []);
        }
    }

    /**
     * 授权
     * auth_type = 1获取openid, 2获取unionid, 3获取token
     */
    public function auth(Request $request)
    {
        $code = $request->input('code') ?: false;
        $state = $request->input('state') ?: '';
        $redirect_info = $request->input('redirect_info') ?: false;

        if ($code) {
            logic('wechatAuth')->exec($code, $state, []);
        } else if ($redirect_info) {
            logic('wechatAuth')->get_code($request->url(), $redirect_info, []);
        } else {
            //测试
            echo '<pre>';
            print_r($request->input());
        }
    }

    /**
     * 接口获取AccessToken
     */
    public function get_access_token(Request $request)
    {
        $config = $request->input('config') ? json_decode($request->input('config'), true) : [];
        $appid  = $request->input('appid') ?: env('WECHAT_APP_ID');
        $token  = logic('wechat')->getAccessToken($appid, $config);
        if ($token) {
            return response()->json(successReturn(['access_token' => $token]));
        } else {
            return response()->json(failReturn(10072, 'AccessToken获取失败'));
        }
    }

    /**
     * 模板消息
     */
    public function tep_msg(Request $request)
    {
        $openid = $request->input('openid') ?: 'ocLZu0V09w-AuGug_i1Njj8ffj80';
        $config = $request->input('config') ? json_decode($request->input('config'), true) : [];
        $appid  = $request->input('appid') ?: env('WECHAT_APP_ID');
        $wechat = logic('wechat');
        $token  = $wechat->getAccessToken($appid, $config);
        if (!$token) {
            return response()->json(failReturn(10072, 'AccessToken获取失败'));
        }

        $tep_info = [
            'touser'      => $openid,
            'template_id' => 'T-hxKYzVpSCPm5d8fagXrq5J6S6marADaH5mpsO-534',
            'url'         => 'http://ucenter.dadi01.net',
            'data'        => [
                'first'    => [
                    'value' => '您的积分兑换成功',
                    'color' => '#173177',
                ],
                'keyword1' => [
                    'value' => date('Y-m-d H:i:s'),
                    'color' => '#173177',
                ],
                'keyword2' => [
                    'value' => mt_rand(100, 1000),
                    'color' => '#173177',
                ],
                'keyword3' => [
                    'value' => '线下积分兑换',
                    'color' => '#173177',
                ],
                'keyword4' => [
                    'value' => '5201314',
                    'color' => '#173177',
                ],
                'remark'  => [
                    'value' => '感谢您使用日子里商城公众号，我们将竭诚为您服务！',
                    'color' => '#173177',
                ]
            ],
        ];

        $res = $wechat->send_tep_msg($tep_info, $token);
        if ($res) {
            return response()->json(successReturn());
        } else {
            return response()->json(failReturn(10073, 'Wechat模板消息发送失败'));
        }
    }
}

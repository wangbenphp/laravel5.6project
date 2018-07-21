<?php

namespace App\Logics;

use WbPHPLibraryPackage\Service\Wechat\Api as WecahtApi;

/**
 * wechat授权(绑定)信息
 * @author:wangben
 * @date:20180721
 */
class WechatAuthLogic extends BaseLogic
{
    //获取code
    public function get_code($redirect_url = '', $state = '', $config = [])
    {
        WecahtApi::getInstance($config)
            ->getAuthCode($redirect_url, $state);
    }

    //授权处理 auth_type = 1获取openid, 2获取unionid, 3获取unionid&token
    public function exec($code, $state, $config = [])
    {
        $info = $this->get_info_by_code($code, $config);
        if ($info) {
            if (isset($info['openid'])) {
                $url = urldecode($state);
                $param = $this->get_url_request_param_key_value($url);
                $auth_type = $param['auth_type'] ? $param['auth_type'] > 0 ? $param['auth_type'] : 0 : 0;
                if ($auth_type == 1) {
                    $url .= '&openid=' . $info['openid'];
                } else if ($auth_type == 2) {
                    $url .= '&unionid=' . $info['unionid'];
                } else if ($auth_type == 3) {
                    $tokeninfo = $this->get_user_login_token_by_unionid($info['unionid']);
                    $url .= '&unionid=' . $info['unionid'] . '&login_type=' . $tokeninfo['login_type'] . '&token=' . $tokeninfo['token'];
                } else {
                    echo '<pre>';
                    print_r($info);exit;
                }
                Header('Location: ' . $url);exit;
            } else {
                echo json_encode($info);exit;
            }
        } else {
            echo 'System Error.';exit;
        }
    }

    //根据code获取用户信息
    private function get_info_by_code($code = '', $config)
    {
        $res = WecahtApi::getInstance($config)
            ->getInfoByAuthCode($code);
        return $res;
    }

    //根据url获取请求参数
    private function get_url_request_param_key_value($url = '')
    {
        $param = [];
        if (!$url) {
            return $param;
        }
        $param_list = parse_url($url);
        if (!$param_list) {
            return $param;
        }
        $param_arr = explode('&', $param_list['query']);
        if (!empty($param_arr)) {
            foreach ($param_arr as $v) {
                $iteam = explode('=', $v);
                $param[$iteam[0]] = $iteam[1];
                unset($iteam);
            }
        }
        return $param;
    }

    //根据unionid获取用户登陆的token
    private function get_user_login_token_by_unionid($unionid)
    {
        return ['login_type' => 1, 'token' => date('YmdHis')];
    }
}
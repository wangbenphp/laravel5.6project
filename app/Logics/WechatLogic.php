<?php

namespace App\Logics;

use WbPHPLibraryPackage\Service\Wechat\Api as WechatApi;
use WbPHPLibraryPackage\Service\Redis;

/**
 * 微信公众号逻辑
 * @author:wangben
 * @date:20180720
 */
class WechatLogic extends BaseLogic
{
    /**
     * 微信公众号接口验证
     */
    public function valid($echostr, $signature, $timestamp, $nonce, $config = [])
    {
        WechatApi::getInstance($config)->init($echostr, $signature, $timestamp, $nonce);
    }

    /**
     * 微信公众号接口入口
     */
    public function init($poststr = '', $appid = '', $config = [])
    {
        $msg = '';
        if ($poststr) {
            libxml_disable_entity_loader(true);
            $postObj = simplexml_load_string($poststr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $RX_TYPE = trim($postObj->MsgType);
            //step1:消息&事件类型分离
            if ($RX_TYPE == 'event') {
                $res = $this->receiveEvent($postObj, $appid, $config);
            } else if ($RX_TYPE == 'text') {
                $res = $this->receiveText($postObj, $appid);
            }
            //step2:回复消息类型输出
            if (isset($res['content'])) {
                if ($res['content']) {
                    $msg = $this->transmitForMsgType($postObj, $res['type'], $res['content'], $config);
                }
            }
        }
        echo $msg;
    }

    /**
     * 根据openid获取微信信息
     */
    public function get_wechat_info_by_openid($openid = '', $appid, $config)
    {
        if (!$openid) {
            return false;
        }
        $config_id = md5(var_export($config, true));
        $redis = Redis::getInstance();
        $info = $redis->get('get.wechat.info.by.openid.' . $config_id . ':' . $openid);
        if ($info === false) {
            $info = WechatApi::getInstance($config)->getUserInfoByOpenid($openid, $this->getAccessToken($appid, $config));
            if ($info) {
                //如果未绑定开放平台，自动生成一个unionid占位符
                if (!isset($info['unionid'])) {
                    $info['unionid'] = env('WECHAT_APP_ID', 'thisisaunionid');
                }
                $redis->setex('get.wechat.info.by.openid.' . $config_id . ':' . $openid, 7200, json_encode($info));
            }
        } else {
            $info = json_decode($info, true);
        }
        return $info;
    }

    /**
     * 获取access_token
     */
    public function getAccessToken($appid, $config)
    {
        $redis = Redis::getInstance();
        $accessToken = $redis->get('get.accesstoken.by.appid:' . $appid);
        if ($accessToken === false) {
            $accessToken = WechatApi::getInstance($config)->accessToken();
            $redis->setex('get.accesstoken.by.appid:' . $appid, 7200, $accessToken);
        }
        return $accessToken;
    }

    /**
     * 模板消息发送
     */
    public function send_tep_msg($tep_info, $token)
    {
        $info = WechatApi::getInstance()->templateMessage($tep_info, $token);
        return $info;
    }

    /**
     * 微信公众号分享
     */
    public function share($appid = '', $config = [], $url = '')
    {
        $appid = $appid ?: env('WECHAT_APP_ID');
        $res = WechatApi::getInstance()->getSignPackage($this->getAccessToken($appid, $config), $appid, $url);
        return $res;
    }

    /**
     * 文本处理
     * 关键词回复
     */
    private function receiveText($object, $appid)
    {
        $res = '';
        $keyword = trim($object->Content);
        if (!empty($keyword)) {
            $res = logic('WechatResponseMsg')->keyword_response($appid, $keyword);
        }
        return $res;
    }

    /**
     * [receiveEvent 事件处理]
     * @param  [type] $object [微信传过来的对象 / 这里主要是用到 关注  ]
     * @return [type]         [description]
     */
    private function receiveEvent($object, $appid, $config)
    {
        header('Content-type: text/html; charset=utf-8');
        $content = '';
        $openid  = $object->FromUserName;
        $event   = is_string($object->Event) ? $object->Event : strval($object->Event);
        switch ($event) {
            case 'subscribe':
                $content  = $this->is_exist_openid_and_back_msg($openid, $appid, $config);
                $eventKey = $object->EventKey;//二维码参数
                $this->register($openid, $appid, $config, $eventKey);
                break;
            case 'unsubscribe':
                $content = '';
                break;
            case 'SCAN':
                $eventKey = $object->EventKey;//二维码参数
                break;
            case 'CLICK':
                if ($object->EventKey) {
                    $gets = json_encode($object->EventKey);
                    $get  = json_decode($gets, true);
                } else {
                    $content = '';
                }
                break;
            default:
                $content = '';
                break;
        }
        return $content;
    }

    /**
     * 判断Openid是否存在数据库并返回消息
     */
    private function is_exist_openid_and_back_msg($openid, $appid, $config)
    {
        $openid   = is_string($openid) ? $openid : strval($openid);
        $is_exist = $this->is_subscribe_by_openid($openid, $appid, $config);
        $userinfo = $this->get_wechat_info_by_openid($openid, $appid, $config);
        $resModel = logic('WechatResponseMsg');
        if ($is_exist) {
            //取消关注再关注自动回复
            $res = $resModel->subscribe_response($appid, 2);
        } else {
            //初次关注自动回复
            $res = $resModel->subscribe_response($appid, 1);
        }
        //替换用户昵称
        if ($res['content'] && ($res['type'] == 1)) {
            $res['content'] = str_replace('[[username]]', $userinfo['nickname'], $res['content']);
        }
        return $res;
    }

    /**
     * 查看openid是否已(存在)关注
     */
    private function is_subscribe_by_openid($openid, $appid, $config)
    {
        $is_subscribe = false;
        $wechat_info = $this->get_wechat_info_by_openid($openid, $appid, $config);
        $redis = Redis::getInstance();
        $info = $redis->hgetall('get.db.wechat.info.by.unionid.' . $wechat_info['unionid'] . '.openid:' . $openid);
        if (empty($info)) {
            $res = model('wechat/user')->get_db_wechat_info_by_unionid_and_openid($wechat_info['unionid'], $openid);
            if ($res) {
                $is_subscribe = true;
                $redis->hmset('get.db.wechat.info.by.unionid.' . $wechat_info['unionid'] . '.openid:' . $openid, $info);
                $redis->expire('get.db.wechat.info.by.unionid.' . $wechat_info['unionid'] . '.openid:' . $openid, 7200);
            }
        } else {
            $is_subscribe = true;
        }
        return $is_subscribe;
    }

    /**
     * 注册微信公众号用户
     */
    private function register($openid, $appid, $config, $eventKey)
    {
        $openid   = is_string($openid) ? $openid : strval($openid);
        $eventKey = strval($eventKey);
        //step1:判断用户是否存在
        $is_exist = $this->is_subscribe_by_openid($openid, $appid, $config);
        //step2:获取用户信息
        $userinfo = $this->get_wechat_info_by_openid($openid, $appid, $config);
        if (!$is_exist) {
            //step3:二维码参数
            $inviter = 0;
            if($eventKey) {
                $invite  = explode('_', $eventKey);
                $inviter = $invite[1];//二维码参数
            }
            //step4:注册用户
            $this->register_wechat_user($userinfo, $appid, $inviter);
        } else {
            $this->update_wechat_user_subscribe_num($userinfo['unionid'], $userinfo['openid']);
        }
        return true;
    }

    /**
     * 更新用户注册次数
     */
    private function update_wechat_user_subscribe_num($unionid, $openid)
    {
        $res = model('wechat/user')->update_wechat_user_subscribe_num($unionid, $openid);
        if ($res) {
            Redis::getInstance()->del('get.db.wechat.info.by.unionid.' . $unionid . '.openid:' . $openid);
        }
        return $res;
    }

    /**
     * 注册逻辑
     */
    private function register_wechat_user($userinfo, $appid, $inviter)
    {
        $res = model('wechat/user')->register_wechat_user($userinfo, $appid, $inviter);
        if ($res) {
            $redis = Redis::getInstance();
            $userinfo['appid'] = $appid;
            $userinfo['inviter_id'] = $inviter;
            $userinfo['create_time'] = time();
            $redis->hmset('get.db.wechat.info.by.unionid.' . $userinfo['unionid'] . '.openid:' . $userinfo['openid'], $userinfo);
            $redis->expire('get.db.wechat.info.by.unionid.' . $userinfo['unionid'] . '.openid:' . $userinfo['openid'], 7200);
        }
        return $res;
    }

    /**
     * 消息类型转化
     */
    private function transmitForMsgType($postObj, $type, $content)
    {
        $wechat = WechatApi::getInstance();
        switch ($type) {
            case 1://文本类型
                $msg = $wechat->transmitText($postObj, $content);
                break;
            case 2://图片类型
                $msg = $wechat->transmitImage($postObj, $content);
                break;
            case 3://语音类型
                $msg = $wechat->transmitVoice($postObj, $content);
                break;
            case 4://视频类型
                $msg = $wechat->transmitVideo($postObj, $content);
                break;
            case 5://图文类型
                $msg = $wechat->transmitNews($postObj, $content);
                break;
            case 6://音乐类型
                $msg = $wechat->transmitMusic($postObj, $content);
                break;
            default://文本类型
                $msg = $wechat->transmitText($postObj, $content);
                break;
        }
        return $msg;
    }
}
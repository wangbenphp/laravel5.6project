<?php

namespace App\Logics;

use WbPHPLibraryPackage\Service\Redis;

/**
 * 微信公众号响应消息逻辑
 * @author:wangben
 * @date:2018-07-20
 */
class WechatResponseMsgLogic extends BaseLogic
{
    //关键词自动回复
    public function keyword_response($appid, $keyword)
    {
        $type_id = $this->get_type_id_by_style($appid, 3, $keyword);
        if (!$type_id) {//默认自动回复
            $type_content = $this->default_response($appid);
        } else {//普通自动回复
            $content = $this->get_content_by_type_id($appid, $type_id['type'], $type_id['id']);
            $type    = $content ? $type_id['type'] : 1;
            $type_content = ['type' => $type, 'content' => $content];
        }
        return $type_content;
    }

    //关注后回复
    public function subscribe_response($appid, $style)
    {
        $type_id = $this->get_type_id_by_style($appid, $style);
        if (!$type_id) {
            $content = '';
        } else {
            $content = $this->get_content_by_type_id($appid, $type_id['type'], $type_id['id']);
        }
        $type = $content ? $type_id['type'] : 1;
        $type_content = ['type' => $type, 'content' => $content];
        return $type_content;
    }

    //根据style|keyword获取type&id
    private function get_type_id_by_style($appid, $style, $keyword = '')
    {
        $redis = Redis::getInstance();
        $type_id = $redis->hgetall('get.response.type.id.by.appid.' . $appid . '.and.style:' . $style . '.keyword.' . $keyword);
        if (empty($type_id)) {
            $type_id = model('wechat/response')->get_response_type_and_msg_id($appid, $style, $keyword);
            if ($type_id) {
                $redis->hmset('get.response.type.id.by.appid.' . $appid . '.and.style:' . $style . '.keyword.' . $keyword, $type_id);
                $redis->expire('get.response.type.id.by.appid.' . $appid . '.and.style:' . $style . '.keyword.' . $keyword, 7200);
            } else {
                $type_id = false;
            }
        }
        return $type_id;
    }

    //默认回复
    private function default_response($appid)
    {
        $res = true;
        $type_id = $this->get_type_id_by_style($appid, 4);
        if (!$type_id) {
            $res = false;
        }
        $content = $res ? $this->get_content_by_type_id($appid, $type_id['type'], $type_id['id']) : '';
        $type    = $res ? $type_id['type'] : 1;
        return ['type' => $type, 'content' => $content];
    }

    //根据type&id获取content
    private function get_content_by_type_id($appid, $type, $id)
    {
        $redis = Redis::getInstance();
        $source_get_fail = false;
        $redis_key = 'get.appid.' . $appid . '.source.by.type.' . $type . '.sourceid:' . $id;
        if ($type == 1 || $type == 2 || $type == 3) {
            $get_source = $redis->get($redis_key);
            if ($get_source === false) {
                $source_get_fail = true;
            }
        } else {
            $get_source = $redis->hgetall($redis_key);
            if (empty($get_source)) {
                $source_get_fail = true;
            }
        }
        if ($source_get_fail) {
            $source_info = model('wechat/response')->get_content_by_type_and_id($appid, $type, $id);
            $get_source  = '';
            if ($source_info) {
                if ($type == 1 || $type == 2 || $type == 3) {
                    $get_source = $source_info['content'];
                    $redis->setex($redis_key, 7200, $get_source);
                } else {
                    $get_source = $source_info;
                    $redis->hmset($redis_key, $get_source);
                    $redis->expire($redis_key, 7200);
                }
            }
        }
        return $get_source;
    }
}
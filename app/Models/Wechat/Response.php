<?php

namespace App\Models\Wechat;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * 微信公众号消息类型模型
 * @author wangben
 * @date 2018-07-20
 */
class Response extends Model
{
    //获取消息的类型&id
    public function get_response_type_and_msg_id($appid, $style, $keyword = '')
    {
        $res = DB::table('wechat_response_msg_info')
            ->select('type', 'source_id as id')
            ->where('appid', $appid)
            ->where('style', $style);
        if ($keyword) {
            $res = $res->where('keyword', $keyword);
        }
        $res = $res->where('status', 1)
            ->where('is_delete', 0)
            ->first();
        return $res ? o2a($res) : false;
    }

    //根据消息的类型&id获取对应的content
    public function get_content_by_type_and_id($appid, $type, $id)
    {
        if ($type == 4) {
            $db = DB::table('wechat_video_source');
        } else if ($type == 5) {
            $db = DB::table('wechat_news_source');
        } else if ($type == 6) {
            $db = DB::table('wechat_music_source');
        } else {
            $db = DB::table('wechat_text_voice_image_source');
        }
        $res = $db
            ->where('id', $id)
            ->where('appid', $appid)
            ->where('status', 1)
            ->where('is_delete', 0)
            ->first();
        return $res ? o2a($res) : false;
    }
}

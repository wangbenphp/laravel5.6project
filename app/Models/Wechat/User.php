<?php

namespace App\Models\Wechat;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * 微信公众号用户模型
 * @author wangben
 * @date 2018-07-20
 */
class User extends Model
{
    /**
     * 查看数据库unionid&openid的信息
     */
    public function get_db_wechat_info_by_unionid_and_openid($unionid, $openid)
    {write_log($unionid, 'userModel_unionid');
    write_log($openid, 'userMode_openid');
        $info = DB::table('wechat_user')
            ->where('unionid', $unionid)
            ->where('openid', $openid)
            ->first();
        write_log($info, 'userModle_res');
        return $info ? o2a($info) : null;
    }

    /**
     * 注册已关注的用户的微信信息
     */
    public function register_wechat_user($info, $appid, $inviter = 0)
    {
        $res = DB::table('wechat_user')
            ->insert([
                'unionid'       => $info['unionid'],
                'openid'        => $info['openid'],
                'nickname'      => $info['nickname'],
                'sex'           => $info['sex'],
                'language'      => $info['language'],
                'country'       => $info['country'],
                'province'      => $info['province'],
                'city'          => $info['city'],
                'headimgurl'    => $info['headimgurl'],
                'create_time'   => time(),
                'appid'         => $appid,
                'num'           => 1,
                'inviter_id'    => $inviter
            ]);
        unset($info);
        return $res;
    }

    /**
     * 更新用户注册次数
     */
    public function update_wechat_user_subscribe_num($unionid, $openid)
    {
        $res = DB::table('wechat_user')
            ->where('unionid', $unionid)
            ->where('openid', $openid)
            ->increment('num', 1, ['update' => time()]);
        return $res;
    }
}

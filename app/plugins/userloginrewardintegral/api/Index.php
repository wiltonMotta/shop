<?php
// +----------------------------------------------------------------------
// | ShopXO 国内领先企业级B2C免费开源电商系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2099 http://shopxo.net All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://opensource.org/licenses/mit-license.php )
// +----------------------------------------------------------------------
// | Author: Devil
// +----------------------------------------------------------------------
namespace app\plugins\userloginrewardintegral\api;

use app\plugins\userloginrewardintegral\api\Common;
use think\facade\Db;
use app\service\PluginsService;
use app\service\IntegralService;
use app\service\UserService;

/**
 * 登录奖励积分 - 每日打开奖励接口
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  1.0.0
 * @datetime 2016-12-01T21:51:08+0800
 */
class Index extends Common
{
    /**
     * 构造方法
     * @author   Devil
     * @blog     http://gong.gg/
     * @version 1.0.0
     * @date     2018-11-30
     * @desc     description
     * @param    [array]          $params [输入参数]
     */
    public function __construct($params = [])
    {
        parent::__construct($params);
    }

    /**
     * 每日奖励积分（每天最多一次）
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2026-09-04
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function Reward($params = [])
    {
        // 用户id（未登录则不处理）
        $user_id = empty($this->user['id']) ? 0 : intval($this->user['id']);
        if($user_id <= 0)
        {
            return DataReturn('未登录、无需处理', 0);
        }

        // 获取应用数据
        $ret = PluginsService::PluginsData('userloginrewardintegral');
        if($ret['code'] != 0)
        {
            return $ret;
        }
        $data = $ret['data'];

        // 限制时间是否已结束
        if(!empty($data['time_start']) && strtotime($data['time_start']) > time())
        {
            return DataReturn('不在限制时间范围、无需处理', 0);
        }
        if(!empty($data['time_end']) && strtotime($data['time_end']) < time())
        {
            return DataReturn('不在限制时间范围、无需处理', 0);
        }

        // 是否日一次限制（默认当日一次）
        $is_day_once = (isset($data['is_day_once']) && $data['is_day_once'] == 1) ? 1 : 0;
        if($is_day_once == 1)
        {
            $where = [
                ['user_id', '=', $user_id],
                ['add_time', '>=', strtotime(date('Y-m-d 00:00:00'))],
                ['operation_type', '=', 1],
                ['msg', '=', '登录奖励积分'],
            ];
            $log = Db::name('UserIntegralLog')->where($where)->find();
            if(!empty($log))
            {
                return DataReturn('今日已赠送、无需处理', 0);
            }
        }

        // 获取奖励积分
        $give_integral = empty($data['give_integral']) ? 0 : intval($data['give_integral']);
        if($give_integral <= 0)
        {
            return DataReturn('未设置奖励积分、无需处理', 0);
        }

        // 用户积分添加
        $user_integral = Db::name('User')->where(['id'=>$user_id])->value('integral');
        if(Db::name('User')->where(['id'=>$user_id])->inc('integral', $give_integral)->update() === false)
        {
            return DataReturn('每日奖励积分失败', -10);
        }

        // 积分日志
        IntegralService::UserIntegralLogAdd($user_id, $user_integral, $give_integral, '登录奖励积分', 1);

        // 更新用户登录缓存数据
        UserService::UserLoginRecord($user_id);

        // 返回本次奖励积分
        return DataReturn('success', 0, ['integral'=>$give_integral]);
    }
}
?>

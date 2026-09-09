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
namespace app\plugins\membershiplevel\admin;

use app\service\PluginsService;
use app\plugins\membershiplevel\service\Service;

/**
 * 会员等级插件 - 管理
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Admin
{
    /**
     * 首页
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function Index($params = [])
    {
        $ret = PluginsService::PluginsData('membershiplevel', Service::$base_config_attachment_field, false);
        if($ret['code'] == 0)
        {
            // 等级规则
            MyViewAssign('members_level_rules_list', Service::$members_level_rules_list);
            
            MyViewAssign('data', $ret['data']);
            return MyView('../../../plugins/membershiplevel/view/admin/admin/index');
        } else {
            return $ret['msg'];
        }
    }

    /**
     * 编辑页面
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function SaveInfo($params = [])
    {
        $ret = PluginsService::PluginsData('membershiplevel', Service::$base_config_attachment_field, false);
        if($ret['code'] == 0)
        {
            // 等级规则
            MyViewAssign('members_level_rules_list', Service::$members_level_rules_list);

            MyViewAssign('data', $ret['data']);
            return MyView('../../../plugins/membershiplevel/view/admin/admin/saveinfo');
        } else {
            return $ret['msg'];
        }
    }

    /**
     * 数据保存
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function Save($params = [])
    {
        // 会员日数据校验
        // 会员日（每月几号、逗号分割、支持 1-31）
        $member_day = '';
        if(!empty($params['member_day']))
        {
            $day_list = [];
            foreach(explode(',', str_replace(['，', '、', ';', '；'], ',', $params['member_day'])) as $dv)
            {
                $dv = intval(trim($dv));
                if($dv < 1 || $dv > 31)
                {
                    return DataReturn('会员日请输入 1~31 的数字、多个日期用逗号分割', -1);
                }
                if(!in_array($dv, $day_list))
                {
                    $day_list[] = $dv;
                }
            }
            sort($day_list);
            $member_day = implode(',', $day_list);
        }
        $params['member_day'] = $member_day;

        // 会员日商品折扣（0~0.99、0 表示不启用）
        if(!empty($params['member_day_discount']) || isset($params['member_day_discount']))
        {
            $params['member_day_discount'] = floatval($params['member_day_discount']);
            if($params['member_day_discount'] < 0 || $params['member_day_discount'] > 0.99)
            {
                return DataReturn('会员日商品折扣请输入 0~0.99 的数字（0 代表不打折）', -1);
            }
            $params['member_day_discount'] = PriceNumberFormat($params['member_day_discount']);
        }

        // 会员日积分倍率（1~3）
        if(empty($params['member_day_integral_rate']))
        {
            $params['member_day_integral_rate'] = 1;
        } else {
            $params['member_day_integral_rate'] = floatval($params['member_day_integral_rate']);
            if($params['member_day_integral_rate'] < 1 || $params['member_day_integral_rate'] > 3)
            {
                return DataReturn('会员日积分倍率请输入 1~3 的数字（1 为正常积分）', -1);
            }
        }

        // 会员等级数据
        $level = Service::LevelDataList();
        $params['level_list'] = $level['data'];
        return PluginsService::PluginsDataSave(['plugins'=>'membershiplevel', 'data'=>$params]);
    }
}
?>
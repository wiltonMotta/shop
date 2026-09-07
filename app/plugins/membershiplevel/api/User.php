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
namespace app\plugins\membershiplevel\api;

use app\plugins\membershiplevel\api\Common;
use app\plugins\membershiplevel\service\Service;

/**
 * 会员等级 - 用户中心
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  1.0.0
 * @datetime 2016-12-01T21:51:08+0800
 */
class User extends Common
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
     * 会员等级卡片数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2026-09-07
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function LevelCard($params = [])
    {
        // 当前用户（未登录则无当前等级）
        $user = (empty($this->props_params['user']) || empty($this->props_params['user']['id'])) ? [] : $this->props_params['user'];

        // 卡片背景图（插件配置附件字段）
        $card_bg_image = empty($this->plugins_config['card_bg_image']) ? '' : $this->plugins_config['card_bg_image'];

        // 规则模式（0积分、1消费总额）
        $level_rules = empty($this->plugins_config['level_rules']) ? 0 : intval($this->plugins_config['level_rules']);
        $rule_unit = ($level_rules == 1) ? '累计消费' : '累计积分';

        // 用户生日标记
        $is_birthday_today = Service::IsUserBirthdayToday($user);

        // 当前用户等级
        $current_level = [];
        if(!empty($user))
        {
            $current_level = Service::LevelDiscountData(Service::UserLevelMatching($user), $user);
        }

        // 等级列表（仅启用、按规则最小值升序）
        $level_data = Service::LevelDataList();
        $temp = [];
        if($level_data['code'] == 0 && !empty($level_data['data']))
        {
            foreach($level_data['data'] as $k => $v)
            {
                if(isset($v['is_enable']) && $v['is_enable'] == 1)
                {
                    $v['id'] = (empty($v['id']) ? $k : $v['id']);
                    $v['sort_min'] = (isset($v['rules_min']) && $v['rules_min'] > 0) ? floatval($v['rules_min']) : 0;
                    $temp[] = $v;
                }
            }
            usort($temp, function($a, $b) {
                if($a['sort_min'] == $b['sort_min']) return 0;
                return ($a['sort_min'] < $b['sort_min']) ? -1 : 1;
            });
        }
        $current_level_id = empty($current_level['id']) ? '' : $current_level['id'];

        // 组装展示数据
        $level_list = [];
        foreach($temp as $v)
        {
            $level_list[] = [
                'id'                    => $v['id'],
                'name'                  => empty($v['name']) ? '' : $v['name'],
                'rules_min'             => empty($v['rules_min']) ? 0 : floatval($v['rules_min']),
                'rules_max'             => empty($v['rules_max']) ? 0 : floatval($v['rules_max']),
                'rules_text'            => self::RulesText($rule_unit, $v),
                'is_current'            => ($current_level_id != '' && $v['id'] == $current_level_id) ? 1 : 0,
                'daily'                 => self::DiscountTextView($v),
                'birthday'              => self::DiscountTextView($v, 'birthday'),
            ];
        }

        return DataReturn('success', 0, [
            'card_bg_image'         => $card_bg_image,
            'is_birthday_today'     => $is_birthday_today ? 1 : 0,
            'current_level_id'      => $current_level_id,
            'level_list'            => $level_list,
        ]);
    }

    /**
     * 规则值文本
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2026-09-07
     * @desc    description
     * @param   [string]     $unit [单位/前缀]
     * @param   [array]      $v    [等级数据]
     */
    private static function RulesText($unit, $v)
    {
        $min = (isset($v['rules_min']) && $v['rules_min'] > 0) ? floatval($v['rules_min']) : 0;
        $max = (isset($v['rules_max']) && $v['rules_max'] > 0) ? floatval($v['rules_max']) : 0;
        if($max <= 0)
        {
            return ($min > 0) ? $unit.'满 '.PriceNumberFormat($min).' 及以上' : $unit.'不限';
        }
        if($min <= 0)
        {
            return $unit.'不足 '.PriceNumberFormat($max);
        }
        return $unit.' '.PriceNumberFormat($min).'~'.PriceNumberFormat($max);
    }

    /**
     * 折扣/满减展示文本
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2026-09-07
     * @desc    description
     * @param   [array]   $v    [等级数据]
     * @param   [string]  $type [daily|birthday]
     */
    private static function DiscountTextView($v, $type = 'daily')
    {
        $prefix = ($type == 'birthday') ? 'birthday_' : '';
        $rate = (isset($v[$prefix.'discount_rate']) && $v[$prefix.'discount_rate'] > 0) ? floatval($v[$prefix.'discount_rate']) : 0;
        $order_price = (isset($v[$prefix.'order_price']) && $v[$prefix.'order_price'] > 0) ? floatval($v[$prefix.'order_price']) : 0;
        $full_reduction_price = (isset($v[$prefix.'full_reduction_price']) && $v[$prefix.'full_reduction_price'] > 0) ? floatval($v[$prefix.'full_reduction_price']) : 0;

        // 折扣文本（0.95 -> 9.5折）
        $discount_text = '';
        if($rate > 0 && $rate < 1)
        {
            $val = rtrim(rtrim(number_format($rate * 10, 2, '.', ''), '0'), '.');
            $discount_text = $val.'折';
        }

        // 满减文本
        $full_text = '';
        if($order_price > 0 && $full_reduction_price > 0)
        {
            $full_text = '满'.PriceNumberFormat($order_price).'减'.PriceNumberFormat($full_reduction_price);
        }

        return [
            'discount_text'     => $discount_text,
            'full_text'         => $full_text,
            'discount_rate'     => $rate,
            'order_price'       => $order_price,
            'full_reduction'    => $full_reduction_price,
            'is_config'         => ($rate > 0 || ($order_price > 0 && $full_reduction_price > 0)) ? 1 : 0,
        ];
    }
}
?>

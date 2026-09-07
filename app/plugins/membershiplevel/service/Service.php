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
namespace app\plugins\membershiplevel\service;

use think\facade\Db;
use app\service\PluginsService;
use app\service\ResourcesService;
use app\service\UserService;

/**
 * 会员等级服务层
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Service
{
    // 基础数据附件字段
    public static $base_config_attachment_field = [
        'default_level_images'
    ];

    // 等级规则
    public static $members_level_rules_list = [
        0 => ['value' => 0, 'name' => '积分（可用积分）', 'checked' => true],
        1 => ['value' => 1, 'name' => '消费总额（已完成订单）'],
    ];

    /**
     * 获取等级数据列表
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-04-22
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function LevelDataList($params = [])
    {
        // 数据字段
        $data_field = 'level_list';

        // 获取数据
        $ret = PluginsService::PluginsData('membershiplevel', self::$base_config_attachment_field);
        $data = (empty($ret['data']) || empty($ret['data'][$data_field])) ? [] : $ret['data'][$data_field];

        // 数据处理
        return self::LevelDataHandle($data, $params);
    }

    /**
     * 用户等级数据列表处理
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-04-27T01:08:23+0800
     * @param    [array]                   $data   [等级数据]
     * @param    [array]                   $params [输入参数]
     */
    public static function LevelDataHandle($data, $params = [])
    {
        if(!empty($data))
        {
            $common_is_enable_tips = MyConst('common_is_enable_tips');
            foreach($data as &$v)
            {
                // 是否启用
                $v['is_enable_text'] = $common_is_enable_tips[$v['is_enable']]['name'];
                
                // 图片地址
                $v['images_url'] = ResourcesService::AttachmentPathViewHandle($v['images_url']);

                // 时间
                $v['operation_time_time'] = empty($v['operation_time']) ? '' : date('Y-m-d H:i:s', $v['operation_time']);
                $v['operation_time_date'] = empty($v['operation_time']) ? '' : date('Y-m-d', $v['operation_time']);
            }
        }

        // 是否读取单条
        if(!empty($params['get_id']) && isset($data[$params['get_id']]))
        {
            $data = $data[$params['get_id']];
        }

        return DataReturn(MyLang('handle_success'), 0, $data);
    }

    /**
     * 获取等级数据保存
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-04-22
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function LevelDataSave($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'length',
                'key_name'          => 'name',
                'checked_data'      => '1,30',
                'error_msg'         => '名称长度 1~30 个字符',
            ],
            [
                'checked_type'      => 'isset',
                'key_name'          => 'rules_min',
                'error_msg'         => '请填写规则最小值',
            ],
            [
                'checked_type'      => 'isset',
                'key_name'          => 'rules_max',
                'error_msg'         => '请填写规则最大值',
            ],
            [
                'checked_type'      => 'max',
                'key_name'          => 'discount_rate',
                'checked_data'      => 0.99,
                'is_checked'        => 1,
                'error_msg'         => '折扣率应输入 0.00~0.99 的数字,小数保留两位',
            ],
            [
                'checked_type'      => 'fun',
                'key_name'          => 'order_price',
                'checked_data'      => 'CheckPrice',
                'is_checked'        => 1,
                'error_msg'         => '请输入有效的订单满金额',
            ],
            [
                'checked_type'      => 'fun',
                'key_name'          => 'full_reduction_price',
                'checked_data'      => 'CheckPrice',
                'is_checked'        => 1,
                'error_msg'         => '请输入有效的满减金额',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 请求参数
        $p = [
            [
                'checked_type'      => 'eq',
                'key_name'          => 'rules_min',
                'checked_data'      => $params['rules_max'],
                'error_msg'         => '规则最小值不能最大值相等',
            ],
            [
                'checked_type'      => 'eq',
                'key_name'          => 'rules_max',
                'checked_data'      => $params['rules_min'],
                'error_msg'         => '规则最大值不能最小值相等',
            ],
        ];
        if(intval($params['rules_max']) > 0)
        {
            $p[] = [
                'checked_type'      => 'max',
                'key_name'          => 'rules_min',
                'checked_data'      => intval($params['rules_max']),
                'error_msg'         => '规则最小值不能大于最大值['.intval($params['rules_max']).']',
            ];
            $p[] = [
                'checked_type'      => 'min',
                'key_name'          => 'rules_max',
                'checked_data'      => intval($params['rules_min']),
                'error_msg'         => '规则最大值不能小于最小值['.intval($params['rules_min']).']',
            ];
        }
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 生日优惠信息校验（折扣率 0~0.99、0 表示无生日折扣）
        if(isset($params['birthday_discount_rate']) && $params['birthday_discount_rate'] !== '')
        {
            $params['birthday_discount_rate'] = floatval($params['birthday_discount_rate']);
            if($params['birthday_discount_rate'] < 0 || $params['birthday_discount_rate'] > 0.99)
            {
                return DataReturn('生日折扣率应输入 0.00~0.99 的数字,小数保留两位', -1);
            }
        } else {
            $params['birthday_discount_rate'] = 0;
        }
        // 生日满减金额校验
        $p_price = [
            [
                'checked_type'      => 'fun',
                'key_name'          => 'birthday_order_price',
                'checked_data'      => 'CheckPrice',
                'is_checked'        => 1,
                'error_msg'         => '请输入有效的生日满减订单满金额',
            ],
            [
                'checked_type'      => 'fun',
                'key_name'          => 'birthday_full_reduction_price',
                'checked_data'      => 'CheckPrice',
                'is_checked'        => 1,
                'error_msg'         => '请输入有效的生日满减金额',
            ],
        ];
        $ret = ParamsChecked($params, $p_price);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 数据字段
        $data_field = 'level_list';

        // 附件
        $data_fields = ['images_url'];
        $attachment = ResourcesService::AttachmentParams($params, $data_fields);

        // 数据
        $data = [
            'name'                  => $params['name'],
            'rules_min'             => $params['rules_min'],
            'rules_max'             => $params['rules_max'],
            'images_url'            => $attachment['data']['images_url'],
            'is_enable'             => isset($params['is_enable']) ? intval($params['is_enable']) : 0,
            'discount_rate'         => isset($params['discount_rate']) ? $params['discount_rate'] : 0,
            'order_price'           => empty($params['order_price']) ? 0.00 : PriceNumberFormat($params['order_price']),
            'full_reduction_price'  => empty($params['full_reduction_price']) ? 0.00 : PriceNumberFormat($params['full_reduction_price']),
            // 生日优惠信息
            'birthday_discount_rate'        => isset($params['birthday_discount_rate']) ? $params['birthday_discount_rate'] : 0,
            'birthday_order_price'          => empty($params['birthday_order_price']) ? 0.00 : PriceNumberFormat($params['birthday_order_price']),
            'birthday_full_reduction_price' => empty($params['birthday_full_reduction_price']) ? 0.00 : PriceNumberFormat($params['birthday_full_reduction_price']),
            'operation_time'        => time(),
        ];

        // 原有数据
        $ret = PluginsService::PluginsData('membershiplevel', self::$base_config_attachment_field, false);

        // 数据id
        $data['id'] = (empty($params['id']) || empty($ret['data']) || empty($ret['data'][$data_field][$params['id']])) ? date('YmdHis').GetNumberCode(6) : $params['id'];
        $ret['data'][$data_field][$data['id']] = $data;

        // 保存
        return PluginsService::PluginsDataSave(['plugins'=>'membershiplevel', 'data'=>$ret['data']]);
    }

    /**
     * 数据删除
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-12-18
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function DataDelete($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => MyLang('data_id_error_tips'),
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 数据字段
        $data_field = empty($params['data_field']) ? 'data_list' : $params['data_field'];

        // 原有数据
        $ret = PluginsService::PluginsData('membershiplevel', self::$base_config_attachment_field, false);
        $ret['data'][$data_field] = (empty($ret['data']) || empty($ret['data'][$data_field])) ? [] : $ret['data'][$data_field];

        // 删除操作
        if(isset($ret['data'][$data_field][$params['id']]))
        {
            unset($ret['data'][$data_field][$params['id']]);
        }
        
        // 保存
        return PluginsService::PluginsDataSave(['plugins'=>'membershiplevel', 'data'=>$ret['data']]);
    }

    /**
     * 数据状态更新
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-12-18
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function DataStatusUpdate($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => MyLang('data_id_error_tips'),
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'field',
                'error_msg'         => MyLang('operate_field_error_tips'),
            ],
            [
                'checked_type'      => 'in',
                'key_name'          => 'state',
                'checked_data'      => [0,1],
                'error_msg'         => MyLang('form_status_range_message'),
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 数据字段
        $data_field = empty($params['data_field']) ? 'data_list' : $params['data_field'];

        // 原有数据
        $ret = PluginsService::PluginsData('membershiplevel', self::$base_config_attachment_field, false);
        $ret['data'][$data_field] = (empty($ret['data']) || empty($ret['data'][$data_field])) ? [] : $ret['data'][$data_field];

        // 删除操作
        if(isset($ret['data'][$data_field][$params['id']]) && is_array($ret['data'][$data_field][$params['id']]))
        {
            $ret['data'][$data_field][$params['id']][$params['field']] = intval($params['state']);
            $ret['data'][$data_field][$params['id']]['operation_time'] = time();
        }
        
        // 保存
        return PluginsService::PluginsDataSave(['plugins'=>'membershiplevel', 'data'=>$ret['data']]);
    }

    /**
     * 优惠价格计算
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-03-26
     * @desc    description
     * @param   [string]          $price            [商品展示金额]
     * @param   [int]             $plugins_discount [折扣系数]
     * @param   [int]             $plugins_price    [减金额]
     */
    public static function PriceCalculate($price, $plugins_discount = 0, $plugins_price = 0)
    {
        if($plugins_discount <= 0 && $plugins_price <= 0)
        {
            return $price;
        }

        // 折扣
        if($plugins_discount > 0)
        {
            if(stripos($price, '-') !== false)
            {
                $text = explode('-', $price);
                $min_price = $text[0]*$plugins_discount;
                $max_price = $text[1]*$plugins_discount;
                $price = ($min_price <= 0) ? '0.00' : PriceNumberFormat($min_price);
                $price .= '-'.(($max_price <= 0) ? '0.00' : PriceNumberFormat($max_price));
            } else {
                $price = (float) $price *$plugins_discount;
                $price = ($price <= 0) ? '0.00' : PriceNumberFormat($price);
            }
        }

        // 减金额
        if($plugins_price > 0)
        {
            if(stripos($price, '-') !== false)
            {
                $text = explode('-', $price);
                $min_price = $text[0]-$plugins_price;
                $max_price = $text[1]-$plugins_price;
                $price = ($min_price <= 0) ? '0.00' : PriceNumberFormat($min_price);
                $price .= '-'.(($max_price <= 0) ? '0.00' : PriceNumberFormat($max_price));
            } else {
                $price = (float) $price-$plugins_price;
                $price = ($price <= 0) ? '0.00' : PriceNumberFormat($price);
            }
        }
        return $price;
    }

    /**
     * 用户等级匹配
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-04-28
     * @desc    description
     * @param   [array]           $user [用户信息]
     */
    public static function UserLevelMatching($user = [])
    {
        // 未指定用户信息，则从服务层读取
        if(empty($user))
        {
            $user = UserService::LoginUserInfo();
        }
        if(!empty($user))
        {
            // 缓存key
            $key = 'plugins_membershiplevel_cache_user_level_'.$user['id'];
            $level = MyCache($key);

            // 应用配置
            if(empty($level) || MyEnv('app_debug'))
            {
                $base = PluginsService::PluginsData('membershiplevel', Service::$base_config_attachment_field);
                if(!empty($base['data']['level_list']))
                {
                    // 规则
                    $level_rules = isset($base['data']['level_rules']) ? $base['data']['level_rules'] : 0;

                    // 匹配类型
                    $value = 0;
                    switch($level_rules)
                    {
                        // 积分（可用积分）
                        case 0 :
                            $value = isset($user['integral']) ? intval($user['integral']) : 0;
                            break;

                        // 消费总额（已完成订单）
                        // 订单状态（0待确认, 1已确认/待支付, 2已支付/待发货, 3已发货/待收货, 4已完成, 5已取消, 6已关闭）
                        case 1 :
                            $where = ['user_id'=>$user['id'], 'status'=>4];
                            $value = (float) Db::name('Order')->where($where)->sum('total_price');
                            break;
                    }
                    
                    // 匹配相应的等级
                    $level_list = self::LevelDataHandle($base['data']['level_list']);
                    foreach($level_list['data'] as $rules)
                    {
                        if(isset($rules['is_enable']) && $rules['is_enable'] == 1)
                        {
                            // 0-*
                            if($rules['rules_min'] <= 0 && $rules['rules_max'] > 0 && $value < $rules['rules_max'])
                            {
                                $level = $rules;
                                break;
                            }

                            // *-*
                            if($rules['rules_min'] > 0 && $rules['rules_max'] > 0 && $value >= $rules['rules_min'] && $value < $rules['rules_max'])
                            {
                                $level = $rules;
                                break;
                            }

                            // *-0
                            if($rules['rules_max'] <= 0 && $rules['rules_min'] > 0 && $value > $rules['rules_min'])
                            {
                                $level = $rules;
                                break;
                            }
                        }
                    }

                    // 等级icon
                    if(!empty($level) && empty($level['images_url']))
                    {
                        $level['images_url'] = empty($base['data']['default_level_images']) ? StaticAttachmentUrl('level-default-images.png') : $base['data']['default_level_images'];
                    }
                    MyCache($key, $level);
                }
            }
            return $level;
        }
        return [];
    }

    /**
     * 会员日基础配置
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2026-09-07
     * @desc    description
     */
    public static function MemberDayConfig()
    {
        // 未启用插件则不返回任何数据
        if(PluginsService::PluginsStatus('membershiplevel') != 1)
        {
            return ['day_list'=>[], 'discount_rate'=>0, 'integral_rate'=>1];
        }

        // 配置缓存（当前请求内有效）
        static $config = null;
        if($config === null)
        {
            $config = ['day_list'=>[], 'discount_rate'=>0, 'integral_rate'=>1];
            $ret = PluginsService::PluginsData('membershiplevel', self::$base_config_attachment_field);
            if($ret['code'] == 0 && !empty($ret['data']))
            {
                // 会员日列表（逗号分割、支持中文逗号）
                $day_list = [];
                if(!empty($ret['data']['member_day']))
                {
                    foreach(explode(',', str_replace(['，', '、', ';', '；'], ',', $ret['data']['member_day'])) as $dv)
                    {
                        $dv = intval(trim($dv));
                        if($dv >= 1 && $dv <= 31 && !in_array($dv, $day_list))
                        {
                            $day_list[] = $dv;
                        }
                    }
                }
                $config['day_list'] = $day_list;
                // 会员日商品折扣（0~0.99、0 代表不启用会员日折扣）
                if(isset($ret['data']['member_day_discount']) && $ret['data']['member_day_discount'] > 0)
                {
                    $config['discount_rate'] = min(0.99, floatval($ret['data']['member_day_discount']));
                }
                // 会员日积分倍率（1~3）
                if(!empty($ret['data']['member_day_integral_rate']) && $ret['data']['member_day_integral_rate'] > 1)
                {
                    $config['integral_rate'] = min(3, floatval($ret['data']['member_day_integral_rate']));
                }
            }
        }
        return $config;
    }

    /**
     * 指定时间是否会员日
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2026-09-07
     * @desc    description
     * @param   [int]          $time [时间戳、默认当前时间]
     */
    public static function IsMemberDay($time = 0)
    {
        $config = self::MemberDayConfig();
        if(!empty($config['day_list']))
        {
            $day = intval(date('j', empty($time) ? time() : $time));
            return in_array($day, $config['day_list']);
        }
        return false;
    }

    /**
     * 商品最终折扣率（会员日折扣与会员等级折扣取最低）
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2026-09-07
     * @desc    description
     * @param   [float]        $level_discount_rate [会员等级折扣率]
     * @return  [float]                             最终折扣率（0 表示无折扣）
     */
    public static function FinalGoodsDiscountRate($level_discount_rate = 0)
    {
        // 等级折扣率
        $level_rate = floatval($level_discount_rate);
        if($level_rate < 0 || $level_rate >= 1)
        {
            $level_rate = 0;
        }

        // 会员日折扣率（0 表示会员日未设置折扣）
        $member_day_rate = 0;
        if(self::IsMemberDay())
        {
            $config = self::MemberDayConfig();
            if($config['discount_rate'] > 0)
            {
                $member_day_rate = $config['discount_rate'];
            }
        }

        // 等级与会员日同时存在折扣则取最低折扣（不叠加）
        if($level_rate > 0 && $member_day_rate > 0)
        {
            return min($level_rate, $member_day_rate);
        }

        // 仅存在一种则使用该折扣
        return ($level_rate > 0) ? $level_rate : $member_day_rate;
    }

    /**
     * 会员日是否启用商品折扣
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2026-09-07
     * @desc    description
     */
    public static function IsMemberDayDiscountEnable()
    {
        if(self::IsMemberDay())
        {
            $config = self::MemberDayConfig();
            return $config['discount_rate'] > 0;
        }
        return false;
    }

    /**
     * 会员日积分倍率
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2026-09-07
     * @desc    description
     * @param   [int]          $time [时间戳、默认当前时间（下单时间）]
     * @return  [float]              积分倍率（非会员日返回 1）
     */
    public static function MemberDayIntegralRate($time = 0)
    {
        if(self::IsMemberDay($time))
        {
            $config = self::MemberDayConfig();
            return ($config['integral_rate'] > 1) ? $config['integral_rate'] : 1;
        }
        return 1;
    }

    /**
     * 是否正价商品（原价等于售价才支持会员折扣、否则为活动/促销价不参与）
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2026-09-07
     * @desc    description
     * @param   [mixed]        $original_price [原价]
     * @param   [mixed]        $price          [售价]
     * @return  [boolean]                      是否正价商品
     */
    public static function IsNormalPriceGoods($original_price, $price)
    {
        // 无原价数据则按正价处理
        if($original_price === null || $original_price === '' || $price === null || $price === '')
        {
            return true;
        }
        // 原价与售价一致（容差 0.001）
        return abs(floatval($original_price) - floatval($price)) < 0.001;
    }

    /**
     * 用户当天是否生日
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2026-09-07
     * @desc    description
     * @param   [array]          $user [用户信息]
     * @return  [boolean]              是否生日当天（未设置生日则 false）
     */
    public static function IsUserBirthdayToday($user = [])
    {
        // 未指定用户信息则读取当前登录用户
        if(empty($user))
        {
            $user = UserService::LoginUserInfo();
        }
        if(empty($user) || empty($user['id']))
        {
            return false;
        }

        // 生日（用户缓存中可能为时间戳或 yyyy-MM-dd 文本，为空则查询数据库）
        $birthday = (empty($user['birthday']) || $user['birthday'] == 0 || $user['birthday'] == '0000-00-00') ? '' : $user['birthday'];
        $timestamp = 0;
        if($birthday !== '')
        {
            // 数字则为时间戳、否则按日期格式解析
            $timestamp = (is_numeric($birthday)) ? intval($birthday) : strtotime($birthday);
        }
        if($timestamp <= 0)
        {
            $timestamp = intval(Db::name('User')->where(['id'=>$user['id']])->value('birthday'));
        }
        if($timestamp > 0)
        {
            // 月日相同则当天生日（年份忽略）
            return date('md', $timestamp) == date('md');
        }
        return false;
    }

    /**
     * 等级优惠数据（生日当天使用生日优惠信息、否则使用日常优惠信息）
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2026-09-07
     * @desc    description
     * @param   [array]          $level [等级数据]
     * @param   [array]          $user  [用户信息]
     * @return  [array]                 处理后的等级优惠数据（含 discount_rate/order_price/full_reduction_price）
     */
    public static function LevelDiscountData($level = [], $user = [])
    {
        if(empty($level))
        {
            return $level;
        }

        // 非生日当天则直接使用日常优惠信息
        if(!self::IsUserBirthdayToday($user))
        {
            return $level;
        }

        // 生日优惠配置
        $birthday_discount_rate = (isset($level['birthday_discount_rate']) && $level['birthday_discount_rate'] > 0) ? floatval($level['birthday_discount_rate']) : 0;
        $birthday_order_price = (isset($level['birthday_order_price']) && $level['birthday_order_price'] > 0) ? floatval($level['birthday_order_price']) : 0;
        $birthday_full_reduction_price = (isset($level['birthday_full_reduction_price']) && $level['birthday_full_reduction_price'] > 0) ? floatval($level['birthday_full_reduction_price']) : 0;

        // 生日当天生日优惠未配置（折扣与满减均为空）则沿用日常优惠信息
        if($birthday_discount_rate <= 0 && $birthday_order_price <= 0 && $birthday_full_reduction_price <= 0)
        {
            return $level;
        }

        // 生日优惠信息覆盖日常优惠字段（供价格与满减计算使用）
        $level['discount_rate'] = $birthday_discount_rate;
        $level['order_price'] = $birthday_order_price;
        $level['full_reduction_price'] = $birthday_full_reduction_price;
        return $level;
    }

}

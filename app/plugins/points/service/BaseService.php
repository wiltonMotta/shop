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
namespace app\plugins\points\service;

use think\facade\Db;
use app\service\PluginsService;
use app\service\UserService;
use app\service\GoodsService;
use app\service\GoodsCategoryService;
use app\service\ResourcesService;
use app\plugins\points\service\PointsService;

/**
 * 积分商城 - 基础服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class BaseService
{
    // 基础数据附件字段
    public static $base_config_attachment_field = [
        'home_user_bg_web_images',
        'home_banner_web_images',
        'home_banner_app_images',
        'scan_success_images',
        'scan_fail_images',
        'scan_top_banner',
        'scan_bottom_images',
    ];

    /**
     * 基础配置信息保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-24
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function BaseConfigSave($params = [])
    {
        // 描述
        if(!empty($params['points_desc']) && is_string($params['points_desc']))
        {
            $params['points_desc'] = explode("\n", $params['points_desc']);
        }

        return PluginsService::PluginsDataSave(['plugins'=>'points', 'data'=>$params], self::$base_config_attachment_field);
    }
    
    /**
     * 基础配置信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-24
     * @desc    description
     * @param   [boolean]          $is_cache [是否缓存中读取]
     * @param   [boolean]          $is_goods [是否处理商品]
     */
    public static function BaseConfig($is_cache = true, $is_goods = false)
    {
        $ret = PluginsService::PluginsData('points', self::$base_config_attachment_field, $is_cache);
        if(empty($ret['data']))
        {
            $ret['data'] = [];
        }

        // 横幅图片链接地址
        // 当前平台的链接地址
        if(!empty($ret['data']['home_banner_images_url_rules']) && is_array($ret['data']['home_banner_images_url_rules']) && array_key_exists(APPLICATION_CLIENT_TYPE, $ret['data']['home_banner_images_url_rules']))
        {
            $ret['data']['home_banner_images_url'] = $ret['data']['home_banner_images_url_rules'][APPLICATION_CLIENT_TYPE];
        } else {
            $ret['data']['home_banner_images_url'] = '';
        }

        // 底部代码
        $ret['data']['footer_code'] = empty($ret['data']['footer_code']) ? '' : htmlspecialchars_decode($ret['data']['footer_code']);

        // 商品兑换
        if($is_goods === true)
        {
            $ret['data']['goods_exchange_data'] = [];
            if(!empty($ret['data']['goods_exchange']) && is_array($ret['data']['goods_exchange']))
            {
                $goods_exchange_ids = [];
                foreach($ret['data']['goods_exchange'] as $v)
                {
                    if(is_array($v))
                    {
                        if(!empty($v['gid']))
                        {
                            $goods_exchange_ids[] = intval($v['gid']);
                        }
                    } elseif(is_numeric($v))
                    {
                        $goods_exchange_ids[] = intval($v);
                    }
                }
                $goods_exchange_ids = array_unique(array_filter($goods_exchange_ids));
                $res = self::GoodsList($goods_exchange_ids);
                if($res['code'] == 0 && !empty($res['data']) && !empty($res['data']['goods']))
                {
                    $goods_data = array_column($res['data']['goods'], null, 'id');
                    foreach($goods_exchange_ids as $gid)
                    {
                        if(array_key_exists($gid, $goods_data) && !empty($goods_data[$gid]['plugins_points_exchange_integral']))
                        {
                            self::GuestHidePriceExchangeGoodsApply($goods_data[$gid]);
                            $ret['data']['goods_exchange_data'][] = $goods_data[$gid];
                        }
                    }
                }
            }
        }
        return $ret;
    }

    /**
     * 后台权限菜单
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2025-11-21
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function AdminPowerMenu($params = [])
    {
        return [
            [
                'name'      => '基础配置编辑',
                'control'   => 'admin',
                'action'    => 'saveinfo',
                'item'      => [
                    ['name' => '保存', 'action' => 'save'],
                ],
            ],
            [
                'name'      => '扫码积分',
                'control'   => 'scan',
                'action'    => 'index',
                'item'      => [
                    ['name' => '详情', 'action' => 'detail'],
                    ['name' => '添加/编辑页面', 'action' => 'saveinfo'],
                    ['name' => '下载页面', 'action' => 'downloadinfo'],
                    ['name' => '下载', 'action' => 'download'],
                    ['name' => '保存', 'action' => 'save'],
                    ['name' => '状态更新', 'action' => 'statusupdate'],
                    ['name' => '二维码数量生成', 'action' => 'generate'],
                    ['name' => '删除', 'action' => 'delete'],
                ],
            ],
            [
                'name'      => '扫码积分列表',
                'control'   => 'scanqrcode',
                'action'    => 'index',
                'item'      => [
                    ['name' => '二维码生成', 'action' => 'generate'],
                ],
            ],
            [
                'name'      => '商品积分',
                'control'   => 'goods',
                'action'    => 'index',
                'item'      => [
                    ['name' => '保存', 'action' => 'save'],
                ],
            ],
        ];
    }

    /**
     * 后台导航
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-12-06
     * @desc    description
     */
    public static function AdminNavMenuList()
    {
        return [
            [
                'name'      => '基础配置',
                'control'   => 'admin',
                'action'    => 'index',
            ],
            [
                'name'      => '扫码积分',
                'control'   => 'scan',
                'action'    => 'index',
            ],
        ];
    }

    /**
     * 默认图片数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2023-07-27
     * @desc    description
     * @param   [array]          $config [配置信息]
     */
    public static function DefaultImagesData($config)
    {
        // 首页横幅图片
        if(APPLICATION_CLIENT_TYPE == 'pc')
        {
            $home_banner_images = empty($config['home_banner_web_images']) ? StaticAttachmentUrl('home-banner-web.png') : $config['home_banner_web_images'];
        } else {
            $home_banner_images = empty($config['home_banner_app_images']) ? StaticAttachmentUrl('home-banner-app.png') : $config['home_banner_app_images'];
        }
        return [
            'home_user_bg_web_images'  => empty($config['home_user_bg_web_images']) ? StaticAttachmentUrl('home-user-bg-web.png') : $config['home_user_bg_web_images'],
            'home_banner_images'       => $home_banner_images,
            'scan_success_images'      => empty($config['scan_success_images']) ? StaticAttachmentUrl('scan/success.png') : $config['scan_success_images'],
            'scan_fail_images'         => empty($config['scan_fail_images']) ? StaticAttachmentUrl('scan/fail.png') : $config['scan_fail_images'],
            'scan_top_banner'          => empty($config['scan_top_banner']) ? StaticAttachmentUrl('scan/top-banner.png') : $config['scan_top_banner'],
            'scan_bottom_images'       => empty($config['scan_bottom_images']) ? StaticAttachmentUrl('scan/bottom-images.png') : $config['scan_bottom_images'],
        ];
    }

    /**
     * 商品列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-29
     * @desc    description
     * @param   [array]         $params [商品id]
     * @param   [int]           $m      [分页起始值]
     * @param   [int]           $n      [分页数量]
     */
    public static function GoodsList($goods_ids = [], $m = 0, $n = 0)
    {
        // 获取推荐商品id
        if(empty($goods_ids))
        {
            return DataReturn('没有商品id', 0, ['goods'=>[], 'goods_ids'=>[]]);
        }
        if(!is_array($goods_ids))
        {
            $goods_ids = json_decode($goods_ids, true);
        }

        // 获取数据
        $where = [
            ['g.is_delete_time', '=', 0],
            ['g.is_shelves', '=', 1],
            ['g.id', 'in', $goods_ids],
        ];
        $ret = GoodsService::CategoryGoodsList(['where'=>$where, 'm'=>$m, 'n'=>$n, 'is_admin_access'=>((defined('APPLICATION') && APPLICATION === 'admin') ? 1 : 0)]);
        return DataReturn(MyLang('operate_success'), 0, ['goods'=>$ret['data'], 'goods_ids'=>$goods_ids]);
    }

    /**
     * 商品搜索
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-07-13
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function GoodsSearchList($params = [])
    {
        // 返回数据
        $result = [
            'page_total'    => 0,
            'page_size'     => 20,
            'page'          => max(1, isset($params['page']) ? intval($params['page']) : 1),
            'total'         => 0,
            'data'          => [],
        ];

        // 条件
        $where = [
            ['g.is_delete_time', '=', 0],
            ['g.is_shelves', '=', 1],
            ['g.plugins_points_exchange_integral', '>', 0],
        ];

        // 关键字
        if(!empty($params['keywords']))
        {
            $where[] = ['g.title', 'like', '%'.$params['keywords'].'%'];
        }

        // 分类id
        if(!empty($params['category_id']))
        {
            $category_ids = GoodsCategoryService::GoodsCategoryItemsIds([$params['category_id']], 1);
            $category_ids[] = $params['category_id'];
            $where[] = ['gci.category_id', 'in', $category_ids];
        }

        // 获取商品总数
        $result['total'] = GoodsService::CategoryGoodsTotal($where);

        // 获取商品列表
        if($result['total'] > 0)
        {
            // 基础参数
            $field = 'g.id,g.title,g.images';
            $order_by = 'g.id desc';

            // 分页计算
            $m = intval(($result['page']-1)*$result['page_size']);
            $goods = GoodsService::CategoryGoodsList(['where'=>$where, 'm'=>$m, 'n'=>$result['page_size'], 'field'=>$field, 'order_by'=>$order_by, 'is_admin_access'=>1]);
            $result['data'] = self::CategoryGoodsListExtract($goods);
            $result['page_total'] = ceil($result['total']/$result['page_size']);
             // 数据处理
            if(!empty($result['data']) && is_array($result['data']) && !empty($params['goods_ids']) && is_array($params['goods_ids']))
            {
                foreach($result['data'] as &$v)
                {
                    // 是否已添加
                    $v['is_exist'] = in_array($v['id'], $params['goods_ids']) ? 1 : 0;
                }
            }
        }
        return DataReturn(MyLang('handle_success'), 0, $result);
    }

    /**
     * 提取商品列表数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2026-05-16
     * @desc    description
     * @param   [array]          $data [CategoryGoodsList返回的数据]
     */
    public static function CategoryGoodsListExtract($data = [])
    {
        if(!is_array($data))
        {
            return [];
        }
        // GoodsDataHandle 返回 DataReturn 结构
        if(isset($data['code']) && array_key_exists('data', $data) && is_array($data['data']))
        {
            return $data['data'];
        }
        return $data;
    }

    /**
     * 规范化积分兑换商品列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2026-05-16
     * @desc    description
     * @param   [array]          $data [商品列表]
     */
    public static function NormalizeExchangeGoodsList($data = [])
    {
        if(!is_array($data))
        {
            return [];
        }
        $result = [];
        foreach($data as $item)
        {
            if(!is_array($item))
            {
                continue;
            }
            $goods_id = 0;
            if(!empty($item['id']))
            {
                $goods_id = intval($item['id']);
            } elseif(!empty($item['goods_id']))
            {
                $goods_id = intval($item['goods_id']);
                $item['id'] = $goods_id;
            }
            if($goods_id <= 0)
            {
                continue;
            }
            if(empty($item['id']))
            {
                $item['id'] = $goods_id;
            }
            $result[] = $item;
        }
        return $result;
    }

    /**
     * 积分商品列表模块数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2026-05-16
     * @desc    description
     * @param   [array]          $goods_list      [商品列表]
     * @param   [array]          $plugins_config  [插件配置]
     */
    public static function GoodsGridModuleData($goods_list = [], $plugins_config = [])
    {
        if(!is_array($plugins_config))
        {
            $plugins_config = [];
        }
        $goods_list = self::NormalizeExchangeGoodsList($goods_list);
        if(!empty($goods_list))
        {
            foreach($goods_list as &$goods_item)
            {
                self::GuestHidePriceExchangeGoodsApply($goods_item);
            }
            unset($goods_item);
        }

        $module_data = [
            'goods_list'         => $goods_list,
            'type'               => 'index',
            'value_type'         => '1',
            'is_currency_symbol' => 1,
            'button_text'        => '兑换',
            'is_disabled'        => 'false',
            'price_key'          => 'plugins_points_exchange_integral',
        ];
        if(isset($plugins_config['is_pure_exchange_modal']) && $plugins_config['is_pure_exchange_modal'] == 1)
        {
            $module_data['is_show_original_price'] = 0;
        } else {
            $module_data['original_price_key'] = 'min_price';
        }
        return $module_data;
    }

    /**
     * 前台积分兑换商品搜索
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2026-05-16
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function ExchangeGoodsSearchList($params = [])
    {
        if(!is_array($params))
        {
            $params = [];
        }

        // 返回数据
        $result = [
            'page_total'    => 0,
            'page_size'     => empty($params['page_size']) ? 24 : intval($params['page_size']),
            'page'          => max(1, isset($params['page']) ? intval($params['page']) : 1),
            'total'         => 0,
            'data'          => [],
        ];

        // 条件
        $where = [
            ['g.is_delete_time', '=', 0],
            ['g.is_shelves', '=', 1],
            ['g.plugins_points_exchange_integral', '>', 0],
        ];

        // 关键字
        if(!empty($params['wd']))
        {
            $keywords = is_string($params['wd']) ? trim($params['wd']) : '';
            if(!empty($keywords))
            {
                $where[] = ['g.title', 'like', '%'.$keywords.'%'];
            }
        }

        // 获取商品总数
        $result['total'] = GoodsService::CategoryGoodsTotal($where);

        // 获取商品列表
        if($result['total'] > 0)
        {
            $m = intval(($result['page']-1)*$result['page_size']);
            $goods_list = GoodsService::CategoryGoodsList([
                'where'           => $where,
                'm'               => $m,
                'n'               => $result['page_size'],
                'field'           => 'g.*',
                'order_by'        => 'g.sort_level desc, g.id desc',
                'is_admin_access' => (defined('APPLICATION') && APPLICATION === 'admin') ? 1 : 0,
            ]);
            $result['data'] = self::NormalizeExchangeGoodsList(self::CategoryGoodsListExtract($goods_list));
            if(!empty($result['data']))
            {
                foreach($result['data'] as &$goods)
                {
                    self::GuestHidePriceExchangeGoodsApply($goods);
                }
            }
            $result['page_total'] = ceil($result['total']/$result['page_size']);
        }
        return DataReturn(MyLang('handle_success'), 0, $result);
    }

    /**
     * 手机端兑换商品列表价格展示（不依赖 Hook 页面标识）
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2026-05-16
     * @desc    description
     * @param   [array]          $goods_list      [商品列表]
     * @param   [array]          $plugins_config  [插件配置]
     */
    public static function ExchangeGoodsAppListFormat($goods_list = [], $plugins_config = [])
    {
        if(empty($goods_list) || !is_array($goods_list))
        {
            return [];
        }
        if(!is_array($plugins_config))
        {
            $plugins_config = [];
        }

        $goods_ids = [];
        foreach($goods_list as $goods)
        {
            if(!empty($goods['id']))
            {
                $goods_ids[] = intval($goods['id']);
            }
        }
        $goods_ids = array_unique(array_filter($goods_ids));
        if(empty($goods_ids))
        {
            return $goods_list;
        }

        $goods_exchange = PointsService::GoodsExchangeData($plugins_config, $goods_ids);
        if(empty($goods_exchange))
        {
            return $goods_list;
        }

        $is_pure_exchange_modal = isset($plugins_config['is_pure_exchange_modal']) && $plugins_config['is_pure_exchange_modal'] == 1;
        $goods_detail_icon = empty($plugins_config['goods_detail_icon']) ? '积分兑换' : $plugins_config['goods_detail_icon'];
        foreach($goods_list as &$goods)
        {
            if(!is_array($goods) || empty($goods['id']))
            {
                continue;
            }
            $goods_id = intval($goods['id']);

            // 兑换数据（与 Hook GoodslistHandle 一致）
            $temp = [];
            if(!empty($goods_exchange[$goods_id]) && is_array($goods_exchange[$goods_id]))
            {
                $temp = $goods_exchange[$goods_id];
            } elseif(!empty($goods['plugins_points_exchange_integral']))
            {
                $temp = [
                    'integral' => intval($goods['plugins_points_exchange_integral']),
                    'price'    => isset($goods['plugins_points_exchange_price']) ? floatval($goods['plugins_points_exchange_price']) : 0,
                ];
                if(!$is_pure_exchange_modal)
                {
                    $temp['price'] = 0;
                }
            }
            if(!is_array($temp) || empty($temp['integral']))
            {
                continue;
            }

            $points_unit = '积分';
            $show_price_symbol = isset($goods['show_price_symbol']) ? $goods['show_price_symbol'] : '';
            $show_price_unit = isset($goods['show_price_unit']) ? $goods['show_price_unit'] : '';
            $points_value = $temp['integral'];

            if(isset($temp['price']) && $temp['price'] > 0 && empty(self::GuestHidePriceData()))
            {
                $points_value = $temp['price'];
                $show_price_symbol = $temp['integral'].$points_unit.' + '.$show_price_symbol;
            } else {
                $show_price_symbol = '';
                $show_price_unit = ' '.$points_unit.$show_price_unit;
            }

            // 与 PC 端 price_key=plugins_points_exchange_integral 一致
            $goods['plugins_points_exchange_integral'] = $temp['integral'];
            $goods['min_price'] = $points_value;
            $goods['max_price'] = $points_value;
            $goods['price'] = $points_value;
            if($is_pure_exchange_modal)
            {
                $goods['original_price'] = 0;
                $goods['min_original_price'] = 0;
                $goods['max_original_price'] = 0;
            }
            $goods['show_price_symbol'] = $show_price_symbol;
            $goods['show_price_unit'] = $show_price_unit;
            $goods['show_field_price_status'] = 1;
            $goods['show_field_price_text'] = $goods_detail_icon;
        }
        unset($goods);

        if(!empty($goods_list))
        {
            foreach($goods_list as &$goods_item)
            {
                self::GuestHidePriceExchangeGoodsApply($goods_item);
            }
            unset($goods_item);
        }

        return $goods_list;
    }

    /**
     * 是否使用积分
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-12-04
     * @desc    description
     * @param   [array]          $config [插件配置]
     * @param   [array]          $goods  [订单商品]
     * @param   [array]          $params [输入参数]
     */
    public static function IsUsePoints($config, $goods = [], $params = [])
    {
        // 是否选中使用积分
        $is_checked = isset($params['is_points']) && $params['is_points'] == 1;
        // 是否默认使用积分
        if(!$is_checked && !isset($params['is_points']) && isset($config['is_default_use_points']) && $config['is_default_use_points'] == 1)
        {
            $is_checked = true;
        }

        // 未开启自动选择并且开启了纯积分兑换模式则默认选中
        if($is_checked == false && isset($config['is_integral_exchange']) && $config['is_integral_exchange'] == 1 && isset($config['is_pure_exchange_modal']) && $config['is_pure_exchange_modal'] == 1)
        {
            // 是否可以兑换验证
            $points = PointsService::BuyUserPointsData($config, $goods, $params);
            if($points['is_checked'] == 1)
            {
                $is_checked = true;
            }
        }

        return $is_checked;
    }

    /**
     * 积分兑换商品数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2024-11-03
     * @desc    description
     * @param   [int]          $goods_id [商品id]
     */
    public static function ExchangeIntegralGoodsData($goods_id)
    {
        $goods = Db::name('Goods')->where(['id'=>$goods_id])->field('id,title,price,images,plugins_points_exchange_integral,plugins_points_exchange_price')->find();
        if(!empty($goods))
        {
            $goods['goods_url'] = GoodsService::GoodsUrlCreate($goods['id']);
            $goods['images'] = ResourcesService::AttachmentPathViewHandle($goods['images']);
        }
        return $goods;
    }

    /**
     * 商品详情页缓存清除（积分兑换配置变更后）
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2026-06-15
     * @desc    description
     * @param   [int]          $goods_id [商品id]
     */
    public static function GoodsDetailCacheDelete($goods_id)
    {
        $goods_id = intval($goods_id);
        if($goods_id <= 0)
        {
            return;
        }
        $keys = [
            'cache_index_goods_detail_goods_'.$goods_id,
            'cache_index_goods_detail_buy_button_'.$goods_id,
            'cache_index_goods_detail_buy_to_link_'.$goods_id,
            'cache_index_goods_detail_breadcrumb_'.$goods_id,
            'cache_index_goods_detail_buy_left_nav_'.$goods_id.'_0',
            'cache_index_goods_detail_buy_left_nav_'.$goods_id.'_1',
        ];
        foreach($keys as $key)
        {
            MyCache($key, null);
        }
    }

    /**
     * 获取店铺id
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-08-08
     * @desc    description
     */
    public static function ShopID()
    {
        $res = CallPluginsServiceMethod('shop', 'ShopService', 'CurrentUserShopID', true);
        return (empty($res) || (isset($res['code']) && $res['code'] != 0)) ? '' : $res;
    }

    /**
     * 未登录隐藏售价插件配置（前台未登录时）
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2026-06-10
     * @desc    description
     */
    public static function GuestHidePriceData()
    {
        static $result = null;
        static $resolved = false;
        if($resolved)
        {
            return $result;
        }
        $resolved = true;
        if((defined('APPLICATION') && APPLICATION === 'admin') || !empty(UserService::LoginUserInfo()))
        {
            return null;
        }
        // 插件未启用则不处理（PluginsData 仅读配置，不判断启用状态）
        if(PluginsService::PluginsStatus('usernotloginhidegoodsprice') != 1)
        {
            return null;
        }
        $ret = PluginsService::PluginsData('usernotloginhidegoodsprice');
        if($ret['code'] != 0 || empty($ret['data']))
        {
            return null;
        }
        if(!empty($ret['data']['limit_terminal']))
        {
            $limit_terminal_all = explode(',', $ret['data']['limit_terminal']);
            if(!in_array(APPLICATION_CLIENT_TYPE, $limit_terminal_all))
            {
                return null;
            }
        }
        $result = [
            'price_placeholder'          => empty($ret['data']['price_placeholder']) ? '登录可见' : $ret['data']['price_placeholder'],
            'original_price_placeholder' => isset($ret['data']['original_price_placeholder']) ? $ret['data']['original_price_placeholder'] : '',
        ];
        return $result;
    }

    /**
     * 未登录隐藏售价字段处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2026-06-10
     * @desc    description
     * @param   [array]          $data   [数据]
     * @param   [array]          $fields [字段列表]
     */
    public static function GuestHidePriceFieldsApply(&$data, $fields = [])
    {
        $hide = self::GuestHidePriceData();
        if(empty($hide) || empty($data) || !is_array($data))
        {
            return;
        }
        if(empty($fields))
        {
            $fields = [
                'price', 'min_price', 'max_price', 'original_price', 'min_original_price', 'max_original_price',
                'plugins_points_exchange_price', 'total_price', 'discount_price',
            ];
        }
        $original_fields = ['original_price', 'min_original_price', 'max_original_price'];
        foreach($fields as $field)
        {
            if(array_key_exists($field, $data))
            {
                $data[$field] = in_array($field, $original_fields, true) ? $hide['original_price_placeholder'] : $hide['price_placeholder'];
            }
        }
        if(!empty($data['price_container']) && is_array($data['price_container']))
        {
            self::GuestHidePriceFieldsApply($data['price_container']);
        }
    }

    /**
     * 积分兑换商品未登录隐藏售价
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2026-06-10
     * @desc    description
     * @param   [array]          $goods [商品数据]
     */
    public static function GuestHidePriceExchangeGoodsApply(&$goods)
    {
        $hide = self::GuestHidePriceData();
        if(empty($hide) || empty($goods) || !is_array($goods))
        {
            return;
        }
        self::GuestHidePriceFieldsApply($goods);
        // 列表 price_key 使用该字段
        if(array_key_exists('plugins_points_exchange_integral', $goods))
        {
            $goods['plugins_points_exchange_integral'] = $hide['price_placeholder'];
        }
        $goods['show_price_symbol'] = '';
        $goods['show_original_price_symbol'] = '';
        if(!empty($goods['plugins_points_data']) && is_array($goods['plugins_points_data']))
        {
            foreach(['points_integral', 'points_price', 'points_value'] as $field)
            {
                if(array_key_exists($field, $goods['plugins_points_data']))
                {
                    $goods['plugins_points_data'][$field] = $hide['price_placeholder'];
                }
            }
        }
    }
}
?>
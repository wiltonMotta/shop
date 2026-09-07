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
namespace app\plugins\wallet;

use app\service\UserService;
use app\plugins\wallet\service\BaseService;
use app\plugins\wallet\service\WalletService;
use app\plugins\wallet\service\IntegralToBalanceService;

/**
 * 会员等级插件 - 钩子入口
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Hook
{
    /**
     * 应用响应入口
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-09T14:25:44+0800
     * @param    [array]                    $params [输入参数]
     */
    public function handle($params = [])
    {
        if(!empty($params['hook_name']))
        {
            $ret = '';
            switch($params['hook_name'])
            {
                // 用户中心左侧导航
                case 'plugins_service_users_center_left_menu_handle' :
                    $ret = $this->UserCenterLeftMenuHandle($params);
                    break;

                // 顶部小导航右侧-我的商城
                case 'plugins_service_header_navigation_top_right_handle' :
                    $ret = $this->CommonTopNavRightMenuHandle($params);
                    break;

                // 用户注册
                case 'plugins_service_user_register_end' :
                    $ret = WalletService::UserWallet($params['user_id']);
                    break;

                // 拖拽可视化-页面地址
                case 'plugins_layout_service_url_value_begin' :
                    $this->LayoutServiceUrlValueBegin($params);
                    break;
                // 拖拽可视化-页面名称
                case 'plugins_layout_service_pages_list' :
                    $this->LayoutServicePagesList($params);
                    break;

                // 静态数据处理
                case 'plugins_service_const_data' :
                    $this->ConstData($params);
                    break;

                // 支付方式获取
                case 'plugins_service_payment_buy_list' :
                    $this->PaymentDataListHandle($params);
                    break;

                // 支付日志页面列表数据
                case 'plugins_service_paylog_pages_list_data' :
                    $this->PayLogPagesListDataHandle($params);
                    break;

                // 支付日志页面详情数据
                case 'plugins_service_paylog_pages_detail_data' :
                    $this->PayLogPagesDetailDataHandle($params);
                    break;

                // diyapi初始化
                case 'plugins_service_diyapi_init_data' :
                    $this->DiyApiInitDataHandle($params);
                    break;

                // 我的积分页 - 表格顶部操作区
                case 'plugins_view_index_userintegral_top_operate' :
                    $ret = $this->IntegralToBalanceEntryView($params, 'integral');
                    break;

                // API 我的积分列表
                case 'plugins_service_base_data_return_api_userintegral_index' :
                    $this->ApiUserIntegralIndexIntegralToBalance($params);
                    break;

                // 我的钱包明细页顶部（用户钱包导航下方）
                case 'plugins_view_index_plugins_wallet_wallet_content_inside_top' :
                    $ret = $this->IntegralToBalanceEntryView($params, 'wallet');
                    break;
            }
            return $ret;
        }
    }

    /**
     * diyapi初始化
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2023-03-23
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function DiyApiInitDataHandle($params = [])
    {
        // 页面链接
        if(isset($params['data']['page_link_list']) && is_array($params['data']['page_link_list']))
        {
            foreach($params['data']['page_link_list'] as &$lv)
            {
                if(isset($lv['data']) && isset($lv['type']) && $lv['type'] == 'plugins')
                {
                    $lv['data'][] = [
                        'name'  => '钱包',
                        'type'  => 'wallet',
                        'data'  => [
                            ['name'=>'我的钱包', 'page'=>'/pages/plugins/wallet/user/user'],
                            ['name'=>'付款码', 'page'=>'/pages/plugins/wallet/payment-code/payment-code'],
                        ],
                    ];
                    break;
                }
            }
        }
    }

    /**
     * 支付日志页面详情数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-15
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function PayLogPagesDetailDataHandle($params = [])
    {
        if(isset($params['data']) && !empty($params['pay_log']['business_type']) && isset($params['pay_log']['business_type']) && $params['pay_log']['business_type'] == 'wallet' && !empty($params['pay_log_business']))
        {
            foreach($params['pay_log_business'] as $v)
            {
                $params['data'][] = [
                    'url'       => (APPLICATION_CLIENT_TYPE == 'pc') ? PluginsHomeUrl('wallet', 'recharge', 'detail', ['id'=>$v['business_id']]) : '/pages/plugins/wallet/user-recharge-detail/user-recharge-detail?id='.$v['business_id'],
                    'order_no'  => $v['business_no'],
                ];
            }
        }
    }

    /**
     * 支付日志页面列表数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-15
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function PayLogPagesListDataHandle($params = [])
    {
        if(isset($params['data']) && is_array($params['data']))
        {
            $params['data'][] = [
                'name'  => '充值订单',
                'url'   => (APPLICATION_CLIENT_TYPE == 'pc') ? PluginsHomeUrl('wallet', 'recharge', 'index') : '/pages/plugins/wallet/user/user?type=recharge',
            ];
        }
    }

    /**
     * 支付方式列表数据处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2023-11-01
     * @desc    description
     * @param   [array]           $params [收入参数]
     */
    public function PaymentDataListHandle($params = [])
    {
        if(!empty($params['data']) && is_array($params['data']))
        {
            $index = array_search('WalletPay', array_column($params['data'], 'payment'));
            if($index !== false)
            {
                $user = UserService::LoginUserInfo();
                if(!empty($user))
                {
                    // 是否存在钱包、钱包支付名称增加余额展示
                    $wallet_ret = CallPluginsServiceMethod('wallet', 'WalletService', 'UserWallet', $user['id']);
                    if(!empty($wallet_ret) && !empty($wallet_ret['data']) && isset($wallet_ret['data']['normal_money']))
                    {
                        $params['data'][$index]['tips'] = $wallet_ret['data']['normal_money'];
                    }
                }
            }
        }
    }

    /**
     * 常量数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-08-14
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function ConstData($params = [])
    {
        // 新增订单状态
        if(!empty($params['key']))
        {
            switch($params['key'])
            {
                // 支付日志业务类型
                case 'common_pay_log_business_type_list' :
                    $value = BaseService::$business_type_name;
                    $params['data'][$value] = ['value' => $value, 'name' => '钱包'];
                    break;
            }
        }
    }

    /**
     * 拖拽可视化-页面名称
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2023-03-23
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function LayoutServicePagesList($params = [])
    {
        $params['data']['plugins']['data'][] = [
            'name'  => '钱包',
            'value' => 'wallet',
            'data'  => [
                [ 'value' => 'user', 'name' => '我的钱包'],
            ],
        ];
    }

    /**
     * 拖拽可视化-页面地址
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2023-03-23
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function LayoutServiceUrlValueBegin($params = [])
    {
        $params['static_url_web_arr']['plugins-wallet-user'] = PluginsHomeUrl('wallet', 'user', 'index');
        $params['static_url_app_arr']['plugins-wallet-user'] = '/pages/plugins/wallet/user/user';
    }

    /**
     * 用户中心左侧菜单处理
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-04-11
     * @desc    description
     * @param   array           $params [description]
     */
    public function UserCenterLeftMenuHandle($params = [])
    {
        $params['data']['property']['item'][] = [
            'name'      =>  '我的钱包',
            'url'       =>  PluginsHomeUrl('wallet', 'wallet', 'index'),
            'contains'  =>  ['walletwalletindex', 'walletrechargeindex', 'walletcashindex', 'walletcashauthinfo', 'walletcashcreateinfo', 'wallettransferindex'],
            'is_show'   =>  1,
            'icon'      =>  'am-icon-google-wallet',
        ];
    }

    /**
     * 顶部小导航右侧-我的商城
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-04-11
     * @desc    description
     * @param   array           $params [description]
     */
    public function CommonTopNavRightMenuHandle($params = [])
    {
        array_push($params['data'][1]['items'], [
            'name'  => '我的钱包',
            'url'   => PluginsHomeUrl('wallet', 'wallet', 'index'),
        ]);
    }

    /**
     * API 用户积分列表首屏附带积分兑换余额数据
     * @param   [array]          $params
     */
    private function ApiUserIntegralIndexIntegralToBalance($params = [])
    {
        if(empty($params['data']) || !is_array($params['data']))
        {
            return;
        }
        $page = isset($params['params']['page']) ? max(1, intval($params['params']['page'])) : 1;
        if($page != 1)
        {
            return;
        }
        $config = BaseService::BaseConfig();
        $cfg = empty($config['data']) ? [] : $config['data'];
        if(empty($cfg['is_enable_integral_to_balance']) || empty($cfg['is_integral_convert_entry_integral']))
        {
            return;
        }
        $user = UserService::LoginUserInfo();
        if(empty($user['id']))
        {
            return;
        }
        $params['data']['integral_to_balance'] = IntegralToBalanceService::EntryViewData($user['id'], $cfg);
    }

    /**
     * 积分兑换余额入口视图（我的积分 / 我的钱包页嵌入）
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2026-05-11
     * @desc     仅 Web 端；校验总开关与各页展示开关后渲染 integral_to_balance_entry，含弹层打开地址
     * @param    [array]           $params    [钩子参数]
     * @param    [string]          $placement [展示位置 integral=积分页 | wallet=钱包页]
     * @return   [string]          [HTML 或空字符串]
     */
    private function IntegralToBalanceEntryView($params = [], $placement = 'integral')
    {
        if(APPLICATION != 'web')
        {
            return '';
        }
        $user = UserService::LoginUserInfo();
        if(empty($user['id']))
        {
            return '';
        }
        $config = BaseService::BaseConfig();
        $cfg = empty($config['data']) ? [] : $config['data'];
        if(empty($cfg['is_enable_integral_to_balance']))
        {
            return '';
        }
        if($placement == 'integral' && empty($cfg['is_integral_convert_entry_integral']))
        {
            return '';
        }
        if($placement == 'wallet' && empty($cfg['is_integral_convert_entry_wallet']))
        {
            return '';
        }
        $integral_to_balance = IntegralToBalanceService::EntryViewData($user['id'], $cfg);
        $integral_to_balance['placement'] = $placement;

        return MyView('../../../plugins/wallet/view/index/public/integral_to_balance_entry', [
            'integral_to_balance' => $integral_to_balance,
        ]);
    }
}
?>
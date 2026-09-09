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
namespace app\plugins\wallet\api;

use app\plugins\wallet\api\Common;
use app\plugins\wallet\service\BaseService;
use app\plugins\wallet\service\WalletService;
use app\plugins\wallet\service\PayService;
use app\plugins\wallet\service\IntegralToBalanceService;

/**
 * 钱包 - 用户中心
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class User extends Common
{
    /**
     * 构造方法
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-11-30
     * @desc    description
     */
    public function __construct($params = [])
    {
        parent::__construct($params);

        // 是否已经登录
        IsUserLogin();
    }

    /**
     * 首页
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-19
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function Index($params = [])
    {
        $integral_to_balance = null;
        if(!empty($this->plugins_config['is_enable_integral_to_balance']) && !empty($this->plugins_config['is_integral_convert_entry_wallet']))
        {
            $integral_to_balance = IntegralToBalanceService::EntryViewData($this->user['id'], $this->plugins_config);
        }
        $result = [
            'base'                  => $this->plugins_config,
            'user_wallet'           => $this->user_wallet,
            'default_images_data'   => BaseService::DefaultImagesData($this->plugins_config),
            'nav_list'              => BaseService::UserCenterNav($this->plugins_config),
            'integral_to_balance'   => $integral_to_balance,
        ];
        return DataReturn('success', 0, $result);
    }

    /**
     * 付款码
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-11-17
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function PaymentCode($params = [])
    {
        $params['user_id'] = $this->user['id'];
        return PayService::PaymentCode($params);
    }



    /**
     * 充值配置数据（兼容老版本，6.3.0起新版本已经调整到充值页面去了）
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-11-17
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function RechargeConfigData($params = [])
    {
        $result = BaseService::RechargeConfigData($this->plugins_config);
        $result['user_wallet'] = $this->user_wallet;
        return DataReturn('success', 0, $result);
    }

    /**
     * 积分兑换余额（页面数据）
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2026-05-13
     * @desc    与 Web 端钱包 Integralbalanceinfo 一致，供 App 独立页拉取
     */
    public function Integralbalanceinfo($params = [])
    {
        $data = IntegralToBalanceService::EntryViewData($this->user['id'], $this->plugins_config);
        return DataReturn('success', 0, $data);
    }

    /**
     * 积分兑换余额（提交）
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2026-05-13
     * @desc    请求参数 integral_amount
     */
    public function Integralbalanceconvert($params = [])
    {
        $params['user'] = $this->user;
        $params['plugins_config'] = $this->plugins_config;
        $params['user_wallet'] = $this->user_wallet;
        return IntegralToBalanceService::ConvertSave($params);
    }
}
?>
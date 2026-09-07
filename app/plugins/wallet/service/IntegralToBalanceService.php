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
namespace app\plugins\wallet\service;

use app\service\IntegralService;
use app\service\ResourcesService;

/**
 * 积分兑换钱包有效余额
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  1.0.0
 * @datetime 2026-05-11
 * @desc     配置比例、入口展示数据组装、兑换提交与积分/钱包联动
 */
class IntegralToBalanceService
{
    /**
     * 获取提交兑换时「功能未开启」提示文案
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2026-05-11
     * @desc     用于 ConvertSave 与 EntryViewData 关闭态，直 POST 时返回明确说明
     * @return   [string]          [提示内容]
     */
    public static function MsgSubmitFeatureDisabled()
    {
        return '积分兑换余额功能未开启，请联系管理员开启！';
    }

    /**
     * 每多少积分兑换 1 元有效余额
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2026-05-11
     * @desc     读取插件配置 integral_to_balance_points，非法或小于 1 时回退为 100
     * @param    [array]          $plugins_config [插件配置]
     * @return   [int]            [积分比例]
     */
    public static function PointsPerYuan($plugins_config = [])
    {
        $n = isset($plugins_config['integral_to_balance_points']) ? intval($plugins_config['integral_to_balance_points']) : 100;

        return ($n < 1) ? 100 : $n;
    }

    /**
     * 按当前比例计算积分可兑换的有效余额金额
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2026-05-11
     * @desc     金额经 PriceNumberFormat 格式化
     * @param    [int]            $integral       [有效积分]
     * @param    [array]          $plugins_config [插件配置]
     * @return   [float|string]   [可兑换金额，类型取决于 PriceNumberFormat]
     */
    public static function MoneyFromIntegral($integral, $plugins_config = [])
    {
        $points = self::PointsPerYuan($plugins_config);
        $integral = intval($integral);

        return PriceNumberFormat($integral / $points);
    }

    /**
     * 补充各端直接使用的展示文案
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2026-05-11
     * @desc     Web 钩子弹层等共用；勿在模板中重复拼接业务中文
     * @param    [array]          $row [引用，需已含 currency_symbol、积分与预估金额等数值字段]
     */
    private static function fillPresentationTexts(&$row)
    {
        $sym = $row['currency_symbol'];
        $est = $row['integral_to_balance_estimate'];
        $pts = intval($row['integral_to_balance_points']);
        $iv = intval($row['user_integral_value']);

        $row['text_entry_intro_before'] = '将积分转换为钱包有效余额，按当前比例预计可兑换约 ';
        $row['text_entry_intro_after'] = '';
        $row['text_entry_intro_plain'] = $row['text_entry_intro_before'].$sym.$est.$row['text_entry_intro_after'];

        $row['text_btn_exchange'] = '积分兑换余额';
        $row['text_popup_title'] = $row['text_btn_exchange'];

        $row['text_popup_line_integral'] = '当前有效积分：'.$iv;
        $row['text_popup_line_ratio'] = '兑换比例：'.$pts.' 积分 = '.$sym.'1.00';
        $row['text_popup_line_estimate_all'] = '当前积分若全部兑换约：'.$sym.$est;

        $row['text_label_exchange_integral'] = '兑换积分';
        $row['text_placeholder_integral_input'] = '请输入要兑换的积分，最多 '.$iv;
        $row['text_validation_integral_input'] = '请输入 1～'.$iv.' 的整数积分';
        $row['text_preview_convertible_balance'] = '可兑换有效余额：';
        $row['text_btn_submit_exchange'] = '确认兑换';
        $row['text_btn_submit_loading'] = $row['text_btn_submit_exchange'];
        $row['text_popup_fallback_tip'] = '暂无法进行积分兑换。';
    }

    /**
     * 用户端积分兑换入口/弹层所需数据
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2026-05-11
     * @desc     组装是否开启、可否兑换、当前积分、比例、预估金额及全套展示文案，供钩子视图与 integralbalanceinfo 页复用
     * @param    [int]            $user_id         [用户 id]
     * @param    [array]          $plugins_config  [插件配置]
     * @return   [array]          [is_enable_feature、can_exchange、display_message、数值字段与 text_* 文案等]
     */
    public static function EntryViewData($user_id, $plugins_config = [])
    {
        $points = self::PointsPerYuan($plugins_config);
        $currency_symbol = ResourcesService::CurrencyDataSymbol(['is_only_currency_default' => 1]);

        $row = [
            'is_enable_feature'            => !empty($plugins_config['is_enable_integral_to_balance']) ? 1 : 0,
            'can_exchange'                 => 0,
            'display_message'              => '',
            'user_integral_value'          => 0,
            'integral_to_balance_points'   => $points,
            'integral_to_balance_estimate' => PriceNumberFormat(0),
            'currency_symbol'              => $currency_symbol,
        ];

        if(empty($user_id))
        {
            $row['display_message'] = MyLang('user_info_incorrect_tips');
            self::fillPresentationTexts($row);

            return $row;
        }

        if(empty($plugins_config['is_enable_integral_to_balance']))
        {
            $row['display_message'] = self::MsgSubmitFeatureDisabled();
            self::fillPresentationTexts($row);

            return $row;
        }

        $user_integral = IntegralService::UserIntegral($user_id);
        if(empty($user_integral))
        {
            $row['display_message'] = MyLang('user_info_incorrect_tips');
            self::fillPresentationTexts($row);

            return $row;
        }

        $integral = intval($user_integral['integral'] ?? 0);
        $row['user_integral_value'] = $integral;
        $row['integral_to_balance_estimate'] = self::MoneyFromIntegral($integral, $plugins_config);

        if($integral < 1)
        {
            $row['display_message'] = '当前暂无有效积分可兑换';
            self::fillPresentationTexts($row);

            return $row;
        }

        $row['can_exchange'] = 1;
        self::fillPresentationTexts($row);

        return $row;
    }

    /**
     * 提交积分兑换为钱包有效余额
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2026-05-11
     * @desc     校验总开关、积分数量与库存后扣减积分；成功则增加钱包 normal_money；钱包失败时回退积分
     * @param    [array]          $params [需含 plugins_config、user、user_wallet；POST 字段 integral_amount]
     * @return   [array]          [DataReturn 结构，含 code/msg/data]
     */
    public static function ConvertSave($params = [])
    {
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'plugins_config',
                'error_msg'         => MyLang('plugins_config_error_tips'),
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'user',
                'error_msg'         => MyLang('user_info_incorrect_tips'),
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'user_wallet',
                'error_msg'         => '用户钱包有误',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        if(empty($params['plugins_config']['is_enable_integral_to_balance']))
        {
            return DataReturn(self::MsgSubmitFeatureDisabled(), -1);
        }

        $points = self::PointsPerYuan($params['plugins_config']);
        $integral_amount = isset($params['integral_amount']) ? intval($params['integral_amount']) : 0;
        if($integral_amount < 1)
        {
            return DataReturn('请输入要兑换的积分数量', -1);
        }

        $user_id = intval($params['user']['id']);
        $user_integral = IntegralService::UserIntegral($user_id);
        $available = intval($user_integral['integral'] ?? 0);
        if($integral_amount > $available)
        {
            return DataReturn('兑换积分不能超过当前有效积分('.$available.')', -1);
        }

        $money = self::MoneyFromIntegral($integral_amount, $params['plugins_config']);
        if($money <= 0)
        {
            return DataReturn('兑换金额无效，请调整积分数量或兑换比例', -1);
        }

        $integral_ret = IntegralService::UserIntegralUpdate($user_id, null, $integral_amount, '积分兑换钱包余额', 0, 'integral', 0);
        if($integral_ret['code'] != 0)
        {
            return $integral_ret;
        }

        $wallet_ret = WalletService::UserWalletMoneyUpdate($user_id, $money, 1, 'normal_money', 5, '积分兑换余额', [
            'operate_id'   => $user_id,
            'operate_name' => $params['user']['user_name_view'] ?? '',
        ]);
        if($wallet_ret['code'] != 0)
        {
            IntegralService::UserIntegralUpdate($user_id, null, $integral_amount, '积分兑换余额失败退回', 1, 'integral', 0);

            return $wallet_ret;
        }

        return DataReturn(MyLang('operate_success'), 0, [
            'integral' => $available - $integral_amount,
            'money'    => $money,
        ]);
    }
}
?>
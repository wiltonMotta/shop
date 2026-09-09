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
namespace app\plugins\points\api;

use app\service\IntegralService;
use app\plugins\points\api\Common;
use app\plugins\points\service\BaseService;

/**
 * 积分商城 - 兑换商品搜索
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2026-05-16
 * @desc    description
 */
class Search extends Common
{
    /**
     * 初始化
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2026-05-16
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function Index($params = [])
    {
        $integral = [];
        if(!empty($this->user['id']))
        {
            $integral = IntegralService::UserIntegral($this->user['id']);
            if(!is_array($integral))
            {
                $integral = [];
            }
        }
        $result = [
            'base'          => $this->plugins_config,
            'user_integral' => $integral,
        ];
        return DataReturn('success', 0, $result);
    }

    /**
     * 数据列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2026-05-16
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function DataList($params = [])
    {
        $search_params = is_array($params) ? $params : [];

        // 关键字（uniapp 传 wd 或 keywords）
        $keywords = '';
        if(!empty($search_params['wd']) && is_scalar($search_params['wd']))
        {
            $keywords = trim($search_params['wd']);
        } elseif(!empty($search_params['keywords']) && is_scalar($search_params['keywords']))
        {
            $keywords = trim($search_params['keywords']);
        }
        if($keywords !== '')
        {
            $search_params['wd'] = $keywords;
        } else {
            unset($search_params['wd']);
        }

        $ret = BaseService::ExchangeGoodsSearchList($search_params);
        if($ret['code'] != 0)
        {
            return $ret;
        }

        return DataReturn('success', 0, $ret['data']);
    }
}
?>
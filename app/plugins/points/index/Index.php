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
namespace app\plugins\points\index;

use app\service\SeoService;
use app\service\IntegralService;
use app\plugins\points\index\Common;
use app\plugins\points\service\BaseService;

/**
 * 积分商城 - 首页
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-10
 * @desc    description
 */
class Index extends Common
{
    /**
     * 首页
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-10
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Index($params = [])
    {
        // 用户积分
        $user_id = (is_array($this->user) && !empty($this->user['id'])) ? intval($this->user['id']) : 0;
        $integral = $user_id > 0 ? IntegralService::UserIntegral($user_id) : [];
        if(!is_array($integral))
        {
            $integral = [];
        }
        MyViewAssign('user_integral', $integral);

        // 商品列表模块数据
        $plugins_config = is_array($this->plugins_config) ? $this->plugins_config : [];
        $goods_exchange_data = (isset($plugins_config['goods_exchange_data']) && is_array($plugins_config['goods_exchange_data'])) ? $plugins_config['goods_exchange_data'] : [];
        MyViewAssign('goods_exchange_grid_module_data', BaseService::GoodsGridModuleData($goods_exchange_data, $plugins_config));

        // seo
        $seo_title = empty($plugins_config['seo_title']) ? '积分商城' : $plugins_config['seo_title'];
        MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle($seo_title, 2));
        if(!empty($plugins_config['seo_keywords']))
        {
            MyViewAssign('home_seo_site_keywords', $plugins_config['seo_keywords']);
        }
        $seo_desc = empty($plugins_config['seo_desc']) ? (empty($plugins_config['describe']) ? '' : $plugins_config['describe']) : $plugins_config['seo_desc'];
        if(!empty($seo_desc))
        {
            MyViewAssign('home_seo_site_description', $seo_desc);
        }
        return MyView('../../../plugins/points/view/index/index/index');
    }
}
?>
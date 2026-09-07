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
     * 搜索列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2026-05-16
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Index($params = [])
    {
        // post搜索
        $data_post = is_array($this->data_post) ? $this->data_post : [];
        if(!empty($data_post['wd']) && is_scalar($data_post['wd']))
        {
            return MyRedirect(PluginsHomeUrl('points', 'search', 'index', ['wd'=>StrToAscii($data_post['wd'])]));
        }

        // 参数
        $request_params = is_array($this->data_request) ? $this->data_request : (is_array($params) ? $params : []);

        // 关键字（url 中为 ascii 编码，查询与展示使用解码后的文本）
        $keywords = '';
        if(!empty($request_params['wd']) && is_scalar($request_params['wd']))
        {
            $wd = trim($request_params['wd']);
            if(preg_match('/^[a-f0-9]+$/i', $wd) && (strlen($wd) % 2) == 0)
            {
                $keywords = AsciiToStr($wd);
            }
        }

        // 商品列表
        $search_params = $request_params;
        if($keywords !== '')
        {
            $search_params['wd'] = $keywords;
        } else {
            unset($search_params['wd']);
        }
        $ret = BaseService::ExchangeGoodsSearchList($search_params);
        $list_data = (isset($ret['code']) && $ret['code'] == 0 && is_array($ret['data'])) ? $ret['data'] : [
            'page_total'    => 0,
            'page_size'     => 24,
            'page'          => 1,
            'total'         => 0,
            'data'          => [],
        ];

        // 分页
        $page_where = [];
        if(!empty($request_params['wd']) && is_scalar($request_params['wd']))
        {
            $page_where['wd'] = $request_params['wd'];
        }
        $page_params = [
            'number'    => $list_data['page_size'],
            'total'     => $list_data['total'],
            'where'     => $page_where,
            'page'      => $list_data['page'],
            'url'       => PluginsHomeUrl('points', 'search', 'index'),
            'bt_number' => IsMobile() ? 2 : 4,
        ];
        $page = new \base\Page($page_params);

        // 用户积分
        $user_id = (is_array($this->user) && !empty($this->user['id'])) ? intval($this->user['id']) : 0;
        $integral = $user_id > 0 ? IntegralService::UserIntegral($user_id) : [];
        if(!is_array($integral))
        {
            $integral = [];
        }

        // 插件配置
        $plugins_config = is_array($this->plugins_config) ? $this->plugins_config : [];

        $data_total = isset($list_data['total']) ? intval($list_data['total']) : 0;
        $data_list = (isset($list_data['data']) && is_array($list_data['data'])) ? $list_data['data'] : [];

        // 基础参数赋值
        MyViewAssign([
            'page_html'              => $page->GetPageHtml(),
            'data_total'             => $data_total,
            'goods_grid_module_data' => BaseService::GoodsGridModuleData($data_list, $plugins_config),
            'params'                 => $request_params,
            'search_keywords'        => $keywords,
            'search_url'             => PluginsHomeUrl('points', 'search', 'index'),
            'points_home_url'        => PluginsHomeUrl('points', 'index', 'index'),
            'user_integral'          => $integral,
            'plugins_config'         => $plugins_config,
        ]);

        // seo
        $application_name = empty($plugins_config['application_name']) ? MyLang('shop_default_name', [], '', 'points') : $plugins_config['application_name'];
        $search_page_title = MyLang('exchange_search_title', [], '', 'points');
        $seo_title = empty($keywords) ? $search_page_title : ($keywords.' - '.$search_page_title);
        MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle($seo_title.' - '.$application_name, 2));
        if(!empty($plugins_config['seo_keywords']))
        {
            MyViewAssign('home_seo_site_keywords', $plugins_config['seo_keywords']);
        }
        $seo_desc = empty($plugins_config['seo_desc']) ? (empty($plugins_config['describe']) ? '' : $plugins_config['describe']) : $plugins_config['seo_desc'];
        if(!empty($seo_desc))
        {
            MyViewAssign('home_seo_site_description', $seo_desc);
        }

        return MyView('../../../plugins/points/view/index/search/index');
    }
}
?>
<?php

namespace app\admin\controller\webscan;

use app\common\controller\Backend;


/**
 *攻击日志
 */
class Webscanlog extends Backend
{

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\webscan\WebscanLog();
    }

    /**
     * 攻击日志
     * @return string|\think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $limit = $limit ? $limit : 10;
            $total = $this->model->where($where)->count();
            $list = $this->model->where($where)->order($sort, $order)->limit($offset, $limit)->fetchSql(false)->select();
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        } else {
            return $this->view->fetch();
        }
    }

    /**
     * 加入黑名单
     * @param null $ip
     */
    public function black($ip = null)
    {

        if (!$ip) {
            $this->error("请输入ip");
        }

        if ($ip == $this->request->ip()) {
            $this->error("不能把自己的IP加入黑名单");
        }

        //加入黑名单
        $config = get_addon_config('webscan');

        if ($config['webscan_black_ip']) {
            $webscan_black_ip_arr = explode(PHP_EOL, $config['webscan_black_ip']);
            if (in_array($ip, $webscan_black_ip_arr)) {
                $this->error("{$ip}已是黑名单");
            }
        }

        //更新配置文件
        $config['webscan_black_ip'] = $config['webscan_black_ip'] . PHP_EOL . $ip;
        set_addon_config('webscan', $config);
        \think\addons\Service::refresh();

        $this->success("已成功把{$ip}加入了黑名单");
    }

    /**
     * 攻击概括
     */
    public function dashboard()
    {
        //今天开始时间
        $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $todaytimes = $this->model->where('create_time', '>', $beginToday)->count();
        $todayips = $this->model->where('create_time', '>', $beginToday)->group('ip')->count();
        $totaltimes = $this->model->count();
        $totalips = $this->model->group('ip')->count();
        $seventtime = \fast\Date::unixtime('day', -7);
        $totallist = [];

        for ($i = 0; $i < 7; $i++) {
            $day = date("Y-m-d", $seventtime + ($i * 86400));
            //开始时间
            $beginDay = strtotime($day);
            $endDay = strtotime($day . " 23:59:59");
            $totallist[$day] = $this->model->where('create_time', 'between', [$beginDay, $endDay])->count();
        }

        $rankingips = $this->model->group('ip')->field('ip,count(ip) as count')->group('ip')->limit(10)->order('count desc')->select();
        $todayranking = $this->model->group('ip')->where('create_time', '>', $beginToday)->field('ip,count(ip) as count')->group('ip')->limit(10)->order('count desc')->select();
        $this->view->assign([
            'todaytimes' => $todaytimes,//今日攻击次数
            'todayips' => $todayips,//今日攻击ip
            'totallist' => $totallist,//
            'rankingips' => $rankingips,//历史攻击排行
            'totaltimes' => $totaltimes,//总攻击
            'totalips' => $totalips,//总ip
            'todayranking' => $todayranking,//今日攻击排行

        ]);

        return $this->view->fetch();
    }
}

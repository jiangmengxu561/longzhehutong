<?php

namespace app\admin\controller;

use app\admin\model\Admin;
use app\admin\model\User;
use app\common\controller\Backend;
use app\common\model\Attachment;
use fast\Date;
use think\Db;
use think\db\Query;

/**
 * 控制台
 *
 * @icon   fa fa-dashboard
 * @remark 用于展示当前系统中的统计数据、统计报表及重要实时数据
 */
class Dashboard extends Backend
{

    protected $noNeedRight = ['orderstat'];

    /**
     * 查看
     */
    public function index()
    {
        try {
            \think\Db::execute("SET @@sql_mode='';");
        } catch (\Exception $e) {

        }
        $column = [];
        $starttime = Date::unixtime('day', -6);
        $endtime = Date::unixtime('day', 0, 'end');
        $joinlist = Db("user")->where('jointime', 'between time', [$starttime, $endtime])
            ->field('jointime, status, COUNT(*) AS nums, DATE_FORMAT(FROM_UNIXTIME(jointime), "%Y-%m-%d") AS join_date')
            ->group('join_date')
            ->select();
        for ($time = $starttime; $time <= $endtime;) {
            $column[] = date("Y-m-d", $time);
            $time += 86400;
        }
        $userlist = array_fill_keys($column, 0);
        foreach ($joinlist as $k => $v) {
            $userlist[$v['join_date']] = $v['nums'];
        }

        $dbTableList = Db::query("SHOW TABLE STATUS");
        $addonList = get_addon_list();
        $totalworkingaddon = 0;
        $totaladdon = count($addonList);
        foreach ($addonList as $index => $item) {
            if ($item['state']) {
                $totalworkingaddon += 1;
            }
        }
        // 默认统计时间段：最近7天
        $defaultStartDate = date('Y-m-d', strtotime('-6 days'));
        $defaultEndDate   = date('Y-m-d');

        // 获取当前管理员及所属用户组
        $currentAdminId = $this->auth->id;
        $currentGroupId = Db::name('auth_group_access')->where('uid', $currentAdminId)->value('group_id');
        $isSuperAdmin   = ($currentGroupId == 1);

        // 子后台只能查看自己的数据
        $defaultAdminId = $isSuperAdmin ? null : $currentAdminId;

        // 订单统计数据
        $orderStatistics = $this->buildOrderStatData(null, $defaultAdminId, $defaultStartDate, $defaultEndDate);

        // 管理员下拉选项：总后台可以看全部，子后台只显示自己
        if ($isSuperAdmin) {
            $adminOptions = Admin::field('id,nickname,username')->order('id', 'asc')->select();
        } else {
            $adminOptions = Admin::field('id,nickname,username')->where('id', $currentAdminId)->select();
        }

        $this->view->assign([
            'totaluser'         => User::count(),
            'totaladdon'        => $totaladdon,
            'totaladmin'        => Admin::count(),
            'totalcategory'     => \app\common\model\Category::count(),
            'todayusersignup'   => User::whereTime('jointime', 'today')->count(),
            'todayuserlogin'    => User::whereTime('logintime', 'today')->count(),
            'sevendau'          => User::whereTime('jointime|logintime|prevtime', '-7 days')->count(),
            'thirtydau'         => User::whereTime('jointime|logintime|prevtime', '-30 days')->count(),
            'threednu'          => User::whereTime('jointime', '-3 days')->count(),
            'sevendnu'          => User::whereTime('jointime', '-7 days')->count(),
            'dbtablenums'       => count($dbTableList),
            'dbsize'            => array_sum(array_map(function ($item) {
                return $item['Data_length'] + $item['Index_length'];
            }, $dbTableList)),
            'totalworkingaddon' => $totalworkingaddon,
            'attachmentnums'    => Attachment::count(),
            'attachmentsize'    => Attachment::sum('filesize'),
            'picturenums'       => Attachment::where('mimetype', 'like', 'image/%')->count(),
            'picturesize'       => Attachment::where('mimetype', 'like', 'image/%')->sum('filesize'),
            'orderStatAdmins'   => $adminOptions,
            'orderStatStartDate'=> $defaultStartDate,
            'orderStatEndDate'  => $defaultEndDate,
            'orderStatAdminId'  => $defaultAdminId,
            'orderStatCanSelectAdmin' => $isSuperAdmin,
        ]);

        $this->assignconfig('column', array_keys($userlist));
        $this->assignconfig('userdata', array_values($userlist));
        $this->assignconfig('orderStatDefault', $orderStatistics);
        $this->assignconfig('orderStatUrl', url('dashboard/orderstat'));

        return $this->view->fetch();
    }

    /**
     * 订单统计数据
     *
     * @return \think\Response
     */
    public function orderstat()
    {
        // 支持新的时间段(start_date/end_date)，也兼容旧的单个date参数
        $date       = $this->request->param('date', '');
        $startDate  = $this->request->param('start_date', '');
        $endDate    = $this->request->param('end_date', '');
        $adminIdReq = $this->request->param('admin_id', '');

        // 子后台只能查看自己的数据
        $currentAdminId = $this->auth->id;
        $currentGroupId = Db::name('auth_group_access')->where('uid', $currentAdminId)->value('group_id');
        $isSuperAdmin   = ($currentGroupId == 1);

        if ($isSuperAdmin) {
            $adminId = $adminIdReq === '' ? null : (int)$adminIdReq;
        } else {
            // 强制使用当前管理员ID
            $adminId = $currentAdminId;
        }

        $data = $this->buildOrderStatData($date ?: null, $adminId, $startDate ?: null, $endDate ?: null);

        $this->success('', null, $data);
    }

    /**
     * 构建订单统计数据
     *
     * @param string|null $date      单个日期（兼容旧参数，可为空）
     * @param int|null    $adminId   管理员ID（为空则统计全部）
     * @param string|null $startDate 开始日期(YYYY-mm-dd)
     * @param string|null $endDate   结束日期(YYYY-mm-dd)
     * @return array
     */
    protected function buildOrderStatData($date = null, $adminId = null, $startDate = null, $endDate = null)
    {
        [$startTime, $endTime] = $this->resolveDateRange($date, $startDate, $endDate);
        $grabQuery      = $this->createOrderStatQuery($adminId, $startTime, $endTime);
        $processedQuery = $this->createOrderStatQuery($adminId, $startTime, $endTime)->where('status', 1);

        $grabCount      = $grabQuery->count();
        $processedCount = $processedQuery->count();

        return [
            'date'       => $date,
            'admin_id'   => $adminId,
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'grab'       => (int)$grabCount,
            'processed'  => (int)$processedCount,
        ];
    }

    /**
     * 解析日期范围
     *
     * @param string|null $date
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    protected function resolveDateRange($date = null, $startDate = null, $endDate = null)
    {
        // 优先使用时间段
        if ($startDate && $endDate) {
            $startTimestamp = strtotime($startDate . ' 00:00:00');
            $endTimestamp   = strtotime($endDate . ' 23:59:59');
            if ($startTimestamp && $endTimestamp && $startTimestamp <= $endTimestamp) {
                return [$startTimestamp, $endTimestamp];
            }
        }

        // 兼容单个日期
        if ($date) {
            $timestamp = strtotime($date);
            if ($timestamp) {
                $startTime = strtotime(date('Y-m-d 00:00:00', $timestamp));
                $endTime   = strtotime(date('Y-m-d 23:59:59', $timestamp));
                return [$startTime, $endTime];
            }
        }

        // 未指定时间，返回null表示不限制
        return [null, null];
    }

    /**
     * 构建基础查询
     *
     * @param int|null $adminId
     * @param int|null $startTime
     * @param int|null $endTime
     * @return Query
     */
    protected function createOrderStatQuery($adminId, $startTime, $endTime)
    {
        $query = Db::name('admin_order');

        if (!is_null($adminId)) {
            $query->where('admin_id', $adminId);
        }

        if ($startTime && $endTime) {
            $query->whereBetween('createtime', [$startTime, $endTime]);
        }

        return $query;
    }

}

<?php

namespace app\admin\controller\admin;

use app\common\controller\Backend;
use think\Db;

/**
 * 抢单统计（调度/线路）
 * 仅统计管理员组为"调度"和"线路"的 admin_order 抢单数据
 * 支持按管理员筛选，展示每位管理员的抢单明细
 *
 * @icon fa fa-bar-chart
 */
class Orderstat extends Backend
{
    protected $noNeedRight = ['index', 'data', 'admins'];

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 统计页面
     */
    public function index()
    {
        $defaultStartDate = date('Y-m-d', strtotime('-6 days'));
        $defaultEndDate   = date('Y-m-d');

        $statData   = $this->buildStatData($defaultStartDate, $defaultEndDate, null);
        $adminList  = $this->getDispatchLineAdmins();

        $this->view->assign([
            'statStartDate' => $defaultStartDate,
            'statEndDate'   => $defaultEndDate,
            'statData'      => $statData,
            'adminList'     => $adminList,
        ]);
        $this->assignconfig('statUrl', url('/admin/admin/orderstat/data'));
        $this->assignconfig('adminsUrl', url('/admin/admin/orderstat/admins'));
        $this->assignconfig('statDefault', $statData);

        return $this->view->fetch();
    }

    /**
     * 获取调度/线路管理员列表（用于下拉筛选）
     */
    public function admins()
    {
        $list = $this->getDispatchLineAdmins();
        $this->success('', null, ['list' => $list]);
    }

    /**
     * AJAX 获取统计数据
     */
    public function data()
    {
        $startDate = $this->request->param('start_date', '');
        $endDate   = $this->request->param('end_date', '');
        $adminId   = $this->request->param('admin_id', '');

        if (!$startDate || !$endDate) {
            $startDate = date('Y-m-d', strtotime('-6 days'));
            $endDate   = date('Y-m-d');
        }

        $adminId = $adminId === '' ? null : (int)$adminId;
        $statData = $this->buildStatData($startDate, $endDate, $adminId);
        $this->success('', null, $statData);
    }

    /**
     * 获取调度和线路组下的管理员列表
     * @return array [['id'=>1,'nickname'=>'xx','group_name'=>'调度'], ...]
     */
    protected function getDispatchLineAdmins()
    {
        $groupIds = $this->getDispatchLineGroupIds();
        if (empty($groupIds)) {
            return [];
        }

        $uids = Db::name('auth_group_access')
            ->where('group_id', 'in', $groupIds)
            ->column('uid');
        if (empty($uids)) {
            return [];
        }

        $admins = Db::name('admin')
            ->where('id', 'in', $uids)
            ->where('status', 'normal')
            ->field('id,nickname,username')
            ->select();

        $groupNames = Db::name('auth_group')
            ->where('id', 'in', $groupIds)
            ->column('name', 'id');

        $uidToGroup = [];
        foreach (Db::name('auth_group_access')->where('uid', 'in', $uids)->where('group_id', 'in', $groupIds)->select() as $row) {
            $uidToGroup[$row['uid']] = $groupNames[$row['group_id']] ?? '';
        }

        $list = [];
        foreach ($admins as $a) {
            $list[] = [
                'id'         => $a['id'],
                'nickname'   => $a['nickname'] ?: $a['username'],
                'username'   => $a['username'],
                'group_name' => $uidToGroup[$a['id']] ?? '',
            ];
        }
        usort($list, function ($a, $b) {
            $o = ['调度' => 0, '线路' => 1];
            $ga = $o[$a['group_name']] ?? 2;
            $gb = $o[$b['group_name']] ?? 2;
            if ($ga !== $gb) return $ga - $gb;
            return strcmp($a['nickname'], $b['nickname']);
        });
        return $list;
    }

    /**
     * 获取调度和线路的 group_id 列表
     * @return array
     */
    protected function getDispatchLineGroupIds()
    {
        $groups = Db::name('auth_group')
            ->where('name', 'in', ['调度', '线路'])
            ->where('status', 'normal')
            ->column('id');
        return $groups ?: [];
    }

    /**
     * 构建统计数据
     * @param string     $startDate Y-m-d
     * @param string     $endDate   Y-m-d
     * @param int|null   $adminId   指定管理员ID则只统计该管理员
     * @return array
     */
    protected function buildStatData($startDate, $endDate, $adminId = null)
    {
        $groupIds = $this->getDispatchLineGroupIds();
        if (empty($groupIds)) {
            return [
                'dispatch'    => ['grab' => 0, 'processed' => 0],
                'line'        => ['grab' => 0, 'processed' => 0],
                'total'       => ['grab' => 0, 'processed' => 0],
                'admin_list'  => [],
            ];
        }

        $startTime = strtotime($startDate . ' 00:00:00');
        $endTime   = strtotime($endDate . ' 23:59:59');

        $dispatchGroupId = Db::name('auth_group')->where('name', '调度')->where('status', 'normal')->value('id');
        $lineGroupId     = Db::name('auth_group')->where('name', '线路')->where('status', 'normal')->value('id');

        $baseWhere = [
            ['createtime', 'between', [$startTime, $endTime]],
            ['group_id', 'in', $groupIds],
        ];
        if ($adminId !== null) {
            $baseWhere[] = ['admin_id', '=', (string)$adminId];
        }

        $dispatch = ['grab' => 0, 'processed' => 0];
        $line     = ['grab' => 0, 'processed' => 0];

        if ($dispatchGroupId) {
            $q = Db::name('admin_order')->where('group_id', $dispatchGroupId)->whereBetween('createtime', [$startTime, $endTime]);
            if ($adminId !== null) $q->where('admin_id', (string)$adminId);
            $dispatch['grab'] = (int)$q->count();

            $q = Db::name('admin_order')->where('group_id', $dispatchGroupId)->where('status', 1)->whereBetween('createtime', [$startTime, $endTime]);
            if ($adminId !== null) $q->where('admin_id', (string)$adminId);
            $dispatch['processed'] = (int)$q->count();
        }

        if ($lineGroupId) {
            $q = Db::name('admin_order')->where('group_id', $lineGroupId)->whereBetween('createtime', [$startTime, $endTime]);
            if ($adminId !== null) $q->where('admin_id', (string)$adminId);
            $line['grab'] = (int)$q->count();

            $q = Db::name('admin_order')->where('group_id', $lineGroupId)->where('status', 1)->whereBetween('createtime', [$startTime, $endTime]);
            if ($adminId !== null) $q->where('admin_id', (string)$adminId);
            $line['processed'] = (int)$q->count();
        }

        $total = [
            'grab'      => $dispatch['grab'] + $line['grab'],
            'processed' => $dispatch['processed'] + $line['processed'],
        ];

        // 每位管理员的明细（按 admin_id 分组）
        $adminList = $this->buildAdminDetailList($groupIds, $startTime, $endTime, $adminId);

        return [
            'dispatch'   => $dispatch,
            'line'       => $line,
            'total'      => $total,
            'admin_list' => $adminList,
        ];
    }

    /**
     * 构建每位管理员的抢单明细
     */
    protected function buildAdminDetailList($groupIds, $startTime, $endTime, $filterAdminId = null)
    {
        $query = Db::name('admin_order')
            ->where('group_id', 'in', $groupIds)
            ->whereBetween('createtime', [$startTime, $endTime]);
        if ($filterAdminId !== null) {
            $query->where('admin_id', (string)$filterAdminId);
        }

        $rows = $query->field('admin_id,group_id,status')->select();
        $groupNames = Db::name('auth_group')->where('id', 'in', $groupIds)->column('name', 'id');

        $byAdmin = [];
        foreach ($rows as $r) {
            $aid = $r['admin_id'];
            if (!isset($byAdmin[$aid])) {
                $byAdmin[$aid] = ['admin_id' => $aid, 'grab' => 0, 'processed' => 0, 'group_name' => $groupNames[$r['group_id']] ?? ''];
            }
            $byAdmin[$aid]['grab']++;
            if ($r['status'] == 1) $byAdmin[$aid]['processed']++;
        }

        $adminIds = array_unique(array_column($byAdmin, 'admin_id'));
        if (empty($adminIds)) return [];

        $adminRows = Db::name('admin')->where('id', 'in', $adminIds)->where('status', 'normal')->field('id,nickname,username')->select();
        $admins = [];
        foreach ($adminRows as $a) {
            $admins[$a['id']] = $a['nickname'] ?: $a['username'];
        }
        $list = [];
        foreach ($byAdmin as $aid => $row) {
            $nickname = $admins[$aid] ?? ('ID:' . $aid);
            $list[] = [
                'admin_id'   => $aid,
                'admin_name' => $nickname,
                'group_name' => $row['group_name'],
                'grab'       => $row['grab'],
                'processed'  => $row['processed'],
            ];
        }
        usort($list, function ($a, $b) {
            $o = ['调度' => 0, '线路' => 1];
            $ga = $o[$a['group_name']] ?? 2;
            $gb = $o[$b['group_name']] ?? 2;
            if ($ga !== $gb) return $ga - $gb;
            if ($a['grab'] !== $b['grab']) return $b['grab'] - $a['grab'];
            return strcmp($a['admin_name'], $b['admin_name']);
        });
        return $list;
    }
}

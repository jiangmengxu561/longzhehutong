<?php

namespace app\admin\controller;

use app\admin\library\FranchiseService;
use app\admin\model\Franchise as FranchiseModel;
use app\common\controller\Backend;
use think\Db;

/**
 * 加盟商管理
 *
 * @icon fa fa-handshake-o
 */
class Franchise extends Backend
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['staffstatus', 'staffdel', 'commdetail'];

    /**
     * @var FranchiseModel
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new FranchiseModel;
        $this->view->assign('membertypeList', [
            '1' => '普通用户',
            '2' => '兼职员工',
            '3' => '正式员工',
            '4' => '会展员工',
        ]);
        // 区域数据（用于市→区县联动）
        $cityList = Db::name('area')->where('level', 2)->field('id,pid,name')->order('id asc')->select();
        $districtList = Db::name('area')->where('level', 3)->field('id,pid,name')->order('id asc')->select();
        $this->view->assign('cityList', $cityList);
        $this->view->assign('districtList', $districtList);
    }

    /**
     * 当前登录人对应的加盟商（总部返回 null）
     */
    protected function currentFranchise(): ?array
    {
        if ($this->auth->isSuperAdmin()) {
            return null;
        }

        return FranchiseService::getFranchiseByAdminId($this->auth->id);
    }

    /**
     * 当前登录人有权限“管理/查看”的加盟商 ID 集合
     *
     * @return int[]|null
     */
    protected function visibleFranchiseIds(?array $operator): ?array
    {
        if ($this->auth->isSuperAdmin()) {
            return null; // null=不限
        }
        if (!$operator) {
            return [];
        }

        return FranchiseService::getSubtreeFranchiseIds((int)$operator['id']);
    }

    /**
     * 是否有权新增下级加盟商：总部=可为一级；一级=可为其下一级；二级=否
     */
    protected function canAddSubFranchise(?array $operator): bool
    {
        if ($this->auth->isSuperAdmin()) {
            return true;
        }
        if (!$operator) {
            return false;
        }

        return (int)$operator['level'] === FranchiseService::LEVEL_1;
    }

    /**
     * 校验某个加盟商是否在当前操作者可管理的范围内
     */
    protected function checkManageScope(?array $operator, int $franchiseId): bool
    {
        if ($this->auth->isSuperAdmin()) {
            return true;
        }
        $ids = $this->visibleFranchiseIds($operator) ?: [];

        return in_array($franchiseId, $ids, true);
    }

    /**
     * 查看
     */
    public function index()
    {
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            [$where, $sort, $order, $offset, $limit] = $this->buildparams();
            $operator = $this->currentFranchise();
            $visible = $this->visibleFranchiseIds($operator);
            if ($visible === []) {
                return json(['total' => 0, 'rows' => []]);
            }

            $query = Db::name('franchise');
            if ($visible !== null) {
                $query->where('id', 'in', $visible);
            }
            // 已删除/停用的加盟商不展示
            $query->where('status', '<>', 'disabled');
            $list = $query
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);
            $rows = $list->items();
            $rowIds = array_map('intval', array_column($rows, 'id'));
            $commMap = $this->sumOrderCommissionMap($rowIds);
            foreach ($rows as &$v) {
                $v['admin_nickname'] = Db::name('admin')->where('id', $v['admin_id'])->value('nickname');
                $v['admin_username'] = Db::name('admin')->where('id', $v['admin_id'])->value('username');
                $v['parent_name'] = $v['parent_id'] > 0
                    ? Db::name('franchise')->where('id', $v['parent_id'])->value('name')
                    : '总部';
                $v['level_text'] = $v['level'] == 2 ? '二级加盟商' : '一级加盟商';
                $v['wallet_balance'] = number_format((float)$v['wallet_balance'], 2, '.', '');
                $v['commission_total'] = number_format((float)($commMap[(int)$v['id']] ?? 0), 2, '.', '');
                $v['status_text'] = $v['status'] == 'hidden' ? '隐藏' : ($v['status'] == 'disabled' ? '停用' : '正常');
            }
            unset($v);

            return json(['total' => $list->total(), 'rows' => $rows]);
        }

        // 累计抽佣总额（总部=全加盟商；一级=仅下属二级加盟商）
        $operator = $this->currentFranchise();
        $visible = $this->visibleFranchiseIds($operator);
        if ($visible === []) {
            $commissionTotal = 0.0;
        } elseif ($visible === null) {
            // 总部：累计抽佣 = 一级加盟商钱包的订单抽佣合计（总部自有收入）
            $level1Ids = array_map('intval', Db::name('franchise')
                ->where('level', FranchiseService::LEVEL_1)
                ->column('id'));
            $commissionTotal = array_sum($this->sumOrderCommissionMap($level1Ids));
        } else {
            // 一级：累计抽佣 = 下属二级加盟商钱包的订单抽佣合计（一级从二级获得的收入）
            $childIds = array_map('intval', Db::name('franchise')
                ->where('id', 'in', $visible)
                ->where('level', FranchiseService::LEVEL_2)
                ->column('id'));
            $commissionTotal = array_sum($this->sumOrderCommissionMap($childIds));
        }
        $this->view->assign('commissionTotal', number_format($commissionTotal, 2, '.', ''));

        return $this->view->fetch();
    }

    /**
     * 加盟商下拉（可输入搜索）：供订单管理「所属加盟商」筛选使用
     */
    public function selectpage_franchise()
    {
        $this->request->filter(['trim', 'strip_tags', 'htmlspecialchars']);
        $word = (array)$this->request->request('q_word/a', []);
        $keyValue = $this->request->request('keyValue', '');
        $page = (int)$this->request->request('pageNumber', 1);
        $pagesize = (int)$this->request->request('pageSize', 10);
        $primarykey = $this->request->request('keyField', 'id');

        $buildQuery = function () use ($word, $keyValue, $primarykey) {
            $query = Db::name('franchise')->where('status', 'normal');
            if ($keyValue !== null && $keyValue !== '') {
                $keyValueArr = is_array($keyValue) ? $keyValue : explode(',', $keyValue);
                $query->where($primarykey, 'in', $keyValueArr);
            } else {
                $word = array_filter(array_unique($word));
                if (!empty($word)) {
                    $kw = '%' . implode('%', $word) . '%';
                    $query->where('name|admin_id', 'like', $kw);
                }
            }
            return $query;
        };

        if ($keyValue !== null && $keyValue !== '') {
            $pagesize = 999999;
        }

        $total = $buildQuery()->count();
        $list = [];
        if ($total > 0) {
            $rows = $buildQuery()->field('id,name,level,parent_id')->order('id', 'asc')->page($page, $pagesize)->select();
            foreach ($rows as $item) {
                $name = trim((string)($item['name'] ?? ''));
                $level = (int)($item['level'] ?? 0);
                if ($level === 1) {
                    $show = $name . '（一级）';
                } elseif ($level === 2) {
                    $parent = '';
                    if ((int)($item['parent_id'] ?? 0) > 0) {
                        $parent = (string)Db::name('franchise')->where('id', (int)$item['parent_id'])->value('name');
                    }
                    $show = $name . '（二级' . ($parent !== '' ? '，一级：' . $parent : '') . '）';
                } else {
                    $show = $name;
                }
                $list[] = [
                    $primarykey => (string)($item[$primarykey] ?? ''),
                    'showField' => htmlentities($show),
                ];
            }
        }
        return json(['list' => $list, 'total' => $total]);
    }

    /**
     * 新增加盟商（总部=一级；一级=二级）
     */
    public function add()
    {
        $operator = $this->currentFranchise();
        if (!$this->canAddSubFranchise($operator)) {
            $this->error('您没有权限新增加盟商（二级加盟商不能发展下级）');
        }
        if ($this->request->isPost()) {
            $this->token();
            $data = $this->request->post('row/a');
            $parentId = (int)$this->request->post('parent_id');

            if ($operator) {
                // 一级加盟商只能在自己名下创建二级
                $parentId = (int)$operator['id'];
            } elseif ($parentId <= 0) {
                $parentId = 0; // 总部创建一级
            } else {
                $parent = Db::name('franchise')->where('id', $parentId)->find();
                if (!$parent || (int)$parent['level'] !== FranchiseService::LEVEL_1) {
                    $this->error('新建二级加盟商时，上级必须是一级加盟商');
                }
            }
            $data['region_ids'] = (array)$this->request->post('region_ids/a');
            $data['formal_employee_quota'] = (int)$this->request->post('formal_employee_quota');
            $data['line_quota'] = (int)$this->request->post('line_quota', 0);
            $data['dispatch_quota'] = (int)$this->request->post('dispatch_quota', 0);
            $data['logistics_audit'] = ((string)$this->request->post('logistics_audit') === '1') ? 1 : 0;

            $res = FranchiseService::createFranchise($parentId, $data, $this->auth);
            if ($res['success']) {
                $this->success($res['msg']);
            }
            $this->error($res['msg']);
        }

        // 可供选择的上级（总部可选0=新增一级，或选一级新增二级；一级固定为自身）
        $parentList = [];
        if ($operator) {
            $parentList[$operator['id']] = $operator['name'] . '（新增二级）';
        } else {
            $parentList[0] = '总部（新增一级）';
            $level1 = Db::name('franchise')->where('level', 1)->where('status', 'normal')->select();
            foreach ($level1 as $v) {
                $parentList[$v['id']] = $v['name'] . '（新增二级）';
            }
        }
        $this->view->assign('parentList', $parentList);
        $this->view->assign('forcedParent', $operator ? (int)$operator['id'] : 0);
        // 供区县联动的“上级城市”区域
        $parentRegion = [];
        $selectParent = (int)$this->request->request('parent_id', 0);
        if ($selectParent > 0) {
            $p = Db::name('franchise')->where('id', $selectParent)->find();
            $parentRegion = $p ? (json_decode((string)($p['region_ids'] ?? '[]'), true) ?: []) : [];
        }
        $this->view->assign('parentRegion', $parentRegion);
        $this->view->assign('isLevel2', $operator ? 1 : 0);
        // 供前端“选择上级→区县联动”使用：上级加盟商ID => 其区域(市ID)
        $parentRegionMap = [];
        if ($operator) {
            $parentRegionMap[$operator['id']] = json_decode((string)($operator['region_ids'] ?? '[]'), true) ?: [];
        } else {
            $level1 = Db::name('franchise')->where('level', 1)->where('status', 'normal')->select();
            foreach ($level1 as $v) {
                $parentRegionMap[$v['id']] = json_decode((string)($v['region_ids'] ?? '[]'), true) ?: [];
            }
        }
        $this->view->assign('parentRegionMap', $parentRegionMap);
        // 已被其他加盟商占用的区域ID（前端过滤掉，避免重复选择）
        $occupied = Db::name('franchise')->select();
        $occupiedRegionIds = [];
        foreach ($occupied as $f) {
            $rids = json_decode((string)($f['region_ids'] ?? '[]'), true) ?: [];
            foreach ($rids as $rid) {
                $occupiedRegionIds[(int)$rid] = true;
            }
        }
        $this->view->assign('occupiedRegionIds', array_values(array_map('intval', array_keys($occupiedRegionIds))));

        return $this->view->fetch();
    }

    /**
     * 编辑加盟商（资料与费率/费用配置）
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $operator = $this->currentFranchise();
        if (!$this->checkManageScope($operator, (int)$ids)) {
            $this->error(__('You have no permission'));
        }
        if ($this->request->isPost()) {
            $this->token();
            $params = $this->request->post('row/a');
            $allow = [
                'name', 'contact', 'mobile', 'remark',
                'order_fee_percent', 'wallet_balance', 'status', 'formal_employee_quota',
                'logistics_audit', 'line_quota', 'dispatch_quota', 'contract',
            ];
            $update = array_intersect_key($params, array_flip($allow));
            // 复选框未勾选时不会提交，这里强制写入 0/1
            $update['logistics_audit'] = ((string)$this->request->post('logistics_audit') === '1') ? 1 : 0;
            // 账号数量上限（0=不限）
            $update['line_quota'] = (int)$this->request->post('line_quota', 0);
            $update['dispatch_quota'] = (int)$this->request->post('dispatch_quota', 0);
            // 区域（如变更则校验并同步角色组 city）
            $regionIds = (array)$this->request->post('region_ids/a');
            if ($regionIds !== []) {
                $regionIds = array_values(array_filter(array_map('intval', $regionIds)));
                $current = json_decode((string)$row['region_ids'], true) ?: [];
                sort($regionIds);
                sort($current);
                if ($regionIds !== $current) {
                    $parentRegionIds = [];
                    if ((int)$row['parent_id'] > 0) {
                        $p = Db::name('franchise')->where('id', (int)$row['parent_id'])->find();
                        $parentRegionIds = $p ? (json_decode((string)($p['region_ids'] ?? '[]'), true) ?: []) : [];
                    }
                    $vr = FranchiseService::validateRegion((int)$row['level'], $regionIds, $parentRegionIds, (int)$ids);
                    if (!$vr['ok']) {
                        $this->error($vr['msg']);
                    }
                    $update['region_ids'] = json_encode($vr['region_ids']);
                    FranchiseService::writeRegionToGroupCity((int)$row['group_id'], $vr['region_ids']);
                }
            }
            if ($update === []) {
                $this->error('请至少填写一项');
            }
            $res = $this->model->isUpdate(true)->allowField(true)->save($update, ['id' => (int)$ids]);
            if ($res === false) {
                $this->error('保存失败');
            }
            $this->success();
        }
        $this->view->assign('row', $row);
        $row->region_ids_arr = json_decode((string)($row['region_ids'] ?? '[]'), true) ?: [];
        $this->view->assign('rowRegion', $row->region_ids_arr);
        $parentRegion = [];
        if ((int)$row['parent_id'] > 0) {
            $p = Db::name('franchise')->where('id', (int)$row['parent_id'])->find();
            $parentRegion = $p ? (json_decode((string)($p['region_ids'] ?? '[]'), true) ?: []) : [];
        }
        $this->view->assign('parentRegion', $parentRegion);
        // 编辑时：记录“被其他加盟商占用”的区域ID（排除自身），前端禁用这些选项
        $occupiedRegionIds = [];
        $others = Db::name('franchise')->where('id', '<>', (int)$ids)->select();
        foreach ($others as $f) {
            $rids = json_decode((string)($f['region_ids'] ?? '[]'), true) ?: [];
            foreach ($rids as $rid) {
                $occupiedRegionIds[(int)$rid] = true;
            }
        }
        $this->view->assign('occupiedRegionIds', array_values(array_map('intval', array_keys($occupiedRegionIds))));

        return $this->view->fetch();
    }

    /**
     * 删除（禁用）加盟商
     */
    public function del($ids = "")
    {
        if (!$this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $ids = $ids ?: $this->request->post("ids");
        $operator = $this->currentFranchise();
        $idArr = array_values(array_filter(array_map('intval', explode(',', $ids))));
        foreach ($idArr as $id) {
            if (!$this->checkManageScope($operator, $id)) {
                $this->error(__('You have no permission'));
            }
        }
        Db::startTrans();
        try {
            foreach ($idArr as $id) {
                $fr = Db::name('franchise')->where('id', $id)->find();
                if (!$fr) {
                    continue;
                }
                // 需要禁用的加盟商：自身及其全部下级（二级），保证删除后下级不再可用
                $subtreeIds = FranchiseService::getSubtreeFranchiseIds($id);
                $subtreeIds = array_values(array_unique(array_map('intval', $subtreeIds)));
                Db::name('franchise')->where('id', 'in', $subtreeIds)->update([
                    'status'     => 'disabled',
                    'updatetime' => time(),
                ]);
                // 禁用这些加盟商的主账号，以及各自角色组下的账号（线路/调度/财务/下级加盟商）
                $adminIds = [];
                foreach ($subtreeIds as $sid) {
                    $sfr = Db::name('franchise')->where('id', $sid)->find();
                    if (!$sfr) {
                        continue;
                    }
                    if ((int)$sfr['admin_id'] > 0) {
                        $adminIds[] = (int)$sfr['admin_id'];
                    }
                    $gids = FranchiseService::getAuthGroupIdsInSubtree((int)$sfr['group_id']);
                    if ($gids) {
                        $uids = Db::name('auth_group_access')->where('group_id', 'in', $gids)->column('uid');
                        foreach ($uids as $uid) {
                            $adminIds[] = (int)$uid;
                        }
                    }
                }
                $adminIds = array_values(array_unique(array_filter($adminIds)));
                if ($adminIds) {
                    // 删除这些管理员账号及角色关联（管理员列表中不再出现）
                    Db::name('auth_group_access')->where('uid', 'in', $adminIds)->delete();
                    Db::name('admin')->where('id', 'in', $adminIds)->delete();
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('删除失败：' . $e->getMessage());
        }
        $this->success();
    }

    /**
     * 钱包流水
     */
    public function wallet()
    {
        $operator = $this->currentFranchise();
        $franchiseId = (int)$this->request->request('franchise_id');
        if ($franchiseId <= 0) {
            // 未指定则默认看当前操作者自己的钱包
            if ($operator) {
                $franchiseId = (int)$operator['id'];
            } else {
                $this->error('请选择加盟商');
            }
        } elseif (!$this->checkManageScope($operator, $franchiseId)) {
            $this->error(__('You have no permission'));
        }
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            [$where, $sort, $order, $offset, $limit] = $this->buildparams();
            $list = Db::name('franchise_wallet_log')
                ->alias('l')
                ->join('franchise f', 'f.id = l.franchise_id', 'LEFT')
                ->field('l.*, f.name AS franchise_name')
                ->where('l.franchise_id', $franchiseId)
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);
            $franchise = Db::name('franchise')->where('id', $franchiseId)->find();
            $rows = $list->items();
            $relatedTypeMap = [
                'order'           => '订单完成扣费',
                'member'          => '修改会员扣费',
                'member_recharge' => '小程序开通会员入账',
                'add_franchisee'  => '新增二级加盟商扣费',
                'monthly_fee'     => '月度加盟费',
                'adjust'          => '钱包调整',
            ];
            foreach ($rows as &$v) {
                $v['type_text'] = $v['type'] === 'income' ? '收入' : '支出';
                $v['createtime_text'] = $v['createtime'] > 0 ? date('Y-m-d H:i:s', $v['createtime']) : '';
                $v['balance_before'] = number_format((float)$v['balance_before'], 2, '.', '');
                $v['balance_after'] = number_format((float)$v['balance_after'], 2, '.', '');
                $v['amount'] = number_format((float)$v['amount'], 2, '.', '');
                $v['related_type'] = $relatedTypeMap[(string)$v['related_type']] ?? (string)$v['related_type'];
            }
            unset($v);
            $this->view->assign('franchise', $franchise);

            return json(['total' => $list->total(), 'rows' => $rows]);
        }
        $franchise = Db::name('franchise')->where('id', $franchiseId)->find();
        $this->view->assign('franchise', $franchise);
        $this->view->assign('franchiseId', $franchiseId);

        return $this->view->fetch();
    }

    /**
     * 抽佣明细（仅统计订单完成的抽佣记录，按当前管理者范围隔离）
     */
    public function commdetail()
    {
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            [$where, $sort, $order, $offset, $limit] = $this->buildparams();
            $operator = $this->currentFranchise();
            $visible = $this->visibleFranchiseIds($operator);
            if ($visible === []) {
                return json(['total' => 0, 'rows' => []]);
            }

            $query = Db::name('franchise_wallet_log')
                ->alias('l')
                ->join('franchise f', 'f.id = l.franchise_id', 'LEFT')
                ->field('l.*, f.name AS franchise_name')
                ->where('l.related_type', 'order')
                ->where('l.type', 'expense');
            if ($visible !== null) {
                $query->where('l.franchise_id', 'in', $visible);
            }
            $start = (string)$this->request->get('startdate');
            $end = (string)$this->request->get('enddate');
            if ($start !== '') {
                $query->where('l.createtime', '>=', strtotime($start . ' 00:00:00'));
            }
            if ($end !== '') {
                $query->where('l.createtime', '<=', strtotime($end . ' 23:59:59'));
            }
            $list = $query
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);
            $rows = $list->items();
            $orderIds = [];
            foreach ($rows as $r) {
                $rid = (int)($r['related_id'] ?? 0);
                if ($rid > 0) {
                    $orderIds[$rid] = true;
                }
            }
            $orderNumberMap = $orderIds ? (Db::name('order')
                ->where('id', 'in', array_keys($orderIds))
                ->column('orderid', 'id')) : [];
            foreach ($rows as &$v) {
                $v['franchise_name'] = $v['franchise_name'] ?: ('#' . $v['franchise_id']);
                $v['order_number'] = (string)($orderNumberMap[(int)($v['related_id'] ?? 0)] ?? '');
                $v['createtime_text'] = ((int)($v['createtime'] ?? 0)) > 0 ? date('Y-m-d H:i:s', (int)$v['createtime']) : '';
                $v['amount'] = number_format((float)$v['amount'], 2, '.', '');
                $v['balance_before'] = number_format((float)$v['balance_before'], 2, '.', '');
                $v['balance_after'] = number_format((float)$v['balance_after'], 2, '.', '');
            }
            unset($v);

            return json(['total' => $list->total(), 'rows' => $rows]);
        }

        return $this->view->fetch();
    }

    /**
     * 调整钱包余额（总部调一级；一级调二级）
     */
    public function adjustwallet()
    {
        $operator = $this->currentFranchise();
        $franchiseId = (int)$this->request->request('franchise_id', (int)$this->request->post('franchise_id'));
        $target = $franchiseId > 0 ? Db::name('franchise')->where('id', $franchiseId)->find() : null;
        if (!$target) {
            $this->error('请选择要调整的加盟商');
        }
        if (!$this->checkManageScope($operator, $franchiseId)) {
            $this->error(__('You have no permission'));
        }
        // 可调整关系：总部=任意；一级=只能调自己名下的二级
        if ($operator && (int)$operator['level'] === FranchiseService::LEVEL_1) {
            if ((int)$target['parent_id'] !== (int)$operator['id']) {
                $this->error('一级加盟商只能调整自己名下的二级加盟商钱包');
            }
        }
        if ($this->request->isPost()) {
            $this->token();
            $amount = (float)$this->request->post('amount');
            $type = $this->request->post('type'); // income/adjust
            $remark = trim((string)$this->request->post('remark'));
            if ($remark === '') {
                $remark = $type === 'income' ? '线下收款，线上充值' : '线下退款，线上扣减';
            }
            $delta = $type === 'income' ? $amount : -$amount;
            $res = FranchiseService::adjustWallet($franchiseId, $delta, $remark, (int)$this->auth->id);
            if ($res['success']) {
                $this->success($res['msg']);
            }
            $this->error($res['msg']);
        }
        $this->view->assign('franchise', $target);

        return $this->view->fetch();
    }

    /**
     * 会员列表（仅当前管理者范围内已绑定的会员）
     */
    public function member()
    {
        $operator = $this->currentFranchise();
        $franchiseId = (int)$this->request->request('franchise_id');
        if ($franchiseId <= 0 && $operator) {
            $franchiseId = (int)$operator['id'];
        }
        if ($franchiseId > 0 && !$this->checkManageScope($operator, $franchiseId)) {
            $this->error(__('You have no permission'));
        }
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            [$where, $sort, $order, $offset, $limit] = $this->buildparams();
            $scopeIds = FranchiseService::getSubtreeFranchiseIds($franchiseId > 0 ? $franchiseId : 0);
            if ($scopeIds === []) {
                return json(['total' => 0, 'rows' => []]);
            }
            // 若未指定 franchise_id 且操作者是加盟商，则按子树；若是总部未指定则看全部已绑定
            $query = Db::name('franchise_member')
                ->alias('fm')
                ->join('user u', 'u.id = fm.user_id', 'LEFT')
                ->field('fm.*, u.username, u.mobile, u.nickname, u.identity, u.membertype, u.member_time, u.platform_commission')
                ->where('fm.franchise_id', 'in', $scopeIds)
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);
            $rows = $query->items();
            foreach ($rows as &$v) {
                $v['franchise_name'] = Db::name('franchise')->where('id', $v['franchise_id'])->value('name');
                $v['membertype_text'] = $this->membertypeText($v['membertype']);
                $v['member_time'] = $v['member_time'] ? date('Y-m-d', is_numeric($v['member_time']) ? (int)$v['member_time'] : strtotime($v['member_time'])) : '';
            }
            unset($v);

            return json(['total' => $query->total(), 'rows' => $rows]);
        }
        $this->view->assign('franchiseId', $franchiseId);

        return $this->view->fetch();
    }

    /**
     * 修改会员身份/职位/到期/名称（加盟商操作时扣费）
     */
    public function memberedit()
    {
        $userId = (int)$this->request->request('user_id', (int)$this->request->post('user_id'));
        $row = $userId > 0 ? Db::name('user')->where('id', $userId)->find() : null;
        if (!$row) {
            $this->error('会员不存在');
        }
        $row['member_time_text'] = $row['member_time']
            ? date('Y-m-d', is_numeric($row['member_time']) ? (int)$row['member_time'] : strtotime($row['member_time']))
            : '';
        $operator = $this->currentFranchise();
        // 加盟商改身份/到期按整月扣费，前端提示需要月费单价；总部直接改不扣费，不做提示
        $this->assignconfig('memberMonthFeeEnabled', !$this->auth->isSuperAdmin());
        $this->assignconfig('memberMonthFee', (float)FranchiseService::getGlobalConfig()['member_month_fee']);
        if ($operator) {
            $bound = Db::name('franchise_member')
                ->where('user_id', $userId)
                ->where('franchise_id', (int)$operator['id'])
                ->find();
            $row['platform_commission'] = $row['platform_commission'] ?? '';
            $row['formal_employee_quota'] = (int)Db::name('franchise')->where('id', (int)$operator['id'])->value('formal_employee_quota');
            $row['formal_employee_count'] = FranchiseService::getFormalEmployeeCount((int)$operator['id']);
        } else {
            $row['platform_commission'] = $row['platform_commission'] ?? '';
            $row['formal_employee_quota'] = 0;
            $row['formal_employee_count'] = 0;
        }
        if ($this->request->isPost()) {
            $this->token();
            $params = [
                'username'    => $this->request->post('username'),
                'membertype'  => $this->request->post('membertype'),
                'member_time' => $this->request->post('member_time'),
                'platform_commission' => $this->request->post('platform_commission'),
            ];
            if ($this->auth->isSuperAdmin()) {
                // 总部直接改，不扣费
                $update = array_filter($params, static function ($v) {
                    return $v !== null && $v !== '';
                });
                if (isset($update['member_time']) && !is_numeric($update['member_time'])) {
                    $update['member_time'] = strtotime($update['member_time']);
                }
                if ($update !== []) {
                    Db::name('user')->where('id', $userId)->update($update);
                }
                $this->success('修改成功');
            }
            if (!$operator) {
                $this->error('您不是加盟商，无权扣费改会员');
            }
            if (!FranchiseService::canManageMember((int)$operator['id'], $userId)) {
                $this->error('该会员未绑定到您的名下，无法修改');
            }
            $res = FranchiseService::changeMemberIdentityTime((int)$operator['id'], $userId, $params);
            if ($res['success']) {
                $this->success($res['msg']);
            }
            $this->error($res['msg']);
        }
        $this->view->assign('row', $row);

        return $this->view->fetch();
    }

    /**
     * 按手机号搜索会员（用于加盟商搜到后绑定）
     */
    public function memberselect()
    {
        if ($this->request->isAjax()) {
            $mobile = trim((string)$this->request->request('mobile'));
            if ($mobile === '') {
                return json(['total' => 0, 'rows' => []]);
            }
            $user = FranchiseService::findUserByMobile($mobile);
            if (!$user) {
                return json(['total' => 0, 'rows' => []]);
            }
            $operator = $this->currentFranchise();
            $bound = $operator ? FranchiseService::canManageMember((int)$operator['id'], (int)$user['id']) : false;
            $row = [
                'id'            => $user['id'],
                'username'      => $user['username'],
                'nickname'      => $user['nickname'],
                'mobile'        => $user['mobile'],
                'identity'      => $user['identity'],
                'membertype'    => $user['membertype'],
                'membertype_text' => $this->membertypeText($user['membertype']),
                'member_time'   => $user['member_time'] ? date('Y-m-d', is_numeric($user['member_time']) ? (int)$user['member_time'] : strtotime($user['member_time'])) : '',
                'bound'         => $bound,
            ];

            return json(['total' => 1, 'rows' => [$row]]);
        }

        return $this->view->fetch();
    }

    /**
     * 加盟商按手机号搜到会员后，绑定到自己名下
     */
    public function bind()
    {
        if (!$this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $userId = (int)$this->request->post('user_id');
        if ($userId <= 0) {
            $this->error('会员参数错误');
        }
        $operator = $this->currentFranchise();
        if (!$operator) {
            $this->error('您不是加盟商，无法绑定会员');
        }
        $res = FranchiseService::bindMember((int)$operator['id'], $userId, (int)$this->auth->id);
        if ($res['success']) {
            $this->success($res['msg']);
        }
        $this->error($res['msg']);
    }

    /**
     * 员工列表（加盟商的线路/调度/财务账号）
     */
    public function staff()
    {
        $operator = $this->currentFranchise();
        $franchiseId = (int)$this->request->request('franchise_id');
        if ($franchiseId <= 0 && $operator) {
            $franchiseId = (int)$operator['id'];
        }
        if ($franchiseId > 0 && !$this->checkManageScope($operator, $franchiseId)) {
            $this->error(__('You have no permission'));
        }
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            [$where, $sort, $order, $offset, $limit] = $this->buildparams();
            $groups = FranchiseService::ensureFranchiseChildGroups($franchiseId);
            $groupIds = array_values(array_unique(array_map('intval', $groups)));
            if ($groupIds === []) {
                return json(['total' => 0, 'rows' => []]);
            }
            $roleMap = array_flip($groups); // group_id => lin/diao/cai
            $query = Db::name('auth_group_access')
                ->alias('aga')
                ->join('admin a', 'a.id = aga.uid', 'LEFT')
                ->field('aga.uid AS admin_id, a.username, a.nickname, a.mobile, a.status, aga.group_id')
                ->where('aga.group_id', 'in', $groupIds)
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);
            $rows = $query->items();
            foreach ($rows as &$v) {
                $roleKey = $roleMap[(int)$v['group_id']] ?? '';
                $v['role_text'] = $roleKey === 'lin' ? '线路' : ($roleKey === 'diao' ? '调度' : '财务');
                $v['role_key'] = $roleKey;
                $v['status_text'] = $v['status'] === 'normal' ? '正常' : ($v['status'] === 'hidden' ? '隐藏' : '停用');
            }
            unset($v);

            return json(['total' => $query->total(), 'rows' => $rows]);
        }
        $this->view->assign('franchiseId', $franchiseId);

        return $this->view->fetch();
    }

    /**
     * 添加员工（线路/调度/财务）
     */
    public function staffadd()
    {
        $operator = $this->currentFranchise();
        $franchiseId = (int)$this->request->request('franchise_id', (int)$this->request->post('franchise_id'));
        if ($franchiseId <= 0 && $operator) {
            $franchiseId = (int)$operator['id'];
        }
        if ($franchiseId > 0 && !$this->checkManageScope($operator, $franchiseId)) {
            $this->error(__('You have no permission'));
        }
        if ($this->request->isPost()) {
            $this->token();
            $role = trim((string)$this->request->post('role'));
            $username = trim((string)$this->request->post('username'));
            $password = (string)$this->request->post('password');
            $nickname = trim((string)$this->request->post('nickname'));
            $mobile = trim((string)$this->request->post('mobile'));
            if ($role === '' || $username === '' || $password === '') {
                $this->error('请填写完整信息');
            }
            // 线路/调度必须填写手机号
            if (in_array($role, ['lin', 'diao'], true) && $mobile === '') {
                $this->error('线路/调度员工必须填写手机号');
            }
            // 手机号格式校验（已填写时）
            if ($mobile !== '' && !preg_match('/^1[3-9]\d{9}$/', $mobile)) {
                $this->error('手机号格式不正确');
            }
            $res = FranchiseService::createFranchiseStaff($franchiseId, $role, $username, $password, $nickname, $mobile, $this->auth);
            if ($res['success']) {
                $this->success($res['msg']);
            }
            $this->error($res['msg']);
        }
        $this->view->assign('franchiseId', $franchiseId);

        return $this->view->fetch();
    }

    /**
     * 员工状态（停用/启用）
     */
    public function staffstatus()
    {
        if (!$this->request->isPost()) {
            $this->error(__('Invalid parameters'));
        }
        $adminId = (int)$this->request->post('admin_id');
        $status = trim((string)$this->request->post('status'));
        if (!$this->checkStaffInScope($adminId)) {
            $this->error(__('You have no permission'));
        }
        $res = FranchiseService::setFranchiseStaffStatus($adminId, $status);
        if ($res['success']) {
            $this->success($res['msg']);
        }
        $this->error($res['msg']);
    }

    /**
     * 删除员工
     */
    public function staffdel()
    {
        if (!$this->request->isPost()) {
            $this->error(__('Invalid parameters'));
        }
        $adminId = (int)$this->request->post('admin_id');
        if (!$this->checkStaffInScope($adminId)) {
            $this->error(__('You have no permission'));
        }
        $res = FranchiseService::deleteFranchiseStaff($adminId);
        if ($res['success']) {
            $this->success($res['msg']);
        }
        $this->error($res['msg']);
    }

    /**
     * 校验目标员工是否在当前操作者可管理的加盟商子树内
     */
    protected function checkStaffInScope(int $adminId): bool
    {
        if ($this->auth->isSuperAdmin()) {
            return true;
        }
        $fr = FranchiseService::resolveFranchiseForAdmin((int)$this->auth->id);
        if (!$fr) {
            return false;
        }
        $scope = FranchiseService::getFranchiseSubtreeAdminIds((int)$fr['id']);

        return in_array($adminId, $scope, true);
    }

    /**
     * 加盟商全局设置（改会员 150 / 新增二级 500，总部统一设置）
     */
    public function config()
    {
        if (!$this->auth->isSuperAdmin()) {
            $this->error('仅总部可设置加盟商全局费用');
        }
        $cfg = FranchiseService::getGlobalConfig();
        if ($this->request->isPost()) {
            $this->token();
            $memberFee = round((float)$this->request->post('member_month_fee'), 2);
            $addFee = round((float)$this->request->post('add_level2_fee'), 2);
            $monthlyFee = round((float)$this->request->post('monthly_fee', 1000), 2);
            if ($memberFee <= 0 || $addFee <= 0) {
                $this->error('金额必须大于0');
            }
            $tplId = (int)$this->request->post('template_franchise_id');
            FranchiseService::saveGlobalConfig($memberFee, $addFee, $tplId, $monthlyFee);
            $this->success('已保存');
        }
        $this->view->assign('cfg', $cfg);
        $level1List = Db::name('franchise')->where('level', 1)->where('status', 'normal')->select();
        $this->view->assign('level1List', $level1List);

        return $this->view->fetch();
    }

    protected function membertypeText($value)
    {
        $list = ['1' => '普通用户', '2' => '兼职员工', '3' => '正式员工', '4' => '会展员工'];

        return $list[(string)$value] ?? '普通用户';
    }

    /**
     * 批量汇总加盟商“订单抽佣”累计金额（franchise_id => 金额）
     * 口径：franchise_wallet_log 中 type=expense 且 related_type=order 的扣费合计
     *
     * @param int[] $franchiseIds
     * @return array<int,float>
     */
    protected function sumOrderCommissionMap(array $franchiseIds): array
    {
        $franchiseIds = array_values(array_unique(array_filter(array_map('intval', $franchiseIds))));
        if (!$franchiseIds) {
            return [];
        }
        $rows = Db::name('franchise_wallet_log')
            ->where('franchise_id', 'in', $franchiseIds)
            ->where('related_type', 'order')
            ->where('type', 'expense')
            ->field('franchise_id, SUM(amount) as total')
            ->group('franchise_id')
            ->select();
        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r['franchise_id']] = (float)$r['total'];
        }

        return $map;
    }
}

<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\common\library\BookkeepingAuditLog;
use app\admin\library\FranchiseService;
use think\Db;

/**
 * 记账修改/删除审核（子后台提交，总后台确认）
 *
 * @icon fa fa-check-square-o
 */
class Bookkeepingaudit extends Backend
{
    /**
     * BookkeepingAudit模型对象
     * @var \app\admin\model\BookkeepingAudit
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\BookkeepingAudit;
    }

    /**
     * 列表
     */
    public function index()
    {
        // 设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            // 如果发送的来源是 Selectpage，则转发到 Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $query = $this->model->where($where);
            // 子后台只能查看自己提交的审核记录，总后台可查看全部；加盟商可看旗下管理员提交的
            if (!$this->auth->isSuperAdmin()) {
                $scope = FranchiseService::getCurrentAdminFranchiseScopeAdminIds((int)$this->auth->id);
                if ($scope !== null) {
                    if ($scope === []) {
                        $query->whereRaw('1 = 0');
                    } else {
                        $query->whereRaw('admin_id IN (' . implode(',', array_map('intval', $scope)) . ')');
                    }
                } else {
                    $query->where('admin_id', (int)$this->auth->id);
                }
            }
            $list = $query->order($sort, $order)->paginate($limit);

            $items = [];
            foreach ($list as $row) {
                // 当前记账记录的实际数据（已存在则展示，删除申请且已通过后可能为空）
                $bookkeeping = Db::name('bookkeeping')->where('id', (int)$row['bookkeeping_id'])->find();
                $items[] = [
                    'id'               => $row['id'],
                    'bookkeeping_id'   => $row['bookkeeping_id'],
                    'type'             => $row['type'],
                    'type_text'        => $row['type_text'],
                    'price'            => $bookkeeping ? $bookkeeping['price'] : '',
                    'break'            => $bookkeeping ? $bookkeeping['break'] : '',
                    'admin_name'       => $row['admin_name'],
                    'audit_status'     => $row['audit_status'],
                    'audit_status_text'=> $row['audit_status_text'],
                    'createtime'       => $row['createtime'],
                    'audit_time'       => $row['audit_time'],
                    'audit_remark'     => $row['audit_remark'],
                ];
            }
            $result = [
                "total" => $list->total(),
                "rows"  => $items,
            ];
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 待审核提醒：供总后台轮询，有新提交的待审核记录时返回 has_new（与前端 last_id 比对）
     * @return \think\response\Json
     */
    public function pending_audit_alert()
    {
        $lastId = (int)$this->request->get('last_id', 0);
        $hasNew = 0;
        $latestId = 0;
        $message = '';

        $query = Db::name('bookkeeping_audit')->where('audit_status', 0);
        if (!$this->auth->isSuperAdmin()) {
            $scope = FranchiseService::getCurrentAdminFranchiseScopeAdminIds((int)$this->auth->id);
            if ($scope !== null) {
                if ($scope === []) {
                    $query->whereRaw('1 = 0');
                } else {
                    $query->whereRaw('admin_id IN (' . implode(',', array_map('intval', $scope)) . ')');
                }
            } else {
                $query->where('admin_id', (int)$this->auth->id);
            }
        }
        $row = $query->order('id', 'desc')->field('id')->find();

        if ($row && isset($row['id'])) {
            $latestId = (int)$row['id'];
            if ($latestId > $lastId) {
                $hasNew = 1;
                $message = $this->auth->isSuperAdmin() ? '有新的记账修改/删除待审核，请及时处理' : '有新的审核结果，请查看';
            }
        }

        return json(['code' => 1, 'data' => ['has_new' => $hasNew, 'latest_id' => $latestId, 'message' => $message]]);
    }

    /**
     * 审核（通过/拒绝），仅总后台可操作
     */
    public function edit($ids = null)
    {
        if (!$this->auth->isSuperAdmin()) {
            $this->error('仅总后台可进行审核操作');
        }
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }

        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if (!$params) {
                $this->error(__('Parameter %s can not be empty', 'row'));
            }

            // 只允许修改审核相关字段
            $allowFields = ['audit_status', 'audit_remark'];
            $data        = [];
            foreach ($allowFields as $field) {
                if (isset($params[$field])) {
                    $data[$field] = $params[$field];
                }
            }
            // 设置审核人和审核时间
            $data['audit_admin_id'] = (int)$this->auth->id;
            $data['audit_time']     = time();

            // 审核前状态 & 目标状态
            $oldStatus = (int)$row['audit_status'];
            $newStatus = isset($data['audit_status']) ? (int)$data['audit_status'] : $oldStatus;

            $row->allowField(true)->save($data);

            // 只有从非“已通过”变为“已通过”时，才真正把变更应用到业务表
            if ($oldStatus !== 1 && $newStatus === 1) {
                try {
                    $fresh = Db::name('bookkeeping_audit')->where('id', $row['id'])->find();
                    BookkeepingAuditLog::apply($fresh);
                } catch (\Exception $e) {
                    $this->error('审核通过，但应用变更到记账失败：' . $e->getMessage());
                }
            }

            $this->success();
        }

        // 解析 JSON，生成差异列表
        $oldData = json_decode($row['old_data'], true) ?: [];
        $newData = json_decode($row['new_data'], true) ?: [];
        $keys    = array_unique(array_merge(array_keys($oldData), array_keys($newData)));

        $fieldComments = $this->getFieldComments();
        $auditStatusMap = [
            '0' => '待审核',
            '1' => '已通过',
            '2' => '已拒绝',
        ];
        $typeMap = [
            'edit' => '修改',
            'del'  => '删除',
        ];

        $diffList = [];
        foreach ($keys as $key) {
            if ($key === 'id') {
                continue;
            }
            $old = isset($oldData[$key]) ? $oldData[$key] : '';
            $new = isset($newData[$key]) ? $newData[$key] : '';
            if ($old === $new) {
                continue;
            }
            if ($key === 'audit_status') {
                $old = isset($auditStatusMap[(string)$old]) ? $auditStatusMap[(string)$old] : $old;
                $new = isset($auditStatusMap[(string)$new]) ? $auditStatusMap[(string)$new] : $new;
            }
            if (is_array($old) || is_object($old)) {
                $old = json_encode($old, JSON_UNESCAPED_UNICODE);
            }
            if (is_array($new) || is_object($new)) {
                $new = json_encode($new, JSON_UNESCAPED_UNICODE);
            }
            $label = isset($fieldComments[$key]) && $fieldComments[$key] !== '' ? $fieldComments[$key] : $key;
            $diffList[] = [
                'field'    => $key,
                'label'    => $label,
                'oldValue' => $old,
                'newValue' => $new,
            ];
        }

        $this->view->assign("row", $row);
        $this->view->assign("typeText", isset($typeMap[$row['type']]) ? $typeMap[$row['type']] : $row['type']);
        $this->view->assign("diffList", $diffList);
        return $this->view->fetch();
    }

    /**
     * 获取 bookkeeping 表字段的备注，以字段名为 key
     */
    protected function getFieldComments()
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $comments = [
            'price' => '金额',
            'break' => '备注',
        ];
        try {
            $fullTable = config('database.prefix') . 'bookkeeping';
            $columns = Db::query("SHOW FULL COLUMNS FROM `{$fullTable}`");
            foreach ($columns as $col) {
                $field = isset($col['Field']) ? $col['Field'] : (isset($col['field']) ? $col['field'] : '');
                $comment = isset($col['Comment']) ? $col['Comment'] : (isset($col['comment']) ? $col['comment'] : '');
                if ($field) {
                    $comments[$field] = $comment;
                }
            }
        } catch (\Exception $e) {
            // 忽略查询失败，使用默认映射
        }
        $cache = $comments;
        return $comments;
    }
}

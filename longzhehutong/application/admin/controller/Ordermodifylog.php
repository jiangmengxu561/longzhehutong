<?php

namespace app\admin\controller;

use app\admin\library\OrderModifyApplier;
use app\common\controller\Backend;
use think\Db;

/**
 * 订单修改记录表（统一查看与审核）
 *
 * @icon fa fa-circle-o
 */
class Ordermodifylog extends Backend
{
    /**
     * Ordermodifylog模型对象
     * @var \app\admin\model\Ordermodifylog
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Ordermodifylog;
    }

    /**
     * 列表
     */
    public function index()
    {
        // 当前是否为关联查询
        $this->relationSearch = false;

        // 设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            // 如果发送的来源是 Selectpage，则转发到 Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);
//            print_r($list);die;
            $items = [];
            foreach ($list as $row) {
                // 只保留列表需要展示的字段，避免把大 JSON 直接返回
                $items[] = [
                    'id'               => $row['id'],
                    'orderid'          => $row['orderid'],
                    'title'            => $row['title'],
                    'modify_type'      => $row['modify_type'],
                    'admin_name'       => $row['admin_name'],
                    'logistics_status' => $row['logistics_status'],
                    'audit_status'     => $row['audit_status'],
                    'createtime'       => $row['createtime'],
                    'audit_time'       => $row['audit_time'],
                    'audit_remark'       => $row['audit_remark'],
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

        // 仅统计「待审核」记录中最大的 id
        $row = Db::name('ordermodifylog')
            ->where('audit_status', 0)
            ->order('id', 'desc')
            ->field('id')
            ->find();

        if ($row && isset($row['id'])) {
            $latestId = (int)$row['id'];
            if ($latestId > $lastId) {
                $hasNew = 1;
                $message = '有新的订单修改待审核，请及时处理';
            }
        }

        return json(['code' => 1, 'data' => ['has_new' => $hasNew, 'latest_id' => $latestId, 'message' => $message]]);
    }

    /**
     * 编辑 / 审核
     */
    public function edit($ids = null)
    {
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
            $data['audit_admin_id'] = $this->auth ? $this->auth->id : 0;
            $data['audit_time']     = time();

            // 审核前状态 & 目标状态
            $oldStatus = (int)$row['audit_status'];
            $newStatus = isset($data['audit_status']) ? (int)$data['audit_status'] : $oldStatus;

            $row->allowField(true)->save($data);

            // 只有从非“已通过”变为“已通过”时，才真正把修改应用到业务表
            if ($oldStatus !== 1 && $newStatus === 1) {
                try {
                    // 审核通过后从数据库重新读取，确保 new_data 可靠（避免模型 save 后状态不一致）
                    $fresh = Db::name('ordermodifylog')->where('id', $row['id'])->find();
                    $newData = isset($fresh['new_data']) ? (is_array($fresh['new_data']) ? $fresh['new_data'] : (json_decode($fresh['new_data'], true) ?: [])) : [];
                    OrderModifyApplier::apply(
                        $row['modify_type'],
                        (int)$row['order_id'],
                        (int)$row['admin_order_id'],
                        $row['orderid'],
                        $newData
                    );
                } catch (\Exception $e) {
                    $this->error('审核通过，但应用修改到订单失败：' . $e->getMessage());
                }
            }

            $this->success();
        }

        // 解析 JSON，生成差异列表（字段名尽量用数据库备注）
        $oldData = json_decode($row['old_data'], true) ?: [];
        $newData = json_decode($row['new_data'], true) ?: [];
        $keys    = array_unique(array_merge(array_keys($oldData), array_keys($newData)));

        $fieldComments = $this->getFieldComments();
        // 物流状态、审核状态映射为中文
        $orderModel          = new \app\admin\model\Order();
        $logisticsStatusMap  = $orderModel->getLogisticsStatusList();
        $auditStatusMap      = [
            '0' => '待审核',
            '1' => '已通过',
            '2' => '已拒绝',
        ];
        // 图片类字段：变更明细中展示为图片，不展示为路径文字
        $imageFields = [
            'pickup_load_image', 'pickup_unload_image', 'line_load_image', 'line_unload_image',
            'delivery_load_image', 'delivery_unload_image', 'receipt_image', 'monad_images',
        ];

        $diffList      = [];
        foreach ($keys as $key) {
            // 不在页面展示打包的 charge 字段，只看具体金额/数量等字段
            if ($key === 'charge') {
                continue;
            }
            $old = isset($oldData[$key]) ? $oldData[$key] : '';
            $new = isset($newData[$key]) ? $newData[$key] : '';
            if ($old === $new) {
                continue;
            }

            // 将枚举值转换为中文说明
            if ($key === 'logistics_status') {
                $old = isset($logisticsStatusMap[(string)$old]) ? $logisticsStatusMap[(string)$old] : $old;
                $new = isset($logisticsStatusMap[(string)$new]) ? $logisticsStatusMap[(string)$new] : $new;
            } elseif ($key === 'audit_status') {
                $old = isset($auditStatusMap[(string)$old]) ? $auditStatusMap[(string)$old] : $old;
                $new = isset($auditStatusMap[(string)$new]) ? $auditStatusMap[(string)$new] : $new;
            }

            $isImageField = in_array($key, $imageFields, true);
            $oldValueImages = [];
            $newValueImages = [];
            if ($isImageField) {
                $oldValueImages = $this->normalizeImageUrls($old);
                $newValueImages = $this->normalizeImageUrls($new);
            }

            if (!$isImageField) {
                // 填写订单额外价格：提交的是“额外金额”，修改后应展示为 修改前+额外金额（累加）
                if ($row['modify_type'] === 'order_other_price' && $key === 'pay_price') {
                    $oldNum = is_numeric($old) ? (float)$old : 0;
                    $newNum = is_numeric($new) ? (float)$new : 0;
                    $new = number_format(round($oldNum + $newNum, 2), 2, '.', '');
                }
                // 订单其他价格：提交的是“本次金额”，总成本修改后应展示为 修改前+本次金额（累加）；old_data 里用 pay_price 键存的是当时总成本
                if ($row['modify_type'] === 'cost_extra_price' && $key === 'pay_price') {
                    $oldNum = is_numeric($old) ? (float)$old : 0;
                    $extraNum = isset($newData['price']) && is_numeric($newData['price']) ? (float)$newData['price'] : 0;
                    $new = number_format(round($oldNum + $extraNum, 2), 2, '.', '');
                }
                if (is_array($old) || is_object($old)) {
                    $old = json_encode($old, JSON_UNESCAPED_UNICODE);
                }
                if (is_array($new) || is_object($new)) {
                    $new = json_encode($new, JSON_UNESCAPED_UNICODE);
                }
            }

            $label = isset($fieldComments[$key]) && $fieldComments[$key] !== '' ? $fieldComments[$key] : $key;
            if ($row['modify_type'] === 'cost_extra_price' && $key === 'pay_price') {
                $label = '总成本';
            }
            if ($row['modify_type'] === 'logistics_extra_price' && $key === 'logistics_driver_cost') {
                $label = '专线价格';
            }
            $diffList[] = [
                'field' => $key,
                'label' => $label,
                'oldValue' => $isImageField ? '' : $old,
                'newValue' => $isImageField ? '' : $new,
                'oldValueImages' => $oldValueImages,
                'newValueImages' => $newValueImages,
            ];
        }

        // 当时订单物流状态（整条记录的状态），转成中文显示
        $logisticsStatusText = '';
        if (isset($row['logistics_status'])) {
            $value = (string)$row['logistics_status'];
            $logisticsStatusText = isset($logisticsStatusMap[$value]) ? $logisticsStatusMap[$value] : $row['logistics_status'];
        }

        $this->view->assign("row", $row);
        $this->view->assign("diffList", $diffList);
        $this->view->assign("logisticsStatusText", $logisticsStatusText);
        return $this->view->fetch();
    }

    /**
     * 获取相关表字段的备注（评论），以字段名为 key
     * 订单修改记录中出现的“虚拟”字段（如来自 dricerorder 的税点）在此映射中文名
     */
    protected function getFieldComments()
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        // 修改记录中常见但不在 order/admin_order 表中的字段，统一给中文显示名
        $comments = [
            'pickup_tax_point'   => '取货司机税点',
            'delivery_tax_point' => '送货司机税点',
            'pickup_load_image' => '取货司机装货图片',
            'pickup_unload_image' => '取货司机卸货图片',
            'line_load_image' => '专线装货图片',
            'line_unload_image' => '专线卸货图片',
            'delivery_load_image' => '送货司机装货图片',
            'delivery_unload_image' => '送货司机卸货图片',
            'receipt_image' => '回单图片',
            'monad_images' => '物流单子图片',
            'price' => '金额',
            'remarks' => '备注',
            'logistics_driver_cost' => '专线价格',
        ];
        $tables = [
            'admin_order',
            'order',
        ];

        foreach ($tables as $table) {
            try {
                $fullTable = config('database.prefix') . $table;
            } catch (\Exception $e) {
                $fullTable = $table;
            }

            try {
                $columns = Db::query("SHOW FULL COLUMNS FROM `{$fullTable}`");
            } catch (\Exception $e) {
                continue;
            }

            foreach ($columns as $col) {
                $field = isset($col['Field']) ? $col['Field'] : (isset($col['field']) ? $col['field'] : '');
                $comment = isset($col['Comment']) ? $col['Comment'] : (isset($col['comment']) ? $col['comment'] : '');
                if ($field) {
                    $comments[$field] = $comment;
                }
            }
        }

        $cache = $comments;
        return $comments;
    }

    /**
     * 将装货/卸货等图片字段值规范为图片 URL 数组，便于前端展示
     * @param mixed $value 可能为字符串（单图路径或 JSON）、数组
     * @return string[]
     */
    protected function normalizeImageUrls($value)
    {
        if ($value === '' || $value === null) {
            return [];
        }
        if (is_array($value)) {
            $out = [];
            foreach ($value as $v) {
                if (is_string($v) && $v !== '') {
                    $out[] = $v;
                }
            }
            return $out;
        }
        if (!is_string($value)) {
            return [];
        }
        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            $out = [];
            foreach ($decoded as $v) {
                if (is_string($v) && $v !== '') {
                    $out[] = $v;
                }
            }
            return $out;
        }
        // 单路径或逗号分隔多路径
        $value = trim($value);
        if ($value === '') {
            return [];
        }
        $arr = preg_split('/\s*,\s*/', $value);
        return array_values(array_filter(array_map('trim', $arr)));
    }

    // 应用修改逻辑已移至 \app\admin\library\OrderModifyApplier，审核通过时直接调用 OrderModifyApplier::apply()
}

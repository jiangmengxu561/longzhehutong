<?php

namespace app\admin\controller;

use app\admin\library\Auth;
use app\admin\library\FranchiseService;
use app\admin\library\OrderModifyApplier;
use app\admin\library\traits\OrderRejectBlockTrait;
use app\common\controller\Backend;
use app\common\library\OrderModifyLog;
use app\common\model\User;
use app\common\library\Commission;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use think\Cache;
use think\Db;
use think\db\exception\BindParamException;
use think\exception\DbException;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\response\Json;

/**
 * 物流
 *
 * @icon fa fa-circle-o
 */
class Logistics extends Backend
{
    use OrderRejectBlockTrait;

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    /**
     * 本次导入过程中的经纬度/距离内存缓存，避免重复调用外部接口
     * @var array
     */
    protected $geocodeCache = [];
    protected $distanceCache = [];
    const GEO_CACHE_TTL = 604800; // 7天
    const DISTANCE_CACHE_TTL = 604800;

    /**
     * Logistics模型对象
     * @var \app\admin\model\Logistics
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Logistics;
        $this->view->assign("statusList", $this->model->statusList());
        $this->view->assign("logisticsStatusList", $this->model->logisticsStatusList());
        $this->view->assign("levelList", $this->model->levelList());
    }



    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    /**
     * 查看
     *
     * @return string|Json
     * @throws \think\Exception
     * @throws DbException
     */
    public function index()
    {
        $id = $this->auth->id;
        if (empty($id)){
            $this->error('登录信息已过期,请刷新浏览器');
        }
        // 加盟商 / 区域子后台：只看自己负责区域（发货城市）内的物流专线
        $visibleScope = $this->resolveLogisticsVisibleCityScope((int)$id);
        $cityFilter = $visibleScope['cities'];
        $franchiseNoRegion = $visibleScope['no_region'];

        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if (false === $this->request->isAjax()) {
            return $this->view->fetch();
        }
        //如果发送的来源是 Selectpage，则转发到 Selectpage
        if ($this->request->request('keyField')) {
            return $this->selectpage();
        }
        [$where, $sort, $order, $offset, $limit] = $this->buildparams();

        // 构建查询
        $query = $this->model->where($where);
        $status = $this->request->param('status', '');
        if ($status !== '' && in_array((string)$status, ['1', '2'], true)) {
            $query->where('status', (int)$status);
        }

        // 如果存在城市过滤条件，添加到查询中（按发货城市）
        if ($cityFilter !== null && !empty($cityFilter)) {
            if (count($cityFilter) > 1) {
                $query->where(function ($q) use ($cityFilter) {
                    foreach ($cityFilter as $cityName) {
                        $q->whereOr('origincity', 'like', '%' . $cityName . '%');
                    }
                });
            } else {
                $query->where('origincity', 'like', '%' . $cityFilter[0] . '%');
            }
        }
        if (!empty($franchiseNoRegion)) {
            $query->where('id', -1);
        }

        // 总后台（超级管理员）列表：已申请删除的优先显示
        // 说明：delete_log.status = 1 视为“已申请删除”，
        // 使用 EXISTS 子查询做优先排序，只在总后台生效，其它子后台仍按原排序规则
        if ($id == 1) {
            $table = $this->model->getQuery()->getTable(); // 通常为 logistics
            $deleteFirstExpr = "EXISTS(SELECT 1 FROM fa_delete_log dl WHERE dl.log_id = {$table}.id AND dl.status = 1) DESC";
            $query->orderRaw($deleteFirstExpr . ', ' . $sort . ' ' . $order);
        } else {
            $query->order($sort, $order);
        }

        $list = $query->paginate($limit);

        // 追加删除申请状态（delete_log.status）
        $rows = $list->items();
        if ($rows) {
            // 收集当前页的专线ID
            $logIds = [];
            foreach ($rows as $row) {
                if (isset($row['id'])) {
                    $logIds[] = $row['id'];
                } elseif (is_array($row) && isset($row['id'])) {
                    $logIds[] = $row['id'];
                }
            }
            if ($logIds) {
                $logIds = array_unique($logIds);
                // 取每条专线最近一次删除申请的状态 + 申请人
                $deleteStatusMap = [];
                $deleteAdminMap = [];
                $deleteLogs = Db::name('delete_log')
                    ->where('log_id', 'in', $logIds)
                    ->order('create_time', 'desc')
                    ->select();
                foreach ($deleteLogs as $dl) {
                    $lid = (int)($dl['log_id'] ?? 0);
                    if (!isset($deleteStatusMap[$lid])) {
                        $deleteStatusMap[$lid] = (int)($dl['status'] ?? 0);
                        $deleteAdminMap[$lid] = (int)($dl['admin_id'] ?? 0);
                    }
                }

                foreach ($rows as $k => $row) {
                    $logId = is_array($row) ? ($row['id'] ?? null) : ($row->id ?? null);
                    $status = ($logId !== null && isset($deleteStatusMap[$logId]))
                        ? (int)$deleteStatusMap[$logId]
                        : null;
                    if (is_array($row)) {
                        $rows[$k]['delete_status'] = $status;
                    } else {
                        $rows[$k]['delete_status'] = $status;
                    }
                    // 操作人 / 所属加盟商展示
                    $get = static function ($row, $key) {
                        return is_array($row) ? ($row[$key] ?? '') : ($row->{$key} ?? '');
                    };
                    $opName = trim((string)$get($row, 'operator_name'));
                    $opMobile = trim((string)$get($row, 'operator_mobile'));
                    $frName = trim((string)$get($row, 'operator_franchise_name'));
                    $frLevel = (int)$get($row, 'operator_franchise_level');
                    $parentName = trim((string)$get($row, 'operator_parent_franchise_name'));
                    $rows[$k]['operator_display'] = $opName !== '' ? ($opName . ($opMobile !== '' ? ' / ' . $opMobile : '')) : '-';
                    if ($frLevel === 1) {
                        $rows[$k]['franchise_display'] = $frName . '（一级）';
                    } elseif ($frLevel === 2) {
                        $rows[$k]['franchise_display'] = $frName . '（二级' . ($parentName !== '' ? '，一级：' . $parentName : '') . '）';
                    } elseif ($frName !== '') {
                        $rows[$k]['franchise_display'] = $frName;
                    } else {
                        $rows[$k]['franchise_display'] = '总部';
                    }
                    // 删除申请人展示
                    if ($status !== null && $status === 1) {
                        $delAdminId = (int)($deleteAdminMap[$logId] ?? 0);
                        $delAdmin = $delAdminId > 0 ? Db::name('admin')->where('id', $delAdminId)->find() : null;
                        $delName = $delAdmin ? (string)($delAdmin['nickname'] ?? $delAdmin['username'] ?? '') : '';
                        $delMobile = $delAdmin ? (string)($delAdmin['mobile'] ?? '') : '';
                        $delFr = $delAdminId > 0 ? \app\admin\library\FranchiseService::resolveFranchiseForAdmin($delAdminId) : null;
                        if ($delMobile === '' && $delFr) {
                            $delMobile = (string)($delFr['mobile'] ?? '');
                        }
                        $rows[$k]['delete_operator_display'] = $delName !== '' ? ($delName . ($delMobile !== '' ? ' / ' . $delMobile : '')) : '-';
                        if ($delFr) {
                            $dlLevel = (int)($delFr['level'] ?? 0);
                            $dlName = (string)($delFr['name'] ?? '');
                            $dlParent = '';
                            if ((int)($delFr['parent_id'] ?? 0) > 0) {
                                $dlParent = (string)Db::name('franchise')->where('id', (int)$delFr['parent_id'])->value('name');
                            }
                            if ($dlLevel === 1) {
                                $rows[$k]['delete_franchise_display'] = $dlName . '（一级）';
                            } elseif ($dlLevel === 2) {
                                $rows[$k]['delete_franchise_display'] = $dlName . '（二级' . ($dlParent !== '' ? '，一级：' . $dlParent : '') . '）';
                            } else {
                                $rows[$k]['delete_franchise_display'] = $dlName;
                            }
                        } else {
                            $rows[$k]['delete_franchise_display'] = '总部';
                        }
                    } else {
                        $rows[$k]['delete_operator_display'] = '';
                        $rows[$k]['delete_franchise_display'] = '';
                    }
                }
            }
        }

        $result = ['total' => $list->total(), 'rows' => $rows];
        return json($result);
    }

    /**
     * 当前登录管理员在「物流」里可见的发货区域（城市）范围。
     *  - 加盟商：按其 fa_franchise.region_ids 解析（区/县取其上级市名，因为 logistics.origincity 存的是市）
     *  - 物流角色组(identity=4)：按其 fa_admin.city
     *
     * @param int $adminId 0 = 取当前登录管理员
     * @return array{cities:?array<int,string>,no_region:bool}
     *         cities=null 表示不按城市限制（总后台等）；no_region=true 表示无任何可见区域（应显示空数据）
     */
    protected function resolveLogisticsVisibleCityScope(int $adminId = 0): array
    {
        $adminId = $adminId > 0 ? $adminId : (int)$this->auth->id;
        if ($adminId <= 0) {
            return ['cities' => null, 'no_region' => false];
        }

        $cityFilter = null;
        $noRegion = false;

        // 加盟商：按 region_ids 过滤物流专线（发货城市），只看到自己负责区域内的专线
        $franchise = FranchiseService::resolveFranchiseForAdmin($adminId);
        if ($franchise) {
            $franchiseCityNames = [];
            foreach ((json_decode((string)($franchise['region_ids'] ?? '[]'), true) ?: []) as $v) {
                $rid = (int)$v;
                if ($rid <= 0) {
                    continue;
                }
                $area = Db::name('area')->where('id', $rid)->find();
                if (!$area) {
                    continue;
                }
                $areaName = (string)($area['shortname'] ?? $area['name'] ?? '');
                $level = (int)($area['level'] ?? 0);
                // 区/县（level>=3）取其上级市名，因为 logistics.origincity 存的是市
                if ($level >= 3 && !empty($area['pid'])) {
                    $parentName = (string)Db::name('area')->where('id', (int)$area['pid'])->value('shortname');
                    if ($parentName === '') {
                        $parentName = (string)Db::name('area')->where('id', (int)$area['pid'])->value('name');
                    }
                    if ($parentName !== '' && !in_array($parentName, $franchiseCityNames, true)) {
                        $franchiseCityNames[] = $parentName;
                    }
                }
                if ($areaName !== '' && !in_array($areaName, $franchiseCityNames, true)) {
                    $franchiseCityNames[] = $areaName;
                }
            }
            if (!empty($franchiseCityNames)) {
                $cityFilter = $franchiseCityNames;
            } else {
                // 加盟商未配置区域：不展示任何专线（防止泄露全部物流数据）
                $noRegion = true;
            }
        }

        if ($adminId != 1 && $cityFilter === null) {
            $admininfo = Db::name('admin')->where('id', $adminId)->find();
            $groupAccess = $admininfo
                ? Db::name('auth_group_access')->where('uid', $admininfo['id'])->find()
                : null;
            $identity = $groupAccess
                ? Db::name('auth_group')->where('id', (int)($groupAccess['group_id'] ?? 0))->find()
                : null;

            if ($identity && (int)$identity['identity'] === 4) {
                $cityNames = [];
                foreach ((json_decode((string)($admininfo['city'] ?? ''), true) ?: []) as $v) {
                    $v = (int)$v;
                    if ($v <= 0) {
                        continue;
                    }
                    $city_name = Db::name('area')->where('id', $v)->value('shortname');
                    if (!$city_name) {
                        $city_name = Db::name('area')->where('id', $v)->value('name');
                    }
                    if ($city_name) {
                        $cityNames[] = $city_name;
                    }
                }

                // 如果选择了“全国”，则不做城市过滤
                if (!empty($cityNames)) {
                    $cityFilter = $cityNames;
                }
            }
        }

        return ['cities' => $cityFilter, 'no_region' => $noRegion];
    }

    /**
     * 生成「可见发货区域」对应的物流专线过滤（订单表通过 logistics_id 命中这些专线）
     *
     * @param array<int,string>|null $cities   可见城市名，null=不限制
     * @param bool                   $noRegion 无任何可见区域时统计结果恒为 0
     * @return array{0:?string,1:array<string,mixed>} [sql, 命名绑定]；sql=null 表示不加限制
     */
    protected function buildLogisticsCityScopeSql(?array $cities, bool $noRegion = false): array
    {
        if ($noRegion) {
            return ['1 = 0', []];
        }
        if (empty($cities)) {
            return [null, []];
        }

        $conditions = [];
        $bind = [];
        $index = 0;
        foreach ($cities as $cityName) {
            $cityName = trim((string)$cityName);
            if ($cityName === '') {
                continue;
            }
            $name = 'lgCity_v' . (++$index);
            $conditions[] = 'origincity LIKE :' . $name;
            $bind[$name] = '%' . $cityName . '%';
        }
        if ($conditions === []) {
            return [null, []];
        }

        $logisticsTable = (string)config('database.prefix') . 'logistics';
        $sql = 'logistics_id IN (SELECT id FROM `' . $logisticsTable . '` WHERE ' . implode(' OR ', $conditions) . ')';

        return [$sql, $bind];
    }

    /**
     * 物流统计卡片（发货单量 / 总干线费 / 总吨数 / 总方位）
     *
     * 路由：logistics/statistics
     * 前端：logistics 列表页顶部统计卡片，按"发货时间"(order.createtime)筛选
     * 时间筛选：start_date/end_date（YYYY-MM-DD，可筛某一天/某个月/任意区间，都为空=全部时间）
     * 兼容旧参数：range=month(本月)/last_month(上个月)/year(今年)/all(全部)
     *
     * @return Json
     */
    public function statistics()
    {
        $range = trim((string)$this->request->param('range', ''));
        $startDate = trim((string)$this->request->param('start_date', ''));
        $endDate   = trim((string)$this->request->param('end_date', ''));
        // 前端是否显式传了起止日期（传了但为空表示“全部时间”，与完全不传的默认本月区分开）
        $allParams = (array)$this->request->param();
        $hasDateParams = array_key_exists('start_date', $allParams) || array_key_exists('end_date', $allParams);
        $filter = (string)$this->request->param('filter', '');
        $op     = (string)$this->request->param('op', '');
        $search = (string)$this->request->param('search', '');
        if ($startDate !== '' || $endDate !== '') {
            // 自定义时间筛选：可只筛某一天（起止同一天）、某个月或任意区间
            list($startTime, $endTime) = $this->resolveStatDateRangeByDates($startDate, $endDate);
            $range = 'custom';
        } elseif ($hasDateParams) {
            // 用户点了“全部时间”（传了空的起止日期）：不限制时间
            $range = 'all';
            list($startTime, $endTime) = [null, null];
        } else {
            // 兼容旧的预设区间参数（month/last_month/year/all）
            $range = $range !== '' ? $range : 'month';
            list($startTime, $endTime) = $this->resolveStatDateRange($range);
        }

        // 若物流列表存在搜索/筛选条件，则统计卡片联动为“这些物流”的订单数据；否则统计全部
        $logisticsIds = $this->resolveFilteredLogisticsIds($filter, $op, $search);

        // 与列表同口径：加盟商/区域子后台只统计自己可见发货区域（城市）内物流专线的发货单
        $visibleScope = $this->resolveLogisticsVisibleCityScope();
        list($cityScopeSql, $cityScopeBind) = $this->buildLogisticsCityScopeSql($visibleScope['cities'], $visibleScope['no_region']);

        // 每个指标独立构建查询（ThinkPHP5 中 count()/sum() 会改变查询对象状态，不能复用）
        $buildQuery = function () use ($startTime, $endTime, $logisticsIds, $cityScopeSql, $cityScopeBind) {
            $q = Db::name('order');
            // 仅统计有效发货单：排除未下单(5)、已取消(4)、已驳回(8)
            $q->whereNotIn('pay_status', [4, 5, 8]);
            if ($startTime !== null) {
                $q->where('createtime', '>=', $startTime);
            }
            if ($endTime !== null) {
                $q->where('createtime', '<=', $endTime);
            }
            if ($cityScopeSql !== null) {
                // 只看得到本区域（发货城市）的物流专线时，其发货单统计同样只算这些专线
                $q->whereRaw($cityScopeSql, $cityScopeBind);
            }
            if ($logisticsIds !== null) {
                if (empty($logisticsIds)) {
                    // 当前筛选条件未匹配到任何物流：结果应恒为 0
                    $q->where('logistics_id', -1);
                } else {
                    $q->where('logistics_id', 'in', $logisticsIds);
                }
            }
            return $q;
        };
        $shipmentCount = (int)$buildQuery()->count();
        $trunkFee      = round((float)$buildQuery()->sum('logistics_driver_cost'), 2);
        $tonnage       = round((float)$buildQuery()->sum('weight'), 2);
        $volume        = round((float)$buildQuery()->sum('direction'), 2);

        return json([
            'code' => 1,
            'msg'  => '',
            'data' => [
                'range'          => $range,
                'start'          => $startTime !== null ? date('Y-m-d H:i:s', $startTime) : '',
                'end'            => $endTime !== null ? date('Y-m-d H:i:s', $endTime) : '',
                'shipment_count' => $shipmentCount,
                'trunk_fee'      => $trunkFee,
                'tonnage'        => $tonnage,
                'volume'         => $volume,
            ],
        ]);
    }

    /**
     * 根据物流列表的搜索/筛选条件，解析出被筛选到的物流ID集合。
     *
     * 复用 index() 的 buildparams() 以保持与列表一致的筛选口径。
     * 当未填写任何搜索/筛选条件时返回 null（表示不按物流限定，统计全部订单）。
     *
     * @param string $filter 筛选JSON（如 {"shipping_logistics_name":"成都"}）
     * @param string $op     操作符JSON（如 {"shipping_logistics_name":"LIKE"}）
     * @param string $search 通用搜索关键词
     * @return int[]|null
     */
    protected function resolveFilteredLogisticsIds($filter, $op, $search)
    {
        $filter = is_string($filter) ? trim($filter) : '';
        $op     = is_string($op) ? trim($op) : '';
        $search = is_string($search) ? trim($search) : '';

        // 通用搜索在无条件时也会传 filter/op 为 '{}'，这里需解析后判断是否真的有筛选字段
        $filterArr = [];
        if ($filter !== '' && $filter !== '{}' && $filter !== '[]') {
            $decoded = json_decode($filter, true);
            if (is_array($decoded)) {
                foreach ($decoded as $k => $v) {
                    $isEmpty = ($v === '' || $v === null || (is_array($v) && count($v) === 0));
                    if (!$isEmpty) {
                        $filterArr[$k] = $v;
                    }
                }
            }
        }
        if (empty($filterArr) && $search === '') {
            return null;
        }

        // buildparams() 内部从 GET 参数读取 filter/op/search/sort/order/limit
        $this->request->get([
            'filter' => $filter !== '' ? ($filter === '{}' || $filter === '[]' ? json_encode($filterArr) : $filter) : '',
            'op'     => $op,
            'search' => $search,
            'sort'   => 'id',
            'order'  => 'desc',
            'offset' => 0,
            'limit'  => 999999,
        ]);
        [$where, $sort, $order, $offset, $limit] = $this->buildparams();

        $ids = Db::name('logistics')->where($where)->column('id');
        return array_values(array_filter(array_map('intval', (array)$ids)));
    }

    /**
     * 按自定义起止日期解析统计时间范围（发货时间筛选）
     *
     *  - 起止都填：开始日 00:00:00 ~ 结束日 23:59:59（同一天即“筛某一天”，同一月首尾即“筛某个月”）
     *  - 只填一个：单边过滤（>= 开始日 或 <= 结束日）
     *  - 都为空：不限制时间
     *
     * @param string $startDate YYYY-MM-DD（也兼容 2026/09/14、带时间等写法）
     * @param string $endDate   YYYY-MM-DD
     * @return array [startTime|null, endTime|null]
     */
    protected function resolveStatDateRangeByDates($startDate, $endDate)
    {
        $toTime = function ($date, $isEnd) {
            $date = trim((string)$date);
            if ($date === '') {
                return null;
            }
            $ts = strtotime($date);
            if ($ts === false) {
                return null;
            }

            return strtotime(date('Y-m-d', $ts) . ($isEnd ? ' 23:59:59' : ' 00:00:00'));
        };

        $startTime = $toTime($startDate, false);
        $endTime   = $toTime($endDate, true);
        // 起止写反了自动交换，避免筛不出数据
        if ($startTime !== null && $endTime !== null && $startTime > $endTime) {
            $tmp = $startTime;
            $startTime = strtotime(date('Y-m-d', $endTime) . ' 00:00:00');
            $endTime   = strtotime(date('Y-m-d', $tmp) . ' 23:59:59');
        }

        return [$startTime, $endTime];
    }

    /**
     * 根据预设区间解析发货时间范围
     *
     * @param string $range month/last_month/year/all
     * @return array [startTime|null, endTime|null]
     */
    protected function resolveStatDateRange($range)
    {
        switch ($range) {
            case 'last_month':
                $firstDay = strtotime('first day of last month');
                return [
                    strtotime(date('Y-m-01 00:00:00', $firstDay)),
                    strtotime(date('Y-m-t 23:59:59', $firstDay)),
                ];
            case 'year':
                return [
                    strtotime(date('Y-01-01 00:00:00')),
                    strtotime(date('Y-12-31 23:59:59')),
                ];
            case 'all':
                return [null, null];
            case 'month':
            default:
                return [
                    strtotime(date('Y-m-01 00:00:00')),
                    strtotime(date('Y-m-t 23:59:59')),
                ];
        }
    }

    /**
     * 子后台申请删除接口
     *
     * 路由：logistics/apply_delete
     * 前端：public/assets/js/backend/logistics.js 中“申请删除”按钮调用
     *
     * @param string|null $ids
     * @return Json
     */
    public function apply_delete($ids = null)
    {
        $ids = $this->request->post('ids');
        if (empty($ids)) {
            $this->error('参数错误，缺少ID');
        }

        $logData = [
            'log_id'      => $ids,
            'create_time' => time(),
            'admin_id'    => $this->auth->id, // 记录操作管理员
            'ip'          => $this->request->ip(),
            'status'      => 1, // 0=待审核，1=已申请, 2=已拒绝（此处按实际业务使用1表示已申请）
        ];

        $res = Db::name('delete_log')->insert($logData);
        if ($res) {
            $this->success('删除申请已提交，请等待总后台审核');
        } else {
            $this->error('申请失败');
        }
    }

    /**
     * 取消删除申请：删除当前线路的申请记录
     *
     * 路由：logistics/cancel_apply_delete
     */
    public function cancel_apply_delete($ids = null)
    {
        $ids = $this->request->post('ids');
        if (empty($ids)) {
            $this->error('参数错误，缺少ID');
        }

        // 删除当前管理员对此线路最近一次“已申请”记录
        $deleted = Db::name('delete_log')
            ->where('log_id', $ids)
            ->where('admin_id', $this->auth->id)
            ->where('status', 1)
            ->delete();

        if ($deleted) {
            $this->success('已取消删除申请');
        } else {
            $this->error('暂无可取消的删除申请');
        }
    }

    /**
     * 设置物流评级（五星 5/4/3/2/1）
     * 路由：logistics/set_level
     */
    public function set_level()
    {
        $id = (int) $this->request->post('id');
        $level = (int) $this->request->post('level');
        if ($id <= 0 || !in_array($level, [5, 4, 3, 2, 1], true)) {
            $this->error('参数错误');
        }
        $row = $this->model->get($id);
        if (!$row) {
            $this->error('记录不存在');
        }
        $row->level = $level;
        $row->save();
        $this->success('评级已更新');
    }

    /**
     * 导入
     *
     * @return void
     * @throws PDOException
     * @throws BindParamException
     */
    public  function import()
    {
        $file = $this->request->request('file');
        if (!$file) {
            $this->error(__('Parameter %s can not be empty', 'file'));
        }
        $filePath = ROOT_PATH . DS . 'public' . DS . $file;
        if (!is_file($filePath)) {
            $this->error(__('No results were found'));
        }

// 实例化reader
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        if (!in_array($ext, ['csv', 'xls', 'xlsx'])) {
            $this->error(__('Unknown data format'));
        }
        if ($ext === 'csv') {
            $file = fopen($filePath, 'r');
            $filePath = tempnam(sys_get_temp_dir(), 'import_csv');
            $fp = fopen($filePath, 'w');
            $n = 0;
            while ($line = fgets($file)) {
                $line = rtrim($line, "\n\r\0");
                $encoding = mb_detect_encoding($line, ['utf-8', 'gbk', 'latin1', 'big5']);
                if ($encoding !== 'utf-8') {
                    $line = mb_convert_encoding($line, 'utf-8', $encoding);
                }
                if ($n == 0 || preg_match('/^".*"$/', $line)) {
                    fwrite($fp, $line . "\n");
                } else {
                    fwrite($fp, '"' . str_replace(['"', ','], ['""', '","'], $line) . "\"\n");
                }
                $n++;
            }
            fclose($file) || fclose($fp);

            $reader = new Csv();
        } elseif ($ext === 'xls') {
            $reader = new Xls();
        } else {
            $reader = new Xlsx();
        }

// 导入文件首行类型,默认是注释,如果需要使用字段名称请使用name
        $importHeadType = isset($this->importHeadType) ? $this->importHeadType : 'comment';

        $table = $this->model->getQuery()->getTable();
        $database = \think\Config::get('database.database');
        $fieldArr = [];
        $list = db()->query("SELECT COLUMN_NAME,COLUMN_COMMENT FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND TABLE_SCHEMA = ?", [$table, $database]);
        foreach ($list as $k => $v) {
            if ($importHeadType == 'comment') {
                $v['COLUMN_COMMENT'] = explode(':', $v['COLUMN_COMMENT'])[0]; // 字段备注有:时截取
                $fieldArr[$v['COLUMN_COMMENT']] = $v['COLUMN_NAME'];
            } else {
                $fieldArr[$v['COLUMN_NAME']] = $v['COLUMN_NAME'];
            }
        }

// 加载文件
        $rawRows = []; // 先收集所有原始数据
        try {
            if (!$PHPExcel = $reader->load($filePath)) {
                $this->error(__('Unknown data format'));
            }
            $currentSheet = $PHPExcel->getSheet(0);  // 读取文件中的第一个工作表
            $allColumn = $currentSheet->getHighestDataColumn(); // 取得最大的列号
            $allRow = $currentSheet->getHighestRow(); // 取得一共有多少行
            $maxColumnNumber = Coordinate::columnIndexFromString($allColumn);
            $fields = [];
            for ($currentRow = 1; $currentRow <= 1; $currentRow++) {
                for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                    $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                    $fields[] = $val;
                }
            }

            // 第一步：收集所有原始数据行
            for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
                $values = [];
                for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                    $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                    $values[] = is_null($val) ? '' : $val;
                }
                $row = [];
                $temp = array_combine($fields, $values);
                foreach ($temp as $k => $v) {
                    if (isset($fieldArr[$k]) && $k !== '') {
                        $row[$fieldArr[$k]] = $v;
                    }
                }
                if ($row) {
                    $rawRows[] = [
                        'row' => $row,
                        'rowNum' => $currentRow
                    ];
                }
            }
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
        
        if (empty($rawRows)) {
            $this->error(__('No rows were updated'));
        }

        // 第二步：批量预加载地址缓存，减少重复查询
        $addressMap = []; // 地址 -> 经纬度的映射
        $uniqueAddresses = []; // 去重后的地址列表（只包含需要查询的地址）
        
        foreach ($rawRows as $item) {
            $row = $item['row'];
            $shipAddress = str_replace(' ', '', ($row['shipping_province'] ?? '') . ($row['origincity'] ?? '') . ($row['shipping_logistics_address'] ?? ''));
            $receiveAddress = str_replace(' ', '', ($row['province'] ?? '') . ($row['destination'] ?? '') . ($row['arrival_logistics_address'] ?? ''));
            
            // 检查发货地址是否需要查询（如果Excel中已有经纬度，则跳过）
            if ($shipAddress && !isset($uniqueAddresses[$shipAddress])) {
                // 检查Excel中是否已有发货经纬度
                $hasShippingCoords = !empty($row['shipping_longitude']) && !empty($row['shipping_latitude']);
                if (!$hasShippingCoords) {
                    $uniqueAddresses[$shipAddress] = true;
                } else {
                    // Excel中已有经纬度，直接使用
                    $addressMap[$shipAddress] = [
                        'lng' => trim($row['shipping_longitude']),
                        'lat' => trim($row['shipping_latitude'])
                    ];
                }
            }
            
            // 检查收货地址是否需要查询（如果Excel中已有经纬度，则跳过）
            if ($receiveAddress && !isset($uniqueAddresses[$receiveAddress])) {
                // 检查Excel中是否已有收货经纬度
                $hasReceiveCoords = !empty($row['arrival_longitude']) && !empty($row['arrival_latitude']);
                if (!$hasReceiveCoords) {
                    $uniqueAddresses[$receiveAddress] = true;
                } else {
                    // Excel中已有经纬度，直接使用
                    $addressMap[$receiveAddress] = [
                        'lng' => trim($row['arrival_longitude']),
                        'lat' => trim($row['arrival_latitude'])
                    ];
                }
            }
        }
        
        // 批量预加载缓存（只查询Excel中没有经纬度的地址）
        foreach (array_keys($uniqueAddresses) as $address) {
            if (!isset($addressMap[$address])) {
                $addressMap[$address] = $this->resolveCoordinatesWithCache($address);
            }
        }

        // 第三步：处理数据并计算距离
        $insert = [];
        $failedAddresses = [];
        $distanceCache = []; // 距离缓存
        
        foreach ($rawRows as $item) {
            $row = $item['row'];
            $rowNum = $item['rowNum'];
            
            // 获取经纬度
            $shipAddress = str_replace(' ', '', ($row['shipping_province'] ?? '') . ($row['origincity'] ?? '') . ($row['shipping_logistics_address'] ?? ''));
            $receiveAddress = str_replace(' ', '', ($row['province'] ?? '') . ($row['destination'] ?? '') . ($row['arrival_logistics_address'] ?? ''));
            
            // 检查Excel中是否已有经纬度
            $hasShippingCoords = !empty($row['shipping_longitude']) && !empty($row['shipping_latitude']);
            $hasReceiveCoords = !empty($row['arrival_longitude']) && !empty($row['arrival_latitude']);
            
            if ($hasShippingCoords) {
                // Excel中已有发货经纬度，直接使用
                $row['shipping_longitude'] = trim($row['shipping_longitude']);
                $row['shipping_latitude'] = trim($row['shipping_latitude']);
            } else {
                // 从缓存中获取发货经纬度
                $shipLocation = $addressMap[$shipAddress] ?? ['lng' => '', 'lat' => ''];
                if (empty($shipLocation['lng']) || empty($shipLocation['lat'])) {
                    $failedAddresses[] = "第{$rowNum}行发货地址查询失败: {$shipAddress}";
                }
                $row['shipping_longitude'] = $shipLocation['lng'];
                $row['shipping_latitude'] = $shipLocation['lat'];
            }
            
            if ($hasReceiveCoords) {
                // Excel中已有收货经纬度，直接使用
                $row['arrival_longitude'] = trim($row['arrival_longitude']);
                $row['arrival_latitude'] = trim($row['arrival_latitude']);
            } else {
                // 从缓存中获取收货经纬度
                $receiveLocation = $addressMap[$receiveAddress] ?? ['lng' => '', 'lat' => ''];
                if (empty($receiveLocation['lng']) || empty($receiveLocation['lat'])) {
                    $failedAddresses[] = "第{$rowNum}行收货地址查询失败: {$receiveAddress}";
                }
                $row['arrival_longitude'] = $receiveLocation['lng'];
                $row['arrival_latitude'] = $receiveLocation['lat'];
            }

            // 计算距离：如果Excel中已有距离，直接使用；否则根据经纬度计算
            if (!empty($row['distance']) && is_numeric($row['distance']) && floatval($row['distance']) > 0) {
                // Excel中已有距离，直接使用
                $row['distance'] = floatval($row['distance']);
            } elseif (
                !empty($row['shipping_latitude']) && !empty($row['shipping_longitude']) &&
                !empty($row['arrival_latitude']) && !empty($row['arrival_longitude'])
            ) {
                // Excel中没有距离，根据经纬度计算
                $lat1 = (float)$row['shipping_latitude'];
                $lng1 = (float)$row['shipping_longitude'];
                $lat2 = (float)$row['arrival_latitude'];
                $lng2 = (float)$row['arrival_longitude'];
                
                // 使用距离缓存键
                $distanceKey = round($lat1, 6) . ':' . round($lng1, 6) . ':' . round($lat2, 6) . ':' . round($lng2, 6);
                
                if (!isset($distanceCache[$distanceKey])) {
                    $distanceCache[$distanceKey] = $this->resolveDrivingDistanceWithCache($lat1, $lng1, $lat2, $lng2);
                }
                $row['distance'] = $distanceCache[$distanceKey];
            } else {
                $row['distance'] = $row['distance'] ?? 0;
            }
            $row['status'] = 2;
            $row['maintained'] = isset($row['maintained']) ? intval($row['maintained']) : 0;
            $insert[] = $row;
        }

        // 如果有查询失败的地址，记录日志并提示
        if (!empty($failedAddresses)) {
            $failedCount = count($failedAddresses);
            $failedMsg = "共有 {$failedCount} 个地址的经纬度查询失败，详情请查看日志。";
            \think\Log::warning("物流信息导入 - 经纬度查询失败详情: " . implode('; ', $failedAddresses));
        }

        // 根据除经纬度外的字段进行去重，避免重复数据导入
        if (!empty($insert)) {
            $dedupInsert = [];
            $seenSignatures = [];
            foreach ($insert as $row) {
                // 复制一份用于比较的数组，去掉经纬度字段
                $compare = $row;
                unset(
                    $compare['shipping_longitude'],
                    $compare['shipping_latitude'],
                    $compare['arrival_longitude'],
                    $compare['arrival_latitude']
                );

                // 使用序列化后的内容作为去重标识
                $signature = md5(serialize($compare));
                if (!isset($seenSignatures[$signature])) {
                    $seenSignatures[$signature] = true;
                    $dedupInsert[] = $row;
                }
            }
            $insert = $dedupInsert;
        }

        try {
            // 是否包含admin_id字段
            $has_admin_id = false;
            foreach ($fieldArr as $name => $key) {
                if ($key == 'admin_id') {
                    $has_admin_id = true;
                    break;
                }
            }
            if ($has_admin_id) {
                $auth = Auth::instance();
                foreach ($insert as &$val) {
                    if (empty($val['admin_id'])) {
                        $val['admin_id'] = $auth->isLogin() ? $auth->id : 0;
                    }
                }
            }
            
            // 使用更高效的批量插入方式
            Db::startTrans();
            try {
                // 分批插入，每批200条，减少单次操作压力
                foreach (array_chunk($insert, 200) as $chunk) {
                    $this->model->saveAll($chunk);
                }
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                throw $e;
            }
        } catch (PDOException $exception) {
            $msg = $exception->getMessage();
            if (preg_match("/.+Integrity constraint violation: 1062 Duplicate entry '(.+)' for key '(.+)'/is", $msg, $matches)) {
                $msg = "导入失败，包含【{$matches[1]}】的记录已存在";
            };
            $this->error($msg);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }

        // 如果有查询失败的地址，在成功提示中显示警告
        if (!empty($failedAddresses)) {
            $failedCount = count($failedAddresses);
            $successMsg = "导入成功！但有 {$failedCount} 个地址的经纬度查询失败，请检查日志或手动补充。";
            $this->success($successMsg);
        } else {
            $this->success();
        }
    }

    /**
     * 导出
     *
     * 使用分批分页的方式写入CSV，避免一次性读取大量数据导致内存溢出
     *
     * @return void
     * @throws DbException
     */
    public function export()
    {
        set_time_limit(0);
        @ini_set('memory_limit', '512M');

        list($where, $sort, $order) = $this->buildparams();
        $idsParam = $this->request->get('ids', '');
        $ids = array_filter(array_unique(array_map('trim', explode(',', $idsParam))));

        $table = $this->model->getQuery()->getTable();
        $query = Db::name($table)->where($where)->order($sort, $order);
        if (!empty($ids)) {
            $query->where('id', 'in', $ids);
        }

        $total = (clone $query)->count();
        if (!$total) {
            $this->error('暂无可导出的数据');
        }

        $columns = [
            'id'                          => 'ID',
            'shipping_province'           => '发货省份',
            'origincity'                  => '发货城市',
            'shipping_area'               => '发货区域',
            'shipping_logistics_park'     => '发货物流园',
            'shipping_logistics_name'     => '发货物流名称',
            'shipping_logistics_address'  => '发货物流地址',
            'shipping_logistics_mobile'   => '发货物流电话',
            'province'                    => '到货省份',
            'destination'                 => '到货城市',
            'arrival_area'                => '到货区域',
            'arrival_logistics_park'      => '到货物流园',
            'arrival_logistics_name'      => '到货物流名称',
            'arrival_logistics_address'   => '到货物流地址',
            'arrival_logistics_mobile'    => '到货物流电话',
            'time_limit'                  => '时效',
            'distance'                    => '距离(公里)',
            'side'                        => '侧重点',
            'perton'                      => '每吨价格',
            'remarks'                     => '备注',
            'level'                       => '物流评级',
        ];

        $filename = 'logistics_' . date('Ymd_His') . '.csv';
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $handle = fopen('php://output', 'w');
        // 输出 UTF-8 BOM，避免 Excel 乱码
        fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($handle, array_values($columns));

        $chunkSize = 500;
        $page = 1;
        while (true) {
            $rows = (clone $query)->page($page, $chunkSize)->select();
            if (!$rows || !count($rows)) {
                break;
            }
            $levelMap = [5 => '五星', 4 => '四星', 3 => '三星', 2 => '二星', 1 => '一星'];
            foreach ($rows as $row) {
                $row = is_array($row) ? $row : $row->toArray();
                $outputRow = [];
                foreach ($columns as $field => $label) {
                    $val = isset($row[$field]) ? $row[$field] : '';
                    if ($field === 'level' && $val !== '') {
                        $val = isset($levelMap[(int)$val]) ? $levelMap[(int)$val] : $val;
                    }
                    $outputRow[] = $val;
                }
                fputcsv($handle, $outputRow);
            }
            $page++;
            if (function_exists('ob_flush')) {
                ob_flush();
            }
            flush();
        }

        fclose($handle);
        exit;
    }
    /**
     * 从百度地图API获取经纬度（带轮询AK机制）
     *
     * @param string $address 地址
     * @return array 经纬度数组
     */
    private function getCoordinatesFromBaiduMap($address)
    {
        // 如果地址为空，直接返回
        if (empty($address)) {
            return ['lng' => '', 'lat' => ''];
        }
        
        // 从配置文件读取AK列表
        $configAk = \think\Config::get('site.BaiduKey');

        // 初始化AK列表
        $akList = [];
        if ($configAk) {
            // 如果是JSON字符串，解析为数组
            if (is_string($configAk)) {
                $decoded = json_decode($configAk, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $akList = $decoded;
                }
            } elseif (is_array($configAk)) {
                $akList = $configAk;
            }
        }
        
        // 如果没有配置AK，使用默认AK
        if (empty($akList)) {
            $akList = ['default' => 'T6fLs4Xa9Hj16REmRNyeY20ZU5ODkpV2'];
        }

        // 记录当前使用的AK索引
        static $currentAkIndex = 0;
        $akKeys = array_keys($akList);

        // 尝试所有AK，直到成功或全部失败
        $maxRetries = count($akList);

        for ($i = 0; $i < $maxRetries; $i++) {
            $currentKey = $akKeys[$currentAkIndex];
            $ak = $akList[$currentKey];
            $url = "http://api.map.baidu.com/geocoding/v3/?address=" . urlencode($address) . "&output=json&ak=" . $ak;

            try {
                // 设置超时时间：连接超时5秒，读取超时10秒
                $context = stream_context_create([
                    'http' => [
                        'timeout' => 10,
                        'method' => 'GET',
                        'header' => [
                            'User-Agent: PHP',
                            'Connection: close'
                        ]
                    ]
                ]);
                
                // 调用API
                $result = @file_get_contents($url, false, $context);

                if ($result === FALSE) {
                    // 网络请求失败，记录并尝试下一个AK
                    \think\Log::error("百度地图API网络请求失败，AK：" . $currentKey);
                    $currentAkIndex = ($currentAkIndex + 1) % count($akList);
                    continue;
                }

                $data = json_decode($result, true);

                if ($data['status'] == 0 && isset($data['result']['location'])) {
                    // 成功获取坐标
                    return [
                        'lng' => $data['result']['location']['lng'],
                        'lat' => $data['result']['location']['lat']
                    ];
                } elseif ($data['status'] == 1 || $data['status'] == 101 || $data['status'] == 302 || $data['status'] == 401) {
                    // AK权限或额度问题：status=1(服务内部错误)、status=101(AK无效/无权限)、status=302(天配额超限)、status=401(并发超限)
                    \think\Log::warning("百度地图AK额度可能用尽，AK：" . $currentKey . "，状态码：" . $data['status']);
                    $currentAkIndex = ($currentAkIndex + 1) % count($akList);
                    continue; // 尝试下一个AK
                } else {
                    // 其他错误（地址解析失败等）
                    \think\Log::error("百度地图API调用失败，地址：{$address}，AK：" . $currentKey . "，返回：" . json_encode($data));
                    break; // 如果是地址问题，不需要切换AK重试
                }
            } catch (\Exception $e) {
                \think\Log::error("获取坐标异常，AK：" . $currentKey . "，错误：" . $e->getMessage());
                $currentAkIndex = ($currentAkIndex + 1) % count($akList);
                continue;
            }
        }

        // 所有AK都尝试失败
        \think\Log::error("所有百度地图AK都尝试失败，地址：{$address}");
        return ['lng' => '', 'lat' => ''];
    }

    /**
     * 使用本地缓存包装经纬度查询，避免重复请求外部接口
     */
    private function resolveCoordinatesWithCache($address)
    {
        if (!$address) {
            return ['lng' => '', 'lat' => ''];
        }

        // 先检查内存缓存
        if (isset($this->geocodeCache[$address])) {
            return $this->geocodeCache[$address];
        }

        // 再检查持久化缓存
        $cacheKey = 'logistics:geocode:' . md5($address);
        if (Cache::has($cacheKey)) {
            $location = Cache::get($cacheKey);
            $this->geocodeCache[$address] = $location; // 更新内存缓存
            return $location;
        }
 
        // 缓存未命中，调用API（这里会延迟）
        $location = $this->getCoordinatesFromBaiduMap($address);
        if (!empty($location['lng']) && !empty($location['lat'])) {
            Cache::set($cacheKey, $location, self::GEO_CACHE_TTL);
        }

        // 更新内存缓存
        $this->geocodeCache[$address] = $location;
        return $location;
    }
    /**
     * 对驾车距离查询做缓存和限速控制
     */
    private function resolveDrivingDistanceWithCache($lat1, $lng1, $lat2, $lng2)
    {
        $key = implode(':', [
            round($lat1, 6),
            round($lng1, 6),
            round($lat2, 6),
            round($lng2, 6)
        ]);

        // 先检查内存缓存
        if (isset($this->distanceCache[$key])) {
            return $this->distanceCache[$key];
        }

        // 再检查持久化缓存
        $cacheKey = 'logistics:distance:' . md5($key);
        if (Cache::has($cacheKey)) {
            $distance = Cache::get($cacheKey);
            $this->distanceCache[$key] = $distance; // 更新内存缓存
            return $distance;
        }
        
        // 缓存未命中，调用API
        $distance = calculateDrivingDistance($lat1, $lng1, $lat2, $lng2);
        Cache::set($cacheKey, $distance, self::DISTANCE_CACHE_TTL);
        
        // 成功调用API后短暂延迟，避免限流（降低到0.05秒）
        usleep(50000);
        
        // 更新内存缓存
        $this->distanceCache[$key] = $distance;
        return $distance;
    }
    /**
     * 查看
     *
     * @return string|Json
     * @throws \think\Exception
     * @throws DbException
     */
    /**
     * 规划路线页面
     */
    public function list()
    {
        $ids  = $this->request->param('ids');
        $order_id = Db::name('admin_order')->where('id',$ids)->value('order_id');
        $orderInfo = Db::name('order')->where('id',$order_id)->find();
        if (!$orderInfo) {
            $this->error('订单不存在');
        }
        $order_lng = Db::name('order')->where('id',$order_id)->field('loading,unload')->find();
        $loading = Db::name('user_address')->where('id',$order_lng['loading'])->find();
        $unload= Db::name('user_address')->where('id',$order_lng['unload'])->find();
        // 检查装货和卸货地址的经纬度是否有效
        if (empty($loading) || empty($loading['lng']) || empty($loading['lat'])) {
            $this->error('装货地址经纬度信息不完整');
        }
        if (empty($unload) || empty($unload['lng']) || empty($unload['lat'])) {
            $this->error('卸货地址经纬度信息不完整');
        }
        $load = [
            'lng' => (float)$loading['lng'],
            'lat' => (float)$loading['lat']
        ];
        $unlo = [
            'lng' => (float)$unload['lng'],
            'lat' => (float)$unload['lat']
        ];

        // 从装货、卸货地址提取城市和省份（用于匹配路线：只显示发货地→到货地相符的专线）
        $loadingCity = $this->extractCityFromAddress($loading['detailed_address'] ?? ($loading['address'] ?? ''));
        $unloadCity  = $this->extractCityFromAddress($unload['detailed_address'] ?? ($unload['address'] ?? ''));
        $loadingProvince = $this->extractProvinceFromAddress($loading['detailed_address'] ?? ($loading['address'] ?? ''));
        $unloadProvince  = $this->extractProvinceFromAddress($unload['detailed_address'] ?? ($unload['address'] ?? ''));

        // 三端价格计价所需：重量、体积、车型单价、取货/专线/送货加成、货物类型上浮
        $weight = isset($orderInfo['weight']) ? (float)$orderInfo['weight'] : 0;
        $volume = isset($orderInfo['direction']) ? (float)$orderInfo['direction'] : 0;
        $carType = Db::name('car_type')->where('id', $orderInfo['car_type_id'] ?? 0)->find();
        $carPrice = 0;
        if ($carType) {
            $carPrice = isset($carType['price']) ? (float)$carType['price'] : (isset($carType['Price']) ? (float)$carType['Price'] : 0);
        }
        $pickUpDriverFreight   = \think\Config::get('site.PickUpDriverFreight') ?: 0;
        $logisticsDriverFreight = \think\Config::get('site.LogisticsDriverFreight') ?: 0;
        $deliveryDriverFreight = \think\Config::get('site.DeliveryDriverFreight') ?: 0;
        $goodsTypePercentage = 0;
        if (!empty($orderInfo['goods_type_id'])) {
            $goodsType = Db::name('goods_type')->where('id', $orderInfo['goods_type_id'])->find();
            if ($goodsType && isset($goodsType['percentage']) && $goodsType['percentage'] > 0) {
                $goodsTypePercentage = (float)$goodsType['percentage'];
            }
        }

        $data = Db::name('logistics')->select();
        // 计算每条数据的三端距离与三端价格
        foreach ($data as $key => &$item) {
            // 检查shippingPoint的经纬度是否为空
            if (empty($item['shipping_longitude']) || empty($item['shipping_latitude'])) {
                unset($data[$key]);
                continue;
            }
            // 检查arrivalPoint的经纬度是否为空
            if (empty($item['arrival_longitude']) || empty($item['arrival_latitude'])) {
                unset($data[$key]);
                continue;
            }

            if ($loadingCity || $unloadCity || $loadingProvince || $unloadProvince) {
                $shippingCity     = trim($item['origincity'] ?? '');
                $arrivalCity      = trim($item['destination'] ?? '');
                $shippingProvince = trim($item['shipping_province'] ?? '');
                $arrivalProvince  = trim($item['province'] ?? '');
                $shippingMatch = false;
                if ($loadingCity) {
                    if ($shippingCity && mb_strpos($shippingCity, $loadingCity) !== false) {
                        $shippingMatch = true;
                    } elseif ($loadingProvince && $shippingProvince && mb_strpos($shippingProvince, $loadingProvince) !== false) {
                        $shippingMatch = true;
                    }
                } elseif ($loadingProvince) {
                    if ($shippingProvince && mb_strpos($shippingProvince, $loadingProvince) !== false) {
                        $shippingMatch = true;
                    }
                }

                $arrivalMatch = false;
                if ($unloadCity) {
                    if ($arrivalCity && mb_strpos($arrivalCity, $unloadCity) !== false) {
                        $arrivalMatch = true;
                    } elseif ($unloadProvince && $arrivalProvince && mb_strpos($arrivalProvince, $unloadProvince) !== false) {
                        $arrivalMatch = true;
                    }
                } elseif ($unloadProvince) {
                    if ($arrivalProvince && mb_strpos($arrivalProvince, $unloadProvince) !== false) {
                        $arrivalMatch = true;
                    }
                }
                if (!$shippingMatch || !$arrivalMatch) {
                    unset($data[$key]);
                    continue;
                }
            }
            $shippingPoint = [
                'lng' =>  (float)$item['shipping_longitude'],
                'lat' => (float)$item['shipping_latitude']
            ];
            $arrivalPoint = [
                'lng' =>  (float)$item['arrival_longitude'],
                'lat' =>  (float)$item['arrival_latitude']
            ];
            // 三端距离：装货→发货点、发货点→到货点、到货点→卸货
            $distanceLoadToShipping = $this->calculateDistance($load, $shippingPoint);
            $distanceShippingToArrival = $this->calculateDistance($shippingPoint, $arrivalPoint);
            $distanceArrivalToUnlo = $this->calculateDistance($arrivalPoint, $unlo);
            $totalDistance = $distanceLoadToShipping + $distanceShippingToArrival + $distanceArrivalToUnlo;

            if (!is_numeric($totalDistance) || is_nan($totalDistance) || is_infinite($totalDistance)) {
                $totalDistance = 0;
            }

            $item['total_distance_value'] = $totalDistance;
            $item['total_distance'] = number_format($totalDistance, 2).'公里';

            // 三端价格：专线费 + 取货司机费 + 送货司机费
            $perton  = isset($item['perton']) ? (float)$item['perton'] : 0;
            $side    = isset($item['side']) ? (float)$item['side'] : 0;
            $reflux  = isset($item['reflux']) ? (float)$item['reflux'] : 0;
            $bulky   = isset($item['bulky']) ? (float)$item['bulky'] : 0;
            $linePrice = 0;
            if (function_exists('calculatePrice')) {
                $priceResult = calculatePrice($weight, $volume, $perton, $side, $reflux, $bulky);
                $linePrice   = isset($priceResult['price']) ? (float)$priceResult['price'] : 0;
            }
            $pickupDriverFee   = $distanceLoadToShipping * $carPrice;
            $shipmentDriverFee = $distanceArrivalToUnlo * $carPrice;
            $logisticsCostBase    = $linePrice * (1 + $logisticsDriverFreight / 100);
            $pickupDriverFeeBase  = $pickupDriverFee * (1 + $pickUpDriverFreight / 100);
            $shipmentDriverFeeBase = $shipmentDriverFee * (1 + $deliveryDriverFreight / 100);
            if ($goodsTypePercentage > 0) {
                $logisticsCost = $logisticsCostBase * (1 + $goodsTypePercentage / 100);
                $pickupFee     = $pickupDriverFeeBase * (1 + $goodsTypePercentage / 100);
                $shipmentFee   = $shipmentDriverFeeBase * (1 + $goodsTypePercentage / 100);
            } else {
                $logisticsCost = $logisticsCostBase;
                $pickupFee     = $pickupDriverFeeBase;
                $shipmentFee   = $shipmentDriverFeeBase;
            }
            $totalPrice = round($logisticsCost, 2) + round($pickupFee, 2) + round($shipmentFee, 2);
            $item['total_price_value'] = $totalPrice;
        }
        // 按三端总价便宜优先排序，价格相同再按三端总距离排序
        usort($data, function($a, $b) {
            $priceCmp = ($a['total_price_value'] ?? PHP_FLOAT_MAX) <=> ($b['total_price_value'] ?? PHP_FLOAT_MAX);
            if ($priceCmp !== 0) {
                return $priceCmp;
            }
            return ($a['total_distance_value'] ?? 0) <=> ($b['total_distance_value'] ?? 0);
        });
        
        // 只显示前200条路线；加盟商线路账号仅显示前20条
        $data = array_slice($data, 0, 200);
        $isFranchiseLine = (bool)FranchiseService::resolveFranchiseForAdmin((int)$this->auth->id);
        if ($isFranchiseLine) {
            $data = array_slice($data, 0, 20);
        }

        $this->view->assign("row", $data);
        $this->view->assign("isFranchise", $isFranchiseLine ? 1 : 0);
        return $this->view->fetch();
    }
// Haversine公式计算两点之间的距离
    function calculateDistance($point1, $point2) {
        // 验证输入参数
        if (empty($point1) || empty($point2) || 
            !isset($point1['lat']) || !isset($point1['lng']) ||
            !isset($point2['lat']) || !isset($point2['lng'])) {
            return 0;
        }
        $lat1 = (float)$point1['lat'];
        $lng1 = (float)$point1['lng'];
        $lat2 = (float)$point2['lat'];
        $lng2 = (float)$point2['lng'];
        
        // 验证经纬度范围
        if ($lat1 < -90 || $lat1 > 90 || $lat2 < -90 || $lat2 > 90 ||
            $lng1 < -180 || $lng1 > 180 || $lng2 < -180 || $lng2 > 180 ||
            is_nan($lat1) || is_nan($lng1) || is_nan($lat2) || is_nan($lng2)) {
            return 0;
        }
        
        $earthRadius = 6371; // 地球半径，单位为公里

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lng1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lng2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos($latFrom) * cos($latTo) *
            sin($lonDelta / 2) * sin($lonDelta / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $c * $earthRadius;
        
        // 验证计算结果
        if (!is_numeric($distance) || is_nan($distance) || is_infinite($distance)) {
            return 0;
        }
        
        return $distance;
    }

    /**
     * 从地址字符串中提取省份
     * @param string $address 地址字符串
     * @return string 省份名称
     */
    private function extractProvinceFromAddress($address)
    {
        if (empty($address)) {
            return '';
        }
        
        // 常见省份名称列表（包含完整名称）
        $provinces = [
            '北京', '上海', '天津', '重庆',
            '河北', '山西', '内蒙古', '辽宁', '吉林', '黑龙江',
            '江苏', '浙江', '安徽', '福建', '江西', '山东', '河南', 
            '湖北', '湖南', '广东', '广西', '海南', 
            '四川', '贵州', '云南', '西藏', 
            '陕西', '甘肃', '青海', '宁夏', '新疆'
        ];
        
        // 先尝试从地址开头匹配省份名称
        foreach ($provinces as $province) {
            if (strpos($address, $province) === 0 || 
                strpos($address, $province . '省') === 0 ||
                strpos($address, $province . '市') === 0 ||
                strpos($address, $province . '自治区') === 0) {
                return $province;
            }
        }
        
        // 如果没匹配到，尝试正则匹配（支持"省"、"自治区"、"市"等）
        if (preg_match('/(.{2,4})(?:省|自治区|市)/u', $address, $matches)) {
            $province = trim($matches[1]);
            // 验证是否是有效省份
            if (in_array($province, $provinces)) {
                return $province;
            }
        }
        
        return '';
    }

    /**
     * 从地址字符串中提取城市（用于路线按城市筛选）
     * - 优先匹配“XX市”
     * - 兼容“地区/自治州/盟”等
     * - 直辖市（北京/上海/天津/重庆）直接返回
     * @param string $address
     * @return string
     */
    private function extractCityFromAddress($address)
    {
        $address = trim((string)$address);
        if ($address === '') {
            return '';
        }

        // 直辖市：北京/上海/天津/重庆
        $municipalities = ['北京', '上海', '天津', '重庆'];
        foreach ($municipalities as $m) {
            if (mb_strpos($address, $m . '市') === 0 || mb_strpos($address, $m) === 0) {
                return $m;
            }
        }

        // 如果包含“省”或“自治区”，先去掉前面的省份部分，只保留后面城市段
        $core = $address;
        if (preg_match('/^(.*?(?:省|自治区))(.*)$/u', $address, $m)) {
            $core = trim($m[2]);
        }
        // 形如“济南市历城区…”、“济南市高新区…”
        if (preg_match('/^([\x{4e00}-\x{9fa5}]{2,8}?)(?:市|地区|自治州|盟)/u', $core, $m2)) {
            return trim($m2[1]);
        }
        // 兜底：在整条地址里找第一个城市名
        if (preg_match('/([\x{4e00}-\x{9fa5}]{2,8}?)(?:市|地区|自治州|盟)/u', $address, $m3)) {
            return trim($m3[1]);
        }
        return '';
    }

    /**
     * @return void
     *
     * 查看路线（可修改）
     */
    public function logistics_detail()
    {
        $ids  = $this->request->param('ids');
        $order_id = Db::name('admin_order')->where('id',$ids)->value('order_id');
        $orderInfo = Db::name('order')->where('id',$order_id)->find();
        if (!$orderInfo) {
            $this->error('订单不存在');
        }
        $order_lng = Db::name('order')->where('id',$order_id)->field('loading,unload')->find();
        if (!$order_lng || empty($order_lng['loading']) || empty($order_lng['unload'])) {
            $this->error('订单地址信息不完整');
        }
        $loading = Db::name('user_address')->where('id',$order_lng['loading'])->find();
        $unload= Db::name('user_address')->where('id',$order_lng['unload'])->find();
        // 检查装货和卸货地址的经纬度是否有效
        if (empty($loading) || empty($loading['lng']) || empty($loading['lat'])) {
            $this->error('装货地址经纬度信息不完整');
        }
        if (empty($unload) || empty($unload['lng']) || empty($unload['lat'])) {
            $this->error('卸货地址经纬度信息不完整');
        }
        $load = [
            'lng' => (float)$loading['lng'],
            'lat' => (float)$loading['lat']
        ];
        $unlo = [
            'lng' => (float)$unload['lng'],
            'lat' => (float)$unload['lat']
        ];
        // 从装货地址和卸货地址中提取城市和省份（城市为空时回退按省份）
        $loadingCity = $this->extractCityFromAddress($loading['detailed_address'] ?? ($loading['address'] ?? ''));

        $unloadCity  = $this->extractCityFromAddress($unload['detailed_address'] ?? ($unload['address'] ?? ''));
        $loadingProvince = $this->extractProvinceFromAddress($loading['detailed_address'] ?? ($loading['address'] ?? ''));
        $unloadProvince  = $this->extractProvinceFromAddress($unload['detailed_address'] ?? ($unload['address'] ?? ''));
//        // 获取当前路线
        $logistics_id = Db::name('order')->where('id',$order_id)->value('logistics_id');
        $current_logistics = null;
        if ($logistics_id) {
            $current_logistics = Db::name('logistics')->where('id',$logistics_id)->find();
        }
        // 如果当前路线不存在，初始化为空数组，避免模板访问null值报错
        if (!$current_logistics) {
            $current_logistics = [];
        }
//        print_r($current_logistics);die;
        // 获取可选路线：发货/收货各方圆 300km 内，按价格排序（数值经纬度列走索引粗筛 + 球面半径精确）
        $radiusKm = 300;
        $radLat = $radiusKm / 6371.0; // 纬度弧度差 → 约 2.7°
        $radLatDeg = rad2deg($radLat);
        // 经度按纬度余弦修正宽度
        $cosLat = abs(cos(deg2rad($load['lat'])));
        $radLngDeg = $cosLat > 0.01 ? rad2deg($radLat / $cosLat) : $radLatDeg * 4;
        $loadLat = (float)$load['lat'];
        $loadLng = (float)$load['lng'];
        $unloLat = (float)$unlo['lat'];
        $unloLng = (float)$unlo['lng'];
        $rawSql = "status = 2 AND logistics_status = 1"
            . " AND shipping_lat_n BETWEEN " . ($loadLat - $radLatDeg) . " AND " . ($loadLat + $radLatDeg)
            . " AND shipping_lng_n BETWEEN " . ($loadLng - $radLngDeg) . " AND " . ($loadLng + $radLngDeg)
            . " AND arrival_lat_n BETWEEN " . ($unloLat - $radLatDeg) . " AND " . ($unloLat + $radLatDeg)
            . " AND arrival_lng_n BETWEEN " . ($unloLng - $radLngDeg) . " AND " . ($unloLng + $radLngDeg)
            . " AND 6371 * ACOS(LEAST(1, COS(RADIANS($loadLat))*COS(RADIANS(shipping_lat_n))*COS(RADIANS(shipping_lng_n)-RADIANS($loadLng))+SIN(RADIANS($loadLat))*SIN(RADIANS(shipping_lat_n)))) <= $radiusKm"
            . " AND 6371 * ACOS(LEAST(1, COS(RADIANS($unloLat))*COS(RADIANS(arrival_lat_n))*COS(RADIANS(arrival_lng_n)-RADIANS($unloLng))+SIN(RADIANS($unloLat))*SIN(RADIANS(arrival_lat_n)))) <= $radiusKm";
        $data = Db::name('logistics')->whereRaw($rawSql)->select();
        // ========== 预先准备计价相关数据 ==========
        $weight = isset($orderInfo['weight']) ? (float)$orderInfo['weight'] : 0;
        $volume = isset($orderInfo['direction']) ? (float)$orderInfo['direction'] : 0;
        $carType = Db::name('car_type')->where('id', $orderInfo['car_type_id'] ?? 0)->find();
        $carPrice = 0;
        if ($carType) {
            $carPrice = isset($carType['price']) ? (float)$carType['price'] : (isset($carType['Price']) ? (float)$carType['Price'] : 0);
        }
        $pickUpDriverFreight   = \think\Config::get('site.PickUpDriverFreight') ?: 0;
        $logisticsDriverFreight = \think\Config::get('site.LogisticsDriverFreight') ?: 0;
        $deliveryDriverFreight = \think\Config::get('site.DeliveryDriverFreight') ?: 0;
        $goodsTypePercentage = 0;
        if (!empty($orderInfo['goods_type_id'])) {
            $goodsType = Db::name('goods_type')->where('id', $orderInfo['goods_type_id'])->find();
            if ($goodsType && isset($goodsType['percentage']) && $goodsType['percentage'] > 0) {
                $goodsTypePercentage = (float)$goodsType['percentage'];
            }
        }

        // 计算每条数据的总距离和三端价格
        foreach ($data as $key => &$item) {
            // 检查shippingPoint的经纬度是否为空
            if (empty($item['shipping_longitude']) || empty($item['shipping_latitude'])) {
                unset($data[$key]);
                continue;
            }
            // 检查arrivalPoint的经纬度是否为空
            if (empty($item['arrival_longitude']) || empty($item['arrival_latitude'])) {
                unset($data[$key]);
                continue;
            }

//            // 按发货/到货城市过滤；如果城市为空则回退按省份过滤
//            if ($loadingCity || $unloadCity || $loadingProvince || $unloadProvince) {
//                $shippingCity     = trim($item['origincity'] ?? '');
//                $arrivalCity      = trim($item['destination'] ?? '');
//                $shippingProvince = trim($item['shipping_province'] ?? '');
//                $arrivalProvince  = trim($item['province'] ?? '');
//                // 发货侧匹配：优先按市匹配，市不存在或匹配失败时按省匹配
//                $shippingMatch = false;
//                if ($loadingCity) {
//                    if ($shippingCity && mb_strpos($shippingCity, $loadingCity) !== false) {
//                        $shippingMatch = true;
//                    } elseif ($loadingProvince && $shippingProvince && mb_strpos($shippingProvince, $loadingProvince) !== false) {
//                        $shippingMatch = true;
//                    }
//                } elseif ($loadingProvince) {
//                    if ($shippingProvince && mb_strpos($shippingProvince, $loadingProvince) !== false) {
//                        $shippingMatch = true;
//                    }
//                }
//
//                // 到货侧匹配：优先按市匹配，市不存在或匹配失败时按省匹配
//                $arrivalMatch = false;
//                if ($unloadCity) {
//                    if ($arrivalCity && mb_strpos($arrivalCity, $unloadCity) !== false) {
//                        $arrivalMatch = true;
//                    } elseif ($unloadProvince && $arrivalProvince && mb_strpos($arrivalProvince, $unloadProvince) !== false) {
//                        $arrivalMatch = true;
//                    }
//                } elseif ($unloadProvince) {
//                    if ($arrivalProvince && mb_strpos($arrivalProvince, $unloadProvince) !== false) {
//                        $arrivalMatch = true;
//                    }
//                }
//                // 如果两端都无法匹配，则过滤掉该路线
//                if (!$shippingMatch || !$arrivalMatch) {
//                    unset($data[$key]);
//                    continue;
//                }
//            }

            $shippingPoint = [
                'lng' =>  (float)$item['shipping_longitude'],
                'lat' => (float)$item['shipping_latitude']
            ];
            $arrivalPoint = [
                'lng' =>  (float)$item['arrival_longitude'],
                'lat' =>  (float)$item['arrival_latitude']
            ];
            // 计算总距离
            $distanceLoadToShipping = $this->calculateDistance($load, $shippingPoint);
            $distanceShippingToArrival = $this->calculateDistance($shippingPoint, $arrivalPoint);
            $distanceArrivalToUnlo = $this->calculateDistance($arrivalPoint, $unlo);
            $totalDistance = $distanceLoadToShipping + $distanceShippingToArrival + $distanceArrivalToUnlo;

            // 检查计算结果是否有效
            if (!is_numeric($totalDistance) || is_nan($totalDistance) || is_infinite($totalDistance)) {
                $totalDistance = 0;
            }

            // 保存原始数值用于排序
            $item['total_distance_value'] = $totalDistance;
            // 格式化后的字符串用于显示
            $item['total_distance'] = number_format($totalDistance, 2).'公里';

            // ========== 计算三端价格 ==========
            $perton  = isset($item['perton']) ? (float)$item['perton'] : 0;
            $side    = isset($item['side']) ? (float)$item['side'] : 0;
            $reflux  = isset($item['reflux']) ? (float)$item['reflux'] : 0;
            $bulky   = isset($item['bulky']) ? (float)$item['bulky'] : 0;

            $linePrice = 0;
            if (function_exists('calculatePrice')) {
                $priceResult = calculatePrice($weight, $volume, $perton, $side, $reflux, $bulky);
                $linePrice   = isset($priceResult['price']) ? (float)$priceResult['price'] : 0;
            }

            // 取货/送货司机基础费用（按距离 * 车型单价）
            $pickupDriverFee   = $distanceLoadToShipping * $carPrice;
            $shipmentDriverFee = $distanceArrivalToUnlo * $carPrice;

            // 管理端加成
            $logisticsCostBase    = $linePrice * (1 + $logisticsDriverFreight / 100);
            $pickupDriverFeeBase  = $pickupDriverFee * (1 + $pickUpDriverFreight / 100);
            $shipmentDriverFeeBase = $shipmentDriverFee * (1 + $deliveryDriverFreight / 100);

            // 货物类型上浮
            if ($goodsTypePercentage > 0) {
                $logisticsCost = $logisticsCostBase * (1 + $goodsTypePercentage / 100);
                $pickupFee     = $pickupDriverFeeBase * (1 + $goodsTypePercentage / 100);
                $shipmentFee   = $shipmentDriverFeeBase * (1 + $goodsTypePercentage / 100);
            } else {
                $logisticsCost = $logisticsCostBase;
                $pickupFee     = $pickupDriverFeeBase;
                $shipmentFee   = $shipmentDriverFeeBase;
            }

            $logisticsCost = round($logisticsCost, 2);
            $pickupFee     = round($pickupFee, 2);
            $shipmentFee   = round($shipmentFee, 2);
            $totalPrice    = $logisticsCost + $pickupFee + $shipmentFee;

            $item['logistics_cost_calc'] = $logisticsCost;
            $item['pickup_fee_calc']     = $pickupFee;
            $item['shipment_fee_calc']   = $shipmentFee;
            $item['total_price_value']   = $totalPrice;
        }
        // 排序：仅按三端总价格、总距离排序
        usort($data, function($a, $b) {
            // 先按三端总价格排序（越便宜越靠前）
            $priceCmp = ($a['total_price_value'] ?? PHP_FLOAT_MAX) <=> ($b['total_price_value'] ?? PHP_FLOAT_MAX);
            if ($priceCmp !== 0) {
                return $priceCmp;
            }
            // 价格相同时，再按总距离排序
            return ($a['total_distance_value'] ?? 0) <=> ($b['total_distance_value'] ?? 0);
        });

        // 表单字段名 logistics_id，实际为专线名称关键词（与模板「输入专线名称搜索」一致）
        $searchLogisticsname = $this->request->param('logistics_id', '');
        if ($searchLogisticsname !== '' && $searchLogisticsname !== null) {
            $searchLogisticsname = trim((string)$searchLogisticsname);
        } else {
            $searchLogisticsname = '';
        }
        // 有搜索词：在「全部已排序」的路线里按名称匹配，再最多展示 80 条（避免只在先截断的 80 条里搜导致排后面的专线永远搜不到）
        if ($searchLogisticsname !== '') {
            $data = array_values(array_filter($data, function ($item) use ($searchLogisticsname) {
                $shipping_logistics_name = isset($item['shipping_logistics_name']) ? (string)$item['shipping_logistics_name'] : '';
                return stripos($shipping_logistics_name, $searchLogisticsname) !== false;
            }));
        }
        $data = array_slice($data, 0, 80);
        // 加盟商线路账号：只展示排行前 20 条，保护总部专线数据
        $isFranchiseLine = (bool)FranchiseService::resolveFranchiseForAdmin((int)$this->auth->id);
        if ($isFranchiseLine) {
            $data = array_slice($data, 0, 20);
        }

        $lineOrder = Db::name('dricerorder')
            ->where('order_id', $orderInfo['orderid'])
            ->where('type', 2)
            ->find();
        $lineStatus = $lineOrder ? intval($lineOrder['status']) : 0;

        // 线路成本（订单表字段 logistics_cost）
        $logisticsCost = isset($orderInfo['logistics_cost']) ? floatval($orderInfo['logistics_cost']) : 0;
        $logisticsCostText = $logisticsCost > 0 ? number_format($logisticsCost, 2) : '';

        $this->view->assign("current_logistics", $current_logistics);
        $this->view->assign("row", $data);
        $this->view->assign("ids", $ids);
        $this->view->assign("order_number", $orderInfo['orderid'] ?? '');
        $this->view->assign("line_status", $lineStatus);
        $this->view->assign("logistics_cost", $logisticsCost);
        $this->view->assign("logistics_cost_text", $logisticsCostText);
        $this->view->assign("isFranchise", $isFranchiseLine ? 1 : 0);
        $this->view->assign("search_logistics_id", $searchLogisticsname);
        return $this->view->fetch();
    }

    /**
     * 子后台手动确认专线送达
     * @return Json
     */
    public function confirm_line_complete()
    {
        $orderid = $this->request->param('orderid');
        if (empty($orderid)) {
            $this->error('订单号不能为空');
        }
        $feeDeducted = (int)$this->request->param('fee_deducted', 0);
        $this->errorIfOrderRejectedByOrderNumber($orderid);

        $lineOrder = Db::name('dricerorder')
            ->where('order_id', $orderid)
            ->where('type', 2)
            ->find();
 
        if (!$lineOrder) {
            $this->error('未找到专线订单');
        }
        if (intval($lineOrder['status']) === 2) {
            $this->success('该专线已完成');
        }
//        if (empty($lineOrder['d_id'])){
//            $this->error('专线还未抢单，不能完成');
//        }
        $qulineOrder = Db::name('dricerorder')
            ->where('order_id', $orderid)
            ->where('type', 1)
            ->find();
        if(empty($qulineOrder)){
            $this->error('请先分配司机');
        }
        if ($qulineOrder['status'] == 1){
            $this->error('司机还没送到 ，不能完成');
        }
        // 专线未“开始运输”前禁止确认送达（开始运输在 confirm_delivery_complete 中将专线单 status 更新为 5）
        if (intval($lineOrder['status']) !== 5) {
            $this->error('专线还没开始运输,不能点送达');
        }
        $order = Db::name('order')->where('orderid', $orderid)->find();
        if (!$order) {
            $this->error('订单不存在');
        }
        $orderNumericId = (int)$order['id'];

        // 已扣专线费：立即从调度备用金扣除干线费用（每单仅扣一次，余额不足会中断操作）
        if ($feeDeducted === 1) {
            $this->deductLineFeeAfterSongDriverFilled($orderNumericId, '专线到达结算（已扣专线费）');
        }

        $result = Db::name('dricerorder')
            ->where('id', $lineOrder['id'])
            ->update(['status' => 2,'unsettime'=>time()]);
        if ($result === false) {
            $this->error('更新失败，请稍后再试');
        }

        // 记录轨迹：专线到达
        $logistics = Db::name('logistics')->where('id', $order['logistics_id'])->find();
        if ($logistics) {
            $centerName = $logistics['arrival_logistics_park'] ?? '';
            if ($centerName === '') {
                $centerName = $logistics['arrival_logistics_address'] ?? '';
            }
            $dispatch = $this->getAdminContactByRole($orderNumericId, 3);
            $dispatchMobile = $dispatch['mobile'] ?? '';
            if (empty($dispatch)) {
                $dispatch['name'] = '调度电话';
            }

            if ($centerName) {
                Db::name('trajectory')->insert([
                    'order_id' => $orderNumericId,
                    'admin_name' => $dispatch['name'] ?? '',
                    'admin_mobile' => $dispatchMobile,
                    'createtime' => time(),
                    'type' => '(' . $centerName . ')' . '已到达',
                ]);
                Db::name('order')->where('id', $orderNumericId)->update(['logistics_status' => 5]);
            }
        }
        $this->success('专线已标记完成');
    }
     public function confirm_delivery_complete(){

         $orderid = $this->request->param('orderid');

         if (empty($orderid)) {
             $this->error('订单号不能为空');
         }
         $this->errorIfOrderRejectedByOrderNumber($orderid);
         $lineOrder = Db::name('dricerorder')
             ->where('order_id', $orderid)
             ->where('type', 2)
             ->find();

         if (!$lineOrder) {
             $insertLineOrder = Db::name('dricerorder')->insert([
                 'order_id' => $orderid,
                 'type' => 2,
                 'status' => 1,
                 'createtime' => time(),
             ]);
             if ($insertLineOrder === false) {
                 $this->error('创建专线订单失败，请稍后再试');
             }
             $lineOrder = Db::name('dricerorder')
                 ->where('order_id', $orderid)
                 ->where('type', 2)
                 ->find();
             if (!$lineOrder) {
                 $this->error('创建专线订单失败，请稍后再试');
             }
         }
         $qulineOrder = Db::name('dricerorder')
             ->where('order_id', $orderid)
             ->where('type', 1)
             ->find();
         if (!$qulineOrder) {
             $this->error('还没有司机接单');
         }
         if ($qulineOrder['status'] == 1){
             $this->error('司机还没送到 ，不能开始');
         }
         $result = Db::name('dricerorder')
             ->where('id', $lineOrder['id'])
             ->update(['status' => 5,'unsettime'=>time()]);

         $results = Db::name('order')
             ->where('orderid', $lineOrder['order_id'])
             ->update(['logistics_status' => 4]);

         // 当天订单且物流未发车，点出发时预计到货时间推迟一天
         $orderRow = Db::name('order')->where('orderid', $orderid)->find();
         if ($orderRow && !empty($orderRow['createtime']) && empty($orderRow['estimated_arrival_time'])) {
             $todayStr = date('Y-m-d');
             if (date('Y-m-d', (int)$orderRow['createtime']) === $todayStr && !empty($orderRow['logistics_id'])) {
                 $lgInfo = Db::name('logistics')->where('id', $orderRow['logistics_id'])->find();
                 if ($lgInfo && !empty($lgInfo['time_limit'])) {
                     $baseEta = (int)$orderRow['createtime'] + (int)$lgInfo['time_limit'] * 86400;
                     Db::name('order')->where('orderid', $orderid)->update(['estimated_arrival_time' => $baseEta + 86400]);
                 }
             }
         }

         if ($result === false) {
             $this->error('更新失败，请稍后再试');
         }
         // 记录轨迹：专线到达
         $order = Db::name('order')->where('orderid', $orderid)->find();
         if ($order) {
             $orderNumericId = $order['id'];
             $logistics = Db::name('logistics')->where('id', $order['logistics_id'])->find();
             if ($logistics) {
                 $centerName = $logistics['arrival_logistics_park'] ?? '';
                 if ($centerName === '') {
                     $centerName = $logistics['arrival_logistics_address'] ?? '';
                 }
                 $dispatch = $this->getAdminContactByRole($orderNumericId, 3);
                 $dispatchMobile = $dispatch['mobile'] ?? '';
                 if (empty($dispatch)) {
                     $dispatch['name'] = '调度电话';
                 }
                 if ($centerName) {
                     Db::name('trajectory')->insert([
                         'order_id' => $orderNumericId,
                         'admin_name' => $dispatch['name'] ?? '',
                         'admin_mobile' => $dispatchMobile,
                         'createtime' => time(),
                         'type' => '运输中',
                     ]);
                 }
             }
         }
         $this->success('专线已标记开始');


     }
    /**
     * @return void
     * 填写物流成本页面
     */
    public function logistics_cost(){
        $ids  = $this->request->param('ids');
        $order_id = Db::name('admin_order')->where('id',$ids)->value('order_id');

        $logistics_cost = Db::name('order')->where('id',$order_id)->value('logistics_cost');
        $logistics_id = Db::name('order')->where('id',$order_id)->value('logistics_id');
        if ($logistics_id == 0){
            $this->error('请先规划路线');
        }
        $this->view->assign("row", $logistics_cost);
        return $this->view->fetch();
    }

    /**
     * @return void
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 填写物流成本
     */
    public function add_logistics_cost(){


        $data = $this->request->param('cost');
        $ids  = $this->request->param('ids');
        $this->errorIfOrderRejectedByAdminOrderId($ids);

        $order_id = Db::name('admin_order')->where('id',$ids)->value('order_id');

        // 路线成本会影响客户应付运费（pay_price），而 recalcCostCont() 里“开票税额”依赖 pay_price。
        // 若只更新 logistics_cost 而不更新 pay_price，后续重算 cost_cont 会出现开票税额口径不一致。
        $orderInfo = Db::name('order')
            ->where('id', $order_id)
            ->field('pay_price,logistics_cost,pickup_fee,shipment_fee,isinvoice,tax_point')
            ->find();

        if (!$orderInfo) {
            $this->error('订单不存在');
        }

        $newLogisticsCost = round((float)$data, 2);
        $oldLogisticsSum = (float)($orderInfo['logistics_cost'] ?? 0)
            + (float)($orderInfo['pickup_fee'] ?? 0)
            + (float)($orderInfo['shipment_fee'] ?? 0);
        $newLogisticsSum = $newLogisticsCost
            + (float)($orderInfo['pickup_fee'] ?? 0)
            + (float)($orderInfo['shipment_fee'] ?? 0);

        $currentPayPrice = (float)($orderInfo['pay_price'] ?? 0);
        $newPayPrice = round($currentPayPrice - $oldLogisticsSum + $newLogisticsSum, 2);

        $isinvoice = isset($orderInfo['isinvoice']) ? (int)$orderInfo['isinvoice'] : 0;
        $taxPointRaw = $orderInfo['tax_point'] ?? null;
        if ($isinvoice === 1 && $taxPointRaw !== '' && $taxPointRaw !== null) {
            $r = (float)str_replace('%', '', trim((string)$taxPointRaw));
            if ($r > 0) {
                $baseNoTax = $currentPayPrice / (1 + $r / 100);
                $baseNoTaxNew = $baseNoTax - $oldLogisticsSum + $newLogisticsSum;
                $taxAmount = round($baseNoTaxNew * ($r / 100), 2);
                $newPayPrice = round($baseNoTaxNew + $taxAmount, 2);
            }
        }

        $res= Db::name('order')->where('id',$order_id)->update([
            'logistics_cost' => $newLogisticsCost,
            'pay_price'       => $newPayPrice,
            'backend_status'  => 3
        ]);

        $ress= Db::name('admin_order')->where('id',$ids)->update(['status'=>1]);

        if ($res){
            $adminId = $this->auth->id;
            Commission::record($adminId, $order_id);
            $data['code']=1;
            return json($data);
        }else{
            $this->error('填写失败');
        }

    }

    /**
     * @return void
     *
     * 调度师填写司机信息
     */
    public function add_driver(){

        $params = $this->request->param();
        if (!empty($params['ids'])) {
            $this->errorIfOrderRejectedByAdminOrderId($params['ids']);
        }
        $order_id = Db::name('admin_order')->where('id',$params['ids'])->value('order_id');
        
        // 车牌号（取货司机）
        $carNumber = isset($params['car_number']) ? trim($params['car_number']) : '';

        // 货运平台（取货司机）1=运满满 2=货拉拉
        $dType1 = isset($params['d_type']) ? trim($params['d_type']) : '';

        // 获取用户输入的取货司机成本价格
        $pickup_driver_cost = isset($params['pickup_driver_cost']) ? floatval($params['pickup_driver_cost']) : null;
        
        $order = Db::name('order')->where('id',$order_id)->find();

        // 检查订单类型，只有"配车"类型才允许填写司机
        $find_car_type = $order['find_car_type'] ?? '';
//        if ($find_car_type !== '配车') {
//            $this->error('只有配车类型的订单才能填写司机信息');
//        }

        // 判断当前操作人是否加盟商：加盟商填写取货司机时不自动加税点
        $isFranchiseOperator = (bool)FranchiseService::resolveFranchiseForAdmin((int)$this->auth->id);

        // ========== 更新order表中的取货司机成本价格 ==========
        if ($pickup_driver_cost !== null && $pickup_driver_cost >= 0) {
            // 订单里的“司机金额”：运满满(1)自动加9%税点，货拉拉(2)保持原价；加盟商填写时不自动加税点
            $orderDriverFee = round($pickup_driver_cost * (($dType1 === '1' && !$isFranchiseOperator) ? 1.09 : 1), 2);
            Db::name('order')->where('id', $order_id)->update([
                'pickup_driver_fee' => $orderDriverFee
            ]);
            // 更新订单中的价格，以便后续使用
            $order['pickup_driver_fee'] = $orderDriverFee;
        } else {
            // 如果没有提供成本价格，使用订单中已有的价格
            $pickup_driver_cost = $order['pickup_driver_fee'] ?? 0;
        }

        // ========== 1. 处理取货司机账号（identity=2） ==========
        $pickupUserId = 0;
        if (!empty($params['pickup_driver_phone'])) {
            // 先按手机号+司机身份查询用户
            $pickupUser = Db::name('user')
                ->where('mobile', $params['pickup_driver_phone'])
                ->where('identity', 2)
                ->find();

            // ===== 暂时关闭：不再自动创建司机账号（代码保留，需要时取消注释） =====
            // if (!$pickupUser) {
            //     // 如果不存在，则自动注册一个司机账号
            //     $username = $params['pickup_driver_phone'];
            //     $password = md5(rand(9999999, 100000000000));
            //     $mobile = $params['pickup_driver_phone'];
            //     $extend = [
            //         'identity' => 2, // 司机身份
            //     ];
            //
            //     $auth = \app\common\library\Auth::instance();
            //     $registerResult = $auth->register($username, $password, '', $mobile, $extend);
            //     if (!$registerResult) {
            //         $this->error('自动注册取货司机失败：' . $auth->getError());
            //     }
            //
            //     // 重新查询获取用户ID
            //     $pickupUser = Db::name('user')
            //         ->where('mobile', $params['pickup_driver_phone'])
            //         ->where('identity', 2)
            //         ->find();
            // }

            $pickupUserId = $pickupUser['id'] ?? 0;
        }

        // 订单号（order表中的业务订单号，用于写入dricerorder.order_id）
        $orderNumber = $order['orderid'] ?? '';
        $pickupDriverName = $params['pickup_driver_name'] ?? '';
        $pickupDriverPhone = $params['pickup_driver_phone'] ?? '';

        // 只要有填写司机姓名/电话就写入司机单（不要求手机号已注册为司机账号，未匹配到则 d_id=0）
        if ($orderNumber && ($pickupDriverName !== '' || $pickupDriverPhone !== '')) {
            // 检查是否已经存在该订单的取货司机单
            $existingPickupOrder = Db::name('dricerorder')
                ->where('order_id', $orderNumber)
                ->where('type', 1)
                ->find();
 
            $pickupData = [
                'd_id'         => $pickupUserId,
                'driver_name'  => $pickupDriverName,
                'driver_mobile'=> $pickupDriverPhone,
                'car_number'   => $carNumber,
                'order_id'     => $orderNumber,
                'type'         => 1, // 取货司机
                'd_type'      => $dType1, // 货运平台 1=运满满 2=货拉拉
                'price'        => round($pickup_driver_cost, 2),
                'createtime'   => time(),
                'grabbingtime' => time(), // 添加抢单时间，模拟司机抢单
                'status'       => 1, // 已指派/进行中
                'payment_screenshot' => $params['payment_screenshot'] ?? '',
            ];
            // 删除错误的更新：不应该更新shipment_driver_fee，已经在上面正确更新了pickup_driver_fee
            if ($existingPickupOrder) { 
                // 已存在时只更新司机信息和价格
                $updateData = [
                    'd_id'         => $pickupData['d_id'],
                    'driver_name'  => $pickupData['driver_name'],
                    'driver_mobile'=> $pickupData['driver_mobile'],
                    'car_number'   => $pickupData['car_number'],
                    'd_type'      => $dType1,
                    'price'        => $pickupData['price'],
                    'grabbingtime' => time(), // 更新抢单时间
                ];
                if (isset($params['payment_screenshot'])) {
                    $updateData['payment_screenshot'] = $params['payment_screenshot'];
                }
                Db::name('dricerorder')
                    ->where('id', $existingPickupOrder['id'])
                    ->update($updateData);
            } else {
                Db::name('dricerorder')->insert($pickupData);
            }
            
            // 写入物流轨迹（模拟司机抢单操作）
            $dispatch = $this->getAdminContactByRole($order_id, 2);
            $dispatchMobile = $dispatch['mobile'] ?? '';
            if ($dispatchMobile !== '') {
                Db::name('trajectory')->insert([
                    'order_id' => $order_id,
                    'admin_name' => ($dispatch['name'] ?? '调度') . $dispatchMobile,
                    'admin_mobile' => $dispatchMobile,
                    'createtime' => time(),
                    'type' => '已发货',
                ]);
                Db::name('order')->where('id', $order_id)->update(['logistics_status'=>2]);

            }
        }

        // 根据最新的司机成本费用重新计算并更新订单总成本
        $this->updateOrderCostCont($order_id);

        $adminId = $this->auth->id;
//        Commission::record($adminId, $order_id);
        $this->success('填写成功');
    }
    public function add_songdriver(){

        $params = $this->request->param();
        if (!empty($params['ids'])) {
            $this->errorIfOrderRejectedByAdminOrderId($params['ids']);
        }
        $order_id = Db::name('admin_order')->where('id',$params['ids'])->value('order_id');

        // 车牌号（送货司机）
        $carNumber = isset($params['car_number']) ? trim($params['car_number']) : '';

        // 货运平台（送货司机）1=运满满 2=货拉拉
        $dType1 = isset($params['d_type']) ? trim($params['d_type']) : '';

        // 获取用户输入的送货司机成本价格
        $delivery_driver_cost = isset($params['delivery_driver_cost']) ? floatval($params['delivery_driver_cost']) : null;
        
        $order = Db::name('order')->where('id',$order_id)->find();

        // 检查订单类型，只有"配车"类型才允许填写司机
        $find_car_type = $order['find_car_type'] ?? '';
        if ($find_car_type !== '配车') {
            $this->error('只有配车类型的订单才能填写司机信息');
        }

        // 判断当前操作人是否加盟商：加盟商填写送货司机时不自动加税点
        $isFranchiseOperator = (bool)FranchiseService::resolveFranchiseForAdmin((int)$this->auth->id);

        // ========== 更新order表中的送货司机成本价格 ==========
        if ($delivery_driver_cost !== null && $delivery_driver_cost >= 0) {
            // 订单里的“司机金额”：运满满(1)自动加9%税点，货拉拉(2)保持原价；加盟商填写时不自动加税点
            $orderDriverFee = round($delivery_driver_cost * (($dType1 === '1' && !$isFranchiseOperator) ? 1.09 : 1), 2);
            Db::name('order')->where('id', $order_id)->update([
                'shipment_driver_fee' => $orderDriverFee
            ]);
            // 更新订单中的价格，以便后续使用
            $order['shipment_driver_fee'] = $orderDriverFee;
        } else {
            // 如果没有提供成本价格，使用订单中已有的价格
            $delivery_driver_cost = $order['shipment_driver_fee'] ?? 0;
        }

        // ========== 1. 处理送货司机账号（identity=2） ==========
        $deliveryUserId = 0;
        if (!empty($params['delivery_driver_phone'])) {
            $deliveryUser = Db::name('user')
                ->where('mobile', $params['delivery_driver_phone'])
                ->where('identity', 2)
                ->find();

            // ===== 暂时关闭：不再自动创建司机账号（代码保留，需要时取消注释） =====
            // if (!$deliveryUser) {
            //     // 自动注册送货司机
            //     $username = $params['delivery_driver_phone'];
            //     $password = md5(rand(9999999, 100000000000));
            //     $mobile   = $params['delivery_driver_phone'];
            //     $extend   = [
            //         'identity' => 2, // 司机身份
            //     ];
            //
            //     $auth = \app\common\library\Auth::instance();
            //     $registerResult = $auth->register($username, $password, '', $mobile, $extend);
            //
            //     if (!$registerResult) {
            //         $this->error('自动注册送货司机失败：' . $auth->getError());
            //     }
            //
            //     $deliveryUser = Db::name('user')
            //         ->where('mobile', $params['delivery_driver_phone'])
            //         ->where('identity', 2)
            //         ->find();
            // }

            $deliveryUserId = $deliveryUser['id'] ?? 0;
        }

        // 订单号（order表中的业务订单号，用于写入dricerorder.order_id）
        $orderNumber = $order['orderid'] ?? '';

        // 专线未确认送达前，禁止填写送货司机（配车订单）
        if ($orderNumber) {
            $lineOrder = Db::name('dricerorder')
                ->where('order_id', $orderNumber)
                ->where('type', 2)
                ->find();
            // 约定：专线送达后 status=2；开始运输时 status=5
            if (!$lineOrder || intval($lineOrder['status']) !== 2) {
                $this->error('专线未确认送达，不能填写送货司机');
            }
        }

        // ========== 2. 创建/更新送货司机订单（dricerorder，type=3） ==========
        if ($deliveryUserId && $orderNumber) {
            // 干线费扣费：填写完送货司机后才扣，每单仅扣一次（余额不足会在此报错终止）
            $this->deductLineFeeAfterSongDriverFilled($order_id);

            $existingDeliveryOrder = Db::name('dricerorder')
                ->where('order_id', $orderNumber)
                ->where('type', 3)
                ->find();

            $deliveryData = [
                'd_id'          => $deliveryUserId,
                'driver_name'   => $params['delivery_driver_name'] ?? '',
                'driver_mobile' => $params['delivery_driver_phone'] ?? '',
                'car_number'    => $carNumber,
                'order_id'      => $orderNumber,
                'type'          => 3, // 送货司机
                'd_type'       => $dType1, // 货运平台 1=运满满 2=货拉拉
                'price'         => round($delivery_driver_cost, 2),
                'createtime'    => time(),
                'grabbingtime'  => time(), // 添加抢单时间，模拟司机抢单
                'status'        => 1, // 已指派/进行中
                'payment_screenshot' => $params['payment_screenshot'] ?? '',
            ];
            // 删除重复的更新：已经在上面正确更新了shipment_driver_fee
            if ($existingDeliveryOrder) {
                // 已存在时只更新司机信息和价格
                $updateData = [
                    'd_id'          => $deliveryData['d_id'],
                    'driver_name'   => $deliveryData['driver_name'],
                    'driver_mobile' => $deliveryData['driver_mobile'],
                    'car_number'    => $deliveryData['car_number'],
                    'd_type'       => $dType1,
                    'price'         => $deliveryData['price'],
                    'grabbingtime'  => time(), // 更新抢单时间
                ];
                if (isset($params['payment_screenshot'])) {
                    $updateData['payment_screenshot'] = $params['payment_screenshot'];
                }
                Db::name('dricerorder')
                    ->where('id', $existingDeliveryOrder['id'])
                    ->update($updateData);
            } else {
                // 不存在则新建一条送货司机订单
                Db::name('dricerorder')->insert($deliveryData);
            }
            
            // 写入物流轨迹（模拟司机抢单操作）
            $dispatch = $this->getAdminContactByRole($order_id, 3);
            $dispatchMobile = $dispatch['mobile'] ?? '';
            if ($dispatchMobile !== '') {
                Db::name('trajectory')->insert([
                    'order_id' => $order_id,
                    'admin_name' => ($dispatch['name'] ?? '调度') . $dispatchMobile,
                    'admin_mobile' => $dispatchMobile,
                    'createtime' => time(),
                    'type' => '派件中',
                ]);
                Db::name('order')->where('id', $order_id)->update(['logistics_status'=>6]);
            }
        }

        // 根据最新的司机成本费用重新计算并更新订单总成本
        $this->updateOrderCostCont($order_id);

        $this->success('填写成功');
    }
    /**
     * @return void
     * 填写司机成本
     */
    public function driver_price()
    {

        $ids  = $this->request->param('ids');
        $order_id = Db::name('admin_order')->where('id',$ids)->value('order_id');

        // 获取订单中的金额（从订单表）
        $order = Db::name('order')->where('id',$order_id)->find();

        // 检查订单类型，只有"配车"类型才允许填写司机
        $find_car_type = $order['find_car_type'] ?? '';
//        if ($find_car_type !== '配车') {
//            $this->error('只有配车类型的订单才能填写司机信息');
//        }

        // 获取已保存的司机信息（从dricerorder表）
        $driverdata = Db::name('dricerorder')->where('order_id', $order['orderid'])->select();
        $pickupRecord = Db::name('dricerorder')
            ->where('order_id', $order['orderid'])
            ->where('type', 1)
            ->find();
        $deliveryrequirements_id = $order['deliveryrequirements_id'] ?? '';
//        print_r($deliveryrequirements_id);die;
        if (strpos($deliveryrequirements_id, '2') !== false) {
            $order_ments_id = 1;
        } else {
            $order_ments_id = 0;
        }
//        print_r($order_ments_id);die;
        $pickupStatus = $pickupRecord ? (int)$pickupRecord['status'] : 0;
        // 组织数据：司机信息从dricerorder表获取，金额从order表获取
        if ($driverdata){
            $data = [
                'pickup_driver_name'=>$driverdata[0]['driver_name'] ?? '',
                'pickup_driver_phone'=>$driverdata[0]['driver_mobile'] ?? '',
                'car_number'=>$driverdata[0]['car_number'] ?? '',
                'pickup_driver_price'=>$order['pickup_driver_fee'] ?? 0, // 使用订单中的金额
                'payment_screenshot'=>$pickupRecord['payment_screenshot'] ?? '',
                'pickup_d_type'=>$pickupRecord['d_type'] ?? '',
            ];
        }else{
            $data = [
                'pickup_driver_name'=>'',
                'pickup_driver_phone'=>'',
                'car_number'=>'',
                'pickup_driver_price'=>$order['pickup_driver_fee'] ?? 0, // 使用订单中的金额
                'payment_screenshot'=>'',
                'pickup_d_type'=>'',
                'delivery_driver_name'=>'',
                'delivery_driver_phone'=>'',
                'delivery_driver_price'=>$order['shipment_driver_fee'] ?? 0, // 使用订单中的金额
                'dedicate_driver_name'=>'',
                'dedicate_driver_phone'=>'',
                'dedicate_driver_price'=>$order['logistics_driver_cost'] ?? 0, // 使用订单中的金额
            ];
        }
        $this->view->assign("row", $data);
        $this->view->assign("order_ments_id", $order_ments_id);
        $this->view->assign("find_car_type", $find_car_type);
        $this->view->assign("order_number", $order['orderid'] ?? '');
        $this->view->assign("pickup_status", intval($pickupStatus));
        return $this->view->fetch();
    }
    /**
     * @return void
     * 填写送货司机成本
     */
    public function songdriver_price()
    {
        $ids  = $this->request->param('ids');
        $order_id = Db::name('admin_order')->where('id',$ids)->value('order_id');

        // 获取订单中的金额（从订单表）
        $order = Db::name('order')->where('id',$order_id)->find();

        // 检查订单类型，只有"配车"类型才允许填写司机
        $find_car_type = $order['find_car_type'] ?? '';
        if ($find_car_type !== '配车') {
            $this->error('只有配车类型的订单才能填写司机信息');
        }

        // 获取已保存的司机信息（从dricerorder表）
        $driverdata = Db::name('dricerorder')->where('order_id', $order['orderid'])->select();
        $deliveryRecord = Db::name('dricerorder')
            ->where('order_id', $order['orderid'])
            ->where('type', 3)
            ->find();
        $deliver = Db::name('dricerorder')
            ->where('order_id', $order['orderid'])
            ->where('type', 2)
            ->find();
        if ($deliver){

        }
        // 取货司机记录，用于读取deliveryrequirements_id（判断是否需要显示车牌号）
        $pickupRecord = Db::name('dricerorder')
            ->where('order_id', $order['orderid'])
            ->where('type', 1)
            ->find();
        $deliveryrequirements_id = $pickupRecord['deliveryrequirements_id'] ?? '';
        if (strpos($deliveryrequirements_id, '2') !== false) {
            $order_ments_id = 1;
        } else {
            $order_ments_id = 0;
        }

        $deliveryStatus = $deliveryRecord ? (int)$deliveryRecord['status'] : 0;

        // 组织数据：司机信息从dricerorder表获取，金额从order表获取
        if ($driverdata){
            $data = [
                'pickup_driver_name'=>$driverdata[0]['driver_name'] ?? '',
                'pickup_driver_phone'=>$driverdata[0]['driver_mobile'] ?? '',
                'pickup_driver_price'=>$order['pickup_driver_fee'] ?? 0, // 使用订单中的金额
                'dedicate_driver_name'=>$driverdata[1]['driver_name'] ?? '',
                'dedicate_driver_phone'=>$driverdata[1]['driver_mobile'] ?? '',
                'dedicate_driver_price'=>$order['logistics_driver_cost'] ?? 0, // 使用订单中的金额
                'delivery_driver_name'=>$driverdata[2]['driver_name'] ?? '',
                'delivery_driver_phone'=>$driverdata[2]['driver_mobile'] ?? '',
                'delivery_driver_price'=>$order['shipment_driver_fee'] ?? 0, // 使用订单中的金额
                'car_number'=>$deliveryRecord['car_number'] ?? '',
                'payment_screenshot'=>$deliveryRecord['payment_screenshot'] ?? '',
                'delivery_d_type'=>$deliveryRecord['d_type'] ?? '',
            ];
        }else{
            $data = [
                'pickup_driver_name'=>'',
                'pickup_driver_phone'=>'',
                'pickup_driver_price'=>$order['pickup_driver_fee'] ?? 0, // 使用订单中的金额
                'delivery_driver_name'=>'',
                'delivery_driver_phone'=>'',
                'delivery_driver_price'=>$order['shipment_driver_fee'] ?? 0, // 使用订单中的金额
                'dedicate_driver_name'=>'',
                'dedicate_driver_phone'=>'',
                'dedicate_driver_price'=>$order['logistics_driver_cost'] ?? 0, // 使用订单中的金额
                'car_number'=>'',
                'payment_screenshot'=>'',
                'delivery_d_type'=>'',
            ];
        }
        $this->view->assign("row", $data);
        $this->view->assign("order_ments_id", $order_ments_id);
        $this->view->assign("find_car_type", $find_car_type);
        $this->view->assign("order_number", $order['orderid'] ?? '');
        $this->view->assign("delivery_status", $deliveryStatus);
        return $this->view->fetch();
    }

    /**
     * 送货司机额外成本页面
     */
    public function songdriver_other_price()
    {
        $ids = $this->request->param('ids');
        if (!$ids) {
            $this->error('参数错误');
        }
        $orderId = Db::name('admin_order')->where('id', $ids)->value('order_id');
        if (!$orderId) {
            $this->error('订单不存在');
        }
        $order = Db::name('order')->where('id', $orderId)->field('orderid')->find();
        if (!$order) {
            $this->error('订单不存在');
        }
        $extraList = Db::name('dirverother')
            ->where('type',2)
            ->where('order_id', $order['orderid'])
            ->order('id', 'desc')
            ->select();

        $deliveryStatus = Db::name('dricerorder')
            ->where('order_id', $order['orderid'])
            ->where('type', 3)
            ->value('status');

        $this->view->assign('orderNumber', $order['orderid']);
        $this->view->assign('type', 2);
        $this->view->assign('extraList', $extraList);
        $this->view->assign('driver_status', intval($deliveryStatus));
        return $this->view->fetch();
    }
    /**
     * 取货司机额外成本页面
     */
    public function driver_other_price()
    {
        $ids = $this->request->param('ids');
        if (!$ids) {
            $this->error('参数错误');
        }
        $orderId = Db::name('admin_order')->where('id', $ids)->value('order_id');
        if (!$orderId) {
            $this->error('订单不存在');
        }
        $order = Db::name('order')->where('id', $orderId)->field('orderid')->find();
        if (!$order) {
            $this->error('订单不存在');
        }
        $extraList = Db::name('dirverother')
            ->where('type',1)
            ->where('order_id', $order['orderid'])
            ->order('id', 'desc')
            ->select();

        $pickupStatus = Db::name('dricerorder')
            ->where('order_id', $order['orderid'])
            ->where('type', 1)
            ->value('status');

        $this->view->assign('orderNumber', $order['orderid']);
        $this->view->assign('driver_status', intval($pickupStatus));
        $this->view->assign('type', 1);
        $this->view->assign('extraList', $extraList);
        return $this->view->fetch();
    }
    /**
     * 取货订单额外成本页面
     */
    public function order_other_price()
    {
        $ids = $this->request->param('ids');
        if (!$ids) {
            $this->error('参数错误');
        }
        $orderId = Db::name('admin_order')->where('id', $ids)->value('order_id');
        if (!$orderId) {
            $this->error('订单不存在');
        }
        $order = Db::name('order')->where('id', $orderId)->field('orderid')->find();
        if (!$order) {
            $this->error('订单不存在');
        }
        $extraList = Db::name('order_extra_price')
             ->where('order_id', $order['orderid'])
            ->order('id', 'desc')
            ->select();

        $this->view->assign('orderNumber', $order['orderid']);
        $this->view->assign('extraList', $extraList);
        return $this->view->fetch();
    }

    /**
     * 物流额外成本页面（数据保存到 logistics_extra_price 表）
     */
    public function logistics_extra_price()
    {
        $ids = $this->request->param('ids');
        if (!$ids) {
            $this->error('参数错误');
        }
        $orderId = Db::name('admin_order')->where('id', $ids)->value('order_id');
        if (!$orderId) {
            $this->error('订单不存在');
        }
        $order = Db::name('order')->where('id', $orderId)->field('orderid')->find();
        if (!$order) {
            $this->error('订单不存在');
        }
        $extraList = Db::name('logistics_extra_price')
            ->where('order_id', $order['orderid'])
            ->order('id', 'desc')
            ->select();

        $this->view->assign('orderNumber', $order['orderid']);
        $this->view->assign('extraList', $extraList);
        return $this->view->fetch();
    }

    /**
     * 订单其他价格页面（cost_extra_price 表，填写后更新 order.cost_cont）
     */
    public function cost_extra_price()
    {
        $ids = $this->request->param('ids');
        if (!$ids) {
            $this->error('参数错误');
        }
        $orderId = Db::name('admin_order')->where('id', $ids)->value('order_id');
        if (!$orderId) {
            $this->error('订单不存在');
        }
        $order = Db::name('order')->where('id', $orderId)->field('orderid')->find();
        if (!$order) {
            $this->error('订单不存在');
        }
        $extraList = Db::name('cost_extra_price')
            ->where('order_id', $order['orderid'])
            ->order('id', 'desc')
            ->select();

        $this->view->assign('orderNumber', $order['orderid']);
        $this->view->assign('extraList', $extraList);
        return $this->view->fetch();
    }

    /**
     * 手动确认司机完成（type=1 取货，type=3 送货）
     * @return Json
     */
    public function confirm_driver_complete()
    {
        $orderid = $this->request->param('orderid');
        $type = intval($this->request->param('type'));

        if (empty($orderid)) {
            $this->error('订单号不能为空');
        }
        if (!in_array($type, [1, 3])) {
            $this->error('司机类型错误');
        }
        $this->errorIfOrderRejectedByOrderNumber($orderid);

        $order = Db::name('order')->where('orderid', $orderid)->find();
        if (!$order) {
            $this->error('订单不存在');
        }
        // 加盟商订单钱包扣费：送货司机完成(type=3)时按总运费×比例扣加盟商钱包
        if ($type === 3) {
            $settle = FranchiseService::settleOrderFranchiseFee((int)$order['id']);
            if (!$settle['success']) {
                $this->error($settle['msg']);
            }
        }

        $driverOrder = Db::name('dricerorder')
            ->where('order_id', $orderid)
            ->where('type', $type)
            ->find();
        if (!$driverOrder) {
            $this->error('未找到对应司机订单');
        }
        if (intval($driverOrder['status']) === 2) {
            $this->success('该司机已完成');
        }

        $settleAmount = isset($driverOrder['price']) ? round(floatval($driverOrder['price']), 2) : 0;
        // 司机完成结算时把本段额外费用一起计入：填额外费时不扣，完成时一并扣（基础费+额外费）
        $extraType = $type === 1 ? 1 : 2;
        $settleAmount = round($settleAmount + (float)Db::name('dirverother')->where('order_id', $orderid)->where('type', $extraType)->sum('price'), 2);
        $driverUserId = isset($driverOrder['d_id']) ? intval($driverOrder['d_id']) : 0;
        if ($settleAmount < 0) {
            $this->error('司机结算金额不能为负数');
        }

        // 非月结标记“送货”后不支付送货司机费用；月结标记“送货”后，送货完成时扣除利润。
        $skipDeliverySettlement = $type === 3
            && (int)($order['is_urgent'] ?? 0) === 1
            && (int)($order['pay_type'] ?? 0) !== 2;
        if ($skipDeliverySettlement) {
            $settleAmount = 0;
        }

        $profitSettlement = $type === 3
            && (int)($order['is_urgent'] ?? 0) === 1
            && (int)($order['pay_type'] ?? 0) === 2;
        $profitAmount = $profitSettlement
            ? max(0, round((float)($order['pay_price'] ?? 0) - (float)($order['cost_cont'] ?? 0), 2))
            : 0;
        $totalSettlement = round($settleAmount + $profitAmount, 2);

        $dispatchAdminId = $this->getDispatchAdminIdByOrderNumber((int)$order['id']);
        if ($dispatchAdminId <= 0) {
            $this->error('未找到对应调度，无法扣减备用金');
        }

        // admin_name 不能为空：优先取当前管理员昵称/用户名，兜底“后台管理员”
        $adminInfo = $this->auth ? $this->auth->getUserInfo() : null;
        $adminName = $adminInfo
            ? (($adminInfo['nickname'] ?? '') ?: (($adminInfo['username'] ?? '') ?: '后台管理员'))
            : '后台管理员';

        Db::startTrans();
        try {
            $reserveFund = Db::name('dispatch_reserve_fund')->lock(true)->where('admin_id', $dispatchAdminId)->find();
            if (!$reserveFund) {
                $reserveFund = [
                    'admin_id' => $dispatchAdminId,
                    'balance' => 0,
                    'total_recharge' => 0,
                    'total_deduct' => 0,
                ];
            }
            $currentBalance = isset($reserveFund['balance']) ? round((float)$reserveFund['balance'], 2) : 0;
            if ($currentBalance < $totalSettlement) {
                throw new \Exception('备用金余额不足，无法付款');
            }

            $updated = Db::name('dricerorder')
                ->where('id', $driverOrder['id'])
                ->update(['status' => 2, 'unsettime' => time()]);
            if ($updated === false) {
                throw new \Exception('更新司机状态失败');
            }

            $afterBalance = round($currentBalance - $totalSettlement, 2);
            if ($totalSettlement > 0) {
                $fundUpdate = Db::name('dispatch_reserve_fund')
                    ->where('admin_id', $dispatchAdminId)
                    ->update([
                        'balance' => $afterBalance,
                        'total_deduct' => Db::raw('total_deduct+' . $totalSettlement),
                        'updatetime' => time(),
                    ]);
                if ($fundUpdate === false) {
                    throw new \Exception('更新备用金失败');
                }

                if ($settleAmount > 0) {
                    $logInsert = Db::name('dispatch_reserve_fund_log')->insert([
                        'admin_id' => $dispatchAdminId,
                        'order_id' => (int)$order['id'],
                        'order_number' => $orderid,
                        'driver_order_id' => (int)$driverOrder['id'],
                        'type' => $type,
                        'amount' => $settleAmount,
                        'direction' => 'deduct',
                        'balance_before' => $currentBalance,
                        'balance_after' => round($currentBalance - $settleAmount, 2),
                        'remark' => $type === 1
                            ? '取货司机完成订单结算'
                            : ($skipDeliverySettlement ? '非月结送货完成（未扣除干线费和送货费）' : ($profitSettlement ? '月结送货完成（送货费结算）' : '送货司机完成订单结算')),
                        'admin_name' => $adminName,
                        'createtime' => time(),
                    ]);
                    if ($logInsert === false) {
                        throw new \Exception('记录备用金流水失败');
                    }
                }

                if ($profitAmount > 0) {
                    $profitLog = Db::name('dispatch_reserve_fund_log')->insert([
                        'admin_id' => $dispatchAdminId,
                        'order_id' => (int)$order['id'],
                        'order_number' => $orderid,
                        'driver_order_id' => (int)$driverOrder['id'],
                        'type' => 4,
                        'amount' => $profitAmount,
                        'direction' => 'deduct',
                        'balance_before' => round($currentBalance - $settleAmount, 2),
                        'balance_after' => $afterBalance,
                        'remark' => '月结送货完成，扣除订单利润',
                        'admin_name' => $adminName,
                        'createtime' => time(),
                    ]);
                    if ($profitLog === false) {
                        throw new \Exception('记录利润扣款流水失败');
                    }
                }
            }

            if ($driverUserId > 0 && $settleAmount > 0) {
                $memo = $type === 1 ? '取货司机完成订单结算' : '送货司机完成订单结算';
                User::money($settleAmount, $driverUserId, $memo);
            }

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        // 记录轨迹：参考前台司机确认逻辑
        $order = Db::name('order')->where('orderid', $orderid)->find();
        if ($order) {
            $orderNumericId = $order['id'];
            if ($type === 1) {
                $logistics = Db::name('logistics')->where('id', $order['logistics_id'])->find();
                if ($logistics) {
                    $customer = $this->getAdminContactByRole($orderNumericId, 3);
                    $customerMobile = $customer['mobile'] ?? '';

                    $centerName = $logistics['shipping_logistics_name'] ?? '';
                    if ($centerName === '' && isset($logistics['shipping_logistics_address'])) {
                        $centerName = $logistics['shipping_logistics_address'];
                    }

                    if ($centerName !== '' && $customerMobile !== '') {
                        Db::name('trajectory')->insert([
                            'order_id' => $orderNumericId,
                            'admin_name' => ($customer['name'] ?? '') . '电话' . $customerMobile,
                            'admin_mobile' => $customerMobile,
                            'createtime' => time(),
                            'type' => '(' . $centerName . ')' . '已收入',
                        ]);
                        Db::name('order')->where('id', $orderNumericId)->update(['logistics_status' => 3]);

//                        Db::name('trajectory')->insert([
//                            'order_id' => $orderNumericId,
//                            'admin_name' => ($customer['name'] ?? '') . '电话' . $customerMobile,
//                            'admin_mobile' => $customerMobile,
//                            'createtime' => time(),
//                            'type' => '运输中',
//                        ]);
                    }
                }
            } elseif ($type === 3) {
                $dispatch = $this->getAdminContactByRole($orderNumericId, 3);
                if (empty($dispatch)) {
                    $dispatch['name'] = '调度电话';
                }
                $dispatchMobile = $dispatch['mobile'] ?? '';

                    Db::name('trajectory')->insert([
                        'order_id' => $orderNumericId,
                        'admin_name' => ($dispatch['name'] ?? '') . $dispatchMobile,
                        'admin_mobile' => $dispatchMobile,
                        'createtime' => time(),
                        'type' => '已收货',
                    ]);
                    Db::name('order')->where('id', $orderNumericId)->update(['logistics_status' => 7]);
            }
        }
        if ($skipDeliverySettlement) {
            $this->success('送货已完成，非月结订单未扣除干线费和送货费');
        }
        if ($profitSettlement) {
            $this->success('送货已完成，已结算送货费并扣除订单利润');
        }
        $this->success('已标记为完成');
    }

    /**
     * 根据订单ID和身份ID获取后台管理员联系方式
     *
     * @param int $orderId order表主键ID
     * @param int $identityId 身份ID：2=线路/规划，3=调度
     * @return array|null
     */
    private function getAdminContactByRole($orderId, $identityId)
    {
        if (empty($orderId) || empty($identityId)) {
            return null;
        }

        $adminOrders = Db::name('admin_order')->where('order_id', $orderId)->select();
        if (!$adminOrders) {
            return null;
        }

        foreach ($adminOrders as $ao) {
            $adminId = $ao['admin_id'] ?? 0;
            if (!$adminId) {
                continue;
            }

            $groupId = Db::name('auth_group_access')->where('uid', $adminId)->value('group_id');
            if (!$groupId) {
                continue;
            }

            $group = Db::name('auth_group')->where('id', $groupId)->find();
            if (!$group) {
                continue;
            }
            $groupIdentity = isset($group['identity']) ? intval($group['identity']) : 0;
            if ($groupIdentity === intval($identityId)) {
                $mobile = Db::name('admin')->where('id', $adminId)->value('mobile');
                return [
                    'name' => $group['name'] ?? '',
                    'mobile' => $mobile ?: '',
                ];
            }
        }

        return null;
    }

    /**
     * 重新计算总成本 cost_cont（实现见 OrderModifyApplier::recalcCostCont）
     *
     * @param int $orderId order表主键ID
     */
    private function updateOrderCostCont($orderId)
    {
        $orderId = intval($orderId);
        if ($orderId <= 0) {
            return;
        }
        OrderModifyApplier::recalcCostCont($orderId);
    }

    /**
     * 根据订单号获取备用金管理员ID：专车/小票快运走线路(identity=2)，配车走调度(identity=3)
     */
    private function getDispatchAdminIdByOrderNumber($orderId)
    {
        $orderId = (int)$orderId;
        if ($orderId <= 0) {
            return 0;
        }
        $order = Db::name('order')->where('id', $orderId)->field('find_car_type')->find();
        if (!$order) {
            return 0;
        }
        $targetIdentity = in_array((string)($order['find_car_type'] ?? ''), ['专车', '小票快运'], true) ? 2 : 3;
        $adminOrders = Db::name('admin_order')->where('order_id', $orderId)->order('id desc')->select();
        if (!$adminOrders) {
            return 0;
        }
        foreach ($adminOrders as $row) {
            $adminId = isset($row['admin_id']) ? (int)$row['admin_id'] : 0;
            if ($adminId <= 0) {
                continue;
            }
            $groupId = Db::name('auth_group_access')->where('uid', $adminId)->value('group_id');
            if (!$groupId) {
                continue;
            }
            $identity = Db::name('auth_group')->where('id', $groupId)->value('identity');
            if ((int)$identity === $targetIdentity) {
                return $adminId;
            }
        }
        return 0;
    }

    /**
     * 干线费扣费：填写完送货司机后，从调度备用金中扣除干线费用（每单仅扣一次）
     *
     * @param int $orderNumericId order表主键ID
     */
    private function deductLineFeeAfterSongDriverFilled(int $orderNumericId, string $remark = '干线结算（填写送货司机后扣款）'): void
    {
        $order = Db::name('order')->where('id', $orderNumericId)->find();
        if (!$order) {
            return;
        }
        // 非月结订单标记“送货”后，干线费不从备用金扣除；未标记送货及月结订单保持原流程。
        if ((int)($order['is_urgent'] ?? 0) === 1 && (int)($order['pay_type'] ?? 0) !== 2) {
            return;
        }
        $orderNumber = (string)($order['orderid'] ?? '');
        if ($orderNumber === '') {
            return;
        }

        // 该订单干线费已扣过则不再重复扣
        $exists = Db::name('dispatch_reserve_fund_log')
            ->where('order_id', $orderNumericId)
            ->where('type', 2)
            ->where('direction', 'deduct')
            ->find();
        if ($exists) {
            return;
        }

        $lineOrder = Db::name('dricerorder')
            ->where('order_id', $orderNumber)
            ->where('type', 2)
            ->find();
        // 干线费以订单字段为准：仅当订单未记录(NULL/空)时才回退到专线调度单价格
        $lineBaseRaw = $order['logistics_driver_cost'] ?? null;
        $lineAmount = ($lineBaseRaw === null || $lineBaseRaw === '') ? 0 : round((float)$lineBaseRaw, 2);
        if ($lineAmount <= 0 && $lineOrder) {
            if ($lineBaseRaw === null || $lineBaseRaw === '') {
                $lineAmount = isset($lineOrder['price']) ? round((float)$lineOrder['price'], 2) : 0;
            }
        }
        if ($lineAmount <= 0) {
            return;
        }

        $dispatchAdminId = $this->getDispatchAdminIdByOrderNumber($orderNumericId);
        if ($dispatchAdminId <= 0) {
            return;
        }

        // admin_name 不能为空：优先取当前管理员昵称/用户名，兜底“后台管理员”
        $adminInfo = $this->auth ? $this->auth->getUserInfo() : null;
        $adminName = $adminInfo
            ? (($adminInfo['nickname'] ?? '') ?: (($adminInfo['username'] ?? '') ?: '后台管理员'))
            : '后台管理员';

        Db::startTrans();
        try {
            $reserveFund = Db::name('dispatch_reserve_fund')->lock(true)->where('admin_id', $dispatchAdminId)->find();
            if (!$reserveFund) {
                $reserveFund = [
                    'admin_id' => $dispatchAdminId,
                    'balance' => 0,
                    'total_recharge' => 0,
                    'total_deduct' => 0,
                ];
            }
            $currentBalance = isset($reserveFund['balance']) ? round((float)$reserveFund['balance'], 2) : 0;
            if ($currentBalance < $lineAmount) {
                throw new \Exception('备用金余额不足，无法支付干线费用');
            }

            $afterBalance = round($currentBalance - $lineAmount, 2);
            $fundUpdate = Db::name('dispatch_reserve_fund')
                ->where('admin_id', $dispatchAdminId)
                ->update([
                    'balance' => $afterBalance,
                    'total_deduct' => Db::raw('total_deduct+' . $lineAmount),
                    'updatetime' => time(),
                ]);
            if ($fundUpdate === false) {
                throw new \Exception('更新备用金失败');
            }

            $logInsert = Db::name('dispatch_reserve_fund_log')->insert([
                'admin_id' => $dispatchAdminId,
                'order_id' => $orderNumericId,
                'order_number' => $orderNumber,
                'driver_order_id' => (int)($lineOrder['id'] ?? 0),
                'type' => 2,
                'amount' => $lineAmount,
                'direction' => 'deduct',
                'balance_before' => $currentBalance,
                'balance_after' => $afterBalance,
                'remark' => $remark,
                'admin_name' => $adminName,
                'createtime' => time(),
            ]);
            if ($logInsert === false) {
                throw new \Exception('记录备用金流水失败');
            }

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
    }

    /**
     * 保存送货司机额外成本（仅记录，等待总后台审核）
     */
    public function add_songdriver_other_price()
    {
        if (!$this->request->isPost()) {
            $this->error('非法请求');
        }
        $ids = $this->request->post('ids');
        $price = trim((string)$this->request->post('price'));
        $remarks = trim((string)$this->request->post('remarks', ''));

        $this->errorIfOrderRejectedByAdminOrderId($ids);
        $orderId = Db::name('admin_order')->where('id', $ids)->value('order_id');
        if (!$orderId) {
            $this->error('订单不存在');
        }
        $order = Db::name('order')->where('id', $orderId)->field('orderid,shipment_driver_fee,logistics_status')->find();
        if (!$order) {
            $this->error('订单不存在');
        }
        // 未填写送货司机前，禁止提交送货司机额外成本
        $deliveryDriver = Db::name('dricerorder')
            ->where('order_id', $order['orderid'])
            ->where('type', 3)
            ->find();
        if (!$deliveryDriver || empty($deliveryDriver['d_id'])) {
            $this->error('请先填写送货司机');
        }
        $logisticsStatus = isset($order['logistics_status']) ? (int)$order['logistics_status'] : 0;
        $oldFee          = isset($order['shipment_driver_fee']) ? (float)$order['shipment_driver_fee'] : 0;
        $delta           = (float)$price;
        $newFee          = $oldFee + $delta;
        $adminInfo = $this->auth->getUserInfo();
        $adminName = $adminInfo['nickname'];

        $newData = ['shipment_driver_fee' => $newFee, 'price' => $price, 'remarks' => $remarks];
        $adminGroupId = (int) Db::name('auth_group_access')->where('uid', $this->auth->id)->value('group_id');
        // 送货司机订单未完成(status!=2)时直接保存，不审核
        $deliveryCompleted = isset($deliveryDriver['status']) && (int)$deliveryDriver['status'] === 2;
        $directApply = ($adminGroupId === 1) || !$deliveryCompleted;
        if ($directApply) {
                OrderModifyApplier::apply('songdriver_other_price', (int)$orderId, (int)$ids, $order['orderid'] ?? '', $newData);
                $this->success('保存成功');

        }

        $logData = [
            'admin_order_id'    => (int)$ids,
            'order_id'         => (int)$orderId,
            'orderid'          => $order['orderid'] ?? '',
            'modify_type'      => 'songdriver_other_price',
            'title'            => '填写送货司机额外成本',
            'old_data'         => ['shipment_driver_fee' => $oldFee],
            'new_data'         => $newData,
            'admin_id'         => $this->auth->id,
            'admin_name'       => $adminName,
            'logistics_status' => $logisticsStatus,
            'remark'           => $remarks,
            'audit_status'     => OrderModifyLog::AUDIT_PENDING,
        ];
        OrderModifyLog::add($logData);
        $this->success('已提交，等待总后台审核');
    }

    /**
     * 保存物流额外成本（与取送货司机一样需总后台审核，审核通过后更新专线价格 logistics_driver_cost 与总成本）
     */
    public function add_logistics_extra_price()
    {
        if (!$this->request->isPost()) {
            $this->error('非法请求');
        }
        $ids = $this->request->post('ids');
        $price = trim((string)$this->request->post('price'));
        $remarks = trim((string)$this->request->post('remarks', ''));

        $this->errorIfOrderRejectedByAdminOrderId($ids);
        $orderId = Db::name('admin_order')->where('id', $ids)->value('order_id');
        if (!$orderId) {
            $this->error('订单不存在');
        }
        $order = Db::name('order')->where('id', $orderId)->field('orderid,logistics_driver_cost,logistics_status')->find();
        if (!$order) {
            $this->error('订单不存在');
        }
        $priceVal = (float)$price;
        if ($price === '' || !is_numeric($price)) {
            $this->error('请输入有效的金额（正数为加、负数为减）');
        }
        $logisticsStatus = isset($order['logistics_status']) ? (int)$order['logistics_status'] : 0;
        $oldFee = isset($order['logistics_driver_cost']) ? (float)$order['logistics_driver_cost'] : 0;
        $newFee = round($oldFee + $priceVal, 2);
        $adminInfo = $this->auth->getUserInfo();
        $adminName = $adminInfo['nickname'];

        $newData = [
            'logistics_driver_cost' => $newFee,
            'price'                 => round($priceVal, 2),
            'remarks'               => $remarks,
        ];
        $adminGroupId = (int) Db::name('auth_group_access')->where('uid', $this->auth->id)->value('group_id');
        if ($adminGroupId === 1) {
            OrderModifyApplier::apply('logistics_extra_price', (int)$orderId, (int)$ids, $order['orderid'] ?? '', $newData);
            $this->success('保存成功');
        }

        $logData = [
            'admin_order_id'    => (int)$ids,
            'order_id'         => (int)$orderId,
            'orderid'          => $order['orderid'] ?? '',
            'modify_type'      => 'logistics_extra_price',
            'title'            => '物流额外成本',
            'old_data'         => ['logistics_driver_cost' => $oldFee],
            'new_data'         => $newData,
            'admin_id'         => $this->auth->id,
            'admin_name'       => $adminName,
            'logistics_status' => $logisticsStatus,
            'remark'           => $remarks,
            'audit_status'     => OrderModifyLog::AUDIT_PENDING,
        ];
        OrderModifyLog::add($logData);
        $this->success('已提交，等待总后台审核');
    }

    /**
     * 保存取货司机额外成本（仅记录，等待总后台审核）
     */
    public function add_driver_other_price()
    {
        if (!$this->request->isPost()) {
            $this->error('非法请求');
        }
        $ids = $this->request->post('ids');
        $price = trim((string)$this->request->post('price'));
        $remarks = trim((string)$this->request->post('remarks', ''));

        $this->errorIfOrderRejectedByAdminOrderId($ids);
        $orderId = Db::name('admin_order')->where('id', $ids)->value('order_id');
        if (!$orderId) {
            $this->error('订单不存在');
        }
        $order = Db::name('order')->where('id', $orderId)->field('orderid,pickup_driver_fee,logistics_status')->find();

        if (!$order) {
            $this->error('订单不存在');
        }
        // 未填写取货司机前，禁止提交取货司机额外成本
        $pickupDriver = Db::name('dricerorder')
            ->where('order_id', $order['orderid'])
            ->where('type', 1)
            ->find();
        if (!$pickupDriver || empty($pickupDriver['d_id'])) {
            $this->error('请先填写取货司机');
        }
        $logisticsStatus = isset($order['logistics_status']) ? (int)$order['logistics_status'] : 0;
        $oldFee          = isset($order['pickup_driver_fee']) ? (float)$order['pickup_driver_fee'] : 0;
        $delta           = (float)$price;
        $newFee          = $oldFee + $delta;
        $adminInfo = $this->auth->getUserInfo();
        $adminName = $adminInfo['nickname'];

        $newData = ['pickup_driver_fee' => $newFee, 'price' => $price, 'remarks' => $remarks];
        $adminGroupId = (int) Db::name('auth_group_access')->where('uid', $this->auth->id)->value('group_id');
        // 取货司机订单未完成(status!=2)时直接保存，不审核
        $pickupCompleted = isset($pickupDriver['status']) && (int)$pickupDriver['status'] === 2;
        $directApply = ($adminGroupId === 1) || !$pickupCompleted;
        if ($directApply) {
                OrderModifyApplier::apply('driver_other_price', (int)$orderId, (int)$ids, $order['orderid'] ?? '', $newData);
                $this->success('保存成功');
        }

        $logData = [
            'admin_order_id'    => (int)$ids,
            'order_id'         => (int)$orderId,
            'orderid'          => $order['orderid'] ?? '',
            'modify_type'      => 'driver_other_price',
            'title'            => '填写取货司机额外成本',
            'old_data'         => ['pickup_driver_fee' => $oldFee],
            'new_data'         => $newData,
            'admin_id'         => $this->auth->id,
            'admin_name'       => $adminName,
            'logistics_status' => $logisticsStatus,
            'remark'           => $remarks,
            'audit_status'     => OrderModifyLog::AUDIT_PENDING,
        ];
        OrderModifyLog::add($logData);
        $this->success('已提交，等待总后台审核');
    }

    /**
     * 保存订单总运费（order_extra_price，累加在总运费上，仅记录，等待总后台审核）
     */
    public function add_order_other_price()
    {
        if (!$this->request->isPost()) {
            $this->error('非法请求');
        }
        $ids = $this->request->post('ids');
        $price = trim((string)$this->request->post('price'));
        $remarks = trim((string)$this->request->post('remarks', ''));

        $this->errorIfOrderRejectedByAdminOrderId($ids);
        $orderId = Db::name('admin_order')->where('id', $ids)->value('order_id');
        if (!$orderId) {
            $this->error('订单不存在');
        }
        $order = Db::name('order')->where('id', $orderId)->field('orderid,pay_price,logistics_status')->find();

        if (!$order) {
            $this->error('订单不存在');
        }
        $logisticsStatus = isset($order['logistics_status']) ? (int)$order['logistics_status'] : 0;
        $adminInfo = $this->auth->getUserInfo();
        $adminName = $adminInfo['nickname'];

        $newData = ['pay_price' => $price, 'remarks' => $remarks];
        $adminGroupId = (int) Db::name('auth_group_access')->where('uid', $this->auth->id)->value('group_id');
        // 取货/送货任一段未完成则直接保存，不审核
//        $pickupCompleted  = (int) Db::name('dricerorder')->where('order_id', $order['orderid'])->where('type', 1)->value('status') === 2;
//        $deliveryCompleted = (int) Db::name('dricerorder')->where('order_id', $order['orderid'])->where('type', 3)->value('status') === 2;
//        $anySegmentCompleted = $pickupCompleted || $deliveryCompleted;
//        $directApply = ($adminGroupId === 1) || !$anySegmentCompleted;
        if ($adminGroupId === 1) {
                OrderModifyApplier::apply('order_other_price', (int)$orderId, (int)$ids, $order['orderid'] ?? '', $newData);
                $this->success('保存成功');
        }

        $logData = [
            'admin_order_id'    => (int)$ids,
            'order_id'         => (int)$orderId,
            'orderid'          => $order['orderid'] ?? '',
            'modify_type'      => 'order_other_price',
            'title'            => '修改订单总运费',
            'old_data'         => ['pay_price' => $order['pay_price']],
            'new_data'         => $newData,
            'admin_id'         => $this->auth->id,
            'admin_name'       => $adminName,
            'logistics_status' => $logisticsStatus,
            'remark'           => $remarks,
            'audit_status'     => OrderModifyLog::AUDIT_PENDING,
        ];
        OrderModifyLog::add($logData);
        $this->success('已提交，等待总后台审核');
    }

    /**
     * 保存订单成本（cost_extra_price，更新订单成本，仅记录，等待总后台审核）
     */
    public function add_cost_extra_price()
    {
        if (!$this->request->isPost()) {
            $this->error('非法请求');
        }
        $ids = $this->request->post('ids');
        $price = trim((string) $this->request->post('price'));
        $remarks = trim((string) $this->request->post('remarks', ''));

        if ($price === '' || !is_numeric($price)) {
            $this->error('请输入有效的金额');
        }
        $price = round((float) $price, 2);

        $this->errorIfOrderRejectedByAdminOrderId($ids);
        $orderId = Db::name('admin_order')->where('id', $ids)->value('order_id');
        if (!$orderId) {
            $this->error('订单不存在');
        }
        $order = Db::name('order')->where('id', $orderId)->field('orderid,logistics_status,cost_cont')->find();
        if (!$order) {
            $this->error('订单不存在');
        }
        $logisticsStatus = isset($order['logistics_status']) ? (int)$order['logistics_status'] : 0;
        $adminInfo = $this->auth->getUserInfo();
        $adminName = $adminInfo['nickname'];

        $newData = ['price' => $price, 'remarks' => $remarks];
        $adminGroupId = (int) Db::name('auth_group_access')->where('uid', $this->auth->id)->value('group_id');
        // 取货/送货任一段未完成则直接保存，不审核
//        $pickupCompleted   = (int) Db::name('dricerorder')->where('order_id', $order['orderid'])->where('type', 1)->value('status') === 2;
//        $deliveryCompleted = (int) Db::name('dricerorder')->where('order_id', $order['orderid'])->where('type', 3)->value('status') === 2;
//        $anySegmentCompleted = $pickupCompleted || $deliveryCompleted;
//        $directApply = ($adminGroupId === 1) || !$anySegmentCompleted;
        if ($adminGroupId === 1) {
                OrderModifyApplier::apply('cost_extra_price', (int)$orderId, (int)$ids, $order['orderid'] ?? '', $newData);
                $this->success('保存成功');
        }

        $logData = [
            'admin_order_id'    => (int)$ids,
            'order_id'         => (int)$orderId,
            'orderid'          => $order['orderid'] ?? '',
            'modify_type'      => 'cost_extra_price',
            'title'            => '修改订单成本',
            'old_data'         => ['pay_price' => $order['cost_cont']],
            'new_data'         => $newData,
            'admin_id'         => $this->auth->id,
            'admin_name'       => $adminName,
            'logistics_status' => $logisticsStatus,
            'remark'           => $remarks,
            'audit_status'     => OrderModifyLog::AUDIT_PENDING,
        ];
        OrderModifyLog::add($logData);
        $this->success('已提交，等待总后台审核');
    }

    /**
     * @return void
     *
     * 查看消息列表
     */
    public function admincomm()
    {
        $ids  = $this->request->param('ids');
        $order_id = Db::name('admin_order')->where('id',$ids)->value('order_id');
        $data = Db::name('admincomm')->where('order_id',$order_id)->order('createtime asc')->select();
        foreach ($data as $k =>$v){
            $data[$k]['mobile'] = Db::name('admin')->where('id',$v['admin_id'])->value('mobile');
            $group_id = Db::name('auth_group_access')->where('uid',$v['admin_id'])->value('group_id');
            $data[$k]['group_name'] = Db::name('auth_group')->where('id',$group_id)->value('name');
        }
        $this->view->assign("row", $data);
        return $this->view->fetch();
    }

    public function add_admincomm()
    {
        $data = $this->request->param();
        if (!empty($data['ids'])) {
            $this->errorIfOrderRejectedByAdminOrderId($data['ids']);
        }
        $order_id = Db::name('admin_order')->where('id',$data['ids'])->value('order_id');
        $admin_id = $this->auth->id;
        $arr = [
            'admin_id'  => $admin_id,
            'order_id'  => $order_id,
            'content'   => $data['content'],
            'createtime'=> time(),
        ];
        $res = Db::name('admincomm')->insert($arr);
        if ($res){
            $this->success('提交成功');
        }else{
            $this->error('提交失败');
        }
    }


    public function isdu()
    {
        $admin_id = $this->auth->id;

        $order_id = $this->request->param();
        $id = explode(',',$order_id['ids']);
       foreach ($id as $k=>$v){
           $data = Db::name('admincomm')->where('id',$v)->find();
           if ($data['admin_id'] != $admin_id){
               Db::name('admincomm')->where('id',$v)->update(['status'=>1]);
           }
       }
    }

    /**
     * 添加
     *
     * @return string
     * @throws \think\Exception
     */
    /**
     * 加盟商/线路在“查看路线”页添加专线：直接弹出添加表单，保存到 fa_logistics（不依赖订单上下文）
     */
    public function franchiseLogisticsAdd()
    {
        if (false === $this->request->isPost()) {
            return $this->view->fetch('logistics/add');
        }
        $params = $this->request->post('row/a');
        // 加盟商添加的专线：开启“物流需审核”时默认待审核(1)，否则默认审核通过(2)、启用
        if (FranchiseService::adminLogisticsAuditEnabled((int)$this->auth->id)) {
            $params['status'] = '1';
        } elseif (empty($params['status'])) {
            $params['status'] = '2';
        }
        if (empty($params['logistics_status'])) {
            $params['logistics_status'] = '1';
        }
        $this->saveLogisticsRecord($params);
    }

    public function add()
    {
        if (false === $this->request->isPost()) {
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        // 加盟商且开启“物流需审核” -> 默认待审核(1)，否则默认审核通过(2)
        if (FranchiseService::adminLogisticsAuditEnabled((int)$this->auth->id)) {
            $params['status'] = '1';
        } elseif (empty($params['status'])) {
            $params['status'] = '2';
        }
        $this->saveLogisticsRecord($params);
    }

    /**
     * 填充操作人信息（添加/修改物流时记录：admin、电话、所属加盟商与层级、二级时的一级）
     */
    private function fillOperatorInfo(array &$params): void
    {
        $adminId = (int)$this->auth->id;
        $params['operator_admin_id'] = $adminId;
        $admin = Db::name('admin')->where('id', $adminId)->find();
        $params['operator_name'] = $admin ? (string)($admin['nickname'] ?? $admin['username'] ?? '') : '';
        $fr = \app\admin\library\FranchiseService::resolveFranchiseForAdmin($adminId);
        // 电话优先取操作人手机号；子账号无手机号时回退为加盟商联系电话
        $operatorMobile = $admin ? (string)($admin['mobile'] ?? '') : '';
        if ($operatorMobile === '' && $fr) {
            $operatorMobile = (string)($fr['mobile'] ?? '');
        }
        $params['operator_mobile'] = $operatorMobile;
        if ($fr) {
            $params['operator_franchise_id'] = (int)$fr['id'];
            $params['operator_franchise_name'] = (string)($fr['name'] ?? '');
            $params['operator_franchise_level'] = (int)($fr['level'] ?? 0);
            $parentName = '';
            if ((int)($fr['parent_id'] ?? 0) > 0) {
                $parentName = (string)Db::name('franchise')->where('id', (int)$fr['parent_id'])->value('name');
            }
            $params['operator_parent_franchise_name'] = $parentName;
        } else {
            $params['operator_franchise_id'] = 0;
            $params['operator_franchise_name'] = '';
            $params['operator_franchise_level'] = 0;
            $params['operator_parent_franchise_name'] = '';
        }
    }

    /**
     * 保存专线记录（按手机号建用户、算坐标、写入 fa_logistics）
     */
    private function saveLogisticsRecord(array $params = []): void
    {
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);

        if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
            $params[$this->dataLimitField] = $this->auth->id;
        }
        $result = false;
        Db::startTrans();
        try {
            //是否采用模型验证
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                $this->model->validateFailException()->validate($validate);
            }
            
            // 根据shipping_logistics_mobile查找或创建用户
            if (empty($params['uid']) && !empty($params['shipping_logistics_mobile'])) {
                $mobile = $params['shipping_logistics_mobile'];
                // 查找用户
                $user = Db::name('user')->where('mobile', $mobile)->find();
                
                // ===== 暂时关闭：不再自动创建专线账号（代码保留，需要时取消注释） =====
                // if (!$user) {
                //     // 用户不存在，创建新用户
                //     $defaultPassword = \fast\Random::alnum(8);
                //     $salt = \fast\Random::alnum();
                //     $encryptedPassword = \app\common\library\Auth::instance()->getEncryptPassword($defaultPassword, $salt);
                //
                //     $username = $mobile;
                //     $nickname = preg_match("/^1[3-9]{1}\d{9}$/", $mobile) ? substr_replace($mobile, '****', 3, 4) : $mobile;
                //
                //     $ip = request()->ip();
                //     $time = time();
                //
                //     $userData = [
                //         'username' => $username,
                //         'nickname' => $nickname,
                //         'password' => $encryptedPassword,
                //         'salt' => $salt,
                //         'mobile' => $mobile,
                //         'level' => 1,
                //         'score' => 0,
                //         'money' => 0.00,
                //         'avatar' => '',
                //         'status' => 'normal',
                //         'jointime' => $time,
                //         'joinip' => $ip,
                //         'logintime' => $time,
                //         'loginip' => $ip,
                //         'prevtime' => $time,
                //         'createtime' => $time,
                //         'updatetime' => $time,
                //     ];
                //
                //     $userId = Db::name('user')->insertGetId($userData);
                //     if (!$userId) {
                //         throw new \Exception('创建用户失败');
                //     }
                //     $params['uid'] = $userId;
                // } else {
                //     // 用户已存在，使用现有用户ID
                //     $params['uid'] = $user['id'];
                // }
                if ($user) {
                    $params['uid'] = $user['id'];
                }
            }
            
            $params['shipping_logistics_address'] = $params['shipping_province'] . $params['origincity'] . $params['shipping_area'] . $params['shipping_logistics_address'];
            $params['arrival_logistics_address'] = $params['province'] . $params['destination'] . $params['arrival_area'] . $params['arrival_logistics_address'];
            $params['shipping_latitude'] = $this->resolveCoordinatesWithCache($params['shipping_logistics_address'])['lat'];
            $params['shipping_longitude']=  $this->resolveCoordinatesWithCache($params['shipping_logistics_address'])['lng'];
            $params['arrival_latitude'] =  $this->resolveCoordinatesWithCache($params['arrival_logistics_address'])['lat'];
            $params['arrival_longitude'] =   $this->resolveCoordinatesWithCache($params['arrival_logistics_address'])['lng'];

            $params['distance'] = $this->resolveDrivingDistanceWithCache(
                $this->resolveCoordinatesWithCache($params['shipping_logistics_address'])['lat'],
                $this->resolveCoordinatesWithCache($params['shipping_logistics_address'])['lng'],
                $this->resolveCoordinatesWithCache($params['arrival_logistics_address'])['lat'],
                $this->resolveCoordinatesWithCache($params['arrival_logistics_address'])['lng']
            );
            if (!isset($params['maintained'])) {
                $params['maintained'] = 0;
            }
            $this->fillOperatorInfo($params);
            $result = $this->model->allowField(true)->save($params);
            Db::commit();
        } catch (ValidateException|PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if ($result === false) {
            $this->error(__('No rows were inserted'));
        }
        $this->success();
    }
    /**
     * 编辑
     *
     * @param $ids
     * @return string
     * @throws DbException
     * @throws \think\Exception
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds) && !in_array($row[$this->dataLimitField], $adminIds)) {
            $this->error(__('You have no permission'));
        }
        if (false === $this->request->isPost()) {
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);
        $result = false;
        Db::startTrans();
        try {
            //是否采用模型验证
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                $row->validateFailException()->validate($validate);
            }
            // 根据shipping_logistics_mobile查找或创建用户
            if (!empty($params['shipping_logistics_mobile'])) {
                $mobile = $params['shipping_logistics_mobile'];
                // 查找用户
                $user = Db::name('user')->where('mobile', $mobile)->find();

                // ===== 暂时关闭：不再自动创建专线账号（代码保留，需要时取消注释） =====
                // if (!$user) {
                //     // 用户不存在，创建新用户
                //     $defaultPassword = \fast\Random::alnum(8);
                //     $salt = \fast\Random::alnum();
                //     $encryptedPassword = \app\common\library\Auth::instance()->getEncryptPassword($defaultPassword, $salt);
                //
                //     $username = $mobile;
                //     $nickname = preg_match("/^1[3-9]{1}\d{9}$/", $mobile) ? substr_replace($mobile, '****', 3, 4) : $mobile;
                //
                //     $ip = request()->ip();
                //     $time = time();
                //
                //     $userData = [
                //         'password' => $encryptedPassword,
                //         'salt' => $salt,
                //         'mobile' => $mobile,
                //         'level' => 1,
                //         'score' => 0,
                //         'money' => 0.00,
                //         'avatar' => '',
                //         'status' => 'normal',
                //         'jointime' => $time,
                //         'joinip' => $ip,
                //         'logintime' => $time,
                //         'loginip' => $ip,
                //         'prevtime' => $time,
                //         'createtime' => $time,
                //         'updatetime' => $time,
                //     ];
                //
                //     $userId = Db::name('user')->insertGetId($userData);
                //     if (!$userId) {
                //         throw new \Exception('创建用户失败');
                //     }
                //     $params['uid'] = $userId;
                // }
            }

//            $params['shipping_logistics_address'] = $params['shipping_province'] . $params['origincity'] . $params['shipping_area'] . $params['shipping_logistics_address'];
//            $params['arrival_logistics_address'] = $params['province'] . $params['destination'] . $params['arrival_area'] . $params['arrival_logistics_address'];
            $params['shipping_latitude'] = $this->resolveCoordinatesWithCache($params['shipping_logistics_address'])['lat'];
            $params['shipping_longitude']=  $this->resolveCoordinatesWithCache($params['shipping_logistics_address'])['lng'];
            $params['arrival_latitude'] =  $this->resolveCoordinatesWithCache($params['arrival_logistics_address'])['lat'];
            $params['arrival_longitude'] =   $this->resolveCoordinatesWithCache($params['arrival_logistics_address'])['lng'];

            $params['distance'] = $this->resolveDrivingDistanceWithCache(
                $this->resolveCoordinatesWithCache($params['shipping_logistics_address'])['lat'],
                $this->resolveCoordinatesWithCache($params['shipping_logistics_address'])['lng'],
                $this->resolveCoordinatesWithCache($params['arrival_logistics_address'])['lat'],
                $this->resolveCoordinatesWithCache($params['arrival_logistics_address'])['lng']
            );
            if (!isset($params['maintained'])) {
                $params['maintained'] = 0;
            }
            $this->fillOperatorInfo($params);
            $result = $row->allowField(true)->save($params);
            // 加盟商且开启“物流需审核”：修改后置为待审核(1)，需总后台重新审核通过(2)
            if (FranchiseService::adminLogisticsAuditEnabled((int)$this->auth->id)) {
                Db::name('logistics')->where('id', (int)$row['id'])->update(['status' => 1, 'update_time' => time()]);
            }
            Db::commit();
        } catch (ValidateException|PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if (false === $result) {
            $this->error(__('No rows were updated'));
        }
        $this->success();
    }


    public function add_cost_other_price()
    {
        if (!$this->request->isPost()) {
            $this->error('非法请求');
        }
        $ids             = $this->request->post('ids');
        $saveType        = trim((string)$this->request->post('save_type', ''));
        $pickupTaxPoint  = trim((string)$this->request->post('pickup_tax_point', ''));
        $deliveryTaxPoint = trim((string)$this->request->post('delivery_tax_point', ''));

        $this->errorIfOrderRejectedByAdminOrderId($ids);
        $orderId = Db::name('admin_order')->where('id', $ids)->value('order_id');
        if (!$orderId) {
            $this->error('订单不存在');
        }

        $order = Db::name('order')
            ->where('id', $orderId)
            ->field('orderid,logistics_status')
            ->find();
        if (!$order) {
            $this->error('订单不存在');
        }

        $orderid = $order['orderid'];
        $logisticsStatus = isset($order['logistics_status']) ? (int)$order['logistics_status'] : 0;
        $adminInfo = $this->auth->getUserInfo();
//        print_r($adminInfo);die;
        $adminName = $adminInfo['nickname'];

        // 有填写税点时，必须先分配对应司机；否则会导致审核通过无法落库
        if ($pickupTaxPoint !== '') {
//            echo 11;die;
            $pickupRecord = Db::name('dricerorder')
                ->where('order_id', $orderid)
                ->where('type', 1)
                ->find();
            $status = $pickupRecord['status'];
            if (!$pickupRecord || empty($pickupRecord['d_id'])) {
                $this->error('取货司机还没找到，不能填写税点');
            }
        } else {
            $pickupRecord = Db::name('dricerorder')->where('order_id', $orderid)->where('type', 1)->find();
        }
        if ($deliveryTaxPoint !== '') {
            $deliveryRecord = Db::name('dricerorder')
                ->where('order_id', $orderid)
                ->where('type', 3)
                ->find();
            $status = $deliveryRecord['status'];
            if (!$deliveryRecord || empty($deliveryRecord['d_id'])) {
                $this->error('送货司机还没找到，不能填写税点');
            }
        } else {
            $deliveryRecord = Db::name('dricerorder')->where('order_id', $orderid)->where('type', 3)->find();
        }

        // 与数据库当前税点比较：只有本次传的税点与数据库不一致时才加入修改；取货和送货分开保存，仅处理 save_type 指定的一段
        $pickupDbTax   = isset($pickupRecord['tax_point']) && $pickupRecord['tax_point'] !== '' && $pickupRecord['tax_point'] !== null ? (string)$pickupRecord['tax_point'] : '';
        $deliveryDbTax = isset($deliveryRecord['tax_point']) && $deliveryRecord['tax_point'] !== '' && $deliveryRecord['tax_point'] !== null ? (string)$deliveryRecord['tax_point'] : '';
        $newData = [];
        if ($saveType !== 'delivery' && (string)$pickupTaxPoint !== $pickupDbTax) {
            $newData['pickup_tax_point'] = $pickupTaxPoint;
        }
        if ($saveType !== 'pickup' && (string)$deliveryTaxPoint !== $deliveryDbTax) {
            $newData['delivery_tax_point'] = $deliveryTaxPoint;
        }

        // 没有任何变更则直接成功
        if (empty($newData)) {
            $this->success('保存成功');
        }

        $adminGroupId = (int) Db::name('auth_group_access')->where('uid', $this->auth->id)->value('group_id');
        $directApply = ($adminGroupId === 1) || $status == 1;
//        print_r($directApply);die;
        if ($directApply) {
            OrderModifyApplier::apply('cost_other_price', (int)$orderId, (int)$ids, $orderid, $newData);
            $this->success('保存成功');
        }

        // 总后台审核时需要看到修改前的税点，只记录本次变更涉及的字段
        $oldData = [];
        if (isset($newData['pickup_tax_point'])) {
            $oldData['pickup_tax_point'] = $pickupDbTax;
        }
        if (isset($newData['delivery_tax_point'])) {
            $oldData['delivery_tax_point'] = $deliveryDbTax;
        }
        $logData = [
            'admin_order_id'    => (int)$ids,
            'order_id'         => (int)$orderId,
            'orderid'          => $orderid,
            'modify_type'      => 'cost_other_price',
            'title'            => '填写税点',
            'old_data'         => $oldData,
            'new_data'         => $newData,
            'admin_id'         => $this->auth->id,
            'admin_name'       => $adminName,
            'logistics_status' => $logisticsStatus,
            'remark'           => '',
            'audit_status'     => OrderModifyLog::AUDIT_PENDING,
        ];
        OrderModifyLog::add($logData);
        $this->success('已提交，等待总后台审核');
    }

    // 填写税点页面（取货/送货司机税点，保存到 dricerorder.tax_point）
    public function cost_other_price()
    {
        $ids = $this->request->param('ids');
        if (!$ids) {
            $this->error('参数错误');
        }
        $orderId = Db::name('admin_order')->where('id', $ids)->value('order_id');
        if (!$orderId) {
            $this->error('订单不存在');
        }

        $order = Db::name('order')
            ->where('id', $orderId)
            ->field('orderid')
            ->find();
        if (!$order) {
            $this->error('订单不存在');
        }

        $orderid = $order['orderid']; 
        // type=1 取货，type=3 送货
        $pickupRecord   = Db::name('dricerorder')->where('order_id', $orderid)->where('type', 1)->field('tax_point')->find();
        $deliveryRecord = Db::name('dricerorder')->where('order_id', $orderid)->where('type', 3)->field('tax_point')->find();

        $pickup_tax_point   = isset($pickupRecord['tax_point']) && $pickupRecord['tax_point'] !== '' && $pickupRecord['tax_point'] !== null ? $pickupRecord['tax_point'] : '';
        $delivery_tax_point = isset($deliveryRecord['tax_point']) && $deliveryRecord['tax_point'] !== '' && $deliveryRecord['tax_point'] !== null ? $deliveryRecord['tax_point'] : '';

        $this->view->assign('orderNumber', $orderid);
        $this->view->assign('pickup_tax_point', $pickup_tax_point);
        $this->view->assign('delivery_tax_point', $delivery_tax_point);
        return $this->view->fetch();
    }
}

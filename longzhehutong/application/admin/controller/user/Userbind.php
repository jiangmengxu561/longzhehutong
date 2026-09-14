<?php

namespace app\admin\controller\user;

use app\admin\library\AdminUserBind;
use app\common\controller\Backend;
use think\Db;
use think\exception\DbException;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 用户绑定（子后台将已注册会员绑定到当前管理员，绑定后可管理该会员）
 *
 * @icon fa fa-link
 */
class Userbind extends Backend
{
    protected $model = null;

    protected $dataLimit = 'personal';

    protected $dataLimitField = 'admin_id';

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\AdminUserBind;
    }

    /**
     * @throws DbException
     * @throws \think\Exception
     */
    public function index()
    {
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            // 用户绑定表本身没有 user_username/user_mobile/user_nickname 这些列（它们来自 user 表），
            // 搜索时先在 user 表查出 user_id，再把条件转换成绑定表的 user_id IN (...)，避免 SQL 里出现未知列。
            // 注意：buildparams() 返回的 $where 是「给查询用的闭包」而不是数组，
            // 所以必须先把 filter/op 里的这些虚拟字段摘出来，再交给 buildparams 解析。
            $userFieldMap = ['user_username' => 'username', 'user_mobile' => 'mobile', 'user_nickname' => 'nickname'];
            $filterArr = (array)json_decode((string)$this->request->get('filter', ''), true);
            $opArr = (array)json_decode((string)$this->request->get('op', ''), true);
            $userCondIdSets = [];
            $filterChanged = false;
            foreach ($userFieldMap as $frontField => $realField) {
                if (!array_key_exists($frontField, $filterArr)) {
                    continue;
                }
                $value = $filterArr[$frontField];
                $sym = strtoupper((string)($opArr[$frontField] ?? 'LIKE'));
                unset($filterArr[$frontField], $opArr[$frontField]);
                $filterChanged = true;
                if (is_array($value)) {
                    continue;
                }
                $value = trim((string)$value);
                if ($value === '') {
                    continue;
                }
                if (in_array($sym, ['LIKE', 'LIKE %...%'], true)) {
                    $ids = Db::name('user')->where($realField, 'LIKE', '%' . $value . '%')->column('id');
                } elseif (in_array($sym, ['NOT LIKE', 'NOT LIKE %...%'], true)) {
                    $ids = Db::name('user')->where($realField, 'NOT LIKE', '%' . $value . '%')->column('id');
                } elseif (in_array($sym, ['=', 'EQ'], true)) {
                    $ids = Db::name('user')->where($realField, '=', $value)->column('id');
                } elseif (in_array($sym, ['<>', 'NEQ'], true)) {
                    $ids = Db::name('user')->where($realField, '<>', $value)->column('id');
                } else {
                    // 其它操作符不支持，等同于该字段未填写搜索条件
                    continue;
                }
                $userCondIdSets[] = array_values(array_unique(array_map('intval', $ids ?: [])));
            }
            if ($filterChanged) {
                // buildparams 直接读取请求参数，摘掉虚拟字段后重新写入再解析
                $this->request->get(['filter' => json_encode($filterArr), 'op' => json_encode($opArr)]);
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $query = $this->model->where($where);
            if ($userCondIdSets !== []) {
                // 多个虚拟字段之间是 AND 关系，取交集
                $intersect = array_shift($userCondIdSets);
                foreach ($userCondIdSets as $ids) {
                    $intersect = array_values(array_intersect($intersect, $ids));
                }
                $query->where('user_id', 'in', $intersect === [] ? [0] : $intersect);
            }
            $list = $query
                ->order($sort, $order)
                ->paginate($limit);
            foreach ($list as $row) {
                $u = Db::name('user')->where('id', $row->user_id)->field('id,username,nickname,mobile')->find();
                $row->user_username = $u['username'] ?? '';
                $row->user_mobile = $u['mobile'] ?? '';
                $row->user_nickname = $u['nickname'] ?? '';
                $row->admin_username = Db::name('admin')->where('id', $row->admin_id)->value('username') ?: '';
            }
            return json(['total' => $list->total(), 'rows' => $list->items()]);
        }
        return $this->view->fetch();
    }

    /**
     * 添加绑定
     */
    public function add()
    {
        if (false === $this->request->isPost()) {
            $this->view->assign('isSuperAdmin', $this->auth->isSuperAdmin());
            if ($this->auth->isSuperAdmin()) {
                // 只能绑定给「总后台」或「各加盟商的总管理员」，不再列出加盟商的线路/调度等子账号
                $this->view->assign('adminList', AdminUserBind::getBindTargetAdminOptions());
            }
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $mobile = isset($params['mobile']) ? preg_replace('/\s+/', '', trim((string)$params['mobile'])) : '';
        if ($mobile === '') {
            $this->error('请填写会员手机号');
        }
        $userId = (int)Db::name('user')
            ->where('mobile', $mobile)
            ->where('identity', '1')
            ->order('id', 'asc')
            ->value('id');
        if ($userId <= 0) {
            $this->error('未找到该手机号对应的注册会员，请确认已前台注册');
        }
        if (Db::name('admin_user_bind')->where('user_id', $userId)->value('id')) {
            $this->error('该会员已被绑定，请先解除原绑定');
        }
        if ($this->auth->isSuperAdmin()) {
            $adminId = (int)($params['admin_id'] ?? 0);
            if ($adminId <= 0) {
                $this->error('请选择子后台管理员');
            }
            if (!\app\admin\model\Admin::where('id', $adminId)->where('status', 'normal')->value('id')) {
                $this->error('管理员不存在或已禁用');
            }
        } else {
            $adminId = (int)$this->auth->id;
        }
        Db::startTrans();
        try {
            $this->model->save([
                'admin_id'   => $adminId,
                'user_id'    => $userId,
                'createtime' => time(),
            ]);
            Db::commit();
        } catch (ValidateException|PDOException|\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        $this->success();
    }

    /**
     * 超级管理员可调整绑定到哪位管理员；子后台仅允许删除后重绑
     *
     * @param mixed $ids
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
        if (!$this->auth->isSuperAdmin()) {
            $this->error('子后台请使用「删除」后重新添加绑定');
        }
        if (false === $this->request->isPost()) {
            $this->view->assign('row', $row);
            // 可选目标同「添加」；并保留当前绑定值，避免下拉缺原值时保存被改绑
            $this->view->assign('adminList', AdminUserBind::getBindTargetAdminOptions((int)$row->admin_id));
            $u = Db::name('user')->where('id', $row->user_id)->field('username,nickname,mobile')->find();
            $this->view->assign('userInfo', $u ?: []);
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $adminId = (int)($params['admin_id'] ?? 0);
        if ($adminId <= 0) {
            $this->error('请选择子后台管理员');
        }
        if (!\app\admin\model\Admin::where('id', $adminId)->where('status', 'normal')->value('id')) {
            $this->error('管理员不存在或已禁用');
        }
        Db::startTrans();
        try {
            $row->save(['admin_id' => $adminId]);
            Db::commit();
        } catch (ValidateException|PDOException|\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        $this->success();
    }

    /**
     * 选择尚未被绑定的会员（供添加绑定时下拉搜索）
     */
    public function selectpage_user()
    {
        $this->request->filter(['trim', 'strip_tags', 'htmlspecialchars']);
        $word = (array)$this->request->request('q_word/a', []);
        $keyValue = $this->request->request('keyValue', '');
        $page = (int)$this->request->request('pageNumber', 1);
        $pagesize = (int)$this->request->request('pageSize', 10);
        $field = $this->request->request('showField', 'username');
        $primarykey = $this->request->request('keyField', 'id');

        $boundIds = AdminUserBind::getAllBoundUserIds();

        $buildQuery = function () use ($boundIds, $keyValue, $primarykey, $word) {
            $query = Db::name('user');
            if (!empty($boundIds)) {
                $query->where('id', 'not in', $boundIds);
            }
            if ($keyValue !== null && $keyValue !== '') {
                $keyValueArr = is_array($keyValue) ? $keyValue : explode(',', $keyValue);
                $query->where($primarykey, 'in', $keyValueArr);
            } else {
                $word = array_filter(array_unique($word));
                if (!empty($word)) {
                    $kw = '%' . implode('%', $word) . '%';
                    $query->where('username|nickname|mobile', 'like', $kw);
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
            $datalist = $buildQuery()->order('id', 'desc')->page($page, $pagesize)->select();
            foreach ($datalist as $item) {
                $show = trim(($item['username'] ?? '') . ' / ' . ($item['mobile'] ?? '') . ' / ' . ($item['nickname'] ?? ''));
                $list[] = [
                    $primarykey => (string)($item[$primarykey] ?? ''),
                    $field      => htmlentities($show),
                ];
            }
        }
        return json(['list' => $list, 'total' => $total]);
    }
}

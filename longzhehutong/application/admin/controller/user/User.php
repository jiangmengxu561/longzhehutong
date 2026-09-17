<?php

namespace app\admin\controller\user;

use app\admin\library\AdminUserBind;
use app\admin\library\FranchiseService;
use app\common\controller\Backend;
use app\common\library\Auth;
use think\Db;

/**
 * 会员管理
 *
 * @icon fa fa-user
 */
class User extends Backend
{
 
    protected $relationSearch = true;
    protected $searchFields = 'id,username,nickname';

    /** 
     * @var \app\admin\model\User
     */
    protected $model = null;  

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\User;
    }
    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            if (!$this->auth->isSuperAdmin()) {
                $boundIds = AdminUserBind::getBoundUserIds($this->auth->id);
                if (empty($boundIds)) {
                    $this->model->whereRaw('user.id = 0');
                } else {
                    $this->model->whereRaw('user.id IN (' . implode(',', array_map('intval', $boundIds)) . ')');
                }
            }
            $sortableFields = ['id', 'order_num', 'ocr_num'];
            if (!in_array($sort, $sortableFields, true)) {
                $sort = 'id';
            }
            $orderStatSql = Db::name('order')
                ->field('userid, COUNT(*) AS order_num')
                ->group('userid')
                ->buildSql();
            $sortableFields = ['id', 'order_num', 'ocr_num'];
            if (!in_array($sort, $sortableFields, true)) {
                $sort = 'id';
            }
            $orderField = $sort === 'order_num' ? 'order_num' : 'user.' . $sort;
            $list = $this->model
                ->alias('user')
                ->with('group')
                ->field('user.*, IFNULL(order_stat.order_num, 0) AS order_num')
                ->join([$orderStatSql => 'order_stat'], 'order_stat.userid = user.id', 'LEFT')
                ->where($where)
                ->order($orderField, $order)
                ->paginate($limit);
            foreach ($list as $k => $v) {
                if ($v->identity == 1){
                    $v->identity_text = '用户';
                }
                if ($v->identity == 2){
                    $v->identity_text = '司机';
                }
                if ($v->identity == 3){
                    $v->identity_text = '专线';
                }
                if ($v->membertype == 1){
                    $v->membertype_text = '普通用户';
                }
                if ($v->membertype == 2){
                    $v->membertype_text = '兼职员工';
                }
                if ($v->membertype == 3){
                    $v->membertype_text = '正式员工';
                }
                $v->channel_id = Db::name('channel')->where('id', $v->channel_id)->value('name');
                $v->avatar = $v->avatar ? cdnurl($v->avatar, true) : letter_avatar($v->nickname);
                $v->hidden(['password', 'salt']);
                $v->order_num = (int) $v->order_num;
                $v->ocr_num = (int) $v->ocr_num;
            }
            $result = array("total" => $list->total(), "rows" => $list->items());
            return json($result);
        }
        // 仅加盟商后台显示“按手机号绑定会员”
        $this->view->assign('isFranchise', FranchiseService::isFranchiseAdmin((int)$this->auth->id));

        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if (!$this->auth->isSuperAdmin()) {
            $this->error('请让会员在前台注册后，在「用户绑定」中将会员关联到您的账号；子后台不可在此直接添加会员。');
        }
        if ($this->request->isPost()) {
            $this->token();
        }
        return parent::add();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        if ($this->request->isPost()) {
            $this->token();
        }
        // 加盟商：仅可改 用户名/平台抽佣/职位/到期时间（且需绑定到本人名下）
        $franchise = !$this->auth->isSuperAdmin() ? FranchiseService::getFranchiseByAdminId((int)$this->auth->id) : null;
        if ($franchise) {
            $userId = (int)$ids;
            if (!FranchiseService::canManageMember((int)$franchise['id'], $userId)) {
                $this->error(__('You have no permission'));
            }
            $row = Db::name('user')->where('id', $userId)->find();
            if (!$row) {
                $this->error(__('No Results were found'));
            }
            $row['member_time_text'] = $row['member_time']
                ? date('Y-m-d', is_numeric($row['member_time']) ? (int)$row['member_time'] : strtotime($row['member_time']))
                : '';
            $row['platform_commission'] = $row['platform_commission'] ?? '';
            $row['formal_employee_quota'] = (int)($franchise['formal_employee_quota'] ?? 0);
            $row['formal_employee_count'] = FranchiseService::getFormalEmployeeCount((int)$franchise['id']);
            // 改身份/到期按整月扣费，前端提示需要月费单价
            $this->assignconfig('memberMonthFee', (float)FranchiseService::getGlobalConfig()['member_month_fee']);
            if ($this->request->isPost()) {
                $params = [
                    'username'            => $this->request->post('username'),
                    'membertype'          => $this->request->post('membertype'),
                    'member_time'         => $this->request->post('member_time'),
                    'platform_commission' => $this->request->post('platform_commission'),
                ];
                $res = FranchiseService::changeMemberIdentityTime((int)$franchise['id'], $userId, $params);
                if ($res['success']) {
                    $this->success($res['msg']);
                }
                $this->error($res['msg']);
            }
            $this->view->assign('membertypeList', ['1' => '普通用户', '2' => '兼职员工', '3' => '正式员工', '4' => '会展员工']);
            $this->view->assign('row', $row);

            return $this->view->fetch('franchise/memberedit');
        }
        if (!$this->auth->isSuperAdmin() && !AdminUserBind::canManageMember($this->auth, $ids)) {
            $this->error(__('You have no permission'));
        }
        $row = $this->model->get($ids);
//        $this->modelValidate = true;
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $this->view->assign('groupList', build_select('row[group_id]', \app\admin\model\UserGroup::column('id,name'), $row['group_id'], ['class' => 'form-control selectpicker']));
        return parent::edit($ids);
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        if (!$this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $ids = $ids ? $ids : $this->request->post("ids");
        if (!$this->auth->isSuperAdmin()) {
            foreach (explode(',', $ids) as $uid) {
                $uid = (int)$uid;
                if ($uid && !AdminUserBind::canManageMember($this->auth, $uid)) {
                    $this->error(__('You have no permission'));
                }
            }
        }
        $row = $this->model->get($ids);
        $this->modelValidate = true;
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        Auth::instance()->delete($row['id']);
        $this->success();
    }

    /**
     * 批量更新：子后台仅能操作已绑定会员
     *
     * @param mixed $ids
     */
    public function multi($ids = null)
    {
        if ($this->request->isPost() && !$this->auth->isSuperAdmin()) {
            $ids = $ids ?: $this->request->post('ids');
            foreach (explode(',', $ids) as $uid) {
                $uid = (int)$uid;
                if ($uid && !AdminUserBind::canManageMember($this->auth, $uid)) {
                    $this->error(__('You have no permission'));
                }
            }
        }
        return parent::multi($ids);
    }

    /**
     * 下拉搜索会员时仅包含当前管理员已绑定会员（超级管理员不限）
     */
    protected function selectpage()
    {
        if (!$this->auth->isSuperAdmin()) {
            $boundIds = AdminUserBind::getBoundUserIds($this->auth->id);
            if (empty($boundIds)) {
                return json(['list' => [], 'total' => 0]);
            }
            $this->model->where('id', 'in', $boundIds);
        }
        return parent::selectpage();
    }

}

<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;

/**
 * 会员充值订单
 *
 * 注意：Linux 服务器上还存在一份仅大小写不同的副本 Memberorder.php，
 * URL /admin/memberorder 解析出的类名是 Memberorder，会命中那份副本；
 * 两份文件内容必须保持一致，否则线上列表会出现字段缺失（手机号/充值前身份/时长/支付状态为空）。
 *
 * @icon fa fa-list-alt
 */
class MemberOrder extends Backend
{

    /**
     * MemberOrder模型对象
     * @var \app\admin\model\MemberOrder
     */
    protected $model = null;

    protected $searchFields = 'order_no,package_name,user_id';

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\MemberOrder;
        $this->view->assign('payStatusList', $this->model->getPayStatusList());
        // add/edit 表单的下拉数据（历史 CRUD 版本依赖这两个变量）
        $this->view->assign('membertypeAfterList', $this->model->getMembertypeAfterList());
        $this->view->assign('memberUnitList', $this->model->getMemberUnitList());
    }

    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            // 会员手机号：整页批量取一次（前端读取 row.user.mobile），
            // 不用 with() 关联，避免关联字段与订单主键同名造成的列冲突
            $userIds = [];
            foreach ($list as $row) {
                $userIds[] = (int)$row['user_id'];
            }
            $userMap = [];
            if ($userIds !== []) {
                $users = Db::name('user')
                    ->where('id', 'in', array_values(array_unique($userIds)))
                    ->field('id,mobile,nickname,username')
                    ->select();
                foreach ($users as $u) {
                    $userMap[(int)$u['id']] = $u;
                }
            }
            foreach ($list as $row) {
                $u = $userMap[(int)$row['user_id']] ?? null;
                $row['user'] = $u ? [
                    'id'       => (int)$u['id'],
                    'mobile'   => (string)$u['mobile'],
                    'nickname' => (string)$u['nickname'],
                    'username' => (string)$u['username'],
                ] : null;
            }
            $result = ['total' => $this->model->where($where)->count(), 'rows' => $list];
            return json($result);
        }
        return $this->view->fetch();
    }
}

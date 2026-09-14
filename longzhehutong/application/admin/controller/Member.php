<?php

namespace app\admin\controller;

use app\common\controller\Backend;

/**
 * 会员列管理
 *
 * @icon fa fa-circle-o
 */
class Member extends Backend
{

    /**
     * Member模型对象
     * @var \app\admin\model\Member
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        // 会员列管理模型暂未提供(app\admin\model\Member)，仅当存在时才挂载，
        // 避免 `member/order` 等历史入口被空模型中断
        if (class_exists('\\app\\admin\\model\\Member')) {
            $this->model = new \app\admin\model\Member;
            $this->view->assign("unitList", $this->model->getUnitList());
            $this->view->assign("statusList", $this->model->getStatusList());
        }
    }

    /**
     * 会员充值列表（兼容历史菜单 member/order）
     *
     * 说明：URL member/order 会被 ThinkPHP 解析为 Member::order，
     * 这里复用已实现的 MemberOrder 模型与视图，仅在 URL 上保留 member/order。
     *
     * @return string|\think\response\Json
     */
    public function order()
    {
        if ($this->request->isAjax()) {
            // 与 memberorder 页保持一致的搜索字段
            $this->searchFields = 'order_no,package_name,user_id';
            [$where, $sort, $order, $offset, $limit] = $this->buildparams();

            $model = new \app\admin\model\MemberOrder;
            $list = $model
                ->with(['user' => function ($query) {
                    $query->field('id, mobile, nickname, username');
                }])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            foreach ($list as $row) {
                $row->append(['pay_status_text', 'membertype_before_text', 'membertype_after_text', 'duration_text']);
            }
            $result = ['total' => $model->where($where)->count(), 'rows' => $list];
            return json($result);
        }
        return $this->view->fetch();
    }



    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */


}

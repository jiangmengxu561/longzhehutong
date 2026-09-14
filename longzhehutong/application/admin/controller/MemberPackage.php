<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;

/**
 * 会员充值套餐
 *
 * @icon fa fa-cny
 */
class MemberPackage extends Backend
{

    /**
     * MemberPackage模型对象
     * @var \app\admin\model\MemberPackage
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\MemberPackage;
        $this->view->assign('statusList', ['0' => '关闭', '1' => '开启']);
        $this->view->assign('unitList', ['week' => '周', 'month' => '月', 'year' => '年']);
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     */
}

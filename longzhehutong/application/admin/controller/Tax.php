<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\admin\library\FranchiseService;
use think\Db;
use think\exception\DbException;
use think\response\Json;

/**
 * 开票信息
 *
 * @icon fa fa-circle-o
 */
class Tax extends Backend
{

    /**
     * Tax模型对象
     * @var \app\admin\model\Tax
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Tax;
        $this->view->assign("typeList", $this->model->getTypeList());
        $this->view->assign("statusList", $this->model->getStatusList());
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
        $query = $this->model->where($where);
        $scope = FranchiseService::getCurrentAdminFranchiseScopeMemberIds((int)$this->auth->id);
        if ($scope !== null) {
            if ($scope === []) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereRaw('uid IN (' . implode(',', array_map('intval', $scope)) . ')');
            }
        }
        $list = $query->order($sort, $order)->paginate($limit);
        foreach ($list as $k=>$v){
                $userinfo = Db::name('user')
                    ->where('id',$v['uid'])
                    ->field('username,mobile,identity')
                    ->find();
                if($userinfo){
                    $v->nickname = $userinfo['username'];
                    if ($userinfo['identity'] == 1){
                        $v->identity_text = '普通用户';
                    }
                    if ($userinfo['identity'] == 2){
                        $v->identity_text = '司机';
                    }
                    if ($userinfo['identity'] == 3){
                        $v->identity_text = '专线';
                    }
                }else{
                    unset($list[$k]);
                }

        }
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }
}

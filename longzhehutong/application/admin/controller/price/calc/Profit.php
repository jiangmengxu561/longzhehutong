<?php

namespace app\admin\controller\price\calc;

use app\common\controller\Backend;
use think\Db;
use think\exception\DbException;
use think\response\Json;

/**
 * 算价有利润记录
 *
 * @icon fa fa-circle-o
 */
class Profit extends Backend
{

    /**
     * Profit模型对象
     * @var \app\admin\model\price\calc\Profit
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\price\calc\Profit;

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
        // 子后台：用当前管理员手机号匹配 userpa，仅查看分配给自己的算价记录
        if (!$this->auth->isSuperAdmin()) {
            $mobile = trim((string)Db::name('admin')->where('id', $this->auth->id)->value('mobile'));
            if ($mobile === '') {
                return json(['total' => 0, 'rows' => []]);
            }
            $userpaId = (int)Db::name('userpa')->where('mobile', $mobile)->value('id');
            if ($userpaId <= 0) {
                return json(['total' => 0, 'rows' => []]);
            }
            $query->where('user_id', $userpaId);
        }
        $list = $query->order($sort, $order)->paginate($limit);
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }
}

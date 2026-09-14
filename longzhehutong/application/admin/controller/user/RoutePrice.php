<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;
use think\Db;

/**
 * 用户路线价格配置
 *
 * @icon fa fa-dollar
 */
class RoutePrice extends Backend
{
    /**
     * RoutePrice模型对象
     * @var \app\admin\model\user\RoutePrice
     */
    protected $model = null;

    /**
     * 快速搜索时执行查找的字段
     */
    protected $searchFields = 'loading_province,unload_province';

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\user\RoutePrice;
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
            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);
            
            foreach ($list as $k => $v) {
                // 格式化百分比显示
                $v->logistics_cost_percentage_text = ($v->logistics_cost_percentage > 0 ? '+' : '') . $v->logistics_cost_percentage . '%';
                $v->pickup_driver_fee_percentage_text = ($v->pickup_driver_fee_percentage > 0 ? '+' : '') . $v->pickup_driver_fee_percentage . '%';
                $v->shipment_driver_fee_percentage_text = ($v->shipment_driver_fee_percentage > 0 ? '+' : '') . $v->shipment_driver_fee_percentage . '%';
            }
            
            $result = array("total" => $list->total(), "rows" => $list->items());
            return json($result);
        }
        
        // 获取省份列表
        $provinceList = \app\admin\model\user\RoutePrice::getProvinceList();
        $this->view->assign('provinceList', $provinceList);
        
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                
                // 设置创建者管理员ID
                $params['create_admin_id'] = $this->auth->id;
                
                // 处理用户手机号转换为user_id
                $userMobile = $this->request->post("user_mobile", '');
                if (empty($userMobile) || $userMobile == '0') {
                    $params['user_id'] = 0; // 全局配置
                } else {
                    // 根据手机号查找用户ID
                    $user = Db::name('user')->where('mobile', $userMobile)->find();
                    if ($user) {
                        $params['user_id'] = $user['id'];
                    } else {
                        $this->error('未找到手机号为 ' . $userMobile . ' 的用户');
                    }
                }
                
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    $result = $this->model->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        
        // 获取省份列表
        $provinceList = \app\admin\model\user\RoutePrice::getProvinceList();
        $this->view->assign('provinceList', $provinceList);
        
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if (false === $this->request->isPost()) {
            // 获取省份列表
            $provinceList = \app\admin\model\user\RoutePrice::getProvinceList();
            $this->view->assign('provinceList', $provinceList);
            
            // 根据user_id获取手机号
            $userMobile = '';
            if (!empty($row['user_id'])) {
                $user = Db::name('user')->where('id', $row['user_id'])->find();
                $userMobile = $user ? $user['mobile'] : '';
            }
            $this->view->assign('user_mobile', $userMobile);
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }
        $params = $this->request->post("row/a");
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);
        $result = false;
        Db::startTrans();
        try {
            // 处理用户手机号转换为user_id
            $userMobile = $this->request->post("user_mobile", '');
            if (empty($userMobile) || $userMobile == '0') {
                $params['user_id'] = 0; // 全局配置
            } else {
                // 根据手机号查找用户ID
                $user = Db::name('user')->where('mobile', $userMobile)->find();
                if ($user) {
                    $params['user_id'] = $user['id'];
                } else {
                    Db::rollback();
                    $this->error('未找到手机号为 ' . $userMobile . ' 的用户');
                }
            }
            
            //是否采用模型验证
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                $row->validateFailException(true)->validate($validate);
            }
            $result = $row->allowField(true)->save($params);
            Db::commit();
        } catch (ValidateException $e) {
            Db::rollback();
            $this->error($e->getMessage());
        } catch (PDOException $e) {
            Db::rollback();
            $this->error($e->getMessage());
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if (false === $result) {
            $this->error(__('No rows were updated'));
        }
        $this->success();
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
        if (empty($ids)) {
            $this->error(__('Parameter %s can not be empty', 'ids'));
        }
        $pk = $this->model->getPk();
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            $this->model->where($this->dataLimitField, 'in', $adminIds);
        }
        $list = $this->model->where($pk, 'in', $ids)->select();

        $count = 0;
        Db::startTrans();
        try {
            foreach ($list as $k => $v) {
                $count += $v->delete();
            }
            Db::commit();
        } catch (PDOException $e) {
            Db::rollback();
            $this->error($e->getMessage());
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if ($count) {
            $this->success();
        } else {
            $this->error(__('No rows were deleted'));
        }
    }
}


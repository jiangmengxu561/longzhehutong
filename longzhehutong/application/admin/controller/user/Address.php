<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;
use think\Db;

/**
 * 用户地址管理
 *
 * @icon fa fa-map-marker
 */
class Address extends Backend
{
    /**
     * Address模型对象
     * @var \app\admin\model\user\Address
     */
    protected $model = null;

    /**
     * 快速搜索时执行查找的字段
     */
    protected $searchFields = 'user_name,mobile,address,detailed_address';

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\user\Address;
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
            
            $result = array("total" => $list->total(), "rows" => $list->items());
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * Selectpage实现
     * 简化版本：只显示姓名+详细地址
     */
    protected function selectpage()
    {
        //设置过滤方法
        $this->request->filter(['trim', 'strip_tags', 'htmlspecialchars']);

        // 获取参数
        $word = (array)$this->request->request("q_word/a", []);
        $keyValue = $this->request->request("keyValue", '');
        $sort = $this->request->request("sort", "id");
        $order = $this->request->request("order", "DESC");
        $offset = $this->request->request("offset/d", 0);
        $limit = $this->request->request("limit/d", 0);
        $field = $this->request->request("showField", "name");
        $primarykey = $this->request->request("keyField", "id");
        $pagesize = $limit ?: 999999;
        $page = $limit ? intval($offset / $limit) + 1 : 1;

        // 构建查询条件
        $where = [];

        // 如果有keyValue，说明是初始化传值
        if ($keyValue !== null && $keyValue !== '') {
            $keyValueArr = is_array($keyValue) ? $keyValue : explode(',', $keyValue);
            $where = [$primarykey => ['in', $keyValueArr]];
            $pagesize = 999999;
        } else {
            // 搜索条件：只搜索姓名和详细地址
            if (!empty($word) && is_array($word)) {
                $word = array_filter(array_unique($word));
                if (!empty($word)) {
                    $where[] = ['user_name|detailed_address', 'like', '%' . implode('%', $word) . '%'];
                }
            }
        }

        // 查询数据
        $query = Db::name('user_address');
        if (!empty($where)) {
            $query->where($where);
        }
        $total = $query->count();
        
        $list = [];
        if ($total > 0) {
            // 排序（避免使用name字段）
            $sortField = $sort == 'name' ? 'id' : $sort;
            $query = Db::name('user_address');
            if (!empty($where)) {
                $query->where($where);
            }
            $query->order($sortField, $order);
            
            $datalist = $query->page($page, $pagesize)->select();

            foreach ($datalist as $item) {
                // 格式化name字段：姓名 + 详细地址
                $item['name'] = ($item['user_name'] ?? '') . ' ' . ($item['detailed_address'] ?? '');
                
                $result = [
                    $primarykey => (string)($item[$primarykey] ?? ''),
                    $field      => (string)($item[$field] ?? ''),
                ];
                $result['pid'] = '0';
                $result = array_map("htmlentities", $result);
                $list[] = $result;
            }
        }
        
        return json(['list' => $list, 'total' => $total]);
    }
}


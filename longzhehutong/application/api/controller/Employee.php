<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Db;

class Employee extends Api
{
    protected $noNeedLogin = ['Updateorder','UpdateCost','mylastadd','mylastlist','mylastpriceadd','UpdateCost','mylastpricelist','mylastpricedelete','mylastorderstat','mylastorderlist'];
    protected $noNeedRight = ['test2'];

    public function Updateorder()
    {
      $data = $this->request->post();
      $Orderupdate = Db::name('order')->where('orderid',$data['orderid'])->update(['pay_price'=>$data['new_price']]);
         //记录修改
      $pricedata = [
          'orderid'=>$data['orderid'],
          'new_price'=>$data['new_price'],
          'old_price'=>$data['old_price'],
          'createtime'=>time(),
      ];
      Db::name('update_price')->insert($pricedata);
      if($Orderupdate){
          $this->success('修改成功');
      }
    }
    
    /**
     * 更新总成本
     */
    public function UpdateCost()
    {
        $data = $this->request->post();
        $orderid = $data['orderid'] ?? '';
        $new_cost = $data['new_cost'] ?? 0;
        $old_cost = $data['old_cost'] ?? 0;
        
        if (empty($orderid)) {
            $this->error('订单ID不能为空');
        }
        
        // 获取订单信息
        $order = Db::name('order')->where('orderid', $orderid)->find();
        if (!$order) {
            $this->error('订单不存在');
        }
        
        // 直接更新总成本字段 cost_cont
        $updateData = [
            'cost_cont' => round(floatval($new_cost), 2)
        ];
        
        $Orderupdate = Db::name('order')->where('orderid', $orderid)->update($updateData);
        if ($Orderupdate !== false) {
            $this->success('修改成功');
        } else {
            $this->error('修改失败');
        }
    }
    /**
     *
     * 我的下级
     */
    public function mylastlist(){
        $user = $this->auth->id;
        $list = Db::name('user')->where('pid',$user)->select();
        $this->success('success',$list);
    }

    public function mylastadd()
    {
        $user = $this->auth->id;
//        print_r($user);die;
        $mobile = $this->request->post('mobile');
        $user= Db::name('user')
            ->where('mobile',$mobile)
            ->find();
        if (empty($user)){
            $this->error('用户未注册');
        }
        $res = Db::name('user')
            ->where('mobile',$mobile)
            ->where('identity',1)
            ->update(['pid'=>$user]);
        if($res){
            $this->success('添加成功');
        }else{
            $this->error('绑定失败');
        }
    }

    /**
     * @return void
     * 员工添加大客户比例
     */
    public function mylastpriceadd(){
        $user = $this->auth->id;
        $data = $this->request->post();
        $res['user_id'] = $data['last_id'];
        $res['loading_province'] = $data['from_province'];
        $res['unload_province'] = $data['to_province'];
        $res['logistics_cost_percentage'] = $data['percent'];
        $res['createtime'] = time();
        $res['create_admin_id'] = $user;

        $isset = Db::name('user_route_price')
            ->where('user_id',$user)
            ->where('loading_province',$data['from_province'])
            ->where('unload_province',$data['to_province'])
            ->field('id,loading_province as from_province,unload_province as to_province ,logistics_cost_percentage as percent')
            ->select();


        $arr  = Db::name('user_route_price')->insert($res);
        if ($arr){
            $this->success('添加成功');
        }else{
            $this->error('添加失败');
        }
    }
    /**
     *
     * 大客户比例列表
     */
    public function mylastpricelist(){

        $last_id = $this->request->get('id');
        $data = Db::name('user_route_price')
            ->where('user_id',$last_id)
            ->field('id,loading_province as from_province,unload_province as to_province ,logistics_cost_percentage as percent')
            ->select();
        if ($data){
            $this->success('success',$data);
        }else{
            $this->error('error');
        }
    }


    public function mylastpricedelete()
    {
        $id = $this->request->post('id');
        $res = Db::name('user_route_price')->where('id',$id)->delete();
        if ($res){
            $this->success('删除成功');
        }
    }

    public function mylastorderstat()
    {
        $last_id = $this->request->get('id');
        $total_orders = Db::name('order')
            ->where('userid',$last_id)
            ->count();
        $today_orders= Db::name('order')
            ->where('userid',$last_id)
            ->whereTime('createtime','today')
            ->where('pay_status',1)
            ->count();
        $today_quotes= Db::name('order')
            ->where('userid',$last_id)
            ->whereTime('createtime','today')
            ->where('pay_status',5)
            ->count();
        $arr['total_orders'] = $total_orders;
        $arr['today_orders'] = $today_orders;
        $arr['today_quotes'] = $today_quotes;
        $this->success('success',$arr);
    }
    public function mylastorderlist(){
        $last_id = $this->request->get('id');
        $start_time = $this->request->get('start_time');
        $end_time = $this->request->get('end_time');
        $data = Db::name('order')
            ->where('userid',$last_id);
        if (!empty($start_time)){
            $data->whereTime('createtime','between',[$start_time,$end_time]);
        }
       $data =  $data ->select();
        $profit = 0;
        foreach ($data as &$v){
            $v['createtime'] = date('Y-m-d H:i:s',$v['createtime']);
            $v['loading'] = Db::name('user_address')->where('id',$v['loading'])->value('detailed_address');
            $v['unload'] = Db::name('user_address')->where('id',$v['unload'])->value('detailed_address');
            $profit = $profit + $v['pay_price'] - $v['logistics_cost'] - $v['pickup_driver_fee'] - $v['shipment_driver_fee'];
        }
//        $data['data'] = $data;
//        $data['profit'] = round($profit,2);
        $this->success('success',$data);

    }
}

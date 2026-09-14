<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\MemberService;
use think\Db;

/**
 * 会员充值接口
 */
class Memberrecharge extends Api
{

    // 无需登录
    protected $noNeedLogin = ['rules', 'memnotify'];
    // 全部不用鉴权
    protected $noNeedRight = ['*'];

    /**
     * 可充值套餐列表
     */
    public function rules()
    {
        $list = Db::name(MemberService::PACKAGE_TABLE)
            ->where('status', 1)
            ->order('weigh desc, id asc')
            ->field('id, member_name, duration, unit, price, weigh')
            ->select();

        foreach ($list as &$row) {
            $row['duration_text'] = MemberService::durationText((int)$row['duration'], (string)$row['unit']);
            $row['price'] = (float)$row['price'];
        }
        unset($row);

        $this->success('success', $list ?: []);
    }

    /**
     * 发起会员充值（创建订单 + 拉起微信支付）
     */
    public function create()
    {
        $userId = (int)$this->auth->id;
        if ($userId <= 0) {
            $this->error('请先登录');
        }
        $packageId = (int)$this->request->post('package_id', (int)$this->request->request('package_id'));
        if ($packageId <= 0) {
            $this->error('请选择充值套餐');
        }

        $package = Db::name(MemberService::PACKAGE_TABLE)->where('id', $packageId)->find();
        if (!$package || (int)$package['status'] !== 1) {
            $this->error('套餐不存在或已下架');
        }

        $user = Db::name('user')->where('id', $userId)->find();
        if (!$user) {
            $this->error('用户不存在');
        }
        $membertype = (int)$user['membertype'];
        if (!MemberService::canRecharge($membertype)) {
            $this->error('当前身份不支持会员充值');
        }
        if (empty($user['openid'])) {
            $this->error('未获取到微信openid，无法支付');
        }

        // 充值前身份=当前身份，充值后为兼职(2)
        $membertypeBefore = $membertype;
        $membertypeAfter  = MemberService::MEMBERTYPE_PARTTIME;
        $expiryBefore     = MemberService::normalizeMemberTime($user['member_time']);

        $franchiseId = (int)Db::name('franchise_member')->where('user_id', $userId)->value('franchise_id');

        $orderNo = MemberService::makeOrderNo();
        $now = time();
        // 将用户此前未支付的订单置为已取消，避免待支付订单堆积
        Db::name(MemberService::ORDER_TABLE)
            ->where('user_id', $userId)
            ->where('pay_status', 1)
            ->update(['pay_status' => 4, 'updatetime' => $now]);

        $order = [
            'order_no'          => $orderNo,
            'user_id'           => $userId,
            'franchise_id'      => $franchiseId,
            'package_id'        => $packageId,
            'package_name'      => $package['member_name'],
            'membertype_before' => $membertypeBefore,
            'membertype_after'  => $membertypeAfter,
            'member_duration'   => (int)$package['duration'],
            'member_unit'       => $package['unit'],
            'price'             => $package['price'],
            'pay_type'          => 'wechat',
            'pay_status'        => 1,
            'pay_time'          => null,
            'expiry_before'     => $expiryBefore,
            'expiry_after'      => null,
            'remark'            => '',
            'createtime'        => $now,
            'updatetime'        => $now,
        ];
        Db::name(MemberService::ORDER_TABLE)->insert($order);

        $data = $this->submitPay($orderNo, $package['member_name'], $package['price'], $user['openid']);
        if (is_null($data)) {
            $this->error('支付下单失败,请稍后重试');
        }

        $this->success('success', [
            'order_no'    => $orderNo,
            'package_name'=> $package['member_name'],
            'price'       => (float)$package['price'],
            'pay'         => $data,
        ]);
    }

    /**
     * 继续支付未支付订单
     */
    public function pay()
    {
        $userId = (int)$this->auth->id;
        if ($userId <= 0) {
            $this->error('请先登录');
        }
        $orderNo = trim((string)$this->request->post('order_no', (string)$this->request->request('order_no')));
        if ($orderNo === '') {
            $this->error('缺少订单号');
        }
        $order = Db::name(MemberService::ORDER_TABLE)->where('order_no', $orderNo)->where('user_id', $userId)->find();
        if (!$order) {
            $this->error('订单不存在');
        }
        if ((int)$order['pay_status'] === 3) {
            $this->error('订单已支付');
        }
        $openid = Db::name('user')->where('id', $userId)->value('openid');
        if (empty($openid)) {
            $this->error('未获取到微信openid，无法支付');
        }
        $data = $this->submitPay($orderNo, $order['package_name'], $order['price'], $openid);
        if (is_null($data)) {
            $this->error('支付下单失败,请稍后重试');
        }
        $this->success('success', ['order_no' => $orderNo, 'pay' => $data]);
    }

    /**
     * 我的充值记录
     */
    public function orders()
    {
        $userId = (int)$this->auth->id;
        if ($userId <= 0) {
            $this->error('请先登录');
        }
        $list = Db::name(MemberService::ORDER_TABLE)
            ->where('user_id', $userId)
            ->order('id desc')
            ->field('id, order_no, package_name, membertype_before, membertype_after, member_duration, member_unit, price, pay_status, pay_time, expiry_before, expiry_after, createtime')
            ->select();
        foreach ($list as &$row) {
            $row['pay_status_text'] = [
                1 => '待支付',
                2 => '支付中',
                3 => '已支付',
                4 => '已取消',
            ][(int)$row['pay_status']] ?? '未知';
            $row['duration_text'] = MemberService::durationText((int)$row['member_duration'], (string)$row['member_unit']);
            $row['price'] = (float)$row['price'];
        }
        unset($row);
        $this->success('success', $list ?: []);
    }

    /**
     * 微信支付回调
     */
    public function memnotify()
    {
        $date = date('Ymd');
        $path = RUNTIME_PATH . 'logs/memrechargedata/' . $date;
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $pay = \addons\epay\library\Service::checkNotify('wechat');
        if (!$pay) {
            file_put_contents($path . "/" . $date . ".log", '签名错误' . PHP_EOL, FILE_APPEND);
            return '签名错误';
        }

        try {
            $data = $pay->verify();
            if (
                isset($data['return_code']) && $data['return_code'] === 'SUCCESS' &&
                isset($data['result_code']) && $data['result_code'] === 'SUCCESS'
            ) {
                $outTradeNo = $data['out_trade_no'] ?? '';
                $orderNo = (strpos($outTradeNo, '::') !== false) ? explode('::', $outTradeNo)[0] : $outTradeNo;
                $payTime = isset($data['time_end']) ? strtotime($data['time_end']) : time();

                $order = Db::name(MemberService::ORDER_TABLE)->where('order_no', $orderNo)->find();
                if ($order && (int)$order['pay_status'] !== 3) {
                    Db::startTrans();
                    try {
                        $user = Db::name('user')->where('id', (int)$order['user_id'])->find();
                        if ($user) {
                            $expiryBefore = MemberService::normalizeMemberTime($user['member_time']);
                            $base = max(time(), $expiryBefore);
                            $expiryAfter = MemberService::calcExpiry($base, (int)$order['member_duration'], (string)$order['member_unit']);
                            Db::name('user')
                                ->where('id', (int)$order['user_id'])
                                ->update([
                                    'membertype' => (int)$order['membertype_after'],
                                    'member_time' => $expiryAfter,
                                    'updatetime' => time(),
                                ]);
                        }
                        Db::name(MemberService::ORDER_TABLE)
                            ->where('id', (int)$order['id'])
                            ->update([
                                'pay_status' => 3,
                                'pay_time'   => $payTime,
                                'expiry_after' => $expiryAfter ?? null,
                                'updatetime' => time(),
                            ]);
                        Db::commit();
                    } catch (\Exception $e) {
                        Db::rollback();
                        file_put_contents($path . "/" . $date . ".log", '处理异常-' . $e->getMessage() . PHP_EOL, FILE_APPEND);
                    }
                }
            }
        } catch (\Exception $e) {
            file_put_contents($path . "/" . $date . ".log", '回调异常-' . $e->getMessage() . PHP_EOL, FILE_APPEND);
        }

        return $pay->success()->send();
    }

    /**
     * 提交微信支付，返回支付参数
     */
    protected function submitPay(string $orderNo, string $title, $price, string $openid)
    {
        $paramss = [
            'amount'    => $price,
            'orderid'   => $orderNo,
            'title'     => '会员充值-' . $title,
            'notifyurl' => 'https://' . $_SERVER['SERVER_NAME'] . '/api/Memberrecharge/memnotify',
            'method'    => 'miniapp',
            'type'      => 'wechat',
            'openid'    => $openid,
        ];
        try {
            $data = \addons\epay\library\Service::submitOrder($paramss);
            if ($data instanceof \Yansongda\Supports\Collection) {
                $data = $data->all();
            } elseif (is_object($data) && method_exists($data, 'toArray')) {
                $data = $data->toArray();
            }
            return $data;
        } catch (\Exception $e) {
            return null;
        }
    }
}

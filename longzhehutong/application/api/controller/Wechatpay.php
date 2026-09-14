<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\Bill;
use app\common\model\MoneyLog;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use think\Config;
use think\Db;
use Yansongda\Pay\Pay;
use Yansongda\Pay\Log;
/**
 * 示例接口
 */
class Wechatpay extends Api
{

    //如果$noNeedLogin为空表示所有接口都需要登录才能请求
    //如果$noNeedRight为空表示所有接口都需要验证权限才能请求
    //如果接口已经设置无需登录,那也就无需鉴权了
    //
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['recharge_rules', 'balrecharge','apijilu','pay','balnotify','Withdrawal','memrecharge','ansrecharge','vip','withlist','hcharge','giftCartNotify','payQrcode'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['*'];

    /**
     * @return void 
     * @throws \Exception
     *
     * 下单操作
     */
    public function hcharge(){

        $user = $this->auth->id;;
        $params = $this->request->param();
        $time = time();
        $openid = Db::name('user')->where('id', $user)->value('openid');
        if (empty($openid)) {
          $this->error('未获取到openid');
        }

        $order = [
            'order_sn'    => date('Ymd') . str_pad(mt_rand(10000, 99999), 5, '0', STR_PAD_LEFT),
            'type'        => $params['type'],
            'pay_price'   => $params['pay_price'],
            'phone_id'    => $params['phone_id'],
            'name'        => $params['name'],
            'mobile'      => $params['mobile'],
            'city'        => $params['city'],
            'address'     => $params['address'],
            'time_expire' => $time + 86400,
        ];
        $paramss = [
            'amount'      => $params['pay_price'],
            'orderid'     => $order['order_sn'],
            'title'       => '下单物流',
            // 回调地址需带上斜杠，避免拼成域名后直接接 api
            'notifyurl'   => 'https://' . $_SERVER['SERVER_NAME'] . '/api/Wechatpay/giftCartNotify',
            'method'      => 'miniapp',
            'type'        => 'wechat',
            'time_expire' => $time + 86400,
            'openid'      => $openid,
        ];
        $order['user_id'] = $user;
        $order['createtime'] = $time;
        $res = Db::name('order')->insertGetId($order);
        if ($res){
            $data = \addons\epay\library\Service::submitOrder($paramss);
            $this->success('请求成功',$data);
        }
    }

    /**
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 继续支付
     */
    public function pay()
    {
        // 前端传入的 order_id 这里按下单时生成的 order_sn 处理
        $order_sn = $this->request->param('order_id');
        if (!$order_sn) {
            $this->error('订单号不能为空');
        }

        $orderInfo = Db::name('order')->where('orderid', $order_sn)->find();
        if (!$orderInfo) {
            $this->error('订单不存在或者已支付');
        }

        // 标记为待支付或进行中（根据你的业务约定，这里沿用原来的 2）
//        Db::name('order')->where('order_sn', $order_sn)->update(['pay_status' => 2]);

        $openid = Db::name('user')->where('id', $orderInfo['userid'])->value('openid');
        if (empty($openid)) {
            $this->error('未获取到openid');
        }

        $paramss = [
            'amount'    => $orderInfo['pay_price'],
            // 继续支付必须与首次下单使用同一个 out_trade_no，这里用 order_sn
            'orderid'   => $orderInfo['orderid'],
            'title'    => '下单物流',
            'notifyurl' => 'https://' . $_SERVER['SERVER_NAME'] . '/api/Wechatpay/giftCartNotify',
            'method'    => 'miniapp',
            'type'      => 'wechat',
            'openid'    => $openid,
        ];

        $data = \addons\epay\library\Service::submitOrder($paramss);
        $this->success('请求成功', $data);
    }
    /**
     * 生成订单的支付二维码（微信 Native 扫码支付）
     * 传入本系统 order_id（支持订单主键 id 或订单号 orderid）
     * 返回 code_url，前端可据此生成二维码供用户扫码支付
     *
     * @return void
     */
    public function payQrcode()
    {
        $order_id = $this->request->param('order_id');
        if (empty($order_id)) {
            $this->error('订单号不能为空');
        }

        // 支持本系统订单主键 id 或订单号 orderid
        $orderInfo = Db::name('order')->where('id', $order_id)->find();
        if (!$orderInfo) {
            $orderInfo = Db::name('order')->where('orderid', $order_id)->find();
        }
        if (!$orderInfo) {
            $this->error('订单不存在');
        }

        if (isset($orderInfo['pay_status']) && $orderInfo['pay_status'] == 3) {
            $this->error('订单已支付');
        }

        // 每次生成使用唯一 out_trade_no，避免“请求重入时参数与首次请求不一致”
        $outTradeNo = $orderInfo['orderid'] . '::' . time();
        $paramss = [
            'amount'    => $orderInfo['pay_price'],
            'orderid'   => $outTradeNo,
            'title'     => '订单支付-' . ($orderInfo['orderid'] ?? ''),
            'notifyurl' => 'https://' . $_SERVER['SERVER_NAME'] . '/api/Wechatpay/giftCartNotify',
            'method'    => 'scan',
            'type'      => 'wechat',
        ];

        try {
            $data = \addons\epay\library\Service::submitOrder($paramss);
        } catch (\Exception $e) {
            $this->error('生成支付二维码失败：' . $e->getMessage());
        }

        $codeUrl = null;
        if (is_object($data) && method_exists($data, 'get')) {
            $codeUrl = $data->get('code_url');
        } elseif (is_array($data)) {
            $codeUrl = $data['code_url'] ?? null;
        }

        if (empty($codeUrl)) {
            $this->error('生成支付二维码失败，未获取到 code_url');
        }

        // 根据 code_url 生成二维码图片并保存到本地，返回带域名的图片链接
        $qrcodeUrl = '';
        try {
            $size = (int) $this->request->param('size', 280);
            $size = $size < 100 ? 280 : ($size > 600 ? 600 : $size);
            $qrCode = new QrCode($codeUrl);
            $qrCode->setSize($size);
            $qrCode->setMargin(10);
            $qrCode->setForegroundColor(new Color(0, 0, 0));
            $qrCode->setBackgroundColor(new Color(255, 255, 255));
            $writer = new PngWriter();
            $result = $writer->write($qrCode);

            $saveDir = ROOT_PATH . 'public' . DS . 'uploads' . DS . 'qrcode' . DS . 'pay' . DS;
            if (!is_dir($saveDir) && !mkdir($saveDir, 0755, true)) {
                $this->error('二维码保存目录创建失败');
            }
            $filename = 'pay_' . $orderInfo['orderid'] . '_' . time() . '.png';
            $savePath = $saveDir . $filename;
            $result->saveToFile($savePath);

            $baseUrl = $this->request->domain();
            $qrcodeUrl = $baseUrl . '/uploads/qrcode/pay/' . $filename;
        } catch (\Exception $e) {
            $this->error('生成二维码图片失败：' . $e->getMessage());
        }

        $this->success('请求成功', [
            'code_url'     => $codeUrl,
            'qrcode_url'   => $qrcodeUrl,
            'order_id'     => $orderInfo['id'],
            'orderid'      => $orderInfo['orderid'],
            'pay_price'    => $orderInfo['pay_price'],
        ]);
    }
    
    // 会员充值回调地址
    public function giftCartNotify()
    {
        // 建议改成相对路径或框架提供的 runtime 目录
        $path = RUNTIME_PATH . 'logs/wechatrechargedata/' . date('Ymd');
        $date = date('Ymd');
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $pay = \addons\epay\library\Service::checkNotify('wechat');
        if (!$pay) {
            file_put_contents($path . "/" . $date . ".log", '签名错误' . PHP_EOL, FILE_APPEND);
            return '签名错误';
        }

        $data = $pay->verify();
        file_put_contents($path . "/" . $date . ".log", '验签……' . PHP_EOL, FILE_APPEND);
        file_put_contents($path . "/" . $date . ".log", json_encode($data) . PHP_EOL, FILE_APPEND);
        try {
            if (
                isset($data['return_code']) && $data['return_code'] == 'SUCCESS' &&
                isset($data['result_code']) && $data['result_code'] == 'SUCCESS'
            ) {
                $pay_time = isset($data['time_end']) ? strtotime($data['time_end']) : time();
                // 关键：用 out_trade_no 对应我们本地的 orderid（支持 orderid::timestamp 格式）
                $outTradeNo = $data['out_trade_no'];
                $order_no = (strpos($outTradeNo, '::') !== false) ? explode('::', $outTradeNo)[0] : $outTradeNo;

                $info = Db::name('order')->where('orderid', $order_no)->find();
                if ($info && $info['pay_status'] != 2) {
                    $update = [
                        'pay_status' => 3,
                        'pay_time'   => $pay_time,
                        'payment_method_id'   => 6,
                    ];
                    $res = Db::name('order')->where('orderid', $order_no)->update($update);

                    if ($res) {
                        // 账单记录
                        Db::name('bill')->insert([
                            'uid'        => $info['user_id'],
                            'turnover'   => 1, // 1=支出
                            'order_name' => '订单支付',
                            'order_price'=> $info['pay_price'],
                            'createtime' => time(),
                        ]);
                        // 分佣
                        $p_id = Db::name('user')->where('id', $info['user_id'])->value('p_id');
                        if ($p_id > 0) {
                            $bili    = Config::get('site.scale');
                            $fenyong = $info['pay_price'] * $bili / 100;
                            $usermoney = Db::name('user')->where('id', $p_id)->value('money');
                            $fey = Db::name('user')->where('id', $p_id)->setInc('money', $fenyong);
                            if ($fey) {
                                MoneyLog::create([
                                    'user_id' => $p_id,
                                    'money'   => $fenyong,
                                    'before'  => $usermoney,
                                    'after'   => $usermoney + $fenyong,
                                    'memo'    => '下级用户分佣',
                                ]);
                            }
                        }
                    }
                }
            }

            file_put_contents($path . "/" . '-' . time() . ".log", '保存成功-' . 'success', FILE_APPEND);
        } catch (\Exception $e) {
            file_put_contents($path . "/" . '-' . time() . ".log", '异常-' . $e->getMessage(), FILE_APPEND);
        }

        return $pay->success()->send();
    }
}
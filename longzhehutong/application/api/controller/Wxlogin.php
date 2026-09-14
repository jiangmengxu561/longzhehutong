<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\User;
use think\Db;
use think\Config;
use think\Validate;

/**
 * 示例接口
 */
class Wxlogin extends Api
{

    //如果$noNeedLogin为空表示所有接口都需要登录才能请求
    //如果$noNeedRight为空表示所有接口都需要验证权限才能请求
    //如果接口已经设置无需登录,那也就无需鉴权了
    //
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['weChatRegister','getPhoneNumber','login','unifiedLogin','dricerweChatRegister','appMobileLogin','appSendSms'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['test2'];

    /**
     * APP端发送短信验证码
     *
     * @param string $mobile 手机号
     * @return void
     */
    public function appSendSms()
    {
        $mobile = $this->request->post('mobile');
        if (!$mobile) {
            $this->error('手机号不能为空');
        }
        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error('手机号格式不正确');
        }
        // 5分钟内同一手机号未使用过的验证码不重复发送
        $is_send = Db::name('sms')
            ->where('mobile', $mobile)
            ->where('event', 'login')
            ->where('times', 0)
            ->whereTime('createtime', '>=', date('Y-m-d H:i:s', strtotime('-5 minutes')))
            ->count();
        if ($is_send) {
            $this->error('请勿重复发送');
        }
        $code = rand(100000, 999999);
        $data = [
            'event'     => 'login',
            'mobile'    => $mobile,
            'code'      => $code,
            'createtime'=> time()
        ];
        $res = Db::name('sms')->insert($data);
        if (!$res) {
            $this->error('发送失败');
        }
        $ret = sendSmss($mobile, '您的验证码为' . $code . '，请勿泄露于他人！');
        if (isset($ret['code']) && $ret['code'] == 0) {
            $this->success('发送成功');
        }
        $this->error('发送失败');
    }

    /**
     * APP端手机号验证码登录
     * 验证码校验通过后，已存在用户直接登录，不存在则自动注册
     *
     * @param string $mobile   手机号
     * @param string $captcha 短信验证码
     * @param string $channel_id 渠道来源
     * @param string $invitation 邀请码
     * @return array
     */
    public function appMobileLogin()
    {
        $mobile   = $this->request->post('mobile');
        $captcha  = $this->request->post('captcha');
        $channel_id = $this->request->post('channel_id');
        $invitation = $this->request->post('invitation');
        if (!$mobile || !$captcha) {
            $this->error('手机号和验证码不能为空');
        }
        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error('手机号格式不正确');
        }
        // 校验验证码（event=login，5分钟内有效）
        $code = Db::name('sms')
            ->where('mobile', $mobile)
            ->where('event', 'login')
            ->where('times', 0)
            ->whereTime('createtime', '>=', date('Y-m-d H:i:s', strtotime('-5 minutes')))
            ->order('id desc')
            ->value('code');
        if (!$code || $captcha != $code) {
            $this->error('验证码错误或已过期');
        }
        // 标记验证码已使用
        Db::name('sms')
            ->where('mobile', $mobile)
            ->where('event', 'login')
            ->where('code', $code)
            ->update(['times' => 1, 'updatetime' => time()]);

        $identity = 1;
        $user = Db::name('user')->where('mobile', $mobile)->where('identity', $identity)->find();
        if ($user) {
            if (isset($user['status']) && $user['status'] != 'normal') {
                $this->error('账号已被锁定');
            }
            $this->auth->direct($user['id']);
        } else {
            // 一个号码只能注册一个身份
            $mobileUser = Db::name('user')->where('mobile', $mobile)->find();
            if ($mobileUser && (int)$mobileUser['identity'] !== (int)$identity) {
                $this->error('该手机号已注册其他身份，一个号码只能注册一个身份');
            }
            // 身份为1时校验渠道来源
            if ((int)$identity === 1 && empty($channel_id)) {
                $this->error('请选择渠道来源');
            }
            $post = [
                'channel_id'  => $channel_id ?? '',
                'username'    => 'APP用户' . $mobile,
                'identity'    => $identity,
                'membertype'  => 2,
                'member_time' => strtotime(date('Y-m-d 23:59:59')),
            ];
            if (!empty($invitation)) {
                $pid = Db::name('user')->where('invitation', $invitation)->value('id');
                if ($pid) {
                    $post['pid'] = $pid;
                }
            }
            $post['invitation'] = generateRandomInviteCode();
            $ret = $this->auth->register($post['username'], '888888', '', $mobile, $post);
            if (!$ret) {
                $this->error($this->auth->getError());
            }
        }
        $data1 = ['userinfo' => $this->auth->getUserinfo()];
        $this->success('登录成功', $data1);
    }

    /**
     * 用户登录
     *
     * @param string $account  账号,用户名、邮箱、手机号
     * @param string $password 密码
     * @return boolean
     */
    public function login()
    {
        $account = $this->request->post('account');
        $password = $this->request->post('password');
        if (!$account || !$password) {
            $this->error(__('Invalid parameters'));
        }
        $ret = $this->auth->login($account, $password);
        if ($ret) {
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Logged in successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 用户统一登录接口
     * 支持微信登录code和获取手机号参数一起传递
     *
     * @param string $code 微信登录code
     * @param string $phoneCode 微信获取手机号的code参数（新版本API）
     * @param string $nickname 用户昵称（可选）
     * @param string $avatarUrl 用户头像（可选）
     * @param int $identity 身份（为空默认1，一个号码只能注册一个身份）
     * @return array
     */
    public function weChatRegister()
    {
        $data = $this->request->param();
//        print_r($data);die;
        // 验证必要参数
        if (!$data['code']) {
            $this->error('code不能为空');
        }
        // 身份参数，为空默认1
        $identity = empty($data['identity']) ? 1 : $data['identity'];

        $sessionInfo = $this->getSessionKey($data['code'], $identity);
        $sessionKey = $sessionInfo['session_key']; 
        $openid = $sessionInfo['openid'];
//     print_r($data);die;
//         2. 解密手机号
        $phoneInfo = $this->decryptPhoneNumber($sessionKey, $data['encryptedData'], $data['iv']);

        // 检查用户是否已存在
        $user = Db::name('user')->where('openid', $openid)->where('identity', $identity)->find();
        
        if ($user) {
            $this->auth->direct($user['id']);
        } else {
            // 一个号码只能注册一个身份 
            $mobile = Db::name('user')->where('mobile', $phoneInfo['purePhoneNumber'])->find();
            if ($mobile) {
                if ((int)$mobile['identity'] !== (int)$identity) {
                    $this->error('该手机号已注册其他身份，一个号码只能注册一个身份');
                }
                $this->error('该手机号已经绑定别的微信，请更换手机号');
            }
            // 仅身份为1时校验渠道来源
            if ((int)$identity === 1 && empty($data['channel_id'])) {
                $this->error('请选择渠道来源');
            }
            $post = [
                'openid' => $openid,
                'channel_id' => $data['channel_id'] ?? '',
                'username' => '微信用户'.$phoneInfo['purePhoneNumber'],
                'identity' => $identity,
                'membertype' => 2,
                'member_time' => strtotime(date('Y-m-d 23:59:59')),
                'avatar' =>'/uploads/20250902/0a61e001103c5d94b00d52c3916de5aa.jpg'
            ];
            if (isset($data['invitation'])){
                $pid = Db::name('user')->where('invitation', $data['invitation'])->value('id');
                $post['pid'] = $pid;
            }

            $post['invitation'] = generateRandomInviteCode();
            // 注册用户
            $ret = $this->auth->register($post['username'], '888888', '', $phoneInfo['purePhoneNumber'], $post);
            if (!$ret) {
                $this->error($this->auth->getError());
            }
        }
        $data1 = ['userinfo' => $this->auth->getUserinfo()];
        $this->success('登录成功', $data1);
    }

    /**
     * 获取 session_key
     * identity=3 使用独立小程序配置
     */
    private function getSessionKey($code, $identity = 1) {
        $url = "https://api.weixin.qq.com/sns/jscode2session";

        if ((int)$identity === 3) {
            $appid = 'wx52a50d8bf247cacd';
            $secret = 'bb9544bd865b4dd6d85a044ec51d02a7';
        } else {
            $appid = 'wx1e4884865032dd4f';
            $secret = '3a255493f9c371b673ed3e247b30548a';
        }

        $params = [
            'appid' => $appid,
            'secret' => $secret,
            'js_code' => $code,
            'grant_type' => 'authorization_code'
        ];

        $requestUrl = $url . '?' . http_build_query($params);
        $response = file_get_contents($requestUrl);
        $result = json_decode($response, true);

        if (isset($result['errcode']) && $result['errcode'] != 0) {
           $this->error('登录失败：' . $result['errmsg']);
        }

        return $result;
    }

    /**
     * 解密手机号
     */
    private function decryptPhoneNumber($sessionKey, $encryptedData, $iv) {
        if (strlen($sessionKey) != 24) {
            $this->error('session_key 长度错误：' );
        }

        $aesKey = base64_decode($sessionKey);
        $aesIV = base64_decode($iv);
        $aesCipher = base64_decode($encryptedData);

        $result = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);

        if (!$result) {
            $this->error('获取手机号失败,请重试' );
        }

        $dataObj = json_decode($result, true);

        if ($dataObj == NULL) {
            $this->error('解析手机号数据失败' );
        }

//        // 验证 watermark
//        if ($dataObj['watermark']['appid'] !== 'wx1e4884865032dd4f') {
//            $this->error('appid 不匹配' );
//        }

        return [
            'phoneNumber' => $dataObj['phoneNumber'],
            'purePhoneNumber' => $dataObj['purePhoneNumber'],
            'countryCode' => $dataObj['countryCode']
        ];
    }
    /**
     * 用户统一登录接口
     * 支持微信登录code和获取手机号参数一起传递
     *
     * @param string $code 微信登录code
     * @param string $phoneCode 微信获取手机号的code参数（新版本API）
     * @param string $nickname 用户昵称（可选）
     * @param string $avatarUrl 用户头像（可选）
     * @return array
     */
    public function dricerweChatRegister()
    {
        $data = $this->request->param();
//        print_r($data);die;
        // 验证必要参数
        if (!$data['code']) {
            $this->error('code不能为空');
        }
        $sessionInfo = $this->dricergetSessionKey($data['code']);
        $sessionKey = $sessionInfo['session_key'];
        $openid = $sessionInfo['openid'];
//     print_r($data);die;
//         2. 解密手机号
        $phoneInfo = $this->decryptPhoneNumber($sessionKey, $data['encryptedData'], $data['iv']);

        // 检查用户是否已存在
        $user = Db::name('user')->where('openid', $openid)->find();

        if ($user) {
            $this->auth->direct($user['id']);
        } else {
            $post = [
                'openid' => $openid,
                'channel_id' => $data['channel_id']??'',
                'username' => '微信用户'.$phoneInfo['purePhoneNumber'],
            ];
            if (isset($data['invitation'])){
                $pid = Db::name('user')->where('invitation', $data['invitation'])->value('id');
                $post['pid'] = $pid;
            }

            $post['invitation'] = generateRandomInviteCode();
            // 注册用户
            $ret = $this->auth->register($post['username'], '888888', '', $phoneInfo['purePhoneNumber'], $post);
            if (!$ret) {
                $this->error($this->auth->getError());
            }
        }

        $data1 = ['userinfo' => $this->auth->getUserinfo()];
        $this->success('登录成功', $data1);
    }

    /**
     * 获取 session_key
     */
    private function dricergetSessionKey($code) {
        $url = "https://api.weixin.qq.com/sns/jscode2session";
        $params = [
            'appid' => 'wxc776fdbfe0011e25',
            'secret' => 'e3f457dbfdd2bfa3af034bad319ad48a',
            'js_code' => $code,
            'grant_type' => 'authorization_code'
        ];
        $requestUrl = $url . '?' . http_build_query($params);
        $response = file_get_contents($requestUrl);
        $result = json_decode($response, true);

        if (isset($result['errcode']) && $result['errcode'] != 0) {
            $this->error('登录失败：' . $result['errmsg']);
        }

        return $result;
    }


}
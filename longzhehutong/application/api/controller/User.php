<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\Ems;
use app\common\library\Sms;
use fast\Random;
use think\Config;
use think\Db;
use think\Validate;

/**
 * 会员接口
 */
class User extends Api
{
    protected $noNeedLogin = ['login', 'mobilelogin', 'register', 'resetpwd', 'changeemail', 'changemobile', 'third','sensms', 'syncLogisticsMobile', 'expireMember'];
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();

        if (!Config::get('fastadmin.usercenter')) {
            $this->error(__('User center already closed'));
        }

    }

    /**
     * 会员中心
     */
    public function index()
    { 
        $this->success('', ['welcome' => $this->auth->nickname]);
    }

    /**
     * 会员登录
     *
     * @ApiMethod (POST)
     * @ApiParams (name="account", type="string", required=true, description="账号")
     * @ApiParams (name="password", type="string", required=true, description="密码")
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
     * 手机验证码登录
     *
     * @ApiMethod (POST)
     * @ApiParams (name="mobile", type="string", required=true, description="手机号")
     * @ApiParams (name="captcha", type="string", required=true, description="验证码")
     */
    public function mobilelogin()
    {
        $mobile = $this->request->post('mobile');
        $captcha = $this->request->post('captcha');
        $identity = $this->request->post('identity');
        if (!$mobile || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        $result = Sms::check($mobile, $captcha, 'register');
        if (!$result) {
            $this->error(__('Captcha is incorrect'));
        }
        $user = Db::name('user')->where('mobile', $mobile)->where('identity',$identity)->find();
        if ($user) {
            if ($user['status'] != 'normal') {
                $this->error(__('Account is locked'));
            }
            //如果已经有账号则直接登录
            $ret = $this->auth->direct($user['id']);
        } else {
            $ret = $this->auth->register($mobile, Random::alnum(), '', $mobile, []);
            $this->error('用户不存在,请先注册');
        }
        if ($ret) {
            Sms::flush($mobile, 'mobilelogin');
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Logged in successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 注册会员
     *
     * @ApiMethod (POST)
     * @ApiParams (name="username", type="string", required=true, description="用户名")
     * @ApiParams (name="password", type="string", required=true, description="密码")
     * @ApiParams (name="email", type="string", required=true, description="邮箱")
     * @ApiParams (name="mobile", type="string", required=true, description="手机号")
     * @ApiParams (name="code", type="string", required=true, description="验证码")
     */
    public function register()
    {

//        $password = $this->request->post('password');
//        $email = $this->request->post('email');
        $mobile = $this->request->post('mobile');
        $captcha = $this->request->post('captcha');
        $identity = $this->request->post('identity');
        $cardid = $this->request->post('cardid');
        $business_type = $this->request->post('business_type');
        $license = $this->request->post('license');
        $business_license = $this->request->post('business_license');
        $carnumber = $this->request->post('carnumber');
        $city = $this->request->post('city');
//        if ($identity == 3){
//            if (!$carnumber || !$city) {
//                $this->error(__('车牌和城市不能为空'));
//            }
//        }
        $username = $mobile;
        $password =md5(rand(9999999,100000000000)) ;
        if (!$username || !$password) {
            $this->error(__('Invalid parameters'));
        }
//        if ($email && !Validate::is($email, "email")) {
//            $this->error(__('Email is incorrect'));
//        }
        if ($mobile && !Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        if ($cardid && !Validate::regex($cardid, "^[1-9]\d{5}(18|19|20)\d{2}(0[1-9]|1[0-2])(0[1-9]|[12][0-9]|3[01])\d{3}(\d|X|x)$")&& !Validate::regex($identity, "^[1-9]\d{5}\d{2}(0[1-9]|1[0-2])(0[1-9]|[12][0-9]|3[01])\d{2}$")) {
            $this->error(__('身份证不正确'));
        }
        $code = Db::name('sms')
            ->where('mobile',$mobile)
            ->where('event','login')
            ->whereTime('createtime', '>=', date('Y-m-d H:i:s', strtotime('-5 minutes')))
            ->value('code');
//        if ($captcha != $code){
//            $this->error('验证码错误');
//        }
        $user = Db::name('user')->where('mobile', $mobile)->where('identity',$identity)->find();
        if ($user){
            $this->error('用户已存在');
        }
        $arr = [
            'identity' => $identity,
            'business_type' => $business_type,
            'license' => $license,
            'cardid' => $cardid,
            'business_license' => $business_license,
            'carnumber' => $carnumber,
            'city' => $city,
        ];
        $ret = $this->auth->register($username, $password, '', $mobile, $arr);
        if ($ret) {
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Sign up successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 退出登录
     * @ApiMethod (POST)
     */
    public function logout()
    {
        if (!$this->request->isPost()) {
            $this->error(__('Invalid parameters'));
        }
        $this->auth->logout();
        $this->success(__('Logout successful'));
    }

    /**
     * 修改会员个人信息
     *
     * @ApiMethod (POST)
     * @ApiParams (name="avatar", type="string", required=true, description="头像地址")
     * @ApiParams (name="username", type="string", required=true, description="用户名")
     * @ApiParams (name="nickname", type="string", required=true, description="昵称")
     * @ApiParams (name="bio", type="string", required=true, description="个人简介")
     */
    public function profile()
    {
        $user = $this->auth->getUser();
        $this->error('暂不支持修改');
        $username = $this->request->post('username');
        $nickname = $this->request->post('nickname');
        $bio = $this->request->post('bio');
        $avatar = $this->request->post('avatar', '', 'trim,strip_tags,htmlspecialchars');
        if ($username) {
            $exists = \app\common\model\User::where('username', $username)->where('id', '<>', $this->auth->id)->find();
            if ($exists) {
                $this->error(__('Username already exists'));
            }
            $user->username = $username;
        }
        if ($nickname) {
            $exists = \app\common\model\User::where('nickname', $nickname)->where('id', '<>', $this->auth->id)->find();
            if ($exists) {
                $this->error(__('Nickname already exists'));
            }
            $user->nickname = $nickname;
        }
        $user->bio = $bio;
        $user->avatar = $avatar;
        $user->save();
        $this->success();
    }

    /**
     * 修改邮箱
     *
     * @ApiMethod (POST)
     * @ApiParams (name="email", type="string", required=true, description="邮箱")
     * @ApiParams (name="captcha", type="string", required=true, description="验证码")
     */
    public function changeemail()
    {
        $user = $this->auth->getUser();
        $email = $this->request->post('email');
        $captcha = $this->request->post('captcha');
        if (!$email || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::is($email, "email")) {
            $this->error(__('Email is incorrect'));
        }
        if (\app\common\model\User::where('email', $email)->where('id', '<>', $user->id)->find()) {
            $this->error(__('Email already exists'));
        }
        $result = Ems::check($email, $captcha, 'changeemail');
        if (!$result) {
            $this->error(__('Captcha is incorrect'));
        }
        $verification = $user->verification;
        $verification->email = 1;
        $user->verification = $verification;
        $user->email = $email;
        $user->save();

        Ems::flush($email, 'changeemail');
        $this->success();
    }

    /**
     * 修改手机号
     *
     * @ApiMethod (POST)
     * @ApiParams (name="mobile", type="string", required=true, description="手机号")
     * @ApiParams (name="captcha", type="string", required=true, description="验证码")
     */
    public function changemobile()
    {
        $user = $this->auth->getUser();
        $mobile = $this->request->post('mobile');
        $captcha = $this->request->post('captcha');
        if (!$mobile || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        if (\app\common\model\User::where('mobile', $mobile)->where('id', '<>', $user->id)->find()) {
            $this->error(__('Mobile already exists'));
        }
        $result = Sms::check($mobile, $captcha, 'changemobile');
        if (!$result) {
            $this->error(__('Captcha is incorrect'));
        }
        $verification = $user->verification;
        $verification->mobile = 1;
        $user->verification = $verification;
        $user->mobile = $mobile;
        $user->save();

        Sms::flush($mobile, 'changemobile');
        $this->success();
    }

    /**
     * 第三方登录
     *
     * @ApiMethod (POST)
     * @ApiParams (name="platform", type="string", required=true, description="平台名称")
     * @ApiParams (name="code", type="string", required=true, description="Code码")
     */
    public function third()
    {
        $url = url('user/index');
        $platform = $this->request->post("platform");
        $code = $this->request->post("code");
        $config = get_addon_config('third');
        if (!$config || !isset($config[$platform])) {
            $this->error(__('Invalid parameters'));
        }
        $app = new \addons\third\library\Application($config);
        //通过code换access_token和绑定会员
        $result = $app->{$platform}->getUserInfo(['code' => $code]);
        if ($result) {
            $loginret = \addons\third\library\Service::connect($platform, $result);
            if ($loginret) {
                $data = [
                    'userinfo'  => $this->auth->getUserinfo(),
                    'thirdinfo' => $result
                ];
                $this->success(__('Logged in successful'), $data);
            }
        }
        $this->error(__('Operation failed'), $url);
    }

    /**
     * 重置密码
     *
     * @ApiMethod (POST)
     * @ApiParams (name="mobile", type="string", required=true, description="手机号")
     * @ApiParams (name="newpassword", type="string", required=true, description="新密码")
     * @ApiParams (name="captcha", type="string", required=true, description="验证码")
     */
    public function resetpwd()
    {
        $type = $this->request->post("type", "mobile");
        $mobile = $this->request->post("mobile");
        $email = $this->request->post("email");
        $newpassword = $this->request->post("newpassword");
        $captcha = $this->request->post("captcha");
        if (!$newpassword || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        //验证Token
        if (!Validate::make()->check(['newpassword' => $newpassword], ['newpassword' => 'require|regex:\S{6,30}'])) {
            $this->error(__('Password must be 6 to 30 characters'));
        }
        if ($type == 'mobile') {
            if (!Validate::regex($mobile, "^1\d{10}$")) {
                $this->error(__('Mobile is incorrect'));
            }
            $user = \app\common\model\User::getByMobile($mobile);
            if (!$user) {
                $this->error(__('User not found'));
            }
            $ret = Sms::check($mobile, $captcha, 'resetpwd');
            if (!$ret) {
                $this->error(__('Captcha is incorrect'));
            }
            Sms::flush($mobile, 'resetpwd');
        } else {
            if (!Validate::is($email, "email")) {
                $this->error(__('Email is incorrect'));
            }
            $user = \app\common\model\User::getByEmail($email);
            if (!$user) {
                $this->error(__('User not found'));
            }
            $ret = Ems::check($email, $captcha, 'resetpwd');
            if (!$ret) {
                $this->error(__('Captcha is incorrect'));
            }
            Ems::flush($email, 'resetpwd');
        }
        //模拟一次登录
        $this->auth->direct($user->id);
        $ret = $this->auth->changepwd($newpassword, '', true);
        if ($ret) {
            $this->success(__('Reset password successful'));
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * @return false|string
     * 发送验证码
     */
    public function sensms()
    {
        $mobile = $this->request->post('mobile');
        if (!$mobile){
            $this->error('手机号不能为空');
        }
        $event = $this->request->post('event');
        if (!$event){
            $this->error('验证码类型不能为空');
        }
        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        $code = rand(100000,999999);
        $data = [
            'event' => $event,
            'mobile' => $mobile,
            'code' => $code,
            'createtime'=>time()
        ];
        $is_send = Db::name('sms')
            ->where('mobile',$mobile)
            ->where('event',$event)
            ->where('times',0)
            ->whereTime('createtime', '>=', date('Y-m-d H:i:s', strtotime('-5 minutes')))
            ->count();
        if ($is_send){
            return $this->error('请勿重复发送');
        }
        $res = Db::name('sms')->insert($data);
        if ($res){
            $ret = sendSmss($mobile
                , '您的验证码为'.$code . '请勿泄露于他人！');
            if ($ret['code'] == 0){
                return $this->success('发送成功');
            }else{
                return $this->error('发送失败');
            }
        }else{
            return $this->error('发送失败');
        }
    }
    /**
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     *
     * 会员资金流水
     */
    public function user_money_log(){
        $user = $this->auth->id;

        $data = Db::name('user_money_log')
            ->where('user_id',$user)
            ->order('createtime desc')
            ->select();
        foreach ($data as &$item) {
            $item['createtime'] = date('Y-m-d H:i:s', $item['createtime']);
        }
        $this->success('请求成功', $data);

    }

    /**
     * 会员到期自动降级为普通用户（membertype=1），供定时任务调用
     *
     * @ApiMethod (GET|POST)
     */
    public function expireMember()
    {
        $now = time();
        $users = Db::name('user')
            ->where('membertype', '<>', 1)
            ->where('member_time', '>', 0)
            ->field('id,member_time')
            ->select();

        $expiredIds = [];
        foreach ($users as $user) {
            $memberTime = (int)$user['member_time'];
            if (strlen((string)abs($memberTime)) === 13) {
                $memberTime = (int)floor($memberTime / 1000);
            }
            if ($memberTime < $now) {
                $expiredIds[] = (int)$user['id'];
            }
        }

        if (empty($expiredIds)) {
            $this->success('无到期会员', ['count' => 0, 'user_ids' => []]);
        }

        $count = Db::name('user')
            ->where('id', 'in', $expiredIds)
            ->update(['membertype' => 1, 'updatetime' => $now]);

        $this->success('处理完成', [
            'count' => $count,
            'user_ids' => $expiredIds,
        ]);
    }

    /**
     * 同步 logistics_3 的手机号到 logistics
     */
    public function syncLogisticsMobile()
    {
        Db::startTrans();
        try {
            $sql = "UPDATE " . config('database.prefix') . "logistics l
                    INNER JOIN " . config('database.prefix') . "logistics_3 l3 ON l.id = l3.id
                    SET l.shipping_logistics_mobile = l3.shipping_logistics_mobile,
                        l.arrival_logistics_mobile = l3.arrival_logistics_mobile";
            $affectedRows = Db::execute($sql);
            Db::commit();
            $this->success('同步成功', ['affected_rows' => $affectedRows]);
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('同步失败：' . $e->getMessage());
        }
    }
}

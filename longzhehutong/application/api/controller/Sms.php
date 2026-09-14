<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\Sms as Smslib;
use app\common\model\User;
use think\Hook;
use think\Db;

/**
 * 手机短信接口
 */
class Sms extends Api
{
    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';

    /**
     * 发送验证码
     *
     * @ApiMethod (POST)
     * @ApiParams (name="mobile", type="string", required=true, description="手机号")
     * @ApiParams (name="event", type="string", required=true, description="事件名称")
     */
    public function send()
    {
        $mobile = $this->request->post("mobile");
        $event = $this->request->post("event");
        $event = $event ? $event : 'register';

        if (!$mobile || !\think\Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('手机号不正确'));
        }
        $last = Smslib::get($mobile, $event);
        if ($last && time() - $last['createtime'] < 60) {
            $this->error(__('发送频繁'));
        }
        $ipSendTotal = \app\common\model\Sms::where(['ip' => $this->request->ip()])->whereTime('createtime', '-1 hours')->count();
        if ($ipSendTotal >= 5) {
            $this->error(__('发送频繁'));
        }
//        if ($event) {
//            $userinfo = User::getByMobile($mobile);
//            if ($event == 'register' && $userinfo) {
//                //已被注册
//                $this->error(__('已被注册'));
//            } elseif (in_array($event, ['changemobile']) && $userinfo) {
//                //被占用
//                $this->error(__('已被占用'));
//            } elseif (in_array($event, ['changepwd', 'resetpwd']) && !$userinfo) {
//                //未注册
//                $this->error(__('未注册'));
//            }
//        }
        if (!Hook::get('sms_send')) {
            $this->error(__('请在后台插件管理安装短信验证插件'));
        }
        $ret = Smslib::send($mobile, null, $event);
        if ($ret) {
            $this->success(__('发送成功'));
        } else {
            $this->error(__('发送失败，请检查短信配置是否正确'));
        }
    }

    /**
     * 检测验证码
     *
     * @ApiMethod (POST)
     * @ApiParams (name="mobile", type="string", required=true, description="手机号")
     * @ApiParams (name="event", type="string", required=true, description="事件名称")
     * @ApiParams (name="captcha", type="string", required=true, description="验证码")
     */
    public function check()
    {
        $mobile = $this->request->post("mobile");
        $event = $this->request->post("event");
        $event = $event ? $event : 'register';
        $captcha = $this->request->post("captcha");
        $channel_id = $this->request->post("channel_id");
        $invitation = $this->request->post("invitation");

        if (!$mobile || !\think\Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('手机号不正确'));
        }
        if ($event) {
            $userinfo = User::getByMobile($mobile);
            if ($event == 'register' && $userinfo) {
                $this->error(__('已被注册'));
            } elseif (in_array($event, ['changemobile']) && $userinfo) {
                $this->error(__('已被占用'));
            } elseif (in_array($event, ['changepwd', 'resetpwd']) && !$userinfo) {
                $this->error(__('未注册'));
            }
        }
        $ret = Smslib::check($mobile, $captcha, $event);
        if (!$ret) {
            $this->error(__('验证码不正确'));
        } 

        if ($event === 'login') {
            $identity = 1;
            $user = Db::name('user')->where('mobile', $mobile)->where('identity', $identity)->find();
            if ($user) {
                if (isset($user['status']) && $user['status'] != 'normal') {
                    $this->error('账号已被锁定');
                }
                $this->auth->direct($user['id']);
            } else {
                $mobileUser = Db::name('user')->where('mobile', $mobile)->find();
                if ($mobileUser && (int)$mobileUser['identity'] !== (int)$identity) {
                    $this->error('该手机号已注册其他身份，一个号码只能注册一个身份');
                }
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
                $registerRet = $this->auth->register($post['username'], '888888', '', $mobile, $post);
                if (!$registerRet) {
                    $this->error($this->auth->getError());
                }
            }
            $this->success('登录成功', ['userinfo' => $this->auth->getUserinfo()]);
        }

        $this->success(__('成功'));
    }
}

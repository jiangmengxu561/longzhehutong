<?php

namespace app\admin\model\user;

use think\Model;

class RoutePrice extends Model
{
    // 表名
    protected $name = 'user_route_price';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    // 追加属性
    protected $append = [
        'user_name',
        'create_admin_name'
    ];

    /**
     * 获取用户名称（显示手机号）
     */
    public function getUserNameAttr($value, $data)
    {
        if (empty($data['user_id'])) {
            return '全局配置';
        }
        $user = \think\Db::name('user')->where('id', $data['user_id'])->find();
        return $user ? ($user['mobile'] ?: ($user['nickname'] ?: $user['username'])) : '未知用户';
    }

    /**
     * 获取创建者管理员名称
     */
    public function getCreateAdminNameAttr($value, $data)
    {
        if (empty($data['create_admin_id'])) {
            return '系统';
        }
        $admin = \think\Db::name('admin')->where('id', $data['create_admin_id'])->find();
        return $admin ? ($admin['nickname'] ?: $admin['username']) : '未知管理员';
    }

    /**
     * 获取省份列表
     */
    public static function getProvinceList()
    {
        return [
            '北京', '上海', '天津', '重庆',
            '河北', '山西', '内蒙古', '辽宁', '吉林', '黑龙江',
            '江苏', '浙江', '安徽', '福建', '江西', '山东', '河南', 
            '湖北', '湖南', '广东', '广西', '海南', 
            '四川', '贵州', '云南', '西藏', 
            '陕西', '甘肃', '青海', '宁夏', '新疆'
        ];
    }
}


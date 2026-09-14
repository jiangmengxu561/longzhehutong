<?php

return [
    'Id'                 => '订单ID',
    'Order_no'           => '充值订单号(唯一,支付用)',
    'User_id'            => '会员用户ID(fa_user.id)',
    'Franchise_id'       => '所属加盟商ID(fa_franchise.id,可空)',
    'Package_id'         => '套餐ID(fa_member_package.id)',
    'Package_name'       => '套餐名称(快照)',
    'Membertype_before'  => '充值前身份',
    'Membertype_after'   => '充值后身份',
    'Membertype_after 2' => '兼职',
    'Member_duration'    => '充值时长(数量,配合member_unit)',
    'Member_unit'        => '时长单位',
    'Price'              => '实付金额(元)',
    'Pay_type'           => '支付方式:wechat/balance等',
    'Pay_status'         => '支付状态',
    'Pay_time'           => '支付时间',
    'Expiry_before'      => '充值前到期时间(时间戳)',
    'Expiry_after'       => '充值后到期时间(时间戳)',
    'Remark'             => '备注',
    'Createtime'         => '创建时间',
    'Updatetime'         => '更新时间'
];

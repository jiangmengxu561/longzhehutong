<?php

namespace app\admin\model;

use think\Model;


class Order extends Model
{

    

    

    // 表名
    protected $name = 'order';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'find_car_type_text',
        'isinvoice_text',
        'pay_type_text',
        'delivery_text',
        'earliest_time_text',
        'latest_time_text',
        'isrequirements_text',
        'service_text',
        'control_text',
        'text_message_text',
        'pay_time_text',
        'pay_status_text',
        'logistics_status_text'
    ];
    

    
    public function getFindCarTypeList()
    {
        return ['专车' => __('专车'), '配车' => __('配车'), '小票快运' => __('小票快运')];
    }

    public function getIsinvoiceList()
    {
        return ['1' => __('Isinvoice 1'), '0' => __('Isinvoice 0')];
    }

    public function getPayTypeList()
    {
        return ['0' => __('现付'), '1' => __('寄付'),'2' => __('月结')];
    }
    public function getPaypartyList()
    {
        return ['0' => __('寄货方付钱'), '1' => __('收货方付钱')];
    }
    public function getDeliveryList()
    {
        return ['1' => __('Delivery 1'), '0' => __('Delivery 0')];
    }

    public function getIsrequirementsList()
    {
        return ['1' => __('Isrequirements 1'), '0' => __('Isrequirements 0')];
    }

    public function getServiceList()
    {
        return ['派送' => __('派送'), '自提' => __('自提')];
    }

    public function getControlList()
    {
        return ['到站点等通知放货' => __('到站点等通知放货'), '到收货地等通知放货' => __('到收货地等通知放货')];
    }

    public function getTextMessageList()
    {
        return ['1' => __('Text_message 1'), '2' => __('Text_message 2')];
    }

    public function getPayStatusList()
    {
        return [
            '1' => __('Pay_status 1'),
            '2' => __('Pay_status 2'),
            '3' => __('Pay_status 3'),
            '4' => __('Pay_status 4'),
            '5' => __('Pay_status 5'),
            '6' => __('Pay_status 6'),
            '7' => __('Pay_status 7'),
            '8' => __('Pay_status 8'),
        ];
    }

    public function getLogisticsStatusList()
    {
        return ['1' => __('Logistics_status 1'), '2' => __('Logistics_status 2'), '3' => __('Logistics_status 3'), '4' => __('Logistics_status 4'), '5' => __('Logistics_status 5'), '6' => __('Logistics_status 6'), '7' => __('Logistics_status 7')];
    }


    public function getFindCarTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['find_car_type'] ?? '');
        $list = $this->getFindCarTypeList();
        return $list[$value] ?? '';
    }


    public function getIsinvoiceTextAttr($value, $data)
    {
        $value = $value ?: ($data['isinvoice'] ?? '');
        $list = $this->getIsinvoiceList();
        return $list[$value] ?? '';
    }


    public function getPayTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['pay_type'] ?? '');
        $list = $this->getPayTypeList();
        return $list[$value] ?? '';
    }


    public function getDeliveryTextAttr($value, $data)
    {
        $value = $value ?: ($data['delivery'] ?? '');
        $list = $this->getDeliveryList();
        return $list[$value] ?? '';
    }


    public function getEarliestTimeTextAttr($value, $data)
    {
        $value = $value ?: ($data['earliest_time'] ?? '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getLatestTimeTextAttr($value, $data)
    {
        $value = $value ?: ($data['latest_time'] ?? '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getIsrequirementsTextAttr($value, $data)
    {
        $value = $value ?: ($data['isrequirements'] ?? '');
        $list = $this->getIsrequirementsList();
        return $list[$value] ?? '';
    }


    public function getServiceTextAttr($value, $data)
    {
        $value = $value ?: ($data['service'] ?? '');
        $list = $this->getServiceList();
        return $list[$value] ?? '';
    }


    public function getControlTextAttr($value, $data)
    {
        $value = $value ?: ($data['control'] ?? '');
        $list = $this->getControlList();
        return $list[$value] ?? '';
    }


    public function getTextMessageTextAttr($value, $data)
    {
        $value = $value ?: ($data['text_message'] ?? '');
        $list = $this->getTextMessageList();
        return $list[$value] ?? '';
    }


    public function getPayTimeTextAttr($value, $data)
    {
        $value = $value ?: ($data['pay_time'] ?? '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getPayStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['pay_status'] ?? '');
        $list = $this->getPayStatusList();
        return $list[$value] ?? '';
    }


    public function getLogisticsStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['logistics_status'] ?? '');
        $list = $this->getLogisticsStatusList();
        return $list[$value] ?? '';
    }

    protected function setEarliestTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setLatestTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setPayTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}

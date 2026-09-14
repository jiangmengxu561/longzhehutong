<?php

namespace app\admin\model;

use think\Model;


class Logistics extends Model
{

    /**
     * 模型初始化
     *
     * 在新增/编辑物流路线时，根据发货/到货物流地址自动补全经纬度
     */
    protected static function init()
    {
        // 新增前
        self::beforeInsert(function ($row) {
            self::fillCoordinates($row);
            self::syncNumericCoords($row);
        });

        // 更新前（编辑时也自动更新经纬度）
        self::beforeUpdate(function ($row) {
            self::fillCoordinates($row);
            self::syncNumericCoords($row);
        });
    }
    public function statusList()
    {
        return ['1' => __('审核中'), '2' => __('审核通过')];
    }

    public function logisticsStatusList()
    {
        return ['1' => __('启用'), '2' => __('禁用')];
    }

    /** 物流评级：5五星 4四星 3三星 2二星 1一星 */
    public function levelList()
    {
        return [
            '5' => __('五星'),
            '4' => __('四星'),
            '3' => __('三星'),
            '2' => __('二星'),
            '1' => __('一星'),
        ];
    }
    /**
     * 根据地址补全经纬度字段
     *
     * @param \think\Model $row
     */
    protected static function fillCoordinates($row)
    {
        // 如果经纬度已经有值，则不再处理（导入时已计算好的情况）
        $hasShippingLocation = !empty($row['shipping_longitude']) && !empty($row['shipping_latitude']);
        $hasArrivalLocation  = !empty($row['arrival_longitude']) && !empty($row['arrival_latitude']);

        // 组合更完整的地址，提升百度解析成功率
        $shippingParts = [
            isset($row['shipping_province']) ? trim($row['shipping_province']) : '',
            isset($row['origincity']) ? trim($row['origincity']) : '',
            isset($row['shipping_area']) ? trim($row['shipping_area']) : '',
            isset($row['shipping_logistics_address']) ? trim($row['shipping_logistics_address']) : '',
        ];
        $shippingAddress = implode('', array_filter($shippingParts));

        $arrivalParts = [
            isset($row['province']) ? trim($row['province']) : '',
            isset($row['destination']) ? trim($row['destination']) : '',
            isset($row['arrival_area']) ? trim($row['arrival_area']) : '',
            isset($row['arrival_logistics_address']) ? trim($row['arrival_logistics_address']) : '',
        ];
        $arrivalAddress  = implode('', array_filter($arrivalParts));

        // 没有地址或已经有经纬度就直接返回
        if (($hasShippingLocation && $hasArrivalLocation) || ($shippingAddress === '' && $arrivalAddress === '')) {
            return;
        }

        // 发货地经纬度
        if (!$hasShippingLocation && $shippingAddress !== '') {
            $shipLocation = self::getCoordinatesFromBaiduMap($shippingAddress);
            if (!empty($shipLocation['lng']) && !empty($shipLocation['lat'])) {
                $row['shipping_longitude'] = $shipLocation['lng'];
                $row['shipping_latitude']  = $shipLocation['lat'];
            }
        }

        // 到货地经纬度
        if (!$hasArrivalLocation && $arrivalAddress !== '') {
            $arriveLocation = self::getCoordinatesFromBaiduMap($arrivalAddress);
            if (!empty($arriveLocation['lng']) && !empty($arriveLocation['lat'])) {
                $row['arrival_longitude'] = $arriveLocation['lng'];
                $row['arrival_latitude']  = $arriveLocation['lat'];
            }
        }
    }

    /**
     * 同步数值经纬度冗余列（用于快速按半径粗筛物流候选）。
     * 从字符串经纬度列解析出数值，非法/空值置 0。
     */
    protected static function syncNumericCoords($row)
    {
        $row['shipping_lat_n'] = self::toNum($row['shipping_latitude'] ?? '');
        $row['shipping_lng_n'] = self::toNum($row['shipping_longitude'] ?? '');
        $row['arrival_lat_n']  = self::toNum($row['arrival_latitude'] ?? '');
        $row['arrival_lng_n']  = self::toNum($row['arrival_longitude'] ?? '');
    }

    protected static function toNum($val): float
    {
        $val = trim((string)$val);
        if ($val === '' || !is_numeric($val)) {
            return 0.0;
        }
        return (float)$val;
    }

    /**
     * 调用高德地图接口根据地址获取经纬度
     *
     * @param string $address
     * @return array [lng => 经度, lat => 纬度]
     */
    protected static function getCoordinatesFromBaiduMap($address)
    {
        // TODO: 建议将 KEY 放到配置文件中统一管理
        $key = '在这里填你的高德KEY';
        if ($key === '在这里填你的高德KEY') {
            // 未配置有效 KEY 直接返回空，避免请求报错
            return ['lng' => '', 'lat' => ''];
        }

        // 高德地理编码接口
        $url = 'https://restapi.amap.com/v3/geocode/geo?address=' . urlencode($address) . '&key=' . $key;

        $response = @file_get_contents($url);
        if ($response === false) {
            return ['lng' => '', 'lat' => ''];
        }

        $data = json_decode($response, true);
        // 高德返回：status=1 表示成功，geocodes[0].location 为 "lng,lat"
        if (isset($data['status']) && (string)$data['status'] === '1'
            && !empty($data['geocodes'][0]['location'])) {
            $location = explode(',', $data['geocodes'][0]['location']);
            if (count($location) === 2) {
                return [
                    'lng' => $location[0],
                    'lat' => $location[1],
                ];
            }
        }

        return ['lng' => '', 'lat' => ''];
    }

    // 表名
    protected $name = 'logistics';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    

    







}

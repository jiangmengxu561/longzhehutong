<?php

namespace app\common\library;

use think\Config;
use think\Db;

/**
 * 物流配车算价服务（逻辑与 Placeorder 配车算价一致，支持模糊地址文本）
 */
class LogisticsPricingService
{
    /**
     * 根据模糊地址与货物信息计算成本、系统报价及利润
     *
     * @param array $params loading, unload, price, weight, direction, car_type_id, mobile, goods_type_id(可选), logistics_id(可选)
     * @param int   $userId 当前用户ID（预留，与下单逻辑一致）
     * @return array
     * @throws \Exception
     */
    public function calculateProfit(array $params, $userId = 0)
    {
        $loadingText = trim((string)($params['loading'] ?? ''));
        $unloadText = trim((string)($params['unload'] ?? ''));
        $quotedPrice = isset($params['price']) ? round(floatval($params['price']), 2) : 0;
        $weight = $params['weight'] ?? null;
        $direction = $params['direction'] ?? null;
        $carTypeId = isset($params['car_type_id']) ? intval($params['car_type_id']) : 0;
        $goodsTypeId = isset($params['goods_type_id']) ? intval($params['goods_type_id']) : 0;
        $logisticsId = isset($params['logistics_id']) ? intval($params['logistics_id']) : 0;
        $type = trim((string)($params['type'] ?? ''));
        $mobile = trim((string)($params['mobile'] ?? ''));

        if ($loadingText === '') {
            throw new \Exception('发货地不能为空');
        }
        if ($unloadText === '') {
            throw new \Exception('卸货地不能为空');
        }
        if ((!is_numeric($weight) || (float)$weight <= 0)
            && (!is_numeric($direction) || (float)$direction <= 0)
        ) {
            throw new \Exception('吨位和方位不能同时为空');
        }
//        if ($carTypeId <= 0) {
//            throw new \Exception('车型不能为空');
//        }
        $loading = $this->resolveFuzzyAddress($loadingText, '发货地');
        $unload = $this->resolveFuzzyAddress($unloadText, '卸货地');

        $carType = Db::name('car_type')->where('id', $carTypeId)->find();
        if (!$carType) {
            throw new \Exception('车型不存在');
        }


        $goodsTypePercentage = $this->getGoodsTypePercentage($goodsTypeId);

        if ($logisticsId > 0) {
            $pricing = $this->resolveLogisticsPricingById(
                $logisticsId,
                $loading,
                $unload,
                $carType,
                $goodsTypePercentage,
                $userId,
                $weight,
                $direction
            );
        } else {
            $pricing = $this->matchLogistics(
                $loading,
                $unload,
                $carType,
                $goodsTypePercentage,
                $userId,
                $weight,
                $direction
            );
        }

        if (!$pricing) {
            throw new \Exception('未找到合适的物流专线,请联系管理员');
        }
        $payPrice = round(
            ($pricing['logistics_cost'] ?? 0)
            + ($pricing['pickup_fee'] ?? 0)
            + ($pricing['shipment_fee'] ?? 0),
            2
        );
        $costCont = round(
            ($pricing['logistics_driver_cost'] ?? 0)
            + ($pricing['pickup_driver_fee'] ?? 0)
            + ($pricing['shipment_driver_fee'] ?? 0),
            2
        );

        $profit = round($quotedPrice - $costCont, 2);
        $systemProfit = round($payPrice - $costCont, 2);

        return [
            'has_profit'           => $profit >= 200 && mb_strpos($type, '冷运') === false,
            'profit'               => $profit,
            'quoted_price'         => $quotedPrice,
            'pay_price'            => $payPrice,
            'cost_cont'            => $costCont,
            'system_profit'        => $systemProfit,
            'logistics_id'         => $pricing['logistics_id'] ?? 0,
            'logistics_cost'       => $pricing['logistics_cost'] ?? 0,
            'logistics_driver_cost'=> $pricing['logistics_driver_cost'] ?? 0,
            'pickup_fee'           => $pricing['pickup_fee'] ?? 0,
            'pickup_driver_fee'    => $pricing['pickup_driver_fee'] ?? 0,
            'shipment_fee'         => $pricing['shipment_fee'] ?? 0,
            'shipment_driver_fee'  => $pricing['shipment_driver_fee'] ?? 0,
            'pickup_distance'      => $pricing['pickup_distance'] ?? 0,
            'logistics_distance'   => $pricing['logistics_distance'] ?? 0,
            'shipmenty_distance'   => $pricing['shipmenty_distance'] ?? 0,
            'loading_address'      => $loadingText,
            'unload_address'       => $unloadText,
            'mobile'               => $mobile,
            'type'                 => $type,
        ]; 
    }

    /**
     * 模糊地址转虚拟地址结构（含经纬度）
     */
    private function resolveFuzzyAddress($addressText, $label)
    {
        $coords = $this->getCoordinatesFromBaiduMap($addressText);
        if (empty($coords['lng']) || empty($coords['lat'])) {
            throw new \Exception($label . '解析失败，请检查地址是否正确');
        }

        return [
            'address'          => $addressText,
            'detailed_address' => $addressText,
            'lng'              => $coords['lng'],
            'lat'              => $coords['lat'],
        ];
    }

    private function getGoodsTypePercentage($goodsTypeId)
    {
        if (empty($goodsTypeId)) {
            return 0;
        }

        $goodsType = Db::name('goods_type')->where('id', $goodsTypeId)->find();
        if (!$goodsType || empty($goodsType['percentage'])) {
            return 0;
        }

        return floatval($goodsType['percentage']);
    }

    private function matchLogistics($loading, $unload, $carType, $goodsTypePercentage = 0, $userId = 0, $weight = null, $direction = null)
    {
        $candidates = $this->collectLogisticsCandidates(
            $loading,
            $unload,
            $carType,
            $goodsTypePercentage,
            $userId,
            $weight,
            $direction
        );
        if (empty($candidates)) {
            return false;
        }

        $selected = $candidates['list'][0]['logistics'];
        $distancesReal = $this->calculateSegmentDistances($loading, $unload, $selected, true);
        $logisticsInfo = [
            'logistics_id'   => $selected['id'],
            'logistics_name' => $selected['name'] ?? '',
            'distances'      => $distancesReal,
            'total_distance' => ($distancesReal['loading_to_start'] ?? 0) + ($distancesReal['logistics_line'] ?? 0) + ($distancesReal['end_to_unload'] ?? 0),
            'logistics_data' => $selected,
        ];

        return $this->calculateLogisticsCost(
            $logisticsInfo,
            $carType,
            $distancesReal,
            $goodsTypePercentage,
            $loading,
            $unload,
            $userId,
            $weight,
            $direction
        );
    }

    private function resolveLogisticsPricingById($logisticsId, $loading, $unload, $carType, $goodsTypePercentage = 0, $userId = 0, $weight = null, $direction = null)
    {
        $logistics = Db::name('logistics')
            ->where('id', $logisticsId)
            ->where('logistics_status', 1)
            ->where('status', 2)
            ->find();

        if (!$logistics) {
            return false;
        }
        if (empty($logistics['shipping_longitude']) || empty($logistics['shipping_latitude'])
            || empty($logistics['arrival_longitude']) || empty($logistics['arrival_latitude'])) {
            return false;
        }

        $loadingAddressStr = $loading['detailed_address'] ?? ($loading['address'] ?? '');
        $unloadAddressStr = $unload['detailed_address'] ?? ($unload['address'] ?? '');
        $routeContext = $this->buildLogisticsRouteContext($loadingAddressStr, $unloadAddressStr);
        if (!$this->isLogisticsRouteMatched($logistics, $routeContext)) {
            return false;
        }

        $distancesReal = $this->calculateSegmentDistances($loading, $unload, $logistics, true);
        $logisticsInfo = [
            'logistics_id'   => $logistics['id'],
            'logistics_name' => $logistics['name'] ?? '',
            'distances'      => $distancesReal,
            'total_distance' => ($distancesReal['loading_to_start'] ?? 0) + ($distancesReal['logistics_line'] ?? 0) + ($distancesReal['end_to_unload'] ?? 0),
            'logistics_data' => $logistics,
        ];

        return $this->calculateLogisticsCost(
            $logisticsInfo,
            $carType,
            $distancesReal,
            $goodsTypePercentage,
            $loading,
            $unload,
            $userId,
            $weight,
            $direction
        );
    }

    private function collectLogisticsCandidates($loading, $unload, $carType, $goodsTypePercentage = 0, $userId = 0, $weight = null, $direction = null)
    {
        $loadingAddressStr = $loading['detailed_address'] ?? ($loading['address'] ?? '');
        $unloadAddressStr = $unload['detailed_address'] ?? ($unload['address'] ?? '');
        $routeContext = $this->buildLogisticsRouteContext($loadingAddressStr, $unloadAddressStr);

        $logisticsList = Db::name('logistics')
            ->where('logistics_status', 1)
            ->where('status', 2)
            ->select();

        $candidates = [];
        foreach ($logisticsList as $logistics) {
            if (empty($logistics['shipping_longitude']) || empty($logistics['shipping_latitude'])) {
                continue;
            }
            if (empty($logistics['arrival_longitude']) || empty($logistics['arrival_latitude'])) {
                continue;
            }
            if (!$this->isLogisticsRouteMatched($logistics, $routeContext)) {
                continue;
            }

            $distances = $this->calculateSegmentDistances($loading, $unload, $logistics, false);
            $totalDistance = ($distances['loading_to_start'] ?? 0) + ($distances['logistics_line'] ?? 0) + ($distances['end_to_unload'] ?? 0);
            $logisticsInfo = [
                'logistics_id'   => $logistics['id'],
                'logistics_name' => $logistics['name'] ?? '',
                'distances'      => $distances,
                'total_distance' => $totalDistance,
                'logistics_data' => $logistics,
            ];
            $costResult = $this->calculateLogisticsCost(
                $logisticsInfo,
                $carType,
                $distances,
                $goodsTypePercentage,
                $loading,
                $unload,
                $userId,
                $weight,
                $direction
            );
            $totalPrice = ($costResult['logistics_cost'] ?? 0) + ($costResult['pickup_fee'] ?? 0) + ($costResult['shipment_fee'] ?? 0);
            $candidates[] = [
                'logistics'            => $logistics,
                'total_price_value'    => $totalPrice,
                'total_distance_value' => $totalDistance,
                'cost_result'          => $costResult,
            ];
        }

        if (empty($candidates)) {
            return false;
        }

        usort($candidates, function ($a, $b) {
            $priceCmp = ($a['total_price_value'] ?? PHP_FLOAT_MAX) <=> ($b['total_price_value'] ?? PHP_FLOAT_MAX);
            if ($priceCmp !== 0) {
                return $priceCmp;
            }
            return ($a['total_distance_value'] ?? 0) <=> ($b['total_distance_value'] ?? 0);
        });

        return [
            'loading'  => $loading,
            'unload'   => $unload,
            'car_type' => $carType,
            'list'     => $candidates,
        ];
    }

    private function isLogisticsRouteMatched(array $logistics, array $routeContext)
    {
        $loadingCity = $routeContext['loading_city'] ?? '';
        $unloadCity = $routeContext['unload_city'] ?? '';
        $loadingProvince = $routeContext['loading_province'] ?? '';
        $unloadProvince = $routeContext['unload_province'] ?? '';

        if (!$loadingCity && !$unloadCity && !$loadingProvince && !$unloadProvince) {
            return true;
        }

        $shippingCity = trim($logistics['origincity'] ?? '');
        $arrivalCity = trim($logistics['destination'] ?? '');
        $shippingProvince = trim($logistics['shipping_province'] ?? '');
        $arrivalProvince = trim($logistics['province'] ?? '');

        $shippingMatch = false;
        if ($loadingCity) {
            if ($shippingCity && mb_strpos($shippingCity, $loadingCity) !== false) {
                $shippingMatch = true;
            } elseif ($loadingProvince && $shippingProvince && mb_strpos($shippingProvince, $loadingProvince) !== false) {
                $shippingMatch = true;
            }
        } elseif ($loadingProvince) {
            if ($shippingProvince && mb_strpos($shippingProvince, $loadingProvince) !== false) {
                $shippingMatch = true;
            }
        } else {
            $shippingMatch = true;
        }

        $arrivalMatch = false;
        if ($unloadCity) {
            if ($arrivalCity && mb_strpos($arrivalCity, $unloadCity) !== false) {
                $arrivalMatch = true;
            } elseif ($unloadProvince && $arrivalProvince && mb_strpos($arrivalProvince, $unloadProvince) !== false) {
                $arrivalMatch = true;
            }
        } elseif ($unloadProvince) {
            if ($arrivalProvince && mb_strpos($arrivalProvince, $unloadProvince) !== false) {
                $arrivalMatch = true;
            }
        } else {
            $arrivalMatch = true;
        }

        return $shippingMatch && $arrivalMatch;
    }

    private function buildLogisticsRouteContext($loadingAddressStr, $unloadAddressStr)
    {
        return [
            'loading_city'     => $this->extractCityFromAddress($loadingAddressStr),
            'unload_city'      => $this->extractCityFromAddress($unloadAddressStr),
            'loading_province' => $this->extractProvinceFromAddress($loadingAddressStr),
            'unload_province'  => $this->extractProvinceFromAddress($unloadAddressStr),
        ];
    }

    private function calculateSegmentDistances($loading, $unload, $logistics, $preferDrivingDistance = false)
    {
        if ($preferDrivingDistance) {
            $segments = [
                [
                    $loading['lat'], $loading['lng'],
                    $logistics['shipping_latitude'], $logistics['shipping_longitude'],
                ],
                [
                    $unload['lat'], $unload['lng'],
                    $logistics['arrival_latitude'], $logistics['arrival_longitude'],
                ],
            ];
            list($segment1, $segment3) = calculateDrivingDistancesParallel($segments);
            return [
                'loading_to_start' => $segment1,
                'logistics_line'   => $logistics['distance'] ?? 0,
                'end_to_unload'    => $segment3,
            ];
        }

        $segment1 = $this->checkDistanceMatch(
            $loading['lat'], $loading['lng'],
            $logistics['shipping_latitude'], $logistics['shipping_longitude'],
            PHP_FLOAT_MAX
        ) ? calculateDistance(
            $loading['lat'], $loading['lng'],
            $logistics['shipping_latitude'], $logistics['shipping_longitude']
        ) : 0;
        $segment2 = $logistics['distance'] ?? 0;
        $segment3 = $this->checkDistanceMatch(
            $unload['lat'], $unload['lng'],
            $logistics['arrival_latitude'], $logistics['arrival_longitude'],
            PHP_FLOAT_MAX
        ) ? calculateDistance(
            $unload['lat'], $unload['lng'],
            $logistics['arrival_latitude'], $logistics['arrival_longitude']
        ) : 0;

        return [
            'loading_to_start' => $segment1,
            'logistics_line'   => $segment2,
            'end_to_unload'    => $segment3,
        ];
    }

    private function checkDistanceMatch($lat1, $lng1, $lat2, $lng2, $maxDistance)
    {
        if (empty($lat1) || empty($lng1) || empty($lat2) || empty($lng2)) {
            return false;
        }
        $distance = calculateDistance($lat1, $lng1, $lat2, $lng2);
        return $distance <= $maxDistance;
    }

    private function calculateLogisticsCost($logisticsInfo, $carType, $distances, $goodsTypePercentage = 0, $loading = null, $unload = null, $userId = 0, $weight = null, $direction = null)
    {
        $logistics = $logisticsInfo['logistics_data'];
        $priceResult = calculatePrice(
            $weight,
            $direction,
            $logistics['perton'],
            $logistics['side'],
            $logistics['reflux'],
            $logistics['bulky']
        );

        $pickUpDriverFreight = Config::get('site.PickUpDriverFreight') ?: 0;
        $logisticsDriverFreight = Config::get('site.LogisticsDriverFreight') ?: 0;
        $deliveryDriverFreight = Config::get('site.DeliveryDriverFreight') ?: 0;
        $carTypeType = $carType['type'] ?? 0;

        $loadingProvince = '';
        if (!empty($loading['detailed_address'])) {
            $loadingProvince = $this->extractProvinceFromAddress($loading['detailed_address']);
        }
        if (empty($loadingProvince)) {
            $loadingProvince = $this->extractProvinceFromAddress($loading['address'] ?? '');
        }
        $unloadProvince = '';
        if (!empty($unload['detailed_address'])) {
            $unloadProvince = $this->extractProvinceFromAddress($unload['detailed_address']);
        }
        if (empty($unloadProvince)) {
            $unloadProvince = $this->extractProvinceFromAddress($unload['address'] ?? '');
        }
        $pickupPrice = $this->getProvincePrice($carTypeType, $loadingProvince);
        if (!$pickupPrice) {
            $pickupPrice = [
                'startingfare' => floatval($carType['Startingfare'] ?? $carType['startingfare'] ?? 0),
                'price'        => floatval($carType['Price'] ?? $carType['price'] ?? 0),
            ];
        }

        $shipmentPrice = $this->getProvincePrice($carTypeType, $unloadProvince);
        if (!$shipmentPrice) {
            $shipmentPrice = [
                'startingfare' => floatval($carType['Startingfare'] ?? $carType['startingfare'] ?? 0),
                'price'        => floatval($carType['Price'] ?? $carType['price'] ?? 0),
            ];
        }

        if ($distances['loading_to_start'] > 5) {
            $pickupDriverFee = $pickupPrice['startingfare'] + ($distances['loading_to_start'] - 5) * $pickupPrice['price'];
        } else {
            $pickupDriverFee = $pickupPrice['startingfare'];
        }

        if ($distances['end_to_unload'] > 5) {
            $shipmentDriverFee = $shipmentPrice['startingfare'] + ($distances['end_to_unload'] - 5) * $shipmentPrice['price'];
        } else {
            $shipmentDriverFee = $shipmentPrice['startingfare'];
        }

        $pickupFee = $pickupDriverFee * (1 + $pickUpDriverFreight / 100);
        $shipmentFee = $shipmentDriverFee * (1 + $deliveryDriverFreight / 100);
        $logisticsCost = $priceResult['price'] * (1 + $logisticsDriverFreight / 100);
        $logisticsDriverCost = $priceResult['price'];

        if ($goodsTypePercentage > 0) {
            $logisticsDriverCost = $logisticsDriverCost * (1 + $goodsTypePercentage / 100);
            $logisticsCost = $logisticsCost * (1 + $goodsTypePercentage / 100);
        }

        return [
            'logistics_id'          => $logisticsInfo['logistics_id'],
            'arrivaltime'           => $logistics['time_limit'] ?? '',
            'logistics_cost'        => round($logisticsCost, 2),
            'logistics_driver_cost' => round($logisticsDriverCost, 2),
            'pickup_driver_fee'     => round($pickupDriverFee, 2),
            'shipment_driver_fee'   => round($shipmentDriverFee, 2),
            'pickup_fee'            => round($pickupFee, 2),
            'shipment_fee'          => round($shipmentFee, 2),
            'loading_to_start'      => $distances['loading_to_start'],
            'pickup_distance'       => round($distances['loading_to_start'], 2),
            'logistics_distance'    => round($distances['logistics_line'], 2),
            'shipmenty_distance'    => round($distances['end_to_unload'], 2),
        ];
    }

    private function getProvincePrice($carTypeType, $province)
    {
        if (empty($carTypeType) || empty($province)) {
            return false;
        }

        $provincePrice = Db::name('provinceprice')
            ->where('car_type', $carTypeType)
            ->where('city', 'like', '%' . $province . '%')
            ->find();

        if ($provincePrice) {
            return [
                'startingfare' => floatval($provincePrice['Startingfare'] ?? $provincePrice['startingfare'] ?? 0),
                'price'        => floatval($provincePrice['Price'] ?? $provincePrice['price'] ?? 0),
            ];
        }

        return false;
    }

    private function extractProvinceFromAddress($address)
    {
        $address = trim((string)$address);
        if ($address === '') {
            return '';
        }

        $provinces = [
            '北京', '上海', '天津', '重庆',
            '河北', '山西', '内蒙古', '辽宁', '吉林', '黑龙江',
            '江苏', '浙江', '安徽', '福建', '江西', '山东', '河南',
            '湖北', '湖南', '广东', '广西', '海南',
            '四川', '贵州', '云南', '西藏',
            '陕西', '甘肃', '青海', '宁夏', '新疆',
        ];

        foreach ($provinces as $province) {
            if (strpos($address, $province) === 0
                || strpos($address, $province . '省') === 0
                || strpos($address, $province . '市') === 0
                || strpos($address, $province . '自治区') === 0
            ) {
                return $province;
            }
        }

        usort($provinces, function ($a, $b) {
            return mb_strlen($b, 'UTF-8') <=> mb_strlen($a, 'UTF-8');
        });
        foreach ($provinces as $province) {
            if (mb_strpos($address, $province . '省') !== false
                || mb_strpos($address, $province . '自治区') !== false
            ) {
                return $province;
            }
        }

        return $this->inferProvinceFromCity($address);
    }

    private function inferProvinceFromCity($address)
    {
        static $cityProvinceMap = null;

        if ($cityProvinceMap === null) {
            $cityProvinceMap = [];
            $path = ROOT_PATH . 'public' . DS . 'assets' . DS . 'libs' . DS
                . 'fastadmin-cxselect' . DS . 'js' . DS . 'cityData.json';
            $data = is_file($path) ? json_decode(file_get_contents($path), true) : null;

            if (is_array($data)) {
                foreach ($data as $provinceData) {
                    $province = $this->normalizeRegionName($provinceData['n'] ?? '');
                    if ($province === '') {
                        continue;
                    }

                    foreach (($provinceData['s'] ?? []) as $cityData) {
                        $city = $this->normalizeRegionName($cityData['n'] ?? '');
                        if ($city !== '' && $city !== $province) {
                            $cityProvinceMap[$city] = $province;
                        }
                    }
                }
            }

            uksort($cityProvinceMap, function ($a, $b) {
                return mb_strlen($b, 'UTF-8') <=> mb_strlen($a, 'UTF-8');
            });
        }

        foreach ($cityProvinceMap as $city => $province) {
            if (mb_strpos($address, $city) !== false) {
                return $province;
            }
        }

        return '';
    }

    private function normalizeRegionName($name)
    {
        return preg_replace('/(特别行政区|自治区|自治州|地区|省|市|盟)$/u', '', trim((string)$name));
    }

    private function extractCityFromAddress($address)
    {
        $address = trim((string)$address);
        if ($address === '') {
            return '';
        }

        $municipalities = ['北京', '上海', '天津', '重庆'];
        foreach ($municipalities as $m) {
            if (mb_strpos($address, $m . '市') === 0 || mb_strpos($address, $m) === 0) {
                return $m;
            }
        }

        $core = $address;
        if (preg_match('/^(.*?(?:省|自治区))(.*)$/u', $address, $m)) {
            $core = trim($m[2]);
        }
        if (preg_match('/^([\x{4e00}-\x{9fa5}]{2,8}?)(?:市|地区|自治州|盟)/u', $core, $m2)) {
            return trim($m2[1]);
        }
        if (preg_match('/([\x{4e00}-\x{9fa5}]{2,8}?)(?:市|地区|自治州|盟)/u', $address, $m3)) {
            return trim($m3[1]);
        }

        return '';
    }

    private function getCoordinatesFromBaiduMap($address)
    {
        if (empty($address)) {
            return ['lng' => '', 'lat' => ''];
        }

        $configAk = Config::get('site.BaiduKey');
        $akList = [];
        if ($configAk) {
            if (is_string($configAk)) {
                $decoded = json_decode($configAk, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $akList = $decoded;
                }
            } elseif (is_array($configAk)) {
                $akList = $configAk;
            }
        }
        if (empty($akList)) {
            $akList = ['default' => 'Mc5XjX716IHNL85z9rZM24q6zxqmv7bu'];
        }

        static $currentAkIndex = 0;
        $akKeys = array_keys($akList);
        $maxRetries = count($akList);

        for ($i = 0; $i < $maxRetries; $i++) {
            $currentKey = $akKeys[$currentAkIndex];
            $ak = $akList[$currentKey];
            $url = 'http://api.map.baidu.com/geocoding/v3/?address=' . urlencode($address) . '&output=json&ak=' . $ak;

            try {
                $result = @file_get_contents($url);
                if ($result === false) {
                    $currentAkIndex = ($currentAkIndex + 1) % count($akList);
                    continue;
                }

                $data = json_decode($result, true);
                if ($data['status'] == 0 && isset($data['result']['location'])) {
                    return [
                        'lng' => $data['result']['location']['lng'],
                        'lat' => $data['result']['location']['lat'],
                    ];
                } elseif (in_array($data['status'], [1, 101, 302, 401], true)) {
                    $currentAkIndex = ($currentAkIndex + 1) % count($akList);
                    continue;
                } else {
                    break;
                }
            } catch (\Exception $e) {
                $currentAkIndex = ($currentAkIndex + 1) % count($akList);
                continue;
            }
        }

        return ['lng' => '', 'lat' => ''];
    }
}

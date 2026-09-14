<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\LogisticsPricingService;
use think\Cache;
use think\Db;

/**
 * 算价接口（免登录，不生成订单）
 */
class Pricecalc extends Api
{
    const STREAM_LIFETIME = 55;
    const STREAM_HEARTBEAT_INTERVAL = 15;
    const EVENT_CACHE_TTL = 120;

    /** 算价和实时推送均可由外部程序直接访问 */
    protected $noNeedLogin = ['stream', 'profitstream', 'calculate','profitstream'];

    /** 登录即可访问，不校验额外节点权限 */
    protected $noNeedRight = ['*'];

    /**
     * 配车算价：根据模糊地址计算成本与利润
     *
     * 请求参数：
     * - loading   发货地（文本，如：山东济南）
     * - unload    卸货地（文本，如：上海浦东）
     * - price     报价/售价
     * - weight    重量（吨）
     * - direction 方位/体积（立方米）
     * - car_type  车长（如 3.8、4.2、5；其中 5 匹配车型 6.8）
     * - mobile    手机号
     * - goods_type 货物类型文字（可选，如含「设备」「机械」则按设备类计价）
     * - logistics_id  指定物流专线ID（可选，不传则自动匹配最优专线）
     */
    public function calculate()
    {
        try {
            $data = $this->request->param();
            $goodsTypeText = trim((string)($data['goods_type'] ?? ($data['goods_name'] ?? ($data['huowuleixing'] ?? ''))));
            $carLength = $data['car_type'] ?? ($data['chexing'] ?? '');
            $goodsTypeId = $this->resolveGoodsTypeIdFromText($goodsTypeText);
            $carTypeId = $this->resolveCarTypeIdByLength($carLength, $data['car_type_id'] ?? null);

            $params = [
                'loading'       => $data['loading'] ?? ($data['fahuodi'] ?? ''),
                'unload'        => $data['unload'] ?? ($data['xiehuodi'] ?? ''),
                'price'         => $data['price'] ?? ($data['jiage'] ?? null),
                'weight'        => $data['weight'] ?? ($data['zhongliang'] ?? null),
                'direction'     => $data['direction'] ?? ($data['fangwei'] ?? null),
                'type'          => $data['type'] ?? '',
                'car_type_id'   => $carTypeId,
                'goods_type_id' => $goodsTypeId,
                'mobile'        => $data['mobile'] ?? ($data['shoujihao'] ?? ''),
                'logistics_id'  => $data['logistics_id'] ?? 0,
            ];

            $userId = $this->getCurrentUserId();
            $channelId = $this->getOrCreateChannelId();

            $service = new LogisticsPricingService();
            $result = $service->calculateProfit($params, $userId);

            // Persist first. The realtime event is published only after this
            // save succeeds, so consumers never receive an unpersisted result.
            $recordId = $this->saveProfitRecord($result, $params);
            $result['record_id'] = $recordId;
            $result['saved'] = true;
            $result['persisted_at'] = time();

            $result['realtime_channel_id'] = $channelId;

            $result['realtime_stream_url'] = $this->buildStreamUrl($channelId);
            $result['realtime_event_id'] = $this->publishCalculationEvent($result, $channelId);
//            print_r($result);die;
            $this->success('保存成功', $result);
        } catch (\think\exception\HttpResponseException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 有利润时保存算价记录
     */
    /**
     * SSE long connection. The stream is scoped to a caller-provided channel ID.
     * EventSource clients reconnect automatically after the connection closes.
     */
    public function stream()
    {
        $channelId = $this->getChannelId();
        if ($channelId === '') {
            $this->error('A valid channel_id is required');
        }

        ignore_user_abort(true);
        set_time_limit(0);
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache, no-transform');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        echo "retry: 3000\n\n"; 
        $this->sendSseEvent('connected', [
            'channel_id'   => $channelId,
            'connected_at' => time(),
        ]);

        $lastEventId = (string)$this->request->server(
            'HTTP_LAST_EVENT_ID',
            $this->request->get('last_event_id', '')
        );
        $cacheKey = $this->getEventCacheKey($channelId);
        $startedAt = microtime(true);
        $lastHeartbeatAt = $startedAt;

        while ((microtime(true) - $startedAt) < self::STREAM_LIFETIME) {
            if (connection_aborted()) {
                break;
            }

            $event = Cache::get($cacheKey);
            if (is_array($event) && !empty($event['id']) && $event['id'] !== $lastEventId) {
                $this->sendSseEvent('pricecalc', $event['data'] ?? [], $event['id']);
                $lastEventId = $event['id'];
            }

            $now = microtime(true);
            if (($now - $lastHeartbeatAt) >= self::STREAM_HEARTBEAT_INTERVAL) {
                echo ': heartbeat ' . time() . "\n\n";
                $this->flushSseOutput();
                $lastHeartbeatAt = $now;
            }

            usleep(500000);
        }

        $this->sendSseEvent('reconnect', ['reconnect' => true]);
        exit;
    }

    /**
     * SSE stream for unqueried, high-profit calculation records.
     * Each delivered record is marked as queried before it is emitted.
     */
    public function profitstream()
    { 
        ignore_user_abort(true);   
        set_time_limit(0);
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache, no-transform');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        echo "retry: 3000\n\n";
        $this->sendSseEvent('connected', ['connected_at' => time()]);

        $startedAt = microtime(true);
        $lastHeartbeatAt = $startedAt;
        while ((microtime(true) - $startedAt) < self::STREAM_LIFETIME) {
            if (connection_aborted()) {
                break;
            }

            foreach ($this->claimHighProfitRecords() as $record) {
                $this->sendSseEvent('profit_record', $record, (string)$record['id']);
            }

            $now = microtime(true);
            if (($now - $lastHeartbeatAt) >= self::STREAM_HEARTBEAT_INTERVAL) {
                echo ': heartbeat ' . time() . "\n\n";
                $this->flushSseOutput();
                $lastHeartbeatAt = $now;
            }
 
            usleep(500000);
        }

        $this->sendSseEvent('reconnect', ['reconnect' => true]);
        exit;
    }

    /** Save every successful calculation result before publishing its event. */
    private function saveProfitRecord(array $result, array $params)
    {
        $recordId = Db::name('price_calc_profit')->insertGetId([
            'user_id'               => 0,
            'mobile'                => '',
            'loading_address'       => $result['loading_address'] ?? '',
            'unload_address'        => $result['unload_address'] ?? '',
            'weight'                => $params['weight'] ?? 0,
            'direction'             => $params['direction'] ?? 0,
            'car_type_id'           => $params['car_type_id'] ?? 0,
            'goods_type_id'         => $params['goods_type_id'] ?? 0,
            'logistics_id'          => $result['logistics_id'] ?? 0,
            'quoted_price'          => $result['quoted_price'] ?? 0,
            'pay_price'             => $result['pay_price'] ?? 0,
            'cost_cont'             => $result['cost_cont'] ?? 0,
            'profit'                => $result['profit'] ?? 0,
            'system_profit'         => $result['system_profit'] ?? 0,
            'logistics_cost'        => $result['logistics_cost'] ?? 0,
            'logistics_driver_cost' => $result['logistics_driver_cost'] ?? 0,
            'pickup_fee'            => $result['pickup_fee'] ?? 0,
            'pickup_driver_fee'     => $result['pickup_driver_fee'] ?? 0,
            'shipment_fee'          => $result['shipment_fee'] ?? 0,
            'shipment_driver_fee'   => $result['shipment_driver_fee'] ?? 0,
            'pickup_distance'       => $result['pickup_distance'] ?? 0,
            'logistics_distance'    => $result['logistics_distance'] ?? 0,
            'shipmenty_distance'    => $result['shipmenty_distance'] ?? 0,
            'is_chaxun'             => 0,
            'createtime'            => time(),
        ]);
        if (!$recordId) {
            throw new \Exception('算价结果写入数据库失败');
        }

        return (int)$recordId;
    }

    private function publishCalculationEvent(array $result, $channelId)
    {
        $eventId = str_replace('.', '', uniqid('', true));
        $result['realtime_event_id'] = $eventId;
        $event = [
            'id'   => $eventId,
            'data' => [
                'event_id'     => $eventId,
                'published_at' => time(),
                'record_id'    => $result['record_id'] ?? 0,
                'saved'        => !empty($result['saved']),
                'result'       => $result,
            ],
        ];

        if (!Cache::set($this->getEventCacheKey($channelId), $event, self::EVENT_CACHE_TTL)) {
            return '';
        }

        return $eventId;
    }

    /**
     * Atomically claim unqueried records before they are delivered over SSE.
     */
    private function claimHighProfitRecords()
    {
        $records = Db::name('price_calc_profit')
            ->where('profit', '>', 100)
            ->where('is_chaxun', 0)
            ->field('id,loading_address,unload_address,quoted_price')
            ->order('id asc')
            ->limit(20)
            ->select();

        $claimed = [];
        foreach ($records as $record) {
            $updated = Db::name('price_calc_profit')
                ->where('id', $record['id'])
                ->where('is_chaxun', 0)
                ->update(['is_chaxun' => 1]);

            if ($updated) {
                $claimed[] = [
                    'id'              => (int)$record['id'],
                    'loading_address' => $this->formatAdministrativeAddress($record['loading_address']),
                    'unload_address'  => $this->formatAdministrativeAddress($record['unload_address']),
                    'price'           => (float)$record['quoted_price'],
                ];
            }
        }

        return $claimed;
    }

    /**
     * Returns only the province, city and district parts of an address.
     */
    private function formatAdministrativeAddress($address)
    {
        $address = preg_replace('/\s+/u', '', trim((string)$address));
        if ($address === '') {
            return '';
        }

        $bestMatch = null;
        foreach ($this->getAdministrativeAreas() as $area) {
            $cityMatched = $this->addressContainsRegion($address, $area['city']);
            $districtMatched = $area['district'] !== ''
                && $this->addressContainsRegion($address, $area['district']);
            if (!$cityMatched && !$districtMatched) {
                continue;
            }

            $score = ($districtMatched ? 10000 : 0)
                + ($cityMatched ? 1000 : 0)
                + strlen($area['district']) + strlen($area['city']);
            if ($bestMatch === null || $score > $bestMatch['score']) {
                $bestMatch = $area + [
                    'city_matched'     => $cityMatched,
                    'district_matched' => $districtMatched,
                    'score'            => $score,
                ];
            }
        }

        if ($bestMatch !== null) {
            $parts = [
                $this->shortAdministrativeName($bestMatch['province']),
                $this->shortAdministrativeName($bestMatch['city']),
            ];
            if ($bestMatch['district_matched']) {
                $parts[] = $this->shortAdministrativeName($bestMatch['district']);
            }
            return implode('/', array_filter($parts));
        }

        foreach ($this->getAdministrativeAreas() as $area) {
            if ($this->addressContainsRegion($address, $area['province'])) {
                return $this->shortAdministrativeName($area['province']);
            }
        }

        return '';
    }

    private function getAdministrativeAreas()
    {
        static $areas = null;
        if ($areas !== null) {
            return $areas;
        }

        $areas = [];
        $path = ROOT_PATH . 'public' . DS . 'assets' . DS . 'libs' . DS
            . 'fastadmin-cxselect' . DS . 'js' . DS . 'cityData.json';
        $data = is_file($path) ? json_decode(file_get_contents($path), true) : null;
        if (!is_array($data)) {
            return $areas;
        }

        foreach ($data as $provinceData) {
            $province = trim((string)($provinceData['n'] ?? ''));
            foreach (($provinceData['s'] ?? []) as $cityData) {
                $city = trim((string)($cityData['n'] ?? ''));
                $districts = $cityData['s'] ?? [];

                // Municipalities list districts directly beneath the province.
                if (empty($districts)) {
                    $areas[] = [
                        'province' => $province,
                        'city'     => $province,
                        'district' => $city,
                    ];
                    continue;
                }

                foreach ($districts as $districtData) {
                    $areas[] = [
                        'province' => $province,
                        'city'     => $city,
                        'district' => trim((string)($districtData['n'] ?? '')),
                    ];
                }
            }
        }

        return $areas;
    }

    private function addressContainsRegion($address, $region)
    {
        $region = trim((string)$region);
        if ($region === '') {
            return false;
        }

        if (mb_strpos($address, $region) !== false) {
            return true;
        }

        $shortName = preg_replace('/(特别行政区|自治区|自治州|地区|省|市|盟|区|县|旗)$/u', '', $region);
        return mb_strlen($shortName, 'UTF-8') >= 2
            && mb_strpos($address, $shortName) !== false;
    }

    private function shortAdministrativeName($name)
    {
        return preg_replace('/(特别行政区|自治区|自治州|地区|省|市|盟|区|县|旗)$/u', '', trim((string)$name));
    }

    private function getEventCacheKey($channelId)
    {
        return 'pricecalc_realtime_channel_' . hash('sha256', $channelId);
    }

    private function getChannelId()
    {
        $channelId = trim((string)$this->request->param('channel_id', ''));
        if (!preg_match('/^[A-Za-z0-9_-]{1,64}$/', $channelId)) {
            return '';
        }

        return $channelId;
    }

    private function getOrCreateChannelId()
    {
        $channelId = $this->getChannelId();
        if ($channelId !== '') {
            return $channelId;
        }

        try {
            return bin2hex(random_bytes(16));
        } catch (\Exception $e) {
            return str_replace('.', '', uniqid('pricecalc_', true));
        }
    }

    private function buildStreamUrl($channelId)
    {
        $domain = rtrim((string)$this->request->domain(), '/');
        return $domain . '/api/pricecalc/stream?channel_id=' . rawurlencode($channelId);
    }

    /**
     * 根据车长匹配车型 ID（参考 Placeorder::car_type，按 car_boxlength 区间查找）
     */
    private function resolveCarTypeIdByLength($carLength, $carTypeId = null)
    {
        if ($carTypeId !== null && $carTypeId !== '' && is_numeric($carTypeId)) {
            return (int)$carTypeId;
        }

        $carLengthText = trim((string)$carLength);
        if ($carLengthText === '' || !is_numeric($carLengthText)) {
            return 0;
        }

        $targetSize = (float)$carLengthText;
        // 入参 5 米对应数据库车型 6.8
        if (abs($targetSize - 5) < 0.001) {
            $targetSize = 6.8;
        }

        $carList = Db::name('car_type')->field('id,car_boxlength')->order('id asc')->select();
        if (empty($carList)) {
            return 0;
        }

        $matched = [];
        foreach ($carList as $item) {
            $lengthStr = trim((string)($item['car_boxlength'] ?? ''));
            if ($lengthStr === '') {
                continue;
            }

            if (strpos($lengthStr, '-') !== false) {
                [$minStr, $maxStr] = explode('-', $lengthStr, 2);
                $min = (float)trim($minStr);
                $max = (float)trim($maxStr);
            } else {
                $min = $max = (float)$lengthStr;
            }

            if ($targetSize >= $min && $targetSize <= $max) {
                $matched[] = [
                    'max_sort' => $max,
                    'min_sort' => $min,
                    'id'       => $item['id'],
                ];
            }
        }

        if (empty($matched)) {
            return 0;
        }

        usort($matched, function ($a, $b) {
            $maxCmp = $a['max_sort'] <=> $b['max_sort'];
            if ($maxCmp !== 0) {
                return $maxCmp;
            }

            return $b['min_sort'] <=> $a['min_sort'];
        });

        return (int)$matched[0]['id'];
    }

    /**
     * 根据货物文字识别类型 ID（与下单 OCR 逻辑一致：含设备/机械则按「设备」计价）
     */
    private function resolveGoodsTypeIdFromText($goodsTypeText)
    {
        $goodsTypeText = trim((string)$goodsTypeText);
        if ($goodsTypeText === '') {
            return 0;
        }

        $equipmentKeywords = ['设备', '机械'];
        foreach ($equipmentKeywords as $keyword) {
            if (mb_strpos($goodsTypeText, $keyword) !== false) {
                $goodsTypeId = Db::name('goods_type')->where('name', '设备')->value('id');
                if ($goodsTypeId) {
                    return (int)$goodsTypeId;
                }

                $goodsTypeId = Db::name('goods_type')->where('name', 'like', '%设备%')->value('id');
                return $goodsTypeId ? (int)$goodsTypeId : 0;
            }
        }

        return 0;
    }

    private function getCurrentUserId()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            return 0;
        }

        if (is_object($user) && method_exists($user, 'toArray')) {
            $user = $user->toArray();
        }

        if (is_array($user)) {
            return (int)($user['id'] ?? 0);
        }

        return (int)$user;
    }

    private function sendSseEvent($eventName, array $data, $eventId = '')
    {
        if ($eventId !== '') {
            echo 'id: ' . str_replace(["\r", "\n"], '', $eventId) . "\n";
        }

        echo 'event: ' . $eventName . "\n";
        echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
        $this->flushSseOutput();
    }

    private function flushSseOutput()
    {
        if (ob_get_level() > 0) {
            @ob_flush();
        }
        flush();
    }
}

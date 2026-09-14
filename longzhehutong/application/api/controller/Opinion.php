<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Cache;
use think\Config;
use think\Db;
use think\Env;
use think\Log;

/**
 * 首页接口
 */
class Opinion extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 意见反馈
     *
     */
    public function feedback() 
    { 
        $data = $this->request->param();
        $user = $this->auth->getUser();
        if (empty($user['id'])){
            $this->error('请登录');
        }
        if (!$data['content']) {
            $this->error('请输入您要反馈的内容');
        }
        $arr['uid'] = $user['id'];
        $arr['content'] = $data['content'];
        $arr['image'] = $data['image'];
        $arr['createtime'] = $data['image'];


        $res = Db::name('opinion')->insert($arr);

        if ($res){
            $this->success('反馈成功');
        }else{
            $this->error('系统错误');
        }
    }


    /**
     * @return void
     * 协议
     */
    public function agreement(){

        $id = $this->request->param('id');
        switch ($id){
            case 1:
                $data = Config::get('site.user_agreement');
                break;
            case 2:
                $data = Config::get('site.privacy_agreement');
                break;
            case 3:
                $data = Config::get('site.aboutus');
                break;
            case 4:
                $data = Config::get('site.Instructions');
                break;
            case 5:
                $data = Config::get('site.contract');
                break;
            case 6:
                $data = Config::get('site.withdraw');
                break;
            case 7:
                $data = Config::get('site.UsageAgreement');
                break;
            case 8:
                $data = Config::get('site.SafetyRules');
                break;

        }
        if ($data){
            $this->success('success',$data);
        }else{
            $this->error('获取失败');
        }
    }

    /**
     * @return void
     * @throws \think\Exception
     * 个人信息
     */

    public function getuserinfo(){

        $userinfo = $this->auth->getUser();
        if (!$userinfo){
            $this->error('请登录');
        }
        $userinfo = $userinfo->toArray();
        $data = [
            'username' =>$userinfo['username'],
            'mobile' =>$userinfo['mobile'],
            'avatar' =>$userinfo['avatar'],
            'money' =>$userinfo['money'],
            'carnumber' =>$userinfo['carnumber'],
            'membertype' =>$userinfo['membertype'],
            'invitation' =>$userinfo['invitation'],
            'city' =>$userinfo['city'],
            'member_time' =>date('Y-m-d',$userinfo['member_time'])
        ];

        $this->success('success',$data);
    }

    /**
     * @return void
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     *
     * 修改个人信息
     */
    public function updateuserinfo(){
        $this->success('暂不支持修改个人信息');
        $userinfo = $this->auth->getUser();
        $username = $this->request->param('username');
        $avatar = $this->request->param('avatar');
        $carnumber = $this->request->param('carnumber');
        $city = $this->request->param('city');
        if (!$userinfo){
            $this->error('请登录');
        }

        $res = Db::name('user')
            ->where('id',$userinfo['id'])
            ->update([
                'username'=>$username,
                'avatar'=>$avatar,
                'carnumber'=>$carnumber,
                'city'=>$city,
            ]);

        if ($res){
            $this->success('个人信息修改成功');
        }else{
            $this->error('个人信息修改失败');
        }
    }

    /**
     * @return void
     *
     * 客服电话
     */
    public function customer_mobile()
    {
        $data = COnfig::get('site.customer_mobile');

        if ($data){
            $this->success('客服电话查询成功',$data);
        }else{
            $this->error('暂无数据');
        }
    }

    /**
     * 物流轨迹（需订单号 + 手机号后四位校验，与下单人手机号一致）
     *
     * @param string $order_id 订单号（order 表 orderid）
     * @param string $phone    手机号后四位（可传完整号码，取后四位比对）
     */
    public function trajectory()
    {
        $orderNo = trim((string) $this->request->param('order_id'));
        $phoneParam = trim((string) $this->request->param('phone'));
        if ($orderNo === '' || $phoneParam === '') {
            $this->error('请输入订单号和手机号后四位');
        }
        $digits = preg_replace('/\D/', '', $phoneParam);
        if (strlen($digits) < 4) {
            $this->error('请输入手机号后四位');
        }
        $tail = substr($digits, -4);

        $order = Db::name('order')->where('orderid', $orderNo)->field('id,mobile')->find();
        if (!$order) {
            $this->error('订单不存在');
        }
        $orderMobile = preg_replace('/\D/', '', (string) ($order['mobile'] ?? ''));
        if (strlen($orderMobile) < 4 || substr($orderMobile, -4) !== $tail) {
            $this->error('订单号或手机号后四位不正确');
        }

        $data = Db::name('trajectory')->where('order_id', $order['id'])->order('id asc')->select();
        foreach ($data as &$v) {
            $v['createtime'] = date('Y-m-d H:i:s', $v['createtime']);
        }
        unset($v);
        $this->success('success', $data);
    }
    public function orderocr()
    {
//        die;
        $requestTime = date('Y-m-d H:i:s');
        $user = $this->auth->getUser();
        $userId = !empty($user['id']) ? (int) $user['id'] : 0;
        $ip = $this->request->ip();
        $forceRefresh = (int) $this->request->param('force', 0) === 1;

        // 1. 接收前端传来的 线上图片地址
        $img_url = trim((string) $this->request->param('image'));
//        $this->error('AI识别功能维护中，请手动输入地址');
//        $img_url = 'https://lzwl.longzhehutong.cn/uploads/20260528/59a3818de2cf6711ec23c6055f08f199.jpg';
        Log::write(sprintf(
            '[orderocr] request time=%s user_id=%d ip=%s force=%d image=%s',
            $requestTime,
            $userId,
            $ip,
            $forceRefresh ? 1 : 0,
            $img_url !== '' ? $img_url : '[empty]'
        ), 'info');
        if ($img_url === '') {
            Log::write(sprintf(
                '[orderocr] rejected time=%s user_id=%d ip=%s reason=empty_image',
                $requestTime,
                $userId,
                $ip
            ), 'warning');
            return json(['code' => 0, 'msg' => '请传入图片地址']);
        }

        $increaseOcrCount = function () use ($userId, $ip, $img_url, $requestTime) {
            if ($userId > 0) {
                Db::name('user')->where('id', $userId)->setInc('ocr_num');
            }
            Log::write(sprintf(
                '[orderocr] count_inc time=%s user_id=%d ip=%s image_md5=%s',
                $requestTime,
                $userId,
                $ip,
                md5($img_url)
            ), 'info');
        };

        // 同图缓存：同一 URL 直接返回，不调百度 OCR（force=1 强制重识别）
        $ocrCacheTtl = 604800; // 7 天
        $urlCacheKey = 'orderocr:v3:url:' . md5($img_url);
        if (!$forceRefresh) {
            $cached = Cache::get($urlCacheKey);
            if (is_array($cached) && !empty($cached)) {
                Log::write(sprintf(
                    '[orderocr] cache_hit time=%s user_id=%d ip=%s image_md5=%s cache_key=url',
                    $requestTime,
                    $userId,
                    $ip,
                    md5($img_url)
                ), 'info');
                return json(['code' => 1, 'msg' => '识别成功', 'data' => $cached]);
            }
        }

        // 下载图片后按内容 MD5 再查一次（不同 URL 同一文件）
        $imgContent = $this->downloadOcrImage($img_url);
        if (!$imgContent) {
            Log::write(sprintf(
                '[orderocr] image_read_failed time=%s user_id=%d ip=%s image_md5=%s url=%s',
                $requestTime,
                $userId,
                $ip,
                md5($img_url),
                $img_url !== '' ? $img_url : '[empty]'
            ), 'warning');
            return json(['code' => 0, 'msg' => '图片地址无法读取']);
        }
        $contentMd5 = md5($imgContent);
        $md5CacheKey = 'orderocr:v3:md5:' . $contentMd5;
        if (!$forceRefresh) {
            $cached = Cache::get($md5CacheKey);
            if (is_array($cached) && !empty($cached)) {
                Log::write(sprintf(
                    '[orderocr] cache_hit time=%s user_id=%d ip=%s image_md5=%s cache_key=md5',
                    $requestTime,
                    $userId,
                    $ip,
                    $contentMd5
                ), 'info');
                Cache::set($urlCacheKey, $cached, $ocrCacheTtl);
                return json(['code' => 1, 'msg' => '识别成功', 'data' => $cached]);
            }
        }

        // 获取百度 TOKEN
        $token = $this->run();
        $token = json_decode($token, true);
        if (!isset($token['access_token'])) {
            Log::write(sprintf(
                '[orderocr] token_failed time=%s user_id=%d ip=%s image_md5=%s raw=%s',
                $requestTime,
                $userId,
                $ip,
                $contentMd5,
                is_array($token) ? json_encode($token, JSON_UNESCAPED_UNICODE) : '[invalid_json]'
            ), 'error');
            return json(['code' => 0, 'msg' => 'token获取失败']);
        }
        $access_token = $token['access_token'];

        // 转 BASE64 并请求百度 OCR
        $imgBase64 = base64_encode($imgContent);
        $url = 'https://aip.baidubce.com/rest/2.0/ocr/v1/accurate_basic?access_token=' . $access_token;
        $bodys = [
            'image' => $imgBase64,
            'image_type' => 'BASE64',
        ];

        $res = request_post($url, $bodys);
        $res = json_decode($res, true);
        if (!is_array($res) || !empty($res['error_code']) || !isset($res['words_result'])) {
            Log::write(sprintf(
                '[orderocr] ocr_failed time=%s user_id=%d ip=%s image_md5=%s raw=%s',
                $requestTime,
                $userId,
                $ip,
                $contentMd5,
                is_array($res) ? json_encode($res, JSON_UNESCAPED_UNICODE) : '[invalid_json]'
            ), 'error');
            return json(['code' => 0, 'msg' => $res['error_msg'] ?? 'OCR识别失败']);
        }
//        print_r($res);die;
        // ===================== 提取全部数据 =====================
        $words = array_column($res['words_result'] ?? [], 'words');
        $text = implode(' ', $words);
//        print_r($text);die;
        // 基础信息
        $load_address = '';
        $unload_address = '';
        $goods_info = '';
        $price = '';
        $weight = '';
        $volume = '';

        // 新增字段
        $car_size = '';
        $car_type = '';
        $goods_type = '';
        $deposit = '';
        $deposit_refund = '';
        $service_fee = '';
        // 判断 OCR 词条是否为地址片段（过滤「为什么里程不一致」等 UI 提示）
        $isAddressFragment = function ($word) {
            $word = trim((string) $word);
            if ($word === '') {
                return false;
            }
            if (preg_match('/为什么|里程|不一致|客服|举报|分享|抢单|加价|有禁区|货源反馈/u', $word)) {
                return false;
            }
            if (preg_match('/^[?？…。!！]+$/u', $word)) {
                return false;
            }
            if (preg_match('/[省市区县镇乡村路街道号园区厂仓物流总部城]/u', $word)) {
                return true;
            }
            if (preg_match('/(?:集团|公司|有限|建材|材料|装饰|物流|仓储|市场|国际|商贸)/u', $word)) {
                return true;
            }
            if (preg_match('/\d/', $word)) {
                return true;
            }
            if (strpos($word, '-') !== false) {
                return true; 
            }
            return false;
        };

        // 合并换行拆分的地址片段（处理 OCR 跨行识别 + 重叠字符拼接）
        $mergeAddressFragments = function (array $fragments) {
            if (empty($fragments)) {
                return '';
            }
            $result = $fragments[0];
            for ($i = 1; $i < count($fragments); $i++) {
                $next = $fragments[$i];
                $maxOverlap = 0;
                $maxLen = min(mb_strlen($result), mb_strlen($next));
                for ($j = 1; $j <= $maxLen; $j++) {
                    if (mb_substr($result, -$j) === mb_substr($next, 0, $j)) {
                        $maxOverlap = $j;
                    }
                }
                $result .= mb_substr($next, $maxOverlap);
            }
            return $result;
        };

        // 收集装/卸之间的地址（含换行续行）
        $collectAddressBetween = function ($startIdx, $endIdx) use ($words, $isAddressFragment) {
            $fragments = [];
            $collecting = false;
            for ($i = $startIdx + 1; $i < $endIdx; $i++) {
                $word = trim($words[$i] ?? '');
                if ($word === '') {
                    continue;
                }
                if (preg_match('/为什么|里程|不一致|客服|举报|分享|抢单|加价|有禁区|货源反馈/u', $word)) {
                    $collecting = false;
                    continue;
                }
                if ($isAddressFragment($word)) {
                    $fragments[] = $word;
                    $collecting = true;
                } elseif ($collecting && preg_match('/^[\x{4e00}-\x{9fa5}\d\-号路街巷弄]+$/u', $word)) {
                    $fragments[] = $word;
                }
            }
            return $fragments;
        };

        // ==========================================
        // 1. 装货地址：装 → 卸
        // ==========================================
        $index_z = array_search('装', $words);
        $index_x = array_search('卸', $words);
        if ($index_z !== false && $index_x !== false) {
            $load_address = $mergeAddressFragments($collectAddressBetween($index_z, $index_x));
        }

        // ==========================================
        // 2. 卸货地址：卸 → km
        // ==========================================
        $km_index = 0;
        foreach ($words as $k => $v) {
            if (strpos($v, 'km') !== false) {
                $km_index = $k;
                break;
            }
        }
        if ($index_x !== false && $km_index > 0) {
            $unload_address = $mergeAddressFragments($collectAddressBetween($index_x, $km_index));
        }

        // ==========================================
        // 3. 货物信息 + 吨 + 方 + 货物类型
        // ==========================================
        $goods_index = array_search('货物', $words);
        if ($goods_index !== false) {
            $goods_info = $words[$goods_index + 1] ?? '';
            // 提取吨、方
            if(preg_match('/(\d+\.?\d*)\s*吨/', $goods_info, $w)) $weight = $w[1];
            if(preg_match('/(\d+\.?\d*)\s*方/', $goods_info, $v)) $volume = $v[1];
            // 货物类型
            $goods_name = '';
            if (preg_match('/^([^\d]+)/u', $goods_info, $gt)) {
                $goods_name = trim($gt[1]);
            }
            $forbidden_words = ['机械', '设备', '易爆', '毒品', '枪支', '弹药', '腐蚀', '放射性'];
            $goods_tips = '';
            // 默认值先设为 1（正常）；用完整 goods_info 检测，避免名称正则未匹配时漏检
            $goods_type = 1;
            $goods_check = $goods_name !== '' ? $goods_name : $goods_info;
            foreach ($forbidden_words as $word) {
                if (mb_strpos($goods_check, $word) !== false) {
                    $goods_type = 3; // 标记违禁
                    break;
                }
            }
        }
        // 4. 运费
        // ==========================================
        foreach($words as $v){
            if(mb_strpos($v, '净得运费') !== false && mb_strpos($v, '元') !== false){
                $price = $v;
                break;
            }
        }
        // ==========================================
        // 5. 车辆信息
// ==========================================
        $car_index = array_search('车辆', $words);
        $car_size = 0; // 初始化
        if ($car_index !== false) {
            $car_str = $words[$car_index + 1] ?? '';

            // ========== 修复：匹配所有数字（不管是不是连着米）==========
            preg_match_all('/\d+\.?\d*/', $car_str, $m);
            $all_numbers = $m[0] ?? [];

            if (!empty($all_numbers)) {
                $car_size = min($all_numbers); // 取最小：4.2
            }

            $car_type = $car_str;
        }
// print_r($car_size);die;

        $car = Db::name('car_type')->field('id,car_boxlength')->select();
// 自动匹配车长 → 对应ID（多个匹配时选最小车长）
        $car_id = 0;
        if (!empty($car_size) && !empty($car)) {
            $matches = [];
            $targetSize = (float)$car_size;
            foreach ($car as $item) {
                $lengthStr = trim((string)$item['car_boxlength']);
                if ($lengthStr === '') {
                    continue;
                }
                // 统一解析为 [min, max]：区间如 2.6-3.8，单值如 9.6 视为 9.6-9.6
                if (str_contains($lengthStr, '-')) {
                    [$minStr, $maxStr] = explode('-', $lengthStr, 2);
                    $min = (float)trim($minStr);
                    $max = (float)trim($maxStr);
                } else {
                    $min = $max = (float)$lengthStr;
                }
                if ($targetSize >= $min && $targetSize <= $max) {
                    $matches[] = [
                        'max_sort' => $max, // 区间上限：越小表示车型越小
                        'min_sort' => $min,
                        'id' => $item['id'],
                    ];
                }
            }
            // 多个匹配时：先选上限最小的（最小够用车型），上限相同则选区间更紧的
            if (!empty($matches)) {
                usort($matches, function ($a, $b) {
                    if ($a['max_sort'] !== $b['max_sort']) {
                        return $a['max_sort'] <=> $b['max_sort'];
                    }
                    return $b['min_sort'] <=> $a['min_sort'];
                });
                $car_id = $matches[0]['id'];
            }
        }
        // ==========================================
        // 6. 订金：取第二个金额
        // ==========================================
        $deposit_refund = '信息费（原定金不退还）';
        $deposit_numbers = [];
        $dingjin_index = array_search('订金', $words);
        if($dingjin_index !== false){
            for($i = $dingjin_index +1; $i < count($words); $i++){
                $w = trim($words[$i]);
                if(preg_match('/^(\d+\.?\d*)元$/u', $w, $match)){
                    $deposit_numbers[] = $match[1];
                }
                if(count($deposit_numbers) == 2) break;
            }
            if(count($deposit_numbers) == 2){
                $deposit = $deposit_numbers[1];
            }elseif(count($deposit_numbers) == 1){
                $deposit = $deposit_numbers[0];
            }
            if(mb_strpos($text, '退还') !== false || mb_strpos($text, '可退') !== false){
                $deposit_refund = '可退';
            }
        }
        // ==========================================
        // 7. 技术服务费
        // ==========================================
        if(preg_match('/技术服务费\D*?(\d+\.?\d*)\s*元/u', $text, $s)){
            $service_fee = $s[1];
        }

        $ensureProvinceInAddress = function ($addr) {
            $addr = trim((string) $addr);
            if ($addr === '') {
                return $addr;
            }
            if (preg_match('/(省|自治区|特别行政区)/u', $addr)) {
                return $addr;
            }
            foreach (['北京', '上海', '天津', '重庆'] as $m) {
                if (mb_strpos($addr, $m) === 0) {
                    return $addr;
                }
            }

            $cityName = '';
            $useDash = strpos($addr, '-') !== false;
            $firstSegment = $useDash ? trim((string) explode('-', $addr, 2)[0]) : $addr;

            // 优先从地址开头匹配数据库中的城市，兼容“杭州-…”、“杭州市…”以及 OCR 未带“市”的情况。
            $cityRows = Db::name('area')->where('level', 2)->field('id,pid,name,shortname')->select();
            $cityRow = null;
            $matchedLength = 0;
            foreach ($cityRows as $row) {
                foreach (array_unique([$row['name'] ?? '', $row['shortname'] ?? '']) as $candidate) {
                    $candidate = trim((string) $candidate);
                    if ($candidate !== '' && mb_strpos($firstSegment, $candidate) === 0 && mb_strlen($candidate) > $matchedLength) {
                        $cityRow = $row;
                        $cityName = $candidate;
                        $matchedLength = mb_strlen($candidate);
                    }
                }
            }

            if (!$cityRow) {
                if ($useDash && preg_match('/[市州盟]$/u', $firstSegment)) {
                    $cityName = $firstSegment;
                } elseif (!$useDash && preg_match('/^([\x{4e00}-\x{9fa5}]{2,10}?(?:市|地区|自治州|盟))/u', $addr, $m)) {
                    $cityName = $m[1];
                }
                if ($cityName !== '') {
                    $cityRow = Db::name('area')->where('level', 2)->where('name', $cityName)->find();
                    if (!$cityRow) {
                        $short = preg_replace('/(市|地区|自治州|盟)$/u', '', $cityName);
                        $cityRow = Db::name('area')->where('level', 2)->where('shortname', $short)->find();
                    }
                }
            }

            $provinceName = '';
            if ($cityRow && !empty($cityRow['pid'])) {
                $parent = Db::name('area')->where('id', $cityRow['pid'])->find();
                // 儋州、东莞等部分城市在行政区数据中可能直接挂在省下，也可能多一层中间节点。
                while ($parent) {
                    if ((int) ($parent['level'] ?? 0) === 1 || empty($parent['pid'])) {
                        $provinceName = trim((string) ($parent['name'] ?? ''));
                        break;
                    }
                    $parent = Db::name('area')->where('id', $parent['pid'])->find();
                }
            }

            // 兼容数据库中缺失或层级异常的省直辖县级市。
            if ($provinceName === '') {
                $directAdminCities = [
                    '儋州' => '海南省',
                    '仙桃' => '湖北省',
                    '潜江' => '湖北省',
                    '天门' => '湖北省',
                    '神农架' => '湖北省',
                    '济源' => '河南省',
                ];
                foreach ($directAdminCities as $city => $province) {
                    if (mb_strpos($firstSegment, $city) === 0) {
                        $provinceName = $province;
                        break;
                    }
                }
            }

            if ($provinceName === '') {
                return $addr;
            }

            return $provinceName . ($useDash ? '-' : '') . $addr;
        };

        // 从路段中提取完整公司名称（路段可能含路名+门牌号前缀）
        $extractCompanyName = function ($segment) {
            $segment = trim((string) $segment);
            if ($segment === '') {
                return '';
            }
            if (preg_match('/([\x{4e00}-\x{9fa5}]+(?:集团|公司|有限|股份|建材|材料|装饰|物流|仓储|产业园|市场|城|厂|店|中心|部|国际|商贸|科技|发展|王国))$/u', $segment, $m)) {
                return $m[1];
            }
            return $segment;
        };

        // 门/入口类后缀：最后一个 - 后面若是这类词，则公司名在其前一段
        $gateSuffixPattern = '/^(?:东|西|南|北|中|主|侧|正|后|东南|西南|东北|西北)(?:\d+)?门$|^\d+号门$/u';

        $splitAddress = function ($addr) use ($ensureProvinceInAddress, $extractCompanyName, $gateSuffixPattern) {
            $addr = $ensureProvinceInAddress((string) $addr);
            $parts = array_values(array_filter(explode('-', $addr), 'strlen'));
            if (empty($parts)) {
                return ['address' => '', 'detailed_address' => $addr];
            }

            $lastPart = $parts[count($parts) - 1];
            if (preg_match($gateSuffixPattern, $lastPart) && count($parts) >= 2) {
                $companyName = $extractCompanyName($parts[count($parts) - 2]);
            } else {
                $companyName = $extractCompanyName($lastPart);
            }
            return [
                'address'          => $companyName,
                'detailed_address' => $addr,
            ];
        };

        // ===================== 返回最终结果 =====================
        $data = [
            'load_address'    => $splitAddress($load_address),
            'unload_address'  => $splitAddress($unload_address),
            'goods_type'      => $goods_type,
            'weight'          => $weight,
            'volume'          => $volume,
            'car_size'        => $car_id,
            'deposit'         => $deposit,
            'service_fee'     => $service_fee
        ];
        // 仅缓存有效识别结果：装/卸地址至少有一个，避免糊图空结果占住缓存
        $loadOk = trim((string) ($data['load_address']['detailed_address'] ?? '')) !== '';
        $unloadOk = trim((string) ($data['unload_address']['detailed_address'] ?? '')) !== '';
        if ($loadOk || $unloadOk) {
            Cache::set($urlCacheKey, $data, $ocrCacheTtl);
            Cache::set($md5CacheKey, $data, $ocrCacheTtl);
        }

        Log::write(sprintf(
            '[orderocr] success time=%s user_id=%d ip=%s image_md5=%s load_ok=%d unload_ok=%d',
            $requestTime,
            $userId,
            $ip,
            $contentMd5,
            $loadOk ? 1 : 0,
            $unloadOk ? 1 : 0
        ), 'info');

        if ($userId > 0) {
            Db::name('user')->where('id', $userId)->setInc('ocr_num');
            Log::write(sprintf(
                '[orderocr] count_inc time=%s user_id=%d ip=%s image_md5=%s',
                $requestTime,
                $userId,
                $ip,
                $contentMd5
            ), 'info');
        }

        // OCR 识别素材用完即删：图片只是用于识别填表，识别成功后不再需要长期保留
        $this->deleteOcrUploadedImage($img_url);

        return json([
            'code' => 1,
            'msg' => '识别成功',
            'data' => $data
        ]);
    }

    /**
     * 下载前端传入的图片地址（供 OCR 识别）。
     * 优先直读本地上传文件（避免服务器回环请求自身域名，绕过宝塔WAF、
     * allow_url_fopen、SSL 校验等限制）；外部地址(CDN/OSS)走 cURL 下载。
     * @param string $url 图片完整地址或相对路径
     * @return string|false 图片二进制内容，失败返回 false
     */
    protected function downloadOcrImage($url)
    {
        $url = trim((string)$url);
        if ($url === '') {
            return false;
        }
        // 1) 本站上传目录 => 直接读本地文件，最稳，也不受 WAF/allow_url_fopen 影响
        $localPath = $this->resolveOcrLocalPath($url);
        if ($localPath !== null && is_file($localPath)) {
            $content = @file_get_contents($localPath);
            if ($content !== false && $content !== '') {
                return $content;
            }
        }
        // 2) 外部地址（CDN/OSS 等）走 cURL：关闭证书校验、跟随跳转、带 UA，
        //    规避 file_get_contents 受 allow_url_fopen 限制及无 UA 被 WAF 拦截
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; LongzheHutong/1.0)',
            CURLOPT_HTTPHEADER     => ['Accept: image/*,*/*;q=0.8'],
        ]);
        $content  = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if ($content === false || $httpCode >= 400) {
            return false;
        }
        return $content === '' ? false : $content;
    }

    /**
     * 将图片地址映射为本地 uploads/ns 目录下的物理路径。
     * 仅当地址指向本站（同 Host，或相对 /uploads/、/ns/ 路径）时返回路径，否则返回 null。
     * @param string $url
     * @return string|null
     */
    protected function resolveOcrLocalPath($url)
    {
        $parts = parse_url($url);
        if ($parts === false) {
            return null;
        }
        $host = isset($parts['host']) ? strtolower((string)$parts['host']) : '';
        $path = isset($parts['path']) && $parts['path'] !== '' ? (string)$parts['path'] : '';
        if (!preg_match('#^/(uploads|ns)/#i', $path)) {
            return null;
        }
        // 无域名（相对路径）或域名与当前请求 Host 一致 => 视为本站上传文件
        if ($host !== '') {
            $reqHost = strtolower((string)$this->request->host());
            $reqHost = preg_replace('/:\d+$/', '', $reqHost);
            if ($host !== $reqHost) {
                return null;
            }
        }
        return $this->uploadPathToLocal($path);
    }

    /**
     * /uploads/... 或 /ns/... 相对路径 -> public 物理路径
     * @param string $path
     * @return string|null
     */
    protected function uploadPathToLocal($path)
    {
        $publicDir = realpath(dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'public');
        if ($publicDir === false) {
            $publicDir = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'public';
        }
        $root = rtrim($publicDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $localPath = $root . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
        // 目录穿越防护：必须仍在 public 目录内
        if (strpos($localPath, '..') !== false || strpos($localPath, $root) !== 0) {
            return null;
        }
        return $localPath;
    }

    /**
     * 删除 OCR 识别用的临时图片（文件 + attachment 记录）。
     * 仅删除位于本地上传目录(/uploads)内的文件，避免误删外部 URL。
     */
    protected function deleteOcrUploadedImage($imgUrl)
    {
        $imgUrl = trim((string)$imgUrl);
        if ($imgUrl === '') {
            return;
        }
        // 提取 /uploads/... 路径段（去掉域名前缀/相对路径前缀）
        if (strpos($imgUrl, '/uploads/') === false) {
            // 兼容去掉域名后仍以 /uploads/ 开头之外的形态（如纯文件名），不做处理
            return;
        }
        $pos = strpos($imgUrl, '/uploads/');
        $relPath = substr($imgUrl, $pos); // 形如 /uploads/20260101/xx.jpg
        // public 目录物理路径：application/api/controller/Opinion.php 上溯三级
        $publicDir = realpath(dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'public');
        if ($publicDir === false) {
            $publicDir = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'public';
        }
        $localPath = rtrim($publicDir, DIRECTORY_SEPARATOR) . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relPath);
        // 安全校验：必须仍在 public/uploads 目录内
        $uploadsDir = rtrim(rtrim($publicDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads', DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!preg_match('#^' . preg_quote($uploadsDir, '#') . '#', $localPath)) {
            return;
        }
        if (is_file($localPath)) {
            @unlink($localPath);
        }
        // 删除 attachment 对应记录（按 url 精确匹配）
        try {
            Db::name('attachment')->where('url', 'like', '%' . $relPath . '%')->delete();
        } catch (\Exception $e) {
            // 忽略删除记录失败，不影响识别结果
        }
    }

    public function run() {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://aip.baidubce.com/oauth/2.0/token?client_id=6PumL0E7s4BTcXrv36dhKDkJ&client_secret=rQBUccayM7pl9U1GODNVgsEfNgKfqTxf&grant_type=client_credentials",
            CURLOPT_TIMEOUT => 30,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',


            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Accept: application/json'
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

}

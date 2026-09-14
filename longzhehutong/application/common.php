<?php

// 公共助手函数

use think\Config;
use think\exception\HttpResponseException;
use think\Response;

if (!function_exists('__')) {

    /**
     * 获取语言变量值
     * @param string $name 语言变量名
     * @param string | array  $vars 动态变量值
     * @param string $lang 语言
     * @return mixed
     */
    function __($name, $vars = [], $lang = '')
    {
        if (is_numeric($name) || !$name) {
            return $name;
        }
        if (!is_array($vars)) {
            $vars = func_get_args();
            array_shift($vars);
            $lang = '';
        }
        return \think\Lang::get($name, $vars, $lang);
    }
}

if (!function_exists('format_bytes')) {

    /**
     * 将字节转换为可读文本
     * @param int    $size      大小
     * @param string $delimiter 分隔符
     * @param int    $precision 小数位数
     * @return string
     */
    function format_bytes($size, $delimiter = '', $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
        for ($i = 0; $size >= 1024 && $i < 5; $i++) {
            $size /= 1024;
        }
        return round($size, $precision) . $delimiter . $units[$i];
    }
}

if (!function_exists('datetime')) {

    /**
     * 将时间戳转换为日期时间
     * @param int    $time   时间戳
     * @param string $format 日期时间格式
     * @return string
     */
    function datetime($time, $format = 'Y-m-d H:i:s')
    {
        $time = is_numeric($time) ? $time : strtotime($time);
        return date($format, $time);
    }
}

if (!function_exists('human_date')) {

    /**
     * 获取语义化时间
     * @param int $time  时间
     * @param int $local 本地时间
     * @return string
     */
    function human_date($time, $local = null)
    {
        return \fast\Date::human($time, $local);
    }
}

if (!function_exists('cdnurl')) {

    /**
     * 获取上传资源的CDN的地址
     * @param string  $url    资源相对地址
     * @param boolean $domain 是否显示域名 或者直接传入域名
     * @return string
     */
    function cdnurl($url, $domain = false)
    {
        $regex = "/^((?:[a-z]+:)?\/\/|data:image\/)(.*)/i";
        $cdnurl = \think\Config::get('upload.cdnurl');
        if (is_bool($domain) || stripos($cdnurl, '/') === 0) {
            $url = preg_match($regex, $url) || ($cdnurl && stripos($url, $cdnurl) === 0) ? $url : $cdnurl . $url;
        }
        if ($domain && !preg_match($regex, $url)) {
            $domain = is_bool($domain) ? request()->domain() : $domain;
            $url = $domain . $url;
        }
        return $url;
    }
}


if (!function_exists('is_really_writable')) {

    /**
     * 判断文件或文件夹是否可写
     * @param string $file 文件或目录
     * @return    bool
     */
    function is_really_writable($file)
    {
        if (DIRECTORY_SEPARATOR === '/') {
            return is_writable($file);
        }
        if (is_dir($file)) {
            $file = rtrim($file, '/') . '/' . md5(mt_rand());
            if (($fp = @fopen($file, 'ab')) === false) {
                return false;
            }
            fclose($fp);
            @chmod($file, 0777);
            @unlink($file);
            return true;
        } elseif (!is_file($file) or ($fp = @fopen($file, 'ab')) === false) {
            return false;
        }
        fclose($fp);
        return true;
    }
}

if (!function_exists('rmdirs')) {

    /**
     * 删除文件夹
     * @param string $dirname  目录
     * @param bool   $withself 是否删除自身
     * @return boolean
     */
    function rmdirs($dirname, $withself = true)
    {
        if (!is_dir($dirname)) {
            return false;
        }
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirname, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
        if ($withself) {
            @rmdir($dirname);
        }
        return true;
    }
}

if (!function_exists('copydirs')) {

    /**
     * 复制文件夹
     * @param string $source 源文件夹
     * @param string $dest   目标文件夹
     */
    function copydirs($source, $dest)
    {
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }
        foreach (
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            ) as $item
        ) {
            if ($item->isDir()) {
                $sontDir = $dest . DS . $iterator->getSubPathName();
                if (!is_dir($sontDir)) {
                    mkdir($sontDir, 0755, true);
                }
            } else {
                copy($item, $dest . DS . $iterator->getSubPathName());
            }
        }
    }
}

if (!function_exists('mb_ucfirst')) {
    function mb_ucfirst($string)
    {
        return mb_strtoupper(mb_substr($string, 0, 1)) . mb_strtolower(mb_substr($string, 1));
    }
}

if (!function_exists('addtion')) {

    /**
     * 附加关联字段数据
     * @param array $items  数据列表
     * @param mixed $fields 渲染的来源字段
     * @return array
     */
    function addtion($items, $fields)
    {
        if (!$items || !$fields) {
            return $items;
        }
        $fieldsArr = [];
        if (!is_array($fields)) {
            $arr = explode(',', $fields);
            foreach ($arr as $k => $v) {
                $fieldsArr[$v] = ['field' => $v];
            }
        } else {
            foreach ($fields as $k => $v) {
                if (is_array($v)) {
                    $v['field'] = $v['field'] ?? $k;
                } else {
                    $v = ['field' => $v];
                }
                $fieldsArr[$v['field']] = $v;
            }
        }
        foreach ($fieldsArr as $k => &$v) {
            $v = is_array($v) ? $v : ['field' => $v];
            $v['display'] = $v['display'] ?? str_replace(['_ids', '_id'], ['_names', '_name'], $v['field']);
            $v['primary'] = $v['primary'] ?? '';
            $v['column'] = $v['column'] ?? 'name';
            $v['model'] = $v['model'] ?? '';
            $v['table'] = $v['table'] ?? '';
            $v['name'] = $v['name'] ?? str_replace(['_ids', '_id'], '', $v['field']);
        }
        unset($v);
        $ids = [];
        $fields = array_keys($fieldsArr);
        foreach ($items as $k => $v) {
            foreach ($fields as $m => $n) {
                if (isset($v[$n])) {
                    $ids[$n] = array_merge(isset($ids[$n]) && is_array($ids[$n]) ? $ids[$n] : [], explode(',', $v[$n]));
                }
            }
        }
        $result = [];
        foreach ($fieldsArr as $k => $v) {
            if ($v['model']) {
                $model = new $v['model'];
            } else {
                // 优先判断使用table的配置
                $model = $v['table'] ? \think\Db::table($v['table']) : \think\Db::name($v['name']);
            }
            $primary = $v['primary'] ?: $model->getPk();
            $result[$v['field']] = isset($ids[$v['field']]) ? $model->where($primary, 'in', $ids[$v['field']])->column($v['column'], $primary) : [];
        }

        foreach ($items as $k => &$v) {
            foreach ($fields as $m => $n) {
                if (isset($v[$n])) {
                    $curr = array_flip(explode(',', $v[$n]));

                    $linedata = array_intersect_key($result[$n], $curr);
                    $v[$fieldsArr[$n]['display']] = $fieldsArr[$n]['column'] == '*' ? $linedata : implode(',', $linedata);
                }
            }
        }
        return $items;
    }
}

if (!function_exists('var_export_short')) {

    /**
     * 使用短标签打印或返回数组结构
     * @param mixed   $data
     * @param boolean $return 是否返回数据
     * @return string
     */
    function var_export_short($data, $return = true)
    {
        return var_export($data, $return);
    }
}

if (!function_exists('letter_avatar')) {
    /**
     * 首字母头像
     * @param $text
     * @return string
     */
    function letter_avatar($text)
    {
        $total = unpack('L', hash('adler32', $text, true))[1];
        $hue = $total % 360;
        list($r, $g, $b) = hsv2rgb($hue / 360, 0.3, 0.9);

        $bg = "rgb({$r},{$g},{$b})";
        $color = "#ffffff";
        $first = mb_strtoupper(mb_substr($text, 0, 1));
        $src = base64_encode('<svg xmlns="http://www.w3.org/2000/svg" version="1.1" height="100" width="100"><rect fill="' . $bg . '" x="0" y="0" width="100" height="100"></rect><text x="50" y="50" font-size="50" text-copy="fast" fill="' . $color . '" text-anchor="middle" text-rights="admin" dominant-baseline="central">' . $first . '</text></svg>');
        $value = 'data:image/svg+xml;base64,' . $src;
        return $value;
    }
}

if (!function_exists('hsv2rgb')) {
    function hsv2rgb($h, $s, $v)
    {
        $r = $g = $b = 0;

        $i = floor($h * 6);
        $f = $h * 6 - $i;
        $p = $v * (1 - $s);
        $q = $v * (1 - $f * $s);
        $t = $v * (1 - (1 - $f) * $s);

        switch ($i % 6) {
            case 0:
                $r = $v;
                $g = $t;
                $b = $p;
                break;
            case 1:
                $r = $q;
                $g = $v;
                $b = $p;
                break;
            case 2:
                $r = $p;
                $g = $v;
                $b = $t;
                break;
            case 3:
                $r = $p;
                $g = $q;
                $b = $v;
                break;
            case 4:
                $r = $t;
                $g = $p;
                $b = $v;
                break;
            case 5:
                $r = $v;
                $g = $p;
                $b = $q;
                break;
        }

        return [
            floor($r * 255),
            floor($g * 255),
            floor($b * 255)
        ];
    }
}

if (!function_exists('check_nav_active')) {
    /**
     * 检测会员中心导航是否高亮
     */
    function check_nav_active($url, $classname = 'active')
    {
        $auth = \app\common\library\Auth::instance();
        $requestUrl = $auth->getRequestUri();
        $url = ltrim($url, '/');
        return $requestUrl === str_replace(".", "/", $url) ? $classname : '';
    }
}

if (!function_exists('check_cors_request')) {
    /**
     * 跨域检测
     */
    function check_cors_request()
    {
        if (isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN'] && config('fastadmin.cors_request_domain')) {
            $info = parse_url($_SERVER['HTTP_ORIGIN']);
            $domainArr = explode(',', config('fastadmin.cors_request_domain'));
            $domainArr[] = request()->host(true);
            if (in_array("*", $domainArr) || in_array($_SERVER['HTTP_ORIGIN'], $domainArr) || (isset($info['host']) && in_array($info['host'], $domainArr))) {
                header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
            } else {
                $response = Response::create('跨域检测无效', 'html', 403);
                throw new HttpResponseException($response);
            }

            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');

            if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
                if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
                    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
                }
                if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
                    header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
                }
                $response = Response::create('', 'html');
                throw new HttpResponseException($response);
            }
        }
    }
}

if (!function_exists('xss_clean')) {
    /**
     * 清理XSS
     */
    function xss_clean($content, $is_image = false)
    {
        return \app\common\library\Security::instance()->xss_clean($content, $is_image);
    }
}

if (!function_exists('url_clean')) {
    /**
     * 清理URL
     */
    function url_clean($url)
    {
        if (!check_url_allowed($url)) {
            return '';
        }
        return xss_clean($url);
    }
}

if (!function_exists('check_ip_allowed')) {
    /**
     * 检测IP是否允许
     * @param string $ip IP地址
     */
    function check_ip_allowed($ip = null)
    {
        $ip = is_null($ip) ? request()->ip() : $ip;
        $forbiddenipArr = config('site.forbiddenip');
        $forbiddenipArr = !$forbiddenipArr ? [] : $forbiddenipArr;
        $forbiddenipArr = is_array($forbiddenipArr) ? $forbiddenipArr : array_filter(explode("\n", str_replace("\r\n", "\n", $forbiddenipArr)));
        if ($forbiddenipArr && \Symfony\Component\HttpFoundation\IpUtils::checkIp($ip, $forbiddenipArr)) {
            $response = Response::create('请求无权访问', 'html', 403);
            throw new HttpResponseException($response);
        }
    }
}

if (!function_exists('check_url_allowed')) {
    /**
     * 检测URL是否允许
     * @param string $url URL
     * @return bool
     */
    function check_url_allowed($url = '')
    {
        //允许的主机列表
        $allowedHostArr = [
            strtolower(request()->host())
        ];

        if (empty($url)) {
            return true;
        }

        //如果是站内相对链接则允许
        if (preg_match("/^[\/a-z][a-z0-9][a-z0-9\.\/]+((\?|#).*)?\$/i", $url) && substr($url, 0, 2) !== '//') {
            return true;
        }

        //如果是站外链接则需要判断HOST是否允许
        if (preg_match("/((http[s]?:\/\/)+((?>[a-z\-0-9]{2,}\.)+[a-z]{2,8}|((?>([0-9]{1,3}\.)){3}[0-9]{1,3}))(:[0-9]{1,5})?)(?:\s|\/)/i", $url)) {
            $chkHost = parse_url(strtolower($url), PHP_URL_HOST);
            if ($chkHost && in_array($chkHost, $allowedHostArr)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('build_suffix_image')) {
    /**
     * 生成文件后缀图片
     * @param string $suffix 后缀
     * @param null   $background
     * @return string
     */
    function build_suffix_image($suffix, $background = null)
    {
        $suffix = mb_substr(strtoupper($suffix), 0, 4);
        $total = unpack('L', hash('adler32', $suffix, true))[1];
        $hue = $total % 360;
        list($r, $g, $b) = hsv2rgb($hue / 360, 0.3, 0.9);

        $background = $background ? $background : "rgb({$r},{$g},{$b})";

        $icon = <<<EOT
        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve">
            <path style="fill:#E2E5E7;" d="M128,0c-17.6,0-32,14.4-32,32v448c0,17.6,14.4,32,32,32h320c17.6,0,32-14.4,32-32V128L352,0H128z"/>
            <path style="fill:#B0B7BD;" d="M384,128h96L352,0v96C352,113.6,366.4,128,384,128z"/>
            <polygon style="fill:#CAD1D8;" points="480,224 384,128 480,128 "/>
            <path style="fill:{$background};" d="M416,416c0,8.8-7.2,16-16,16H48c-8.8,0-16-7.2-16-16V256c0-8.8,7.2-16,16-16h352c8.8,0,16,7.2,16,16 V416z"/>
            <path style="fill:#CAD1D8;" d="M400,432H96v16h304c8.8,0,16-7.2,16-16v-16C416,424.8,408.8,432,400,432z"/>
            <g><text><tspan x="220" y="380" font-size="124" font-family="Verdana, Helvetica, Arial, sans-serif" fill="white" text-anchor="middle">{$suffix}</tspan></text></g>
        </svg>
EOT;
        return $icon;
    }
}


//获取OPenid
function getopenid($code,$appid,$secret){
    $url = "https://api.weixin.qq.com/sns/jscode2session";
    // 参数
    $params['appid']= $appid;
    $params['secret']= $secret;
    $params['js_code']= $code;
    $params['grant_type']= 'authorization_code';
    // 微信API返回的session_key 和 openid
    $arr = Post($params,$url,  'POST');
    $arr = json_decode($arr,true);
    // 判断是否成功
    if(isset($arr['errcode']) && !empty($arr['errcode'])){
        return ['code'=>1,'msg'=>$arr['errmsg']];
    }else{
        return ['code'=>0,'msg'=>'获取成功','openid'=>$arr['openid']];
    }
}
function Post($curlPost, $url, $ssl = false)
{
    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_NOBODY, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);
    if (!$ssl) {
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    }
    $return_str = curl_exec($curl);
    return $return_str;
}

function curlget($url,$headers,$method,$host)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_FAILONERROR, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, true);
    if (1 == strpos("$".$host, "https://"))
    {
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    }
    $return_str = curl_exec($curl);
    return $return_str;
}
/*获取access_token,不能用于获取用户信息的token*/
function getAccessToken()
{
    $app_id = Config::get('site.appid');
    $app_secret = Config::get('site.app_secret');
    $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$app_id."&secret=".$app_secret."";

    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_HEADER,0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
    exit();
}
function sendSmss($tel = '', $content = '')
{
    $url = 'http://api.jucrm.cn:8001/sms/api/sendMessageOne';
    $user_name = '15064343350';
    $password = 'fb6VtH5MAHbh';
    $timestamp = time() * 1000;

    $data = [
        'userName' => $user_name,
        'messageList' => [[
            'phone' => $tel,
            'content' => '【山东龙喆货运】'.$content
        ]],
        'timestamp' => $timestamp,
        'sign' => md5($user_name . $timestamp.md5($password)),
    ];
    $re = httpUtils($url,json_encode($data, JSON_UNESCAPED_UNICODE),'POST',['Content-type:application/json']);
    $re_data = json_decode($re,true);
    if($re_data['code'] =='0'){
        return ['code' => 0, 'data' => '', 'msg' => '发送成功'];
    }
    return ['code' => -1, 'data' => '', 'msg' => '发送失败'];
}

function httpUtils($url, $data = '', $method = 'GET', $header = ''){
    try {

        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        if ($method == 'POST') {
            curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
            if ($data != '') {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
            }
        }
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        $tmpInfo = curl_exec($curl); // 执行操作
        curl_close($curl); // 关闭CURL会话
        return $tmpInfo; // 返回数据
    } catch (Exception $e) {
        return $e->getMessage();
    }
}

//加密
function wx_sign($userid='',$ts='',$appkey=''){
    $sign = trim($userid.$ts.$appkey);
    return md5($sign);
}
//获得当前时间毫秒
function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return  (float)sprintf('%.0f', (floatval($usec) + floatval($sec)) * 1000);
}
//门店token
 
// 计算两点之间的距离（简化为直线距离，实际应用中可以使用更精确的算法）
function calculateDistance($lat1, $lng1, $lat2, $lng2) {
    $earthRadius = 6371; // 地球半径，单位公里
//    print_r($lat2);die;
    // 确保参数是有效的数值，并转换为浮点数
    $lat1 = floatval($lat1);
    $lng1 = floatval($lng1);
    $lat2 = floatval($lat2);
    $lng2 = floatval($lng2);
    
    // 验证参数是否有效（经纬度范围：纬度 -90 到 90，经度 -180 到 180）
    if ($lat1 < -90 || $lat1 > 90 || $lng1 < -180 || $lng1 > 180 ||
        $lat2 < -90 || $lat2 > 90 || $lng2 < -180 || $lng2 > 180) {
        return 0;
    }
    
    $lat1 = deg2rad($lat1);
    $lng1 = deg2rad($lng1);
    $lat2 = deg2rad($lat2);
    $lng2 = deg2rad($lng2);

    $dlat = $lat2 - $lat1;
    $dlng = $lng2 - $lng1;

    $a = sin($dlat/2) * sin($dlat/2) + cos($lat1) * cos($lat2) * sin($dlng/2) * sin($dlng/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));

    return $earthRadius * $c;
}

/**
 * 计算两点之间的驾车距离（使用百度地图API）
 * @param float $lat1 起点纬度
 * @param float $lng1 起点经度
 * @param float $lat2 终点纬度
 * @param float $lng2 终点经度
 * @return float 驾车距离（单位：公里），失败时返回直线距离
 */
/**
 * 驾车距离缓存键（内存 + 持久化共用）
 */
function drivingDistanceCacheKey($lat1, $lng1, $lat2, $lng2)
{
    return 'map:driving_distance:' . md5(implode(':', [
        round(floatval($lat1), 6),
        round(floatval($lng1), 6),
        round(floatval($lat2), 6),
        round(floatval($lng2), 6),
    ]));
}

/**
 * 读取驾车距离缓存，未命中返回 null
 */
function lookupDrivingDistanceCache($lat1, $lng1, $lat2, $lng2)
{
    static $memoryCache = [];
    $memKey = implode(':', [
        round(floatval($lat1), 6),
        round(floatval($lng1), 6),
        round(floatval($lat2), 6),
        round(floatval($lng2), 6),
    ]);
    if (isset($memoryCache[$memKey])) {
        return $memoryCache[$memKey];
    }
    $cached = \think\Cache::get(drivingDistanceCacheKey($lat1, $lng1, $lat2, $lng2));
    if ($cached !== false && $cached !== null) {
        $memoryCache[$memKey] = $cached;
        return $cached;
    }
    return null;
}

/**
 * 写入驾车距离缓存
 */
function storeDrivingDistanceCache($lat1, $lng1, $lat2, $lng2, $distance)
{
    static $memoryCache = [];
    $memKey = implode(':', [
        round(floatval($lat1), 6),
        round(floatval($lng1), 6),
        round(floatval($lat2), 6),
        round(floatval($lng2), 6),
    ]);
    $memoryCache[$memKey] = $distance;
    \think\Cache::set(drivingDistanceCacheKey($lat1, $lng1, $lat2, $lng2), $distance, 604800);
}

/**
 * 获取百度地图 Web 服务 Key 列表
 */
function getBaiduMapAkList()
{
    $configAk = \think\Config::get('site.BaiduKey');
    if (is_string($configAk)) {
        $decoded = json_decode($configAk, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return array_filter($decoded);
        }
        return trim($configAk) === '' ? [] : ['default' => trim($configAk)];
    }
    return is_array($configAk) ? array_filter($configAk) : [];
}

/**
 * 并行请求多段驾车距离（仅对缓存未命中的路段发起外部请求）
 * @param array $segments [[lat1, lng1, lat2, lng2], ...]
 * @return array 与输入顺序对应的距离（公里）
 */
function calculateDrivingDistancesParallel(array $segments)
{
    $results = [];
    $pending = [];

    foreach ($segments as $index => $segment) {
        if (!is_array($segment) || count($segment) < 4) {
            $results[$index] = 0;
            continue; 
        }
        $lat1 = floatval($segment[0]);
        $lng1 = floatval($segment[1]);
        $lat2 = floatval($segment[2]);
        $lng2 = floatval($segment[3]);

        if ($lat1 < -90 || $lat1 > 90 || $lng1 < -180 || $lng1 > 180 ||
            $lat2 < -90 || $lat2 > 90 || $lng2 < -180 || $lng2 > 180) {
            $results[$index] = 0;
            continue;
        }

        $cached = lookupDrivingDistanceCache($lat1, $lng1, $lat2, $lng2);
        if ($cached !== null) {
            $results[$index] = $cached;
            continue;
        }
        $pending[$index] = [$lat1, $lng1, $lat2, $lng2];
    }

    if (empty($pending)) {
        ksort($results);
        return array_values($results);
    }

    $keyList = getBaiduMapAkList();
    if (empty($keyList)) {
        foreach ($pending as $index => $coords) {
            $results[$index] = calculateDistance($coords[0], $coords[1], $coords[2], $coords[3]);
        }
        ksort($results);
        return array_values($results);
    }
    static $currentKeyIndex = 0;
    $keyNames = array_keys($keyList);
    $key = $keyList[$keyNames[$currentKeyIndex % count($keyNames)]];

    if (function_exists('curl_multi_init')) {
        $mh = curl_multi_init();
        $handles = [];
        foreach ($pending as $index => $coords) {
            list($lat1, $lng1, $lat2, $lng2) = $coords;
            $url = 'https://api.map.baidu.com/directionlite/v1/driving?origin=' . $lat1 . ',' . $lng1
                . '&destination=' . $lat2 . ',' . $lng2 . '&ak=' . urlencode($key) . '&tactics=3';
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_multi_add_handle($mh, $ch);
            $handles[$index] = $ch;
        }

        $running = null;
        do {
            curl_multi_exec($mh, $running);
            if ($running > 0) {
                curl_multi_select($mh, 0.5);
            }
        } while ($running > 0);

        foreach ($handles as $index => $ch) {
            list($lat1, $lng1, $lat2, $lng2) = $pending[$index];
            $response = curl_multi_getcontent($ch);
            $distance = null;
            if ($response !== false && $response !== '') {
                $data = json_decode($response, true);
                if (isset($data['status']) && (int)$data['status'] === 0
                    && isset($data['result']['routes'][0]['distance'])) {
                    $distance = round($data['result']['routes'][0]['distance'] / 1000, 2);
                }
            }
            if ($distance === null) {
                $distance = calculateDistance($lat1, $lng1, $lat2, $lng2);
            }
            storeDrivingDistanceCache($lat1, $lng1, $lat2, $lng2, $distance);
            $results[$index] = $distance;
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);
    } else {
        foreach ($pending as $index => $coords) {
            $results[$index] = calculateDrivingDistance($coords[0], $coords[1], $coords[2], $coords[3]);
        }
    }

    ksort($results);
    return array_values($results);
}

function calculateDrivingDistance($lat1, $lng1, $lat2, $lng2) {
    // 确保参数是有效的数值，并转换为浮点数
    $lat1 = floatval($lat1);
    $lng1 = floatval($lng1);
    $lat2 = floatval($lat2);
    $lng2 = floatval($lng2);
    
    // 验证参数是否有效（经纬度范围：纬度 -90 到 90，经度 -180 到 180）
    if ($lat1 < -90 || $lat1 > 90 || $lng1 < -180 || $lng1 > 180 ||
        $lat2 < -90 || $lat2 > 90 || $lng2 < -180 || $lng2 > 180) {
        return 0;
    }

    $cached = lookupDrivingDistanceCache($lat1, $lng1, $lat2, $lng2);
    if ($cached !== null) {
        return $cached;
    }
    
    $keyList = getBaiduMapAkList();
    if (empty($keyList)) {
        \think\Log::error('未配置百度地图 AK（site.BaiduKey），使用直线距离');
        return calculateDistance($lat1, $lng1, $lat2, $lng2);
    }

    static $currentKeyIndex = 0;
    $keyNames = array_keys($keyList);
    $origin = "{$lat1},{$lng1}";
    $destination = "{$lat2},{$lng2}";
    $maxRetries = count($keyList);

    for ($i = 0; $i < $maxRetries; $i++) {
        $currentKey = $keyNames[$currentKeyIndex % $maxRetries];
        $key = $keyList[$currentKey];
        $url = "https://api.map.baidu.com/directionlite/v1/driving?origin={$origin}&destination={$destination}&ak=" . urlencode($key) . "&tactics=3";

        try {
            $response = false;
            if (function_exists('curl_init')) {
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                $response = curl_exec($ch);
                curl_close($ch);
            } else {
                $response = @file_get_contents($url);
            }
            
            if ($response === false) {
                \think\Log::error('百度地图 API 网络请求失败，Key：' . $currentKey);
                $currentKeyIndex++;
                continue;
            }

            $data = json_decode($response, true);
            if (isset($data['status']) && (int)$data['status'] === 0 && isset($data['result']['routes'][0]['distance'])) {
                $distance = round($data['result']['routes'][0]['distance'] / 1000, 2);
                storeDrivingDistanceCache($lat1, $lng1, $lat2, $lng2, $distance);
                return $distance;
            }

            \think\Log::warning('高德驾车路线调用失败，Key：' . $currentKey . '，返回：' . json_encode($data, JSON_UNESCAPED_UNICODE));
            $currentKeyIndex++;
        } catch (\Exception $e) {
            \think\Log::error('获取高德驾车距离异常，Key：' . $currentKey . '，错误：' . $e->getMessage());
            $currentKeyIndex++;
        }
    }

    \think\Log::error('所有高德地图 Key 都尝试失败，使用直线距离作为备用');
    $fallback = calculateDistance($lat1, $lng1, $lat2, $lng2);
    storeDrivingDistanceCache($lat1, $lng1, $lat2, $lng2, $fallback);
    return $fallback;
}



/**
 * 计算物流价格（重量单位：吨）
 * @param float $weight 重量（吨）
 * @param float $volume 体积（立方米）
 */
function calculatePrice($weight, $volume, $pricePerTon, $pricePerCube,$reflux,$bulky) {
    $hasWeight = is_numeric($weight) && (float)$weight > 0;
    $hasVolume = is_numeric($volume) && (float)$volume > 0;

    if (!$hasWeight && !$hasVolume) {
        return [
            'type' => '未知',
            'density' => 0,
            'price' => 0,
            'calculation_method' => '未知'
        ];
    }

    $weight = $hasWeight ? (float)$weight : 0;
    $volume = $hasVolume ? (float)$volume : 0;
    $pricePerTon = is_numeric($pricePerTon) ? (float)$pricePerTon : 0;
    $pricePerCube = is_numeric($pricePerCube) ? (float)$pricePerCube : 0;
    $reflux = is_numeric($reflux) ? (float)$reflux : 0;
    $bulky = is_numeric($bulky) ? (float)$bulky : 0;
    // 重量单位已为吨，直接参与计算
    $density = $hasVolume ? ($weight / $volume) : 0;
////    print_r($pricePerCube);die;/**/
//    if ($density > 1/2) {
//        // 重货：按吨位计价
//        $price = $weight * $pricePerTon;
//        $type = '重货';
//    } elseif ($density > 1/4 && $density < 1/2) {
//        // 重抛货：按方计价
//        $price = $volume * $reflux;
//        $type = '重抛货';
//    } elseif ($density > 1/5 && $density < 1/4) {
//        // 轻抛货：按方计价
//        $price = $volume * $bulky;
//        $type = '轻抛货';
//    } else {
//        // 抛货：按方计价
//        $price = $volume * $pricePerCube;
//        $type = '抛货';
//    }
//    if ($heavyprice > $price){
//        $price = $heavyprice;
//    }
    if (empty($reflux)) {
        $reflux = 0;
    }
    if (empty($bulky)) {
        $bulky = 0;
    }
    $pricePerTon =  $weight * $pricePerTon;
    $reflux =  $weight * $reflux;
    $bulky = $volume * $bulky;
    $pricePerCube =  $volume * $pricePerCube;
    $price = max($pricePerTon, $reflux, $bulky, $pricePerCube);
    if ($hasWeight && !$hasVolume) {
        $calculationMethod = '按吨位计价';
    } elseif (!$hasWeight && $hasVolume) {
        $calculationMethod = '按方计价';
    } else {
        $calculationMethod = $density > 1 / 2 ? '按吨位计价' : '按方计价';
    }
    return [
//        'type' => $type,
        'density' => round($density, 4),
        'price' => $price,
        'calculation_method' => $calculationMethod
    ];
}
function extractProvince($loadingAddress) {
        if (strpos($loadingAddress, '省') !== false) {
            $part = explode('省', $loadingAddress);
            $province = trim($part[0]);
            $city = trim(explode('市', $part[1])[0]);
        } else {
            // 直辖市直接按"市"分割
            $part = explode('市', $loadingAddress);
            $province = trim($part[0]);
            $city = trim($part[0]); // 直辖市省和市是同一个
        }
        $loading = "$province $city";

    return $loading;
}
function generateRandomInviteCode($length = 8) {
    // 排除容易混淆的字符：0, O, I, 1, L
    $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    $max = strlen($chars) - 1;
    $code = '';

    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, $max)];
    }

    return $code;
}

/****************二维码合成海报**********************/
function hc_hb($qrcode,$bg='/static/yqhb.png',$invite_code){
//合成带logo的二维码图片跟 模板图片
    $path_1 = Env::get('ROOT_PATH')."public".$bg; //背景图
    $path_2 = Env::get('ROOT_PATH')."public".$qrcode; //带logo的二维码图
    $dst = imagecreatefromstring(file_get_contents($path_1));
    $src = imagecreatefromstring(file_get_contents($path_2));
    list($src_w, $src_h) = getimagesize($path_2);
    imagecopymerge($dst, $src, 232, 413, 0, 0, $src_w, $src_h, 100);
    list($dst_w, $dst_h, $dst_type) = getimagesize($path_1);
//$out_png= Env::get('ROOT_PATH')."public".'/static/tyz_qr_code/bg_'.$invite_code.date('YmdHis'.time()).'.png';
    $out_png= './static/tyz_qr_code/bg_'.$invite_code.date('YmdHis'.time()).'.png';
    imagepng($dst,$out_png);
    return $out_png;
}

/**
 * 生成运单内容（紧凑版）
 */
function generateWaybillContent($item) {
    $ESC = "\x1B";
    $GS = "\x1D";
    $LF = "\x0A";

    // 获取数据
    $waybillNo = isset($item['orderid']) ? strval($item['orderid']) : '';
    $recvName = isset($item['unload_address']['name']) ? $item['unload_address']['name'] : '';
    $recvPhone = isset($item['unload_address']['mobile']) ? $item['unload_address']['mobile'] :
        (isset($item['unload_address']['tel']) ? $item['unload_address']['tel'] : '');
    $recvAddr = isset($item['unload_address']['address']) ? $item['unload_address']['address'] : '';
    $sendAddr = isset($item['loading_address']['address']) ? $item['loading_address']['address'] : '';
    $goodsName = isset($item['goods_name']) ? $item['goods_name'] : '货物';
    $weight = isset($item['weight']) ? $item['weight'] : '0';
    $volume = isset($item['volume']) ? $item['volume'] : '0';
    $pieces = isset($item['num']) ? $item['num'] : '1';
    $freight = isset($item['pay_price']) ? $item['pay_price'] : '0.00';
    $createTime = isset($item['createtime']) ? $item['createtime'] : '';

    $content = '';

    // 初始化
    $content .= $ESC . '@';
    $content .= $ESC . '2';          // 默认行距

    // 设置紧凑模式
    $content .= $ESC . '3' . "\x10"; // 设置行距为16/180英寸

    // 运单号（居中，稍大）
    $content .= $ESC . 'a' . "\x01"; // 居中
    $content .= $ESC . '!' . "\x08"; // 加粗
    $content .= '运单号：' . $waybillNo . $LF;

    // 恢复左对齐，正常字体
    $content .= $ESC . 'a' . "\x00";
    $content .= $ESC . '!' . "\x00";

    // 条码（CODE128，如果支持）
    if (strlen($waybillNo) > 0) {
        $content .= $ESC . 'a' . "\x01"; // 居中
        $content .= $GS . 'h' . "\x28";  // 条码高度40
        $content .= $GS . 'w' . "\x02";  // 条码宽度2
        $content .= $GS . 'H' . "\x02";  // 条码文字在下
        $len = chr(strlen($waybillNo));
        $content .= $GS . 'k' . "\x49" . $len . $waybillNo;
        $content .= $LF;
        $content .= $ESC . 'a' . "\x00"; // 恢复左对齐
    }

    // 分隔线
    $content .= '--------------------' . $LF;

    // 收件信息（精简）
    $content .= '【收件人】' . truncateText($recvName, 8) . $LF;
    $content .= '电  话：' . truncateText($recvPhone, 11) . $LF;
    $content .= '地  址：' . truncateText($recvAddr, 20) . $LF;

    $content .= '--------------------' . $LF;

    // 寄件信息
    $content .= '【寄件人】' . truncateText($sendAddr, 20) . $LF;

    $content .= '--------------------' . $LF;

    // 货物信息
    $content .= '货  品：' . truncateText($goodsName, 12) . $LF;
    $content .= '件  数：' . $pieces . '件';
    $content .= '  重  量：' . $weight . '吨' . $LF;

    // 费用信息
    $content .= '运  费：￥' . $freight . $LF;

    // 时间信息
    $content .= '时  间：' . truncateText($createTime, 16) . $LF;

    // 状态信息
    $content .= '状  态：' . getStatusText($item['pay_status']) . $LF;

    // 底部提示
    $content .= '--------------------' . $LF;
    $content .= '请核对后签收' . $LF;

    // 走纸和切纸（根据打印机类型调整）
    $content .= $ESC . 'd' . "\x02";  // 走纸2行
    $content .= $GS . 'V' . "\x00";  // 不切纸（撕纸型）

    return $content;
}

/**
 * 生成签收单内容（完整版）
 */
function generateReceiptContent($item) {
    $ESC = "\x1B";
    $GS = "\x1D";
    $LF = "\x0A";

    // 获取数据
    $waybillNo = isset($item['orderid']) ? strval($item['orderid']) : '';
    $recvName = isset($item['unload_address']['name']) ? $item['unload_address']['name'] : '';
    $recvPhone = isset($item['unload_address']['mobile']) ? $item['unload_address']['mobile'] :
        (isset($item['unload_address']['tel']) ? $item['unload_address']['tel'] : '');
    $recvAddr = isset($item['unload_address']['address']) ? $item['unload_address']['address'] : '';
    $recvCompany = isset($item['unload_address']['company']) ? $item['unload_address']['company'] : '';
    $sendName = isset($item['loading_address']['name']) ? $item['loading_address']['name'] : '';
    $sendPhone = isset($item['loading_address']['mobile']) ? $item['loading_address']['mobile'] :
        (isset($item['loading_address']['tel']) ? $item['loading_address']['tel'] : '');
    $sendAddr = isset($item['loading_address']['address']) ? $item['loading_address']['address'] : '';
    $sendCompany = isset($item['loading_address']['company']) ? $item['loading_address']['company'] : '';

    $goodsName = isset($item['goods_name']) ? $item['goods_name'] : '货物';
    $packType = isset($item['pack_type']) ? $item['pack_type'] : '标准';
    $weight = isset($item['weight']) ? $item['weight'] : '0';
    $volume = isset($item['volume']) ? $item['volume'] : '0';
    $pieces = isset($item['num']) ? $item['num'] : '1';
    $freight = isset($item['pay_price']) ? $item['pay_price'] : '0.00';
    $declareValue = isset($item['declare_value']) ? $item['declare_value'] : '0.00';
    $serviceFee = isset($item['service_fee']) ? $item['service_fee'] : '0.00';
    $codAmount = isset($item['cod_amount']) ? $item['cod_amount'] : '0.00';
    $createTime = isset($item['createtime']) ? $item['createtime'] : '';
    $deliveryType = isset($item['delivery_type']) ? $item['delivery_type'] : '标准';
    $payType = isset($item['pay_type_name']) ? $item['pay_type_name'] : '现付';

    $content = '';

    // 初始化
    $content .= $ESC . '@';
    $content .= $GS . 'L' . "\x18" . "\x00";
    $content .= $ESC . '3' . "\x08";

    // 标题
    $content .= '签 收 单' . $LF;

    // 运单号
    $content .= $ESC . 'a' . "\x01";
    $content .= $ESC . '!' . "\x08";
    $content .= '单号：' . $waybillNo . $LF;
    $content .= $ESC . 'a' . "\x00";
    $content .= $ESC . '!' . "\x00";

    // 收方信息
    $content .= $ESC . '!' . "\x08"; // 加粗
    $content .= '收方信息：' . $LF;
    $content .= $ESC . '!' . "\x00";
    $content .= '姓  名：' . truncateText($recvName, 8);
    $content .= '电  话：' . truncateText($recvPhone, 11) . $LF;
    if ($recvCompany) {
        $content .= '公  司：' . truncateText($recvCompany, 18) . $LF;
    }
    $content .= '地  址：' . truncateText($recvAddr, 20) . $LF;

    // 寄方信息
    $content .= $ESC . '!' . "\x08";
    $content .= '寄方信息：' . $LF;
    $content .= $ESC . '!' . "\x00";
    $content .= '姓  名：' . truncateText($sendName, 8);
    $content .= '电  话：' . truncateText($sendPhone, 11) . $LF;
    if ($sendCompany) {
        $content .= '公  司：' . truncateText($sendCompany, 18) . $LF;
    }
    $content .= '地  址：' . truncateText($sendAddr, 20) . $LF;

    // 货物信息
    $content .= $ESC . '!' . "\x08";
    $content .= '货物信息1：' . $LF;
    $content .= $ESC . '!' . "\x00";
    // 第一行：品名 + 包装
    $content .= '品 名：' . truncateText($goodsName, 10);
    $content .= '  包 装：' . truncateText($packType, 6) . $LF;
    // 第二行：件数 + 付款方式
    $content .= '件 数：' . $pieces . '件';
    $content .= '    付 款：' . truncateText($payType, 6) . $LF;
    // 第三行：重量 + 体积
    $content .= '重 量：' . $weight . '吨';
    $content .= '    体 积：' . $volume . 'm³' . $LF;

    // 费用信息
    $content .= $ESC . '!' . "\x08";
    $content .= '费用信息：' . $LF;
    $content .= $ESC . '!' . "\x00";
    $content .= '运  费：￥' . $freight . $LF;
    $content .= '保价费：￥' . $declareValue . $LF;
    $content .= '服务费：￥' . $serviceFee . $LF;
    $content .= '下单时间：' . truncateText($createTime, 16) . $LF;
    if ($codAmount !== '0.00') {
        $content .= '代收款：￥' . $codAmount . $LF;
    }

    // 走纸略微减少行数，避免多张纸
    $content .= $ESC . 'd' . "\x01";
    $content .= $GS . 'V' . "\x00";

    return $content;
}

/**
 * 截断文本
 */
function truncateText($text, $maxLength) {
    if (empty($text)) {
        return '';
    }
    $text = mb_substr($text, 0, $maxLength, 'UTF-8');
    return mb_strlen($text, 'UTF-8') > $maxLength ? $text . '...' : $text;
}

/**
 * 获取状态文本
 */
function getStatusText($status) {
    $statusMap = [
        1 => '待付款',
        2 => '服务中',
        3 => '已完成',
        4 => '已取消',
        6 => '测算中',
        7 => '已出价'
    ];
    return isset($statusMap[$status]) ? $statusMap[$status] : '未知状态';
}
function extractProvinceCityEnhanced($address) {
    $result = [
        'province' => '',
        'city' => '',      // 地级市
        'area' => '',      // 区/县/县级市
        'full' => ''
    ];

    // 步骤1：提取省级单位（省/自治区/直辖市）
    $provincePattern = '/(.*?(省|自治区|直辖市|特别行政区))/u';
    if (preg_match($provincePattern, $address, $provinceMatch)) {
        $result['province'] = $provinceMatch[1];
        $address = substr($address, strlen($result['province'])); // 移除省级部分
    }

    // 步骤2：提取地级市（第一个出现的“市”，但要排除县级市干扰）
    // 策略：找到第一个“市”，但后面如果还有“市”，而且中间有“区/县/市”，那么第一个是地级市
    if (preg_match('/^.*?(市)/u', $address, $cityMatch)) {
        $firstCityPos = mb_strpos($address, '市');
        $remainingAfterFirstCity = mb_substr($address, $firstCityPos + 1);

        // 判断第一个“市”后面是否还有“市”且中间没有明显的区级关键词
        if (mb_strpos($remainingAfterFirstCity, '市') !== false) {
            // 有第二个市，说明第一个是地级市，第二个是县级市
            // 找到第二个市的位置
            $secondCityPos = mb_strpos($address, '市', $firstCityPos + 1);
            $result['city'] = mb_substr($address, 0, $secondCityPos + 1);

            // 剩下的部分继续解析区
            $address = mb_substr($address, $secondCityPos + 1);
        } else {
            // 只有一个市
            $result['city'] = mb_substr($address, 0, $firstCityPos + 1);
            $address = mb_substr($address, $firstCityPos + 1);
        }
    }

    // 步骤3：提取区/县/县级市
    $areaKeywords = ['区', '县', '市']; // 注意：这里的“市”针对县级市
    foreach ($areaKeywords as $keyword) {
        if (mb_strpos($address, $keyword) !== false) {
            $areaPos = mb_strpos($address, $keyword);
            $result['area'] = mb_substr($address, 0, $areaPos + 1);
            break;
        }
    }

    // 组合完整地址
    $result['full'] = $result['province'] . $result['city'] . $result['area'];

    return  $result['full'];
}

/**
 * 从地址中提取省市（省+地级市，不含区县），如：山东省济南市、北京市、内蒙古自治区呼和浩特市
 *
 * @param string $address
 * @return string
 */
function extractProvinceCity($address)
{
    if ($address === null || $address === '') {
        return '';
    }
    $address = trim((string)$address);

    // 步骤1：提取省级单位（省/自治区/直辖市/特别行政区）
    $province = '';
    if (preg_match('/(.*?(省|自治区|直辖市|特别行政区))/u', $address, $provinceMatch)) {
        $province = $provinceMatch[1];
        $address = substr($address, strlen($province));
    }

    // 步骤2：提取地级市（排除县级市干扰：若第一个“市”后还有“市”且中间有区/县/市，则第一个是地级市）
    $city = '';
    if (mb_strpos($address, '市') !== false) {
        $firstCityPos = mb_strpos($address, '市');
        $remainingAfterFirstCity = mb_substr($address, $firstCityPos + 1);
        if (mb_strpos($remainingAfterFirstCity, '市') !== false) {
            $secondCityPos = mb_strpos($address, '市', $firstCityPos + 1);
            $city = mb_substr($address, 0, $secondCityPos + 1);
        } else {
            $city = mb_substr($address, 0, $firstCityPos + 1);
        }
    }

    return $province . $city;
}

/**
 * 从地址中提取省份（不带「省」字，如：山东、北京）
 * 支持「滨州市-博兴县-...-山东省...」等省份不在开头的格式
 *
 * @param string $address
 * @return string
 */
function extractAddressProvince($address)
{
    if ($address === null || $address === '') {
        return '';
    }
    $address = trim((string)$address);

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
            || strpos($address, $province . '自治区') === 0) {
            return $province;
        }
    }

    $sorted = $provinces;
    usort($sorted, function ($a, $b) {
        return mb_strlen($b, 'UTF-8') <=> mb_strlen($a, 'UTF-8');
    });
    foreach ($sorted as $province) {
        if (mb_strpos($address, $province . '省') !== false
            || mb_strpos($address, $province . '自治区') !== false) {
            return $province;
        }
    }

    return '';
}

function hasRegion($addr) {
    // 省级行政区（包括省、自治区、直辖市）
    $provinces = [
        '省', '自治区',
        '北京', '天津', '上海', '重庆',  // 直辖市
        '内蒙古', '广西', '西藏', '宁夏', '新疆'  // 自治区简称/全称
    ];
    $hasProvince = false;
    foreach ($provinces as $province) {
        if (strpos($addr, $province) !== false) {
            $hasProvince = true;
            break;
        }
    }
    // 市级/县级行政区（多种情况没有“市”字）
    // 县：直辖县、省辖县；旗/自治旗：内蒙古；区：直辖市常写“北京朝阳区”；林区/特区：省直辖
    $hasCity = strpos($addr, '市') !== false ||
        strpos($addr, '县') !== false ||
        strpos($addr, '区') !== false ||
        strpos($addr, '旗') !== false ||
        strpos($addr, '自治旗') !== false ||
        strpos($addr, '林区') !== false ||
        strpos($addr, '特区') !== false ||
        strpos($addr, '自治州') !== false ||
        strpos($addr, '地区') !== false ||
        strpos($addr, '盟') !== false;

    return $hasProvince && $hasCity;
}

/**
 * 发起http post请求(REST API), 并获取REST请求的结果
 * @param string $url
 * @param string $param
 * @return - http response body if succeeds, else false.
 */
function request_post($url = '', $param = '')
{
    if (empty($url) || empty($param)) {
        return false;
    }

    $postUrl = $url;
    $curlPost = $param;
    // 初始化curl
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $postUrl);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    // 要求结果为字符串且输出到屏幕上
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    // post提交方式
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);
    // 运行curl
    $data = curl_exec($curl);
    curl_close($curl);

    return $data;
}

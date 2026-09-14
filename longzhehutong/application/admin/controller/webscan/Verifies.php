<?php

namespace app\admin\controller\webscan;

use app\common\controller\Backend;
use think\Db;

/**
 *文件校验
 */
class Verifies extends Backend
{
    //文件校验结果缓存名
    private $result_cache_name = 'InconformityCacheArr';
    //本地需要校验的文件缓存名
    private $loca_cache_name = "WebscanVerifiesCache";

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\webscan\WebscanVerifies();
    }

    /**
     * 校验首页
     * @return string|\think\response\Json
     * @throws \think\Exception
     */
    public function index()
    {

        if ($this->request->isAjax()) {
            /*$search=$this->request->param('search');
            $limit=$this->request->param('limit','10','intval');
            $offset=$this->request->param('offset','0','intval');
            $sort=$this->request->param('sort');
            $order=$this->request->param('order');*/
            $files_arr = cache($this->result_cache_name);
            $count = $files_arr?count($files_arr):0;
            //分页处理
            //$files_arr=array_slice($files_arr,$offset,$limit);
            $result = array("total" => $count, "rows" => $files_arr);
            return json($result);
        } else {
            $verifi_data = $this->model->where('method', 'local')->count();

            if (!$verifi_data) cache($this->loca_cache_name, null);//删除缓存

            return $this->view->fetch('', ['verifi_data' => $verifi_data]);
        }
    }

    /**
     * 查看文件
     */
    public function show($filename)
    {
        if (file_exists(ROOT_PATH . $filename)) {
            show_source(ROOT_PATH . $filename);
        } else {
            $this->error("文件不存在");
        }
    }

    /**
     *加入信任
     * @param $filename
     */
    public function trust($index)
    {
        $files_arr = cache($this->result_cache_name);
        $temp = $files_arr[$index];

        if ($temp) {
            unset($files_arr[$index]);
            $info = $this->model->where('method', $temp['method'])->where('filename', $temp['filename'])->find();
            if ($info) {
                $info->md5 = $temp['md5'];
                $info->save();
            } else {
                $this->model->insert($temp);
            }
        }

        cache($this->result_cache_name, array_values($files_arr));
        $this->success();
    }

    /**
     *批量信任
     * @param $filename
     */
    public function trusts($ids)
    {
        if (!$ids) $this->error("请选择");

        $ids = explode(',', $ids);
        $files_arr = cache($this->result_cache_name);

        //TODO 待优化
        foreach ($ids as $index) {
            $temp = $files_arr[$index];
            if ($temp) {
                unset($files_arr[$index]);
                $info = $this->model->where('method', $temp['method'])->where('filename', $temp['filename'])->find();
                if ($info) {
                    $info->md5 = $temp['md5'];
                    $info->save();
                } else {
                    $this->model->insert($temp);
                }
            }
        }

        cache($this->result_cache_name, array_values($files_arr));
        $this->success();
    }

    /**
     * 初始化数据
     */
    public function build()
    {
        $verifi_data = $this->model->where('method', 'local')->count();

        if (!$verifi_data) {
            cache($this->loca_cache_name, null);//删除缓存
        } else {
            $this->error("已经初始化");
        }

        $files_arr = $this->readFiles();

        if ($files_arr) {
            $this->model->insertAll($files_arr);
            $this->success("初始化数据成功");
        } else {
            $this->error("初始化失败");
        }

    }

    /**
     *遍历检查
     * @param $dir
     * @return array
     */
    public function bianli()
    {
        $limit = $this->request->param('limit', '1000', 'intval');
        $offset = $this->request->param('offset', '0', 'intval');

        if ($offset == 0) {
            cache($this->result_cache_name, null);//清空之前的结果
        }

        $files_arr = $this->readFiles();
        $count = count($files_arr);
        //分页处理
        $files_arr = array_slice($files_arr, $offset, $limit);

        if ($files_arr) {
            $md5_arr = array_column($files_arr, 'md5');
            $subQuery = Db::name('webscan_verifies')->field('md5')->where('md5', 'in', $md5_arr)->buildSql();
            $sql = "SELECT md5,method,filename,mktime FROM (";
            $tempsql = "";

            foreach ($files_arr as $row) {
                //TODO待优化
                $tempsql .= $tempsql ? " UNION SELECT '" . $row['md5'] . "' as md5, '" . $row['method'] . "' as method ,'" . addslashes($row['filename']) . "' as filename ,'" . $row['mktime'] . "' as mktime " : "  SELECT '" . $row['md5'] . "' as md5, '" . $row['method'] . "' as method ,'" . addslashes($row['filename']) . "' as filename ,'" . $row['mktime'] . "' as mktime ";
            }

            $sql = $sql . $tempsql . ")a WHERE md5 NOT IN(" . $subQuery . ")";
            $result = Db::query($sql);
            $InconformityCacheArr = cache($this->result_cache_name);
            $InconformityCacheArr = $InconformityCacheArr ?: [];

            if ($result) {
                $InconformityCacheArr = $InconformityCacheArr ? array_merge($InconformityCacheArr, $result) : $result;
                cache($this->result_cache_name, $InconformityCacheArr);
            }

            $this->success("正在检测中，已检测" . ($offset + $limit) . "个文件,有" . count($InconformityCacheArr) . "个文件不一致", url('webscan.verifies/bianli', ['offset' => ($offset + $limit)]));

        }

        $this->success("检测完成{$count}", url('webscan.verifies/index'));
    }

    /**
     * 读取本地的文件数据
     * @return mixed
     */
    protected function readFiles()
    {
        $files_arr = cache($this->loca_cache_name);

        if (!$files_arr) {
            $config = get_addon_config('webscan');
            $suffix = explode('|', $config['files_suffix']);
            $dir = $config['ignore_dir'];//忽略的文件夹
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(ROOT_PATH), \RecursiveIteratorIterator::LEAVES_ONLY
            );
            $i = 0;

            foreach ($files as $name => $file) {
                $temp_str = str_replace(ROOT_PATH, '', $name);
                $search = ["/", "?", "=", ".", "&", '|', '\\'];
                $replace = ["\/", "\?", "\=", "\.", "\&", '|^', '\/'];
                $white_url = str_replace($search, $replace, $dir);
                if (preg_match("/^" . $white_url . "/is", $temp_str)) {
                    continue;
                }

                if (!is_dir($temp_str)) {
                    if (in_array($file->getExtension(), $suffix)) {
                        $files_arr[$i]['md5'] = md5_file($name);
                        $files_arr[$i]['filename'] = $temp_str;
                        $files_arr[$i]['method'] = 'local';
                        $files_arr[$i]['mktime'] = $file->getMTime();
                    }
                    $i++;
                }
            }
            unset($files);

            if ($files_arr) {
                cache($this->loca_cache_name, $files_arr, 3600);//缓存一个小时
            }

        }

        return $files_arr;
    }


}

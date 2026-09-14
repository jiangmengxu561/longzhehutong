<?php

namespace app\admin\controller\editpage;

use app\common\controller\Backend;
use addons\editpage\library\EditpageService;

class Editpage extends Backend
{
    // 类型与语言映射
    private $typeMap = [
        'controller' => ['lang' => 'php'],
        'model'      => ['lang' => 'php'],
        'view'       => ['lang' => 'html'],
        'js'         => ['lang' => 'javascript'],
        'validate'   => ['lang' => 'php'],
        'lang'       => ['lang' => 'php'],
    ];

    public function _initialize()
    {
        parent::_initialize();

        // 权限与调试模式检查
        if (!$this->auth->isSuperAdmin() || !config('app_debug')) {
            $this->error(!$this->auth->isSuperAdmin()
                ? '只有超级管理员可以使用'
                : '只在调试模式下可用'
            );
        }
    }

    public function index()
    {
        $type = $this->request->request('type', '');
        $data = [
            'filepath' => '',
            'language_type' => 'php',
            'code' => '抱歉，没有找到相关文件！',
            'size' => 0,
            'fileatime' => '',
            'filectime' => '',
        ];

        // 根据类型获取文件路径和语言类型
        if (isset($this->typeMap[$type])) {
            $method = 'get' . ucfirst($type) . 'FilePath';
            $data['filepath'] = EditpageService::$method();
            $data['language_type'] = $this->typeMap[$type]['lang'];
        }

        // 读取文件信息
        $editpageConfig = get_addon_config('editpage');
        if (file_exists($data['filepath'])) {
            $data['code'] = file_get_contents($data['filepath']);
            $data['size'] = format_bytes(filesize($data['filepath']));
            $data['fileatime'] = date('Y-m-d H:i:s', fileatime($data['filepath']));
            $data['filectime'] = date('Y-m-d H:i:s', filectime($data['filepath']));
        } else {
            $editpageConfig['setreadonly'] = 1; // 文件不存在时设为只读
        }

        // 获取备份信息
        $data['backup_stats'] = \addons\editpage\library\EditpageService::getBackupStats();

        $data['editpage_config'] = $editpageConfig;
        $this->assignconfig($data);
        $this->view->assign($data);
        return $this->view->fetch();
    }

    /**
     * 保存文件并备份
     */
    public function savefile()
    {
        if ($this->request->isPost()) {
            $file = $this->request->request('file');
            $type = $this->request->request('type', '');
            $filepath = '';
            // 根据类型获取文件路径检查是否合法
            if (isset($this->typeMap[$type])) {
                $method = 'get' . ucfirst($type) . 'FilePath';
                $filepath = EditpageService::$method();
            }
            if ($filepath !== $file) {
                $this->error('非法请求');
            }
            $content = $this->request->request('content');

            $result = EditpageService::saveFileAndBackup($filepath, $content);
            $result === true ? $this->success() : $this->error($result ?: '文件写入失败');
        }
        $this->error('非法请求');
    }

    /**
     * @notes 清理备份
     * @author Xing <464401240@qq.com>
     */
    public function clearBackups()
    {
        if ($this->request->isPost()) {
            $result = \addons\editpage\library\EditpageService::cleanupAllBackups(true);
            $result === true ? $this->success() : $this->error($result ?: '清理备份文件失败');
        }
        $this->error('非法请求');
    }
}
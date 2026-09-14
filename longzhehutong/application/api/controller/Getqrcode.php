<?php

namespace app\api\controller;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Logo\Logo;
use think\Db;
use think\Response;
use app\common\controller\Api;

class Getqrcode extends Api
{

    protected $noNeedLogin = ['qrcode','getPhoneNumber','login','poster'];

    /**
     * 生成二维码并保存到本地
     */
    public function qrcode1()
    {
        $user = $this->auth->id;
        $invitation = Db::name('user')->where('id', $user)->value('invitation');
        $text = $this->request->param('text', 'https://lzwl.longzhehutong.cn/invitation?invitation=' . $invitation);
        // 二维码固定为 290 * 290，可通过 size 参数自定义，默认 290
        $size = $this->request->param('size', 500);

        // 自定义文件名
        $filename = $this->request->param('filename', 'qrcode_' . $user . '_' . time());

        // 中间 Logo 图片路径（请根据实际存放位置修改）
        // 例如：public/static/logo.png 或 public/uploads/logo.png
        $logoPath = ROOT_PATH . 'public' . DS . 'uploads' . DS . 'logo.jpg';

        try {
            $qrCode = new QrCode($text);
            $qrCode->setSize($size);
            $qrCode->setMargin(10);
            $qrCode->setForegroundColor(new Color(0, 0, 0));
            $qrCode->setBackgroundColor(new Color(255, 255, 255));

            $writer = new PngWriter();

            // 如果存在 Logo，则写入带 Logo 的二维码
            if (is_file($logoPath)) {
                // 控制 Logo 宽度，避免遮挡太多二维码内容
                $logo = Logo::create($logoPath)
                    ->setResizeToWidth((int)($size * 0.25)); // Logo 宽度约为二维码的 25%

                $result = $writer->write($qrCode, $logo);
            } else {
                // 未找到 Logo 文件时生成普通二维码
                $result = $writer->write($qrCode);
            }

            // 保存路径 - 修改为您需要的目录
            $savePath = 'uploads/qrcode/';

            // 确保目录存在
            if (!is_dir($savePath)) {
                mkdir($savePath, 0755, true);
            }

            // 完整的文件路径
            $filePath = $savePath . $filename . '.png';

            // 保存文件
            $result->saveToFile($filePath);

            // 返回文件信息
            return json([
                'code' => 200,
                'message' => '二维码生成成功',
                'data' => [
                    'path' => $filePath,
                    'url' => $this->request->domain() . '/' . $filePath,
                    'filename' => $filename . '.png'
                ]
            ]);

        } catch (\Exception $e) {
            return json(['error' => $e->getMessage()]);
        }
    }

    /**
     * 生成带背景的海报（将二维码叠加到指定背景图上）
     */
    public function qrcode()
    {
        $userId = $this->auth->id;
        $invitation = $this->request->param('invitation');
        if (!$invitation && $userId) {
            $invitation = Db::name('user')->where('id', $userId)->value('invitation');
        }

        $text = $this->request->param(
            'text',
            $invitation ? 'https://lzwl.longzhehutong.cn/invitation?invitation=' . $invitation : 'https://lzwl.longzhehutong.cn/'
        );
        // 海报中的二维码固定为 290 * 290，可通过 size 参数自定义，默认 290
        $size = (int)$this->request->param('size', 1000);

        // 默认背景为 public/uploads/beijing.png，可通过 background 参数覆盖
        $backgroundRelative = $this->request->param('background', 'uploads/beijing.png');
        $backgroundPath = ROOT_PATH . 'public' . DS . str_replace(['/', '\\'], DS, $backgroundRelative);

        if (!is_file($backgroundPath)) {
            return json([
                'code' => 404,
                'message' => '背景图不存在',
                'data' => ['path' => $backgroundRelative]
            ]);
        }

        $filename = $this->request->param('filename', 'poster_' . ($userId ?: 'guest') . '_' . time());

        // 输出目录（绝对路径）与相对路径
        $outputDir = ROOT_PATH . 'public' . DS . 'uploads' . DS . 'qrcode' . DS;
        $relativeDir = 'uploads/qrcode/';

        if (!is_dir($outputDir) && !mkdir($outputDir, 0755, true)) {
            return json(['code' => 500, 'message' => '二维码目录创建失败']);
        }

        // 中间 Logo 图片路径
        $logoPath = ROOT_PATH . 'public' . DS . 'uploads' . DS . 'logo.jpg';

        // 临时二维码文件
        $tempQrPath = $outputDir . 'tmp_' . $filename . '.png';

        try {
            // 生成二维码（与 qrcode 方法一致）
            $qrCode = new QrCode($text);
            $qrCode->setSize($size);
            $qrCode->setMargin(10);
            $qrCode->setForegroundColor(new Color(0, 0, 0));
            $qrCode->setBackgroundColor(new Color(255, 255, 255));

            $writer = new PngWriter();

            if (is_file($logoPath)) {
                $logo = Logo::create($logoPath)->setResizeToWidth((int)($size * 0.1));
                $qrResult = $writer->write($qrCode, $logo);
            } else {
                $qrResult = $writer->write($qrCode);
            }

            $qrResult->saveToFile($tempQrPath);

            // 合成海报
            $backgroundImg = imagecreatefromstring(file_get_contents($backgroundPath));
            $qrImg = imagecreatefromstring(file_get_contents($tempQrPath));

            $bgWidth = imagesx($backgroundImg);
            $bgHeight = imagesy($backgroundImg);
            $qrWidth = imagesx($qrImg);
            $qrHeight = imagesy($qrImg);

            // 将二维码强制缩放到 size（默认 290）大小，保证海报上的二维码为 290 * 290
            $targetSize = $size;

            $qrResized = imagecreatetruecolor($targetSize, $targetSize);
            imagealphablending($qrResized, false);
            imagesavealpha($qrResized, true);
            imagecopyresampled($qrResized, $qrImg, 0, 0, 0, 0, $targetSize, $targetSize, $qrWidth, $qrHeight);

            // 默认放在底部居中，可通过 offsetX / offsetY 调整，并确保落在画布内
            $offsetX = (int)$this->request->param('offsetX', ($bgWidth - $targetSize) / 2);
            $offsetY = (int)$this->request->param('offsetY', $bgHeight - $targetSize - ($bgHeight * 0.07));
            $offsetX = max(0, min($bgWidth - $targetSize, $offsetX));
            $offsetY = max(0, min($bgHeight - $targetSize, $offsetY));

            imagecopy($backgroundImg, $qrResized, $offsetX, $offsetY, 0, 0, $targetSize, $targetSize);

            $posterPath = $outputDir . $filename . '.png';
            $posterRelativePath = $relativeDir . $filename . '.png';

            imagepng($backgroundImg, $posterPath);

            imagedestroy($backgroundImg);
            imagedestroy($qrImg);
            imagedestroy($qrResized);
            @unlink($tempQrPath);

            return json([
                'code' => 200,
                'message' => '海报生成成功',
                'data' => [
                    'path' => $posterRelativePath,
                    'url' => $this->request->domain() . '/' . $posterRelativePath,
                    'background' => $backgroundRelative
                ]
            ]);
        } catch (\Exception $e) {
            if (is_file($tempQrPath)) {
                @unlink($tempQrPath);
            }
            return json(['code' => 500, 'message' => $e->getMessage()]);
        }
    }

    /**
     * 生成Base64格式的二维码
     */
    public function base64()
    {
        $text = $this->request->param('text', '默认内容');

        $qrCode = new QrCode($text);
        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        $base64 = 'data:image/png;base64,' . base64_encode($result->getString());

        return json([
            'base64' => $base64,
            'mime_type' => $result->getMimeType()
        ]);
    }
}
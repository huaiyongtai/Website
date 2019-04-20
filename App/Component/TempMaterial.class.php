<?php

/**
 * 微信临时素材
 */

namespace DevSxe\Application\Component\Wx;

use DevSxe\Application\Component\Wx\CurlCompoent;

class TempMaterial
{

    //const filePath = 'd://';
    const filePath = '/home/www/gwapi.domain.com/Img/';
    const qrCodepostfix = '_qrCode.jpg';
    const combineImg = '_img.jpg';
    const headImg = '_headImg.jpg';
    const defaultImg = '1.jpg';
    const uploadImgUrl = 'https://api.weixin.qq.com/cgi-bin/media/upload?access_token=';
    const backPng = 'backGround.png';
    // curl组件
    private static $curlC = null;

    public function __construct()
    {
        if (empty($curlC)) {
            $this->curlC = \DevSxe\Lib\G('DevSxe\Application\Component\Wx\CurlCompoent');
        }
    }

    // 下载二维码
    // $qrCodeUrl 二维码链接
    // $openid 微信openid 作为其二维码文件名 
    // $status 下载状态 1.需要下载 2.不需要下载
    public function downLoad($qrCodeUrl, $openid, $status)
    {
        $filePath = $this->_combineString(self::filePath, $openid, self::qrCodepostfix);

        if ($status == 2) {

            return $filePath;
        }
        $curlC = \DevSxe\Lib\G('DevSxe\Application\Component\Wx\CurlCompoent');
        $res = $curlC->download($qrCodeUrl);
        file_put_contents($filePath, $res['data'], FILE_APPEND);
    }


    // 下载用户头像
    public function downHeadImg($imgUrl, $openid, $status)
    {
        $filePath = $this->_combineString(self::filePath, $openid, self::headImg);

        if (is_file($filePath) && filesize($filePath) != 0 && $status == 2) {
            return $filePath;
        }

        $curlC = \DevSxe\Lib\G('DevSxe\Application\Component\Wx\CurlCompoent');
        $res = $curlC->download($imgUrl);
        file_put_contents($filePath, $res['data'], FILE_APPEND);
    }

    /**
     * 合成分享海报
     */
    public function combinePosterImg($qrCodeUrl, $openid, $status)
    {
        // 合成后图片路径
        $tarImgPath = $this->_combineString(self::filePath, $openid, self::combineImg);
        if (is_file($tarImgPath) && filesize($tarImgPath) != 0 && $status == 2) {
            return $tarImgPath;
        }
        // 背景图
        $backImgPath = '/home/www/gwapi.domain.com/backImg/fiftyBackGround.png';
        // 二维码
        $qrCodePath = $this->_combineString(self::filePath, $openid, self::qrCodepostfix);
        // 载入背景图 二维码 文字
        $backImg = imagecreatefrompng($backImgPath);
        $qrCodeImg = imagecreatefromjpeg($qrCodePath);
        // 待合成图片
        $tarImg = imageCreatetruecolor(imagesx($backImg), imagesy($backImg));
        // 用户缩小二维码
        list($qWidth, $qHeight) = getimagesize($qrCodePath);
        $sQrCodeImg = imagecreatetruecolor(150, 150);
        imagecopyresized($sQrCodeImg, $qrCodeImg, 0, 0, 0, 0, 150, 150, $qWidth - 10, $qHeight - 10);
        $color = imagecolorallocate($tarImg, 255, 255, 255);
        imagefill($tarImg, 0, 0, $color);
        imageColorTransparent($tarImg, $color);
        imagecopyresampled($tarImg, $backImg, 0, 0, 0, 0, imagesx($backImg), imagesy($backImg), imagesx($backImg), imagesy($backImg));
        // 将缩小后用户二维码合并到图像上
        imagecopymerge($tarImg, $sQrCodeImg, 0, imagesy($backImg) - 200, 0, 0, imagesx($sQrCodeImg), imagesy($sQrCodeImg), 100);
        imagejpeg($tarImg, $tarImgPath);
        return $tarImgPath;
    }

    // 合成图片

    public function combineImg($qrCodeUrl, $openid, $status)
    {

        // 合成后图片路径
        $tarImgPath = $this->_combineString(self::filePath, $openid, self::combineImg);

        if (is_file($tarImgPath) && filesize($tarImgPath) != 0 && $status == 2) {
            return $tarImgPath;
        }
        // 背景图
        // 暂写为绝对路径
        $backImgPath = '/home/www/gwapi.domain.com/backImg/backGround.jpg';
        // 二维码
        $qrCodePath = $this->_combineString(self::filePath, $openid, self::qrCodepostfix);
        // 用户头像
        #$headImgPath = $this->_combineString(self::filePath,$openid,self::headImg);

        // 载入背景图 二维码 头像
        $backImg = imagecreatefromjpeg($backImgPath);
        $qrCodeImg = imagecreatefromjpeg($qrCodePath);
        #$headImg = imagecreatefromjpeg($headImgPath);
        // 待合成图片
        $tarImg = imageCreatetruecolor(imagesx($backImg), imagesy($backImg));
        // 用户缩小头像
        #list($headWidth,$headHeight) = getimagesize($headImgPath);
        #$sHeadImg = imagecreatetruecolor(95,95);
        #imagecopyresized($sHeadImg, $headImg,0, 0,0, 0,95, 95, $headWidth, $headHeight);
        // 用户缩小二维码
        list($qWidth, $qHeight) = getimagesize($qrCodePath);
        $sQrCodeImg = imagecreatetruecolor(200, 200);
        imagecopyresized($sQrCodeImg, $qrCodeImg, 0, 0, 0, 0, 200, 200, $qWidth, $qHeight);
        $color = imagecolorallocate($tarImg, 255, 255, 255);
        imagefill($tarImg, 0, 0, $color);
        imageColorTransparent($tarImg, $color);
        imagecopyresampled($tarImg, $backImg, 0, 0, 0, 0, imagesx($backImg), imagesy($backImg), imagesx($backImg), imagesy($backImg));
        // 将缩小后用户二维码合并到图像上
        imagecopymerge($tarImg, $sQrCodeImg, 468, 1040, 0, 0, imagesx($sQrCodeImg), imagesy($sQrCodeImg), 100);
        // 头像
        #imagecopymerge($tarImg,$sHeadImg, 65,690,0,0,imagesx($sHeadImg),imagesy($sHeadImg), 100);
        imagejpeg($tarImg, $tarImgPath);
        return $tarImgPath;
    }


    // 上传素材
    public function upLoad($token, $data)
    {

        $url = self::uploadImgUrl . $token . '&type=image';
        $upRes = $this->curlC->post($url, $data);
        $upRes = json_decode($upRes['data'], true);
        return $upRes;
    }


    // 拼接字符串
    private function _combineString()
    {
        // 获取传入的所有参数
        $args = func_get_args();
        $str = '';
        foreach ($args as $value) {
            $str .= $value;
        }
        return $str;
    }
}

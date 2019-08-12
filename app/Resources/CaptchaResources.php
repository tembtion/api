<?php

namespace App\Resources;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class CaptchaResources
{
    const CAPTCHA_EXPIRES = 60;

    /**
     * 获取验证字符串
     *
     * @param int $length
     * @return bool|string
     */
    public static function getVerifyStr($length = 51)
    {
        $token = Str::random($length);
        $captchaTokensKey = CachekeyResources::getKey('captcha_tokens', $token);
        $code = self::createCode();
        if (!Redis::setEx($captchaTokensKey, self::CAPTCHA_EXPIRES, $code)) {
            return false;
        }

        return $token;
    }

    /**
     * 创建code
     *
     * @param int $size
     * @return string
     */
    public static function createCode($size = 4)
    {
        $contentArray = array_merge(range(0, 9), range('a', 'z '), range('A', 'Z'));

        $code = '';
        for ($i = 0; $i < $size; $i++) {
            $code .= $contentArray[array_rand($contentArray)];
        }

        return $code;
    }

    /**
     * 判断验证码是否正确
     *
     * @param $token
     * @param $inputCode
     * @return bool
     */
    public static function check($verifystr, $inputCode)
    {
        $captchaKey = CachekeyResources::getKey('captcha_tokens', $verifystr);
        $code = Redis::get($captchaKey);
        if (!empty($code) && strtolower($inputCode) === strtolower($code) && Redis::delete($captchaKey)) {
            return true;
        }

        return false;
    }

    /**
     * 获取code值
     *
     * @param $verifystr
     * @return bool
     */
    public static function getCode($verifystr)
    {
        $captchaKey = CachekeyResources::getKey('captcha_tokens', $verifystr);
        if (!Redis::exists($captchaKey) || empty(Redis::get($captchaKey))) {
            return false;
        }

        return Redis::get($captchaKey);
    }

    /**
     * 删除code
     *
     * @param $verifystr
     * @return bool
     */
    public static function deleteCode($verifystr)
    {
        $captchaKey = CachekeyResources::getKey('captcha_tokens', $verifystr);
        if (!Redis::del($captchaKey)) {
            return false;
        }

        return Redis::get($captchaKey);
    }

    /**
     * 创建验证码图片
     *
     * @param $code
     */
    public static function createImage($code)
    {
        $imageWidth = 100;
        $imageHeight = 40;

        $image = imagecreatetruecolor($imageWidth, $imageHeight);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        $color = self::getFontColor();
        $x = mt_rand(5, 10);
        foreach (str_split($code) as $key => $text) {
            // 字体文件
            $fontFile = self::getFontPath();
            // 偏移量
            $angle = 0;
            // 字体大小
            $fontSize = 25;
            // 字体颜色
            $fontcolor = imagecolorallocate($image, $color[0], $color[1], $color[2]);
            $fontBox = imagettfbbox($fontSize, $angle, $fontFile, $text);

            $fontWidth = max($fontBox[2], $fontBox[4]) - min($fontBox[0], $fontBox[6]);
            $fontHeight = max($fontBox[1], $fontBox[3]) - min($fontBox[5], $fontBox[7]);
            // 显示的坐标
            $y = mt_rand($fontHeight, max($fontHeight, $imageHeight));
            // 填充内容到画布中
            imagettftext($image, $fontSize, $angle, $x, $y, $fontcolor, $fontFile, $text);
            $x += $fontWidth;
        }
        imagepng($image);
        imagedestroy($image);
    }

    /**
     * 获取字体颜色
     *
     * @return mixed
     */
    public static function getFontColor()
    {
        $colorArray = [
            [0, 0, 0],
            [200, 80, 80],
            [57, 107, 255],
            [46, 130, 255],
            [52, 168, 83],
        ];

        return $colorArray[array_rand($colorArray)];
    }

    /**
     * 获取字体文件路径
     *
     * @return string
     */
    public static function getFontPath()
    {
        $fontArray = [
            'font/font20465/DatBox.ttf',
            'font/font20637/Carnevalee Freakshow.ttf',
            'font/font20658/HBM-Infektion-Distorted__donationware_.ttf',
            'font/font20706/fibre-font.otf',
            'font/font20796/sonsation.ttf',
            'font/font20822/orange juice.ttf',
            'font/font202901/Childs-Play.ttf',
            'font/font20729/appopaint-Regular.otf',
        ];

        return public_path($fontArray[array_rand($fontArray)]);
    }
}

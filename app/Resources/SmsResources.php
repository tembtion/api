<?php

namespace App\Resources;

use Illuminate\Support\Facades\Redis;

class SmsResources
{
    const CAPTCHA_EXPIRES = 60;

    /**
     * 发送验证码
     *
     * @param $mobile
     * @return bool
     */
    public static function sendVerifyCode($mobile)
    {
        $code = rand(100000, 999999);
        $cacheKey = CachekeyResources::getKey('mobile_verify_code', $mobile);
        if (!Redis::setEx($cacheKey, self::CAPTCHA_EXPIRES, $code)) {
            return false;
        }
        // 发送信息

        return true;
    }

    /**
     * 校验验证码
     *
     * @param $mobile
     * @param $inputCode
     * @return bool
     */
    public static function checkVerifyCode($mobile, $inputCode)
    {
        $captchaKey = CachekeyResources::getKey('mobile_verify_code', $mobile);
        $code = Redis::get($captchaKey);
        if (!empty($code) && $inputCode === $code && Redis::delete($captchaKey)) {
            return true;
        }

        return false;
    }
}

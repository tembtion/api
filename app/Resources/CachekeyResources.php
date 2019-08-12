<?php

namespace App\Resources;

class CachekeyResources
{
    public static $keyMap = [
        'access_tokens' => 1,
        'refresh_tokens' => 2,
        'captcha_tokens' => 3,
        'email_reset_code' => 4,
        'reset_token' => 5,
        'user_tokens' => 6,
        'reset_timer' => 7,
        'mobile_verify_code' => 8,
    ];

    /**
     * get cache key
     *
     * @param $name
     * @param mixed ...$args
     * @return string
     */
    public static function getKey($name, ...$args)
    {
        $data = [];
        // prefix
        $data[] = config('app.name', 'api');
        // name
        $data[] = self::$keyMap[$name] ?? 0;
        // args
        if (!empty($args)) {
            $data[] = md5(serialize($args));
        }

        return implode(':', $data);
    }
}

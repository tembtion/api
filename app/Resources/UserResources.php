<?php

namespace App\Resources;

use App\Mail\ResetPassword;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class UserResources
{
    const ATTEMPTS = 5;
    const ATTEMPTS_EXPIRE = 86400;

    /**
     * 根据用户名获取用户信息
     *
     * @param $username
     * @return mixed
     */
    public static function findByUserName($username)
    {
        return User::where('name', $username)->first();
    }

    /**
     * 根据用户ID获取用户信息
     *
     * @param $username
     * @return mixed
     */
    public static function findById($id)
    {
        return User::where('id', $id)->first();
    }

    /**
     * 是否锁定重置
     *
     * @param $email
     * @return bool
     */
    public static function isLockReset($email)
    {
        $resetCodeKey = CachekeyResources::getKey('reset_timer', $email);
        $timer = Redis::get($resetCodeKey) ?: 0;

        return $timer > self::ATTEMPTS;
    }

    /**
     * 增加尝试次数
     *
     * @param $email
     * @return bool
     */
    public static function incResetTimer($email)
    {
        $resetCodeKey = CachekeyResources::getKey('reset_timer', $email);

        return Redis::exists($resetCodeKey) ? Redis::incr($resetCodeKey) : Redis::setEx($resetCodeKey, self::ATTEMPTS_EXPIRE, 1);
    }

    /**
     * 发送重置密码邮件
     *
     * @param $email
     * @throws \Exception
     */
    public static function sendResetEmail($email)
    {
        // 通过邮箱获取用户信息
        $user = User::where('email', $email)->first();
        if (empty($user)) {
            throw new \Exception(__('message.email_not_exist'));
        }
        $code = Str::random(6);
        // 写入缓存
        $resetCodeKey = CachekeyResources::getKey('email_reset_code', $email);
        $result = Redis::multi()
            ->hMSet($resetCodeKey, [
                'code' => $code,
                'uid' => $user->id,
            ])
            ->expire($resetCodeKey, 3600)
            ->exec();
        if (!$result) {
            throw new \Exception(__('message.cache_save_error'));
        }
        Mail::to($email)->send(new ResetPassword([
            'code' => $code,
        ]));
    }

    public static function checkCode($email, $code)
    {
        $resetKey = CachekeyResources::getKey('email_reset_code', $email);
        if (!Redis::exists($resetKey) || Redis::get($resetKey) !== $code) {
            return false;
        }

        return true;
    }

    public static function getResetToken($email, $code)
    {
        $emailCodeKey = CachekeyResources::getKey('email_reset_code', $email);
        $resetInfo = Redis::hGetAll($emailCodeKey);
        if (!isset($resetInfo['code']) || $resetInfo['code'] !== $code) {
            throw new \Exception(__('message.code_not_exist'));
        }
        Redis::delete($emailCodeKey);

        $uid = $resetInfo['uid'];
        // 创建reset_token
        $token = md5(Str::random(6));
        $resetKey = CachekeyResources::getKey('reset_token', $token);
        $cacheResult = Redis::setEx($resetKey, 3600, $uid);
        if (!$cacheResult) {
            throw new \Exception(__('message.token_create_fail'));
        }

        return $token;
    }

    /**
     * 创建重置token
     *
     * @param $uid
     * @return string
     * @throws \Exception
     */
    public static function createResetToken($uid)
    {

    }


    /**
     * 根据重置token获取用户ID
     *
     * @param $resetToken
     * @return mixed
     */
    public static function getUidByResetToken($resetToken)
    {
        return Redis::get(CachekeyResources::getKey('reset_token', $resetToken));
    }

    /**
     * 更新用户密码
     *
     * @param $uid
     * @param $password
     * @return bool
     */
    public static function updatePassword($uid, $password)
    {
        $userResult = User::where('id', $uid)->update([
            'password' => bcrypt($password)
        ]);
        if (!$userResult) {
            return false;
        }

        return true;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Resources\CachekeyResources;
use App\Resources\CaptchaResources;
use App\Resources\OauthResources;
use App\Resources\UserResources;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;

class ForgotPasswordController extends BaseController
{
    /**
     * 发送验证码
     */
    public function sendResetCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'codestring' => 'required',
            'verifycode' => 'required',
        ], [
            'email.required' => __('message.email_required'),
            'email.email' => __('message.email_format_error'),
            'codestring.required' => __('message.codestring_required'),
            'verifycode.required' => __('message.verifycode_required'),
        ]);
        if ($validator->fails()) {
            return $this->error(2000, $validator->errors()->first());
        }
        $email = $request->get('email');
        // 校验验证码
        if (!CaptchaResources::check($request->get('codestring'), $request->get('verifycode'))) {
            return $this->error(901);
        }
        // 判断是否锁定
        $isLockReset = UserResources::isLockReset($email);
        if ($isLockReset) {
            return $this->error(903);
        }
        // 发送邮件
        try {
            UserResources::sendResetEmail($email);
        } catch (\Exception $e) {
            return $this->error(2000, $e->getMessage());
        }

        return $this->success();
    }

    /**
     * 校验验证码
     */
    public function checkCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'code' => 'required',
        ], [
            'email.required' => __('message.email_required'),
            'email.email' => __('message.email_format_error'),
            'code.required' => __('message.code_required'),
        ]);
        if ($validator->fails()) {
            return $this->error(2000, $validator->errors()->first());
        }
        $email = $request->get('email');
        $code = $request->get('code');
        // 增加尝试次数
        if (!UserResources::incResetTimer($email)) {
            return $this->error(9000);
        }
        // 判断是否锁定
        $isLockReset = UserResources::isLockReset($email);
        if ($isLockReset) {
            return $this->error(903);
        }

        try {
            $resetToken = UserResources::getResetToken($email, $code);
        } catch (\Exception $e) {
            return $this->error(2000, $e->getMessage());
        }

        return $this->success(['reset_token' => $resetToken]);
    }

    /**
     * 重置密码
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reset_token' => 'required',
            'password' => 'required|min:6|max:15|confirmed',
        ], [
            'reset_token.required' => __('message.reset_token_required'),
            'password.required' => __('message.password_required'),
            'password.min' => __('message.password_min'),
            'password.max' => __('message.password_max'),
            'password.confirmed' => __('message.password_confirmed'),
        ]);
        if ($validator->fails()) {
            return $this->error(2000, $validator->errors()->first());
        }
        // 获取重置密码用户ID
        $uid = UserResources::getUidByResetToken($request->get('reset_token'));
        if (!$uid) {
            return $this->error(2000, __('message.code_not_exist'));
        }
        // 保存密码
        $userResult = UserResources::updatePassword($uid, $request->get('password'));
        if (!$userResult) {
            return $this->error(2000, __('message.password_update_failed'));
        }
        // 重置登录
        OauthResources::clearToken($uid);

        return $this->success();
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Resources\OauthResources;
use App\Resources\SmsResources;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends BaseController
{
    /**
     * 注册
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|unique:users',
            'mobile' => 'required|unique:users',
            'code' => 'required',
            'password' => 'required|digits_between:6,16',
        ], [
            'username.required' => __('message.register.username_required'),
            'username.unique' => __('message.register.username_unique'),
            'mobile.required' => __('message.register.mobile_required'),
            'mobile.unique' => __('message.register.mobile_unique'),
            'code.required' => __('message.register.code_required'),
            'password.required' => __('message.register.password_required'),
            'password.digits_between' => __('message.register.password_digits_between'),
        ]);
        if ($validator->fails()) {
            return $this->error(2000, $validator->errors()->first());
        }
        $username = $request->get('username');
        $mobile = $request->get('mobile');
        $code = $request->get('code');
        $password = $request->get('password');
        // 校验验证码
        if (!SmsResources::checkVerifyCode($mobile, $code)) {
            return $this->error(901);
        }
        // 保存用户信息
        try {
            $user = User::create([
                'username' => $username,
                'mobile' => $mobile,
                'password' => bcrypt($password),
            ]);
            // 创建token
            $response = OauthResources::buildToken($user->id);
        } catch (\Exception $e) {
            return $this->error(10000);
        }

        return $this->success($response);
    }
}

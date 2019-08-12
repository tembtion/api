<?php

namespace App\Http\Controllers;

use App\Resources\CaptchaResources;
use App\Resources\UserResources;
use Illuminate\Http\Request;
use App\Resources\OauthResources;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class OauthController extends BaseController
{
    /**
     * 获取token
     */
    public function getToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required',
            'codestring' => 'required',
            'verifycode' => 'required',
        ], [
            'username.required' => __('message.oauth.username_required'),
            'password.required' => __('message.oauth.password_required'),
            'codestring.required' => __('message.oauth.codestring_required'),
            'verifycode.required' => __('message.oauth.verifycode_required'),
        ]);
        if ($validator->fails()) {
            return $this->error(2000, $validator->errors()->first());
        }
        $username = $request->get('username');
        $password = $request->get('password');
        $codestring = $request->get('codestring');
        $verifycode = $request->get('verifycode');
        // 验证码验证
        if (!CaptchaResources::check($codestring, $verifycode)) {
            return $this->error(901);
        }

        try {
            // 查询用户信息
            $user = UserResources::findByUserName($username);
            if (empty($user) || !Hash::check($password, $user->password)) {
                throw new \Exception(__('message.oauth.username_password_error'));
            }
            // 创建token
            $response = OauthResources::buildToken($user->id);
        } catch (\Exception $e) {
            return $this->error(2000, $e->getMessage());
        }

        return $this->success($response);
    }

    /**
     * 刷新token
     */
    public function refreshToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required',
        ], [
            'refresh_token.required' => __('message.oauth.refresh_token_required'),
        ]);
        if ($validator->fails()) {
            return $this->error(2000, $validator->errors()->first());
        }
        $refreshToken = $request->get('refresh_token');

        try {
            $result = OauthResources::getAccessTokenByRefresh($refreshToken);
        } catch (\Exception $e) {
            return $this->error(2000, $e->getMessage());
        }

        return $this->success($result);
    }
}

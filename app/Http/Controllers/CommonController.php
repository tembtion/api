<?php

namespace App\Http\Controllers;

use App\Resources\CaptchaResources;
use App\Resources\SmsResources;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CommonController extends BaseController
{
    /**
     * 获取token
     */
    public function getVerifyStr()
    {
        $verifyStr = CaptchaResources::getVerifyStr();
        if ($verifyStr === false) {
            return $this->error(902);
        }

        return $this->success($verifyStr);
    }

    /**
     * 获取验证码图片
     */
    public function getVerifyImage(Request $request)
    {
        $verifystr = $request->get('verifystr');
        if (empty($verifystr) || !($code = CaptchaResources::getCode($verifystr))) {
            return $this->error(900);
        }

        ob_clean();
        ob_start();
        CaptchaResources::createImage($code);

        $content = ob_get_clean();
        return response($content, 200, ['Content-Type' => 'image/png']);
    }

    /**
     * 获取token
     */
    public function sendMobileCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'required',
            'codestring' => 'required',
            'verifycode' => 'required',
        ], [
            'mobile_number.required' => __('message.mobile_required'),
            'codestring.required' => __('message.codestring_required'),
            'verifycode.required' => __('message.verifycode_required'),
        ]);
        if ($validator->fails()) {
            return $this->error(2000, $validator->errors()->first());
        }
        // 验证码验证
        if (!CaptchaResources::check($request->get('codestring'), $request->get('verifycode'))) {
            return $this->error(901);
        }
        // 发送手机验证码
        if (!SmsResources::sendVerifyCode($request->get('mobile'))) {
            return $this->error(20000);
        }

        return $this->success();
    }
}

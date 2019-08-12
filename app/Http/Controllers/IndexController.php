<?php

namespace App\Http\Controllers;

use App\Mail\OrderShipped;
use App\Mail\ResetPassword;
use App\Resources\CachekeyResources;
use App\Resources\CaptchaResources;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class IndexController extends BaseController
{
    /**
     * 首页
     */
    public function index(Request $request)
    {
        $res = [
            'aa' => 1,
            'bb' => $request->user(),
        ];

        return $this->success($res);
    }
}

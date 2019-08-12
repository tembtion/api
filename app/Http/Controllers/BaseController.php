<?php

namespace App\Http\Controllers;

use App\Resources\ResponseResources;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class BaseController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * 返回数据
     *
     * @param array $data
     * @return \Illuminate\Http\JsonResponse
     */
    public function success($data = [])
    {
        return ResponseResources::success($data);
    }

    /**
     * 返回错误
     *
     * @param $code
     * @param string $msg
     * @return \Illuminate\Http\JsonResponse
     */
    public function error($code, $msg = '')
    {
        return ResponseResources::error($code, $msg);
    }
}

<?php

namespace App\Resources;


class ResponseResources
{
    /**
     * 返回数据
     *
     * @param $data
     * @return \Illuminate\Http\JsonResponse
     */
    public static function success($data = [])
    {
        return response()->json([
            'code' => 200,
            'msg' => 'ok',
            'data' => $data,
        ]);
    }

    /**
     * 返回错误
     *
     * @param $code
     * @param string $msg
     * @return \Illuminate\Http\JsonResponse
     */
    public static function error($code, $msg = '')
    {
        return response()->json([
            'code' => $code,
            'msg' => $msg ?: (__("error.{$code}") ?: ''),
            'data' => [],
        ]);
    }
}

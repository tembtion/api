<?php
// 获取图形验证码code
Route::get('getverifystr', 'CommonController@getVerifyStr');
// 获取验证码图片
Route::get('getimage', 'CommonController@getVerifyImage');
// 发送手机验证码
Route::post('sendcode', 'CommonController@sendMobileCode');
// 注册
Route::post('register', 'UserController@register');
// 发送验证码
Route::post('password/sendverify', 'ForgotPasswordController@sendResetCode');
// 校验验证码
Route::post('password/checkcode', 'ForgotPasswordController@checkCode');
// 重置密码
Route::post('password/reset', 'ForgotPasswordController@resetPassword');
// 获取accesstoken
Route::post('oauth/token', 'OauthController@getToken');
// 刷新accesstoken
Route::post('oauth/refresh', 'OauthController@refreshToken');

Route::group(['prefix' => 'api', 'middleware' => 'auth:api'], function () {
    // 首页
    Route::post('index', 'IndexController@index');
});

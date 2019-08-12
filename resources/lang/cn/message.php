<?php

return [

    'token_create_fail' => 'token创建失败',
    'token_decrypt_error' => 'token解析错误',
    'refresh_token_error' => 'refresh_token不存在',
    'cache_save_error' => '缓存保存失败',
    'mail_send_error' => '邮件发送失败',




    'password_min' => '密码不能小于:min个字符',
    'password_max' => '密码不能大于:max个字符',
    'password_confirmed' => '确认密码错误',
    'password_update_failed' => '密码更新失败',



    'email_required' => '邮箱不能为空',
    'email_format_error' => '邮箱格式错误',
    'email_not_exist' => '邮箱不存在',
    'code_not_exist' => '验证码错误',


    'reset_token_required' => 'token不能为空',

    // oauth
    'oauth' => [
        'username_required' => '用户名不能为空',
        'password_required' => '密码不能为空',
        'codestring_required' => '验证码token不能为空',
        'verifycode_required' => '验证码不能为空',
        'refresh_token_required' => 'refresh_token不能为空',
        'username_password_error' => '用户名或密码错误',
    ],
    // 注册
    'register' => [
        'username_required' => '用户名不能为空',
        'username_unique' => '用户名已存在',
        'mobile_required' => '手机号不能为空',
        'mobile_unique' => '手机号已存在',
        'code_required' => '短信验证码不能为空',
        'password_required' => '密码不能为空',
        'password_digits_between' => '密码长度在:min-:max个之间',
    ],

];

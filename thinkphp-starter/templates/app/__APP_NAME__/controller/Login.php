<?php
declare (strict_types = 1);

namespace app\__APP_NAME__\controller;

use app\__APP_NAME__\service\UserService;
use app\common\JwtToken;
use think\Request;
use think\response\Json;

/** 登录接口：不挂 jwt.auth 中间件，校验账号密码后签发 Token。 */
class Login
{
    public function login(Request $request): Json
    {
        // 账号密码校验与账号状态检查均在 Service 层完成，失败由 error() 抛出 ApiException。
        $user = UserService::instance()->login($request->param());

        $token = JwtToken::createToken([
            'uid'      => $user['id'],
            'username' => $user['username'],
        ]);

        return success([
            'token' => $token,
            'user'  => $user,
        ], '登录成功');
    }
}

<?php
declare (strict_types = 1);

namespace app\common\middleware;

use app\common\JwtToken;
use think\Request;
use think\Response;

/** JWT 鉴权中间件，注册别名为 jwt.auth。 */
class JwtAuth
{
    public function handle(Request $request, \Closure $next): Response
    {
        $token = JwtToken::getTokenFromRequest($request);
        if (empty($token)) {
            return json(['code' => 401, 'message' => 'Token 缺失', 'data' => null], 401);
        }

        // 验证失败会抛出 ApiException，由全局异常处理器统一返回。
        $payload = JwtToken::verifyToken($token);

        // 注入请求对象，控制器可通过 $request->jwtPayload 获取。
        $request->jwtPayload = $payload;

        return $next($request);
    }
}

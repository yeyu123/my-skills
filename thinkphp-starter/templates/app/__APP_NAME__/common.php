<?php
declare (strict_types = 1);

use app\common\exception\ApiException;
use think\response\Json;

/** 正确返回：HTTP 状态码与业务 code 一致。 */
function success(mixed $data = null, string $message = '获取成功', int $code = 200): Json
{
    return json([
        'code'    => $code,
        'message' => $message,
        'data'    => $data,
    ], $code);
}

/** 错误返回：抛出 ApiException，由全局异常处理器渲染成统一结构。 */
function error(mixed $data = null, string|array $message = '获取失败', int $code = 400): never
{
    throw new ApiException($message, $data, $code);
}

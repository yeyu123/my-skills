<?php
declare (strict_types = 1);

namespace app;

use app\common\exception\ApiException;
use think\exception\Handle;
use think\Response;
use Throwable;

/**
 * 全局异常处理：将 ApiException 渲染成与 success() 一致的 JSON 结构。
 * 注意：目标项目通常已有 app/ExceptionHandle.php，请勿直接覆盖；按此模板修改 render() 即可。
 */
class ExceptionHandle extends Handle
{
    public function render($request, Throwable $e): Response
    {
        if ($e instanceof ApiException) {
            $code = $e->getCode();
            $status = ($code >= 100 && $code <= 599) ? $code : 500;

            return json([
                'code'    => $code,
                'message' => $e->getRawMessage(),
                'data'    => $e->getData(),
            ], $status);
        }

        return parent::render($request, $e);
    }
}

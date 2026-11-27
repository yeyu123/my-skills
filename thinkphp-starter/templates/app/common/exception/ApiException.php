<?php
declare (strict_types = 1);

namespace app\common\exception;

use RuntimeException;

/**
 * 业务异常。
 * 由全局 app\ExceptionHandle 渲染成与 success() 一致的 JSON 结构。
 */
class ApiException extends RuntimeException
{
    /** 原始 message，允许数组。 */
    protected string|array $rawMessage;

    /** 附加数据。 */
    protected mixed $data;

    public function __construct(string|array $message, mixed $data = null, int $code = 400)
    {
        $this->rawMessage = $message;
        $this->data       = $data;
        parent::__construct(
            is_array($message) ? json_encode($message, JSON_UNESCAPED_UNICODE) : $message,
            $code
        );
    }

    public function getRawMessage(): string|array
    {
        return $this->rawMessage;
    }

    public function getData(): mixed
    {
        return $this->data;
    }
}

<?php
declare (strict_types = 1);

// Redis 连接参数从环境变量读取，避免把密码等敏感信息写入代码库。
return [
    'host'     => env('REDIS_HOST', '127.0.0.1'),
    'port'     => (int) env('REDIS_PORT', 6379),
    'password' => (string) env('REDIS_PASSWORD', ''),
    'database' => (int) env('REDIS_DB', 0),
    'timeout'  => (float) env('REDIS_TIMEOUT', 2.0),
    'prefix'   => (string) env('REDIS_PREFIX', 'tp:'),
];

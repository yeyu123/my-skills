<?php
declare (strict_types = 1);

namespace app\__APP_NAME__\common;

use Predis\Client;
use Throwable;
use function error;

/**
 * __APP_NAME__ 应用复用的 Redis 封装。
 * 只提供通用键值操作，不承载具体业务逻辑。
 */
class RedisManager
{
    protected Client $client;

    /** 根据配置初始化 Predis 客户端，配置为空时读取 config('redis')。 */
    public function __construct(array $config = [])
    {
        $config = $config ?: (array) config('redis');
        $parameters = [
            'scheme'   => 'tcp',
            'host'     => (string) ($config['host'] ?? '127.0.0.1'),
            'port'     => (int) ($config['port'] ?? 6379),
            'database' => (int) ($config['database'] ?? 0),
            'timeout'  => (float) ($config['timeout'] ?? 2.0),
        ];

        $password = (string) ($config['password'] ?? '');
        if ($password !== '') {
            $parameters['password'] = $password;
        }

        try {
            $this->client = new Client($parameters, [
                'prefix' => (string) ($config['prefix'] ?? ''),
            ]);
        } catch (Throwable $e) {
            error(['message' => $e->getMessage()], 'Redis 客户端初始化失败', 500);
        }
    }

    /** 读取缓存值，键不存在时返回默认值。 */
    public function get(string $key, mixed $default = null): mixed
    {
        try {
            $value = $this->client->get($key);
        } catch (Throwable $e) {
            error(['message' => $e->getMessage()], 'Redis GET 失败', 500);
        }
        return $value === null ? $default : $value;
    }

    /** 写入缓存值，ttl 大于 0 时设置过期秒数。 */
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        try {
            if ($ttl !== null && $ttl > 0) {
                $this->client->setex($key, $ttl, (string) $value);
            } else {
                $this->client->set($key, (string) $value);
            }
        } catch (Throwable $e) {
            error(['message' => $e->getMessage()], 'Redis SET 失败', 500);
        }
        return true;
    }

    /** 原子写入 SET NX EX，用于防抖或简单互斥。 */
    public function setNxEx(string $key, mixed $value, int $ttl): bool
    {
        if ($ttl <= 0) {
            error(['ttl' => $ttl], 'Redis NX EX 过期时间必须大于 0', 400);
        }
        try {
            return (bool) $this->client->set($key, (string) $value, 'EX', $ttl, 'NX');
        } catch (Throwable $e) {
            error(['message' => $e->getMessage()], 'Redis SET NX EX 失败', 500);
        }
    }

    /** 删除一个或多个缓存键，返回成功删除的数量。 */
    public function delete(string ...$keys): int
    {
        if ($keys === []) {
            return 0;
        }
        try {
            return (int) $this->client->del(...$keys);
        } catch (Throwable $e) {
            error(['message' => $e->getMessage()], 'Redis DEL 失败', 500);
        }
    }

    /** 判断缓存键是否存在。 */
    public function exists(string $key): bool
    {
        try {
            return (bool) $this->client->exists($key);
        } catch (Throwable $e) {
            error(['message' => $e->getMessage()], 'Redis EXISTS 失败', 500);
        }
    }

    /** 为已有缓存键设置过期时间。 */
    public function expire(string $key, int $ttl): bool
    {
        try {
            return (bool) $this->client->expire($key, $ttl);
        } catch (Throwable $e) {
            error(['message' => $e->getMessage()], 'Redis EXPIRE 失败', 500);
        }
    }

    /** 获取底层 Predis 客户端，便于使用未封装的命令。 */
    public function getClient(): Client
    {
        return $this->client;
    }
}

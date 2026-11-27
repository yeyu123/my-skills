<?php
declare (strict_types = 1);

namespace app\common;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use function error;

/**
 * JWT 公共工具类。
 * 负责生成 Token、验证 Token、获取 Payload 以及从请求头提取 Token。
 */
class JwtToken
{
    /** 读取 config/app.php 的 jwt 配置节点。 */
    protected static function config(string $name): mixed
    {
        return config('app.jwt.' . $name);
    }

    /**
     * 生成 Token。
     * @param array   $data   自定义载荷，例如 uid、role，避免写入敏感信息
     * @param int|null $expire 自定义有效期（秒），不传则使用配置值
     */
    public static function createToken(array $data, ?int $expire = null): string
    {
        $now    = time();
        $expire = $expire ?? (int) self::config('expire');
        $payload = [
            'iss'  => 'tp8-api',
            'iat'  => $now,
            'nbf'  => $now,
            'exp'  => $now + $expire,
            'jti'  => uniqid('jwt_', true),
            'data' => $data,
        ];

        return JWT::encode($payload, self::config('key'), self::config('alg'));
    }

    /**
     * 验证 Token 并返回 Payload，失败时通过 error() 抛出 401。
     */
    public static function verifyToken(string $token): object
    {
        try {
            JWT::$leeway = 60;
            return JWT::decode($token, new Key(self::config('key'), self::config('alg')));
        } catch (ExpiredException $e) {
            error(null, 'Token 已过期', 401);
        } catch (SignatureInvalidException $e) {
            error(null, 'Token 签名无效', 401);
        } catch (\Throwable $e) {
            error(null, 'Token 无效', 401);
        }
    }

    /** 仅获取 Payload，验证失败返回 null，不抛异常。 */
    public static function getPayload(string $token): ?object
    {
        try {
            return self::verifyToken($token);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** 从 Authorization: Bearer <token> 请求头提取 Token。 */
    public static function getTokenFromRequest($request): ?string
    {
        $header = $request->header('Authorization', '');
        if (preg_match('/^Bearer\s+(?<token>[^\s]+)$/i', $header, $matches)) {
            return $matches['token'];
        }
        return null;
    }
}

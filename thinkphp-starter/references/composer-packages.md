# Composer 依赖管理规范

## 依赖基线

| 依赖 | 约束 | 用途 |
|---|---|---|
| `topthink/framework` | `^8.0` | 框架本体 |
| `topthink/think-multi-app` | `^1.1` | 多应用支持 |
| `topthink/think-orm` | `^3.0\|^4.0` | ORM |
| `topthink/think-filesystem` | `^2.0\|^3.0` | 文件系统 |
| `predis/predis` | `^2.2` | Redis 客户端 |
| `firebase/php-jwt` | `6.11` | JWT 签发与校验 |

## Redis

添加 `predis/predis` 前，检查 PHP 版本、是否已有 `ext-redis`、`predis/predis` 或其他 Redis 抽象层。已有客户端时优先复用，不重复引入。

连接参数通过环境变量提供：`REDIS_HOST`、`REDIS_PORT`、`REDIS_PASSWORD`、`REDIS_DB`、`REDIS_TIMEOUT`、`REDIS_PREFIX`。不要硬编码密码。

## JWT

JWT 依赖为 `firebase/php-jwt`。密钥只通过 `JWT_SECRET` 环境变量注入，配置项放在 `config/app.php` 的 `jwt` 节点，包含 `key`、`expire`、`alg`。不要覆盖项目中已有的有效密钥。

## 多应用扩展

多应用能力由 `topthink/think-multi-app` 提供，并与 `config/app.php` 的 `app_map` 配合使用。项目缺少该扩展时，先说明版本兼容性与所需改动并征求用户同意；不得擅自安装或升级。

## 通用规则

- 只添加用户明确需要的依赖；保留已有约束、不删除已有依赖、不覆盖完整 `composer.json`。
- 使用 `scripts/update_composer.py` 做最小改动，脚本不得执行 Composer。
- 绝不自动执行 `composer update` 或 `composer install`。安装完成后由用户自行更新 `composer.lock`。
- 完成后用中文列出变更内容与用户需要手动执行的命令。

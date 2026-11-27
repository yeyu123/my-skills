## ThinkPHP 多应用开发规范

### 项目环境

- 框架：ThinkPHP 8（`composer.json` 约束 `^8.0`）。
- PHP：`>=8.1`。
- 多应用扩展：`topthink/think-multi-app:^1.1`。
- 已创建应用：`__APP_NAME__`，URL 前缀为 `__APP_LOWER__`，通过 `config/app.php` 的 `app_map` 映射。
- 其他主要依赖：`predis/predis:^2.2`、`firebase/php-jwt:6.11`。

### 目录结构

```text
app/
├── BaseController.php                  # 项目级控制器基类
├── ExceptionHandle.php                 # 全局异常处理，渲染 ApiException
├── common/                             # 跨应用公共组件
│   ├── JwtToken.php
│   ├── exception/ApiException.php
│   ├── middleware/JwtAuth.php
│   └── traits/ContainerSingleton.php
└── __APP_NAME__/
    ├── common.php                      # success() / error() 响应助手
    ├── common/RedisManager.php
    ├── config/redis.php
    ├── config/route.php
    ├── controller/
    │   ├── __APP_NAME__Base.php
    │   ├── Users.php
    │   └── Login.php
    ├── model/
    │   ├── BaseModel.php
    │   └── UserModel.php
    ├── route/app.php
    ├── service/
    │   ├── BaseService.php
    │   └── UserService.php
    └── validate/
        ├── BaseValidate.php
        └── UserValidate.php
```

### 分层职责

- Controller：读取请求参数、调用 Service、使用 `success()` 返回统一 JSON，不直接操作数据库。
- Service：业务规则、流程编排、事务管理，真实调用 Validate 与 Model；登录密码校验与 Token 载荷生成也在此层完成。
- Validate：场景化参数校验与输入清洗，失败时通过 `error()` 抛出 422。
- Model：数据库查询、写入、ORM 关系与软删除。
- Common：跨应用或当前应用复用的基础组件，不承载具体业务。

`UserService` 的方法均通过 `UserModel` 读写数据库：列表使用真实分页，详情/修改/删除先按主键查找再操作，密码使用 `password_hash()` 存储、`password_verify()` 校验，输出前移除 `password` 与 `delete_time` 字段。

### 响应与鉴权

统一响应为 `{"code": 200, "message": "获取成功", "data": ...}`，HTTP 状态码与 `code` 一致。业务失败通过 `error()` 抛出 `ApiException`，由 `app/ExceptionHandle.php` 渲染为同一结构；非 `ApiException` 交给框架默认处理器。

登录接口 `/__APP_LOWER__/login` 使用 `UserValidate` 的 `login` 场景校验入参，查询 `user` 表并校验密码哈希与账号状态，成功后签发 JWT；业务路由分组统一挂 `jwt.auth` 中间件，Token 通过 `Authorization: Bearer <token>` 传递。

### 环境变量

复制技能目录中的 `templates/.env.example` 为项目根目录 `.env`，再按实际环境填写。不要提交 `.env` 到版本控制，不要写入真实密钥：

```text
DB_TYPE=mysql
DB_HOST=127.0.0.1
DB_NAME=test
DB_USER=root
DB_PASS=
DB_PORT=3306
DB_CHARSET=utf8mb4
DB_PREFIX=

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DB=0
REDIS_TIMEOUT=2.0
REDIS_PREFIX=tp:

JWT_SECRET=
```

其中 `JWT_SECRET` 应替换为随机强密钥，可用 `php -r "echo bin2hex(random_bytes(32));"` 生成。

### 数据表 DDL

以下为 MySQL 8 示例，字段与 `UserValidate` 的验证规则及 `UserService` 的实际读写一致。数据库结构未知时仅提供 DDL，不自动建表；项目使用其他数据库时请按对应语法调整：

```sql
CREATE TABLE `user` (
  `id`          bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `username`    varchar(25)  NOT NULL COMMENT '用户名，用于登录',
  `password`    varchar(255) NOT NULL COMMENT '密码哈希，由 password_hash() 生成',
  `email`       varchar(100) NOT NULL COMMENT '邮箱',
  `status`      tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态：1启用 0禁用',
  `delete_time` datetime DEFAULT NULL COMMENT '软删除时间',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  UNIQUE KEY `uk_email` (`email`),
  KEY `idx_status` (`status`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表';
```

`BaseModel` 使用 `delete_time` 作为软删除字段，删除接口只写入该字段；`create_time`、`update_time` 由框架自动时间戳维护。

登录接口需要真实用户，密码必须用哈希写入。先生成哈希，再插入测试账号：

```bash
php -r "echo password_hash('123456', PASSWORD_DEFAULT);"
```

```sql
INSERT INTO `user` (`username`, `password`, `email`, `status`)
VALUES ('admin', '<粘贴上一步生成的哈希>', 'admin@example.com', 1);
```

### CRUD 接口

| 方法 | 路径 | 说明 |
|---|---|---|
| POST | `/__APP_LOWER__/login` | 校验用户名密码与状态，签发 Token（无需鉴权） |
| GET | `/__APP_LOWER__/users/list?page=1&limit=15` | 用户列表，`limit` 上限 100，返回 `list`/`total`/`page`/`limit` |
| GET | `/__APP_LOWER__/users/detail/:id` | 用户详情，不存在返回 404 |
| POST | `/__APP_LOWER__/users/create` | 创建用户，密码自动哈希 |
| PUT | `/__APP_LOWER__/users/update/:id` | 修改用户名与邮箱 |
| DELETE | `/__APP_LOWER__/users/delete/:id` | 软删除用户 |
| POST | `/__APP_LOWER__/users/debounce` | Redis 防抖示例，同一用户 5 秒内只允许一次 |

除登录外，以上接口均需请求头 `Authorization: Bearer <token>`。

### 安装步骤

1. 检查 `composer.json` 并安装新增依赖，更新 `composer.lock`。
2. 复制 `.env.example` 为 `.env`，配置数据库、Redis 与 JWT 密钥。
3. 执行上面的数据表 DDL，并插入测试用户。
4. 确认 `app/ExceptionHandle.php` 已能渲染 `ApiException`。
5. 启动服务：`php think run`，用测试账号调用 `/__APP_LOWER__/login` 获取 Token，再访问 `/__APP_LOWER__/users/list` 验证接口。

> 这是起步示例。正式使用前请根据所安装的 ThinkPHP 版本确认应用映射、路由加载、验证器、分页与响应助手行为。

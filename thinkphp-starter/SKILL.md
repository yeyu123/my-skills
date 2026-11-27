---
name: thinkphp-starter
description: 按已有 ThinkPHP 8 多应用项目实践初始化或扩展应用。执行前先询问应用名称，提供 Admin（后台管理，默认推荐）、Applet（小程序）和自定义名称。在指定应用内搭建 Controller-Service-Model-Validate 分层、JWT 鉴权、统一响应、应用级 Redis 封装、Composer 依赖维护、User CRUD 案例和中文 README。当用户要求搭建、规范化或扩展 ThinkPHP 多应用项目时使用。
---

# ThinkPHP 多应用项目初始化技能

所有面向用户的说明、执行报告、README 和代码注释均使用中文。类名、方法名、变量名、Composer 包名等代码标识符保留英文。

本技能的项目基线来自一个已实际运行的 ThinkPHP 8 多应用项目：ThinkPHP 8、PHP >= 8.1、`topthink/think-multi-app:^1.1`、`predis/predis:^2.2`、`firebase/php-jwt:6.11`，应用目录为 PascalCase，URL 前缀为小写，并通过 `app_map` 映射。

## 一、先询问应用名称

执行 `init` 前必须先询问用户创建哪个应用，不得直接创建目录：

1. `Admin`：后台管理应用（默认推荐）。
2. `Applet`：小程序应用。
3. `自定义`：用户输入英文 PascalCase 名称，例如 `Api`、`Merchant`、`Web`。

用户明确说“默认”时才使用 `Admin`。未选择时等待回答。应用名称只允许以英文字母开头，后续由字母和数字组成；拒绝路径分隔符、空格、点号和路径穿越字符。URL 前缀默认取应用名的小写形式（例如 `Admin` 对应 `admin`）。除非用户明确要求，不要同时创建多个应用。

执行 `module` 或 `package` 时，如果还没有确定应用名称，也必须先询问。

## 二、支持的操作

- `init`：检查项目 → 创建应用目录与公共组件 → 配置 Redis/JWT/路由 → 生成 User CRUD 案例 → 追加中文 README。
- `inspect`：只检查项目，不修改文件。
- `module <模块名称>`：在指定应用内按 User 示例生成 Controller/Service/Model/Validate 骨架。先执行 `python scripts/module_project.py . --app <应用名> --module <模块名称>`，再由编码助手调整字段、规则、SQL 与路由。
- `package redis`：为指定应用添加 Redis 配置与封装。先执行 `python scripts/package_redis.py . --app <应用名>`，脚本会复用模板并检查 `predis/predis` 依赖。

以上脚本均不覆盖已有文件；生成后仍需由编码助手按项目实际约定补齐业务逻辑。

## 三、项目检查

先执行：

```bash
python scripts/inspect_project.py .
```

检查内容包括：`composer.json`、`topthink/framework` 版本、PHP 版本约束、PSR-4 命名空间、`topthink/think-multi-app`、`predis/predis`、`firebase/php-jwt`、`config/app.php` 的 `app_map`、`config/middleware.php` 的中间件别名、已有应用目录、跨应用公共组件以及应用级配置。无法确认 ThinkPHP 项目或框架版本时停止，不得猜测版本专属 API。

## 四、目录结构

业务代码按应用隔离。以 `Admin` 为例：

```text
app/
├── BaseController.php                    # 项目级控制器基类，不修改
├── ExceptionHandle.php                   # 统一渲染 ApiException，不修改业务逻辑
├── common.php                            # 项目级公共文件，保持原有内容
├── common/                               # 跨应用公共组件
│   ├── JwtToken.php
│   ├── exception/ApiException.php
│   ├── middleware/JwtAuth.php
│   └── traits/ContainerSingleton.php
└── Admin/                                # 用户选择的应用
    ├── common.php                        # success() / error() 响应助手
    ├── common/RedisManager.php           # 当前应用的 Redis 封装
    ├── config/redis.php                  # 应用级 Redis 配置
    ├── config/route.php                  # 应用级路由配置
    ├── controller/
    │   ├── AdminBase.php                 # 继承 app\BaseController
    │   ├── Users.php                     # 用户接口
    │   └── Login.php                     # 登录接口，签发 JWT
    ├── model/
    │   ├── BaseModel.php                 # 启用软删除
    │   └── UserModel.php
    ├── route/app.php                     # 应用独立路由
    ├── service/
    │   ├── BaseService.php               # 使用容器单例 trait
    │   └── UserService.php
    └── validate/
        ├── BaseValidate.php              # 场景化验证基类
        └── UserValidate.php
config/
├── app.php                               # app_map: ['admin' => 'Admin']
├── database.php                          # 数据库环境变量
└── middleware.php                        # 别名 jwt.auth
route/app.php                             # 全局路由保持原样
```

禁止默认创建 `app/controller`、`app/service`、`app/model` 这种单应用结构。框架原有的单应用文件（例如 `app/controller/Index.php`）不得删除，即使开启多应用后需要迁移，也只向用户说明。

## 五、命名与调用约定

- 应用目录：英文 PascalCase，例如 `Admin`、`Applet`、`Merchant`。
- 控制器：`Users`、`Login`、`Order`，继承本应用的 `AdminBase`，不强制 `Controller` 后缀；应用级 `config/route.php` 设置 `controller_suffix => false`。
- 控制器基类：`AdminBase extends app\BaseController`，负责登录人信息、请求上下文等初始化，不写具体业务。
- 服务：`UserService extends BaseService`，`BaseService` 使用 `app\common\traits\ContainerSingleton`，通过 `UserService::instance($params)` 获取实例；构造函数必须为 `public`，用 `protected array $params = []` 保存参数（禁止 `public function __construct(protected ...)` 这类非 public 构造，容器反射会失败）。
- 模型：`UserModel extends BaseModel`，`BaseModel extends think\Model` 并启用 `SoftDelete`，软删除字段为 `delete_time`。
- 验证器：`UserValidate extends BaseValidate`，声明 `$rule`、`$message`、`$sceneRules`，用 `UserValidate::instance()->verify('add', $data)` 按场景验证。
- 公共组件：`app/common/` 存放跨应用共享的 `JwtToken`、`JwtAuth`、`ApiException`、`ContainerSingleton`；当前应用独占的封装放在 `app/<应用名>/common/`。
- 路由：使用 `Route::get/post/put/delete` 配合 `[Users::class, 'index']` 数组形式；数字参数使用 `->pattern(['id' => '\d+'])`；需要鉴权的路由用 `Route::group(...)->middleware('jwt.auth')`。
- 参数传递：控制器统一用 `$this->request->param()` 取参，整体传入 Service；验证与数据清洗在 Service + Validate 层完成。
- 命名空间必须根据 `composer.json` 的 PSR-4 配置及 ThinkPHP 多应用加载规则确认，不得仅凭目录名猜测。

## 六、统一响应与异常

响应助手定义在应用级 `common.php`，生成代码必须复用，不要新建第二套响应结构：

```php
success(mixed $data = null, string $message = '获取成功', int $code = 200): Json
error(mixed $data = null, string|array $message = '获取失败', int $code = 400): never
```

- 统一响应体为 `{"code": 200, "message": "...", "data": ...}`，HTTP 状态码与 `code` 一致。
- `error()` 抛出 `app\common\exception\ApiException`，由 `app\ExceptionHandle.php` 渲染成与 `success()` 相同的结构。
- 业务失败一律通过 `error()` 中断，不要自行拼接错误 JSON。
- 验证失败由 `BaseValidate::verify()` 调用 `error()` 抛出 422。
- `app/ExceptionHandle.php` 通常已存在，不要覆盖；按 `templates/app/ExceptionHandle.php` 修改 `render()`，使 `ApiException` 渲染成与 `success()` 相同的结构，其余异常交回框架默认处理器。HTTP 状态码应限制在 100–599。

## 七、配置规范

- `config/app.php`：已存在时只追加 `app_map` 条目，小写 URL 前缀映射到 PascalCase 应用名，例如 `'admin' => 'Admin'`；不要覆盖 `default_app`、`app_namespace` 等已有配置。
- `config/route.php`：全局配置保持原样；应用级 `config/route.php` 设置 `'controller_suffix' => false`。
- `config/middleware.php`：注册别名 `'jwt.auth' => \app\common\middleware\JwtAuth::class`，已有别名时保留。
- Redis 配置放在应用级 `app/<应用名>/config/redis.php`，环境变量使用 `REDIS_HOST`、`REDIS_PORT`、`REDIS_PASSWORD`、`REDIS_DB`、`REDIS_TIMEOUT`、`REDIS_PREFIX`。读取端口时使用 `env('REDIS_PORT', 6379)`，不要使用空键名。
- 数据库沿用 `config/database.php`，环境变量为 `DB_TYPE`、`DB_HOST`、`DB_NAME`、`DB_USER`、`DB_PASS`、`DB_PORT`、`DB_CHARSET`，可选 `DB_PREFIX`。
- JWT 配置放在 `config/app.php` 的 `jwt` 节点：`key`（从 `JWT_SECRET` 读取）、`expire`、`alg`。不要覆盖已有的有效密钥。
- 从 `templates/.env.example` 复制环境变量清单（数据库、Redis、JWT）作为项目 `.env` 的起点；只在项目已使用 dotenv 时补充到 `.env.example`，绝不修改或覆盖 `.env`，绝不把密码或密钥写入代码库。

## 八、Composer 依赖

目标依赖基线：

| 依赖 | 约束 | 用途 |
|---|---|---|
| `topthink/framework` | `^8.0` | 框架 |
| `topthink/think-multi-app` | `^1.1` | 多应用支持 |
| `predis/predis` | `^2.2` | Redis 客户端 |
| `firebase/php-jwt` | `6.11` | JWT 签发与校验 |

规则：

- 添加前检查 PHP 版本、已有 Redis/JWT 客户端及扩展；优先复用项目已有实现。
- 使用 `python scripts/update_composer.py . --package <包名> --constraint <约束>` 做最小改动；保留已有依赖和约束，不重复添加，不覆盖整个 `composer.json`。
- 多应用扩展缺失时，先说明所需版本与兼容性并征求用户同意，不得擅自安装或升级。
- 绝不自动执行 `composer update` 或 `composer install`。完成后用中文列出变更，并提示用户自行安装依赖、更新 `composer.lock`。

## 九、Redis 封装

在 `app/<应用名>/common/RedisManager.php` 中提供：`get`（支持默认值）、`set`（支持 TTL）、`setNxEx`（SET NX EX，用于防抖/互斥）、`delete`（支持批量）、`exists`、`expire`、`getClient`。要求：

- 构造时允许传入配置数组，未传入时读取 `config('redis')`。
- 支持前缀 `prefix`，连接失败和命令失败通过 `error()` 抛出 `ApiException`，不得静默吞掉异常。
- 不承载具体业务逻辑，业务防抖等流程放在 Controller/Service 中。

## 十、JWT 鉴权

- `app/common/JwtToken.php` 提供 `createToken`、`verifyToken`、`getPayload`、`getTokenFromRequest`，从 `config('app.jwt.*')` 读取密钥、有效期和算法；Token 从 `Authorization: Bearer <token>` 提取。
- `app/common/middleware/JwtAuth.php` 校验 Token，并把 payload 注入 `$request->jwtPayload` 供控制器使用。
- 登录接口（例如 `Login::login()`）不挂鉴权中间件，用于签发 Token；业务路由分组统一挂 `jwt.auth`。
- 不要在代码中硬编码 JWT 密钥；密钥只能通过 `JWT_SECRET` 环境变量提供。

## 十一、User CRUD 案例

在所选应用内生成第九至十节所述的分层文件，所有方法必须通过 `UserModel` 真实读写数据库，不得保留 Mock 数据。路由写在 `app/<应用名>/route/app.php`，不得写入全局 `route/app.php`：

```text
POST   /<小写应用名>/login              登录并签发 Token（无鉴权）
GET    /<小写应用名>/users/list         用户列表及分页
GET    /<小写应用名>/users/detail/:id   用户详情
POST   /<小写应用名>/users/create       创建用户
PUT    /<小写应用名>/users/update/:id   修改用户
DELETE /<小写应用名>/users/delete/:id   删除用户
POST   /<小写应用名>/users/debounce     Redis 防抖示例
```

要求：

- 业务路由用 `Route::group('users', ...)->middleware('jwt.auth')` 包裹，登录路由单独注册。
- Controller 方法使用 `success()` 返回；Service 负责流程编排、验证与真实 Model 调用，Model 负责持久化。
- `paginate()` 使用 `UserModel::order(...)->paginate()`，返回 `list`/`total`/`page`/`limit`；`detail()`/`update()`/`delete()` 先按主键查找，不存在时通过 `error()` 返回 404。
- `create()` 使用 `password_hash()` 写密码，`login()` 使用 `password_verify()` 校验并检查 `status`；输出前统一移除 `password` 与 `delete_time`。
- 验证字段以 `UserValidate` 为准：`username`（必填，最长 25）、`password`（必填，最短 6）、`email`（必填，邮箱格式）、`id`（必填，正整数）；场景 `add`、`edit`、`login`、`read`。
- README 中给出完整数据表 DDL（含 `username`/`email` 唯一索引、`delete_time` 软删除字段及测试用户插入示例）；字段须与验证规则和 Service 读写一致，数据库结构未知时只给出 DDL，不自动建表。
- 若目标项目的实际字段命名与验证规则不一致，先向用户说明，不得自行假定。

## 十二、中文 README

在 README.md 末尾追加“ThinkPHP 多应用开发规范”章节，保留原有内容，不得整体覆盖。章节应包含：项目环境、应用名称与用途、目录结构、分层职责、Composer 依赖、数据库与 Redis/JWT 环境变量、数据表 DDL（含测试用户插入示例）、CRUD 接口、安装步骤以及待办事项。所有说明使用中文。

## 十三、校验

执行：

```bash
python scripts/validate_project.py . --app <应用名>
```

脚本除文件存在与配置文本外，还会检查 Service 是否真实调用 Model、是否残留 Mock 数据、登录是否使用 `password_verify`、异常处理器是否渲染 `ApiException`、JWT 密钥是否从 `JWT_SECRET` 读取。如有 PHP CLI，对新增文件执行 `php -l`；没有 PHP CLI 时明确说明未执行语法检查。依赖、数据库、Redis 和路由未验证前，不得声称项目完全可运行。

## 十四、安全规范

不删除原有文件；不未经检查覆盖业务代码；不覆盖完整 `composer.json`；不修改 `.env` 和密钥；不擅自升级 ThinkPHP 或安装多应用扩展；不自动执行 Composer 安装、数据库迁移或破坏性命令。文件已存在时先读取比较，必要时询问。执行后用中文列出新增、修改、跳过的文件及原因。

## 十五、最终报告

全部使用中文输出：项目环境、应用名称及用途、创建目录和文件、Composer 依赖变更、Redis/JWT 配置、User CRUD 接口、校验结果和用户还需手动完成的事项。必须以独立段落包含第十六节的 Composer 提醒，不得省略。

## 十六、收尾提醒

所有会改动项目的操作（`init`、`module`、`package`）完成后，无论校验是否通过，都必须在最终回复的最后单独提醒用户执行 Composer 命令，并说明：未安装新增依赖前，多应用、Redis、JWT 等能力不可用，项目不能运行。

命令根据目标项目状态选择：

- 已有 `composer.lock`（仅新增依赖）：`composer update <本次新增的包名...>`，例如 `composer update topthink/think-multi-app predis/predis firebase/php-jwt`。
- 没有 `composer.lock`：`composer install`。

提醒中必须给出可直接复制的命令，并明确说明本技能不会自动执行 Composer。不要把这一步省略、合并，或只写在 README 里。

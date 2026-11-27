# ThinkPHP 8 多应用架构规范

## 请求流程

`应用路由 -> Controller -> Service -> Validate / Model -> 统一响应`

- 应用路由位于 `app/<应用名>/route/app.php`，不要写入全局 `route/app.php`。
- Controller 通过 `$this->request->param()` 读取参数，调用 Service 后使用 `success()` 返回 JSON；不直接操作数据库。
- Service 负责业务规则、流程编排和事务，通过 `XxxValidate::instance()->verify()` 校验，通过 `XxxModel` 持久化。
- Validate 负责场景化校验和输入清洗，`BaseValidate::verify()` 失败时统一抛出 422 的 `ApiException`。
- Model 负责表映射、查询、写入与 ORM 关系；`BaseModel` 统一启用软删除。
- 跨应用组件（JWT、异常、中间件、trait）放在 `app/common/`；应用独占的封装放在 `app/<应用名>/common/`。

## 目录约定

```text
app/
├── BaseController.php
├── ExceptionHandle.php
├── common.php
├── common/
│   ├── JwtToken.php
│   ├── exception/ApiException.php
│   ├── middleware/JwtAuth.php
│   └── traits/ContainerSingleton.php
└── <应用名>/
    ├── common.php
    ├── common/RedisManager.php
    ├── config/redis.php
    ├── config/route.php
    ├── controller/<业务>.php
    ├── model/<业务>Model.php
    ├── route/app.php
    ├── service/<业务>Service.php
    └── validate/<业务>Validate.php
```

## 命名规范

- 应用目录：PascalCase，例如 `Admin`、`Applet`、`Merchant`；URL 前缀取小写形式。
- 控制器：`Users`、`Login`、`Order`，不强制 `Controller` 后缀，配置 `controller_suffix => false`。
- 应用基类：`<应用名>Base`，例如 `AdminBase`，继承 `app\BaseController`。
- 服务：`<业务>Service`，继承 `BaseService`，通过 `instance()` 单例调用。
- 模型：`<业务>Model`，继承 `BaseModel`。
- 验证器：`<业务>Validate`，继承 `BaseValidate`，使用场景名限定校验字段。
- 方法：小驼峰命名法。
- 数据表：沿用项目已有约定；示例表为 `user`，字段以对应验证器规则为准。

## 统一响应与异常

```php
success(mixed $data = null, string $message = '获取成功', int $code = 200): Json
error(mixed $data = null, string|array $message = '获取失败', int $code = 400): never
```

响应体统一为 `{"code": ..., "message": ..., "data": ...}`，HTTP 状态码与 `code` 一致。`app\common\exception\ApiException` 由 `app\ExceptionHandle.php` 渲染成同一结构。不要创建第二套响应封装或异常渲染逻辑。

## 鉴权约定

- JWT 密钥来自 `JWT_SECRET`，配置项位于 `config/app.php` 的 `jwt` 节点。
- `JwtToken` 负责签发与校验，Token 通过 `Authorization: Bearer <token>` 传递。
- `JwtAuth` 中间件在 `config/middleware.php` 中注册为别名 `jwt.auth`，校验通过后把 payload 注入 `$request->jwtPayload`。
- 登录接口不挂中间件；业务路由分组统一挂 `jwt.auth`。

## 兼容性

ThinkPHP 6 与 ThinkPHP 8 在 PHP 约束、应用映射、路由加载、验证器和辅助函数行为上可能不同。生成代码前必须检查目标项目的框架版本、PSR-4 配置和已有实现，不得依赖未文档化的假设。

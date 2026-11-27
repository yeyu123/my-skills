# 模板使用说明

`templates/` 中的目录层级即目标项目中的相对路径。生成文件前必须替换占位符：

| 占位符 | 含义 | 示例 |
|---|---|---|
| `__APP_NAME__` | PascalCase 应用名称 | `Admin`、`Applet`、`Merchant` |
| `__APP_LOWER__` | 小写 URL 前缀 | `admin`、`applet`、`merchant` |

## 模板清单

```text
templates/
├── .env.example                                    环境变量模板（数据库/Redis/JWT）
├── app/ExceptionHandle.php                         全局异常渲染参考（不覆盖已有文件）
├── app/common/JwtToken.php                         跨应用 JWT 工具
├── app/common/exception/ApiException.php           业务异常
├── app/common/middleware/JwtAuth.php               JWT 中间件
├── app/common/traits/ContainerSingleton.php        容器单例 trait
└── app/__APP_NAME__/
    ├── common.php                                  success() / error() 响应助手
    ├── common/RedisManager.php                     应用级 Redis 封装
    ├── config/redis.php                            应用级 Redis 配置
    ├── config/route.php                            应用级路由配置
    ├── controller/__APP_NAME__Base.php             应用控制器基类
    ├── controller/Users.php                        用户接口
    ├── controller/Login.php                        登录接口
    ├── model/BaseModel.php                         模型基类（软删除）
    ├── model/UserModel.php                         用户模型
    ├── route/app.php                               应用独立路由
    ├── service/BaseService.php                     服务基类（单例）
    ├── service/UserService.php                     用户服务
    ├── validate/BaseValidate.php                   验证器基类
    └── validate/UserValidate.php                   用户验证器
```

## 生成规则

- 模板只是代码起点，必须按目标 ThinkPHP 版本、项目命名空间、依赖注入方式和现有编码规范适配。
- 不得未经检查直接覆盖项目文件；目标文件已存在时先读取比较，必要时创建 `.example` 文件并说明冲突。
- 项目级 `app/BaseController.php`、`app/ExceptionHandle.php` 和全局 `config/` 文件通常已存在，只需按 `SKILL.md` 追加必要配置，不要用模板覆盖。
- User 系列模板已使用真实 Model 查询与写入，不得保留假数据；派生其他模块时可用 `scripts/module_project.py`，再按实际字段调整验证规则与 DDL。
- `scripts/module_project.py` 通过词边界替换将 User 模板派生为指定模块，`username` 等字段名不会被误改，但中文注释与业务路由仍需人工调整。

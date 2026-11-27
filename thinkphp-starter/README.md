# ThinkPHP Starter 技能 V1.0

一个可复用的 Agent Skill，基于已运行的 ThinkPHP 8 多应用项目实践，用于搭建 Controller-Service-Model-Validate 分层、JWT 鉴权、统一响应、应用级 Redis 封装、Composer 依赖维护、User CRUD 案例以及中文 README。

## 安装

- Codex：将 `thinkphp-starter` 目录复制到 `<project>/.agents/skills/thinkphp-starter/`，或复制到用户级的 `.agents/skills/` 目录。
- Claude Code：将其复制到 `<project>/.claude/skills/thinkphp-starter/`。

请保持目录名为 `thinkphp-starter`。

## 使用

在 ThinkPHP 项目的编码助手中提问：

- `用 thinkphp-starter 先检查一下这个项目。`
- `用 thinkphp-starter 初始化这个项目。`
- `用 thinkphp-starter 添加 Redis 包和封装。`
- `用 thinkphp-starter 参照已有的 User 示例生成一个 Order 模块。`

这是一个指令包；编码助手必须具有读写项目文件以及运行 Python/PHP 命令的权限。执行 `init` 前会先询问要创建的应用名称，默认为 `Admin`。

## 脚本

在技能目录下运行，并传入 ThinkPHP 项目路径：

```bash
python scripts/inspect_project.py /path/to/project
python scripts/init_project.py /path/to/project --app Admin
python scripts/module_project.py /path/to/project --app Admin --module Order
python scripts/package_redis.py /path/to/project --app Admin
python scripts/update_composer.py /path/to/project --package predis/predis --constraint "^2.2"
python scripts/validate_project.py /path/to/project --app Admin
```

- `inspect_project.py` 只输出检查结果，不修改文件。
- `init_project.py` 只创建目录，不写入代码文件；具体文件由编码助手根据 `templates/` 和 `SKILL.md` 生成。
- `module_project.py` 按 User 示例派生业务模块骨架，`package_redis.py` 复用 Redis 模板并检查 `predis/predis` 依赖；两者均不覆盖已有文件，仍需由编码助手补齐业务逻辑。
- `update_composer.py` 只做 `composer.json` 的最小改动，不执行 Composer。
- `validate_project.py` 检查文件存在、关键配置与真实实现，不修改文件。

如果系统中的 `python` 命令不可用（例如 Windows 上指向应用商店占位程序），请改用 `py -3` 执行上述脚本。

## 模板

`templates/` 中的路径对应目标项目路径，`__APP_NAME__` 表示 PascalCase 应用名（如 `Admin`），`__APP_LOWER__` 表示小写 URL 前缀（如 `admin`）。生成代码前必须替换占位符，并按目标项目的 ThinkPHP 版本、命名空间和已有约定适配，不得未经检查直接覆盖文件。`templates/.env.example` 可直接复制为项目 `.env` 的起点；`templates/app/ExceptionHandle.php` 用于修改已有异常处理器的 `render()`，不要覆盖原文件。

## 重要说明

- 请根据实际的 ThinkPHP 和 PHP 版本检查生成的代码。
- 运行 Composer 前请先检查 `composer.json`。
- 本技能不会自动运行 `composer update`，也不会擅自安装或升级多应用扩展。
- 数据库、Redis 和 JWT 凭据应在版本控制之外配置；绝不修改 `.env`。
- `JWT_SECRET` 请替换为随机强密钥，例如：`php -r "echo bin2hex(random_bytes(32));"`。
- User CRUD 与登录均为真实数据库实现；README 章节包含完整数据表 DDL 与测试用户插入示例，字段须与验证规则一致，请按实际数据库调整。

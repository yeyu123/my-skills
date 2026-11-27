# my-skills

可复用的编码助手技能（Agent Skill）集合。每个技能是一个独立目录，包含指令文件 `SKILL.md`、可执行脚本、模板和参考文档，供编码助手在目标项目中读取并执行。

目前收录 1 个技能：`thinkphp-starter`。

## 技能列表

| 技能 | 说明 |
|---|---|
| [thinkphp-starter](thinkphp-starter/) | ThinkPHP 8 多应用项目初始化与扩展：Controller-Service-Model-Validate 分层、JWT 鉴权、统一响应、应用级 Redis 封装、Composer 依赖维护、User CRUD 案例和中文 README。 |

## 安装

将所需技能目录复制到编码助手的技能目录，并保持目录名不变：

- CodeBuddy：`<项目>/.codebuddy/skills/thinkphp-starter/`，或用户级 `~/.codebuddy/skills/thinkphp-starter/`。
- Codex：`<项目>/.agents/skills/thinkphp-starter/`，或用户级 `~/.agents/skills/thinkphp-starter/`。
- Claude Code：`<项目>/.claude/skills/thinkphp-starter/`。

也可以直接将本仓库克隆到对应技能目录的父级下。安装后重启编码助手，使新技能被加载。

## 快速使用

在 ThinkPHP 项目的编码助手中提问：

- `用 thinkphp-starter 先检查一下这个项目。`
- `用 thinkphp-starter 初始化这个项目。`
- `用 thinkphp-starter 添加 Redis 包和封装。`
- `用 thinkphp-starter 参照已有的 User 示例生成一个 Order 模块。`

执行 `init` 前会先询问要创建的应用名称（`Admin`、`Applet` 或自定义 PascalCase 名称），默认推荐 `Admin`。更多用法与注意事项见 [技能文档](thinkphp-starter/README.md) 和 [SKILL.md](thinkphp-starter/SKILL.md)。

技能是**指令包**，需要编码助手具备读写项目文件和运行 Python/PHP 命令的权限。脚本不执行 Composer、数据库迁移或安装依赖；新增依赖后需按提示手动执行 `composer update` 或 `composer install`。

## 目录结构

```text
my-skills/
├── README.md                          # 本文件，仓库概览
└── thinkphp-starter/                  # 唯一的技能
    ├── README.md                      # 技能安装与使用说明
    ├── SKILL.md                       # 技能指令、约定与安全规范
    ├── assets/                        # 可复用的 README 章节
    ├── references/                    # 架构与 Composer 依赖规范
    ├── scripts/                       # 检查、初始化、校验等 Python 脚本
    └── templates/                     # 代码与配置模板，占位符为 __APP_NAME__ / __APP_LOWER__
```

`scripts/` 中各脚本的用途：

- `inspect_project.py`：只读检查项目环境，不修改文件。
- `init_project.py`：创建应用目录结构，不写入代码文件。
- `module_project.py`：按 User 示例派生业务模块骨架。
- `package_redis.py`：复用 Redis 模板并检查 `predis/predis` 依赖。
- `update_composer.py`：对 `composer.json` 做最小改动，不执行 Composer。
- `validate_project.py`：校验文件、配置与真实实现，不修改文件。

在技能目录下运行脚本并传入目标项目路径，例如：

```bash
python scripts/inspect_project.py /path/to/project
python scripts/validate_project.py /path/to/project --app Admin
```

Windows 上若 `python` 指向应用商店占位程序，请改用 `py -3`。

## 新增技能约定

1. 在仓库根目录新建以技能名命名的目录，目录名使用小写英文与连字符。
2. 目录内必须包含 `SKILL.md`，并在 YAML front matter 中写明 `name` 和 `description`。
3. 将脚本放入 `scripts/`、模板放入 `templates/`、参考文档放入 `references/`；模板占位符需在使用前替换。
4. 在本文件的“技能列表”和“目录结构”中补充该技能。

## 通用原则

- 不覆盖目标项目的原有文件，不修改 `.env` 或提交密钥。
- 不自动执行 Composer 安装、数据库迁移或破坏性命令。
- 生成代码前先确认目标项目的框架版本、命名空间和已有实现。
- 面向用户的说明、执行报告和生成的文档均使用中文；代码标识符保留英文。

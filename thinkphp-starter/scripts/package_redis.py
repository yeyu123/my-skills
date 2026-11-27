#!/usr/bin/env python3
# 为指定应用添加 Redis 配置与封装，不覆盖任何已有文件。
import argparse
import json
import re
import sys
from pathlib import Path

p = argparse.ArgumentParser(description="为 ThinkPHP 应用添加 Redis 配置与封装")
p.add_argument("project", nargs="?", default=".")
p.add_argument("--app", required=True, help="应用名称，例如 Admin")
args = p.parse_args()

if not re.fullmatch(r"[A-Za-z][A-Za-z0-9]*", args.app):
    sys.exit("错误：应用名称必须以英文字母开头，且只包含字母和数字")

root = Path(args.project).resolve()
app_dir = root / "app" / args.app
if not app_dir.is_dir():
    sys.exit(f"错误：未找到应用目录 {app_dir}，请先执行 init_project.py --app {args.app}")

template_dir = Path(__file__).resolve().parent.parent / "templates" / "app" / "__APP_NAME__"
mapping = [
    ("common/RedisManager.php", "common/RedisManager.php"),
    ("config/redis.php", "config/redis.php"),
]

for src_rel, dst_rel in mapping:
    src = template_dir / src_rel
    dst = app_dir / dst_rel
    if not src.is_file():
        print(f"跳过：模板不存在 {src_rel}")
        continue
    dst.parent.mkdir(parents=True, exist_ok=True)
    if dst.exists():
        print(f"跳过（已存在）：app/{args.app}/{dst_rel}")
        continue
    text = src.read_text(encoding="utf-8").replace("__APP_NAME__", args.app)
    dst.write_text(text, encoding="utf-8")
    print(f"已创建：app/{args.app}/{dst_rel}")

missing = [
    rel for rel in (
        "app/common/exception/ApiException.php",
        f"app/{args.app}/common.php",
    ) if not (root / rel).is_file()
]
if missing:
    print("提示：缺少依赖文件，请先执行 init：" + "、".join(missing))

composer = root / "composer.json"
if composer.is_file():
    try:
        data = json.loads(composer.read_text(encoding="utf-8"))
        require = {**data.get("require", {}), **data.get("require-dev", {})}
        if "predis/predis" not in require:
            print("提示：未找到 predis/predis 依赖，可执行：")
            print(f"  python scripts/update_composer.py {args.project} --package predis/predis --constraint ^2.2")
    except Exception as exc:
        print(f"提示：无法解析 composer.json：{exc}")

print("提示：请从 templates/.env.example 复制环境变量到项目 .env，并填写真实 Redis 配置。")

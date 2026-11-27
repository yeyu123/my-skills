#!/usr/bin/env python3
# 为指定应用创建多应用分层目录，不覆盖任何已有文件。
import argparse, re, sys
from pathlib import Path

p = argparse.ArgumentParser(description="创建 ThinkPHP 多应用目录")
p.add_argument("project", nargs="?", default=".")
p.add_argument("--app", required=True, help="应用名称，例如 Admin 或 Applet")
args = p.parse_args()

if not re.fullmatch(r"[A-Za-z][A-Za-z0-9]*", args.app):
    sys.exit("错误：应用名称必须以英文字母开头，且只包含字母和数字")

root = Path(args.project).resolve()
app = args.app
directories = [
    f"app/{app}/controller",
    f"app/{app}/service",
    f"app/{app}/model",
    f"app/{app}/validate",
    f"app/{app}/common",
    f"app/{app}/config",
    f"app/{app}/route",
    "app/common/exception",
    "app/common/middleware",
    "app/common/traits",
]
for rel in directories:
    path = root / rel
    existed = path.exists()
    path.mkdir(parents=True, exist_ok=True)
    print(("已存在 " if existed else "已创建 ") + rel)
print(f"应用 {app} 的目录已就绪，未修改任何已有文件。")

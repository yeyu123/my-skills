#!/usr/bin/env python3
# 安全地修改 composer.json 中的依赖约束，不执行 Composer。
import argparse, json, re, sys
from pathlib import Path

p = argparse.ArgumentParser(description="安全添加 Composer 依赖，不执行 Composer")
p.add_argument("project", nargs="?", default=".")
p.add_argument("--package", required=True)
p.add_argument("--constraint", required=True)
args = p.parse_args()

if not re.fullmatch(r"[a-z0-9][a-z0-9_\-.]*/[a-z0-9][a-z0-9_\-.]*", args.package):
    sys.exit("错误：Composer 包名格式不正确")

path = Path(args.project).resolve() / "composer.json"
if not path.exists():
    sys.exit("错误：未找到 composer.json")
try:
    data = json.loads(path.read_text(encoding="utf-8"))
except Exception as exc:
    sys.exit(f"错误：composer.json 无效：{exc}")

require = data.setdefault("require", {})
if args.package in require:
    print(f"未改动：{args.package} 已存在，当前约束为 {require[args.package]}")
else:
    require[args.package] = args.constraint
    # 尽量保持键的顺序不变；JSON 格式会被规范化。
    path.write_text(json.dumps(data, ensure_ascii=False, indent=4) + "\n", encoding="utf-8")
    print(f"已添加：{args.package} {args.constraint}")
print("未执行 Composer。安装依赖前请先检查 composer.json。")

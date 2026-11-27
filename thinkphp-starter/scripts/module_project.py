#!/usr/bin/env python3
# 在指定应用内按 User 示例生成业务模块骨架，不覆盖任何已有文件。
import argparse
import re
import sys
from pathlib import Path

p = argparse.ArgumentParser(description="在 ThinkPHP 应用内生成业务模块骨架")
p.add_argument("project", nargs="?", default=".")
p.add_argument("--app", required=True, help="应用名称，例如 Admin")
p.add_argument("--module", required=True, help="模块名称，PascalCase，例如 Order")
args = p.parse_args()

if not re.fullmatch(r"[A-Za-z][A-Za-z0-9]*", args.app):
    sys.exit("错误：应用名称必须以英文字母开头，且只包含字母和数字")
if not re.fullmatch(r"[A-Za-z][A-Za-z0-9]*", args.module):
    sys.exit("错误：模块名称必须以英文字母开头，且只包含字母和数字")
if args.module == args.app:
    sys.exit("错误：模块名称不能与应用名称相同")

root = Path(args.project).resolve()
app_dir = root / "app" / args.app
if not app_dir.is_dir():
    sys.exit(f"错误：未找到应用目录 {app_dir}，请先执行 init_project.py --app {args.app}")

template_dir = Path(__file__).resolve().parent.parent / "templates" / "app" / "__APP_NAME__"
module = args.module
lower = module[0].lower() + module[1:]
mapping = [
    ("controller/Users.php", f"controller/{module}s.php"),
    ("service/UserService.php", f"service/{module}Service.php"),
    ("model/UserModel.php", f"model/{module}Model.php"),
    ("validate/UserValidate.php", f"validate/{module}Validate.php"),
]


def convert(text: str) -> str:
    text = text.replace("__APP_NAME__", args.app)
    # 先替换复合类名，\bUser\b 无法匹配 UserModel / UserService 这类标识符。
    for suffix in ("Model", "Service", "Validate"):
        text = text.replace("User" + suffix, module + suffix)
    text = re.sub(r"\bUsers\b", module + "s", text)
    text = re.sub(r"\bUser\b", module, text)
    text = re.sub(r"\busers\b", lower + "s", text)
    text = re.sub(r"\buser\b", lower, text)
    return text


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
    dst.write_text(convert(src.read_text(encoding="utf-8")), encoding="utf-8")
    print(f"已创建：app/{args.app}/{dst_rel}")

print(f"模块 {module} 的骨架已生成。")
print(f"后续请由编码助手调整字段、验证规则、SQL 与业务路由；路由需手动追加到 app/{args.app}/route/app.php。")

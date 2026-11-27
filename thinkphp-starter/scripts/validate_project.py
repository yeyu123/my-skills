#!/usr/bin/env python3
# 按 ThinkPHP 8 多应用基线校验目录、依赖与关键配置，不修改文件。
import argparse, json, re, sys
from pathlib import Path

p = argparse.ArgumentParser(description="校验 ThinkPHP 多应用项目")
p.add_argument("project", nargs="?", default=".")
p.add_argument("--app", required=True, help="要校验的应用名称，例如 Admin")
args = p.parse_args()

root = Path(args.project).resolve()
app = args.app
errors, warnings = [], []
composer = root / "composer.json"
if not composer.exists():
    errors.append("缺少 composer.json")
else:
    try:
        data = json.loads(composer.read_text(encoding="utf-8"))
        require = {**data.get("require", {}), **data.get("require-dev", {})}
        if "topthink/framework" not in require:
            errors.append("Composer 依赖中未找到 topthink/framework")
        if "topthink/think-multi-app" not in require:
            warnings.append("未找到多应用扩展 topthink/think-multi-app")
        for package in ("predis/predis", "firebase/php-jwt"):
            if package not in require:
                warnings.append(f"未找到依赖：{package}")
        if "app\\" not in data.get("autoload", {}).get("psr-4", {}):
            warnings.append("PSR-4 中未找到 app\\ 命名空间映射")
    except Exception as exc:
        errors.append(f"composer.json 格式错误：{exc}")

app_config = (root / "config/app.php")
if not app_config.is_file():
    warnings.append("缺少 config/app.php")
else:
    text = app_config.read_text(encoding="utf-8")
    match = re.search(r"['\"]app_map['\"]\s*=>\s*\[(.*?)\]", text, re.S)
    app_map = dict(re.findall(r"['\"]([^'\"]+)['\"]\s*=>\s*['\"]([^'\"]+)['\"]", match.group(1))) if match else {}
    if app not in app_map.values():
        warnings.append(f"config/app.php 的 app_map 中未映射应用 {app}")

middleware_config = root / "config/middleware.php"
if not middleware_config.is_file() or "jwt.auth" not in middleware_config.read_text(encoding="utf-8"):
    warnings.append("config/middleware.php 中未找到 jwt.auth 别名")

expected_common = [
    "app/common/JwtToken.php",
    "app/common/exception/ApiException.php",
    "app/common/middleware/JwtAuth.php",
    "app/common/traits/ContainerSingleton.php",
    "app/BaseController.php",
    "app/ExceptionHandle.php",
]
expected_app = [
    f"app/{app}/common.php",
    f"app/{app}/common/RedisManager.php",
    f"app/{app}/config/redis.php",
    f"app/{app}/config/route.php",
    f"app/{app}/controller/{app}Base.php",
    f"app/{app}/controller/Users.php",
    f"app/{app}/controller/Login.php",
    f"app/{app}/model/BaseModel.php",
    f"app/{app}/model/UserModel.php",
    f"app/{app}/route/app.php",
    f"app/{app}/service/BaseService.php",
    f"app/{app}/service/UserService.php",
    f"app/{app}/validate/BaseValidate.php",
    f"app/{app}/validate/UserValidate.php",
]
for rel in expected_common + expected_app:
    if not (root / rel).is_file():
        warnings.append(f"未找到文件：{rel}（可能尚未生成）")


def content_of(rel):
    path = root / rel
    return path.read_text(encoding="utf-8") if path.is_file() else ""


service_text = content_of(f"app/{app}/service/UserService.php")
if service_text:
    if not re.search(r"UserModel::|new\s+UserModel", service_text):
        warnings.append("UserService 未调用 UserModel，可能仍是 Mock 实现")
    if "张三" in service_text or "zhangsan@example.com" in service_text:
        warnings.append("UserService 中残留 Mock 假数据")
    if "password_verify" not in service_text:
        warnings.append("UserService 中未找到 password_verify，登录可能未校验密码")

handler_text = content_of("app/ExceptionHandle.php")
if handler_text and "ApiException" not in handler_text:
    warnings.append("app/ExceptionHandle.php 未渲染 ApiException，统一异常响应不生效")

app_text = content_of("config/app.php")
if "jwt" in app_text and "JWT_SECRET" not in app_text:
    warnings.append("config/app.php 的 jwt 配置未从 JWT_SECRET 读取密钥")

print("校验结果")
for item in errors:
    print("错误：", item)
for item in warnings:
    print("警告：", item)
if not errors and not warnings:
    print(f"通过：ThinkPHP 多应用项目 {app} 的基础文件与配置均已找到")
if (root / "composer.lock").is_file():
    print("收尾提醒：请执行 composer update 安装新增依赖并更新 composer.lock（本技能不会自动执行 Composer）。")
else:
    print("收尾提醒：请执行 composer install 安装依赖并生成 composer.lock（本技能不会自动执行 Composer）。")
sys.exit(1 if errors else 0)

#!/usr/bin/env python3
# 只读检查 ThinkPHP 版本、多应用扩展、应用目录与关键配置，不修改任何文件。
import json, re, sys
from pathlib import Path

root = Path(sys.argv[1] if len(sys.argv) > 1 else ".").resolve()
composer = root / "composer.json"
result = {
    "项目目录": str(root),
    "composer.json存在": composer.exists(),
    "是否ThinkPHP项目": False,
    "框架版本约束": None,
    "PHP版本约束": None,
    "PSR-4命名空间": {},
    "多应用扩展版本约束": None,
    "Redis相关依赖": [],
    "JWT相关依赖": [],
    "应用映射": {},
    "jwt.auth中间件别名": None,
    "现有应用目录": [],
    "单应用残留目录": [],
    "跨应用公共组件": {},
    "应用级文件": {},
}
if not composer.exists():
    print(json.dumps(result, ensure_ascii=False, indent=2))
    sys.exit(1)
try:
    data = json.loads(composer.read_text(encoding="utf-8"))
except Exception as exc:
    print(json.dumps({"错误": f"无法解析 composer.json：{exc}", **result}, ensure_ascii=False, indent=2))
    sys.exit(2)

requires = {**data.get("require", {}), **data.get("require-dev", {})}
result["框架版本约束"] = requires.get("topthink/framework")
result["PHP版本约束"] = requires.get("php")
result["PSR-4命名空间"] = data.get("autoload", {}).get("psr-4", {})
result["是否ThinkPHP项目"] = bool(result["框架版本约束"])
result["多应用扩展版本约束"] = requires.get("topthink/think-multi-app")
result["Redis相关依赖"] = [name for name in requires if "redis" in name.lower()]
result["JWT相关依赖"] = [name for name in requires if "jwt" in name.lower()]


def read_text(relative):
    path = root / relative
    return path.read_text(encoding="utf-8") if path.is_file() else ""


app_config = read_text("config/app.php")
app_map_match = re.search(r"['\"]app_map['\"]\s*=>\s*\[(.*?)\]", app_config, re.S)
if app_map_match:
    result["应用映射"] = dict(re.findall(r"['\"]([^'\"]+)['\"]\s*=>\s*['\"]([^'\"]+)['\"]", app_map_match.group(1)))

middleware_config = read_text("config/middleware.php")
alias_match = re.search(r"['\"]jwt\.auth['\"]\s*=>\s*([^,\n\]]+)", middleware_config)
if alias_match:
    result["jwt.auth中间件别名"] = alias_match.group(1).strip()

app_dir = root / "app"
non_app_dirs = {"common", "controller", "service", "model", "validate", "config", "route"}
if app_dir.is_dir():
    result["现有应用目录"] = sorted(
        item.name for item in app_dir.iterdir()
        if item.is_dir() and item.name not in non_app_dirs
    )
    result["单应用残留目录"] = sorted(
        item.name for item in app_dir.iterdir()
        if item.is_dir() and item.name in {"controller", "service", "model", "validate"}
    )

common_components = [
    "app/common/JwtToken.php",
    "app/common/exception/ApiException.php",
    "app/common/middleware/JwtAuth.php",
    "app/common/traits/ContainerSingleton.php",
    "app/BaseController.php",
    "app/ExceptionHandle.php",
]
result["跨应用公共组件"] = {rel: (root / rel).is_file() for rel in common_components}

app_files = [
    "common.php", "common/RedisManager.php", "config/redis.php", "config/route.php",
    "controller/Users.php", "controller/Login.php", "model/BaseModel.php",
    "model/UserModel.php", "route/app.php", "service/BaseService.php",
    "service/UserService.php", "validate/BaseValidate.php", "validate/UserValidate.php",
]
for name in result["现有应用目录"]:
    result["应用级文件"][name] = {
        rel: (app_dir / name / rel).is_file() for rel in app_files
    }

print(json.dumps(result, ensure_ascii=False, indent=2))
sys.exit(0 if result["是否ThinkPHP项目"] else 1)

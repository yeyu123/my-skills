<?php
declare (strict_types = 1);

use think\facade\Route;
use app\__APP_NAME__\controller\Users;
use app\__APP_NAME__\controller\Login;

// 应用自己的路由，URL 前缀由应用名提供，不写入全局 route/app.php。
Route::group('users', function () {
    Route::get('list', [Users::class, 'index']);
    Route::get('detail/:id', [Users::class, 'read'])->pattern(['id' => '\d+']);
    Route::post('create', [Users::class, 'create']);
    Route::put('update/:id', [Users::class, 'update'])->pattern(['id' => '\d+']);
    Route::delete('delete/:id', [Users::class, 'delete'])->pattern(['id' => '\d+']);
    Route::post('debounce', [Users::class, 'debounce']);
})->middleware('jwt.auth');

// 登录接口不挂鉴权中间件，用于签发 Token。
Route::post('login', [Login::class, 'login']);

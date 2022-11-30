<?php
/**
 * Name: Redis插件
 * Author: 耗子
 * Date: 2022-11-30
 */

use Illuminate\Support\Facades\Route;
use Plugins\Redis\Controllers\RedisController;

// 视图
app('router')->group([
    'prefix' => 'panel/views/plugin/redis',
    //'middleware' => ['auth:sanctum'],
], function () {
    Route::view('/', 'redis::index');
});
// 控制器
app('router')->group([
    'prefix' => 'api/plugin/redis',
    'middleware' => ['auth:sanctum'],
], function () {
    Route::get('status', [RedisController::class, 'status']);
    Route::get('restart', [RedisController::class, 'restart']);
    Route::get('reload', [RedisController::class, 'reload']);
    Route::get('load', [RedisController::class, 'load']);
    Route::get('config', [RedisController::class, 'getConfig']);
    Route::post('config', [RedisController::class, 'saveConfig']);
});


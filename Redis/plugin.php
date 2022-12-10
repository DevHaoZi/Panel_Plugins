<?php
/**
 * Name: Redis插件
 * Author: 耗子
 * Date: 2022-12-10
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
    Route::post('start', [RedisController::class, 'start']);
    Route::post('stop', [RedisController::class, 'stop']);
    Route::post('restart', [RedisController::class, 'restart']);
    Route::post('reload', [RedisController::class, 'reload']);
    Route::get('load', [RedisController::class, 'load']);
    Route::get('config', [RedisController::class, 'getConfig']);
    Route::post('config', [RedisController::class, 'saveConfig']);
});


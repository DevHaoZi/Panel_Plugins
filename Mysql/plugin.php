<?php
/**
 * Name: MySQL插件
 * Author: 耗子
 * Date: 2022-11-21
 */

use Illuminate\Support\Facades\Route;
use Plugins\Mysql\Controllers\MysqlController;

// 视图
app('router')->group([
    'prefix' => 'panel/views/plugin/mysql',
    //'middleware' => ['auth:sanctum'],
], function () {
    Route::view('/', 'mysql::index');
});
// 控制器
app('router')->group([
    'prefix' => 'api/plugin/mysql',
    'middleware' => ['auth:sanctum'],
], function () {
    Route::get('status', [MysqlController::class, 'status']);
    Route::get('start', [MysqlController::class, 'start']);
    Route::get('stop', [MysqlController::class, 'stop']);
    Route::get('load', [MysqlController::class, 'load']);
    Route::get('errorLog', [MysqlController::class, 'errorLog']);
    Route::get('slowLog', [MysqlController::class, 'slowLog']);
    Route::get('config', [MysqlController::class, 'getConfig']);
    Route::post('config', [MysqlController::class, 'saveConfig']);
    Route::get('cleanErrorLog', [MysqlController::class, 'cleanErrorLog']);
    Route::get('cleanSlowLog', [MysqlController::class, 'cleanSlowLog']);
    Route::get('restart', [MysqlController::class, 'restart']);
    Route::get('reload', [MysqlController::class, 'reload']);
});


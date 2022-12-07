<?php
/**
 * Name: Pure-Ftpd插件
 * Author: 耗子
 * Date: 2022-12-07
 */

use Illuminate\Support\Facades\Route;
use Plugins\PureFtpd\Controllers\PureFtpdController;

// 视图
app('router')->group([
    'prefix' => 'panel/views/plugin/pure-ftpd',
    //'middleware' => ['auth:sanctum'],
], function () {
    Route::view('/', 'pure-ftpd::index');
    Route::view('add_user', 'pure-ftpd::add_user');
});
// 控制器
app('router')->group([
    'prefix' => 'api/plugin/pure-ftpd',
    'middleware' => ['auth:sanctum'],
], function () {
    Route::get('getUserList', [PureFtpdController::class, 'getUserList']);
    Route::post('addUser', [PureFtpdController::class, 'addUser']);
    Route::post('deleteUser', [PureFtpdController::class, 'deleteUser']);
    Route::post('changePassword', [PureFtpdController::class, 'changePassword']);
    Route::post('getPort', [PureFtpdController::class, 'getPort']);
    Route::post('setPort', [PureFtpdController::class, 'setPort']);
});

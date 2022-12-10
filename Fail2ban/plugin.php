<?php
/**
 * Name: Fail2ban插件
 * Author: 耗子
 * Date: 2022-12-08
 */

use Illuminate\Support\Facades\Route;
use Plugins\Fail2ban\Controllers\Fail2banController;

// 视图
app('router')->group([
    'prefix' => 'panel/views/plugin/fail2ban',
    //'middleware' => ['auth:sanctum'],
], function () {
    Route::view('/', 'fail2ban::index');
    Route::view('add_rule', 'fail2ban::add_rule');
    Route::view('view_rule', 'fail2ban::view_rule');
});
// 控制器
app('router')->group([
    'prefix' => 'api/plugin/fail2ban',
    'middleware' => ['auth:sanctum'],
], function () {
    Route::get('getList', [Fail2banController::class, 'getList']);
    Route::post('addRule', [Fail2banController::class, 'addRule']);
    Route::post('deleteRule', [Fail2banController::class, 'deleteRule']);
    Route::get('getBanList', [Fail2banController::class, 'getBanList']);
    Route::post('unBan', [Fail2banController::class, 'unBan']);
    Route::post('setWhiteList', [Fail2banController::class, 'setWhiteList']);
    Route::get('getWhiteList', [Fail2banController::class, 'getWhiteList']);
    Route::get('status', [Fail2banController::class, 'status']);
    Route::post('start', [Fail2banController::class, 'start']);
    Route::post('stop', [Fail2banController::class, 'stop']);
    Route::post('restart', [Fail2banController::class, 'restart']);
    Route::post('reload', [Fail2banController::class, 'reload']);
});

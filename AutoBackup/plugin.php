<?php
/**
 * Name: 自动备份插件
 * Author: 耗子
 * Date: 2022-12-04
 */

use Illuminate\Support\Facades\Route;
use Plugins\AutoBackup\Controllers\AutoBackupController;

// 视图
app('router')->group([
    'prefix' => 'panel/views/plugin/auto-backup',
    //'middleware' => ['auth:sanctum'],
], function () {
    Route::view('/', 'auto-backup::index');
    Route::view('add_backup', 'auto-backup::add_backup');
});
// 控制器
app('router')->group([
    'prefix' => 'api/plugin/auto-backup',
    'middleware' => ['auth:sanctum'],
], function () {
    Route::get('getTaskList', [AutoBackupController::class, 'getTaskList']);
    Route::post('addTask', [AutoBackupController::class, 'addTask']);
    Route::post('deleteTask', [AutoBackupController::class, 'deleteTask']);
});

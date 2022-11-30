<?php
/**
 * Name: MySQL插件
 * Author: 耗子
 * Date: 2022-11-30
 */

use Illuminate\Support\Facades\Route;
use Plugins\Mysql\Controllers\MysqlController;

// 视图
app('router')->group([
    'prefix' => 'panel/views/plugin/mysql',
    //'middleware' => ['auth:sanctum'],
], function () {
    Route::view('/', 'mysql::index');
    Route::view('add_database', 'mysql::add_database');
    Route::view('add_user', 'mysql::add_user');
    Route::view('backup', 'mysql::backup');
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
    Route::get('getSettings', [MysqlController::class, 'getSettings']);
    Route::post('saveSettings', [MysqlController::class, 'saveSettings']);
    Route::get('getDatabases', [MysqlController::class, 'getDatabases']);
    Route::post('addDatabase', [MysqlController::class, 'addDatabase']);
    Route::post('deleteDatabase', [MysqlController::class, 'deleteDatabase']);
    Route::get('getBackupList', [MysqlController::class, 'getBackupList']);
    Route::post('createBackup', [MysqlController::class, 'createBackup']);
    Route::post('uploadBackup', [MysqlController::class, 'uploadBackup']);
    Route::post('restoreBackup', [MysqlController::class, 'restoreBackup']);
    Route::post('deleteBackup', [MysqlController::class, 'deleteBackup']);
    Route::get('getUsers', [MysqlController::class, 'getUsers']);
    Route::post('addUser', [MysqlController::class, 'addUser']);
    Route::post('deleteUser', [MysqlController::class, 'deleteUser']);
    Route::post('changePassword', [MysqlController::class, 'changePassword']);
    Route::post('changePrivileges', [MysqlController::class, 'changePrivileges']);
});


<?php
/**
 * Name: PostgreSQL插件
 * Author: 耗子
 * Date: 2022-11-30
 */

use Illuminate\Support\Facades\Route;
use Plugins\Postgresql\Controllers\PostgresqlController;

// 视图
app('router')->group([
    'prefix' => 'panel/views/plugin/postgresql',
    //'middleware' => ['auth:sanctum'],
], function () {
    Route::view('/', 'postgresql::index');
    Route::view('add_database', 'postgresql::add_database');
    Route::view('add_user', 'postgresql::add_user');
    Route::view('backup', 'postgresql::backup');
});
// 控制器
app('router')->group([
    'prefix' => 'api/plugin/postgresql',
    'middleware' => ['auth:sanctum'],
], function () {
    Route::get('status', [PostgresqlController::class, 'status']);
    Route::post('start', [PostgresqlController::class, 'start']);
    Route::post('stop', [PostgresqlController::class, 'stop']);
    Route::post('restart', [PostgresqlController::class, 'restart']);
    Route::post('reload', [PostgresqlController::class, 'reload']);
    Route::get('load', [PostgresqlController::class, 'load']);
    Route::get('log', [PostgresqlController::class, 'log']);
    Route::get('config', [PostgresqlController::class, 'getConfig']);
    Route::get('userConfig', [PostgresqlController::class, 'getUserConfig']);
    Route::post('config', [PostgresqlController::class, 'saveConfig']);
    Route::post('userConfig', [PostgresqlController::class, 'saveUserConfig']);
    Route::get('getDatabases', [PostgresqlController::class, 'getDatabases']);
    Route::post('addDatabase', [PostgresqlController::class, 'addDatabase']);
    Route::post('deleteDatabase', [PostgresqlController::class, 'deleteDatabase']);
    Route::get('getBackupList', [PostgresqlController::class, 'getBackupList']);
    Route::post('createBackup', [PostgresqlController::class, 'createBackup']);
    Route::post('uploadBackup', [PostgresqlController::class, 'uploadBackup']);
    Route::post('restoreBackup', [PostgresqlController::class, 'restoreBackup']);
    Route::post('deleteBackup', [PostgresqlController::class, 'deleteBackup']);
    Route::get('getUsers', [PostgresqlController::class, 'getUsers']);
    Route::post('addUser', [PostgresqlController::class, 'addUser']);
    Route::post('deleteUser', [PostgresqlController::class, 'deleteUser']);
    Route::post('changePassword', [PostgresqlController::class, 'changePassword']);
    Route::post('changePrivileges', [PostgresqlController::class, 'changePrivileges']);
});


<?php
/**
 * Name: PHP8.0插件
 * Author: 耗子
 * Date: 2022-12-10
 */

use Illuminate\Support\Facades\Route;
use Plugins\Php80\Controllers\Php80Controller;

// 视图
app('router')->group([
    'prefix' => 'panel/views/plugin/php80',
    //'middleware' => ['auth:sanctum'],
], function () {
    Route::view('/', 'php80::index');
});
// 控制器
app('router')->group([
    'prefix' => 'api/plugin/php80',
    'middleware' => ['auth:sanctum'],
], function () {
    Route::get('status', [Php80Controller::class, 'status']);
    Route::post('start', [Php80Controller::class, 'start']);
    Route::post('stop', [Php80Controller::class, 'stop']);
    Route::post('restart', [Php80Controller::class, 'restart']);
    Route::post('reload', [Php80Controller::class, 'reload']);
    Route::get('load', [Php80Controller::class, 'load']);
    Route::get('errorLog', [Php80Controller::class, 'errorLog']);
    Route::get('slowLog', [Php80Controller::class, 'slowLog']);
    Route::get('config', [Php80Controller::class, 'getConfig']);
    Route::post('config', [Php80Controller::class, 'saveConfig']);
    Route::post('cleanErrorLog', [Php80Controller::class, 'cleanErrorLog']);
    Route::post('cleanSlowLog', [Php80Controller::class, 'cleanSlowLog']);
    Route::get('getExtensionList', [Php80Controller::class, 'getExtensionList']);
    Route::post('installExtension', [Php80Controller::class, 'installExtension']);
    Route::post('uninstallExtension', [Php80Controller::class, 'uninstallExtension']);
});


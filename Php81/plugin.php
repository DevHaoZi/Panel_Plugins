<?php
/**
 * Name: PHP8.1插件
 * Author: 耗子
 * Date: 2022-11-30
 */

use Illuminate\Support\Facades\Route;
use Plugins\Php81\Controllers\Php81Controller;

// 视图
app('router')->group([
    'prefix' => 'panel/views/plugin/php81',
    //'middleware' => ['auth:sanctum'],
], function () {
    Route::view('/', 'php81::index');
});
// 控制器
app('router')->group([
    'prefix' => 'api/plugin/php81',
    'middleware' => ['auth:sanctum'],
], function () {
    Route::get('status', [Php81Controller::class, 'status']);
    Route::get('load', [Php81Controller::class, 'load']);
    Route::get('errorLog', [Php81Controller::class, 'errorLog']);
    Route::get('slowLog', [Php81Controller::class, 'slowLog']);
    Route::get('config', [Php81Controller::class, 'getConfig']);
    Route::post('config', [Php81Controller::class, 'saveConfig']);
    Route::get('cleanErrorLog', [Php81Controller::class, 'cleanErrorLog']);
    Route::get('cleanSlowLog', [Php81Controller::class, 'cleanSlowLog']);
    Route::get('restart', [Php81Controller::class, 'restart']);
    Route::get('reload', [Php81Controller::class, 'reload']);
    Route::get('getExtensionList', [Php81Controller::class, 'getExtensionList']);
    Route::post('installExtension', [Php81Controller::class, 'installExtension']);
    Route::post('uninstallExtension', [Php81Controller::class, 'uninstallExtension']);
});


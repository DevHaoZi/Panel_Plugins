<?php
/**
 * Name: PHP8.2插件
 * Author: 耗子
 * Date: 2022-12-10
 */

use Illuminate\Support\Facades\Route;
use Plugins\Php82\Controllers\Php82Controller;

// 视图
app('router')->group([
    'prefix' => 'panel/views/plugin/php82',
    //'middleware' => ['auth:sanctum'],
], function () {
    Route::view('/', 'php82::index');
});
// 控制器
app('router')->group([
    'prefix' => 'api/plugin/php82',
    'middleware' => ['auth:sanctum'],
], function () {
    Route::get('status', [Php82Controller::class, 'status']);
    Route::post('start', [Php82Controller::class, 'start']);
    Route::post('stop', [Php82Controller::class, 'stop']);
    Route::post('restart', [Php82Controller::class, 'restart']);
    Route::post('reload', [Php82Controller::class, 'reload']);
    Route::get('load', [Php82Controller::class, 'load']);
    Route::get('errorLog', [Php82Controller::class, 'errorLog']);
    Route::get('slowLog', [Php82Controller::class, 'slowLog']);
    Route::get('config', [Php82Controller::class, 'getConfig']);
    Route::post('config', [Php82Controller::class, 'saveConfig']);
    Route::post('cleanErrorLog', [Php82Controller::class, 'cleanErrorLog']);
    Route::post('cleanSlowLog', [Php82Controller::class, 'cleanSlowLog']);
    Route::get('getExtensionList', [Php82Controller::class, 'getExtensionList']);
    Route::post('installExtension', [Php82Controller::class, 'installExtension']);
    Route::post('uninstallExtension', [Php82Controller::class, 'uninstallExtension']);
});


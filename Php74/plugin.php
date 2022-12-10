<?php
/**
 * Name: PHP7.4插件
 * Author: 耗子
 * Date: 2022-12-10
 */

use Illuminate\Support\Facades\Route;
use Plugins\Php74\Controllers\Php74Controller;

// 视图
app('router')->group([
    'prefix' => 'panel/views/plugin/php74',
    //'middleware' => ['auth:sanctum'],
], function () {
    Route::view('/', 'php74::index');
});
// 控制器
app('router')->group([
    'prefix' => 'api/plugin/php74',
    'middleware' => ['auth:sanctum'],
], function () {
    Route::get('status', [Php74Controller::class, 'status']);
    Route::post('start', [Php74Controller::class, 'start']);
    Route::post('stop', [Php74Controller::class, 'stop']);
    Route::post('restart', [Php74Controller::class, 'restart']);
    Route::post('reload', [Php74Controller::class, 'reload']);
    Route::get('load', [Php74Controller::class, 'load']);
    Route::get('errorLog', [Php74Controller::class, 'errorLog']);
    Route::get('slowLog', [Php74Controller::class, 'slowLog']);
    Route::get('config', [Php74Controller::class, 'getConfig']);
    Route::post('config', [Php74Controller::class, 'saveConfig']);
    Route::post('cleanErrorLog', [Php74Controller::class, 'cleanErrorLog']);
    Route::post('cleanSlowLog', [Php74Controller::class, 'cleanSlowLog']);
    Route::get('getExtensionList', [Php74Controller::class, 'getExtensionList']);
    Route::post('installExtension', [Php74Controller::class, 'installExtension']);
    Route::post('uninstallExtension', [Php74Controller::class, 'uninstallExtension']);
});


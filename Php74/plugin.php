<?php
/**
 * Name: PHP7.4插件
 * Author: 耗子
 * Date: 2022-11-30
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
    Route::get('load', [Php74Controller::class, 'load']);
    Route::get('errorLog', [Php74Controller::class, 'errorLog']);
    Route::get('slowLog', [Php74Controller::class, 'slowLog']);
    Route::get('config', [Php74Controller::class, 'getConfig']);
    Route::post('config', [Php74Controller::class, 'saveConfig']);
    Route::get('cleanErrorLog', [Php74Controller::class, 'cleanErrorLog']);
    Route::get('cleanSlowLog', [Php74Controller::class, 'cleanSlowLog']);
    Route::get('restart', [Php74Controller::class, 'restart']);
    Route::get('reload', [Php74Controller::class, 'reload']);
    Route::get('getExtensionList', [Php74Controller::class, 'getExtensionList']);
    Route::post('installExtension', [Php74Controller::class, 'installExtension']);
    Route::post('uninstallExtension', [Php74Controller::class, 'uninstallExtension']);
});


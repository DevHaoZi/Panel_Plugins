<?php
/**
 * Name: phpMyAdmin插件
 * Author: 耗子
 * Date: 2022-11-30
 */

use Illuminate\Support\Facades\Route;
use Plugins\Phpmyadmin\Controllers\PhpmyadminController;

// 视图
app('router')->group([
    'prefix' => 'panel/views/plugin/phpmyadmin',
    //'middleware' => ['auth:sanctum'],
], function () {
    Route::view('/', 'phpmyadmin::index');
});
// 控制器
app('router')->group([
    'prefix' => 'api/plugin/phpmyadmin',
    'middleware' => ['auth:sanctum'],
], function () {
    Route::get('info', [PhpmyadminController::class, 'info']);
});


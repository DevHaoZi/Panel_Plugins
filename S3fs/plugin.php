<?php
/**
 * Name: S3fs插件
 * Author: 耗子
 * Date: 2022-12-10
 */

use Illuminate\Support\Facades\Route;
use Plugins\S3fs\Controllers\S3fsController;

// 视图
app('router')->group([
    'prefix' => 'panel/views/plugin/s3fs',
    //'middleware' => ['auth:sanctum'],
], function () {
    Route::view('/', 's3fs::index');
    Route::view('add_mount', 's3fs::add_mount');
});
// 控制器
app('router')->group([
    'prefix' => 'api/plugin/s3fs',
    'middleware' => ['auth:sanctum'],
], function () {
    Route::get('getList', [S3fsController::class, 'getList']);
    Route::post('addMount', [S3fsController::class, 'addMount']);
    Route::post('deleteMount', [S3fsController::class, 'deleteMount']);
});

<?php
/**
 * Name: phpMyAdmin插件控制器
 * Author:耗子
 * Date: 2022-11-30
 */

namespace Plugins\Phpmyadmin\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class PhpmyadminController extends Controller
{

    public function info(): JsonResponse
    {
        // 获取/www/wwwroot/phpmyadmin目录下以phpmyadmin_开头的文件夹名称
        $dir = '/www/wwwroot/phpmyadmin';
        $files = scandir($dir);
        $phpmyadmin = "";
        foreach ($files as $file) {
            if (str_contains($file, 'phpmyadmin_')) {
                $phpmyadmin = $file;
            }
        }

        if ($phpmyadmin == "") {
            return response()->json(['code' => 1, 'msg' => '未找到phpMyAdmin，可能已损坏']);
        }

        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = $phpmyadmin;

        return response()->json($res);
    }

}

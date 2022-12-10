<?php
/**
 * Name: phpMyAdmin插件控制器
 * Author: 耗子
 * Date: 2022-12-10
 */

namespace Plugins\Phpmyadmin\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PhpmyadminController extends Controller
{

    /**
     * 获取配置信息
     * @return JsonResponse
     */
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

        $nginxConfig = @file_get_contents('/www/server/vhost/phpmyadmin.conf');
        if (empty($nginxConfig)) {
            return response()->json(['code' => 1, 'msg' => '未找到phpMyAdmin配置文件，可能已损坏']);
        }
        // 获取端口
        preg_match('/listen\s+(\d+);/', $nginxConfig, $matches);
        if (!isset($matches[1])) {
            return response()->json(['code' => 1, 'msg' => '未找到phpMyAdmin端口，可能已损坏']);
        }

        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = [
            'port' => $matches[1],
            'phpmyadmin' => $phpmyadmin
        ];

        return response()->json($res);
    }

    /**
     * 设置端口
     * @param  Request  $request
     * @return JsonResponse
     */
    public function setPort(Request $request): JsonResponse
    {
        try {
            $input = $this->validate($request, [
                'port' => 'required|integer|min:1|max:65535',
            ]);
            $port = $input['port'];
        } catch (ValidationException $e) {
            return response()->json(['code' => 1, 'msg' => $e->getMessage()]);
        }
        if (empty($port)) {
            return response()->json(['code' => 1, 'msg' => '端口不能为空']);
        }
        $nginxConfig = @file_get_contents('/www/server/vhost/phpmyadmin.conf');
        if (empty($nginxConfig)) {
            return response()->json(['code' => 1, 'msg' => '未找到phpMyAdmin配置文件，可能已损坏']);
        }
        // 替换端口
        preg_replace('/listen\s+(\d+);/', 'listen '.$port.';', $nginxConfig);
        $res = file_put_contents('/www/server/vhost/phpmyadmin.conf', $nginxConfig);
        if ($res === false) {
            return response()->json(['code' => 1, 'msg' => '修改保存失败']);
        }

        shell_exec('firewall-cmd --zone=public --add-port='.$port.'/tcp --permanent');
        shell_exec('firewall-cmd --reload');
        shell_exec('systemctl reload nginx');
        return response()->json(['code' => 0, 'msg' => 'success']);
    }

}

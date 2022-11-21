<?php
/**
 * Name: Php81插件控制器
 * Author:耗子
 * Date: 2022-11-21
 */

namespace Plugins\Php81\Controllers;

use App\Http\Controllers\Controller;

// HTTP
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Request;
// Filesystem
use Illuminate\Filesystem\Filesystem;

class Php81Controller extends Controller
{

    public function status()
    {
        $command = 'systemctl status php-fpm-81';
        $result = shell_exec($command);

        $res['code'] = 0;
        $res['msg'] = 'success';
        if (str_contains($result, 'inactive')) {
            $res['data'] = 'stopped';
        } else {
            $res['data'] = 'running';
        }

        return response()->json($res);
    }

    public function restart()
    {
        $command = 'systemctl restart php-fpm-81';
        shell_exec($command);

        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = 'PHP-8.1已重启';

        return response()->json($res);
    }

    public function reload()
    {
        $command = 'systemctl reload php-fpm-81';
        shell_exec($command);

        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = 'PHP-8.1已重载';

        return response()->json($res);
    }

    public function getConfig()
    {
        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = @file_get_contents('/www/server/php/81/etc/php.ini');
        return response()->json($res);
    }

    public function saveConfig()
    {
        $res['code'] = 0;
        $res['msg'] = 'success';
        // 获取配置内容
        $config = Request::post('config');
        // 写入配置
        file_put_contents('/www/server/php/81/etc/php.ini', $config);
        // 重载PHP-8.1
        shell_exec('systemctl reload php-fpm-81');
        $res['data'] = 'PHP-8.1主配置已保存';
        return response()->json($res);
    }

    public function load()
    {
        $raw_status = HTTP::get('http://127.0.0.1/phpfpm_81_status')->body();
        /*pool:                 www
process manager:      dynamic
start time:           21/Nov/2022:15:37:00 +0800
start since:          22693
accepted conn:        2
listen queue:         0
max listen queue:     0
listen queue len:     0
idle processes:       19
active processes:     1
total processes:      20
max active processes: 1
max children reached: 0
slow requests:        0*/
        $res['data'][0]['name'] = '应用池';
        // 正则匹配pool
        preg_match('/pool:\s+(.*)/', $raw_status, $matches);
        $res['data'][0]['value'] = $matches[1];
        $res['data'][1]['name'] = '工作模式';
        // 正则匹配process manager
        preg_match('/process manager:\s+(.*)/', $raw_status, $matches);
        $res['data'][1]['value'] = $matches[1];
        $res['data'][2]['name'] = '启动时间';
        // 正则匹配start time
        preg_match('/start time:\s+(.*)/', $raw_status, $matches);
        $res['data'][2]['value'] = $matches[1];
        $res['data'][3]['name'] = '接受连接';
        // 正则匹配accepted conn
        preg_match('/accepted conn:\s+(.*)/', $raw_status, $matches);
        $res['data'][3]['value'] = $matches[1];
        $res['data'][4]['name'] = '监听队列';
        // 正则匹配listen queue
        preg_match('/listen queue:\s+(.*)/', $raw_status, $matches);
        $res['data'][4]['value'] = $matches[1];
        $res['data'][5]['name'] = '最大监听队列';
        // 正则匹配max listen queue
        preg_match('/max listen queue:\s+(.*)/', $raw_status, $matches);
        $res['data'][5]['value'] = $matches[1];
        $res['data'][6]['name'] = '监听队列长度';
        // 正则匹配listen queue len
        preg_match('/listen queue len:\s+(.*)/', $raw_status, $matches);
        $res['data'][6]['value'] = $matches[1];
        $res['data'][7]['name'] = '空闲进程数量';
        // 正则匹配idle processes
        preg_match('/idle processes:\s+(.*)/', $raw_status, $matches);
        $res['data'][7]['value'] = $matches[1];
        $res['data'][8]['name'] = '活动进程数量';
        // 正则匹配active processes
        preg_match('/active processes:\s+(.*)/', $raw_status, $matches);
        $res['data'][8]['value'] = $matches[1];
        $res['data'][9]['name'] = '总进程数量';
        // 正则匹配total processes
        preg_match('/total processes:\s+(.*)/', $raw_status, $matches);
        $res['data'][9]['value'] = $matches[1];
        $res['data'][10]['name'] = '最大活跃进程数量';
        // 正则匹配max active processes
        preg_match('/max active processes:\s+(.*)/', $raw_status, $matches);
        $res['data'][10]['value'] = $matches[1];
        $res['data'][11]['name'] = '达到进程上限次数';
        // 正则匹配max children reached
        preg_match('/max children reached:\s+(.*)/', $raw_status, $matches);
        $res['data'][11]['value'] = $matches[1];
        $res['data'][12]['name'] = '慢请求';
        // 正则匹配slow requests
        preg_match('/slow requests:\s+(.*)/', $raw_status, $matches);
        $res['data'][12]['value'] = $matches[1];

        $res['code'] = 0;
        $res['msg'] = 'success';

        return response()->json($res);
    }

    public function errorLog()
    {
        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = shell_exec('tail -n 100 /www/server/php/81/var/log/php-fpm.log');
        //如果data为换行符，则令返回空
        if ($res['data'] == "\n") {
            $res['data'] = '';
        }
        return response()->json($res);
    }

    public function cleanErrorLog()
    {
        $res['code'] = 0;
        $res['msg'] = 'success';
        shell_exec('echo "" > /www/server/php/81/var/log/php-fpm.log');
        return response()->json($res);
    }

    /**
     * 慢日志
     */
    public function slowLog()
    {
        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = shell_exec('tail -n 100 /www/server/php/81/var/log/slow.log');
        //如果data为换行符，则令返回空
        if ($res['data'] == "\n") {
            $res['data'] = '';
        }
        return response()->json($res);
    }

    /**
     * 清空慢日志
     */
    public function cleanSlowLog()
    {
        $res['code'] = 0;
        $res['msg'] = 'success';
        shell_exec('echo "" > /www/server/php/81/var/log/slow.log');
        return response()->json($res);
    }

}

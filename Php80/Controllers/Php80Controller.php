<?php
/**
 * Name: Php80插件控制器
 * Author:耗子
 * Date: 2022-11-30
 */

namespace Plugins\Php80\Controllers;

use App\Http\Controllers\Controller;

use App\Jobs\ProcessShell;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class Php80Controller extends Controller
{

    public function status(): JsonResponse
    {
        $command = 'systemctl status php-fpm-80';
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

    public function restart(): JsonResponse
    {
        $command = 'systemctl restart php-fpm-80';
        shell_exec($command);

        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = 'PHP-8.0已重启';

        return response()->json($res);
    }

    public function reload(): JsonResponse
    {
        $command = 'systemctl reload php-fpm-80';
        shell_exec($command);

        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = 'PHP-8.0已重载';

        return response()->json($res);
    }

    public function getConfig(): JsonResponse
    {
        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = @file_get_contents('/www/server/php/80/etc/php.ini');
        return response()->json($res);
    }

    public function saveConfig(Request $request): JsonResponse
    {
        $res['code'] = 0;
        $res['msg'] = 'success';
        // 获取配置内容
        $config = $request->input('config');
        // 写入配置
        file_put_contents('/www/server/php/80/etc/php.ini', $config);
        // 重载PHP-8.0
        shell_exec('systemctl reload php-fpm-80');
        $res['data'] = 'PHP-8.0主配置已保存';
        return response()->json($res);
    }

    public function load()
    {
        $raw_status = HTTP::get('http://127.0.0.1/phpfpm_80_status')->body();
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
        $res['data'] = shell_exec('tail -n 100 /www/server/php/80/var/log/php-fpm.log');
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
        shell_exec('echo "" > /www/server/php/80/var/log/php-fpm.log');
        return response()->json($res);
    }

    /**
     * 慢日志
     */
    public function slowLog(): JsonResponse
    {
        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = shell_exec('tail -n 100 /www/server/php/80/var/log/slow.log');
        //如果data为换行符，则令返回空
        if ($res['data'] == "\n") {
            $res['data'] = '';
        }
        return response()->json($res);
    }

    /**
     * 清空慢日志
     */
    public function cleanSlowLog(): JsonResponse
    {
        $res['code'] = 0;
        $res['msg'] = 'success';
        shell_exec('echo "" > /www/server/php/80/var/log/slow.log');
        return response()->json($res);
    }

    /**
     * 获取拓展列表
     */
    public function getExtensionList(): JsonResponse
    {
        // 获取远程拓展列表
        $remoteExtensionList = self::getRemoteExtension();

        // 获取本地拓展列表
        $rawExtensionList = shell_exec('php-80 -m');
        $rawExtensionList = explode("\n", $rawExtensionList);
        $extensionList = array_map(function ($item) {
            if (str_contains($item, '[') || empty($item)) {
                return '';
            }
            return $item;
        }, $rawExtensionList);
        $extensionList = array_flip(array_filter($extensionList));

        // 处理数据
        $data = [];
        foreach ($remoteExtensionList as $k => $extension) {
            $data[$k] = $extension;
            // 去除不需要的字段
            unset($data[$k]['install']);
            unset($data[$k]['uninstall']);
            unset($data[$k]['update']);
            if (isset($extensionList[$extension['slug']])) {
                $data[$k]['control']['installed'] = true;
            } else {
                $data[$k]['control']['installed'] = false;
            }
        }
        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = $data;
        return response()->json($res);
    }

    /**
     * 安装拓展
     */
    public function installExtension(Request $request): JsonResponse
    {
        $slug = $request->input('slug');
        $remoteExtensionList = self::getRemoteExtension();
        $extensionData = [];
        $check = false;

        // 查找拓展
        foreach ($remoteExtensionList as $k => $item) {
            if ($item['slug'] == $slug) {
                $extensionData = $item;
                $check = true;
                break;
            }
        }

        // 检查是否存在
        if (!$check) {
            $res['code'] = 1;
            $res['msg'] = '拓展不存在';
            return response()->json($res);
        }

        // 入库等待安装
        $task = new Task();
        $task->name = '安装PHP-80拓展 '.$extensionData['name'];
        $task->shell = $extensionData['install'];
        $task->status = 'waiting';
        $task->log = '/tmp/'.$extensionData['slug'].'.log';
        $task->save();
        // 塞入队列
        ProcessShell::dispatch($task->id)->delay(1);
        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = '任务添加成功';

        return response()->json($res);
    }

    /**
     * 卸载拓展
     */
    public function uninstallExtension(Request $request): JsonResponse
    {
        $slug = $request->input('slug');
        $remoteExtensionList = self::getRemoteExtension();
        $extensionData = [];
        $check = false;

        // 查找拓展
        foreach ($remoteExtensionList as $k => $item) {
            if ($item['slug'] == $slug) {
                $extensionData = $item;
                $check = true;
                break;
            }
        }

        // 检查是否存在
        if (!$check) {
            $res['code'] = 1;
            $res['msg'] = '拓展不存在';
            return response()->json($res);
        }

        // 入库等待安装
        $task = new Task();
        $task->name = '卸载PHP-80拓展 '.$extensionData['name'];
        $task->shell = $extensionData['uninstall'];
        $task->status = 'waiting';
        $task->log = '/tmp/'.$extensionData['slug'].'.log';
        $task->save();
        // 塞入队列
        ProcessShell::dispatch($task->id)->delay(1);
        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = '任务添加成功';

        return response()->json($res);
    }

    /**
     * 获取远程拓展列表
     */
    private function getRemoteExtension($cache = true)
    {
        // 判断刷新缓存
        if (!$cache) {
            Cache::forget('php80ExtensionList');
        }
        if (!Cache::has('php80ExtensionList')) {
            return Cache::remember('php80ExtensionList', 3600, function () {
                $response = Http::get('https://api.panel.haozi.xyz/api/phpExtension/list', ['version' => '80']);
                // 判断请求是否成功，如果不成功则抛出异常
                if ($response->failed()) {
                    return response()->json(['code' => 1, 'msg' => '获取拓展列表失败']);
                }
                // 判断返回的JSON数据中code是否为0，如果不为0则抛出异常
                if (!$response->json('code') == 0) {
                    return response()->json(['code' => 1, 'msg' => '获取拓展列表失败']);
                }
                return $response->json('data');
            });
        } else {
            // 从缓存中获取access_token
            return Cache::get('php80ExtensionList');
        }
    }
}

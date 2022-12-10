<?php
/**
 * Name: Php80插件控制器
 * Author: 耗子
 * Date: 2022-12-10
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

    /**
     * 版本号
     * @var string
     */
    private string $version = '80';

    /**
     * 获取服务运行状态
     * @return JsonResponse
     */
    public function status(): JsonResponse
    {
        $status = shell_exec('systemctl status php-fpm-'.$this->version.' | grep Active | grep -v grep | awk \'{print $2}\'');
        // 格式化掉换行符
        $status = trim($status);
        if (empty($status)) {
            return response()->json(['code' => 1, 'msg' => '获取服务运行状态失败']);
        }
        if ($status == 'active') {
            $status = 1;
        } else {
            $status = 0;
        }

        // 返回结果
        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = $status;
        return response()->json($res);
    }

    /**
     * 启动服务
     * @return JsonResponse
     */
    public function start(): JsonResponse
    {
        shell_exec('systemctl start php-fpm-'.$this->version);
        $status = shell_exec('systemctl status php-fpm-'.$this->version.' | grep Active | grep -v grep | awk \'{print $2}\'');
        // 格式化掉换行符
        $status = trim($status);
        if (empty($status)) {
            return response()->json(['code' => 1, 'msg' => '获取服务运行状态失败']);
        }
        if ($status != 'active') {
            return response()->json(['code' => 1, 'msg' => '启动服务失败']);
        }

        // 返回结果
        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = $status;
        return response()->json($res);
    }

    /**
     * 停止服务
     * @return JsonResponse
     */
    public function stop(): JsonResponse
    {
        shell_exec('systemctl stop php-fpm-'.$this->version);
        $status = shell_exec('systemctl status php-fpm-'.$this->version.' | grep Active | grep -v grep | awk \'{print $2}\'');
        // 格式化掉换行符
        $status = trim($status);
        if (empty($status)) {
            return response()->json(['code' => 1, 'msg' => '获取服务运行状态失败']);
        }
        if ($status != 'inactive') {
            return response()->json(['code' => 1, 'msg' => '停止服务失败']);
        }

        // 返回结果
        $res['code'] = 0;
        $res['msg'] = 'success';
        return response()->json($res);
    }

    /**
     * 重启服务
     * @return JsonResponse
     */
    public function restart(): JsonResponse
    {
        shell_exec('systemctl restart php-fpm-'.$this->version);
        $status = shell_exec('systemctl status php-fpm-'.$this->version.' | grep Active | grep -v grep | awk \'{print $2}\'');
        // 格式化掉换行符
        $status = trim($status);
        if (empty($status)) {
            return response()->json(['code' => 1, 'msg' => '获取服务运行状态失败']);
        }
        if ($status != 'active') {
            return response()->json(['code' => 1, 'msg' => '重启服务失败']);
        }

        // 返回结果
        $res['code'] = 0;
        $res['msg'] = 'success';
        return response()->json($res);
    }

    /**
     * 重载服务
     * @return JsonResponse
     */
    public function reload(): JsonResponse
    {
        shell_exec('systemctl reload php-fpm-'.$this->version);
        $status = shell_exec('systemctl status php-fpm-'.$this->version.' | grep Active | grep -v grep | awk \'{print $2}\'');
        // 格式化掉换行符
        $status = trim($status);
        if (empty($status)) {
            return response()->json(['code' => 1, 'msg' => '获取服务运行状态失败']);
        }
        if ($status != 'active') {
            return response()->json(['code' => 1, 'msg' => '重载服务失败']);
        }

        // 返回结果
        $res['code'] = 0;
        $res['msg'] = 'success';
        return response()->json($res);
    }

    /**
     * 获取配置文件
     * @return JsonResponse
     */
    public function getConfig(): JsonResponse
    {
        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = @file_get_contents('/www/server/php/'.$this->version.'/etc/php.ini');
        return response()->json($res);
    }

    /**
     * 保存配置文件
     * @param  Request  $request
     * @return JsonResponse
     */
    public function saveConfig(Request $request): JsonResponse
    {
        $res['code'] = 0;
        $res['msg'] = 'success';
        // 获取配置内容
        $config = $request->input('config');
        // 写入配置
        @file_put_contents('/www/server/php/'.$this->version.'/etc/php.ini', $config);
        // 重载PHP
        shell_exec('systemctl reload php-fpm-'.$this->version);
        return response()->json($res);
    }

    /**
     * 获取负载状态
     * @return JsonResponse
     */
    public function load(): JsonResponse
    {
        $status = HTTP::get('http://127.0.0.1/phpfpm_'.$this->version.'_status');
        // 判断状态码
        if ($status->status() != 200) {
            return response()->json(['code' => 1, 'msg' => '获取状态失败']);
        }
        $statusRaw = $status->body();

        $res['data'][0]['name'] = '应用池';
        // 正则匹配pool
        preg_match('/pool:\s+(.*)/', $statusRaw, $matches);
        $res['data'][0]['value'] = $matches[1];
        $res['data'][1]['name'] = '工作模式';
        // 正则匹配process manager
        preg_match('/process manager:\s+(.*)/', $statusRaw, $matches);
        $res['data'][1]['value'] = $matches[1];
        $res['data'][2]['name'] = '启动时间';
        // 正则匹配start time
        preg_match('/start time:\s+(.*)/', $statusRaw, $matches);
        $res['data'][2]['value'] = $matches[1];
        $res['data'][3]['name'] = '接受连接';
        // 正则匹配accepted conn
        preg_match('/accepted conn:\s+(.*)/', $statusRaw, $matches);
        $res['data'][3]['value'] = $matches[1];
        $res['data'][4]['name'] = '监听队列';
        // 正则匹配listen queue
        preg_match('/listen queue:\s+(.*)/', $statusRaw, $matches);
        $res['data'][4]['value'] = $matches[1];
        $res['data'][5]['name'] = '最大监听队列';
        // 正则匹配max listen queue
        preg_match('/max listen queue:\s+(.*)/', $statusRaw, $matches);
        $res['data'][5]['value'] = $matches[1];
        $res['data'][6]['name'] = '监听队列长度';
        // 正则匹配listen queue len
        preg_match('/listen queue len:\s+(.*)/', $statusRaw, $matches);
        $res['data'][6]['value'] = $matches[1];
        $res['data'][7]['name'] = '空闲进程数量';
        // 正则匹配idle processes
        preg_match('/idle processes:\s+(.*)/', $statusRaw, $matches);
        $res['data'][7]['value'] = $matches[1];
        $res['data'][8]['name'] = '活动进程数量';
        // 正则匹配active processes
        preg_match('/active processes:\s+(.*)/', $statusRaw, $matches);
        $res['data'][8]['value'] = $matches[1];
        $res['data'][9]['name'] = '总进程数量';
        // 正则匹配total processes
        preg_match('/total processes:\s+(.*)/', $statusRaw, $matches);
        $res['data'][9]['value'] = $matches[1];
        $res['data'][10]['name'] = '最大活跃进程数量';
        // 正则匹配max active processes
        preg_match('/max active processes:\s+(.*)/', $statusRaw, $matches);
        $res['data'][10]['value'] = $matches[1];
        $res['data'][11]['name'] = '达到进程上限次数';
        // 正则匹配max children reached
        preg_match('/max children reached:\s+(.*)/', $statusRaw, $matches);
        $res['data'][11]['value'] = $matches[1];
        $res['data'][12]['name'] = '慢请求';
        // 正则匹配slow requests
        preg_match('/slow requests:\s+(.*)/', $statusRaw, $matches);
        $res['data'][12]['value'] = $matches[1];

        $res['code'] = 0;
        $res['msg'] = 'success';

        return response()->json($res);
    }

    /**
     * 获取错误日志
     * @return JsonResponse
     */
    public function errorLog(): JsonResponse
    {
        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = shell_exec('tail -n 100 /www/server/php/'.$this->version.'/var/log/php-fpm.log');
        //如果data为换行符，则令返回空
        if ($res['data'] == "\n") {
            $res['data'] = '';
        }
        return response()->json($res);
    }

    /**
     * 清空错误日志
     * @return JsonResponse
     */
    public function cleanErrorLog(): JsonResponse
    {
        $res['code'] = 0;
        $res['msg'] = 'success';
        shell_exec('echo "" > /www/server/php/'.$this->version.'/var/log/php-fpm.log');
        return response()->json($res);
    }

    /**
     * 慢日志
     * @return JsonResponse
     */
    public function slowLog(): JsonResponse
    {
        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = shell_exec('tail -n 100 /www/server/php/'.$this->version.'/var/log/slow.log');
        //如果data为换行符，则令返回空
        if ($res['data'] == "\n") {
            $res['data'] = '';
        }
        return response()->json($res);
    }

    /**
     * 清空慢日志
     * @return JsonResponse
     */
    public function cleanSlowLog(): JsonResponse
    {
        $res['code'] = 0;
        $res['msg'] = 'success';
        shell_exec('echo "" > /www/server/php/'.$this->version.'/var/log/slow.log');
        return response()->json($res);
    }

    /**
     * 获取拓展列表
     * @return JsonResponse
     */
    public function getExtensionList(): JsonResponse
    {
        // 获取远程拓展列表
        $remoteExtensionList = self::getRemoteExtension();

        // 获取本地拓展列表
        $rawExtensionList = shell_exec('php-'.$this->version.' -m');
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
     * @param  Request  $request
     * @return JsonResponse
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
        $task->name = '安装PHP-'.$this->version.'拓展 '.$extensionData['name'];
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
     * @param  Request  $request
     * @return JsonResponse
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
        $task->name = '卸载PHP-'.$this->version.'拓展 '.$extensionData['name'];
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
     * @param  bool  $cache
     * @return array
     */
    private function getRemoteExtension(bool $cache = true): array
    {
        // 判断刷新缓存
        if (!$cache) {
            Cache::forget('php'.$this->version.'ExtensionList');
        }
        if (!Cache::has('php'.$this->version.'ExtensionList')) {
            return Cache::remember('php'.$this->version.'ExtensionList', 3600, function () {
                $response = Http::get('https://api.panel.haozi.xyz/api/phpExtension/list',
                    ['version' => $this->version]);
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
            return Cache::get('php'.$this->version.'ExtensionList');
        }
    }
}

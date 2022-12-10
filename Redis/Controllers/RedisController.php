<?php
/**
 * Name: Redis插件控制器
 * Author: 耗子
 * Date: 2022-12-10
 */

namespace Plugins\Redis\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RedisController extends Controller
{

    /**
     * 获取运行状态
     * @return JsonResponse
     */
    public function status(): JsonResponse
    {
        $status = shell_exec('systemctl status redis | grep Active | grep -v grep | awk \'{print $2}\'');
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
     * 启动
     * @return JsonResponse
     */
    public function start(): JsonResponse
    {
        shell_exec('systemctl start redis');
        $status = shell_exec('systemctl status redis | grep Active | grep -v grep | awk \'{print $2}\'');
        // 格式化掉换行符
        $status = trim($status);
        if (empty($status)) {
            return response()->json(['code' => 1, 'msg' => '获取服务运行状态失败']);
        }
        if ($status != 'active') {
            return response()->json(['code' => 1, 'msg' => '启动服务失败']);
        }

        $res['code'] = 0;
        $res['msg'] = 'success';

        return response()->json($res);
    }

    /**
     * 停止
     * @return JsonResponse
     */
    public function stop(): JsonResponse
    {
        shell_exec('systemctl stop redis');
        $status = shell_exec('systemctl status redis | grep Active | grep -v grep | awk \'{print $2}\'');
        // 格式化掉换行符
        $status = trim($status);
        if (empty($status)) {
            return response()->json(['code' => 1, 'msg' => '获取服务运行状态失败']);
        }
        if ($status != 'inactive') {
            return response()->json(['code' => 1, 'msg' => '停止服务失败']);
        }

        $res['code'] = 0;
        $res['msg'] = 'success';

        return response()->json($res);
    }

    /**
     * 重启
     * @return JsonResponse
     */
    public function restart(): JsonResponse
    {
        shell_exec('systemctl restart redis');
        $status = shell_exec('systemctl status redis | grep Active | grep -v grep | awk \'{print $2}\'');
        // 格式化掉换行符
        $status = trim($status);
        if (empty($status)) {
            return response()->json(['code' => 1, 'msg' => '获取服务运行状态失败']);
        }
        if ($status != 'active') {
            return response()->json(['code' => 1, 'msg' => '重启服务失败']);
        }

        $res['code'] = 0;
        $res['msg'] = 'success';

        return response()->json($res);
    }

    /**
     * 重载
     * @return JsonResponse
     */
    public function reload(): JsonResponse
    {
        shell_exec('systemctl reload redis');
        $status = shell_exec('systemctl status redis | grep Active | grep -v grep | awk \'{print $2}\'');
        // 格式化掉换行符
        $status = trim($status);
        if (empty($status)) {
            return response()->json(['code' => 1, 'msg' => '获取服务运行状态失败']);
        }
        if ($status != 'active') {
            return response()->json(['code' => 1, 'msg' => '重载服务失败']);
        }

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
        if (file_exists('/etc/redis/redis.conf')) {
            $res['data'] = file_get_contents('/etc/redis/redis.conf');
        } else {
            if (file_exists('/etc/redis.conf')) {
                $res['data'] = file_get_contents('/etc/redis.conf');
            } else {
                $res['data'] = '未找到Redis配置文件，Redis 可能已损坏';
            }
        }
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
        if (file_exists('/etc/redis/redis.conf')) {
            file_put_contents('/etc/redis/redis.conf', $config);
        } else {
            if (file_exists('/etc/redis.conf')) {
                file_put_contents('/etc/redis.conf', $config);
            } else {
                $res['code'] = 1;
                $res['msg'] = '未找到Redis配置文件，Redis 可能已损坏';
                return response()->json($res);
            }
        }
        // 重载
        shell_exec('systemctl reload redis');
        $res['data'] = 'Redis主配置已保存';
        return response()->json($res);
    }

    /**
     * 获取负载状态
     * @return JsonResponse
     */
    public function load(): JsonResponse
    {
        $status = shell_exec('systemctl status redis | grep Active | grep -v grep | awk \'{print $2}\'');
        // 格式化掉换行符
        $status = trim($status);
        if (empty($status)) {
            return response()->json(['code' => 1, 'msg' => '获取服务运行状态失败']);
        }
        if ($status != 'active') {
            $res['code'] = 1;
            $res['msg'] = 'Redis 已停止运行';
            return response()->json($res);
        }

        $infoRaw = shell_exec('redis-cli info');
        // 处理数据
        $info = explode(PHP_EOL, $infoRaw);
        $dataRaw = [];
        foreach ($info as $item) {
            $item = explode(':', $item);
            if (count($item) == 2) {
                $dataRaw[trim($item[0])] = trim($item[1]);
            }
        }

        $data[0]['name'] = 'TCP 端口';
        $data[0]['value'] = $dataRaw['tcp_port'];
        $data[1]['name'] = '已运行天数';
        $data[1]['value'] = $dataRaw['uptime_in_days'];
        $data[2]['name'] = '连接的客户端数';
        $data[2]['value'] = $dataRaw['connected_clients'];
        $data[3]['name'] = '已分配的内存总量';
        $data[3]['value'] = $dataRaw['used_memory_human'];
        $data[4]['name'] = '占用内存总量';
        $data[4]['value'] = $dataRaw['used_memory_rss_human'];
        $data[5]['name'] = '占用内存峰值';
        $data[5]['value'] = $dataRaw['used_memory_peak_human'];
        $data[6]['name'] = '内存碎片比率';
        $data[6]['value'] = $dataRaw['mem_fragmentation_ratio'];
        $data[7]['name'] = '运行以来连接过的客户端的总数';
        $data[7]['value'] = $dataRaw['total_connections_received'];
        $data[8]['name'] = '运行以来执行过的命令的总数';
        $data[8]['value'] = $dataRaw['total_commands_processed'];
        $data[9]['name'] = '每秒执行的命令数';
        $data[9]['value'] = $dataRaw['instantaneous_ops_per_sec'];
        $data[10]['name'] = '查找数据库键成功次数';
        $data[10]['value'] = $dataRaw['keyspace_hits'];
        $data[11]['name'] = '查找数据库键失败次数';
        $data[11]['value'] = $dataRaw['keyspace_misses'];
        $data[12]['name'] = '最近一次 fork() 操作耗费的毫秒数';
        $data[12]['value'] = $dataRaw['latest_fork_usec'];

        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = $data;
        return response()->json($res);
    }
}

<?php
/**
 * Name: Redis插件控制器
 * Author:耗子
 * Date: 2022-12-02
 */

namespace Plugins\Redis\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RedisController extends Controller
{

    public function status(): JsonResponse
    {
        $command = 'systemctl status redis';
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

    public function start(): JsonResponse
    {
        $command = 'systemctl start redis';
        shell_exec($command);

        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = 'Redis已启动';

        return response()->json($res);
    }

    public function stop(): JsonResponse
    {
        $command = 'systemctl stop redis';
        shell_exec($command);

        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = 'Redis已停止';

        return response()->json($res);
    }

    public function restart(): JsonResponse
    {
        $command = 'systemctl restart redis';
        shell_exec($command);

        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = 'Redis已重启';

        return response()->json($res);
    }

    public function reload(): JsonResponse
    {
        $command = 'systemctl reload redis';
        shell_exec($command);

        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = 'Redis已重载';

        return response()->json($res);
    }

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

    public function load(): JsonResponse
    {
        $command = 'systemctl status redis';
        $result = shell_exec($command);
        $res['code'] = 0;
        $res['msg'] = 'success';
        if (str_contains($result, 'inactive')) {
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

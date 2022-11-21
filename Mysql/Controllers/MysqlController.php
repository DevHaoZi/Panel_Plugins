<?php
/**
 * Name: Mysql插件控制器
 * Author:耗子
 * Date: 2022-11-21
 */

namespace Plugins\Mysql\Controllers;

use App\Http\Controllers\Controller;

// HTTP
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Request;

// Filesystem
use Illuminate\Filesystem\Filesystem;

class MysqlController extends Controller
{

    public function status()
    {
        $command = 'systemctl status mysqld';
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

    public function start()
    {
        $command = 'systemctl start mysqld';
        shell_exec($command);

        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = 'MySQL已启动';

        return response()->json($res);
    }

    public function stop()
    {
        $command = 'systemctl stop mysqld';
        shell_exec($command);

        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = 'MySQL已停止';

        return response()->json($res);
    }

    public function restart()
    {
        $command = 'systemctl restart mysqld';
        shell_exec($command);

        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = 'MySQL已重启';

        return response()->json($res);
    }

    public function reload()
    {
        $command = 'systemctl reload mysqld';
        shell_exec($command);

        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = 'MySQL已重载';

        return response()->json($res);
    }

    public function getConfig()
    {
        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = @file_get_contents('/etc/my.cnf');
        return response()->json($res);
    }

    public function saveConfig()
    {
        $res['code'] = 1;
        $res['msg'] = '待修复功能';
        // 获取配置内容
        $config = Request::post('config');
        // 备份一份旧配置
        /*shell_exec('cp /etc/my.cnf /etc/my.cnf.bak');
        // 写入配置
        //file_put_contents('/etc/my.cnf', $config);
        // 由于read only，改为使用shell命令
        shell_exec('echo "'.$config.'" > /etc/my.cnf');
        // 重载MySQL
        shell_exec('systemctl reload mysqld');
        $res['data'] = 'MySQL主配置已保存';*/
        return response()->json($res);
    }

    public function load()
    {
        $mysqlRootPassword = Setting::query()->where('name', 'mysql_root_password')->value('value');
        // 判断是否设置了MySQL密码
        if (!$mysqlRootPassword) {
            $res['code'] = 1;
            $res['msg'] = 'MySQL root密码错误';
            return response()->json($res);
        }
        $raw_status = shell_exec('mysqladmin -uroot -p'.$mysqlRootPassword.' extended-status 2>&1');

        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'][0]['name'] = '运行时间';
        // 使用正则匹配Uptime的值
        preg_match('/Uptime\s+\|\s+(\d+)\s+\|/', $raw_status, $matches);
        $res['data'][0]['value'] = $matches[1].'s';
        $res['data'][1]['name'] = '每秒查询';
        // 使用正则匹配Queries的值
        preg_match('/Queries\s+\|\s+(\d+)\s+\|/', $raw_status, $matches);
        $res['data'][1]['value'] = $matches[1];
        $res['data'][2]['name'] = '总连接次数';
        // 使用正则匹配Connections的值
        preg_match('/Connections\s+\|\s+(\d+)\s+\|/', $raw_status, $matches);
        $res['data'][2]['value'] = $matches[1];
        $res['data'][3]['name'] = '每秒事务';
        // 使用正则匹配Com_commit的值
        preg_match('/Com_commit\s+\|\s+(\d+)\s+\|/', $raw_status, $matches);
        $res['data'][3]['value'] = $matches[1];
        $res['data'][4]['name'] = '每秒回滚';
        // 使用正则匹配Com_rollback的值
        preg_match('/Com_rollback\s+\|\s+(\d+)\s+\|/', $raw_status, $matches);
        $res['data'][4]['value'] = $matches[1];
        $res['data'][5]['name'] = '发送';
        // 使用正则匹配Bytes_sent的值
        preg_match('/Bytes_sent\s+\|\s+(\d+)\s+\|/', $raw_status, $matches);
        $res['data'][5]['value'] = $matches[1];
        $res['data'][6]['name'] = '接收';
        // 使用正则匹配Bytes_received的值
        preg_match('/Bytes_received\s+\|\s+(\d+)\s+\|/', $raw_status, $matches);
        $res['data'][6]['value'] = $matches[1];
        $res['data'][7]['name'] = '活动连接数';
        // 使用正则匹配Threads_connected的值
        preg_match('/Threads_connected\s+\|\s+(\d+)\s+\|/', $raw_status, $matches);
        $res['data'][7]['value'] = $matches[1];
        $res['data'][8]['name'] = '峰值连接数';
        // 使用正则匹配Max_used_connections的值
        preg_match('/Max_used_connections\s+\|\s+(\d+)\s+\|/', $raw_status, $matches);
        $res['data'][8]['value'] = $matches[1];
        $res['data'][9]['name'] = '索引命中率';
        // 使用正则匹配Key_read_requests的值
        preg_match('/Key_read_requests\s+\|\s+(\d+)\s+\|/', $raw_status, $matches);
        $key_read_requests = $matches[1];
        // 使用正则匹配Key_reads的值
        preg_match('/Key_reads\s+\|\s+(\d+)\s+\|/', $raw_status, $matches);
        $key_reads = $matches[1];
        $res['data'][9]['value'] = round(($key_read_requests / ($key_reads + $key_read_requests != 0 ? $key_reads + $key_read_requests : 1)) * 100,
                2).'%';
        $res['data'][10]['name'] = 'Innodb索引命中率';
        // 使用正则匹配Innodb_buffer_pool_reads的值
        preg_match('/Innodb_buffer_pool_reads\s+\|\s+(\d+)\s+\|/', $raw_status, $matches);
        $innodb_buffer_pool_reads = $matches[1];
        // 使用正则匹配Innodb_buffer_pool_read_requests的值
        preg_match('/Innodb_buffer_pool_read_requests\s+\|\s+(\d+)\s+\|/', $raw_status, $matches);
        $innodb_buffer_pool_read_requests = $matches[1];
        $res['data'][10]['value'] = round(($innodb_buffer_pool_read_requests / ($innodb_buffer_pool_reads + $innodb_buffer_pool_read_requests != 0 ? $innodb_buffer_pool_reads + $innodb_buffer_pool_read_requests : 1)),
                2).'%';
        $res['data'][11]['name'] = '创建临时表到磁盘';
        // 使用正则匹配Created_tmp_disk_tables的值
        preg_match('/Created_tmp_disk_tables\s+\|\s+(\d+)\s+\|/', $raw_status, $matches);
        $res['data'][11]['value'] = $matches[1];
        $res['data'][12]['name'] = '已打开的表';
        // 使用正则匹配Open_tables的值
        preg_match('/Open_tables\s+\|\s+(\d+)\s+\|/', $raw_status, $matches);
        $res['data'][12]['value'] = $matches[1];
        $res['data'][13]['name'] = '没有使用索引的量';
        // 使用正则匹配Select_full_join的值
        preg_match('/Select_full_join\s+\|\s+(\d+)\s+\|/', $raw_status, $matches);
        $res['data'][13]['value'] = $matches[1];
        $res['data'][14]['name'] = '没有索引的JOIN量';
        // 使用正则匹配Select_full_range_join的值
        preg_match('/Select_full_range_join\s+\|\s+(\d+)\s+\|/', $raw_status, $matches);
        $res['data'][14]['value'] = $matches[1];
        $res['data'][15]['name'] = '没有索引的子查询量';
        // 使用正则匹配Select_range_check的值
        preg_match('/Select_range_check\s+\|\s+(\d+)\s+\|/', $raw_status, $matches);
        $res['data'][15]['value'] = $matches[1];
        $res['data'][16]['name'] = '排序后的合并次数';
        // 使用正则匹配Sort_merge_passes的值
        preg_match('/Sort_merge_passes\s+\|\s+(\d+)\s+\|/', $raw_status, $matches);
        $res['data'][16]['value'] = $matches[1];
        $res['data'][17]['name'] = '锁表次数';
        // 使用正则匹配Table_locks_waited的值
        preg_match('/Table_locks_waited\s+\|\s+(\d+)\s+\|/', $raw_status, $matches);
        $res['data'][17]['value'] = $matches[1];

        return response()->json($res);
    }

    public function errorLog()
    {
        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = shell_exec('tail -n 100 /www/server/mysql/mysql-error.log');
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
        shell_exec('echo "" > /www/server/mysql/mysql-error.log');
        return response()->json($res);
    }

    public function slowLog()
    {
        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = shell_exec('tail -n 100 /www/server/mysql/mysql-slow.log');
        //如果data为换行符，则令返回空
        if ($res['data'] == "\n") {
            $res['data'] = '';
        }
        return response()->json($res);
    }

    public function cleanSlowLog()
    {
        $res['code'] = 0;
        $res['msg'] = 'success';
        shell_exec('echo "" > /www/server/mysql/mysql-slow.log');
        return response()->json($res);
    }

}

<?php
/**
 * Name: Mysql插件控制器
 * Author:耗子
 * Date: 2022-12-04
 */

namespace Plugins\Mysql\Controllers;

use App\Http\Controllers\Controller;

// HTTP
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

// Filesystem
use Illuminate\Filesystem\Filesystem;
use Illuminate\Validation\ValidationException;

class MysqlController extends Controller
{

    public function status(): JsonResponse
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

    public function start(): JsonResponse
    {
        $command = 'systemctl start mysqld';
        shell_exec($command);

        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = 'MySQL已启动';

        return response()->json($res);
    }

    public function stop(): JsonResponse
    {
        $command = 'systemctl stop mysqld';
        shell_exec($command);

        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = 'MySQL已停止';

        return response()->json($res);
    }

    public function restart(): JsonResponse
    {
        $command = 'systemctl restart mysqld';
        shell_exec($command);

        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = 'MySQL已重启';

        return response()->json($res);
    }

    public function reload(): JsonResponse
    {
        $command = 'systemctl reload mysqld';
        shell_exec($command);

        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = 'MySQL已重载';

        return response()->json($res);
    }

    public function getConfig(): JsonResponse
    {
        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = @file_get_contents('/etc/my.cnf');
        return response()->json($res);
    }

    public function saveConfig(Request $request): JsonResponse
    {
        $res['code'] = 0;
        $res['msg'] = 'success';
        // 获取配置内容
        $config = $request->input('config');
        // 备份一份旧配置
        shell_exec('cp /etc/my.cnf /etc/my.cnf.bak');
        // 写入配置
        file_put_contents('/etc/my.cnf', $config);
        // 重载MySQL
        shell_exec('systemctl reload mysqld');
        $res['data'] = 'MySQL主配置已保存';
        return response()->json($res);
    }

    public function load(): JsonResponse
    {
        $mysqlRootPassword = Setting::query()->where('name', 'mysql_root_password')->value('value');
        // 判断是否设置了MySQL密码
        if (!$mysqlRootPassword) {
            $res['code'] = 1;
            $res['msg'] = 'MySQL root密码错误';
            return response()->json($res);
        }
        // 判断MySQL是否已关闭
        $command = 'systemctl status mysqld';
        $result = shell_exec($command);
        if (str_contains($result, 'inactive')) {
            $res['code'] = 1;
            $res['msg'] = 'MySQL 已停止运行';
            return response()->json($res);
        }

        $raw_status = shell_exec('mysqladmin -uroot -p'.$mysqlRootPassword.' extended-status 2>&1');

        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'][0]['name'] = '运行时间';
        // 使用正则匹配Uptime的值
        preg_match('/Uptime\s+\|\s+(\d+)\s+\|/', $raw_status, $matches);
        $res['data'][0]['value'] = $matches[1].'s';
        $res['data'][1]['name'] = '总查询次数';
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
        $res['data'][5]['value'] = formatBytes($matches[1]);
        $res['data'][6]['name'] = '接收';
        // 使用正则匹配Bytes_received的值
        preg_match('/Bytes_received\s+\|\s+(\d+)\s+\|/', $raw_status, $matches);
        $res['data'][6]['value'] = formatBytes($matches[1]);
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

    public function errorLog(): JsonResponse
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

    public function cleanErrorLog(): JsonResponse
    {
        $res['code'] = 0;
        $res['msg'] = 'success';
        shell_exec('echo "" > /www/server/mysql/mysql-error.log');
        return response()->json($res);
    }

    public function slowLog(): JsonResponse
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

    public function cleanSlowLog(): JsonResponse
    {
        $res['code'] = 0;
        $res['msg'] = 'success';
        shell_exec('echo "" > /www/server/mysql/mysql-slow.log');
        return response()->json($res);
    }

    /**
     * 获取配置信息
     */
    public function getSettings(): JsonResponse
    {
        $settings = Setting::query()->where('name', 'like', 'mysql%')->pluck('value', 'name')->toArray();
        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = $settings;
        return response()->json($res);
    }

    /**
     * 保存配置信息
     */
    public function saveSettings(Request $request): JsonResponse
    {
        $newPassword = $request->input('mysql_root_password');
        $oldPassword = Setting::query()->where('name', 'mysql_root_password')->value('value');
        if ($oldPassword != $newPassword) {
            shell_exec('mysql -uroot -p'.$oldPassword.' -e "ALTER USER \'root\'@\'localhost\' IDENTIFIED BY \''.$newPassword.'\';"');
            shell_exec('mysql -uroot -p'.$oldPassword.' -e "flush privileges;"');
            Setting::query()->where('name', 'mysql_root_password')->update(['value' => $newPassword]);
        }
        $res['code'] = 0;
        $res['msg'] = 'success';
        return response()->json($res);
    }

    /**
     * 获取数据库列表
     */
    public function getDatabases(): JsonResponse
    {
        $password = Setting::query()->where('name', 'mysql_root_password')->value('value');
        $rawDatabases = shell_exec("mysql -uroot -p{$password} -e 'show databases'");
        // 格式化数据
        $databases = explode("\n", $rawDatabases);
        array_shift($databases);
        array_pop($databases);
        // 去除系统数据库
        $systemDatabases = ['information_schema', 'mysql', 'performance_schema', 'sys'];
        $databases = array_diff($databases, $systemDatabases);
        // 重新排序
        $databases = array_values($databases);
        // 重新组装数据
        $databases = array_map(function ($database) {
            return ['name' => $database];
        }, $databases);
        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = $databases;
        return response()->json($res);
    }

    /**
     * 添加数据库
     */
    public function addDatabase(Request $request): JsonResponse
    {
        // 消毒数据
        try {
            $credentials = $this->validate($request, [
                'name' => 'required|max:255',
                'username' => 'required|max:255',
                'password' => ['required', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*(_|[^\w])).+$/', 'min:8'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'code' => 1,
                'msg' => '参数错误：'.$e->getMessage(),
                'errors' => $e->errors()
            ], 200);
        }

        $password = Setting::query()->where('name', 'mysql_root_password')->value('value');
        shell_exec("mysql -u root -p".$password." -e \"CREATE DATABASE IF NOT EXISTS ".$credentials['name']." DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;\" 2>&1");
        shell_exec("mysql -u root -p".$password." -e \"CREATE USER '".$credentials['username']."'@'localhost' IDENTIFIED BY '".$credentials['password']."';\"");
        shell_exec("mysql -u root -p".$password." -e \"GRANT ALL PRIVILEGES ON ".$credentials['name'].".* TO '".$credentials['username']."'@'localhost';\"");
        shell_exec("mysql -u root -p".$password." -e \"flush privileges;\"");

        $res['code'] = 0;
        $res['msg'] = 'success';
        return response()->json($res);
    }

    /**
     * 删除数据库
     */
    public function deleteDatabase(Request $request): JsonResponse
    {
        // 消毒数据
        try {
            $credentials = $this->validate($request, [
                'name' => 'required|max:255',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'code' => 1,
                'msg' => '参数错误：'.$e->getMessage(),
                'errors' => $e->errors()
            ], 200);
        }

        $password = Setting::query()->where('name', 'mysql_root_password')->value('value');
        shell_exec("mysql -u root -p".$password." -e \"DROP DATABASE ".$credentials['name'].";\" 2>&1");

        $res['code'] = 0;
        $res['msg'] = 'success';
        return response()->json($res);
    }

    /**
     * 获取备份列表
     */
    public function getBackupList(): JsonResponse
    {
        $backupPath = '/www/backup/mysql';
        // 判断备份目录是否存在
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0644, true);
        }
        $backupFiles = scandir($backupPath);
        $backupFiles = array_diff($backupFiles, ['.', '..']);
        $backupFiles = array_values($backupFiles);
        $backupFiles = array_map(function ($backupFile) {
            return [
                'backup' => $backupFile,
                'size' => formatBytes(filesize('/www/backup/mysql/'.$backupFile)),
            ];
        }, $backupFiles);
        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = $backupFiles;
        return response()->json($res);
    }

    /**
     * 创建备份
     */
    public function createBackup(Request $request): JsonResponse
    {
        // 消毒数据
        try {
            $credentials = $this->validate($request, [
                'name' => 'required|max:255',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'code' => 1,
                'msg' => '参数错误：'.$e->getMessage(),
                'errors' => $e->errors()
            ], 200);
        }

        $password = Setting::query()->where('name', 'mysql_root_password')->value('value');
        $backupPath = '/www/backup/mysql';
        // 判断备份目录是否存在
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0644, true);
        }
        $backupFile = $backupPath.'/'.$credentials['name'].'_'.date('YmdHis').'.sql';
        shell_exec("mysqldump -u root -p".$password." ".$credentials['name']." > ".$backupFile." 2>&1");
        // zip压缩
        shell_exec('zip -r '.$backupFile.'.zip '.$backupFile.' 2>&1');
        // 删除sql文件
        unlink($backupFile);

        $res['code'] = 0;
        $res['msg'] = 'success';
        return response()->json($res);
    }

    /**
     * 上传备份
     */
    public function uploadBackup(Request $request): JsonResponse
    {
        // 消毒数据
        try {
            $credentials = $this->validate($request, [
                'file' => 'required|file',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'code' => 1,
                'msg' => '参数错误：'.$e->getMessage(),
                'errors' => $e->errors()
            ], 200);
        }

        $file = $request->file('file');
        $backupPath = '/www/backup/mysql';

        // 判断备份目录是否存在
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0644, true);
        }
        $backupFile = $backupPath.'/'.$file->getClientOriginalName();
        $file->move($backupPath, $file->getClientOriginalName());

        // 返回文件名
        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = $file->getClientOriginalName();
        return response()->json($res);
    }

    /**
     * 恢复备份
     */
    public function restoreBackup(Request $request): JsonResponse
    {
        // 消毒数据
        try {
            $credentials = $this->validate($request, [
                'name' => 'required|max:255',
                'backup' => 'required|max:255',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'code' => 1,
                'msg' => '参数错误：'.$e->getMessage(),
                'errors' => $e->errors()
            ], 200);
        }

        $password = Setting::query()->where('name', 'mysql_root_password')->value('value');
        $backupPath = '/www/backup/mysql';
        // 判断备份目录是否存在
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0644, true);
        }
        $backupFile = $backupPath.'/'.$credentials['backup'];
        // 判断备份文件是否存在
        if (!is_file($backupFile)) {
            return response()->json([
                'code' => 1,
                'msg' => '备份文件不存在',
            ], 200);
        }
        // 判断备份文件是否经过压缩
        if (pathinfo($backupFile, PATHINFO_EXTENSION) != 'sql') {
            // 解压
            switch (pathinfo($backupFile, PATHINFO_EXTENSION)) {
                case 'zip':
                    // 解压
                    shell_exec('unzip '.$backupFile.' -d '.$backupPath.' 2>&1');
                    // 获取解压后的sql文件
                    $backupFile = substr($backupFile, 0, -4);
                    break;
                case 'gz':
                    // 判断是否是tar.gz
                    if (pathinfo(str_replace('.gz', '', $backupFile), PATHINFO_EXTENSION) == 'tar') {
                        // 解压
                        shell_exec('tar -zxvf '.$backupFile.' -C '.$backupPath.' 2>&1');
                        // 获取解压后的sql文件
                        $backupFile = substr($backupFile, 0, -7);
                    } else {
                        // 解压
                        shell_exec('gzip -d '.$backupFile.' 2>&1');
                        // 获取解压后的sql文件
                        $backupFile = substr($backupFile, 0, -3);
                    }
                    break;
                case 'bz2':
                    // 解压
                    shell_exec('bzip2 -d '.$backupFile.' 2>&1');
                    // 获取解压后的sql文件
                    $backupFile = substr($backupFile, 0, -4);
                    break;
                case 'tar':
                    // 解压
                    shell_exec('tar -xvf '.$backupFile.' -C '.$backupPath.' 2>&1');
                    // 获取解压后的sql文件
                    $backupFile = substr($backupFile, 0, -4);
                    break;
                case 'rar':
                    // 解压
                    shell_exec('unrar x '.$backupFile.' '.$backupPath.' 2>&1');
                    // 获取解压后的sql文件
                    $backupFile = substr($backupFile, 0, -4);
                    break;
                default:
                    return response()->json([
                        'code' => 1,
                        'msg' => '备份文件格式错误',
                    ], 200);
            }
            // 判断解压后的sql文件是否存在
            if (!is_file($backupFile)) {
                return response()->json([
                    'code' => 1,
                    'msg' => '无法被自动识别的压缩文件，请先解压后再上传',
                ], 200);
            }
        }

        shell_exec("mysql -u root -p".$password." ".$credentials['name']." < ".$backupFile." 2>&1");
        $res['code'] = 0;
        $res['msg'] = 'success';
        return response()->json($res);
    }

    /**
     * 删除备份
     */
    public function deleteBackup(Request $request): JsonResponse
    {
        // 消毒数据
        try {
            $credentials = $this->validate($request, [
                'backup' => 'required|max:255',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'code' => 1,
                'msg' => '参数错误：'.$e->getMessage(),
                'errors' => $e->errors()
            ], 200);
        }

        $backupPath = '/www/backup/mysql';
        // 判断备份目录是否存在
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0644, true);
        }
        $backupFile = $backupPath.'/'.$credentials['backup'];
        // 判断备份文件是否存在
        if (!is_file($backupFile)) {
            return response()->json([
                'code' => 1,
                'msg' => '备份文件不存在',
            ], 200);
        }

        unlink($backupFile);
        $res['code'] = 0;
        $res['msg'] = 'success';
        return response()->json($res);
    }

    /**
     * 获取数据库用户列表
     */
    public function getUsers(): JsonResponse
    {
        $password = Setting::query()->where('name', 'mysql_root_password')->value('value');
        $rawUsers = shell_exec("mysql -uroot -p{$password} -e 'select user,host from mysql.user'");
        // 格式化数据
        $users = explode("\n", $rawUsers);
        array_shift($users);
        array_pop($users);
        // 进一步格式化数据
        $users = array_map(function ($user) {
            $user = explode("\t", $user);
            $password = Setting::query()->where('name', 'mysql_root_password')->value('value');
            // 删除系统用户
            if ($user[0] == 'root' || $user[0] == 'mysql.sys' || $user[0] == 'mysql.infoschema' || $user[0] == 'mysql.session') {
                return '';
            }
            // 获取授权信息
            $rawPrivileges = shell_exec("mysql -uroot -p{$password} -e 'show grants for ".$user[0]."@".$user[1]."'");
            // 格式化数据
            $privilegesArr = explode("\n", $rawPrivileges);
            array_shift($privilegesArr);
            array_pop($privilegesArr);
            // 进一步格式化数据
            $privileges = '';
            foreach ($privilegesArr as $k => $privilege) {
                // 截取GRANT 和 TO之间的内容
                $privilege = substr($privilege, 6, strpos($privilege, ' TO') - 6);
                if ($k == 0) {
                    $privileges .= $privilege;
                } else {
                    $privileges .= ' | '.$privilege;
                }

            }
            return [
                'username' => $user[0],
                'host' => $user[1],
                'privileges' => $privileges
            ];
        }, $users);
        // 去除空值
        $users = array_filter($users);
        // 重新排序
        $users = array_values($users);
        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = $users;
        return response()->json($res);
    }

    /**
     * 添加数据库用户
     */
    public function addUser(Request $request): JsonResponse
    {
        // 消毒数据
        try {
            $credentials = $this->validate($request, [
                'username' => 'required|max:255',
                'password' => ['required', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*(_|[^\w])).+$/', 'min:8'],
                'database' => 'required|max:255',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'code' => 1,
                'msg' => '参数错误：'.$e->getMessage(),
                'errors' => $e->errors()
            ], 200);
        }

        $password = Setting::query()->where('name', 'mysql_root_password')->value('value');
        shell_exec("mysql -u root -p".$password." -e \"CREATE USER '".$credentials['username']."'@'localhost' IDENTIFIED BY '".$credentials['password']."';\"");
        shell_exec("mysql -u root -p".$password." -e \"GRANT ALL PRIVILEGES ON ".$credentials['database'].".* TO '".$credentials['username']."'@'localhost';\"");
        shell_exec("mysql -u root -p".$password." -e \"flush privileges;\"");

        $res['code'] = 0;
        $res['msg'] = 'success';
        return response()->json($res);
    }

    /**
     * 删除数据库用户
     */
    public function deleteUser(Request $request): JsonResponse
    {
        // 消毒数据
        try {
            $credentials = $this->validate($request, [
                'username' => 'required|max:255',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'code' => 1,
                'msg' => '参数错误：'.$e->getMessage(),
                'errors' => $e->errors()
            ], 200);
        }

        $password = Setting::query()->where('name', 'mysql_root_password')->value('value');
        shell_exec("mysql -u root -p".$password." -e \"DROP USER '".$credentials['username']."'@'localhost';\"");

        $res['code'] = 0;
        $res['msg'] = 'success';
        return response()->json($res);
    }

    /**
     * 修改数据库用户密码
     */
    public function changePassword(Request $request): JsonResponse
    {
        // 消毒数据
        try {
            $credentials = $this->validate($request, [
                'username' => 'required|max:255',
                'password' => ['required', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*(_|[^\w])).+$/', 'min:8'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'code' => 1,
                'msg' => '参数错误：'.$e->getMessage(),
                'errors' => $e->errors()
            ], 200);
        }

        $password = Setting::query()->where('name', 'mysql_root_password')->value('value');
        shell_exec("mysql -u root -p".$password." -e \"ALTER USER '".$credentials['username']."'@'localhost' IDENTIFIED BY '".$credentials['password']."';\"");

        $res['code'] = 0;
        $res['msg'] = 'success';
        return response()->json($res);
    }

    /**
     * 修改数据库用户授权
     */
    public function changePrivileges(Request $request): JsonResponse
    {
        // 消毒数据
        try {
            $credentials = $this->validate($request, [
                'username' => 'required|max:255',
                'database' => 'required|max:255',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'code' => 1,
                'msg' => '参数错误：'.$e->getMessage(),
                'errors' => $e->errors()
            ], 200);
        }

        // 判断是否在操作root用户
        if ($credentials['username'] == 'root') {
            return response()->json([
                'code' => 1,
                'msg' => '请不要花样做死',
            ], 200);
        }

        $password = Setting::query()->where('name', 'mysql_root_password')->value('value');
        // 撤销权限
        shell_exec("mysql -u root -p".$password." -e \"REVOKE ALL PRIVILEGES ON *.* FROM '".$credentials['username']."'@'localhost';\"");
        // 授权
        shell_exec("mysql -u root -p".$password." -e \"GRANT ALL PRIVILEGES ON ".$credentials['database'].".* TO '".$credentials['username']."'@'localhost';\"");

        $res['code'] = 0;
        $res['msg'] = 'success';
        return response()->json($res);
    }
}

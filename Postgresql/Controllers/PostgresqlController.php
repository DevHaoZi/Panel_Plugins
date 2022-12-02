<?php
/**
 * Name: PostgreSQL插件控制器
 * Author:耗子
 * Date: 2022-11-30
 */

namespace Plugins\Postgresql\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PostgresqlController extends Controller
{

    public function status(): JsonResponse
    {
        $command = 'systemctl status postgresql-15';
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
        $command = 'systemctl start postgresql-15';
        shell_exec($command);

        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = 'PostgreSQL已启动';

        return response()->json($res);
    }

    public function stop(): JsonResponse
    {
        $command = 'systemctl stop postgresql-15';
        shell_exec($command);

        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = 'PostgreSQL已停止';

        return response()->json($res);
    }

    public function restart(): JsonResponse
    {
        $command = 'systemctl restart postgresql-15';
        shell_exec($command);

        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = 'PostgreSQL已重启';

        return response()->json($res);
    }

    public function reload(): JsonResponse
    {
        $command = 'systemctl reload postgresql-15';
        shell_exec($command);

        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = 'PostgreSQL已重载';

        return response()->json($res);
    }

    public function getConfig(): JsonResponse
    {
        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = @file_get_contents('/www/server/postgresql/15/postgresql.conf');
        return response()->json($res);
    }

    public function getUserConfig(): JsonResponse
    {
        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = @file_get_contents('/www/server/postgresql/15/pg_hba.conf');
        return response()->json($res);
    }

    public function saveConfig(Request $request): JsonResponse
    {
        $res['code'] = 0;
        $res['msg'] = 'success';
        // 获取配置内容
        $config = $request->input('config');
        // 备份一份旧配置
        shell_exec('cp /www/server/postgresql/15/postgresql.conf /www/server/postgresql/15/postgresql.conf.bak');
        // 写入配置
        file_put_contents('/www/server/postgresql/15/postgresql.conf', $config);
        // 重载
        shell_exec('systemctl reload postgresql-15');
        $res['data'] = 'PostgreSQL主配置已保存';
        return response()->json($res);
    }

    public function saveUserConfig(Request $request): JsonResponse
    {
        $res['code'] = 0;
        $res['msg'] = 'success';
        // 获取配置内容
        $config = $request->input('config');
        // 备份一份旧配置
        shell_exec('cp /www/server/postgresql/15/pg_hba.conf /www/server/postgresql/15/pg_hba.conf.bak');
        // 写入配置
        file_put_contents('/www/server/postgresql/15/pg_hba.conf', $config);
        // 重载
        shell_exec('systemctl reload postgresql-15');
        $res['data'] = 'PostgreSQL用户配置已保存';
        return response()->json($res);
    }

    public function load(): JsonResponse
    {
        // 判断PostgreSQL是否已关闭
        $command = 'systemctl status postgresql-15';
        $result = shell_exec($command);
        if (str_contains($result, 'inactive')) {
            $res['code'] = 1;
            $res['msg'] = 'PostgreSQL 已停止运行';
            return response()->json($res);
        }
        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'][0]['name'] = '启动时间';
        $res['data'][0]['value'] = Carbon::create(shell_exec('echo "select pg_postmaster_start_time();"|su - postgres -c "psql"|sed -n 3p'))->toDateTimeString();
        $res['data'][1]['name'] = '进程PID';
        $res['data'][1]['value'] = shell_exec('echo "select pg_backend_pid();"|su - postgres -c "psql"|sed -n 3p');
        $res['data'][2]['name'] = '进程数';
        $res['data'][2]['value'] = shell_exec('ps aux | grep postgres | grep -v grep | wc -l');
        $res['data'][3]['name'] = '总连接数';
        $res['data'][3]['value'] = shell_exec('echo "SELECT count(*) FROM pg_stat_activity WHERE NOT pid=pg_backend_pid();"|su - postgres -c "psql"|sed -n 3p');
        $res['data'][4]['name'] = '空间占用';
        $res['data'][4]['value'] = shell_exec('echo "select pg_size_pretty(pg_database_size(\'postgres\'));"|su - postgres -c "psql"|sed -n 3p');

        return response()->json($res);
    }

    public function log(): JsonResponse
    {
        $res['code'] = 0;
        $res['msg'] = 'success';
        // 获取今天星期几简称
        $week = Carbon::now()->format('D');
        $res['data'] = shell_exec('tail -n 100 /www/server/postgresql/15/log/postgresql-'.$week.'.log');
        //如果data为换行符，则令返回空
        if ($res['data'] == "\n") {
            $res['data'] = '';
        }
        return response()->json($res);
    }

    /**
     * 获取数据库列表
     */
    public function getDatabases(): JsonResponse
    {
        $databases = shell_exec('echo "\l"|su - postgres -c "psql"');
        // 处理数据
        $databases = explode(PHP_EOL, $databases);
        $databases = array_slice($databases, 3, -3);
        $databases = array_map(function ($item) {
            $item = explode('|', $item);
            $item = array_map('trim', $item);
            if (empty($item[0])) {
                return '';
            }
            return [
                'name' => $item[0],
                'owner' => $item[1],
                'encoding' => $item[2],
            ];
        }, (array) $databases);
        // 过滤空值
        $databases = array_filter($databases);
        // 重新排序
        $databases = array_values((array) $databases);
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

        shell_exec('echo "CREATE DATABASE '.$credentials['name'].';"|su - postgres -c "psql"');
        shell_exec('echo "CREATE USER '.$credentials['username'].' WITH PASSWORD \''.$credentials['password'].'\';"|su - postgres -c "psql"');
        shell_exec('echo "GRANT ALL PRIVILEGES ON DATABASE '.$credentials['name'].' TO '.$credentials['username'].';"|su - postgres -c "psql"');

        // 写入用户配置
        shell_exec('echo "host    '.$credentials['name'].'    '.$credentials['username'].'    127.0.0.1/32    scram-sha-256" >> /www/server/postgresql/15/pg_hba.conf');

        // 重载
        shell_exec('systemctl reload postgresql-15');

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

        $del = shell_exec('echo "DROP DATABASE '.$credentials['name'].';"|su - postgres -c "psql" 2>&1');
        // 判断是否删除成功
        if (str_contains($del, 'ERROR')) {
            return response()->json([
                'code' => 1,
                'msg' => '删除失败：'.$del,
            ], 200);
        }
        $res['code'] = 0;
        $res['msg'] = 'success';
        return response()->json($res);
    }

    /**
     * 获取备份列表
     */
    public function getBackupList(): JsonResponse
    {
        $backupPath = '/www/backup/postgresql';
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
                'size' => formatBytes(filesize('/www/backup/postgresql/'.$backupFile)),
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

        $backupPath = '/www/backup/postgresql';
        // 判断备份目录是否存在
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0644, true);
        }
        $backupFile = $backupPath.'/'.$credentials['name'].'_'.date('YmdHis').'.sql';
        shell_exec('su - postgres -c "pg_dump '.$credentials['name'].'" > '.$backupFile.' 2>&1');

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
        $backupPath = '/www/backup/postgresql';

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

        $backupPath = '/www/backup/postgresql';
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

        shell_exec('su - postgres -c "psql '.$credentials['name'].'" < '.$backupFile.' 2>&1');
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

        $backupPath = '/www/backup/postgresql';
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
        $databases = shell_exec('echo "\du"|su - postgres -c "psql"');
        // 处理数据
        $databases = explode(PHP_EOL, $databases);
        $databases = array_slice($databases, 3, -2);
        $databases = array_map(function ($item) {
            $item = explode('|', $item);
            $item = array_map('trim', $item);
            return [
                'username' => $item[0],
                'role' => $item[1],
            ];
        }, (array) $databases);
        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = $databases;
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

        shell_exec('echo "CREATE USER '.$credentials['username'].' WITH PASSWORD \''.$credentials['password'].'\';"|su - postgres -c "psql"');
        shell_exec('echo "GRANT ALL PRIVILEGES ON DATABASE '.$credentials['database'].' TO '.$credentials['username'].'; "|su - postgres -c "psql"');

        // 写入用户配置
        shell_exec('echo "host    '.$credentials['database'].'    '.$credentials['username'].'    127.0.0.1/32    scram-sha-256" >> /www/server/postgresql/15/pg_hba.conf');
        // 重载
        shell_exec('systemctl reload postgresql-15');

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

        $del = shell_exec('echo "DROP USER '.$credentials['username'].'; "|su - postgres -c "psql" 2>&1');
        // 判断是否删除成功
        if (str_contains($del, 'ERROR')) {
            return response()->json([
                'code' => 1,
                'msg' => '删除失败：'.$del,
            ], 200);
        }
        // 删除用户配置
        shell_exec('sed -i "/'.$credentials['username'].'/d" /www/server/postgresql/15/pg_hba.conf');
        // 重载
        shell_exec('systemctl reload postgresql-15');

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

        shell_exec('echo "ALTER USER '.$credentials['username'].' WITH PASSWORD \''.$credentials['password'].'\';"|su - postgres -c "psql"');

        $res['code'] = 0;
        $res['msg'] = 'success';
        return response()->json($res);
    }
}

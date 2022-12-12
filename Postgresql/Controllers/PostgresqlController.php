<?php
/**
 * Name: PostgreSQL插件控制器
 * Author: 耗子
 * Date: 2022-12-09
 */

namespace Plugins\Postgresql\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PostgresqlController extends Controller
{

    /**
     * 获取运行状态
     * @return JsonResponse
     */
    public function status(): JsonResponse
    {
        $status = shell_exec('systemctl status postgresql-15 | grep Active | grep -v grep | awk \'{print $2}\'');
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
        shell_exec('systemctl start postgresql-15');
        $status = shell_exec('systemctl status postgresql-15 | grep Active | grep -v grep | awk \'{print $2}\'');
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
        shell_exec('systemctl stop postgresql-15');
        $status = shell_exec('systemctl status postgresql-15 | grep Active | grep -v grep | awk \'{print $2}\'');
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
        shell_exec('systemctl restart postgresql-15');
        $status = shell_exec('systemctl status postgresql-15 | grep Active | grep -v grep | awk \'{print $2}\'');
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
        shell_exec('systemctl reload postgresql-15');
        $status = shell_exec('systemctl status postgresql-15 | grep Active | grep -v grep | awk \'{print $2}\'');
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
        $res['data'] = @file_get_contents('/www/server/postgresql/15/postgresql.conf');
        return response()->json($res);
    }

    /**
     * 获取用户配置文件
     * @return JsonResponse
     */
    public function getUserConfig(): JsonResponse
    {
        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = @file_get_contents('/www/server/postgresql/15/pg_hba.conf');
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
        // 备份一份旧配置
        shell_exec('cp /www/server/postgresql/15/postgresql.conf /www/server/postgresql/15/postgresql.conf.bak');
        // 写入配置
        file_put_contents('/www/server/postgresql/15/postgresql.conf', $config);
        // 重载
        shell_exec('systemctl reload postgresql-15');
        $res['data'] = 'PostgreSQL主配置已保存';
        return response()->json($res);
    }

    /**
     * 保存用户配置文件
     * @param  Request  $request
     * @return JsonResponse
     */
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

    /**
     * 获取负载状态
     * @return JsonResponse
     */
    public function load(): JsonResponse
    {
        // 判断PostgreSQL是否启动
        shell_exec('systemctl status postgresql-15');
        $status = shell_exec('systemctl status postgresql-15 | grep Active | grep -v grep | awk \'{print $2}\'');
        // 格式化掉换行符
        $status = trim($status);
        if (empty($status)) {
            return response()->json(['code' => 1, 'msg' => '获取服务运行状态失败']);
        }
        if ($status != 'active') {
            return response()->json(['code' => 1, 'msg' => 'PostgreSQL服务未启动']);
        }
        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'][0]['name'] = '启动时间';
        $res['data'][0]['value'] = Carbon::create(shell_exec('echo "select pg_postmaster_start_time();"|su - postgres -c "psql"|sed -n 3p'))->toDateTimeString();
        $res['data'][1]['name'] = '进程PID';
        $res['data'][1]['value'] = trim(shell_exec('echo "select pg_backend_pid();"|su - postgres -c "psql"|sed -n 3p'));
        $res['data'][2]['name'] = '进程数';
        $res['data'][2]['value'] = trim(shell_exec('ps aux | grep postgres | grep -v grep | wc -l'));
        $res['data'][3]['name'] = '总连接数';
        $res['data'][3]['value'] = trim(shell_exec('echo "SELECT count(*) FROM pg_stat_activity WHERE NOT pid=pg_backend_pid();"|su - postgres -c "psql"|sed -n 3p'));
        $res['data'][4]['name'] = '空间占用';
        $res['data'][4]['value'] = trim(shell_exec('echo "select pg_size_pretty(pg_database_size(\'postgres\'));"|su - postgres -c "psql"|sed -n 3p'));

        return response()->json($res);
    }

    /**
     * 获取日志
     * @return JsonResponse
     */
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
     * @return JsonResponse
     */
    public function getDatabases(): JsonResponse
    {
        $databases = shell_exec('echo "\l"|su - postgres -c "psql" 2>&1');
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
     * @param  Request  $request
     * @return JsonResponse
     */
    public function addDatabase(Request $request): JsonResponse
    {
        // 消毒数据
        try {
            $credentials = $this->validate($request, [
                'name' => ['required', 'max:255', 'regex:/^[a-zA-Z][a-zA-Z0-9_]+$/'],
                'username' => ['required', 'max:255', 'regex:/^[a-zA-Z][a-zA-Z0-9_]+$/'],
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
        $userConfig = 'host    '.$credentials['name'].'    '.$credentials['username'].'    127.0.0.1/32    scram-sha-256';
        file_put_contents('/www/server/postgresql/15/pg_hba.conf', PHP_EOL.$userConfig, FILE_APPEND);

        // 重载
        shell_exec('systemctl reload postgresql-15');

        $res['code'] = 0;
        $res['msg'] = 'success';
        return response()->json($res);
    }

    /**
     * 删除数据库
     * @param  Request  $request
     * @return JsonResponse
     */
    public function deleteDatabase(Request $request): JsonResponse
    {
        // 消毒数据
        try {
            $credentials = $this->validate($request, [
                'name' => ['required', 'max:255', 'regex:/^[a-zA-Z][a-zA-Z0-9_]+$/'],
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
     * @return JsonResponse
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
     * @param  Request  $request
     * @return JsonResponse
     */
    public function createBackup(Request $request): JsonResponse
    {
        // 消毒数据
        try {
            $credentials = $this->validate($request, [
                'name' => ['required', 'max:255', 'regex:/^[a-zA-Z][a-zA-Z0-9_]+$/'],
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
        // zip压缩
        shell_exec('zip -r '.$backupFile.'.zip '.escapeshellarg($backupFile).' 2>&1');
        // 删除sql文件
        unlink($backupFile);

        $res['code'] = 0;
        $res['msg'] = 'success';
        return response()->json($res);
    }

    /**
     * 上传备份
     * @param  Request  $request
     * @return JsonResponse
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
     * @param  Request  $request
     * @return JsonResponse
     */
    public function restoreBackup(Request $request): JsonResponse
    {
        // 消毒数据
        try {
            $credentials = $this->validate($request, [
                'name' => ['required', 'max:255', 'regex:/^[a-zA-Z][a-zA-Z0-9_]+$/'],
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
        // 判断备份文件是否经过压缩
        if (pathinfo($backupFile, PATHINFO_EXTENSION) != 'sql') {
            // 解压
            switch (pathinfo($backupFile, PATHINFO_EXTENSION)) {
                case 'zip':
                    // 解压
                    shell_exec('unzip -o '.escapeshellarg($backupFile).' -d '.$backupPath.' 2>&1');
                    // 获取解压后的sql文件
                    $backupFile = substr($backupFile, 0, -4);
                    break;
                case 'gz':
                    // 判断是否是tar.gz
                    if (pathinfo(str_replace('.gz', '', $backupFile), PATHINFO_EXTENSION) == 'tar') {
                        // 解压
                        shell_exec('tar -zxvf '.escapeshellarg($backupFile).' -C '.$backupPath.' 2>&1');
                        // 获取解压后的sql文件
                        $backupFile = substr($backupFile, 0, -7);
                    } else {
                        // 解压
                        shell_exec('gzip -d '.escapeshellarg($backupFile).' 2>&1');
                        // 获取解压后的sql文件
                        $backupFile = substr($backupFile, 0, -3);
                    }
                    break;
                case 'bz2':
                    // 解压
                    shell_exec('bzip2 -d '.escapeshellarg($backupFile).' 2>&1');
                    // 获取解压后的sql文件
                    $backupFile = substr($backupFile, 0, -4);
                    break;
                case 'tar':
                    // 解压
                    shell_exec('tar -xvf '.escapeshellarg($backupFile).' -C '.$backupPath.' 2>&1');
                    // 获取解压后的sql文件
                    $backupFile = substr($backupFile, 0, -4);
                    break;
                case 'rar':
                    // 解压
                    shell_exec('unrar x '.escapeshellarg($backupFile).' '.$backupPath.' 2>&1');
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

        shell_exec('su - postgres -c "psql '.$credentials['name'].'" < '.escapeshellarg($backupFile).' 2>&1');
        $res['code'] = 0;
        $res['msg'] = 'success';
        return response()->json($res);
    }

    /**
     * 删除备份
     * @param  Request  $request
     * @return JsonResponse
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

        @unlink($backupFile);
        $res['code'] = 0;
        $res['msg'] = 'success';
        return response()->json($res);
    }

    /**
     * 获取数据库用户列表
     * @return JsonResponse
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
     * @param  Request  $request
     * @return JsonResponse
     */
    public function addUser(Request $request): JsonResponse
    {
        // 消毒数据
        try {
            $credentials = $this->validate($request, [
                'username' => ['required', 'max:255', 'regex:/^[a-zA-Z][a-zA-Z0-9_]+$/'],
                'password' => ['required', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*(_|[^\w])).+$/', 'min:8'],
                'database' => ['required', 'max:255', 'regex:/^[a-zA-Z][a-zA-Z0-9_]+$/'],
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
        $userConfig = 'host    '.$credentials['database'].'    '.$credentials['username'].'    127.0.0.1/32    scram-sha-256';
        file_put_contents('/www/server/postgresql/15/pg_hba.conf', PHP_EOL.$userConfig, FILE_APPEND);
        // 重载
        shell_exec('systemctl reload postgresql-15');

        $res['code'] = 0;
        $res['msg'] = 'success';
        return response()->json($res);
    }

    /**
     * 删除数据库用户
     * @param  Request  $request
     * @return JsonResponse
     */
    public function deleteUser(Request $request): JsonResponse
    {
        // 消毒数据
        try {
            $credentials = $this->validate($request, [
                'username' => ['required', 'max:255', 'regex:/^[a-zA-Z][a-zA-Z0-9_]+$/'],
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
     * @param  Request  $request
     * @return JsonResponse
     */
    public function changePassword(Request $request): JsonResponse
    {
        // 消毒数据
        try {
            $credentials = $this->validate($request, [
                'username' => ['required', 'max:255', 'regex:/^[a-zA-Z][a-zA-Z0-9_]+$/'],
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

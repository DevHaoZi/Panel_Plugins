<?php
/**
 * Name: Pure-Ftpd插件控制器
 * Author: 耗子
 * Date: 2022-12-07
 */

namespace Plugins\PureFtpd\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PureFtpdController extends Controller
{

    /**
     * 获取用户列表
     * @param  Request  $request
     * @return JsonResponse
     */
    public function getUserList(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);
        $page = $request->input('page', 1);

        $userRaw = shell_exec('pure-pw list');
        $users = [];
        if (!empty($userRaw)) {
            $userRaw = explode(PHP_EOL, $userRaw);
            // 去除最后一个空行
            array_pop($userRaw);
            $users = array_map(function ($item) {
                preg_match_all('/(\S+)\s+(\S+)/', $item, $matches);
                return [
                    'username' => $matches[1][0],
                    'path' => str_replace('/./', '/', $matches[2][0]),
                ];
            }, $userRaw);
        }

        // 分页
        $total = count($users);
        $users = array_slice($users, ($page - 1) * $limit, $limit);

        $data['code'] = 0;
        $data['msg'] = 'success';
        $data['count'] = $total;
        $data['data'] = $users;
        return response()->json($data);
    }

    /**
     * 添加用户
     * @param  Request  $request
     * @return JsonResponse
     */
    public function addUser(Request $request): JsonResponse
    {
        // 消毒
        try {
            $credentials = $this->validate($request, [
                'username' => 'required',
                'password' => 'required|min:6',
                'path' => 'required',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['code' => 1, 'msg' => $e->getMessage()]);
        }

        $username = $credentials['username'];
        $password = $credentials['password'];
        $path = $credentials['path'];
        if (!str_starts_with($path, '/')) {
            $path = '/'.$path;
        }
        if (!is_dir($path)) {
            return response()->json(['code' => 1, 'msg' => '目录不存在']);
        }

        shell_exec('chown -R www:www '.escapeshellarg($path));
        shell_exec('echo '.escapeshellarg($password.PHP_EOL.$password).' | pure-pw useradd '.escapeshellarg($username).' -u www -d '.escapeshellarg($path));
        shell_exec('pure-pw mkdb');

        // 返回结果
        $res['code'] = 0;
        $res['msg'] = '添加成功';
        return response()->json($res);
    }

    /**
     * 删除用户
     * @param  Request  $request
     * @return JsonResponse
     */
    public function deleteUser(Request $request): JsonResponse
    {
        // 消毒
        try {
            $credentials = $this->validate($request, [
                'username' => 'required',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['code' => 1, 'msg' => $e->getMessage()]);
        }

        $username = $credentials['username'];

        shell_exec('pure-pw userdel '.escapeshellarg($username));
        shell_exec('pure-pw mkdb');

        // 返回结果
        $res['code'] = 0;
        $res['msg'] = '删除成功';
        return response()->json($res);
    }

    /**
     * 修改用户密码
     * @param  Request  $request
     * @return JsonResponse
     */
    public function changePassword(Request $request): JsonResponse
    {
        // 消毒
        try {
            $credentials = $this->validate($request, [
                'username' => 'required',
                'password' => 'required|min:6',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['code' => 1, 'msg' => $e->getMessage()]);
        }

        $username = $credentials['username'];
        $password = $credentials['password'];

        shell_exec('echo '.escapeshellarg($password.PHP_EOL.$password).' | pure-pw passwd '.escapeshellarg($username));
        shell_exec('pure-pw mkdb');

        // 返回结果
        $res['code'] = 0;
        $res['msg'] = '修改成功';
        return response()->json($res);
    }

    /**
     * 获取pure-ftpd端口
     * @return JsonResponse
     */
    public function getPort(): JsonResponse
    {
        $port = shell_exec('cat /etc/pure-ftpd/pure-ftpd.conf | grep "Bind" | awk \'{print $2}\' | awk -F "," \'{print $2}\'');
        if (empty($port)) {
            return response()->json(['code' => 1, 'msg' => 'pure-ftpd 端口获取失败，可能已损坏']);
        }
        return response()->json(['code' => 0, 'msg' => 'success', 'data' => $port]);
    }

    /**
     * 设置pure-ftpd端口
     * @param  Request  $request
     * @return JsonResponse
     */
    public function setPort(Request $request): JsonResponse
    {
        // 消毒
        try {
            $credentials = $this->validate($request, [
                'port' => 'required|integer|min:1|max:65535',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['code' => 1, 'msg' => $e->getMessage()]);
        }

        // 设置端口
        $port = $credentials['port'];
        shell_exec('sed -i "s/Bind.*/Bind 0.0.0.0,'.$port.'/g" /etc/pure-ftpd/pure-ftpd.conf');
        // 防火墙放行
        shell_exec('firewall-cmd --zone=public --add-port='.$port.'/tcp --permanent');
        shell_exec('firewall-cmd --reload');
        // 重启服务
        shell_exec('service pure-ftpd restart');
        // 返回结果
        $res['code'] = 0;
        $res['msg'] = 'success';
        return response()->json($res);
    }

    /**
     * 获取运行状态
     * @return JsonResponse
     */
    public function status(): JsonResponse
    {
        $status = shell_exec('systemctl status pure-ftpd | grep Active | grep -v grep | awk \'{print $2}\'');
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
        shell_exec('systemctl start pure-ftpd');
        $status = shell_exec('systemctl status pure-ftpd | grep Active | grep -v grep | awk \'{print $2}\'');
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
     * 停止
     * @return JsonResponse
     */
    public function stop(): JsonResponse
    {
        shell_exec('systemctl stop pure-ftpd');
        $status = shell_exec('systemctl status pure-ftpd | grep Active | grep -v grep | awk \'{print $2}\'');
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
     * 重启
     * @return JsonResponse
     */
    public function restart(): JsonResponse
    {
        shell_exec('systemctl restart pure-ftpd');
        $status = shell_exec('systemctl status pure-ftpd | grep Active | grep -v grep | awk \'{print $2}\'');
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
     * 重载
     * @return JsonResponse
     */
    public function reload(): JsonResponse
    {
        shell_exec('systemctl reload pure-ftpd');
        $status = shell_exec('systemctl status pure-ftpd | grep Active | grep -v grep | awk \'{print $2}\'');
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
}

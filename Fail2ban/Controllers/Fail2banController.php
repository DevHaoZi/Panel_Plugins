<?php
/**
 * Name: Fail2ban插件控制器
 * Author: 耗子
 * Date: 2022-12-08
 */

namespace Plugins\Fail2ban\Controllers;

use App\Http\Controllers\Controller;

use App\Models\Website;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class Fail2banController extends Controller
{

    /**
     * 获取规则列表
     * @param  Request  $request
     * @return JsonResponse
     */
    public function getList(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);
        $page = $request->input('page', 1);

        $jailRaw = @file_get_contents('/etc/fail2ban/jail.local');
        if (empty($jailRaw)) {
            return response()->json(['code' => 1, 'msg' => '获取Fail2ban规则列表失败，Fail2ban可能已损坏']);
        }
        $jails = [];
        // 正则匹配出每个规则
        preg_match_all('/\[(.*?)]/', $jailRaw, $jails);
        // 判断是否有规则
        if (empty($jails[1])) {
            return response()->json(['code' => 1, 'msg' => 'Fail2ban规则为空']);
        }
        // 除掉第一个默认规则
        array_shift($jails[1]);
        $jails = array_values($jails[1]);
        // 取每个规则的配置
        $jailsConfig = [];
        foreach ($jails as $k => $jail) {
            $jailsConfig[$k]['name'] = $jail;
            $jailConfig = cut('# '.$jail.'-START'.PHP_EOL, PHP_EOL.'# '.$jail.'-END', $jailRaw);
            $jailConfig = explode(PHP_EOL, $jailConfig);
            unset($jailConfig[0]);// 删除第一个名称行
            foreach ($jailConfig as $value) {
                $value = trim($value);
                if (!empty($value)) {
                    $arr = explode('=', $value);
                    if (count($arr) == 2) {
                        $jailsConfig[$k][trim($arr[0])] = trim($arr[1]);
                    }
                }
            }
        }

        // 分页
        $total = count($jailsConfig);
        $rules = array_slice($jailsConfig, ($page - 1) * $limit, $limit);

        $data['code'] = 0;
        $data['msg'] = 'success';
        $data['count'] = $total;
        $data['data'] = $rules;
        return response()->json($data);
    }

    /**
     * 添加规则
     * @param  Request  $request
     * @return JsonResponse
     */
    public function addRule(Request $request): JsonResponse
    {
        // 消毒
        try {
            $credentials = $this->validate($request, [
                'name' => 'required|string',
                'type' => 'required|in:website,service',
                'maxretry' => 'required|integer',
                'findtime' => 'required|integer',
                'bantime' => 'required|integer',
                'website_mode' => 'required_if:type,website|in:cc,path',
                'website_path' => 'required_if:type,website',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['code' => 1, 'msg' => $e->getMessage()]);
        }

        // 检查规则是否存在
        $jailRaw = @file_get_contents('/etc/fail2ban/jail.local');
        if (str_contains($jailRaw, '['.$credentials['name'].']') || (str_contains($jailRaw,
                    '['.$credentials['name'].'-cc]') && $credentials['website_mode'] == 'cc') || (str_contains($jailRaw,
                    '['.$credentials['name'].'-path]') && $credentials['website_mode'] == 'path')) {
            return response()->json(['code' => 1, 'msg' => '规则已存在']);
        }

        // 判断类型
        if ($credentials['type'] == 'website') {
            // 检查网站是否存在
            $website = Website::query()->where('name', $credentials['name'])->first();
            if (empty($website)) {
                return response()->json(['code' => 1, 'msg' => '网站不存在']);
            }
            // 从网站配置中获取日志路径
            $nginxConfig = @file_get_contents('/www/server/vhost/'.$website->name.'.conf');
            if (empty($nginxConfig)) {
                return response()->json(['code' => 1, 'msg' => '获取网站配置文件失败，请检查OpenResty配置']);
            }
            $logPath = '/www/wwwlogs/'.$credentials['name'].'.log';
            // 如果目录不是以/开头，则加上
            if (!str_starts_with($credentials['website_path'], '/')) {
                $credentials['website_path'] = '/'.$credentials['website_path'];
            }
            // 取网站端口
            preg_match_all('/listen\s+(.*?);/', $nginxConfig, $ports);
            if (empty($ports[1][0])) {
                return response()->json(['code' => 1, 'msg' => '获取网站端口失败，请检查OpenResty配置']);
            }
            $port = implode(',', array_map(function ($item) {
                if (is_numeric($item)) {
                    return $item;
                } else {
                    return explode(' ', $item)[0];
                }
            }, $ports[1]));
            $rule = PHP_EOL.<<<EOF
# $credentials[name]-$credentials[website_mode]-START
[$credentials[name]-$credentials[website_mode]]
enabled = true
filter = haozi-$credentials[name]-$credentials[website_mode]
port = $port
maxretry = $credentials[maxretry]
findtime = $credentials[findtime]
bantime = $credentials[bantime]
action = %(action_mwl)s
logpath = $logPath
# $credentials[name]-$credentials[website_mode]-END
EOF;
            shell_exec('echo "'.$rule.'" >> /etc/fail2ban/jail.local');
            if ($credentials['website_mode'] == 'cc') {
                $filter = <<<EOF
[Definition]
failregex = ^<HOST>\s-.*HTTP/.*$
ignoreregex =
EOF;
            } else {
                $filter = <<<EOF
[Definition]
failregex = ^<HOST>\s-.*\s$credentials[website_path].*HTTP/.*$
ignoreregex =
EOF;
            }
            file_put_contents('/etc/fail2ban/filter.d/haozi-'.$credentials['name'].'-'.$credentials['website_mode'].'.conf',
                $filter);
        } else {
            // 服务
            switch ($credentials['name']) {
                case 'ssh':
                    $logPath = '/var/log/secure';
                    $filter = 'sshd';
                    $port = trim(shell_exec("cat /etc/ssh/sshd_config | grep 'Port ' | awk '{print $2}'"));
                    break;
                case 'mysql':
                    $logPath = '/www/server/mysql/mysql-error.log';
                    $filter = 'mysqld-auth';
                    $port = trim(shell_exec("cat /etc/my.cnf | grep 'port' | head -n 1 | awk '{print $3}'"));
                    break;
                case 'pure-ftpd':
                    $logPath = '/var/log/messages';
                    $filter = 'pure-ftpd';
                    $port = trim(shell_exec('cat /etc/pure-ftpd/pure-ftpd.conf | grep "Bind" | awk \'{print $2}\' | awk -F "," \'{print $2}\''));
                    break;
                default:
                    return response()->json(['code' => 1, 'msg' => '未知服务']);
            }

            if (empty($port)) {
                return response()->json(['code' => 1, 'msg' => '获取服务端口失败，请检查是否安装']);
            }

            $rule = PHP_EOL.<<<EOF
# $credentials[name]-START
[$credentials[name]]
enabled = true
filter = $filter
port = $port
maxretry = $credentials[maxretry]
findtime = $credentials[findtime]
bantime = $credentials[bantime]
action = %(action_mwl)s
logpath = $logPath
# $credentials[name]-END
EOF;
            shell_exec('echo "'.$rule.'" >> /etc/fail2ban/jail.local');
        }
        shell_exec('fail2ban-client reload');

        // 返回结果
        $res['code'] = 0;
        $res['msg'] = 'success';
        return response()->json($res);
    }

    /**
     * 删除规则
     * @param  Request  $request
     * @return JsonResponse
     */
    public function deleteRule(Request $request): JsonResponse
    {
        // 消毒
        try {
            $credentials = $this->validate($request, [
                'name' => 'required',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['code' => 1, 'msg' => $e->getMessage()]);
        }

        $rule = @file_get_contents('/etc/fail2ban/jail.local');
        if (empty($rule)) {
            return response()->json(['code' => 1, 'msg' => '获取Fail2ban规则列表失败，Fail2ban可能已损坏']);
        }

        // 截取规则
        $ruleCheck = cut("# $credentials[name]-START", "# $credentials[name]-END", $rule);
        if (empty($ruleCheck)) {
            return response()->json(['code' => 1, 'msg' => '规则不存在']);
        }

        // 删除规则
        $rule = str_replace(PHP_EOL."# $credentials[name]-START".$ruleCheck."# $credentials[name]-END", '', $rule);
        $rule = trim($rule);
        file_put_contents('/etc/fail2ban/jail.local', $rule);

        // 重载服务
        shell_exec('fail2ban-client reload');

        // 返回结果
        $res['code'] = 0;
        $res['msg'] = 'success';
        return response()->json($res);
    }

    /**
     * 获取封禁列表
     * @param  Request  $request
     * @return JsonResponse
     */
    public function getBanList(Request $request): JsonResponse
    {
        // 消毒
        try {
            $credentials = $this->validate($request, [
                'name' => 'required',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['code' => 1, 'msg' => $e->getMessage()]);
        }

        // 获取封禁列表
        $name = escapeshellarg($credentials['name']);
        $currentlyBan = trim(shell_exec('fail2ban-client status '.$name.' | grep "Currently banned" | awk \'{print $4}\''));
        $totalBan = trim(shell_exec('fail2ban-client status '.$name.' | grep "Total banned" | awk \'{print $4}\''));
        $bannedIpList = trim(shell_exec('fail2ban-client status '.$name.' | grep "Banned IP list" | awk -F ":" \'{print $2}\''));
        $bannedIpList = explode(' ', $bannedIpList);

        if (empty($bannedIpList)) {
            return response()->json(['code' => 1, 'msg' => '获取封禁列表失败']);
        }

        // 格式化数据
        $bannedIpListNew = [];
        foreach ($bannedIpList as $key => $value) {
            if (!empty($value)) {
                $bannedIpListNew[$key]['name'] = $credentials['name'];
                $bannedIpListNew[$key]['ip'] = $value;
            }
        }

        // 返回结果
        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = [
            'currentlyBan' => $currentlyBan,
            'totalBan' => $totalBan,
            'bannedIpList' => $bannedIpListNew,
        ];
        return response()->json($res);
    }

    /**
     * 删除封禁
     * @param  Request  $request
     * @return JsonResponse
     */
    public function unBan(Request $request): JsonResponse
    {
        // 消毒
        try {
            $credentials = $this->validate($request, [
                'ip' => 'required|ip',
                'name' => 'required',
            ]);
            $name = escapeshellarg($credentials['name']);
            $ip = escapeshellarg($credentials['ip']);
        } catch (ValidationException $e) {
            return response()->json(['code' => 1, 'msg' => $e->getMessage()]);
        }
        shell_exec("fail2ban-client set $name unbanip $ip");

        // 返回结果
        $res['code'] = 0;
        $res['msg'] = 'success';
        return response()->json($res);
    }

    /**
     * 设置ip白名单
     */
    public function setWhiteList(Request $request): JsonResponse
    {
        // 消毒
        try {
            $credentials = $this->validate($request, [
                'ip' => 'required',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['code' => 1, 'msg' => $e->getMessage()]);
        }

        $rule = @file_get_contents('/etc/fail2ban/jail.local');
        if (empty($rule)) {
            return response()->json(['code' => 1, 'msg' => '获取Fail2ban规则列表失败，Fail2ban可能已损坏']);
        }
        // 正则替换
        $rule = preg_replace('/ignoreip\s*=\s*.*\n/', "ignoreip = $credentials[ip]\n", $rule);
        file_put_contents('/etc/fail2ban/jail.local', $rule);

        // 重载服务
        shell_exec('fail2ban-client reload');

        // 返回结果
        $res['code'] = 0;
        $res['msg'] = 'success';
        return response()->json($res);
    }

    /**
     * 获取ip白名单
     */
    public function getWhiteList(): JsonResponse
    {
        $rule = @file_get_contents('/etc/fail2ban/jail.local');
        if (empty($rule)) {
            return response()->json(['code' => 1, 'msg' => '获取Fail2ban规则列表失败，Fail2ban可能已损坏']);
        }
        preg_match('/ignoreip\s*=\s*(.*)\n/', $rule, $match);
        if (empty($match[1])) {
            return response()->json(['code' => 1, 'msg' => '获取ip白名单失败']);
        }

        // 返回结果
        $res['code'] = 0;
        $res['msg'] = 'success';
        $res['data'] = $match[1];
        return response()->json($res);
    }

    /**
     * 获取服务运行状态
     */
    public function status(): JsonResponse
    {
        $status = shell_exec('systemctl status fail2ban | grep Active | grep -v grep | awk \'{print $2}\'');
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
     */
    public function start(): JsonResponse
    {
        shell_exec('systemctl start fail2ban');
        $status = shell_exec('systemctl status fail2ban | grep Active | grep -v grep | awk \'{print $2}\'');
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
        return response()->json($res);
    }

    /**
     * 停止服务
     */
    public function stop(): JsonResponse
    {
        shell_exec('systemctl stop fail2ban');
        $status = shell_exec('systemctl status fail2ban | grep Active | grep -v grep | awk \'{print $2}\'');
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
     */
    public function restart(): JsonResponse
    {
        shell_exec('systemctl restart fail2ban');
        $status = shell_exec('systemctl status fail2ban | grep Active | grep -v grep | awk \'{print $2}\'');
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
     */
    public function reload(): JsonResponse
    {
        shell_exec('systemctl reload fail2ban');
        $status = shell_exec('systemctl status fail2ban | grep Active | grep -v grep | awk \'{print $2}\'');
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

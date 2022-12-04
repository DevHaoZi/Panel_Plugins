<?php
/**
 * Name: 自动备份插件控制器
 * Author:耗子
 * Date: 2022-12-04
 */

namespace Plugins\AutoBackup\Controllers;

use App\Http\Controllers\Api\CronsController;
use App\Http\Controllers\Controller;

use App\Models\Cron;
use App\Models\Setting;
use App\Models\Website;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class AutoBackupController extends Controller
{

    /**
     * 获取任务列表
     * @param  Request  $request
     * @return JsonResponse
     */
    public function getTaskList(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);

        $crons = Cron::query()->where('type', '自动备份')->orderBy('id', 'desc')->paginate($limit);
        $cronData = [];

        foreach ($crons as $k => $v) {
            // 格式化时间
            $cronData[$k]['id'] = $v['id'];
            $cronData[$k]['name'] = $v['name'];
            $cronData[$k]['status'] = $v['status'];
            $cronData[$k]['type'] = $v['type'];
            $cronData[$k]['time'] = $v['time'];
            $cronData[$k]['shell'] = $v['shell'];
            $cronData[$k]['script'] = @file_get_contents('/www/server/cron/'.$v['shell']);
            $cronData[$k]['created_at'] = Carbon::create($v['created_at'])->toDateTimeString();
            $cronData[$k]['updated_at'] = Carbon::create($v['updated_at'])->toDateTimeString();
        }

        $data['code'] = 0;
        $data['msg'] = 'success';
        $data['count'] = $crons->total();
        $data['data'] = $cronData;
        return response()->json($data);
    }

    /**
     * 添加任务
     * @param  Request  $request
     * @return JsonResponse
     */
    public function addTask(Request $request): JsonResponse
    {
        // 消毒
        try {
            $credentials = $this->validate($request, [
                'time' => [
                    'required',
                    'regex:/^((\*|\d+|\d+-\d+|\d+\/\d+|\d+-\d+\/\d+|\*\/\d+)(\,(\*|\d+|\d+-\d+|\d+\/\d+|\d+-\d+\/\d+|\*\/\d+))*\s?){5}$/'
                ],
                'backup_type' => 'required|in:website,mysql,postgresql',
                'path' => 'required',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['code' => 1, 'msg' => $e->getMessage()]);
        }

        switch ($credentials['backup_type']) {
            case 'website':
                $name = $request->input('website_name');
                break;
            case 'mysql':
                $name = $request->input('mysql_name');
                break;
            case 'postgresql':
                $name = $request->input('postgresql_name');
                break;
            default:
                $res['code'] = 1;
                $res['msg'] = '未知的备份类型';
                return response()->json($res);
        }

        // 判断备份目录是否以/结尾，有则去掉
        if (str_ends_with($credentials['path'], '/')) {
            $credentials['path'] = substr($credentials['path'], 0, -1);
        }

        // 写入shell文件
        $shell = <<<EOF
#!/bin/bash
shopt -s expand_aliases
PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin:~/bin
export PATH
. /etc/profile
# 定时备份脚本
# 请勿随意修改此文件，否则可能导致定时任务失效
panel backup $credentials[backup_type] $name $credentials[path] 2>&1;
EOF;
        $shellDir = '/www/server/cron/';
        $shellLogDir = '/www/server/cron/logs/';
        if (!is_dir($shellDir)) {
            mkdir($shellDir, 0755, true);
        }
        if (!is_dir($shellLogDir)) {
            mkdir($shellLogDir, 0755, true);
        }
        $shellFile = uniqid().'.sh';
        file_put_contents($shellDir.$shellFile, $shell);
        // 将文件转为unix格式
        exec('dos2unix '.$shellDir.$shellFile);
        // 设置文件权限
        exec('chmod 700 '.$shellDir.$shellFile);

        $cron = new Cron();
        $cron->name = $credentials['backup_type'].'自动备份['.$name.']';
        $cron->status = 1;
        $cron->type = '自动备份';
        $cron->time = $credentials['time'];
        $cron->shell = $shellFile;
        $cron->save();

        // 返回结果
        $res['code'] = 0;
        $res['msg'] = '添加成功';
        return response()->json($res);
    }

    /**
     * 删除任务
     * @param  Request  $request
     * @return JsonResponse
     */
    public function deleteTask(Request $request): JsonResponse
    {
        // 调用CronsController的删除方法
        $cronsController = new CronsController();
        return $cronsController->delete($request);
    }
}

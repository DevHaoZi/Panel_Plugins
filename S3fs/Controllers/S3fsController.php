<?php
/**
 * Name: S3fs插件控制器
 * Author: 耗子
 * Date: 2022-12-10
 */

namespace Plugins\S3fs\Controllers;

use App\Http\Controllers\Api\CronsController;
use App\Http\Controllers\Controller;

use App\Models\Cron;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class S3fsController extends Controller
{

    /**
     * 获取挂载列表
     * @param  Request  $request
     * @return JsonResponse
     */
    public function getList(Request $request): JsonResponse
    {
        try {
            $input = $this->validate($request, [
                'page' => 'required|integer',
                'limit' => 'required|integer',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['code' => 1, 'msg' => $e->getMessage()]);
        }

        $data = Setting::query()->where('name', 's3fs')->value('value');
        $data = json_decode($data, true);

        if (empty($data)) {
            return response()->json(['code' => 1, 'msg' => '无S3fs挂载信息']);
        }

        // 分页
        $s3fsData = array_slice($data, ($input['page'] - 1) * $input['limit'], $input['limit']);

        return response()->json(['code' => 0, 'msg' => 'success', 'total' => count($s3fsData), 'data' => $s3fsData]);
    }

    /**
     * 添加挂载
     * @param  Request  $request
     * @return JsonResponse
     */
    public function addMount(Request $request): JsonResponse
    {
        // 消毒
        try {
            $credentials = $this->validate($request, [
                'ak' => ['required', 'string', 'regex:/^[a-zA-Z0-9]*$/'],
                'sk' => ['required', 'string', 'regex:/^[a-zA-Z0-9]*$/'],
                'bucket' => ['required', 'string', 'regex:/^[a-zA-Z0-9_-]*$/'],
                'url' => ['required', 'string', 'url'],
                'path' => ['required', 'string', 'regex:/^\/[a-zA-Z0-9_-]+$/'],
            ]);
        } catch (ValidationException $e) {
            return response()->json(['code' => 1, 'msg' => $e->getMessage()]);
        }

        // 检查下地域节点中是否包含bucket，如果包含了，肯定是错误的
        if (str_contains($credentials['url'], $credentials['bucket'])) {
            return response()->json(['code' => 1, 'msg' => '地域节点输入错误']);
        }

        // 检查目录是否存在且是否为空
        if (is_dir($credentials['path'])) {
            if (count(scandir($credentials['path'])) > 2) {
                return response()->json(['code' => 1, 'msg' => '挂载目录不为空']);
            }
        } else {
            // 创建目录
            if (!mkdir($credentials['path'], 0755, true)) {
                return response()->json(['code' => 1, 'msg' => '挂载目录创建失败']);
            }
        }

        $id = uniqid();
        $ak = $credentials['ak'];
        $sk = $credentials['sk'];
        $bucket = $credentials['bucket'];
        $path = $credentials['path'];

        // 写入密码文件
        $password = $ak.':'.$sk;
        file_put_contents('/etc/passwd-s3fs-'.$id, $password);
        chmod('/etc/passwd-s3fs-'.$id, 0600);

        // 设置挂载
        shell_exec('echo '.escapeshellarg('s3fs#'.$bucket.' '.$credentials['path'].' fuse _netdev,allow_other,nonempty,url='.$credentials['url'].',passwd_file=/etc/passwd-s3fs-'.$id.' 0 0').' >> /etc/fstab');
        $check = trim(shell_exec('mount -a 2>&1'));
        if (!empty($check)) {
            // 移除fstab配置
            shell_exec("sed -i 's@^s3fs#$bucket\s$path.*$@@g' /etc/fstab");
            return response()->json(['code' => 1, 'msg' => '检测到/etc/fstab有误：'.$check]);
        }
        $check2 = trim(shell_exec('df -h | grep '.escapeshellarg($credentials['path'])));
        if (empty($check2)) {
            // 移除fstab配置
            shell_exec("sed -i 's@^s3fs#$bucket\s$path.*$@@g' /etc/fstab");
            return response()->json(['code' => 1, 'msg' => '挂载失败，请检查配置是否正确']);
        }

        // 写入数据库
        $data = Setting::query()->where('name', 's3fs')->value('value');
        $data = json_decode($data, true);
        $data[] = [
            'id' => $id,
            'bucket' => $bucket,
            'url' => $credentials['url'],
            'path' => $credentials['path']
        ];
        Setting::query()->where('name', 's3fs')->updateOrCreate(['name' => 's3fs'], ['value' => json_encode($data)]);

        // 返回结果
        $res['code'] = 0;
        $res['msg'] = 'success';
        return response()->json($res);
    }

    /**
     * 删除挂载
     * @param  Request  $request
     * @return JsonResponse
     */
    public function deleteMount(Request $request): JsonResponse
    {
        // 消毒
        try {
            $input = $this->validate($request, [
                'id' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['code' => 1, 'msg' => $e->getMessage()]);
        }

        // 从数据库中获取挂载信息
        $data = Setting::query()->where('name', 's3fs')->value('value');
        $data = json_decode($data, true);
        foreach ($data as $item) {
            if ($item['id'] == $input['id']) {
                $mount = $item;
                break;
            }
        }

        if (empty($mount)) {
            return response()->json(['code' => 1, 'msg' => '挂载不存在']);
        }

        $id = $mount['id'];
        $path = $mount['path'];
        $bucket = $mount['bucket'];

        // 卸载
        shell_exec('fusermount -u '.$path);
        shell_exec('umount '.$path);
        // 移除fstab配置
        shell_exec("sed -i 's@^s3fs#$bucket\s$path.*$@@g' /etc/fstab");
        $check = trim(shell_exec('mount -a 2>&1'));
        if (!empty($check)) {
            return response()->json(['code' => 1, 'msg' => '卸载异常，请检查/etc/fstab']);
        }
        // 删除密码文件
        shell_exec('rm -f /etc/passwd-s3fs-'.$id);
        // 删除数据库记录
        $data = array_filter($data, function ($item) use ($input) {
            return $item['id'] !== $input['id'];
        });
        Setting::query()->where('name', 's3fs')->updateOrCreate(['name' => 's3fs'], ['value' => json_encode($data)]);

        return response()->json(['code' => 0, 'msg' => 'success']);
    }
}

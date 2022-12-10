<!--
Name: S3fs插件 - 新增挂载
Author: 耗子
Date: 2022-12-10
-->
<script type="text/html" template lay-done="layui.data.sendParams(d.params)">
    <form class="layui-form" action="" lay-filter="add-mount-s3fs-form">
        <div class="layui-form-item">
            <label class="layui-form-label">Bucket</label>
            <div class="layui-input-block">
                <input type="text" name="bucket" id="add-mount-s3fs-bucket"
                       lay-verify="required" placeholder="请输入Bucket名" class="layui-input"
                       value=""/>
            </div>
            <div class="layui-form-mid layui-word-aux">输入Bucket名字，腾讯云COS为（xxxx-用户ID）</div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">AK</label>
            <div class="layui-input-block">
                <input type="text" name="ak" id="add-mount-s3fs-ak"
                       lay-verify="required" placeholder="请输入AK密钥" class="layui-input"
                       value=""/>
            </div>
            <div class="layui-form-mid layui-word-aux">访问密钥中的Access Key，需具备Bucket操作权限，腾讯云为SecretId
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">SK</label>
            <div class="layui-input-block">
                <input type="text" name="sk" id="add-mount-s3fs-sk"
                       lay-verify="required" placeholder="请输入SK密钥" class="layui-input"
                       value=""/>
            </div>
            <div class="layui-form-mid layui-word-aux">访问密钥中的Access Key
                Secret，需具备Bucket操作权限，腾讯云为SecretKey
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">地域节点</label>
            <div class="layui-input-block">
                <input type="text" name="url" id="add-mount-s3fs-sk"
                       lay-verify="required" placeholder="请输入Bucket地域节点" class="layui-input"
                       value=""/>
            </div>
            <div class="layui-form-mid layui-word-aux">
                地域节点可在<a target="_blank" href="https://github.com/s3fs-fuse/s3fs-fuse/wiki/Non-Amazon-S3">https://github.com/s3fs-fuse/s3fs-fuse/wiki/Non-Amazon-S3</a>查找
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">挂载目录</label>
            <div class="layui-input-block">
                <input type="text" name="path" id="add-mount-s3fs-path"
                       lay-verify="required" placeholder="请输入挂载目录" class="layui-input"
                       value=""/>
            </div>
            <div class="layui-form-mid layui-word-aux">挂载目录，如/data，不存在将会自动创建</div>
        </div>
        <div class="layui-form-item">
            <div class="layui-input-block">
                <div class="layui-footer">
                    <button class="layui-btn" lay-submit="" lay-filter="add-mount-s3fs-submit">立即提交</button>
                    <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                </div>
            </div>
        </div>
    </form>
</script>
<script>
    layui.data.sendParams = function (params) {
        layui.use(['admin', 'form', 'jquery', 'cron'], function () {
            var $ = layui.jquery
                , admin = layui.admin
                , layer = layui.layer
                , form = layui.form
                , table = layui.table;

            form.render();

            // 提交
            form.on('submit(add-mount-s3fs-submit)', function (data) {
                index = layer.msg('正在提交...', {icon: 16, time: 0});
                admin.req({
                    url: "/api/plugin/s3fs/addMount"
                    , method: 'post'
                    , data: data.field
                    , success: function (result) {
                        layer.close(index);
                        if (result.code !== 0) {
                            return false;
                        }
                        table.reload('s3fs-list');
                        layer.alert('S3fs挂载已提交，请自行检查是否挂载成功！', {
                            icon: 1
                            , title: '提示'
                            , btn: ['确定']
                            , yes: function (index) {
                                layer.closeAll();
                            }
                        });
                    }
                    , error: function (xhr, status, error) {
                        console.log('耗子Linux面板：ajax请求出错，错误' + error);
                    }
                });
                return false;
            });
        });
    };
</script>

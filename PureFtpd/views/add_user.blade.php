<!--
Name: Pure-Ftpd管理器 - 新建用户
Author: 耗子
Date: 2022-12-07
-->
<script type="text/html" template lay-done="layui.data.sendParams(d.params)">
    <form class="layui-form" action="" lay-filter="add-pure-ftpd-user-form">
        <div class="layui-form-item">
            <label class="layui-form-label">用户名</label>
            <div class="layui-input-block">
                <input type="text" name="username" id="add-pure-ftpd-user-username"
                       lay-verify="required" placeholder="请输入用户名" class="layui-input"
                       value=""/>
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">密码</label>
            <div class="layui-input-block">
                <input type="password" name="password" id="add-pure-ftpd-user-password"
                       lay-verify="required" placeholder="请输入密码" class="layui-input"
                       value=""/>
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">目录</label>
            <div class="layui-input-block">
                <input type="text" name="path" id="add-pure-ftpd-user-path"
                       lay-verify="required" placeholder="请输入目录" class="layui-input"
                       value=""/>
            </div>
        </div>
        <div class="layui-form-item">
            <div class="layui-input-block">
                <div class="layui-footer">
                    <button class="layui-btn" lay-submit="" lay-filter="add-pure-ftpd-user-submit">立即提交</button>
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
            form.on('submit(add-pure-ftpd-user-submit)', function (data) {
                index = layer.msg('正在提交...', {icon: 16, time: 0});
                admin.req({
                    url: "/api/plugin/pure-ftpd/addUser"
                    , method: 'post'
                    , data: data.field
                    , success: function (result) {
                        layer.close(index);
                        if (result.code !== 0) {
                            console.log('耗子Linux面板：Pure-Ftpd用户添加失败，接口返回' + result);
                            layer.msg('Pure-Ftpd用户添加失败，请刷新重试！')
                            return false;
                        }
                        table.reload('pure-ftpd-user-list');
                        layer.alert('Pure-Ftpd用户添加成功！', {
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

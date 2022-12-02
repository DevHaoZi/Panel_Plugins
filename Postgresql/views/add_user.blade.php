<!--
Name: PostgreSQL管理器 - 添加用户
Author: 耗子
Date: 2022-12-02
-->
<script type="text/html" template lay-done="layui.data.sendParams(d.params)">
    <form class="layui-form" action="" lay-filter="add-postgresql-user-form">
        <div class="layui-form-item layui-form-text">
            <label class="layui-form-label">用户名</label>
            <div class="layui-input-block">
                <input type="text" name="username" lay-verify="required" placeholder="请输入用户名"
                       autocomplete="off" class="layui-input"/>
            </div>
        </div>
        <div class="layui-form-item layui-form-text">
            <label class="layui-form-label">密码</label>
            <div class="layui-input-block">
                <input type="text" name="password" lay-verify="required" placeholder="请输入密码（8位以上大小写数字特殊符号混合）"
                       autocomplete="off" class="layui-input"/>
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">数据库</label>
            <div class="layui-input-block">
                <input type="text" name="database" lay-verify="required" placeholder="输入授权给该用户的数据库名"
                       autocomplete="off" class="layui-input"/>
            </div>
        </div>
        <div class="layui-form-item">
            <div class="layui-input-block">
                <div class="layui-footer">
                    <button class="layui-btn" lay-submit="" lay-filter="add-postgresql-user-submit">立即提交</button>
                    <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                </div>
            </div>
        </div>
    </form>
</script>
<script>
    layui.data.sendParams = function (params) {
        layui.use(['admin', 'form'], function () {
            var $ = layui.$
                , admin = layui.admin
                , layer = layui.layer
                , form = layui.form
                , table = layui.table

            form.render();

            // 提交
            form.on('submit(add-postgresql-user-submit)', function (data) {
                admin.req({
                    url: "/api/plugin/postgresql/addUser"
                    , method: 'post'
                    , data: data.field
                    , success: function (result) {
                        if (result.code !== 0) {
                            console.log('耗子Linux面板：用户添加失败，接口返回' + result);
                            layer.msg('用户添加失败，请刷新重试！')
                            return false;
                        }
                        table.reload('postgresql-database-list');
                        table.reload('postgresql-user-list');
                        layer.alert('用户添加成功！', {
                            icon: 1
                            , title: '提示'
                            , btn: ['确定']
                            , yes: function (index) {
                                layer.closeAll();
                                //location.reload();
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
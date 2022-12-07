<!--
Name: Pure-Ftpd管理器
Author: 耗子
Date: 2022-12-07
-->
<title>Pure-Ftpd</title>
<div class="layui-fluid" id="component-tabs">
    <div class="layui-row">
        <div class="layui-col-md12">
            <div class="layui-card">
                <div class="layui-card-header">Pure-Ftpd 用户列表</div>
                <div class="layui-card-body">
                    <table class="layui-hide" id="pure-ftpd-user-list" lay-filter="pure-ftpd-user-list"></table>
                    <!-- 顶部工具栏 -->
                    <script type="text/html" id="pure-ftpd-user-list-bar">
                        <div class="layui-btn-container">
                            <button class="layui-btn layui-btn-sm" lay-event="add_user">新建用户</button>
                        </div>
                    </script>
                    <!-- 右侧管理 -->
                    <script type="text/html" id="pure-ftpd-user-list-control">
                        <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="change_password">改密</a>
                        <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="del">删除</a>
                    </script>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    layui.use(['index', 'code', 'table'], function () {
        let $ = layui.$
            , admin = layui.admin
            , table = layui.table
            , form = layui.form
            , view = layui.view;

        // 获取用户列表
        table.render({
            elem: '#pure-ftpd-user-list'
            , url: '/api/plugin/pure-ftpd/getUserList'
            , toolbar: '#pure-ftpd-user-list-bar'
            , title: 'Pure-Ftpd 用户列表'
            , cols: [[
                {field: 'username', title: '用户名', fixed: 'left', unresize: true, sort: true}
                , {field: 'path', title: '目录', unresize: true, sort: true}
                , {fixed: 'right', title: '操作', toolbar: '#pure-ftpd-user-list-control', width: 150}
            ]]
            , page: true
        });
        // 头工具栏事件
        table.on('toolbar(pure-ftpd-user-list)', function (obj) {
            if (obj.event === 'add_user') {
                admin.popup({
                    title: '新建Pure-Ftpd用户'
                    , area: ['600px', '400px']
                    , id: 'LAY-popup-pure-ftpd-user-add'
                    , success: function (layer, index) {
                        view(this.id).render('plugin/pure-ftpd/add_user', {}).done(function () {
                            form.render(null, 'LAY-popup-pure-ftpd-user-add');
                        });
                    }
                });
            }
        });
        // 行工具事件
        table.on('tool(pure-ftpd-user-list)', function (obj) {
            let data = obj.data;
            if (obj.event === 'del') {
                layer.confirm('确定要删除用户 <b style="color: red;">' + data.username + '</b> 吗？', function (index) {
                    admin.req({
                        url: "/api/plugin/pure-ftpd/deleteUser"
                        , method: 'post'
                        , data: data
                        , success: function (result) {
                            if (result.code !== 0) {
                                console.log('耗子Linux面板：Pure-Ftpd用户删除失败，接口返回' + result);
                                layer.msg('Pure-Ftpd用户删除失败，请刷新重试！')
                                return false;
                            }
                            obj.del();
                            layer.alert(data.name + '删除成功！');
                        }
                        , error: function (xhr, status, error) {
                            console.log('耗子Linux面板：ajax请求出错，错误' + error);
                        }
                    });
                    layer.close(index);
                });
            } else if (obj.event === 'change_password') {
                // 弹出输入密码框
                layer.prompt({
                    formType: 1
                    , title: '请输入新密码（6位以上）'
                }, function (value, index) {
                    layer.close(index);
                    layer.load(2);
                    // 发送请求
                    admin.req({
                        url: "/api/plugin/pure-ftpd/changePassword"
                        , method: 'post'
                        , data: {
                            username: data.username,
                            password: value
                        }
                        , success: function (result) {
                            layer.closeAll('loading');
                            if (result.code !== 0) {
                                console.log('耗子Linux面板：密码修改失败，接口返回' + result);
                                layer.msg('密码修改失败，请刷新重试！')
                                return false;
                            }
                            layer.alert('用户' + data.username + '密码修改成功！');
                        }
                        , error: function (xhr, status, error) {
                            layer.closeAll('loading');
                            console.log('耗子Linux面板：ajax请求出错，错误' + error);
                        }
                    });
                });
            }
        });
    });
</script>
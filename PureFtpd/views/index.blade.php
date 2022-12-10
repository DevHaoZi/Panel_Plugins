<!--
Name: Pure-Ftpd管理器
Author: 耗子
Date: 2022-12-09
-->
<title>Pure-Ftpd</title>
<div class="layui-fluid" id="component-tabs">
    <div class="layui-row">
        <div class="layui-col-md12">
            <div class="layui-card">
                <div class="layui-card-header">Pure-Ftpd 运行状态</div>
                <div class="layui-card-body">
                    <blockquote id="pure-ftpd-status" class="layui-elem-quote layui-quote-nm">当前状态：<span
                                class="layui-badge layui-bg-black">获取中</span></blockquote>
                    <div class="layui-btn-container" style="padding-top: 30px;">
                        <button id="pure-ftpd-start" class="layui-btn">启动</button>
                        <button id="pure-ftpd-stop" class="layui-btn layui-btn-danger">停止</button>
                        <button id="pure-ftpd-restart" class="layui-btn layui-btn-warm">重启</button>
                        <button id="pure-ftpd-reload" class="layui-btn layui-btn-normal">重载</button>
                    </div>
                    <fieldset class="layui-elem-field layui-field-title" style="margin-top: 30px;">
                        <legend>基本设置</legend>
                    </fieldset>
                    <div class="layui-form" lay-filter="pure-ftpd_setting">
                        <div class="layui-form-item">
                            <label class="layui-form-label" style="font-size: 13px;">端口</label>
                            <div class="layui-input-inline">
                                <input type="text" name="pure-ftpd_port" value="获取中ing..." class="layui-input"
                                       disabled/>
                            </div>
                            <div class="layui-form-mid layui-word-aux">设置Pure-Ftpd的访问端口</div>
                        </div>
                        <div class="layui-form-item">
                            <div class="layui-input-block">
                                <button class="layui-btn layui-btn-sm" lay-submit lay-filter="pure-ftpd_setting_submit">
                                    确认修改
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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

        // 获取运行状态并渲染
        admin.req({
            url: "/api/plugin/pure-ftpd/status"
            , method: 'get'
            , success: function (result) {
                if (result.code !== 0) {
                    console.log('耗子Linux面板：pure-ftpd运行状态获取失败，接口返回' + result);
                    return false;
                }
                if (result.data) {
                    $('#pure-ftpd-status').html('当前状态：<span class="layui-badge layui-bg-green">运行中</span>');
                } else {
                    $('#pure-ftpd-status').html('当前状态：<span class="layui-badge layui-bg-red">已停止</span>');
                }
            }
            , error: function (xhr, status, error) {
                console.log('耗子Linux面板：ajax请求出错，错误' + error)
            }
        });

        // 获取端口并渲染
        admin.req({
            url: "/api/plugin/pure-ftpd/getPort"
            , method: 'get'
            , success: function (result) {
                if (result.code !== 0) {
                    console.log('耗子Linux面板：pure-ftpd 端口获取失败，接口返回' + result);
                    return false;
                }
                $('input[name=pure-ftpd_port]').val(result.data);
                $('input').attr('disabled', false);
                form.render();
            }
            , error: function (xhr, status, error) {
                console.log('耗子Linux面板：ajax请求出错，错误' + error);
            }
        });

        // 监听提交
        form.on('submit(pure-ftpd_setting_submit)', function (data) {
            data.field.port = $('input[name=pure-ftpd_port]').val();
            index = layer.msg('请稍候...', {icon: 16, time: 0});
            admin.req({
                url: "/api/plugin/pure-ftpd/setPort"
                , method: 'post'
                , data: data.field
                , success: function (result) {
                    layer.close(index);
                    if (result.code !== 0) {
                        console.log('耗子Linux面板：pure-ftpd 端口设置失败，接口返回' + result);
                        return false;
                    }
                    layer.msg('设置成功', {icon: 1});
                }
                , error: function (xhr, status, error) {
                    console.log('耗子Linux面板：ajax请求出错，错误' + error);
                }
            });
            return false;
        });

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
                    index = layer.msg('请稍等...', {
                        icon: 16
                        , time: 0
                    });
                    admin.req({
                        url: "/api/plugin/pure-ftpd/deleteUser"
                        , method: 'post'
                        , data: data
                        , success: function (result) {
                            layer.close(index);
                            if (result.code !== 0) {
                                console.log('耗子Linux面板：Pure-Ftpd用户删除失败，接口返回' + result);
                                layer.msg('Pure-Ftpd用户删除失败，请刷新重试！')
                                return false;
                            }
                            obj.del();
                            layer.alert(data.username + '删除成功！');
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
                    index = layer.msg('请稍等...', {
                        icon: 16
                        , time: 0
                    });
                    // 发送请求
                    admin.req({
                        url: "/api/plugin/pure-ftpd/changePassword"
                        , method: 'post'
                        , data: {
                            username: data.username,
                            password: value
                        }
                        , success: function (result) {
                            layer.close(index);
                            if (result.code !== 0) {
                                console.log('耗子Linux面板：密码修改失败，接口返回' + result);
                                layer.msg('密码修改失败，请刷新重试！')
                                return false;
                            }
                            layer.alert('用户' + data.username + '密码修改成功！');
                        }
                        , error: function (xhr, status, error) {
                            console.log('耗子Linux面板：ajax请求出错，错误' + error);
                        }
                    });
                });
            }
        });

        // 事件监听
        $('#pure-ftpd-start').click(function () {
            layer.confirm('确定要启动pure-ftpd吗？', {
                btn: ['启动', '取消']
            }, function () {
                index = layer.msg('请稍等...', {
                    icon: 16
                    , time: 0
                });
                admin.req({
                    url: "/api/plugin/pure-ftpd/start"
                    , method: 'post'
                    , success: function (result) {
                        layer.close(index);
                        if (result.code !== 0) {
                            console.log('耗子Linux面板：pure-ftpd启动失败，接口返回' + result);
                            return false;
                        }
                        admin.events.refresh();
                        layer.alert('pure-ftpd启动成功！');
                    }
                    , error: function (xhr, status, error) {
                        console.log('耗子Linux面板：ajax请求出错，错误' + error)
                    }
                });
            });
        });
        $('#pure-ftpd-stop').click(function () {
            layer.confirm('停止pure-ftpd将导致FTP无法连接，是否继续停止？', {
                btn: ['停止', '取消']
            }, function () {
                index = layer.msg('请稍等...', {
                    icon: 16
                    , time: 0
                });
                admin.req({
                    url: "/api/plugin/pure-ftpd/stop"
                    , method: 'post'
                    , success: function (result) {
                        layer.close(index);
                        if (result.code !== 0) {
                            console.log('耗子Linux面板：pure-ftpd停止失败，接口返回' + result);
                            return false;
                        }
                        admin.events.refresh();
                        layer.alert('pure-ftpd停止成功！');
                    }
                    , error: function (xhr, status, error) {
                        console.log('耗子Linux面板：ajax请求出错，错误' + error)
                    }
                });
            });
        });
        $('#pure-ftpd-restart').click(function () {
            layer.confirm('确定要重启pure-ftpd吗？', {
                btn: ['重启', '取消']
            }, function () {
                index = layer.msg('请稍等...', {
                    icon: 16
                    , time: 0
                });
                admin.req({
                    url: "/api/plugin/pure-ftpd/restart"
                    , method: 'post'
                    , success: function (result) {
                        layer.close(index);
                        if (result.code !== 0) {
                            console.log('耗子Linux面板：pure-ftpd重启失败，接口返回' + result);
                            return false;
                        }
                        admin.events.refresh();
                        layer.alert('pure-ftpd重启成功！');
                    }
                    , error: function (xhr, status, error) {
                        console.log('耗子Linux面板：ajax请求出错，错误' + error)
                    }
                });
            });
        });
        $('#pure-ftpd-reload').click(function () {
            index = layer.msg('请稍等...', {
                icon: 16
                , time: 0
            });
            admin.req({
                url: "/api/plugin/pure-ftpd/reload"
                , method: 'post'
                , success: function (result) {
                    layer.close(index);
                    if (result.code !== 0) {
                        console.log('耗子Linux面板：pure-ftpd重载失败，接口返回' + result);
                        return false;
                    }
                    layer.alert('pure-ftpd重载成功！');
                }
                , error: function (xhr, status, error) {
                    console.log('耗子Linux面板：ajax请求出错，错误' + error)
                }
            });
        });
    });
</script>
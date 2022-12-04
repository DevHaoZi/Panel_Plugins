<!--
Name: PostgreSQL管理器
Author: 耗子
Date: 2022-12-02
-->
<title>PostgreSQL</title>
<div class="layui-fluid" id="component-tabs">
    <div class="layui-row">
        <div class="layui-col-md12">
            <div class="layui-card">
                <div class="layui-card-header">PostgreSQL管理</div>
                <div class="layui-card-body">
                    <div class="layui-tab">
                        <ul class="layui-tab-title">
                            <li class="layui-this">基本信息</li>
                            <li>管理</li>
                            <li>主配置</li>
                            <li>用户配置</li>
                            <li>负载状态</li>
                            <li>日志</li>
                        </ul>
                        <div class="layui-tab-content">
                            <div class="layui-tab-item layui-show">
                                <fieldset class="layui-elem-field layui-field-title" style="margin-top: 30px;">
                                    <legend>运行状态</legend>
                                </fieldset>
                                <blockquote id="postgresql-status" class="layui-elem-quote layui-quote-nm">当前状态：<span
                                        class="layui-badge layui-bg-black">获取中</span></blockquote>
                                <div class="layui-btn-container" style="padding-top: 30px;">
                                    <button id="postgresql-start" class="layui-btn">启动</button>
                                    <button id="postgresql-stop" class="layui-btn layui-btn-danger">停止</button>
                                    <button id="postgresql-restart" class="layui-btn layui-btn-warm">重启</button>
                                    <button id="postgresql-reload" class="layui-btn layui-btn-normal">重载</button>
                                </div>
                            </div>
                            <div class="layui-tab-item">
                                <blockquote class="layui-elem-quote">面板仅集成了部分常用功能，如需更多功能，请使用 pgAdmin 客户端。
                                </blockquote>
                                <fieldset class="layui-elem-field layui-field-title" style="margin-top: 30px;">
                                    <legend>数据库列表</legend>
                                </fieldset>
                                <table class="layui-hide" id="postgresql-database-list" lay-filter="postgresql-database-list"></table>
                                <fieldset class="layui-elem-field layui-field-title" style="margin-top: 30px;">
                                    <legend>用户列表</legend>
                                </fieldset>
                                <table class="layui-hide" id="postgresql-user-list" lay-filter="postgresql-user-list"></table>
                                <!-- 数据库顶部工具栏 -->
                                <script type="text/html" id="postgresql-database-list-bar">
                                    <div class="layui-btn-container">
                                        <button class="layui-btn layui-btn-sm" lay-event="add_database">新建数据库</button>
                                    </div>
                                </script>
                                <!-- 用户顶部工具栏 -->
                                <script type="text/html" id="postgresql-user-list-bar">
                                    <div class="layui-btn-container">
                                        <button class="layui-btn layui-btn-sm" lay-event="add_user">新建用户</button>
                                    </div>
                                </script>
                                <!-- 数据库右侧管理 -->
                                <script type="text/html" id="postgresql-database-list-control">
                                    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="backup">备份</a>
                                    <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="del">删除</a>
                                </script>
                                <!-- 用户右侧管理 -->
                                <script type="text/html" id="postgresql-user-list-control">
                                    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="change_password">改密</a>
                                    <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="del">删除</a>
                                </script>
                            </div>
                            <div class="layui-tab-item">
                                <blockquote class="layui-elem-quote">此处修改的是PostgreSQL主配置文件，如果你不了解各参数的含义，请不要随意修改！<br>
                                    提示：Ctrl+F 搜索关键字，Ctrl+S 保存，Ctrl+H 查找替换！
                                </blockquote>
                                <div id="postgresql-config-editor"
                                     style="height: 600px;"></div>
                                <div class="layui-btn-container" style="padding-top: 30px;">
                                    <button id="postgresql-config-save" class="layui-btn">保存</button>
                                </div>
                            </div>
                            <div class="layui-tab-item">
                                <blockquote class="layui-elem-quote">此处修改的是PostgreSQL用户配置文件，如果你不了解各参数的含义，请不要随意修改！<br>
                                    提示：Ctrl+F 搜索关键字，Ctrl+S 保存，Ctrl+H 查找替换！
                                </blockquote>
                                <div id="postgresql-user-config-editor"
                                     style="height: 600px;"></div>
                                <div class="layui-btn-container" style="padding-top: 30px;">
                                    <button id="postgresql-user-config-save" class="layui-btn">保存</button>
                                </div>
                            </div>
                            <div class="layui-tab-item">
                                <table class="layui-hide" id="postgresql-load-status"></table>
                            </div>
                            <div class="layui-tab-item">
                                <pre id="postgresql-log" class="layui-code">
                                    获取中...
                                </pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let postgresql_config_editor;// 定义postgresql配置编辑器的全局变量
    let postgresql_user_config_editor;// 定义postgresql用户配置编辑器的全局变量
    layui.use(['index', 'code', 'table'], function () {
        let $ = layui.$
            , admin = layui.admin
            , element = layui.element
            , code = layui.code
            , table = layui.table
            , form = layui.form
            , view = layui.view;

        // 渲染表单
        form.render();

        // 获取postgresql运行状态并渲染
        admin.req({
            url: "/api/plugin/postgresql/status"
            , method: 'get'
            , success: function (result) {
                if (result.code !== 0) {
                    console.log('耗子Linux面板：PostgreSQL运行状态获取失败，接口返回' + result);
                    return false;
                }
                if (result.data === "running") {
                    $('#postgresql-status').html('当前状态：<span class="layui-badge layui-bg-green">运行中</span>');
                } else {
                    $('#postgresql-status').html('当前状态：<span class="layui-badge layui-bg-red">已停止</span>');
                }

            }
            , error: function (xhr, status, error) {
                console.log('耗子Linux面板：ajax请求出错，错误' + error)
            }
        });

        // 获取数据库列表
        table.render({
            elem: '#postgresql-database-list'
            , url: '/api/plugin/postgresql/getDatabases'
            , toolbar: '#postgresql-database-list-bar'
            , title: '数据库列表'
            , cols: [[
                {field: 'name', title: '库名', fixed: 'left', unresize: true, sort: true}
                , {field: 'owner', title: '所有者', unresize: true, sort: true}
                , {field: 'encoding', title: '编码', unresize: true, sort: true}
                , {fixed: 'right', title: '操作', toolbar: '#postgresql-database-list-control', width: 150}
            ]]
            /**
             * TODO: 分页
             */
            //, page: true
        });
        // 头工具栏事件
        table.on('toolbar(postgresql-database-list)', function (obj) {
            if (obj.event === 'add_database') {
                admin.popup({
                    title: '新建数据库'
                    , area: ['600px', '300px']
                    , id: 'LAY-popup-postgresql-database-add'
                    , success: function (layer, index) {
                        view(this.id).render('plugin/postgresql/add_database', {
                        }).done(function () {
                            form.render(null, 'LAY-popup-postgresql-database-add');
                        });
                    }
                });
            }
        });
        // 行工具事件
        table.on('tool(postgresql-database-list)', function (obj) {
            let data = obj.data;
            if (obj.event === 'del') {
                layer.confirm('高风险操作，确定要删除数据库 <b style="color: red;">'+data.name+'</b> 吗？', function (index) {
                    admin.req({
                        url: "/api/plugin/postgresql/deleteDatabase"
                        , method: 'post'
                        , data: data
                        , success: function (result) {
                            if (result.code !== 0) {
                                console.log('耗子Linux面板：数据库删除失败，接口返回' + result);
                                layer.msg('数据库删除失败，请刷新重试！')
                                return false;
                            }
                            obj.del();
                            layer.alert('数据库' + data.name + '删除成功！');
                        }
                        , error: function (xhr, status, error) {
                            console.log('耗子Linux面板：ajax请求出错，错误' + error);
                        }
                    });
                    layer.close(index);
                });
            } else if (obj.event === 'backup') {
                // 打开备份页面
                admin.popup({
                    title: '备份管理 - ' + data.name
                    , area: ['70%', '80%']
                    , id: 'LAY-popup-postgresql-backup'
                    , success: function (layero, index) {
                        view(this.id).render('plugin/postgresql/backup', {
                            data: data
                        }).done(function () {
                            form.render(null, 'LAY-popup-postgresql-backup');
                        });
                    }
                });
            }
        });

        // 获取数据库用户列表
        table.render({
            elem: '#postgresql-user-list'
            , url: '/api/plugin/postgresql/getUsers'
            , toolbar: '#postgresql-user-list-bar'
            , title: '用户列表'
            , cols: [[
                {field: 'username', title: '用户名', fixed: 'left', width: 300, sort: true}
                , {field: 'role', title: '权限'}
                , {fixed: 'right', title: '操作', toolbar: '#postgresql-user-list-control', width: 150}
            ]]
            /**
             * TODO: 分页
             */
            //, page: true
        });
        // 头工具栏事件
        table.on('toolbar(postgresql-user-list)', function (obj) {
            if (obj.event === 'add_user') {
                admin.popup({
                    title: '新建用户'
                    , area: ['600px', '300px']
                    , id: 'LAY-popup-postgresql-user-add'
                    , success: function (layer, index) {
                        view(this.id).render('plugin/postgresql/add_user', {
                        }).done(function () {
                            form.render(null, 'LAY-popup-postgresql-user-add');
                        });
                    }
                });
            }
        });
        // 行工具事件
        table.on('tool(postgresql-user-list)', function (obj) {
            let data = obj.data;
            if (obj.event === 'del') {
                layer.confirm('高风险操作，确定要删除用户 <b style="color: red;">'+data.username+'</b> 吗？', function (index) {
                    admin.req({
                        url: "/api/plugin/postgresql/deleteUser"
                        , method: 'post'
                        , data: data
                        , success: function (result) {
                            if (result.code !== 0) {
                                console.log('耗子Linux面板：用户删除失败，接口返回' + result);
                                layer.msg('用户删除失败，请刷新重试！')
                                return false;
                            }
                            obj.del();
                            layer.alert('用户' + data.username + '删除成功！');
                        }
                        , error: function (xhr, status, error) {
                            console.log('耗子Linux面板：ajax请求出错，错误' + error);
                        }
                    });
                    layer.close(index);
                });
            }else if(obj.event === 'change_password'){
                // 弹出输入密码框
                layer.prompt({
                    formType: 1
                    , title: '请输入新密码（8位以上大小写数字特殊符号混合）'
                }, function (value, index) {
                    layer.close(index);
                    layer.load(2);
                    // 发送请求
                    admin.req({
                        url: "/api/plugin/postgresql/changePassword"
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

        // 获取postgresql日志并渲染
        admin.req({
            url: "/api/plugin/postgresql/log"
            , method: 'get'
            , success: function (result) {
                if (result.code !== 0) {
                    console.log('耗子Linux面板：PostgreSQL日志获取失败，接口返回' + result);
                    $('#postgresql-log').text('PostgreSQL日志获取失败，请刷新重试！');
                    code({
                        elem: '#postgresql-log'
                        , title: 'error.log'
                        , encode: true
                        , about: false

                    });
                    return false;
                }
                $('#postgresql-log').text(result.data);
                code({
                    elem: '#postgresql-log'
                    , title: 'postgresql.log'
                    , encode: true
                    , about: false

                });
            }
            , error: function (xhr, status, error) {
                console.log('耗子Linux面板：ajax请求出错，错误' + error)
            }
        });

        // 获取postgresql配置并渲染
        admin.req({
            url: "/api/plugin/postgresql/config"
            , method: 'get'
            , success: function (result) {
                if (result.code !== 0) {
                    console.log('耗子Linux面板：PostgreSQL主配置获取失败，接口返回' + result);
                    return false;
                }
                $('#postgresql-config-editor').text(result.data);
                postgresql_config_editor = ace.edit("postgresql-config-editor", {
                    mode: "ace/mode/ini",
                    selectionStyle: "text"
                });
            }
            , error: function (xhr, status, error) {
                console.log('耗子Linux面板：ajax请求出错，错误' + error)
            }
        });

        // 获取postgresql用户配置并渲染
        admin.req({
            url: "/api/plugin/postgresql/userConfig"
            , method: 'get'
            , success: function (result) {
                if (result.code !== 0) {
                    console.log('耗子Linux面板：PostgreSQL用户配置获取失败，接口返回' + result);
                    return false;
                }
                $('#postgresql-user-config-editor').text(result.data);
                postgresql_user_config_editor = ace.edit("postgresql-user-config-editor", {
                    mode: "ace/mode/ini",
                    selectionStyle: "text"
                });
            }
            , error: function (xhr, status, error) {
                console.log('耗子Linux面板：ajax请求出错，错误' + error)
            }
        });

        // 获取postgresql负载状态并渲染
        table.render({
            elem: '#postgresql-load-status'
            , url: '/api/plugin/postgresql/load'
            , cols: [[
                {field: 'name', width: '80%', title: '属性',}
                , {field: 'value', width: '20%', title: '当前值'}
            ]]
        });
        element.render();

        // 事件监听
        $('#postgresql-start').click(function () {
            layer.confirm('确定要启动PostgreSQL吗？', {
                btn: ['启动', '取消']
            }, function () {
                admin.req({
                    url: "/api/plugin/postgresql/start"
                    , method: 'get'
                    , success: function (result) {
                        if (result.code !== 0) {
                            console.log('耗子Linux面板：PostgreSQL启动失败，接口返回' + result);
                            return false;
                        }
                        if (result.msg === 'error') {
                            layer.alert(result.data);
                            return false;
                        }
                        admin.events.refresh();
                        layer.alert('PostgreSQL启动成功！');
                    }
                    , error: function (xhr, status, error) {
                        console.log('耗子Linux面板：ajax请求出错，错误' + error)
                    }
                });
            }, function () {
                layer.msg('取消启动');
            });
        });
        $('#postgresql-stop').click(function () {
            layer.confirm('停止PostgreSQL将导致使用PostgreSQL的网站无法访问，是否继续停止？', {
                btn: ['停止', '取消']
            }, function () {
                admin.req({
                    url: "/api/plugin/postgresql/stop"
                    , method: 'get'
                    , success: function (result) {
                        if (result.code !== 0) {
                            console.log('耗子Linux面板：PostgreSQL停止失败，接口返回' + result);
                            return false;
                        }
                        if (result.msg === 'error') {
                            layer.alert(result.data);
                            return false;
                        }
                        admin.events.refresh();
                        layer.alert('PostgreSQL停止成功！');
                    }
                    , error: function (xhr, status, error) {
                        console.log('耗子Linux面板：ajax请求出错，错误' + error)
                    }
                });
            }, function () {
                layer.msg('取消停止');
            });
        });
        $('#postgresql-restart').click(function () {
            layer.confirm('重启PostgreSQL将导致使用PostgreSQL的网站短时间无法访问，是否继续重启？', {
                btn: ['重启', '取消']
            }, function () {
                admin.req({
                    url: "/api/plugin/postgresql/restart"
                    , method: 'get'
                    , success: function (result) {
                        if (result.code !== 0) {
                            console.log('耗子Linux面板：PostgreSQL重启失败，接口返回' + result);
                            return false;
                        }
                        if (result.msg === 'error') {
                            layer.alert(result.data);
                            return false;
                        }
                        admin.events.refresh();
                        layer.alert('PostgreSQL重启成功！');
                    }
                    , error: function (xhr, status, error) {
                        console.log('耗子Linux面板：ajax请求出错，错误' + error)
                    }
                });
            }, function () {
                layer.msg('取消重启');
            });
        });
        $('#postgresql-reload').click(function () {
            layer.msg('PostgreSQL重载中...');
            admin.req({
                url: "/api/plugin/postgresql/reload"
                , method: 'get'
                , success: function (result) {
                    if (result.code !== 0) {
                        console.log('耗子Linux面板：PostgreSQL重载失败，接口返回' + result);
                        return false;
                    }
                    if (result.msg === 'error') {
                        layer.alert(result.data);
                        return false;
                    }
                    layer.alert('PostgreSQL重载成功！');
                }
                , error: function (xhr, status, error) {
                    console.log('耗子Linux面板：ajax请求出错，错误' + error)
                }
            });
        });
        $('#postgresql-config-save').click(function () {
            layer.msg('PostgreSQL配置保存中...');
            admin.req({
                url: "/api/plugin/postgresql/config"
                , method: 'post'
                , data: {
                    config: postgresql_config_editor.getValue()
                }
                , success: function (result) {
                    if (result.code !== 0) {
                        console.log('耗子Linux面板：PostgreSQL配置保存失败，接口返回' + result);
                        return false;
                    }
                    if (result.msg === 'error') {
                        layer.alert(result.data);
                        return false;
                    }
                    layer.alert('PostgreSQL配置保存成功！');
                }
                , error: function (xhr, status, error) {
                    console.log('耗子Linux面板：ajax请求出错，错误' + error)
                }
            });
        });
        $('#postgresql-user-config-save').click(function () {
            layer.msg('PostgreSQL用户配置保存中...');
            admin.req({
                url: "/api/plugin/postgresql/userConfig"
                , method: 'post'
                , data: {
                    config: postgresql_user_config_editor.getValue()
                }
                , success: function (result) {
                    if (result.code !== 0) {
                        console.log('耗子Linux面板：PostgreSQL用户配置保存失败，接口返回' + result);
                        return false;
                    }
                    if (result.msg === 'error') {
                        layer.alert(result.data);
                        return false;
                    }
                    layer.alert('PostgreSQL用户配置保存成功！');
                }
                , error: function (xhr, status, error) {
                    console.log('耗子Linux面板：ajax请求出错，错误' + error)
                }
            });
        });

    });
</script>
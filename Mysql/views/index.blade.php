<!--
Name: MySQL管理器
Author: 耗子
Date: 2022-11-27
-->
<title>MySQL</title>
<div class="layui-fluid" id="component-tabs">
    <div class="layui-row">
        <div class="layui-col-md12">
            <div class="layui-card">
                <div class="layui-card-header">MySQL管理</div>
                <div class="layui-card-body">
                    <div class="layui-tab">
                        <ul class="layui-tab-title">
                            <li class="layui-this">基本信息</li>
                            <li>管理</li>
                            <li>配置修改</li>
                            <li>负载状态</li>
                            <li>错误日志</li>
                            <li>慢查询日志</li>
                        </ul>
                        <div class="layui-tab-content">
                            <div class="layui-tab-item layui-show">
                                <fieldset class="layui-elem-field layui-field-title" style="margin-top: 30px;">
                                    <legend>运行状态</legend>
                                </fieldset>
                                <blockquote id="mysql-status" class="layui-elem-quote layui-quote-nm">当前状态：<span
                                        class="layui-badge layui-bg-black">获取中</span></blockquote>
                                <div class="layui-btn-container" style="padding-top: 30px;">
                                    <button id="mysql-start" class="layui-btn">启动</button>
                                    <button id="mysql-stop" class="layui-btn layui-btn-danger">停止</button>
                                    <button id="mysql-restart" class="layui-btn layui-btn-warm">重启</button>
                                    <button id="mysql-reload" class="layui-btn layui-btn-normal">重载</button>
                                </div>
                                <fieldset class="layui-elem-field layui-field-title" style="margin-top: 30px;">
                                    <legend>基本设置</legend>
                                </fieldset>
                                <div class="layui-form" lay-filter="mysql_setting">
                                    <div class="layui-form-item">
                                        <label class="layui-form-label" style="font-size: 13px;">root 密码</label>
                                        <div class="layui-input-inline">
                                            <input type="text" name="mysql_root_password" value="获取中ing..." class="layui-input" disabled/>
                                        </div>
                                        <div class="layui-form-mid layui-word-aux">查看/修改MySQL的root密码</div>
                                    </div>
                                    <div class="layui-form-item">
                                        <div class="layui-input-block">
                                            <button class="layui-btn layui-btn-sm" lay-submit lay-filter="mysql_setting_submit">确认修改</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="layui-tab-item">
                                <blockquote class="layui-elem-quote">面板仅集成了部分常用功能，如需更多功能，建议安装 phpMyAdmin 使用。
                                </blockquote>
                                <fieldset class="layui-elem-field layui-field-title" style="margin-top: 30px;">
                                    <legend>数据库列表</legend>
                                </fieldset>
                                <table class="layui-hide" id="mysql-database-list" lay-filter="mysql-database-list"></table>
                                <fieldset class="layui-elem-field layui-field-title" style="margin-top: 30px;">
                                    <legend>用户列表</legend>
                                </fieldset>
                                <table class="layui-hide" id="mysql-user-list" lay-filter="mysql-user-list"></table>
                                <!-- 数据库顶部工具栏 -->
                                <script type="text/html" id="mysql-database-list-bar">
                                    <div class="layui-btn-container">
                                        <button class="layui-btn layui-btn-sm" lay-event="add_database">新建数据库</button>
                                    </div>
                                </script>
                                <!-- 用户顶部工具栏 -->
                                <script type="text/html" id="mysql-user-list-bar">
                                    <div class="layui-btn-container">
                                        <button class="layui-btn layui-btn-sm" lay-event="add_user">新建用户</button>
                                    </div>
                                </script>
                                <!-- 数据库右侧管理 -->
                                <script type="text/html" id="mysql-database-list-control">
                                    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="backup">备份</a>
                                    <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="del">删除</a>
                                </script>
                                <!-- 用户右侧管理 -->
                                <script type="text/html" id="mysql-user-list-control">
                                    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="change_password">改密</a>
                                    <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="del">删除</a>
                                </script>
                            </div>
                            <div class="layui-tab-item">
                                <blockquote class="layui-elem-quote">此处修改的是MySQL主配置文件，如果你不了解各参数的含义，请不要随意修改！<br>
                                    提示：Ctrl+F 搜索关键字，Ctrl+S 保存，Ctrl+H 查找替换！
                                </blockquote>
                                <div id="mysql-config-editor"
                                     style="height: 600px;"></div>
                                <div class="layui-btn-container" style="padding-top: 30px;">
                                    <button id="mysql-config-save" class="layui-btn">保存</button>
                                </div>
                            </div>
                            <div class="layui-tab-item">
                                <table class="layui-hide" id="mysql-load-status"></table>
                            </div>
                            <div class="layui-tab-item">
                                <div class="layui-btn-container">
                                    <button id="mysql-clean-error-log" class="layui-btn">清空日志</button>
                                </div>
                                <pre id="mysql-error-log" class="layui-code">
                                    获取中...
                                </pre>
                            </div>
                            <div class="layui-tab-item">
                                <div class="layui-btn-container">
                                    <button id="mysql-clean-slow-log" class="layui-btn">清空日志</button>
                                </div>
                                <pre id="mysql-slow-log" class="layui-code">
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
    let mysql_config_editor;// 定义mysql配置编辑器的全局变量
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

        // ajax获取设置项并赋值
        admin.req({
            url: "/api/plugin/mysql/getSettings"
            , method: 'get'
            , success: function (result) {
                if (result.code !== 0) {
                    console.log('耗子Linux面板：系统信息获取失败，接口返回' + result);
                    layer.msg('系统信息获取失败，请刷新重试！')
                    return false;
                }
                form.val("mysql_setting",
                    result.data
                );
                $('input').attr('disabled', false);
            }
            , error: function (xhr, status, error) {
                console.log('耗子Linux面板：ajax请求出错，错误' + error);
            }
        });
        // 提交修改
        form.on('submit(mysql_setting_submit)', function (data) {
            admin.req({
                url: "/api/plugin/mysql/saveSettings"
                , method: 'post'
                , data: data.field
                , success: function (result) {
                    if (result.code !== 0) {
                        console.log('耗子Linux面板：MySQL设置保存失败，接口返回' + result);
                        layer.msg('MySQL设置保存失败，请刷新重试！')
                        return false;
                    }
                    layer.msg('修改成功！')
                }
                , error: function (xhr, status, error) {
                    console.log('耗子Linux面板：ajax请求出错，错误' + error);
                }
            });
            return false;
        });

        // 获取mysql运行状态并渲染
        admin.req({
            url: "/api/plugin/mysql/status"
            , method: 'get'
            , success: function (result) {
                if (result.code !== 0) {
                    console.log('耗子Linux面板：MySQL运行状态获取失败，接口返回' + result);
                    return false;
                }
                if (result.data === "running") {
                    $('#mysql-status').html('当前状态：<span class="layui-badge layui-bg-green">运行中</span>');
                } else {
                    $('#mysql-status').html('当前状态：<span class="layui-badge layui-bg-red">已停止</span>');
                }

            }
            , error: function (xhr, status, error) {
                console.log('耗子Linux面板：ajax请求出错，错误' + error)
            }
        });

        // 获取数据库列表
        table.render({
            elem: '#mysql-database-list'
            , url: '/api/plugin/mysql/getDatabases'
            , toolbar: '#mysql-database-list-bar'
            , title: '数据库列表'
            , cols: [[
                {field: 'name', title: '库名', fixed: 'left', unresize: true, sort: true}
                , {fixed: 'right', title: '操作', toolbar: '#mysql-database-list-control', width: 150}
            ]]
            /**
             * TODO: 分页
             */
            //, page: true
        });
        // 头工具栏事件
        table.on('toolbar(mysql-database-list)', function (obj) {
            console.log(obj);
            if (obj.event === 'add_database') {
                admin.popup({
                    title: '新建数据库'
                    , area: ['40%', '40%']
                    , id: 'LAY-popup-mysql-database-add'
                    , success: function (layer, index) {
                        view(this.id).render('plugin/mysql/add_database', {
                        }).done(function () {
                            form.render(null, 'LAY-popup-mysql-database-add');
                        });
                    }
                });
            }
        });
        // 行工具事件
        table.on('tool(mysql-database-list)', function (obj) {
            console.log(obj);
            let data = obj.data;
            if (obj.event === 'del') {
                layer.confirm('高风险操作，确定要删除数据库 <b style="color: red;">'+data.name+'</b> 吗？', function (index) {
                    admin.req({
                        url: "/api/plugin/mysql/deleteDatabase"
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
                    , id: 'LAY-popup-mysql-backup'
                    , success: function (layero, index) {
                        view(this.id).render('plugin/mysql/backup', {
                            data: data
                        }).done(function () {
                            form.render(null, 'LAY-popup-mysql-backup');
                        });
                    }
                });
            }
        });

        // 获取数据库用户列表
        table.render({
            elem: '#mysql-user-list'
            , url: '/api/plugin/mysql/getUsers'
            , toolbar: '#mysql-user-list-bar'
            , title: '用户列表'
            , cols: [[
                {field: 'username', title: '用户名', fixed: 'left', width: 300, sort: true}
                , {field: 'host', title: '主机', width: 250, sort: true}
                , {field: 'privileges', title: '权限'}
                , {fixed: 'right', title: '操作', toolbar: '#mysql-user-list-control', width: 150}
            ]]
            /**
             * TODO: 分页
             */
            //, page: true
        });
        // 头工具栏事件
        table.on('toolbar(mysql-user-list)', function (obj) {
            console.log(obj);
            if (obj.event === 'add_user') {
                admin.popup({
                    title: '新建用户'
                    , area: ['40%', '40%']
                    , id: 'LAY-popup-mysql-user-add'
                    , success: function (layer, index) {
                        view(this.id).render('plugin/mysql/add_user', {
                        }).done(function () {
                            form.render(null, 'LAY-popup-mysql-user-add');
                        });
                    }
                });
            }
        });
        // 行工具事件
        table.on('tool(mysql-user-list)', function (obj) {
            console.log(obj);
            let data = obj.data;
            if (obj.event === 'del') {
                layer.confirm('高风险操作，确定要删除用户 <b style="color: red;">'+data.username+'</b> 吗？', function (index) {
                    admin.req({
                        url: "/api/plugin/mysql/deleteUser"
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
                        url: "/api/plugin/mysql/changePassword"
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

        // 获取mysql错误日志并渲染
        admin.req({
            url: "/api/plugin/mysql/errorLog"
            , method: 'get'
            , success: function (result) {
                if (result.code !== 0) {
                    console.log('耗子Linux面板：MySQL错误日志获取失败，接口返回' + result);
                    $('#mysql-error-log').text('MySQL错误日志获取失败，请刷新重试！');
                    code({
                        elem: '#mysql-error-log'
                        , title: 'error.log'
                        , encode: true
                        , about: false

                    });
                    return false;
                }
                $('#mysql-error-log').text(result.data);
                code({
                    elem: '#mysql-error-log'
                    , title: 'error.log'
                    , encode: true
                    , about: false

                });
            }
            , error: function (xhr, status, error) {
                console.log('耗子Linux面板：ajax请求出错，错误' + error)
            }
        });

        // 获取mysql慢查询日志并渲染
        admin.req({
            url: "/api/plugin/mysql/slowLog"
            , method: 'get'
            , success: function (result) {
                if (result.code !== 0) {
                    console.log('耗子Linux面板：MySQL慢查询日志获取失败，接口返回' + result);
                    $('#mysql-slow-log').text('MySQL慢查询日志获取失败，请刷新重试！');
                    code({
                        elem: '#mysql-slow-log'
                        , title: 'slow.log'
                        , encode: true
                        , about: false

                    });
                    return false;
                }
                $('#mysql-slow-log').text(result.data);
                code({
                    elem: '#mysql-slow-log'
                    , title: 'slow.log'
                    , encode: true
                    , about: false

                });
            }
            , error: function (xhr, status, error) {
                console.log('耗子Linux面板：ajax请求出错，错误' + error)
            }
        });

        // 获取mysql配置并渲染
        admin.req({
            url: "/api/plugin/mysql/config"
            , method: 'get'
            , success: function (result) {
                if (result.code !== 0) {
                    console.log('耗子Linux面板：MySQL主配置获取失败，接口返回' + result);
                    return false;
                }
                $('#mysql-config-editor').text(result.data);
                mysql_config_editor = ace.edit("mysql-config-editor", {
                    mode: "ace/mode/ini",
                    selectionStyle: "text"
                });
            }
            , error: function (xhr, status, error) {
                console.log('耗子Linux面板：ajax请求出错，错误' + error)
            }
        });

        // 获取mysql负载状态并渲染
        table.render({
            elem: '#mysql-load-status'
            , url: '/api/plugin/mysql/load'
            , cols: [[
                {field: 'name', width: '80%', title: '属性',}
                , {field: 'value', width: '20%', title: '当前值'}
            ]]
        });
        element.render();

        // 事件监听
        $('#mysql-start').click(function () {
            layer.confirm('确定要启动MySQL吗？', {
                btn: ['启动', '取消']
            }, function () {
                admin.req({
                    url: "/api/plugin/mysql/start"
                    , method: 'get'
                    , success: function (result) {
                        if (result.code !== 0) {
                            console.log('耗子Linux面板：MySQL启动失败，接口返回' + result);
                            return false;
                        }
                        if (result.msg === 'error') {
                            layer.alert(result.data);
                            return false;
                        }
                        admin.events.refresh();
                        layer.alert('MySQL启动成功！');
                    }
                    , error: function (xhr, status, error) {
                        console.log('耗子Linux面板：ajax请求出错，错误' + error)
                    }
                });
            }, function () {
                layer.msg('取消启动');
            });
        });
        $('#mysql-stop').click(function () {
            layer.confirm('停止MySQL将导致使用MySQL的网站无法访问，是否继续停止？', {
                btn: ['停止', '取消']
            }, function () {
                admin.req({
                    url: "/api/plugin/mysql/stop"
                    , method: 'get'
                    , success: function (result) {
                        if (result.code !== 0) {
                            console.log('耗子Linux面板：MySQL停止失败，接口返回' + result);
                            return false;
                        }
                        if (result.msg === 'error') {
                            layer.alert(result.data);
                            return false;
                        }
                        admin.events.refresh();
                        layer.alert('MySQL停止成功！');
                    }
                    , error: function (xhr, status, error) {
                        console.log('耗子Linux面板：ajax请求出错，错误' + error)
                    }
                });
            }, function () {
                layer.msg('取消停止');
            });
        });
        $('#mysql-restart').click(function () {
            layer.confirm('重启MySQL将导致使用MySQL的网站短时间无法访问，是否继续重启？', {
                btn: ['重启', '取消']
            }, function () {
                admin.req({
                    url: "/api/plugin/mysql/restart"
                    , method: 'get'
                    , success: function (result) {
                        if (result.code !== 0) {
                            console.log('耗子Linux面板：MySQL重启失败，接口返回' + result);
                            return false;
                        }
                        if (result.msg === 'error') {
                            layer.alert(result.data);
                            return false;
                        }
                        admin.events.refresh();
                        layer.alert('MySQL重启成功！');
                    }
                    , error: function (xhr, status, error) {
                        console.log('耗子Linux面板：ajax请求出错，错误' + error)
                    }
                });
            }, function () {
                layer.msg('取消重启');
            });
        });
        $('#mysql-reload').click(function () {
            layer.msg('MySQL重载中...');
            admin.req({
                url: "/api/plugin/mysql/reload"
                , method: 'get'
                , success: function (result) {
                    if (result.code !== 0) {
                        console.log('耗子Linux面板：MySQL重载失败，接口返回' + result);
                        return false;
                    }
                    if (result.msg === 'error') {
                        layer.alert(result.data);
                        return false;
                    }
                    layer.alert('MySQL重载成功！');
                }
                , error: function (xhr, status, error) {
                    console.log('耗子Linux面板：ajax请求出错，错误' + error)
                }
            });
        });
        $('#mysql-config-save').click(function () {
            layer.msg('MySQL配置保存中...');
            admin.req({
                url: "/api/plugin/mysql/config"
                , method: 'post'
                , data: {
                    config: mysql_config_editor.getValue()
                }
                , success: function (result) {
                    if (result.code !== 0) {
                        console.log('耗子Linux面板：MySQL配置保存失败，接口返回' + result);
                        return false;
                    }
                    if (result.msg === 'error') {
                        layer.alert(result.data);
                        return false;
                    }
                    layer.alert('MySQL配置保存成功！');
                }
                , error: function (xhr, status, error) {
                    console.log('耗子Linux面板：ajax请求出错，错误' + error)
                }
            });
        });
        $('#mysql-clean-error-log').click(function () {
            layer.msg('错误日志清空中...');
            admin.req({
                url: "/api/plugin/mysql/cleanErrorLog"
                , method: 'get'
                , success: function (result) {
                    if (result.code !== 0) {
                        console.log('耗子Linux面板：MySQL错误日志清空失败，接口返回' + result);
                        return false;
                    }
                    layer.msg('MySQL错误日志已清空！');
                    setTimeout(function () {
                        admin.events.refresh();
                    }, 1000);
                }
                , error: function (xhr, status, error) {
                    console.log('耗子Linux面板：ajax请求出错，错误' + error)
                }
            });
        });
        $('#mysql-clean-slow-log').click(function () {
            layer.msg('慢查询日志清空中...');
            admin.req({
                url: "/api/plugin/mysql/cleanSlowLog"
                , method: 'get'
                , success: function (result) {
                    if (result.code !== 0) {
                        console.log('耗子Linux面板：MySQL慢查询日志清空失败，接口返回' + result);
                        return false;
                    }
                    layer.msg('MySQL慢查询日志已清空！');
                    setTimeout(function () {
                        admin.events.refresh();
                    }, 1000);
                }
                , error: function (xhr, status, error) {
                    console.log('耗子Linux面板：ajax请求出错，错误' + error)
                }
            });
        });
    });
</script>
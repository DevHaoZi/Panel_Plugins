<!--
Name: MySQL管理器
Author: 耗子
Date: 2022-11-21
-->
<title>MySQL-8</title>
<div class="layui-fluid" id="component-tabs">
    <div class="layui-row">
        <div class="layui-col-md12">
            <div class="layui-card">
                <div class="layui-card-header">MySQL管理</div>
                <div class="layui-card-body">
                    <div class="layui-tab">
                        <ul class="layui-tab-title">
                            <li class="layui-this">运行状态</li>
                            <li>配置修改</li>
                            <li>负载状态</li>
                            <li>错误日志</li>
                            <li>慢查询日志</li>
                        </ul>
                        <div class="layui-tab-content">
                            <div class="layui-tab-item layui-show">
                                <blockquote id="mysql-status" class="layui-elem-quote layui-quote-nm">当前状态：<span
                                        class="layui-badge layui-bg-black">获取中</span></blockquote>
                                <div class="layui-btn-container" style="padding-top: 30px;">
                                    <button id="mysql-start" class="layui-btn">启动</button>
                                    <button id="mysql-stop" class="layui-btn layui-btn-danger">停止</button>
                                    <button id="mysql-restart" class="layui-btn layui-btn-warm">重启</button>
                                    <button id="mysql-reload" class="layui-btn layui-btn-normal">重载</button>
                                </div>
                            </div>
                            <div class="layui-tab-item">
                                <blockquote class="layui-elem-quote">此处修改的是MySQL主配置文件，如果你不了解各参数的含义，请不要随意修改！<br>
                                    提示：Ctrl+F 搜索关键字，Ctrl+S 保存，Ctrl+H 查找替换！
                                </blockquote>
                                <div id="mysql-config-editor"
                                     style="width: -webkit-fill-available; height: 600px;"></div>
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
            , table = layui.table;

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
                    mode: "ace/mode/mysql",
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
                        layer.alert('MySQL启动成功！');
                        admin.events.refresh();
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
                        layer.alert('MySQL停止成功！');
                        admin.events.refresh();
                    }
                    , error: function (xhr, status, error) {
                        console.log('耗子Linux面板：ajax请求出错，错误' + error)
                    }
                });
            }, function () {
                layer.msg('取消重启');
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
                        layer.alert('MySQL重启成功！');
                        admin.events.refresh();
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
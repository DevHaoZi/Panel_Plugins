<!--
Name: PHP-7.4管理器
Author: 耗子
Date: 2022-11-30
-->
<title>PHP-7.4</title>
<div class="layui-fluid" id="component-tabs">
    <div class="layui-row">
        <div class="layui-col-md12">
            <div class="layui-card">
                <div class="layui-card-header">PHP-7.4管理</div>
                <div class="layui-card-body">
                    <div class="layui-tab">
                        <ul class="layui-tab-title">
                            <li class="layui-this">运行状态</li>
                            <li>拓展管理</li>
                            <li>配置修改</li>
                            <li>负载状态</li>
                            <li>运行日志</li>
                            <li>慢日志</li>
                        </ul>
                        <div class="layui-tab-content">
                            <div class="layui-tab-item layui-show">
                                <blockquote id="php74-status" class="layui-elem-quote layui-quote-nm">当前状态：<span
                                            class="layui-badge layui-bg-black">获取中</span></blockquote>
                                <div class="layui-btn-container" style="padding-top: 30px;">
                                    <button id="php74-start" class="layui-btn">启动</button>
                                    <button id="php74-stop" class="layui-btn layui-btn-danger">停止</button>
                                    <button id="php74-restart" class="layui-btn layui-btn-warm">重启</button>
                                    <button id="php74-reload" class="layui-btn layui-btn-normal">重载</button>
                                </div>
                            </div>
                            <div class="layui-tab-item">
                                <table id="php74-extension" lay-filter="php74-extension"></table>
                                <!-- 操作按钮模板 -->
                                <script type="text/html" id="php74-extension-control">
                                    @{{#  console.log(d); }}
                                    @{{#  if(d.control.installed == true){ }}
                                    <a class="layui-btn layui-btn-warm layui-btn-xs" lay-event="uninstall">卸载</a>
                                    @{{#  } else{ }}
                                    <a class="layui-btn layui-btn-xs" lay-event="install">安装</a>
                                    @{{#  } }}
                                </script>
                            </div>
                            <div class="layui-tab-item">
                                <blockquote class="layui-elem-quote">此处修改的是PHP-7.4主配置文件，如果你不了解各参数的含义，请不要随意修改！<br>
                                    提示：Ctrl+F 搜索关键字，Ctrl+S 保存，Ctrl+H 查找替换！
                                </blockquote>
                                <div id="php74-config-editor"
                                     style="height: 600px;"></div>
                                <div class="layui-btn-container" style="padding-top: 30px;">
                                    <button id="php74-config-save" class="layui-btn">保存</button>
                                </div>
                            </div>
                            <div class="layui-tab-item">
                                <table class="layui-hide" id="php74-load-status"></table>
                            </div>
                            <div class="layui-tab-item">
                                <div class="layui-btn-container">
                                    <button id="php74-clean-error-log" class="layui-btn">清空日志</button>
                                </div>
                                <pre id="php74-error-log" class="layui-code">
                                    获取中...
                                </pre>
                            </div>
                            <div class="layui-tab-item">
                                <div class="layui-btn-container">
                                    <button id="php74-clean-slow-log" class="layui-btn">清空日志</button>
                                </div>
                                <pre id="php74-slow-log" class="layui-code">
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
    let php74_config_editor;// 定义php74配置编辑器的全局变量
    layui.use(['index', 'code', 'table'], function () {
        let $ = layui.$
            , admin = layui.admin
            , element = layui.element
            , code = layui.code
            , table = layui.table;

        // 获取php74运行状态并渲染
        admin.req({
            url: "/api/plugin/php74/status"
            , method: 'get'
            , success: function (result) {
                if (result.code !== 0) {
                    console.log('耗子Linux面板：PHP-7.4运行状态获取失败，接口返回' + result);
                    return false;
                }
                if (result.data === "running") {
                    $('#php74-status').html('当前状态：<span class="layui-badge layui-bg-green">运行中</span>');
                } else {
                    $('#php74-status').html('当前状态：<span class="layui-badge layui-bg-red">已停止</span>');
                }

            }
            , error: function (xhr, status, error) {
                console.log('耗子Linux面板：ajax请求出错，错误' + error)
            }
        });

        // 获取php74错误日志并渲染
        admin.req({
            url: "/api/plugin/php74/errorLog"
            , method: 'get'
            , success: function (result) {
                if (result.code !== 0) {
                    console.log('耗子Linux面板：PHP-7.4日志获取失败，接口返回' + result);
                    $('#php74-error-log').text('PHP-7.4日志获取失败，请刷新重试！');
                    code({
                        elem: '#php74-error-log'
                        , title: 'php-fpm.log'
                        , encode: true
                        , about: false

                    });
                    return false;
                }
                $('#php74-error-log').text(result.data);
                code({
                    elem: '#php74-error-log'
                    , title: 'php-fpm.log'
                    , encode: true
                    , about: false

                });
            }
            , error: function (xhr, status, error) {
                console.log('耗子Linux面板：ajax请求出错，错误' + error)
            }
        });

        // 获取php74慢日志并渲染
        admin.req({
            url: "/api/plugin/php74/slowLog"
            , method: 'get'
            , success: function (result) {
                if (result.code !== 0) {
                    console.log('耗子Linux面板：PHP-7.4慢日志获取失败，接口返回' + result);
                    $('#php74-slow-log').text('PHP-7.4慢日志获取失败，请刷新重试！');
                    code({
                        elem: '#php74-slow-log'
                        , title: 'slow.log'
                        , encode: true
                        , about: false

                    });
                    return false;
                }
                $('#php74-slow-log').text(result.data);
                code({
                    elem: '#php74-slow-log'
                    , title: 'slow.log'
                    , encode: true
                    , about: false

                });
            }
            , error: function (xhr, status, error) {
                console.log('耗子Linux面板：ajax请求出错，错误' + error)
            }
        });

        // 获取php74配置并渲染
        admin.req({
            url: "/api/plugin/php74/config"
            , method: 'get'
            , success: function (result) {
                if (result.code !== 0) {
                    console.log('耗子Linux面板：PHP-7.4主配置获取失败，接口返回' + result);
                    return false;
                }
                $('#php74-config-editor').text(result.data);
                php74_config_editor = ace.edit("php74-config-editor", {
                    mode: "ace/mode/ini",
                    selectionStyle: "text"
                });
            }
            , error: function (xhr, status, error) {
                console.log('耗子Linux面板：ajax请求出错，错误' + error)
            }
        });

        // 获取php74负载状态并渲染
        table.render({
            elem: '#php74-load-status'
            , url: '/api/plugin/php74/load'
            , cols: [[
                {field: 'name', width: '80%', title: '属性',}
                , {field: 'value', width: '20%', title: '当前值'}
            ]]
        });
        element.render();

        // 获取php74扩展并渲染
        table.render({
            elem: '#php74-extension'
            , url: '/api/plugin/php74/getExtensionList'
            , cols: [[
                {field: 'slug', hide: true, title: 'Slug', sort: true}
                , {field: 'name', width: '20%', title: '拓展名'}
                , {field: 'describe', width: '70%', title: '描述'}
                , {
                    field: 'control',
                    title: '操作',
                    templet: '#php74-extension-control',
                    fixed: 'right',
                    align: 'left'
                }
            ]]
            , page: false
            , text: {
                none: '暂无拓展'
            }
            , done: function () {
                //element.render('progress');
            }
        });
        // 工具条
        table.on('tool(php74-extension)', function (obj) {
            let data = obj.data;
            if (obj.event === 'install') {
                layer.confirm('确定安装该拓展吗？', function (index) {
                    layer.close(index);
                    admin.req({
                        url: '/api/plugin/php74/installExtension',
                        type: 'POST',
                        data: {
                            slug: data.slug
                        }
                        , success: function (res) {
                            if (res.code === 0) {
                                table.reload('php74-extension');
                                layer.msg('安装：' + data.name + ' 成功加入任务队列', {
                                    icon: 1,
                                    time: 1000
                                });
                            } else {
                                layer.msg(res.msg, {icon: 2, time: 1000});
                            }
                        }
                        , error: function (xhr, status, error) {
                            console.log('耗子Linux面板：ajax请求出错，错误' + error);
                        }
                    });
                });
            } else if (obj.event === 'uninstall') {
                layer.confirm('确定卸载该拓展吗？', function (index) {
                    layer.close(index);
                    admin.req({
                        url: '/api/plugin/php74/uninstallExtension',
                        type: 'POST',
                        data: {
                            slug: data.slug
                        }
                        , success: function (res) {
                            if (res.code === 0) {
                                table.reload('php74-extension');
                                layer.msg('卸载：' + data.name + ' 成功加入任务队列', {icon: 1, time: 1000});
                            } else {
                                layer.msg(res.msg, {icon: 2, time: 1000});
                            }
                        }
                        , error: function (xhr, status, error) {
                            console.log('耗子Linux面板：ajax请求出错，错误' + error);
                        }
                    });
                });
            }
        });

        // 事件监听
        $('#php74-start').click(function () {
            layer.confirm('确定要启动PHP-7.4吗？', {
                btn: ['启动', '取消']
            }, function () {
                admin.req({
                    url: "/api/plugin/php74/start"
                    , method: 'get'
                    , success: function (result) {
                        if (result.code !== 0) {
                            console.log('耗子Linux面板：PHP-7.4启动失败，接口返回' + result);
                            return false;
                        }
                        if (result.msg === 'error') {
                            layer.alert(result.data);
                            return false;
                        }
                        admin.events.refresh();
                        layer.alert('PHP-7.4启动成功！');
                    }
                    , error: function (xhr, status, error) {
                        console.log('耗子Linux面板：ajax请求出错，错误' + error)
                    }
                });
            }, function () {
                layer.msg('取消重启');
            });
        });
        $('#php74-stop').click(function () {
            layer.confirm('停止PHP-7.4将导致使用PHP-7.4的网站无法访问，是否继续停止？', {
                btn: ['重启', '取消']
            }, function () {
                admin.req({
                    url: "/api/plugin/php74/stop"
                    , method: 'get'
                    , success: function (result) {
                        if (result.code !== 0) {
                            console.log('耗子Linux面板：PHP-7.4停止失败，接口返回' + result);
                            return false;
                        }
                        if (result.msg === 'error') {
                            layer.alert(result.data);
                            return false;
                        }
                        admin.events.refresh();
                        layer.alert('PHP-7.4停止成功！');
                    }
                    , error: function (xhr, status, error) {
                        console.log('耗子Linux面板：ajax请求出错，错误' + error)
                    }
                });
            }, function () {
                layer.msg('取消重启');
            });
        });
        $('#php74-restart').click(function () {
            layer.confirm('重启PHP-7.4将导致使用PHP-7.4的网站短时间无法访问，是否继续重启？', {
                btn: ['重启', '取消']
            }, function () {
                admin.req({
                    url: "/api/plugin/php74/restart"
                    , method: 'get'
                    , success: function (result) {
                        if (result.code !== 0) {
                            console.log('耗子Linux面板：PHP-7.4重启失败，接口返回' + result);
                            return false;
                        }
                        if (result.msg === 'error') {
                            layer.alert(result.data);
                            return false;
                        }
                        admin.events.refresh();
                        layer.alert('PHP-7.4重启成功！');
                    }
                    , error: function (xhr, status, error) {
                        console.log('耗子Linux面板：ajax请求出错，错误' + error)
                    }
                });
            }, function () {
                layer.msg('取消重启');
            });
        });
        $('#php74-reload').click(function () {
            layer.msg('PHP-7.4重载中...');
            admin.req({
                url: "/api/plugin/php74/reload"
                , method: 'get'
                , success: function (result) {
                    if (result.code !== 0) {
                        console.log('耗子Linux面板：PHP-7.4重载失败，接口返回' + result);
                        return false;
                    }
                    if (result.msg === 'error') {
                        layer.alert(result.data);
                        return false;
                    }
                    layer.alert('PHP-7.4重载成功！');
                }
                , error: function (xhr, status, error) {
                    console.log('耗子Linux面板：ajax请求出错，错误' + error)
                }
            });
        });
        $('#php74-config-save').click(function () {
            layer.msg('PHP-7.4配置保存中...');
            admin.req({
                url: "/api/plugin/php74/config"
                , method: 'post'
                , data: {
                    config: php74_config_editor.getValue()
                }
                , success: function (result) {
                    if (result.code !== 0) {
                        console.log('耗子Linux面板：PHP-7.4配置保存失败，接口返回' + result);
                        return false;
                    }
                    if (result.msg === 'error') {
                        layer.alert(result.data);
                        return false;
                    }
                    layer.alert('PHP-7.4配置保存成功！');
                }
                , error: function (xhr, status, error) {
                    console.log('耗子Linux面板：ajax请求出错，错误' + error)
                }
            });
        });
        $('#php74-clean-error-log').click(function () {
            layer.msg('日志清空中...');
            admin.req({
                url: "/api/plugin/php74/cleanErrorLog"
                , method: 'get'
                , success: function (result) {
                    if (result.code !== 0) {
                        console.log('耗子Linux面板：PHP-7.4日志清空失败，接口返回' + result);
                        return false;
                    }
                    layer.msg('PHP-7.4日志已清空！');
                    setTimeout(function () {
                        admin.events.refresh();
                    }, 1000);
                }
                , error: function (xhr, status, error) {
                    console.log('耗子Linux面板：ajax请求出错，错误' + error)
                }
            });
        });
        $('#php74-clean-slow-log').click(function () {
            layer.msg('日志清空中...');
            admin.req({
                url: "/api/plugin/php74/cleanSlowLog"
                , method: 'get'
                , success: function (result) {
                    if (result.code !== 0) {
                        console.log('耗子Linux面板：PHP-7.4慢日志清空失败，接口返回' + result);
                        return false;
                    }
                    layer.msg('PHP-7.4慢日志已清空！');
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
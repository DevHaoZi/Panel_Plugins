<!--
Name: Redis管理器
Author: 耗子
Date: 2022-12-10
-->
<title>Redis</title>
<div class="layui-fluid" id="component-tabs">
    <div class="layui-row">
        <div class="layui-col-md12">
            <div class="layui-card">
                <div class="layui-card-header">Redis管理</div>
                <div class="layui-card-body">
                    <div class="layui-tab">
                        <ul class="layui-tab-title">
                            <li class="layui-this">运行状态</li>
                            <li>配置修改</li>
                            <li>负载状态</li>
                        </ul>
                        <div class="layui-tab-content">
                            <div class="layui-tab-item layui-show">
                                <blockquote id="redis-status" class="layui-elem-quote layui-quote-nm">当前状态：<span
                                            class="layui-badge layui-bg-black">获取中</span></blockquote>
                                <div class="layui-btn-container" style="padding-top: 30px;">
                                    <button id="redis-start" class="layui-btn">启动</button>
                                    <button id="redis-stop" class="layui-btn layui-btn-danger">停止</button>
                                    <button id="redis-restart" class="layui-btn layui-btn-warm">重启</button>
                                    <button id="redis-reload" class="layui-btn layui-btn-normal">重载</button>
                                </div>
                            </div>
                            <div class="layui-tab-item">
                                <blockquote class="layui-elem-quote">此处修改的是Redis主配置文件，如果你不了解各参数的含义，请不要随意修改！<br>
                                    提示：Ctrl+F 搜索关键字，Ctrl+S 保存，Ctrl+H 查找替换！
                                </blockquote>
                                <div id="redis-config-editor"
                                     style="height: 600px;"></div>
                                <div class="layui-btn-container" style="padding-top: 30px;">
                                    <button id="redis-config-save" class="layui-btn">保存</button>
                                </div>
                            </div>
                            <div class="layui-tab-item">
                                <table class="layui-hide" id="redis-load-status"></table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let redis_config_editor;// 定义redis配置编辑器的全局变量
    layui.use(['index', 'code', 'table'], function () {
        let $ = layui.$
            , admin = layui.admin
            , element = layui.element
            , table = layui.table;

        // 获取redis运行状态并渲染
        admin.req({
            url: "/api/plugin/redis/status"
            , method: 'get'
            , success: function (result) {
                if (result.code !== 0) {
                    console.log('耗子Linux面板：Redis运行状态获取失败，接口返回' + result);
                    return false;
                }
                if (result.data === "running") {
                    $('#redis-status').html('当前状态：<span class="layui-badge layui-bg-green">运行中</span>');
                } else {
                    $('#redis-status').html('当前状态：<span class="layui-badge layui-bg-red">已停止</span>');
                }

            }
            , error: function (xhr, status, error) {
                console.log('耗子Linux面板：ajax请求出错，错误' + error)
            }
        });

        // 获取redis配置并渲染
        admin.req({
            url: "/api/plugin/redis/config"
            , method: 'get'
            , success: function (result) {
                if (result.code !== 0) {
                    console.log('耗子Linux面板：Redis主配置获取失败，接口返回' + result);
                    return false;
                }
                $('#redis-config-editor').text(result.data);
                redis_config_editor = ace.edit("redis-config-editor", {
                    mode: "ace/mode/ini",
                    selectionStyle: "text"
                });
            }
            , error: function (xhr, status, error) {
                console.log('耗子Linux面板：ajax请求出错，错误' + error)
            }
        });

        // 获取redis负载状态并渲染
        table.render({
            elem: '#redis-load-status'
            , url: '/api/plugin/redis/load'
            , cols: [[
                {field: 'name', width: '80%', title: '属性',}
                , {field: 'value', width: '20%', title: '当前值'}
            ]]
        });
        element.render();

        // 事件监听
        $('#redis-start').click(function () {
            layer.confirm('确定要启动Redis吗？', {
                btn: ['启动', '取消']
            }, function () {
                index = layer.msg('正在启动Redis，请稍候...', {icon: 16, time: 0});
                admin.req({
                    url: "/api/plugin/redis/start"
                    , method: 'post'
                    , success: function (result) {
                        layer.close(index);
                        if (result.code !== 0) {
                            console.log('耗子Linux面板：Redis启动失败，接口返回' + result);
                            return false;
                        }
                        admin.events.refresh();
                        layer.alert('Redis启动成功！');
                    }
                    , error: function (xhr, status, error) {
                        console.log('耗子Linux面板：ajax请求出错，错误' + error)
                    }
                });
            });
        });
        $('#redis-stop').click(function () {
            layer.confirm('停止Redis将导致使用Redis的网站出现异常，是否继续停止？', {
                btn: ['停止', '取消']
            }, function () {
                index = layer.msg('正在停止Redis，请稍候...', {icon: 16, time: 0});
                admin.req({
                    url: "/api/plugin/redis/stop"
                    , method: 'post'
                    , success: function (result) {
                        layer.close(index);
                        if (result.code !== 0) {
                            console.log('耗子Linux面板：Redis停止失败，接口返回' + result);
                            return false;
                        }
                        admin.events.refresh();
                        layer.alert('Redis停止成功！');
                    }
                    , error: function (xhr, status, error) {
                        console.log('耗子Linux面板：ajax请求出错，错误' + error)
                    }
                });
            });
        });
        $('#redis-restart').click(function () {
            layer.confirm('重启Redis将导致使用Redis的网站短时间出现异常，是否继续重启？', {
                btn: ['重启', '取消']
            }, function () {
                index = layer.msg('正在重启Redis，请稍候...', {icon: 16, time: 0});
                admin.req({
                    url: "/api/plugin/redis/restart"
                    , method: 'post'
                    , success: function (result) {
                        layer.close(index);
                        if (result.code !== 0) {
                            console.log('耗子Linux面板：Redis重启失败，接口返回' + result);
                            return false;
                        }
                        admin.events.refresh();
                        layer.alert('Redis重启成功！');
                    }
                    , error: function (xhr, status, error) {
                        console.log('耗子Linux面板：ajax请求出错，错误' + error)
                    }
                });
            });
        });
        $('#redis-reload').click(function () {
            index = layer.msg('正在重载Redis，请稍候...', {icon: 16, time: 0});
            admin.req({
                url: "/api/plugin/redis/reload"
                , method: 'post'
                , success: function (result) {
                    layer.close(index);
                    if (result.code !== 0) {
                        console.log('耗子Linux面板：Redis重载失败，接口返回' + result);
                        return false;
                    }
                    layer.alert('Redis重载成功！');
                }
                , error: function (xhr, status, error) {
                    console.log('耗子Linux面板：ajax请求出错，错误' + error)
                }
            });
        });
        $('#redis-config-save').click(function () {
            index = layer.msg('正在保存Redis配置，请稍候...', {icon: 16, time: 0});
            admin.req({
                url: "/api/plugin/redis/config"
                , method: 'post'
                , data: {
                    config: redis_config_editor.getValue()
                }
                , success: function (result) {
                    layer.close(index);
                    if (result.code !== 0) {
                        console.log('耗子Linux面板：Redis配置保存失败，接口返回' + result);
                        return false;
                    }
                    layer.alert('Redis配置保存成功！');
                }
                , error: function (xhr, status, error) {
                    console.log('耗子Linux面板：ajax请求出错，错误' + error)
                }
            });
        });
    });
</script>
<!--
Name: Fail2ban管理器
Author: 耗子
Date: 2022-12-09
-->
<title>Fail2ban</title>
<div class="layui-fluid" id="component-tabs">
    <div class="layui-row">
        <div class="layui-col-md12">
            <div class="layui-card">
                <div class="layui-card-header">Fail2ban 运行状态</div>
                <div class="layui-card-body">
                    <blockquote id="fail2ban-status" class="layui-elem-quote layui-quote-nm">当前状态：<span
                                class="layui-badge layui-bg-black">获取中</span></blockquote>
                    <div class="layui-btn-container" style="padding-top: 30px;">
                        <button id="fail2ban-start" class="layui-btn">启动</button>
                        <button id="fail2ban-stop" class="layui-btn layui-btn-danger">停止</button>
                        <button id="fail2ban-restart" class="layui-btn layui-btn-warm">重启</button>
                        <button id="fail2ban-reload" class="layui-btn layui-btn-normal">重载</button>
                    </div>
                    <fieldset class="layui-elem-field layui-field-title" style="margin-top: 30px;">
                        <legend>基本设置</legend>
                    </fieldset>
                    <div class="layui-form" lay-filter="fail2ban_setting">
                        <div class="layui-form-item">
                            <label class="layui-form-label" style="font-size: 13px;">IP白名单</label>
                            <div class="layui-input-inline">
                                <input type="text" name="fail2ban_white_list" value="获取中ing..." class="layui-input"
                                       disabled/>
                            </div>
                            <div class="layui-form-mid layui-word-aux">IP白名单，以英文逗号,分隔</div>
                        </div>
                        <div class="layui-form-item">
                            <div class="layui-input-block">
                                <button class="layui-btn layui-btn-sm" lay-submit lay-filter="fail2ban_setting_submit">
                                    确认修改
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="layui-card">
                <div class="layui-card-header">Fail2ban 规则列表</div>
                <div class="layui-card-body">
                    <table class="layui-hide" id="fail2ban-rule-list" lay-filter="fail2ban-rule-list"></table>
                    <!-- 顶部工具栏 -->
                    <script type="text/html" id="fail2ban-rule-list-bar">
                        <div class="layui-btn-container">
                            <button class="layui-btn layui-btn-sm" lay-event="add_rule">新建规则</button>
                        </div>
                    </script>
                    <!-- 右侧管理 -->
                    <script type="text/html" id="fail2ban-rule-list-control">
                        <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="view">查看</a>
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
            url: "/api/plugin/fail2ban/status"
            , method: 'get'
            , success: function (result) {
                if (result.code !== 0) {
                    console.log('耗子Linux面板：Fail2ban运行状态获取失败，接口返回' + result);
                    return false;
                }
                if (result.data) {
                    $('#fail2ban-status').html('当前状态：<span class="layui-badge layui-bg-green">运行中</span>');
                } else {
                    $('#fail2ban-status').html('当前状态：<span class="layui-badge layui-bg-red">已停止</span>');
                }
            }
            , error: function (xhr, status, error) {
                console.log('耗子Linux面板：ajax请求出错，错误' + error)
            }
        });

        // 获取白名单并渲染
        admin.req({
            url: "/api/plugin/fail2ban/getWhiteList"
            , method: 'get'
            , success: function (result) {
                if (result.code !== 0) {
                    console.log('耗子Linux面板：Fail2ban 白名单获取失败，接口返回' + result);
                    return false;
                }
                $('input[name=fail2ban_white_list]').val(result.data);
                $('input').attr('disabled', false);
                form.render();
            }
            , error: function (xhr, status, error) {
                console.log('耗子Linux面板：ajax请求出错，错误' + error);
            }
        });

        // 监听提交
        form.on('submit(fail2ban_setting_submit)', function (data) {
            data.field.ip = $('input[name=fail2ban_white_list]').val();
            index = layer.msg('请稍候...', {icon: 16, time: 0});
            admin.req({
                url: "/api/plugin/fail2ban/setWhiteList"
                , method: 'post'
                , data: data.field
                , success: function (result) {
                    layer.close(index);
                    if (result.code !== 0) {
                        console.log('耗子Linux面板：Fail2ban 白名单设置失败，接口返回' + result);
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

        let websiteList = [];
        admin.req({
            url: '/api/panel/website/getList'
            , type: 'get'
            , async: false
            , success: function (res) {
                websiteList = res.data;
            }
            , error: function (xhr, status, error) {
                layer.open({
                    title: '错误'
                    , icon: 2
                    , content: '网站列表获取失败，接口返回' + xhr.status + ' ' + xhr.statusText
                });
                console.log('耗子Linux面板：ajax请求出错，错误' + error);
            }
        });

        // 获取规则列表
        table.render({
            elem: '#fail2ban-rule-list'
            , url: '/api/plugin/fail2ban/getList'
            , toolbar: '#fail2ban-rule-list-bar'
            , title: 'Fail2ban 规则列表'
            , cols: [[
                {field: 'name', title: '规则名', fixed: 'left', unresize: true, sort: true}
                , {fixed: 'right', title: '操作', toolbar: '#fail2ban-rule-list-control', width: 150}
            ]]
            , page: true
        });
        // 头工具栏事件
        table.on('toolbar(fail2ban-rule-list)', function (obj) {
            if (obj.event === 'add_rule') {
                index = layer.msg('加载中...', {
                    icon: 16
                    , time: 0
                });
                admin.popup({
                    title: '新建Fail2ban规则'
                    , area: ['600px', '600px']
                    , id: 'LAY-popup-fail2ban-rule-add'
                    , success: function () {
                        layer.close(index);
                        view(this.id).render('plugin/fail2ban/add_rule', {
                            websiteList: websiteList
                        }).done(function () {
                            form.render(null, 'LAY-popup-fail2ban-rule-add');
                        });
                    }
                });
            }
        });
        // 行工具事件
        table.on('tool(fail2ban-rule-list)', function (obj) {
            let data = obj.data;
            if (obj.event === 'del') {
                layer.confirm('确定要删除 <b style="color: red;">' + data.name + '</b> 吗？', function (index) {
                    index = layer.msg('请稍等...', {
                        icon: 16
                        , time: 0
                    });
                    admin.req({
                        url: "/api/plugin/fail2ban/deleteRule"
                        , method: 'post'
                        , data: data
                        , success: function (result) {
                            layer.close(index);
                            if (result.code !== 0) {
                                console.log('耗子Linux面板：fail2ban规则删除失败，接口返回' + result);
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
            } else if (obj.event === 'view') {
                index = layer.msg('加载中...', {
                    icon: 16
                    , time: 0
                });
                admin.req({
                    url: "/api/plugin/fail2ban/getBanList?name=" + data.name
                    , method: 'get'
                    , success: function (result) {
                        layer.close(index);
                        if (result.code !== 0) {
                            console.log('耗子Linux面板：fail2ban规则获取失败，接口返回' + result);
                            return false;
                        }
                        admin.popup({
                            title: '查看Fail2ban规则'
                            , area: ['600px', '600px']
                            , id: 'LAY-popup-fail2ban-rule-view'
                            , success: function () {
                                layer.close(index);
                                view(this.id).render('plugin/fail2ban/view_rule', {
                                    data: data
                                    , banList: result.data
                                }).done(function () {
                                    form.render(null, 'LAY-popup-fail2ban-rule-view');
                                });
                            }
                        });
                    }
                });
            }
        });

        // 事件监听
        $('#fail2ban-start').click(function () {
            layer.confirm('确定要启动Fail2ban吗？', {
                btn: ['启动', '取消']
            }, function () {
                index = layer.msg('请稍等...', {
                    icon: 16
                    , time: 0
                });
                admin.req({
                    url: "/api/plugin/fail2ban/start"
                    , method: 'post'
                    , success: function (result) {
                        layer.close(index);
                        if (result.code !== 0) {
                            console.log('耗子Linux面板：Fail2ban启动失败，接口返回' + result);
                            return false;
                        }
                        admin.events.refresh();
                        layer.alert('Fail2ban启动成功！');
                    }
                    , error: function (xhr, status, error) {
                        console.log('耗子Linux面板：ajax请求出错，错误' + error)
                    }
                });
            });
        });
        $('#fail2ban-stop').click(function () {
            layer.confirm('停止Fail2ban将导致Fail2ban防护失效，是否继续停止？', {
                btn: ['停止', '取消']
            }, function () {
                index = layer.msg('请稍等...', {
                    icon: 16
                    , time: 0
                });
                admin.req({
                    url: "/api/plugin/fail2ban/stop"
                    , method: 'post'
                    , success: function (result) {
                        layer.close(index);
                        if (result.code !== 0) {
                            console.log('耗子Linux面板：Fail2ban停止失败，接口返回' + result);
                            return false;
                        }
                        admin.events.refresh();
                        layer.alert('Fail2ban停止成功！');
                    }
                    , error: function (xhr, status, error) {
                        console.log('耗子Linux面板：ajax请求出错，错误' + error)
                    }
                });
            });
        });
        $('#fail2ban-restart').click(function () {
            layer.confirm('确定要重启Fail2ban吗？', {
                btn: ['重启', '取消']
            }, function () {
                index = layer.msg('请稍等...', {
                    icon: 16
                    , time: 0
                });
                admin.req({
                    url: "/api/plugin/fail2ban/restart"
                    , method: 'post'
                    , success: function (result) {
                        layer.close(index);
                        if (result.code !== 0) {
                            console.log('耗子Linux面板：Fail2ban重启失败，接口返回' + result);
                            return false;
                        }
                        admin.events.refresh();
                        layer.alert('Fail2ban重启成功！');
                    }
                    , error: function (xhr, status, error) {
                        console.log('耗子Linux面板：ajax请求出错，错误' + error)
                    }
                });
            });
        });
        $('#fail2ban-reload').click(function () {
            index = layer.msg('请稍等...', {
                icon: 16
                , time: 0
            });
            admin.req({
                url: "/api/plugin/fail2ban/reload"
                , method: 'post'
                , success: function (result) {
                    layer.close(index);
                    if (result.code !== 0) {
                        console.log('耗子Linux面板：Fail2ban重载失败，接口返回' + result);
                        return false;
                    }
                    layer.alert('Fail2ban重载成功！');
                }
                , error: function (xhr, status, error) {
                    console.log('耗子Linux面板：ajax请求出错，错误' + error)
                }
            });
        });
    });
</script>
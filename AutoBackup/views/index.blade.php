<!--
Name: 自动备份管理器
Author: 耗子
Date: 2022-12-04
-->
<title>自动备份</title>
<div class="layui-fluid" id="component-tabs">
    <div class="layui-row">
        <div class="layui-col-md12">
            <div class="layui-card">
                <div class="layui-card-header">自动备份任务列表</div>
                <div class="layui-card-body">
                    <table class="layui-hide" id="auto-backup-list" lay-filter="auto-backup-list"></table>
                    <!-- 顶部工具栏 -->
                    <script type="text/html" id="auto-backup-list-bar">
                        <div class="layui-btn-container">
                            <button class="layui-btn layui-btn-sm" lay-event="add_backup">新建备份任务</button>
                        </div>
                    </script>
                    <!-- 右侧管理 -->
                    <script type="text/html" id="auto-backup-list-control">
                        <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="edit">管理</a>
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
            , element = layui.element
            , code = layui.code
            , table = layui.table
            , form = layui.form
            , view = layui.view;

        // 获取备份任务列表
        table.render({
            elem: '#auto-backup-list'
            , url: '/api/plugin/auto-backup/getTaskList'
            , toolbar: '#auto-backup-list-bar'
            , title: '自动备份任务列表'
            , cols: [[
                {field: 'id', hide: true, title: 'ID', sort: true}
                , {field: 'name', title: '任务名', fixed: 'left', unresize: true, sort: true}
                , {fixed: 'right', title: '操作', toolbar: '#auto-backup-list-control', width: 150}
            ]]
            , page: true
        });
        // 获取网站列表
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
        // 获取数据库列表
        let mysqlList = [];
        let postgresqlList = [];
        admin.req({
            url: "/api/panel/info/getInstalledDbAndPhp"
            , method: 'get'
            , success: function (result) {
                if (result.code !== 0) {
                    console.log('耗子Linux面板：已安装的PHP和DB版本获取失败，接口返回' + result);
                    layer.msg('已安装的PHP和DB版本获取失败，请刷新重试！')
                    return false;
                }
                if (result.data.db_version.mysql !== false) {
                    admin.req({
                        url: '/api/plugin/mysql/getDatabases'
                        , type: 'get'
                        , async: false
                        , success: function (res) {
                            mysqlList = res.data;
                        }
                        , error: function (xhr, status, error) {
                            layer.open({
                                title: '错误'
                                , icon: 2
                                , content: 'MySQL数据库列表获取失败，接口返回' + xhr.status + ' ' + xhr.statusText
                            });
                            console.log('耗子Linux面板：ajax请求出错，错误' + error);
                        }
                    });
                }
                if (result.data.db_version.postgresql !== false) {
                    admin.req({
                        url: '/api/plugin/postgresql/getDatabases'
                        , type: 'get'
                        , async: false
                        , success: function (res) {
                            postgresqlList = res.data;
                        }
                        , error: function (xhr, status, error) {
                            layer.open({
                                title: '错误'
                                , icon: 2
                                , content: 'PostgreSQL数据库列表获取失败，接口返回' + xhr.status + ' ' + xhr.statusText
                            });
                            console.log('耗子Linux面板：ajax请求出错，错误' + error);
                        }
                    });
                }
            }
            , error: function (xhr, status, error) {
                layer.open({
                    title: '错误'
                    , icon: 2
                    , content: '已安装的PHP和DB版本获取失败，接口返回' + xhr.status + ' ' + xhr.statusText
                });
                console.log('耗子Linux面板：ajax请求出错，错误' + error);
            }
        });
        // 头工具栏事件
        table.on('toolbar(auto-backup-list)', function (obj) {
            if (obj.event === 'add_backup') {
                admin.popup({
                    title: '新建自动备份任务'
                    , area: ['600px', '400px']
                    , id: 'LAY-popup-auto-backup-add'
                    , success: function (layer, index) {
                        view(this.id).render('plugin/auto-backup/add_backup', {
                            websiteList: websiteList
                            , mysqlList: mysqlList
                            , postgresqlList: postgresqlList
                        }).done(function () {
                            form.render(null, 'LAY-popup-auto-backup-backup-add');
                        });
                    }
                });
            }
        });
        // 行工具事件
        table.on('tool(auto-backup-list)', function (obj) {
            let data = obj.data;
            if (obj.event === 'del') {
                layer.confirm('确定要删除 <b style="color: red;">' + data.name + '</b> 吗？', function (index) {
                    index = layer.msg('请稍候...', {icon: 16, time: 0});
                    admin.req({
                        url: "/api/plugin/auto-backup/deleteTask"
                        , method: 'post'
                        , data: data
                        , success: function (result) {
                            layer.close(index);
                            if (result.code !== 0) {
                                console.log('耗子Linux面板：自动备份任务删除失败，接口返回' + result);
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
            } else if (obj.event === 'edit') {
                // 提示前往计划任务编辑
                layer.alert('请前往计划任务管理自动备份任务！');
            }
        });
    });
</script>
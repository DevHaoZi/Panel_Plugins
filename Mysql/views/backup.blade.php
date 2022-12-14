<!--
Name: MySQL管理器 - 数据库备份
Author: 耗子
Date: 2022-12-04
-->
<script type="text/html" template lay-done="layui.data.sendParams(d.params)">
    <div class="layui-row">
        <div class="layui-col-xs12 layui-col-sm12 layui-col-md12">
            <table class="layui-hide" id="mysql-backup-list" lay-filter="mysql-backup-list"></table>
        </div>
    </div>
</script>
<!-- 备份顶部工具栏 -->
<script type="text/html" id="mysql-database-backup-bar">
    <div class="layui-btn-container">
        <button class="layui-btn layui-btn-sm" lay-event="backup_database">备份数据库</button>
        <button class="layui-btn layui-btn-sm" id="upload_mysql_backup">上传备份</button>
    </div>
</script>
<!-- 备份右侧管理 -->
<script type="text/html" id="mysql-database-backup-control">
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="restore">恢复</a>
    <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="del">删除</a>
</script>
<script>
    layui.data.sendParams = function (params) {
        layui.use(['admin', 'form', 'laydate', 'code'], function () {
            var $ = layui.$
                , admin = layui.admin
                , layer = layui.layer
                , table = layui.table
                , upload = layui.upload;

            // 渲染表格
            table.render({
                elem: '#mysql-backup-list'
                , url: '/api/plugin/mysql/getBackupList'
                , toolbar: '#mysql-database-backup-bar'
                , title: '备份列表'
                , cols: [[
                    {field: 'backup', title: '备份名称', width: 500}
                    , {field: 'size', title: '文件大小'}
                    , {field: 'right', title: '操作', width: 150, toolbar: '#mysql-database-backup-control'}
                ]]
                , text: {
                    none: '无备份数据'
                }
                , done: function (res, curr, count) {
                    upload.render({
                        elem: '#upload_mysql_backup'
                        , url: '/api/plugin/mysql/uploadBackup'
                        , accept: 'file'
                        , ext: 'sql|zip|rar|tar|gz|bz2'
                        , before: function (obj) {
                            index = layer.msg('正在上传备份文件，可能需要较长时间，请勿操作...', {
                                icon: 16
                                , time: 0
                            });
                        }
                        , done: function (res) {
                            layer.close(index);
                            layer.msg('上传成功！', {icon: 1});
                            table.reload('mysql-backup-list');
                        }
                        , error: function (res) {
                            layer.msg('上传失败：' + res.msg, {icon: 2});
                        }
                    });
                }
            });
            // 头工具栏事件
            table.on('toolbar(mysql-backup-list)', function (obj) {
                if (obj.event === 'backup_database') {
                    index = layer.msg('正在备份数据库，请稍等...', {
                        icon: 16
                        , time: 0
                    });
                    admin.req({
                        url: '/api/plugin/mysql/createBackup'
                        , type: 'post'
                        , data: {
                            name: params.data.name
                        }
                        , success: function (result) {
                            layer.close(index);
                            if (result.code !== 0) {
                                console.log('耗子Linux面板：备份数据库失败，接口返回' + result);
                                layer.alert('备份失败！');
                                return false;
                            }
                            table.reload('mysql-backup-list');
                            layer.msg('备份成功！', {icon: 1});
                        }
                        , error: function (xhr, status, error) {
                            console.log('耗子Linux面板：ajax请求出错，错误' + error);
                        }
                    });
                }
            });
            // 行工具事件
            table.on('tool(mysql-backup-list)', function (obj) {
                let data = obj.data;
                if (obj.event === 'del') {
                    layer.confirm('确定要删除数据库备份 <b style="color: red;">' + data.backup + '</b> 吗？', function (index) {
                        index = layer.msg('正在删除数据库备份，请稍等...', {
                            icon: 16
                            , time: 0
                        });
                        admin.req({
                            url: "/api/plugin/mysql/deleteBackup"
                            , method: 'post'
                            , data: data
                            , success: function (result) {
                                layer.close(index);
                                if (result.code !== 0) {
                                    console.log('耗子Linux面板：数据库备份删除失败，接口返回' + result);
                                    layer.msg('数据库备份删除失败，请刷新重试！')
                                    return false;
                                }
                                obj.del();
                                layer.alert('数据库备份' + data.backup + '删除成功！');
                            }
                            , error: function (xhr, status, error) {
                                console.log('耗子Linux面板：ajax请求出错，错误' + error);
                            }
                        });
                    });
                } else if (obj.event === 'restore') {
                    layer.confirm('高风险操作，确定要恢复数据库备份 <b style="color: red;">' + data.backup + '</b> 吗？', function (index) {
                        index = layer.msg('正在恢复数据库备份，可能需要较长时间，请勿操作...', {
                            icon: 16
                            , time: 0
                        });
                        data.name = params.data.name;
                        admin.req({
                            url: "/api/plugin/mysql/restoreBackup"
                            , method: 'post'
                            , data: data
                            , success: function (result) {
                                layer.close(index);
                                if (result.code !== 0) {
                                    console.log('耗子Linux面板：数据库恢复失败，接口返回' + result);
                                    layer.msg('数据库备份恢复失败，请刷新重试！')
                                    return false;
                                }
                                layer.alert('数据库备份' + data.backup + '恢复成功！');
                            }
                            , error: function (xhr, status, error) {
                                console.log('耗子Linux面板：ajax请求出错，错误' + error);
                            }
                        });
                    });
                }
            });
        });
    };
</script>
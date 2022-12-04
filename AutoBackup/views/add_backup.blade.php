<!--
Name: 自动备份插件 - 添加备份任务
Author: 耗子
Date: 2022-12-04
-->
<script type="text/html" template lay-done="layui.data.sendParams(d.params)">
    <form class="layui-form" action="" lay-filter="add-auto-backup-form">
        <div class="layui-form-item">
            <label class="layui-form-label">备份类型</label>
            <div class="layui-input-block">
                <input type="radio" lay-filter="add-auto-backup-type-radio" name="backup_type" value="website"
                       title="网站目录" checked="">
                <input type="radio" lay-filter="add-auto-backup-type-radio" name="backup_type" value="mysql"
                       title="MySQL数据库">
                <input type="radio" lay-filter="add-auto-backup-type-radio" name="backup_type" value="postgresql"
                       title="PostgreSQL数据库">
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">执行周期</label>
            <div class="layui-input-inline">
                <input type="text" name="time" id="add-auto-backup-time"
                       lay-verify="required" placeholder="请选择或输入cron表达式" class="layui-input"/>
            </div>
            <div class="layui-form-mid layui-word-aux">请务必正确填写执行周期</div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">备份目录</label>
            <div class="layui-input-block">
                <input type="text" name="path" id="add-auto-backup-path"
                       lay-verify="required" placeholder="请输入备份目录" class="layui-input"
                       value="/www/backup/website"/>
            </div>
            <div class="layui-form-mid layui-word-aux">备份目录可以为对象存储挂载目录，默认为面板默认备份目录</div>
        </div>
        <div id="add-auto-backup-website-input" class="layui-form-item">
            <label class="layui-form-label">网站</label>
            <div class="layui-input-block">
                <select name="website_name" lay-filter="add-auto-backup-website">
                    @{{# layui.each(d.params.websiteList, function(index, item){ }}
                    @{{# if(index == 0){ }}
                    <option value="@{{ item.name }}" selected="">@{{ item.name }}</option>
                    @{{# }else{ }}
                    <option value="@{{ item.name }}">@{{ item.name }}</option>
                    @{{# } }}
                    @{{# }); }}
                </select>
            </div>
        </div>
        <div id="add-auto-backup-mysql-input" class="layui-form-item">
            <label class="layui-form-label">MySQL数据库</label>
            <div class="layui-input-block">
                <select name="mysql_name" lay-filter="add-auto-backup-mysql">
                    @{{# layui.each(d.params.mysqlList, function(index, item){ }}
                    @{{# if(index == 0){ }}
                    <option value="@{{ item.name }}" selected="">@{{ item.name }}</option>
                    @{{# }else{ }}
                    <option value="@{{ item.name }}">@{{ item.name }}</option>
                    @{{# } }}
                    @{{# }); }}
                </select>
            </div>
        </div>
        <div id="add-auto-backup-postgresql-input" class="layui-form-item">
            <label class="layui-form-label">PostgreSQL数据库</label>
            <div class="layui-input-block">
                <select name="postgresql_name" lay-filter="add-auto-backup-postgresql">
                    @{{# layui.each(d.params.postgresqlList, function(index, item){ }}
                    @{{# if(index == 0){ }}
                    <option value="@{{ item.name }}" selected="">@{{ item.name }}</option>
                    @{{# }else{ }}
                    <option value="@{{ item.name }}">@{{ item.name }}</option>
                    @{{# } }}
                    @{{# }); }}
                </select>
            </div>
        </div>
        <div class="layui-form-item">
            <div class="layui-input-block">
                <div class="layui-footer">
                    <button class="layui-btn" lay-submit="" lay-filter="add-auto-backup-submit">立即提交</button>
                    <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                </div>
            </div>
        </div>
    </form>
</script>
<script>
    layui.data.sendParams = function (params) {
        layui.use(['admin', 'form', 'jquery', 'cron'], function () {
            var $ = layui.jquery
                , admin = layui.admin
                , layer = layui.layer
                , form = layui.form
                , table = layui.table
                , cron = layui.cron;

            form.render();
            cron.render({
                elem: "#add-auto-backup-time",
                btns: ['confirm'],
                show: false,
                done: function (value) {
                    $('#add-auto-backup-time').val(value);
                }
            });

            // 监听备份类型选择
            $('#add-auto-backup-mysql-input').hide();
            $('#add-auto-backup-postgresql-input').hide();
            form.on('radio(add-auto-backup-type-radio)', function (data) {
                if (data.value == 'website') {
                    $('#add-auto-backup-website-input').show();
                    $('#add-auto-backup-mysql-input').hide();
                    $('#add-auto-backup-postgresql-input').hide();
                    $('#add-auto-backup-path').val('/www/backup/website');
                } else if (data.value == 'mysql') {
                    $('#add-auto-backup-website-input').hide();
                    $('#add-auto-backup-mysql-input').show();
                    $('#add-auto-backup-postgresql-input').hide();
                    $('#add-auto-backup-path').val('/www/backup/mysql');
                } else if (data.value == 'postgresql') {
                    $('#add-auto-backup-website-input').hide();
                    $('#add-auto-backup-mysql-input').hide();
                    $('#add-auto-backup-postgresql-input').show();
                    $('#add-auto-backup-path').val('/www/backup/postgresql');
                }
            });

            // 提交
            form.on('submit(add-auto-backup-submit)', function (data) {
                admin.req({
                    url: "/api/plugin/auto-backup/addTask"
                    , method: 'post'
                    , data: data.field
                    , success: function (result) {
                        if (result.code !== 0) {
                            console.log('耗子Linux面板：自动备份任务添加失败，接口返回' + result);
                            layer.msg('自动备份任务添加失败，请刷新重试！')
                            return false;
                        }
                        table.reload('auto-backup-list');
                        layer.alert('自动备份任务添加成功！', {
                            icon: 1
                            , title: '提示'
                            , btn: ['确定']
                            , yes: function (index) {
                                layer.closeAll();
                            }
                        });
                    }
                    , error: function (xhr, status, error) {
                        console.log('耗子Linux面板：ajax请求出错，错误' + error);
                    }
                });
                return false;
            });
        });
    };
</script>

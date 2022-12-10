<!--
Name: Fail2ban管理器 - 新建规则
Author: 耗子
Date: 2022-12-07
-->
<script type="text/html" template lay-done="layui.data.sendParams(d.params)">
    <form class="layui-form" action="" lay-filter="add-pure-ftpd-user-form">
        <div class="layui-form-item">
            <label class="layui-form-label">类型</label>
            <div class="layui-input-block">
                <input type="radio" lay-filter="add-fail2ban-rule-type-radio" name="type" value="website"
                       title="网站" checked="">
                <input type="radio" lay-filter="add-fail2ban-rule-type-radio" name="type" value="service"
                       title="服务">
            </div>
        </div>
        <div id="add-fail2ban-rule-website-input" class="layui-form-item">
            <label class="layui-form-label">网站</label>
            <div class="layui-input-block">
                <select name="website" lay-filter="add-fail2ban-rule-website">
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
        <div id="add-fail2ban-rule-service-input" class="layui-form-item">
            <label class="layui-form-label">服务</label>
            <div class="layui-input-block">
                <select name="service" lay-filter="add-fail2ban-rule-service">
                    <option value="ssh" selected="">ssh</option>
                    <option value="mysql">mysql</option>
                    <option value="pure-ftpd">pure-ftpd</option>
                </select>
            </div>
        </div>
        <div id="add-fail2ban-rule-website-mode-input" class="layui-form-item">
            <label class="layui-form-label">网站模式</label>
            <div class="layui-input-block">
                <input type="radio" lay-filter="add-fail2ban-rule-website-mode-radio" name="website_mode" value="cc"
                       title="CC" checked="">
                <input type="radio" lay-filter="add-fail2ban-rule-website-mode-radio" name="website_mode" value="path"
                       title="目录">
            </div>
        </div>
        <div id="add-fail2ban-rule-website-path-input" class="layui-form-item">
            <label class="layui-form-label">网站目录</label>
            <div class="layui-input-block">
                <input type="text" name="website_path"
                       lay-verify="required" placeholder="输入一个禁止访问的目录" class="layui-input"
                       value="/admin"/>
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">最大重试</label>
            <div class="layui-input-block">
                <input type="text" name="maxretry"
                       lay-verify="required" placeholder="单位：次" class="layui-input"
                       value="30"/>
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">周期</label>
            <div class="layui-input-block">
                <input type="text" name="findtime"
                       lay-verify="required" placeholder="单位：秒" class="layui-input"
                       value="300"/>
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">禁止时间</label>
            <div class="layui-input-block">
                <input type="text" name="bantime"
                       lay-verify="required" placeholder="单位：秒" class="layui-input"
                       value="600"/>
            </div>
            <div class="layui-form-mid layui-word-aux">
                在设置周期内(秒)有超过最大重试(次)的IP访问，将禁止该IP禁止时间(秒)
            </div>
            <div class="layui-form-mid layui-word-aux">
                <span style="color: red;">防护端口自动获取，如果修改了规则项对应的端口，请删除重新添加，否则防护可能不会生效</span>
            </div>
        </div>
        <div class="layui-form-item">
            <div class="layui-input-block">
                <div class="layui-footer">
                    <button class="layui-btn" lay-submit="" lay-filter="add-fail2ban-rule-submit">立即提交</button>
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
                , table = layui.table;

            form.render();

            // 监听类型选择
            $('#add-fail2ban-rule-service-input').hide();
            form.on('radio(add-fail2ban-rule-type-radio)', function (data) {
                if (data.value == 'website') {
                    $('#add-fail2ban-rule-website-input').show();
                    $('#add-fail2ban-rule-website-mode-input').show();
                    $('#add-fail2ban-rule-service-input').hide();
                    if ($('input[name="website_mode"]:checked').val() == 'cc') {
                        $('#add-fail2ban-rule-website-path-input').hide();
                    } else {
                        $('#add-fail2ban-rule-website-path-input').show();
                    }
                } else if (data.value == 'service') {
                    $('#add-fail2ban-rule-website-input').hide();
                    $('#add-fail2ban-rule-website-mode-input').hide();
                    $('#add-fail2ban-rule-service-input').show();
                    $('#add-fail2ban-rule-website-path-input').hide();
                }
            });
            // 监听网站模式选择
            $('#add-fail2ban-rule-website-path-input').hide();
            form.on('radio(add-fail2ban-rule-website-mode-radio)', function (data) {
                if (data.value == 'cc') {
                    $('#add-fail2ban-rule-website-path-input').hide();
                } else if (data.value == 'path') {
                    $('#add-fail2ban-rule-website-path-input').show();
                }
            });

            // 提交
            form.on('submit(add-fail2ban-rule-submit)', function (data) {
                if (data.field.type === 'website') {
                    data.field.name = data.field.website;
                } else if (data.field.type === 'service') {
                    data.field.name = data.field.service;
                }
                index = layer.msg('提交中...', {icon: 16, time: 0});
                admin.req({
                    url: "/api/plugin/fail2ban/addRule"
                    , method: 'post'
                    , data: data.field
                    , success: function (result) {
                        layer.close(index);
                        if (result.code !== 0) {
                            console.log('耗子Linux面板：Fail2ban规则添加失败，接口返回' + result);
                            return false;
                        }
                        table.reload('fail2ban-rule-list');
                        layer.alert('Fail2ban规则添加成功！', {
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

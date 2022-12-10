<!--
Name: phpMyAdmin管理器
Author: 耗子
Date: 2022-12-09
-->
<title>phpMyAdmin</title>
<div class="layui-fluid" id="component-tabs">
    <div class="layui-row">
        <div class="layui-col-md12">
            <div class="layui-card">
                <div class="layui-card-header">phpMyAdmin信息</div>
                <div class="layui-card-body">
                    <blockquote class="layui-elem-quote layui-quote-nm">访问地址：<span
                                id="phpmyadmin-info">获取中</span></blockquote>
                    <blockquote class="layui-elem-quote layui-quote-nm">
                        注意：phpMyAdmin安装目录为/www/wwwroot/phpmyadmin，请勿删除
                    </blockquote>
                    <fieldset class="layui-elem-field layui-field-title" style="margin-top: 30px;">
                        <legend>基本设置</legend>
                    </fieldset>
                    <div class="layui-form" lay-filter="phpmyadmin_setting">
                        <div class="layui-form-item">
                            <label class="layui-form-label" style="font-size: 13px;">访问端口</label>
                            <div class="layui-input-inline">
                                <input type="text" name="phpmyadmin_port" value="获取中ing..." class="layui-input"
                                       disabled/>
                            </div>
                            <div class="layui-form-mid layui-word-aux">查看/修改phpMyAdmin访问端口</div>
                        </div>
                        <div class="layui-form-item">
                            <div class="layui-input-block">
                                <button class="layui-btn layui-btn-sm" lay-submit
                                        lay-filter="phpmyadmin_setting_submit">确认修改
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    layui.use(['index', 'code', 'table', 'form'], function () {
        let $ = layui.$
            , form = layui.form
            , admin = layui.admin;

        // 获取phpmyadmin信息
        admin.req({
            url: "/api/plugin/phpmyadmin/info"
            , method: 'get'
            , success: function (result) {
                if (result.code !== 0) {
                    layer.alert('phpMyAdmin信息获取失败，可能已损坏！', {
                        icon: 2
                    });
                    console.log('耗子Linux面板：phpMyAdmin信息获取失败，接口返回' + result);
                    return false;
                }
                // 获取当前域名
                let hostname = window.location.hostname;
                // 拼接phpmyadmin访问地址
                let phpmyadmin_url = 'http://' + hostname + ':888/' + result.data.phpmyadmin;
                $('#phpmyadmin-info').html('<a href="' + phpmyadmin_url + '" target="_blank">' + phpmyadmin_url + '</a>');
                $('input[name=phpmyadmin_port]').val(result.data.port);
                $('input').attr('disabled', false);
                form.render();
            }
            , error: function (xhr, status, error) {
                console.log('耗子Linux面板：ajax请求出错，错误' + error)
            }
        });

        // 监听phpmyadmin设置提交
        form.on('submit(phpmyadmin_setting_submit)', function (data) {
            data.field.port = data.field.phpmyadmin_port;
            index = layer.msg('请稍候...', {icon: 16, time: 0});
            admin.req({
                url: "/api/plugin/phpmyadmin/setPort"
                , method: 'post'
                , data: data.field
                , success: function (result) {
                    layer.close(index);
                    if (result.code !== 0) {
                        layer.alert('phpMyAdmin设置失败，可能已损坏！', {
                            icon: 2
                        });
                        console.log('耗子Linux面板：phpMyAdmin设置失败，接口返回' + result);
                        return false;
                    }
                    layer.msg('设置成功', {
                        icon: 1
                    });
                }
                , error: function (xhr, status, error) {
                    console.log('耗子Linux面板：ajax请求出错，错误' + error)
                }
            });
        });
    });
</script>

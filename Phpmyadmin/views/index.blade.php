<!--
Name: phpMyAdmin管理器
Author: 耗子
Date: 2022-11-30
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
                    <blockquote class="layui-elem-quote layui-quote-nm">注意：phpMyAdmin安装目录为/www/wwwroot/phpmyadmin，请勿删除</blockquote>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    layui.use(['index', 'code', 'table'], function () {
        let $ = layui.$
            , admin = layui.admin;

        // 获取phpmyadmin信息
        admin.req({
            url: "/api/plugin/phpmyadmin/info"
            , method: 'get'
            , success: function (result) {
                if (result.code !== 0) {
                    layer.alert('phpMyAdmin信息获取失败，可能已损坏！',{
                        icon: 2
                    });
                    console.log('耗子Linux面板：phpMyAdmin信息获取失败，接口返回' + result);
                    return false;
                }
                // 获取当前域名
                console.log(window.location)
                let hostname = window.location.hostname;
                // 拼接phpmyadmin访问地址
                let phpmyadmin_url = 'http://' + hostname + ':888/' + result.data;
                $('#phpmyadmin-info').html('<a href="' + phpmyadmin_url + '" target="_blank">' + phpmyadmin_url + '</a>');

            }
            , error: function (xhr, status, error) {
                console.log('耗子Linux面板：ajax请求出错，错误' + error)
            }
        });
    });
</script>
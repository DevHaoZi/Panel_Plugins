<!--
Name: Fail2ban管理器 - 查看规则
Author: 耗子
Date: 2022-12-09
-->
<script type="text/html" template lay-done="layui.data.sendParams(d.params)">
    @{{# console.log(d.params) }}
    <div class="layui-fluid">
        <div class="layui-row layui-col-space15">
            <div class="layui-col-sm6 layui-col-md6">
                <div class="layui-card">
                    <div class="layui-card-body layuiadmin-card-list">
                        <p class="layuiadmin-big-font">@{{ d.params.banList.currentlyBan }}</p>
                        <p>当前封禁IP数</p>
                    </div>
                </div>
            </div>
            <div class="layui-col-sm6 layui-col-md6">
                <div class="layui-card">
                    <div class="layui-card-body layuiadmin-card-list">
                        <p class="layuiadmin-big-font">@{{ d.params.banList.totalBan }}</p>
                        <p>累计封禁IP数</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="layui-row layui-col-space15">
            <div class="layui-col-sm12 layui-col-md12">
                <div class="layui-card">
                    <div class="layui-card-body layuiadmin-card-list">
                        <table class="layui-table" id="fail2ban-view-rule-table"
                               lay-filter="fail2ban-view-rule-table"></table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>
<script type="text/html" id="fail2ban-view-rule-ip-control">
    <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="unBan">解封</a>
</script>
<script>
    layui.data.sendParams = function (params) {
        layui.use(['admin', 'form', 'jquery', 'cron'], function () {
            var $ = layui.jquery
                , admin = layui.admin
                , layer = layui.layer
                , form = layui.form
                , table = layui.table;

            table.render({
                elem: '#fail2ban-view-rule-table'
                , cols: [[
                    {field: 'name', title: '规则名', unresize: true, hide: true}
                    , {field: 'ip', title: 'IP', unresize: true, sort: true}
                    , {
                        fixed: 'right',
                        title: '操作',
                        width: 150,
                        unresize: true,
                        toolbar: '#fail2ban-view-rule-ip-control'
                    }
                ]]
                , data: params.banList.bannedIpList
                , page: true
            });

            // 监听工具条
            table.on('tool(fail2ban-view-rule-table)', function (obj) {
                var data = obj.data;
                if (obj.event === 'unBan') {
                    layer.confirm('确定要解封 <b style="color: red;">' + data.ip + '</b> 吗？', function (index) {
                        index = layer.msg('请稍等...', {
                            icon: 16
                            , time: 0
                        });
                        admin.req({
                            url: "/api/plugin/fail2ban/unBan"
                            , method: 'post'
                            , data: data
                            , success: function (result) {
                                layer.close(index);
                                if (result.code !== 0) {
                                    console.log('耗子Linux面板：fail2ban IP解封失败，接口返回' + result);
                                    return false;
                                }
                                obj.del();
                                layer.alert(data.ip + '解封成功！');
                            }
                            , error: function (xhr, status, error) {
                                console.log('耗子Linux面板：ajax请求出错，错误' + error);
                            }
                        });
                        layer.close(index);
                    });
                }
            });
        });
    };
</script>

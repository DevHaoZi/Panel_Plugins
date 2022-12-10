<!--
Name: S3fs管理器
Author: 耗子
Date: 2022-12-10
-->
<title>S3fs</title>
<div class="layui-fluid" id="component-tabs">
    <div class="layui-row">
        <div class="layui-col-md12">
            <div class="layui-card">
                <div class="layui-card-header">S3fs挂载列表</div>
                <div class="layui-card-body">
                    <table class="layui-hide" id="s3fs-list" lay-filter="s3fs-list"></table>
                    <!-- 顶部工具栏 -->
                    <script type="text/html" id="s3fs-list-bar">
                        <div class="layui-btn-container">
                            <button class="layui-btn layui-btn-sm" lay-event="add_mount">新建挂载</button>
                        </div>
                    </script>
                    <!-- 右侧管理 -->
                    <script type="text/html" id="s3fs-list-control">
                        <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="umount">卸载</a>
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

        // 获取备份任务列表
        table.render({
            elem: '#s3fs-list'
            , url: '/api/plugin/s3fs/getList'
            , toolbar: '#s3fs-list-bar'
            , title: 'S3fs挂载列表'
            , cols: [[
                {field: 'id', hide: true, title: 'ID', sort: true}
                , {field: 'bucket', title: 'Bucket', fixed: 'left', unresize: true, sort: true}
                , {field: 'url', title: 'URL', sort: true}
                , {field: 'path', title: '挂载目录', sort: true}
                , {fixed: 'right', title: '操作', toolbar: '#s3fs-list-control', width: 150}
            ]]
            , page: true
        });
        // 头工具栏事件
        table.on('toolbar(s3fs-list)', function (obj) {
            if (obj.event === 'add_mount') {
                admin.popup({
                    title: '新建挂载'
                    , area: ['600px', '600px']
                    , id: 'LAY-popup-s3fs-mount-add'
                    , success: function (layer, index) {
                        view(this.id).render('plugin/s3fs/add_mount', {}).done(function () {
                            form.render(null, 'LAY-popup-s3fs-mount-add');
                        });
                    }
                });
            }
        });
        // 行工具事件
        table.on('tool(s3fs-list)', function (obj) {
            let data = obj.data;
            if (obj.event === 'umount') {
                layer.confirm('确定要卸载 <b style="color: red;">' + data.path + '</b> 吗？', function (index) {
                    index = layer.msg('请稍候...', {icon: 16, time: 0});
                    admin.req({
                        url: "/api/plugin/s3fs/deleteMount"
                        , method: 'post'
                        , data: data
                        , success: function (result) {
                            layer.close(index);
                            if (result.code !== 0) {
                                return false;
                            }
                            obj.del();
                            layer.alert(data.path + ' 卸载成功！');
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
</script>
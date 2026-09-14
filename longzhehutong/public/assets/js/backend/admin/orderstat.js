define(['jquery', 'bootstrap', 'backend', 'echarts', 'echarts-theme'], function ($, undefined, Backend, Echarts) {

    var Controller = {
        index: function () {
            var statUrl = Config.statUrl || '';
            var statDefault = Config.statDefault || { dispatch: {grab:0,processed:0}, line: {grab:0,processed:0}, total: {grab:0,processed:0}, admin_list: [] };

            var updateUI = function (data) {
                data = data || statDefault;
                $('#dispatch-grab').text(data.dispatch ? data.dispatch.grab : 0);
                $('#dispatch-processed').text(data.dispatch ? data.dispatch.processed : 0);
                $('#line-grab').text(data.line ? data.line.grab : 0);
                $('#line-processed').text(data.line ? data.line.processed : 0);
                $('#total-grab').text(data.total ? data.total.grab : 0);
                $('#total-processed').text(data.total ? data.total.processed : 0);

                if (window.orderstatChart) {
                    var chartData = [
                        data.dispatch ? data.dispatch.grab : 0,
                        data.line ? data.line.grab : 0,
                        data.total ? data.total.grab : 0
                    ];
                    var chartData2 = [
                        data.dispatch ? data.dispatch.processed : 0,
                        data.line ? data.line.processed : 0,
                        data.total ? data.total.processed : 0
                    ]; 
                    window.orderstatChart.setOption({
                        series: [
                            { name: '抢单数', type: 'bar', barWidth: '30%', data: chartData },
                            { name: '已完成数', type: 'bar', barWidth: '30%', data: chartData2 }
                        ]
                    });
                }

                // 更新管理员明细表格
                var list = data.admin_list || [];
                var $tbody = $('#admin-detail-tbody');
                if (list.length === 0) {
                    $tbody.html('<tr><td colspan="4" class="text-center text-muted">暂无数据</td></tr>');
                } else {
                    var html = '';
                    for (var i = 0; i < list.length; i++) {
                        var r = list[i];
                        html += '<tr><td>' + (r.admin_name || '') + '</td><td>' + (r.group_name || '') + '</td><td>' + (r.grab || 0) + '</td><td>' + (r.processed || 0) + '</td></tr>';
                    }
                    $tbody.html(html);
                }
            };

            var requestStat = function () {
                if (!statUrl) return;
                var params = {
                    start_date: $('#stat-date-start').val(),
                    end_date: $('#stat-date-end').val(),
                    admin_id: $('#stat-admin').val()
                };
                Fast.api.ajax({
                    url: statUrl,
                    type: 'GET',
                    data: params,
                    loading: true
                }, function (res) {
                    updateUI(res);
                    return false;
                }, function () { return false; });
            };

            $('#stat-submit').on('click', function (e) {
                e.preventDefault();
                requestStat();
            });

            // 初始化图表
            var chartDom = document.getElementById('orderstat-chart');
            if (chartDom) {
                window.orderstatChart = Echarts.init(chartDom, 'walden');
                var option = {
                    color: ['#3fb1e3', '#18d1b1'],
                    tooltip: { trigger: 'axis' },
                    legend: { data: ['抢单数', '已完成数'] },
                    grid: { left: '3%', right: '4%', bottom: '3%', containLabel: true },
                    xAxis: [{
                        type: 'category',
                        data: ['调度', '线路', '合计'],
                        axisTick: { alignWithLabel: true }
                    }],
                    yAxis: [{ type: 'value' }],
                    series: [
                        { name: '抢单数', type: 'bar', barWidth: '30%', data: [0, 0, 0] },
                        { name: '已完成数', type: 'bar', barWidth: '30%', data: [0, 0, 0] }
                    ]
                };
                window.orderstatChart.setOption(option);
                updateUI(statDefault);

                $(window).resize(function () {
                    window.orderstatChart && window.orderstatChart.resize();
                });
            } else {
                updateUI(statDefault);
            }
        }
    };

    return Controller;
});

define(['jquery', 'backend', 'echarts', 'echarts-theme'], function ($, Backend, echarts) {

    var Controller = {
        index: function () {

            var chart;

            // 组装后台接口地址
            function url(path) {
                if (typeof Backend !== 'undefined' && Backend.api && Backend.api.fixurl) {
                    return Backend.api.fixurl(path);
                }
                var base = location.pathname.replace(/\/index.*$/, '').replace(/\/$/, '') || '/admin/profit';
                return base + '/' + (path.split('/').pop() || path);
            }

            // 初始化图表
            function initChart() {
                var el = document.getElementById('profitChart');
                if (!el) {
                    return;
                }
                chart = echarts.init(el, 'walden');
            }

            // 更新图表
            function updateChart(data) {
                if (!chart) {
                    return;
                }
                var labels = data.map(function (item) { return item.date; });
                var income = data.map(function (item) { return parseFloat(item.income) || 0; });
                var expense = data.map(function (item) { return parseFloat(item.expense) || 0; });
                var profit = data.map(function (item) { return parseFloat(item.profit) || 0; });

                chart.setOption({
                    title: {
                        text: '公司利润趋势分析',
                        left: 'center'
                    },
                    tooltip: {
                        trigger: 'axis'
                    },
                    legend: {
                        data: ['主营收入', '总成本', '公司利润'],
                        top: 28
                    },
                    grid: {
                        left: '3%',
                        right: '4%',
                        bottom: '3%',
                        containLabel: true
                    },
                    xAxis: {
                        type: 'category',
                        boundaryGap: false,
                        data: labels
                    },
                    yAxis: {
                        type: 'value'
                    },
                    series: [
                        { name: '主营收入', type: 'line', smooth: true, data: income },
                        { name: '总成本', type: 'line', smooth: true, data: expense },
                        { name: '公司利润', type: 'line', smooth: true, data: profit }
                    ]
                });
            }

            // 更新表格
            function updateTable(data) {
                var tbody = $('#report-tbody');
                if (!tbody.length) {
                    return;
                }
                tbody.empty();
                data.forEach(function (item) {
                    var income = parseFloat(item.income) || 0;
                    var expense = parseFloat(item.expense) || 0;
                    var profit = parseFloat(item.profit) || 0;
                    var orders = parseInt(item.orders, 10) || 0;
                    var cls = profit >= 0 ? 'text-success' : 'text-danger';
                    $('<tr>')
                        .append('<td>' + item.date + '</td>')
                        .append('<td>￥' + income.toFixed(2) + '</td>')
                        .append('<td>￥' + expense.toFixed(2) + '</td>')
                        .append('<td class="' + cls + '">￥' + profit.toFixed(2) + '</td>')
                        .append('<td>' + orders + '</td>')
                        .appendTo(tbody);
                });
            }

            // 更新卡片
            function updateStatistics(stats) {
                $('#total-income').text('￥' + (parseFloat(stats.total_income) || 0).toFixed(2));
                $('#total-expense').text('￥' + (parseFloat(stats.total_expense) || 0).toFixed(2));
                $('#net-profit').text('￥' + (parseFloat(stats.total_profit) || 0).toFixed(2));
                $('#profit-rate').text((parseFloat(stats.profit_rate) || 0).toFixed(1) + '%');
            }

            // 查询数据
            function loadData() {
                var startdate = $('#startdate').val();
                var enddate = $('#enddate').val();
                $.ajax({
                    url: url('profit/report'),
                    type: 'GET',
                    data: {
                        startdate: startdate,
                        enddate: enddate
                    },
                    dataType: 'json',
                    success: function (response) {
                        if (response.code === 1) {
                            updateChart(response.data || []);
                            updateTable(response.data || []);
                            updateStatistics(response.statistics || {});
                        } else {
                            Toastr.error(response.msg || '获取数据失败');
                        }
                    },
                    error: function () {
                        Toastr.error('获取数据失败');
                    }
                });
            }

            // 初始化
            initChart();

            // 绑定事件
            $('#search-btn').on('click', function () {
                loadData();
            });
            $('#export-btn').on('click', function () {
                var startdate = $('#startdate').val();
                var enddate = $('#enddate').val();
                window.open(url('profit/export') + '?startdate=' + encodeURIComponent(startdate) + '&enddate=' + encodeURIComponent(enddate));
            });
            $(window).on('resize', function () {
                if (chart) {
                    chart.resize();
                }
            });

            // 页面加载默认查询最近30天
            loadData();
        }
    };

    return Controller;
});

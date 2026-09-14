define(['jquery', 'bootstrap', 'backend', 'addtabs', 'table', 'echarts', 'echarts-theme', 'template'], function ($, undefined, Backend, Datatable, Table, Echarts, undefined, Template) {

    var Controller = {
        index: function () {
            // 基于准备好的dom，初始化echarts实例
            var myChart = Echarts.init(document.getElementById('echart'), 'walden');
            var charts = [myChart];

            // 指定图表的配置项和数据
            var option = {
                title: {
                    text: '',
                    subtext: ''
                },
                color: [
                    "#18d1b1",
                    "#3fb1e3",
                    "#626c91",
                    "#a0a7e6",
                    "#c4ebad",
                    "#96dee8"
                ],
                tooltip: {
                    trigger: 'axis'
                },
                legend: {
                    data: [__('Register user')]
                },
                toolbox: {
                    show: false,
                    feature: {
                        magicType: {show: true, type: ['stack', 'tiled']},
                        saveAsImage: {show: true}
                    }
                },
                xAxis: {
                    type: 'category',
                    boundaryGap: false,
                    data: Config.column
                },
                yAxis: {},
                grid: [{
                    left: 'left',
                    top: 'top',
                    right: '10',
                    bottom: 30
                }],
                series: [{
                    name: __('Register user'),
                    type: 'line',
                    smooth: true,
                    areaStyle: {
                        normal: {}
                    },
                    lineStyle: {
                        normal: {
                            width: 1.5
                        }
                    },
                    data: Config.userdata
                }]
            };
            // 使用刚指定的配置项和数据显示图表。
            myChart.setOption(option);
            var orderStatRoot = document.getElementById('order-stat-chart');
            var orderStatChart = null;
            if (orderStatRoot) {
                orderStatChart = Echarts.init(orderStatRoot, 'walden');
                charts.push(orderStatChart);
                var orderStatOption = {
                    color: [
                        "#3fb1e3",
                        "#18d1b1"
                    ],
                    tooltip: {
                        trigger: 'axis',
                        axisPointer: {
                            type: 'shadow'
                        }
                    },
                    grid: {
                        left: '3%',
                        right: '4%',
                        bottom: '3%',
                        containLabel: true
                    },
                    xAxis: [{
                        type: 'category',
                        data: [__('Grabbed orders'), __('Processed orders')],
                        axisTick: {
                            alignWithLabel: true
                        }
                    }],
                    yAxis: [{
                        type: 'value'
                    }],
                    series: [{
                        name: __('Order statistics'),
                        type: 'bar',
                        barWidth: '40%',
                        data: [0, 0]
                    }]
                };

                var renderOrderStat = function (payload) {
                    var data = payload || {};
                    var grab = data.grab || 0;
                    var processed = data.processed || 0;
                    orderStatOption.series[0].data = [grab, processed];
                    orderStatChart.setOption(orderStatOption, true);
                };
                renderOrderStat(Config.orderStatDefault || {});

                var requestOrderStat = function () {
                    if (!Config.orderStatUrl) {
                        return;
                    }
                    var params = {
                        start_date: $('#order-stat-date-start').val(),
                        end_date: $('#order-stat-date-end').val(),
                        admin_id: $('#order-stat-admin').val()
                    };
                    Fast.api.ajax({
                        url: Config.orderStatUrl,
                        type: 'GET',
                        data: params,
                        loading: true
                    }, function (data) {
                        renderOrderStat(data);
                        return false;
                    }, function () {
                        return false;
                    });
                };

                $('#order-stat-submit').on('click', function (event) {
                    event.preventDefault();
                    requestOrderStat();
                });
            }

            $(window).resize(function () {
                charts.forEach(function (chart) {
                    chart.resize();
                });
            });

            $(document).on("click", ".btn-refresh", function () {
                setTimeout(function () {
                    charts.forEach(function (chart) {
                        chart.resize();
                    });
                }, 0);
            });

        }
    };

    return Controller;
});

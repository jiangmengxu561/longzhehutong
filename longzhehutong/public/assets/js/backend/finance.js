define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'echarts'], function ($, undefined, Backend, Table, Form, Echarts) {

    var Controller = {
        cost_ledger: function () { initPage('cost_ledger'); },
        cost_ledger_dispatch: function () { initPage('cost_ledger_dispatch'); },
        cost_ledger_detail: function () { initPage('cost_ledger_detail'); },
        reserve_fund: function () { initReserveFund(); },
        reserve_fund_log: function () { initReserveFundLog(); },
    };

    function initPage(pageName) {
        Table.api.init({ extend: {} });

        var table = $("#table");
        var columns = getPageColumns(pageName);
        setDefaultRange();
 
        table.bootstrapTable({
            url: getUrl(pageName),  
            pk: getPagePk(pageName),
            pagination: true, 
            sidePagination: 'server',
            commonSearch: false, 
            search: false,
            pageSize: 20,
            pageList: [10, 20, 50, 100], 
            queryParams: function (params) {
                var q = getFilters();
                q.page = params.pageNumber || 1;
                q.limit = params.pageSize || 20;
                q.pageSize = params.pageSize || 20;
                q.offset = params.offset || 0;
                return q;
            },
            responseHandler: function (res) {
                if (res && res.statistics) {
                    updateStatistics(res.statistics);
                }
                if (res && res.chart) {
                    updateChart(res.chart);
                }
                return res;
            },
            columns: columns,
            rowStyle: function (row) {
                var cls = row.row_class || '';
                return cls ? { classes: cls } : {};
            }
        });

        $('#search-btn').on('click', function () {
            table.bootstrapTable('refresh', { page: 1 });
        });

        $('#back-btn').on('click', function () {
            history.back();
        });

        $('#dispatch-btn').on('click', function () {
            var q = getFilters();
            var qs = $.param(q);
            Fast.api.open('finance/cost_ledger_dispatch' + (qs ? '?' + qs : ''), '调度费用汇总');
        });

        $('#orderid, #username').on('keypress', function (e) {
            if (e.which === 13) {
                table.bootstrapTable('refresh', { page: 1 });
            }
        });

        function getFilters() {
            var orderId = Fast.api.query('order_id') || 0;
            if (orderId) {
                return { order_id: orderId };
            }
            return {
                startdate: $('#startdate').val() || '',
                enddate: $('#enddate').val() || '',
                orderid: $('#orderid').val() || '',
                username: $('#username').val() || '',
                payment_state: $('#payment_state').val() || 'all',
                order_id: 0,
                dispatch_id: $('#dispatch_id').val() || Fast.api.query('dispatch_id') || 0
            };
        }

        function getUrl(page) {
            return 'finance/' + page;
        }

        function getPagePk(page) {
            if (page === 'cost_ledger_dispatch') return 'dispatch_id';
            return 'order_pk';
        }
    }

    function initReserveFund() {
        Table.api.init({ extend: {} });
        var table = $('#table');
        window._canRecharge = $('#recharge-btn').length > 0;
        table.bootstrapTable({
            url: 'finance/reserve_fund',
            pk: 'admin_id',
            pagination: true,
            sidePagination: 'server',
            search: false,
            commonSearch: false,
            pageSize: 20,
            queryParams: function (params) {
                var limit = parseInt(params.limit, 10) || 20;
                var offset = parseInt(params.offset, 10) || 0;
                return {
                    page: Math.floor(offset / limit) + 1,
                    limit: limit,
                    offset: offset,
                    role: $('#reserve-role').val() || 'all'
                };
            },
            columns: [[
                { field: 'role_name', title: '角色' },
                { field: 'name', title: '姓名' },
                { field: 'mobile', title: '手机号' },
                { field: 'balance', title: '备用金余额', formatter: function (v) { return '<span class="text-success">¥' + moneyFormatter(v) + '</span>'; } },
                { field: 'total_recharge', title: '累计充值', formatter: function (v) { return '¥' + moneyFormatter(v); } },
                { field: 'total_deduct', title: '累计扣款', formatter: function (v) { return '<span class="text-danger">¥' + moneyFormatter(v) + '</span>'; } },
                { field: 'operate', title: '操作', formatter: function (v, row) {
                    return '<a href="javascript:;" class="btn btn-xs btn-info btn-log" data-admin-id="' + row.admin_id + '">流水</a>' +
                           ' <a href="javascript:;" class="btn btn-xs btn-success btn-recharge" data-admin-id="' + row.admin_id + '">充值</a>' +
                           ' <a href="javascript:;" class="btn btn-xs btn-warning btn-deduct" data-admin-id="' + row.admin_id + '">扣除</a>';
                }, events: {
                    'click .btn-log': function (e, value, row) {
                        Fast.api.open('finance/reserve_fund_log?admin_id=' + row.admin_id, '备用金流水');
                    },
                    'click .btn-recharge': function (e, value, row) {
                        promptRecharge(row.admin_id, row.name || row.nickname || row.username || '管理员');
                    },
                    'click .btn-deduct': function (e, value, row) {
                        promptDeduct(row.admin_id, row.name || row.nickname || row.username || '管理员');
                    }
                }}
            ]]
        });

        if (!window._canRecharge) {
            $('.btn-recharge, .btn-deduct, #recharge-btn').remove();
        }

        $('#reserve-role').val(Fast.api.query('role') || 'all');
        $('#reserve-role').on('change', function () {
            table.bootstrapTable('refresh', { page: 1 });
        });

        $('#recharge-btn').on('click', function () {
            Toastr.info('请点击对应行右侧的“充值”按钮进行充值。线路与调度会分别列出。');
        });
    }

    function initReserveFundLog() {
        Table.api.init({ extend: {} });
        var table = $('#table');
        $('#dispatch_id').val(Fast.api.query('dispatch_id') || Fast.api.query('admin_id') || 0);
        $('#order_number').val(Fast.api.query('order_number') || '');
        $('#startdate').val(Fast.api.query('startdate') || '');
        $('#enddate').val(Fast.api.query('enddate') || '');
        function getReserveFundLogQuery(params) {
            var limit = parseInt(params.limit, 10) || 20;
            var offset = parseInt(params.offset, 10) || 0;
            return {
                page: Math.floor(offset / limit) + 1,
                limit: limit,
                offset: offset,
                selected_dispatch_id: parseInt($('#dispatch_id').val(), 10) || 0,
                order_number: $('#order_number').val() || '',
                startdate: $('#startdate').val() || '',
                enddate: $('#enddate').val() || ''
            };
        }
        table.bootstrapTable({
            url: 'finance/reserve_fund_log',
            pk: 'id',
            pagination: true,
            sidePagination: 'server',
            search: false,
            commonSearch: false,
            pageSize: 20,
            queryParams: getReserveFundLogQuery,
            columns: [[
                { field: 'admin_name', title: '操作人' },
                { field: 'order_number', title: '订单号' },
                { field: 'direction', title: '方向' },
                { field: 'amount', title: '金额', formatter: function (v, row) {
                    var cls = row.direction === 'deduct' ? 'text-danger' : 'text-success';
                    return '<span class="' + cls + '">¥' + moneyFormatter(v) + '</span>';
                }},
                { field: 'balance_before', title: '变动前余额', formatter: function (v) { return '¥' + moneyFormatter(v); } },
                { field: 'balance_after', title: '变动后余额', formatter: function (v) { return '¥' + moneyFormatter(v); } },
                { field: 'remark', title: '备注' },
                { field: 'createtime', title: '时间', formatter: Table.api.formatter.datetime },
            ]]
        });
        $('#search-btn').on('click', function () {
            table.bootstrapTable('refresh', { pageNumber: 1 });
        });
        $('#dispatch_id').on('change', function () {
            table.bootstrapTable('refresh', { pageNumber: 1 });
        });
        $('#order_number, #startdate, #enddate').on('keypress', function (e) {
            if (e.which === 13) {
                table.bootstrapTable('refresh', { page: 1 });
            }
        });
        $('#back-btn').on('click', function () {
            history.back();
        });
    }

    function promptRecharge(adminId, nickname) {
        Layer.prompt({ title: '给 ' + nickname + ' 充值备用金', formType: 0, value: '' }, function (value, index) {
            var amount = parseFloat(value);
            if (isNaN(amount) || amount <= 0) {
                Toastr.error('请输入大于0的金额');
                return;
            }
            Layer.prompt({ title: '备注（可选）', formType: 0, value: '' }, function (remark, index2) {
                Fast.api.ajax({
                    url: 'finance/reserve_fund_recharge',
                    data: { admin_id: adminId, amount: amount, remark: remark || '' }
                }, function () {
                    Layer.close(index2);
                    Layer.close(index);
                    $('#table').bootstrapTable('refresh');
                });
            });
        });
    }

    function promptDeduct(adminId, nickname) {
        Layer.prompt({ title: '从 ' + nickname + ' 扣除备用金（金额需大于0）', formType: 0, value: '' }, function (value, index) {
            var amount = parseFloat(value);
            if (isNaN(amount) || amount <= 0) {
                Toastr.error('请输入大于0的扣除金额');
                return;
            }
            Layer.prompt({ title: '备注（可选）', formType: 0, value: '' }, function (remark, index2) {
                Fast.api.ajax({
                    url: 'finance/reserve_fund_deduct',
                    data: { admin_id: adminId, amount: amount, remark: remark || '' }
                }, function () {
                    Layer.close(index2);
                    Layer.close(index);
                    $('#table').bootstrapTable('refresh');
                });
            });
        });
    }

    function setDefaultRange() {
        if (Fast.api.query('order_id')) {
            return;
        }
        var start = $('#startdate');
        var end = $('#enddate');
        var today = formatDate(new Date());
        if (start.length && !start.val()) {
            start.val(today);
        }
        if (end.length && !end.val()) {
            end.val(today);
        }
    }

    function formatDate(d) {
        var y = d.getFullYear();
        var m = ('0' + (d.getMonth() + 1)).slice(-2);
        var day = ('0' + d.getDate()).slice(-2);
        return y + '-' + m + '-' + day;
    }

    function moneyFormatter(v) {
        var f = parseFloat(v || 0);
        return isNaN(f) ? '0.00' : f.toFixed(2);
    }

    function moneyWithColor(v, cellState, paidAtText, paidClass) {
        var f = parseFloat(v || 0);
        if (isNaN(f) || f === 0) return '<span class="text-muted">-</span>';
        var val = f.toFixed(2);
        var cls = paidClass || (cellState === 'paid' ? 'ledger-paid' : (cellState === 'outrange' ? 'ledger-outrange' : 'ledger-unpaid'));
        var html = '<span class="' + cls + '" style="display:inline-block;padding:4px 8px;border-radius:4px;font-weight:600;">¥' + val + '</span>';
        if (paidAtText && paidAtText !== '-') {
            html += '<div style="font-size:11px;color:#999;margin-top:2px;">' + paidAtText + '</div>';
        }
        return html;
    }

    function stateBadge(row) {
        var state = row.payment_state || 'empty';
        var text = row.payment_state_text || '无数据';
        return '<span class="badge-state ' + state + '">' + text + '</span>';
    }

    function getPageColumns(page) {
        if (page === 'cost_ledger') {
            return [[
                { field: 'orderid', title: '订单号', width: 140 },
                { field: 'username', title: '下单人', width: 100 },
                { field: 'dispatch_name', title: '调度', width: 100 },
            { field: 'dispatch_id', title: '调度ID', width: 90 },
                { field: 'biz_date', title: '业务日期', width: 110 },
                { field: 'payment_time_text', title: '付款时间', width: 160 },
                { field: 'state', title: '付款状态', width: 100, formatter: function (v, row) { return stateBadge(row); } },
                { field: 'pickup_total', title: '取货费合计', width: 120, formatter: function (v, row) {
                    return moneyWithColor(v, row.pickup_cell_state, row.pickup_driver_fee_paid_at);
                }},
                { field: 'pickup_yunmanman', title: '取货·运满满', width: 110, formatter: function (v) { return '¥' + moneyFormatter(v); }},
                { field: 'pickup_huolala', title: '取货·货拉拉', width: 110, formatter: function (v) { return '¥' + moneyFormatter(v); }},
                { field: 'logistics_total', title: '干线费合计', width: 120, formatter: function (v, row) {
                    var black = row.logistics_cell_state === 'non_monthly_delivery';
                    return moneyWithColor(v, row.logistics_cell_state, black ? '-' : row.logistics_paid_at_text, black ? 'ledger-shipment-black' : null);
                }},
                { field: 'shipment_total', title: '送货费合计', width: 120, formatter: function (v, row) {
                    var black = row.shipment_cell_state === 'non_monthly_delivery';
                    return moneyWithColor(v, row.shipment_cell_state, black ? '-' : row.shipment_paid_at_text, black ? 'ledger-shipment-black' : null);
                }},
                { field: 'shipment_yunmanman', title: '送货·运满满', width: 110, formatter: function (v) { return '¥' + moneyFormatter(v); }},
                { field: 'shipment_huolala', title: '送货·货拉拉', width: 110, formatter: function (v) { return '¥' + moneyFormatter(v); }},
                { field: 'other_fee_total', title: '其他费用合计', width: 120, formatter: function (v, row) {
                    return moneyWithColor(v, row.other_cell_state, '-');
                }},
                // { field: 'cost_total', title: '费用总额', width: 120, formatter: function (v) { return '<b>¥' + moneyFormatter(v) + '</b>'; }},
                { field: 'paid_total', title: '已付金额', width: 120, formatter: function (v) { return '<span class="text-success">¥' + moneyFormatter(v) + '</span>'; }},
                { field: 'unpaid_total', title: '未付金额', width: 120, formatter: function (v) { return '<span class="text-danger">¥' + moneyFormatter(v) + '</span>'; }},
                { field: 'pay_price', title: '应收运费', width: 100, formatter: function (v) { return '¥' + moneyFormatter(v); }},
                { field: 'cost_cont', title: '成本合计', width: 100, formatter: function (v) { return '¥' + moneyFormatter(v); }},
                { field: 'profit', title: '利润', width: 100, formatter: function (v) {
                    var f = parseFloat(v || 0);
                    var cls = f >= 0 ? 'text-success' : 'text-danger';
                    return '<span class="' + cls + '">¥' + moneyFormatter(v) + '</span>';
                }},
                { field: 'reserve_balance', title: '剩余备用金', width: 120, formatter: function (v) { return '<span class="text-primary">¥' + moneyFormatter(v) + '</span>'; }},
                { field: 'operate', title: '操作', width: 100, formatter: function (value, row) {
                    return '<a href="javascript:;" class="btn btn-xs btn-info btn-detail" data-order-id="' + row.order_pk + '"><i class="fa fa-list"></i> 明细</a>';
                }, events: {
                    'click .btn-detail': function (e, value, row, index) {
                        Fast.api.open('finance/cost_ledger_detail?order_id=' + row.order_pk, '订单费用明细');
                    }
                }}
            ]];
        }

        if (page === 'cost_ledger_dispatch') {
            return [[
                { field: 'dispatch_name', title: '调度', width: 120 },
                { field: 'total_orders', title: '订单数', width: 80 },
                { field: 'reserve_balance', title: '剩余备用金', width: 120, formatter: function (v) { return '<span class="text-primary">¥' + moneyFormatter(v) + '</span>'; }},
                { field: 'state', title: '付款状态', width: 100, formatter: function (v, row) { return stateBadge(row); }},
                { field: 'pickup_total', title: '取货费', width: 120, formatter: function (v) { return '¥' + moneyFormatter(v); }},
                { field: 'pickup_yunmanman', title: '取货·运满满', width: 110, formatter: function (v) { return '¥' + moneyFormatter(v); }},
                { field: 'pickup_huolala', title: '取货·货拉拉', width: 110, formatter: function (v) { return '¥' + moneyFormatter(v); }},
                { field: 'logistics_total', title: '干线费', width: 120, formatter: function (v) { return '¥' + moneyFormatter(v); }},
                { field: 'shipment_total', title: '送货费', width: 120, formatter: function (v) { return '¥' + moneyFormatter(v); }},
                { field: 'shipment_yunmanman', title: '送货·运满满', width: 110, formatter: function (v) { return '¥' + moneyFormatter(v); }},
                { field: 'shipment_huolala', title: '送货·货拉拉', width: 110, formatter: function (v) { return '¥' + moneyFormatter(v); }},
                { field: 'other_fee_total', title: '其他费用', width: 120, formatter: function (v) { return '¥' + moneyFormatter(v); }},
                // { field: 'cost_total', title: '费用总额', width: 120, formatter: function (v) { return '<b>¥' + moneyFormatter(v) + '</b>'; }},
                { field: 'paid_total', title: '已付金额', width: 120, formatter: function (v) { return '<span class="text-success">¥' + moneyFormatter(v) + '</span>'; }},
                { field: 'unpaid_total', title: '未付金额', width: 120, formatter: function (v) { return '<span class="text-danger">¥' + moneyFormatter(v) + '</span>'; }},
                { field: 'operate', title: '操作', width: 100, formatter: function (value, row) {
                    return '<a href="javascript:;" class="btn btn-xs btn-info btn-detail"><i class="fa fa-list"></i> 订单明细</a>';
                }, events: {
                    'click .btn-detail': function (e, value, row, index) {
                        Fast.api.open('finance/cost_ledger_detail?dispatch_id=' + row.dispatch_id, '调度订单明细');
                    }
                }}
            ]];
        }

        return [[
            { field: 'orderid', title: '订单号', width: 140 },
            { field: 'username', title: '下单人', width: 100 },
            { field: 'dispatch_name', title: '调度', width: 100 },
            { field: 'fee_group', title: '费用类别', width: 100 },
            { field: 'fee_name', title: '费用名称', width: 120 },
            { field: 'platform_name', title: '平台', width: 90, formatter: function (v) { return v || '-'; } },
            { field: 'amount', title: '金额', width: 120, formatter: function (v, row) {
                return moneyWithColor(v, row.cell_state, row.paid_at_text);
            }},
            { field: 'paid_text', title: '付款状态', width: 100, formatter: function (v, row) {
                var state = row.paid ? 'paid' : 'unpaid';
                return '<span class="badge-state ' + state + '">' + (v || '-') + '</span>';
            }},
            { field: 'paid_at_text', title: '付款时间', width: 160, formatter: function (v, row) {
                if (row.type_key && String(row.type_key).indexOf('shipment_') === 0 && String(row.paid_class || '').indexOf('ledger-shipment-black') !== -1) {
                    return '-';
                }
                return v || '-';
            } },
            { field: 'biz_date', title: '业务日期', width: 110 },
            { field: 'remarks', title: '备注', width: 150 },
        ]];
    }

    function updateStatistics(stats) {
        if (!stats) return;
        var cards = [
            { title: '订单数', value: stats.order_count || 0, cls: '' },
            { title: '已付取货费', value: '¥' + moneyFormatter(stats.pickup_paid_total), cls: 'text-success' },
            { title: '已付干线费', value: '¥' + moneyFormatter(stats.logistics_paid_total), cls: 'text-primary' },
            { title: '已付送货费', value: '¥' + moneyFormatter(stats.shipment_paid_total), cls: 'text-info' },
            { title: '取货费·运满满', value: '¥' + moneyFormatter(stats.pickup_yunmanman), cls: 'text-success' },
            { title: '取货费·货拉拉', value: '¥' + moneyFormatter(stats.pickup_huolala), cls: 'text-danger' },
            { title: '送货费·运满满', value: '¥' + moneyFormatter(stats.shipment_yunmanman), cls: 'text-success' },
            { title: '送货费·货拉拉', value: '¥' + moneyFormatter(stats.shipment_huolala), cls: 'text-danger' },
            { title: '已付其他费用', value: '¥' + moneyFormatter(stats.other_paid_total), cls: 'text-warning' },
            // { title: '费用总额', value: '¥' + moneyFormatter(stats.cost_total), cls: 'text-danger' },
            { title: '已付金额', value: '¥' + moneyFormatter(stats.paid_total), cls: 'text-success' },
            { title: '未付金额', value: '¥' + moneyFormatter(stats.unpaid_total), cls: 'text-danger' },
            // { title: '利润', value: '¥' + moneyFormatter(stats.profit_total), cls: stats.profit_total >= 0 ? 'text-success' : 'text-danger' },
            { title: '剩余备用金', value: '¥' + moneyFormatter(stats.reserve_total), cls: 'text-primary' },
        ];
        var html = '';
        for (var i = 0; i < cards.length; i++) {
            if (i % 4 === 0) html += '<div class="row">';
            html += '<div class="col-md-3 col-sm-6">'
                + '<div class="ledger-stat-card">'
                + '<div class="title">' + cards[i].title + '</div>'
                + '<div class="value ' + cards[i].cls + '">' + cards[i].value + '</div>'
                + '</div></div>';
            if (i % 4 === 3 || i === cards.length - 1) html += '</div>';
        }
        $('#ledger-statistics').html(html);
    }

    function updateChart(chartData) {
        if (!chartData || !chartData.labels || chartData.labels.length === 0) {
            return;
        }
        var dom = document.getElementById('ledgerChart');
        if (!dom) return;
        if (window._ledgerChart) {
            window._ledgerChart.dispose();
        }
        window._ledgerChart = Echarts.init(dom);
        window._ledgerChart.setOption({
            backgroundColor: '#fff', 
            tooltip: {
                trigger: 'axis',
                axisPointer: { type: 'line', lineStyle: { color: '#5ab1ef', width: 1 } },
                backgroundColor: 'rgba(50,50,50,0.92)',
                borderWidth: 0,
                textStyle: { color: '#fff' },
                padding: [10, 12]
            },
            legend: {  
                data: ['费用总额', '取货费-运满满', '取货费-货拉拉', '送货费-运满满', '送货费-货拉拉'],
                top: 8,
                right: 12,
                textStyle: { color: '#666', fontSize: 12 },
                itemWidth: 14,
                itemHeight: 10
            },
            grid: { left: '3%', right: '3%', top: '18%', bottom: '14%', containLabel: true },
            xAxis: {
                type: 'category',
                boundaryGap: false,
                data: chartData.labels,
                axisLine: { lineStyle: { color: '#dfe6ec' } },
                axisTick: { show: false },
                axisLabel: { interval: 0, color: '#8c8c8c', margin: 16, hideOverlap: true }
            },
            yAxis: {
                type: 'value',
                axisLine: { show: false },
                axisTick: { show: false },
                splitLine: { lineStyle: { color: '#eef2f7' } },
                axisLabel: { color: '#8c8c8c' }
            },
            series: [
                { 
                    name: '费用总额',
                    type: 'line',
                    smooth: true,
                    data: chartData.total,
                    symbol: 'circle',
                    symbolSize: 7,
                    showSymbol: true,
                    lineStyle: { color: '#5ab1ef', width: 3 },
                    itemStyle: { color: '#fff', borderColor: '#5ab1ef', borderWidth: 2 },
                    areaStyle: {
                        color: {
                            type: 'linear',
                            x: 0,
                            y: 0,
                            x2: 0,
                            y2: 1,
                            colorStops: [
                                { offset: 0, color: 'rgba(90,177,239,0.38)' },
                                { offset: 1, color: 'rgba(90,177,239,0.04)' }
                            ]
                        }
                    }
                },
                {
                    name: '取货费-运满满',
                    type: 'line',
                    smooth: true,
                    data: chartData.pickup_yunmanman || [],
                    symbol: 'circle',
                    symbolSize: 5,
                    showSymbol: false,
                    lineStyle: { color: '#91c7ae', width: 2 },
                    itemStyle: { color: '#91c7ae' }
                },
                {
                    name: '取货费-货拉拉',
                    type: 'line',
                    smooth: true,
                    data: chartData.pickup_huolala || [],
                    symbol: 'circle',
                    symbolSize: 5,
                    showSymbol: false,
                    lineStyle: { color: '#fc8452', width: 2 },
                    itemStyle: { color: '#fc8452' }
                },
                {
                    name: '送货费-运满满',
                    type: 'line',
                    smooth: true,
                    data: chartData.shipment_yunmanman || [],
                    symbol: 'circle',
                    symbolSize: 5,
                    showSymbol: false,
                    lineStyle: { color: '#73c0de', width: 2 },
                    itemStyle: { color: '#73c0de' }
                },
                {
                    name: '送货费-货拉拉',
                    type: 'line',
                    smooth: true,
                    data: chartData.shipment_huolala || [],
                    symbol: 'circle',
                    symbolSize: 5,
                    showSymbol: false,
                    lineStyle: { color: '#d48265', width: 2 },
                    itemStyle: { color: '#d48265' }
                }
            ]
        });
    }

    return Controller;
});

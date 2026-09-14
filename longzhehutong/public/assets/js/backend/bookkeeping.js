define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'bookkeeping/index' + location.search,
                    add_url: 'bookkeeping/add',
                    edit_url: 'bookkeeping/edit',
                    del_url: 'bookkeeping/del',
                    multi_url: 'bookkeeping/multi',
                    import_url: 'bookkeeping/import',
                    table: 'bookkeeping',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                responseHandler: function (res) {
                    if (res && res.statistics) {
                        Controller.updateStatistics(res.statistics);
                    }
                    return res;
                },
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'price', title: __('Price'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'break', title: __('Break'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        updateStatistics: function (statistics) {
            var totalIncome = parseFloat(statistics.total_income || 0);
            var totalExpense = parseFloat(statistics.total_expense || 0);
            var totalProfit = parseFloat(statistics.total_profit || 0);

            var $stats = $('#bookkeeping-statistics');
            if ($stats.length === 0) {
                $stats = $('<div id="bookkeeping-statistics" class="order-statistics-panel" style="margin-top:10px;"></div>');
                $('.toolbar').after($stats);
            }

            var html = ''
                + '<div class="row">'
                + '  <div class="col-md-4 col-sm-6">'
                + '    <div class="statistics-item income">'
                + '      <div class="statistics-label">总收入</div>'
                + '      <div class="statistics-value">¥' + (isNaN(totalIncome) ? '0.00' : totalIncome.toFixed(2)) + '</div>'
                + '    </div>'
                + '  </div>'
                + '  <div class="col-md-4 col-sm-6">'
                + '    <div class="statistics-item expense">'
                + '      <div class="statistics-label">总支出</div>'
                + '      <div class="statistics-value">¥' + (isNaN(totalExpense) ? '0.00' : totalExpense.toFixed(2)) + '</div>'
                + '    </div>'
                + '  </div>'
                + '  <div class="col-md-4 col-sm-6">'
                + '    <div class="statistics-item profit">'
                + '      <div class="statistics-label">利润</div>'
                + '      <div class="statistics-value">¥' + (isNaN(totalProfit) ? '0.00' : totalProfit.toFixed(2)) + '</div>'
                + '    </div>'
                + '  </div>'
                + '</div>';

            $stats.html(html).show();
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});

define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/route_price/index',
                    add_url: 'user/route_price/add',
                    edit_url: 'user/route_price/edit',
                    del_url: 'user/route_price/del',
                    multi_url: 'user/route_price/multi',
                    table: 'user_route_price',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                sortOrder: 'desc',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), sortable: true},
                        {field: 'user_name', title: '用户', operate: false},
                        {field: 'loading_province', title: '发货省份', operate: 'LIKE'},
                        {field: 'unload_province', title: '收货省份', operate: 'LIKE'},
                        {field: 'logistics_cost_percentage_text', title: '线路价格调整', operate: false},
                        // {field: 'pickup_driver_fee_percentage_text', title: '取货司机价格调整', operate: false},
                        // {field: 'shipment_driver_fee_percentage_text', title: '送货司机价格调整', operate: false},
                        {field: 'create_admin_name', title: '创建者', operate: false},
                        {field: 'createtime', title: __('Createtime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'updatetime', title: __('Updatetime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
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


define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'price/calc/profit/index' + location.search,
                    add_url: 'price/calc/profit/add',
                    edit_url: 'price/calc/profit/edit',
                    del_url: 'price/calc/profit/del',
                    multi_url: 'price/calc/profit/multi',
                    import_url: 'price/calc/profit/import',
                    table: 'price_calc_profit',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                fixedColumns: true,
                fixedRightNumber: 1,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'user_id', title: __('User_id')},
                        {field: 'mobile', title: __('Mobile'), operate: 'LIKE'},
                        {field: 'loading_address', title: __('Loading_address'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'unload_address', title: __('Unload_address'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'weight', title: __('Weight'), operate:'BETWEEN'},
                        {field: 'direction', title: __('Direction'), operate:'BETWEEN'},
                        {field: 'car_type_id', title: __('Car_type_id')},
                        {field: 'goods_type_id', title: __('Goods_type_id')},
                        {field: 'logistics_id', title: __('Logistics_id')},
                        {field: 'quoted_price', title: __('Quoted_price'), operate:'BETWEEN'},
                        {field: 'pay_price', title: __('Pay_price'), operate:'BETWEEN'},
                        {field: 'cost_cont', title: __('Cost_cont'), operate:'BETWEEN'},
                        {field: 'profit', title: __('Profit'), operate:'BETWEEN'},
                        {field: 'system_profit', title: __('System_profit'), operate:'BETWEEN'},
                        {field: 'logistics_cost', title: __('Logistics_cost'), operate:'BETWEEN'},
                        {field: 'logistics_driver_cost', title: __('Logistics_driver_cost'), operate:'BETWEEN'},
                        {field: 'pickup_fee', title: __('Pickup_fee'), operate:'BETWEEN'},
                        {field: 'pickup_driver_fee', title: __('Pickup_driver_fee'), operate:'BETWEEN'},
                        {field: 'shipment_fee', title: __('Shipment_fee'), operate:'BETWEEN'},
                        {field: 'shipment_driver_fee', title: __('Shipment_driver_fee'), operate:'BETWEEN'},
                        {field: 'pickup_distance', title: __('Pickup_distance'), operate:'BETWEEN'},
                        {field: 'logistics_distance', title: __('Logistics_distance'), operate:'BETWEEN'},
                        {field: 'shipmenty_distance', title: __('Shipmenty_distance'), operate:'BETWEEN'},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'is_chaxun', title: __('Is_chaxun')},
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

define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'provinceprice/index' + location.search,
                    add_url: 'provinceprice/add',
                    edit_url: 'provinceprice/edit',
                    del_url: 'provinceprice/del',
                    multi_url: 'provinceprice/multi',
                    import_url: 'provinceprice/import',
                    table: 'provinceprice',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'car_type', title: __('Car_type'), searchList: {"1":__('Car_type 1'),"2":__('Car_type 2'),"3":__('Car_type 3'),"4":__('Car_type 4')}, formatter: Table.api.formatter.normal},
                        {field: 'city', title: __('City'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'startingfare', title: __('Startingfare'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'price', title: __('Price'), operate:'BETWEEN'},
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

define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'settle/index' + location.search,
                    add_url: 'settle/add',
                    edit_url: 'settle/edit',
                    del_url: 'settle/del',
                    multi_url: 'settle/multi',
                    import_url: 'settle/import',
                    table: 'settle',
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
                        {field: 'uid', title: __('Uid')},
                        {field: 'company_name', title: __('Company_name'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'username', title: __('Username'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'mobile', title: __('Mobile'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'start_point', title: __('Start_point'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'end_point', title: __('End_point'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'time_limit', title: __('Time_limit')},
                        {field: 'heavy_pirce', title: __('Heavy_pirce'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'bulky_price', title: __('Bulky_price'), operate:'BETWEEN'},
                        {field: 'postcard_front_image', title: __('Postcard_front_image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'postcard_back_image', title: __('Postcard_back_image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'doorway_image', title: __('Doorway_image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1')}, formatter: Table.api.formatter.status},
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

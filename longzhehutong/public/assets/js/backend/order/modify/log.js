define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'order.modify.log/index' + location.search,
                    add_url: 'order.modify.log/add',
                    edit_url: 'order.modify.log/edit',
                    del_url: 'order.modify.log/del',
                    multi_url: 'order.modify.log/multi',
                    import_url: 'order.modify.log/import',
                    table: 'order_modify_log',
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
                        {field: 'admin_order_id', title: __('Admin_order_id')},
                        {field: 'order_id', title: __('Order_id')},
                        {field: 'orderid', title: __('Orderid'), operate: 'LIKE'},
                        {field: 'modify_type', title: __('Modify_type'), operate: 'LIKE'},
                        {field: 'title', title: __('Title'), operate: 'LIKE'},
                        {field: 'admin_id', title: __('Admin_id')},
                        {field: 'admin_name', title: __('Admin_name'), operate: 'LIKE'},
                        {field: 'logistics_status', title: __('Logistics_status')},
                        {field: 'audit_status', title: __('Audit_status')},
                        {field: 'remark', title: __('Remark'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'audit_time', title: __('Audit_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'audit_admin_id', title: __('Audit_admin_id')},
                        {field: 'audit_remark', title: __('Audit_remark'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
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

define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'member.order/index' + location.search,
                    add_url: 'member.order/add',
                    edit_url: 'member.order/edit',
                    del_url: 'member.order/del',
                    multi_url: 'member.order/multi',
                    import_url: 'member.order/import',
                    table: 'member_order',
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
                        {field: 'order_no', title: __('Order_no'), operate: 'LIKE'},
                        {field: 'user_id', title: __('User_id')},
                        {field: 'franchise_id', title: __('Franchise_id')},
                        {field: 'package_id', title: __('Package_id')},
                        {field: 'package_name', title: __('Package_name'), operate: 'LIKE'},
                        {field: 'membertype_before', title: __('Membertype_before')},
                        {field: 'membertype_after', title: __('Membertype_after'), searchList: {"2":__('Membertype_after 2')}, formatter: Table.api.formatter.normal},
                        {field: 'member_duration', title: __('Member_duration')},
                        {field: 'member_unit', title: __('Member_unit')},
                        {field: 'price', title: __('Price'), operate:'BETWEEN'},
                        {field: 'pay_type', title: __('Pay_type'), operate: 'LIKE'},
                        {field: 'pay_status', title: __('Pay_status')},
                        {field: 'pay_time', title: __('Pay_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'expiry_before', title: __('Expiry_before')},
                        {field: 'expiry_after', title: __('Expiry_after')},
                        {field: 'remark', title: __('Remark'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
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

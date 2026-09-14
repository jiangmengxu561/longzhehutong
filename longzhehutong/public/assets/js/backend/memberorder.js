define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'memberorder/index' + location.search,
                    add_url: 'memberorder/add',
                    edit_url: 'memberorder/edit',
                    del_url: 'memberorder/del',
                    multi_url: 'memberorder/multi',
                    table: 'memberorder',
                }
            });

            var table = $("#table");

            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                commonSearch: true,
                search: true,
                pageSize: 20,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'order_no', title: __('Order_no'), operate: 'LIKE'},
                        {
                            field: 'user.mobile',
                            title: __('会员手机号'),
                            operate: false,
                            formatter: function (value, row) {
                                return (row.user && row.user.mobile) ? row.user.mobile : (row.user_id || '');
                            }
                        },
                        {field: 'package_name', title: __('Package_name'), operate: 'LIKE'},
                        {field: 'membertype_before_text', title: __('Membertype_before'), operate: false},
                        {field: 'membertype_after_text', title: __('Membertype_after'), operate: false},
                        {field: 'duration_text', title: __('Member_duration'), operate: false},
                        {field: 'price', title: __('Price'), operate: false},
                        {field: 'pay_status_text', title: __('Pay_status'), operate: false},
                        {field: 'expiry_after', title: __('Expiry_after'), operate: false, formatter: Table.api.formatter.datetime},
                        {field: 'pay_time', title: __('Pay_time'), operate: false, formatter: Table.api.formatter.datetime},
                        {field: 'createtime', title: __('Createtime'), operate: false, formatter: Table.api.formatter.datetime},
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

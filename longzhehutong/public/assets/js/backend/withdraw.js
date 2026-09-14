define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'withdraw/index' + location.search,
                    add_url: 'withdraw/add',
                    edit_url: 'withdraw/edit',
                    del_url: 'withdraw/del',
                    multi_url: 'withdraw/multi',
                    import_url: 'withdraw/import',
                    table: 'withdraw',
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
                        {field: 'money', title: __('Money'), operate:'BETWEEN'},
                        {field: 'handingfee', title: __('Handingfee'), operate:'BETWEEN'},
                        {field: 'taxes', title: __('Taxes'), operate:'BETWEEN'},
                        {field: 'type', title: __('Type'), operate: 'LIKE'},
                        {field: 'account', title: __('Account'), operate: 'LIKE'},
                        {field: 'name', title: __('Name'), operate: 'LIKE'},
                        {field: 'memo', title: __('Memo'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'orderid', title: '关联订单', operate: 'LIKE', formatter: function (value) {
                                return value ? value : '-';
                            }},
                        {field: 'transactionid', title: __('Transactionid'), operate: 'LIKE'},
                        {field: 'status', title: __('Status'), searchList: {"created":__('Status created'),"successed":__('Status successed'),"rejected":__('Status rejected')}, formatter: Table.api.formatter.status},
                        {field: 'transfertime', title: __('Transfertime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'view_order',
                                    text: '订单详情',
                                    title: '查看关联订单详情',
                                    classname: 'btn btn-xs btn-info btn-dialog',
                                    icon: 'fa fa-eye',
                                    url: 'withdraw/order_detail',
                                    hidden: function (row) {
                                        return !row.orderid;
                                    }
                                },
                                {
                                    name: 'pay',
                                    text: '直接打款',
                                    title: '直接打款',
                                    classname: 'btn btn-xs btn-success btn-ajax',
                                    icon: 'fa fa-rmb',
                                    url: 'withdraw/pay',
                                    confirm: '确认对该提现记录执行打款操作？',
                                    success: function () {
                                        table.bootstrapTable('refresh');
                                    }
                                }
                            ]
                        }
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

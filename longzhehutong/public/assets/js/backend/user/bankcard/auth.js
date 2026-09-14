define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/bankcard/auth/index' + location.search,
                    add_url: 'user/bankcard/auth/add',
                    edit_url: 'user/bankcard/auth/edit',
                    del_url: 'user/bankcard/auth/del',
                    multi_url: 'user/bankcard/auth/multi',
                    import_url: 'user/bankcard/auth/import',
                    table: 'user_bankcard_auth',
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
                        {field: 'name', title: __('Name'), operate: 'LIKE'},
                        {field: 'id_card', title: __('Id_card'), operate: 'LIKE'},
                        {field: 'account_no', title: __('Account_no'), operate: 'LIKE'},
                        {field: 'mobile', title: __('Mobile'), operate: 'LIKE'},
                        {field: 'status', title: __('Status'), searchList: {"10":__('Status 10')}, formatter: Table.api.formatter.status},
                        {field: 'msg', title: __('Msg'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'bank', title: __('Bank'), operate: 'LIKE'},
                        {field: 'card_name', title: __('Card_name'), operate: 'LIKE'},
                        {field: 'card_type', title: __('Card_type'), operate: 'LIKE'},
                        {field: 'trace_id', title: __('Trace_id'), operate: 'LIKE'},
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

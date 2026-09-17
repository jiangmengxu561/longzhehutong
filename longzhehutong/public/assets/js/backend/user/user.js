define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/user/index',
                    add_url: 'user/user/add',
                    edit_url: 'user/user/edit',
                    del_url: 'user/user/del',
                    multi_url: 'user/user/multi',
                    table: 'user',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'user.id',
                fixedColumns: true, 
                fixedRightNumber: 1,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), sortable: true},
                        {field: 'order_num', title: __('测价次数'), sortable: true},
                        {field: 'pay_num', title: __('下单次数')},
                        {field: 'ocr_num', title: __('OCR使用次数'), sortable: true},
                        {
                            field: 'identity',
                            title: __('用户身份'),
                            searchList: {1: __('用户'), 2: __('司机'), 3: __('专线')},
                            formatter: function (value) {
                                return {1: __('用户'), 2: __('司机'), 3: __('专线')}[value] || '-';
                            }
                        },
                        {field: 'username', title: __('Username'), operate: 'LIKE'},
                        // {field: 'nickname', title: __('Nickname'), operate: 'LIKE'},
                        // {field: 'email', title: __('Email'), operate: 'LIKE'},
                        {field: 'mobile', title: __('Mobile'), operate: 'LIKE'},
                        {field: 'percentage', title: __('收费百分比'), operate: 'LIKE'},
                        {field: 'channel_id', title: __('用户来源'), operate: 'LIKE'},
                        // {field: 'avatar', title: __('Avatar'), events: Table.api.events.image, formatter: Table.api.formatter.image, operate: false},
                        // {field: 'level', title: __('Level'), operate: 'BETWEEN', sortable: true},
                        // {field: 'gender', title: __('Gender'), visible: false, searchList: {1: __('Male'), 0: __('Female')}},
                        // {field: 'score', title: __('Score'), operate: 'BETWEEN', sortable: true},
                        {field: 'successions', title: __('Successions'), visible: false, operate: 'BETWEEN', sortable: true},
                        {field: 'maxsuccessions', title: __('Maxsuccessions'), visible: false, operate: 'BETWEEN', sortable: true},
                        {field: 'logintime', title: __('Logintime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'loginip', title: __('Loginip'), formatter: Table.api.formatter.search},
                        {field: 'jointime', title: __('Jointime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'member_time', title: __('会员到期时间'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'joinip', title: __('Joinip'), formatter: Table.api.formatter.search},
                        {field: 'membertype', title: __('职位'), formatter: Table.api.formatter.status, searchList: {1: __('普通用户'), 2: __('兼职员工'), 3: __('正式员工'), 4: __('会展员工')}},
                        {field: 'status', title: __('Status'), formatter: Table.api.formatter.status, searchList: {normal: __('Normal'), hidden: __('Hidden')}},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'detail',
                                    text: __('查看下单情况'),
                                    title: __('查看下单情况'),
                                    classname: 'btn btn-xs btn-info btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'order/user_orders'
                                }
                        ]

                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
            // 仅加盟商后台：按手机号绑定会员
            $('#btn-bind-member').on('click', function () {
                Fast.api.open('franchise/memberselect', '按手机号绑定会员', {
                    callback: function () {
                        Table.api.refresh(Table.api.init());
                    }
                });
            });
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            // 加盟商在此页编辑会员身份/到期（表单与 franchise/memberedit 相同），
            // 非整月或到期时间比现在早时先提示；其他编辑表单走默认逻辑
            require(['backend/memberfee'], function (MemberFee) {
                MemberFee.bind($("form[role=form]"));
            });
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});

define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            Table.api.init({
                extend: {
                    index_url: 'franchise/index' + location.search,
                    add_url: 'franchise/add',
                    edit_url: 'franchise/edit',
                    del_url: 'franchise/del',
                    multi_url: 'franchise/multi',
                    table: 'franchise',
                }
            });
            var table = $("#table");
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [[
                    {checkbox: true},
                    {field: 'id', title: 'ID'},
                    {field: 'name', title: '加盟商名称', operate: 'LIKE'},
                    {field: 'level_text', title: '层级', operate: false},
                    {field: 'parent_name', title: '上级', operate: false},
                    {field: 'contact', title: '联系人', operate: 'LIKE'},
                    {field: 'mobile', title: '电话', operate: 'LIKE'},
                    {field: 'admin_username', title: '登录账号', operate: false},
                    {field: 'wallet_balance', title: '钱包余额', operate: false},
                    {field: 'commission_total', title: '累计抽佣', operate: false, align: 'right', formatter: function (v) { return '¥' + (v || '0'); }},
                    {field: 'status_text', title: '状态', operate: false},
                    {field: 'operate', title: '操作', operate: false, align: 'left', events: Controller.operateEvents, formatter: Controller.operateFormatter}
                ]]
            });
            Table.api.bindevent(table);
            $('.btn-fr-config').on('click', function () {
                Fast.api.open('franchise/config', null, { callback: function () {
                    Table.api.refresh(Table.api.init());
                }});
            });
            $('.btn-comm-detail').on('click', function () {
                Fast.api.open('franchise/commdetail', '抽佣明细');
            });
        },

        operateFormatter: function (value, row, index) {
            var html = '';
            html += '<a class="btn btn-xs btn-info btn-fr-edit" href="javascript:;" data-id="' + row.id + '"><i class="fa fa-pencil"></i> 编辑</a> ';
            html += '<a class="btn btn-xs btn-success btn-fr-wallet" href="javascript:;" data-id="' + row.id + '"><i class="fa fa-coins"></i> 钱包</a> ';
            html += '<a class="btn btn-xs btn-primary btn-fr-member" href="javascript:;" data-id="' + row.id + '"><i class="fa fa-users"></i> 会员</a> ';
            html += '<a class="btn btn-xs btn-info btn-fr-staff" href="javascript:;" data-id="' + row.id + '"><i class="fa fa-users"></i> 员工</a> ';
            if (row.level == 1) {
                html += '<a class="btn btn-xs btn-warning btn-fr-adjust" href="javascript:;" data-id="' + row.id + '"><i class="fa fa-edit"></i> 调钱包</a> ';
            }
            if (row.contract) {
                var contractUrl = row.contract;
                if (contractUrl.indexOf('http') !== 0 && contractUrl.indexOf('//') !== 0
                    && typeof Fast !== 'undefined' && Fast.api && Fast.api.cdnurl) {
                    contractUrl = Fast.api.cdnurl(contractUrl);
                }
                html += '<a class="btn btn-xs btn-success btn-fr-contract" href="' + contractUrl + '" target="_blank" title="下载合同"><i class="fa fa-download"></i> 下载合同</a> ';
            }
            html += '<a class="btn btn-xs btn-danger btn-fr-del" href="javascript:;" data-id="' + row.id + '"><i class="fa fa-trash"></i> 删除</a>';
            return html;
        },

        operateEvents: {
            'click .btn-fr-edit': function (e, value, row, index) {
                Fast.api.open('franchise/edit?ids=' + row.id, '编辑加盟商');
            },
            'click .btn-fr-wallet': function (e, value, row, index) {
                Fast.api.open('franchise/wallet?franchise_id=' + row.id, null, { callback: function () {
                    Table.api.refresh(Table.api.init());
                }});
            },
            'click .btn-fr-member': function (e, value, row, index) {
                Fast.api.open('franchise/member?franchise_id=' + row.id, null, { callback: function () {
                    Table.api.refresh(Table.api.init());
                }});
            },
            'click .btn-fr-staff': function (e, value, row, index) {
                Fast.api.open('franchise/staff?franchise_id=' + row.id, null, { callback: function () {
                    Table.api.refresh(Table.api.init());
                }});
            },
            'click .btn-fr-adjust': function (e, value, row, index) {
                Fast.api.open('franchise/adjustwallet?franchise_id=' + row.id, null, { callback: function () {
                    Table.api.refresh(Table.api.init());
                }});
            },
            'click .btn-fr-del': function (e, value, row, index) {
                var ids = row.id;
                Layer.confirm('确定要删除（禁用）该加盟商吗？', function () {
                    $.post('franchise/del', {ids: ids}, function (ret) {
                        if (ret.code === 1) {
                            Layer.alert(ret.msg || '操作成功', {
                                icon: 1,
                                title: '删除加盟商',
                                yes: function (index) {
                                    Layer.close(index);
                                    Table.api.refresh(Table.api.init());
                                }
                            });
                        } else {
                            Layer.alert(ret.msg || '操作失败', {icon: 2, title: '删除加盟商'});
                        }
                    }, 'json');
                });
            }
        },

        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        config: function () {
            Controller.api.bindevent();
        },
        adjustwallet: function () {
            Controller.api.bindevent();
        },
        staff: function () {
            var franchiseId = $('#franchise_id').val();
            Table.api.init({
                extend: {
                    index_url: 'franchise/staff?franchise_id=' + franchiseId,
                    table: 'franchise_staff',
                }
            });
            var table = $("#table");
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'admin_id',
                sortName: 'admin_id',
                columns: [[
                    {field: 'admin_id', title: 'ID'},
                    {field: 'username', title: '登录账号', operate: 'LIKE'},
                    {field: 'nickname', title: '姓名/昵称', operate: 'LIKE'},
                    {field: 'mobile', title: '手机号', operate: 'LIKE', formatter: function (v) { return v || '-'; }},
                    {field: 'role_text', title: '角色', operate: false},
                    {field: 'status_text', title: '状态', operate: false},
                    {field: 'operate', title: '操作', operate: false, align: 'left',
                     formatter: function (value, row, index) {
                         return '<a class="btn btn-xs btn-warning btn-staff-toggle" href="javascript:;" data-id="' + row.admin_id + '" data-status="' + (row.status === 'normal' ? 'hidden' : 'normal') + '">' +
                             (row.status === 'normal' ? '停用' : '启用') + '</a> ' +
                             '<a class="btn btn-xs btn-danger btn-staff-del" href="javascript:;" data-id="' + row.admin_id + '">删除</a>';
                     },
                     events: {
                         'click .btn-staff-toggle': function (e, value, row, index) {
                             $.post('franchise/staffstatus', {admin_id: row.admin_id, status: (row.status === 'normal' ? 'hidden' : 'normal')}, function (ret) {
                                 Layer.msg(ret.msg);
                                 Table.api.refresh(Table.api.init());
                             }, 'json');
                         },
                         'click .btn-staff-del': function (e, value, row, index) {
                             Layer.confirm('确定删除该员工账号吗？', function () {
                                 $.post('franchise/staffdel', {admin_id: row.admin_id}, function (ret) {
                                     Layer.msg(ret.msg);
                                     Table.api.refresh(Table.api.init());
                                 }, 'json');
                             });
                         }
                     }}
                ]]
            });
            Table.api.bindevent(table);
            $('#btn-add-staff').on('click', function () {
                Fast.api.open('franchise/staffadd?franchise_id=' + franchiseId, null, { callback: function () {
                    Table.api.refresh(Table.api.init());
                }});
            });
        },
        staffadd: function () {
            Controller.api.bindevent();
        },
        memberedit: function () {
            // 改身份/到期：整月不提示，非整月/到期时间比现在早时先提示（见 backend/memberfee.js）
            require(['backend/memberfee'], function (MemberFee) {
                MemberFee.bind($("form[role=form]"));
            });
        },

        commdetail: function () {
            Table.api.init({
                extend: {
                    index_url: 'franchise/commdetail',
                    table: 'franchise_wallet_log',
                }
            });
            var table = $("#table");
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                sortOrder: 'desc',
                columns: [[
                    {field: 'id', title: 'ID', operate: false},
                    {field: 'franchise_name', title: '加盟商', operate: false},
                    {field: 'amount', title: '抽佣金额', operate: false, align: 'right', formatter: function (v) { return '¥' + (v || '0'); }},
                    {field: 'order_number', title: '订单号', operate: false},
                    {field: 'remark', title: '备注', operate: false},
                    {field: 'balance_after', title: '扣后余额', operate: false},
                    {field: 'createtime_text', title: '抽佣时间', operate: false}
                ]]
            });
            Table.api.bindevent(table);
            $('#btn-comm-search').on('click', function () {
                table.bootstrapTable('refresh', {
                    query: {
                        startdate: $('#startdate').val(),
                        enddate: $('#enddate').val()
                    }
                });
            });
        },

        wallet: function () {
            var franchiseId = $('#franchise_id').val();
            Table.api.init({
                extend: {
                    index_url: 'franchise/wallet?franchise_id=' + franchiseId,
                    table: 'franchise_wallet_log',
                }
            });
            var table = $("#table");
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [[
                    {field: 'id', title: 'ID'},
                    {field: 'franchise_name', title: '加盟商', operate: false},
                    {field: 'type_text', title: '方向', operate: false},
                    {field: 'amount', title: '金额', operate: false},
                    {field: 'balance_before', title: '变动前', operate: false},
                    {field: 'balance_after', title: '变动后', operate: false},
                    {field: 'related_type', title: '关联类型', operate: false},
                    {field: 'related_id', title: '关联ID', operate: false},
                    {field: 'remark', title: '备注', operate: false},
                    {field: 'operator_name', title: '操作人', operate: false},
                    {field: 'createtime_text', title: '时间', operate: false}
                ]]
            });
            Table.api.bindevent(table);
        },

        member: function () {
            var franchiseId = $('#franchise_id').val();
            Table.api.init({
                extend: {
                    index_url: 'franchise/member?franchise_id=' + franchiseId,
                    table: 'franchise_member',
                }
            });
            var table = $("#table");
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [[
                    {field: 'id', title: 'ID'},
                    {field: 'username', title: '用户名', operate: 'LIKE'},
                    {field: 'mobile', title: '手机号', operate: 'LIKE'},
                    {field: 'membertype_text', title: '身份/职位', operate: false},
                    {field: 'member_time', title: '到期时间', operate: false},
                    {field: 'platform_commission', title: '平台抽佣%', operate: false},
                    {field: 'franchise_name', title: '所属加盟商', operate: false},
                    {field: 'operate', title: '操作', operate: false, align: 'left', events: Controller.memberEvents, formatter: Controller.memberFormatter}
                ]]
            });
            Table.api.bindevent(table);
            $('#btn-add-member').on('click', function () {
                Fast.api.open('franchise/memberselect', null, { callback: function () {
                    Table.api.refresh(Table.api.init());
                }});
            });
        },

        memberFormatter: function (value, row, index) {
            return '<a class="btn btn-xs btn-success btn-fr-memberedit" href="javascript:;" data-id="' + row.user_id + '"><i class="fa fa-pencil"></i> 改身份/到期</a>';
        },

        memberEvents: {
            'click .btn-fr-memberedit': function (e, value, row, index) {
                Fast.api.open('franchise/memberedit?user_id=' + row.user_id, null, { callback: function () {
                    Table.api.refresh(Table.api.init());
                }});
            }
        },

        memberselect: function () {
            Form.api.bindevent($("form[role=form]"));
            $('#btn-search').on('click', function () {
                var mobile = $('#mobile').val();
                if (!mobile) {
                    Layer.msg('请输入手机号');
                    return;
                }
                $.get('franchise/memberselect', {mobile: mobile}, function (ret) {
                    if (ret.total > 0) {
                        var row = ret.rows[0];
                        $('#search-result').html(
                            '<table class="table table-bordered">' +
                            '<tr><th>用户名</th><td>' + row.username + '</td></tr>' +
                            '<tr><th>手机号</th><td>' + row.mobile + '</td></tr>' +
                            '<tr><th>身份/职位</th><td>' + row.membertype_text + '</td></tr>' +
                            '<tr><th>到期时间</th><td>' + row.member_time + '</td></tr>' +
                            '</table>' +
                            '<button class="btn btn-primary btn-bind" data-user-id="' + row.id + '">绑定到我的名下</button>'
                        );
                    } else {
                        $('#search-result').html('<div class="alert alert-warning">未找到可绑定的普通用户（仅手机号已注册且为“普通用户”的会员可绑定，司机/专线不可绑定）</div>');
                    }
                }, 'json');
            });
            $(document).on('click', '.btn-bind', function () {
                var userId = $(this).data('user-id');
                $.post('franchise/bind', {user_id: userId}, function (res) {
                    Layer.msg(res.msg);
                    if (res.code === 1) {
                        $('#search-result').html('<div class="alert alert-success">' + res.msg + '</div>');
                    }
                }, 'json');
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

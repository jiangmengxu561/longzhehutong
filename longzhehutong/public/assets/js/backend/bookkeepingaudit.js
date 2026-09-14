define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 轮询：有新提交的待审核申请时提醒并刷新（总后台为主）
            (function pendingAuditPoll() {
                var lastId = 0;
                var unlocked = false;
                var audioCtx = null;
                window.__bookkeepingauditVoiceUnlock = function () { unlocked = true; };
                function url(path) {
                    if (typeof Backend !== 'undefined' && Backend.api && Backend.api.fixurl)
                        return Backend.api.fixurl(path);
                    var base = location.pathname.replace(/\/index.*$/, '').replace(/\/$/, '') || '/admin/bookkeepingaudit';
                    return base + '/' + (path.split('/').pop() || path);
                }
                function beep(times) {
                    times = times || 1;
                    try {
                        var Ctx = window.AudioContext || window.webkitAudioContext;
                        if (!Ctx) return;
                        if (!audioCtx) audioCtx = new Ctx();
                        if (audioCtx.state === 'suspended') audioCtx.resume();
                        for (var i = 0; i < times; i++) {
                            (function (j) {
                                setTimeout(function () {
                                    try {
                                        var osc = audioCtx.createOscillator();
                                        var g = audioCtx.createGain();
                                        osc.connect(g);
                                        g.connect(audioCtx.destination);
                                        osc.frequency.value = 920;
                                        osc.type = 'sine';
                                        g.gain.setValueAtTime(0.2, audioCtx.currentTime);
                                        g.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.15);
                                        osc.start(audioCtx.currentTime);
                                        osc.stop(audioCtx.currentTime + 0.15);
                                    } catch (e) {}
                                }, j * 220);
                            })(i);
                        }
                    } catch (e) {}
                }
                function speak(text) {
                    if (!text || !window.speechSynthesis) return;
                    try {
                        window.speechSynthesis.cancel();
                        var u = new SpeechSynthesisUtterance(text);
                        u.lang = 'zh-CN';
                        u.rate = 0.9;
                        window.speechSynthesis.speak(u);
                    } catch (e) {}
                }
                function desktopNotify(title, body) {
                    if (!('Notification' in window) || Notification.permission !== 'granted') return;
                    try {
                        var n = new Notification(title || '记账审核提醒', { body: body || '', tag: 'bookkeepingaudit-pending', renotify: true });
                        n.onclick = function () { window.focus(); n.close(); };
                    } catch (e) {}
                }
                function toast(msg) {
                    if (typeof Toastr !== 'undefined') {
                        Toastr.info(msg, '记账审核');
                    } else if (window.top && window.top.Toastr) {
                        window.top.Toastr.info(msg, '记账审核');
                    }
                }
                function unlock() {
                    if (unlocked) return;
                    unlocked = true;
                    if ('Notification' in window && Notification.permission === 'default') {
                        Notification.requestPermission();
                    }
                    beep(1);
                    setTimeout(function () { speak('审核提醒已开启'); }, 100);
                    var tip = document.getElementById('bookkeepingaudit-voice-tip');
                    if (tip) {
                        tip.innerHTML = '<i class="fa fa-volume-up"></i> 提醒已开启';
                        tip.style.color = '#5cb85c';
                        tip.onclick = null;
                    }
                }
                setTimeout(function () {
                    var toolbar = document.getElementById('toolbar');
                    if (toolbar && !document.getElementById('bookkeepingaudit-voice-tip')) {
                        var tip = document.createElement('a');
                        tip.id = 'bookkeepingaudit-voice-tip';
                        tip.href = 'javascript:;';
                        tip.className = 'btn btn-default btn-sm';
                        tip.innerHTML = '<i class="fa fa-volume-off"></i> 点击启用审核提醒';
                        tip.title = '点击后开启声音，并请求系统通知权限（最小化时也能弹窗提醒）';
                        tip.onclick = function () { unlock(); };
                        toolbar.appendChild(tip);
                    }
                }, 800);
                function check() {
                    $.ajax({
                        url: url('bookkeepingaudit/pending_audit_alert'),
                        type: 'GET',
                        data: { last_id: lastId },
                        dataType: 'json',
                        xhrFields: { withCredentials: true },
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    }).done(function (ret) {
                        if (!ret || ret.code !== 1 || !ret.data) return;
                        var d = ret.data;
                        var hasNew = d.has_new == 1 || d.has_new === true;
                        var msg = (d.message && d.message.trim()) ? d.message.trim() : '';
                        if (hasNew && msg) {
                            toast(msg);
                            if ('Notification' in window && Notification.permission === 'granted') {
                                desktopNotify('记账审核提醒', msg);
                            }
                            if (unlocked) {
                                beep(3);
                                setTimeout(function () { speak(msg); }, 450);
                            }
                            var t = document.getElementById('table');
                            if (t && $(t).data('bootstrap.table')) $(t).bootstrapTable('refresh');
                            if (d.latest_id > 0) lastId = d.latest_id;
                        } else if (!hasNew && d.latest_id > 0) {
                            lastId = d.latest_id;
                        }
                    });
                }
                setInterval(check, 5000);
                check();
            })();

            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'bookkeepingaudit/index' + location.search,
                    add_url: 'bookkeepingaudit/add',
                    edit_url: 'bookkeepingaudit/edit',
                    del_url: 'bookkeepingaudit/del',
                    multi_url: 'bookkeepingaudit/multi',
                    import_url: 'bookkeepingaudit/import',
                    table: 'bookkeeping_audit',
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
                        {field: 'bookkeeping_id', title: '记账ID'},
                        {field: 'type_text', title: '操作类型', searchList: {"edit":'修改',"del":'删除'}, formatter: function (value, row) {
                            return row.type === 'del' ? '<span class="label label-danger">删除</span>' : '<span class="label label-primary">修改</span>';
                        }},
                        {field: 'price', title: __('Price'), table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'break', title: __('Break'), table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'admin_name', title: __('Admin_name'), operate: 'LIKE'},
                        {field: 'audit_status', title: __('Audit_status'), searchList: {"0":__('待审核'),"1":__('已通过'),"2":__('已拒绝')}, formatter: function (value, row) {
                            var map = {0: ['待审核', 'bk-audit-status-0'], 1: ['已通过', 'bk-audit-status-1'], 2: ['已拒绝', 'bk-audit-status-2']};
                            var item = map[value] || [value, ''];
                            return '<span class="' + item[1] + '">' + item[0] + '</span>';
                        }},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'audit_time', title: __('Audit_time'), formatter: function (value) {
                            return value > 0 ? Table.api.formatter.datetime.call(this, value) : '';
                        }},
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

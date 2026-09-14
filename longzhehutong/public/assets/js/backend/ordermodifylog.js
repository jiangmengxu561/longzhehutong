define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 总后台：他人提交待审核时轮询提示音（与订单页逻辑类似）
            (function pendingAuditPoll() {
                var lastId = 0;
                var unlocked = false;
                var audioCtx = null;
                window.__ordermodifylogVoiceUnlock = function () { unlocked = true; };
                function url(path) {
                    if (typeof Backend !== 'undefined' && Backend.api && Backend.api.fixurl)
                        return Backend.api.fixurl(path);
                    var base = location.pathname.replace(/\/index.*$/, '').replace(/\/$/, '') || '/admin/ordermodifylog';
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
                        var n = new Notification(title || '订单修改待审核', { body: body || '', tag: 'ordermodifylog-pending', renotify: true });
                        n.onclick = function () { window.focus(); n.close(); };
                    } catch (e) {}
                }
                function unlock() {
                    if (unlocked) return;
                    unlocked = true;
                    if ('Notification' in window && Notification.permission === 'default') {
                        Notification.requestPermission();
                    }
                    beep(1);
                    setTimeout(function () { speak('审核提醒已开启'); }, 100);
                    var tip = document.getElementById('ordermodifylog-voice-tip');
                    if (tip) {
                        tip.innerHTML = '<i class="fa fa-volume-up"></i> 提醒已开启';
                        tip.style.color = '#5cb85c';
                        tip.onclick = null;
                    }
                }
                setTimeout(function () {
                    var toolbar = document.getElementById('toolbar');
                    if (toolbar && !document.getElementById('ordermodifylog-voice-tip')) {
                        var tip = document.createElement('a');
                        tip.id = 'ordermodifylog-voice-tip';
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
                        url: url('ordermodifylog/pending_audit_alert'),
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
                            var notified = false;
                            if ('Notification' in window && Notification.permission === 'granted') {
                                desktopNotify('订单修改待审核', msg);
                                notified = true;
                            }
                            if (unlocked) {
                                beep(3);
                                setTimeout(function () { speak(msg); }, 450);
                                notified = true;
                            }
                            if (notified) {
                                var t = document.getElementById('table');
                                if (t && $(t).data('bootstrap.table')) $(t).bootstrapTable('refresh');
                                if (d.latest_id > 0) lastId = d.latest_id;
                            }
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
                    index_url: 'ordermodifylog/index' + location.search,
                    add_url: 'ordermodifylog/add',
                    edit_url: 'ordermodifylog/edit',
                    del_url: 'ordermodifylog/del',
                    multi_url: 'ordermodifylog/multi',
                    import_url: 'ordermodifylog/import',
                    table: 'ordermodifylog',
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
                        // {field: 'admin_order_id', title: __('Admin_order_id')},
                        // {field: 'order_id', title: __('Order_id')},
                        {field: 'orderid', title: __('Orderid'), operate: 'LIKE'},
                        // {field: 'modify_type', title: __('Modify_type'), operate: 'LIKE'},
                        {field: 'title', title: __('Title'), operate: 'LIKE'},
                        // {field: 'admin_id', title: __('Admin_id')},
                        {field: 'admin_name', title: __('Admin_name'), operate: 'LIKE'},
                        // {field: 'logistics_status', title: __('Logistics_status')},
                        {field: 'audit_status', title: __('Audit_status'),searchList: {"1":__('已审核'),"0":__('待审核')}, formatter: Table.api.formatter.status},
                        // {field: 'remark', title: __('Remark'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'audit_time', title: __('Audit_time'), formatter: Table.api.formatter.datetime},
                        // {field: 'audit_admin_id', title: __('Audit_admin_id')},
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

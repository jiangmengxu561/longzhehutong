define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // ===== 提醒开关（每个账号/每台电脑独立记忆，默认关闭）=====
            (function () {
                var cfg = (typeof Config !== 'undefined' && Config.admin) ? Config.admin
                    : (window.parent && window.parent.Config && window.parent.Config.admin ? window.parent.Config.admin : null);
                var adminId = (cfg && cfg.id) ? parseInt(cfg.id, 10) : 0;
                var vId = (cfg && (cfg.voice_identity !== undefined ? cfg.voice_identity : cfg.identity) !== undefined)
                    ? parseInt(cfg.voice_identity !== undefined ? cfg.voice_identity : cfg.identity, 10)
                    : 0;
                var isLineOrDispatch = (vId === 2 || vId === 3);
                var key = 'order_remind_' + adminId;
                function read() {
                    try { return window.localStorage.getItem(key) === '1'; } catch (e) { return false; }
                }
                function write(v) {
                    try {
                        if (v) { window.localStorage.setItem(key, '1'); } else { window.localStorage.removeItem(key); }
                    } catch (e) {}
                }
                var control = {
                    adminId: adminId,
                    isLineOrDispatch: isLineOrDispatch,
                    isOn: read,
                    enable: function () { write(true); },
                    disable: function () { write(false); },
                    _starters: [],
                    _stoppers: [],
                    _started: {},
                    register: function (start, stop) {
                        if (typeof start === 'function') control._starters.push(start);
                        if (typeof stop === 'function') control._stoppers.push(stop);
                    }
                };
                window.__orderRemindStart = function () {
                    (control._starters || []).forEach(function (fn) { try { fn(); } catch (e) {} });
                };
                window.__orderRemindStop = function () {
                    (control._stoppers || []).forEach(function (fn) { try { fn(); } catch (e) {} });
                };
                window.__orderRemind = control;
            })();
            if (typeof console !== 'undefined' && console.log) {
                console.log('[路线变更提醒] order.js index() 已执行（总后台订单页）');
            }
            // 尽早启动轮询（不依赖后续表格绑定），并在此处理新单提示音
            (function startPollingEarly() {
                var earlyLastId = 0;
                var earlyAudioCtx = null;
                var earlyVoiceUnlocked = false;
                window.__orderVoiceUnlock = function () { earlyVoiceUnlocked = true; };
                function url(path) {
                    if (typeof Backend !== 'undefined' && Backend.api && Backend.api.fixurl)
                        return Backend.api.fixurl(path);
                    var base = location.pathname.replace(/\/index.*$/, '').replace(/\/$/, '') || '/admin/order';
                    return base + '/' + (path.split('/').pop() || path);
                }
                function beep(times) {
                    times = times || 1;
                    try {
                        var Ctx = window.AudioContext || window.webkitAudioContext;
                        if (!Ctx) return;
                        if (!earlyAudioCtx) earlyAudioCtx = new Ctx();
                        if (earlyAudioCtx.state === 'suspended') earlyAudioCtx.resume();
                        for (var i = 0; i < times; i++) {
                            (function (j) {
                                setTimeout(function () {
                                    try {
                                        var osc = earlyAudioCtx.createOscillator();
                                        var g = earlyAudioCtx.createGain();
                                        osc.connect(g);
                                        g.connect(earlyAudioCtx.destination);
                                        osc.frequency.value = 880;
                                        osc.type = 'sine';
                                        g.gain.setValueAtTime(0.2, earlyAudioCtx.currentTime);
                                        g.gain.exponentialRampToValueAtTime(0.01, earlyAudioCtx.currentTime + 0.15);
                                        osc.start(earlyAudioCtx.currentTime);
                                        osc.stop(earlyAudioCtx.currentTime + 0.15);
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
                function unlockVoice() {
                    if (earlyVoiceUnlocked) return;
                    earlyVoiceUnlocked = true;
                    beep(1);
                    setTimeout(function () { speak('语音提醒已开启'); }, 100);
                    var tip = document.getElementById('order-voice-unlock-tip');
                    if (tip) {
                        tip.innerHTML = '<i class="fa fa-volume-up"></i> 语音已开启';
                        tip.style.color = '#5cb85c';
                        tip.onclick = null;
                    }
                }
                // 提醒开关按钮（每个账号/每台电脑独立记忆，默认关闭，点开才开始轮询）
                function refreshEarlyRemindTip() {
                    var tip = document.getElementById('order-voice-unlock-tip');
                    if (!tip) return;
                    var on = window.__orderRemind && window.__orderRemind.isOn();
                    if (on) {
                        tip.innerHTML = '<i class="fa fa-volume-up"></i> 提醒已开启';
                        tip.style.color = '#5cb85c';
                        tip.title = '点击关闭提醒（将停止新单/路线变更轮询）';
                    } else {
                        tip.innerHTML = '<i class="fa fa-volume-off"></i> 开启提醒';
                        tip.style.color = '';
                        tip.title = '点击后新单/待接单将 3 声蜂鸣 + 语音播报';
                    }
                }
                setTimeout(function () {
                    if (!(window.__orderRemind && window.__orderRemind.isLineOrDispatch)) return;
                    var toolbar = document.getElementById('toolbar');
                    if (toolbar && !document.getElementById('order-voice-unlock-tip')) {
                        var tip = document.createElement('a');
                        tip.id = 'order-voice-unlock-tip';
                        tip.href = 'javascript:;';
                        tip.className = 'btn btn-default btn-sm';
                        tip.onclick = function () {
                            if (!window.__orderRemind) return;
                            var on = window.__orderRemind.isOn();
                            if (on) {
                                window.__orderRemind.disable();
                                earlyVoiceUnlocked = false;
                                if (window.__orderRemindStop) window.__orderRemindStop();
                            } else {
                                window.__orderRemind.enable();
                                earlyVoiceUnlocked = true;
                                if (window.__orderRemindStart) window.__orderRemindStart();
                                beep(1);
                                setTimeout(function () { speak('语音提醒已开启'); }, 100);
                            }
                            refreshEarlyRemindTip();
                        };
                        toolbar.appendChild(tip);
                        refreshEarlyRemindTip();
                    }
                }, 800);
                function routeCheck() {
                    $.ajax({ url: url('order/route_change_alerts'), type: 'GET', dataType: 'json', xhrFields: { withCredentials: true }, headers: { 'X-Requested-With': 'XMLHttpRequest' } }).fail(function () {});
                }
                function voiceCheck() {
                    $.ajax({
                        url: url('order/new_order_voice_alert'),
                        type: 'GET',
                        data: { last_id: earlyLastId },
                        dataType: 'json',
                        xhrFields: { withCredentials: true },
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    }).done(function (ret) {
                        if (!ret || ret.code !== 1 || !ret.data) return;
                        var d = ret.data;
                        var hasNew = d.has_new == 1 || d.has_new === true;
                        var msg = (d.message && d.message.trim()) ? d.message.trim() : '';
                        if (hasNew && msg && !document.hidden && earlyVoiceUnlocked) {
                            beep(3);
                            setTimeout(function () { speak(msg); }, 400);
                            var t = document.getElementById('table');
                            if (t && $(t).data('bootstrap.table')) $(t).bootstrapTable('refresh');
                        }
                        if (d.latest_id > 0) earlyLastId = d.latest_id;
                    }).fail(function () {});
                }
                // 提醒开关：注册启动/停止实现，仅在开启时才轮询路线变更与新单语音
                var _earlyRouteTimer = null;
                var _earlyVoiceTimer = null;
                var _earlyRouteStart = function () {
                    if (_earlyRouteTimer) return;
                    if (window.__orderRemind && window.__orderRemind._started && window.__orderRemind._started.route) return;
                    if (window.__orderRemind && window.__orderRemind._started) window.__orderRemind._started.route = true;
                    _earlyRouteTimer = setInterval(routeCheck, 4000);
                    routeCheck();
                };
                var _earlyRouteStop = function () {
                    if (_earlyRouteTimer) { clearInterval(_earlyRouteTimer); _earlyRouteTimer = null; }
                };
                var _earlyVoiceStart = function () {
                    if (_earlyVoiceTimer) return;
                    if (window.__orderRemind && window.__orderRemind._started && window.__orderRemind._started.voice) return;
                    if (window.__orderRemind && window.__orderRemind._started) window.__orderRemind._started.voice = true;
                    _earlyVoiceTimer = setInterval(voiceCheck, 5000);
                    voiceCheck();
                };
                var _earlyVoiceStop = function () {
                    if (_earlyVoiceTimer) { clearInterval(_earlyVoiceTimer); _earlyVoiceTimer = null; }
                };
                if (window.__orderRemind && typeof window.__orderRemind.register === 'function') {
                    window.__orderRemind.register(_earlyRouteStart, _earlyRouteStop);
                    window.__orderRemind.register(_earlyVoiceStart, _earlyVoiceStop);
                }
                if (window.__orderRemind && window.__orderRemind.isLineOrDispatch && window.__orderRemind.isOn()) {
                    _earlyRouteStart();
                    _earlyVoiceStart();
                }
            })();
            // 初始化表格参数配置（showExport 关闭表格自带导出，只保留工具栏「导出」按 ids 请求后端）
            Table.api.init({
                extend: {
                    index_url: 'order/index' + location.search,
                    add_url: 'order/add',
                    edit_url: 'order/edit',
                    del_url: 'order/del',
                    multi_url: 'order/multi',
                    import_url: 'order/import',
                    export_url: 'order/export', 
                    table: 'order',
                    showExport: false,
                }
            });
            var table = $("#table");
            // 判断当前登录用户是否是总后台（group_id == 1）
            var isCurrentUserSuperAdmin = function() {
                var currentGroupId = (typeof Config !== 'undefined' && Config.admin && Config.admin.group_id) ? parseInt(Config.admin.group_id) : 0;
                return currentGroupId === 1;
            };
            // 判断当前登录用户是否是子后台（group_id != 1）
            var isCurrentUserRegularAdmin = function() {
                return !isCurrentUserSuperAdmin();
            };
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                showExport: false,
                fixedColumns: true,
                fixedRightNumber: 1,
                responseHandler: function(res) {
                    // 处理统计信息
                    if (res.statistics) {
                        Controller.updateStatistics(res.statistics);
                    }
                    return res;
                },
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'orderid', title: __('订单编号'), operate: 'LIKE'},
                        {field: 'find_car_type', title: __('找车类型'), operate: 'LIKE'},
                        {field: 'username', title: __('下单人'), operate: 'LIKE'},
                        {field: 'franchise_detail', title: '所属加盟商', operate: 'LIKE', formatter: function (v) { return v || '总部'; }},
                        {field: 'grab_line', title: '抢单线路', operate: false, visible: (function() {
                                var gid = (typeof Config !== 'undefined' && Config.admin && Config.admin.group_id) ? parseInt(Config.admin.group_id) : 0;
                                return gid === 1 || gid === 30;
                            })(), formatter: function (v) { return v ? v : '-'; }},
                        {field: 'grab_dispatch', title: '抢单调度', operate: false, visible: (function() {
                                var gid = (typeof Config !== 'undefined' && Config.admin && Config.admin.group_id) ? parseInt(Config.admin.group_id) : 0;
                                return gid === 1 || gid === 30;
                            })(), formatter: function (v) { return v ? v : '-'; }},
                        {field: 'address_contact', title: __('Address_contact'), operate: 'LIKE'},
                        {field: 'order_address', title: __('地址'), operate: 'LIKE'},
                        {
                            field: 'information_deposit',
                            title: __('信息费+不可退定金'),
                            operate: 'LIKE',
                            formatter: function(val, row) {
                                var a = parseFloat(row.information) || 0;
                                var b = parseFloat(row.deposit) || 0;
                                return a + b;
                            }
                        },
                        {field: 'information_image', title: __('付款截图'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'pay_type', title: __('支付方式'),searchList: {"1":__('到付'),"2":__('月结'),"0":__('寄付')}, formatter: Table.api.formatter.status},
                        {field: 'createtime', title: __('下单时间'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'pay_status', title: __('支付状态'), searchList: {"1":__('Pay_status 1'),"2":__('Pay_status 2'),"3":__('Pay_status 3'),"4":__('Pay_status 4'),"5":__('Pay_status 5'),"8":__('Pay_status 8')}, formatter: Table.api.formatter.status},
                        {field: 'payment_method', title: __('付款方式'), operate: 'LIKE'},
                        {field: 'profit', title: '利润', visible: (function() {
                                var gid = (typeof Config !== 'undefined' && Config.admin && Config.admin.group_id) ? parseInt(Config.admin.group_id) : 0;
                                return gid === 1 || gid === 30;
                            })(), operate: 'LIKE', formatter: function(value, row, index) {
                                var v = parseFloat(value);
                                if (isNaN(v)) return '-';
                                var cls = v >= 0 ? 'text-success' : 'text-danger';
                                return '<span class="' + cls + '">¥' + v.toFixed(2) + '</span>';
                            }},
                        {field: 'is_pay_salary', title: __('是否已发工资'), searchList: {"0":__('未发工资'),"1":__('已发工资')}, formatter: Table.api.formatter.status},
                        {field: 'timeout', title: __('抢单时效'), searchList: {"超时": __('超时'), "未超时": __('未超时')}, formatter: Table.api.formatter.normal},
                        {field: 'is_urgent', title: '送货', searchList: {"0":'否',"1":'是'}, formatter: function (value, row, index) {
                            if (value == 1) {
                                return '<span class="badge badge-danger">已送货</span>';
                            }
                            return '<span class="badge badge-secondary">否</span>';
                        }},
                        {field: 'logistics_status', title: __('物流状态'), searchList: {"1":__('Logistics_status 1'),"2":__('Logistics_status 2'),"3":__('Logistics_status 3'),"4":__('Logistics_status 4'),"5":__('Logistics_status 5'),"6":__('Logistics_status 6'),"7":__('Logistics_status 7')}, formatter: Table.api.formatter.status},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'print_form',
                                    text: '托运单模板',
                                    title: '托运单模板（针式底图）',
                                    classname: 'btn btn-xs btn-warning btn-dialog',
                                    icon: 'fa fa-file-image-o',
                                    url: 'order/print',
                                    dropdown: '打印'
                                },
                                {
                                    name: 'print_receipt',
                                    text: '签收单模板',
                                    title: '签收单模板（与前端签收单一致）',
                                    classname: 'btn btn-xs btn-warning btn-dialog',
                                    icon: 'fa fa-print',
                                    url: 'order/print?template=receipt',
                                    dropdown: '打印'
                                },
                                {
                                    name: 'ajax',
                                    text: __('标记已发工资'),
                                    title: __('标记已发工资'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: 'fa fa-money',
                                    url: 'order/set_pay_salary?status=1',
                                    hidden: function (row) {
                                        var currentGroupId = (typeof Config !== 'undefined' && Config.admin && Config.admin.group_id) ? parseInt(Config.admin.group_id) : 0;
                                        // 仅财务（group_id=30）和总后台（group_id=1）可见，且当前为未发工资状态
                                        return !((currentGroupId === 1 || currentGroupId === 30) && parseInt(row.is_pay_salary || 0) === 0);
                                    },
                                    success: function (data, ret) {
                                        var msg = (ret && ret.msg) ? ret.msg : '已标记为已发工资';
                                        Layer.alert(msg, {
                                            icon: 1,
                                            title: '工资发放',
                                            yes: function(index) {
                                                Layer.close(index);
                                                table.bootstrapTable('refresh');
                                            }
                                        });
                                        return false;
                                    },
                                    error: function (data, ret) {
                                        var msg = (ret && ret.msg) ? ret.msg : '操作失败，请重试';
                                        Layer.alert(msg, {
                                            icon: 2,
                                            title: '工资发放失败'
                                        });
                                        return false;
                                    }
                                },
                                {
                                    name: 'ajax',
                                    text: __('标记未发工资'),
                                    title: __('标记未发工资'),
                                    classname: 'btn btn-xs btn-warning btn-magic btn-ajax',
                                    icon: 'fa fa-money',
                                    url: 'order/set_pay_salary?status=0',
                                    hidden: function (row) {
                                        var currentGroupId = (typeof Config !== 'undefined' && Config.admin && Config.admin.group_id) ? parseInt(Config.admin.group_id) : 0;
                                        // 仅财务（group_id=30）和总后台（group_id=1）可见，且当前为已发工资状态
                                        return !((currentGroupId === 1 || currentGroupId === 30) && parseInt(row.is_pay_salary || 0) === 1);
                                    },
                                    success: function (data, ret) {
                                        var msg = (ret && ret.msg) ? ret.msg : '已标记为未发工资';
                                        Layer.alert(msg, {
                                            icon: 1,
                                            title: '工资发放',
                                            yes: function(index) {
                                                Layer.close(index);
                                                table.bootstrapTable('refresh');
                                            }
                                        });
                                        return false;
                                    },
                                    error: function (data, ret) {
                                        var msg = (ret && ret.msg) ? ret.msg : '操作失败，请重试';
                                        Layer.alert(msg, {
                                            icon: 2,
                                            title: '工资发放失败'
                                        });
                                        return false;
                                    }
                                },
                                {
                                    name: 'urgent',
                                    text: '送货',
                                    title: '标记为送货',
                                    classname: 'btn btn-xs btn-danger btn-click',
                                    icon: 'fa fa-bolt',
                                    click: function (options, row) {
                                        if (row.is_urgent == 1) {
                                            Layer.msg('该订单已送货');
                                            return;
                                        }
                                        var isMonthly = String(row.pay_type) === '2';
                                        var confirmText = isMonthly
                                            ? '确认标记为月结送货吗？送货完成后将从备用金扣除全部订单运费。'
                                            : '确认标记为送货吗？非月结送货完成时不扣除干线费和送货费。';
                                        Layer.confirm(confirmText, function (index) {
                                            Backend.api.ajax({
                                                url: 'order/urgent',
                                                data: {ids: row.id}
                                            }, function (data, ret) {
                                                Layer.alert(ret.msg || '操作成功');
                                                table.bootstrapTable('refresh');
                                            }, function (data, ret) {
                                                Layer.alert(ret.msg || '操作失败');
                                            });
                                            Layer.close(index);
                                        });
                                    },
                                    hidden: function (row) {
                                        // 已送货的不再显示按钮
                                        if (row.is_urgent == 1) {
                                            return true;
                                        }
                                        if (row.logistics_status == 7) {
                                            return true;
                                        }
                                        return false;
                                    }
                                },
                                {
                                    name: 'cancel_urgent',
                                    text: '取消送货',
                                    title: '取消送货',
                                    classname: 'btn btn-xs btn-default btn-click',
                                    icon: 'fa fa-times',
                                    click: function (options, row) {
                                        if (row.is_urgent != 1) {
                                            Layer.msg('该订单未送货');
                                            return;
                                        }
                                        Layer.confirm('确认取消该订单的送货标记吗？', function (index) {
                                            Backend.api.ajax({
                                                url: 'order/cancelUrgent',
                                                data: {ids: row.id}
                                            }, function (data, ret) {
                                                Layer.alert(ret.msg || '操作成功');
                                                table.bootstrapTable('refresh');
                                            }, function (data, ret) {
                                                Layer.alert(ret.msg || '操作失败');
                                            });
                                            Layer.close(index);
                                        });
                                    },
                                    hidden: function (row) {
                                        // 只有已送货订单显示“取消送货”
                                        return row.is_urgent != 1;
                                    }
                                },
                                {
                                    name: 'detail',
                                    text: __('查看订单信息'),
                                    title: __('查看订单信息'),
                                    classname: 'btn btn-xs btn-info btn-dialog',
                                    icon: 'fa fa-eye',
                                    url: 'order/view'
                                },
                                {
                                    name: 'ajax',
                                    text: __('抢单'),
                                    title: __('抢单'),
                                    classname: 'btn btn-xs btn-primary btn-magic btn-ajax',
                                    icon: 'fa fa-angellist', 
                                    // confirm: '确认发送Ajax请求？',
                                    url: 'order/order_grabbing',
                                    hidden:function(row){
                                        // 总后台和财务都隐藏抢单按钮（财务 group_id=30）
                                        var currentGroupId = (typeof Config !== 'undefined' && Config.admin && Config.admin.group_id) ? parseInt(Config.admin.group_id) : 0;
                                        return currentGroupId === 1 || currentGroupId === 30;
                                    },
                                    success: function (data, ret) {
                                        console.log('抢单成功回调:', data, ret);
                                        var msg = (ret && ret.msg) ? ret.msg : '抢单成功';
                                        Layer.alert(msg, {
                                            icon: 1,
                                            title: '抢单结果',
                                            yes: function(index) {
                                                // 点击确认后刷新表格
                                                Layer.close(index);
                                                table.bootstrapTable('refresh');
                                            }
                                         });
                                        //如果需要阻止成功提示，则必须使用return false;
                                        return false;
                                    },
                                    error: function (data, ret) {
                                        console.log('抢单失败回调:', data, ret);
                                        var msg = (ret && ret.msg) ? ret.msg : '抢单失败，请重试';
                                        Layer.alert(msg, {
                                            icon: 2,
                                            title: '抢单失败'
                                        });
                                        return false;
                                    }
                                },                                  {
                                    name: 'ajax',
                                    text: __('取消订单'),
                                    title: __('取消订单'),
                                    classname: 'btn btn-xs btn-primary btn-magic btn-ajax',
                                    icon: 'fa fa-angellist',
                                    // confirm: '确认发送Ajax请求？',
                                    url: 'order/cancel_order',
                                    hidden:function(row){
                                        // 子后台（当前登录用户）隐藏取消订单按钮
                                         return isCurrentUserRegularAdmin();
                                    },
                                    success: function (data, ret) {
                                        var msg = (ret && ret.msg) ? ret.msg : '取消成功';
                                        Layer.alert(msg, {
                                            icon: 1,
                                            title: '取消订单',
                                             yes: function(index) {
                                                // 点击确认后刷新表格
                                                Layer.close(index);
                                                table.bootstrapTable('refresh');
                                            }
                                        });
                                        //如果需要阻止成功提示，则必须使用return false;
                                        return false;
                                    },
                                    error: function (data, ret) {
                                        console.log('取消订单回调:', data, ret);
                                        var msg = (ret && ret.msg) ? ret.msg : '取消失败，请重试';
                                        Layer.alert(msg, {
                                            icon: 2,
                                            title: '取消失败'
                                        });
                                        return false;
                                    }
                                },
                                {
                                    name: 'ajax',
                                    text: __('退款'),
                                    title: __('退款'),
                                    classname: 'btn btn-xs btn-danger btn-magic btn-ajax',
                                    icon: 'fa fa-undo',
                                    // 总后台操作不需要二次确认（避免被“审核/确认”拦住）
                                    confirm: function () {
                                        return isCurrentUserSuperAdmin() ? false : '确认退款？';
                                    },
                                    url: 'order/refund',
                                    success: function (data, ret) {
                                        var msg = (ret && ret.msg) ? ret.msg : '退款成功';
                                        Layer.alert(msg, {
                                            icon: 1,
                                            title: '退款结果',
                                            yes: function(index) {
                                                // 点击确认后刷新表格
                                                Layer.close(index);
                                                table.bootstrapTable('refresh');
                                            }
                                        });
                                        //如果需要阻止成功提示，则必须使用return false;
                                        return false;
                                    },
                                    error: function (data, ret) {
                                        console.log('退款回调:', data, ret);
                                        var msg = (ret && ret.msg) ? ret.msg : '退款失败，请重试';
                                        Layer.alert(msg, {
                                            icon: 2,
                                            title: '退款失败'
                                        });
                                        return false;
                                    }
                                },
                                {
                                    name: 'detail',
                                    text: __('查看物流'),
                                    title: __('查看物流'),
                                    classname: 'btn btn-xs btn-info btn-dialog',
                                    icon: 'fa fa-truck',
                                    url: 'order/logistics_detail',
                                    hidden: function (row) {
                                        // 仅总后台和财务能看到（财务 group_id=30）
                                        var currentGroupId = (typeof Config !== 'undefined' && Config.admin && Config.admin.group_id) ? parseInt(Config.admin.group_id) : 0;
                                        return currentGroupId !== 1 && currentGroupId !== 30;
                                    }
                                },
                                {
                                    name: 'fill_return_tracking',
                                    text: '填写寄回单号',
                                    title: '填写寄回单号',
                                    classname: 'btn btn-xs btn-info btn-click',
                                    icon: 'fa fa-reply',
                                    hidden: function (row) {
                                        // 仅回单方式 id 为 2 时显示（列表里 receipt_type_id 被后端改成名称，用 receipt_type_id_value 判断）
                                        return parseInt(row.receipt_type_id_value, 10) !== 2;
                                    },
                                    click: function (options, row) {
                                        var esc = function (s) { return (s || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;'); };
                                        var nameVal = esc(row.return_express_name);
                                        var noVal = esc(row.return_express_no);
                                        Layer.open({
                                            type: 1,
                                            title: '填写寄回单号',
                                            area: ['420px', '260px'],
                                            content: '<div class="panel-body" style="padding:20px;">' +
                                                '<div class="form-group">' +
                                                '<label>快递名称</label>' +
                                                '<input type="text" class="form-control" id="return_express_name" placeholder="请输入快递名称" value="' + nameVal + '">' +
                                                '</div>' +
                                                '<div class="form-group">' +
                                                '<label>单号</label>' +
                                                '<input type="text" class="form-control" id="return_express_no" placeholder="请输入单号" value="' + noVal + '">' +
                                                '</div>' +
                                                '</div>',
                                            btn: ['确定', '取消'],
                                            yes: function (index) {
                                                var expressName = $('#return_express_name').val().trim();
                                                var expressNo = $('#return_express_no').val().trim();
                                                if (!expressName) {
                                                    Layer.msg('请填写快递名称');
                                                    return;
                                                }
                                                if (!expressNo) {
                                                    Layer.msg('请填写单号');
                                                    return;
                                                }
                                                Layer.close(index);
                                                Backend.api.ajax({
                                                    url: 'order/save_return_tracking',
                                                    data: { ids: row.id, return_express_name: expressName, return_express_no: expressNo }
                                                }, function (data, ret) {
                                                    Layer.msg(ret.msg || '保存成功');
                                                    table.bootstrapTable('refresh');
                                                }, function (data, ret) {
                                                    Layer.msg((ret && ret.msg) ? ret.msg : '保存失败');
                                                });
                                            }
                                        });
                                    }
                                }
                            ]
                        }

                    ]
                ]
            });

            // 为表格绑定事件 
            Table.api.bindevent(table);

            // 移除表格自带的「导出」下拉（客户端导出，会导出全部/当前页），只保留工具栏「导出」按 ids 请求后端
            function removeTableExportDropdown() { 
                table.closest('.bootstrap-table').find('.export').remove();
            }
            setTimeout(removeTableExportDropdown, 100);
            table.on('post-body.bs.table', removeTableExportDropdown);

            // 根据是否勾选订单来启用/禁用「导出」按钮（只导出所选）
            function updateExportButtonState() {
                var ids = Table.api.selectedids(table);
                var $btn = $('#toolbar .btn-export');
                if (ids && ids.length > 0) {
                    $btn.removeClass('btn-disabled disabled').prop('disabled', false).attr('title', '导出已勾选的 ' + ids.length + ' 条订单');
                } else {
                    $btn.addClass('btn-disabled disabled').prop('disabled', true).attr('title', '请先勾选要导出的订单');
                }
            }
            table.on('check.bs.table check-all.bs.table uncheck.bs.table uncheck-all.bs.table load-success.bs.table', updateExportButtonState);
            setTimeout(updateExportButtonState, 200);

            // 录单按钮：打开录单页（新标签页），保存时会写入当前管理员为经办人
            $(document).on('click', '.btn-ludan', function () {
                Fast.api.open('order/ludan', '录单');
            });

            // 监听表格刷新事件，更新统计信息
            table.on('refresh.bs.table', function() {
                // 刷新时会自动触发 responseHandler，统计信息会自动更新
            });

            // 导出：仅导出勾选的订单，用表单 POST 提交 ids，确保后端只收到所选 id
            $(document).on('click', '#toolbar .btn-export', function (e) {
                e.preventDefault();
                e.stopPropagation();
                if ($(this).hasClass('disabled') || $(this).prop('disabled')) {
                    return false;
                }
                var exportUrl = $.fn.bootstrapTable.defaults.extend.export_url;
                if (!exportUrl) {
                    return false;
                }
                var selectedIds = Table.api.selectedids(table);
                if (!selectedIds || selectedIds.length === 0) {
                    Layer.msg('请先勾选要导出的订单');
                    return false;
                }
                var actionUrl = Fast.api.fixurl(exportUrl);
                if (actionUrl.indexOf('http') !== 0) {
                    actionUrl = (window.location.origin || '') + actionUrl;
                }
                var idsStr = selectedIds.join(',');
                var $form = $('<form method="post" action="' + actionUrl + '" target="_blank" style="display:none;"></form>');
                $form.append($('<input type="hidden" name="ids">').val(idsStr));
                $('body').append($form);
                Layer.msg('正在导出已勾选的 ' + selectedIds.length + ' 条订单…', { icon: 16, time: 1500 });
                $form[0].submit();
                setTimeout(function () { $form.remove(); }, 1000);
                return false;
            });

            Controller.bindStatsAmountToggle();
        },
        bindStatsAmountToggle: function () {
            if (Controller._statsAmountToggleBound) {
                return;
            }
            Controller._statsAmountToggleBound = true;
            $(document).on('click', '#order-statistics .stats-amount-toggle', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var $wrap = $(this).closest('.stats-amount-wrap');
                var key = $wrap.data('key') || 'amount';
                var hidden = !$wrap.hasClass('amount-hidden');
                Controller.setStatsAmountVisible($wrap, !hidden);
                try {
                    localStorage.setItem('order_stats_hide_' + key, hidden ? '1' : '0');
                } catch (err) {}
            });
        },
        setStatsAmountVisible: function ($wrap, visible) {
            var amount = $wrap.data('amount');
            var $text = $wrap.find('.stats-amount-text');
            var $toggle = $wrap.find('.stats-amount-toggle');
            var $icon = $toggle.find('i');
            if (visible) {
                $wrap.removeClass('amount-hidden');
                $text.text('¥' + amount);
                $icon.removeClass('fa-eye-slash').addClass('fa-eye');
                $toggle.attr('title', '隐藏金额');
            } else {
                $wrap.addClass('amount-hidden');
                $text.text('¥****');
                $icon.removeClass('fa-eye').addClass('fa-eye-slash');
                $toggle.attr('title', '显示金额');
            }
        },
        buildStatsAmountHtml: function (amount, extraClass, key) {
            extraClass = extraClass || '';
            var hidden = false;
            try {
                hidden = localStorage.getItem('order_stats_hide_' + key) === '1';
            } catch (e) {}
            var text = hidden ? '¥****' : ('¥' + amount);
            var iconClass = hidden ? 'fa-eye-slash' : 'fa-eye';
            var title = hidden ? '显示金额' : '隐藏金额';
            return '<div class="statistics-value stats-amount-wrap' + (hidden ? ' amount-hidden' : '') + (extraClass ? ' ' + extraClass : '') + '" data-amount="' + amount + '" data-key="' + key + '">' +
                '<span class="stats-amount-text">' + text + '</span>' +
                '<a href="javascript:;" class="stats-amount-toggle" title="' + title + '"><i class="fa ' + iconClass + '"></i></a>' +
                '</div>';
        },
        // 更新统计信息显示
        updateStatistics: function(statistics) {
            var $statsContainer = $('#order-statistics');
            if ($statsContainer.length === 0) {
                // 如果统计容器不存在，创建它
                $statsContainer = $('<div id="order-statistics" class="order-statistics-panel"></div>');
                $('.toolbar').after($statsContainer);
            }
            
            // 仅允许总后台（group_id=1）或财务（group_id=30）查看统计
            var currentGroupId = (typeof Config !== 'undefined' && Config.admin && Config.admin.group_id) ? parseInt(Config.admin.group_id) : 0;
            console.log('order statistics currentGroupId =>', currentGroupId);
            if (!(currentGroupId === 1 || currentGroupId === 30)) {
                $statsContainer.hide();
                return;
            }
            var totalIncome = parseFloat(statistics.total_income || 0).toFixed(2);
            var totalExpense = parseFloat(statistics.total_expense || 0).toFixed(2);
            var totalProfit = parseFloat(statistics.total_profit || 0).toFixed(2);
            var totalWeight = parseFloat(statistics.total_weight || 0).toFixed(2);
            var totalVolume = parseFloat(statistics.total_volume || 0).toFixed(2);
            var totalOrders = parseInt(statistics.total_orders || 0, 10);

            var html = ''
                // 第一行：订单总运费 / 订单总成本 / 订单总利润
                + '<div class="row">' +
                '<div class="col-md-4">' +
                '<div class="statistics-item income">' +
                '<div class="statistics-label">订单总运费</div>' +
                Controller.buildStatsAmountHtml(totalIncome, '', 'income') +
                '</div>' +
                '</div>' +
                '<div class="col-md-4">' + 
                '<div class="statistics-item expense">' +
                '<div class="statistics-label">订单总成本</div>' +
                Controller.buildStatsAmountHtml(totalExpense, '', 'expense') +
                '</div>' +
                '</div>' +
                '<div class="col-md-4">' +
                '<div class="statistics-item profit">' +
                '<div class="statistics-label">订单总利润</div>' +
                Controller.buildStatsAmountHtml(totalProfit, (parseFloat(totalProfit) >= 0 ? 'text-success' : 'text-danger'), 'profit') +
                '</div>' +
                '</div>' +
                '</div>' +
                // 第二行：总吨数 / 总方数 / 总单量
                '<div class="row" style="margin-top:15px;">' +
                '<div class="col-md-4">' +
                '<div class="statistics-item">' +
                '<div class="statistics-label">总吨数</div>' +
                '<div class="statistics-value">' + totalWeight + ' 吨</div>' +
                '</div>' +
                '</div>' +
                '<div class="col-md-4">' +
                '<div class="statistics-item">' +
                '<div class="statistics-label">总方数</div>' +
                '<div class="statistics-value">' + totalVolume + ' 方</div>' +
                '</div>' +
                '</div>' +
                '<div class="col-md-4">' +
                '<div class="statistics-item">' +
                '<div class="statistics-label">总单量</div>' +
                '<div class="statistics-value">' + totalOrders + ' 单</div>' +
                '</div>' +
                '</div>' +
                '</div>';
            
            $statsContainer.html(html).show();
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        // 查看某个用户的下单情况列表
        user_orders: function () {
            var table = $("#table");
            var userId = table.data("user-id");
            if (!userId) {
                Layer.msg('缺少用户ID');
                return;
            }

            var indexUrl = 'order/user_orders?ids=' + userId;
            Table.api.init({
                extend: {
                    index_url: indexUrl,
                    table: 'order'
                }
            });

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
                        {field: 'orderid', title: __('订单编号')},
                        {field: 'find_car_type', title: __('找车类型')},
                        {field: 'pay_type', title: __('支付方式'), searchList: {"1": __('Pay_type 1'), "2": __('Pay_type 2')}, formatter: Table.api.formatter.status},
                        {field: 'username', title: __('下单人')},
                        {field: 'createtime', title: __('下单时间'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime},
                        {field: 'pay_status', title: __('支付状态'), searchList: {"1": __('待付款'), "2": __('进行中'), "3": __('已完成'), "4": __('已取消'), "5": __('未下单')}, formatter: Table.api.formatter.status},
                        {
                            field: 'information_deposit',
                            title: __('信息费+不可退定金'),
                            formatter: function(val, row) {
                                var a = parseFloat(row.information) || 0;
                                var b = parseFloat(row.deposit) || 0;
                                return a + b;
                            }
                        },
                        {field: 'information_image', title: __('付款截图'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        // {field: 'logistics_status', title: __('物流状态'), formatter: Table.api.formatter.status},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'detail',
                                    text: __('查看订单信息'),
                                    title: __('查看订单信息'),
                                    classname: 'btn btn-xs btn-info btn-dialog',
                                    icon: 'fa fa-eye',
                                    url: 'order/view'
                                }
                            ]
                        }
                    ]
                ]
            });

            // 路线变更提醒 + 新单语音提醒：在 bindevent 前启动轮询，避免 bindevent 异常导致轮询不跑
            (function () {
                if (typeof console !== 'undefined' && console.log) {
                    console.log('[路线变更提醒] 轮询已启动(总后台订单页)');
                }
                var POLL_INTERVAL = 4000;
                var LayerObj = (typeof Layer !== 'undefined' && Layer) ? Layer : (window.parent && window.parent.Layer) ? window.parent.Layer : null;
                function getUrl(path) {
                    if (typeof Backend !== 'undefined' && Backend.api && Backend.api.fixurl) {
                        return Backend.api.fixurl(path);
                    }
                    var base = location.pathname.replace(/\/index.*$/, '').replace(/\/$/, '');
                    return (base || '/admin/order') + '/' + path.split('/').pop();
                }
                function toArray(list) {
                    if (!list) return [];
                    if (Array.isArray(list)) return list;
                    return Object.keys(list).map(function (k) { return list[k]; });
                }
                function check() {
                    $.ajax({
                        url: getUrl('order/route_change_alerts'),
                        type: 'GET',
                        dataType: 'json',
                        xhrFields: { withCredentials: true },
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    }).done(function (ret) {
                        if (!ret || ret.code !== 1) return;
                        var data = ret.data;
                        var list = data && data.list ? toArray(data.list) : [];
                        if (list.length === 0) return;
                        var ids = list.map(function (a) { return a.id; });
                        var orderids = list.map(function (a) { return a.orderid || ''; }).filter(Boolean);
                        var msg = '您有订单路线被修改，请及时查看。' + (orderids.length ? '\n订单号：' + orderids.join('、') : '');
                        if (LayerObj && LayerObj.alert) {
                            LayerObj.alert(msg, { icon: 0, title: '路线变更提醒', yes: function () {
                                if (LayerObj.closeAll) LayerObj.closeAll();
                                if (typeof table !== 'undefined' && table.bootstrapTable) table.bootstrapTable('refresh');
                            } });
                        } else {
                            alert(msg);
                        }
                        var u = new SpeechSynthesisUtterance('您有订单路线被修改，请及时查看。');
                        u.lang = 'zh-CN';
                        u.rate = 0.9;
                        if (window.speechSynthesis) window.speechSynthesis.speak(u);
                        $.ajax({
                            url: getUrl('order/mark_route_change_read'),
                            type: 'POST',
                            data: { ids: ids.join(',') },
                            dataType: 'json',
                            xhrFields: { withCredentials: true },
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        });
                    });
                }
                // 提醒开关：路线变更轮询（与早期段去重，避免双请求）
                var _midRouteTimer = null;
                var _midRouteStart = function () {
                    if (_midRouteTimer || (window.__orderRemind && window.__orderRemind._started && window.__orderRemind._started.route)) return;
                    _midRouteTimer = setInterval(check, POLL_INTERVAL);
                    check();
                };
                var _midRouteStop = function () {
                    if (_midRouteTimer) { clearInterval(_midRouteTimer); _midRouteTimer = null; }
                };
                if (window.__orderRemind && typeof window.__orderRemind.register === 'function') {
                    window.__orderRemind.register(_midRouteStart, _midRouteStop);
                }
                if (window.__orderRemind && window.__orderRemind.isLineOrDispatch && window.__orderRemind.isOn()) {
                    _midRouteStart();
                }
            })();

            // 线路/调度新单语音提醒：蜂鸣 + TTS，点击启用后生效（轮询始终运行，后端按身份返回 has_new）
            (function () {
                var cfg = (typeof Config !== 'undefined' && Config.admin) ? Config : (window.parent && window.parent.Config && window.parent.Config.admin ? window.parent.Config : null);
                var identity = (cfg && cfg.admin && cfg.admin.identity !== undefined) ? parseInt(cfg.admin.identity, 10) : 0;
                var voiceIdentity = (cfg && cfg.admin && cfg.admin.voice_identity !== undefined)
                    ? parseInt(cfg.admin.voice_identity, 10)
                    : identity;
                var isLineOrDispatch = (voiceIdentity === 2 || voiceIdentity === 3);
                var VOICE_POLL_INTERVAL = 5000;
                var lastVoiceId = 0;
                // 与全局提醒开关状态同步：若该账号/该电脑已开启，则默认已解锁
                var voiceUnlocked = (window.__orderRemind && window.__orderRemind.isOn()) ? true : false;
                var audioCtx = null;
                function getUrl(path) {
                    if (typeof Backend !== 'undefined' && Backend.api && Backend.api.fixurl) {
                        return Backend.api.fixurl(path);
                    }
                    var base = location.pathname.replace(/\/index.*$/, '').replace(/\/$/, '');
                    return (base || '/admin/order') + '/' + path.split('/').pop();
                }
                // 蜂鸣提示（不依赖 TTS，多数浏览器可用）
                function doBeep(times) {
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
                                        osc.frequency.value = 880;
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
                function doSpeak(text) {
                    if (!text) return;
                    try {
                        if (window.speechSynthesis) {
                            window.speechSynthesis.cancel();
                            var u = new SpeechSynthesisUtterance(text);
                            u.lang = 'zh-CN';
                            u.rate = 0.9;
                            var voices = window.speechSynthesis.getVoices();
                            for (var i = 0; i < voices.length; i++) {
                                if (voices[i].lang === 'zh-CN' || String(voices[i].lang).indexOf('zh') === 0) {
                                    u.voice = voices[i];
                                    break;
                                }
                            }
                            window.speechSynthesis.speak(u);
                        }
                    } catch (e) {}
                }
                function unlockVoice() {
                    if (voiceUnlocked) return;
                    voiceUnlocked = true;
                    if (window.__orderVoiceUnlock) window.__orderVoiceUnlock();
                    doBeep(1);
                    setTimeout(function () { doSpeak('语音提醒已开启'); }, 100);
                    var tip = document.getElementById('order-voice-unlock-tip');
                    if (tip) {
                        tip.innerHTML = '<i class="fa fa-volume-up"></i> 语音已开启';
                        tip.style.color = '#5cb85c';
                        tip.onclick = null;
                    }
                }
                setTimeout(function () {
                    if (!isLineOrDispatch) return;
                    var toolbar = document.getElementById('toolbar');
                    if (toolbar && !document.getElementById('order-voice-unlock-tip')) {
                        var tip = document.createElement('a');
                        tip.id = 'order-voice-unlock-tip';
                        tip.href = 'javascript:;';
                        tip.className = 'btn btn-default btn-sm';
                        tip.innerHTML = '<i class="fa fa-volume-off"></i> 点击启用语音提醒';
                        tip.title = '点击后新单/待接单将蜂鸣并语音播报';
                        tip.onclick = function () { unlockVoice(); };
                        toolbar.appendChild(tip);
                    }
                }, 500);
                function checkNewOrder() {
                    $.ajax({
                        url: getUrl('order/new_order_voice_alert'),
                        type: 'GET',
                        data: { last_id: lastVoiceId },
                        dataType: 'json',
                        xhrFields: { withCredentials: true },
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    }).done(function (ret) {
                        if (!ret || ret.code !== 1 || !ret.data) return;
                        var d = ret.data;
                        var hasNew = d.has_new == 1 || d.has_new === true;
                        var msg = (d.message && d.message.trim()) ? d.message.trim() : '';
                        if (hasNew && msg) {
                            if (voiceUnlocked && !document.hidden) {
                                doBeep(3);
                                setTimeout(function () { doSpeak(msg); }, 400);
                            }
                            if (typeof table !== 'undefined' && table.bootstrapTable) table.bootstrapTable('refresh');
                            if (!document.hidden && d.latest_id > 0) lastVoiceId = d.latest_id;
                        } else if (d.latest_id > 0) {
                            lastVoiceId = d.latest_id;
                        }
                    });
                }
                // 提醒开关：新单语音轮询（与早期段去重，避免双请求）
                var _lateVoiceTimer = null;
                var _lateVoiceStart = function () {
                    if (_lateVoiceTimer) return;
                    if (window.__orderRemind && window.__orderRemind._started && window.__orderRemind._started.voice) return;
                    if (window.__orderRemind && window.__orderRemind._started) window.__orderRemind._started.voice = true;
                    _lateVoiceTimer = setInterval(checkNewOrder, VOICE_POLL_INTERVAL);
                    checkNewOrder();
                };
                var _lateVoiceStop = function () {
                    if (_lateVoiceTimer) { clearInterval(_lateVoiceTimer); _lateVoiceTimer = null; }
                };
                if (window.__orderRemind && typeof window.__orderRemind.register === 'function') {
                    window.__orderRemind.register(_lateVoiceStart, _lateVoiceStop);
                }
                if (window.__orderRemind && window.__orderRemind.isLineOrDispatch && window.__orderRemind.isOn()) {
                    _lateVoiceStart();
                }
            })();
        },
        ludan: function () {
            Form.api.bindevent($("#ludan-form"));
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});

define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // ===== 提醒开关（每个账号/每台电脑独立记忆，默认关闭）=====
            (function () {
                var cfg = (typeof Config !== 'undefined' && Config.admin) ? Config.admin
                    : (window.parent && window.parent.Config && window.parent.Config.admin ? window.parent.Config.admin : null);
                var adminId = (cfg && cfg.id) ? parseInt(cfg.id, 10) : 0;
                var voiceIdentity = (cfg && (cfg.voice_identity !== undefined ? cfg.voice_identity : cfg.identity) !== undefined)
                    ? parseInt(cfg.voice_identity !== undefined ? cfg.voice_identity : cfg.identity, 10)
                    : 0;
                var isLineOrDispatch = (voiceIdentity === 2 || voiceIdentity === 3);
                var key = 'order_remind_' + adminId;
                function read() {
                    try { return window.localStorage.getItem(key) === '1'; } catch (e) { return false; }
                }
                function write(v) {
                    try {
                        if (v) { window.localStorage.setItem(key, '1'); } else { window.localStorage.removeItem(key); }
                    } catch (e) {}
                }
                // 是否允许开启（仅线路/调度账号）
                var control = {
                    adminId: adminId,
                    isLineOrDispatch: isLineOrDispatch,
                    isOn: read,
                    enable: function () { write(true); },
                    disable: function () { write(false); }
                };
                // 供各轮询模块注册启动/停止实现，方便开关统一控制
                control._starters = [];
                control._stoppers = [];
                control.register = function (start, stop) {
                    if (typeof start === 'function') control._starters.push(start);
                    if (typeof stop === 'function') control._stoppers.push(stop);
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
                console.log('[路线变更提醒] admin/order.js index() 已执行');
            }
            // 路线变更提醒：在表格初始化前就启动轮询，避免被表格/bindevent 阻塞
            (function () {
                var POLL_INTERVAL = 4000;
                if (typeof console !== 'undefined' && console.log) {
                    console.log('[路线变更提醒] 轮询已启动(子后台)，每 ' + (POLL_INTERVAL/1000) + ' 秒请求一次');
                }
                var LayerObj = (typeof Layer !== 'undefined' && Layer) ? Layer : (window.parent && window.parent.Layer) ? window.parent.Layer : null;
                function getRouteAlertsUrl() {
                    if (typeof Backend !== 'undefined' && Backend.api && Backend.api.fixurl) {
                        return Backend.api.fixurl('admin/order/route_change_alerts');
                    }
                    var p = location.pathname.replace(/\/index.*$/, '').replace(/\/$/, '');
                    return (p || '/admin/admin/order') + '/route_change_alerts';
                }
                function getMarkReadUrl() {
                    if (typeof Backend !== 'undefined' && Backend.api && Backend.api.fixurl) {
                        return Backend.api.fixurl('admin/order/mark_route_change_read');
                    }
                    var p = location.pathname.replace(/\/index.*$/, '').replace(/\/$/, '');
                    return (p || '/admin/admin/order') + '/mark_route_change_read';
                }
                function showRouteAlert(msg) {
                    if (LayerObj && LayerObj.alert) {
                        LayerObj.alert(msg, { icon: 0, title: '路线变更提醒', yes: function () {
                            if (LayerObj.closeAll) LayerObj.closeAll();
                            var t = document.getElementById('table');
                            if (t && $(t).data('bootstrap.table')) $(t).bootstrapTable('refresh');
                        } });
                    } else {
                        alert(msg);
                    }
                }
                function speakRouteAlert() {
                    var u = new SpeechSynthesisUtterance('您有订单路线被修改，请及时查看。');
                    u.lang = 'zh-CN';
                    u.rate = 0.9;
                    if (window.speechSynthesis) window.speechSynthesis.speak(u);
                }
                function toArray(list) {
                    if (!list) return [];
                    if (Array.isArray(list)) return list;
                    return Object.keys(list).map(function (k) { return list[k]; });
                }
                function checkRouteChangeAlerts() {
                    $.ajax({
                        url: getRouteAlertsUrl(),
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
                        showRouteAlert(msg);
                        speakRouteAlert();
                        $.ajax({
                            url: getMarkReadUrl(),
                            type: 'POST',
                            data: { ids: ids.join(',') },
                            dataType: 'json',
                            xhrFields: { withCredentials: true }
                        });
                    }).fail(function (xhr) {
                        if (xhr.status === 404 || xhr.status === 0) return;
                        console.warn('路线变更提醒轮询异常', xhr.status, xhr.responseText && xhr.responseText.substring(0, 200));
                    });
                }
                // 提醒开关：注册启动/停止实现，仅在开启时才轮询路线变更
                var _routeTimer = null;
                var _routeStart = function () {
                    if (_routeTimer) return;
                    _routeTimer = setInterval(checkRouteChangeAlerts, POLL_INTERVAL);
                    checkRouteChangeAlerts();
                };
                var _routeStop = function () {
                    if (_routeTimer) {
                        clearInterval(_routeTimer);
                        _routeTimer = null;
                    }
                };
                if (window.__orderRemind && typeof window.__orderRemind.register === 'function') {
                    window.__orderRemind.register(_routeStart, _routeStop);
                }
                if (window.__orderRemind && window.__orderRemind.isLineOrDispatch && window.__orderRemind.isOn()) {
                    _routeStart();
                }
            })();

            // 线路/调度新单语音提醒：蜂鸣 + TTS，轮询始终运行，后端按身份返回 has_new
            (function () {
                function getAdminCfg() {
                    if (typeof Config !== 'undefined' && Config.admin) {
                        return Config;
                    }
                    if (window.parent && window.parent.Config && window.parent.Config.admin) {
                        return window.parent.Config;
                    }
                    return null;
                }
                function resolveVoiceIdentity() {
                    var cfg = getAdminCfg();
                    var fromCfg = (cfg && cfg.admin && cfg.admin.identity !== undefined)
                        ? parseInt(cfg.admin.identity, 10)
                        : 0;
                    if (fromCfg === 2 || fromCfg === 3) {
                        return fromCfg;
                    }
                    var tableEl = document.getElementById('table');
                    if (tableEl && tableEl.getAttribute('data-group-identity')) {
                        return parseInt(tableEl.getAttribute('data-group-identity'), 10) || 0;
                    }
                    return fromCfg;
                }
                var voiceIdentity = resolveVoiceIdentity();
                var isLineOrDispatch = (voiceIdentity === 2 || voiceIdentity === 3);
                var VOICE_POLL_INTERVAL = 5000;
                var lastVoiceId = window.__adminOrderVoiceLastId || 0;
                // 与全局提醒开关状态同步：若该账号/该电脑已开启，则默认已解锁
                var voiceUnlocked = (window.__orderRemind && window.__orderRemind.isOn()) ? true : false;
                var voiceInitPending = !window.__adminOrderVoiceInitDone;
                var audioCtx = null;
                function getVoiceAlertUrl() {
                    if (typeof Backend !== 'undefined' && Backend.api && Backend.api.fixurl) {
                        return Backend.api.fixurl('admin/order/new_order_voice_alert');
                    }
                    var p = location.pathname.replace(/\/index.*$/, '').replace(/\/$/, '');
                    return (p || '/admin/admin/order') + '/new_order_voice_alert';
                }
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
                    doBeep(1);
                    setTimeout(function () { doSpeak('语音提醒已开启'); }, 100);
                    var tip = document.getElementById('admin-order-voice-unlock-tip');
                    if (tip) {
                        tip.innerHTML = '<i class="fa fa-volume-up"></i> 语音已开启';
                        tip.style.color = '#5cb85c';
                        tip.onclick = null;
                    }
                }
                // 提醒开关（每个账号/每台电脑独立记忆，默认关闭，点开才开始轮询）
                function refreshRemindTip() {
                    var tip = document.getElementById('admin-order-voice-unlock-tip');
                    if (!tip) return;
                    var on = window.__orderRemind && window.__orderRemind.isOn();
                    if (on) {
                        tip.innerHTML = '<i class="fa fa-volume-up"></i> 提醒已开启';
                        tip.style.color = '#5cb85c';
                        tip.title = '点击关闭提醒（将停止新单/路线变更轮询）';
                    } else {
                        tip.innerHTML = '<i class="fa fa-volume-off"></i> 开启提醒';
                        tip.style.color = '';
                        tip.title = '点击后新单/待接单将蜂鸣并语音播报（新单在公海，与下方已抢列表无关）';
                    }
                }
                setTimeout(function () {
                    if (!isLineOrDispatch) return;
                    var toolbar = document.getElementById('toolbar');
                    if (toolbar && !document.getElementById('admin-order-voice-unlock-tip')) {
                        var tip = document.createElement('a');
                        tip.id = 'admin-order-voice-unlock-tip';
                        tip.href = 'javascript:;';
                        tip.className = 'btn btn-default btn-sm';
                        tip.onclick = function () {
                            if (!window.__orderRemind) return;
                            var on = window.__orderRemind.isOn();
                            if (on) {
                                window.__orderRemind.disable();
                                if (window.__orderRemindStop) window.__orderRemindStop();
                            } else {
                                window.__orderRemind.enable();
                                if (window.__orderRemindStart) window.__orderRemindStart();
                                voiceUnlocked = true;
                                doBeep(1);
                                setTimeout(function () { doSpeak('语音提醒已开启'); }, 100);
                            }
                            refreshRemindTip();
                        };
                        toolbar.appendChild(tip);
                        refreshRemindTip();
                    }
                }, 300);
                function checkNewOrder() {
                    var isInit = voiceInitPending;
                    $.ajax({
                        url: getVoiceAlertUrl(),
                        type: 'GET',
                        data: { last_id: lastVoiceId, init: isInit ? 1 : 0 },
                        dataType: 'json',
                        xhrFields: { withCredentials: true },
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    }).done(function (ret) {
                        if (!ret || ret.code !== 1 || !ret.data) return;
                        var d = ret.data;
                        if (isInit) {
                            voiceInitPending = false;
                            window.__adminOrderVoiceInitDone = true;
                            if (d.latest_id > 0) {
                                lastVoiceId = d.latest_id;
                                window.__adminOrderVoiceLastId = lastVoiceId;
                            }
                            return;
                        }
                        var hasNew = d.has_new == 1 || d.has_new === true;
                        var msg = (d.message && d.message.trim()) ? d.message.trim() : '';
                        if (d.latest_id > 0) {
                            lastVoiceId = d.latest_id;
                            window.__adminOrderVoiceLastId = lastVoiceId;
                        }
                        if (hasNew && msg && voiceUnlocked && !document.hidden) {
                            doBeep(3);
                            setTimeout(function () { doSpeak(msg); }, 400);
                            var t = document.getElementById('table');
                            if (t && $(t).data('bootstrap.table')) $(t).bootstrapTable('refresh');
                        }
                    });
                }
                // 提醒开关：注册启动/停止实现，仅在开启时才轮询新单语音
                var _voiceTimer = null;
                var _voiceStart = function () {
                    if (_voiceTimer) return;
                    _voiceTimer = setInterval(checkNewOrder, VOICE_POLL_INTERVAL);
                    checkNewOrder();
                };
                var _voiceStop = function () {
                    if (_voiceTimer) {
                        clearInterval(_voiceTimer);
                        _voiceTimer = null;
                    }
                };
                if (window.__orderRemind && typeof window.__orderRemind.register === 'function') {
                    window.__orderRemind.register(_voiceStart, _voiceStop);
                }
                if (window.__orderRemind && window.__orderRemind.isLineOrDispatch && window.__orderRemind.isOn()) {
                    _voiceStart();
                }
            })();

            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'admin/order/index' + location.search,
                    // add_url: 'admin/order/add',
                    // edit_url: 'admin/order/edit',
                    // del_url: 'admin/order/del',
                    multi_url: 'admin/order/multi',
                    import_url: 'admin/order/import',
                    table: 'admin_order',
                }
            });

            var table = $("#table");
            var isSales = parseInt(table.data('is-sales'), 10) === 1;

            var getGroupName = function(row) {
                return (row && row.admin_group_name) ? row.admin_group_name.toString() : '';
            };
            var roleContains = function(row, keyword) {
                if (!keyword) {
                    return false;
                }
                return getGroupName(row).indexOf(keyword) !== -1;
            };
            var table = $("#table");
            var rowIsSuperAdmin = function(row) { return roleContains(row, '管理'); };
            var rowIsRegularAdmin = function(row) {
                return roleContains(row, '管理') && !rowIsSuperAdmin(row);
            };

            var rowIsSalesRole = function(row) { return roleContains(row, '线路'); };

            var rowIsPlanningRole = function(row) { return roleContains(row, '规划'); };
            var rowIsDispatchRole = function(row) { return roleContains(row, '调度'); };
            // 线路仅允许查看“小票快运”的取货司机相关信息
            var rowIsLineExpressRole = function(row) {
                return rowIsSalesRole(row) && row && row.find_car_type == '小票快运';
            };
            var rowIsFinanceRole = function(row) { return roleContains(row, '财务'); };
            /** pay_status=8 驳回：除「查看订单信息」「查看物流轨迹」外隐藏所有操作 */
            var rowRejectedLockOps = function (row) {
                return parseInt(row.pay_status, 10) === 8;
            };
            // 预计到货时间为明天时整行标红（用于提醒即将到货）
            var isArrivalTomorrow = function (row) {
                if (!row || !row.estimated_arrival_time) {
                    return false;
                }
                var pad = function (n) { return n < 10 ? '0' + n : '' + n; };
                var now = new Date();
                var tomorrow = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1);
                var target = tomorrow.getFullYear() + '-' + pad(tomorrow.getMonth() + 1) + '-' + pad(tomorrow.getDate());
                return String(row.estimated_arrival_time) === target;
            };
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                // 兼容接口返回 {code, data:{total,rows}} 与直接 {total,rows} 两种结构
                responseHandler: function (res) {
                    // 兼容不同接口返回结构
                    var data = res && res.data ? res.data : res;
                    var rows = [];
                    // 常见字段 rows/list/items/data
                    if (data) {
                        rows = data.rows || data.list || data.items || data.data || [];
                        if (!Array.isArray(rows) && typeof rows === 'object') {
                            // 极端情况下 rows 本身是对象，取其值数组
                            rows = Object.values(rows);
                        }
                    }
                    var total = (data && (data.total || data.count || data.totalNum || data.total_count)) || rows.length || 0;
                    // 处理统计信息（与 order.js 一致）
                    var statistics = (res && res.statistics) || (data && data.statistics);
                    if (statistics) {
                        Controller.updateStatistics(statistics);
                    }
                    return {total: total, rows: rows};
                },
                // 预计到货时间为明天时整行标红
                rowStyle: function (row) {
                    if (isArrivalTomorrow(row)) {
                        return {classes: 'order-arrival-tomorrow'};
                    }
                    return {};
                },
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), width: 70, cellStyle: function () { return { css: { 'white-space': 'nowrap' } }; }},
                        // {field: 'isdu', title: __('消息'), formatter: function(value, row, index) {
                        //     if (value == 1) {
                        //         return '<span class="badge badge-danger" style="background-color: red;">新消息</span>';
                        //     }else{
                        //         return '<span class="badge badge-secondary">无消息</span>';
                        //     }
                        // }},
                        // {field: 'status', title: __('订单状态'), formatter: function(value, row, index) {
                        //     // 优先显示status_text（转换后的中文文本），如果不存在则显示原始的status值
                        //     return row.status_text !== undefined ? row.status_text : value;
                        // }},
                        {field: 'order_id', title: __('订单号'), width: 140, cellStyle: function () { return { css: { 'white-space': 'nowrap' } }; }},
                        {field: 'username', title: __('下单人')},
                        {field: 'order_address', title: __('地址')},
                        {field: 'find_car_type', title: __('找车类型')},
                        {field: 'createtime', title: __('下单时间'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime, datetimeFormat: 'YYYY-MM-DD'},
                        {field: 'is_urgent', title: '送货', searchList: {"0":'否',"1":'是'}, formatter: function (value, row, index) {
                            if (value == 1) {
                                return '<span class="badge badge-danger">已送货</span>';
                            }
                            return '<span class="badge badge-secondary">否</span>';
                        }},
                        {field: 'estimated_arrival_time', title: '预计到货时间', operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime, datetimeFormat: 'YYYY-MM-DD'},
                        {field: 'pay_type', title: __('支付方式'),searchList: {"1":__('到付'),"2":__('月结'),"0":__('寄付')}, formatter: Table.api.formatter.status},
                        {field: 'pay_status', title: __('支付状态'), searchList: {"1":__('Pay_status 1'),"2":__('Pay_status 2'),"3":__('Pay_status 3'),"4":__('Pay_status 4'),"5":__('Pay_status 5'),"6":__('确认价格'),"7":__('已出价'),"8":__('驳回')}, formatter: Table.api.formatter.status},
                        {field: 'payment_method', title: __('付款方式')},
                        {field: 'information_image', title: __('付款截图'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},

                        {field: 'logistics_status', title: __('物流状态'), searchList: {"1":__('Logistics_status 1'),"2":__('Logistics_status 2'),"3":__('Logistics_status 3'),"4":__('Logistics_status 4'),"5":__('Logistics_status 5'),"6":__('Logistics_status 6'),"7":__('Logistics_status 7')}, formatter: Table.api.formatter.status},
                        {field: 'profit', title: '利润', formatter: function(value, row, index) {
                            var v = parseFloat(value);
                            if (isNaN(v)) return '-';
                            var cls = v >= 0 ? 'text-success' : 'text-danger';
                            return '<span class="' + cls + '">¥' + v.toFixed(2) + '</span>';
                        }},

                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                            buttons: [
                                // {
                                //     name: 'edit_main_order',
                                //     group: 'edit_main_order', 8月7日
                                //     text: '编辑订单',
                                //     title: '编辑主订单（order 表）',
                                //     classname: 'btn btn-xs btn-warning btn-click',
                                //     icon: 'fa fa-pencil',
                                //     click: function (options, row) {
                                //         var mainOrderId = row.main_order_id;
                                //         if (!mainOrderId) {
                                //             Layer.msg('未找到对应的主订单ID');
                                //             return;
                                //         }
                                //         var url = 'order/edit/ids/' + mainOrderId;
                                //         Backend.api.open(url, '编辑订单');
                                //     },
                                //     dropdown: '更多'
                                // },
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
                                    name: 'urgent',
                                    group: 'edit_main_order',
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
                                                url: 'admin/order/urgent',
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
                                        if (rowRejectedLockOps(row)) {
                                            return true;
                                        }
                                        // 已送货的不再显示按钮
                                        if (row.is_urgent == 1) {
                                            return true;
                                        }
                                        if (row.logistics_status  == 7){
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
                                                url: 'admin/order/cancelUrgent',
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
                                        if (rowRejectedLockOps(row)) {
                                            return true;
                                        }
                                        // 只有已送货订单显示“取消送货”
                                        if (row.is_urgent == 1) {
                                            return false;
                                        }
                                        return true;
                                    }
                                },
                                {
                                    name: 'reject',
                                    text: '驳回',
                                    title: '驳回订单（可选择驳回项并填写原因，用户修改时仅可改该项）',
                                    classname: 'btn btn-xs btn-warning btn-click',
                                    icon: 'fa fa-times-circle',
                                    click: function (options, row) {
                                        if (row.pay_status == 8) {
                                            Layer.msg('该订单已驳回');
                                            return;
                                        }
                                        var rejectFieldOptions = [
                                            { value: '', text: '整单驳回（用户可修改全部）' },
                                            { value: 'loading', text: '发货地址' },
                                            { value: 'unload', text: '收货地址' },
                                            { value: 'quantity', text: '数量' },
                                            { value: 'weight', text: '重量' },
                                            { value: 'dimensions', text: '尺寸（长宽高）' },
                                            { value: 'goods_type_id', text: '货物类型' },
                                            { value: 'car_type_id', text: '车型' },
                                            { value: 'direction', text: '总方位' },
                                            { value: 'pay_price', text: '总运费' },
                                        ];
                                        var selectHtml = '<select id="reject-field-select" class="form-control" style="width:100%;margin-bottom:10px;">';
                                        rejectFieldOptions.forEach(function (opt) {
                                            selectHtml += '<option value="' + (opt.value || '') + '">' + (opt.text || '') + '</option>';
                                        });
                                        selectHtml += '</select>';
                                        var content = '<div style="padding:15px;">' +
                                            '<div style="margin-bottom:8px;">驳回项（用户修改订单时仅可修改该项）：</div>' +
                                            selectHtml +
                                            '<div style="margin-bottom:8px;">驳回原因：</div>' +
                                            '<textarea id="reject-reason-input" class="form-control" rows="3" placeholder="请输入驳回原因" style="width:100%;resize:vertical;"></textarea>' +
                                            '</div>';
                                        Layer.open({
                                            type: 1,
                                            title: '驳回订单',
                                            area: ['420px', '280px'],
                                            content: content,
                                            btn: ['确定', '取消'],
                                            yes: function (idx) {
                                                var reason = (document.getElementById('reject-reason-input').value || '').trim();
                                                if (!reason) {
                                                    Layer.msg('请填写驳回原因');
                                                    return;
                                                }
                                                var rejectField = (document.getElementById('reject-field-select').value || '').trim();
                                                Layer.close(idx);
                                                Backend.api.ajax({
                                                    url: 'admin/order/reject',
                                                    data: { ids: row.id, reject: reason, reject_field: rejectField }
                                                }, function (data, ret) {
                                                    Layer.alert(ret.msg || '操作成功');
                                                    table.bootstrapTable('refresh');
                                                }, function (data, ret) {
                                                    Layer.alert(ret.msg || '操作失败');
                                                });
                                            }
                                        });
                                    },
                                    hidden: function (row) {
                                        if (rowRejectedLockOps(row)) {
                                            return true;
                                        }
                                        if (row.logistics_status  >= 7){
                                            return true;
                                        }
                                    }
                                },
                                {
                                    name: 'confirm_line_complete',
                                    text: '专线确认到达',
                                    title: '专线确认到达',
                                    classname: 'btn btn-xs btn-success btn-click',
                                    icon: 'fa fa-flag-checkered',
                                    click: function (options, row) {
                                        if (!row.order_id) {
                                            Layer.msg('订单号不存在');
                                            return;
                                        }
                                        var submitLineComplete = function (feeDeducted) {
                                            Backend.api.ajax({
                                                url: 'logistics/confirm_line_complete',
                                                data: {orderid: row.order_id, fee_deducted: feeDeducted}
                                            }, function (data, ret) {
                                                Layer.alert(ret.msg || '操作成功');
                                                table.bootstrapTable('refresh');
                                            }, function (data, ret) {
                                                Layer.alert(ret.msg || '操作失败');
                                            });
                                        };
                                        Layer.confirm('专线确认到达，是否已扣专线费？', {
                                            icon: 3,
                                            title: '专线确认到达',
                                            btn: ['是', '否']
                                        }, function (index) {
                                            // 已扣专线费：从调度备用金中扣除干线费用
                                            Layer.close(index);
                                            submitLineComplete(1);
                                        }, function (index) {
                                            // 未扣专线费：沿用原有扣费逻辑（填写送货司机时扣除）
                                            Layer.close(index);
                                            submitLineComplete(0);
                                        });
                                    },
                                    hidden: function (row) {
                                        if (rowRejectedLockOps(row)) {
                                            return true;
                                        }
                                        // 已完成专线（line_status == 2）不再显示按钮
                                        var lineStatus = typeof row.line_status !== 'undefined' ? parseInt(row.line_status, 10) : 0;
                                        if (lineStatus === 2) {
                                            return true;
                                        }
                                        // 2. 非销售角色全部隐藏
                                        if (!rowIsDispatchRole(row)) {
                                            return true;
                                        }
                                        return false;

                                    },
                                    dropdown: '更多'
                                },
                                {
                                    name: 'confirm_info_fee_pay',
                                    text: '信息费确认打款',
                                    title: '信息费确认打款',
                                    classname: 'btn btn-xs btn-warning btn-click',
                                    icon: 'fa fa-money',
                                    click: function (options, row) {
                                        if (!row.id) {
                                            Layer.msg('订单信息不完整');
                                            return;
                                        }
                                        var loadIdx = Layer.load(2, { shade: 0.3 });
                                        Backend.api.ajax({
                                            url: 'admin/order/get_info_fee_pay_info',
                                            data: { ids: row.id }
                                        }, function (data, ret) {
                                            Layer.close(loadIdx);
                                            var info = ret.data || {};
                                            var total = parseFloat(info.total || 0);
                                            if (total <= 0) {
                                                Layer.alert('该订单没有需要打款的信息费或额外费用');
                                                return;
                                            }
                                            if (parseInt(info.info_fee_paid_at, 10) > 0) {
                                                Layer.alert('该订单信息费已确认打款');
                                                table.bootstrapTable('refresh');
                                                return;
                                            }
                                            var fmt = function (v) { return parseFloat(v || 0).toFixed(2); };
                                            var balanceTip = (info.balance !== null && info.balance !== undefined && info.balance !== '')
                                                ? '<b class="' + (parseFloat(info.balance) < total ? 'text-danger' : 'text-success') + '">¥' + fmt(info.balance) + '</b>'
                                                : '未开通备用金';
                                            var html = '<div style="padding:16px 20px;line-height:2.1;font-size:13px;">'
                                                + '<div>订单号：<b>' + (info.order_number || row.order_id || '') + '</b></div>'
                                                + '<div>信息费：¥' + fmt(info.info_fee) + '</div>'
                                                + '<div>额外费用（回单费/拆包费/定金）：¥' + fmt(info.extra_fee) + '</div>'
                                                + '<div style="border-top:1px dashed #e5e5e5;margin:8px 0;"></div>'
                                                + '<div>本次扣除合计：<b class="text-danger">¥' + fmt(total) + '</b></div>'
                                                + '<div>当前备用金余额：' + balanceTip + '</div>'
                                                + '</div>';
                                            Layer.open({
                                                type: 1,
                                                title: '信息费确认打款',
                                                area: ['460px', 'auto'],
                                                content: html,
                                                btn: ['确认打款', '取消'],
                                                btnAlign: 'c',
                                                yes: function (index) {
                                                    Backend.api.ajax({
                                                        url: 'admin/order/confirm_info_fee_pay',
                                                        data: { ids: row.id }
                                                    }, function (data, ret) {
                                                        Layer.close(index);
                                                        Layer.alert(ret.msg || '打款成功', { icon: 1 });
                                                        table.bootstrapTable('refresh');
                                                    }, function (data, ret) {
                                                        Layer.alert(ret.msg || '打款失败', { icon: 2 });
                                                    });
                                                }
                                            });
                                        }, function () {
                                            Layer.close(loadIdx);
                                            Layer.alert('获取打款信息失败', { icon: 2 });
                                        });
                                    },
                                    hidden: function (row) {
                                        if (rowRejectedLockOps(row)) {
                                            return true;
                                        }
                                        // 仅调度角色显示
                                        if (!rowIsDispatchRole(row)) {
                                            return true;
                                        }
                                        // 已确认打款则隐藏
                                        if (parseInt(row.info_fee_paid_at || 0, 10) > 0) {
                                            return true;
                                        }
                                        // 无信息费/额外费用则隐藏
                                        if (parseFloat(row.info_fee_total || 0) <= 0) {
                                            return true;
                                        }
                                        return false;
                                    }
                                },
                                {
                                    name: 'detail',
                                    text: __('查看订单信息'),
                                    title: __('查看订单信息'),
                                    classname: 'btn btn-xs btn-info btn-dialog',
                                    icon: 'fa fa-eye',
                                    url: 'admin/order/view'
                                },
                                {
                                    name: 'pay_qrcode',
                                    text: __('展示收款码'),
                                    title: __('展示收款方式'),
                                    classname: 'btn btn-xs btn-success btn-click',
                                    icon: 'fa fa-qrcode',
                                    click: function (options, row) {
                                        if (!row.id) {
                                            Layer.msg('订单信息不完整');
                                            return;
                                        }
                                        var loadIdx = Layer.load(2, { shade: 0.3 });
                                        Backend.api.ajax({
                                            url: 'admin/order/getPaymentMethodList'
                                        }, function (data, ret) {
                                            Layer.close(loadIdx);
                                            var list = (ret && ret.data) ? ret.data : (data || []);
                                            // 二级分类：p_id=0 为一级，其余按 p_id 归到对应父级下
                                            var topLevel = list.filter(function (m) { return parseInt(m.p_id, 10) === 0; });
                                            var withChildren = {};
                                            list.forEach(function (m) {
                                                var pid = parseInt(m.p_id, 10);
                                                if (pid !== 0) {
                                                    if (!withChildren[pid]) withChildren[pid] = [];
                                                    withChildren[pid].push(m);
                                                }
                                            });

                                            var wrapId = 'pay-method-wrap-' + Date.now();
                                            var listHtml = '';
                                            topLevel.forEach(function (p) {
                                                var children = withChildren[p.id] || [];
                                                var name = (p.payment_method || '').replace(/"/g, '&quot;');
                                                if (children.length > 0) {
                                                    listHtml += '<div class="payment-group" style="margin-bottom:14px;">';
                                                    listHtml += '<div class="payment-group-title" style="font-size:12px;color:#888;padding:6px 12px;background:#f8f9fa;border-radius:4px;margin-bottom:6px;font-weight:600;">' + name + '</div>';
                                                    children.forEach(function (c) {
                                                        var cName = (c.payment_method || '').replace(/"/g, '&quot;');
                                                        listHtml += '<div class="payment-item" data-id="' + c.id + '" style="padding:10px 14px;margin:4px 0;cursor:pointer;border-radius:6px;border:1px solid #e9ecef;background:#fff;transition:all 0.2s;margin-left:8px;" onmouseover="this.style.background=\'#f0f7ff\';this.style.borderColor=\'#0d6efd\';" onmouseout="if(!this.classList.contains(\'selected\')){this.style.background=\'#fff\';this.style.borderColor=\'#e9ecef\';}" onclick="var w=this.closest(\'.payment-method-dialog\');w.querySelector(\'[name=pay-method-selected]\').value=this.getAttribute(\'data-id\');var all=w.querySelectorAll(\'.payment-item\');for(var i=0;i<all.length;i++){all[i].classList.remove(\'selected\');all[i].style.background=\'#fff\';all[i].style.borderColor=\'#e9ecef\';}this.classList.add(\'selected\');this.style.background=\'#e7f1ff\';this.style.borderColor=\'#0d6efd\';">' + cName + '</div>';
                                                    });
                                                    listHtml += '</div>';
                                                } else {
                                                    listHtml += '<div class="payment-item" data-id="' + p.id + '" style="padding:10px 14px;margin:6px 0;cursor:pointer;border-radius:6px;border:1px solid #e9ecef;background:#fff;transition:all 0.2s;" onmouseover="this.style.background=\'#f0f7ff\';this.style.borderColor=\'#0d6efd\';" onmouseout="if(!this.classList.contains(\'selected\')){this.style.background=\'#fff\';this.style.borderColor=\'#e9ecef\';}" onclick="var w=this.closest(\'.payment-method-dialog\');w.querySelector(\'[name=pay-method-selected]\').value=this.getAttribute(\'data-id\');var all=w.querySelectorAll(\'.payment-item\');for(var i=0;i<all.length;i++){all[i].classList.remove(\'selected\');all[i].style.background=\'#fff\';all[i].style.borderColor=\'#e9ecef\';}this.classList.add(\'selected\');this.style.background=\'#e7f1ff\';this.style.borderColor=\'#0d6efd\';">' + name + '</div>';
                                                }
                                            });
                                            var content = '<div class="payment-method-dialog" id="' + wrapId + '" style="padding:20px;max-height:360px;overflow-y:auto;">' +
                                                '<input type="hidden" name="pay-method-selected" value="" />' +
                                                '<p style="margin:0 0 16px 0;color:#495057;font-size:14px;">请选择收款方式</p>' +
                                                '<div class="payment-method-list">' + listHtml + '</div></div>';
                                            Layer.open({
                                                type: 1,
                                                title: '<i class="fa fa-credit-card"></i> 选择收款方式',
                                                area: ['420px', '480px'],
                                                content: content,
                                                btn: ['确定', '取消收款方式', '关闭'],
                                                btn2: function (index) {
                                                    Backend.api.ajax({
                                                        url: 'admin/order/cancelOrderPaymentComplete',
                                                        data: { ids: row.id }
                                                    }, function () {
                                                        Layer.close(index);
                                                        Layer.msg('已取消收款方式');
                                                        table.bootstrapTable('refresh');
                                                    }, function (data2, ret2) {
                                                        Layer.alert((ret2 && ret2.msg) || '取消失败');
                                                    });
                                                    return false;
                                                },
                                                yes: function (index) {
                                                    var val = parseInt($('#' + wrapId).find('[name=pay-method-selected]').val(), 10) || 0;
                                                    if (!val) {
                                                        Layer.msg('请先选择收款方式');
                                                        return;
                                                    }
                                                    Layer.close(index);
                                                    if (val === 6) {
                                                        // 订单二维码：弹出支付二维码
                                                        var loadIdx2 = Layer.load(2, { shade: 0.3 });
                                                        Backend.api.ajax({
                                                            url: 'admin/order/payQrcode',
                                                            data: { ids: row.id }
                                                        }, function (data2, ret2) {
                                                            Layer.close(loadIdx2);
                                                            var d = (ret2 && ret2.data) ? ret2.data : (data2 || {});
                                                            var qrcodeUrl = d.qrcode_url || d.code_url;
                                                            if (!qrcodeUrl) {
                                                                Layer.alert((ret2 && ret2.msg) || '未获取到收款码');
                                                                return;
                                                            }
                                                            var html = '<div style="padding: 20px; text-align: center;">' +
                                                                '<p style="margin-bottom: 10px;"><strong>订单号：</strong>' + (d.orderid || row.order_id || '') + '</p>' +
                                                                '<p style="margin-bottom: 15px;"><strong>支付金额：</strong>¥' + (d.pay_price || row.pay_price || '0') + '</p>' +
                                                                '<p style="margin-bottom: 10px;">请使用微信扫码支付</p>' +
                                                                '<img src="' + qrcodeUrl + '" alt="收款码" style="max-width: 280px; height: auto; border: 1px solid #eee;">' +
                                                                '</div>';
                                                            Layer.open({
                                                                type: 1,
                                                                title: '订单收款码',
                                                                area: ['500px', '500px'],
                                                                content: html,
                                                                btn: ['关闭']
                                                            });
                                                        }, function (data2, ret2) {
                                                            Layer.close(loadIdx2);
                                                            Layer.alert((ret2 && ret2.msg) || '获取收款码失败');
                                                        });
                                                    } else {
                                                        var isPaid = parseInt(row.pay_status, 10) === 3;
                                                        var confirmText = isPaid
                                                            ? '确定要修改该订单的收款方式吗？'
                                                            : '请确认款项已经实际到账。未收到款项请勿确认，确定已收款吗？';
                                                        Layer.confirm(confirmText, {
                                                            title: isPaid ? '修改收款方式' : '确认已收款',
                                                            icon: isPaid ? 3 : 0,
                                                            btn: ['确认', '取消']
                                                        }, function (confirmIndex) {
                                                            Layer.close(confirmIndex);
                                                            Backend.api.ajax({
                                                                url: 'admin/order/setOrderPaymentComplete',
                                                                data: { ids: row.id, payment_method_id: val }
                                                            }, function () {
                                                                Layer.msg(isPaid ? '付款方式已更新' : '已确认收款');
                                                                table.bootstrapTable('refresh');
                                                            }, function (data2, ret2) {
                                                                Layer.alert((ret2 && ret2.msg) || '操作失败');
                                                            });
                                                        });
                                                    }
                                                }
                                            });
                                        }, function (data, ret) {
                                            Layer.close(loadIdx);
                                            Layer.alert((ret && ret.msg) || '获取收款方式失败');
                                        });
                                    },
                                    hidden: function (row) {
                                        if (rowRejectedLockOps(row)) {
                                            return true;
                                        }
                                        if (!rowIsDispatchRole(row)) {
                                            return true;
                                        }
                                        return false;
                                    }
                                },
                                {
                                    name: 'detail',
                                    text: __('查看物流轨迹'),
                                    title: __('查看物流轨迹'),
                                    classname: 'btn btn-xs btn-default btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'order/logistics_detail',
                                    dropdown: '更多'
                                },
                                {
                                    name: 'confirm',
                                    text: __('配车确定订单'),
                                    title: __('配车确定订单'),
                                    classname: 'btn btn-xs btn-primary btn-click',
                                    icon: 'fa fa-check',
                                    click: function (options, row) {
                                            Layer.confirm('确定要确认配车订单吗？', function(index) {
                                                Backend.api.ajax({
                                                    url: 'admin/order/carfim',
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
                                    hidden:function(row){
                                        if (rowRejectedLockOps(row)) {
                                            return true;
                                        }
                                        // 1. 已确认订单不显示
                                        if (row.status == 1) {
                                            return true;
                                        }
                                        // 2. 不是配车类型不显示
                                        if (row.find_car_type != '配车') {
                                            return true;
                                        }
                                        // 3. 非线路角色不显示
                                        if (!rowIsSalesRole(row)) {
                                            return true;
                                        }
                                        return false;
                                    },
                                    dropdown: '更多'
                                },
                                {
                                    name: 'confirm',
                                    text: __('专车/小票快运确定订单'),
                                    title: __('专车/小票快运确定订单'),
                                    classname: 'btn btn-xs btn-primary btn-click',
                                    icon: 'fa fa-check',
                                    click: function (options, row) {
                                        Layer.prompt({
                                            title: '请输入订单金额（元）',
                                            formType: 0,
                                            value: row.pay_price ? row.pay_price : ''
                                        }, function (value, index) {
                                            var amount = parseFloat(value);
                                            if (isNaN(amount) || amount < 0) {
                                                Layer.msg('请输入有效的金额');
                                                return;
                                            }
                                            Layer.close(index);
                                            Backend.api.ajax({
                                                url: 'admin/order/carfim', 
                                                data: {ids: row.id, pay_price: amount.toFixed(2)}
                                            }, function (data, ret) {
                                                Layer.alert(ret.msg || '操作成功');
                                                table.bootstrapTable('refresh');
                                            }, function (data, ret) {
                                                Layer.alert(ret.msg || '操作失败');
                                            });
                                        });
                                    },
                                    hidden:function(row){
                                        if (rowRejectedLockOps(row)) {
                                            return true;
                                        }
                                        // 1. 已确认订单不显示
                                        if (row.status == 1) {
                                            return true;
                                        }
                                        // 2. 非销售角色全部隐藏
                                        if (!rowIsSalesRole(row)) {
                                            return true;
                                        }
                                        if (row.find_car_type == '配车') {
                                            // console.log(1111);
                                            return true;
                                        }
                                        return false;
                                    },
                                    dropdown: '更多'
                                },
                                {
                                    name: 'reprice',
                                    text: __('重新报价'),
                                    title: __('重新报价'),
                                    classname: 'btn btn-xs btn-success btn-click',
                                    icon: 'fa fa-edit',
                                    click: function (options, row) {
                                        Layer.prompt({
                                            title: '请输入新的订单金额（元）',
                                            formType: 0,
                                            value: row.pay_price ? row.pay_price : ''
                                        }, function (value, index) {
                                            var amount = parseFloat(value);
                                            if (isNaN(amount) || amount < 0) {
                                                Layer.msg('请输入有效的金额');
                                                return;
                                            }
                                            Layer.close(index);
                                            Backend.api.ajax({
                                                url: 'admin/order/carfim',
                                                data: {ids: row.id, pay_price: amount.toFixed(2)}
                                            }, function (data, ret) {
                                                Layer.alert(ret.msg || '操作成功');
                                                table.bootstrapTable('refresh');
                                            }, function (data, ret) {
                                                Layer.alert(ret.msg || '操作失败');
                                            });
                                        });
                                    },

                                    hidden:function(row){
                                        if (rowRejectedLockOps(row)) {
                                            return true;
                                        }
                                        // 已收货后不能重新报价
                                        if (row.logistics_status == 7) {
                                            return true;
                                        }
                                        // 1. 未确认订单不显示（只有已确认的订单才能重新报价）
                                        if (row.status != 1) {
                                            return true;
                                        }
                                        // 2. 非销售角色全部隐藏
                                        if (!rowIsSalesRole(row)) {
                                            return true;
                                        }
                                        // 3. 配车类型不显示
                                        if (row.find_car_type == '配车') {
                                            return true;
                                        }
                                        return false;
                                    },
                                    dropdown: '更多'
                                },
                                {
                                    name: 'fillinfo',
                                    text: __('填写订单信息'),
                                    title: __('填写订单信息'),
                                    classname: 'btn btn-xs btn-success btn-click',
                                    icon: 'fa fa-edit',
                                    click: function (options, row) {
                                        var type = row.find_car_type || '';
                                        var openCarForm = function (info) {
                                            var carHtml = '' +
                                                '<div style="padding: 15px 20px;">' +
                                                '<div class="form-group"><label>车牌号</label><input id="orderinfo_car_num" type="text" class="form-control" placeholder="请输入车牌号"/></div>' +
                                                '<div class="form-group"><label>司机姓名</label><input id="orderinfo_driver_name" type="text" class="form-control" placeholder="请输入司机姓名"/></div>' +
                                                '<div class="form-group"><label>司机电话</label><input id="orderinfo_driver_num" type="text" class="form-control" placeholder="请输入司机电话"/></div>' +
                                                '<div class="form-group"><label>司机价格</label><input id="orderinfo_driver_price" type="text" class="form-control" placeholder="请输入司机价格"/></div>' +
                                                '<div class="form-group"><label>行驶证照片</label>' +
                                                    '<div class="input-group">' +
                                                        '<input id="orderinfo_vehicle_img" type="text" class="form-control" placeholder="点击右侧上传" readonly />' +
                                                        '<span class="input-group-btn">' +
                                                            '<button class="btn btn-default" type="button" id="btn_upload_vehicle">上传</button>' +
                                                        '</span>' +
                                                    '</div>' +
                                                    '<input id="orderinfo_vehicle_file" type="file" accept="image/*" style="display:none;" />' +
                                                '</div>' +
                                                '<div class="form-group"><label>驾驶证照片</label>' +
                                                    '<div class="input-group">' +
                                                        '<input id="orderinfo_license_img" type="text" class="form-control" placeholder="点击右侧上传" readonly />' +
                                                        '<span class="input-group-btn">' +
                                                            '<button class="btn btn-default" type="button" id="btn_upload_license">上传</button>' +
                                                        '</span>' +
                                                    '</div>' +
                                                    '<input id="orderinfo_license_file" type="file" accept="image/*" style="display:none;" />' +
                                                '</div>' +
                                                '</div>';
                                            Layer.open({
                                                type: 1,
                                                title: '填写专车信息',
                                                area: ['520px', 'auto'],
                                                content: carHtml,
                                                btn: ['提交', '取消'],
                                                success: function () {
                                                    // 回显（与填写取货司机一致，再次打开时数据回显）
                                                    $('#orderinfo_car_num').val(info.car_num || '');
                                                    $('#orderinfo_driver_name').val(info.driver_name || '');
                                                    $('#orderinfo_driver_num').val(info.driver_num || '');
                                                    $('#orderinfo_driver_price').val(info.driver_price || '');
                                                    $('#orderinfo_vehicle_img').val(info.vehicle_img || '');
                                                    $('#orderinfo_license_img').val(info.license_img || '');
                                                    // 绑定上传行为
                                                    var bindUpload = function (btnId, fileId, inputId) {
                                                        $(btnId).off('click').on('click', function () {
                                                            $(fileId).trigger('click');
                                                        });
                                                        $(fileId).off('change').on('change', function (evt) {
                                                            var file = evt.target.files && evt.target.files[0];
                                                            if (!file) {
                                                                return;
                                                            }
                                                            require(['upload'], function (Upload) {
                                                                Upload.api.send(file, function (data) {
                                                                    var url = data.url || (data.data && data.data.url) || '';
                                                                    $(inputId).val(url);
                                                                    Layer.msg('上传成功');
                                                                }, function () {
                                                                    Layer.msg('上传失败');
                                                                });
                                                            });
                                                         });
                                                    };
                                                    bindUpload('#btn_upload_vehicle', '#orderinfo_vehicle_file', '#orderinfo_vehicle_img');
                                                    bindUpload('#btn_upload_license', '#orderinfo_license_file', '#orderinfo_license_img');
                                                },
                                                yes: function (index) {
                                                    var payload = {
                                                        ids: row.id,
                                                        order_id: row.id,
                                                        car_num: $.trim($('#orderinfo_car_num').val()),
                                                        driver_name: $.trim($('#orderinfo_driver_name').val()),
                                                        driver_num: $.trim($('#orderinfo_driver_num').val()),
                                                        driver_price: $.trim($('#orderinfo_driver_price').val()) || '0',
                                                        vehicle_img: $.trim($('#orderinfo_vehicle_img').val()),
                                                        license_img: $.trim($('#orderinfo_license_img').val()),
                                                        find_car_type: type
                                                    };
                                                    if (!payload.car_num || !payload.driver_name || !payload.driver_num) {
                                                        Layer.msg('车牌号、司机姓名、司机电话为必填项');
                                                        return;
                                                    }
                                                    Backend.api.ajax({
                                                        url: 'admin/order/save_order_info',
                                                        data: payload
                                                    }, function (data, ret) {
                                                        Layer.close(index);
                                                        Layer.alert(ret.msg || '保存成功');
                                                        table.bootstrapTable('refresh');
                                                    }, function (data, ret) {
                                                        Layer.alert(ret.msg || '保存失败');
                                                    });
                                                }
                                            });
                                        };

                                        var openExpressForm = function (info) {
                                            var expressHtml = '' +
                                                '<div style="padding: 15px 20px;">' +
                                                '<div class="form-group"><label>快递名称</label><input id="orderinfo_delivery_name" type="text" class="form-control" placeholder="请输入快递名称"/></div>' +
                                                '<div class="form-group"><label>快递单号</label><input id="orderinfo_delivery_num" type="text" class="form-control" placeholder="请输入快递单号"/></div>' +
                                                '<div class="form-group"><label>小票价格</label><input id="orderinfo_driver_price" type="text" class="form-control" placeholder="请输入小票价格"/></div>' +
                                                '</div>';
                                            Layer.open({
                                                type: 1,
                                                title: '填写小票快运信息',
                                                area: ['420px', 'auto'],
                                                content: expressHtml,
                                                btn: ['提交', '取消'],
                                                success: function () {
                                                    $('#orderinfo_delivery_name').val(info.delivery_name || '');
                                                    $('#orderinfo_delivery_num').val(info.delivery_num || '');
                                                    $('#orderinfo_driver_price').val(info.driver_price || '');
                                                },
                                                yes: function (index) {
                                                    var payload = {
                                                        ids: row.id,
                                                        order_id: row.id,
                                                        delivery_name: $.trim($('#orderinfo_delivery_name').val()),
                                                        delivery_num: $.trim($('#orderinfo_delivery_num').val()),
                                                        driver_price: $.trim($('#orderinfo_driver_price').val()) || '0',
                                                        find_car_type: type
                                                    };
                                                    if (!payload.delivery_name || !payload.delivery_num) {
                                                        Layer.msg('快递名称和快递单号为必填项');
                                                        return;
                                                    }
                                                    Backend.api.ajax({
                                                        url: 'admin/order/save_order_info',
                                                        data: payload
                                                    }, function (data, ret) {
                                                        Layer.close(index);
                                                        Layer.alert(ret.msg || '保存成功');
                                                        table.bootstrapTable('refresh');
                                                    }, function (data, ret) {
                                                        Layer.alert(ret.msg || '保存失败');
                                                    });
                                                }
                                            });
                                        };

                                        var openEmptyType = function () {
                                            Layer.msg('当前订单类型不支持填写该信息');
                                        };

                                        var renderByType = function (info) {
                                            if (type === '专车') {
                                                openCarForm(info || {});
                                            } else if (type === '小票快运') {
                                                openExpressForm(info || {});
                                            } else {
                                                openEmptyType();
                                            }
                                        };

                                        // 先获取已有信息再渲染
                                        Backend.api.ajax({
                                            url: 'admin/order/get_order_info_extra',
                                            data: {order_id: row.id}
                                        }, function (data, ret) {
                                            renderByType(ret.data || {});
                                        }, function () {
                                            renderByType({});
                                        });
                                    },
                                    hidden:function(row){
                                        if (rowRejectedLockOps(row)) {
                                            return true;
                                        }
                                        // 已收货后不能更改订单信息
                                        if (row.logistics_status == 7) {
                                            return true;
                                        }
                                        // 1. 未确认订单不显示（只有已确认的订单才能填写）
                                        if (row.status != 1) {
                                            return true;
                                        }
                                        // 2. 非销售角色全部隐藏
                                        if (!rowIsSalesRole(row)) {
                                            return true;
                                        }
                                        // 3. 配车类型不显示
                                        if (row.find_car_type == '配车') {
                                            return true;
                                        }
                                        return false;
                                    },
                                    dropdown: '更多'
                                },
                                {
                                    name: 'confirm_dedicated_arrival',
                                    text: __('确认专车到达'),
                                    title: __('确认专车到达'),
                                    classname: 'btn btn-xs btn-info btn-click',
                                    icon: 'fa fa-flag-checkered',
                                    click: function (options, row) {
                                        Layer.confirm('确认专车已到达？', {icon: 3, title: '确认'}, function (index) {
                                            Backend.api.ajax({
                                                url: 'admin/order/confirm_dedicated_arrival',
                                                data: { ids: row.id }
                                            }, function (data, ret) {
                                                Layer.close(index);
                                                Layer.msg(ret.msg || '操作成功');
                                                table.bootstrapTable('refresh');
                                            }, function (data, ret) {
                                                Layer.alert(ret.msg || '操作失败');
                                            });
                                        });
                                    },
                                    hidden: function (row) {
                                        if (rowRejectedLockOps(row)) {
                                            return true;
                                        }
                                        if (row.find_car_type != '专车') {
                                            return true;
                                        }
                                        // 已收货后不显示确认到达按钮
                                        if (row.logistics_status == 7) {
                                            return true;
                                        }
                                        return false;
                                    },
                                    dropdown: '更多'
                                },
                                // {
                                //     name: 'confirm',
                                //     text: __('填写司机成本'),
                                //     title: __('填写司机成本'),
                                //     classname: 'btn btn-xs btn-primary btn-click',
                                //     icon: 'fa fa-check',
                                //     click: function (options, row) {
                                //         Layer.prompt({
                                //             title: '请输入司机成本金额（元）',
                                //             formType: 0,
                                //             value: row.pay_price ? row.pay_price : ''
                                //         }, function (value, index) {
                                //             var amount = parseFloat(value);
                                //             if (isNaN(amount) || amount < 0) {
                                //                 Layer.msg('请输入有效的金额');
                                //                 return;
                                //             }
                                //             Layer.close(index);
                                //             Backend.api.ajax({
                                //                 url: 'admin/order/carfimprice',
                                //                 data: {ids: row.id, pay_price: amount.toFixed(2)}
                                //             }, function (data, ret) {
                                //                 Layer.alert(ret.msg || '操作成功');
                                //                 table.bootstrapTable('refresh');
                                //             }, function (data, ret) {
                                //                 Layer.alert(ret.msg || '操作失败');
                                //             });
                                //         });
                                //     },
                                //     hidden:function(row){
                                //         if (row.find_car_type == '配车') {
                                //             // console.log(1111);
                                //             return true;
                                //         }
                                //         // 2. 非规划角色全部隐藏
                                //         if (!rowIsSalesRole(row)) {
                                //             return true;
                                //         }
                                //
                                //         return false;
                                //     }
                                // },
                                {
                                    name: 'detail',
                                    text: __('查看路线'),
                                    title: __('查看路线（可修改）'),
                                    extend:'data-area=\'["1230px","880px"]\'data-shade=\'[0.5,"##000"]\'',
                                    classname: 'btn btn-xs btn-default btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'logistics/logistics_detail',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    },
                                    hidden:function(row){
                                        if (rowRejectedLockOps(row)) {
                                            return true;
                                        }
                                        if (!rowIsSalesRole(row)) {
                                            return true;
                                        }
                                        if (row.find_car_type == '专车') {
                                            // console.log(1111);
                                            return true;
                                        }
                                        if (row.find_car_type == '小票快运') {
                                            // console.log(1111);
                                            return true;
                                        }
                                        if (row.logistics_status >= 4) {
                                            return true;
                                        }
                                        return false;
                                    },
                                    dropdown: '更多'
                                },
                                {
                                    name: 'click',
                                    text: __('修改线路成本'),
                                    title: __('修改线路成本'),
                                    classname: 'btn btn-xs btn-warning btn-click',
                                    icon: 'fa fa-edit',
                                    hidden:function(row){
                                        if (rowRejectedLockOps(row)) {
                                            return true;
                                        }
                                        if (!rowIsSalesRole(row)) {
                                            return true;
                                        }
                                        if (row.find_car_type == '专车') {
                                            // console.log(1111);
                                            return true;
                                        }
                                        if (row.find_car_type == '小票快运') {
                                            // console.log(1111);
                                            return true;
                                        }
                                        if (row.logistics_status >= 4) {
                                            return true;
                                        }
                                        return false;
                                    },
                                    click: function(options, row) {
                                        // 先获取当前线路成本
                                        Backend.api.ajax({
                                            url: 'admin/order/get_logistics_cost',
                                            data: {ids: row.id}
                                        }, function(data, ret) {
                                            if (ret.code == 1) {
                                                var costInfo = ret.data;
                                                var currentCost = costInfo.logistics_cost || '0.00';
                                                var costFormHtml = '<div style="padding: 15px 20px;">' +
                                                    '<div class="form-group"><label>线路成本（元）</label><input id="logistics_cost_amount" type="number" step="0.01" class="form-control" placeholder="请输入金额" value="' + currentCost + '"/></div>' +
                                                    '<div class="form-group"><label>备注</label><textarea id="logistics_cost_remark" class="form-control" rows="3" placeholder="选填，说明修改原因等"></textarea></div>' +
                                                    '</div>';
                                                Layer.open({
                                                    type: 1,
                                                    title: '修改线路成本',
                                                    area: ['400px', 'auto'],
                                                    content: costFormHtml,
                                                    btn: ['确定', '取消'],
                                                    yes: function(index) {
                                                        var amount = parseFloat($('#logistics_cost_amount').val());
                                                        if (isNaN(amount) || amount < 0) {
                                                            Layer.msg('请输入有效的金额');
                                                            return;
                                                        }
                                                        var remark = $.trim($('#logistics_cost_remark').val());
                                                        Layer.close(index);
                                                        Backend.api.ajax({
                                                            url: 'admin/order/update_logistics_cost',
                                                            data: {
                                                                ids: row.id,
                                                                logistics_cost: amount.toFixed(2),
                                                                remark: remark
                                                            }
                                                        }, function(data, ret) {
                                                            Layer.alert(ret.msg || '修改成功', {icon: 1});
                                                            table.bootstrapTable('refresh');
                                                        }, function(data, ret) {
                                                            Layer.alert(ret.msg || '修改失败', {icon: 2});
                                                        });
                                                    }
                                                });
                                            } else {
                                                Layer.alert(ret.msg || '获取成本信息失败', {icon: 2});
                                            }
                                        }, function(data, ret) {
                                            Layer.alert(ret.msg || '获取成本信息失败', {icon: 2});
                                        });
                                    },
                                    dropdown: '更多'
                                },
                                {
                                    name: 'fill_return_tracking',
                                    text: '填写回单寄回单号',
                                    title: '填写回单寄回单号',
                                    classname: 'btn btn-xs btn-info btn-click',
                                    icon: 'fa fa-reply',

                                    hidden: function (row) {
                                        if (rowRejectedLockOps(row)) {
                                            return true;
                                        }
                                        // 仅回单方式 id 为 2 时显示（列表里 receipt_type_id 被后端改成名称，用 receipt_type_id_value 判断）
                                        return parseInt(row.receipt_type_id_value, 10) !== 2;
                                    },
                                    click: function (options, row) {
                                        var esc = function (s) { return (s || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;'); };
                                        var nameVal = esc(row.return_express_name);
                                        var noVal = esc(row.return_express_no);
                                        Layer.open({
                                            type: 1,
                                            title: '填写回单寄回单号',
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
                                },
                                {
                                    name: 'detail',
                                    text: __('填写物流成本'),
                                    title: __('填写物流成本'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-cny',
                                    url: 'logistics/logistics_cost',
                                    hidden: function(row) {
                                        if (rowRejectedLockOps(row)) {
                                            return true;
                                        }
                                        if (isSales) {
                                            return true;
                                        }
                                        if (rowIsSalesRole(row) || rowIsPlanningRole(row) || rowIsFinanceRole(row) || rowIsSalesRole(row)|| row.logistics_cost != 0) {
                                            return true;
                                        }
                                        return false;
                                    },
                                    dropdown: '更多'
                                },
                                {
                                    name: 'detail',
                                    text: __('填写取货司机'),
                                    title: __('填写取货司机'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-cny',
                                    url: 'logistics/driver_price',
                                    // 取货司机弹窗内会根据货运平台自动计算货拉拉 9% 税点
                                    hidden:function(row){
                                        if (rowRejectedLockOps(row)) {
                                            return true;
                                        }
                                        // 调度：保持原逻辑
                                        if (rowIsDispatchRole(row)) {
                                            // 已有司机成本则隐藏
                                            return row.is_dirver == 1;
                                        }
                                        // 线路：仅“小票快运”可见（且已有司机成本则隐藏）
                                        if (rowIsLineExpressRole(row)) {
                                            return row.is_dirver == 1;
                                        }
                                        return true;
                                    },
                                    dropdown: '更多'
                                },
                                {
                                    name: 'detail',
                                    text: __('填写取货司机额外成本'),
                                    title: __('填写取货司机额外成本'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-cny',
                                    url: 'logistics/driver_other_price',
                                    hidden:function(row){
                                        if (rowRejectedLockOps(row)) {
                                            return true;
                                        }
                                        // 调度保持原逻辑
                                        if (rowIsDispatchRole(row)) {
                                            return false;
                                        }
                                        // 线路：仅“小票快运”可见
                                        if (rowIsLineExpressRole(row)) {
                                            return false;
                                        }
                                        // 其他角色隐藏；已收货也显示，修改后需总后台审核
                                        return true;
                                    },
                                    dropdown: '更多'
                                },
                                {
                                    name: 'detail',
                                    text: __('填写送货司机'),
                                    title: __('填写送货司机'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-cny',
                                    url: 'logistics/songdriver_price',
                                    hidden:function(row){
                                        if (rowRejectedLockOps(row)) {
                                            return true;
                                        }
                                        // 只有“调度”角色，并且当前还没有司机成本标记(is_dirver == 0)时，才显示“填写司机成本”
                                        // 非调度角色全部隐藏
                                        if (!rowIsDispatchRole(row)) {
                                            return true;
                                        }
                                        // 已有司机成本则隐藏
                                        if (row.is_songdirver == 1) {
                                            return true;
                                        }
                                        return false;
                                    },
                                    dropdown: '更多'
                                },
                                {
                                    name: 'detail',
                                    text: __('填写送货司机额外成本'),
                                    title: __('填写送货司机额外成本'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-cny',
                                    url: 'logistics/songdriver_other_price',
                                    hidden:function(row){
                                        if (rowRejectedLockOps(row)) {
                                            return true;
                                        }
                                        // 非调度角色全部隐藏；已收货也显示，修改后需总后台审核
                                        if (!rowIsDispatchRole(row)) {
                                            return true;
                                        }
                                        return false;
                                    },
                                    dropdown: '更多'
                                },
                                {
                                    name: 'detail',
                                    text: __('物流额外成本'),
                                    title: __('物流额外成本'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-truck',
                                    url: 'logistics/logistics_extra_price',
                                    hidden:function(row){
                                        if (rowRejectedLockOps(row)) {
                                            return true;
                                        }
                                        return false;
                                    },
                                    dropdown: '更多'
                                },
                                {
                                    name: 'click',
                                    text: __('取消专线订单'),
                                    title: __('取消专线订单'),
                                    classname: 'btn btn-xs btn-warning btn-click',
                                    icon: 'fa fa-close',
                                    hidden: function(row) {
                                        if (rowRejectedLockOps(row)) {
                                            return true;
                                        }
                                        if (!rowIsSalesRole(row)) {
                                            return true;
                                        }
                                        if (row.find_car_type == '专车') {
                                            // console.log(1111);
                                            return true;
                                        }
                                        if (row.find_car_type == '小票快运') {
                                            // console.log(1111);
                                            return true;
                                        }
                                        if (row.logistics_status >= 4) {
                                            return true;
                                        }
                                        return false;
                                    },
                                    click: function(options, row) {
                                        // 获取订单详细信息
                                        Backend.api.ajax({
                                            url: 'admin/order/get_order_info',
                                            data: {ids: row.id}
                                        }, function(data, ret) {
                                            if (ret.code == 1) {
                                                var info = ret.data;
                                                var html = '<div style="padding: 20px;">' +
                                                    '<h4 style="margin-bottom: 20px;">订单信息</h4>' +
                                                    '<div class="row" style="margin-bottom: 15px;">' +
                                                    '<div class="col-md-6"><strong>订单编号：</strong>' + (info.orderid || '') + '</div>' +
                                                    '</div>' +
                                                    '<hr/>' +
                                                    '<h5 style="margin-bottom: 15px;">专线信息</h5>' +
                                                    '<div class="row" style="margin-bottom: 10px;">' +
                                                    '<div class="col-md-6"><strong>专线名称：</strong>' + (info.logistics_name || '未分配') + '</div>' +
                                                    '<div class="col-md-6"><strong>起点电话：</strong>' + (info.logistics_start_phone || '无') + '</div>' +
                                                    '</div>' +
                                                    '<div class="row" style="margin-bottom: 10px;">' +
                                                    '<div class="col-md-6"><strong>终点电话：</strong>' + (info.logistics_end_phone || '无') + '</div>' +
                                                    '</div>' +
                                                    '<hr/>' +
                                                    '<h5 style="margin-bottom: 15px;">取货司机信息</h5>' +
                                                    '<div class="row" style="margin-bottom: 10px;">' +
                                                    '<div class="col-md-6"><strong>司机姓名：</strong>' + (info.pickup_driver_name || '未分配') + '</div>' +
                                                    '<div class="col-md-6"><strong>司机电话：</strong>' + (info.pickup_driver_phone || '无') + '</div>' +
                                                    '</div>' +
                                                    '<hr/>' +
                                                    '<h5 style="margin-bottom: 15px;">送货司机信息</h5>' +
                                                    '<div class="row" style="margin-bottom: 10px;">' +
                                                    '<div class="col-md-6"><strong>司机姓名：</strong>' + (info.shipment_driver_name || '未分配') + '</div>' +
                                                    '<div class="col-md-6"><strong>司机电话：</strong>' + (info.shipment_driver_phone || '无') + '</div>' +
                                                    '</div>' +
                                                    '</div>';
                                                Layer.open({
                                                    type: 1,
                                                    title: '取消专线订单',
                                                    area: ['600px', 'auto'],
                                                    content: html,
                                                    btn: ['确认取消', '取消'],
                                                    yes: function(index) {
                                                        // 执行取消专线订单
                                                        Backend.api.ajax({
                                                            url: 'admin/order/cancel_logistics_order',
                                                            data: {ids: row.id}
                                                        }, function(data, ret) {
                                                            Layer.close(index);
                                                            Layer.alert(ret.msg || '取消成功', {icon: 1});
                                                            table.bootstrapTable('refresh');
                                                        }, function(data, ret) {
                                                            Layer.alert(ret.msg || '取消失败', {icon: 2});
                                                        });
                                                     }
                                                });
                                            } else {
                                                Layer.alert(ret.msg || '获取订单信息失败', {icon: 2});
                                            }
                                        }, function(data, ret) {
                                            Layer.alert(ret.msg || '获取订单信息失败', {icon: 2});
                                        });
                                    },
                                    dropdown: '更多'
                                },
                                {
                                    name: 'click',
                                    text: __('取消司机订单'),
                                    title: __('取消司机订单'),
                                    classname: 'btn btn-xs btn-warning btn-click',
                                    icon: 'fa fa-user-times',
                                    hidden: function(row) {
                                        if (rowRejectedLockOps(row)) {
                                            return true;
                                        }
                                        // 只有"规划"角色可以看到
                                        if (!rowIsDispatchRole(row)) {
                                            return true;
                                        }
                                        // 派送阶段仍允许取消，收货后再隐藏
                                        if (row.logistics_status >= 7) {
                                            return true;
                                        }
                                        return false;
                                    },
                                    click: function(options, row) {
                                        // 获取订单详细信息
                                        Backend.api.ajax({
                                            url: 'admin/order/get_order_info',
                                            data: {ids: row.id}
                                        }, function(data, ret) {
                                            if (ret.code == 1) {
                                                var info = ret.data;
                                                var hasPickup = info.pickup_driver_name && info.pickup_driver_name != '未分配';
                                                var hasShipment = info.shipment_driver_name && info.shipment_driver_name != '未分配';
                                                if (!hasPickup && !hasShipment) {
                                                    Layer.msg('该订单没有分配司机', {icon: 2});
                                                    return;
                                                }
                                                var html = '<div style="padding: 20px;">' +
                                                    '<h4 style="margin-bottom: 20px;">选择要取消的司机订单</h4>' +
                                                    '<div class="row" style="margin-bottom: 15px;">' +
                                                    '<div class="col-md-12"><strong>订单编号：</strong>' + (info.orderid || '') + '</div>' +
                                                    '</div>' +
                                                    '<hr/>';
                                                if (hasPickup) {
                                                    html += '<div class="row" style="margin-bottom: 15px;">' +
                                                        '<div class="col-md-12">' +
                                                        '<label><input type="radio" name="driver_type" value="1" checked> 取消取货司机订单</label>' +
                                                        '<div style="margin-left: 25px; margin-top: 5px;">' +
                                                        '<strong>司机姓名：</strong>' + (info.pickup_driver_name || '') + ' ' +
                                                        '<strong>司机电话：</strong>' + (info.pickup_driver_phone || '') +
                                                        '</div>' +
                                                        '</div>' +
                                                        '</div>';
                                                }
                                                if (hasShipment) {
                                                    html += '<div class="row" style="margin-bottom: 15px;">' +
                                                        '<div class="col-md-12">' +
                                                        '<label><input type="radio" name="driver_type" value="3"' + (!hasPickup ? ' checked' : '') + '> 取消送货司机订单</label>' +
                                                        '<div style="margin-left: 25px; margin-top: 5px;">' +
                                                        '<strong>司机姓名：</strong>' + (info.shipment_driver_name || '') + ' ' +
                                                        '<strong>司机电话：</strong>' + (info.shipment_driver_phone || '') +
                                                         '</div>' +
                                                         '</div>' +
                                                        '</div>';
                                                }
                                                html += '</div>';

                                                Layer.open({
                                                    type: 1,
                                                    title: '取消司机订单',
                                                    area: ['500px', 'auto'],
                                                    content: html,
                                                    btn: ['确认取消', '取消'],
                                                    yes: function(index) {
                                                        var driverType = $('input[name="driver_type"]:checked').val();
                                                        if (!driverType) {
                                                            Layer.msg('请选择要取消的司机订单', {icon: 2});
                                                            return;
                                                        }

                                                        // 执行取消司机订单
                                                        Backend.api.ajax({
                                                            url: 'admin/order/cancel_driver_order',
                                                            data: {
                                                                ids: row.id,
                                                                driver_type: driverType
                                                            }
                                                        }, function(data, ret) {
                                                            Layer.close(index);
                                                            Layer.alert(ret.msg || '取消成功', {icon: 1});
                                                            table.bootstrapTable('refresh');
                                                        }, function(data, ret) {
                                                            Layer.alert(ret.msg || '取消失败', {icon: 2});
                                                        });
                                                    }
                                                });
                                            } else {
                                                Layer.alert(ret.msg || '获取订单信息失败', {icon: 2});
                                            }
                                        }, function(data, ret) {
                                            Layer.alert(ret.msg || '获取订单信息失败', {icon: 2});
                                        });
                                    },
                                    dropdown: '更多'
                                },
                                {
                                    name: 'detail',
                                    text: __('修改订单总运费'),
                                    title: __('修改订单总运费'),
                                    classname: 'btn btn-xs btn-default btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'logistics/order_other_price',
                                    hidden:function(row){
                                        if (rowRejectedLockOps(row)) {
                                            return true;
                                        }
                                        // 已收货也显示，修改后需总后台审核
                                        return false;
                                    },
                                    dropdown: '更多'
                                },
                                {
                                    name: 'detail',
                                    text: __('修改订单成本'),
                                    title: __('修改订单成本'),
                                    classname: 'btn btn-xs btn-default btn-dialog',
                                    icon: 'fa fa-cny',
                                    url: 'logistics/cost_extra_price',
                                    hidden:function(row){
                                        if (rowRejectedLockOps(row)) {
                                            return true;
                                        }
                                        // 已收货也显示，修改后需总后台审核
                                        return false;
                                    },
                                    dropdown: '更多'
                                },
                                {
                                    name: 'detail',
                                    text: __('填写税点'),
                                    title: __('填写税点'),
                                    classname: 'btn btn-xs btn-default btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'logistics/cost_other_price',
                                    hidden:function(row){
                                        if (rowRejectedLockOps(row)) {
                                            return true;
                                        }
                                        if (!rowIsDispatchRole(row)) {
                                            return true;
                                        }
                                        // 已收货也显示，修改后需总后台审核
                                        return false;
                                    },
                                    dropdown: '更多'
                                },
                                {
                                    name: 'detail',
                                    text: __('上传装卸货图片'),
                                    title: __('上传装卸货图片'),
                                    classname: 'btn btn-xs btn-default btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'admin/order/upload_images',
                                    hidden: function (row) {
                                        if (rowRejectedLockOps(row)) {
                                            return true;
                                        }
                                        return false;
                                    },
                                    dropdown: '更多'
                                },
                                {
                                    name: 'click',
                                    text: __('查看电话'),
                                    title: __('查看电话'),
                                    classname: 'btn btn-xs btn-info btn-click',
                                    icon: 'fa fa-phone',
                                    hidden: function(row) {
                                        if (rowRejectedLockOps(row)) {
                                            return true;
                                        }
                                        if (!rowIsDispatchRole(row)) {
                                            return true;
                                        }
                                        return false;
                                    },
                                    click: function(options, row) {
                                        // 获取订单电话信息
                                        Backend.api.ajax({
                                            url: 'admin/order/get_order_phones',
                                            data: {ids: row.id}
                                        }, function(data, ret) {
                                            if (ret.code == 1) {
                                                var phones = ret.data;
                                                var html = '<div style="padding: 20px;">' +
                                                    '<h4 style="margin-bottom: 20px;">订单电话信息</h4>' +
                                                    '<div class="row" style="margin-bottom: 15px;">' +
                                                    '<div class="col-md-12"><strong>订单编号：</strong>' + (phones.orderid || '') + '</div>' +
                                                    '</div>' +
                                                    '<hr/>' +
                                                    '<div class="row" style="margin-bottom: 15px;">' +
                                                    '<div class="col-md-6"><strong>取货司机电话：</strong></div>' +
                                                    '<div class="col-md-6">' + (phones.pickup_driver_phone || '未分配') + '</div>' +
                                                    '</div>' +
                                                    '<div class="row" style="margin-bottom: 15px;">' +
                                                    '<div class="col-md-6"><strong>专线起点电话：</strong></div>' +
                                                    '<div class="col-md-6">' + (phones.logistics_start_phone || '未分配') + '</div>' +
                                                    '</div>' +
                                                    '<div class="row" style="margin-bottom: 15px;">' +
                                                    '<div class="col-md-6"><strong>专线终点电话：</strong></div>' +
                                                    '<div class="col-md-6">' + (phones.logistics_end_phone || '未分配') + '</div>' +
                                                    '</div>' +
                                                    '<div class="row" style="margin-bottom: 15px;">' +
                                                    '<div class="col-md-6"><strong>送货司机电话：</strong></div>' +
                                                    '<div class="col-md-6">' + (phones.shipment_driver_phone || '未分配') + '</div>' +
                                                    '</div>' +
                                                    '</div>';
                                                Layer.open({
                                                    type: 1,
                                                    title: '查看电话',
                                                    area: ['500px', 'auto'],
                                                    content: html,
                                                    btn: ['关闭'],
                                                    yes: function(index) {
                                                        Layer.close(index);
                                                     }
                                                });
                                            } else {
                                                Layer.alert(ret.msg || '获取电话信息失败', {icon: 2});
                                            }
                                        }, function(data, ret) {
                                            Layer.alert(ret.msg || '获取电话信息失败', {icon: 2});
                                        });
                                    },
                                    dropdown: '更多'
                                },
                                // {
                                //     name: 'detail',
                                //     text: __('问题反馈'),
                                //     title: __('问题反馈'),
                                //     classname: 'btn btn-xs btn-warning btn-dialog ',
                                //     icon: 'fa fa-exclamation-circle',
                                //     url: 'logistics/admincomm',
                                // },
                            ]
                        }

                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            // // 设置定时器每10秒刷新一次表格
            // setInterval(function() {
            //     table.bootstrapTable('refresh');
            // }, 10000);
        },
        // 更新统计信息显示（与前台订单列表一致，展示总收入/总支出/利润等）
        updateStatistics: function(statistics) {
            var $statsContainer = $('#order-statistics');
            if ($statsContainer.length === 0) {
                $statsContainer = $('<div id="order-statistics" class="order-statistics-panel"></div>');
                $('.toolbar').after($statsContainer);
            }

            var totalIncome = parseFloat(statistics.total_income || 0).toFixed(2);
            var totalExpense = parseFloat(statistics.total_expense || 0).toFixed(2);
            var totalProfit = parseFloat(statistics.total_profit || 0).toFixed(2);
            var totalWeight = parseFloat(statistics.total_weight || 0).toFixed(2);
            var totalVolume = parseFloat(statistics.total_volume || 0).toFixed(2);
            var totalOrders = parseInt(statistics.total_orders || 0, 10);

            var html = ''
                // 第一行：总收入 / 总支出 / 利润
                + '<div class="row">' +
                '<div class="col-md-4">' +
                '<div class="statistics-item income">' +
                '<div class="statistics-label">总收入</div>' +
                '<div class="statistics-value">¥' + totalIncome + '</div>' +
                '</div>' +
                '</div>' +
                '<div class="col-md-4">' +
                '<div class="statistics-item expense">' +
                '<div class="statistics-label">总支出</div>' +
                '<div class="statistics-value">¥' + totalExpense + '</div>' +
                '</div>' +
                '</div>' +
                '<div class="col-md-4">' +
                '<div class="statistics-item profit">' +
                '<div class="statistics-label">利润</div>' +
                '<div class="statistics-value ' + (parseFloat(totalProfit) >= 0 ? 'text-success' : 'text-danger') + '">¥' + totalProfit + '</div>' +
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
        upload_images: function () {
            Controller.api.bindevent();
        },
        user_orders: function () {
            var userId = $("#table").data("user-id");
            if (!userId) {
                Layer.msg('缺少用户ID');
                return;
            }
            var indexUrl = 'admin/order/user_orders?ids=' + userId;
            Table.api.init({
                extend: {
                    index_url: indexUrl,
                    table: 'admin_order',
                }
            });
            var table = $("#table");
            table.bootstrapTable({
                url: indexUrl,
                pk: 'id',
                sortName: 'id',
                responseHandler: function (res) {
                    var data = res && res.data ? res.data : res;
                    var rows = data ? (data.rows || data.list || data.items || data.data || []) : [];
                    if (!Array.isArray(rows) && typeof rows === 'object') {
                        rows = Object.values(rows);
                    }
                    var total = (data && (data.total || data.count || data.totalNum || data.total_count)) || rows.length || 0;
                    return { total: total, rows: rows };
                },
                columns: [
                    [
                        { checkbox: true }                                                                                                                ,
                        { field: 'id', title: __('Id') },
                        { field: 'order_id', title: __('订单编号') },
                        { field: 'find_car_type', title: __('找车类型') },
                        { field: 'pay_type_text', title: __('支付方式') },
                        { field: 'username', title: __('下单人') },
                        { field: 'createtime', title: __('下单时间'), formatter: Table.api.formatter.datetime },
                        { field: 'pay_status', title: __('支付状态'), formatter: Table.api.formatter.status },
                        { field: 'logistics_status', title: __('物流状态'), formatter: Table.api.formatter.status },
                        { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate,
                            formatter: function(value, row, index) {
                                var html = Table.api.formatter.operate.call(this, value, row, index);
                                return '<div class="operate-btns-wrap">' + (html || '') + '</div>';
                            },
                            buttons: [
                                {
                                    name: 'detail',
                                    text: __('查看订单信息'),
                                    title: __('查看订单信息'),
                                    classname: 'btn btn-xs btn-info btn-dialog',
                                    icon: 'fa fa-eye',
                                    url: 'admin/order/view'
                                }
                            ]
                        }
                    ]
                ]
            });
            Table.api.bindevent(table);
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };

    return Controller;
});

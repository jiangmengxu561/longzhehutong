define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'logistics/index' + location.search,
                    add_url: 'logistics/add',
                    edit_url: 'logistics/edit',
                    del_url: 'logistics/del',
                    multi_url: 'logistics/multi',
                    import_url: 'logistics/import',
                    export_url: 'logistics/export',
                    table: 'logistics',
                    showExport: true,
                }
            });

            var table = $("#table"); 
            var currentStatus = '';
            // 发货时间筛选：起止日期（可筛某一天、某个月或任意区间），空表示全部时间
            var statDateRange = {start: '', end: ''};
            var statFilterSnapshot = {filter: '', op: '', search: ''};
            // 切换审核状态标签时刷新列表，status=1 为审核中，status=2 为审核通过
            $(".nav-tabs a[data-status]").on('click', function () {
                currentStatus = $(this).data('status').toString();
                $(this).parent().addClass('active').siblings().removeClass('active');
                table.bootstrapTable('refresh', {pageNumber: 1});
            });
            // 判断当前登录用户是否是总后台（id == 1）
            // 直接从表格 data 属性中读取，和后端 $auth->id 保持一致
            var isCurrentUserSuperAdmin = function() {
                var flag = parseInt(table.data('is-super-admin'), 10) || 0;
                return flag === 1;
            };
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                showExport: true,
                fixedColumns: true,
                fixedRightNumber: 1,
                queryParams: function (params) {
                    params.status = currentStatus;
                    // 记录当前搜索/筛选条件，供统计卡片联动使用
                    statFilterSnapshot.filter = params.filter || '';
                    statFilterSnapshot.op = params.op || '';
                    statFilterSnapshot.search = params.search || '';
                    return params;
                },
                columns: [
                    [
                        {checkbox: true},
                        {
                            field: 'maintained',
                            title: __('Maintained'),
                            operate: '=',
                            searchList: {
                                0: __('Maintained 0'),
                                1: __('Maintained 1'),
                            },
                            formatter: Table.api.formatter.toggle,
                            table: table
                        },
                        {
                            field: 'level',
                            title: __('物流评级'),
                            operate: false,
                            searchList: { 0: '未评级', 5: '五星', 4: '四星', 3: '三星', 2: '二星', 1: '一星' },
                            formatter: function (value, row, index) {
                                var level = parseInt(value, 10) || 0;
                                var titles = { 1: '一星', 2: '二星', 3: '三星', 4: '四星', 5: '五星' };
                                var html = '<div class="logistics-level-wrap" data-id="' + row.id + '">';
                                for (var i = 1; i <= 5; i++) {
                                    var active = level >= i ? ' active' : '';
                                    html += '<button type="button" class="star-btn' + active + '" data-level="' + i + '" title="' + (titles[i] || '') + '">';
                                    html += '<i class="fa fa-star' + (level >= i ? '' : '-o') + '"></i>';
                                    html += '</button>';
                                }
                                html += '</div>';
                                return html;
                            },
                            events: {
                                'click .logistics-level-wrap .star-btn': function (e, value, row, index) {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    var wrap = $(e.currentTarget).closest('.logistics-level-wrap');
                                    var id = wrap.data('id');
                                    var level = $(e.currentTarget).data('level');
                                    Backend.api.ajax({
                                        url: 'logistics/set_level',
                                        data: { id: id, level: level }
                                    }, function (data, ret) {
                                        Layer.msg(ret.msg || '已更新');
                                        table.bootstrapTable('refresh');
                                    }, function (data, ret) {
                                        Layer.msg(ret.msg || '操作失败');
                                    });
                                }
                            }
                        },
                        {field: 'id', title: __('Id')},
                        {field: 'shipping_province', title: __('Shipping_province'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'origincity', title: __('Origincity'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'province', title: __('Province'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'destination', title: __('Destination'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'shipping_area', title: __('Shipping_area'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'shipping_logistics_park', title: __('Shipping_logistics_park'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'shipping_logistics_name', title: __('Shipping_logistics_name'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'shipping_logistics_address', title: __('Shipping_logistics_address'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'shipping_logistics_mobile', title: __('Shipping_logistics_mobile'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'shipping_longitude', title: __('发货区物流经度'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'shipping_latitude', title: __('发货区物流纬度'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'arrival_area', title: __('Arrival_area'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'arrival_logistics_park', title: __('Arrival_logistics_park'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'arrival_logistics_name', title: __('Arrival_logistics_name'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'arrival_logistics_address', title: __('Arrival_logistics_address'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'arrival_logistics_mobile', title: __('Arrival_logistics_mobile'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'arrival_longitude', title: __('到货区物流经度'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'arrival_latitude', title: __('到货区物流纬度'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'time_limit', title: __('Time_limit')},
                        {field: 'distance', title: __('Distance')},
                        {field: 'side', title: __('Side'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'perton', title: __('Perton'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'reflux', title: __('重抛'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'bulky', title: __('轻抛'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'remarks', title: __('Remarks'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'operator_display', title: '操作人', operate: false, formatter: function (v) { return v || '-'; }},
                        {field: 'franchise_display', title: '所属加盟商', operate: false, formatter: function (v) { return v || '-'; }},
                        {field: 'delete_operator_display', title: '删除申请人', operate: false, formatter: function (v) { return v || '-'; }},
                        {field: 'delete_franchise_display', title: '删除人加盟商', operate: false, formatter: function (v) { return v || '-'; }},
                        {field: 'operate', title: __('Operate'), table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'apply-delete',
                                    // 根据删除申请状态动态显示按钮文字
                                    text: function (row) {
                                        if (row.delete_status === 1) {
                                            return __('已申请');
                                        } else if (row.delete_status === 2) {
                                            return __('驳回重新申请');
                                        }
                                        return __('申请删除');
                                    },
                                    title: __('申请删除'),
                                    classname: 'btn btn-xs btn-primary btn-click',
                                    icon: 'fa fa-check',
                                    click: function (options, row) {
                                        // 若当前状态为已申请(1)，则执行“取消申请”（删除记录）
                                        if (row.delete_status === 1) {
                                            Layer.confirm('当前已申请删除，是否取消申请？', function(index) {
                                                Backend.api.ajax({
                                                    url: 'logistics/cancel_apply_delete',
                                                    data: {ids: row.id}
                                                }, function (data, ret) {
                                                    Layer.alert(ret.msg || '已取消删除申请');
                                                    table.bootstrapTable('refresh');
                                                }, function (data, ret) {
                                                    Layer.alert(ret.msg || '取消失败');
                                                });
                                                Layer.close(index);
                                            });
                                        } else {
                                            // 正常发起删除申请
                                            Layer.confirm('确定要申请删除嘛？', function(index) {
                                                Backend.api.ajax({
                                                    url: 'logistics/apply_delete',
                                                    data: {ids: row.id}
                                                }, function (data, ret) {
                                                    Layer.alert(ret.msg || '申请已提交');
                                                    table.bootstrapTable('refresh');
                                                }, function (data, ret) {
                                                    Layer.alert(ret.msg || '申请失败');
                                                });
                                                Layer.close(index);
                                            });
                                        }
                                    },
                                    hidden:function(row){
                                        // 总后台隐藏，其他子后台显示
                                        return isCurrentUserSuperAdmin();
                                    },
                                },
                                {
                                    name: 'approve-delete',
                                    text: __('同意删除'),
                                    title: __('同意删除'),
                                    classname: 'btn btn-xs btn-danger btn-click',
                                    icon: 'fa fa-trash',
                                    click: function (options, row) {
                                        Layer.confirm('确定同意删除该条记录吗？', function(index) {
                                            // 直接调用内置删除逻辑，删除成功后刷新表格
                                            Table.api.multi("del", row.id, table, this);
                                            Layer.close(index);
                                        });
                                    },
                                    hidden:function(row){
                                        // 仅总后台可见，且仅在存在待审核申请(status=0)时显示
                                        if (!isCurrentUserSuperAdmin()) {
                                            return true;
                                        }
                                        return row.delete_status !== 1;
                                    }
                                }
                                ]
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            // 为删除按钮添加二次确认弹窗
            table.on("click", "[data-id].btn-del", function (e) {
                e.preventDefault();
                e.stopPropagation(); // 阻止默认事件传播
                var id = $(this).data("id");
                var that = this;
                // 第一次确认
                Layer.confirm(
                    __('确定要删除这条记录吗？'),
                    {icon: 3, title: __('提示'), shadeClose: true, btn: [__('确定'), __('取消')]},
                    function (index) {
                        Layer.close(index);
                        // 第二次确认
                        Layer.confirm(
                            __('请再次确认，删除后无法恢复！'),
                            {icon: 0, title: __('警告'), shadeClose: true, btn: [__('确定删除'), __('取消')]},
                            function (secondIndex) {
                                Table.api.multi("del", id, table, that);
                                Layer.close(secondIndex);
                            }
                        );
                    }
                );
            });

            var buildExportParams = function (extra) {
                extra = extra || {};
                var options = table.bootstrapTable('getOptions');
                var baseParams = {
                    search: options.searchText,
                    sort: options.sortName,
                    order: options.sortOrder,
                    offset: 0,
                    limit: 0
                };
                if (typeof options.queryParams === 'function') {
                    baseParams = options.queryParams(baseParams) || baseParams;
                }
                return $.extend({}, baseParams, extra);
            };

            $("#toolbar").on('click', '.btn-export', function () {
                var type = $(this).data('type');
                var ids = [];
                if (type === 'selected') {
                    ids = Table.api.selectedids(table);
                    if (!ids.length) {
                        Layer.msg(__('请先选择要导出的记录'));
                        return;
                    }
                }
                var params = buildExportParams(ids.length ? {ids: ids.join(',')} : {});
                var query = $.param(params);
                var url = $.fn.bootstrapTable.defaults.extend.export_url || 'logistics/export';
                window.open(Fast.api.fixurl(url) + (query ? '?' + query : ''));
            });

            // ===== 物流统计卡片（发货单量/总干线费/总吨数/总方位） =====
            var formatStatNumber = function (val, digits) {
                digits = digits || 0;
                if (val === null || val === undefined || val === '') return '0';
                var num = parseFloat(val);
                if (isNaN(num)) return '0';
                return num.toLocaleString('zh-CN', {
                    minimumFractionDigits: digits,
                    maximumFractionDigits: digits
                });
            };
            var loadLogisticsStatistics = function () {
                Backend.api.ajax({
                    url: 'logistics/statistics',
                    data: {
                        start_date: statDateRange.start,
                        end_date: statDateRange.end,
                        filter: statFilterSnapshot.filter,
                        op: statFilterSnapshot.op,
                        search: statFilterSnapshot.search
                    }
                }, function (data, ret) {
                    var res = ret.data || {};
                    console.log('[logistics statistics]', statDateRange, ret);
                    $('#logisticsStatShipmentCount').text(res.shipment_count != null ? res.shipment_count : 0);
                    $('#logisticsStatTrunkFee').text(formatStatNumber(res.trunk_fee, 2));
                    $('#logisticsStatTonnage').text(formatStatNumber(res.tonnage, 2));
                    $('#logisticsStatVolume').text(formatStatNumber(res.volume, 2));
                    // 关闭成功后的默认 Toast 提示
                    return false;
                }, function (data, ret) {
                    console.error('[logistics statistics] 请求失败', ret);
                    Layer.msg((ret && ret.msg) ? ret.msg : '物流统计数据加载失败', {icon: 2});
                });
            };
            // 发货时间：原生日期选择（无需第三方组件，点击即弹日历），支持某天/某月/任意区间
            var $statStart = $('#logisticsStatStartDate');
            var $statEnd = $('#logisticsStatEndDate');
            var pad2 = function (n) {
                return (n < 10 ? '0' : '') + n;
            };
            var formatDate = function (date) {
                return date.getFullYear() + '-' + pad2(date.getMonth() + 1) + '-' + pad2(date.getDate());
            };
            // 点击输入框直接弹出系统日历（Chrome/Edge/Firefox 支持 showPicker）
            var openDatePicker = function (el) {
                if (el && typeof el.showPicker === 'function') {
                    try {
                        el.showPicker();
                    } catch (e) {
                        // 需要用户手势或被浏览器拒绝时忽略，用户仍可点右侧日历图标选择
                    }
                }
            };
            var setStatDateRange = function (start, end) {
                statDateRange = {start: start || '', end: end || ''};
                $statStart.val(statDateRange.start);
                $statEnd.val(statDateRange.end);
            };
            // 以输入框当前值为准（起止写反自动交换），然后刷新卡片
            var applyStatDateRangeFromInputs = function () {
                var start = $statStart.val() || '';
                var end = $statEnd.val() || '';
                if (start !== '' && end !== '' && start > end) {
                    var tmp = start;
                    start = end;
                    end = tmp;
                }
                setStatDateRange(start, end);
                loadLogisticsStatistics();
            };
            $statStart.on('click focus', function () {
                openDatePicker(this);
            }).on('change', applyStatDateRangeFromInputs);
            $statEnd.on('click focus', function () {
                openDatePicker(this);
            }).on('change', applyStatDateRangeFromInputs);
            $('#logisticsStatQueryBtn').on('click', applyStatDateRangeFromInputs);
            // 快捷区间：今天/本月/上月/今年
            $('.logistics-stat-quick').on('click', function () {
                var now = new Date();
                var year = now.getFullYear();
                var month = now.getMonth();
                var quick = $(this).data('quick');
                if (quick === 'today') {
                    setStatDateRange(formatDate(now), formatDate(now));
                } else if (quick === 'month') {
                    setStatDateRange(formatDate(new Date(year, month, 1)), formatDate(new Date(year, month + 1, 0)));
                } else if (quick === 'last_month') {
                    setStatDateRange(formatDate(new Date(year, month - 1, 1)), formatDate(new Date(year, month, 0)));
                } else if (quick === 'year') {
                    setStatDateRange(year + '-01-01', year + '-12-31');
                }
                loadLogisticsStatistics();
            });
            // 全部时间：清空起止日期
            $('#logisticsStatClearBtn').on('click', function () {
                setStatDateRange('', '');
                loadLogisticsStatistics();
            });
            // 表格每次加载数据（含搜索/切页/切换审核状态）后，按当前筛选刷新卡片
            table.on('load-success.bs.table', function () {
                loadLogisticsStatistics();
            });
            // 默认与原来一致：本月
            (function () {
                var now = new Date();
                var year = now.getFullYear();
                var month = now.getMonth();
                setStatDateRange(formatDate(new Date(year, month, 1)), formatDate(new Date(year, month + 1, 0)));
            })();
            loadLogisticsStatistics();
        },
        add: function () {
            Controller.api.bindevent();
        },
        franchiseLogisticsAdd: function () {
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

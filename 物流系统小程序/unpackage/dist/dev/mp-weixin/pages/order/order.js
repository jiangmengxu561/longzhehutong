(global["webpackJsonp"] = global["webpackJsonp"] || []).push([["pages/order/order"],{

/***/ 70:
/*!***********************************************************************************************!*\
  !*** D:/phpstudy_pro/WWW/longzhehutongdaima/物流系统小程序/main.js?{"page":"pages%2Forder%2Forder"} ***!
  \***********************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
/* WEBPACK VAR INJECTION */(function(wx, createPage) {

var _interopRequireDefault = __webpack_require__(/*! @babel/runtime/helpers/interopRequireDefault */ 4);
__webpack_require__(/*! uni-pages */ 30);
var _vue = _interopRequireDefault(__webpack_require__(/*! vue */ 25));
var _order = _interopRequireDefault(__webpack_require__(/*! ./pages/order/order.vue */ 71));
// @ts-ignore
wx.__webpack_require_UNI_MP_PLUGIN__ = __webpack_require__;
createPage(_order.default);
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! ./node_modules/@dcloudio/uni-mp-weixin/dist/wx.js */ 1)["default"], __webpack_require__(/*! ./node_modules/@dcloudio/uni-mp-weixin/dist/index.js */ 2)["createPage"]))

/***/ }),

/***/ 71:
/*!****************************************************************************!*\
  !*** D:/phpstudy_pro/WWW/longzhehutongdaima/物流系统小程序/pages/order/order.vue ***!
  \****************************************************************************/
/*! no static exports found */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _order_vue_vue_type_template_id_127632e4___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./order.vue?vue&type=template&id=127632e4& */ 72);
/* harmony import */ var _order_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./order.vue?vue&type=script&lang=js& */ 74);
/* harmony reexport (unknown) */ for(var __WEBPACK_IMPORT_KEY__ in _order_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__) if(["default"].indexOf(__WEBPACK_IMPORT_KEY__) < 0) (function(key) { __webpack_require__.d(__webpack_exports__, key, function() { return _order_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__[key]; }) }(__WEBPACK_IMPORT_KEY__));
/* harmony import */ var _order_vue_vue_type_style_index_0_lang_css___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./order.vue?vue&type=style&index=0&lang=css& */ 76);
/* harmony import */ var _HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../../../../../HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/vue-cli-plugin-uni/packages/vue-loader/lib/runtime/componentNormalizer.js */ 36);

var renderjs





/* normalize component */

var component = Object(_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _order_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _order_vue_vue_type_template_id_127632e4___WEBPACK_IMPORTED_MODULE_0__["render"],
  _order_vue_vue_type_template_id_127632e4___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"],
  false,
  null,
  null,
  null,
  false,
  _order_vue_vue_type_template_id_127632e4___WEBPACK_IMPORTED_MODULE_0__["components"],
  renderjs
)

component.options.__file = "pages/order/order.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ 72:
/*!***********************************************************************************************************!*\
  !*** D:/phpstudy_pro/WWW/longzhehutongdaima/物流系统小程序/pages/order/order.vue?vue&type=template&id=127632e4& ***!
  \***********************************************************************************************************/
/*! exports provided: render, staticRenderFns, recyclableRender, components */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_17_0_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_template_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_uni_app_loader_page_meta_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_order_vue_vue_type_template_id_127632e4___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/vue-cli-plugin-uni/packages/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../../../HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/vue-cli-plugin-uni/packages/webpack-preprocess-loader??ref--17-0!../../../../../../HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/webpack-uni-mp-loader/lib/template.js!../../../../../../HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/vue-cli-plugin-uni/packages/webpack-uni-app-loader/page-meta.js!../../../../../../HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/vue-cli-plugin-uni/packages/vue-loader/lib??vue-loader-options!../../../../../../HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/webpack-uni-mp-loader/lib/style.js!./order.vue?vue&type=template&id=127632e4& */ 73);
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "render", function() { return _HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_17_0_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_template_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_uni_app_loader_page_meta_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_order_vue_vue_type_template_id_127632e4___WEBPACK_IMPORTED_MODULE_0__["render"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "staticRenderFns", function() { return _HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_17_0_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_template_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_uni_app_loader_page_meta_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_order_vue_vue_type_template_id_127632e4___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "recyclableRender", function() { return _HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_17_0_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_template_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_uni_app_loader_page_meta_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_order_vue_vue_type_template_id_127632e4___WEBPACK_IMPORTED_MODULE_0__["recyclableRender"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "components", function() { return _HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_17_0_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_template_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_uni_app_loader_page_meta_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_order_vue_vue_type_template_id_127632e4___WEBPACK_IMPORTED_MODULE_0__["components"]; });



/***/ }),

/***/ 73:
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/@dcloudio/vue-cli-plugin-uni/packages/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/@dcloudio/vue-cli-plugin-uni/packages/webpack-preprocess-loader??ref--17-0!./node_modules/@dcloudio/webpack-uni-mp-loader/lib/template.js!./node_modules/@dcloudio/vue-cli-plugin-uni/packages/webpack-uni-app-loader/page-meta.js!./node_modules/@dcloudio/vue-cli-plugin-uni/packages/vue-loader/lib??vue-loader-options!./node_modules/@dcloudio/webpack-uni-mp-loader/lib/style.js!D:/phpstudy_pro/WWW/longzhehutongdaima/物流系统小程序/pages/order/order.vue?vue&type=template&id=127632e4& ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/*! exports provided: render, staticRenderFns, recyclableRender, components */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "render", function() { return render; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "staticRenderFns", function() { return staticRenderFns; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "recyclableRender", function() { return recyclableRender; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "components", function() { return components; });
var components
var render = function () {
  var _vm = this
  var _h = _vm.$createElement
  var _c = _vm._self._c || _h
  var g0 = _vm.showDeviceModal ? _vm.sortedDevices.length : null
  if (!_vm._isMounted) {
    _vm.e0 = function ($event, item) {
      var _temp = arguments[arguments.length - 1].currentTarget.dataset,
        _temp2 = _temp.eventParams || _temp["event-params"],
        item = _temp2.item
      var _temp, _temp2
      $event.stopPropagation()
      _vm.isConnected ? _vm.printOrder(item) : _vm.openConnectPrinter()
    }
  }
  _vm.$mp.data = Object.assign(
    {},
    {
      $root: {
        g0: g0,
      },
    }
  )
}
var recyclableRender = false
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ 74:
/*!*****************************************************************************************************!*\
  !*** D:/phpstudy_pro/WWW/longzhehutongdaima/物流系统小程序/pages/order/order.vue?vue&type=script&lang=js& ***!
  \*****************************************************************************************************/
/*! no static exports found */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _HBuilderX_plugins_uniapp_cli_node_modules_babel_loader_lib_index_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_13_1_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_script_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_order_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../HBuilderX/plugins/uniapp-cli/node_modules/babel-loader/lib!../../../../../../HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/vue-cli-plugin-uni/packages/webpack-preprocess-loader??ref--13-1!../../../../../../HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/webpack-uni-mp-loader/lib/script.js!../../../../../../HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/vue-cli-plugin-uni/packages/vue-loader/lib??vue-loader-options!../../../../../../HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/webpack-uni-mp-loader/lib/style.js!./order.vue?vue&type=script&lang=js& */ 75);
/* harmony import */ var _HBuilderX_plugins_uniapp_cli_node_modules_babel_loader_lib_index_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_13_1_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_script_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_order_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_HBuilderX_plugins_uniapp_cli_node_modules_babel_loader_lib_index_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_13_1_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_script_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_order_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__);
/* harmony reexport (unknown) */ for(var __WEBPACK_IMPORT_KEY__ in _HBuilderX_plugins_uniapp_cli_node_modules_babel_loader_lib_index_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_13_1_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_script_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_order_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__) if(["default"].indexOf(__WEBPACK_IMPORT_KEY__) < 0) (function(key) { __webpack_require__.d(__webpack_exports__, key, function() { return _HBuilderX_plugins_uniapp_cli_node_modules_babel_loader_lib_index_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_13_1_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_script_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_order_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__[key]; }) }(__WEBPACK_IMPORT_KEY__));
 /* harmony default export */ __webpack_exports__["default"] = (_HBuilderX_plugins_uniapp_cli_node_modules_babel_loader_lib_index_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_13_1_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_script_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_order_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0___default.a); 

/***/ }),

/***/ 75:
/*!************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib!./node_modules/@dcloudio/vue-cli-plugin-uni/packages/webpack-preprocess-loader??ref--13-1!./node_modules/@dcloudio/webpack-uni-mp-loader/lib/script.js!./node_modules/@dcloudio/vue-cli-plugin-uni/packages/vue-loader/lib??vue-loader-options!./node_modules/@dcloudio/webpack-uni-mp-loader/lib/style.js!D:/phpstudy_pro/WWW/longzhehutongdaima/物流系统小程序/pages/order/order.vue?vue&type=script&lang=js& ***!
  \************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
/* WEBPACK VAR INJECTION */(function(uni, wx) {

var _interopRequireDefault = __webpack_require__(/*! @babel/runtime/helpers/interopRequireDefault */ 4);
Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;
var _toConsumableArray2 = _interopRequireDefault(__webpack_require__(/*! @babel/runtime/helpers/toConsumableArray */ 18));
var _mixin = __webpack_require__(/*! @/https/mixin.js */ 59);
function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it.return != null) it.return(); } finally { if (didErr) throw err; } } }; }
function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }
var _default = {
  mixins: [_mixin.systemInfo],
  data: function data() {
    return {
      imgUrl: this.$utils.imgUrl,
      scrollTop: 0,
      types: 0,
      list: [],
      membertype: null,
      // 用户类型：1=普通用户，2=兼职员工，3=正式员工
      // 蓝牙相关
      bluetoothAdapter: null,
      deviceId: '',
      serviceId: '',
      characteristicId: '',
      isConnected: false,
      devices: [],
      showDeviceModal: false,
      sortedDevices: [],
      printType: 'simple' // 打印类型: simple(简版), receipt(签收单)
    };
  },
  onLoad: function onLoad() {
    uni.setStorageSync('zaiOrder', '');
    this.getSystemInfo();
    this.getUserInfo();
  },
  onUnload: function onUnload() {
    if (this.isConnected) {
      this.closeBluetooth();
    }
  },
  onShow: function onShow() {
    uni.setStorageSync('zaiOrder', '');
    this.loads();
  },
  onPullDownRefresh: function onPullDownRefresh() {
    this.list = [];
    this.loads();
  },
  onPageScroll: function onPageScroll(event) {
    this.scrollTop = event.scrollTop;
  },
  methods: {
    // 获取用户信息，判断是否为正式员工
    getUserInfo: function getUserInfo() {
      var _this = this;
      this.$httpapi('Opinion/getuserinfo', 'GET', {}).then(function (res) {
        if (res.data && res.data.membertype !== undefined) {
          _this.membertype = res.data.membertype;
          // 只有正式员工（membertype === 3）才初始化蓝牙打印机
          if (_this.membertype === 4) {
            _this.initBluetooth();
          }
        } else {
          // 如果获取不到用户信息，默认不连接打印机
          _this.membertype = null;
        }
      }).catch(function (err) {
        console.log('获取用户信息失败', err);
        // 获取失败时，默认不连接打印机
        _this.membertype = null;
      });
    },
    chonglai: function chonglai(item) {
      this.$httpapi('Placeorder/orderdetaile', 'POST', {
        order_id: item.orderid
      }).then(function (rex) {
        uni.setStorageSync('zaiOrder', rex.data);
        uni.switchTab({
          url: '/pages/index/index'
        });
      });
    },
    // 修改订单：直接打开修改订单表单页，带 order_id，不再经 editOrder 中转
    xiugaiOrder: function xiugaiOrder(item) {
      var orderId = item.orderid || item.id;
      uni.navigateTo({
        url: '/pages/order/orderForm/orderForm?order_id=' + orderId
      });
    },
    quxiao: function quxiao(item) {
      var _this2 = this;
      this.$httpapi('Placeorder/CancelOrder', 'POST', {
        order_id: item.orderid
      }).then(function (res) {
        uni.showToast({
          title: res.msg,
          duration: 1500,
          icon: 'none'
        });
        _this2.loads();
      });
    },
    // 删除订单（仅已取消的订单显示删除按钮）
    deleteOrder: function deleteOrder(item, index) {
      var _this3 = this;
      uni.showModal({
        title: '提示',
        content: '确定要删除该订单吗？',
        success: function success(res) {
          if (res.confirm) {
            _this3.$httpapi('Placeorder/DeleteOrder', 'POST', {
              order_id: item.orderid
            }).then(function (res) {
              uni.showToast({
                title: res.msg || '删除成功',
                icon: 'none'
              });
              _this3.loads();
            }).catch(function (err) {
              uni.showToast({
                title: err.msg || '删除失败',
                icon: 'none'
              });
            });
          }
        }
      });
    },
    queren: function queren(item) {
      var _this4 = this;
      this.$httpapi('Placeorder/cancel_price', 'POST', {
        order_id: item.orderid
      }).then(function (res) {
        uni.showToast({
          title: res.msg,
          duration: 1500,
          icon: 'none'
        });
        _this4.loads();
      });
    },
    fukuan: function fukuan(item) {
      var that = this;
      this.$httpapi('Wechatpay/pay', 'POST', {
        order_id: item.orderid
      }).then(function (res) {
        wx.requestPayment({
          provider: 'wxpay',
          timeStamp: res.data.timeStamp,
          nonceStr: res.data.nonceStr,
          package: res.data.package,
          signType: res.data.signType,
          paySign: res.data.paySign,
          success: function success(res) {
            wx.showToast({
              title: '支付成功',
              icon: 'success'
            });
            that.loads();
          },
          fail: function fail(err) {
            wx.showToast({
              title: '支付失败',
              icon: 'error'
            });
          }
        });
      });
    },
    details: function details(item) {
      uni.navigateTo({
        url: '/pages/order/orderDetail/orderDetail?id=' + item.orderid
      });
    },
    qiehuans: function qiehuans(type) {
      this.types = type;
      this.loads();
    },
    loads: function loads() {
      var _this5 = this;
      this.$httpapi('Placeorder/orderlist', 'POST', {
        type: this.types
      }).then(function (res) {
        uni.stopPullDownRefresh();
        _this5.list = res.data;
      });
    },
    // ========== 蓝牙打印相关方法 ==========
    initBluetooth: function initBluetooth() {
      var _this6 = this;
      uni.openBluetoothAdapter({
        success: function success(res) {
          console.log('蓝牙初始化成功');
          var savedDeviceId = uni.getStorageSync('bluetoothDeviceId');
          if (savedDeviceId) {
            _this6.deviceId = savedDeviceId;
            _this6.connectBluetooth(savedDeviceId);
          }
        },
        fail: function fail(err) {
          console.error('蓝牙初始化失败', err);
          if (err.errCode === 10001) {
            uni.showToast({
              title: '请打开手机蓝牙',
              icon: 'none'
            });
          }
        }
      });
    },
    // 微信里部分机型在 openBluetoothAdapter 成功后立刻 getBluetoothAdapterState 会误报 available=false，导致一直提示「蓝牙不可用」。此处改为：打开适配器成功 →（安卓）位置授权 → 直接搜设备。
    isBluetoothAuthDenied: function isBluetoothAuthDenied(err) {
      if (!err) return false;
      var msg = (err.errMsg || '') + '';
      if (err.errno === 103 || err.errCode === 103) return true;
      if (msg.indexOf('auth deny') !== -1) return true;
      return false;
    },
    formatOpenBluetoothError: function formatOpenBluetoothError(err) {
      if (!err) return '无法打开蓝牙，请重试';
      if (err.errCode === 10001) return '请先打开手机系统蓝牙';
      if (this.isBluetoothAuthDenied(err)) return '微信蓝牙权限被拒绝';
      var msg = (err.errMsg || '').replace(/^openBluetoothAdapter:fail\s*/i, '');
      return msg && msg.length < 40 ? msg : '无法使用蓝牙，请检查蓝牙与微信权限';
    },
    runAfterAdapterOpenForBleScan: function runAfterAdapterOpenForBleScan(next) {
      var platform = '';
      try {
        platform = (uni.getSystemInfoSync().platform || '').toLowerCase();
      } catch (e) {
        next();
        return;
      }
      if (platform !== 'android') {
        next();
        return;
      }
      uni.getSetting({
        success: function success(st) {
          if (st.authSetting && st.authSetting['scope.userLocation']) {
            next();
            return;
          }
          uni.authorize({
            scope: 'scope.userLocation',
            success: function success() {
              return next();
            },
            fail: function fail() {
              uni.showModal({
                title: '需要位置权限',
                content: '安卓手机搜索蓝牙打印机需要「位置信息」权限（微信对蓝牙扫描的要求），请允许或到设置中开启。',
                confirmText: '去设置',
                cancelText: '取消',
                success: function success(r) {
                  if (r.confirm) uni.openSetting({});
                }
              });
            }
          });
        },
        fail: function fail() {
          return next();
        }
      });
    },
    openAdapterAndStartSearch: function openAdapterAndStartSearch() {
      var _this7 = this;
      uni.openBluetoothAdapter({
        success: function success() {
          _this7.runAfterAdapterOpenForBleScan(function () {
            _this7.startDiscovery();
          });
        },
        fail: function fail(err) {
          console.error('openBluetoothAdapter fail', err);
          if (_this7.isBluetoothAuthDenied(err)) {
            uni.showModal({
              title: '蓝牙权限被拒绝',
              content: '请在系统设置里为「微信」打开蓝牙权限；也可点「去设置」进入小程序权限页尝试开启。',
              confirmText: '去设置',
              cancelText: '取消',
              success: function success(r) {
                if (r.confirm) {
                  if (typeof wx !== 'undefined' && wx.openAppAuthorizeSetting) {
                    wx.openAppAuthorizeSetting({});
                  } else {
                    uni.openSetting({});
                  }
                }
              }
            });
            return;
          }
          uni.showToast({
            title: _this7.formatOpenBluetoothError(err),
            icon: 'none',
            duration: 3000
          });
        }
      });
    },
    // 点击「连接」时直接打开设备列表并开始搜索（不再弹二次确认）
    openConnectPrinter: function openConnectPrinter() {
      this.openAdapterAndStartSearch();
    },
    searchBluetoothDevices: function searchBluetoothDevices() {
      this.openAdapterAndStartSearch();
    },
    startDiscovery: function startDiscovery() {
      var _this8 = this;
      uni.showLoading({
        title: '搜索设备中...',
        mask: true
      });
      this.devices = [];
      this.showDeviceModal = true;
      uni.offBluetoothDeviceFound();
      uni.onBluetoothDeviceFound(function (res) {
        if (res.devices && res.devices.length > 0) {
          res.devices.forEach(function (device) {
            if (!_this8.devices.find(function (d) {
              return d.deviceId === device.deviceId;
            })) {
              _this8.devices.push(device);
            }
          });
          _this8.sortDevices();
        }
      });
      uni.stopBluetoothDevicesDiscovery({
        success: function success() {
          _this8.doStartDiscovery();
        },
        fail: function fail() {
          _this8.doStartDiscovery();
        }
      });
    },
    doStartDiscovery: function doStartDiscovery() {
      var _this9 = this;
      uni.startBluetoothDevicesDiscovery({
        allowDuplicatesKey: false,
        interval: 0,
        success: function success(res) {
          setTimeout(function () {
            uni.getBluetoothDevices({
              success: function success(res) {
                if (res.devices && res.devices.length > 0) {
                  res.devices.forEach(function (device) {
                    if (!_this9.devices.find(function (d) {
                      return d.deviceId === device.deviceId;
                    })) {
                      _this9.devices.push(device);
                    }
                  });
                  _this9.sortDevices();
                }
              }
            });
          }, 1000);
          setTimeout(function () {
            uni.stopBluetoothDevicesDiscovery();
            uni.hideLoading();
            if (_this9.devices.length === 0) {
              uni.showModal({
                title: '未找到设备',
                content: '未找到蓝牙打印机\n请检查打印机是否开启',
                showCancel: false
              });
            }
          }, 10000);
        },
        fail: function fail(err) {
          uni.hideLoading();
          var errorMsg = '搜索失败';
          if (err.errCode === 10001) errorMsg = '请打开手机蓝牙';else if (err.errCode === 10012) errorMsg = '蓝牙未初始化';
          uni.showModal({
            title: errorMsg,
            content: '错误代码：' + (err.errCode || '未知'),
            showCancel: false
          });
        }
      });
    },
    sortDevices: function sortDevices() {
      this.sortedDevices = (0, _toConsumableArray2.default)(this.devices).sort(function (a, b) {
        if (a.connectable && !b.connectable) return -1;
        if (!a.connectable && b.connectable) return 1;
        if (a.name && a.name !== '未知设备' && (!b.name || b.name === '未知设备')) return -1;
        if ((!a.name || a.name === '未知设备') && b.name && b.name !== '未知设备') return 1;
        if (a.RSSI && b.RSSI) return b.RSSI - a.RSSI;
        return 0;
      });
    },
    selectDevice: function selectDevice(device) {
      if (!device.connectable) {
        uni.showToast({
          title: '设备不支持连接',
          icon: 'none'
        });
        return;
      }
      this.closeDeviceModal();
      this.connectBluetooth(device.deviceId);
    },
    closeDeviceModal: function closeDeviceModal() {
      this.showDeviceModal = false;
    },
    connectBluetooth: function connectBluetooth(deviceId) {
      var _this10 = this;
      if (!deviceId) {
        this.searchBluetoothDevices();
        return;
      }
      var device = this.devices.find(function (d) {
        return d.deviceId === deviceId;
      });
      if (device && device.connectable === false) {
        uni.showModal({
          title: '无法连接',
          content: '该设备不支持连接',
          showCancel: false
        });
        return;
      }
      uni.showLoading({
        title: '连接中...',
        mask: true
      });
      uni.stopBluetoothDevicesDiscovery({
        success: function success() {
          _this10.doConnect(deviceId);
        },
        fail: function fail() {
          _this10.doConnect(deviceId);
        }
      });
    },
    doConnect: function doConnect(deviceId) {
      var _this11 = this;
      if (this.deviceId && this.deviceId !== deviceId) {
        uni.closeBLEConnection({
          deviceId: this.deviceId,
          success: function success() {
            _this11.connectDevice(deviceId);
          },
          fail: function fail() {
            _this11.connectDevice(deviceId);
          }
        });
      } else {
        this.connectDevice(deviceId);
      }
    },
    connectDevice: function connectDevice(deviceId) {
      var _this12 = this;
      uni.offBLEConnectionStateChange();
      uni.onBLEConnectionStateChange(function (res) {
        if (res.deviceId === deviceId) {
          _this12.isConnected = res.connected;
          if (!res.connected) {
            uni.showToast({
              title: '蓝牙已断开',
              icon: 'none'
            });
          }
        }
      });
      uni.createBLEConnection({
        deviceId: deviceId,
        timeout: 10000,
        success: function success(res) {
          _this12.deviceId = deviceId;
          _this12.isConnected = true;
          uni.setStorageSync('bluetoothDeviceId', deviceId);
          setTimeout(function () {
            _this12.getBLEDeviceServices(deviceId);
          }, 1000);
        },
        fail: function fail(err) {
          uni.hideLoading();
          var errorMsg = '连接失败';
          if (err.errCode === 10003) errorMsg = '设备未找到';else if (err.errCode === 10004) errorMsg = '连接超时';else if (err.errCode === 10007) errorMsg = '连接失败';
          uni.showModal({
            title: errorMsg,
            content: '设备ID: ' + deviceId.substring(0, 17),
            confirmText: '重试',
            cancelText: '取消',
            success: function success(res) {
              if (res.confirm) {
                _this12.connectBluetooth(deviceId);
              }
            }
          });
          uni.removeStorageSync('bluetoothDeviceId');
          _this12.deviceId = '';
          _this12.isConnected = false;
        }
      });
    },
    getBLEDeviceServices: function getBLEDeviceServices(deviceId) {
      var _this13 = this;
      uni.getBLEDeviceServices({
        deviceId: deviceId,
        success: function success(res) {
          var services = res.services;
          if (!services || services.length === 0) {
            uni.hideLoading();
            uni.showModal({
              title: '未找到服务',
              content: '设备没有可用服务',
              showCancel: false
            });
            return;
          }
          var serviceId = '';
          var printServiceUUIDs = ['0000FFE0-0000-1000-8000-00805F9B34FB', 'FFE0', '0000180F-0000-1000-8000-00805F9B34FB'];
          var _iterator = _createForOfIteratorHelper(services),
            _step;
          try {
            var _loop = function _loop() {
              var service = _step.value;
              var uuid = service.uuid.toUpperCase();
              if (printServiceUUIDs.some(function (printUUID) {
                return uuid.includes(printUUID.toUpperCase());
              })) {
                serviceId = service.uuid;
                return "break";
              }
            };
            for (_iterator.s(); !(_step = _iterator.n()).done;) {
              var _ret = _loop();
              if (_ret === "break") break;
            }
          } catch (err) {
            _iterator.e(err);
          } finally {
            _iterator.f();
          }
          if (!serviceId) {
            var _iterator2 = _createForOfIteratorHelper(services),
              _step2;
            try {
              for (_iterator2.s(); !(_step2 = _iterator2.n()).done;) {
                var service = _step2.value;
                if (service.isPrimary) {
                  serviceId = service.uuid;
                  break;
                }
              }
            } catch (err) {
              _iterator2.e(err);
            } finally {
              _iterator2.f();
            }
          }
          if (!serviceId && services.length > 0) {
            serviceId = services[0].uuid;
          }
          _this13.serviceId = serviceId;
          setTimeout(function () {
            _this13.getBLEDeviceCharacteristics(deviceId, serviceId);
          }, 300);
        },
        fail: function fail(err) {
          uni.hideLoading();
          uni.showModal({
            title: '获取服务失败',
            content: '错误代码: ' + (err.errCode || '未知'),
            confirmText: '重新连接',
            cancelText: '取消',
            success: function success(res) {
              if (res.confirm) {
                _this13.connectBluetooth(deviceId);
              }
            }
          });
        }
      });
    },
    getBLEDeviceCharacteristics: function getBLEDeviceCharacteristics(deviceId, serviceId) {
      var _this14 = this;
      uni.getBLEDeviceCharacteristics({
        deviceId: deviceId,
        serviceId: serviceId,
        success: function success(res) {
          var characteristics = res.characteristics;
          if (!characteristics || characteristics.length === 0) {
            uni.hideLoading();
            uni.showModal({
              title: '未找到特征值',
              content: '服务没有可用特征值',
              showCancel: false
            });
            return;
          }
          var characteristicId = '';
          var printCharUUIDs = ['0000FFE1-0000-1000-8000-00805F9B34FB', 'FFE1'];
          var _iterator3 = _createForOfIteratorHelper(characteristics),
            _step3;
          try {
            var _loop2 = function _loop2() {
              var char = _step3.value;
              var uuid = char.uuid.toUpperCase();
              if (printCharUUIDs.some(function (printUUID) {
                return uuid.includes(printUUID.toUpperCase());
              })) {
                if (char.properties.write || char.properties.writeNoResponse) {
                  characteristicId = char.uuid;
                  return "break";
                }
              }
            };
            for (_iterator3.s(); !(_step3 = _iterator3.n()).done;) {
              var _ret2 = _loop2();
              if (_ret2 === "break") break;
            }
          } catch (err) {
            _iterator3.e(err);
          } finally {
            _iterator3.f();
          }
          if (!characteristicId) {
            var _iterator4 = _createForOfIteratorHelper(characteristics),
              _step4;
            try {
              for (_iterator4.s(); !(_step4 = _iterator4.n()).done;) {
                var char = _step4.value;
                if (char.properties.write || char.properties.writeNoResponse) {
                  characteristicId = char.uuid;
                  break;
                }
              }
            } catch (err) {
              _iterator4.e(err);
            } finally {
              _iterator4.f();
            }
          }
          if (!characteristicId && characteristics.length > 0) {
            characteristicId = characteristics[0].uuid;
          }
          _this14.characteristicId = characteristicId;
          uni.hideLoading();
          uni.showToast({
            title: '连接成功！',
            icon: 'success',
            duration: 2000
          });
        },
        fail: function fail(err) {
          uni.hideLoading();
          uni.showModal({
            title: '获取特征值失败',
            content: '错误代码: ' + (err.errCode || '未知'),
            confirmText: '重新连接',
            cancelText: '取消',
            success: function success(res) {
              if (res.confirm) {
                _this14.connectBluetooth(deviceId);
              }
            }
          });
        }
      });
    },
    // ========== 打印订单方法 ==========
    printOrder: function printOrder(item) {
      var _this15 = this;
      if (!this.isConnected || !this.deviceId || !this.serviceId || !this.characteristicId) {
        uni.showModal({
          title: '未连接打印机',
          content: '请先搜索并连接打印机',
          confirmText: '连接打印机',
          cancelText: '取消',
          success: function success(res) {
            if (res.confirm) {
              _this15.openAdapterAndStartSearch();
            }
          }
        });
        return;
      }

      // 选择打印类型
      uni.showActionSheet({
        itemList: ['运单(紧凑版)', '签收单(7.5×9cm)', '测试打印'],
        success: function success(res) {
          _this15.choosePrintCopies(item, res.tapIndex);
        }
      });
    },
    // 选择打印张数：弹窗输入（微信小程序等支持 showModal editable）
    choosePrintCopies: function choosePrintCopies(item, typeIndex) {
      var _this16 = this;
      var maxCopies = 99;
      uni.showModal({
        title: '打印份数',
        content: "",
        editable: true,
        placeholderText: '1',
        confirmText: '确定',
        cancelText: '取消',
        success: function success(res) {
          if (!res.confirm) return;
          var raw = ((res.content != null ? String(res.content) : '') + '').trim();
          var n = parseInt(raw, 10);
          if (!raw || Number.isNaN(n) || n < 1 || n > maxCopies) {
            uni.showToast({
              title: "\u8BF7\u8F93\u51651\uFF5E".concat(maxCopies, "\u7684\u6574\u6570"),
              icon: 'none'
            });
            return;
          }
          _this16.getPrintDataFromServer(item, typeIndex, n);
        }
      });
    },
    // 从服务器获取GBK编码的打印数据（printNum 会传给后台 print_num）
    getPrintDataFromServer: function getPrintDataFromServer(item, typeIndex) {
      var _this17 = this;
      var printNum = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : 1;
      uni.showLoading({
        title: '准备打印数据...',
        mask: true
      });

      // 如果是测试打印，不需要调用接口
      if (typeIndex === 2) {
        uni.hideLoading();
        var printContent = this.generateTestContent();
        this.previewAndPrint(printContent, typeIndex, true, printNum);
        return;
      }

      // 调用后端接口获取打印数据
      this.$httpapi('Index/getPrintData', 'POST', {
        order_id: item.orderid,
        print_type: typeIndex === 0 ? 'waybill' : 'receipt',
        // waybill=运单, receipt=签收单
        print_num: printNum
      }).then(function (res) {
        uni.hideLoading();
        if (res.code === 1 && res.data && res.data.print_data) {
          // 后端返回的是GBK编码的base64字符串
          _this17.previewAndPrint(res.data.print_data, typeIndex, false, printNum);
        } else {
          uni.showToast({
            title: res.msg || '获取打印数据失败',
            icon: 'none'
          });
        }
      }).catch(function (err) {
        uni.hideLoading();
        console.error('获取打印数据失败:', err);
        uni.showToast({
          title: '获取打印数据失败',
          icon: 'none'
        });
      });
    },
    // 预览并打印（printNum：实际蓝牙输出张数）
    previewAndPrint: function previewAndPrint(printContent, typeIndex) {
      var _this18 = this;
      var isLocalContent = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
      var printNum = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : 1;
      var typeNames = ['运单', '签收单', '测试页'];
      var num = Math.min(Math.max(parseInt(printNum, 10) || 1, 1), 99);
      var copyHint = num > 1 ? "\n\u4EFD\u6570: ".concat(num, "\u5F20") : '';

      // 如果是本地生成的内容（测试打印），显示预览
      if (isLocalContent) {
        var lineCount = (printContent.match(/\n/g) || []).length;
        console.log("=== ".concat(typeNames[typeIndex], " \u6253\u5370\u5185\u5BB9\u9884\u89C8 ==="));
        console.log("\u884C\u6570: ".concat(lineCount, "\u884C"));
        console.log('内容:');
        console.log(printContent.replace(/\x1B/g, '[ESC]').replace(/\x1D/g, '[GS]').replace(/\x0A/g, '[LF]\n'));
        uni.showModal({
          title: '打印确认',
          content: "\u7EB8\u5F20: 7.5cm \xD7 9cm\n\u7C7B\u578B: ".concat(typeNames[typeIndex], "\n\u884C\u6570: ").concat(lineCount, "\u884C").concat(copyHint, "\n\n\u786E\u8BA4\u6253\u5370\uFF1F"),
          confirmText: '立即打印',
          cancelText: '取消',
          success: function success(res) {
            if (res.confirm) {
              // 测试打印使用UTF-8编码
              _this18.sendPrintData(printContent, true, num);
            } else {
              uni.showToast({
                title: '已取消打印',
                icon: 'none'
              });
            }
          }
        });
      } else {
        // 服务器返回的base64数据，直接打印
        uni.showModal({
          title: '打印确认',
          content: "\u7EB8\u5F20: 7.5cm \xD7 9cm\n\u7C7B\u578B: ".concat(typeNames[typeIndex]).concat(copyHint, "\n\n\u786E\u8BA4\u6253\u5370\uFF1F"),
          confirmText: '立即打印',
          cancelText: '取消',
          success: function success(res) {
            if (res.confirm) {
              // base64数据，使用GBK编码
              _this18.sendPrintData(printContent, false, num);
            } else {
              uni.showToast({
                title: '已取消打印',
                icon: 'none'
              });
            }
          }
        });
      }
    },
    // 生成运单内容（紧凑版，适配小票纸）
    generateWaybillContent: function generateWaybillContent(item) {
      var ESC = '\x1B';
      var GS = '\x1D';
      var LF = '\x0A';

      // 获取数据
      var waybillNo = (item.orderid || '') + '';
      var recvName = item.unload_address && item.unload_address.name || '';
      var recvPhone = item.unload_address && (item.unload_address.mobile || item.unload_address.tel) || '';
      var recvAddr = item.unload_address && item.unload_address.address || '';
      var sendAddr = item.loading_address && item.loading_address.address || '';
      var goodsName = item.goods_name || '货物';
      var weight = item.weight || '0';
      var volume = item.volume || '0';
      var pieces = item.num || '1';
      var freight = item.pay_price || '0.00';
      var createTime = item.createtime || '';
      var content = '';

      // 初始化
      content += ESC + '@';
      content += ESC + '2'; // 默认行距

      // 设置紧凑模式
      content += ESC + '3' + '\x10'; // 设置行距为16/180英寸

      // 运单号（居中，稍大）
      content += ESC + 'a' + '\x01'; // 居中
      content += ESC + '!' + '\x08'; // 加粗
      content += '运单号：' + waybillNo + LF;

      // 恢复左对齐，正常字体
      content += ESC + 'a' + '\x00';
      content += ESC + '!' + '\x00';

      // 条码（CODE128，如果支持）
      if (waybillNo.length > 0) {
        content += ESC + 'a' + '\x01'; // 居中
        content += GS + 'h' + '\x28'; // 条码高度40
        content += GS + 'w' + '\x02'; // 条码宽度2
        content += GS + 'H' + '\x02'; // 条码文字在下
        var len = String.fromCharCode(waybillNo.length);
        content += GS + 'k' + '\x49' + len + waybillNo;
        content += LF;
        content += ESC + 'a' + '\x00'; // 恢复左对齐
      }

      // 分隔线
      content += '--------------------' + LF;

      // 收件信息（精简）
      content += '【收件人】' + this.truncateText(recvName, 8) + LF;
      content += '电  话：' + this.truncateText(recvPhone, 11) + LF;
      content += '地  址：' + this.truncateText(recvAddr, 20) + LF;
      content += '--------------------' + LF;

      // 寄件信息
      content += '【寄件人】' + this.truncateText(sendAddr, 20) + LF;
      content += '--------------------' + LF;

      // 货物信息
      content += '货  品：' + this.truncateText(goodsName, 12) + LF;
      content += '件  数：' + pieces + '件';
      content += '  重  量：' + weight + 'kg' + LF;

      // 费用信息
      content += '运  费：￥' + freight + LF;

      // 时间信息
      content += '时  间：' + this.truncateText(createTime, 16) + LF;

      // 状态信息
      content += '状  态：' + this.getStatusText(item.pay_status) + LF;

      // 底部提示
      content += '--------------------' + LF;
      content += '请核对后签收' + LF;

      // 走纸和切纸（根据打印机类型调整）
      content += ESC + 'd' + '\x02'; // 走纸2行
      // content += GS + 'V' + '\x41' + '\x00' // 全切（如果支持）
      content += GS + 'V' + '\x00'; // 不切纸（撕纸型）

      return content;
    },
    // 签收单紧凑版：7.5cm 宽 × 9cm 高单页（约 14 汉字/行，小行距）
    receiptWrapLines: function receiptWrapLines(text, lineWidth, maxLines) {
      var t = ((text || '') + '').replace(/\s+/g, ' ').trim();
      if (!t) return [];
      var lines = [];
      var pos = 0;
      while (pos < t.length && lines.length < maxLines) {
        lines.push(t.substring(pos, pos + lineWidth));
        pos += lineWidth;
      }
      if (pos < t.length && lines.length) {
        var li = lines.length - 1;
        lines[li] = lines[li].substring(0, Math.max(1, lineWidth - 1)) + '…';
      }
      return lines;
    },
    generateReceiptContent: function generateReceiptContent(item) {
      var ESC = '\x1B';
      var GS = '\x1D';
      var LF = '\x0A';
      var LW = 14;
      var waybillNo = (item.orderid || '') + '';
      var recvName = item.unload_address && item.unload_address.name || '';
      var recvPhone = item.unload_address && (item.unload_address.mobile || item.unload_address.tel) || '';
      var recvAddr = item.unload_address && item.unload_address.address || '';
      var recvCompany = item.unload_address && item.unload_address.company || '';
      var sendName = item.loading_address && item.loading_address.name || '';
      var sendPhone = item.loading_address && (item.loading_address.mobile || item.loading_address.tel) || '';
      var sendAddr = item.loading_address && item.loading_address.address || '';
      var sendCompany = item.loading_address && item.loading_address.company || '';
      var goodsName = item.goods_name || '货物';
      var packType = item.pack_type || '标准';
      var weight = item.weight || '0';
      var volume = item.volume || '0';
      var pieces = item.num || '1';
      var freight = item.pay_price || '0.00';
      var declareValue = item.declare_value || '0.00';
      var serviceFee = item.service_fee || '0.00';
      var codAmount = item.cod_amount || '0.00';
      var createTime = item.createtime || '';
      var payType = item.pay_type_name || '现付';
      var sep = '--------------';
      var content = '';
      content += ESC + '@';
      content += ESC + '3' + '\x08';
      content += ESC + 'a' + '\x01';
      content += ESC + '!' + '\x08';
      content += '签收单' + LF;
      content += ESC + '!' + '\x00';
      content += '单' + this.truncateText(waybillNo, LW - 1) + LF;
      content += ESC + 'a' + '\x00';
      content += sep + LF;
      content += '收' + this.truncateText(recvName, LW - 1) + LF;
      content += this.truncateText(recvPhone, LW) + LF;
      this.receiptWrapLines(recvAddr, LW, 2).forEach(function (ln) {
        content += ln + LF;
      });
      if (recvCompany) {
        content += this.truncateText('司:' + recvCompany, LW) + LF;
      }
      content += '寄' + this.truncateText(sendName, LW - 1) + LF;
      content += this.truncateText(sendPhone, LW) + LF;
      this.receiptWrapLines(sendAddr, LW, 2).forEach(function (ln) {
        content += ln + LF;
      });
      if (sendCompany) {
        content += this.truncateText('司:' + sendCompany, LW) + LF;
      }
      var goodsLine = '货' + this.truncateText(goodsName, 5) + pieces + '件' + weight + 'kg';
      content += this.truncateText(goodsLine, LW) + LF;
      var packPayLine = '包' + this.truncateText(packType, 4) + '付' + this.truncateText(payType, 4) + '体' + this.truncateText(String(volume), 4);
      content += this.truncateText(packPayLine, LW) + LF;
      content += this.truncateText('运' + freight, LW) + LF;
      content += this.truncateText('保' + declareValue + '服' + serviceFee, LW) + LF;
      content += this.truncateText(createTime, LW) + LF;
      if (codAmount !== '0.00' && codAmount !== 0 && String(codAmount) !== '0') {
        content += this.truncateText('代' + codAmount, LW) + LF;
      }
      content += sep + LF;
      content += ESC + 'd' + '\x01';
      content += GS + 'V' + '\x00';
      return content;
    },
    // 生成测试打印内容
    generateTestContent: function generateTestContent() {
      var ESC = '\x1B';
      var GS = '\x1D';
      var LF = '\x0A';
      var content = ESC + '@';
      content += ESC + '2';
      content += ESC + 'a' + '\x01';
      content += ESC + '!' + '\x18';
      content += '打印机测试' + LF;
      content += ESC + '!' + '\x00';
      content += ESC + 'a' + '\x00';
      content += '====================' + LF;
      content += '纸张尺寸：7.5cm × 13cm' + LF;
      content += '打印时间：' + new Date().toLocaleString() + LF;
      content += '====================' + LF;

      // 测试各种字体
      content += ESC + '!' + '\x00' + '正常字体' + LF;
      content += ESC + '!' + '\x08' + '加粗字体' + LF;
      content += ESC + '!' + '\x10' + '倍高字体' + LF;
      content += ESC + '!' + '\x20' + '倍宽字体' + LF;
      content += ESC + '!' + '\x30' + '倍高宽字体' + LF;
      content += ESC + '!' + '\x00';
      content += '====================' + LF;
      content += 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' + LF;
      content += 'abcdefghijklmnopqrstuvwxyz' + LF;
      content += '1234567890!@#$%^&*()' + LF;
      content += '====================' + LF;
      content += ESC + 'a' + '\x01';
      content += '测试完成 ✓' + LF;
      content += ESC + 'a' + '\x00';
      content += ESC + 'd' + '\x03';
      content += GS + 'V' + '\x00';
      return content;
    },
    // 辅助方法：截断文本
    truncateText: function truncateText(text, maxLength) {
      if (!text) return '';
      return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
    },
    // 辅助方法：获取状态文本
    getStatusText: function getStatusText(status) {
      var statusMap = {
        1: '待付款',
        2: '服务中',
        3: '已完成',
        4: '已取消',
        6: '测算中',
        7: '已出价',
        8: '订单驳回'
      };
      return statusMap[status] || '未知状态';
    },
    // 查看驳回原因（pay_status==8 时）
    showRejectReason: function showRejectReason(item) {
      var reason = item.reject || '暂无驳回原因说明';
      uni.showModal({
        title: '驳回原因',
        content: reason,
        showCancel: false,
        confirmText: '知道了'
      });
    },
    // 发送打印数据
    // content: 打印内容（base64字符串或普通字符串）
    // isUtf8: true=使用UTF-8编码（测试打印），false=使用GBK编码（从服务器获取的base64）
    // copies: 连续打印张数（每张内容相同）
    sendPrintData: function sendPrintData(content) {
      var _this19 = this;
      var isUtf8 = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
      var copies = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : 1;
      var totalCopies = Math.min(Math.max(parseInt(copies, 10) || 1, 1), 99);
      uni.showLoading({
        title: totalCopies > 1 ? "\u6253\u5370\u4E2D (1/".concat(totalCopies, ")") : '打印中...',
        mask: true
      });
      var buffer;
      if (isUtf8) {
        buffer = this.stringToUtf8Buffer(content);
      } else {
        buffer = this.base64ToArrayBuffer(content);
      }
      var chunkSize = 20;
      var chunks = [];
      for (var i = 0; i < buffer.byteLength; i += chunkSize) {
        chunks.push(buffer.slice(i, i + chunkSize));
      }
      var copyDone = 0;
      var sendOneCopy = function sendOneCopy() {
        var index = 0;
        var sendNext = function sendNext() {
          if (index >= chunks.length) {
            copyDone++;
            if (copyDone >= totalCopies) {
              uni.hideLoading();
              uni.showToast({
                title: totalCopies > 1 ? "\u5DF2\u6253\u5370 ".concat(totalCopies, " \u5F20") : '打印完成',
                icon: 'success'
              });
              return;
            }
            uni.showLoading({
              title: "\u6253\u5370\u4E2D (".concat(copyDone + 1, "/").concat(totalCopies, ")"),
              mask: true
            });
            setTimeout(sendOneCopy, 200);
            return;
          }
          uni.writeBLECharacteristicValue({
            deviceId: _this19.deviceId,
            serviceId: _this19.serviceId,
            characteristicId: _this19.characteristicId,
            value: chunks[index],
            success: function success() {
              index++;
              setTimeout(sendNext, 50);
            },
            fail: function fail(err) {
              uni.hideLoading();
              console.error('打印失败:', err);
              uni.showToast({
                title: '打印失败',
                icon: 'none'
              });
            }
          });
        };
        sendNext();
      };
      sendOneCopy();
    },
    // Base64字符串转ArrayBuffer
    base64ToArrayBuffer: function base64ToArrayBuffer(base64) {
      // 小程序环境使用uni.base64ToArrayBuffer
      if (typeof uni !== 'undefined' && uni.base64ToArrayBuffer) {
        return uni.base64ToArrayBuffer(base64);
      }

      // 兼容H5环境
      var binaryString = atob(base64);
      var bytes = new Uint8Array(binaryString.length);
      for (var i = 0; i < binaryString.length; i++) {
        bytes[i] = binaryString.charCodeAt(i);
      }
      return bytes.buffer;
    },
    // 字符串转 UTF-8 ArrayBuffer（支持中文）
    stringToUtf8Buffer: function stringToUtf8Buffer(str) {
      var codeUnits = [];
      for (var i = 0; i < str.length; i++) {
        var codePoint = str.charCodeAt(i);

        // 处理代理对（高低位代理组成的 4 字节字符）
        if (codePoint >= 0xd800 && codePoint <= 0xdbff && i + 1 < str.length) {
          var next = str.charCodeAt(i + 1);
          if (next >= 0xdc00 && next <= 0xdfff) {
            codePoint = (codePoint - 0xd800 << 10) + (next - 0xdc00) + 0x10000;
            i++;
          }
        }

        // 按 UTF-8 规则编码
        if (codePoint <= 0x7f) {
          codeUnits.push(codePoint);
        } else if (codePoint <= 0x7ff) {
          codeUnits.push(0xc0 | codePoint >> 6, 0x80 | codePoint & 0x3f);
        } else if (codePoint <= 0xffff) {
          codeUnits.push(0xe0 | codePoint >> 12, 0x80 | codePoint >> 6 & 0x3f, 0x80 | codePoint & 0x3f);
        } else {
          codeUnits.push(0xf0 | codePoint >> 18, 0x80 | codePoint >> 12 & 0x3f, 0x80 | codePoint >> 6 & 0x3f, 0x80 | codePoint & 0x3f);
        }
      }
      var buffer = new ArrayBuffer(codeUnits.length);
      var view = new Uint8Array(buffer);
      for (var _i = 0; _i < codeUnits.length; _i++) {
        view[_i] = codeUnits[_i];
      }
      return buffer;
    },
    // 关闭蓝牙
    closeBluetooth: function closeBluetooth() {
      var _this20 = this;
      if (this.deviceId) {
        uni.closeBLEConnection({
          deviceId: this.deviceId,
          success: function success() {
            _this20.isConnected = false;
            _this20.deviceId = '';
            _this20.serviceId = '';
            _this20.characteristicId = '';
          }
        });
      }
    }
  }
};
exports.default = _default;
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! ./node_modules/@dcloudio/uni-mp-weixin/dist/index.js */ 2)["default"], __webpack_require__(/*! ./node_modules/@dcloudio/uni-mp-weixin/dist/wx.js */ 1)["default"]))

/***/ }),

/***/ 76:
/*!*************************************************************************************************************!*\
  !*** D:/phpstudy_pro/WWW/longzhehutongdaima/物流系统小程序/pages/order/order.vue?vue&type=style&index=0&lang=css& ***!
  \*************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _HBuilderX_plugins_uniapp_cli_node_modules_mini_css_extract_plugin_dist_loader_js_ref_6_oneOf_1_0_HBuilderX_plugins_uniapp_cli_node_modules_css_loader_dist_cjs_js_ref_6_oneOf_1_1_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_loaders_stylePostLoader_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_6_oneOf_1_2_HBuilderX_plugins_uniapp_cli_node_modules_postcss_loader_src_index_js_ref_6_oneOf_1_3_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_order_vue_vue_type_style_index_0_lang_css___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../HBuilderX/plugins/uniapp-cli/node_modules/mini-css-extract-plugin/dist/loader.js??ref--6-oneOf-1-0!../../../../../../HBuilderX/plugins/uniapp-cli/node_modules/css-loader/dist/cjs.js??ref--6-oneOf-1-1!../../../../../../HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/vue-cli-plugin-uni/packages/vue-loader/lib/loaders/stylePostLoader.js!../../../../../../HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/vue-cli-plugin-uni/packages/webpack-preprocess-loader??ref--6-oneOf-1-2!../../../../../../HBuilderX/plugins/uniapp-cli/node_modules/postcss-loader/src??ref--6-oneOf-1-3!../../../../../../HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/vue-cli-plugin-uni/packages/vue-loader/lib??vue-loader-options!../../../../../../HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/webpack-uni-mp-loader/lib/style.js!./order.vue?vue&type=style&index=0&lang=css& */ 77);
/* harmony import */ var _HBuilderX_plugins_uniapp_cli_node_modules_mini_css_extract_plugin_dist_loader_js_ref_6_oneOf_1_0_HBuilderX_plugins_uniapp_cli_node_modules_css_loader_dist_cjs_js_ref_6_oneOf_1_1_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_loaders_stylePostLoader_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_6_oneOf_1_2_HBuilderX_plugins_uniapp_cli_node_modules_postcss_loader_src_index_js_ref_6_oneOf_1_3_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_order_vue_vue_type_style_index_0_lang_css___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_HBuilderX_plugins_uniapp_cli_node_modules_mini_css_extract_plugin_dist_loader_js_ref_6_oneOf_1_0_HBuilderX_plugins_uniapp_cli_node_modules_css_loader_dist_cjs_js_ref_6_oneOf_1_1_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_loaders_stylePostLoader_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_6_oneOf_1_2_HBuilderX_plugins_uniapp_cli_node_modules_postcss_loader_src_index_js_ref_6_oneOf_1_3_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_order_vue_vue_type_style_index_0_lang_css___WEBPACK_IMPORTED_MODULE_0__);
/* harmony reexport (unknown) */ for(var __WEBPACK_IMPORT_KEY__ in _HBuilderX_plugins_uniapp_cli_node_modules_mini_css_extract_plugin_dist_loader_js_ref_6_oneOf_1_0_HBuilderX_plugins_uniapp_cli_node_modules_css_loader_dist_cjs_js_ref_6_oneOf_1_1_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_loaders_stylePostLoader_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_6_oneOf_1_2_HBuilderX_plugins_uniapp_cli_node_modules_postcss_loader_src_index_js_ref_6_oneOf_1_3_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_order_vue_vue_type_style_index_0_lang_css___WEBPACK_IMPORTED_MODULE_0__) if(["default"].indexOf(__WEBPACK_IMPORT_KEY__) < 0) (function(key) { __webpack_require__.d(__webpack_exports__, key, function() { return _HBuilderX_plugins_uniapp_cli_node_modules_mini_css_extract_plugin_dist_loader_js_ref_6_oneOf_1_0_HBuilderX_plugins_uniapp_cli_node_modules_css_loader_dist_cjs_js_ref_6_oneOf_1_1_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_loaders_stylePostLoader_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_6_oneOf_1_2_HBuilderX_plugins_uniapp_cli_node_modules_postcss_loader_src_index_js_ref_6_oneOf_1_3_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_order_vue_vue_type_style_index_0_lang_css___WEBPACK_IMPORTED_MODULE_0__[key]; }) }(__WEBPACK_IMPORT_KEY__));
 /* harmony default export */ __webpack_exports__["default"] = (_HBuilderX_plugins_uniapp_cli_node_modules_mini_css_extract_plugin_dist_loader_js_ref_6_oneOf_1_0_HBuilderX_plugins_uniapp_cli_node_modules_css_loader_dist_cjs_js_ref_6_oneOf_1_1_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_loaders_stylePostLoader_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_6_oneOf_1_2_HBuilderX_plugins_uniapp_cli_node_modules_postcss_loader_src_index_js_ref_6_oneOf_1_3_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_order_vue_vue_type_style_index_0_lang_css___WEBPACK_IMPORTED_MODULE_0___default.a); 

/***/ }),

/***/ 77:
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/mini-css-extract-plugin/dist/loader.js??ref--6-oneOf-1-0!./node_modules/css-loader/dist/cjs.js??ref--6-oneOf-1-1!./node_modules/@dcloudio/vue-cli-plugin-uni/packages/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/@dcloudio/vue-cli-plugin-uni/packages/webpack-preprocess-loader??ref--6-oneOf-1-2!./node_modules/postcss-loader/src??ref--6-oneOf-1-3!./node_modules/@dcloudio/vue-cli-plugin-uni/packages/vue-loader/lib??vue-loader-options!./node_modules/@dcloudio/webpack-uni-mp-loader/lib/style.js!D:/phpstudy_pro/WWW/longzhehutongdaima/物流系统小程序/pages/order/order.vue?vue&type=style&index=0&lang=css& ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin
    if(false) { var cssReload; }
  

/***/ })

},[[70,"common/runtime","common/vendor"]]]);
//# sourceMappingURL=../../../.sourcemap/mp-weixin/pages/order/order.js.map
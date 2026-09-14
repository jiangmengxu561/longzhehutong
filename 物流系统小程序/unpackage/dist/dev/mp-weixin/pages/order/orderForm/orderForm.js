(global["webpackJsonp"] = global["webpackJsonp"] || []).push([["pages/order/orderForm/orderForm"],{

/***/ 134:
/*!***************************************************************************************************************!*\
  !*** D:/phpstudy_pro/WWW/longzhehutongdaima/物流系统小程序/main.js?{"page":"pages%2Forder%2ForderForm%2ForderForm"} ***!
  \***************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
/* WEBPACK VAR INJECTION */(function(wx, createPage) {

var _interopRequireDefault = __webpack_require__(/*! @babel/runtime/helpers/interopRequireDefault */ 4);
__webpack_require__(/*! uni-pages */ 30);
var _vue = _interopRequireDefault(__webpack_require__(/*! vue */ 25));
var _orderForm = _interopRequireDefault(__webpack_require__(/*! ./pages/order/orderForm/orderForm.vue */ 135));
// @ts-ignore
wx.__webpack_require_UNI_MP_PLUGIN__ = __webpack_require__;
createPage(_orderForm.default);
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! ./node_modules/@dcloudio/uni-mp-weixin/dist/wx.js */ 1)["default"], __webpack_require__(/*! ./node_modules/@dcloudio/uni-mp-weixin/dist/index.js */ 2)["createPage"]))

/***/ }),

/***/ 135:
/*!******************************************************************************************!*\
  !*** D:/phpstudy_pro/WWW/longzhehutongdaima/物流系统小程序/pages/order/orderForm/orderForm.vue ***!
  \******************************************************************************************/
/*! no static exports found */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _orderForm_vue_vue_type_template_id_5c7ea7aa_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./orderForm.vue?vue&type=template&id=5c7ea7aa&scoped=true& */ 136);
/* harmony import */ var _orderForm_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./orderForm.vue?vue&type=script&lang=js& */ 138);
/* harmony reexport (unknown) */ for(var __WEBPACK_IMPORT_KEY__ in _orderForm_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__) if(["default"].indexOf(__WEBPACK_IMPORT_KEY__) < 0) (function(key) { __webpack_require__.d(__webpack_exports__, key, function() { return _orderForm_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__[key]; }) }(__WEBPACK_IMPORT_KEY__));
/* harmony import */ var _orderForm_vue_vue_type_style_index_0_id_5c7ea7aa_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./orderForm.vue?vue&type=style&index=0&id=5c7ea7aa&scoped=true&lang=css& */ 140);
/* harmony import */ var _HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../../../../../../HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/vue-cli-plugin-uni/packages/vue-loader/lib/runtime/componentNormalizer.js */ 36);

var renderjs





/* normalize component */

var component = Object(_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _orderForm_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _orderForm_vue_vue_type_template_id_5c7ea7aa_scoped_true___WEBPACK_IMPORTED_MODULE_0__["render"],
  _orderForm_vue_vue_type_template_id_5c7ea7aa_scoped_true___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"],
  false,
  null,
  "5c7ea7aa",
  null,
  false,
  _orderForm_vue_vue_type_template_id_5c7ea7aa_scoped_true___WEBPACK_IMPORTED_MODULE_0__["components"],
  renderjs
)

component.options.__file = "pages/order/orderForm/orderForm.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ 136:
/*!*************************************************************************************************************************************!*\
  !*** D:/phpstudy_pro/WWW/longzhehutongdaima/物流系统小程序/pages/order/orderForm/orderForm.vue?vue&type=template&id=5c7ea7aa&scoped=true& ***!
  \*************************************************************************************************************************************/
/*! exports provided: render, staticRenderFns, recyclableRender, components */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_17_0_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_template_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_uni_app_loader_page_meta_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_orderForm_vue_vue_type_template_id_5c7ea7aa_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../../HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/vue-cli-plugin-uni/packages/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../../../../HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/vue-cli-plugin-uni/packages/webpack-preprocess-loader??ref--17-0!../../../../../../../HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/webpack-uni-mp-loader/lib/template.js!../../../../../../../HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/vue-cli-plugin-uni/packages/webpack-uni-app-loader/page-meta.js!../../../../../../../HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/vue-cli-plugin-uni/packages/vue-loader/lib??vue-loader-options!../../../../../../../HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/webpack-uni-mp-loader/lib/style.js!./orderForm.vue?vue&type=template&id=5c7ea7aa&scoped=true& */ 137);
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "render", function() { return _HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_17_0_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_template_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_uni_app_loader_page_meta_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_orderForm_vue_vue_type_template_id_5c7ea7aa_scoped_true___WEBPACK_IMPORTED_MODULE_0__["render"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "staticRenderFns", function() { return _HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_17_0_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_template_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_uni_app_loader_page_meta_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_orderForm_vue_vue_type_template_id_5c7ea7aa_scoped_true___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "recyclableRender", function() { return _HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_17_0_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_template_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_uni_app_loader_page_meta_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_orderForm_vue_vue_type_template_id_5c7ea7aa_scoped_true___WEBPACK_IMPORTED_MODULE_0__["recyclableRender"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "components", function() { return _HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_17_0_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_template_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_uni_app_loader_page_meta_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_orderForm_vue_vue_type_template_id_5c7ea7aa_scoped_true___WEBPACK_IMPORTED_MODULE_0__["components"]; });



/***/ }),

/***/ 137:
/*!*************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/@dcloudio/vue-cli-plugin-uni/packages/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/@dcloudio/vue-cli-plugin-uni/packages/webpack-preprocess-loader??ref--17-0!./node_modules/@dcloudio/webpack-uni-mp-loader/lib/template.js!./node_modules/@dcloudio/vue-cli-plugin-uni/packages/webpack-uni-app-loader/page-meta.js!./node_modules/@dcloudio/vue-cli-plugin-uni/packages/vue-loader/lib??vue-loader-options!./node_modules/@dcloudio/webpack-uni-mp-loader/lib/style.js!D:/phpstudy_pro/WWW/longzhehutongdaima/物流系统小程序/pages/order/orderForm/orderForm.vue?vue&type=template&id=5c7ea7aa&scoped=true& ***!
  \*************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/*! exports provided: render, staticRenderFns, recyclableRender, components */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "render", function() { return render; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "staticRenderFns", function() { return staticRenderFns; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "recyclableRender", function() { return recyclableRender; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "components", function() { return components; });
var components
try {
  components = {
    uniPopup: function () {
      return __webpack_require__.e(/*! import() | uni_modules/uni-popup/components/uni-popup/uni-popup */ "uni_modules/uni-popup/components/uni-popup/uni-popup").then(__webpack_require__.bind(null, /*! @/uni_modules/uni-popup/components/uni-popup/uni-popup.vue */ 297))
    },
    lPicker: function () {
      return Promise.all(/*! import() | uni_modules/lime-picker/components/l-picker/l-picker */[__webpack_require__.e("common/vendor"), __webpack_require__.e("uni_modules/lime-picker/components/l-picker/l-picker")]).then(__webpack_require__.bind(null, /*! @/uni_modules/lime-picker/components/l-picker/l-picker.vue */ 304))
    },
    lPickerItem: function () {
      return Promise.all(/*! import() | uni_modules/lime-picker/components/l-picker-item/l-picker-item */[__webpack_require__.e("common/vendor"), __webpack_require__.e("uni_modules/lime-picker/components/l-picker-item/l-picker-item")]).then(__webpack_require__.bind(null, /*! @/uni_modules/lime-picker/components/l-picker-item/l-picker-item.vue */ 313))
    },
  }
} catch (e) {
  if (
    e.message.indexOf("Cannot find module") !== -1 &&
    e.message.indexOf(".vue") !== -1
  ) {
    console.error(e.message)
    console.error("1. 排查组件名称拼写是否正确")
    console.error(
      "2. 排查组件是否符合 easycom 规范，文档：https://uniapp.dcloud.net.cn/collocation/pages?id=easycom"
    )
    console.error(
      "3. 若组件不符合 easycom 规范，需手动引入，并在 components 中注册该组件"
    )
  } else {
    throw e
  }
}
var render = function () {
  var _vm = this
  var _h = _vm.$createElement
  var _c = _vm._self._c || _h
  var m0 =
    _vm.isRejectOnlyEdit && !_vm.isFieldEditable("loading_address", "loading")
  var m1 =
    _vm.isRejectOnlyEdit &&
    !_vm.isFieldEditable(
      "loading_address",
      "loading",
      "unload_address",
      "unload"
    )
  var m2 =
    _vm.isRejectOnlyEdit && !_vm.isFieldEditable("unload_address", "unload")
  var m3 = _vm.isRejectOnlyEdit && !_vm.isFieldEditable("find_car_type")
  var m4 = _vm.isRejectOnlyEdit && !_vm.isFieldEditable("isinvoice", "invoice")
  var m5 =
    _vm.isinvoice == 1
      ? _vm.isRejectOnlyEdit &&
        !_vm.isFieldEditable(
          "isinvoice",
          "invoice",
          "type",
          "tax_point",
          "company_letterhead",
          "company_tax_id",
          "company_email",
          "company_mobile",
          "bank_deposits"
        )
      : null
  var m6 = _vm.isRejectOnlyEdit && !_vm.isFieldEditable("goods_name")
  var m7 = _vm.isRejectOnlyEdit && !_vm.isFieldEditable("goods_name")
  var m8 = _vm.isRejectOnlyEdit && !_vm.isFieldEditable("goods_type_id")
  var m9 = _vm.isRejectOnlyEdit && !_vm.isFieldEditable("goods_type_id")
  var m10 = _vm.isRejectOnlyEdit && !_vm.isFieldEditable("goods_image")
  var m11 = _vm.isRejectOnlyEdit && !_vm.isFieldEditable("quantity")
  var m12 = !_vm.canAddMultipleSizes
    ? _vm.isRejectOnlyEdit &&
      !_vm.isFieldEditable("long", "wide", "hige", "size", "sizeList")
    : null
  var m13 = _vm.canAddMultipleSizes
    ? _vm.isRejectOnlyEdit &&
      !_vm.isFieldEditable("long", "wide", "hige", "size", "sizeList")
    : null
  var l0 = _vm.canAddMultipleSizes
    ? _vm.__map(_vm.sizeList, function (item, index) {
        var $orig = _vm.__get_orig(item)
        var g0 = _vm.sizeList.length
        return {
          $orig: $orig,
          g0: g0,
        }
      })
    : null
  var m14 = _vm.isRejectOnlyEdit && !_vm.isFieldEditable("weight")
  var m15 = _vm.isRejectOnlyEdit && !_vm.isFieldEditable("direction")
  var m16 = _vm.isRejectOnlyEdit && !_vm.isFieldEditable("pay_price")
  var m17 =
    _vm.isRejectOnlyEdit && !_vm.isFieldEditable("packaging_id", "packaging")
  var g1 = _vm.showPackagePanel ? _vm.baoList && _vm.baoList.length : null
  var l1 =
    _vm.showPackagePanel && g1
      ? _vm.__map(_vm.baoList, function (item, __i0__) {
          var $orig = _vm.__get_orig(item)
          var m18 = _vm.isPackageSelected(item.id)
          var m19 = _vm.isPackageSelected(item.id)
          var m20 = m19 ? _vm.getPackageQuantity(item.id) : null
          return {
            $orig: $orig,
            m18: m18,
            m19: m19,
            m20: m20,
          }
        })
      : null
  var m21 =
    _vm.isRejectOnlyEdit && !_vm.isFieldEditable("pay_type", "pay_party")
  var m22 =
    _vm.pay_type == 1
      ? _vm.isRejectOnlyEdit && !_vm.isFieldEditable("pay_type", "pay_party")
      : null
  var m23 =
    _vm.isRejectOnlyEdit && !_vm.isFieldEditable("delivery", "behalf_price")
  var m24 =
    _vm.delivery == 1
      ? _vm.isRejectOnlyEdit &&
        !_vm.isFieldEditable(
          "delivery",
          "behalf_price",
          "bank_branch",
          "open_number",
          "bank_type",
          "open_name",
          "open_type"
        )
      : null
  var m25 = _vm.isInternalStaff
    ? _vm.isRejectOnlyEdit &&
      !_vm.isFieldEditable("information", "deposit", "information_image")
    : null
  var m26 = _vm.isRejectOnlyEdit && !_vm.isFieldEditable("car_type_id")
  var m27 = _vm.isRejectOnlyEdit && !_vm.isFieldEditable("car_type_id")
  var m28 =
    _vm.isRejectOnlyEdit && !_vm.isFieldEditable("earliest_time", "latest_time")
  var m29 = _vm.isRejectOnlyEdit && !_vm.isFieldEditable("isrequirements")
  var m30 =
    _vm.isrequirements == 1
      ? _vm.isRejectOnlyEdit &&
        !_vm.isFieldEditable(
          "deliveryrequirements",
          "loadingrequirements",
          "delivery_type_id",
          "receipt_type_id",
          "service",
          "unpack_id",
          "control",
          "other_id",
          "text_message"
        )
      : null
  var g2 =
    _vm.isrequirements == 1 && _vm.showDeliveryPanel
      ? _vm.deliveryRequirementsList && _vm.deliveryRequirementsList.length
      : null
  var l2 =
    _vm.isrequirements == 1 && _vm.showDeliveryPanel && g2
      ? _vm.__map(_vm.deliveryRequirementsList, function (item, __i1__) {
          var $orig = _vm.__get_orig(item)
          var g3 = _vm.selectedDeliveryRequirements.includes(item.id)
          return {
            $orig: $orig,
            g3: g3,
          }
        })
      : null
  var g4 =
    _vm.isrequirements == 1 && _vm.showLoadingPanel
      ? _vm.loadingRequirementsList && _vm.loadingRequirementsList.length
      : null
  var l3 =
    _vm.isrequirements == 1 && _vm.showLoadingPanel && g4
      ? _vm.__map(_vm.loadingRequirementsList, function (item, __i2__) {
          var $orig = _vm.__get_orig(item)
          var g5 = _vm.selectedLoadingRequirements.includes(item.id)
          return {
            $orig: $orig,
            g5: g5,
          }
        })
      : null
  var g6 =
    _vm.isrequirements == 1
      ? _vm.huiList[0] &&
        _vm.huiList[0].length > 0 &&
        _vm.huiList[1] &&
        _vm.huiList[1].length > 0 &&
        _vm.huiList[0][_vm.huiIndex[0]] &&
        _vm.huiList[1][_vm.huiIndex[1]]
      : null
  var g7 =
    _vm.isrequirements == 1 && _vm.showOtherPanel
      ? _vm.qitaList && _vm.qitaList.length
      : null
  var l4 =
    _vm.isrequirements == 1 && _vm.showOtherPanel && g7
      ? _vm.__map(_vm.qitaList, function (item, __i3__) {
          var $orig = _vm.__get_orig(item)
          var m31 = _vm.isOtherSelected(item.id)
          var m32 = _vm.formatOtherPrice(item)
          return {
            $orig: $orig,
            m31: m31,
            m32: m32,
          }
        })
      : null
  var m33 =
    _vm.isrequirements == 1
      ? _vm.isRejectOnlyEdit && !_vm.isFieldEditable("remark", "break")
      : null
  var m34 =
    _vm.isrequirements == 1
      ? _vm.isRejectOnlyEdit && !_vm.isFieldEditable("remark", "break")
      : null
  if (!_vm._isMounted) {
    _vm.e0 = function ($event) {
      _vm.carType = 0
    }
    _vm.e1 = function ($event) {
      _vm.carType = 0
    }
    _vm.e2 = function ($event) {
      _vm.carType = 1
    }
    _vm.e3 = function ($event) {
      _vm.carType = 1
    }
    _vm.e4 = function ($event) {
      _vm.carType = 2
    }
    _vm.e5 = function ($event) {
      _vm.carType = 2
    }
    _vm.e6 = function ($event) {
      _vm.isinvoice = 0
    }
    _vm.e7 = function ($event) {
      _vm.isinvoice = 0
    }
    _vm.e8 = function ($event) {
      _vm.isinvoice = 1
    }
    _vm.e9 = function ($event) {
      _vm.isinvoice = 1
    }
    _vm.e10 = function ($event) {
      _vm.quantity++
    }
    _vm.e11 = function ($event) {
      _vm.pay_type = 0
    }
    _vm.e12 = function ($event) {
      _vm.pay_type = 0
    }
    _vm.e13 = function ($event) {
      _vm.pay_type = 1
    }
    _vm.e14 = function ($event) {
      _vm.pay_type = 1
    }
    _vm.e15 = function ($event) {
      _vm.pay_type = 2
    }
    _vm.e16 = function ($event) {
      _vm.pay_type = 2
    }
    _vm.e17 = function ($event) {
      _vm.pay_party = 0
    }
    _vm.e18 = function ($event) {
      _vm.pay_party = 0
    }
    _vm.e19 = function ($event) {
      _vm.pay_party = 1
    }
    _vm.e20 = function ($event) {
      _vm.pay_party = 1
    }
    _vm.e21 = function ($event) {
      _vm.delivery = 0
    }
    _vm.e22 = function ($event) {
      _vm.delivery = 0
    }
    _vm.e23 = function ($event) {
      _vm.delivery = 1
    }
    _vm.e24 = function ($event) {
      _vm.delivery = 1
    }
    _vm.e25 = function ($event) {
      _vm.isrequirements = 0
    }
    _vm.e26 = function ($event) {
      _vm.isrequirements = 0
    }
    _vm.e27 = function ($event) {
      _vm.isrequirements = 1
    }
    _vm.e28 = function ($event) {
      _vm.isrequirements = 1
    }
    _vm.e29 = function ($event) {
      _vm.remark = $event.detail.value
    }
    _vm.e30 = function ($event) {
      _vm.check = !_vm.check
    }
    _vm.e31 = function ($event) {
      _vm.check = !_vm.check
    }
    _vm.e32 = function ($event) {
      _vm.check = !_vm.check
    }
  }
  _vm.$mp.data = Object.assign(
    {},
    {
      $root: {
        m0: m0,
        m1: m1,
        m2: m2,
        m3: m3,
        m4: m4,
        m5: m5,
        m6: m6,
        m7: m7,
        m8: m8,
        m9: m9,
        m10: m10,
        m11: m11,
        m12: m12,
        m13: m13,
        l0: l0,
        m14: m14,
        m15: m15,
        m16: m16,
        m17: m17,
        g1: g1,
        l1: l1,
        m21: m21,
        m22: m22,
        m23: m23,
        m24: m24,
        m25: m25,
        m26: m26,
        m27: m27,
        m28: m28,
        m29: m29,
        m30: m30,
        g2: g2,
        l2: l2,
        g4: g4,
        l3: l3,
        g6: g6,
        g7: g7,
        l4: l4,
        m33: m33,
        m34: m34,
      },
    }
  )
}
var recyclableRender = false
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ 138:
/*!*******************************************************************************************************************!*\
  !*** D:/phpstudy_pro/WWW/longzhehutongdaima/物流系统小程序/pages/order/orderForm/orderForm.vue?vue&type=script&lang=js& ***!
  \*******************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _HBuilderX_plugins_uniapp_cli_node_modules_babel_loader_lib_index_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_13_1_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_script_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_orderForm_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../../HBuilderX/plugins/uniapp-cli/node_modules/babel-loader/lib!../../../../../../../HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/vue-cli-plugin-uni/packages/webpack-preprocess-loader??ref--13-1!../../../../../../../HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/webpack-uni-mp-loader/lib/script.js!../../../../../../../HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/vue-cli-plugin-uni/packages/vue-loader/lib??vue-loader-options!../../../../../../../HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/webpack-uni-mp-loader/lib/style.js!./orderForm.vue?vue&type=script&lang=js& */ 139);
/* harmony import */ var _HBuilderX_plugins_uniapp_cli_node_modules_babel_loader_lib_index_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_13_1_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_script_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_orderForm_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_HBuilderX_plugins_uniapp_cli_node_modules_babel_loader_lib_index_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_13_1_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_script_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_orderForm_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__);
/* harmony reexport (unknown) */ for(var __WEBPACK_IMPORT_KEY__ in _HBuilderX_plugins_uniapp_cli_node_modules_babel_loader_lib_index_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_13_1_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_script_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_orderForm_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__) if(["default"].indexOf(__WEBPACK_IMPORT_KEY__) < 0) (function(key) { __webpack_require__.d(__webpack_exports__, key, function() { return _HBuilderX_plugins_uniapp_cli_node_modules_babel_loader_lib_index_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_13_1_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_script_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_orderForm_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__[key]; }) }(__WEBPACK_IMPORT_KEY__));
 /* harmony default export */ __webpack_exports__["default"] = (_HBuilderX_plugins_uniapp_cli_node_modules_babel_loader_lib_index_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_13_1_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_script_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_orderForm_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0___default.a); 

/***/ }),

/***/ 139:
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib!./node_modules/@dcloudio/vue-cli-plugin-uni/packages/webpack-preprocess-loader??ref--13-1!./node_modules/@dcloudio/webpack-uni-mp-loader/lib/script.js!./node_modules/@dcloudio/vue-cli-plugin-uni/packages/vue-loader/lib??vue-loader-options!./node_modules/@dcloudio/webpack-uni-mp-loader/lib/style.js!D:/phpstudy_pro/WWW/longzhehutongdaima/物流系统小程序/pages/order/orderForm/orderForm.vue?vue&type=script&lang=js& ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
/* WEBPACK VAR INJECTION */(function(uni) {

var _interopRequireDefault = __webpack_require__(/*! @babel/runtime/helpers/interopRequireDefault */ 4);
Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;
var _typeof2 = _interopRequireDefault(__webpack_require__(/*! @babel/runtime/helpers/typeof */ 13));
var _slicedToArray2 = _interopRequireDefault(__webpack_require__(/*! @babel/runtime/helpers/slicedToArray */ 5));
var _mixin = __webpack_require__(/*! @/https/mixin.js */ 59);
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
var _default = {
  mixins: [_mixin.systemInfo],
  data: function data() {
    return {
      imgUrl: this.$utils.imgUrl,
      editOrderId: null,
      _detailLoaded: false,
      // 驳回状态：仅可改 reject_field 对应项
      orderPayStatus: null,
      rejectField: '',
      rejectReason: '',
      riqi: '',
      zuizao: '',
      zuiwan: '',
      riqis: '',
      zuizaos: '',
      zuiwans: '',
      indexs: null,
      cityOptions: [{
        value: '测试',
        label: '测试'
      }],
      carType: 1,
      isinvoice: 0,
      delivery: 0,
      check: false,
      zhuangAddress: {},
      xieAddress: {},
      carList: [],
      carIndex: 0,
      typeList: [],
      typeIndex: 0,
      baoList: [],
      showPackagePanel: false,
      packageSelections: [],
      _pendingPackageList: null,
      songList: [],
      songIndex: -1,
      huiList: [[], []],
      huiLists: [],
      huiIndex: [-1, -1],
      chaiList: [[], []],
      chaiIndex: [-1, -1],
      qitaList: [],
      showOtherPanel: false,
      otherSelections: [],
      showDeliveryPanel: false,
      showLoadingPanel: false,
      deliveryRequirementsList: [],
      loadingRequirementsList: [],
      selectedDeliveryRequirements: [],
      selectedLoadingRequirements: [],
      kaiType: ['个人', '公司'],
      kaiIndex: 0,
      kongType: ['到站点等通知放货', '到收货地等通知放货'],
      kongIndex: -1,
      xinType: ['收件方', '发件方'],
      xinIndex: -1,
      fuwuList: ['派送', '自提'],
      fuwuIndex: 0,
      remark: '',
      pay_type: 1,
      pay_party: 0,
      type: 1,
      isrequirements: 0,
      quantity: 1,
      weight: '',
      long: '',
      wide: '',
      hige: '',
      direction: '',
      pay_price: '',
      sizeList: [{
        long: '',
        wide: '',
        hige: ''
      }],
      behalf_price: '',
      bank_branch: '',
      open_number: '',
      bank_type: '',
      open_name: '',
      open_type: '',
      charge: '',
      tax_point: '',
      company_letterhead: '',
      company_tax_id: '',
      company_email: '',
      company_mobile: '',
      bank_deposits: '',
      earliest_time: '',
      latest_time: '',
      goods_name: '',
      goods_image: '',
      hourList: [],
      hourLists: [],
      hourList1: [],
      hourLists1: [],
      hourList2: [],
      tijiaos: true,
      quickInputText: '',
      quickInputType: 1,
      userType: 1,
      information: '',
      deposit: '',
      information_image: ''
    };
  },
  onLoad: function onLoad(option) {
    this.getSystemInfo();
    this.editOrderId = option.order_id || option.id || null;
    this.loadx();
    this.jinri();
  },
  onShow: function onShow() {
    var _this = this;
    // 兼容从 editOrder 中转：若 storage 有数据则用，用完即清
    var editOrderData = uni.getStorageSync('editOrderData');
    var editOrderId = uni.getStorageSync('editOrderId');
    if (editOrderData && editOrderId) {
      uni.removeStorageSync('editOrderData');
      uni.removeStorageSync('editOrderId');
      this.editOrderId = editOrderId;
      this._detailLoaded = true;
      this.fillForm(editOrderData);
      return;
    }
    // 直接进入（带 order_id）：仅未加载过详情时请求一次，避免从地址簿返回时重复请求
    if (this.editOrderId && !this._detailLoaded) {
      this._detailLoaded = true;
      this.$httpapi('Placeorder/orderdetaile', 'POST', {
        order_id: this.editOrderId
      }).then(function (rex) {
        if (rex && rex.data) _this.fillForm(rex.data);
      }).catch(function () {
        uni.showToast({
          title: '加载订单失败',
          icon: 'none'
        });
        setTimeout(function () {
          return uni.navigateBack();
        }, 1500);
      });
      return;
    }
    if (!this.editOrderId) {
      uni.showToast({
        title: '缺少订单信息',
        icon: 'none'
      });
      setTimeout(function () {
        return uni.navigateBack();
      }, 1500);
    }
  },
  computed: {
    // 税点列表：专票=6%/9%，普票=1%/2%
    array: function array() {
      return this.$utils.taxArray(this.type);
    },
    // 是否为「驳回且仅可改某一项」模式
    isRejectOnlyEdit: function isRejectOnlyEdit() {
      return Number(this.orderPayStatus) === 8 && (this.rejectField || '').trim() !== '';
    },
    isInternalStaff: function isInternalStaff() {
      return Number(this.userType) === 2 || Number(this.userType) === 3;
    },
    canAddMultipleSizes: function canAddMultipleSizes() {
      return this.typeList && this.typeList[this.typeIndex] && this.typeList[this.typeIndex].switch == 1;
    },
    packageSummary: function packageSummary() {
      return (this.packageSelections || []).filter(function (i) {
        return i.quantity > 0;
      }).map(function (i) {
        return "".concat(i.name, "x").concat(i.quantity);
      }).join('，') || '';
    },
    otherSummary: function otherSummary() {
      return (this.otherSelections || []).map(function (i) {
        return i.name;
      }).join('，') || '';
    },
    deliverySummary: function deliverySummary() {
      var _this2 = this;
      return (this.deliveryRequirementsList || []).filter(function (i) {
        return (_this2.selectedDeliveryRequirements || []).includes(i.id);
      }).map(function (i) {
        return i.name;
      }).join('，') || '';
    },
    loadingSummary: function loadingSummary() {
      var _this3 = this;
      return (this.loadingRequirementsList || []).filter(function (i) {
        return (_this3.selectedLoadingRequirements || []).includes(i.id);
      }).map(function (i) {
        return i.name;
      }).join('，') || '';
    }
  },
  methods: {
    selectTaxType: function selectTaxType(t) {
      this.type = t;
      this.syncTaxIndexs();
    },
    // 根据发票类型与已选税点校正索引，避免切换类型后索引越界或错位
    syncTaxIndexs: function syncTaxIndexs() {
      var arr = this.array;
      if (this.tax_point) {
        var idx = arr.indexOf(this.tax_point);
        this.indexs = idx >= 0 ? idx : 0;
      } else if (this.indexs == null || this.indexs >= arr.length) {
        this.indexs = 0;
      }
    },
    goBack: function goBack() {
      uni.navigateBack({
        fail: function fail() {
          uni.switchTab({
            url: '/pages/order/order'
          });
        }
      });
    },
    applyPendingPackageSelection: function applyPendingPackageSelection() {
      var _this4 = this;
      if (!this._pendingPackageList || this._pendingPackageList.length === 0 || !this.baoList || this.baoList.length === 0) return;
      this.packageSelections = [];
      this._pendingPackageList.forEach(function (pkgItem) {
        var pendingId = pkgItem && pkgItem.id != null ? String(pkgItem.id).trim() : '';
        var pendingName = pkgItem && pkgItem.name != null ? String(pkgItem.name).trim() : '';
        var matched = _this4.baoList.find(function (bao) {
          var baoId = bao && bao.id != null ? String(bao.id).trim() : '';
          var baoName = bao && bao.name != null ? String(bao.name).trim() : '';
          return pendingId && baoId && pendingId === baoId || pendingName && baoName && pendingName === baoName;
        });
        if (matched) _this4.packageSelections.push({
          id: matched.id,
          name: matched.name,
          quantity: pkgItem.quantity || 1
        });
      });
      this._pendingPackageList = null;
      this.calculateTotalPackageQuantity();
    },
    // 驳回仅改一项时：判断某区块是否可编辑。keys 为后端 reject_field 可能取值（如 'loading_address','loading'）
    isFieldEditable: function isFieldEditable() {
      if (!this.isRejectOnlyEdit) return true;
      var r = (this.rejectField || '').trim().toLowerCase();
      if (!r) return true;
      for (var _len = arguments.length, keys = new Array(_len), _key = 0; _key < _len; _key++) {
        keys[_key] = arguments[_key];
      }
      return keys.some(function (k) {
        return k && r === String(k).toLowerCase();
      });
    },
    _parseRequirementIds: function _parseRequirementIds(val) {
      if (val == null || val === '') return [];
      if (Array.isArray(val)) return val.map(function (id) {
        return typeof id === 'number' ? id : isNaN(Number(id)) ? id : Number(id);
      });
      var str = String(val).trim();
      if (!str) return [];
      return str.split(',').map(function (s) {
        var n = Number(s.trim());
        return isNaN(n) ? s.trim() : n;
      }).filter(function (v) {
        return v !== '' && v !== undefined;
      });
    },
    fillForm: function fillForm(details) {
      var _this5 = this;
      if (!details) return;
      this.orderPayStatus = details.pay_status != null ? details.pay_status : null;
      this.rejectField = details.reject_field != null && details.reject_field !== '' ? String(details.reject_field).trim() : '';
      this.rejectReason = details.reject || details.reject_reason || '';
      if (details.find_car_type == '专车') this.carType = 0;else if (details.find_car_type == '配车') this.carType = 1;else this.carType = 2;
      this.quantity = details.quantity || 1;
      this.weight = details.weight;
      this.long = details.long;
      this.wide = details.wide;
      this.hige = details.hige;
      if (details.sizeList && Array.isArray(details.sizeList) && details.sizeList.length > 0) {
        this.sizeList = details.sizeList.map(function (item) {
          return {
            long: item.long || '',
            wide: item.wide || '',
            hige: item.hige || ''
          };
        });
      } else {
        this.sizeList = [{
          long: details.long || '',
          wide: details.wide || '',
          hige: details.hige || ''
        }];
      }
      this.direction = details.direction;
      this.pay_price = details.pay_price != null && details.pay_price !== '' ? details.pay_price : '';
      this.isinvoice = details.isinvoice;
      if (details.pay_type == '到付' || details.pay_type == '到付-寄货方' || details.pay_type == '到付-收货方') {
        this.pay_type = 1;
        this.pay_party = details.pay_type == '到付-收货方' ? 1 : 0;
      } else if (details.pay_type == '现付' || details.pay_type == '寄付') {
        this.pay_type = 0;
        this.pay_party = 0;
      } else {
        this.pay_type = 2;
        this.pay_party = 0;
      }
      this.delivery = details.delivery == '代收货款' ? 1 : 0;
      this.earliest_time = details.earliest_time;
      this.latest_time = details.latest_time;
      this.isrequirements = details.isrequirements || 0;
      this.behalf_price = details.behalf_price;
      this.bank_branch = details.bank_branch;
      this.open_number = details.open_number;
      this.bank_type = details.bank_type;
      this.open_name = details.open_name;
      this.charge = details.charge;
      this.type = details.type;
      this.tax_point = details.tax_point;
      this.syncTaxIndexs();
      this.company_letterhead = details.company_letterhead;
      this.company_tax_id = details.company_tax_id;
      this.company_email = details.company_email;
      this.company_mobile = details.company_mobile;
      this.bank_deposits = details.bank_deposits;
      this.information = details.information || '';
      this.deposit = details.deposit || '';
      this.information_image = details.information_image || '';
      this.goods_name = details.goods_name || '';
      this.goods_image = details.goods_image || '';
      this.zhuangAddress = details.loading_address || {};
      this.xieAddress = details.unload_address || {};
      this.remark = details.break || details.remark || '';
      if (details.packaging_list && Array.isArray(details.packaging_list) && details.packaging_list.length > 0) {
        this._pendingPackageList = details.packaging_list;
      } else {
        var rawPackaging = details.packaging_id || details.packaging || details.pack_type || details.pack_id;
        if (rawPackaging) this._pendingPackageList = [{
          id: rawPackaging,
          name: rawPackaging,
          quantity: 1
        }];
      }
      this.applyPendingPackageSelection();
      // 提货要求、专线装货要求：数据库字段为 deliveryrequirements_id / loadingrequirements_id
      var deliveryIdsRaw = details.deliveryrequirements_id != null ? details.deliveryrequirements_id : details.deliveryrequirements;
      var loadingIdsRaw = details.loadingrequirements_id != null ? details.loadingrequirements_id : details.loadingrequirements;
      this.selectedDeliveryRequirements = this._parseRequirementIds(deliveryIdsRaw);
      this.selectedLoadingRequirements = this._parseRequirementIds(loadingIdsRaw);
      this.$nextTick(function () {
        var deliveryIds = _this5._parseRequirementIds(deliveryIdsRaw);
        var loadingIds = _this5._parseRequirementIds(loadingIdsRaw);
        if (_this5.deliveryRequirementsList && deliveryIds.length) {
          _this5.selectedDeliveryRequirements = _this5.deliveryRequirementsList.filter(function (item) {
            return deliveryIds.some(function (id) {
              return id == item.id || String(id) === String(item.id);
            });
          }).map(function (item) {
            return item.id;
          });
        }
        if (_this5.loadingRequirementsList && loadingIds.length) {
          _this5.selectedLoadingRequirements = _this5.loadingRequirementsList.filter(function (item) {
            return loadingIds.some(function (id) {
              return id == item.id || String(id) === String(item.id);
            });
          }).map(function (item) {
            return item.id;
          });
        }
        if (_this5.carList && details.car_type_id) {
          for (var i = 0; i < _this5.carList.length; i++) {
            if (details.car_type_id == _this5.carList[i].name) {
              _this5.carIndex = i;
              break;
            }
          }
        }
        if (_this5.typeList && details.goods_type_id) {
          for (var _i = 0; _i < _this5.typeList.length; _i++) {
            if (details.goods_type_id == _this5.typeList[_i].name) {
              _this5.typeIndex = _i;
              break;
            }
          }
        }
        if (_this5.songList && details.delivery_type_id) {
          for (var _i2 = 0; _i2 < _this5.songList.length; _i2++) {
            if (details.delivery_type_id == _this5.songList[_i2].name) {
              _this5.songIndex = _i2;
              break;
            }
          }
        }
        if (_this5.huiList[1] && details.receipt_type_id) {
          for (var _i3 = 0; _i3 < (_this5.huiList[1].length || 0); _i3++) {
            if (details.receipt_type_id == (_this5.huiList[1][_i3] && _this5.huiList[1][_i3].name)) {
              _this5.huiIndex = [_this5.huiIndex[0] >= 0 ? _this5.huiIndex[0] : 0, _i3];
              break;
            }
          }
        }
        if (_this5.fuwuList && details.service) {
          for (var _i4 = 0; _i4 < _this5.fuwuList.length; _i4++) {
            if (details.service == _this5.fuwuList[_i4]) {
              _this5.fuwuIndex = _i4;
              break;
            }
          }
        }
        if (_this5.chaiList[0] && details.unpack_id) {
          for (var _i5 = 0; _i5 < (_this5.chaiList[0].length || 0); _i5++) {
            if (details.unpack_id == (_this5.chaiList[0][_i5] && _this5.chaiList[0][_i5].name)) {
              _this5.chaiIndex = [_i5, _this5.chaiIndex[1] >= 0 ? _this5.chaiIndex[1] : 0];
              break;
            }
          }
        }
        if (_this5.kongType && details.control) {
          for (var _i6 = 0; _i6 < _this5.kongType.length; _i6++) {
            if (details.control == _this5.kongType[_i6]) {
              _this5.kongIndex = _i6;
              break;
            }
          }
        }
        if (_this5.qitaList && details.other_id) {
          for (var _i7 = 0; _i7 < _this5.qitaList.length; _i7++) {
            if (details.other_id == _this5.qitaList[_i7].name || details.other_id == _this5.qitaList[_i7].id) {
              _this5.otherSelections = [{
                id: _this5.qitaList[_i7].id,
                name: _this5.qitaList[_i7].name,
                price: _this5.qitaList[_i7].price || _this5.qitaList[_i7].other_type_price
              }];
              break;
            }
          }
        }
        if (_this5.kaiType && details.open_type) {
          for (var _i8 = 0; _i8 < _this5.kaiType.length; _i8++) {
            if (details.open_type == _this5.kaiType[_i8]) {
              _this5.kaiIndex = _i8;
              break;
            }
          }
        }
        if (details.text_message == '短信通知收件方') _this5.xinIndex = 0;else _this5.xinIndex = 1;
        if (details.earliest_time) {
          var p = details.earliest_time.split(' ');
          if (p[0]) _this5.riqi = p[0];
          if (p[1]) _this5.zuizao = p[1].replace(/:00$/, '');
        }
        if (details.latest_time) {
          var _p = details.latest_time.split(' ');
          if (_p[0]) _this5.riqis = _p[0];
          if (_p[1]) _this5.zuiwan = _p[1].replace(/:00$/, '');
        }
      });
    },
    loadx: function loadx() {
      var _this6 = this;
      this.$httpapi('Placeorder/car_type', 'POST', {}).then(function (res) {
        _this6.carList = res.data || [];
      });
      this.$httpapi('Placeorder/goods_type', 'GET', {}).then(function (res) {
        _this6.typeList = res.data || [];
      });
      this.$httpapi('Placeorder/packaging', 'GET', {}).then(function (res) {
        _this6.baoList = res.data || [];
        _this6.applyPendingPackageSelection();
      });
      this.$httpapi('Placeorder/delivery_type', 'GET', {}).then(function (res) {
        _this6.songList = res.data || [];
      });
      this.$httpapi('Placeorder/other', 'GET', {}).then(function (res) {
        _this6.qitaList = res.data || [];
      });
      this.$httpapi('index/deliveryrequirements', 'GET', {}).then(function (res) {
        _this6.deliveryRequirementsList = res.data || [];
      });
      this.$httpapi('index/loadingrequirements', 'GET', {}).then(function (res) {
        _this6.loadingRequirementsList = res.data || [];
      });
      this.$httpapi('Placeorder/receipt_type', 'GET', {}).then(function (res) {
        _this6.huiList = [[], []];
        _this6.huiIndex = [-1, -1];
        _this6.huiLists = [];
        if (res.data && Array.isArray(res.data) && res.data.length > 0) {
          _this6.huiLists = res.data;
          var typeSet = new Set();
          res.data.forEach(function (item) {
            if (item.type) typeSet.add(item.type);
          });
          var typeArray = Array.from(typeSet).map(function (t) {
            return {
              name: t
            };
          });
          if (typeArray.length > 0) {
            var subOptions = res.data.filter(function (item) {
              return item.type === typeArray[0].name;
            }).map(function (item) {
              return {
                id: item.id,
                name: item.name,
                price: item.receipt_type_price,
                type: item.type
              };
            });
            _this6.huiList = [typeArray, subOptions];
          }
        }
      });
      this.$httpapi('Placeorder/unpack', 'GET', {}).then(function (res) {
        _this6.chaiList = [[], []];
        _this6.chaiIndex = [-1, -1];
        var numArray = [];
        for (var i = 1; i <= 9999; i++) {
          numArray.push({
            name: i
          });
        }
        _this6.chaiList[0] = res.data || [];
        _this6.chaiList[1] = numArray;
      });
      this.$httpapi('Opinion/getuserinfo', 'GET', {}).then(function (res) {
        if (res && res.data) {
          var _res$data$membertype;
          var mt = (_res$data$membertype = res.data.membertype) !== null && _res$data$membertype !== void 0 ? _res$data$membertype : res.data.user_type;
          _this6.userType = mt !== undefined && mt !== null ? Number(mt) : 1;
        }
      });
    },
    jinri: function jinri() {
      this.cityOptions = [];
      this.hourList = [];
      this.hourLists = [];
      this.hourList1 = [];
      this.hourLists1 = [];
      this.hourList2 = [];
      var today = new Date();
      var todayTimestamp = today.setHours(0, 0, 0, 0);
      for (var i = 0; i < 60; i++) {
        var currentDate = new Date(todayTimestamp);
        currentDate.setDate(currentDate.getDate() + i);
        var dateTimestamp = currentDate.setHours(0, 0, 0, 0);
        var diffDays = Math.floor((dateTimestamp - todayTimestamp) / (1000 * 60 * 60 * 24));
        var dateDesc = '',
          xiandate = '';
        var year = currentDate.getFullYear(),
          month = String(currentDate.getMonth() + 1).padStart(2, '0'),
          day = String(currentDate.getDate()).padStart(2, '0');
        xiandate = "".concat(year, "-").concat(month, "-").concat(day);
        if (diffDays === 0) dateDesc = '今天';else if (diffDays === 1) dateDesc = '明天';else dateDesc = "".concat(month, "-").concat(day);
        var weekMap = ['日', '一', '二', '三', '四', '五', '六'];
        var weekDesc = '星期' + weekMap[currentDate.getDay()];
        this.cityOptions.push({
          label: dateDesc + ' ' + weekDesc,
          value: xiandate
        });
      }
      var now = new Date();
      var totalMinutes = now.getHours() * 60 + now.getMinutes();
      var remainder = totalMinutes % 30;
      if (remainder !== 0) totalMinutes += 30 - remainder;
      for (; totalMinutes <= 24 * 60; totalMinutes += 30) {
        var hour = Math.floor(totalMinutes / 60),
          minute = totalMinutes % 60;
        var timeStr = "".concat(String(hour).padStart(2, '0'), ":").concat(String(minute).padStart(2, '0'));
        var item = {
          label: timeStr,
          value: timeStr
        };
        this.hourList.push(item);
        this.hourLists.push(item);
        this.hourList1.push(item);
        this.hourLists1.push(item);
      }
      for (var _hour = 1; _hour <= 24; _hour++) {
        for (var _i9 = 0, _arr = [0, 30]; _i9 < _arr.length; _i9++) {
          var _minute = _arr[_i9];
          var _timeStr = "".concat(String(_hour).padStart(2, '0'), ":").concat(String(_minute).padStart(2, '0'));
          this.hourList2.push({
            label: _timeStr,
            value: _timeStr
          });
        }
      }
      this.riqis = this.cityOptions[0] && this.cityOptions[0].label;
      this.zuizaos = this.hourList[0] && this.hourList[0].label;
      this.riqi = this.cityOptions[0] && this.cityOptions[0].value;
      this.zuizao = this.hourList[0] && this.hourList[0].value;
      this.setLatestTimeAfterEarliest();
    },
    setLatestTimeAfterEarliest: function setLatestTimeAfterEarliest() {
      if (!this.zuizao || !this.hourLists || !this.hourLists.length) return;
      var _this$zuizao$split$ma = this.zuizao.split(':').map(Number),
        _this$zuizao$split$ma2 = (0, _slicedToArray2.default)(_this$zuizao$split$ma, 2),
        earliestHour = _this$zuizao$split$ma2[0],
        earliestMinute = _this$zuizao$split$ma2[1];
      var latestMinute = earliestMinute + 30,
        latestHour = earliestHour;
      if (latestMinute >= 60) {
        latestMinute = 0;
        latestHour += 1;
      }
      if (latestHour > 24) {
        latestHour = 24;
        latestMinute = 0;
      }
      var latestTimeStr = "".concat(String(latestHour).padStart(2, '0'), ":").concat(String(latestMinute).padStart(2, '0'));
      var idx = this.hourLists.findIndex(function (item) {
        return item.value === latestTimeStr;
      });
      if (idx >= 0) {
        this.zuiwan = this.hourLists[idx].value;
        this.zuiwans = this.hourLists[idx].label;
      } else {
        var found = this.hourLists.find(function (item) {
          var _item$value$split$map = item.value.split(':').map(Number),
            _item$value$split$map2 = (0, _slicedToArray2.default)(_item$value$split$map, 2),
            h = _item$value$split$map2[0],
            m = _item$value$split$map2[1];
          return h > latestHour || h === latestHour && m >= latestMinute;
        });
        if (found) {
          this.zuiwan = found.value;
          this.zuiwans = found.label;
        }
      }
      this.$forceUpdate();
    },
    onChange: function onChange(e) {
      var _this7 = this;
      this.riqi = this.cityOptions[e.index].value;
      this.riqis = this.cityOptions[e.index].label;
      this.hourList = e.index > 0 ? this.hourList2 : this.hourLists1;
      this.hourLists = this.hourList;
      this.zuizao = this.hourList[0].value;
      this.zuizaos = this.hourList[0].label;
      this.$nextTick(function () {
        return _this7.setLatestTimeAfterEarliest();
      });
    },
    onChange1: function onChange1(e) {
      this.zuizao = this.hourList[e.index].value;
      this.zuizaos = this.hourList[e.index].label;
      this.setLatestTimeAfterEarliest();
    },
    onChange2: function onChange2(e) {
      this.zuiwan = this.hourLists[e.index].value;
      this.zuiwans = this.hourLists[e.index].label;
    },
    queding: function queding() {
      this.earliest_time = this.riqi + ' ' + this.zuizao + ':00';
      this.latest_time = this.riqi + ' ' + this.zuiwan + ':00';
      this.$refs.popup.close();
    },
    guanbi: function guanbi() {
      this.$refs.popup.close();
    },
    fahuo: function fahuo() {
      this.$refs.popup.open('bottom');
    },
    sanitizeNumber: function sanitizeNumber(value, decimalPlaces) {
      value = String(value || '').replace(/[^\d.]/g, '');
      var parts = value.split('.');
      if (parts.length > 1) value = parts[0] + '.' + parts.slice(1).join('');
      if (decimalPlaces === 0) return parts[0] || '';
      if (decimalPlaces != null && parts.length > 1) return parts[0] + '.' + (parts[1] || '').slice(0, decimalPlaces);
      return value;
    },
    onNumberInput: function onNumberInput(field, value, decimalPlaces) {
      this[field] = this.sanitizeNumber(value, decimalPlaces);
    },
    zhongliang: function zhongliang() {},
    chang: function chang() {
      if (this.long && this.wide && this.hige) {
        var nums = this.long * this.wide * this.hige;
        this.direction = nums.toFixed(4);
      }
    },
    onSizeInput: function onSizeInput(index, key, value) {
      this.$set(this.sizeList[index], key, this.sanitizeNumber(value, 0));
      this.canAddMultipleSizes ? this.calculateTotalDirection() : this.chang();
    },
    changMultiple: function changMultiple(index) {
      this.calculateTotalDirection();
    },
    calculateTotalDirection: function calculateTotalDirection() {
      var total = 0;
      this.sizeList.forEach(function (item) {
        if (item.long && item.wide && item.hige) total += parseFloat(item.long) * parseFloat(item.wide) * parseFloat(item.hige);
      });
      this.direction = total.toFixed(4);
    },
    addSize: function addSize() {
      this.sizeList.push({
        long: '',
        wide: '',
        hige: ''
      });
    },
    removeSize: function removeSize(index) {
      if (this.sizeList.length > 1) {
        this.sizeList.splice(index, 1);
        this.calculateTotalDirection();
      }
    },
    jianjian: function jianjian() {
      if (this.quantity > 1) this.quantity--;
    },
    onQuantityInput: function onQuantityInput(e) {
      var num = parseInt(e.detail.value, 10);
      if (!isNaN(num) && num >= 1) this.quantity = num;
    },
    onQuantityBlur: function onQuantityBlur(e) {
      var num = parseInt(e.detail.value, 10);
      this.quantity = isNaN(num) || num < 1 ? 1 : num;
    },
    togglePackagePanel: function togglePackagePanel() {
      this.showPackagePanel = !this.showPackagePanel;
    },
    isPackageSelected: function isPackageSelected(id) {
      return (this.packageSelections || []).some(function (item) {
        return item.id === id;
      });
    },
    getPackageQuantity: function getPackageQuantity(id) {
      var t = (this.packageSelections || []).find(function (item) {
        return item.id === id;
      });
      return t && t.quantity ? t.quantity : 1;
    },
    calculateTotalPackageQuantity: function calculateTotalPackageQuantity() {
      var total = (this.packageSelections || []).reduce(function (sum, item) {
        return sum + (item.quantity || 1);
      }, 0);
      this.quantity = total > 0 ? total : 1;
    },
    togglePackage: function togglePackage(item) {
      if (!item || !item.id) return;
      var arr = this.packageSelections || [];
      var idx = arr.findIndex(function (p) {
        return p.id === item.id;
      });
      if (idx > -1) arr.splice(idx, 1);else arr.push({
        id: item.id,
        name: item.name,
        quantity: 1
      });
      this.packageSelections = arr;
      this.calculateTotalPackageQuantity();
    },
    changePackageQuantity: function changePackageQuantity(id, delta) {
      var t = (this.packageSelections || []).find(function (item) {
        return item.id === id;
      });
      if (!t) return;
      t.quantity = (t.quantity || 1) + delta;
      if (t.quantity < 1) t.quantity = 1;
      this.calculateTotalPackageQuantity();
    },
    toggleDeliveryRequirement: function toggleDeliveryRequirement(item) {
      if (!item || !item.id) return;
      var idx = (this.selectedDeliveryRequirements || []).indexOf(item.id);
      if (idx > -1) this.selectedDeliveryRequirements.splice(idx, 1);else this.selectedDeliveryRequirements.push(item.id);
    },
    toggleLoadingRequirement: function toggleLoadingRequirement(item) {
      if (!item || !item.id) return;
      var idx = (this.selectedLoadingRequirements || []).indexOf(item.id);
      if (idx > -1) this.selectedLoadingRequirements.splice(idx, 1);else this.selectedLoadingRequirements.push(item.id);
    },
    toggleDeliveryPanel: function toggleDeliveryPanel() {
      this.showDeliveryPanel = !this.showDeliveryPanel;
    },
    toggleLoadingPanel: function toggleLoadingPanel() {
      this.showLoadingPanel = !this.showLoadingPanel;
    },
    toggleOtherPanel: function toggleOtherPanel() {
      this.showOtherPanel = !this.showOtherPanel;
    },
    isOtherSelected: function isOtherSelected(id) {
      return (this.otherSelections || []).some(function (item) {
        return item.id === id;
      });
    },
    toggleOther: function toggleOther(item) {
      var _item$price;
      if (!item || !item.id) return;
      var arr = this.otherSelections || [];
      var idx = arr.findIndex(function (o) {
        return o.id === item.id;
      });
      if (idx > -1) arr.splice(idx, 1);else arr.push({
        id: item.id,
        name: item.name,
        price: (_item$price = item.price) !== null && _item$price !== void 0 ? _item$price : item.other_type_price
      });
      this.otherSelections = arr;
    },
    formatOtherPrice: function formatOtherPrice(item) {
      var _item$price2;
      var p = item && ((_item$price2 = item.price) !== null && _item$price2 !== void 0 ? _item$price2 : item.other_type_price);
      return p === undefined || p === null || p === '' ? '—' : Number(p) === 0 ? '免费' : "\uFFE5".concat(p);
    },
    clearZhuangAddress: function clearZhuangAddress() {
      this.zhuangAddress = {};
    },
    clearXieAddress: function clearXieAddress() {
      this.xieAddress = {};
    },
    swapAddress: function swapAddress() {
      var t = this.zhuangAddress;
      this.zhuangAddress = this.xieAddress;
      this.xieAddress = t;
      uni.showToast({
        title: '地址已互换',
        duration: 1000,
        icon: 'none'
      });
    },
    zhuangAddressClick: function zhuangAddressClick() {
      if (this.zhuangAddress && this.zhuangAddress.address) this.showAddressDetail(this.zhuangAddress, '装货地址');else this.chooseAndSaveAddress(1);
    },
    xieAddressClick: function xieAddressClick() {
      if (this.xieAddress && this.xieAddress.address) this.showAddressDetail(this.xieAddress, '卸货地址');else this.chooseAndSaveAddress(2);
    },
    zhuangxuan: function zhuangxuan() {
      var that = this;
      uni.navigateTo({
        url: '/pages/user/address/address?type=1',
        events: {
          acceptDataFromOpenedPages: function acceptDataFromOpenedPages(data) {
            that.zhuangAddress = data;
          }
        }
      });
    },
    xiexuan: function xiexuan() {
      var that = this;
      uni.navigateTo({
        url: '/pages/user/address/address?type=2',
        events: {
          acceptDataFromOpenedPages: function acceptDataFromOpenedPages(data) {
            that.xieAddress = data;
          }
        }
      });
    },
    showAddressDetail: function showAddressDetail(address, title) {
      var addressData = {
        id: address.id,
        user_name: address.user_name,
        mobile: address.mobile,
        address: address.address,
        detailed_address: address.detailed_address,
        lat: address.lat || address.latitude,
        lng: address.lng || address.longitude,
        default: address.default || 0,
        type: address.type || (title === '装货地址' ? '1' : '2')
      };
      uni.setStorageSync('addressData', addressData);
      uni.navigateTo({
        url: '/pages/user/address/addAddress?type=' + addressData.type
      });
    },
    chooseAndSaveAddress: function chooseAndSaveAddress(type) {
      var that = this;
      uni.chooseLocation({
        success: function success(res) {
          var addressName = res.name || res.address,
            detailedAddress = res.address || '';
          that.$httpapi('Placeorder/address', 'POST', {
            user_name: '',
            company_name: '',
            mobile: '',
            address: addressName,
            detailed_address: detailedAddress,
            default: 0,
            lat: res.latitude,
            lng: res.longitude,
            type: type
          }).then(function (resp) {
            uni.showToast({
              title: resp && (resp.msg || resp.message) || '操作成功',
              duration: 1500,
              icon: 'none'
            });
            if (resp.code == 1) {
              var addressId = typeof resp.data === 'number' || typeof resp.data === 'string' && resp.data !== '' ? resp.data : resp.data && (0, _typeof2.default)(resp.data) === 'object' && (resp.data.id !== undefined ? resp.data.id : resp.data.address_id);
              var addressData = {
                id: addressId,
                user_name: '',
                company_name: '',
                mobile: '',
                address: addressName,
                detailed_address: detailedAddress,
                lat: res.latitude,
                lng: res.longitude
              };
              if (type == 1) that.zhuangAddress = addressData;else if (type == 2) that.xieAddress = addressData;
            }
          });
        }
      });
    },
    agreement: function agreement(type) {
      uni.navigateTo({
        url: '/pages/user/agreement/agreement?type=' + type
      });
    },
    bindPickerChange: function bindPickerChange(e) {
      this.indexs = e.detail.value;
    },
    bindPickerChangeType: function bindPickerChangeType(e) {
      this.typeIndex = e.detail.value;
      if (this.typeList[this.typeIndex] && this.typeList[this.typeIndex].switch == 1 && (this.sizeList.length === 0 || this.sizeList.length === 1 && !this.sizeList[0].long)) this.sizeList = [{
        long: this.long || '',
        wide: this.wide || '',
        hige: this.hige || ''
      }];
    },
    bindPickerChangeCar: function bindPickerChangeCar(e) {
      this.carIndex = e.detail.value;
    },
    bindPickerChangekai: function bindPickerChangekai(e) {
      this.kaiIndex = e.detail.value;
    },
    bindPickerChangehui: function bindPickerChangehui(e) {
      this.huiIndex = e.detail.value;
    },
    bindPickerChangesonghuo: function bindPickerChangesonghuo(e) {
      this.songIndex = e.detail.value;
    },
    bindPickerChangefuwu: function bindPickerChangefuwu(e) {
      this.fuwuIndex = e.detail.value;
    },
    bindPickerChangechai: function bindPickerChangechai(e) {
      this.chaiIndex = e.detail.value;
    },
    bindPickerChangekong: function bindPickerChangekong(e) {
      this.kongIndex = e.detail.value;
    },
    bindPickerChangexin: function bindPickerChangexin(e) {
      this.xinIndex = e.detail.value;
    },
    clearSonghuo: function clearSonghuo() {
      this.songIndex = -1;
    },
    clearHui: function clearHui() {
      this.huiIndex = [-1, -1];
    },
    clearChai: function clearChai() {
      this.chaiIndex = [-1, -1];
    },
    clearKong: function clearKong() {
      this.kongIndex = -1;
    },
    clearXin: function clearXin() {
      this.xinIndex = -1;
    },
    columnchanges: function columnchanges(e) {
      if (e.detail.column == 0 && this.huiList[0] && this.huiList[0][e.detail.value]) {
        var selectedType = this.huiList[0][e.detail.value].name;
        var subOptions = (this.huiLists || []).filter(function (item) {
          return item.type === selectedType;
        }).map(function (item) {
          return {
            id: item.id,
            name: item.name,
            price: item.receipt_type_price,
            type: item.type
          };
        });
        this.huiList = [this.huiList[0], subOptions];
        this.huiIndex = [e.detail.value, 0];
        this.$forceUpdate();
      }
    },
    upImage: function upImage() {
      var that = this;
      uni.chooseImage({
        count: 1,
        sizeType: ['original', 'compressed'],
        sourceType: ['album', 'camera'],
        success: function success(res) {
          that.$upShop('common/upload', res.tempFilePaths[0]).then(function (rex) {
            var arr = JSON.parse(rex.data);
            that.goods_image = arr.data.url;
          }).catch(function () {});
        }
      });
    },
    upInformationImage: function upInformationImage() {
      var that = this;
      uni.chooseImage({
        count: 1,
        sizeType: ['original', 'compressed'],
        sourceType: ['album', 'camera'],
        success: function success(res) {
          that.$upShop('common/upload', res.tempFilePaths[0]).then(function (rex) {
            var arr = JSON.parse(rex.data);
            that.information_image = arr.data.url;
          }).catch(function () {});
        }
      });
    },
    previewCarImage: function previewCarImage() {
      if (this.carList[this.carIndex] && this.carList[this.carIndex].car_image) uni.previewImage({
        urls: [this.imgUrl + this.carList[this.carIndex].car_image],
        current: this.imgUrl + this.carList[this.carIndex].car_image
      });
    },
    openQuickInput: function openQuickInput(type) {
      this.quickInputType = type;
      this.quickInputText = '';
      this.$refs.quickInputPopup.open('center');
    },
    closeQuickInput: function closeQuickInput() {
      this.$refs.quickInputPopup.close();
      this.quickInputText = '';
    },
    confirmQuickInput: function confirmQuickInput() {
      if (!this.quickInputText || !this.quickInputText.trim()) {
        uni.showToast({
          title: '请输入地址信息',
          duration: 1500,
          icon: 'none'
        });
        return;
      }
      var parsed = this.parseAddressText(this.quickInputText.trim());
      if (!parsed.address) {
        uni.showToast({
          title: '无法识别地址，请检查格式',
          duration: 1500,
          icon: 'none'
        });
        return;
      }
      this.saveQuickInputAddress(parsed, this.quickInputType);
    },
    parseAddressText: function parseAddressText(text) {
      var result = {
        user_name: '',
        mobile: '',
        address: ''
      };
      var originalText = text.trim();
      var mobileRegex = /1[3-9]\d[\s\-()]?\d{4}[\s\-()]?\d{4}/;
      var mobileMatch = originalText.match(mobileRegex);
      if (mobileMatch) {
        result.mobile = mobileMatch[0].replace(/[\s\-()]/g, '');
        var mobileIndex = originalText.indexOf(mobileMatch[0]);
        var textAfter = originalText.substring(mobileIndex + mobileMatch[0].length).trim();
        var textBefore = originalText.substring(0, mobileIndex).trim();
        if (textAfter.length <= 3) {
          var nameMatch = textBefore.match(/([\u4e00-\u9fa5]{2,4})\s*$/);
          if (nameMatch) {
            result.user_name = nameMatch[1];
            result.address = textBefore.substring(0, textBefore.length - nameMatch[1].length).trim();
          } else result.address = textBefore;
        } else {
          var _nameMatch = textBefore.match(/^([\u4e00-\u9fa5]{2,4})(\s|$)/);
          if (_nameMatch) {
            result.user_name = _nameMatch[1];
            result.address = textAfter;
          } else result.address = textAfter;
        }
      } else {
        var _nameMatch2 = text.match(/^([\u4e00-\u9fa5]{2,4})(\s|$)/);
        if (_nameMatch2 && (text.substring(_nameMatch2[1].length).length >= 8 || /省|市|区|县|街道|路|号/.test(text))) {
          result.user_name = _nameMatch2[1];
          result.address = text.substring(_nameMatch2[1].length).trim();
        } else result.address = originalText;
      }
      result.address = (result.address || '').replace(/\s+/g, ' ').trim() || originalText;
      return result;
    },
    saveQuickInputAddress: function saveQuickInputAddress(parsed, type) {
      var that = this;
      uni.showLoading({
        title: '正在保存地址...'
      });
      that.$httpapi('Placeorder/address', 'POST', {
        user_name: parsed.user_name || '',
        mobile: parsed.mobile || '',
        address: parsed.address,
        detailed_address: '',
        default: 0,
        lat: '',
        lng: '',
        type: type
      }).then(function (res) {
        uni.hideLoading();
        uni.showToast({
          title: res && (res.msg || res.message) || '保存成功',
          duration: 1500,
          icon: 'none'
        });
        if (res.code == 1) {
          var addressId = typeof res.data === 'number' || typeof res.data === 'string' && res.data !== '' ? res.data : res.data && (0, _typeof2.default)(res.data) === 'object' && res.data.id;
          var addressData = {
            id: addressId,
            user_name: parsed.user_name || '',
            mobile: parsed.mobile || '',
            address: parsed.address,
            detailed_address: '',
            lat: '',
            lng: ''
          };
          if (type == 1) that.zhuangAddress = addressData;else if (type == 2) that.xieAddress = addressData;
          that.closeQuickInput();
        }
      }).catch(function () {
        uni.hideLoading();
        uni.showToast({
          title: '保存地址失败',
          duration: 1500,
          icon: 'none'
        });
      });
    },
    tijiao: function tijiao() {
      var _this8 = this;
      if (!this.check) {
        uni.showToast({
          title: '请阅读并同意下单须知',
          duration: 1500,
          icon: 'none'
        });
        return;
      }
      this.earliest_time = this.riqi + ' ' + this.zuizao + ':00';
      this.latest_time = this.riqi + ' ' + this.zuiwan + ':00';
      var find_car_type = this.carType == 0 ? '专车' : this.carType == 1 ? '配车' : '小票快运';
      if (!this.tijiaos) return;
      this.tijiaos = false;
      var canAddMultiple = this.typeList && this.typeList[this.typeIndex] && this.typeList[this.typeIndex].switch == 1;
      var sizeData = canAddMultiple && this.sizeList && this.sizeList.length > 0 ? {
        long: this.sizeList[0].long || this.long,
        wide: this.sizeList[0].wide || this.wide,
        hige: this.sizeList[0].hige || this.hige,
        sizeList: this.sizeList
      } : {
        long: this.long,
        wide: this.wide,
        hige: this.hige
      };
      if (!this.typeList || !this.typeList[this.typeIndex]) {
        uni.showToast({
          title: '请选择货物类型',
          duration: 1500,
          icon: 'none'
        });
        this.tijiaos = true;
        return;
      }
      if (!this.packageSelections || this.packageSelections.length === 0) {
        uni.showToast({
          title: '请选择包装方式',
          duration: 1500,
          icon: 'none'
        });
        this.tijiaos = true;
        return;
      }
      if (!this.carList || !this.carList[this.carIndex]) {
        uni.showToast({
          title: '请选择车型',
          duration: 1500,
          icon: 'none'
        });
        this.tijiaos = true;
        return;
      }
      var zhuangAddressId = this.zhuangAddress && this.zhuangAddress.id;
      if (zhuangAddressId === undefined || zhuangAddressId === null || zhuangAddressId === '') {
        uni.showToast({
          title: '请选择装货地址',
          duration: 1500,
          icon: 'none'
        });
        this.tijiaos = true;
        return;
      }
      var xieAddressId = this.xieAddress && this.xieAddress.id;
      if (xieAddressId === undefined || xieAddressId === null || xieAddressId === '') {
        uni.showToast({
          title: '请选择卸货地址',
          duration: 1500,
          icon: 'none'
        });
        this.tijiaos = true;
        return;
      }
      var requestData = {
        loading: zhuangAddressId,
        unload: xieAddressId,
        find_car_type: find_car_type,
        quantity: this.quantity,
        goods_name: this.goods_name,
        weight: this.weight,
        long: sizeData.long,
        wide: sizeData.wide,
        hige: sizeData.hige,
        direction: parseFloat(this.direction || 0).toFixed(2),
        pay_price: parseFloat(this.pay_price || 0).toFixed(2),
        isinvoice: this.isinvoice === '' || this.isinvoice === null || this.isinvoice === undefined ? 0 : this.isinvoice,
        goods_type_id: this.typeList[this.typeIndex].id,
        pay_type: this.pay_type,
        pay_party: this.pay_type === 1 ? this.pay_party : 0,
        delivery: this.delivery,
        car_type_id: this.carList[this.carIndex].id,
        earliest_time: this.earliest_time,
        latest_time: this.latest_time,
        isrequirements: this.isrequirements,
        goods_image: this.goods_image,
        service: this.fuwuList && this.fuwuList[this.fuwuIndex] ? this.fuwuList[this.fuwuIndex] : '派送',
        break: this.remark || '',
        remark: this.remark || '',
        order_id: this.editOrderId
      };
      if (sizeData.sizeList) requestData.sizeList = sizeData.sizeList;
      if (this.packageSelections && this.packageSelections.length > 0) {
        requestData.packaging_id = this.packageSelections[0].id;
        requestData.packaging_list = this.packageSelections.map(function (item) {
          return {
            name: item.name,
            quantity: item.quantity || 1
          };
        });
      }
      if (this.isrequirements == 1) {
        if (this.songList && this.songList[this.songIndex]) requestData.delivery_type_id = this.songList[this.songIndex].id;
        if (this.huiList[1] && this.huiList[1][this.huiIndex[1]] && this.huiList[1][this.huiIndex[1]].id) requestData.receipt_type_id = this.huiList[1][this.huiIndex[1]].id;
        if (this.chaiList[0] && this.chaiList[0][this.chaiIndex[0]] && this.chaiList[1] && this.chaiList[1][this.chaiIndex[1]]) {
          requestData.unpack_id = this.chaiList[0][this.chaiIndex[0]].id;
          requestData.unpack_num = this.chaiList[1][this.chaiIndex[1]].name;
        }
        if (this.kongType[this.kongIndex]) requestData.control = this.kongType[this.kongIndex];
        if (this.otherSelections && this.otherSelections.length > 0) {
          requestData.other_id = this.otherSelections[0].id;
          requestData.other_list = this.otherSelections.map(function (o) {
            return o.id;
          });
        }
        if (this.xinIndex >= 0 && this.xinType[this.xinIndex] !== undefined) requestData.text_message = parseInt(this.xinIndex) + 1;
        if (this.selectedDeliveryRequirements && this.selectedDeliveryRequirements.length > 0) requestData.deliveryrequirements = this.selectedDeliveryRequirements.join(',');
        if (this.selectedLoadingRequirements && this.selectedLoadingRequirements.length > 0) requestData.loadingrequirements = this.selectedLoadingRequirements.join(',');
      }
      if (this.delivery == 1) {
        requestData.behalf_price = this.behalf_price;
        requestData.bank_branch = this.bank_branch;
        requestData.open_number = this.open_number;
        requestData.bank_type = this.bank_type;
        requestData.open_name = this.open_name;
        if (this.kaiType[this.kaiIndex]) requestData.open_type = this.kaiType[this.kaiIndex];
      }
      if (this.isinvoice == 1) {
        requestData.charge = this.charge;
        requestData.type = this.type;
        if (this.array && this.array[this.indexs]) requestData.tax_point = this.array[this.indexs];
        requestData.company_letterhead = this.company_letterhead;
        requestData.company_tax_id = this.company_tax_id;
        requestData.company_email = this.company_email;
        requestData.company_mobile = this.company_mobile;
        requestData.bank_deposits = this.bank_deposits;
      }
      if (this.isInternalStaff) {
        requestData.information = this.information || '';
        requestData.deposit = this.deposit || '';
        requestData.information_image = this.information_image || '';
      }
      this.$httpapi('Placeorder/update_order', 'POST', requestData).then(function (res) {
        uni.showToast({
          title: res && (res.msg || res.message) || (res.code == 1 ? '修改成功' : '提交失败'),
          duration: 1500,
          icon: res.code == 1 ? 'success' : 'none'
        });
        _this8.tijiaos = true;
        if (res.code == 1) setTimeout(function () {
          uni.switchTab({
            url: '/pages/order/order'
          });
        }, 1500);
      }).catch(function (err) {
        console.error('修改订单失败:', err);
        uni.showToast({
          title: '修改失败，请重试',
          duration: 1500,
          icon: 'none'
        });
        _this8.tijiaos = true;
      });
    }
  }
};
exports.default = _default;
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! ./node_modules/@dcloudio/uni-mp-weixin/dist/index.js */ 2)["default"]))

/***/ }),

/***/ 140:
/*!***************************************************************************************************************************************************!*\
  !*** D:/phpstudy_pro/WWW/longzhehutongdaima/物流系统小程序/pages/order/orderForm/orderForm.vue?vue&type=style&index=0&id=5c7ea7aa&scoped=true&lang=css& ***!
  \***************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _HBuilderX_plugins_uniapp_cli_node_modules_mini_css_extract_plugin_dist_loader_js_ref_6_oneOf_1_0_HBuilderX_plugins_uniapp_cli_node_modules_css_loader_dist_cjs_js_ref_6_oneOf_1_1_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_loaders_stylePostLoader_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_6_oneOf_1_2_HBuilderX_plugins_uniapp_cli_node_modules_postcss_loader_src_index_js_ref_6_oneOf_1_3_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_orderForm_vue_vue_type_style_index_0_id_5c7ea7aa_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../../HBuilderX/plugins/uniapp-cli/node_modules/mini-css-extract-plugin/dist/loader.js??ref--6-oneOf-1-0!../../../../../../../HBuilderX/plugins/uniapp-cli/node_modules/css-loader/dist/cjs.js??ref--6-oneOf-1-1!../../../../../../../HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/vue-cli-plugin-uni/packages/vue-loader/lib/loaders/stylePostLoader.js!../../../../../../../HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/vue-cli-plugin-uni/packages/webpack-preprocess-loader??ref--6-oneOf-1-2!../../../../../../../HBuilderX/plugins/uniapp-cli/node_modules/postcss-loader/src??ref--6-oneOf-1-3!../../../../../../../HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/vue-cli-plugin-uni/packages/vue-loader/lib??vue-loader-options!../../../../../../../HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/webpack-uni-mp-loader/lib/style.js!./orderForm.vue?vue&type=style&index=0&id=5c7ea7aa&scoped=true&lang=css& */ 141);
/* harmony import */ var _HBuilderX_plugins_uniapp_cli_node_modules_mini_css_extract_plugin_dist_loader_js_ref_6_oneOf_1_0_HBuilderX_plugins_uniapp_cli_node_modules_css_loader_dist_cjs_js_ref_6_oneOf_1_1_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_loaders_stylePostLoader_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_6_oneOf_1_2_HBuilderX_plugins_uniapp_cli_node_modules_postcss_loader_src_index_js_ref_6_oneOf_1_3_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_orderForm_vue_vue_type_style_index_0_id_5c7ea7aa_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_HBuilderX_plugins_uniapp_cli_node_modules_mini_css_extract_plugin_dist_loader_js_ref_6_oneOf_1_0_HBuilderX_plugins_uniapp_cli_node_modules_css_loader_dist_cjs_js_ref_6_oneOf_1_1_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_loaders_stylePostLoader_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_6_oneOf_1_2_HBuilderX_plugins_uniapp_cli_node_modules_postcss_loader_src_index_js_ref_6_oneOf_1_3_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_orderForm_vue_vue_type_style_index_0_id_5c7ea7aa_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__);
/* harmony reexport (unknown) */ for(var __WEBPACK_IMPORT_KEY__ in _HBuilderX_plugins_uniapp_cli_node_modules_mini_css_extract_plugin_dist_loader_js_ref_6_oneOf_1_0_HBuilderX_plugins_uniapp_cli_node_modules_css_loader_dist_cjs_js_ref_6_oneOf_1_1_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_loaders_stylePostLoader_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_6_oneOf_1_2_HBuilderX_plugins_uniapp_cli_node_modules_postcss_loader_src_index_js_ref_6_oneOf_1_3_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_orderForm_vue_vue_type_style_index_0_id_5c7ea7aa_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__) if(["default"].indexOf(__WEBPACK_IMPORT_KEY__) < 0) (function(key) { __webpack_require__.d(__webpack_exports__, key, function() { return _HBuilderX_plugins_uniapp_cli_node_modules_mini_css_extract_plugin_dist_loader_js_ref_6_oneOf_1_0_HBuilderX_plugins_uniapp_cli_node_modules_css_loader_dist_cjs_js_ref_6_oneOf_1_1_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_loaders_stylePostLoader_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_6_oneOf_1_2_HBuilderX_plugins_uniapp_cli_node_modules_postcss_loader_src_index_js_ref_6_oneOf_1_3_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_orderForm_vue_vue_type_style_index_0_id_5c7ea7aa_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__[key]; }) }(__WEBPACK_IMPORT_KEY__));
 /* harmony default export */ __webpack_exports__["default"] = (_HBuilderX_plugins_uniapp_cli_node_modules_mini_css_extract_plugin_dist_loader_js_ref_6_oneOf_1_0_HBuilderX_plugins_uniapp_cli_node_modules_css_loader_dist_cjs_js_ref_6_oneOf_1_1_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_loaders_stylePostLoader_js_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_6_oneOf_1_2_HBuilderX_plugins_uniapp_cli_node_modules_postcss_loader_src_index_js_ref_6_oneOf_1_3_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_orderForm_vue_vue_type_style_index_0_id_5c7ea7aa_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0___default.a); 

/***/ }),

/***/ 141:
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/mini-css-extract-plugin/dist/loader.js??ref--6-oneOf-1-0!./node_modules/css-loader/dist/cjs.js??ref--6-oneOf-1-1!./node_modules/@dcloudio/vue-cli-plugin-uni/packages/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/@dcloudio/vue-cli-plugin-uni/packages/webpack-preprocess-loader??ref--6-oneOf-1-2!./node_modules/postcss-loader/src??ref--6-oneOf-1-3!./node_modules/@dcloudio/vue-cli-plugin-uni/packages/vue-loader/lib??vue-loader-options!./node_modules/@dcloudio/webpack-uni-mp-loader/lib/style.js!D:/phpstudy_pro/WWW/longzhehutongdaima/物流系统小程序/pages/order/orderForm/orderForm.vue?vue&type=style&index=0&id=5c7ea7aa&scoped=true&lang=css& ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin
    if(false) { var cssReload; }
  

/***/ })

},[[134,"common/runtime","common/vendor"]]]);
//# sourceMappingURL=../../../../.sourcemap/mp-weixin/pages/order/orderForm/orderForm.js.map
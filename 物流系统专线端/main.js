import App from './App'
import { apiapi,upShop, up  } from "@/https/https.js"
import utils from '@/https/utils.js'

Vue.config.productionTip = false

//直接挂载到原型上  通过this.$api直接调用
Vue.prototype.$httpapi = apiapi;
Vue.prototype.$upShop = upShop;
Vue.prototype.$up = up;
Vue.prototype.$utils=utils;

// #ifndef VUE3
import Vue from 'vue'
import './uni.promisify.adaptor'
Vue.config.productionTip = false
App.mpType = 'app'
const app = new Vue({
  ...App
})
app.$mount()
// #endif

// #ifdef VUE3
import { createSSRApp } from 'vue'
export function createApp() {
  const app = createSSRApp(App)
  return {
    app
  }
}
// #endif
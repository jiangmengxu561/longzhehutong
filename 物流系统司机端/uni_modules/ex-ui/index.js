// 公共函数导入
import utils from './libs/utils/index.js'

// 封装的日期函数
import date from './libs/utils/date.js'

// 关于颜色的配置，特殊场景使用
import color from './libs/config/color.js'

// 关于文件的函数
import file from './libs/utils/file.js'

const $ex = {
	utils,
	color,
	date,
	file
}

// #ifdef VUE2
uni.$ex = $ex
const install = (Vue) => {
	Vue.prototype.$ex = $ex
}
// #endif

// #ifdef VUE3
const install = (Vue) => {
	uni.$ex = $ex
}
// #endif

export default {
	install
}
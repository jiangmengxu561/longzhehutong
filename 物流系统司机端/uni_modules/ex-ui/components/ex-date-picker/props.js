const minDate = new Date().setFullYear(new Date().getFullYear() - 100)
const maxDate = new Date().setFullYear(new Date().getFullYear() + 100)
export default {
	// 双向绑定
	// #ifndef VUE3
	value: {
		type: [String, Date, Number, Array]
	},
	// #endif
	// #ifdef VUE3
	modelValue: {
		type: [String, Date, Number, Array]
	},
	// #endif
	// y(year)年,2month年月,3date年月日,4hour年月日时,5minute年月日时分,6second年月日时分秒
	// 展示几列
	showColumns: {
		type: Number,
		default: 6
	},
	// 展示的列对应的文字,默认展示的年月日时分
	label: {
		type: [Array],
		default: () => {
			return ['年', '月', '日', '时', '分', '秒']
		}
	},
	minDate: {
		type: [String, Date, Number],
		default: () => {
			return minDate
		}
	},
	maxDate: {
		type: [String, Date, Number],
		default: () => {
			return maxDate
		}
	},
	// 返回值类型  timestamp string
	returnType: {
		type: String,
		default: 'string'
	},

	// 弹出方式 top顶部弹出,center居中弹出,bottom底部弹出,left左侧弹出,right右侧弹出
	type: {
		type: String,
		default: 'bottom'
	},
	//是否开启动画
	animation: {
		type: Boolean,
		default: true
	},
	//蒙版点击是否关闭弹窗
	isMaskClick: {
		type: Boolean,
		default: true
	},
	// 蒙版颜色
	maskBackgroundColor: {
		type: String,
		default: 'rgba(0,0,0,0.4)'
	},
	// 主窗口背景色 默认#ffffff
	backgroundColor: {
		type: String,
		default: '#ffffff'
	},
	// 设置圆角(左上、右上、右下和左下) 示例:"10px 10px 10px 10px"
	borderRadius: {
		type: String,
		default: '10px'
	},
	// 是否适配底部安全区
	safeArea: {
		type: Boolean,
		default: true
	},
	title: {
		type: String,
		default: '请选择'
	}
}
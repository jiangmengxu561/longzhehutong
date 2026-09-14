const beforeDate = new Date().setFullYear(new Date().getFullYear() - 20)
const afterDate = new Date().setFullYear(new Date().getFullYear() + 20)

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
	// 展示的列[true,true,true,true,true,false],默认展示的年月日时分
	label: {
		type: [Array],
		default: () => {
			return ['年', '月', '日', '时', '分', '秒']
		}
	},
	// 最小日期 默认1970年一月一日0点可自定义
	minDate: {
		type: [String, Date, Number],
		default: ()=>{
			console.log(beforeDate,'beforeDate=============')
			return beforeDate
		}
	},
	// 最大日期 默认2099年12月31日 23点59分59秒
	maxDate: {
		type: [String, Date, Number],
		default: afterDate
	},
}
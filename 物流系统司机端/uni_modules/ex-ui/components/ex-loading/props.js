export default {
	// 动画大小
	size: {
		type: String,
		default: '30px'
	},
	// 动画的背景颜色
	color: {
		type: String,
		default: 'rgb(25, 190, 107)'
	},
	// 动画的线条宽度
	borderWidth: {
		type: String,
		default: '5px'
	},
	// 动画时长 (毫秒)
	duration: {
		type: Number,
		default: 1000
	},
	// 圆点还是直线 line dot
	type:{
		type:String,
		default:'line'
	}
}
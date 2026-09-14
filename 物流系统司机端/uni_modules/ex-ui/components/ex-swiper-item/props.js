export default {
	// 偏移量 
	slideItemOffSet: {
		type: Number,
		default: 60
	},
	// 索引 必传
	index: {
		type: Number,
		required: true
	},
	// 是否显示倒影
	showBoxReflect: {
		type: Boolean,
		default: false
	},
	// 倒影
	boxReflect: {
		type: String,
		default: 'below 10px linear-gradient(rgba(255,255,255,0),rgba(255,255,255,0.5))'
	}
}
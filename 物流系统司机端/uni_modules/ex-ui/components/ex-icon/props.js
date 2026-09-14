export default {
	// 图标类名
	name: {
		type: String,
		default: ''
	},
	// 图标颜色，可接受主题色
	color: {
		type: String,
		default: '#606266'
	},
	// 字体大小，单位px
	size: {
		type: [String, Number],
		default: '16px'
	},
	// 是否显示粗体
	bold: {
		type: Boolean,
		default: false
	},
	// 触摸图标时的类名
	hoverClass: {
		type: String,
		default: ''
	},
	// 自定义扩展前缀，方便用户扩展自己的图标库
	customPrefix: {
		type: String,
		default: 'exicon'
	}
}
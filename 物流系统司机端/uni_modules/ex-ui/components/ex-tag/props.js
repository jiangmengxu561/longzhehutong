export default {
	// 标签文字
	text: {
		type: [String, Number],
		required: true
	},
	// 左侧图标
	leftImg: {
		type: String,
		default: ''
	},
	// 右侧图标
	rightImg: {
		type: String,
		default: ''
	},
	// 角标
	badgeImg: {
		type: String,
		default: ''
	},
	// 是否禁用 
	disabled: {
		type: Boolean,
		default: false
	},
	// 大小  default   large  mini
	size: {
		type: String,
		default: 'default'
	},

	// 标签形状 square circle
	shape:{
		type: String,
		default: 'square'
	},
	tagStyle:{
		type:Object,
		default:()=>{
			return {
				color:'#FFFFFF',
				borderColor:'#409eff',
				background:'#409eff'
			}
		}
	},
	// 角标横向方向相对容器的位置 left right
	horizontal:{
		type: String,
		default: 'right'
	},
	// 角标横向方向偏移量
	horizontalOffset:{
		type: String,
		default: '0px'
	},
	// 角标竖向方向相对容器的位置 top bottom
	vertical:{
		type: String,
		default: 'top'
	},
	// 角标竖向方向偏移量
	verticalOffset:{
		type: String,
		default: '0px'
	}
}
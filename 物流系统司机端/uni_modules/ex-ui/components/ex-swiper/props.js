export default {
	// 景深
	perspective: {
		type: String,
		default: '2700px'
	},
	// 子容器滑块的数量
	swiperItemNum: {
		type: Number,
		required: true
	},
	// swiper的宽高
	swiperStyle: {
		type: String,
		default: 'width:200px;height:300px'
	},
	// 当前激活的索引
	acIndex: {
		type: Number,
		required: 0
	},
	// 是否自动切换
	autoplay:{
		type: Boolean,
		default: true
	},
	// 自动切换的间隔时长
	interval:{
		type: Number,
		default: 2000
	},
	rotateX:{
		type: Number,
		default: -15
	}
}
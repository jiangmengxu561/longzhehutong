export default {
	// #ifndef VUE3
	value: {
		type: Array,
		default: () => {
			return []
		}
	},
	// #endif
	// #ifdef VUE3
	modelValue: {
		type: Array,
		default: () => {
			return []
		}
	},
	// #endif
	column: {
		type: Number,
		default: 4
	},
	// 容器的宽度(百分比或者px)
	width: {
		type: [String, Number],
		default: '100%',
	},
	// 容器的高度
	height: {
		type: String,
		default: 'auto'
	},
	// 选项的高度
	itemHeight: {
		type: Number,
		default: 100
	},
	// 每一个子项竖向间距
	heightGap: {
		type: Number,
		default: 20
	},
	// 每一个子项横向的间距
	widthGap: {
		type: Number,
		default: 10
	},
	itemKey: {
		type: String,
		required: true
	},
	// 拖动方向
	direction: {
		type: String,
		default: 'all',
		validator: value => {
			return ['all', 'vertical', 'horizontal', 'none'].includes(value);
		}
	},
	animation: {
		type: Boolean,
		default: true
	},
	damping: {
		type: Number,
		default: 20
	},
	longpress: {
		type: Boolean,
		default: false
	}
}
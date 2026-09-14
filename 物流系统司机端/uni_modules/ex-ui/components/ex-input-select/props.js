export default {
	list: {
		type: Array,
		default () {
			return []
		}
	},
	// 双向绑定
	// #ifndef VUE3
	value: {
		type: [String, Number, Array],
		default: ''
	},
	// #endif
	// #ifdef VUE3
	modelValue: {
		type: [String, Number, Array],
		default: ''
	},
	// #endif

	placeholder: {
		type: String,
		default: '请选择'
	},
	emptyTips: {
		type: String,
		default: '无选项'
	},
	clear: {
		type: Boolean,
		default: false
	},
	labelKey: {
		type: String,
		default: 'label'
	},
	valueKey: {
		type: String,
		default: 'value'
	},
	// placeholder样式
	placeholderStyle: {
		type: String,
		default: 'color:#B2B2B2;font-size:24rpx'
	},
	// 输入框文字对齐方式
	textAlign: {
		type: String,
		default: 'left'
	},
	// list选项相对输入框的对齐方式 默认左对齐 left || right
	listPostion: {
		type: String,
		default: 'left'
	},
	// list选项相对输入框的偏移量
	listOffset: {
		type: String,
		default: '0px'
	},
	// list的宽度,默认继承父元素的宽度100%
	listWidth: {
		type: String,
		default: '100%'
	},
	// 层级
	zIndex: {
		type: Number,
		default: 1000
	},
	// 是否多选
	multiple: {
		type: Boolean,
		default: false
	},
	// 最大选中数量, 仅多选有效
	maxNum: {
		type: Number,
		default: Infinity
	},
	// 超出最大数量的提示信息
	maxNumToast: {
		type: String,
		default: '超出最大选中数量'
	},
	// 能否取消选中选项（已经选中的选项再次点击的时候能否取消选中,默认true为可以取消）
	cancelSelectItem: {
		type: Boolean,
		default: true
	},
	// 禁用的数组
	disabledArr: {
		type: Array,
		default: () => {
			return []
		}
	},
	// 选中的文字样式
	selectedStyle: {
		type: Object,
		default: () => {
			return {
				color: '#409eff'
			}
		}
	},
	// 未选中样式
	unSelectedStyle: {
		type: Object,
		default: () => {
			return {
				color: '#4D4D4D'
			}
		}
	},
	// 禁用样式
	disabledStyle: {
		type: Object,
		default: () => {
			return {
				color: '#80808050'
			}
		}
	},

	// 下拉选项选中时图标样式
	selectedIconColor: {
		type: String,
		default: '#409eff'
	},

	// 下拉滚动区域高度
	scrollHeight: {
		type: String,
		default: '200px'
	}
}
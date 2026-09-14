export default {
	// #ifndef VUE3
	value: {
		type: [String, Array, Number]
	},
	// #endif
	// #ifdef VUE3
	modelValue: {
		type: [String, Array, Number]
	},
	// #endif
	// 数组选项
	list: {
		type: Array,
		default: () => {
			return []
		}
	},
	// 字段key映射配置项
	props: {
		type: Object,
		default: () => {
			return {
				label: 'label',
				value: 'value'
			}
		}
	},
	// 是否多选
	multiple: {
		type: Boolean,
		default: false
	},
	// 最大选中数量, 仅多选有效
	maxNum: {
		type: Number,
		default: 999999
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
	disabledArr: {
		type: Array,
		default: () => {
			return []
		}
	},
	// 每行按钮个数
	lineNumber: {
		type: Number,
		default: 3
	},
	// 按钮间距
	gap: {
		type: String,
		default: '12px'
	},
	// 选中样式
	selectedStyle: {
		type: Object,
		default: () => {
			return {
				background: '#409eff',
				color: '#FFFFFF',
				borderColor: '#409eff'
			}
		}
	},
	// 未选中样式
	unSelectedStyle: {
		type: Object,
		default: () => {
			return {
				background: '#F5F5F5',
				color: '#4D4D4D',
				borderColor: '#F5F5F5'
			}
		}
	},
	// 禁用样式
	disabledStyle: {
		type: Object,
		default: () => {
			return {
				background: '#e9e9eb',
				color: '#bcbec2',
				borderColor: '#e9e9eb'
			}
		}
	},
	// 显示选中角标
	showBadge: {
		type: Boolean,
		default: false
	},
	// 选中角标位置 top bottom
	badgePosition: {
		type: String,
		default: 'top'
	},
	// 角标的大小
	badgeSize: {
		type: String,
		default: '16px'
	},
	// 角标默认颜色
	badgeColor: {
		type: String,
		default: '#cdcdcd'
	},
	// 角标选中颜色
	badgeSelectedColor: {
		type: String,
		default: '#409eff'
	},
	// 角标禁用颜色
	badgeDisabledColor: {
		type: String,
		default: '#bcbec2'
	}
}
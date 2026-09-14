export default {
	// 双向绑定
	// #ifndef VUE3
	value: {
	// #endif
	// #ifdef VUE3
	modelValue: {
	// #endif
		type: [String, Number, Array],
		default: ''
	},
	// 弹窗标题
	title: {
		type: String,
		default: '请选择'
	},
	// 是否显示弹窗标题
	isShowTitle: {
		type: Boolean,
		default: true
	},
	// 复选框颜色
	iconColor: {
		type: String,
		default: '#409eff'
	},
	confirmTextStyle: {
		type: String,
		default: 'color:#409eff'
	},
	// 是否能过滤 (列表上面的输入框是否显示)
	showFilter: {
		type: Boolean,
		default: true
	},
	// 过滤的关键字,默认是label 可传多个字段
	filterKeys: {
		type: Array,
		default: () => {
			return ['label']
		}
	},
	props: {
		type: Object,
		default: {
			label: 'label',
			value: 'value'
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
		default: Infinity
	},
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
	// 数组选项
	list: {
		type: Array,
		default: () => {
			return []
		}
	},
	// 弹窗内容区宽度
	width: {
		type: String,
		default: '750rpx'
	},
	// 弹窗内容区高度
	height: {
		type: String,
		default: '80vh'
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
	// 是否能添加选项(暂未生效)
	canAddItem: {
		type: Boolean,
		default: false
	},
	// 搜索时不区分大小写
	searchDistinguishCapital: {
		type: Boolean,
		default: true
	}
}
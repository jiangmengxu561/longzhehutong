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
	nodeData: {
		type: Array,
		default: () => {
			return []
		}
	},
	props: {
		type: Object,
		default: () => {
			return {
				label: 'label',
				value: 'id',
				children: 'children'
			}
		}
	},
	// 每一列的样式
	columnStyle: {
		type: String,
		default: 'height: 90vh;flex: 1'
	},
	// 是否点击节点时选中节点
	checkOnClickNode: {
		type: Boolean,
		default: false
	},
	// 点击复选框时当前节点是否选中
	clickCheckPithNode: {
		type: Boolean,
		default: false
	},
	// 最多展示列5 暂未用到
	maxColumnNum: {
		type: Number,
		default: 5
	},
	// 选中时候复选框样式
	selectIconColor: {
		type: String,
		default: '#409eff'
	},
	// 选中选项的文字样式
	selectTextStyle: {
		type: Object,
		default: () => {
			return {
				'color': '#409eff',
				'font-weight': 'bold'
			}
		}
	}
}
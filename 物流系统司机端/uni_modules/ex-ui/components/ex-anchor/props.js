export default {
	// 传递过来的数据
	list: {
		type: Array,
		default: () => {
			return []
		}
	},
	// 区域高度
	scrollViewHeight: {
		type: String,
		default: '80vh'
	},
	// 是否出现滚动条
	showScrollbar: {
		type: Boolean,
		default: true
	},
	// 是否显示索引项
	showIndex:{
		type: Boolean,
		default: true
	},
	// 右侧索引栏相对定位位置
	indexStyle: {
		type: Object,
		default: () => {
			return {
				top: '60px',
				right: '12px'
			}
		}
	},
	// 索引项选中样式
	indexItemActiveStyle: {
		type: Object,
		default: () => {
			return {
				'background-color': '#409eff',
				'color':'#fff',
				'border-radius':'100px'
			}	
		}
	},
	// 索引项未选中样式
	indexItemUnActiveStyle: {
		type: Object,
		default: () => {
			return {
			}
		}
	},
	// 配置项
	props:{
		type: Object,
		default:{
			indexKey:'label',  // 索引项对应的key
			anchorKey:'$uniqueKey' // 锚点key 锚点对应的id(数字字母格式，需要以字母开头)
		}
	}
}
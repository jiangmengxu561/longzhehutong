export default {
	// 索引项选中样式
	indexItemActiveStyle: {
		type: Object,
		default: () => {
			return {
				'color': '#409eff',
			}
		}
	},
	// 配置项
	props: {
		type: Object,
		default: () => {
			return {
				label: 'label', // 分组的标签key
				indexKey: 'label', // 右侧索引项对应的key
				anchorKey: '$uniqueKey', // 锚点key 锚点对应的id(数字字母格式，需要以字母开头)
				subListKey: 'list', // 子列表对应的key
				childProps: {
					label: 'label',
					value: 'value'
				}
			}
		}
	}
}
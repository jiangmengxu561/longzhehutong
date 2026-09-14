export default {
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
	
	// 配置项
	props:{
		type:Object,
		default:()=>{
			return {
				label:'label',
				subListKey:'list',
				childProps:{
					label:'label',
					value:'value'
				}
			}
		}
	}
}
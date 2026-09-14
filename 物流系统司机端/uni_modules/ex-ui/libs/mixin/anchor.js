export default {
	emits: ['anchorChange'],
	data() {
		return {
			anchorVal: '', // 绑定的锚点
			arr: [], // 数组值
			activeKey: ''
		}
	},
	computed: {
		indexKey() {
			return this?.props?.indexKey ?? 'indexKey'
		},
		anchorKey() {
			return this?.props?.anchorKey ?? '$uniqueKey'
		}
	},
	methods: {
		//滚动事件
		scroll({
			detail
		}) {
			// 滚动条距离顶部的距离
			let scrollTop = detail.scrollTop
			let index = this.arr.findIndex(o => {
				return scrollTop + 20 < o.top
			})
			// index 为0则赋值为1
			index = index || 1
			// index为-1则赋值最后一项
			if (index === -1) {
				index = this.arr.length
			}
			let tempObj = this.arr[index - 1]
			this.activeKey = tempObj.$uniqueKey
			this.$emit('anchorChange', tempObj)
		},

		// 滚动到底部 锚点为最后一项
		scrolltolower(a, b) {
			let tempObj = this.arr[this.arr.length - 1]
			this.$emit('anchorChange', tempObj)
		},

		// 设置数组, 异步数据的情况下通过调用此函数设置数据
		setArr() {
			const query = uni.createSelectorQuery().in(this);
			// 父元素距离顶部的距离
			let parentTop = 0
			this.arr = this.arr.map((o, i) => {
				query.select("#" + o.$uniqueKey).boundingClientRect((data) => {
					if (i === 0) {
						parentTop = data?.top
					}
					o.top = data?.top - parentTop
				}).exec();
				return o
			})
			// 设置默认锚点为第一项
			let firstKey = this.arr?.[0]?.$uniqueKey
			if (firstKey) {
				this.setAnchor(firstKey)
			}
		},

		// 滚动到指定锚点
		setAnchor(val) {
			this.anchorVal = val
			this.activeKey = val
		}
	}
}
<template>
	<view class="page">
		<view class="cascader-select">
			<view class="cascader-select-item" :style="columnStyle" v-for="(item,index) in columnIndex" :key="index">
				<view class="item" v-for="(o,i) in getColumn(index)" :key="i">
					<!-- 				<ex-icon :name="getIcon(o,i)" :color="selectIconColor"
						@click.native="iconClick(o,i,index)"></ex-icon> -->
					<image class="image-ico" :src="getIcon(o,i)" @click="iconClick(o,i,index)">
					</image>
					<view class="item-node" @click="nodeClick(o,i,index)">
						<text :class="item===i?'active':''" :style="[isActiveItem(item===i)]">{{o[props.label]}}</text>
					</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	import props from './props.js'
	import {
		deepClone
	} from './utils.js'
	export default {
		emits: ['update:modelValue', 'change'],
		props,
		watch: {
			nodeData: {
				handler(val = []) {
					if (val.length > 0) {
						this.column = deepClone(val)
						this.columnIndex = this.initColumnIndex(val[0], [0])
						this.initSelectVal()
					}
				},
				deep: true,
				immediate: true
			},
			// #ifndef VUE3
			value: {
				handler(val = []) {
					this.selectIds = val
					if (val.length > 0 && !this.isDone) {
						this.initSelectVal()
						this.isDone = true
					}
				},
				deep: true,
				immediate: true
			},
			// #endif
			// #ifdef VUE3
			modelValue: {
				handler(val = []) {
					this.selectIds = val
					if (val.length > 0 && !this.isDone) {
						this.initSelectVal()
						this.isDone = true
					}
				},
				deep: true,
				immediate: true
			},
			// #endif
		},
		data() {
			return {
				columnIndex: [],
				column: [],
				selectIds: [],
				isDone: false,
			}
		},

		methods: {
			isActiveItem(bol) {
				if (bol) {
					return this.selectTextStyle
				}
			},

			// 初始化选中的数据
			initSelectVal() {
				let val = this.selectIds
				let startTime = new Date().valueOf()
				let arr = deepClone(this.column)
				let valueKey = this.props.value
				let childKey = this.props.children
				let parentIndexArr = []
				const reflectDownData = (tempArr = [], status = 0, pathIndexs = []) => {
					tempArr.forEach((o, i) => {
						let index = [...pathIndexs, i]
						o.pathIndexs = index
						// 存在子元素递归处理
						if (o?.[childKey]?.length > 0) {
							let tempStatus = 0
							if (status === 2 || val.includes(o[valueKey])) {
								o.$checkStatus = 2
								tempStatus = 2
							}
							o[childKey] = reflectDownData(o[childKey], tempStatus, o.pathIndexs)
							// 记录需要触发往上的pathIndexs
							if (val.includes(o[valueKey])) {
								parentIndexArr.push([...pathIndexs])
							}
						} else {
							if (status === 2 || val.includes(o[valueKey])) {
								o.$checkStatus = 2
							}
							// 记录需要触发往上的pathIndexs
							if (val.includes(o[valueKey])) {
								parentIndexArr.push([...pathIndexs])
							}
						}
					})
					return tempArr
				}
				let newArr = reflectDownData(arr, 0)
				const reflectUpData = (tempIs, status) => {
					let index = tempIs.length
					let childKey = this.props.children
					// 非半选的情况获取所有子节点的状态(当前节点为半选的情况所有父节点必然为半选,)
					if (status !== 1) {
						let childs = []
						if (index === 1) {
							childs = newArr[tempIs[0]][childKey]
						}
						if (index === 2) {
							childs = newArr[tempIs[0]][childKey][tempIs[1]][childKey]
						}
						if (index === 3) {
							childs = newArr[tempIs[0]][childKey][tempIs[1]][childKey][tempIs[2]][childKey]
						}
						if (index === 4) {
							childs = newArr[tempIs[0]][childKey][tempIs[1]][childKey][tempIs[2]][childKey][tempIs[3]][
								childKey
							]
						}
						status = this.getStatusByChilds(childs)
					}

					if (index === 1) {
						newArr[tempIs[0]]['$checkStatus'] = status
					}
					if (index === 2) {
						newArr[tempIs[0]][childKey][tempIs[1]]['$checkStatus'] = status
					}
					if (index === 3) {
						newArr[tempIs[0]][childKey][tempIs[1]][childKey][tempIs[2]]['$checkStatus'] = status
					}
					if (index === 4) {
						newArr[tempIs[0]][childKey][tempIs[1]][childKey][tempIs[2]][childKey][tempIs[3]][
							'$checkStatus'
						] = status
					}
					if (index > 1) {
						let newIs = tempIs.slice(0, index - 1)
						reflectUpData(newIs, status)
					}
				}
				for (let i = 0; i < parentIndexArr.length; i++) {
					reflectUpData(parentIndexArr[i])
				}
				let endTime = new Date().valueOf()
				console.warn(`数据初始化耗时${endTime - startTime}ms`)
				this.column = deepClone(newArr)
			},

			// 获取每一列数组
			getColumn(index) {
				let arr = deepClone(this.column)
				let i = 0
				let childKey = this.props.children
				while (i < index) {
					let nodeIndex = this.columnIndex[i]
					arr = arr[nodeIndex][childKey]
					i++
				}
				return arr
			},

			getIcon(item) {
				let map = {
					0: 'none-selected', // 全部未选
					1: 'half-selected', // 部分选
					2: 'all-selected' // 全选
				}
				let checkStatus = item.$checkStatus ?? 0
				return `/uni_modules/hy-address-multiple/static/address/${map[checkStatus]}.png`
			},

			// 更新节点以及子节点(写法待优化,~~嘿嘿嘿~~)
			updateNodeAndChildren(index, i, data) {
				let tempIs = this.columnIndex
				let childKey = this.props.children
				// 第一列
				if (index === 0) {
					this.$set(this.column, i, data)
					return
				}
				// 第二列
				if (index === 1) {
					this.$set(this.column[tempIs[0]][childKey], i, data)
					return
				}
				// 第三列
				if (index === 2) {
					this.$set(this.column[tempIs[0]][childKey][tempIs[1]][childKey], i, data)
					return
				}
				// 第四列
				if (index === 3) {
					this.$set(this.column[tempIs[0]][childKey][tempIs[1]][childKey][tempIs[2]][childKey], i, data)
					return
				}
				// 第五列
				if (index === 4) {
					this.$set(this.column[tempIs[0]][childKey][tempIs[1]][childKey][tempIs[2]][childKey][tempIs[3]][
						childKey
					], i, data)
					return
				}
			},

			// 更新节点状态(写法待优化,~~嘿嘿嘿~~)
			updateNodeAndParentStatus(index, status) {
				let tempIs = this.columnIndex
				let childKey = this.props.children
				// 非半选的情况获取所有子节点的状态(当前节点为半选的情况所有父节点必然为半选,)
				if (status !== 1) {
					let childs = []
					if (index === 1) {
						childs = this.column[tempIs[0]][childKey]
					}
					if (index === 2) {
						childs = this.column[tempIs[0]][childKey][tempIs[1]][childKey]
					}
					if (index === 3) {
						childs = this.column[tempIs[0]][childKey][tempIs[1]][childKey][tempIs[2]][childKey]
					}
					if (index === 4) {
						childs = this.column[tempIs[0]][childKey][tempIs[1]][childKey][tempIs[2]][childKey][tempIs[3]][
							childKey
						]
					}
					status = this.getStatusByChilds(childs)
				}

				if (index === 1) {
					this.$set(this.column[tempIs[0]], '$checkStatus', status)
				}
				if (index === 2) {
					this.$set(this.column[tempIs[0]][childKey][tempIs[1]], '$checkStatus', status)
				}
				if (index === 3) {
					this.$set(this.column[tempIs[0]][childKey][tempIs[1]][childKey][tempIs[2]], '$checkStatus', status)
				}
				if (index === 4) {
					this.$set(this.column[tempIs[0]][childKey][tempIs[1]][childKey][tempIs[2]][childKey][tempIs[3]],
						'$checkStatus', status)
				}
				if (index > 1) {
					this.updateNodeAndParentStatus(--index, status)
				}
			},

			// 根据子节点获取当前节点选中状态
			getStatusByChilds(childs) {
				// 子元素都选中,则当前状态选中
				let AllCheckFlag = childs.every(o => {
					return o.$checkStatus === 2
				})
				if (AllCheckFlag) {
					return 2
				}
				// 都为未选 则当前状态未选
				let noCheckFlag = childs.every(o => {
					return o.$checkStatus === 0 || o.$checkStatus === undefined
				})
				if (noCheckFlag) {
					return 0
				}
				return 1
			},

			// 设置所有子元素的status
			recursionChildrenStatus(arr = [], status) {
				let childKey = this.props.children
				arr.forEach(o => {
					o.$checkStatus = status
					if (o?.[childKey]?.length > 0) {
						this.recursionChildrenStatus(o[childKey], status)
					}
				})
				return arr
			},

			// 复选框点击事件
			iconClick(o, i, index) {
				let startTime = new Date().valueOf()
				let status = o.$checkStatus
				this.clickDownEvent(o, i, index)
				// 非第一列需要往上处理数据
				if (index !== 0) {
					this.updateNodeAndParentStatus(index, status)
				}
				// 复选框点击之后默认当前列选中
				if (this.clickCheckPithNode) {
					this.updateColumnIndex(o, i, index)
				}
				this.getData()
				let endTime = new Date().valueOf()
				console.warn(`勾选耗时${endTime - startTime}ms`)
			},

			// icon点击往下事件
			clickDownEvent(o, i, index) {
				// 全选
				let bol = o.$checkStatus === 2
				let val = bol ? 0 : 2
				let tempData = deepClone(o)
				tempData.$checkStatus = val
				let childKey = this.props.children
				if (tempData?.[childKey]?.length > 0) {
					tempData[childKey] = this.recursionChildrenStatus(tempData[childKey], val)
				}
				this.updateNodeAndChildren(index, i, tempData)
			},

			// 文字节点点击事件
			nodeClick(o, i, index) {
				if (this.checkOnClickNode) {
					this.iconClick(o, i, index)
				}
				this.updateColumnIndex(o, i, index)
			},

			// 初始化索引(有几列)
			initColumnIndex(arr, indexs = []) {
				let childKey = this.props.children
				if (arr?.[childKey]?.length > 0) {
					indexs.push(0)
					this.initColumnIndex(arr[childKey][0], indexs)
				}
				return indexs
			},

			// 更新columnIndex
			updateColumnIndex(o, i, index) {
				let indexs = this.initColumnIndex(o, [i])
				// 级联层级最多10级
				this.columnIndex.splice(index, 10, ...indexs)
			},

			// 获取数据
			getData() {
				let halfCheckIds = [] // 记录所有半选id
				let halfCheckLabels = [] // 记录所有半选label
				let halfChecks = [] // 记录所有半选的item
				let checkIds = [] // 记录选中的id
				let checkLabels = [] // 记录选中的label
				let checks = [] // 所有选中的项
				let lastChildIds = [] // 最后一级选中的id
				let lastChildLabels = [] // 最后一级选中的label
				let lastChilds = [] // 最后一级选中的项
				let childKey = this.props.children
				let idKey = this.props.value
				let labelKey = this.props.label
				const getRecursionData = (arr) => {
					arr.map(o => {
						if (o.$checkStatus === 2) {
							checkIds.push(o[idKey])
							checkLabels.push(o[labelKey])
							checks.push(o)
							if (!o[childKey]?.length > 0) {
								lastChildIds.push(o[idKey])
								lastChildLabels.push(o[labelKey])
								lastChilds.push(o)
							}
						}
						if (o.$checkStatus === 1) {
							halfCheckIds.push(o[idKey])
							halfCheckLabels.push(o[labelKey])
							halfChecks.push(o)
						}
						if (o[childKey]?.length > 0) {
							getRecursionData(o[childKey])
						}
					})
				}
				getRecursionData(this.column)
				let returnObj = {
					halfCheckIds,
					halfCheckLabels,
					halfChecks,
					checkIds,
					checkLabels,
					checks,
					lastChildIds,
					lastChildLabels,
					lastChilds
				}
				// #ifndef VUE3
				this.$emit('input', checkIds)
				// #endif
				// #ifdef VUE3
				this.$emit('update:modelValue', checkIds)
				// #endif
				this.$emit('change', returnObj)
				return returnObj
			}
		},
	}
</script>

<style lang="scss" scoped>
	.cascader-select {
		display: flex;

		&-item {
			overflow-y: auto;
			border: solid 1rpx #f0f0f0;
			padding: 2px 6px;
			border-radius: 2px;
			border-right: none;
		}

		&-item:last-child {
			border-right: solid 1rpx #f0f0f0;
		}
	}

	.image-ico {
		width: 18px;
		height: 18px;
		flex-shrink: 0;
	}

	.item {
		padding: 8px 0;
		flex: 1;
		padding-right: 6px;
		display: flex;
		align-items: center;

		.item-node {
			display: flex;
			align-items: center;

			text {
				font-size: 12px;
				margin-left: 5px;
				line-height: 12px;
			}
		}
	}
</style>
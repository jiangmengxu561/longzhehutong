<template>
	<view>
		<view class="input-select" :style="compStyle">
			<view class="input-select__selected">
				<view class="left-box" @click="toggleSelector" :style="[{'z-index':zIndex-1}]">
					<view class="input-select__selected-tag-item" v-for="(item,index) in selectLabel" :key="index">
						<view class="tag-item-text"> {{item}}</view>
						<view class="tag-item-close" @click.stop="delItem(index)">
							<ex-icon name="circle-close" color="#c0c4cc" size="14px" />
						</view>
					</view>
					<view class="input-select__selected-input-box">
						<input class="input-class" type="text" :placeholder="placeholder" v-model="inputVal"
							@click.stop="inputClick" @input="input" @blur="blur" :placeholder-style="placeholderStyle"
							:style="{'textAlign':textAlign }" />
						<view class="clear-icon" v-if="clear && inputVal" @click.stop="clearVal">
							<ex-icon name="circle-close" color="#c0c4cc" size="14px" />
						</view>
					</view>
				</view>
				<view class="right-icon" @click="toggleSelector">
					<ex-icon :name="showSelector? 'arrow-right' : 'arrow-bottom'" size="14px" color="#999" />
				</view>
			</view>
			<view class="input-select__droplist" v-if="showSelector" :style="[listStyle]">
				<!-- <view class="input-select-popper__arrow"></view> -->
				<scroll-view scroll-y="true" class="input-select__scroll" :style="{height:scrollHeight} ">
					<view class="input-select__item no-data" v-if="showList.length === 0 && !showInputVal">
						<text>{{emptyTips}}</text>
					</view>
					<view class="input-select__item" v-if="showInputVal" @click="comfirmVal">
						<text :style="[selectedStyle]">{{inputVal}}</text>
					</view>
					<view class="input-select__item" v-for="(item,index) in showList" :key="index"
						@click="selectItem(item)" :style="[getSelectItemStyle(item)]">
						<text class="input-select__item-text">{{item[labelKey]}}</text>
						<ex-icon v-if="showDropListIcon(item)" name="correct" size="14px" class="correct-icon"
							:color="selectedIconColor" />
					</view>
				</scroll-view>
			</view>
			<view class="input-select__mask" v-if="showSelector" @click="showSelector = false" />
		</view>
	</view>
</template>

<script>
	import props from './props.js'
	export default {
		props,
		name: "ex-input-select",
		data() {
			return {
				allList: [], // 全部数据列表
				showList: [], // 显示的数据列表
				inputVal: '', // 输入框的值
				showSelector: false, // 显示下拉列表
				selectLabel: [], // 多选选中的
				disableds: []
			};
		},
		emits: ['update:modelValue', 'change'],
		computed: {
			showInputVal() {
				if (this.inputVal === '' || this.inputVal === null || this.inputVal === undefined) {
					return false
				}
				let index = this.showList.findIndex(o => {
					return o[this.labelKey] == this.inputVal
				})
				return index === -1
			},
			listStyle() {
				let height = this.showList.length * 50
				if (height > 300) {
					height = 300
				}
				return {
					[this.listPostion]: this.listOffset,
					width: this.listWidth,
				}
			},
			compStyle() {
				return `--z-index:${this.zIndex};`
			}
		},
		watch: {
			list: {
				immediate: true,
				deep: true,
				handler(val, old) {
					if (Array.isArray(val) && old !== val) {
						let list = val.map(o => {
							if (typeof o === "string" || typeof o === "number") {
								return {
									[this.labelKey]: o,
									[this.valueKey]: o
								};
							} else {
								return o
							}
						})
						this.allList = uni.$ex.utils.deepClone(list)
						this.showList = uni.$ex.utils.deepClone(list)
					}
				}
			},
			// 双向绑定
			// #ifndef VUE3
			value: {
				immediate: true,
				deep: true,
				handler(newval, oldval) {
					if (this.multiple) {
						let labels = []
						newval?.map(o => {
							let item = this.list.find(m => {
								return o === m[this.valueKey]
							})
							item ? labels.push(item[this.labelKey]) : labels.push(o)
						})
						this.selectLabel = labels
					} else {
						let item = this.list.find(o => {
							return newval === o[this.valueKey]
						})
						this.inputVal = item?.[this.labelKey] ?? newval
					}
				}
			},
			// #endif
			// #ifdef VUE3
			modelValue: {
				immediate: true,
				deep: true,
				handler(newval, oldval) {
					if (this.multiple) {
						let labels = []
						newval?.map(o => {
							let item = this.list.find(m => {
								return o === m[this.valueKey]
							})
							item ? labels.push(item[this.labelKey]) : labels.push(o)
						})
						this.selectLabel = labels
					} else {
						let item = this.list.find(o => {
							return newval === o[this.valueKey]
						})
						this.inputVal = item?.[this.labelKey] ?? newval
					}
				}
			},
			// #endif
			disabledArr: {
				handler(val = []) {
					this.disableds = val
				},
				deep: true,
				immediate: true
			}
		},
		created() {
			 this.input = uni.$ex.utils.throttle(this.input.bind(this));
		},
		methods: {
			showDropListIcon(item) {
				if (this.multiple) {
					if (this.selectLabel.includes(item[this.labelKey])) {
						return true
					}
				} else {
					if (this.inputVal == item[this.labelKey]) {
						return true
					}
				}
				return false
			},
			getSelectItemStyle(item) {
				if (item.disabled || this.disableds.includes(item[this.valueKey])) {
					return this.disabledStyle
				}
				if (this.multiple) {
					if (this.selectLabel.includes(item[this.labelKey])) {
						return this.selectedStyle
					}
				} else {
					if (this.inputVal == item[this.labelKey]) {
						return this.selectedStyle
					}
				}
				return this.unSelectedStyle
			},
			// 移除选项
			delItem(index) {
				this.selectLabel.splice(index, 1)
				this.emitData()
			},
			// 是否为空
			isEmpty(val) {
				return val === null || val === undefined || val === ''
			},
			blur() {
				// 失去焦点应关闭列表, 多选的情况点击下拉列表选项时也会失去焦点,此时不应该关闭下拉列表, 设置标志位closeFlag
				this.closeFlag = false
				setTimeout(() => {
					if (this.multiple && !this.isEmpty(this.inputVal)) {
						this.clearVal()
					}
					if (!this.closeFlag) {
						this.showSelector = false
					}
				}, 50)
			},
			comfirmVal() {
				// 多选
				if (this.multiple) {
					this.closeFlag = true
					if (this.isEmpty(this.inputVal)) {
						return
					}
					// 已经选中的则取消
					if (this.selectLabel.includes(this.inputVal)) {
						this.selectLabel = this.selectLabel.filter(o => {
							return o !== this.inputVal
						})
					} else {
						this.selectLabel.push(this.inputVal)
					}
					this.clearVal()
				} else {
					this.showSelector = false
				}
				this.emitData()
			},

			inputClick() {
				if (!this.multiple || !this.showSelector) {
					this.toggleSelector()
				}
			},
			
			input(){
				this.filterList()
			},

			filterList() {
				let list = this.allList.filter(o => {
					return o[this.labelKey].includes(this.inputVal)
				}) || []
				this.showList = list
			},
			clearVal() {
				this.inputVal = ''
				this.showList = uni.$ex.utils.deepClone(this.allList)
			},
			toggleSelector() {
				this.showSelector = !this.showSelector
			},

			selectItem(item) {
				// 禁用的项 (如果配置了disabledArr属性,同时list属性传递的数组有选项属性为disabled则都会禁用)
				if (item.disabled || this.disableds.includes(item[this.valueKey])) {
					return
				}
				// 多选
				if (this.multiple) {
					this.closeFlag = true
					// 已经选中的则取消选中
					if (this.selectLabel.includes(item[this.labelKey])) {
						this.selectLabel = this.selectLabel.filter(o => {
							return o !== item[this.labelKey]
						})
					} else {
						// 最大数量校验
						if ((this.selectLabel.length + 1) > this.maxNum) {
							uni.showToast({
								icon: 'none',
								title: this.maxNumToast
							})
							return
						}
						this.selectLabel.push(item[this.labelKey])
					}
				} else {
					this.inputVal = item[this.labelKey]
					this.showSelector = false
				}
				this.emitData()
			},
			emitData() {
				this.multiple ? this.multipleEmit() : this.singleEmit()
			},
			// 多选
			multipleEmit() {
				let items = []
				let labels = []
				let values = []
				this.selectLabel.map(o => {
					let item = this.allList.find(m => {
						return m[this.labelKey] === o
					})
					if (item) {
						values.push(item[this.valueKey])
						labels.push(item[this.labelKey])
						items.push(item)
					} else {
						values.push(o)
						labels.push(o)
						items.push({
							[this.labelKey]: o,
							[this.valueKey]: o
						})
					}
				})
				// #ifndef VUE3
				this.$emit('input', values)
				// #endif
				// #ifdef VUE3
				this.$emit('update:modelValue', values)
				// #endif
				this.$emit('change', {
					label: labels,
					value: labels,
					item: items
				})


			},

			// 单选
			singleEmit() {
				let item = this.allList.find(o => {
					return o[this.labelKey] === this.inputVal
				})
				if (item) {
					// this.$emit('input', item[this.valueKey])
					this.$emit("update:modelValue", item[this.valueKey]);
					this.$emit('change', {
						label: item[this.labelKey],
						value: item[this.valueKey],
						item
					})
				} else {
					// this.$emit('input', this.inputVal)
					this.$emit("update:modelValue", this.inputVal);
					this.$emit('change', {
						label: this.inputVal,
						value: this.inputVal,
						item: {
							[this.labelKey]: this.inputVal,
							[this.valueKey]: this.inputVal
						}
					})
				}
			}
		}
	}
</script>

<style lang="scss">
	.input-select {
		width: 100%;
		height: 100%;
		position: relative;

		&__selected {
			display: flex;
			align-items: center;
			border: solid 1px #f0f0f0;
			padding: 0 6px;

			.left-box {
				flex: 1;
				width: calc(100% - 28px);
				position: relative;
				display: flex;
				flex-wrap: wrap;
			}

			.right-icon {
				width: 28px;
				flex-shrink: 0;
				align-items: center;
			}

			&-tag-item {
				max-width: calc(100% - 28px);
				display: flex;
				align-items: center;
				padding: 4px 4px 4px 8px;
				margin: 8px 8px 0px 0px;
				background-color: #f4f4f5;
				border: solid 1px #e9e9eb;
				border-radius: 4px;
				color: #909399;

				.tag-item-text {
					white-space: nowrap;
					overflow: hidden;
					text-overflow: ellipsis;
					flex: 1;
				}

				.tag-item-close {
					width: 28px;
					flex-shrink: 0;
				}
			}

			&-input-box {
				width: 100%;
				height: 100%;
				font-size: 14px;
				box-sizing: border-box;
				border-radius: 4px;
				display: flex;
				align-items: center;
				user-select: none;
				height: 36px;
				margin-left: 6px;

				.input-class {
					margin-right: auto;
					flex: 1;
					height: calc(100% + 6px);
				}
			}
		}

	}


	/* picker 弹出层通用的指示小三角 */
	.input-select-popper__arrow,
	.input-select-popper__arrow::after {
		position: absolute;
		display: block;
		width: 0;
		height: 0;
		border-color: transparent;
		border-style: solid;
		border-width: 6px;
	}

	.input-select-popper__arrow {
		filter: drop-shadow(0 2px 12px rgba(0, 0, 0, 0.03));
		top: -6px;
		left: 10%;
		margin-right: 3px;
		border-top-width: 0;
		border-bottom-color: #EBEEF5;
	}

	.input-select-popper__arrow::after {
		content: " ";
		top: 1px;
		margin-left: -6px;
		border-top-width: 0;
		border-bottom-color: #fff;
	}

	.input-select__mask {
		position: fixed;
		top: 0;
		bottom: 0;
		right: 0;
		left: 0;
		z-index: var(--z-index)-2;
	}

	.input-select__droplist {
		box-sizing: border-box;
		position: absolute;
		top: calc(100% + 8px);
		background-color: #FFFFFF;
		// border: 1px solid #EBEEF5;
		border-radius: 6px;
		box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.1);
		z-index: var(--z-index);
		padding: 8px 0;
		transition: height 200ms ease;
	}

	.input-select__scroll {
		max-height: 300px;
		box-sizing: border-box;
	}

	.input-select__item {
		display: flex;
		align-items: center;
		cursor: pointer;
		font-size: 14px;
		padding: 10px 0;
		margin: 0 16px;
		border-bottom: solid 1px #f5f5f5;

		.input-select__item-text {
			white-space: normal;
			word-break: break-word;
		}

		.correct-icon {
			width: 28px;
			flex-shrink: 0;
			margin-left: auto;
		}

		&:last-child {
			border: none;
		}
	}

	.clear-icon {
		width: 30px;
		height: 100%;
		display: flex;
		align-items: center;
		justify-content: center;
	}

	.no-data {
		text-align: center;
		opacity: 0.6;
		cursor: none;
	}
</style>
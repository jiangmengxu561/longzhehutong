<template>
	<view>
		<ex-popup ref="popup" :type="type" :animation="animation" :is-mask-click="isMaskClick"
			:background-color="backgroundColor" :mask-background-color="maskBackgroundColor"
			:borderRadius="borderRadius" :safe-area="safeArea">
			<view class="date-picker">
				<view class="date-picker__header">
					<view class="title">{{title}}</view>
					<view class="btn">
						<text @click="close" style="margin-left: 30rpx;">取消</text>
						<text class="done" @click="confirm" style="margin-right: 30rpx;">确定</text>
					</view>
				</view>
			</view>
			<picker-view :indicator-style="indicatorStyle" :value="innerIndex" class="picker-view" @change="bindChange">
				<picker-view-column v-for="(item,index) in columns" :key="index">
					<view style="color: black;" class="item" v-for="(o,i) in item.column" :key="i">
						{{getShowVal(index,o) }}{{label[index]}}
					</view>
				</picker-view-column>
			</picker-view>
		</ex-popup>
	</view>
</template>
<script>
	import props from './props'
	import dayjs from 'dayjs'
	const typeArr = ['year', 'month', 'date', 'hour', 'minute', 'second']
	export default {
		name: 'ex-date-picker',
		emits: ['update:modelValue', 'close', 'confirm'],
		props,
		data() {
			return {
				show: false,
				indicatorStyle: 'height: 50px;',
				innerIndex: [], // 内部索引
				columns: [], //内部数组
				innerValue: null, // 内部绑定的值
				tempInitDate: null,
				tempDate: null,
			}
		},
		computed: {
			// 如果以下这些变量发生了变化，意味着需要重新初始化各列的值
			propsChange() {
				return [this.showColumns, this.maxDate, this.minDate, this.value]
			}
		},

		watch: {
			propsChange() {
				this.updateColumnValue()
			},
			// 双向绑定
			// #ifndef VUE3
			value: {
				handler(val) {
					let date = val || Number(new Date())
					this.innerValue = date
					this.tempInitDate = date
				},
				deep: true,
				immediate: true
			}
			// #endif
			// #ifdef VUE3
			modelValue: {
				handler(val) {
					let date = val || Number(new Date())
					this.innerValue = date
					this.tempInitDate = date
				},
				deep: true,
				immediate: true
			},
			// #endif
		},
		methods: {
			getShowVal(index, o) {
				if (index === 1) {
					o = o + 1
				}
				return o > 9 ? o : '0' + o
			},

			// 打开弹窗
			open() {
				this.show = true
				this.updateColumnValue()
				this.$refs.popup.open()
			},
			// 关闭弹窗
			close() {
				this.innerValue = this.tempInitDate
				this.$emit('close')
				this.$refs.popup.close()
			},
			confirm() {
				this.$refs.popup.close()
				let date = this.tempDate || this.innerValue
				let regMap = {
					1: 'YYYY',
					2: 'YYYY-MM',
					3: 'YYYY-MM-DD',
					4: 'YYYY-MM-DD HH',
					5: 'YYYY-MM-DD HH:mm',
					6: 'YYYY-MM-DD HH:mm:ss'
				}
				let reg = regMap[this.showColumns]
				date = dayjs(date).format(reg)
				if (this.returnType !== 'string') {
					date = dayjs(date).valueOf()
				}
				this.$emit('confirm', date)
				this.$emit('input', date)
			},
			dateChange(date) {
				this.tempDate = date
			},

			updateColumnValue() {
				this.updateColumn()
				this.updateInnerIndex()
			},
			// 初始化各列的值
			updateColumn() {
				this.columns = this.getRanges().map(o => {
					return {
						type: o.type,
						column: this.generateArray(o.range)
					}
				})
			},
			// 初始化内部索引
			updateInnerIndex() {
				let tempIndexArr = []
				let innerDate = dayjs(this.innerValue)
				this.columns.map((o, i) => {
					let val = innerDate[typeArr[i]]()
					let index = o.column.findIndex((m, n) => val === m)
					// 没找到设置为第一项
					tempIndexArr.push(index === -1 ? 0 : index)
				})
				this.innerIndex = tempIndexArr
			},
			// 通过最大值和最小值生成数组
			generateArray([start, end]) {
				let tempArr = []
				for (let i = start; i < end + 1; i++) {
					tempArr.push(i)
				}
				return tempArr
			},
			// 获取每列的最大和最小值
			getRanges() {
				const {
					maxYear,
					maxDate,
					maxMonth,
					maxHour,
					maxMinute,
					maxSecond
				} = this.getBoundary('max', this.innerValue);
				const {
					minYear,
					minDate,
					minMonth,
					minHour,
					minMinute,
					minSecond
				} = this.getBoundary('min', this.innerValue);
				const result = [{
						type: 'year',
						range: [minYear, maxYear],
					},
					{
						type: 'month',
						range: [minMonth, maxMonth],
					},
					{
						type: 'day',
						range: [minDate, maxDate],
					},
					{
						type: 'hour',
						range: [minHour, maxHour],
					},
					{
						type: 'minute',
						range: [minMinute, maxMinute],
					},
					{
						type: 'second',
						range: [minSecond, maxSecond],
					}
				];
				return result.slice(0, this.showColumns);
			},
			// 根据minDate、maxDate等边界值，判断各列的开始和结束边界值
			getBoundary(type, innerValue) {
				const value = dayjs(innerValue)
				const boundary = dayjs(this[`${type}Date`])
				const year = boundary.year()
				let month = 0
				let date = 1
				let hour = 0
				let minute = 0
				let second = 0
				if (type === 'max') {
					month = 11
					// 月份的天数
					date = value.daysInMonth()
					hour = 23
					minute = 59
					second = 59
				}
				// 获取边界值，逻辑是：当年达到了边界值(最大或最小年)，就检查月允许的最大和最小值，以此类推
				if (value.year() === year) {
					month = boundary.month()
					if (value.month() === month) {
						date = boundary.date()
						if (value.date() === date) {
							hour = boundary.hour()
							if (value.hour() === hour) {
								minute = boundary.minute()
								if (value.minute() === minute) {
									second = boundary.second()
								}
							}
						}
					}
				}
				return {
					[`${type}Year`]: year,
					[`${type}Month`]: month,
					[`${type}Date`]: date,
					[`${type}Hour`]: hour,
					[`${type}Minute`]: minute,
					[`${type}Second`]: second
				}
			},

			// 得出合法的时间, 默认是最小时间
			correctValue(value = this.minDate) {
				// 控制在最小日期和最大日期之间
				value = dayjs(value).isBefore(dayjs(this.minDate)) ? this.minDate : value
				value = dayjs(value).isAfter(dayjs(this.maxDate)) ? this.maxDate : value
				this.innerValue = value
				return value
			},

			// 更改事件
			bindChange(e) {
				let indexs = e.detail.value
				let vals = indexs.map((o, i) => {
					return this.columns[i].column[indexs[i]]
				})
				if (vals.length > 2) {
					const maxDate = dayjs(`${vals[0]}-${vals[1]}`).daysInMonth()
					vals[2] = Math.min(maxDate, vals[2])
				}
				let selectValue = new Date(...vals)
				selectValue = this.correctValue(selectValue)
				this.tempDate = selectValue
				this.updateColumnValue(selectValue)
			}
		}
	}
</script>

<style lang="scss" scoped>
	.date-picker__header {
		display: flex;
		align-items: center;
		height: 40px;
		position: relative;
		border-bottom: solid 1rpx #F0F0F0;

		.title,
		.btn {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			display: flex;
			align-items: center;
			font-size: 12px;
		}

		.title {
			justify-content: center;
			font-weight: bold;
			color: #000000;
		}

		.btn {
			justify-content: space-between;

			.done {
				font-weight: 400;
				color: #409eff;
				flex-shrink: 0;
			}
		}
	}


	.picker-view {
		width: 100vw;
		height: 200px;
		margin: 20px 0;
	}

	.item {
		line-height: 50px;
		text-align: center;
	}
</style>
<template>
	<picker-view :indicator-style="indicatorStyle" :value="innerIndex" class="picker-view" @change="bindChange">
		<picker-view-column v-for="(item,index) in columns" :key="index">
			<view style="color: black;" class="item" v-for="(o,i) in item.column" :key="i">
				{{ index===1 ? o+1 : o }}{{label[index]}}
			</view>
		</picker-view-column>
	</picker-view>
</template>

<script>
	import props from './props'
	import dayjs from 'dayjs'
	const typeArr = ['year', 'month', 'date', 'hour', 'minute', 'second']
	export default {
		name: 'ex-datetime-comp',
		emits: ['update:modelValue','change'],
		props,
		watch: {
			propsChange() {
				this.updateColumnValue()
			},
			// 双向绑定
			// #ifndef VUE3
			value: {
				handler(val) {
					if (val) {
						this.innerValue = val
					}
				},
				deep: true,
				immediate: true
			},
			// #endif
			// #ifdef VUE3
			modelValue: {
				handler(val) {
					if (val) {
						this.innerValue = val
					}
				},
				deep: true,
				immediate: true
			},
			// #endif
		},
		computed: {
			// 如果以下这些变量发生了变化，意味着需要重新初始化各列的值
			propsChange() {
				return [this.showColumns, this.maxDate, this.minDate, this.value]
			}
		},
		mounted() {
			console.log('mounted=============')
			console.log(this.showColumns,this.minDate,this.maxDate,this.label)
			this.updateColumnValue()
		},

		data() {
			return {
				indicatorStyle: 'height: 50px;',
				innerIndex: [], // 内部索引
				columns: [], //内部数组
				innerValue: Number(new Date()) // 内部绑定的值
			}
		},
		methods: {
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
				this.emitChange(selectValue)
				this.updateColumnValue(selectValue)
			},
			emitChange(selectValue) {
				let val = dayjs(selectValue).format('YYYY-MM-DD HH:mm:ss')
				this.$emit('change', val)
				this.$emit('input', val)
			}
		}
	}
</script>

<style lang="scss" scoped>
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
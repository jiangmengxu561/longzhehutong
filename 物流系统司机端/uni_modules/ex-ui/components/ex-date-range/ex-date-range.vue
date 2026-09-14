<template>
	<view>
		<view class="date-range">
			<picker class="date-range-picker" mode="date" :value="timeRange[0]" @change="startDayChange">
				<view class="date">{{showStartTime}}</view>
			</picker>
			<text class="date-range-split">至</text>
			<picker class="date-range-picker" mode="date" :value="timeRange[1]" @change="endDayChange">
				<view class="date">{{showEndTime}}</view>
			</picker>
		</view>
		<view class="btn">
			<view class="btn-group" :style="{'--line-number':lineNumber,'--gap':gap}">
				<view v-for="(item, index) in dataList" :key="index" class="btn-group-item" @click="btnItemClick(item)"
					:style="[getBtnStyle(item)]">
					{{item.label}}
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	import dayjs from 'dayjs'
	import props from './props'
	export default {
		name: 'ex-date-range',
		emits: ['update:modelValue'],
		props,
		data() {
			return {
				show: false,
				defaultDate: null,
				timeRange: [],
			};
		},
		methods: {
			getBtnStyle(item) {
				let bol = JSON.stringify(this.timeRange) == JSON.stringify(item.value)
				return bol ? this.selectedStyle : this.unSelectedStyle
			},
			btnItemClick(item) {
				this.timeRange = item.value
				this.emitData()
			},
			close() {
				this.show = false
			},
			// 打开日历
			openCalenDar(index) {
				this.index = index
				this.defaultDate = this.timeRange?.[index] || dayjs(new Date()).format('YYYY-MM-DD')
				this.show = true
			},
			// 开始日期
			startDayChange({
				detail
			}) {
				let time = detail.value + ' 00:00:00'
				this.$set(this.timeRange, 0, dayjs(time).format(this.valueFormat))
				this.emitData()
			},
			// 结束日期
			endDayChange({
				detail
			}) {
				let time = detail.value + ' 23:59:59'
				this.$set(this.timeRange, 1, dayjs(time).format(this.valueFormat))
				this.emitData()
			},
			emitData() {
				// #ifndef VUE3
				this.$emit('input', this.timeRange)
				// #endif
				// #ifdef VUE3
				this.$emit('update:modelValue', this.timeRange)
				// #endif
			}
		},
		watch: {
			// 双向绑定
			// #ifndef VUE3
			value: {
				handler(val) {
					this.timeRange = val || []
				},
				deep: true,
				immediate: true
			},
			// #endif
			// #ifdef VUE3
			modelValue: {
				handler(val) {
					this.timeRange = val || []
				},
				deep: true,
				immediate: true
			},
			// #endif
		},
		computed: {
			showStartTime() {
				let val = this.timeRange?.[0]
				if (!val) {
					return this.startPlaceholder
				} else {
					return dayjs(val).format(this.showFormat)
				}
			},
			showEndTime() {
				let val = this.timeRange?.[1]
				if (!val) {
					return this.endPlaceholder
				} else {
					return dayjs(val).format(this.showFormat)
				}
			},
			dataList() {
				return this.list?.map(o => {
					let params = {
						type: o.type,
						nums: o.nums ?? 0,
						date: new Date(),
						reg: this.valueFormat,
						weekStart: this.weekStart,
						defaultTime: this.defaultTime
					}
					return {
						...o,
						value: uni.$ex.date.getDaterange(params)
					}
				}) || []
			}
		}
	}
</script>

<style lang="scss" scoped>
	@import '../../libs/css/btn-select.scss';

	.date-range {
		width: 100%;
		display: flex;
		gap: 10px;
		align-items: center;
		flex-wrap: wrap;
		margin-bottom: 10px;

		&-split {
			width: 25px;
			text-align: center;
			font-size: 12px;
			font-weight: 400;
			color: #4D4D4D;
		}

		&-picker {
			flex: 1;
			height: 36px;
			border-radius: 5px;
			font-size: 12px;
			background: #F5F5F5;
			color: #4D4D4D;

			.date {
				width: 100%;
				height: 36px;
				display: flex;
				align-items: center;
				justify-content: center;
			}
		}
	}
</style>
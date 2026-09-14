<template>
	<view>
		<ex-popup ref="popup" :type="type" :animation="animation" :is-mask-click="isMaskClick"
			:background-color="backgroundColor" :mask-background-color="maskBackgroundColor"
			:borderRadius="borderRadius" :safe-area="safeArea">
			<view class="popup-box" :style="{width,height}">
				<view class="popup-header" v-if="isShowTitle">
					<view class="title">{{title}}</view>
					<view class="btn">
						<text @click="cancel" class="cancel">取消</text>
						<text class="done" @click="confirm" :style="confirmTextStyle">确定</text>
					</view>
				</view>
				<view v-if="showFilter">
					<input class="search-input" v-model="filterVal" type="text" placeholder="请输入"
						@input="inputFilter" />
				</view>
				<scroll-view class="popup-content" :scroll-y="true">
					<view v-for="(item,index) in arr" :key="index" class="popup-content-item"
						@click="itemClick(item,index)">
						<slot :item="item" :index="index">
							<text>{{item[labelKey] }}</text>
						</slot>
						<template v-if="!item.disabled && !disableds.includes(item[valueKey])">
							<ex-icon :name="getIconName(item)" :color="iconColor"></ex-icon>
						</template>
					</view>
				</scroll-view>
			</view>
		</ex-popup>
	</view>
</template>

<script>
	import props from './props.js';
	import {
		selectMixin
	} from '../../libs/mixin/select.js'
	export default {
		name: 'ex-popup-select',
		components: {},
		props,
		mixins: [selectMixin],
		emits:['close','save','update:modelValue'],
		data() {
			return {
				filterVal: '',
				allList: [],
				oldSelectids: []
			};
		},
		computed: {
			labelKey() {
				return this?.props?.label ?? 'label'
			},
			valueKey() {
				return this?.props?.value ?? 'value'
			}
		},
		methods: {
			// 过滤
			inputFilter() {
				this.arr = this.allList.filter(m => {
					let tempStr = ''
					this.filterKeys.map(n => {
						let str = m?.[n] ?? ''
						tempStr = tempStr + str
					})
					if (this.searchDistinguishCapital) {
						return tempStr.toUpperCase().includes(this.filterVal.toUpperCase())
					} else {
						return tempStr.includes(this.filterVal)
					}
				})
			},
			getIconName(item) {
				let iconName = ''
				let bol = this.selecteds.includes(item[this.valueKey])
				// 多选
				if (this.multiple) {
					iconName = bol ? 'checkbox-all-fill' : 'checkbox-null'
				} else {
					iconName = bol ? 'radio-checked' : 'radio-null'
				}
				return iconName
			},
			// 打开弹窗
			open() {
				this.oldSelectids = [...this.selecteds]
				this.$refs.popup.open()
			},
			// 关闭
			close() {
				this.$emit('close')
				this.filterVal = ''
				this.inputFilter()
				this.$refs.popup.close()
			},
			// 取消
			cancel() {
				this.close()
				this.selecteds = [...this.oldSelectids]
			},
			//  确定
			confirm() {
				let filterArr = this.arr.filter(o => {
					return this.selecteds.includes(o[this.valueKey])
				})
				let value = this.multiple ? this.selecteds : this.selecteds?.[0]
				let labels = filterArr.map(o => o[this.labelKey]) || []
				// #ifndef VUE3
				this.$emit('input', value)
				// #endif
				// #ifdef VUE3
				this.$emit('update:modelValue', value)
				// #endif
				this.$emit('save', {
					value,
					label: this.multiple ? labels : labels.join(','),
					arr: filterArr
				})
				this.close()
			}
		}
	}
</script>

<style lang="scss" scoped>
	@import '../../libs/css/popup-select.scss';
</style>
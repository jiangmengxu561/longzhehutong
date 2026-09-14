<template>
	<view>
		<view v-if="showFilter">
			<input class="search-input" v-model="filterVal" type="text" placeholder="请输入" @input="inputFilter" />
		</view>
		<view v-for="(item,index) in arr" :key="index">
			<slot name="label" :item="item" :index="index">
				<view style="padding: 20rpx 0;">
					{{item[tagKey]}}
				</view>
			</slot>
			<view class="btn">
				<view class="btn-group" :style="{'--line-number':lineNumber,'--gap':gap}">
					<view v-for="(subItem, subIndex) in item[listKey]" :key="subIndex" class="btn-group-item"
						@click="btnItemClick(subItem,subIndex)" :style="[getBtnStyle(subItem)]">
						<text>{{subItem[labelKey] }}</text>
						<view class="btn-group-badge" :style="[getBadgePosition]" v-if="showBadge">
							<text
								:class="['exicon-iconfont', badgePosition === 'top'?'exicon-right-top-mark-check':'exicon-right-bot-mark-check' ]"
								:style="[iconStyle(subItem)]"></text>
						</view>
					</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	import props from './props.js'
	import btnProps from '../ex-btn-select/props.js'
	import {
		selectMixin,
		btnSelectMixin
	} from '../../libs/mixin/select.js'
	export default {
		name: 'ex-btn-group',
		props: {
			...btnProps,
			...props
		},
		data() {
			return {
				filterVal: '',
				allList: []
			}
		},
		mixins: [selectMixin, btnSelectMixin],
		computed: {
			tagKey() {
				return this?.props?.label ?? 'label'
			},
			listKey() {
				return this?.props?.subListKey ?? 'list'
			}
		},
		methods: {
			iconStyle(subItem) {
				return {
					fontSize: this.badgeSize,
					color: this.getBadegColor(subItem)
				}
			},
			// 过滤
			inputFilter() {
				let tempList = uni.$ex.utils.deepClone(this.allList)
				let tempArr = []
				for (let i = 0; i < tempList.length; i++) {
					let m = tempList[i]
					m[this.listKey] = m[this.listKey].filter(n => {
						let tempStr = ''
						this.filterKeys.map(o => {
							let str = n?.[o] ?? ''
							tempStr = tempStr + str
						})
						return tempStr.includes(this.filterVal)
					})
					if (m[this.listKey].length > 0) {
						tempArr.push(m)
					}
				}
				this.arr = uni.$ex.utils.deepClone(tempArr)
			}
		}
	}
</script>

<style lang="scss" scoped>
	@import '../../libs/css/btn-select.scss';
	@import url(../../libs/css/icon.scss);

	.search-input {
		border: solid 1rpx #DDDDDD;
		padding: 10px;
		margin: 10px 0;
		border-radius: 5px;
	}
</style>
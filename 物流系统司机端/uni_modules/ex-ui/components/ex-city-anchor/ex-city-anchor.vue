<template>
	<view class="city-anchor">
		<slot name="top">
			<view class="city-anchor-selected">
				<view class="city-anchor-selected-label">
					<text> 已选
						<template v-if="multiple">
							<text>
								（</text> <text>{{selecteds.length}}</text> <text> /{{maxNum}}）
							</text>
						</template>
					</text>
				</view>
				<view class="city-anchor-selected-tag-box">
					<ex-tag :text="item[labelKey]" v-for="(item,index) in selectItems" :tagStyle="selectedStyle"
						style="flex-shrink: 0;" :key="index">
						<template v-slot:right>
							<view @click="removeItem(index)" style="display: flex;align-items: center;">
								<ex-icon v-if="cancelSelectItem" name="close" :color="selectedStyle.color" size="10px"
									style="margin-left: 6px;"></ex-icon>
							</view>
						</template>
					</ex-tag>
				</view>
			</view>
		</slot>
		<scroll-view scroll-y="true" ref="scrollRef" class="city-anchor-scroll-view" :scroll-into-view="anchorVal"
			:style="{height:scrollViewHeight}" :scroll-with-animation="true" :show-scrollbar="showScrollbar"
			@scroll="scroll" @scrolltolower="scrolltolower">
			<view v-for="(item,index) in arr" :key="index" :id="item.$uniqueKey" class="city-anchor-scroll-view-item">
				<view class="city-anchor-scroll-view-item-label">
					{{item[tagKey]}}
				</view>
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
		</scroll-view>
		<view class="index-list" :style="[indexStyle]">
			<view v-for="(item,index) in arr" :key="index" class="index-item"
				:style="[activeKey==item.$uniqueKey ? indexItemActiveStyle:indexItemUnActiveStyle]"
				@click="setAnchor(item.$uniqueKey)">
				{{item[indexKey] || ''}}
			</view>
		</view>
	</view>
</template>
<script>
	import props from './props.js'
	import btnProps from '../ex-btn-select/props.js'
	import anchorProps from '../ex-anchor/props.js'
	import anchorMixin from '../../libs/mixin/anchor.js'
	import {
		selectMixin,
		btnSelectMixin
	} from '../../libs/mixin/select.js'
	export default {
		name: 'ex-city-anchor',
		mixins: [selectMixin, btnSelectMixin, anchorMixin],
		props: {
			...anchorProps,
			...btnProps,
			...props
		},
		watch: {
			list: {
				handler(val) {
					let tempArr = []
					val?.map(o => {
						if (o?.[this.listKey] && o?.[this.listKey]?.length > 0) {
							o.$uniqueKey = o.$uniqueKey ?? uni.$ex.utils.generateDomId()
							tempArr.push(o)
						}
					})
					this.arr = tempArr
					this.$nextTick(() => {
						this.setArr()
					})
				},
				deep: true,
				immediate: true
			}
		},
		computed: {
			tagKey() {
				return this?.props?.label ?? 'label'
			},
			listKey() {
				return this?.props?.subListKey ?? 'list'
			},
			selectItems() {
				let tempArr = []
				this.arr.map(o => {
					o[this.listKey].map(m => {
						let tempVal = m[this.valueKey]
						// 单选或者多选
						if (tempVal === this.selecteds || this.selecteds.includes(tempVal)) {
							tempArr.push(m)
						}
					})
				})
				return uni.$ex.utils.arrayFilterByKey(tempArr)
			},
		},
		data() {
			return {}
		},
		methods: {
			removeItem(index) {
				this.selecteds.splice(index, 1)
			},
			iconStyle(subItem) {
				return {
					fontSize: this.badgeSize,
					color: this.getBadegColor(subItem)
				}
			}
		}
	}
</script>
<style lang="scss" scoped>
	@import '../../libs/css/btn-select.scss';
	@import url(../../libs/css/icon.scss);

	.city-anchor {
		&-selected {
			padding: 0px 40px 0px 20px;
			height: 60px;
			display: flex;
			align-items: center;

			&-label {
				font-size: 12px;
				font-weight: bold;
				color: #333333;
				margin-right: 20px;
				flex-shrink: 0;
			}

			&-tag-box {
				display: flex;
				align-items: center;
				flex: 1;
				overflow-x: auto;
				gap: 10px;
			}
		}

		&-scroll-view {
			&-item {
				padding-right: 40px;
				padding-left: 20px;
				padding-bottom: 20px;

				&-label {
					font-size: 14px;
					font-weight: bold;
					color: #333333;
					padding-bottom: 12px;
				}
			}
		}
	}


	.index-list {
		position: fixed;

		.index-item {
			font-size: 12px;
			padding: 3px 0;
			display: flex;
			align-items: center;
			justify-content: center;
			min-width: 22px;
		}
	}
</style>
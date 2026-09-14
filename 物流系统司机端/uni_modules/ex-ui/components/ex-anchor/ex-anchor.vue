<template>
	<view class="anchor">
		<scroll-view scroll-y="true" ref="scrollRef" class="scroll-view" :scroll-into-view="anchorVal"
			:style="{height:scrollViewHeight}" :scroll-with-animation="true" :show-scrollbar="showScrollbar"
			@scroll="scroll" @scrolltolower="scrolltolower">
			<view v-for="(item,index) in arr" :key="index" :id="item.$uniqueKey">
				<slot :item="item" :index="index">
				</slot>
			</view>
		</scroll-view>
		<view class="anchor-index-list" :style="[indexStyle]">
			<view v-for="(item,index) in arr" :key="index" class="anchor-index-item"
				:style="[activeKey==item.$uniqueKey ? indexItemActiveStyle:indexItemUnActiveStyle]"
				@click="setAnchor(item.$uniqueKey)">
				{{item[indexKey]}}
			</view>
		</view>
	</view>
</template>
<script>
	import props from './props.js'
	import anchorMixin from '../../libs/mixin/anchor.js'
	export default {
		name: 'ex-anchor',
		props,
		mixins: [anchorMixin],
		watch: {
			list: {
				handler(val) {
					this.arr = val?.map(o => {
						o.$uniqueKey = o.$uniqueKey ?? uni.$ex.utils.generateDomId()
						return o
					}) || []
					this.$nextTick(() => {
						this.setArr()
					})
				},
				deep: true,
				immediate: true
			}
		}
	}
</script>
<style lang="scss" scoped>
	.anchor-index-list {
		position: fixed;
		z-index: 100;

		.anchor-index-item {
			font-size: 12px;
			padding: 3px 0;
			display: flex;
			align-items: center;
			justify-content: center;
			min-width: 22px;
		}
	}
</style>
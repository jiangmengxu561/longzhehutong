<template>
	<view class="swiper-item" :style="[swiperItemStyle]">
		<slot></slot>
	</view>
</template>
<script>
	import props from './props.js';
	export default {
		name: 'ex-swiper-item',
		inject: ['swiperItemNum'],
		options: {
			multipleSlots: true,
			styleIsolation: 'isolated',
			addGlobalClass: true,
			virtualHost: true
		},
		props,
		components: {},
		data() {
			return {}
		},
		methods: {},
		computed: {
			swiperItemStyle() {
				return {
					'transform': `rotateY(${(360 / this.swiperItemNum) * this.index}deg) translateZ( ${this.slideItemOffSet * this.swiperItemNum}rpx)`,
					'-webkit-transform': `rotateY(${(360 / this.swiperItemNum) * this.index}deg) translateZ( ${this.slideItemOffSet * this.swiperItemNum}rpx)`,
					'-webkit-box-reflect': this.showBoxReflect ? this.boxReflect : '',
					'box-reflect': this.showBoxReflect ? this.boxReflect : ''
				}
			},
			activeIndex() {
				return this.curentIndex
			}
		}
	}
</script>
<style lang="scss" scoped>
	.swiper-item {
		position: absolute;
	}
</style>
<template>
	<view class="swiper">
		<view class="swiper-perspective" :style="[perspectiveStyle]" @touchstart.stop="touchstart"
			@touchend.stop="touchend">
			<view class="animation" :animation="animationData" :style="swiperStyle">
				<slot></slot>
			</view>
		</view>
	</view>
</template>

<script>
	import props from './props.js';
	export default {
		name: 'ex-swiper',
		emits: ['change'],
		provide() {
			return {
				swiperItemNum: this.swiperItemNum
			}
		},
		props,
		computed: {
			perspectiveStyle() {
				return {
					'perspective': this.perspective,
					'-webkit-perspective': this.perspective,
					'transform': `rotateX(${this.rotateX}deg)`
				}
			}
		},
		watch: {
			acIndex: {
				handler(val) {
					console.log(val)
				},
				deep: true,
				immediate: true
			}
		},
		data() {
			return {
				animationData: {},
				rotateY: 0,
				startX: 0, // 滑动X轴起始位置
				startY: 0, // 滑动Y轴起始位置 
				animation: null,
				activeIndex: 0
			}
		},
		methods: {
			touchstart(e) {
				this.clearInterval()
				this.startX = e.changedTouches[0].clientX
			},
			touchend(e) {
				let endX = e.changedTouches[0].clientX
				let xDirection = endX - this.startX
				this.rotate(xDirection)
				this.$nextTick(() => {
					this.createInterver()
				})
			},
			rotate(xDirection) {
				// 左滑<0 右滑
				if (xDirection < -10) {
					this.activeIndex = (this.activeIndex + this.swiperItemNum + 1) % this.swiperItemNum
					this.rotateY = this.rotateY - 360 / this.swiperItemNum
				} else if (xDirection > 10) {
					this.activeIndex = (this.activeIndex + this.swiperItemNum - 1) % this.swiperItemNum
					this.rotateY = this.rotateY + 360 / this.swiperItemNum
				}
				this.$emit('change', this.activeIndex)
				this.animation.rotateY(this.rotateY).step()
				this.animationData = this.animation.export()
			},
			createInterver() {
				if (this.autoplay) {
					this.interVer = setInterval(() => {
						this.rotate(-15)
					}, this.interval)
				}
			},
			clearInterval() {
				clearInterval(this.interVer)
			}
		},
		created() {
			let animation = uni.createAnimation({
				duration: 200
			})
			this.animation = animation
			this.animationData = animation.export()

			// 初始化
			this.activeIndex = this.acIndex ?? 0
			let oneDeg = 360 / this.swiperItemNum
			this.rotateY = -oneDeg * this.activeIndex
			this.animation.rotateY(this.rotateY).step()
			this.animationData = this.animation.export()
		},
		mounted() {
			this.createInterver()
		},
		destroyed() {
			console.log('desyrrr')
			this.clearInterval()
		}
	}
</script>

<style lang="scss" scoped>
	.swiper {
		width: 100%;
		height: 100%;
		background-color: white;

		&-perspective {
			transform-style: preserve-3d;
			-webkit-transform-style: preserve-3d;

			.animation {
				margin: 0 auto;
				position: relative;
				transform-style: preserve-3d;
				/*添加3D*/
				-webkit-transform-style: preserve-3d;
				/*添加3D*/
				transform: rotateY(0deg);
				transform-origin: 'center center center';
				-webkit-transform-origin: 'center center center';
			}
		}
	}
</style>
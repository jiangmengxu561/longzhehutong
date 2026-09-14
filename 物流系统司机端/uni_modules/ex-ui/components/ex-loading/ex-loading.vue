<template>
	<view class="ex-loading">
		<view class="ex-loading-box" :style="[loadingBoxStyle]">
			<view class="ex-loading-box-item" v-for="(item,index) in 12" :key="index">
				<view class="ex-loading-box-item-content" :style="[itemStyle]">

				</view>
			</view>
		</view>
	</view>
</template>
<script>
	import props from './props.js'
	export default {
		name: 'ex-loading',
		props,
		components: {},
		data() {
			return {}
		},
		methods: {},
		computed: {
			loadingBoxStyle() {
				return {
					width: this.size,
					height: this.size,
					'animation-duration': this.duration + 'ms',
					color: this.color
				}
			},
			itemStyle() {
				if (this.type == 'dot') {
					return {
						width: '8px',
						height: '8px',
						borderRadius: '50%',
						backgroundColor: this.color
					}
				} else {
					return {
						width: '2px',
						height: '28%',
						backgroundColor: this.color
					}
				}

			}
		}
	}
</script>
<style lang="scss" scoped>
	.ex-loading {
		.ex-loading-box {
			position: relative;
			-webkit-animation: rotation 1.2s linear infinite;
			animation: rotation 1.2s linear infinite;
			animation-timing-function: steps(12);

			.ex-loading-box-item {
				position: absolute;
				top: 0;
				left: 0;
				width: 100%;
				height: 100%;

				.ex-loading-box-item-content {
					display: block;
					margin: 0 auto;
					border-radius: 40%;
				}
			}
		}
	}

	@keyframes rotation {
		0% {
			transform: rotate(0deg);
		}

		100% {
			transform: rotate(1turn);
		}
	}

	@for $i from 1 through 12 {
		.ex-loading-box-item:nth-of-type(#{$i}) {
			transform: rotate($i * 30deg);
			opacity: 1 - 0.0625 * ($i - 1);
		}
	}
</style>
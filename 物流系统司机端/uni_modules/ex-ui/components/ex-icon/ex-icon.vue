<template>
	<view class="icon">
		<text class="exicon-iconfont" :class="classes" :style="[iconStyle]"></text>
	</view>
</template>

<script>
	import props from './props.js'
	export default {
		props,
		name: 'ex-icon',
		data() {
			return {}
		},
		computed: {
			classes() {
				let classes = []
				classes.push(this.customPrefix + '-' + this.name)
				// 阿里，头条，百度小程序通过数组绑定类名时，无法直接使用[a, b, c]的形式，否则无法识别
				// 故需将其拆成一个字符串的形式，通过空格隔开各个类名
				//#ifdef MP-ALIPAY || MP-TOUTIAO || MP-BAIDU
				classes = classes.join(' ')
				//#endif
				return classes
			},
			iconStyle() {
				return {
					fontSize: this.size,
					fontWeight: this.bold ? 'bold' : 'normal',
					color: this.color
				}
			}
		}
	}
</script>

<style lang="scss" scoped>
	@import url(../../libs/css/icon.scss);
	.icon {
		display: flex;
		align-items: center;
		justify-content: center;
		.exicon-iconfont {
			font-family: exicon-iconfont;
		}
	}
</style>
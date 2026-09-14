<template>
	<view class="content">
		<view style="width: 750rpx;height: 272rpx;background: linear-gradient( 180deg, #D8EEFC 0%, #F7F8FA 100%);"></view>
		<view style="margin-top:-272rpx;"></view>
		<view style="width: 750rpx;z-index: 9;position: fixed;top:0;left:0;color:#000;"
			:style="scrollTop>0?'background:#FFF;height:'+(statusBarHeight+navigationBarHeight)+'px':''">
			<view :style="'height:'+statusBarHeight+'px'"></view>
			<view class="flexJusp" style="font-weight: bold;font-size: 36rpx;align-items: center;"
				:style="'height:'+navigationBarHeight+'px;line-height:'+navigationBarHeight+'px'">
				<view style="width:100rpx;text-align: center;" @click="tuichu">
					<image style="width:20rpx;height:34rpx;" src="/static/images/mv_tuichu.png"></image>
				</view>
				<view>我的账单</view>
				<view style="width:100rpx;"></view>
			</view>
		</view>
		<view :style="'height:'+(statusBarHeight+navigationBarHeight)+'px'"></view>
		<view class="flexJusp" v-for="(item,index) in list" :key="index" style="margin:20rpx;border-radius:20rpx;background-color: #FFF;padding:24rpx;align-items: center;">
			<view style="align-items: center;">
				<view style="color: #333333;">支出</view>
				<view style="font-size: 24rpx;color: #B0B3BF;margin-top:8rpx;">2022 01-05</view>
			</view>
			<view style="font-size: 30rpx;color: #E50014;">-12.00</view>
		</view>
		<view style="height:60rpx;"></view>
		
	</view>
</template>

<script>
	import {
		systemInfo
	} from '@/https/mixin.js'
	export default {
		mixins: [systemInfo],
		data() {
			return {
				imgUrl: this.$utils.imgUrl,
				banner: [],
				notice: [],
				carType: 0,
				kaipiao: 0,
				array: ['类型一'],
				index: 0,
				daishou: 0,
				num: 1,
				yaoqiu: 0,
				check: false,
				scrollTop: 0,
				list:[]
			}
		},
		onLoad() {
			this.getSystemInfo()
			this.loads()
		},
		onPageScroll(event) {
			const scrollTop = event.scrollTop // 获取当前页面滚动高度
			this.scrollTop = event.scrollTop
		},
		methods: {
			tuichu() {
				uni.navigateBack()
			},
			loads() {
				this.$httpapi('Placeorder/billList', 'POST', {}).then(res => {
					this.list = res.data
				})
			}
		}
	}
</script>

<style lang="scss">
	.post{
		position: fixed;
		bottom:0;
		left:0;
		width:750rpx;
		background-color: #FFF;
	}

	.buttons {
		width: 160rpx;
		height: 56rpx;
		background: linear-gradient( 180deg, #4897FF 0%, #4878FF 100%);
		border-radius: 32rpx;
		font-size: 26rpx;
		color: #FFFFFF;
		line-height: 56rpx;
		text-align: center;
	}

	.buttonx {
		width: 160rpx;
		height: 56rpx;
		background: #FFFFFF;
		border-radius: 32rpx;
		border: 1rpx solid #4878FF;
		font-size: 26rpx;
		color: #4878FF;
		line-height: 56rpx;
		text-align: center;
	}
</style>
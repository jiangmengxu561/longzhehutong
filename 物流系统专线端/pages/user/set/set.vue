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
				<view>设置</view>
				<view style="width:100rpx;"></view>
			</view>
		</view>
		<view :style="'height:'+(statusBarHeight+navigationBarHeight)+'px'"></view>
		<view class="flexJusp" @click="agreement('yonghu')" style="align-items: center;height:120rpx;margin:0 20rpx;border-bottom:2rpx solid #F5F5F5;">
			<view style="">用户协议</view>
			<image style="width:16rpx;height:26rpx;" src="/static/images/icon_27jt.png"></image>
		</view>
		<view class="flexJusp" @click="agreement('yinsi')" style="align-items: center;height:120rpx;margin:0 20rpx;border-bottom:2rpx solid #F5F5F5;">
			<view style="">隐私政策</view>
			<image style="width:16rpx;height:26rpx;" src="/static/images/icon_27jt.png"></image>
		</view>
	
		<view class="flexJusp" @click="agreement('guanyu')" style="align-items: center;height:120rpx;margin:0 20rpx;border-bottom:2rpx solid #F5F5F5;">
			<view style="">关于我们</view>
			<image style="width:16rpx;height:26rpx;" src="/static/images/icon_27jt.png"></image>
		</view>
		<view class="buttons" @click="tuideng">退出登录</view>
		<view style="height:180rpx;"></view>
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
				scrollTop:0,
			}
		},
		onLoad() {
			this.getSystemInfo()
		},
		onPageScroll(event) {
			const scrollTop = event.scrollTop // 获取当前页面滚动高度
			this.scrollTop = event.scrollTop
		},
		methods: {
			ChangePassword(){
				uni.navigateTo({
					url:'/pages/user/set/ChangePassword'
				})
			},
			agreement(type){
				uni.navigateTo({
					url:'/pages/user/set/agreement?type='+type
				})
			},
			tuideng(){
				uni.setStorageSync('uid', '')
				uni.setStorageSync('token', '')
				uni.reLaunch({
					url: '/pages/login/login'
				})
			},
			tuichu(){
				uni.navigateBack()
			},
		}
	}
</script>

<style lang="scss">
	.content {
		.buttons{
			position: fixed;
			left:30rpx;
			bottom:88rpx;
			width: 690rpx;
			height: 88rpx;
			background: linear-gradient( 180deg, #4897FF 0%, #4878FF 100%);
			border-radius: 50rpx;
			font-weight: bold;
			font-size: 30rpx;
			color: #FFFFFF;
			line-height: 88rpx;
			text-align: center;
		}
	}
</style>

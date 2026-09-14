<template>
	<view>
		<image style="width:750rpx;height:440rpx;display: block;" src="/static/images/icon_47bj.png"></image>
		<view style="margin-top:-440rpx;"></view>
		<view style="width: 750rpx;z-index: 9;position: fixed;top:0;left:0;color:#FFF;"
			:style="scrollTop>0?'background:#FFF;color:#000;height:'+(statusBarHeight+navigationBarHeight)+'px':''">
			<view :style="'height:'+statusBarHeight+'px'"></view>
			<view class="flexJusp" style="font-weight: bold;font-size: 36rpx;align-items: center;"
				:style="'height:'+navigationBarHeight+'px;line-height:'+navigationBarHeight+'px'">
				<view style="width:100rpx;text-align: center;" @click="tuichu">
					<image style="width:20rpx;height:34rpx;" src="/static/images/mv_tuichus.png"></image>
				</view>
				<view>我的钱包</view>
				<view style="width:100rpx;"></view>
			</view>
		</view>
		<view style="position: relative;z-index:2;">
			<view :style="'height:'+(statusBarHeight+navigationBarHeight)+'px'"></view>
			<view style="height:202rpx;align-items: center;" class="flexJusp">
				<view style="width:300rpx;text-align: center;">
					<view style="font-weight: bold;font-size: 52rpx;color: #FFFFFF;">{{money}}</view>
					<view style="font-size: 26rpx;color: #FFFFFF;">余额</view>
				</view>
				<view @click="withdraw" style="width: 120rpx;height: 60rpx;background: #FFFFFF;border-radius: 38rpx 0rpx 0rpx 38rpx;font-size: 26rpx;color: #4878FF;line-height: 60rpx;text-align: center;">提现</view>
			</view>
			<view style="background: #F7F8FA;border-radius: 40rpx 40rpx 0rpx 0rpx;padding:32rpx 20rpx;">
				<view style="font-weight: bold;font-size: 30rpx;padding-bottom:32rpx;">余额明细</view>
				<view v-for="(item,index) in list" :key="index" style="padding:28rpx 24rpx;margin:0 0 20rpx;background: #FFFFFF;border-radius: 16rpx;" class="flexJusp">
					<view>
						<view>{{item.memo}}</view>
						<view style="font-size: 24rpx;color: #B0B3BF;">{{item.createtime}}</view>
					</view>
					<view>{{item.money}}</view>
				</view>
			</view>
		</view>
	
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
				list:[],
				money:0
			};
		},
		onLoad() {
			this.getSystemInfo()
		},
		onShow() {
			this.loads()
		},
		methods:{
			withdraw(){
				uni.navigateTo({
					url:'/pages/user/myWallet/withdraw'
				})
			},
			tuichu(){
				uni.navigateBack()
			},
			loads(){
				this.$httpapi('Opinion/getuserinfo', 'GET', {}).then(res => {
					this.money = res.data.money
				})
				this.$httpapi('user/user_money_log', 'POST', {}).then(res => {
					this.list = res.data
				})
			}
		}
	}
</script>

<style lang="scss">

</style>

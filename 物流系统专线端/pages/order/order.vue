<template>
	<view class="content">
		<view style="width: 750rpx;height: 272rpx;background: linear-gradient( 180deg, #D8EEFC 0%, #F7F8FA 100%);"></view>
		<view style="margin-top:-272rpx;"></view>
		<view style="width: 750rpx;z-index: 9;position: fixed;top:0;left:0;color:#000;"
			:style="scrollTop>0?'background:#FFF;height:'+(statusBarHeight+navigationBarHeight)+'px':''">
			<view :style="'height:'+statusBarHeight+'px'"></view>
			<view class="flexJusp" style="font-weight: bold;font-size: 36rpx;align-items: center;"
				:style="'height:'+navigationBarHeight+'px;line-height:'+navigationBarHeight+'px'">
				<view style="width:100rpx;"></view>
				<view>我的订单</view>
				<view style="width:100rpx;"></view>
			</view>
			<view class="flexJusp" style="margin:0 28rpx;">
				<view style="text-align: center;" @click="qiehuans(0)">
					<view :style="types==0?'font-weight: bold;':''">全部订单</view>
					<view class="xian" v-if="types==0"></view>
				</view>
				<view style="text-align: center;" @click="qiehuans(3)">
					<view :style="types==3?'font-weight: bold;':''">待接单</view>
					<view class="xian" v-if="types==3"></view>
				</view>
				<view style="text-align: center;" @click="qiehuans(1)">
					<view :style="types==1?'font-weight: bold;':''">服务中</view>
					<view class="xian" v-if="types==1"></view>
				</view>
				<view style="text-align: center;" @click="qiehuans(2)">
					<view :style="types==2?'font-weight: bold;':''">已完成</view>
					<view class="xian" v-if="types==2"></view>
				</view>
				<view style="text-align: center;" @click="qiehuans(4)">
					<view :style="types==4?'font-weight: bold;':''">已取消</view>
					<view class="xian" v-if="types==4"></view>
				</view>
			</view>
		</view>
		<view :style="'height:'+(statusBarHeight+navigationBarHeight+28)+'px'"></view>
		<view v-for="(item,index) in list" :key="index" style="background: #FFFFFF;border-radius: 24rpx;padding:24rpx;margin:20rpx;">
			<view class="flexJusp">
				<view style="color: #666666;">{{item.createtime}}</view>
				<view style="color: #F29100;">	
					{{item.status_name}}
				</view>
			</view>
			<view class="flex" style="align-items: center;padding:24rpx 0;" @click="details(item)">
				<view
					style="width: 48rpx;height: 48rpx;background: #F29100;border-radius: 12rpx;font-weight: bold;color: #FFFFFF;line-height: 48rpx;text-align: center;margin-right:12rpx;">
					装</view>
				<!-- <view style="width: 16rpx;height: 16rpx;background: #F29100;border-radius:50%;margin-right: 12rpx;"></view> -->
				<view style="width:630rpx;font-weight: bold;font-size: 30rpx;">{{item.loading_address }}</view>
			</view>
			<view class="flex" style="align-items: center;" @click="details(item)">
				<view
					style="width: 48rpx;height: 48rpx;background: #4878FF;border-radius: 12rpx;font-weight: bold;color: #FFFFFF;line-height: 48rpx;text-align: center;margin-right:12rpx;">
					卸</view>
				<!-- <view style="width: 16rpx;height: 16rpx;background: #4878FF;border-radius:50%;margin-right: 12rpx;"></view> -->
				<view style="width:630rpx;font-weight: bold;font-size: 30rpx;">{{ item.unload_address}}</view>
			</view>
			<view class="flexJusp" style="padding-top:24rpx;border-top:2rpx solid #F7F7F7;align-items: center;margin-top:24rpx;">
				<view style="font-size:24rpx;color:#666;">总价:<text style="font-weight: 800;font-size: 30rpx;color: #EC0000;">￥{{item.price}}</text></view>
				<!-- <view class="flex" v-if="item.status==3">
					<view class="buttons" @click="qiangdan(item)">取消</view>
					<view class="buttons" @click="qiangdan(item)">接单</view>
				</view> -->
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
				imgUrl: this.$utils.imgUrl,
				scrollTop:0,
				types:0,
				list:[]
			}
		},
		onLoad() {
			this.getSystemInfo()
		},
		onShow() {
			// 检查是否登录
			if (!uni.getStorageSync('token')) {
				uni.showModal({
					title: '请登录',
					content: '您需要登录后才能查看订单',
					confirmText: '去登录',
					success: (res) => {
						if (res.confirm) {
							uni.navigateTo({
								url: '/pages/login/login'
							})
						} else {
							// 如果取消，跳转到首页
							uni.switchTab({
								url: '/pages/index/index'
							})
						}
					}
				})
				return
			}
			this.loads()
		},
		onPullDownRefresh() {
			this.loads()
		},
		onPageScroll(event) {
			const scrollTop = event.scrollTop // 获取当前页面滚动高度
			this.scrollTop = event.scrollTop
		},
		methods: {
			quxiao(item){
				this.$httpapi('Dedicated/cancel', 'POST', {order_id:item.order_id}).then(res => {
					uni.showToast({
						title: res.msg,
						duration: 1500,
						icon:'none'
					});
					this.loads()
				})
			},
			fukuan(item){  
			},
			details(item){
				uni.navigateTo({
					url:'/pages/order/orderDetail/orderDetail?id='+item.order_id+'&ids='+item.id
				})
			},
			qiehuans(type){
				this.types = type
				this.loads()
			},
			loads() {
				this.$httpapi('Dedicated/dricerorder', 'POST', {type:this.types}).then(res => {
					this.list = res.data
					uni.stopPullDownRefresh()
				})
			}
		}
	}
</script>

<style>
	.xian {
		margin:4rpx auto 0;
		width: 28rpx;
		height: 6rpx;
		background: linear-gradient( 180deg, #4897FF 0%, #4878FF 100%);
		border-radius: 4rpx;
	}

	.buttons {
		width: 138rpx;
		height: 52rpx;
		background: linear-gradient( 180deg, #4897FF 0%, #4878FF 100%);
		border-radius: 26rpx;
		font-size: 26rpx;
		color: #FFFFFF;
		line-height: 52rpx;
		text-align: center;
	}
	.buttonx{
		width: 138rpx;
		height: 52rpx;
		background: #FFFFFF;
		border-radius: 26rpx;
		border: 1rpx solid #CCCCCC;
		font-size: 26rpx;
		color: #666666;
		line-height: 52rpx;
		text-align: center;
	}
</style>
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
				<view>专线接单</view>
				<view style="width:100rpx;"></view>
			</view>
			<!-- <view class="flexJusp" style="margin:0 28rpx;">
				<view style="text-align: center;" @click="qiehuans(0)">
					<view :style="types==0?'font-weight: bold;':''">地点</view>
					<view class="xian" v-if="types==0"></view>
				</view>
				<view style="text-align: center;" @click="qiehuans(1)">
					<view :style="types==1?'font-weight: bold;':''">时间</view>
					<view class="xian" v-if="types==1"></view> 
				</view>
			</view> -->
		</view>
		<view :style="'height:'+(statusBarHeight+navigationBarHeight+28)+'px'"></view>
		
		<swiper class="banners" circular :autoplay="true" :interval="3000" :duration="1000">
			<swiper-item v-for="(item,index) in banner" :key="index">
				<image :src="imgUrl+item" mode="aspectFill" style="width:100%;height:100%;border-radius:20rpx;">
				</image>
			</swiper-item>
		</swiper>
		<view v-if="list.length==0" style="margin:100rpx 0 0;text-align: center;">暂无数据</view>
		<view v-for="(item,index) in list" :key="index" style="background: #FFFFFF;border-radius: 24rpx;padding:24rpx;margin:20rpx;">
				<view style="color: #666666;">订单编号：{{item.orderid}}</view>
			
			<view class="flex" style="align-items: center;margin:20rpx 0;font-size:30rpx;font-weight: bold;" @click="details(item)">
				<view>{{item.origincity}}</view>
				<image style="width:32rpx;height:32rpx;margin:0 20rpx;" src="/static/images/icon_36j.png"></image>
				<view>{{item.province}}</view>
			</view>
			<!-- <view @click="details(item)" style="background: #F0F7FF;border-radius: 16rpx;padding:20rpx 24rpx;font-size: 24rpx;color: #666666;margin-top:20rpx;">
				<view>{{item.find_car_type||""}}  {{item.goods_type_id||""}}  {{item.packaging_id||""}}</view>
			</view> -->
			<view class="flexJusp" style="margin-top:20rpx;align-items: center;">
				<view style="font-weight: 800;font-size: 30rpx;color: #EC0000;">￥{{item.logistics_driver_cost||'未定义价格'}}</view>
				<view class="buttons" @click="qiangdan(item)">抢单</view>
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
				list:[],
				banner:[]
			}
		},
		onLoad() {
			this.getSystemInfo()
		},
		onShow() {
			this.loads()
		},
		onPullDownRefresh() {
			this.list = []
			this.loads()
		},
		onPageScroll(event) {
			const scrollTop = event.scrollTop // 获取当前页面滚动高度
			this.scrollTop = event.scrollTop
		},
		methods: {
			qiangdan(item){
				// 检查是否登录
				if (!uni.getStorageSync('token')) {
					uni.showModal({
						title: '请登录',
						content: '您需要登录后才能抢单',
						confirmText: '去登录',
						success: (res) => {
							if (res.confirm) {
								uni.navigateTo({
									url: '/pages/login/login'
								})
							}
						}
					})
					return
				}
				this.$httpapi('Dedicated/addorder', 'POST', {
					order_id:item.orderid
				}).then(res => {
					uni.showToast({
						title: res.msg,
						duration: 1500,
						icon:'none'
					});
					if (res.code == 1) {
						setTimeout(() => {
							uni.switchTab({
								url:'/pages/order/order'
							})
						}, 1500)
					}
				})
			},
			quxiao(item){
				this.$httpapi('Placeorder/CancelOrder', 'POST', {order_id:item.orderid}).then(res => {
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
					url:'/pages/index/orderDetail/orderDetail?id='+item.orderid
				})
			},
			qiehuans(type){
				this.types = type
				this.loads()
			},
			loads() {
				this.$httpapi('Dedicated/OrderList', 'POST', {
					address:'',
					time:''
				}).then(res => {
					uni.stopPullDownRefresh()
					this.list = res.data
				})
				this.$httpapi('Placeorder/banner', 'GET', {}).then(res => {
					this.banner = res.data
				})
			}
		}
	}
</script>

<style>
	.banners {
		width: 710rpx;
		height: 250rpx;
		margin:0 20rpx 20rpx;
		border-radius: 20rpx;
	}
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
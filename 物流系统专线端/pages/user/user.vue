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
				<view>个人中心</view>
				<view style="width:100rpx;"></view>
			</view>
		</view>
		<view :style="'height:'+(statusBarHeight+navigationBarHeight)+'px'"></view>
		<view class="flex" style="padding:14rpx 36rpx 44rpx;align-items: center;" @click="personal">
			<image style="width: 116rpx;height: 116rpx;border-radius:50%;" :src="imgUrl+details.avatar"></image>
			<view style="margin-left:32rpx;">
				<view style="font-weight: bold;font-size: 36rpx;">{{details.username}}</view>
				<view style="font-size: 26rpx;color: #666666;margin-top:12rpx;">{{details.mobile}}</view>
			</view>
		</view>
		<image @click="myWallet" style="width:710rpx;height:100rpx;margin: 0 20rpx;" src="/static/images/icon_44wdqb.png"></image>
		<view style="margin: 20rpx;background-color: #FFF;border-radius: 20rpx;padding:24rpx 0 34rpx;">
			<view style="font-weight: bold;font-size: 30rpx;">服务中心</view>
			<view class="flex" style="flex-wrap: wrap;">
				<view style="width:25%;text-align:center;margin-top:36rpx;" @click="boda">
					<image style="width: 48rpx;height:48rpx;" src="/static/images/icon_14kf.png"></image>
					<view>我的客服</view>
				</view>
				<view style="width:25%;text-align:center;margin-top:36rpx;" @click="agreement('guanyu')">
					<image style="width: 48rpx;height:48rpx;" src="/static/images/icon_44gy.png"></image>
					<view>关于我们</view>
				</view>
				<view style="width:25%;text-align:center;margin-top:36rpx;" @click="Complaint">
					<image style="width: 48rpx;height:48rpx;" src="/static/images/icon_44fk.png"></image>
					<view>意见反馈</view>
				</view>
				<view style="width:25%;text-align:center;margin-top:36rpx;" @click="set">
					<image style="width: 48rpx;height:48rpx;" src="/static/images/icon_14sz.png"></image>
					<view>设置</view>
				</view>
				<view style="width:25%;text-align:center;margin-top:36rpx;" @click="DedicatedLine">
					<image style="width: 48rpx;height:48rpx;" src="/static/images/icon_44fb.png"></image>
					<view>专线入驻</view>
				</view>
			</view>
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
				scrollTop: 0,
				details:{}
			}
		},
		onLoad() {
			this.getSystemInfo()
			this.loads()
		},
		onShow() {
			// 检查是否登录
			if (!uni.getStorageSync('token')) {
				uni.showModal({
					title: '请登录',
					content: '您需要登录后才能查看个人信息',
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
			this.loadx()
		},
		onPageScroll(event) {
			const scrollTop = event.scrollTop // 获取当前页面滚动高度
			this.scrollTop = event.scrollTop
		},
		methods: {
			myWallet(){
				uni.navigateTo({
					url:'/pages/user/myWallet/myWallet'
				})
			},
			DedicatedLine(){
				uni.navigateTo({
					url:'/pages/user/DedicatedLine/DedicatedLine'
				})
			},
			personal(){
				uni.navigateTo({
					url:'/pages/user/personalData/personalData'
				})
			},
			boda(){
				this.$httpapi('Opinion/customer_mobile', 'POST', {}).then(res => {
					uni.makePhoneCall({
						phoneNumber:res.data
					});
				})
			},
			Mybill(){
				uni.navigateTo({
					url:'/pages/user/Mybill/Mybill'
				})
			},
			agreement(type){
				uni.navigateTo({
					url:'/pages/user/agreement/agreement?type='+type
				})
			},
			shiming(){
				uni.showToast({
					title: '待开发',
					duration: 1500,
					icon:'none'
				});
			},
			address(){
				uni.navigateTo({
					url:'/pages/user/address/address'
				})
			},
			Complaint(){
				uni.navigateTo({
					url:'/pages/user/Complaint/Complaint'
				})
			},
			set(){
				uni.navigateTo({
					url:'/pages/user/set/set'
				})
			},
			Myinvoice(){
				uni.navigateTo({
					url:'/pages/user/Myinvoice/Myinvoice'
				})
			},
			guanbi() {
				this.$refs.popup.close()
			},
			fahuo() {
				this.$refs.popup.open('bottom')
			},
			bindPickerChange(e) {
				this.index = e.detail.value
			},
			loads() {
				this.$httpapi('Placeorder/banner', 'GET', {}).then(res => {
					this.banner = res.data
				})
			},
			loadx(){
				this.$httpapi('Opinion/getuserinfo', 'GET', {}).then(res => {
					this.details = res.data
				})
			}
		}
	}
</script>

<style lang="scss">
	.banners {
		width: 710rpx;
		height: 240rpx;
		margin: 20rpx;
		border-radius: 20rpx;
	}
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
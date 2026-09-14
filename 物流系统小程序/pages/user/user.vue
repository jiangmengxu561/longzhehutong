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
				<view v-if="details.member_time" style="display:flex;align-items:center;justify-content:space-between;font-size:26rpx;color:#666666;margin-top:12rpx;" @click.stop="memberRecharge">
					<view style="display:flex;align-items:center;">
						<view style="width:12rpx;height:12rpx;border-radius:50%;background:#F29100;margin-right:12rpx;"></view>
						<view>会员到期：{{memberTimeText}}</view>
					</view>
					<view v-if="canRecharge" style="color:#4878FF;font-size:24rpx;margin-left:16rpx;">续费 ›</view>
				</view>
			</view>
		</view>
		<swiper class="banners" circular :autoplay="true" :interval="3000" :duration="1000">
			<swiper-item v-for="(item,index) in banner" :key="index">
				<image :src="imgUrl+item" mode="aspectFill" style="width:100%;height:100%;border-radius:20rpx;">
				</image>
			</swiper-item>
		</swiper>
		<view style="margin: 20rpx;background-color: #FFF;border-radius: 20rpx;padding:24rpx 0 34rpx;">
			<view style="font-weight: bold;font-size: 30rpx;">服务中心</view>
			<view class="flex" style="flex-wrap: wrap;">
				<view style="width:25%;text-align:center;margin-top:36rpx;" @click="address">
					<image style="width: 48rpx;height:48rpx;" src="/static/images/icon_14dz.png"></image>
					<view>地址薄</view>
				</view>
				<view style="width:25%;text-align:center;margin-top:36rpx;" @click="Complaint">
					<image style="width: 48rpx;height:48rpx;" src="/static/images/icon_14ts.png"></image>
					<view>投诉意见</view>
				</view>
				<view style="width:25%;text-align:center;margin-top:36rpx;">
					<image style="width: 48rpx;height:48rpx;" src="/static/images/icon_14yd.png"></image>
					<view>运单订单</view>
				</view>
				<view style="width:25%;text-align:center;margin-top:36rpx;" @click="boda">
					<image style="width: 48rpx;height:48rpx;" src="/static/images/icon_14kf.png"></image>
					<view>联系客服</view>
				</view>
				<view style="width:25%;text-align:center;margin-top:36rpx;" @click="Myinvoice">
					<image style="width: 48rpx;height:48rpx;" src="/static/images/icon_14fp.png"></image>
					<view>我的发票</view>
				</view>
				<view style="width:25%;text-align:center;margin-top:36rpx;" @click="Mywallet">
					<image style="width: 48rpx;height:48rpx;" src="/static/images/icon_14zd.png"></image>
					<view>钱包</view>
				</view>
				<view style="width:25%;text-align:center;margin-top:36rpx;" @click="Mybill">
					<image style="width: 48rpx;height:48rpx;" src="/static/images/icon_14zd.png"></image>
					<view>我的账单</view>
				</view>
				<view v-if="canSeeSubordinates" style="width:25%;text-align:center;margin-top:36rpx;" @click="mySubordinates">
					<image style="width: 48rpx;height:48rpx;" src="/static/images/icon_14rz.png"></image>
					<view>我的推广</view>
				</view>
				<view style="width:25%;text-align:center;margin-top:36rpx;" @click="shiming">
					<image style="width: 48rpx;height:48rpx;" src="/static/images/icon_14rz.png"></image>
					<view>实名认证</view>
				</view>
				<view v-if="canRecharge" style="width:25%;text-align:center;margin-top:36rpx;" @click="memberRecharge">
					<image style="width: 48rpx;height:48rpx;" src="/static/images/icon_14zd.png"></image>
					<view>会员充值</view>
				</view>
				<view v-if="canRecharge" style="width:25%;text-align:center;margin-top:36rpx;" @click="memberRechargeRecord">
					<image style="width: 48rpx;height:48rpx;" src="/static/images/icon_14yd.png"></image>
					<view>充值记录</view>
				</view>
			<!-- 	<view style="width:25%;text-align:center;margin-top:36rpx;" @click="agreement">
					<image style="width: 48rpx;height:48rpx;" src="/static/images/icon_14ht.png"></image>
					<view>合同</view>
				</view> -->
				<view style="width:25%;text-align:center;margin-top:36rpx;" @click="set">
					<image style="width: 48rpx;height:48rpx;" src="/static/images/icon_14sz.png"></image>
					<view>设置</view>
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
				details:{},
				walletSummary: {
					available: 0,
					pending: 0,
					history: 0
				}
			}
		},
		onLoad() {
			uni.setStorageSync('zaiOrder','')
			this.getSystemInfo()
			this.loads()
		},
		onShow() {
			uni.setStorageSync('zaiOrder','')
			this.loadx()
		},
		computed:{
			canSeeSubordinates(){
				if(!this.details || this.details.membertype === undefined || this.details.membertype === null){
					return false
				}
				return Number(this.details.membertype) !== 1
			},
			canRecharge(){
				if(!this.details || this.details.membertype === undefined || this.details.membertype === null){
					return false
				}
				const t = Number(this.details.membertype)
				return t === 1 || t === 2
			},
			memberTimeText(){
				const t = this.details && this.details.member_time
				if(!t) return ''
				const s = String(t)
				if(/^\d{10}$/.test(s)) return this.$utils.YMD(Number(s) * 1000)
				if(/^\d{13}$/.test(s)) return this.$utils.YMD(Number(s))
				return s
			}
		},
		onPageScroll(event) {
			const scrollTop = event.scrollTop // 获取当前页面滚动高度
			this.scrollTop = event.scrollTop
		},
		methods: {
			personal(){
				uni.navigateTo({
					url:'/pages/user/personalData/personalData'
				})
			},
			memberRecharge(){
				uni.navigateTo({
					url:'/pages/user/memberRecharge/memberRecharge'
				})
			},
			memberRechargeRecord(){
				uni.navigateTo({
					url:'/pages/user/memberRechargeRecord/memberRechargeRecord'
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
			agreement(){
				uni.navigateTo({
					url:'/pages/user/agreement/agreement?type=hetong'
				})
			},
			shiming(){
				uni.navigateTo({
					url:'/pages/user/shiming/shiming'
				})
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
			mySubordinates(){
				uni.navigateTo({
					url:'/pages/user/subordinate/subordinate'
				})
			},
			Myinvoice(){
				uni.navigateTo({
					url:'/pages/user/Myinvoice/Myinvoice'
				})
			},
			Mywallet(){
				uni.navigateTo({
					url:'/pages/user/wallet/wallet'
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
		width: 700rpx;
		height: 250rpx;
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

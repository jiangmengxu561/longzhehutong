<template>
	<view class="confirm">
		<view style="width: 750rpx;height: 272rpx;background: linear-gradient( 180deg, #D8EEFC 0%, #F7F8FA 100%);"></view>

		<view style="position: relative;z-index: 2;margin-top:-272rpx;">
			<view :style="'height:'+(statusBarHeight)+'px'"></view>
			<view class="flexJusp" :style="'height:'+navigationBarHeight+'px;line-height:'+navigationBarHeight+'px'"
				style="padding:0 20rpx;">
				<view style="width:100rpx;z-index: 99;" @click="backs">
					<image style="width:20rpx;height:32rpx;" src="/static/images/mv_tuichu.png"></image>
				</view>
				<view style="font-weight: bold;font-size: 36rpx;">提现</view>
				<view style="width: 100rpx;"></view>
			</view>

			<view style="background: #FFFFFF;border-radius: 20rpx;margin:20rpx;padding:32rpx 36rpx;">
				<view class="flex" style="margin-bottom:40rpx;">
					<view style="font-weight: bold;" >提现金额</view>
					<view style="color: #666666;margin-left:20rpx;">可提现金额:<text style="color: #EB0000;">{{money}}(元)</text> </view>
				</view>
				<view class="flex" style="align-items: center;">
					<view style="font-size:40rpx;font-weight: bold;">￥</view>
					<input @input="daozhang" v-model="price" type="digit"style="font-size:36rpx;font-weight: bold;width:260rpx;" placeholder="0.00" />
				</view>

				<view style="padding-top:24rpx;border-top:2rpx solid #F0F0F0;align-items: center;margin-top:28rpx;color: #666666;">平台服务费：<text style="color: #E50014;">5%</text> </view>
			</view>
			<view style="background: #FFFFFF;border-radius: 20rpx;margin:20rpx;padding:0 24rpx;">
				<view class="flexJusp" style="height:118rpx;align-items: center;border-bottom: 2rpx solid #F0F0F0;">
					<view style="margin-left:20rpx;">姓名</view>
					<input v-model="username" style="text-align: right;" placeholder="请输入姓名" />
				</view>
				<view class="flexJusp" style="height:118rpx;align-items: center;border-bottom: 2rpx solid #F0F0F0;">
					<view style="margin-left:20rpx;">银行名称</view>
					<input v-model="bankname" style="text-align: right;" placeholder="请输入银行名称" />
				</view>
				<view class="flexJusp" style="height:118rpx;align-items: center;border-bottom: 2rpx solid #F0F0F0;">
					<view style="margin-left:20rpx;">银行账号</view>
					<input v-model="bankcode" style="text-align: right;" placeholder="请输入卡号" />
				</view>
			</view>
			<view style="height:160rpx;"></view>
			<view class="buttons" @click="tixian">立即提现</view>
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
				price: 0,
				money: 0,
				bankname: '',
				bankcode: '',
				nameCode: '',
				username: '',
				phone: '',
				sysPoint: 0,
				type: 1,
				types: 1,
				shouxu:0
			};
		},
		onLoad(option) {
			this.getSystemInfo()
			this.$httpapi('Opinion/getuserinfo', 'GET', {}).then(res => {
				this.money = res.data.money
			})
		},
		methods: {
			daozhang(e) {
				this.moneys = ((parseFloat(this.sysPoint) * 1000) * (parseFloat(this.money) * 1000)) / 1000000
			},
			tixian() {
				if (parseInt(this.price)<0) return this.$utils.showtt('提现金额不能为负数')
				if (this.username=='') return this.$utils.showtt('姓名不能为空')
				if (this.bankname=='') return this.$utils.showtt('银行名称不能为空')
				if (this.bankcode=='') return this.$utils.showtt('银行卡号不能为空')
				this.$httpapi('Dricer/withdraw', 'POST', {
					branchName: this.branchName,
					money: this.price,
					name: this.username,
					// bankname: this.bankname,
					// bankcode: this.bankcode,
				}).then(res => {
					uni.showToast({
						title: res.msg,
						duration: 1500,
						icon: 'none'
					});
					if (res.code == 200) {
						setTimeout(() => {
							uni.navigateBack()
						}, 1500)
					}
				})
			},
			loads() {
				if(this.type==2){
					this.$httpapi('verify/getStoreByUserId', 'POST', {}).then(res => {
						this.price = res.data.money
					})
				}else{
					this.$httpapi('verify/getUserByUserId', 'POST', {}).then(res => {
						this.price = res.data.money
					})
				}
				
			},
			backs() {
				uni.navigateBack()
			},
		}
	}
</script>

<style lang="scss">
	page {
		background-color: #F7F6FA;
	}

	.confirm {
		.buttons {
			position: fixed;
			bottom: 68rpx;
			left: 28rpx;
			width: 694rpx;
			height: 92rpx;
			background: #1672FF;
			border-radius: 46rpx;
			font-size: 32rpx;
			color: #FFFFFF;
			line-height: 92rpx;
			text-align: center;
		}

	}
</style>
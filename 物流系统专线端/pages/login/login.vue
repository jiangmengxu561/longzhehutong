<template>
	<view class="logins">
		<image style="width:750rpx;height:782rpx;position: relative;z-index:1;" src="/static/images/icon_34bj.png"></image>

		<view style="margin:-782rpx 0 0;position: relative;z-index:2;">
			<!-- 导航栏 -->
			<view style="width: 750rpx;z-index: 9;position: fixed;top:0;left:0;color:#000;">
				<view :style="'height:'+statusBarHeight+'px'"></view>
				<view class="flexJusp" style="font-weight: bold;font-size: 36rpx;align-items: center;"
					:style="'height:'+navigationBarHeight+'px;line-height:'+navigationBarHeight+'px'">
					<view style="width:100rpx;text-align: center;" @click="goBack" v-if="showBack">
						<image style="width:20rpx;height:34rpx;" src="/static/images/mv_tuichu.png"></image>
					</view>
					<view style="width:100rpx;" v-else></view>
					<view>登录</view>
					<view style="width:100rpx;"></view>
				</view>
			</view>
			<view :style="'height:'+(statusBarHeight+navigationBarHeight)+'px'"></view>
				<view style="padding:134rpx 36rpx 80rpx;font-weight: bold;font-size: 44rpx;">登录</view>

				<view class="flexJusp" style="justify-content: center;align-items: center;">
					<image @click="check=!check" v-if="check" style="width:28rpx;height:28rpx;" src="/static/images/icon_1xuan1.png">
					</image>
					<image @click="check=!check" v-else style="width:28rpx;height:28rpx;" src="/static/images/icon_1xuan.png">
					</image>
					<view style="margin-left:12rpx;font-size:24rpx;">阅读并同意<text style="color: #147CFD;"
							@click="agreement('yonghu')">《用户协议》</text><text style="color: #147CFD;"
							@click="agreement('yinsi')">《隐私政策》</text> </view>
				</view>
				<button class="no-click-effect" type="default" open-type="getPhoneNumber"
					@getphonenumber="decryptPhoneNumber">
					<view class="buttons">手机号快捷登录</view>
				</button>

				<!-- 注册入口已取消，统一走授权登录自动注册 -->
				<!-- <view @click="register" style="text-align: center;font-size: 26rpx;color: #4878FF;margin-top:24rpx;">立即注册</view> -->
				
				<!-- <view style="color: #147CFD;text-align: center;padding-bottom:60rpx;" @click="register">新用户注册</view> -->
				<!-- <view v-if="type=='miniapp'">
					<button class="no-click-effect" type="default" open-type="getPhoneNumber"
						@getphonenumber="decryptPhoneNumber">
						<view class="buttons">一键极速登录</view>
					</button>
				</view> -->
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
				check: false,
				time: 60,
				isCode: true,
				mobile: '',
				captcha: '',
				type: 'app',
				showBack: false // 是否显示返回按钮
			};
		},
		onLoad() {
			// #ifdef MP-WEIXIN
			wx.showShareMenu({
				withShareTicket: true,
				menus: ["shareAppMessage", "shareTimeline"]
			})
			// #endif

			this.getSystemInfo()
			// #ifdef MP-WEIXIN
			this.type = 'miniapp'
			// #endif
			
			// 检查页面栈，如果有上一页则显示返回按钮
			const pages = getCurrentPages()
			if (pages.length > 1) {
				this.showBack = true
			}
		},
		onShow() {
			if(uni.getStorageSync('token')){
				uni.switchTab({
					url: '/pages/index/index'
				})
			}
		},
		methods: {
			goBack() {
				// 检查页面栈，如果有上一页则返回，否则跳转到首页
				const pages = getCurrentPages()
				if (pages.length > 1) {
					uni.navigateBack()
				} else {
					uni.switchTab({
						url: '/pages/index/index'
					})
				}
			},
			yulan(type) {
				var url = []
				if (type == 1) {
					url.push('/static/yingye.jpg')
					uni.previewImage({
						urls: url
					});
				} else {
					url.push('/static/renli.jpg')
					uni.previewImage({
						urls: url
					});
				}

			},
			decryptPhoneNumber(e) {
				if (!this.check) return this.$utils.showtt('请阅读并同意协议')
				const that = this
				uni.login({
					provider: 'weixin',
					success(loginRes) {
						that.$httpapi('Wxlogin/weChatRegister', 'POST', {
							code: loginRes.code,
							iv: e.detail.iv,
							encryptedData: e.detail.encryptedData,
							identity: 3
						}).then(res => {
							uni.showToast({
								title: res.msg,
								duration: 1500,
								icon: 'none'
							});

							if (res.code == 1) {
								if (res.data && res.data.userinfo && res.data.userinfo.token) {
									uni.setStorageSync('token', res.data.userinfo.token)
								}
								if (res.data && res.data.id) {
									uni.setStorageSync('uid', res.data.id)
								}
								setTimeout(() => {
									uni.switchTab({
										url: '/pages/index/index'
									})
								}, 1500)
							}
						})
					}
				})
			},
			agreement(type) {
				uni.navigateTo({
					url: '/pages/user/agreement/agreement?type=' + type
				})
			}
		}
	}
</script>

<style lang="scss">
	page{
		background-color: #FFFFFF;
	}
	.logins {
		.buttons {
			margin: 20rpx auto 28rpx;
			width: 633rpx;
			height: 92rpx;
			background: linear-gradient( 180deg, #4897FF 0%, #4878FF 100%);
			border-radius: 46rpx;
			font-size: 32rpx;
			color: #FFFFFF;
			line-height: 92rpx;
			text-align: center;
		}

		button::after {
			border: none;
		}

		.no-click-effect {
			margin: 20rpx 0 0;
			/* 去除点击时的背景色改变效果 */
			background-color: transparent;
			/* 去除边框的点击效果，如果有需要 */
			border: none;
			/* 去除阴影等效果，如果有需要 */
			box-shadow: none;
			/* 其他你需要覆盖的点击效果样式 */
		}
	}
</style>
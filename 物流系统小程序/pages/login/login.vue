<template>
	<view class="login">
		<view style="width: 750rpx;height: 272rpx;background: linear-gradient( 180deg, #D8EEFC 0%, #FFFFFF 100%);"></view>
		<view style="margin-top:-272rpx;"></view>
		<view :style="'height:'+(statusBarHeight)+'px'"></view>
		<view class="flexJusp" :style="'height:'+(navigationBarHeight)+'px;'"
			style="font-weight: bold;font-size: 36rpx;color: #333333;align-items: center;">
			<view style="width: 100rpx;text-align: center;" @click="fanhui">
				<image style="width:20rpx;height:34rpx;" src="/static/images/mv_tuichu.png"></image>
			</view>
			<view style="width: 100rpx;"></view>
		</view>

		<image class="logo" src="/static/logo.jpg"></image>
		<view style="text-align: center;font-size: 32rpx;color:#333;font-weight: 600;">龙喆互通</view>

		<!-- 推荐来源 -->
		<view class="channel-wrapper">
			<picker mode="selector" :range="channelList" range-key="name" :value="channelIndex"
				@change="onChannelChange">
				<view class="channel-picker-box">
					<view class="channel-label">推荐来源</view>
					<view class="channel-value">
						{{ channelIndex > 0 ? channelList[channelIndex].name : '请选择来源渠道' }}
					</view>
					<image class="arrow" src="/static/images/icon_xiala.png"></image>
				</view>
			</picker>
		</view>

		<!-- #ifdef MP-WEIXIN -->
		<button class="buttons no-click-effect" type="default" open-type="getPhoneNumber"
			@getphonenumber="decryptPhoneNumber">
			<view class="flex" style="align-items: center;justify-content: center;">
				<view style="font-weight: bold;font-size: 32rpx;color: #FFFFFF;">手机号快捷登录</view>
			</view>
		</button>
		<!-- #endif -->

		<!-- #ifdef APP-PLUS -->
		<view class="sms-login-wrapper">
			<view class="sms-input-box">
				<input class="sms-input" type="number" maxlength="11" v-model="phone" placeholder="请输入手机号"
					placeholder-style="color:#C0C0C0;" />
			</view>
			<view class="sms-input-box">
				<input class="sms-input" type="number" maxlength="6" v-model="smsCode" placeholder="请输入验证码"
					placeholder-style="color:#C0C0C0;" />
				<view class="sms-code-btn" :class="{disabled: countdown > 0}" @click="sendSmsCode">
					<text>{{ countdown > 0 ? countdown + 's' : '获取验证码' }}</text>
				</view>
			</view>
			<button class="buttons no-click-effect" type="default" @click="appSmsLogin">
				<view class="flex" style="align-items: center;justify-content: center;">
					<view style="font-weight: bold;font-size: 32rpx;color: #FFFFFF;">手机号验证码登录</view>
				</view>
			</button>
		</view>
		<!-- #endif -->

		<view class="flex agreement-box">
			<image @click="check=!check" v-if="check" style="width:28rpx;height:28rpx;"
				src="/static/images/icon_1xuan1.png"></image>
			<image @click="check=!check" v-else style="width:28rpx;height:28rpx;"
				src="/static/images/icon_1xuan.png"></image>
			<view>
				<text @click="check=!check">已阅读并同意</text>
				<text @click="agreement('yonghu')" style="color:#4878FF;">《用户协议》</text>
				<text @click="agreement('yinsi')" style="color:#4878FF;">《隐私协议》</text>
				<text @click="agreement('shiyong')" style="color:#4878FF;">《软件使用协议》</text>
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
				check: false,
				phone_encryptedData: '',
				phone_iv: '',
				session_key: '',
				phone: '',
				scene: '',
				pid: '',
				channelList: [],
				channelIndex: 0,
				channelId: '',
				// #ifdef APP-PLUS
				smsCode: '',
				countdown: 0,
				timer: null
				// #endif
			};
		},
		onLoad(option) {
			uni.setStorageSync('zaiOrder','')
			this.getSystemInfo()
			this.getChannelList()
			let sceneValue = ''
			if (option && option.scene) {
				sceneValue = String(option.scene)
			} else {
				const qrcodeScene = uni.getStorageSync('qrcode_scene')
				if (qrcodeScene) {
					sceneValue = String(qrcodeScene)
				}
			}
			
			const storedInvitation = uni.getStorageSync('qrcode_invitation')
			if(storedInvitation){
				this.pid = String(storedInvitation)
			} else if(sceneValue){
				this.pid = this.parseInvitationFromScene(sceneValue)
				if(this.pid){
					uni.setStorageSync('qrcode_invitation', this.pid)
				}
			}
		},
		onShow() {
			uni.setStorageSync('zaiOrder','')
			const storedInvitation = uni.getStorageSync('qrcode_invitation')
			if(storedInvitation && !this.pid){
				this.pid = String(storedInvitation)
			} else if(!storedInvitation && !this.pid){
				const qrcodeScene = uni.getStorageSync('qrcode_scene')
				if(qrcodeScene){
					this.pid = this.parseInvitationFromScene(qrcodeScene)
					if(this.pid){
						uni.setStorageSync('qrcode_invitation', this.pid)
					}
				}
			}
		},
		onUnload() {
			// #ifdef APP-PLUS
			if (this.timer) {
				clearInterval(this.timer)
				this.timer = null
			}
			// #endif
		},
		methods: {
			fanhui() {
				uni.switchTab({
					url: '/pages/index/index'
				})
			},
			decryptPhoneNumber(e) {
				if (!this.check) return this.$utils.showtt('请阅读并同意协议')
				var that = this
				const invitation = that.pid || uni.getStorageSync('qrcode_invitation') || ''
				uni.login({
					provider: 'weixin',
					success: function(loginRes) {
						that.$httpapi('Wxlogin/weChatRegister', 'POST', {
							code: loginRes.code,
							iv: e.detail.iv,
							encryptedData: e.detail.encryptedData,
							channel_id: that.channelId,
							invitation: invitation
						}).then(res => {
							uni.showToast({
								title: res.msg,
								duration: 1500,
								icon: 'none'
							});

							if (res.code == 1) {
								uni.setStorageSync('token', res.data.userinfo.token)
								uni.removeStorageSync('qrcode_scene')
								uni.removeStorageSync('qrcode_invitation')
								setTimeout(() => {
									uni.switchTab({
										url: '/pages/index/index'
									})
								}, 1500)
							}
						})
					},
				});
			},
			wxLogin(phone) {
				var that = this
				const invitation = that.pid || uni.getStorageSync('qrcode_invitation') || ''
				uni.login({
					provider: 'weixin',
					success: function(loginRes) {
						that.$httpapi('user/wxLogin', 'POST', {
							code: loginRes.code,
							channel_id: that.channelId,
							invitation: invitation
						}).then(res => {
							uni.showToast({
								title: res.msg,
								duration: 1500,
								icon: 'none'
							});
							if (res.code == 1) {
								uni.setStorageSync('token', res.data.userinfo.token)
								uni.removeStorageSync('qrcode_scene')
								uni.removeStorageSync('qrcode_invitation')
								setTimeout(() => {
									uni.switchTab({
										url: '/pages/index/index'
									})
								}, 1500)
							}
						})
					},
				});
			},
			agreement(type) {
				uni.navigateTo({
					url: '/pages/user/agreement/agreement?type=' + type
				})
			},
			getuserinfos(e) {
				console.log(e, '-----------登录')
			},
			logins() {
				var that = this
				if (!this.check) return this.$utils.showtt('请阅读并同意协议')
				const invitation = that.pid || uni.getStorageSync('qrcode_invitation') || ''
				uni.login({
					provider: 'weixin',
					success: function(loginRes) {
						that.$httpapi('user/getPhoneWx', 'POST', {
							code: loginRes.code,
							invitation: invitation,
							channel_id: that.channelId
						}).then(res => {
							uni.setStorageSync('uid', res.data.id)
							uni.setStorageSync('token', res.data.token)
							uni.showToast({
								title: res.msg,
								duration: 1500,
								icon: 'none'
							});
							if (res.code == 1) {
								uni.removeStorageSync('qrcode_scene')
								uni.removeStorageSync('qrcode_invitation')
								setTimeout(() => {
									uni.navigateBack()
								}, 1500)
							}
						})
					},
					fail: function(err) {}
				});
			},
			getChannelList() {
				this.$httpapi('index/channel', 'GET', {}).then(res => {
					if (res.code == 1 && res.data && res.data.length) {
						// 插入一个默认选项：请选择
						this.channelList = [{ id: '', name: '请选择来源渠道' }, ...res.data]
						this.channelIndex = 0
						this.channelId = ''
					}
				})
			},
			onChannelChange(e) {
				this.channelIndex = e.detail.value
				if (this.channelList && this.channelList.length) {
					this.channelId = String(this.channelList[this.channelIndex].id || '')
				}
			},
			// #ifdef APP-PLUS
			sendSmsCode() {
				if (this.countdown > 0) return
				if (!this.phone || !/^1\d{10}$/.test(this.phone)) {
					return this.$utils.showtt('请输入正确的手机号')
				}
				this.$httpapi('sms/send', 'POST', {
					mobile: this.phone
				}).then(res => {
					uni.showToast({
						title: res.msg,
						duration: 1500,
						icon: 'none'
					})
					if (res.code == 1) {
						this.countdown = 60
						this.timer = setInterval(() => {
							this.countdown--
							if (this.countdown <= 0) {
								clearInterval(this.timer)
								this.timer = null
							}
						}, 1000)
					}
				})
			},
			appSmsLogin() {
				if (!this.check) return this.$utils.showtt('请阅读并同意协议')
				if (!this.phone || !/^1\d{10}$/.test(this.phone)) {
					return this.$utils.showtt('请输入正确的手机号')
				}
				if (!this.smsCode) {
					return this.$utils.showtt('请输入验证码')
				}
				var that = this
				const invitation = that.pid || uni.getStorageSync('qrcode_invitation') || ''
				that.$httpapi('User/mobilelogin', 'POST', {
					mobile: that.phone,
					captcha: that.smsCode,
					identity: 1
				}).then(res => {
					uni.showToast({
						title: res.msg,
						duration: 1500,
						icon: 'none'
					})
					if (res.code == 1) {
						if (res.data && res.data.userinfo && res.data.userinfo.token) {
							uni.setStorageSync('token', res.data.userinfo.token)
						}
						uni.removeStorageSync('qrcode_scene')
						uni.removeStorageSync('qrcode_invitation')
						setTimeout(() => {
							uni.switchTab({
								url: '/pages/index/index'
							})
						}, 1500)
					}
				})
			},
			// #endif
			register(openid) {
				var upId = null
				if (uni.getStorageSync('upId')) {
					upId = uni.getStorageSync('upId')
				}
				this.$httpapi('/api/wxRegister', 'POST', {
					openId: openid,
					upId: upId
				}).then(res => {
					uni.setStorageSync('id', res.data.id)
					uni.setStorageSync('openId', res.openid)
					uni.showToast({
						title: res.msg,
						duration: 1500,
						icon: 'none'
					});
					if (res.code == 200) {
						uni.removeStorageSync('qrcode_scene')
						uni.removeStorageSync('qrcode_invitation')
						setTimeout(() => {
							uni.navigateBack()
						}, 1500)
					}
				})
			},
			parseInvitationFromScene(scene) {
				if(!scene) return ''
				try {
					let url = String(scene)
					try { url = decodeURIComponent(url) } catch(e) {}
					
					if(url.indexOf('invitation') !== -1){
						const match = url.match(/[?&]invitation=([^&]*)/)
						if(match && match[1]) return decodeURIComponent(match[1])
					}
					if(/^[a-zA-Z0-9]+$/.test(scene)) return scene
					return ''
				} catch(e) {
					return String(scene || '')
				}
			}
		}
	}
</script>

<style lang="scss">
	page {
		background-color: #FFF;
	}

	.login {
		color: #333;

		.logo {
			border-radius: 50%;
			display: block;
			width: 184rpx;
			height: 184rpx;
			margin: 110rpx auto 20rpx;
		}

		.buttons {
			text-align: center;
			width: 610rpx;
			height: 100rpx;
			background: linear-gradient(180deg, #4897FF 0%, #4878FF 100%);
			border-radius: 50rpx;
			color: #FFF;
			font-weight: bold;
			font-size: 32rpx;
			line-height: 100rpx;
		}
	}

	/* 推荐来源样式 */
	.channel-wrapper {
		width: 610rpx;
		margin: 80rpx auto 40rpx;
	}

	.channel-picker-box {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 24rpx 30rpx;
		border: 2rpx solid #E5E5E5;
		border-radius: 12rpx;
		background-color: #fff;
	}

	.channel-label {
		font-size: 28rpx;
		color: #666;
		font-weight: 500;
	}

	.channel-value {
		flex: 1;
		text-align: right;
		font-size: 28rpx;
		color: #333;
		margin-right: 16rpx;
	}

	.arrow {
		width: 24rpx;
		height: 24rpx;
		flex-shrink: 0;
	}

	.channel-picker-box:active {
		background-color: #f7f7f7;
		border-color: #4878FF;
	}

	button::after {
		border: none;
	}

	/* #ifdef APP-PLUS */
	.sms-login-wrapper {
		width: 610rpx;
		margin: 60rpx auto 0;
	}

	.sms-input-box {
		display: flex;
		align-items: center;
		border: 2rpx solid #E5E5E5;
		border-radius: 12rpx;
		padding: 0 24rpx;
		margin-bottom: 24rpx;
		height: 96rpx;
		background-color: #fff;
	}

	.sms-input {
		flex: 1;
		font-size: 28rpx;
		color: #333;
		height: 96rpx;
	}

	.sms-code-btn {
		flex-shrink: 0;
		font-size: 26rpx;
		color: #4878FF;
		padding-left: 20rpx;
		border-left: 2rpx solid #E5E5E5;
		line-height: 40rpx;
	}

	.sms-code-btn.disabled {
		color: #C0C0C0;
	}

	.sms-login-wrapper .buttons {
		margin-top: 20rpx;
	}
	/* #endif */

	.no-click-effect {
		padding: 0;
		margin: 30rpx auto 24rpx;
		background-color: transparent;
		border: none;
		box-shadow: none;
	}

	.agreement-box {
		font-size: 26rpx;
		align-items: center;
		justify-content: center;
		color: #C0C0C0;
		display: flex;
		gap: 10rpx;
	}
</style>
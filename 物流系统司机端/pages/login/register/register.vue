<template>
	<view class="logins">
		<view :style="'height:'+(statusBarHeight+navigationBarHeight)+'px'"></view>
		<view class="flexJusp" style="align-items: center;"
			:style="'height:'+navigationBarHeight+'px;line-height:'+navigationBarHeight+'px'">
			<view style="width:100rpx;text-align: center;" @click="tuichu">
				<image style="width:20rpx;height:34rpx;" src="/static/images/mv_tuichu.png"></image>
			</view>
			<view></view>
		</view>
		<view style="padding:24rpx 56rpx 112rpx;font-weight: bold;font-size: 56rpx;color: #4878FF;">立即注册</view>

		<view class="flexJusp" style="justify-content: center;align-items: center;margin:0 40rpx 40rpx;">
			<image @click="check=!check" v-if="check" style="width:28rpx;height:28rpx;" src="/static/images/icon_1xuan1.png">
			</image>
			<image @click="check=!check" v-else style="width:28rpx;height:28rpx;" src="/static/images/icon_1xuan.png">
			</image>
			<view style="margin-left:12rpx;font-size:24rpx;">阅读并同意
				<text style="color: #147CFD;" @click="agreement('yonghu')">《用户协议》</text>
				<text style="color: #147CFD;" @click="agreement('yinsi')">《隐私政策》</text>
			</view>
		</view>

		<button class="no-click-effect" type="default" open-type="getPhoneNumber"
			@getphonenumber="decryptPhoneNumber">
			<view class="buttons">微信授权手机号注册</view>
		</button>

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
				time: 60,
				isCode: true,
				mobile: '',
				code: '',
				type: 'app',
				cardid:'',
				carnumber:'',
				city:'',
				license:'',
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
		},
		methods: {
			upImage(){
				var that = this
				uni.chooseImage({
					count: 1, //默认9
					sizeType: ['original', 'compressed'], //可以指定是原图还是压缩图，默认二者都有
					sourceType: ['album', 'camera'], //从相册选择
					success: function(res) {
						that.$upShop('common/upload', res.tempFilePaths[0]).then(rex => {
							let arr = JSON.parse(rex.data)
							that.license = arr.data.url
						}).catch((e) => {});
					}
				})
			},
			tuichu() {
				uni.navigateBack()
			},
			register() {
				uni.navigateTo({
					url: '/pages/login/register/register'
				})
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
						that.$httpapi('User/getPhoneLogin', 'POST', {
							code: loginRes.code,
							iv: e.detail.iv,
							encryptedData: e.detail.encryptedData,
							identity: 2
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
			},
			// 短信注册已移除，仅保留微信授权手机号注册
			register() {
				uni.navigateTo({
					url: '/pages/login/register/register'
				})
			}
		}
	}
</script>

<style lang="scss">
	page {
		background-color: #FFFFFF;
	}

	.logins {
		.buttons {
			margin: 20rpx auto 28rpx;
			width: 633rpx;
			height: 92rpx;
			background: linear-gradient(180deg, #4897FF 0%, #4878FF 100%);
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
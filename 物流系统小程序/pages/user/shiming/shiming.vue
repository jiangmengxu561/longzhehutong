<template>
	<view class="shiming">
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
				<view>实名认证</view>
				<view style="width:100rpx;"></view>
			</view>
		</view>
		<view :style="'height:'+(statusBarHeight+navigationBarHeight)+'px'"></view>

		<!-- 已认证：脱敏展示 -->
		<block v-if="isAuth">
			<view class="status-bar">
				<view class="status-tag">已认证</view>
				<view class="status-time" v-if="authTime">认证时间：{{authTime}}</view>
			</view>
			<view class="formBox">
				<view class="flexJusp form-item">
					<view class="label">姓名</view>
					<view class="value">{{name}}</view>
				</view>
				<view class="flexJusp form-item">
					<view class="label">电话</view>
					<view class="value">{{phone_no}}</view>
				</view>
				<view class="flexJusp form-item">
					<view class="label">身份证号</view>
					<view class="value">{{id_no}}</view>
				</view>
				<view class="flexJusp form-item">
					<view class="label">银行卡号</view>
					<view class="value">{{card_no}}</view>
				</view>
				<view class="flexJusp form-item" style="border-bottom: none;">
					<view class="label">银行名</view>
					<view class="value">{{bank_name || '—'}}</view>
				</view>
			</view>
		</block>

		<!-- 未认证：填写表单 -->
		<block v-else-if="loaded">
			<view class="tip">请填写本人真实信息，用于银行卡四要素核验</view>
			<view class="formBox">
				<view class="flexJusp form-item">
					<view class="label"><text class="star">*</text>姓名</view>
					<input v-model="name" style="flex:1;text-align: right;font-size:28rpx;" placeholder="请输入真实姓名" />
				</view>
				<view class="flexJusp form-item">
					<view class="label"><text class="star">*</text>电话</view>
					<input maxlength="11" v-model="phone_no" type="number" style="flex:1;text-align: right;font-size:28rpx;"
						placeholder="请输入银行预留手机号" />
				</view>
				<view class="flexJusp form-item">
					<view class="label"><text class="star">*</text>身份证号</view>
					<input maxlength="18" v-model="id_no" style="flex:1;text-align: right;font-size:28rpx;"
						placeholder="请输入身份证号" />
				</view>
				<view class="flexJusp form-item">
					<view class="label"><text class="star">*</text>银行卡号</view>
					<input maxlength="30" v-model="card_no" type="number" style="flex:1;text-align: right;font-size:28rpx;"
						placeholder="请输入银行卡号" />
				</view>
				<view class="flexJusp form-item" style="border-bottom: none;">
					<view class="label"><text class="star">*</text>银行名</view>
					<input v-model="bank_name" style="flex:1;text-align: right;font-size:28rpx;" placeholder="请输入开户银行名称" />
				</view>
			</view>

			<view class="flex agreement-box">
				<image @click="check=!check" v-if="check" style="width:28rpx;height:28rpx;"
					src="/static/images/icon_1xuan1.png"></image>
				<image @click="check=!check" v-else style="width:28rpx;height:28rpx;"
					src="/static/images/icon_1xuan.png"></image>
				<view>
					<text @click="check=!check">已阅读并同意</text>
					<text @click="agreement" style="color:#4878FF;">《实名认证服务协议》</text>
				</view>
			</view>

			<view class="buttons" :style="submitting?'opacity:0.6;':''" @click="submit">提交认证</view>
		</block>
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
				scrollTop: 0,
				loaded: false,
				isAuth: false,
				authTime: '',
				name: '',
				phone_no: '',
				id_no: '',
				card_no: '',
				bank_name: '',
				check: false,
				submitting: false
			};
		},
		onLoad() {
			this.getSystemInfo()
			this.loadAuthInfo()
		},
		onPageScroll(event) {
			this.scrollTop = event.scrollTop
		},
		methods: {
			tuichu() {
				uni.navigateBack()
			},
			agreement() {
				uni.navigateTo({
					url: '/pages/user/agreement/agreement?type=shiming'
				})
			},
			loadAuthInfo() {
				this.$httpapi('Index/bankcardAuthInfo', 'POST', {}).then(res => {
					const data = (res && res.data) || {}
					if (res.code == 1 && Number(data.is_auth) === 1) {
						this.isAuth = true
						this.name = data.name || ''
						this.phone_no = data.mobile || ''
						this.id_no = data.id_card || ''
						this.card_no = data.account_no || ''
						this.bank_name = data.bank || ''
						this.authTime = data.auth_time || ''
						this.loaded = true
						return
					}
					this.isAuth = false
					this.loaded = true
					this.$httpapi('Opinion/getuserinfo', 'GET', {}).then(ures => {
						if (ures.data && ures.data.mobile) {
							this.phone_no = String(ures.data.mobile)
						}
					})
				}).catch(() => {
					this.loaded = true
				})
			},
			submit() {
				if (this.submitting || this.isAuth) return
				const name = String(this.name || '').trim()
				const phone_no = String(this.phone_no || '').trim()
				const id_no = String(this.id_no || '').trim()
				const card_no = String(this.card_no || '').trim()
				const bank_name = String(this.bank_name || '').trim()

				if (!name) return this.$utils.showtt('请输入姓名')
				if (!phone_no) return this.$utils.showtt('请输入预留手机号')
				if (!/^1\d{10}$/.test(phone_no)) return this.$utils.showtt('请输入正确的手机号')
				if (!id_no) return this.$utils.showtt('请输入身份证号')
				if (!/(^\d{15}$)|(^\d{17}[\dXx]$)/.test(id_no)) return this.$utils.showtt('请输入正确的身份证号')
				if (!card_no) return this.$utils.showtt('请输入银行卡号')
				if (!/^\d{10,30}$/.test(card_no)) return this.$utils.showtt('请输入正确的银行卡号')
				if (!bank_name) return this.$utils.showtt('请输入银行名')
				if (!this.check) return this.$utils.showtt('请阅读并同意实名认证服务协议')

				this.submitting = true
				this.$httpapi('Index/bankcard4', 'POST', {
					name: name,
					id_no: id_no,
					card_no: card_no,
					phone_no: phone_no,
					bank_name: bank_name
				}).then(res => {
					uni.showToast({
						title: res.msg || (res.code == 1 ? '认证通过' : '认证失败'),
						duration: 2000,
						icon: 'none'
					})
					if (res.code == 1) {
						setTimeout(() => {
							this.loadAuthInfo()
						}, 800)
					}
				}).finally(() => {
					this.submitting = false
				})
			}
		}
	}
</script>

<style lang="scss">
	.shiming {
		padding: 0 0 200rpx;
		color: #000;

		.status-bar {
			margin: 28rpx 36rpx 0;
			display: flex;
			align-items: center;
		}

		.status-tag {
			padding: 6rpx 20rpx;
			background: rgba(72, 120, 255, 0.12);
			color: #4878FF;
			font-size: 24rpx;
			border-radius: 8rpx;
			font-weight: bold;
		}

		.status-time {
			margin-left: 20rpx;
			font-size: 24rpx;
			color: #999999;
		}

		.tip {
			margin: 24rpx 36rpx 0;
			font-size: 24rpx;
			color: #999999;
			line-height: 36rpx;
		}

		.formBox {
			margin: 24rpx 24rpx 0;
			background: #FFFFFF;
			border-radius: 20rpx;
			padding: 0 24rpx;
		}

		.form-item {
			line-height: 114rpx;
			border-bottom: 2rpx solid #F0F0F0;
			align-items: center;
		}

		.label {
			width: 180rpx;
			font-size: 30rpx;
			color: #333333;
			flex-shrink: 0;
		}

		.value {
			flex: 1;
			text-align: right;
			font-size: 28rpx;
			color: #333333;
			word-break: break-all;
		}

		.star {
			color: #CF2D22;
			margin-right: 4rpx;
		}

		.agreement-box {
			margin: 32rpx 36rpx 0;
			align-items: center;
			font-size: 24rpx;
			color: #666666;

			image {
				margin-right: 12rpx;
				flex-shrink: 0;
			}
		}

		.buttons {
			position: fixed;
			left: 30rpx;
			bottom: 88rpx;
			width: 690rpx;
			height: 88rpx;
			background: linear-gradient(180deg, #4897FF 0%, #4878FF 100%);
			border-radius: 50rpx;
			font-weight: bold;
			font-size: 30rpx;
			color: #FFFFFF;
			line-height: 88rpx;
			text-align: center;
		}
	}
</style>

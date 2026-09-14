<template>
	<view class="page">
		<!-- 顶部渐变背景 -->
		<view class="top-bg"></view>

		<!-- 自定义导航栏 -->
		<view class="navbar" :class="{ solid: scrollTop > 40 }" :style="'height:' + navHeight + 'px;padding-top:' + statusBarHeight + 'px'">
			<view class="nav-inner">
				<view class="nav-back" @click="tuichu">
					<view class="chevron"></view>
				</view>
				<view class="nav-title">会员充值</view>
				<view class="nav-right" @click="goRecord">充值记录</view>
			</view>
		</view>
		<view :style="'height:' + navHeight + 'px'"></view>

		<!-- 会员状态卡 -->
		<view class="hero card">
			<view class="hero-left">
				<view class="vip-badge">
					<text class="vip-badge-txt">VIP</text>
				</view>
				<view class="hero-title">会员充值</view>
			</view>
			<view class="hero-right">
				<view class="hero-right-label">{{ memberText ? '当前会员到期' : '当前会员状态' }}</view>
				<view v-if="memberText" class="hero-right-val">{{ memberText }}</view>
				<view v-else class="hero-right-empty">尚未开通会员</view>
			</view>
		</view>

		<!-- 套餐选择 -->
		<view class="section card">
			<view class="section-title">
				<text class="section-bar"></text>选择套餐
			</view>
			<view v-if="!packageList.length" class="empty">暂无可用套餐</view>
			<view v-else class="pkg-grid">
				<view
					v-for="(item, index) in packageList"
					:key="item.id"
					class="pkg"
					:class="{ active: selected === item.id }"
					@click="selected = item.id"
				>
					<view v-if="item.hot" class="pkg-tag">超值推荐</view>
					<view class="pkg-head">
						<view class="pkg-check" :class="{ on: selected === item.id }">
							<text v-if="selected === item.id" class="pkg-check-mark">✓</text>
						</view>
						<view class="pkg-name">{{ item.member_name }}</view>
					</view>
					<view class="pkg-duration">{{ item.duration_text }}</view>
					<view class="pkg-price">
						<text class="pkg-price-symbol">¥</text>
						<text class="pkg-price-num">{{ item.price }}</text>
					</view>
				</view>
			</view>
		</view>

		<!-- 温馨提示 -->
		<view class="tips">温馨提示：充值成功后立即升级为兼职员工，会员时长从当前到期时间起顺延；已到期则从当前时间起算。</view>

		<view class="bottom-space"></view>

		<!-- 底部支付栏 -->
		<view class="paybar" v-if="packageList.length">
			<view class="paybar-left">
				<view class="paybar-price">
					<text class="paybar-symbol">¥</text>
					<text class="paybar-num">{{ selectedPrice }}</text>
				</view>
				<view v-if="selectedPackage" class="paybar-name">{{ selectedPackageName }} · {{ selectedDurationText }}</view>
			</view>
			<view class="paybar-btn" :class="{ disabled: paying }" @click="zhifu">立即支付</view>
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
				scrollTop: 0,
				packageList: [],
				selected: null,
				paying: false,
				userInfo: {}
			}
		},
		onLoad() {
			this.getSystemInfo()
			this.loadUser()
			this.loadData()
		},
		onPageScroll(event) {
			this.scrollTop = event.scrollTop
		},
		computed: {
			selectedPackage() {
				return this.packageList.find(p => p.id === this.selected) || null
			},
			selectedPrice() {
				return this.selectedPackage ? this.selectedPackage.price : 0
			},
			selectedPackageName() {
				return this.selectedPackage ? this.selectedPackage.member_name : ''
			},
			selectedDurationText() {
				return this.selectedPackage ? this.selectedPackage.duration_text : ''
			},
			memberText() {
				const t = this.userInfo && this.userInfo.member_time
				if (!t) return ''
				const s = String(t)
				if (/^\d{10}$/.test(s)) return this.$utils.YMD(Number(s) * 1000)
				if (/^\d{13}$/.test(s)) return this.$utils.YMD(Number(s))
				return s
			}
		},
		methods: {
			tuichu() {
				uni.navigateBack()
			},
			goRecord() {
				uni.navigateTo({
					url: '/pages/user/memberRechargeRecord/memberRechargeRecord'
				})
			},
			loadUser() {
				this.$httpapi('Opinion/getuserinfo', 'GET', {}).then(res => {
					if (res.code == 1) this.userInfo = res.data
				}).catch(() => {})
			},
			loadData() {
				this.$httpapi('Memberrecharge/rules', 'GET', {}).then(res => {
					if (res.code == 1) {
						const list = res.data || []
						// 标记超值推荐：时长最长者
						let maxIdx = 0
						let maxVal = -1
						list.forEach((it, i) => {
							const v = Number(it.duration) || 0
							if (v > maxVal) {
								maxVal = v
								maxIdx = i
							}
						})
						list.forEach((it, i) => {
							it.hot = i === maxIdx
						})
						this.packageList = list
						if (list.length > 0 && !this.selected) {
							this.selected = list[0].id
						}
					} else if (res.msg) {
						uni.showToast({ title: res.msg, icon: 'none' })
					}
				})
			},
			zhifu() {
				if (this.paying) return
				if (!this.selected) {
					uni.showToast({ title: '请选择套餐', icon: 'none' })
					return
				}
				this.paying = true
				this.$httpapi('Memberrecharge/create', 'POST', {
					package_id: this.selected
				}).then(res => {
					this.paying = false
					if (res.code !== 1) {
						uni.showToast({ title: res.msg || '下单失败', icon: 'none' })
						return
					}
					const pay = res.data.pay
					uni.requestPayment({
						provider: 'wxpay',
						timeStamp: pay.timeStamp,
						nonceStr: pay.nonceStr,
						package: pay.package,
						signType: pay.signType,
						paySign: pay.paySign,
						success: () => {
							uni.showToast({ title: '支付成功', duration: 1500, icon: 'none' })
							setTimeout(() => {
								this.loadData()
								this.loadUser()
							}, 1500)
						},
						fail: () => {}
					})
				})
			}
		}
	}
</script>

<style lang="scss" scoped>
	.page {
		position: relative;
		min-height: 100vh;
		background: #f5f7fa;
	}

	/* 顶部渐变 */
	.top-bg {
		position: absolute;
		top: 0;
		left: 0;
		width: 100%;
		height: 460rpx;
		background: linear-gradient(160deg, #6eb1ff 0%, #4878ff 100%);
		border-bottom-left-radius: 40rpx;
		border-bottom-right-radius: 40rpx;
		z-index: 0;
	}

	/* 自定义导航栏 */
	.navbar {
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		z-index: 99;
		box-sizing: border-box;
		transition: all 0.2s;
	}

	.nav-inner {
		height: 44px;
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 0 24rpx;
	}

	.nav-back {
		width: 100rpx;
		height: 100%;
		display: flex;
		align-items: center;
	}

	.chevron {
		width: 20rpx;
		height: 20rpx;
		border-top: 4rpx solid #fff;
		border-left: 4rpx solid #fff;
		transform: rotate(-45deg);
		border-radius: 4rpx;
	}

	.nav-title {
		font-size: 34rpx;
		font-weight: bold;
		color: #fff;
	}

	.nav-right {
		width: 100rpx;
		text-align: right;
		font-size: 26rpx;
		color: #fff;
	}

	.navbar.solid {
		background: #fff;
		box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.06);
	}

	.navbar.solid .nav-title {
		color: #1a1a1a;
	}

	.navbar.solid .nav-right {
		color: #4878ff;
	}

	.navbar.solid .nav-back .chevron {
		border-top-color: #1a1a1a;
		border-left-color: #1a1a1a;
	}

	.card {
		position: relative;
		z-index: 1;
		margin: 20rpx 24rpx;
		border-radius: 24rpx;
		background: #fff;
		box-shadow: 0 8rpx 24rpx rgba(31, 51, 118, 0.06);
	}

	/* 会员状态卡 */
	.hero {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 36rpx 32rpx;
		margin-top: 4rpx;
	}

	.hero-left {
		display: flex;
		flex-direction: column;
	}

	.vip-badge {
		width: 84rpx;
		height: 44rpx;
		border-radius: 12rpx;
		background: linear-gradient(90deg, #f7c255 0%, #f29100 100%);
		display: flex;
		align-items: center;
		justify-content: center;
		margin-bottom: 18rpx;
	}

	.vip-badge-txt {
		color: #fff;
		font-size: 24rpx;
		font-weight: bold;
		letter-spacing: 2rpx;
	}

	.hero-title {
		font-size: 36rpx;
		font-weight: bold;
		color: #1a1a1a;
	}

	.hero-sub {
		font-size: 24rpx;
		color: #999;
		margin-top: 8rpx;
	}

	.hero-right {
		text-align: right;
		padding-left: 20rpx;
	}

	.hero-right-label {
		font-size: 22rpx;
		color: #999;
	}

	.hero-right-val {
		font-size: 28rpx;
		font-weight: bold;
		color: #4878ff;
		margin-top: 8rpx;
	}

	.hero-right-empty {
		font-size: 26rpx;
		color: #bbb;
		margin-top: 8rpx;
	}

	/* 套餐区 */
	.section {
		padding: 32rpx 28rpx 40rpx;
	}

	.section-title {
		display: flex;
		align-items: center;
		font-size: 32rpx;
		font-weight: bold;
		color: #1a1a1a;
		margin-bottom: 26rpx;
	}

	.section-bar {
		width: 8rpx;
		height: 32rpx;
		border-radius: 8rpx;
		background: linear-gradient(180deg, #4897ff 0%, #4878ff 100%);
		margin-right: 16rpx;
	}

	.empty {
		padding: 60rpx 0;
		text-align: center;
		color: #999;
		font-size: 26rpx;
	}

	.pkg-grid {
		display: flex;
		flex-wrap: wrap;
		justify-content: space-between;
	}

	.pkg {
		position: relative;
		width: 48%;
		box-sizing: border-box;
		border: 2rpx solid #eef1f6;
		border-radius: 20rpx;
		padding: 30rpx 26rpx;
		margin-bottom: 20rpx;
		background: #fff;
		transition: all 0.15s;
		overflow: hidden;
	}

	.pkg.active {
		border-color: #4878ff;
		background: #f6f9ff;
		box-shadow: 0 6rpx 20rpx rgba(72, 120, 255, 0.12);
	}

	.pkg-tag {
		position: absolute;
		top: 0;
		right: 0;
		padding: 6rpx 14rpx;
		font-size: 20rpx;
		color: #fff;
		background: linear-gradient(90deg, #f7c255 0%, #f29100 100%);
		border-bottom-left-radius: 16rpx;
	}

	.pkg-name {
		flex: 1;
		min-width: 0;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
		font-size: 32rpx;
		font-weight: bold;
		color: #1a1a1a;
	}

	.pkg-head {
		display: flex;
		align-items: center;
	}

	.pkg-duration {
		font-size: 26rpx;
		color: #666;
		margin-top: 10rpx;
	}

	.pkg-price {
		margin-top: 24rpx;
		color: #f29100;
		display: flex;
		align-items: baseline;
	}

	.pkg-price-symbol {
		font-size: 26rpx;
		font-weight: bold;
	}

	.pkg-price-num {
		font-size: 44rpx;
		font-weight: bold;
		margin-left: 4rpx;
	}

	.pkg-check {
		flex-shrink: 0;
		margin-right: 12rpx;
		width: 36rpx;
		height: 36rpx;
		border-radius: 50%;
		border: 2rpx solid #d5dbe6;
		background: #fff;
		display: flex;
		align-items: center;
		justify-content: center;
	}

	.pkg-check.on {
		border-color: #4878ff;
		background: #4878ff;
	}

	.pkg-check-mark {
		color: #fff;
		font-size: 24rpx;
		font-weight: bold;
		line-height: 1;
	}

	/* 温馨提示 */
	.tips {
		margin: 8rpx 32rpx 0;
		font-size: 22rpx;
		color: #aaa;
		line-height: 1.6;
	}

	.bottom-space {
		height: 200rpx;
	}

	/* 底部支付栏 */
	.paybar {
		position: fixed;
		left: 0;
		right: 0;
		bottom: 0;
		z-index: 90;
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 20rpx 32rpx;
		padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
		background: #fff;
		box-shadow: 0 -6rpx 20rpx rgba(0, 0, 0, 0.06);
	}

	.paybar-left {
		display: flex;
		flex-direction: column;
		min-width: 0;
	}

	.paybar-price {
		color: #f29100;
		display: flex;
		align-items: baseline;
	}

	.paybar-symbol {
		font-size: 30rpx;
		font-weight: bold;
	}

	.paybar-num {
		font-size: 48rpx;
		font-weight: bold;
		margin-left: 4rpx;
	}

	.paybar-name {
		font-size: 24rpx;
		color: #999;
		margin-top: 4rpx;
		overflow: hidden;
		white-space: nowrap;
		text-overflow: ellipsis;
		max-width: 400rpx;
	}

	.paybar-btn {
		flex-shrink: 0;
		width: 240rpx;
		height: 84rpx;
		border-radius: 42rpx;
		background: linear-gradient(90deg, #4897ff 0%, #4878ff 100%);
		color: #fff;
		font-size: 32rpx;
		font-weight: bold;
		display: flex;
		align-items: center;
		justify-content: center;
		box-shadow: 0 8rpx 16rpx rgba(72, 120, 255, 0.3);
	}

	.paybar-btn.disabled {
		opacity: 0.6;
	}
</style>

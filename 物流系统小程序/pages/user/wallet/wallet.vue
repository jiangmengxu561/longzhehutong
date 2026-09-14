<template>
	<view class="content">
		<view style="width: 750rpx;height: 272rpx;background: linear-gradient(180deg, #D8EEFC 0%, #F7F8FA 100%);"></view>
		<view style="margin-top:-272rpx;"></view>
		<view style="width: 750rpx;z-index: 9;position: fixed;top:0;left:0;color:#000;" :style="scrollTop>0?'background:#FFF;height:'+(statusBarHeight+navigationBarHeight)+'px':''">
			<view :style="'height:'+statusBarHeight+'px'"></view>
			<view class="flexJusp" style="font-weight: bold;font-size: 36rpx;align-items: center;" :style="'height:'+navigationBarHeight+'px;line-height:'+navigationBarHeight+'px'">
				<view style="width:100rpx;text-align: center;" @click="back">
					<image style="width:20rpx;height:34rpx;" src="/static/images/mv_tuichu.png"></image>
				</view>
				<view>钱包</view>
				<view style="width:100rpx;"></view>
			</view>
		</view>
		<view :style="'height:'+(statusBarHeight+navigationBarHeight)+'px'"></view>

		<view style="margin:20rpx;background:#FFF;border-radius:20rpx;padding:24rpx;">
			<view class="wallet-grid">
				<view class="wallet-card" @click="openOrderList('available')">
					<view class="wallet-card__label">可提现额度</view>
					<view class="wallet-card__value">￥{{formatMoney(summary.available)}}</view>
					<view class="wallet-card__tip">点击查看订单明细</view>
				</view>
				<view class="wallet-card">
					<view class="wallet-card__label">历史提现额度</view>
					<view class="wallet-card__value">￥{{formatMoney(summary.history)}}</view>
				</view>
			</view>
			<view class="withdraw-btn" @click="showWithdrawPanel = !showWithdrawPanel">提现</view>
		</view>

		<view style="margin:0 20rpx 20rpx;background:#FFF6E6;border-radius:16rpx;padding:24rpx;">
			<view style="font-weight:bold;font-size:28rpx;color:#E6A23C;margin-bottom:12rpx;">提现规则</view>
			<view style="font-size:24rpx;color:#666;line-height:1.6;">
				<view style="margin-bottom:8rpx;">1、每日提现一次；</view>
				<view style="margin-bottom:8rpx;">2、提现时间不限；</view>
				<view style="margin-bottom:8rpx;">3、到账时间每周一；</view>
			</view>
		</view>

		<view v-if="showWithdrawPanel" style="margin:20rpx;background:#FFF;border-radius:20rpx;padding:24rpx;">
			<view style="font-weight:bold;font-size:30rpx;margin-bottom:20rpx;">提现</view>
			<view style="font-size:24rpx;color:#666;margin-bottom:20rpx;">实名认证信息已自动带入，当前只支持全部提现吗</view>
			<view class="withdraw-form-item">
				<view class="withdraw-form-label">提现账户</view>
				<view class="withdraw-readonly">{{authInfo.account_no || '-'}}</view>
			</view>
			<view class="withdraw-form-item">
				<view class="withdraw-form-label">姓名</view>
				<view class="withdraw-readonly">{{authInfo.name || '-'}}</view>
			</view>
			<view class="withdraw-form-item">
				<view class="withdraw-form-label">备注</view>
				<input class="withdraw-form-input" v-model="withdrawForm.memo" placeholder="选填" />
			</view>
			<view class="withdraw-summary">{{withdrawSummaryText}}</view>
			<view class="withdraw-actions">
				<view class="withdraw-cancel" @click="showWithdrawPanel=false">取消</view>
				<view class="withdraw-submit" @click="submitWithdraw">提交提现</view>
			</view>
		</view>

		<view v-if="showOrderList" style="margin:20rpx;background:#FFF;border-radius:20rpx;padding:24rpx;">
			<view class="flexJusp" style="font-weight:bold;font-size:30rpx;margin-bottom:20rpx;align-items:center;">
				<view>{{orderListTitle}}</view>
				<view style="color:#4878FF;font-size:26rpx;" @click="showOrderList=false">收起</view>
			</view>
			<view v-if="orderList.length">
				<view v-for="item in orderList" :key="item.orderid" class="order-item">
					<view>
						<view style="font-weight:bold;">订单 {{item.orderid}}</view>
						<view style="font-size:24rpx;color:#999;margin-top:8rpx;">{{item.createtime}}</view>
					</view>
					<view style="text-align:right;">
						<view style="font-weight:bold;color:#4878FF;">￥{{formatMoney(item.amount)}}</view>
						<view style="font-size:24rpx;color:#999;margin-top:8rpx;">{{item.status_text}}</view>
					</view>
				</view>
			</view>
			<view v-else style="color:#999;">暂无明细</view>
		</view>

		<view style="margin:20rpx;background:#FFF;border-radius:20rpx;padding:24rpx;">
			<view style="font-weight:bold;font-size:30rpx;margin-bottom:20rpx;">提现记录</view>
			<view v-if="records.length">
				<view v-for="item in records" :key="item.id" class="record-item">
					<view>
						<view style="font-weight:bold;">￥{{formatMoney(item.money)}}</view>
						<view style="font-size:24rpx;color:#999;margin-top:8rpx;">{{item.createtime || ''}}</view>
					</view>
					<view style="text-align:right;flex:1;margin-left:20rpx;">
						<view>{{formatWithdrawStatus(item.status)}}</view>
						<view style="font-size:24rpx;color:#999;margin-top:8rpx;max-width:360rpx;word-break:break-all;line-height:1.4;">订单：{{item.orderid || '-'}}</view>
					</view>
				</view>
			</view>
			<view v-else style="color:#999;">暂无提现记录</view>
		</view>

		<view style="height:60rpx;"></view>
	</view>
</template>

<script>
import { systemInfo } from '@/https/mixin.js'
export default {
	mixins: [systemInfo],
	data() {
		return {
			scrollTop: 0,
			summary: { available: 0, history: 0 },
			records: [],
			showWithdrawPanel: false,
			withdrawForm: {
				memo: ''
			},
			authInfo: {},
			showOrderList: false,
			orderListType: 'available',
			orderList: []
		}
	},
	onLoad() {
		this.getSystemInfo()
		this.loadData()
	},
	onShow() {
		this.loadData()
	},
	onPageScroll(event) {
		this.scrollTop = event.scrollTop
	},
	methods: {
		back() {
			uni.navigateBack()
		},
		formatMoney(val) {
			const num = Number(val)
			return isNaN(num) ? '0.00' : num.toFixed(2)
		},
		formatWithdrawStatus(status) {
			const map = {
				created: '已提交',
				pending: '审核中',
				successed: '已到账',
				rejected: '已驳回',
				failed: '失败'
			}
			return map[String(status || '').toLowerCase()] || status || '已提交'
		},
		openOrderList(type) {
			this.orderListType = type
			this.showOrderList = true
			this.loadOrderList(type)
		},
		loadAuthInfo() {
			this.$httpapi('Index/bankcardAuthInfo', 'POST', {}).then(res => {
				this.authInfo = res.data || {}
			})
		},
		loadOrderList(type) {
			this.$httpapi('Placeorder/walletOrderList', 'POST', {
				type: type || 'available'
			}).then(res => {
				this.orderList = Array.isArray(res.data) ? res.data : []
			})
		},
		loadData() {
			this.$httpapi('Placeorder/walletSummary', 'POST', {}).then(res => {
				this.summary = res.data || { available: 0, history: 0 }
			})
			this.$httpapi('Placeorder/withdrawList', 'POST', {}).then(res => {
				this.records = Array.isArray(res.data) ? res.data : []
			})
			this.loadAuthInfo()
			if (this.showOrderList) {
				this.loadOrderList(this.orderListType)
			}
		},
		submitWithdraw() {
			if (Number(this.summary.available) <= 0) {
				uni.showToast({ title: '可提现金额为0', icon: 'none' })
				return
			}
			this.$httpapi('Placeorder/withdrawApply', 'POST', {
				memo: this.withdrawForm.memo
			}).then(() => {
				uni.showToast({ title: '提交成功', icon: 'success' })
				this.showWithdrawPanel = false
				this.withdrawForm = { memo: '' }
				this.loadData()
			})
		}
	}
}
</script>

<style lang="scss">
.wallet-grid {
	display: grid;
	grid-template-columns: 1fr;
	gap: 20rpx;
}
.wallet-card {
	background: linear-gradient(180deg, #F7FBFF 0%, #EEF5FF 100%);
	border-radius: 20rpx;
	padding: 24rpx;
}
.wallet-card__label {
	font-size: 26rpx;
	color: #666;
}
.wallet-card__value {
	font-size: 42rpx;
	font-weight: bold;
	margin-top: 12rpx;
	color: #4878FF;
}
.wallet-card__tip {
	margin-top: 10rpx;
	font-size: 22rpx;
	color: #999;
}
.order-item {
	display: flex;
	justify-content: space-between;
	padding: 20rpx 0;
	border-bottom: 1rpx solid #F2F2F2;
}
.order-item:last-child {
	border-bottom: 0;
}
.record-item {
	display: flex;
	justify-content: space-between;
	padding: 20rpx 0;
	border-bottom: 1rpx solid #F2F2F2;
}
.record-item:last-child {
	border-bottom: 0;
}
.withdraw-btn {
	margin-top: 24rpx;
	background: linear-gradient(180deg, #4897FF 0%, #4878FF 100%);
	color: #FFF;
	text-align: center;
	padding: 22rpx 0;
	border-radius: 16rpx;
	font-size: 30rpx;
	font-weight: bold;
}
.withdraw-form-item {
	margin-bottom: 20rpx;
}
.withdraw-form-label {
	font-size: 24rpx;
	color: #666;
	margin-bottom: 10rpx;
}
.withdraw-form-input,
.withdraw-picker,
.withdraw-readonly {
	width: 100%;
	min-height: 76rpx;
	background: #F7F8FA;
	border-radius: 12rpx;
	padding: 0 20rpx;
	box-sizing: border-box;
	display: flex;
	align-items: center;
}
.withdraw-mode {
	display: flex;
	gap: 16rpx;
	margin-bottom: 20rpx;
}
.withdraw-mode-item {
	flex: 1;
	text-align: center;
	padding: 16rpx 0;
	border: 1rpx solid #4878FF;
	border-radius: 12rpx;
	font-size: 26rpx;
	color: #4878FF;
}
.withdraw-mode-item.active {
	background: #4878FF;
	color: #FFF;
}
.withdraw-summary {
	font-size: 24rpx;
	color: #4878FF;
	margin: 12rpx 0 20rpx;
}
.withdraw-actions {
	display: flex;
	gap: 20rpx;
	margin-top: 28rpx;
}
.withdraw-cancel,
.withdraw-submit {
	flex: 1;
	text-align: center;
	padding: 20rpx 0;
	border-radius: 12rpx;
	font-size: 28rpx;
}
.withdraw-cancel {
	background: #F2F2F2;
	color: #333;
}
.withdraw-submit {
	background: linear-gradient(180deg, #4897FF 0%, #4878FF 100%);
	color: #FFF;
}
</style>

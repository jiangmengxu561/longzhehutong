<template>
	<view class="content">
		<view class="loading-wrap">
			<view class="loading-tip">正在加载订单信息...</view>
		</view>
	</view>
</template>

<script>
	import { systemInfo } from '@/https/mixin.js'
	export default {
		mixins: [systemInfo],
		data() {
			return {}
		},
		onLoad(option) {
			const orderId = option.id || option.order_id
			if (!orderId) {
				uni.showToast({ title: '缺少订单信息', icon: 'none' })
				setTimeout(() => uni.navigateBack(), 1500)
				return
			}
			this.$httpapi('Placeorder/orderdetaile', 'POST', { order_id: orderId }).then(rex => {
				uni.setStorageSync('editOrderId', orderId)
				uni.setStorageSync('editOrderData', rex.data)
				uni.redirectTo({ url: '/pages/order/orderForm/orderForm' })
			}).catch(err => {
				uni.showToast({ title: '加载失败，请重试', icon: 'none' })
				setTimeout(() => uni.navigateBack(), 1500)
			})
		}
	}
</script>

<style scoped>
	.content { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #F7F8FA; }
	.loading-wrap { text-align: center; }
	.loading-tip { font-size: 28rpx; color: #666; }
</style>

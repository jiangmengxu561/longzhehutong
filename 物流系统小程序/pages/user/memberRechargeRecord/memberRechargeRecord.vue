<template>
	<view class="content">
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
				<view>充值记录</view>
				<view style="width:100rpx;"></view>
			</view>
		</view>
		<view :style="'height:'+(statusBarHeight+navigationBarHeight)+'px'"></view>

		<view v-if="list.length === 0" style="text-align:center;color:#999;padding:120rpx 0;font-size:26rpx;">
			暂无充值记录
		</view>

		<view v-for="item in list" :key="item.id"
			style="background:#FFFFFF;border-radius:20rpx;padding:24rpx;margin:20rpx;">
			<view class="flexJusp" style="align-items:center;">
				<view style="font-weight:bold;font-size:30rpx;">{{item.package_name}}</view>
				<view :style="'color:'+(item.pay_status==3?'#36B34A':'#F29100')+';font-size:26rpx;'">{{item.pay_status_text}}</view>
			</view>
			<view class="flexJusp" style="align-items:center;margin-top:20rpx;">
				<view style="color:#666;font-size:26rpx;">{{item.duration_text}}</view>
				<view style="color:#F29100;font-size:28rpx;">¥{{item.price}}</view>
			</view>
			<view style="color:#999;font-size:24rpx;margin-top:16rpx;">订单号：{{item.order_no}}</view>
			<view style="color:#999;font-size:24rpx;margin-top:8rpx;">创建时间：{{item.createtimeText}}</view>
			<view v-if="item.expiry_after" style="color:#999;font-size:24rpx;margin-top:8rpx;">会员到期：{{item.expiryText}}</view>
		</view>
		<view style="height:40rpx;"></view>
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
				list: []
			}
		},
		onLoad() {
			this.getSystemInfo()
			this.loadData()
		},
		onPageScroll(event) {
			this.scrollTop = event.scrollTop
		},
		methods: {
			tuichu() {
				uni.navigateBack()
			},
			formatTime(ts) {
				if (!ts) return ''
				const s = String(ts)
				if (/^\d{10}$/.test(s)) return this.$utils.YMD(Number(s) * 1000)
				if (/^\d{13}$/.test(s)) return this.$utils.YMD(Number(s))
				return s
			},
			loadData() {
				this.$httpapi('Memberrecharge/orders', 'POST', {}).then(res => {
					if (res.code == 1) {
						this.list = (res.data || []).map(item => {
							item.createtimeText = this.formatTime(item.createtime)
							item.expiryText = this.formatTime(item.expiry_after)
							return item
						})
					} else if (res.msg) {
						uni.showToast({ title: res.msg, icon: 'none' })
					}
				})
			}
		}
	}
</script>

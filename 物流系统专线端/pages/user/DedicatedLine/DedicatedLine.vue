<template>
	<view class="content">
		<view class="bg-gradient"></view>
		<view class="nav-fixed" :style="scrollTop>0?'background:#FFF;':''">
			<view :style="'height:'+statusBarHeight+'px'"></view>
			<view class="flexJusp nav-bar" :style="'height:'+navigationBarHeight+'px;line-height:'+navigationBarHeight+'px'">
				<view class="nav-back" @tap="tuichu">
					<image style="width:20rpx;height:34rpx;" src="/static/images/mv_tuichu.png"></image>
				</view>
				<view>专线入驻</view>
				<view style="width:100rpx;"></view>
			</view>
		</view>
		<view :style="'height:'+(statusBarHeight+navigationBarHeight)+'px'"></view>

		<view class="page-body">
			<view v-if="!loading && shippingList.length === 0" class="empty-box">
				<view class="empty-tip">暂未添加发货地信息</view>
				<button class="add-btn" @tap="goAdd">添加</button>
			</view>

			<view v-else-if="!loading">
				<view v-for="(item, index) in shippingList" :key="index" class="ship-card">
					<view class="ship-title">{{item.address_text}}</view>
					<view class="ship-sub" v-if="item.shipping_logistics_park">{{item.shipping_logistics_park}}</view>
					<view class="ship-sub">落货地 {{item.arrival_count || 0}} 个</view>
					<view class="ship-actions">
						<button class="action-btn edit" hover-class="action-btn-hover" @tap="onEdit(index)">修改</button>
						<button class="action-btn del" hover-class="action-btn-hover" @tap="onDelete(index)">删除</button>
					</view>
				</view>
				<button class="continue-btn" @tap="goAdd">继续添加</button>
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
				scrollTop: 0,
				loading: true,
				shippingList: []
			}
		},
		onLoad() {
			this.getSystemInfo()
		},
		onShow() {
			this.getList()
		},
		onPageScroll(event) {
			this.scrollTop = event.scrollTop
		},
		methods: {
			getList() {
				var that = this
				this.$httpapi('Dedicated/logistics_detail', 'POST', {}).then(res => {
					that.loading = false
					if (res.code == 1 && res.data) {
						let list = res.data.shipping_list || []
						if (!list.length && (res.data.shipping_province || res.data.arrival_list)) {
							let first = res.data
							let arrivals = first.arrival_list || [first]
							list = [{
								shipping_key: [first.shipping_province, first.origincity, first.shipping_area].join('|'),
								address_text: [first.shipping_province, first.origincity, first.shipping_area].filter(Boolean).join(' '),
								shipping_province: first.shipping_province,
								origincity: first.origincity,
								shipping_area: first.shipping_area,
								shipping_logistics_park: first.shipping_logistics_park,
								arrival_count: arrivals.length,
								arrival_list: arrivals
							}]
						}
						that.shippingList = list
					} else {
						that.shippingList = []
					}
				}).catch(() => {
					that.loading = false
					that.shippingList = []
				})
			},
			goAdd() {
				uni.navigateTo({
					url: '/pages/user/DedicatedLine/DedicatedLineForm?mode=add'
				})
			},
			onEdit(index) {
				var item = this.shippingList[index]
				if (!item) {
					uni.showToast({ title: '数据异常', icon: 'none' })
					return
				}
				uni.navigateTo({
					url: '/pages/user/DedicatedLine/DedicatedLineForm?mode=edit&shipping_key=' + encodeURIComponent(item.shipping_key || '')
				})
			},
			onDelete(index) {
				var that = this
				var item = this.shippingList[index]
				if (!item) {
					uni.showToast({ title: '数据异常', icon: 'none' })
					return
				}
				uni.showModal({
					title: '提示',
					content: '确定删除发货地「' + (item.address_text || '') + '」及其全部落货信息吗？',
					success(res) {
						if (!res.confirm) return
						that.$httpapi('Dedicated/delete_shipping', 'POST', {
							shipping_key: item.shipping_key,
							shipping_province: item.shipping_province,
							origincity: item.origincity,
							shipping_area: item.shipping_area
						}).then(rex => {
							uni.showToast({
								title: rex.msg || '删除成功',
								icon: 'none'
							})
							if (rex.code == 1) {
								that.getList()
							}
						})
					}
				})
			},
			tuichu() {
				uni.navigateBack()
			}
		}
	}
</script>

<style>
	.content {
		position: relative;
		min-height: 100vh;
	}

	.bg-gradient {
		position: absolute;
		left: 0;
		top: 0;
		width: 750rpx;
		height: 272rpx;
		background: linear-gradient(180deg, #D8EEFC 0%, #F7F8FA 100%);
		z-index: 0;
		pointer-events: none;
	}

	.nav-fixed {
		width: 750rpx;
		z-index: 99;
		position: fixed;
		top: 0;
		left: 0;
		color: #000;
	}

	.nav-bar {
		font-weight: bold;
		font-size: 36rpx;
		align-items: center;
	}

	.nav-back {
		width: 100rpx;
		text-align: center;
	}

	.page-body {
		position: relative;
		z-index: 2;
	}

	.empty-box {
		margin: 120rpx 40rpx 0;
		text-align: center;
	}

	.empty-tip {
		font-size: 28rpx;
		color: #999;
		margin-bottom: 48rpx;
	}

	.add-btn {
		width: 320rpx;
		height: 80rpx;
		line-height: 80rpx;
		margin: 0 auto;
		background: linear-gradient(180deg, #4897FF 0%, #4878FF 100%);
		border-radius: 40rpx;
		font-weight: bold;
		font-size: 30rpx;
		color: #FFFFFF;
		padding: 0;
		border: none;
	}

	.add-btn::after {
		border: none;
	}

	.ship-card {
		position: relative;
		z-index: 2;
		background: #FFFFFF;
		border-radius: 24rpx;
		margin: 20rpx;
		padding: 28rpx 24rpx;
	}

	.ship-title {
		font-size: 32rpx;
		font-weight: bold;
		color: #333;
	}

	.ship-sub {
		margin-top: 12rpx;
		font-size: 26rpx;
		color: #888;
	}

	.ship-actions {
		display: flex;
		flex-direction: row;
		justify-content: flex-end;
		align-items: center;
		margin-top: 24rpx;
	}

	.action-btn {
		min-width: 140rpx;
		height: 64rpx;
		line-height: 64rpx;
		text-align: center;
		border-radius: 32rpx;
		font-size: 26rpx;
		margin: 0 0 0 20rpx;
		padding: 0 24rpx;
		box-sizing: border-box;
	}

	.action-btn::after {
		border: none;
	}

	.action-btn.edit {
		color: #4878FF;
		background: #FFFFFF;
		border: 2rpx solid #4878FF;
	}

	.action-btn.del {
		color: #EC0000;
		background: #FFFFFF;
		border: 2rpx solid #EC0000;
	}

	.action-btn-hover {
		opacity: 0.7;
	}

	.continue-btn {
		width: auto;
		margin: 40rpx;
		height: 80rpx;
		line-height: 80rpx;
		text-align: center;
		border: 2rpx dashed #4878FF;
		border-radius: 40rpx;
		color: #4878FF;
		font-size: 28rpx;
		font-weight: bold;
		background: transparent;
		padding: 0;
	}

	.continue-btn::after {
		border: none;
	}
</style>

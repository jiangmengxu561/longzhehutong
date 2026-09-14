<template>
	<view class="address">
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
				<view>地址薄</view>
				<view style="width:100rpx;"></view>
			</view>
		</view>
		<view :style="'height:'+(statusBarHeight+navigationBarHeight)+'px'"></view>
		<view class="addressList">
			<view class="addressBox" v-for="(item,index) in list" :key="index">
				<view @click="checkAddress(item)"
					style="display: flex;border-bottom:2rpx solid #F0F0F0;padding-bottom:24rpx;align-items: center;">
					<image style="width:64rpx;height:64rpx;margin:14rpx 20rpx 0 0;" src="/static/images/icon_16dz.png"></image>
					<view style="width:560rpx;">
						<view style="display: flex;line-height:52rpx;">
							<view style="font-size: 30rpx;font-weight: bold;color: #333333;margin-right:12rpx;">
								{{item.user_name}}
							</view>
							<view style="font-size: 24rpx;color: #333333;">{{item.mobile}}</view>
						</view>
						<view style="font-size: 24rpx;color: #666666;">{{item.address}} </view>
						
						<view style="font-size: 24rpx;color: #666666;">{{item.detailed_address}} </view>
					</view>
				</view>
				<view style="display: flex;justify-content: space-between;padding-top:16rpx;">
					<view style="display: flex;align-items: center;" @click="detail(item)">

						<image v-if="item.default==1" style="width:32rpx;height:32rpx;margin:0 16rpx 0 0;"
							src="/static/images/icon_1xuan1.png"></image>
						<image v-if="item.default==0" style="width:32rpx;height:32rpx;margin:0 16rpx 0 0;"
							src="/static/images/icon_1xuan.png"></image>
						<view :style="item.default==1?'color: #4878FF;':''">设为默认</view>
					</view>
					<view style="display: flex;">
						<image @click="edit(item)" style="width:40rpx;height:40rpx;" src="/static/images/icon_23bj.png"></image>
						<image @click="delect(item.id)" style="width:40rpx;height:40rpx;margin:0 0 0 28rpx;"
							src="/static/images/icon_23sc.png"></image>
					</view>
				</view>
			</view>
		</view>
		<view style="height:180rpx;"></view>
		<view class="buttons" @click="addAddress">添加地址</view>
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
				list: [],
				scrollTop: 0,
				addressType: '' // 1-装货地址 2-卸货地址
			};
		},
		onLoad(option) {
			this.getSystemInfo()
			if (option.type) {
				this.addressType = option.type
			}
		},
		onShow() {
			this.loads()
		},
		onPageScroll(event) {
			const scrollTop = event.scrollTop // 获取当前页面滚动高度
			this.scrollTop = event.scrollTop
		},
		methods: {
			tuichu(){
				uni.navigateBack()
			},
			checkAddress(item) {
				console.log('地址簿选择地址:', item)
				const eventChannel = this.getOpenerEventChannel()
				if (eventChannel) {
					eventChannel.emit('acceptDataFromOpenedPages', item);
					console.log('地址数据已发送:', item)
				} else {
					console.error('eventChannel 不存在')
				}
				uni.navigateBack()
			},
			loads() {
				var params = {}
				if (this.addressType) {
					params.type = this.addressType
				}
				this.$httpapi('Placeorder/address_list', 'GET', params).then(res => {
					this.list = res.data
				})
			},
			addAddress() {
				uni.setStorageSync('addressData', '')
				var url = '/pages/user/address/addAddress'
				if (this.addressType) {
					url += '?type=' + this.addressType
				}
				uni.navigateTo({
					url: url
				})
			},
			detail(item) {
				var that = this
				if (item.defaultswitch == 1) {
					uni.showToast({
						title: '此地址已经是默认地址了',
						duration: 1500,
						icon: 'none'
					});
				} else {
					uni.showModal({
						title: '默认地址',
						content: '是否设置此地址为默认地址',
						confirmText: '确定',
						success: function(res) {
							if (res.confirm) {
								that.$httpapi('Deliver/edit', 'POST', {
									id: item.id,
									name: item.name,
									mobile: item.mobile,
									city: item.city,
									address: item.address,
									default: 1,
								}).then(ress => {
									uni.showToast({
										title: ress.msg,
										duration: 1500,
										icon: 'none'
									});
									that.loads()
								})
							} else if (res.cancel) {}
						}
					});
				}
			},
			edit(item) {
				uni.setStorageSync('addressData', item)
				uni.navigateTo({
					url: '/pages/user/address/addAddress'
				})
			},
			delect(id) {
				var that = this
				uni.showModal({
					title: '删除地址',
					content: '是否确定删除该地址',
					confirmText: '确定',
					success: function(res) {
						if (res.confirm) {
							that.$httpapi('Placeorder/deleteaddress', 'POST', {
								id: id
							}).then(ress => {
								uni.showToast({
									title: ress.msg,
									duration: 1500,
									icon: 'none'
								});
								that.loads()
							})
						} else if (res.cancel) {}
					}
				});
			}
		}
	}
</script>

<style lang="scss">
	page {
		background-color: #F7F6FA;
	}

	.address {
		padding: 0 0 200rpx;

		.addressList {
			width: 710rpx;
			margin: auto;

			.addressBox {
				margin-bottom: 20rpx;
				background-color: #FFF;
				padding: 24rpx 28rpx 18rpx 30rpx;
			}
		}

		.buttons{
			position: fixed;
			left:30rpx;
			bottom:88rpx;
			width: 690rpx;
			height: 88rpx;
			background: linear-gradient( 180deg, #4897FF 0%, #4878FF 100%);
			border-radius: 50rpx;
			font-weight: bold;
			font-size: 30rpx;
			color: #FFFFFF;
			line-height: 88rpx;
			text-align: center;
		}
	}
</style>
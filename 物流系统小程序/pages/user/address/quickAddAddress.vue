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
				<view>选择地址</view>
				<view style="width:100rpx;"></view>
			</view>
		</view>
		<view :style="'height:'+(statusBarHeight+navigationBarHeight)+'px'"></view>
		<view class="addressBox">
			<view class="flexJusp" style="line-height:114rpx;border-bottom: 2rpx solid #F0F0F0;padding:0 20rpx;">
				<view style="width:160rpx;">所在地址</view>
				<view class="flex" @click="dakai" style="align-items: center;">
					<view>{{address||'请选择地址'}}</view>
					<image style="width:24rpx;height:24rpx;margin:0 0 0 10rpx;" src="/static/images/icon_2jt.png"></image>
				</view>
			</view>
			<view style="border-bottom: 2rpx solid #F0F0F0;padding:0 20rpx 36rpx;" v-if="address">
				<view style="width:160rpx;padding:36rpx 0 20rpx;">详细地址</view>
				<textarea
					style="width: 670rpx;height: 204rpx;background: #FFF;border-radius: 20rpx;font-size:28rpx;padding:20rpx;"
					v-model="detailed_address" placeholder="请输入详细地址"></textarea>
			</view>
			<view class="flexJusp" style="line-height:114rpx;border-bottom: 2rpx solid #F0F0F0;padding:0 20rpx;" v-if="address">
				<view style="width:160rpx;">姓名</view>
				<input v-model="user_name" style="margin-top:34rpx;text-align: right;font-size:28rpx;" placeholder="请输入姓名" />
			</view>
			<view class="flexJusp" style="line-height:114rpx;border-bottom: 2rpx solid #F0F0F0;padding:0 20rpx;" v-if="address">
				<view style="width:160rpx;">联系电话</view>
				<input maxlength="11" v-model="mobile" type="number" style="margin-top:34rpx;text-align: right;font-size:28rpx;"
					placeholder="请输入联系电话" />
			</view>
		</view>
		<view class="buttons" @click="saveAddress" v-if="address">保存地址</view>
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
				user_name: '',
				mobile: '',
				address: '',
				detailed_address: '',
				latitude: '',
				longitude: '',
				addressType: '' // 1-装货地址 2-卸货地址
			};
		},
		onLoad(option) {
			this.getSystemInfo()
			if (option.type) {
				this.addressType = option.type
			}
			// 页面加载时自动打开地图选择
			this.dakai()
		},
		onPageScroll(event) {
			const scrollTop = event.scrollTop // 获取当前页面滚动高度
			this.scrollTop = event.scrollTop
		},
		methods: {
			tuichu(){
				uni.navigateBack()
			},
			dakai() {
				var that = this
				uni.chooseLocation({
					success: function (res) {
						console.log(res,res.address,'-----')
						// address字段存储地点名称（如：历下大厦（解放东路北））
						// detailed_address字段存储完整地址（如：山东省济南市历下区解放东路99号）
						that.address = res.name || res.address
						that.detailed_address = res.address || ''
						that.latitude = res.latitude
						that.longitude = res.longitude
					},
					fail: function (err) {
						// 用户取消选择时，返回上一页
						if (err.errMsg && err.errMsg.indexOf('cancel') !== -1) {
							uni.navigateBack()
						}
					}
				});
			},
			saveAddress() {
				if (!this.address) {
					uni.showToast({
						title: '请先选择地址',
						duration: 1500,
						icon: 'none'
					});
					return
				}
				// if (!this.user_name) {
				// 	uni.showToast({
				// 		title: '请输入姓名',
				// 		duration: 1500,
				// 		icon: 'none'
				// 	});
				// 	return
				// }
				// if (!this.mobile) {
				// 	uni.showToast({
				// 		title: '请输入联系电话',
				// 		duration: 1500,
				// 		icon: 'none'
				// 	});
				// 	return
				// }
				// // 验证手机号
				// if (!/^1[3-9]\d{9}$/.test(this.mobile)) {
				// 	uni.showToast({
				// 		title: '请输入正确的手机号',
				// 		duration: 1500,
				// 		icon: 'none'
				// 	});
				// 	return
				// }
				
				// 保存地址
				this.$httpapi('Placeorder/address', 'POST', {
					user_name: this.user_name,
					mobile: this.mobile,
					address: this.address,
					detailed_address: this.detailed_address,
					default: 0,
					lat: this.latitude,
					lng: this.longitude,
					type: this.addressType || '', // 装货地址type=1，卸货地址type=2
				}).then(res => {
					uni.showToast({
						title: res.msg,
						duration: 1500,
						icon: 'none'
					});
					if (res.code == 1) {
						// 返回选中的地址
						console.log('快速添加地址接口返回数据:', res.data)
						console.log('res.data类型:', typeof res.data)
						
						// 处理 id：优先处理数字/字符串（直接是id），然后处理对象
						let addressId = null
						
						// 如果 res.data 是数字或字符串，直接使用（后端可能直接返回id）
						if (typeof res.data === 'number' || (typeof res.data === 'string' && res.data !== '')) {
							addressId = res.data
						}
						// 如果 res.data 是对象且不是 null
						else if (res.data !== null && typeof res.data === 'object') {
							// 优先查找 id 字段，如果不存在则查找其他可能的字段
							addressId = res.data.id !== undefined ? res.data.id : 
							           (res.data.address_id !== undefined ? res.data.address_id : 
							           (res.data.ID !== undefined ? res.data.ID : null))
						}
						
						console.log('解析后的地址ID:', addressId)
						console.log('addressId类型:', typeof addressId)
						
						// 如果无法获取 id，尝试重新查询地址列表找到对应的地址
						if (addressId === null || addressId === undefined || addressId === '') {
							console.warn('无法从接口返回获取 id，尝试查询地址列表')
							// 重新查询地址列表，通过地址信息匹配
							const that = this
							this.$httpapi('Placeorder/address_list', 'GET', {
								type: this.addressType || ''
							}).then(listRes => {
								if (listRes.code == 1 && listRes.data && listRes.data.length > 0) {
									// 通过地址和手机号匹配找到对应的地址
									const matchedAddress = listRes.data.find(item => 
										item.address === that.address && 
										item.mobile === that.mobile
									)
									if (matchedAddress && matchedAddress.id) {
										addressId = matchedAddress.id
										console.log('通过地址列表匹配找到的ID:', addressId)
									}
								}
								
								// 构建地址数据
								const addressData = {
									id: addressId,
									user_name: this.user_name,
									mobile: this.mobile,
									address: this.address,
									detailed_address: this.detailed_address,
									lat: this.latitude,
									lng: this.longitude
								}
								console.log('准备返回的地址数据:', addressData)
								
								const eventChannel = this.getOpenerEventChannel()
								if (eventChannel) {
									eventChannel.emit('acceptDataFromOpenedPages', addressData);
								} else {
									console.error('eventChannel 不存在')
								}
							}).catch(err => {
								console.error('查询地址列表失败:', err)
								// 即使查询失败，也尝试返回数据（id 可能为空，但让前端验证去处理）
								const addressData = {
									id: addressId,
									user_name: this.user_name,
									mobile: this.mobile,
									address: this.address,
									detailed_address: this.detailed_address,
									lat: this.latitude,
									lng: this.longitude
								}
								console.log('准备返回的地址数据（查询失败）:', addressData)
								
								const eventChannel = this.getOpenerEventChannel()
								if (eventChannel) {
									eventChannel.emit('acceptDataFromOpenedPages', addressData);
								}
							})
						} else {
							// 如果能获取到 id，直接返回
							const addressData = {
								id: addressId,
								user_name: this.user_name,
								mobile: this.mobile,
								address: this.address,
								detailed_address: this.detailed_address,
								lat: this.latitude,
								lng: this.longitude
							}
							console.log('准备返回的地址数据:', addressData)
							const eventChannel = this.getOpenerEventChannel()
							if (eventChannel) {
								eventChannel.emit('acceptDataFromOpenedPages', addressData);
							} else {
								console.error('eventChannel 不存在')
							}
						}
						setTimeout(() => {
							uni.navigateBack()
						}, 1500)
					}
				})
			}
		}
	}
</script>

<style lang="scss">
	.address {
		padding: 0 0 100rpx;
		color: #000;

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


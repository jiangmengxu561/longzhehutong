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
				<view>地址信息</view>
				<view style="width:100rpx;"></view>
			</view>
		</view>
		<view :style="'height:'+(statusBarHeight+navigationBarHeight)+'px'"></view>
		<view class="addressBox">
			<view class="flexJusp" style="line-height:114rpx;border-bottom: 2rpx solid #F0F0F0;padding:0 20rpx;">
				<view style="width:160rpx;">姓名</view>
				<input v-model="user_name" style="margin-top:34rpx;text-align: right;font-size:28rpx;" placeholder="请输入姓名" />
			</view>
			<view class="flexJusp" style="line-height:114rpx;border-bottom: 2rpx solid #F0F0F0;padding:0 20rpx;">
				<view style="width:160rpx;">公司名称</view>
				<input v-model="company_name" style="margin-top:34rpx;text-align: right;font-size:28rpx;" placeholder="请输入公司名称" />
			</view>
			<view class="flexJusp" style="line-height:114rpx;border-bottom: 2rpx solid #F0F0F0;padding:0 20rpx;">
				<view style="width:160rpx;">联系电话</view>
				<input maxlength="11" v-model="mobile" type="number" style="margin-top:34rpx;text-align: right;font-size:28rpx;"
					placeholder="请输入联系电话" />
			</view>
			<view class="flexJusp" style="line-height:114rpx;border-bottom: 2rpx solid #F0F0F0;padding:0 20rpx;">
				<view style="width:160rpx;">所在地址</view>
				<view class="flex" @click="dakai" style="align-items: center;">
					<view>{{address||'请选择'}}</view>
					<image style="width:24rpx;height:24rpx;margin:0 0 0 10rpx;" src="/static/images/icon_2jt.png"></image>
				</view>
			</view>
			<view style="border-bottom: 2rpx solid #F0F0F0;padding:0 20rpx 36rpx;">
				<view style="width:160rpx;padding:36rpx 0 20rpx;">详细地址</view>
				<textarea
					style="width: 670rpx;height: 204rpx;background: #FFF;border-radius: 20rpx;font-size:28rpx;padding:20rpx;"
					v-model="detailed_address" placeholder="请输入详细地址"></textarea>
			</view>
			<view class="flexJusp" style="line-height:114rpx;padding:0 24rpx;align-items: center;">
				<view style="width:200rpx;">设为默认地址：</view>
				<image style="width:32rpx;height:32rpx;" @click="qiehuan" v-if="defaults==1" src="/static/images/icon_1xuan1.png">
				</image>
				<image style="width:32rpx;height:32rpx;" @click="qiehuan" v-else src="/static/images/icon_1xuan.png"></image>
			</view>
		</view>
		<view class="buttons" @click="addAddress">保存</view>
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
				defaults: 1,
				citys: [],
				name: '',
				mobile: '',
				address_detail: '',
				id: '',
				check: true,
				lng: '',
				lat: '',
				city: '市辖区',
				province: '北京市',
				area: '东城区',
				visible: false,
				maskCloseAble: true,
				str: '',
				defaultValue: ['北京市', '市辖区', '东城区'],
				column: 3,
				id: '',
				user_name: '',
				company_name:'',
				address:'',
				detailed_address: '',
				latitude: '',
				longitude: '',
				addressType: '' // 1-装货地址 2-卸货地址
			};
		},
		onLoad(option) {
			this.getSystemInfo()
			// 接收type参数
			if (option.type) {
				this.addressType = option.type
			}
			// if (option.id) {
			// 	this.id = option.id
			// 	this.loads()
			// }
			if (uni.getStorageSync('addressData')) {
				var datas = uni.getStorageSync('addressData')
				this.id = datas.id
				this.mobile = datas.mobile
				this.user_name = datas.user_name
				this.company_name = datas.company_name
				this.latitude = datas.lat
				this.longitude = datas.lng
				this.address = datas.address
				this.detailed_address = datas.detailed_address
				this.defaults = datas.default
				// 如果地址数据中有type，也保存
				if (datas.type) {
					this.addressType = datas.type
				}
			}
		},
		onPageScroll(event) {
			const scrollTop = event.scrollTop // 获取当前页面滚动高度
			this.scrollTop = event.scrollTop
		},
		methods: {
			tuichu(){
				uni.navigateBack()
			},
			qiehuan() {
				if (this.defaults == 1) {
					this.defaults = 0
				} else {
					this.defaults = 1
				}
			},
			dakai() {
				var that = this
				uni.chooseLocation({
					success: function (res) {
						console.log(res,res.address,'-----')
						that.latitude = res.latitude
						that.longitude = res.longitude
						// 所在地址：显示选点名称（res.name）
						that.address = res.name || res.address || ''
						// 详细地址：保存带省市区的完整地址（res.address）
						that.detailed_address = res.address || ''
					}
				});
				// this.visible = true
			},
			confirm(val) {
				console.log(val)
				this.province = val.provinceName
				this.city = val.cityName
				this.area = val.areaName
				this.province = val.provinceName
				this.area = val.areaName

				this.str = JSON.stringify(val)
				this.visible = false
			},
			cancel() {
				this.visible = false
			},
			dituxuanze() {
				var that = this
				uni.chooseLocation({
					success: function(res) {
						that.lng = res.longitude
						that.lat = res.latitude
						that.address = res.address
						uni.request({
							header: {
								"Content-Type": "application/text"
							},
							url: 'https://restapi.amap.com/v3/geocode/regeo?output=JSON&location=' +
								res
								.longitude + ',' + res.latitude +
								'&key=2a9c52f16852b98220d549bbc817e957&radius=1000&extensions=base',
							success(rex) {
								that.city = rex.data.regeocode.addressComponent.province + ',' + rex
									.data.regeocode.addressComponent.city + ',' + rex.data.regeocode
									.addressComponent.district
							}
						});
					}
				});
			},
			changes(e) {
				this.check = e.detail.value
				console.log(e, '--------------')
			},
			bindPickerChange(e) {
				this.city = e.detail.value.toString('/')
			},
			addressXuan() {
				var that = this
				uni.chooseLocation({
					success: (data) => {
						that.address = data.name;
						that.latitude = data.latitude;
						that.longitude = data.longitude;
					}
				})
			},
			addAddress() {
				if (this.id) {
					var updateData = {
						id: this.id,
						user_name: this.user_name,
						company_name :this.company_name,
						mobile: this.mobile,
						address: this.address,
						detailed_address: this.detailed_address,
						default: this.defaults,
						lat: this.latitude,
						lng: this.longitude,
					}
					// 如果有type参数，添加到更新数据中
					if (this.addressType) {
						updateData.type = this.addressType
					}
					this.$httpapi('Placeorder/updateaddress', 'POST', updateData).then(res => {
						uni.showToast({
							title: res.msg,
							duration: 1500,
							icon: 'none'
						});
						if (res.code == 1) {
							// 组装更新后的地址数据，回传给上一页并写入缓存（供首页onShow兜底刷新）
							const addressData = {
								id: this.id,
								user_name: this.user_name,
								company_name:this.company_name,
								mobile: this.mobile,
								address: this.address,
								detailed_address: this.detailed_address,
								default: this.defaults,
								lat: this.latitude,
								lng: this.longitude,
								type: this.addressType || ''
							}
							// 写入缓存：用于返回首页后刷新显示
							uni.setStorageSync('lastEditedAddress', addressData)
							uni.setStorageSync('lastEditedAddress_ts', Date.now())
							// 尝试通过eventChannel通知打开本页的页面立即更新
							try {
								const eventChannel = this.getOpenerEventChannel && this.getOpenerEventChannel()
								if (eventChannel) {
									eventChannel.emit('acceptDataFromOpenedPages', addressData)
								}
							} catch (e) {}
							// 清理编辑缓存，避免下次误用
							uni.setStorageSync('addressData', '')
							setTimeout(() => {
								uni.navigateBack()
							}, 1500)
						}
					})
				} else {
					var addData = {
						user_name: this.user_name,
						company_name:this.company_name,
						mobile: this.mobile,
						address: this.address,
						detailed_address: this.detailed_address,
						default: this.defaults,
						lat: this.latitude,
						lng: this.longitude,
					}
					// 如果有type参数，添加到新增数据中
					if (this.addressType) {
						addData.type = this.addressType
					}
					this.$httpapi('Placeorder/address', 'POST', addData).then(res => {
						uni.showToast({
							title: res.msg,
							duration: 1500,
							icon: 'none'
						});
						if (res.code == 1) {
							// 新增成功也写入缓存，方便返回首页/上一页同步显示（id可能由后端返回，若无则暂不强依赖）
							const newId = (res && res.data && (res.data.id ?? res.data.address_id ?? res.data.ID)) ?? res.data ?? ''
							const addressData = {
								id: newId,
								user_name: this.user_name,
								company_name:this.company_name,
								mobile: this.mobile,
								address: this.address,
								detailed_address: this.detailed_address,
								default: this.defaults,
								lat: this.latitude,
								lng: this.longitude,
								type: this.addressType || ''
							}
							uni.setStorageSync('lastEditedAddress', addressData)
							uni.setStorageSync('lastEditedAddress_ts', Date.now())
							try {
								const eventChannel = this.getOpenerEventChannel && this.getOpenerEventChannel()
								if (eventChannel) {
									eventChannel.emit('acceptDataFromOpenedPages', addressData)
								}
							} catch (e) {}
							uni.setStorageSync('addressData', '')
							setTimeout(() => {
								uni.navigateBack()
							}, 1500)
						}
					})
				}
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
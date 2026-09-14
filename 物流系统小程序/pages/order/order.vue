<template>
	<view class="content">
		<view style="width: 750rpx;height: 272rpx;background: linear-gradient( 180deg, #D8EEFC 0%, #F7F8FA 100%);"></view>
		<view style="margin-top:-272rpx;"></view>
		<view style="width: 750rpx;z-index: 9;position: fixed;top:0;left:0;color:#000;"
			:style="scrollTop>0?'background:#FFF;height:'+(statusBarHeight+navigationBarHeight)+'px':''">
			<view :style="'height:'+statusBarHeight+'px'"></view>
			<view class="flexJusp" style="font-weight: bold;font-size: 36rpx;align-items: center;"
				:style="'height:'+navigationBarHeight+'px;line-height:'+navigationBarHeight+'px'">
				<view style="width:100rpx;"></view>
				<view>我的订单</view>
				<view style="width:100rpx;"></view>
			</view>
			<view class="flexJusp" style="margin:0 28rpx;">
				<view style="text-align: center;" @click="qiehuans(0)">
					<view :style="types==0?'font-weight: bold;':''">全部订单</view>
					<view class="xian" v-if="types==0"></view>
				</view>
				<view style="text-align: center;" @click="qiehuans(1)">
					<view :style="types==1?'font-weight: bold;':''">待付款</view>
					<view class="xian" v-if="types==1"></view>
				</view>
				<view style="text-align: center;" @click="qiehuans(2)">
					<view :style="types==2?'font-weight: bold;':''">服务中</view>
					<view class="xian" v-if="types==2"></view>
				</view>
				<view style="text-align: center;" @click="qiehuans(3)">
					<view :style="types==3?'font-weight: bold;':''">已完成</view>
					<view class="xian" v-if="types==3"></view>
				</view>
			</view>
		</view>
		<view :style="'height:'+(statusBarHeight+navigationBarHeight+28)+'px'"></view>
		<view v-for="(item,index) in list" :key="index"
			style="background: #FFFFFF;border-radius: 24rpx;padding:24rpx;margin:20rpx;">
			<view class="flexJusp">
				<view style="color: #666666;">{{item.createtime}}</view>
				<view style="color: #F29100;">
					<text v-if="item.pay_status==1">待付款</text>
					<text v-if="item.pay_status==2">服务中</text>
					<text v-if="item.pay_status==3">已付款</text>
					<text v-if="item.pay_status==4">取消</text>
					<text v-if="item.pay_status==6">价格测算中</text>
					<text v-if="item.pay_status==7">已出价</text>
					<text v-if="item.pay_status==8">订单驳回</text>
				</view>
			</view>
			<view class="flex" style="align-items: center;padding:24rpx 0;" @click="details(item)">
				<view
					style="width: 48rpx;height: 48rpx;background: #F29100;border-radius: 12rpx;font-weight: bold;color: #FFFFFF;line-height: 48rpx;text-align: center;margin-right:12rpx;">
					装</view>
				<view style="width:600rpx;font-weight: bold;font-size: 30rpx;" v-if="item.loading_address">
					{{item.loading_address.address}}
				</view>
			</view>
			<view class="flex" style="align-items: center;" @click="details(item)">
				<view
					style="width: 48rpx;height: 48rpx;background: #4878FF;border-radius: 12rpx;font-weight: bold;color: #FFFFFF;line-height: 48rpx;text-align: center;margin-right:12rpx;">
					卸</view>
				<view style="width:600rpx;font-weight: bold;font-size: 30rpx;" v-if="item.unload_address">
					{{ item.unload_address.address}}
				</view>
			</view>
			<view class="flexJusp"
				style="padding-top:24rpx;border-top:2rpx solid #F7F7F7;align-items: center;margin-top:24rpx;">
				<view style="font-size:24rpx;color:#666;">总价:<text
						style="font-weight: 800;font-size: 30rpx;color: #EC0000;">￥{{item.pay_price||'暂未出价'}}</text></view>

				<view class="flex">
					<view class="buttonx" v-if="item.pay_status==4 || item.pay_status==8" style="margin-right:16rpx;" @click="deleteOrder(item, index)">删除</view>
					<view class="buttonx" v-if="item.pay_status==8" style="margin-right:16rpx;" @click="showRejectReason(item)">查看驳回原因</view>
					<view class="buttons" v-if="item.pay_status==8" style="margin-right:16rpx;" @click="xiugaiOrder(item)">修改订单</view>
					<view class="buttonx" v-if="item.pay_status==4 || item.pay_status==2 || item.pay_status==3" @click="chonglai(item)">再来一单</view>
					<view class="buttonx" v-if="item.logistics_status==1 &&item.pay_status==1 " @click="quxiao(item)">取消订单</view>
					<view class="buttons" v-if="item.pay_status==1" style="margin-left:16rpx;" @click="fukuan(item)">立即付款</view>
					<view class="buttons" v-if="item.pay_status==7" style="margin-left:16rpx;" @click="queren(item)">确认价格</view>
					<view class="buttonx" v-if="item.pay_status==2 || item.pay_status==3" @click="details(item)">查看详情</view>
					<!-- 只有正式员工才显示打印按钮：未连接时点「连接」直接打开设备列表，已连接时点「打印」 -->
					<view v-if="membertype === 4 && item.pay_status!=8" class="button-print" :class="{'button-print-connected': isConnected}" style="margin-left:16rpx;" @click.stop="isConnected ? printOrder(item) : openConnectPrinter()">
						<text class="print-icon">🖨️</text>
						<text>{{isConnected ? '打印' : '连接'}}</text>
					</view>
				</view>
			</view>
		</view>
		<!-- 设备选择弹窗 -->
		<view class="device-modal" v-if="showDeviceModal" @click="closeDeviceModal">
			<view class="device-modal-content" @click.stop>
				<view class="device-modal-header">
					<text class="device-modal-title">选择蓝牙打印机</text>
					<text class="device-modal-close" @click="closeDeviceModal">✕</text>
				</view>
				<scroll-view class="device-modal-list" scroll-y>
					<view 
						v-for="(device, index) in sortedDevices" 
						:key="index"
						class="device-item"
						:class="{'device-item-disabled': !device.connectable}"
						@click="selectDevice(device)">
						<view class="device-item-info">
							<view class="device-item-name">
								<text class="device-status" :class="device.connectable ? 'device-status-ok' : 'device-status-no'">
									{{device.connectable ? '✓' : '✗'}}
								</text>
								<text class="device-name">{{device.name || '未知设备'}}</text>
							</view>
							<view class="device-item-id">{{device.deviceId}}</view>
							<view class="device-item-rssi" v-if="device.RSSI">信号: {{device.RSSI}}dBm</view>
						</view>
					</view>
					<view v-if="sortedDevices.length === 0" class="device-empty">
						正在搜索设备...
					</view>
				</scroll-view>
				<view class="device-modal-footer">
					<view class="device-modal-btn" @click="closeDeviceModal">取消</view>
					<view class="device-modal-btn device-modal-btn-primary" @click="searchBluetoothDevices">重新搜索</view>
				</view>
			</view>
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
				types: 0,
				list: [],
				membertype: null, // 用户类型：1=普通用户，2=兼职员工，3=正式员工
				// 蓝牙相关
				bluetoothAdapter: null,
				deviceId: '',
				serviceId: '',
				characteristicId: '',
				isConnected: false,
				devices: [],
				showDeviceModal: false,
				sortedDevices: [],
				printType: 'simple' // 打印类型: simple(简版), receipt(签收单)
			}
		},
		onLoad() {
			uni.setStorageSync('zaiOrder','')
			this.getSystemInfo()
			this.getUserInfo()
		},
		onUnload() {
			if (this.isConnected) {
				this.closeBluetooth()
			}
		},
		onShow() {
			uni.setStorageSync('zaiOrder', '')
			this.loads()
		},
		onPullDownRefresh() {
			this.list = []
			this.loads()
		},
		onPageScroll(event) {
			this.scrollTop = event.scrollTop
		},
		methods: {
			// 获取用户信息，判断是否为正式员工
			getUserInfo() {
				this.$httpapi('Opinion/getuserinfo', 'GET', {}).then(res => {
					if (res.data && res.data.membertype !== undefined) {
						this.membertype = res.data.membertype
						// 只有正式员工（membertype === 3）才初始化蓝牙打印机
						if (this.membertype === 4) {
							this.initBluetooth()
						}
					} else {
						// 如果获取不到用户信息，默认不连接打印机
						this.membertype = null
					}
				}).catch(err => {
					console.log('获取用户信息失败', err)
					// 获取失败时，默认不连接打印机
					this.membertype = null
				})
			},
			chonglai(item) {
				this.$httpapi('Placeorder/orderdetaile', 'POST', {
					order_id: item.orderid
				}).then(rex => {
					uni.setStorageSync('zaiOrder', rex.data)
					uni.switchTab({
						url: '/pages/index/index'
					})
				})
			},
			// 修改订单：直接打开修改订单表单页，带 order_id，不再经 editOrder 中转
			xiugaiOrder(item) {
				const orderId = item.orderid || item.id
				uni.navigateTo({
					url: '/pages/order/orderForm/orderForm?order_id=' + orderId
				})
			},
			quxiao(item) {
				this.$httpapi('Placeorder/CancelOrder', 'POST', {
					order_id: item.orderid
				}).then(res => {
					uni.showToast({
						title: res.msg,
						duration: 1500,
						icon: 'none'
					});
					this.loads()
				})
			},
			// 删除订单（仅已取消的订单显示删除按钮）
			deleteOrder(item, index) {
				uni.showModal({
					title: '提示',
					content: '确定要删除该订单吗？',
					success: (res) => {
						if (res.confirm) {
							this.$httpapi('Placeorder/DeleteOrder', 'POST', {
								order_id: item.orderid
							}).then(res => {
								uni.showToast({
									title: res.msg || '删除成功',
									icon: 'none'
								});
								this.loads()
							}).catch(err => {
								uni.showToast({
									title: err.msg || '删除失败',
									icon: 'none'
								});
							})
						}
					}
				});
			},
			queren(item) {
				this.$httpapi('Placeorder/cancel_price', 'POST', {
					order_id: item.orderid
				}).then(res => {
					uni.showToast({
						title: res.msg,
						duration: 1500,
						icon: 'none'
					});
					this.loads()
				})
			},
			fukuan(item) {
				var that = this;
				this.$httpapi('Wechatpay/pay', 'POST', {
					order_id: item.orderid
				}).then(res => {
					wx.requestPayment({
						provider: 'wxpay',
						timeStamp: res.data.timeStamp,
						nonceStr: res.data.nonceStr,
						package: res.data.package,
						signType: res.data.signType,
						paySign: res.data.paySign,
						success: function(res) {
							wx.showToast({
								title: '支付成功',
								icon: 'success'
							})
							that.loads()
						},
						fail: function(err) {
							wx.showToast({
								title: '支付失败',
								icon: 'error'
							})
						}
					});
				})
			},
			details(item) {
				uni.navigateTo({
					url: '/pages/order/orderDetail/orderDetail?id=' + item.orderid
				})
			},
			qiehuans(type) {
				this.types = type
				this.loads()
			},
			loads() {
				this.$httpapi('Placeorder/orderlist', 'POST', {
					type: this.types
				}).then(res => {
					uni.stopPullDownRefresh()
					this.list = res.data
				})
			},
			
			// ========== 蓝牙打印相关方法 ==========
			initBluetooth() {
				uni.openBluetoothAdapter({
					success: (res) => {
						console.log('蓝牙初始化成功')
						const savedDeviceId = uni.getStorageSync('bluetoothDeviceId')
						if (savedDeviceId) {
							this.deviceId = savedDeviceId
							this.connectBluetooth(savedDeviceId)
						}
					},
					fail: (err) => {
						console.error('蓝牙初始化失败', err)
						if (err.errCode === 10001) {
							uni.showToast({
								title: '请打开手机蓝牙',
								icon: 'none'
							})
						}
					}
				})
			},
			
			// 微信里部分机型在 openBluetoothAdapter 成功后立刻 getBluetoothAdapterState 会误报 available=false，导致一直提示「蓝牙不可用」。此处改为：打开适配器成功 →（安卓）位置授权 → 直接搜设备。
			isBluetoothAuthDenied(err) {
				if (!err) return false
				const msg = (err.errMsg || '') + ''
				if (err.errno === 103 || err.errCode === 103) return true
				if (msg.indexOf('auth deny') !== -1) return true
				return false
			},

			formatOpenBluetoothError(err) {
				if (!err) return '无法打开蓝牙，请重试'
				if (err.errCode === 10001) return '请先打开手机系统蓝牙'
				if (this.isBluetoothAuthDenied(err)) return '微信蓝牙权限被拒绝'
				const msg = (err.errMsg || '').replace(/^openBluetoothAdapter:fail\s*/i, '')
				return msg && msg.length < 40 ? msg : '无法使用蓝牙，请检查蓝牙与微信权限'
			},

			runAfterAdapterOpenForBleScan(next) {
				let platform = ''
				try {
					platform = (uni.getSystemInfoSync().platform || '').toLowerCase()
				} catch (e) {
					next()
					return
				}
				if (platform !== 'android') {
					next()
					return
				}
				uni.getSetting({
					success: (st) => {
						if (st.authSetting && st.authSetting['scope.userLocation']) {
							next()
							return
						}
						uni.authorize({
							scope: 'scope.userLocation',
							success: () => next(),
							fail: () => {
								uni.showModal({
									title: '需要位置权限',
									content: '安卓手机搜索蓝牙打印机需要「位置信息」权限（微信对蓝牙扫描的要求），请允许或到设置中开启。',
									confirmText: '去设置',
									cancelText: '取消',
									success: (r) => {
										if (r.confirm) uni.openSetting({})
									}
								})
							}
						})
					},
					fail: () => next()
				})
			},

			openAdapterAndStartSearch() {
				uni.openBluetoothAdapter({
					success: () => {
						this.runAfterAdapterOpenForBleScan(() => {
							this.startDiscovery()
						})
					},
					fail: (err) => {
						console.error('openBluetoothAdapter fail', err)
						if (this.isBluetoothAuthDenied(err)) {
							uni.showModal({
								title: '蓝牙权限被拒绝',
								content: '请在系统设置里为「微信」打开蓝牙权限；也可点「去设置」进入小程序权限页尝试开启。',
								confirmText: '去设置',
								cancelText: '取消',
								success: (r) => {
									if (r.confirm) {
										if (typeof wx !== 'undefined' && wx.openAppAuthorizeSetting) {
											wx.openAppAuthorizeSetting({})
										} else {
											uni.openSetting({})
										}
									}
								}
							})
							return
						}
						uni.showToast({
							title: this.formatOpenBluetoothError(err),
							icon: 'none',
							duration: 3000
						})
					}
				})
			},

			// 点击「连接」时直接打开设备列表并开始搜索（不再弹二次确认）
			openConnectPrinter() {
				this.openAdapterAndStartSearch()
			},
			
			searchBluetoothDevices() {
				this.openAdapterAndStartSearch()
			},
			
			startDiscovery() {
				uni.showLoading({
					title: '搜索设备中...',
					mask: true
				})
				
				this.devices = []
				this.showDeviceModal = true
				
				uni.offBluetoothDeviceFound()
				
				uni.onBluetoothDeviceFound((res) => {
					if (res.devices && res.devices.length > 0) {
						res.devices.forEach(device => {
							if (!this.devices.find(d => d.deviceId === device.deviceId)) {
								this.devices.push(device)
							}
						})
						this.sortDevices()
					}
				})
				
				uni.stopBluetoothDevicesDiscovery({
					success: () => {
						this.doStartDiscovery()
					},
					fail: () => {
						this.doStartDiscovery()
					}
				})
			},
			
			doStartDiscovery() {
				uni.startBluetoothDevicesDiscovery({
					allowDuplicatesKey: false,
					interval: 0,
					success: (res) => {
						setTimeout(() => {
							uni.getBluetoothDevices({
								success: (res) => {
									if (res.devices && res.devices.length > 0) {
										res.devices.forEach(device => {
											if (!this.devices.find(d => d.deviceId === device.deviceId)) {
												this.devices.push(device)
											}
										})
										this.sortDevices()
									}
								}
							})
						}, 1000)
						
						setTimeout(() => {
							uni.stopBluetoothDevicesDiscovery()
							uni.hideLoading()
							if (this.devices.length === 0) {
								uni.showModal({
									title: '未找到设备',
									content: '未找到蓝牙打印机\n请检查打印机是否开启',
									showCancel: false
								})
							}
						}, 10000)
					},
					fail: (err) => {
						uni.hideLoading()
						let errorMsg = '搜索失败'
						if (err.errCode === 10001) errorMsg = '请打开手机蓝牙'
						else if (err.errCode === 10012) errorMsg = '蓝牙未初始化'
						
						uni.showModal({
							title: errorMsg,
							content: '错误代码：' + (err.errCode || '未知'),
							showCancel: false
						})
					}
				})
			},
			
			sortDevices() {
				this.sortedDevices = [...this.devices].sort((a, b) => {
					if (a.connectable && !b.connectable) return -1
					if (!a.connectable && b.connectable) return 1
					if (a.name && a.name !== '未知设备' && (!b.name || b.name === '未知设备')) return -1
					if ((!a.name || a.name === '未知设备') && b.name && b.name !== '未知设备') return 1
					if (a.RSSI && b.RSSI) return b.RSSI - a.RSSI
					return 0
				})
			},
			
			selectDevice(device) {
				if (!device.connectable) {
					uni.showToast({
						title: '设备不支持连接',
						icon: 'none'
					})
					return
				}
				
				this.closeDeviceModal()
				this.connectBluetooth(device.deviceId)
			},
			
			closeDeviceModal() {
				this.showDeviceModal = false
			},
			
			connectBluetooth(deviceId) {
				if (!deviceId) {
					this.searchBluetoothDevices()
					return
				}
				
				const device = this.devices.find(d => d.deviceId === deviceId)
				if (device && device.connectable === false) {
					uni.showModal({
						title: '无法连接',
						content: '该设备不支持连接',
						showCancel: false
					})
					return
				}
				
				uni.showLoading({
					title: '连接中...',
					mask: true
				})
				
				uni.stopBluetoothDevicesDiscovery({
					success: () => {
						this.doConnect(deviceId)
					},
					fail: () => {
						this.doConnect(deviceId)
					}
				})
			},
			
			doConnect(deviceId) {
				if (this.deviceId && this.deviceId !== deviceId) {
					uni.closeBLEConnection({
						deviceId: this.deviceId,
						success: () => {
							this.connectDevice(deviceId)
						},
						fail: () => {
							this.connectDevice(deviceId)
						}
					})
				} else {
					this.connectDevice(deviceId)
				}
			},
			
			connectDevice(deviceId) {
				uni.offBLEConnectionStateChange()
				
				uni.onBLEConnectionStateChange((res) => {
					if (res.deviceId === deviceId) {
						this.isConnected = res.connected
						if (!res.connected) {
							uni.showToast({
								title: '蓝牙已断开',
								icon: 'none'
							})
						}
					}
				})
				
				uni.createBLEConnection({
					deviceId: deviceId,
					timeout: 10000,
					success: (res) => {
						this.deviceId = deviceId
						this.isConnected = true
						uni.setStorageSync('bluetoothDeviceId', deviceId)
						
						setTimeout(() => {
							this.getBLEDeviceServices(deviceId)
						}, 1000)
					},
					fail: (err) => {
						uni.hideLoading()
						let errorMsg = '连接失败'
						if (err.errCode === 10003) errorMsg = '设备未找到'
						else if (err.errCode === 10004) errorMsg = '连接超时'
						else if (err.errCode === 10007) errorMsg = '连接失败'
						
						uni.showModal({
							title: errorMsg,
							content: '设备ID: ' + deviceId.substring(0, 17),
							confirmText: '重试',
							cancelText: '取消',
							success: (res) => {
								if (res.confirm) {
									this.connectBluetooth(deviceId)
								}
							}
						})
						
						uni.removeStorageSync('bluetoothDeviceId')
						this.deviceId = ''
						this.isConnected = false
					}
				})
			},
			
			getBLEDeviceServices(deviceId) {
				uni.getBLEDeviceServices({
					deviceId: deviceId,
					success: (res) => {
						const services = res.services
						if (!services || services.length === 0) {
							uni.hideLoading()
							uni.showModal({
								title: '未找到服务',
								content: '设备没有可用服务',
								showCancel: false
							})
							return
						}
						
						let serviceId = ''
						const printServiceUUIDs = [
							'0000FFE0-0000-1000-8000-00805F9B34FB',
							'FFE0',
							'0000180F-0000-1000-8000-00805F9B34FB'
						]
						
						for (let service of services) {
							const uuid = service.uuid.toUpperCase()
							if (printServiceUUIDs.some(printUUID => uuid.includes(printUUID.toUpperCase()))) {
								serviceId = service.uuid
								break
							}
						}
						
						if (!serviceId) {
							for (let service of services) {
								if (service.isPrimary) {
									serviceId = service.uuid
									break
								}
							}
						}
						
						if (!serviceId && services.length > 0) {
							serviceId = services[0].uuid
						}
						
						this.serviceId = serviceId
						
						setTimeout(() => {
							this.getBLEDeviceCharacteristics(deviceId, serviceId)
						}, 300)
					},
					fail: (err) => {
						uni.hideLoading()
						uni.showModal({
							title: '获取服务失败',
							content: '错误代码: ' + (err.errCode || '未知'),
							confirmText: '重新连接',
							cancelText: '取消',
							success: (res) => {
								if (res.confirm) {
									this.connectBluetooth(deviceId)
								}
							}
						})
					}
				})
			},
			
			getBLEDeviceCharacteristics(deviceId, serviceId) {
				uni.getBLEDeviceCharacteristics({
					deviceId: deviceId,
					serviceId: serviceId,
					success: (res) => {
						const characteristics = res.characteristics
						if (!characteristics || characteristics.length === 0) {
							uni.hideLoading()
							uni.showModal({
								title: '未找到特征值',
								content: '服务没有可用特征值',
								showCancel: false
							})
							return
						}
						
						let characteristicId = ''
						const printCharUUIDs = [
							'0000FFE1-0000-1000-8000-00805F9B34FB',
							'FFE1'
						]
						
						for (let char of characteristics) {
							const uuid = char.uuid.toUpperCase()
							if (printCharUUIDs.some(printUUID => uuid.includes(printUUID.toUpperCase()))) {
								if (char.properties.write || char.properties.writeNoResponse) {
									characteristicId = char.uuid
									break
								}
							}
						}
						
						if (!characteristicId) {
							for (let char of characteristics) {
								if (char.properties.write || char.properties.writeNoResponse) {
									characteristicId = char.uuid
									break
								}
							}
						}
						
						if (!characteristicId && characteristics.length > 0) {
							characteristicId = characteristics[0].uuid
						}
						
						this.characteristicId = characteristicId
						uni.hideLoading()
						uni.showToast({
							title: '连接成功！',
							icon: 'success',
							duration: 2000
						})
					},
					fail: (err) => {
						uni.hideLoading()
						uni.showModal({
							title: '获取特征值失败',
							content: '错误代码: ' + (err.errCode || '未知'),
							confirmText: '重新连接',
							cancelText: '取消',
							success: (res) => {
								if (res.confirm) {
									this.connectBluetooth(deviceId)
								}
							}
						})
					}
				})
			},
			
			// ========== 打印订单方法 ==========
			printOrder(item) {
				if (!this.isConnected || !this.deviceId || !this.serviceId || !this.characteristicId) {
					uni.showModal({
						title: '未连接打印机',
						content: '请先搜索并连接打印机',
						confirmText: '连接打印机',
						cancelText: '取消',
						success: (res) => {
							if (res.confirm) {
								this.openAdapterAndStartSearch()
							}
						}
					})
					return
				}

				// 选择打印类型
				uni.showActionSheet({
					itemList: ['运单(紧凑版)', '签收单(7.5×9cm)', '测试打印'],
					success: (res) => {
						this.choosePrintCopies(item, res.tapIndex)
					}
				})
			},

			// 选择打印张数：弹窗输入（微信小程序等支持 showModal editable）
			choosePrintCopies(item, typeIndex) {
				const maxCopies = 99
				uni.showModal({
					title: '打印份数',
					content: ``,
					editable: true,
					placeholderText: '1',
					confirmText: '确定',
					cancelText: '取消',
					success: (res) => {
						if (!res.confirm) return
						const raw = ((res.content != null ? String(res.content) : '') + '').trim()
						const n = parseInt(raw, 10)
						if (!raw || Number.isNaN(n) || n < 1 || n > maxCopies) {
							uni.showToast({
								title: `请输入1～${maxCopies}的整数`,
								icon: 'none'
							})
							return
						}
						this.getPrintDataFromServer(item, typeIndex, n)
					}
				})
			},
			
			// 从服务器获取GBK编码的打印数据（printNum 会传给后台 print_num）
			getPrintDataFromServer(item, typeIndex, printNum = 1) {
				uni.showLoading({
					title: '准备打印数据...',
					mask: true
				})
				
				// 如果是测试打印，不需要调用接口
				if (typeIndex === 2) {
					uni.hideLoading()
					const printContent = this.generateTestContent()
					this.previewAndPrint(printContent, typeIndex, true, printNum)
					return
				}
				
				// 调用后端接口获取打印数据
				this.$httpapi('Index/getPrintData', 'POST', {
					order_id: item.orderid,
					print_type: typeIndex === 0 ? 'waybill' : 'receipt', // waybill=运单, receipt=签收单
					print_num: printNum
				}).then(res => {
					uni.hideLoading()
					if (res.code === 1 && res.data && res.data.print_data) {
						// 后端返回的是GBK编码的base64字符串
						this.previewAndPrint(res.data.print_data, typeIndex, false, printNum)
					} else {
						uni.showToast({
							title: res.msg || '获取打印数据失败',
							icon: 'none'
						})
					}
				}).catch(err => {
					uni.hideLoading()
					console.error('获取打印数据失败:', err)
					uni.showToast({
						title: '获取打印数据失败',
						icon: 'none'
					})
				})
			},
			
			// 预览并打印（printNum：实际蓝牙输出张数）
			previewAndPrint(printContent, typeIndex, isLocalContent = false, printNum = 1) {
				const typeNames = ['运单', '签收单', '测试页']
				const num = Math.min(Math.max(parseInt(printNum, 10) || 1, 1), 99)
				const copyHint = num > 1 ? `\n份数: ${num}张` : ''
				
				// 如果是本地生成的内容（测试打印），显示预览
				if (isLocalContent) {
					const lineCount = (printContent.match(/\n/g) || []).length
					console.log(`=== ${typeNames[typeIndex]} 打印内容预览 ===`)
					console.log(`行数: ${lineCount}行`)
					console.log('内容:')
					console.log(printContent.replace(/\x1B/g, '[ESC]').replace(/\x1D/g, '[GS]').replace(/\x0A/g, '[LF]\n'))
					
					uni.showModal({
						title: '打印确认',
						content: `纸张: 7.5cm × 9cm\n类型: ${typeNames[typeIndex]}\n行数: ${lineCount}行${copyHint}\n\n确认打印？`,
						confirmText: '立即打印',
						cancelText: '取消',
						success: (res) => {
							if (res.confirm) {
								// 测试打印使用UTF-8编码
								this.sendPrintData(printContent, true, num)
							} else {
								uni.showToast({
									title: '已取消打印',
									icon: 'none'
								})
							}
						}
					})
				} else {
					// 服务器返回的base64数据，直接打印
					uni.showModal({
						title: '打印确认',
						content: `纸张: 7.5cm × 9cm\n类型: ${typeNames[typeIndex]}${copyHint}\n\n确认打印？`,
						confirmText: '立即打印',
						cancelText: '取消',
						success: (res) => {
							if (res.confirm) {
								// base64数据，使用GBK编码
								this.sendPrintData(printContent, false, num)
							} else {
								uni.showToast({
									title: '已取消打印',
									icon: 'none'
								})
							}
						}
					})
				}
			},
			
			// 生成运单内容（紧凑版，适配小票纸）
			generateWaybillContent(item) {
				const ESC = '\x1B'
				const GS = '\x1D'
				const LF = '\x0A'
				
				// 获取数据
				const waybillNo = (item.orderid || '') + ''
				const recvName = (item.unload_address && item.unload_address.name) || ''
				const recvPhone = (item.unload_address && (item.unload_address.mobile || item.unload_address.tel)) || ''
				const recvAddr = (item.unload_address && item.unload_address.address) || ''
				const sendAddr = (item.loading_address && item.loading_address.address) || ''
				const goodsName = item.goods_name || '货物'
				const weight = item.weight || '0'
				const volume = item.volume || '0'
				const pieces = item.num || '1'
				const freight = item.pay_price || '0.00'
				const createTime = item.createtime || ''
				
				let content = ''
				
				// 初始化
				content += ESC + '@'
				content += ESC + '2'          // 默认行距
				
				// 设置紧凑模式
				content += ESC + '3' + '\x10' // 设置行距为16/180英寸
				
				// 运单号（居中，稍大）
				content += ESC + 'a' + '\x01' // 居中
				content += ESC + '!' + '\x08' // 加粗
				content += '运单号：' + waybillNo + LF
				
				// 恢复左对齐，正常字体
				content += ESC + 'a' + '\x00'
				content += ESC + '!' + '\x00'
				
				// 条码（CODE128，如果支持）
				if (waybillNo.length > 0) {
					content += ESC + 'a' + '\x01' // 居中
					content += GS + 'h' + '\x28'  // 条码高度40
					content += GS + 'w' + '\x02'  // 条码宽度2
					content += GS + 'H' + '\x02'  // 条码文字在下
					const len = String.fromCharCode(waybillNo.length)
					content += GS + 'k' + '\x49' + len + waybillNo
					content += LF
					content += ESC + 'a' + '\x00' // 恢复左对齐
				}
				
				// 分隔线
				content += '--------------------' + LF
				
				// 收件信息（精简）
				content += '【收件人】' + this.truncateText(recvName, 8) + LF
				content += '电  话：' + this.truncateText(recvPhone, 11) + LF
				content += '地  址：' + this.truncateText(recvAddr, 20) + LF
				
				content += '--------------------' + LF
				
				// 寄件信息
				content += '【寄件人】' + this.truncateText(sendAddr, 20) + LF
				
				content += '--------------------' + LF
				
				// 货物信息
				content += '货  品：' + this.truncateText(goodsName, 12) + LF
				content += '件  数：' + pieces + '件'
				content += '  重  量：' + weight + 'kg' + LF
				
				// 费用信息
				content += '运  费：￥' + freight + LF
				
				// 时间信息
				content += '时  间：' + this.truncateText(createTime, 16) + LF
				
				// 状态信息
				content += '状  态：' + this.getStatusText(item.pay_status) + LF
				
				// 底部提示
				content += '--------------------' + LF
				content += '请核对后签收' + LF
				
				// 走纸和切纸（根据打印机类型调整）
				content += ESC + 'd' + '\x02'  // 走纸2行
				// content += GS + 'V' + '\x41' + '\x00' // 全切（如果支持）
				content += GS + 'V' + '\x00'  // 不切纸（撕纸型）
				
				return content
			},
			
			// 签收单紧凑版：7.5cm 宽 × 9cm 高单页（约 14 汉字/行，小行距）
			receiptWrapLines(text, lineWidth, maxLines) {
				const t = ((text || '') + '').replace(/\s+/g, ' ').trim()
				if (!t) return []
				const lines = []
				let pos = 0
				while (pos < t.length && lines.length < maxLines) {
					lines.push(t.substring(pos, pos + lineWidth))
					pos += lineWidth
				}
				if (pos < t.length && lines.length) {
					const li = lines.length - 1
					lines[li] = lines[li].substring(0, Math.max(1, lineWidth - 1)) + '…'
				}
				return lines
			},

			generateReceiptContent(item) {
				const ESC = '\x1B'
				const GS = '\x1D'
				const LF = '\x0A'
				const LW = 14

				const waybillNo = (item.orderid || '') + ''
				const recvName = (item.unload_address && item.unload_address.name) || ''
				const recvPhone = (item.unload_address && (item.unload_address.mobile || item.unload_address.tel)) || ''
				const recvAddr = (item.unload_address && item.unload_address.address) || ''
				const recvCompany = (item.unload_address && item.unload_address.company) || ''
				const sendName = (item.loading_address && item.loading_address.name) || ''
				const sendPhone = (item.loading_address && (item.loading_address.mobile || item.loading_address.tel)) || ''
				const sendAddr = (item.loading_address && item.loading_address.address) || ''
				const sendCompany = (item.loading_address && item.loading_address.company) || ''

				const goodsName = item.goods_name || '货物'
				const packType = item.pack_type || '标准'
				const weight = item.weight || '0'
				const volume = item.volume || '0'
				const pieces = item.num || '1'
				const freight = item.pay_price || '0.00'
				const declareValue = item.declare_value || '0.00'
				const serviceFee = item.service_fee || '0.00'
				const codAmount = item.cod_amount || '0.00'
				const createTime = item.createtime || ''
				const payType = item.pay_type_name || '现付'

				const sep = '--------------'

				let content = ''
				content += ESC + '@'
				content += ESC + '3' + '\x08'

				content += ESC + 'a' + '\x01'
				content += ESC + '!' + '\x08'
				content += '签收单' + LF
				content += ESC + '!' + '\x00'
				content += '单' + this.truncateText(waybillNo, LW - 1) + LF
				content += ESC + 'a' + '\x00'
				content += sep + LF

				content += '收' + this.truncateText(recvName, LW - 1) + LF
				content += this.truncateText(recvPhone, LW) + LF
				this.receiptWrapLines(recvAddr, LW, 2).forEach((ln) => {
					content += ln + LF
				})
				if (recvCompany) {
					content += this.truncateText('司:' + recvCompany, LW) + LF
				}

				content += '寄' + this.truncateText(sendName, LW - 1) + LF
				content += this.truncateText(sendPhone, LW) + LF
				this.receiptWrapLines(sendAddr, LW, 2).forEach((ln) => {
					content += ln + LF
				})
				if (sendCompany) {
					content += this.truncateText('司:' + sendCompany, LW) + LF
				}

				const goodsLine = '货' + this.truncateText(goodsName, 5) + pieces + '件' + weight + 'kg'
				content += this.truncateText(goodsLine, LW) + LF
				const packPayLine = '包' + this.truncateText(packType, 4) + '付' + this.truncateText(payType, 4) + '体' + this.truncateText(String(volume), 4)
				content += this.truncateText(packPayLine, LW) + LF

				content += this.truncateText('运' + freight, LW) + LF
				content += this.truncateText('保' + declareValue + '服' + serviceFee, LW) + LF
				content += this.truncateText(createTime, LW) + LF
				if (codAmount !== '0.00' && codAmount !== 0 && String(codAmount) !== '0') {
					content += this.truncateText('代' + codAmount, LW) + LF
				}

				content += sep + LF
				content += ESC + 'd' + '\x01'
				content += GS + 'V' + '\x00'

				return content
			},
			
			// 生成测试打印内容
			generateTestContent() {
				const ESC = '\x1B'
				const GS = '\x1D'
				const LF = '\x0A'
				
				let content = ESC + '@'
				content += ESC + '2'
				
				content += ESC + 'a' + '\x01'
				content += ESC + '!' + '\x18'
				content += '打印机测试' + LF
				content += ESC + '!' + '\x00'
				content += ESC + 'a' + '\x00'
				
				content += '====================' + LF
				content += '纸张尺寸：7.5cm × 13cm' + LF
				content += '打印时间：' + new Date().toLocaleString() + LF
				content += '====================' + LF
				
				// 测试各种字体
				content += ESC + '!' + '\x00' + '正常字体' + LF
				content += ESC + '!' + '\x08' + '加粗字体' + LF
				content += ESC + '!' + '\x10' + '倍高字体' + LF
				content += ESC + '!' + '\x20' + '倍宽字体' + LF
				content += ESC + '!' + '\x30' + '倍高宽字体' + LF
				content += ESC + '!' + '\x00'
				
				content += '====================' + LF
				content += 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' + LF
				content += 'abcdefghijklmnopqrstuvwxyz' + LF
				content += '1234567890!@#$%^&*()' + LF
				content += '====================' + LF
				
				content += ESC + 'a' + '\x01'
				content += '测试完成 ✓' + LF
				content += ESC + 'a' + '\x00'
				
				content += ESC + 'd' + '\x03'
				content += GS + 'V' + '\x00'
				
				return content
			},
			
			// 辅助方法：截断文本
			truncateText(text, maxLength) {
				if (!text) return ''
				return text.length > maxLength ? text.substring(0, maxLength) + '...' : text
			},
			
			// 辅助方法：获取状态文本
			getStatusText(status) {
				const statusMap = {
					1: '待付款',
					2: '服务中',
					3: '已完成',
					4: '已取消',
					6: '测算中',
					7: '已出价',
					8: '订单驳回'
				}
				return statusMap[status] || '未知状态'
			},
			// 查看驳回原因（pay_status==8 时）
			showRejectReason(item) {
				const reason = item.reject || '暂无驳回原因说明'
				uni.showModal({
					title: '驳回原因',
					content: reason,
					showCancel: false,
					confirmText: '知道了'
				})
			},
			
			// 发送打印数据
			// content: 打印内容（base64字符串或普通字符串）
			// isUtf8: true=使用UTF-8编码（测试打印），false=使用GBK编码（从服务器获取的base64）
			// copies: 连续打印张数（每张内容相同）
			sendPrintData(content, isUtf8 = false, copies = 1) {
				const totalCopies = Math.min(Math.max(parseInt(copies, 10) || 1, 1), 99)
				uni.showLoading({
					title: totalCopies > 1 ? `打印中 (1/${totalCopies})` : '打印中...',
					mask: true
				})
				
				let buffer
				
				if (isUtf8) {
					buffer = this.stringToUtf8Buffer(content)
				} else {
					buffer = this.base64ToArrayBuffer(content)
				}
				
				const chunkSize = 20
				const chunks = []
				for (let i = 0; i < buffer.byteLength; i += chunkSize) {
					chunks.push(buffer.slice(i, i + chunkSize))
				}
				
				let copyDone = 0
				const sendOneCopy = () => {
					let index = 0
					const sendNext = () => {
						if (index >= chunks.length) {
							copyDone++
							if (copyDone >= totalCopies) {
								uni.hideLoading()
								uni.showToast({
									title: totalCopies > 1 ? `已打印 ${totalCopies} 张` : '打印完成',
									icon: 'success'
								})
								return
							}
							uni.showLoading({
								title: `打印中 (${copyDone + 1}/${totalCopies})`,
								mask: true
							})
							setTimeout(sendOneCopy, 200)
							return
						}
						
						uni.writeBLECharacteristicValue({
							deviceId: this.deviceId,
							serviceId: this.serviceId,
							characteristicId: this.characteristicId,
							value: chunks[index],
							success: () => {
								index++
								setTimeout(sendNext, 50)
							},
							fail: (err) => {
								uni.hideLoading()
								console.error('打印失败:', err)
								uni.showToast({
									title: '打印失败',
									icon: 'none'
								})
							}
						})
					}
					sendNext()
				}
				
				sendOneCopy()
			},
			
			// Base64字符串转ArrayBuffer
			base64ToArrayBuffer(base64) {
				// 小程序环境使用uni.base64ToArrayBuffer
				if (typeof uni !== 'undefined' && uni.base64ToArrayBuffer) {
					return uni.base64ToArrayBuffer(base64)
				}
				
				// 兼容H5环境
				const binaryString = atob(base64)
				const bytes = new Uint8Array(binaryString.length)
				for (let i = 0; i < binaryString.length; i++) {
					bytes[i] = binaryString.charCodeAt(i)
				}
				return bytes.buffer
			},
			
			// 字符串转 UTF-8 ArrayBuffer（支持中文）
			stringToUtf8Buffer(str) {
				const codeUnits = []
				for (let i = 0; i < str.length; i++) {
					let codePoint = str.charCodeAt(i)
					
					// 处理代理对（高低位代理组成的 4 字节字符）
					if (codePoint >= 0xd800 && codePoint <= 0xdbff && i + 1 < str.length) {
						const next = str.charCodeAt(i + 1)
						if (next >= 0xdc00 && next <= 0xdfff) {
							codePoint = ((codePoint - 0xd800) << 10) + (next - 0xdc00) + 0x10000
							i++
						}
					}
					
					// 按 UTF-8 规则编码
					if (codePoint <= 0x7f) {
						codeUnits.push(codePoint)
					} else if (codePoint <= 0x7ff) {
						codeUnits.push(
							0xc0 | (codePoint >> 6),
							0x80 | (codePoint & 0x3f)
						)
					} else if (codePoint <= 0xffff) {
						codeUnits.push(
							0xe0 | (codePoint >> 12),
							0x80 | ((codePoint >> 6) & 0x3f),
							0x80 | (codePoint & 0x3f)
						)
					} else {
						codeUnits.push(
							0xf0 | (codePoint >> 18),
							0x80 | ((codePoint >> 12) & 0x3f),
							0x80 | ((codePoint >> 6) & 0x3f),
							0x80 | (codePoint & 0x3f)
						)
					}
				}
				
				const buffer = new ArrayBuffer(codeUnits.length)
				const view = new Uint8Array(buffer)
				for (let i = 0; i < codeUnits.length; i++) {
					view[i] = codeUnits[i]
				}
				return buffer
			},
			
			// 关闭蓝牙
			closeBluetooth() {
				if (this.deviceId) {
					uni.closeBLEConnection({
						deviceId: this.deviceId,
						success: () => {
							this.isConnected = false
							this.deviceId = ''
							this.serviceId = ''
							this.characteristicId = ''
						}
					})
				}
			}
		}
	}
</script>

<style>
	.xian {
		margin: 4rpx auto 0;
		width: 28rpx;
		height: 6rpx;
		background: linear-gradient(180deg, #4897FF 0%, #4878FF 100%);
		border-radius: 4rpx;
	}

	.buttons {
		min-width: 138rpx;
		height: 52rpx;
		background: linear-gradient(180deg, #4897FF 0%, #4878FF 100%);
		border-radius: 26rpx;
		font-size: 26rpx;
		color: #FFFFFF;
		line-height: 52rpx;
		text-align: center;
		padding: 0 20rpx;
		white-space: nowrap;
	}

	.buttonx {
		min-width: 96rpx;
		height: 52rpx;
		background: #FFFFFF;
		border-radius: 26rpx;
		border: 1rpx solid #CCCCCC;
		font-size: 26rpx;
		color: #666666;
		line-height: 52rpx;
		text-align: center;
		padding: 0 20rpx;
		white-space: nowrap;
	}

	.button-print {
		min-width: 138rpx;
		height: 52rpx;
		background: linear-gradient(180deg, #F29100 0%, #FF8C00 100%);
		border-radius: 26rpx;
		font-size: 26rpx;
		color: #FFFFFF;
		line-height: 52rpx;
		text-align: center;
		padding: 0 20rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 6rpx;
	}

	.button-print-connected {
		background: linear-gradient(180deg, #4878FF 0%, #4897FF 100%);
	}

	.print-icon {
		font-size: 28rpx;
	}

	/* 设备选择弹窗 */
	.device-modal {
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background: rgba(0, 0, 0, 0.5);
		z-index: 9999;
		display: flex;
		align-items: center;
		justify-content: center;
	}

	.device-modal-content {
		width: 680rpx;
		max-height: 80vh;
		background: #FFFFFF;
		border-radius: 24rpx;
		display: flex;
		flex-direction: column;
		overflow: hidden;
	}

	.device-modal-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 32rpx;
		border-bottom: 2rpx solid #F7F7F7;
	}

	.device-modal-title {
		font-size: 36rpx;
		font-weight: bold;
		color: #333333;
	}

	.device-modal-close {
		font-size: 48rpx;
		color: #999999;
		line-height: 1;
		width: 48rpx;
		height: 48rpx;
		display: flex;
		align-items: center;
		justify-content: center;
	}

	.device-modal-list {
		flex: 1;
		max-height: 60vh;
		padding: 0 32rpx;
	}

	.device-item {
		padding: 24rpx 0;
		border-bottom: 2rpx solid #F7F7F7;
	}

	.device-item:last-child {
		border-bottom: none;
	}

	.device-item-disabled {
		opacity: 0.5;
	}

	.device-item-info {
		display: flex;
		flex-direction: column;
		gap: 8rpx;
	}

	.device-item-name {
		display: flex;
		align-items: center;
		gap: 12rpx;
	}

	.device-status {
		width: 32rpx;
		height: 32rpx;
		border-radius: 50%;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 24rpx;
		font-weight: bold;
	}

	.device-status-ok {
		background: #4CAF50;
		color: #FFFFFF;
	}

	.device-status-no {
		background: #CCCCCC;
		color: #FFFFFF;
	}

	.device-name {
		font-size: 32rpx;
		font-weight: bold;
		color: #333333;
	}

	.device-item-id {
		font-size: 24rpx;
		color: #666666;
		margin-left: 44rpx;
	}

	.device-item-rssi {
		font-size: 24rpx;
		color: #999999;
		margin-left: 44rpx;
	}

	.device-empty {
		padding: 80rpx 0;
		text-align: center;
		color: #999999;
		font-size: 28rpx;
	}

	.device-modal-footer {
		display: flex;
		padding: 24rpx 32rpx;
		border-top: 2rpx solid #F7F7F7;
		gap: 24rpx;
	}

	.device-modal-btn {
		flex: 1;
		height: 80rpx;
		line-height: 80rpx;
		text-align: center;
		border-radius: 12rpx;
		font-size: 30rpx;
		background: #F7F7F7;
		color: #666666;
	}

	.device-modal-btn-primary {
		background: linear-gradient(180deg, #4897FF 0%, #4878FF 100%);
		color: #FFFFFF;
	}
	
	/* 响应式调整 */
	.flex {
		display: flex;
	}
	
	.flexJusp {
		display: flex;
		justify-content: space-between;
	}
</style>
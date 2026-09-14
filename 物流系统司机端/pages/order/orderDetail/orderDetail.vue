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
				<view>订单详情</view>
				<view style="width:100rpx;"></view>
			</view>
		</view>
		<view :style="'height:'+(statusBarHeight+navigationBarHeight)+'px'"></view>

		<view style="background: #FFFFFF;border-radius: 24rpx;padding:0 24rpx;margin:20rpx;">
			<view class="flexJusp" style="align-items: center;height:104rpx;border-bottom:2rpx solid #F7F7F7;">
				<view>订单编号</view>
				<view>{{details.orderid}}</view>
			</view>
			<view class="flexJusp" style="align-items: center;height:104rpx;border-bottom:2rpx solid #F7F7F7;">
				<view>订单状态</view>
				<view style="color: #F29100;">
					<text v-if="details.status==1">服务中</text>
					<text v-if="details.status==2">已完成</text>
					<text v-if="details.status==3">待接单</text>
					<text v-if="details.status==4">已取消</text>
				</view>
			</view>
			<view class="flexJusp" style="align-items: center;height:104rpx;">
				<view>车型类型</view>
				<view>{{details.car_type_id}}</view>
			</view>
		</view>

		<view style="margin:20rpx;padding:24rpx 20rpx;background: #FFFFFF;border-radius: 24rpx;">
			<view class="flex"
				style="height: 136rpx;background: #F7F7F7;border-radius: 20rpx;align-items: center;padding:0 20rpx;">
				<view
					style="width: 48rpx;height: 48rpx;background: #F29100;border-radius: 12rpx;font-weight: bold;color: #FFFFFF;line-height: 48rpx;text-align: center;">
					装</view>
				<view style="margin:0 24rpx;width:498rpx;">
					<view style="font-weight: bold;font-size: 30rpx;"> {{details.loading_address}}</view>
					<view style="font-size: 26rpx;color: #888888;margin-top:8rpx;">
						{{details.loading_mobile}}
					</view>
				</view>
			</view>
			<view class="flex"
				style="height: 136rpx;background: #F7F7F7;border-radius: 20rpx;align-items: center;padding:0 20rpx;margin-top:20rpx;">
				<view
					style="width: 48rpx;height: 48rpx;background: linear-gradient( 180deg, #4897FF 0%, #4878FF 100%);border-radius: 12rpx;font-weight: bold;color: #FFFFFF;line-height: 48rpx;text-align: center;">
					卸</view>
				<view style="margin:0 24rpx;width:498rpx;">
					<view style="font-weight: bold;font-size: 30rpx;"> {{details.unload_address}} </view>
					<view style="font-size: 26rpx;color: #888888;margin-top:8rpx;">
						{{details.unload_mobile}}
					</view>
				</view>
			</view>
		</view>

		<view style="background: #FFFFFF;border-radius: 24rpx;padding:0 24rpx;margin:20rpx;">

			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
				<view>货物类型</view>
				<view>{{details.goods_type_id}}</view>
			</view>
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
				<view>数量</view>
				<view>{{details.quantity}}</view>
			</view>
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;padding:20rpx 0;">
				<view>尺寸</view>
				<view style="flex:1;margin-left:20rpx;">
					<!-- 多尺寸模式：后端返回 details.sizeList 数组（和用户端保持一致） -->
					<view v-if="details.sizeList && details.sizeList.length">
						<view
							v-for="(item, index) in details.sizeList"
							:key="index"
							class="flex"
							style="align-items: center;margin-bottom:12rpx;"
						>
							<input
								placeholder="长"
								v-model="item.long"
								disabled="disabled"
								style="width: 120rpx;height: 60rpx;background: #F7F7F7;border-radius: 12rpx;text-align: center;"
							/>
							<view style="margin:0 12rpx;">x</view>
							<input
								placeholder="宽"
								v-model="item.wide"
								disabled="disabled"
								style="width: 120rpx;height: 60rpx;background: #F7F7F7;border-radius: 12rpx;text-align: center;"
							/>
							<view style="margin:0 12rpx;">x</view>
							<input
								placeholder="高"
								v-model="item.hige"
								disabled="disabled"
								style="width: 120rpx;height: 60rpx;background: #F7F7F7;border-radius: 12rpx;text-align: center;"
							/>
							<view style="margin-left:12rpx;">厘米</view>
						</view>
					</view>
					<!-- 单尺寸模式：兼容旧数据，只用 long / wide / hige 字段 -->
					<view v-else class="flex" style="align-items: center;">
						<input
							placeholder="长"
							v-model="details.long"
							disabled="disabled"
							style="width: 120rpx;height: 60rpx;background: #F7F7F7;border-radius: 12rpx;text-align: center;"
						/>
						<view style="margin:0 12rpx;">x</view>
						<input
							placeholder="宽"
							v-model="details.wide"
							disabled="disabled"
							style="width: 120rpx;height: 60rpx;background: #F7F7F7;border-radius: 12rpx;text-align: center;"
						/>
						<view style="margin:0 12rpx;">x</view>
						<input
							placeholder="高"
							v-model="details.hige"
							disabled="disabled"
							style="width: 120rpx;height: 60rpx;background: #F7F7F7;border-radius: 12rpx;text-align: center;"
						/>
						<view style="margin-left:12rpx;">厘米</view>
					</view>
				</view>
			</view>
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
				<view>总方位</view>
				<view class="flex" style="align-items: center;">
					<view style="height: 60rpx;background: #F7F7F7;border-radius: 12rpx;text-align: center;padding: 0 12rpx;line-height:60rpx;">{{details.direction}}</view>
					<view style="margin-left:12rpx;">立方米</view>
				</view>
			</view>
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
				<view>总重量</view>
				<view class="flex" style="align-items: center;">
					<view style="height: 60rpx;background: #F7F7F7;border-radius: 12rpx;text-align: center;padding: 0 12rpx;line-height:60rpx;">{{details.weight}}</view>
					
					<view style="margin-left:12rpx;">吨</view>
				</view>
			</view>
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;padding:20rpx 0;">
				<view>包装方式</view>
				<view style="flex:1;margin-left:20rpx;">
					<!-- 多包装模式：后端返回 details.packaging_list 数组（和用户端保持一致） -->
					<view v-if="details.packaging_list && details.packaging_list.length">
						<view
							v-for="(item, index) in details.packaging_list"
							:key="index"
							class="flex"
							style="align-items: center;margin-bottom:8rpx;"
						>
							<view>{{item.name}}</view>
							<view style="margin-left:12rpx;color:#888;">x {{item.quantity}}</view>
						</view>
					</view>
					<!-- 单包装模式：兼容旧数据，只用 packaging_id 字段 -->
					<view v-else>
						{{details.packaging_id}}
					</view>
				</view>
			</view>
			<view v-if="details.break" style="padding:20rpx 0;border-bottom:2rpx solid #F7F7F7;">
				<view style="margin-bottom:16rpx;">备注</view>
				<view style="min-height:60rpx;background:#F7F7F7;border-radius:12rpx;padding:16rpx;font-size:28rpx;color:#333;white-space: pre-wrap;word-break: break-all;">{{details.break}}</view>
			</view>
			<view class="flexJusp" style="align-items: center;height:104rpx;">
				<view>发货时间</view>
				<view>{{details.earliest_time}} - {{details.latest_time}}</view>
			</view>
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
				<view>货物图片</view>
				<image style="width:120rpx;height:120rpx;" @click="yulan" :src="imgUrl+details.goods_image"></image>
			</view>
			
			<view class="flexJusp" style="align-items: center;height:104rpx;" v-if="details.price">
				<view>原运费金额</view>
				<view style="color: #EC0000;">¥{{details.price}}</view>
			</view>
			<view v-if="details.otherprice">
				<view v-if="details.otherprice.length>0">
					<view>其他费用</view>
					<view class="flexJusp" style="align-items: center;height:104rpx;" v-for="(item,index) in details.otherprice" :key="index">
						<view>{{item.remarks}}</view>
						<view style="color: #EC0000;">¥{{item.price}}</view>
					</view>
				</view>
			</view>
			<view class="flexJusp" style="align-items: center;height:104rpx;" v-if="details.totalprice">
				<view>总运费</view>
				<view style="color: #EC0000;">¥{{details.totalprice}}</view>
			</view>
			<view v-if="details.totalprice && details.type == 3" style="padding:24rpx 0;border-top:2rpx solid #F7F7F7;">
				<view class="buttonx" style="width:100%;height:80rpx;line-height:80rpx;font-size:28rpx;" @click="getPayQrcode">生成支付二维码</view>
			</view>
		</view>
		<!-- 支付二维码弹窗 -->
		<view class="qrcode-mask" v-if="showPayQrcodeModal" @click="closePayQrcode">
			<view class="qrcode-modal" @click.stop>
				<view class="qrcode-modal-title">支付二维码</view>
				<view v-if="payQrcodeLoading" class="qrcode-loading-tip">生成中...</view>
				<template v-else-if="payQrcodeUrl">
					<image class="qrcode-img" :src="payQrcodeUrl" mode="aspectFit"></image>
					<view class="qrcode-codeurl-tip">请使用微信扫码支付</view>
				</template>
				<view class="qrcode-modal-close" @click="closePayQrcode">关闭</view>
			</view>
		</view>
		<view style="background: #FFFFFF;border-radius: 24rpx;padding:32rpx 24rpx 0;margin:20rpx;" v-if="details.status==1">
			<view>备注说明</view>
			<textarea v-model="content"
				style="width: 614rpx;height: 156rpx;background: #F7F7F7;border-radius: 20rpx;padding:24rpx;font-size:28rpx;margin:20rpx 0;"
				placeholder="请输入"></textarea>
			
			<view style="margin-top: 20rpx;">
				<view style="font-weight: bold;font-size: 30rpx;margin-bottom: 20rpx;">装货照片</view>
				<view class="flex" style="flex-wrap: wrap;">
					<view style="width:180rpx;height:180rpx;margin:0 32rpx 32rpx 0;position: relative;"
						:style="(index+1)%3==0?'margin:0 0 20rpx 0;':''" v-for="(item,index) in loadingImages" :key="'loading-'+index">
						<image style="width:180rpx;height:180rpx;display: block;border-radius: 12rpx;" mode="aspectFill"
							:src="imgUrl+item">
						</image>
						<image @click="delect(index, 'loading')" style="width:32rpx;height: 32rpx;position: absolute;top: -16rpx;right:-16rpx;"
							src="/static/images/icon_3qx.png"></image>
					</view>
					<image v-if="loadingImages.length<6" @click="upImage('loading')"
						style="width:180rpx;height: 180rpx;display: block;margin:0 0 32rpx 0;" src="/static/images/icon_35sc.png">
					</image>
				</view>
			</view>
			
			<view style="margin-top: 20rpx;">
				<view style="font-weight: bold;font-size: 30rpx;margin-bottom: 20rpx;">卸货照片</view>
				<view class="flex" style="flex-wrap: wrap;">
					<view style="width:180rpx;height:180rpx;margin:0 32rpx 32rpx 0;position: relative;"
						:style="(index+1)%3==0?'margin:0 0 20rpx 0;':''" v-for="(item,index) in unloadingImages" :key="'unloading-'+index">
						<image style="width:180rpx;height:180rpx;display: block;border-radius: 12rpx;" mode="aspectFill"
							:src="imgUrl+item">
						</image>
						<image @click="delect(index, 'unloading')" style="width:32rpx;height: 32rpx;position: absolute;top: -16rpx;right:-16rpx;"
							src="/static/images/icon_3qx.png"></image>
					</view>
					<image v-if="unloadingImages.length<6" @click="upImage('unloading')"
						style="width:180rpx;height: 180rpx;display: block;margin:0 0 32rpx 0;" src="/static/images/icon_35sc.png">
					</image>
				</view>
			</view>
		</view>
		<view class="buttonss" v-if="details.status==1" @click="queding">完成订单</view>

		<view v-if="details.status!=2" style="height:180rpx;"></view>
		<view v-else style="height:60rpx;"></view>
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
				banner: [],
				notice: [],
				carType: 0,
				kaipiao: 0,
				array: ['类型一'],
				index: 0,
				daishou: 0,
				num: 1,
				yaoqiu: 0,
				check: false,
				scrollTop: 0,
				id: '',
				type: '',
				details: {},
				content: '',
				loadingImages: [],
				unloadingImages: [],
				showPayQrcodeModal: false,
				payQrcodeUrl: '',
				payQrcodeLoading: false
			}
		},
		onLoad(option) {
			this.id = option.id
			this.type = option.type || ''
			this.getSystemInfo()
			// 先从本地存储加载照片
			this.loadLocalImages()
			this.loads()
		},
		onPageScroll(event) {
			const scrollTop = event.scrollTop // 获取当前页面滚动高度
			this.scrollTop = event.scrollTop
		},
		watch: {
			// 监听备注内容变化，自动保存
			content(newVal) {
				this.saveLocalImages()
			}
		},
		methods: {
			yulan(){
				var list = []
				list.push(this.imgUrl + this.details.goods_image)
				console.log(list, '预览图片')
				uni.previewImage({
					current: 0,
					urls: list,
					longPressActions: {
						success: function(data) {
							console.log('选中了第' + (data.tapIndex + 1) + '个按钮,第' + (data.index + 1) + '张图片');
						},
						fail: function(err) {
							console.log(err.errMsg);
						}
					}
				});
			},
			delect(index, type) {
				if (type === 'loading') {
					this.loadingImages.splice(index, 1)
				} else if (type === 'unloading') {
					this.unloadingImages.splice(index, 1)
				}
				// 删除后保存到本地
				this.saveLocalImages()
			},
			upImage(type) {
				var that = this
				const currentImages = type === 'loading' ? this.loadingImages : this.unloadingImages
				uni.chooseImage({
					count: 6 - currentImages.length, //默认9
					sizeType: ['original', 'compressed'], //可以指定是原图还是压缩图，默认二者都有
					sourceType: ['album', 'camera'], //从相册选择
					success: function(res) {
						console.log(res.tempFilePaths)
						for (let i = 0; i < res.tempFilePaths.length; i++) {
							console.log(res.tempFilePaths[i], '-----山川数据')
							that.$upShop('common/upload', res.tempFilePaths[i]).then(rex => {
								let arr = JSON.parse(rex.data)
								if (type === 'loading') {
									that.loadingImages.push(arr.data.url)
								} else if (type === 'unloading') {
									that.unloadingImages.push(arr.data.url)
								}
								// 上传成功后保存到本地
								that.saveLocalImages()
							}).catch((e) => {});
						}
					}
				})
			},
			tuichu() {
				uni.navigateBack()
			},
			queding() {
				this.$httpapi('Dricer/confirmorder', 'POST', {
					id: this.details.orderid,
					content: this.content,
					loading_images: this.loadingImages.toString(),
					unloading_images: this.unloadingImages.toString()
				}).then(res => {
					uni.showToast({
						title: res.msg,
						duration: 1500,
						icon: 'none'
					});
					// 提交成功后清除本地存储（因为已经保存到后端了）
					this.clearLocalImages()
					this.loads()
				})
			},
			guanbi() {
				this.$refs.popup.close()
			},
			fahuo() {
				this.$refs.popup.open('bottom')
			},
			bindPickerChange(e) {
				this.index = e.detail.value
			},
			// 保存照片到本地存储
			saveLocalImages() {
				const storageKey = `order_images_${this.id}`
				const data = {
					loadingImages: this.loadingImages,
					unloadingImages: this.unloadingImages,
					content: this.content
				}
				try {
					uni.setStorageSync(storageKey, data)
				} catch (e) {
					console.error('保存照片失败', e)
				}
			},
			// 从本地存储加载照片
			loadLocalImages() {
				const storageKey = `order_images_${this.id}`
				try {
					const data = uni.getStorageSync(storageKey)
					if (data) {
						if (data.loadingImages && data.loadingImages.length > 0) {
							this.loadingImages = data.loadingImages
						}
						if (data.unloadingImages && data.unloadingImages.length > 0) {
							this.unloadingImages = data.unloadingImages
						}
						if (data.content) {
							this.content = data.content
						}
					}
				} catch (e) {
					console.error('加载照片失败', e)
				}
			},
			// 清除本地存储的照片
			clearLocalImages() {
				const storageKey = `order_images_${this.id}`
				try {
					uni.removeStorageSync(storageKey)
				} catch (e) {
					console.error('清除照片失败', e)
				}
			},
			// 生成支付二维码（调用 Wechatpay/payQrcode）
			getPayQrcode() {
				if (!this.id) {
					uni.showToast({ title: '订单信息异常', icon: 'none' })
					return
				}
				this.showPayQrcodeModal = true
				this.payQrcodeLoading = true
				this.payQrcodeUrl = ''
				this.$httpapi('Wechatpay/payQrcode', 'POST', {
					order_id: this.id
				}).then(res => {
					this.payQrcodeLoading = false
					if (res.code === 1 && res.data) {
						// 后端直接返回二维码图片链接（如 url / qrcode_url 等）
						const imgUrl = res.data.url || res.data.qrcode_url || ''
						if (imgUrl) {
							this.payQrcodeUrl = imgUrl.indexOf('http') === 0 ? imgUrl : (this.imgUrl + imgUrl)
						} else {
							uni.showToast({ title: res.msg || '未返回二维码数据', icon: 'none' })
						}
					} else {
						uni.showToast({ title: res.msg || '生成失败', icon: 'none' })
					}
				}).catch(() => {
					this.payQrcodeLoading = false
					uni.showToast({ title: '生成失败', icon: 'none' })
				})
			},
			closePayQrcode() {
				this.showPayQrcodeModal = false
				this.payQrcodeUrl = ''
			},
			loads() {
				this.$httpapi('Dricer/OrderlistOrderDetail', 'POST', {
					order_id: this.id,
					type: this.type
				}).then(res => {
					res.data.latest_time = res.data.latest_time.slice(10,19)
					this.details = res.data
					// 优先使用后端返回的照片数据（如果后端有数据）
					let hasBackendImages = false
					if (res.data.loading_images) {
						// 如果是字符串，转换为数组
						if (typeof res.data.loading_images === 'string' && res.data.loading_images) {
							this.loadingImages = res.data.loading_images.split(',').filter(item => item.trim())
							hasBackendImages = true
						} else if (Array.isArray(res.data.loading_images)) {
							this.loadingImages = res.data.loading_images
							hasBackendImages = true
						}
					}
					if (res.data.unloading_images) {
						// 如果是字符串，转换为数组
						if (typeof res.data.unloading_images === 'string' && res.data.unloading_images) {
							this.unloadingImages = res.data.unloading_images.split(',').filter(item => item.trim())
							hasBackendImages = true
						} else if (Array.isArray(res.data.unloading_images)) {
							this.unloadingImages = res.data.unloading_images
							hasBackendImages = true
						}
					}
					// 兼容旧数据格式（如果后端返回的是 images 字段）
					if (res.data.images && !res.data.loading_images && !res.data.unloading_images) {
						if (typeof res.data.images === 'string' && res.data.images) {
							this.loadingImages = res.data.images.split(',').filter(item => item.trim())
							hasBackendImages = true
						} else if (Array.isArray(res.data.images)) {
							this.loadingImages = res.data.images
							hasBackendImages = true
						}
					}
					// 如果后端有照片数据，更新本地存储
					if (hasBackendImages) {
						this.saveLocalImages()
					}
					// 如果后端有备注，使用后端的备注
					if (res.data.content) {
						this.content = res.data.content
					}
				})
			}
		}
	}
</script>

<style lang="scss">
	.post {
		position: fixed;
		bottom: 0;
		left: 0;
		width: 750rpx;
		background-color: #FFF;
	}

	.buttonss {
		position: fixed;
		left: 30rpx;
		bottom: 80rpx;
		// margin: 72rpx 30rpx 20rpx;
		width: 690rpx;
		height: 88rpx;
		background: linear-gradient(180deg, #4897FF 0%, #4878FF 100%);
		border-radius: 46rpx;
		font-weight: bold;
		font-size: 30rpx;
		color: #FFFFFF;
		line-height: 88rpx;
		text-align: center;
	}

	.buttons {
		width: 160rpx;
		height: 56rpx;
		background: linear-gradient(180deg, #4897FF 0%, #4878FF 100%);
		border-radius: 32rpx;
		font-size: 26rpx;
		color: #FFFFFF;
		line-height: 56rpx;
		text-align: center;
	}

	.buttonx {
		width: 160rpx;
		height: 56rpx;
		background: #FFFFFF;
		border-radius: 32rpx;
		border: 1rpx solid #4878FF;
		font-size: 26rpx;
		color: #4878FF;
		line-height: 56rpx;
		text-align: center;
	}

	.qrcode-mask {
		position: fixed;
		left: 0;
		top: 0;
		right: 0;
		bottom: 0;
		background: rgba(0, 0, 0, 0.5);
		z-index: 999;
		display: flex;
		align-items: center;
		justify-content: center;
	}
	.qrcode-modal {
		width: 560rpx;
		background: #FFF;
		border-radius: 24rpx;
		padding: 32rpx;
		text-align: center;
	}
	.qrcode-modal-title {
		font-weight: bold;
		font-size: 32rpx;
		margin-bottom: 24rpx;
	}
	.qrcode-loading-tip {
		font-size: 28rpx;
		color: #888;
		padding: 60rpx 0;
	}
	.qrcode-img {
		width: 400rpx;
		height: 400rpx;
		display: block;
		margin: 0 auto 24rpx;
	}
	.qrcode-codeurl-tip {
		font-size: 26rpx;
		color: #666;
		padding: 40rpx 0;
	}
	.qrcode-modal-close {
		margin-top: 16rpx;
		height: 72rpx;
		line-height: 72rpx;
		background: #F7F7F7;
		border-radius: 36rpx;
		font-size: 28rpx;
		color: #333;
	}
</style>
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
				<view>{{details.order_id}}</view>
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
			<!-- <view class="flexJusp" style="align-items: center;height:104rpx;">
				<view>车型类型</view>
				<view>{{details.car_type_id}}</view>
			</view> -->
		</view>

		<view style="margin:20rpx;padding:24rpx 20rpx;background: #FFFFFF;border-radius: 24rpx;">
			<view class="flex"
				style="height: 136rpx;background: #F7F7F7;border-radius: 20rpx;align-items: center;padding:0 20rpx;">
				<view
					style="width: 48rpx;height: 48rpx;background: #F29100;border-radius: 12rpx;font-weight: bold;color: #FFFFFF;line-height: 48rpx;text-align: center;">
					装</view>
				<view style="margin:0 24rpx;width:498rpx;">
					<view style="font-weight: bold;font-size: 30rpx;"> {{details.logistics.start_address}}</view>
					<view style="font-size: 26rpx;color: #888888;margin-top:8rpx;">
						{{details.logistics.start_mobile}}
					</view>
				</view>
			</view>
			<view class="flex"
				style="height: 136rpx;background: #F7F7F7;border-radius: 20rpx;align-items: center;padding:0 20rpx;margin-top:20rpx;">
				<view
					style="width: 48rpx;height: 48rpx;background: linear-gradient( 180deg, #4897FF 0%, #4878FF 100%);border-radius: 12rpx;font-weight: bold;color: #FFFFFF;line-height: 48rpx;text-align: center;">
					卸</view>
				<view style="margin:0 24rpx;width:498rpx;">
					<view style="font-weight: bold;font-size: 30rpx;"> {{details.logistics.end_address}} </view>
					<view style="font-size: 26rpx;color: #888888;margin-top:8rpx;">
						{{details.logistics.end_mobile}}
					</view>
				</view>
			</view>
		</view>

		<view style="background: #FFFFFF;border-radius: 24rpx;padding:0 24rpx;margin:20rpx;">
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
				<view>货物类型</view>
				<view class="flex" style="align-items: center;">
					<view
						style="height: 60rpx;background: #F7F7F7;border-radius: 12rpx;text-align: center;padding:0 12rpx;line-height:60rpx;">
						{{details.goods_type_id}}
					</view>
				</view>
			</view>
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
				<view>货物图片</view>
				<image style="width:120rpx;height:120rpx;" @click="yulan" :src="imgUrl+details.goods_image"></image>
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
				<view>总重量</view>
				<view class="flex" style="align-items: center;">
					<input placeholder="0" v-model="details.weight" disabled="disabled"
						style="width: 120rpx;height: 60rpx;background: #F7F7F7;border-radius: 12rpx;text-align: center;" />
					<view style="margin-left:12rpx;">吨</view>
				</view>
			</view>
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
				<view>总方位</view>
				<view class="flex" style="align-items: center;">
					<input placeholder="0" v-model="details.direction" disabled="disabled"
						style="width: 120rpx;height: 60rpx;background: #F7F7F7;border-radius: 12rpx;text-align: center;" />
					<view style="margin-left:12rpx;">立方米</view>
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
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
				<view>价格</view>
				<view style="color: #EC0000;">¥{{details.price}}</view>
			</view>
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
				<view>时效</view>
				<view >{{details.logistics.time_limit}}天</view>
			</view>
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
				<view>开始时间</view>
				<view >{{details.truckstarttime}}</view>
			</view>
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
				<view>完成时间</view>
				<view >{{details.truckendtime}}</view>
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

	<!-- 	<view class="post" v-if="details.status==0">
			<view class="flexJusp" style="padding:20rpx 24rpx;">
				<view></view>
				<view class="flex">
					<view class="buttonx">取消订单</view>
					<view class="buttons" style="margin-left:16rpx;">立即接单</view>
				</view>
			</view>
		</view> -->

		<view v-if="details.status!=2" style="height:180rpx;"></view>
		<view v-else style="height:20rpx;"></view>
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
				details: {},
				content: '',
				loadingImages: [],
				unloadingImages: [],
				ids: ''
			}
		},
		onLoad(option) {
			this.id = option.id
			this.ids = option.ids
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
				this.$httpapi('Dedicated/confirmorder', 'POST', {
					id: this.ids,
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
			loads() {
				this.$httpapi('Dedicated/orderdetail', 'POST', {
					order_id: this.id
				}).then(res => {
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
</style>
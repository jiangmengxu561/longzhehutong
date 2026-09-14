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
				<view>投诉意见</view>
				<view style="width:100rpx;"></view>
			</view>
		</view>
		<view :style="'height:'+(statusBarHeight+navigationBarHeight)+'px'"></view>
		<view style="margin:36rpx 24rpx 20rpx;font-weight: bold;"><text style="color: #CF2D22;">*</text> 内容描述</view>
		<textarea
			style="width: 662rpx;height: 204rpx;background: #FFFFFF;border-radius: 20rpx;font-size:28rpx;padding:20rpx;margin:0 24rpx;"
			v-model="content" maxlength="-1" placeholder="请输入..."></textarea>
		<view style="margin:32rpx 24rpx 24rpx;font-weight: bold;">截图（选填）</view>
		<image @click="upImage" style="width: 166rpx;height:166rpx;margin:0 24rpx;border-radius:12rpx;" :src="image?imgUrl+image:'/static/images/icon_26sc.png'"></image>
		<view class="buttons" @click="addAddress">提交</view>
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
				imgUrl:this.$utils.imgUrl,
				scrollTop: 0,
				content: '',
				image: '',
			};
		},
		onLoad(option) {
			this.getSystemInfo()
		},
		onPageScroll(event) {
			const scrollTop = event.scrollTop // 获取当前页面滚动高度
			this.scrollTop = event.scrollTop
		},
		methods: {
			upImage(){
				var that = this
				uni.chooseImage({
					count: 1, //默认9
					sizeType: ['original', 'compressed'], //可以指定是原图还是压缩图，默认二者都有
					sourceType: ['album','camera'], //从相册选择
					success: function (res) {
						that.$upShop('common/upload',res.tempFilePaths[0]).then(rex => {
							let arr = JSON.parse(rex.data)
							that.image = arr.data.url
						}).catch((e) => {
						});
					}
				})
			},
			tuichu() {
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
				this.visible = true
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
					}
				})
			},
			addAddress() {
				if (this.content == '') return this.$utils.showtt('请输入反馈内容')
				this.$httpapi('Opinion/feedback', 'POST', {
					content: this.content,
					image: this.image,
				}).then(res => {
					uni.showToast({
						title: res.msg,
						duration: 1500,
						icon: 'none'
					});
					if (res.code == 1) {
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

		.buttons {
			position: fixed;
			left: 30rpx;
			bottom: 88rpx;
			width: 690rpx;
			height: 88rpx;
			background: linear-gradient(180deg, #4897FF 0%, #4878FF 100%);
			border-radius: 50rpx;
			font-weight: bold;
			font-size: 30rpx;
			color: #FFFFFF;
			line-height: 88rpx;
			text-align: center;
		}
	}
</style>
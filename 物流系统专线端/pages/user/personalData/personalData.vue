<template>
	<view class="personal">
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
				<view>个人资料</view>
				<view style="width:100rpx;"></view>
			</view>
		</view>
		<view :style="'height:'+(statusBarHeight+navigationBarHeight)+'px'"></view>
		<view style="margin:40rpx auto 0;width:168rpx;height:168rpx;position:relative;">
			<image style="width:168rpx;height:168rpx;border-radius:50%;" mode="aspectFill" :src="imgUrl+avatar"></image>
			<image @click="upImage" style="width:52rpx;height:52rpx; position:absolute;bottom:0;right:0;" src="/static/images/icon_22sc.png"></image>
		</view>
		<view class="label" style="margin:92rpx 36rpx 60rpx;">
			<view>昵称</view>
			<input v-model="username" placeholder="请输入昵称" placeholder-style="color:#CCCCCC" style="text-align: right;" />
		</view>
		<view class="label" style="margin:0 36rpx 60rpx;">
			<view>手机号</view>
			<input disabled="disabled" maxlength="11" v-model="mobile" type="number" placeholder="请输入手机号" placeholder-style="color:#CCCCCC" style="text-align: right;" />
		</view>
		<view class="buttons" @click="editUser">保存</view>
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
				user:{},
				type:1,
				scrollTop: 0,
				details:{},
				avatar:'',
				username:'',
				mobile:''
			};
		},
		onLoad() {
			this.getSystemInfo()
			this.$httpapi('Opinion/getuserinfo', 'GET', {}).then(res => {
				this.avatar = res.data.avatar
				this.username = res.data.username
				this.mobile = res.data.mobile
			})
		},
		onPageScroll(event) {
			const scrollTop = event.scrollTop // 获取当前页面滚动高度
			this.scrollTop = event.scrollTop
		},
		methods:{
			tuichu(){
				uni.navigateBack()
			},
			editUser(){
				this.$httpapi('Opinion/updateuserinfo', 'POST',{
					avatar:this.avatar,
					username:this.username,
				}).then(res => {
					uni.showToast({
						title: res.msg,
						duration: 1500,
						icon:'none'
					});
				})
			},
			upImage(){
				var that = this
				uni.chooseImage({
					count: 1, //默认9
					sizeType: ['original', 'compressed'], //可以指定是原图还是压缩图，默认二者都有
					sourceType: ['album','camera'], //从相册选择
					success: function (res) {
						that.$upShop('common/upload',res.tempFilePaths[0]).then(rex => {
							let arr = JSON.parse(rex.data)
							that.avatar = arr.data.url
						}).catch((e) => {
						});
					}
				})
			}
		}
	}
</script>

<style lang="scss">
	.personal{
		.label{
			display: flex;
			justify-content: space-between;
			font-size: 30rpx;
			color: #333333;
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

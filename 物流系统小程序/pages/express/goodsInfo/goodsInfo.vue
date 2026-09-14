<template>
	<view class="content">
		<!-- 顶部自定义导航 -->
		<view class="nav" :style="'padding-top:'+statusBarHeight+'px'">
			<view class="nav-inner" :style="'height:'+navigationBarHeight+'px;line-height:'+navigationBarHeight+'px'">
				<view class="nav-back" @click="goBack">
					<image class="nav-back-icon" src="/static/images/mv_tuichu.png"></image>
				</view>
				<view class="nav-title">物品信息</view>
				<view class="nav-right"></view>
			</view>
		</view>
		<view :style="'height:'+(statusBarHeight+navigationBarHeight)+'px'"></view>

		<view class="card">
			<view class="row">
				<view class="label">物品类型</view>
				<picker :range="goodsTypeList" :value="goodsTypeIndex" @change="onGoodsTypeChange">
					<view class="value value--picker">
						{{ goodsTypeList[goodsTypeIndex] || '请选择' }}
						<image class="chev" src="/static/images/icon_2jt.png"></image>
					</view>
				</picker>
			</view>
			<view class="row">
				<view class="label">重量(kg)</view>
				<input v-model="form.weight" type="digit" placeholder="如：1.2" placeholder-style="color:#bbb" class="input" />
			</view>
			<view class="row">
				<view class="label">体积(选填)</view>
				<view class="value-inline">
					<input v-model="form.len" type="digit" placeholder="长" placeholder-style="color:#bbb" class="input input--mini" />
					<text class="x">×</text>
					<input v-model="form.wid" type="digit" placeholder="宽" placeholder-style="color:#bbb" class="input input--mini" />
					<text class="x">×</text>
					<input v-model="form.hig" type="digit" placeholder="高" placeholder-style="color:#bbb" class="input input--mini" />
					<text class="unit">cm</text>
				</view>
			</view>
			<view class="row row--column">
				<view class="label">备注(选填)</view>
				<textarea v-model="form.remark" placeholder="如：易碎品/上门取件时间等" placeholder-style="color:#bbb" class="textarea"></textarea>
			</view>
		</view>

		<view class="footer-space"></view>
		<view class="footer">
			<view class="submit" @click="done">完成</view>
		</view>
	</view>
</template>

<script>
	import { systemInfo } from '@/https/mixin.js'
	const GOODS_INFO_STORAGE_KEY = 'sendExpress_goodsInfo_v1'
	export default {
		mixins: [systemInfo],
		data() {
			return {
				goodsTypeList: ['文件', '日用品', '食品', '数码产品', '服饰', '其他'],
				goodsTypeIndex: 0,
				form: {
					weight: '',
					len: '',
					wid: '',
					hig: '',
					remark: ''
				}
			}
		},
		onLoad() {
			this.getSystemInfo()
			this.loadFromStorage()
		},
		methods: {
			goBack() {
				uni.navigateBack()
			},
			loadFromStorage() {
				const saved = uni.getStorageSync(GOODS_INFO_STORAGE_KEY)
				if (!saved || typeof saved !== 'object') return
				if (typeof saved.goodsTypeIndex === 'number') this.goodsTypeIndex = saved.goodsTypeIndex
				if (saved.form && typeof saved.form === 'object') this.form = { ...this.form, ...saved.form }
			},
			onGoodsTypeChange(e) {
				this.goodsTypeIndex = Number(e.detail.value || 0)
			},
			done() {
				uni.setStorageSync(GOODS_INFO_STORAGE_KEY, {
					goodsTypeIndex: this.goodsTypeIndex,
					form: { ...this.form }
				})
				uni.navigateBack()
			}
		}
	}
</script>

<style scoped>
	.content{
		min-height: 100vh;
		background: #F7F8FA;
	}
	.nav{
		position: fixed;
		top: 0;
		left: 0;
		width: 750rpx;
		z-index: 10;
		background: #FFFFFF;
	}
	.nav-inner{
		display: flex;
		align-items: center;
		justify-content: space-between;
	}
	.nav-back{
		width: 120rpx;
		display: flex;
		align-items: center;
		justify-content: center;
	}
	.nav-back-icon{
		width: 20rpx;
		height: 34rpx;
	}
	.nav-title{
		font-weight: bold;
		font-size: 34rpx;
	}
	.nav-right{ width: 120rpx; }

	.card{
		margin: 20rpx;
		background: #FFFFFF;
		border-radius: 24rpx;
		overflow: hidden;
	}
	.row{
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 22rpx 20rpx;
		border-bottom: 2rpx solid #F2F2F2;
	}
	.row:last-child{ border-bottom: 0; }
	.row--column{
		display: block;
	}
	.label{
		font-size: 28rpx;
		color: #333;
		flex-shrink: 0;
	}
	.value{
		display: flex;
		align-items: center;
		justify-content: flex-end;
		font-size: 28rpx;
		color: #333;
	}
	.value--picker{
		min-width: 240rpx;
	}
	.input{
		flex: 1;
		text-align: right;
		font-size: 28rpx;
		color: #111;
	}
	.value-inline{
		display: flex;
		align-items: center;
		justify-content: flex-end;
		flex: 1;
		gap: 10rpx;
	}
	.input--mini{
		flex: 0 0 120rpx;
		text-align: center;
		background: #F7F7F7;
		border-radius: 12rpx;
		height: 60rpx;
		line-height: 60rpx;
	}
	.x{ color: #999; }
	.unit{ color: #666; margin-left: 6rpx; }
	.textarea{
		width: 100%;
		min-height: 160rpx;
		margin-top: 16rpx;
		background: #F7F7F7;
		border-radius: 16rpx;
		padding: 16rpx;
		box-sizing: border-box;
		font-size: 28rpx;
		color: #111;
	}
	.chev{
		width: 20rpx;
		height: 20rpx;
	}
	.footer-space{ height: 140rpx; }
	.footer{
		position: fixed;
		left: 0;
		bottom: 0;
		width: 750rpx;
		padding: 16rpx 30rpx 30rpx;
		box-sizing: border-box;
		background: #FFFFFF;
		border-top: 2rpx solid #F2F2F2;
	}
	.submit{
		height: 88rpx;
		border-radius: 46rpx;
		background: linear-gradient(180deg, #4897FF 0%, #4878FF 100%);
		color: #FFFFFF;
		font-weight: bold;
		font-size: 30rpx;
		line-height: 88rpx;
		text-align: center;
	}
</style>


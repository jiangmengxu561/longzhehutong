<template>
	<view class="content">
		<!-- 顶部自定义导航 -->
		<view class="nav" :style="'padding-top:'+statusBarHeight+'px'">
			<view class="nav-inner" :style="'height:'+navigationBarHeight+'px;line-height:'+navigationBarHeight+'px'">
				<view class="nav-back" @click="goBack">
					<image class="nav-back-icon" src="/static/images/mv_tuichu.png"></image>
				</view>
				<view class="nav-title">发快递</view>
				<view class="nav-right"></view>
			</view>
		</view>
		<view :style="'height:'+(statusBarHeight+navigationBarHeight)+'px'"></view>

		<!-- 寄/收 -->
		<view class="card">
			<view class="addr-row" @click="pickAddress('sender')">
				<view class="addr-tag addr-tag--send">寄</view>
				<view class="addr-main">
					<view v-if="sender.name" class="addr-top">
						<text class="addr-name">{{sender.name}}</text>
						<text class="addr-phone">{{sender.phone}}</text>
					</view>
					<view v-if="sender.address" class="addr-bottom">{{sender.address}}</view>
					<view v-if="!sender.address" class="addr-empty">请选择寄件地址</view>
				</view>
				<image class="chev" src="/static/images/icon_2jt.png"></image>
			</view>
			<view class="addr-split"></view>
			<view class="addr-row" @click="pickAddress('receiver')">
				<view class="addr-tag addr-tag--recv">收</view>
				<view class="addr-main">
					<view v-if="receiver.name" class="addr-top">
						<text class="addr-name">{{receiver.name}}</text>
						<text class="addr-phone">{{receiver.phone}}</text>
					</view>
					<view v-if="receiver.address" class="addr-bottom">{{receiver.address}}</view>
					<view v-if="!receiver.address" class="addr-empty">请选择收件地址</view>
				</view>
				<image class="chev" src="/static/images/icon_2jt.png"></image>
			</view>
		</view>

		<!-- 物品信息 -->
		<view class="card">
			<view class="row row--clickable" @click="openGoodsInfo">
				<view class="label">物品信息</view>
				<view class="value value--summary">
					<text class="summary-text">{{ goodsInfoSummary }}</text>
					<image class="chev" src="/static/images/icon_2jt.png"></image>
				</view>
			</view>
		</view>

		<!-- 快递公司/服务 -->
		<view class="card">
			<view class="row">
				<view class="label">快递公司</view>
				<picker :range="companyList" :value="companyIndex" @change="onCompanyChange">
					<view class="value value--picker">
						{{ companyList[companyIndex] || '请选择' }}
						<image class="chev" src="/static/images/icon_2jt.png"></image>
					</view>
				</picker>
			</view>
			<view class="row">
				<view class="label">保价(元)</view>
				<input v-model="form.insured" type="digit" placeholder="不保价可不填" placeholder-style="color:#bbb" class="input" />
			</view>
			<view class="row">
				<view class="label">上门取件</view>
				<switch :checked="form.pickup" @change="form.pickup = $event.detail.value" color="#4878FF" />
			</view>
		</view>

		<view class="footer-space"></view>
		<view class="footer">
			<view class="submit" @click="submit">提交寄件</view>
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
				sender: { name: '', phone: '', address: '' },
				receiver: { name: '', phone: '', address: '' },
				goodsTypeList: ['文件', '日用品', '食品', '数码产品', '服饰', '其他'],
				goodsTypeIndex: 0,
				companyList: ['韵达', '中通', '圆通', '申通', '顺丰', '京东'],
				companyIndex: 0,
				form: {
					weight: '',
					len: '',
					wid: '',
					hig: '',
					remark: '',
					insured: '',
					pickup: true
				}
			}
		},
		computed: {
			goodsInfoSummary() {
				const typeText = this.goodsTypeList[this.goodsTypeIndex] || '未选类型'
				const weightText = this.form.weight ? `${this.form.weight}kg` : '未填重量'
				const hasVolume = this.form.len || this.form.wid || this.form.hig
				const volumeText = hasVolume ? `${this.form.len || '-'}×${this.form.wid || '-'}×${this.form.hig || '-'}cm` : '未填体积'
				return `${typeText} / ${weightText} / ${volumeText}`
			}
		},
		onLoad() {
			this.getSystemInfo()
		},
		onShow() {
			this.syncGoodsInfoFromStorage()
		},
		methods: {
			goBack() {
				uni.navigateBack()
			},
			openGoodsInfo() {
				this.saveGoodsInfoToStorage()
				uni.navigateTo({
					url: '/pages/express/goodsInfo/goodsInfo'
				})
			},
			saveGoodsInfoToStorage() {
				uni.setStorageSync(GOODS_INFO_STORAGE_KEY, {
					goodsTypeIndex: this.goodsTypeIndex,
					form: { ...this.form }
				})
			},
			syncGoodsInfoFromStorage() {
				const saved = uni.getStorageSync(GOODS_INFO_STORAGE_KEY)
				if (!saved || typeof saved !== 'object') return
				if (typeof saved.goodsTypeIndex === 'number') this.goodsTypeIndex = saved.goodsTypeIndex
				if (saved.form && typeof saved.form === 'object') this.form = { ...this.form, ...saved.form }
			},
			pickAddress(type) {
				// 先做成占位交互：后续可对接你现有的地址簿页
				uni.showToast({
					title: '这里可对接地址簿选择',
					icon: 'none',
					duration: 1500
				})
				// 示例：快速填充，便于你预览 UI（不影响正式逻辑）
				if (type === 'sender') {
					this.sender = { name: '寄件人', phone: '13800000000', address: '请在此处选择/填写寄件地址' }
				} else {
					this.receiver = { name: '收件人', phone: '13900000000', address: '请在此处选择/填写收件地址' }
				}
			},
			onCompanyChange(e) {
				this.companyIndex = Number(e.detail.value || 0)
			},
			submit() {
				if (!this.sender.address) {
					uni.showToast({ title: '请选择寄件地址', icon: 'none' })
					return
				}
				if (!this.receiver.address) {
					uni.showToast({ title: '请选择收件地址', icon: 'none' })
					return
				}
				if (!this.form.weight) {
					uni.showToast({ title: '请填写重量', icon: 'none' })
					return
				}
				uni.showToast({
					title: '已提交（示例页面）',
					icon: 'none',
					duration: 1500
				})
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
	.addr-row{
		display: flex;
		align-items: center;
		padding: 22rpx 20rpx;
	}
	.addr-split{
		height: 2rpx;
		background: #F2F2F2;
		margin: 0 20rpx;
	}
	.addr-tag{
		width: 56rpx;
		height: 56rpx;
		border-radius: 16rpx;
		color: #FFFFFF;
		font-weight: bold;
		font-size: 28rpx;
		line-height: 56rpx;
		text-align: center;
		flex-shrink: 0;
	}
	.addr-tag--send{ background: #F29100; }
	.addr-tag--recv{ background: linear-gradient(180deg, #4897FF 0%, #4878FF 100%); }
	.addr-main{
		flex: 1;
		min-width: 0;
		margin: 0 16rpx;
	}
	.addr-top{
		display: flex;
		align-items: baseline;
		gap: 16rpx;
	}
	.addr-name{ font-weight: bold; font-size: 30rpx; color: #111; }
	.addr-phone{ font-size: 26rpx; color: #666; }
	.addr-bottom{
		margin-top: 8rpx;
		font-size: 26rpx;
		color: #333;
	}
	.addr-empty{
		font-size: 28rpx;
		color: #999;
	}
	.row{
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 22rpx 20rpx;
		border-bottom: 2rpx solid #F2F2F2;
	}
	.row:last-child{ border-bottom: 0; }
	.row--clickable{ }
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
	.value--summary{
		flex: 1;
		min-width: 0;
	}
	.summary-text{
		flex: 1;
		min-width: 0;
		overflow: hidden;
		white-space: nowrap;
		text-overflow: ellipsis;
		text-align: right;
		margin-right: 10rpx;
		color: #666;
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


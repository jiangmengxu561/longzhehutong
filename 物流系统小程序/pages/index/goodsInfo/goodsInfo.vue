<template>
	<view class="content">
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
				<view class="label">货物名称</view>
				<input v-model="goods_name" placeholder="请输入" placeholder-style="color:#bbb" class="input" />
			</view>
			<picker @change="bindPickerChangeType" :value="typeIndex" :range="typeList" range-key="name">
				<view class="row">
					<view class="label">货物类型</view>
					<view class="value value--picker">
						{{ (typeList[typeIndex] && typeList[typeIndex].name) || '请选择' }}
						<image class="chev" src="/static/images/icon_2jt.png"></image>
					</view>
				</view>
			</picker>
			<view class="row row--tall">
				<view class="label">货物图片</view>
				<image class="goods-img" @click="upImage" :src="goods_image?imgUrl+goods_image:'/static/images/icon_35sc.png'"></image>
			</view>
			<view class="row">
				<view class="label">数量</view>
				<view class="stepper">
					<view @click="jianjian" class="stepper-btn stepper-btn--minus">-</view>
					<input type="number" :value="quantity" @input="onQuantityInput" @blur="onQuantityBlur" class="stepper-input" />
					<view @click="quantity++" class="stepper-btn">+</view>
				</view>
			</view>

			<view v-if="!canAddMultipleSizes" class="row">
				<view class="label">尺寸</view>
				<view class="size-single-wrap">
					<view class="value-inline">
						<input placeholder="长" :value="long" @input="onNumberInput('long', $event.detail.value, 4)" @blur="chang" type="digit" class="mini" />
						<text class="x">×</text>
						<input placeholder="宽" :value="wide" @input="onNumberInput('wide', $event.detail.value, 4)" @blur="chang" type="digit" class="mini" />
						<text class="x">×</text>
						<input placeholder="高" :value="hige" @input="onNumberInput('hige', $event.detail.value, 4)" @blur="chang" type="digit" class="mini" />
						<text class="unit">米</text>
					</view>
					<view v-if="hasSingleSize" class="del" @click="clearSingleSize">删除</view>
				</view>
			</view>

			<view v-if="canAddMultipleSizes" class="row row--column">
				<view class="flexJusp" style="align-items:center;margin-bottom:16rpx;">
					<view class="label">尺寸</view>
					<view class="add" @click="addSize">+ 添加尺寸</view>
				</view>
				<view v-for="(item, index) in sizeList" :key="index" class="size-item">
					<view class="flex" style="align-items:center;flex:1;min-width:0;">
						<input placeholder="长" :value="item.long" @input="onSizeInput(index, 'long', $event.detail.value)" type="digit" @blur="changMultiple(index)" class="mini mini--white" />
						<text class="x">×</text>
						<input placeholder="宽" :value="item.wide" @input="onSizeInput(index, 'wide', $event.detail.value)" type="digit" @blur="changMultiple(index)" class="mini mini--white" />
						<text class="x">×</text>
						<input placeholder="高" :value="item.hige" @input="onSizeInput(index, 'hige', $event.detail.value)" type="digit" @blur="changMultiple(index)" class="mini mini--white" />
						<text class="unit">米</text>
					</view>
					<view class="del" @click="removeSize(index)">删除</view>
				</view>
			</view>

			<view class="row">
				<view class="label">总重量</view>
				<view class="value-inline">
					<input placeholder="0" :value="weight" @input="onNumberInput('weight', $event.detail.value, 2)" @blur="zhongliang" type="digit" class="mini" />
					<text class="unit">吨</text>
				</view>
			</view>
			<view class="row">
				<view class="label">总方位</view>
				<view class="value-inline">
					<input placeholder="0" :value="direction" @input="onNumberInput('direction', $event.detail.value, 2)" type="digit" class="mini" />
					<text class="unit">立方米</text>
				</view>
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
	const INDEX_GOODS_INFO_STORAGE_KEY = 'index_goodsInfo_v1'
	export default {
		mixins: [systemInfo],
		data() {
			return {
				imgUrl: this.$utils.imgUrl,
				typeList: [],
				typeIndex: 0,
				goods_name: '',
				goods_image: '',
				quantity: 1,
				long: '',
				wide: '',
				hige: '',
				sizeList: [{ long: '', wide: '', hige: '' }],
				weight: '',
				direction: ''
			}
		},
		computed: {
			canAddMultipleSizes() {
				if (this.typeList && this.typeList[this.typeIndex] && this.typeList[this.typeIndex].switch == 1) return true
				return false
			},
			hasSingleSize() {
				return !!(this.long || this.wide || this.hige)
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
				const saved = uni.getStorageSync(INDEX_GOODS_INFO_STORAGE_KEY)
				if (!saved || typeof saved !== 'object') return
				if (Array.isArray(saved.typeList)) this.typeList = saved.typeList
				if (typeof saved.typeIndex === 'number') this.typeIndex = saved.typeIndex
				if (saved.goods_name != null) this.goods_name = saved.goods_name || ''
				if (saved.goods_image != null) this.goods_image = saved.goods_image || ''
				if (saved.quantity != null) this.quantity = Number(saved.quantity || 1)
				if (saved.long != null) this.long = saved.long || ''
				if (saved.wide != null) this.wide = saved.wide || ''
				if (saved.hige != null) this.hige = saved.hige || ''
				if (Array.isArray(saved.sizeList) && saved.sizeList.length) this.sizeList = saved.sizeList
				if (saved.weight != null) this.weight = saved.weight || ''
				if (saved.direction != null) this.direction = saved.direction || ''
			},
			bindPickerChangeType(e) {
				this.typeIndex = Number(e.detail.value || 0)
			},
			upImage() {
				const that = this
				uni.chooseImage({
					count: 1,
					sizeType: ['original', 'compressed'],
					sourceType: ['album', 'camera'],
					success(res) {
						console.log(res.tempFilePaths[0])
						that.$upShop('common/upload', res.tempFilePaths[0]).then(rex => {
							const arr = JSON.parse(rex.data)
							that.goods_image = arr.data.url
						}).catch(() => {})
					}
				})
			},
			jianjian() {
				if (this.quantity < 2) this.quantity = 1
				else this.quantity--
			},
			onQuantityInput(e) {
				const val = e.detail.value
				if (val === '' || val === null || val === undefined) return
				const num = parseInt(val, 10)
				if (!isNaN(num) && num >= 1) this.quantity = num
			},
			onQuantityBlur(e) {
				const val = e.detail.value
				const num = parseInt(val, 10)
				if (isNaN(num) || num < 1) this.quantity = 1
				else this.quantity = num
			},
			sanitizeNumber(value, decimalPlaces = null) {
				value = String(value || '')
				value = value.replace(/[^\d.]/g, '')
				const parts = value.split('.')
				if (parts.length > 1) value = parts[0] + '.' + parts.slice(1).join('')
				if (decimalPlaces === 0) return parts[0] || ''
				if (decimalPlaces != null && parts.length > 1) return parts[0] + '.' + (parts[1] || '').slice(0, decimalPlaces)
				return value
			},
			onNumberInput(field, value, decimalPlaces = null) {
				const cleaned = this.sanitizeNumber(value, decimalPlaces)
				this[field] = cleaned
			},
			onSizeInput(index, key, value) {
				const cleaned = this.sanitizeNumber(value, 4)
				this.$set(this.sizeList[index], key, cleaned)
				if (this.canAddMultipleSizes) this.calculateTotalDirection()
				else this.chang()
			},
			addSize() {
				this.sizeList.push({ long: '', wide: '', hige: '' })
			},
			clearSingleSize() {
				this.long = ''
				this.wide = ''
				this.hige = ''
				this.direction = ''
				this.sizeList = [{ long: '', wide: '', hige: '' }]
			},
			removeSize(index) {
				if (this.sizeList.length > 1) {
					this.sizeList.splice(index, 1)
					this.calculateTotalDirection()
				} else {
					this.$set(this.sizeList, 0, { long: '', wide: '', hige: '' })
					this.calculateTotalDirection()
				}
			},
			changMultiple(index) {
				const item = this.sizeList[index]
				if (item.long && item.long > 10000) item.long = 10000
				if (item.wide && item.wide > 10000) item.wide = 10000
				if (item.hige && item.hige > 10000) item.hige = 10000
				this.calculateTotalDirection()
			},
			calculateTotalDirection() {
				let totalDirection = 0
				this.sizeList.forEach(item => {
					if (item.long && item.wide && item.hige) {
						totalDirection += parseFloat(item.long) * parseFloat(item.wide) * parseFloat(item.hige)
					}
				})
				this.direction = totalDirection.toFixed(4)
			},
			chang() {
				if (this.long && this.wide && this.hige) {
					this.direction = (parseFloat(this.long) * parseFloat(this.wide) * parseFloat(this.hige)).toFixed(4)
				}
			},
			zhongliang() {
				if (this.weight && parseFloat(this.weight) > 999999) this.weight = '999999'
			},
			done() {
				uni.setStorageSync(INDEX_GOODS_INFO_STORAGE_KEY, {
					typeList: this.typeList || [],
					typeIndex: this.typeIndex,
					goods_name: this.goods_name || '',
					goods_image: this.goods_image || '',
					quantity: Number(this.quantity || 1),
					long: this.long || '',
					wide: this.wide || '',
					hige: this.hige || '',
					sizeList: this.sizeList || [{ long: '', wide: '', hige: '' }],
					weight: this.weight || '',
					direction: this.direction || ''
				})
				uni.navigateBack()
			}
		}
	}
</script>

<style scoped>
	.content{ min-height: 100vh; background: #F7F8FA; }
	.nav{ position: fixed; top: 0; left: 0; width: 750rpx; z-index: 10; background: #FFFFFF; }
	.nav-inner{ display: flex; align-items: center; justify-content: space-between; }
	.nav-back{ width: 120rpx; display: flex; align-items: center; justify-content: center; }
	.nav-back-icon{ width: 20rpx; height: 34rpx; }
	.nav-title{ font-weight: bold; font-size: 34rpx; }
	.nav-right{ width: 120rpx; }

	.card{ margin: 20rpx; background: #FFFFFF; border-radius: 24rpx; overflow: hidden; }
	.row{ display:flex; align-items:center; justify-content:space-between; padding:22rpx 20rpx; border-bottom:2rpx solid #F2F2F2; }
	.row:last-child{ border-bottom:0; }
	.row--column{ display:block; }
	.row--tall{ height:156rpx; }
	.label{ font-size:28rpx; color:#333; flex-shrink:0; }
	.value{ display:flex; align-items:center; justify-content:flex-end; font-size:28rpx; color:#333; }
	.value--picker{ min-width: 240rpx; }
	.input{ flex:1; text-align:right; font-size:28rpx; color:#111; }
	.chev{ width:20rpx; height:20rpx; margin-left:12rpx; }

	.goods-img{ width:120rpx; height:120rpx; }

	.stepper{ display:flex; align-items:center; height:42rpx; border-radius:12rpx; border:1rpx solid #CACACA; text-align:center; overflow:hidden; }
	.stepper-btn{ width:40rpx; }
	.stepper-btn--minus{ color:#CACACA; }
	.stepper-input{ width:80rpx; height:42rpx; font-size:24rpx; text-align:center; border-left:2rpx solid #CACACA; border-right:2rpx solid #CACACA; box-sizing:border-box; }

	.size-single-wrap{ display:flex; align-items:center; justify-content:flex-end; flex:1; min-width:0; }
	.value-inline{ display:flex; align-items:center; justify-content:flex-end; flex:1; gap:10rpx; min-width:0; }
	.mini{ width: 140rpx; height:60rpx; line-height:60rpx; background:#F7F7F7; border-radius:12rpx; text-align:center; font-size:28rpx; }
	.mini--white{ background:#FFFFFF; }
	.x{ color:#999; }
	.unit{ color:#666; margin-left:6rpx; }

	.size-item{ display:flex; align-items:center; justify-content:space-between; padding:20rpx; background:#F7F7F7; border-radius:12rpx; margin-bottom:16rpx; }
	.del{ margin-left:20rpx; color:#E50014; font-size:28rpx; flex-shrink:0; }
	.add{ color:#4878FF; font-size:28rpx; }

	.footer-space{ height: 140rpx; }
	.footer{ position: fixed; left:0; bottom:0; width:750rpx; padding:16rpx 30rpx 30rpx; box-sizing:border-box; background:#FFFFFF; border-top:2rpx solid #F2F2F2; }
	.submit{ height:88rpx; border-radius:46rpx; background: linear-gradient(180deg, #4897FF 0%, #4878FF 100%); color:#FFFFFF; font-weight:bold; font-size:30rpx; line-height:88rpx; text-align:center; }
</style>


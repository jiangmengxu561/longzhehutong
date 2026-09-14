<template>
	<view class="orders">
		<!-- 顶部背景+导航条 -->
		<view style="width: 750rpx;height: 272rpx;background: linear-gradient(180deg,#D8EEFC 0%,#F7F8FA 100%);"></view>
		<view style="margin-top:-272rpx;"></view>
		<view style="width: 750rpx;z-index: 9;position: fixed;top:0;left:0;color:#000;"
			  :style="scrollTop>0?'background:#FFF;height:'+(statusBarHeight+navigationBarHeight)+'px':''">
			<view :style="'height:'+statusBarHeight+'px'"></view>
			<view class="flexJusp" style="font-weight: bold;font-size: 36rpx;align-items: center;"
				  :style="'height:'+navigationBarHeight+'px;line-height:'+navigationBarHeight+'px'">
				<view style="width:100rpx;text-align: center;" @click="tuichu">
					<image style="width:20rpx;height:34rpx;" src="/static/images/mv_tuichu.png"></image>
				</view>
				<view>{{ titleText }}</view>
				<view style="width:100rpx;"></view>
			</view>
		</view>
		<view :style="'height:'+(statusBarHeight+navigationBarHeight)+'px'"></view>

		<!-- 用户统计信息 -->
		<view class="top-card">
			<view class="user-name">{{ name || '用户名' }}</view>
			<view class="stat-row">
				<view class="stat-item">
					<view class="stat-label">总单数</view>
					<view class="stat-value">{{ stat.total_orders }}</view>
				</view>
				<view class="stat-item">
					<view class="stat-label">今日单数</view>
					<view class="stat-value">{{ stat.today_orders }}</view>
				</view>
				<view class="stat-item">
					<view class="stat-label">今日报价次数</view>
					<view class="stat-value">{{ stat.today_quotes }}</view>
				</view>
			</view>
			<view class="stat-row">
				<view class="stat-item">
					<view class="stat-label">利润</view>
					<view class="stat-value">{{ stat.profit }}</view>
				</view>
				<view class="stat-item">
					<view class="stat-label">提成</view>
					<view class="stat-value">{{ stat.commission }}</view>
				</view>
				<view class="stat-item"></view>
			</view>
		</view>

		<!-- 时间选择（时间区间） -->
	<view class="filter-bar">
	  <view class="filter-label">时间选择</view>
	  <view class="filter-range">
		<picker mode="date" :value="startDate" @change="onStartDateChange">
		  <view class="filter-date">
			<text class="date-text">{{ startDate || '开始日期' }}</text>
			<image src="/static/images/date_icon.png" class="date-icon"></image>
		  </view>
		</picker>
		<text class="range-split">至</text>
		<picker mode="date" :value="endDate" @change="onEndDateChange">
		  <view class="filter-date">
			<text class="date-text">{{ endDate || '结束日期' }}</text>
			<image src="/static/images/date_icon.png" class="date-icon"></image>
		  </view>
		</picker>
	  </view>
	</view>

		<!-- 订单列表标题 -->
		<!-- <view class="section-title">订单列表</view> -->

		<!-- 订单列表 -->
		<view class="list-box">
			<view v-if="list.length" v-for="(item,index) in list" :key="index" class="card">
				<view class="flexJusp">
					<view class="order-time-label">下单时间</view>
					<view class="order-time">{{ item.createtime}}</view>
				</view>
				<view class="line"></view>
				<view class="station-row">
					<view class="station-label">始发站</view>
					<view class="station-value">{{ item.loading}}</view>
				</view>
				<view class="station-row">
					<view class="station-label">到达站</view>
					<view class="station-value">{{ item.unload }}</view>
				</view>
			</view>
			<view v-else class="empty">暂无订单</view>
		</view>
	</view>
</template>

<script>
	import { systemInfo } from '@/https/mixin.js'
	export default {
		mixins: [systemInfo],
		data() {
			return {
				scrollTop: 0,
				uid: '',
				name: '',
				stat: {
					total_orders: 0,
					today_orders: 0,
					today_quotes: 0,
					profit: 0,
					commission: 0
				},
				startDate: '',
				endDate: '',
				list: []
			}
		},
		computed:{
			titleText(){
				return this.name ? this.name + '-订单' : '订单'
			}
		},
		onLoad(option){
			this.getSystemInfo()
			if(option && option.id){
				this.uid = option.id
			}
			if(option && option.name){
				this.name = decodeURIComponent(option.name)
			}
			this.loadList()
		},
		onPageScroll(e){
			this.scrollTop = e.scrollTop
		},
		methods:{
			tuichu(){
				uni.navigateBack()
			},
			// 开始日期变更
			onStartDateChange(e){
				this.startDate = e.detail.value
				this.loadList()
			},
			// 结束日期变更
			onEndDateChange(e){
				this.endDate = e.detail.value
				this.loadList()
			},
			// 加载订单列表 + 统计（利润、提成等从 mylastorderlist 返回）
			loadList(){
				if(!this.uid){
					return
				}
				const params = {
					id: this.uid
				}
				// 后端是时间区间，常见两种写法，先都按字段传过去（按你后台实际字段调整）
				if(this.startDate){
					params.start_date = this.startDate
					params.start_time = this.startDate
				}
				if(this.endDate){
					params.end_date = this.endDate
					params.end_time = this.endDate
				}
				this.$httpapi('Employee/mylastorderlist', 'GET', params).then(res=>{
					const data = res.data || {}
					// 1. 列表数据：兼容 data 是数组 或 data.list / data.orders
					if(Array.isArray(data)){
						this.list = data
					}else{
						this.list = data.list || data.orders || []
					}
					// 2. 统计数据：利润 / 提成等从接口里取
					if(!Array.isArray(data) && data){
						this.stat = Object.assign({}, this.stat, {
							total_orders: data.total_orders || data.order_count || this.list.length || 0,
							today_orders: data.today_orders || this.stat.today_orders || 0,
							today_quotes: data.today_quotes || this.stat.today_quotes || 0,
							profit: data.profit || data.total_profit || 0,
							commission: data.commission || data.total_commission || 0
						})
					}
				}).catch(()=>{
					uni.showToast({
						title: '加载订单失败，请稍后再试',
						duration: 1500,
						icon:'none'
					});
				})
			}
		}
	}
</script>

<style lang="scss">
	.orders{
		padding-bottom: 40rpx;
		min-height: 100vh;
		background-color: #F7F6FA;
	}
	.top-card{
		margin: 20rpx;
		background: #FFF;
		border-radius: 20rpx;
		padding: 30rpx 26rpx 24rpx;
	}
	.user-name{
		text-align: center;
		font-size: 36rpx;
		font-weight: bold;
		color: #4878FF;
		margin-bottom: 26rpx;
	}
	.stat-row{
		display: flex;
		justify-content: space-between;
		margin-bottom: 16rpx;
	}
	.stat-item{
		flex: 1;
	}
	.stat-item:nth-child(2){
		align-items: center;
		text-align: center;
	}
	.stat-item:nth-child(3){
		text-align: right;
	}
	.stat-label{
		font-size: 24rpx;
		color: #666;
		margin-bottom: 6rpx;
	}
	.stat-value{
		font-size: 30rpx;
		color: #333;
		font-weight: bold;
	}
	.filter-bar{
		margin: 0 20rpx 10rpx;
		padding: 24rpx 26rpx;
		background: #FFF;
		border-radius: 20rpx;
		display: flex;
		justify-content: space-between;
		align-items: center;
	}
	.filter-label{
		font-size: 30rpx;
		color: #4878FF;
		font-weight: bold;
	}
	.filter-date{
		display: flex;
		align-items: center;
		font-size: 26rpx;
		color: #333;
	}
	.section-title{
		margin: 10rpx 20rpx 0;
		font-size: 30rpx;
		color: #4878FF;
		font-weight: bold;
	}
	.list-box{
		padding: 0 20rpx 20rpx;
	}
	.card{
		margin-top: 16rpx;
		background: #FFF;
		border-radius: 20rpx;
		padding: 22rpx 26rpx 20rpx;
	}
	.order-time-label{
		font-size: 26rpx;
		color: #666;
	}
	.order-time{
		font-size: 26rpx;
		color: #333;
	}
	.line{
		height: 1rpx;
		background: #EEE;
		margin: 18rpx 0;
	}
	.station-row{
		display: flex;
		margin-bottom: 10rpx;
	}
	.station-label{
		width: 120rpx;
		font-size: 26rpx;
		color: #666;
	}
	.station-value{
		flex: 1;
		font-size: 26rpx;
		color: #333;
	}
	.empty{
		text-align: center;
		color: #888;
		font-size: 28rpx;
		padding: 120rpx 0;
	}
	/* 样式优化 */
	.filter-bar {
	  display: flex;
	  align-items: center;
	  padding: 20rpx 32rpx;
	  background-color: #fff;
	}
	
	.filter-label {
	  font-size: 28rpx;
	  color: #333;
	  margin-right: 32rpx;
	  white-space: nowrap;
	}
	
	.filter-range {
	  display: flex;
	  align-items: center;
	  flex: 1;
	}
	
	.filter-date {
	  display: flex;
	  align-items: center;
	  padding: 16rpx 24rpx;
	  background-color: #f5f5f5;
	  border-radius: 8rpx;
	  min-width: 200rpx;
	  justify-content: space-between;
	}
	
	.date-text {
	  font-size: 28rpx;
	  color: #333;
	}
	
	.date-text:empty::before {
	  content: attr(placeholder);
	  color: #999;
	}
	
	.range-split {
	  margin: 0 20rpx;
	  font-size: 28rpx;
	  color: #666;
	  min-width: 40rpx;
	  text-align: center;
	}
	
	.date-icon {
	  width: 28rpx;
	  height: 28rpx;
	}
</style>



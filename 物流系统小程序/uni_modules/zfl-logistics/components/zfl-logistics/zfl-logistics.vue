<template>
	<view class="logistics">
		<view v-for="(item, index) in list" :key="index" class="item">
			<view class="content">
				<view class="dot-container">
					<view class="dot" :class="{ active: index === 0 }"></view>
				</view>
				<view class="info">
					<text class="status">{{ item.status }}</text>
					<text class="time">{{ item.time }}</text>
					<text class="description">{{ item.description }}</text>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		name: 'ZflLogistics',
		props: {
			list: {
				type: Array,
				default: ()=> []
			}
		}
	}
</script>

<style lang="scss" scoped>
	.logistics {
		padding: 30rpx 10rpx;
		background-color: #ffffff;
		.item {
			position: relative;
			padding-bottom: 40rpx;
		}
		.item:not(:last-child)::after {
			content: '';
			position: absolute;
			left: 15rpx;
			top: 30rpx;
			bottom: 0;
			width: 1rpx;
			background-color: #e0e0e0;
		}
		
		.info {
			margin-left: 20rpx;
			flex: 1;
			.status {
				display: block;
				font-size: 28rpx;
				color: #333;
				font-weight: 700;
				margin-bottom: 4rpx;
			}
		
			.time {
				font-size: 24rpx;
				color: #999;
				margin-bottom: 8rpx;
			}
		
			.description {
				display: block;
				font-size: 26rpx;
				color: #666;
				line-height: 1.4;
			}
		}
	}

	.content {
		display: flex;
		align-items: flex-start;
	}

	.dot-container {
		width: 30rpx;
		height: 30rpx;
		display: flex;
		justify-content: center;
		align-items: center;
		.dot {
			width: 12rpx;
			height: 12rpx;
			border-radius: 50%;
			background-color: #e0e0e0;
			&.active {
				width: 20rpx;
				height: 20rpx;
				background-color: #6c7fff;
				box-shadow: 0 0 0 6rpx rgba(111, 129, 255, 0.2);
			}
		}
	}

</style>
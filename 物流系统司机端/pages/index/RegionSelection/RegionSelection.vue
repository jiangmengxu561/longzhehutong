<template>
	<view>
		<view style="overflow: auto;max-height:80vh;">
			<ex-cascader-select v-model="value" @change="change" :nodeData="nodeData" :checkOnClickNode="false"
				:clickCheckPithNode="false" :props="{
			    label: 'name',
			    value: 'code',
			    children: 'children',
			  }">
			</ex-cascader-select>
		</view>
		
		<view class="post">
			<view class="flexJusp" style="padding: 20rpx;">
				<view class="buttonxx" @click="qingchu">清除</view>
				<view class="buttonss" @click="xuanzes">确认选择</view>
			</view>
			<view style="height:env(safe-area-inset-bottom);"></view>
		</view>
	</view>
</template>

<script>
	import
	address
	from './address.js'
	export default {
		data() {
			return {
				nodeData: Object.freeze(address),
				value: [],
				selectHeight: 50,
				checkOnClickNode: false,
				radiolist: [{
						name: '点击节点选中',
						value: true
					},
					{
						name: '点击节点不选中',
						value: false
					}
				],
				clickCheckPithNode: false,
				radiolist1: [{
						name: '点击复选框选中当前节点',
						value: true
					},
					{
						name: '点击复选框不选中当前节点',
						value: false
					}
				],
				selectData: {
					halfCheckIds: [],
					halfCheckLabels: [],
					halfChecks: [],
					checkIds: [],
					checkLabels: [],
					checks: [],
					lastChildIds: [],
					lastChildLabels: [],
					lastChilds: []
				}
			};
		},
		onLoad() {
			console.log(uni.getStorageSync('selectData'))
			if(uni.getStorageSync('selectData')){
				this.value = uni.getStorageSync('selectData').checkIds
			}
		},
		methods: {
			change(data) {
				console.log(data, 'data--------')
				this.selectData = data
			},
			qingchu(){
				console.log(address,Object.freeze(address),'------')
				this.value = Object.freeze(address)
			},
			xuanzes(){
				console.log(this.selectData,'-----')
				uni.setStorageSync('selectData',this.selectData)
				const eventChannel = this.getOpenerEventChannel()
				eventChannel.emit('acceptDataFromOpenedPages', this.selectData.checkLabels);
				uni.navigateBack()
			}
		}
	}
</script>

<style lang="scss">
	.post {
		position: fixed;
		left: 0;
		bottom: 0;
		width: 750rpx;
		background-color: #FFF;
		box-shadow: 0rpx -4rpx 12rpx 2rpx rgba(0, 0, 0, 0.08);
	}

	.buttonxx {
		width: 220rpx;
		height: 88rpx;
		background: #FFFFFF;
		border-radius: 12rpx;
		border: 2rpx solid #147CFD;
		font-size: 30rpx;
		color: #147CFD;
		line-height: 84rpx;
		text-align: center;
	}

	.buttonss {
		width: 460rpx;
		height: 88rpx;
		background: #147CFD;
		border-radius: 12rpx;
		border: 2rpx solid #147CFD;
		font-size: 30rpx;
		color: #FFF;
		line-height: 84rpx;
		text-align: center;
	}
</style>
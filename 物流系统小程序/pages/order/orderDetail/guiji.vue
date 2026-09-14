<template>
	<view style="padding:20rpx;">
		<view class="flexJusp" style="align-items: center;height:104rpx;background-color: #FFF;border-radius:20rpx;margin-bottom:20rpx;padding:0 20rpx;">
			<view style="font-weight: bold;">订单编号</view>
			<view>{{code}}</view>
		</view>
		<zfl-logistics :list="list"></zfl-logistics>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				id: '',
				list: [],
				code:''
			}
		},
		onLoad(option) {
			this.code = option.code
			this.id = option.id
			this.loads()
		},
		methods: {
			loads() {
				this.$httpapi('index/trajectory', 'POST', {
					id:this.id
				}).then(res => {
					var lists = []
					for(let i=0;i<res.data.length;i++){
						lists.push({
							status:res.data[i].type,
							time:res.data[i].createtime,
							description:res.data[i].admin_name,
						})
					}
					
					this.list = lists.reverse()
				})
			}
		}
	}
</script>

<style>

</style>
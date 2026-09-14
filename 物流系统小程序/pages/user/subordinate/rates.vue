<template>
	<view class="rates">
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
				<view>{{ titleText }}</view>
				<view style="width:100rpx;"></view>
			</view>
		</view>
		<view :style="'height:'+(statusBarHeight+navigationBarHeight)+'px'"></view>

		<view class="list-box">
			<view v-if="list.length" v-for="(item,index) in list" :key="index" class="card">
				<view style="font-size: 30rpx;font-weight: bold;color:#333333;">{{ displayRoute(item) }}</view>
				<view style="margin: 10rpx 0 6rpx;color:#666;">{{ formatPercent(item) }}</view>
				<view style="font-size: 24rpx;color:#999;">正数上调，负数下调，0 表示不调整</view>
				<view class="flexJusp" style="margin-top: 18rpx;align-items:center;">
					<view class="action-btn" @click="openEdit(item)">编辑</view>
					<view class="action-btn danger" @click="confirmDelete(item)">删除</view>
				</view>
			</view>
			<view v-else class="empty">暂无比例</view>
		</view>

		<view class="buttons" @click="openEdit()">新增比例</view>

		<view v-if="showEdit" class="mask">
			<view class="modal">
				<view style="font-size: 32rpx;font-weight: bold;text-align: center;">{{ form.id?'编辑比例':'新增比例' }}</view>
				<view class="form">
					<view class="form-item">
						<view class="label">发货城市 *</view>
						<input v-model="form.from_province" placeholder="请输入发货城市" />
					</view>
					<view class="form-item">
						<view class="label">收货城市 *</view>
						<input v-model="form.to_province" placeholder="请输入收货城市" />
					</view>
					<view class="form-item">
						<view class="label">调整百分比 *</view>
						<input v-model="form.percent" type="number" placeholder="正数上调，负数下调，0 不调" />
					</view>
				</view>
				<view class="modal-actions">
					<view class="modal-btn cancel" @click="closeEdit">取消</view>
					<view class="modal-btn confirm" @click="saveRate">保存</view>
				</view>
			</view>
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
				list: [],
				uid: '',
				name: '',
				showEdit: false,
				form: {
					id: '',
					from_province: '',
					to_province: '',
					percent: ''
				}
			}
		},
		computed:{
			titleText(){
				return this.name ? `${this.name}-价格比例` : '价格比例'
			}
		},
		onLoad(option) {
			this.getSystemInfo()
			if(option && option.id){
				this.uid = option.id
			}
			if(option && option.name){
				this.name = decodeURIComponent(option.name)
			}
			this.loadRates()
		},
		onPageScroll(event) {
			const scrollTop = event.scrollTop
			this.scrollTop = scrollTop
		},
		methods: {
			tuichu(){
				uni.navigateBack()
			},
			displayRoute(item){
				const from = item.from_province || item.start_province || item.province_from || ''
				const to = item.to_province || item.end_province || item.province_to || ''
				return `${from || '未填'} → ${to || '未填'}`
			},
			formatPercent(item){
				const val = item.percent ?? item.rate ?? item.price_rate ?? item.price_percent ?? ''
				if(val === '' || val === null || val === undefined){
					return '未设置'
				}
				const str = val.toString()
				return str.indexOf('%')>-1 ? str : `${str}%`
			},
			loadRates(){
				if(!this.uid){
					uni.showToast({
						title: '缺少下级ID',
						duration: 1500,
						icon:'none'
					});
					return
				}
				this.$httpapi('Employee/mylastpricelist', 'GET', {id:this.uid}).then(res=>{
					this.list = res.data || []
				}).catch(()=>{
					uni.showToast({
						title: '加载比例失败，请稍后再试',
						duration: 1500,
						icon:'none'
					});
				})
			},
			openEdit(item){
				if(item){
					this.form = {
						id: item.id || item.pid || '',
						from_province: item.from_province || item.start_province || item.province_from || '',
						to_province: item.to_province || item.end_province || item.province_to || '',
						percent: item.percent ?? item.rate ?? item.price_rate ?? item.price_percent ?? ''
					}
				}else{
					this.form = { id:'', from_province:'', to_province:'', percent:'' }
				}
				this.showEdit = true
			},
			closeEdit(){
				this.showEdit = false
			},
			saveRate(){
				if(!this.form.from_province || !this.form.to_province || this.form.percent === ''){
					uni.showToast({
						title: '请完善必填项',
						duration: 1500,
						icon:'none'
					});
					return
				}
				const api = this.form.id ? 'Employee/mylastpriceupdate' : 'Employee/mylastpriceadd'
				const payload = {
					id: this.form.id,
					last_id: this.uid,
					from_province: this.form.from_province,
					to_province: this.form.to_province,
					percent: this.form.percent,
					rate: this.form.percent,
					price_rate: this.form.percent,
					price_percent: this.form.percent
				}
				this.$httpapi(api, 'POST', payload).then(res=>{
					uni.showToast({
						title: res.msg || '操作成功',
						duration: 1500,
						icon:'none'
					});
					if(res.code === 1 || res.code === 200 || res.code === 0){
						this.showEdit = false
						this.loadRates()
					}
				}).catch(()=>{
					uni.showToast({
						title: '保存失败，请重试',
						duration: 1500,
						icon:'none'
					});
				})
			},
			confirmDelete(item){
				const id = item.id || item.pid || ''
				if(!id){
					uni.showToast({
						title: '缺少比例ID，无法删除',
						duration: 1500,
						icon:'none'
					});
					return
				}
				uni.showModal({
					title: '删除比例',
					content: '是否确认删除该比例？',
					confirmText: '确定',
					success: (res) => {
						if (res.confirm) {
							this.deleteRate(id)
						}
					}
				});
			},
			deleteRate(id){
				this.$httpapi('Employee/mylastpricedelete', 'POST', {id:id}).then(res=>{
					uni.showToast({
						title: res.msg || '删除成功',
						duration: 1500,
						icon:'none'
					});
					if(res.code === 1 || res.code === 200 || res.code === 0){
						this.loadRates()
					}
				}).catch(()=>{
					uni.showToast({
						title: '删除失败，请重试',
						duration: 1500,
						icon:'none'
					});
				})
			}
		}
	}
</script>

<style lang="scss">
	.rates{
		padding-bottom: 160rpx;
		min-height: 100vh;
		background-color: #F7F6FA;
	}
	.list-box{
		padding: 0 20rpx;
	}
	.card{
		margin-top: 20rpx;
		background: #FFF;
		border-radius: 20rpx;
		padding: 24rpx 28rpx;
	}
	.action-btn{
		width: 240rpx;
		height: 64rpx;
		border-radius: 32rpx;
		border: 1rpx solid #4878FF;
		color: #4878FF;
		text-align: center;
		line-height: 64rpx;
		font-size: 26rpx;
	}
	.action-btn.danger{
		color: #E84A4A;
		border-color: #E84A4A;
	}
	.empty{
		text-align: center;
		color: #888;
		font-size: 28rpx;
		padding: 120rpx 0;
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
	.mask{
		position: fixed;
		top:0;
		left:0;
		right:0;
		bottom:0;
		background: rgba(0,0,0,0.4);
		display: flex;
		align-items: center;
		justify-content: center;
		z-index: 99;
	}
	.modal{
		width: 660rpx;
		background: #FFF;
		border-radius: 20rpx;
		padding: 30rpx 30rpx 24rpx;
	}
	.form{
		margin-top: 20rpx;
	}
	.form-item{
		display: flex;
		align-items: center;
		border: 1rpx solid #F0F0F0;
		border-radius: 12rpx;
		padding: 0 20rpx;
		margin-bottom: 12rpx;
		height: 88rpx;
	}
	.form-item input{
		flex:1;
		text-align: right;
		font-size: 28rpx;
	}
	.label{
		width: 240rpx;
		color: #333;
		font-size: 28rpx;
	}
	.modal-actions{
		display: flex;
		justify-content: space-between;
		margin-top: 10rpx;
	}
	.modal-btn{
		width: 300rpx;
		height: 80rpx;
		border-radius: 16rpx;
		text-align: center;
		line-height: 80rpx;
		font-size: 30rpx;
	}
	.modal-btn.cancel{
		border: 1rpx solid #4878FF;
		color: #4878FF;
	}
	.modal-btn.confirm{
		background: linear-gradient( 180deg, #4897FF 0%, #4878FF 100%);
		color: #FFF;
	}
</style>


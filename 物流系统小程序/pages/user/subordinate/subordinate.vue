<template>
	<view class="subordinate">
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
				<view>我的推广</view>
				<view style="width:100rpx;"></view>
			</view>
		</view> 
		<view :style="'height:'+(statusBarHeight+navigationBarHeight)+'px'"></view>

		<view class="list-box">
			<view v-if="list.length" v-for="(item,index) in list" :key="index" class="card">
				<view class="flexJusp" style="align-items: center;margin-bottom: 8rpx;">
					<view style="font-size: 32rpx;font-weight: bold;color:#333333;">{{ displayName(item) }}</view>
					<view class="order-btn" @click="goOrders(item)">
						<text>查看订单</text>
					</view>
					<!-- <view class="tag">{{ formatRate(item) }}</view> -->
				</view>
				<view style="font-size:26rpx;color:#666666;margin-bottom: 16rpx;">{{ displayMobile(item) }}</view>
				
				<view class="flexJusp" style="align-items: center;">
					<view class="action-btn" @click="goRates(item)">设置价格比例</view>
					<view class="action-btn danger" @click="confirmDelete(item)">删除</view>
				</view>
			</view>
			<view v-else class="empty">暂无下级</view>
		</view>

		<view class="buttons" @click="openEdit()">新增推广</view>

		<view v-if="showEdit" class="mask">
			<view class="modal">
				<view style="font-size: 32rpx;font-weight: bold;text-align: center;">{{ form.id?'编辑下级':'新增推广' }}</view>
				<!-- 推广方式切换 -->
				<view v-if="!form.id" class="promote-tabs">
					<view class="tab-item" :class="{active: promoteType === 'mobile'}" @click="switchPromoteType('mobile')">填写手机号</view>
					<view class="tab-item" :class="{active: promoteType === 'qrcode'}" @click="switchPromoteType('qrcode')">展示二维码</view>
				</view>
				<view class="form">
					<!-- 手机号方式 -->
					<view v-if="promoteType === 'mobile' || form.id" class="form-item">
						<view class="label">手机号</view>
						<input v-model="form.mobile" type="number" placeholder="请输入手机号" />
					</view>
					<!-- 二维码方式 -->
					<view v-if="promoteType === 'qrcode' && !form.id" class="qrcode-box">
						<view class="qrcode-tip">扫描下方二维码进行推广</view>
						<view v-if="qrcodeUrl" class="qrcode-image-wrapper">
							<image class="qrcode-image" :src="qrcodeUrl" mode="aspectFit"></image>
						</view>
						<view v-else class="qrcode-loading">加载中...</view>
						<view v-if="qrcodeUrl" class="qrcode-download-btn" @click="downloadQrcode">下载二维码</view>
					</view>
			<!-- 		<template v-if="form.id">
						<view class="label" style="margin:16rpx 0 8rpx 6rpx;">价格比例(可多条，正负均可)</view>
						<view v-for="(rateVal,idx) in form.rates" :key="idx" class="form-item">
							<input v-model="form.rates[idx]" type="number" placeholder="请输入比例" />
							<view class="mini-btn danger" v-if="form.rates.length>1" @click="removeRate(idx)">删</view>
						</view>
						<view class="mini-btn add" @click="addRate">再添一条比例</view>
					</template> -->
				</view>
				<view class="modal-actions">
					<view class="modal-btn cancel" @click="closeEdit">取消</view>
					<view v-if="promoteType === 'mobile' || form.id" class="modal-btn confirm" @click="saveSubordinate">保存</view>
					<view v-else class="modal-btn confirm" @click="closeEdit">关闭</view>
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
				showEdit: false,
				promoteType: 'mobile', // 'mobile' 或 'qrcode'
				qrcodeUrl: '',
				form: {
					id: '',
					mobile: '',
					rates: ['']
				}
			}
		},
		onLoad() {
			this.getSystemInfo()
			this.loads()
		},
		onPageScroll(event) {
			const scrollTop = event.scrollTop
			this.scrollTop = scrollTop
		},
		methods: {
			tuichu(){
				uni.navigateBack()
			},
			loads() {
				this.$httpapi('Employee/mylastlist', 'GET', {}).then(res => {
					this.list = res.data || []
				}).catch(() => {
					uni.showToast({
						title: '加载下级失败，请稍后再试',
						duration: 1500,
						icon:'none'
					});
				})
			},
			displayName(item){
				return item.username || item.name || item.nickname || '未命名'
			},
			displayMobile(item){
				return item.mobile || item.phone || item.tel || '未填写联系方式'
			},
			getRateValue(item){
				if(item.rate !== undefined && item.rate !== null){
					return item.rate
				}
				if(item.price_rate !== undefined && item.price_rate !== null){
					return item.price_rate
				}
				if(item.percent !== undefined && item.percent !== null){
					return item.percent
				}
				if(item.price_percent !== undefined && item.price_percent !== null){
					return item.price_percent
				}
				return ''
			},
			formatRate(item){
				const arr = this.getRatesArray(item)
				if(!arr.length){
					const val = this.getRateValue(item)
					// if(val === '' || val === null || val === undefined){
					// 	return '未设置'
					// }
					const str = val.toString()
					// return str.indexOf('%')>-1 ? str : `${str}%`
				}
				return arr.map(v=>{
					const str = v.toString()
					return str.indexOf('%')>-1 ? str : `${str}%`
				}).join('，')
			},
			getItemId(item){
				return item.id || item.uid || item.user_id || item.mid || item.member_id || ''
			},
			getRatesArray(item){
				if(Array.isArray(item.rate_list)){
					return item.rate_list
				}
				if(item.rate_list){
					try{
						const parsed = JSON.parse(item.rate_list)
						if(Array.isArray(parsed)){
							return parsed
						}
					}catch(e){}
					if(typeof item.rate_list === 'string'){
						return item.rate_list.split(',').map(v=>v.trim()).filter(v=>v!== '')
					}
				}
				const single = this.getRateValue(item)
				return single !== '' && single !== null && single !== undefined ? [single] : []
			},
			openEdit(item){
				if(item){
					const rates = this.getRatesArray(item)
					this.form = {
						id: this.getItemId(item),
						mobile: item.mobile || item.phone || item.tel || '',
						rates: rates.length ? rates : ['']
					}
					this.promoteType = 'mobile'
					this.qrcodeUrl = ''
				}else{
					this.form = { id:'', mobile:'', rates:[''] }
					this.promoteType = 'mobile'
					this.qrcodeUrl = ''
				}
				this.showEdit = true
				// 如果是新增且选择二维码方式，自动获取二维码
				if(!item && this.promoteType === 'qrcode'){
					this.getQrcode()
				}
			},
			switchPromoteType(type){
				this.promoteType = type
				if(type === 'qrcode' && !this.qrcodeUrl){
					this.getQrcode()
				}
			},
			getQrcode(){
				this.qrcodeUrl = ''
				const token = uni.getStorageSync('token') || ''
				this.$httpapi('Getqrcode/qrcode', 'GET', {token: token}).then(res => {
					if(res.code === 200 || res.code === 1 || res.code === 0){
						if(res.data && res.data.url){
							this.qrcodeUrl = res.data.url
						}else{
							uni.showToast({
								title: '二维码获取失败',
								duration: 1500,
								icon:'none'
							});
						}
					}else{
						uni.showToast({
							title: res.message || '二维码获取失败',
							duration: 1500,
							icon:'none'
						});
					}
				}).catch(() => {
					uni.showToast({
						title: '二维码获取失败，请稍后再试',
						duration: 1500,
						icon:'none'
					});
				})
			},
			downloadQrcode(){
				if(!this.qrcodeUrl){
					uni.showToast({
						title: '二维码未加载完成',
						duration: 1500,
						icon:'none'
					});
					return
				}
				uni.showLoading({
					title: '下载中...',
					mask: true
				});
				// 先下载图片
				uni.downloadFile({
					url: this.qrcodeUrl,
					success: (res) => {
						if(res.statusCode === 200){
							// 保存到相册
							uni.saveImageToPhotosAlbum({
								filePath: res.tempFilePath,
								success: () => {
									uni.hideLoading();
									uni.showToast({
										title: '保存成功',
										duration: 1500,
										icon:'success'
									});
								},
								fail: (err) => {
									uni.hideLoading();
									if(err.errMsg.indexOf('auth deny') !== -1 || err.errMsg.indexOf('authorize') !== -1){
										uni.showModal({
											title: '提示',
											content: '需要您授权保存相册权限',
											success: (modalRes) => {
												if(modalRes.confirm){
													uni.openSetting();
												}
											}
										});
									}else{
										uni.showToast({
											title: '保存失败，请重试',
											duration: 1500,
											icon:'none'
										});
									}
								}
							});
						}else{
							uni.hideLoading();
							uni.showToast({
								title: '下载失败，请重试',
								duration: 1500,
								icon:'none'
							});
						}
					},
					fail: () => {
						uni.hideLoading();
						uni.showToast({
							title: '下载失败，请重试',
							duration: 1500,
							icon:'none'
						});
					}
				});
			}, 
			closeEdit(){
				this.showEdit = false
				this.qrcodeUrl = ''
			},
			addRate(){
				this.form.rates.push('')
			},
			removeRate(idx){
				if(this.form.rates.length>1){
					this.form.rates.splice(idx,1)
				}
			},
			goRates(item){ 
				const id = this.getItemId(item)
				if(!id){
					uni.showToast({
						title: '缺少下级ID，无法打开',
						duration: 1500,
						icon:'none'
					});
					return
				}
				const name = this.displayName(item)
				uni.navigateTo({
					url:`/pages/user/subordinate/rates?id=${id}&name=${encodeURIComponent(name)}`
				})
			},
			// 查看订单
			goOrders(item){
				const id = this.getItemId(item)
				if(!id){
					uni.showToast({
						title: '缺少下级ID，无法打开',
						duration: 1500,
						icon:'none'
					});
					return
				}
				const name = this.displayName(item)
				uni.navigateTo({
					url:`/pages/user/subordinate/orders?id=${id}&name=${encodeURIComponent(name)}`
				})
			},
			saveSubordinate(){
				// 二维码方式不需要验证手机号
				if(this.promoteType === 'qrcode' && !this.form.id){
					return
				}
				if(!this.form.mobile){
					uni.showToast({
						title: '请完善手机号',
						duration: 1500,
						icon:'none'
					});
					return
				}
				const api = this.form.id ? 'Employee/mylastupdate' : 'Employee/mylastadd'
				const payload = {
					id: this.form.id,
					name: this.form.mobile,
					username: this.form.mobile,
					mobile: this.form.mobile,
					phone: this.form.mobile
				}
				// 允许比例为空；填写多条则全部传递，首条兼容旧字段
				const rates = this.form.rates.filter(v => v !== '' && v !== null && v !== undefined)
				if(rates.length){
					payload.rate = rates[0]
					payload.price_rate = rates[0]
					payload.percent = rates[0]
					payload.price_percent = rates[0]
					payload.rate_list = rates
				}
				this.$httpapi(api, 'POST', payload).then(res => {
					uni.showToast({
						title: res.msg || '操作成功',
						duration: 1500,
						icon:'none'
					});
					if(res.code === 1 || res.code === 200 || res.code === 0){
						this.showEdit = false
						this.loads()
					}
				}).catch(() => {
					uni.showToast({
						title: '保存失败，请重试',
						duration: 1500,
						icon:'none'
					});
				})
			},
			confirmDelete(item){
				const id = this.getItemId(item)
				if(!id){
					uni.showToast({
						title: '缺少下级ID，无法删除',
						duration: 1500,
						icon:'none'
					});
					return
				}
				uni.showModal({
					title: '删除下级',
					content: '是否确认删除该下级？',
					confirmText: '确定',
					success: (res) => {
						if (res.confirm) {
							this.deleteSubordinate(id)
						}
					}
				});
			},
			deleteSubordinate(id){
				this.$httpapi('Employee/mylastdelete', 'POST', {id:id}).then(res => {
					uni.showToast({
						title: res.msg || '删除成功',
						duration: 1500,
						icon:'none'
					});
					if(res.code === 1 || res.code === 200 || res.code === 0){
						this.loads()
					}
				}).catch(() => {
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
	.subordinate{
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
	.tag{
		padding: 10rpx 20rpx;
		background: #EEF4FF;
		border-radius: 28rpx;
		color: #4878FF;
		font-size: 24rpx;
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
	/* 查看订单按钮：小一点、实心渐变、右上角悬浮感 */
	.order-btn{
		min-width: 180rpx;
		padding: 0 28rpx;
		height: 60rpx;
		border-radius: 999rpx;
		background: linear-gradient(135deg,#4897FF 0%,#4878FF 100%);
		box-shadow: 0 8rpx 16rpx rgba(72,151,255,0.35);
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 24rpx;
		color: #FFFFFF;
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
		width: 200rpx;
		color: #333;
		font-size: 28rpx;
	}
	.mini-btn{
		margin-left: 14rpx;
		padding: 10rpx 18rpx;
		border-radius: 12rpx;
		font-size: 24rpx;
		border: 1rpx solid #4878FF;
		color: #4878FF;
	}
	.mini-btn.add{
		display: inline-block;
		margin-top: 6rpx;
	}
	.mini-btn.danger{
		border-color: #E84A4A;
		color: #E84A4A;
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
	.promote-tabs{
		display: flex;
		margin-top: 20rpx;
		border-bottom: 1rpx solid #F0F0F0;
	}
	.tab-item{
		flex: 1;
		text-align: center;
		padding: 20rpx 0;
		font-size: 28rpx;
		color: #666;
		position: relative;
	}
	.tab-item.active{
		color: #4878FF;
		font-weight: bold;
	}
	.tab-item.active::after{
		content: '';
		position: absolute;
		bottom: 0;
		left: 50%;
		transform: translateX(-50%);
		width: 60rpx;
		height: 4rpx;
		background: #4878FF;
		border-radius: 2rpx;
	}
	.qrcode-box{
		margin-top: 20rpx;
		text-align: center;
	}
	.qrcode-tip{
		font-size: 26rpx;
		color: #666;
		margin-bottom: 30rpx;
	}
	.qrcode-image-wrapper{
		display: flex;
		justify-content: center;
		align-items: center;
		padding: 20rpx;
		background: #F7F8FA;
		border-radius: 12rpx;
	}
	.qrcode-image{
		width: 400rpx;
		height: 400rpx;
	}
	.qrcode-loading{
		padding: 60rpx 0;
		font-size: 26rpx;
		color: #999;
	}
	.qrcode-download-btn{
		margin-top: 30rpx;
		width: 100%;
		height: 80rpx;
		background: linear-gradient( 180deg, #4897FF 0%, #4878FF 100%);
		border-radius: 16rpx;
		color: #FFF;
		font-size: 28rpx;
		text-align: center;
		line-height: 80rpx;
		font-weight: bold;
	}
</style>


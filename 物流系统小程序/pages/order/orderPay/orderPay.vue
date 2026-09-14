<template>
	<view class="content">
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
				<view>订单详情</view>
				<view style="width:100rpx;"></view>
			</view>
		</view>
		<view :style="'height:'+(statusBarHeight+navigationBarHeight)+'px'"></view>

		<view style="background: #FFFFFF;border-radius: 24rpx;padding:0 24rpx;margin:20rpx;">
			<view class="flexJusp" style="align-items: center;height:104rpx;border-bottom:2rpx solid #F7F7F7;">
				<view>订单编号</view>
				<view>{{details.orderid}}</view>
			</view>
			<view class="flexJusp" style="align-items: center;height:104rpx;border-bottom:2rpx solid #F7F7F7;">
				<view>订单状态</view>
				<view style="color: #F29100;">
					<text v-if="details.pay_status==1">待付款</text>
					<text v-if="details.pay_status==2">服务中</text>
					<text v-if="details.pay_status==3">已完成</text>
					<text v-if="details.pay_status==4">取消</text>
				</view>
				
			</view>
			<view class="flexJusp" style="align-items: center;height:104rpx;">
				<view>车型类型</view>
				<view>{{details.car_type_id}}</view>
			</view>
		</view>
		<view style="margin:20rpx;padding:24rpx 20rpx;background: #FFFFFF;border-radius: 24rpx;">
			<view class="flex"
				style="height: 136rpx;background: #F7F7F7;border-radius: 20rpx;align-items: center;padding:0 20rpx;">
				<view
					style="width: 48rpx;height: 48rpx;background: #F29100;border-radius: 12rpx;font-weight: bold;color: #FFFFFF;line-height: 48rpx;text-align: center;">
					装</view>
				<view style="margin:0 24rpx;width:498rpx;">
					<view style="font-weight: bold;font-size: 30rpx;" v-if="details.loading_address">
						{{details.loading_address.address}}
						{{details.loading_address.detailed_address}}
					</view>
					<view style="font-size: 26rpx;color: #888888;margin-top:8rpx;" v-if="details.loading_address">
						{{details.loading_address.user_name}}
						{{details.loading_address.mobile}}
					</view>
				</view>
			</view>
			<view class="flex"
				style="height: 136rpx;background: #F7F7F7;border-radius: 20rpx;align-items: center;padding:0 20rpx;margin-top:20rpx;">
				<view
					style="width: 48rpx;height: 48rpx;background: linear-gradient( 180deg, #4897FF 0%, #4878FF 100%);border-radius: 12rpx;font-weight: bold;color: #FFFFFF;line-height: 48rpx;text-align: center;">
					卸</view>
				<view style="margin:0 24rpx;width:498rpx;">
					<view style="font-weight: bold;font-size: 30rpx;" v-if="details.unload_address">
						{{details.unload_address.address}}
						{{details.unload_address.detailed_address}}
					</view>
					<view style="font-size: 26rpx;color: #888888;margin-top:8rpx;">{{details.unload_address.user_name}}
						{{details.unload_address.mobile}}
					</view>
				</view>
			</view>
		</view>
		<view style="background: #FFFFFF;border-radius: 24rpx;padding:0 24rpx;margin:20rpx;">
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
				<view>发票</view>
				<view class="flex" style="align-items: center;">
					<view v-if="details.isinvoice==0" style="margin:0 32rpx 0 10rpx;">不开票</view>
					<view v-else style="margin:0 0 0 10rpx;">开票</view>
				</view>
			</view>
			<view style="background: #F7F7F7;border-radius: 20rpx;padding:0 24rpx;" v-if="details.isinvoice==1">
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>发票类型</view>
					<view v-if="details.type==1">专票</view>
					<view v-else>普票</view>
				</view>
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>选择税点</view>
					<view class="flex" style="align-items: center;">
						<view>{{details.tax_point}}</view>
					</view>
				</view>

				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>公司抬头 </view>
					<view>{{details.company_letterhead}}</view>
				</view>
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>公司税号</view>
					<view>{{details.company_tax_id}}</view>
				</view>
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>接收邮箱</view>
					<view>{{details.company_email}}</view>
				</view>
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>公司电话</view>
					<view>{{details.company_mobile}}</view>
				</view>
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>开户银行</view>
					<view>{{details.bank_deposits}}</view>
				</view>
			</view>
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
				<view>货物类型</view>
				<view v-if="typeList[typeIndex]">{{typeList[typeIndex].name}}</view>
			</view>

			<!-- <picker @change="bindPickerChangeType" :value="typeIndex" :range="typeList" range-key="name">
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>货物类型</view>
					<view class="flex" style="align-items: center;">
						<view v-if="typeList[typeIndex]">{{typeList[typeIndex].name}}</view>
						<view v-else>请选择</view>
						<image style="width:20rpx;height:20rpx;margin-left:12rpx;" src="/static/images/icon_2jt.png"></image>
					</view>
				</view>
			</picker> -->
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
				<view>数量</view>
				<view>{{details.quantity}}</view>
			</view>
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
				<view>尺寸</view>
				<view class="flex" style="align-items: center;">
					<input placeholder="长" v-model="details.long" disabled="disabled"
						style="width: 120rpx;height: 60rpx;background: #F7F7F7;border-radius: 12rpx;text-align: center;" />
					<view style="margin:0 12rpx;">x</view>
					<input placeholder="宽" v-model="details.wide" disabled="disabled"
						style="width: 120rpx;height: 60rpx;background: #F7F7F7;border-radius: 12rpx;text-align: center;" />
					<view style="margin:0 12rpx;">x</view>
					<input placeholder="高" v-model="details.hige" disabled="disabled"
						style="width: 120rpx;height: 60rpx;background: #F7F7F7;border-radius: 12rpx;text-align: center;" />
					<view style="margin-left:12rpx;">厘米</view>
				</view>
			</view>
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
				<view>总重量</view>
				<view class="flex" style="align-items: center;">
					<input placeholder="0" v-model="details.weight" disabled="disabled"
						style="width: 120rpx;height: 60rpx;background: #F7F7F7;border-radius: 12rpx;text-align: center;" />
					<view style="margin-left:12rpx;">吨</view>
				</view>
			</view>
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
				<view>总方位</view>
				<view class="flex" style="align-items: center;">
					<input placeholder="0" v-model="details.direction" disabled="disabled"
						style="width: 120rpx;height: 60rpx;background: #F7F7F7;border-radius: 12rpx;text-align: center;" />
					<view style="margin-left:12rpx;">立方米</view>
				</view>
			</view>
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
				<view>包装方式</view>
				<view>{{details.packaging_id}}</view>
			</view>
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
				<view>付款方式</view>
				<view>{{details.pay_type}}</view>
			</view>
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
				<view>代收货款</view>
				<view v-if="details.delivery=='代收货款'">是</view>
				<view v-else>否</view>
			</view>
			<view style="background: #F7F7F7;border-radius: 20rpx;padding:0 24rpx;" v-if="details.delivery=='代收货款'">
				<view style="padding:32rpx 0;border-bottom:2rpx solid #F7F7F7;">
					<view class="flexJusp" style="align-items: center;">
						<view>代收金额</view>
						<view>{{details.behalf_price}}</view>
					</view>
					<view style="font-size: 24rpx;color: #F29100;margin-top:8rpx;">按代收款总金额的0.3%,最低收费10元，最高收费100元</view>
				</view>

				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>银行网点</view>
					<view>{{details.bank_branch}}</view>
				</view>
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>开户账号</view>
					<view>{{details.open_number}}</view>
				</view>
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>银行类型</view>
					<view>{{details.bank_type}}</view>
				</view>
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>开户名</view>
					<view>{{details.open_name}}</view>
				</view>
				<picker @change="bindPickerChangekai" :value="kaiIndex" :range="kaiType">
					<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
						<view>开户类型</view>
						<view class="flex" style="align-items: center;">
							<view v-if="kaiType[kaiIndex]">{{kaiType[kaiIndex]}}</view>
							<view v-else>请选择</view>
							<image style="width:20rpx;height:20rpx;margin-left:12rpx;" src="/static/images/icon_2jt.png"></image>
						</view>
					</view>
				</picker>
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>返款时效</view>
					<view>(T+3)</view>
				</view>
			</view>

			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;" @click="fahuo">
				<view>发货时间</view>
				<view>{{details.earliest_time}} - {{details.latest_time}}</view>
			</view>

			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
				<view>其他运输要求</view>
				<view v-if="details.isrequirements==1">是</view>
				<view v-else>否</view>
			</view>

			<view style="background: #F7F7F7;border-radius: 20rpx;padding:0 24rpx;" v-if="details.isrequirements==1">
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>送货方式</view>
					<view>{{details.delivery_type_id}}</view>
				</view>
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>回单</view>
					<view>{{details.receipt_type_id}}</view>
				</view>
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>服务方式</view>
					<view>{{details.service}}</view>
				</view>
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>拆包服务</view>
					<view>{{details.unpack_id}}</view>
				</view>
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>控货要求</view>
					<view>{{details.control}}</view>
				</view>
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>其他费用</view>
					<view>{{details.other_id}}</view>
				</view>
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>短信通知</view>
					<view>{{details.text_message}}</view>
				</view>
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>装货费和卸货费</view>
					<view style="color: #F29100;">协商价格</view>
				</view>
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>仓储费</view>
					<view style="color: #F29100;">协商价格</view>
				</view>
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>打包装</view>
					<view style="color: #F29100;">协商价格</view>
				</view>
			</view>
		</view>
<!-- v-if="details.pay_status==1" -->
		<view class="post" >
			<view class="flexJusp" style="padding:20rpx 24rpx;">
				<view></view>
				<view class="flex">
					<view class="buttonx" v-if="details.pay_status==4" @click="chonglai">再来一单</view>
					<view class="buttonx" v-if="details.pay_status==1" @click="quxiao">取消订单</view>
					<view class="buttons" v-if="details.pay_status==1" @click="zhifu" style="margin-left:16rpx;">立即付款</view>
					
					<view class="buttons" v-if="details.pay_status==2||details.pay_status==3" @click="guiji" style="margin-left:16rpx;">物流轨迹</view>
					<!-- <view class="buttons"  @click="zhifu" style="margin-left:16rpx;">确认完成</view> -->
				</view>
			</view>
		</view>
		<view style="height:180rpx;"></view>

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
				imgUrl: this.$utils.imgUrl,
				banner: [],
				notice: [],
				carType: 0,
				kaipiao: 0,
				indexs: null,
				daishou: 0,
				num: 1,
				yaoqiu: 0,
				check: false,
				scrollTop: 0,
				id: '',
				details: {},
				carList: [],
				carIndex: 0,
				typeList: [],
				typeIndex: 0,
				baoList: [],
				baoIndex: 0,
				songList: [],
				songIndex: 0,
				huiList: [],
				huiIndex: 0,
				chaiList: [],
				chaiIndex: 0,
				qitaList: [],
				qitaIndex: 0,
				songhuoList: ['不上楼', '送货上楼', '送货进仓'],
				songhuoIndex: 0,
				fuwuList: ['派送', '自提'],
				fuwuIndex: 0,
				kaiType: ['个人', '公司'],
				kaiIndex: 0,
				kongType: ['到站点等通知放货', '到收货地等通知放货'],
				kongIndex: 0,
				xinType: ['收件方', '发件方'],
				xinIndex: 0,
			}
		},
		onLoad(option) {
			this.id = option.id
			this.getSystemInfo()
			this.loads()
		},
		onPageScroll(event) {
			const scrollTop = event.scrollTop // 获取当前页面滚动高度
			this.scrollTop = event.scrollTop
		},
		computed: {
			// 税点列表：专票=6%/9%，普票=1%/2%
			array() {
				return this.$utils.taxArray(this.details && this.details.type)
			}
		},
		methods: {
			guiji(){
				uni.navigateTo({
					url:'/pages/order/orderDetail/guiji'
				})
			},
			chonglai(){
				uni.setStorageSync('zaiOrder', this.details)
				uni.switchTab({
					url:'/pages/index/index'
				})
			},
			zhifu(){
				var that = this
				this.$httpapi('Wechatpay/pay', 'POST', {
					order_id:this.id
				}).then(res => {
					uni.requestPayment({
						provider: 'wxpay',
						timeStamp: res.data.timeStamp,
						nonceStr: res.data.nonceStr,
						package: res.data.package,
						signType: res.data.signType,
						paySign: res.data.paySign,
						success: function(rexx) {
							uni.showToast({
								title: '支付成功',
								duration: 1500,
								icon: 'none'
							});
							setTimeout(() => {
								that.loads()
							}, 1500)
						},
						fail: function(err) {}
					});
				})
			},
			quxiao(){
				this.$httpapi('Placeorder/CancelOrder', 'POST', {
					order_id:this.id
				}).then(res => {
					uni.showToast({
						title: res.msg,
						duration: 1500,
						icon: 'none'
					});
					if (res.code == 1) {
						setTimeout(() => {
							uni.navigateBack()
						}, 1500)
					}
				})
			},
			tuichu() {
				uni.navigateBack()
			},
			guanbi() {
				this.$refs.popup.close()
			},
			fahuo() {
				this.$refs.popup.open('bottom')
			},
			bindPickerChange(e) {
				this.index = e.detail.value
			},
			loads() {
				this.$httpapi('Placeorder/orderdetaile', 'POST', {
					order_id: this.id
				}).then(rex => {
					this.details = rex.data
					for (let i = 0; i < this.array.length; i++) {
						if (this.array[i] == rex.data.tax_point) {
							this.indexs = i
						}
					}

					this.$httpapi('Placeorder/car_type', 'POST', {}).then(res => {
						this.carList = res.data
					})
					this.$httpapi('Placeorder/goods_type', 'GET', {}).then(res => {
						this.typeList = res.data
						for (let i = 0; i < res.data.length; i++) {
							if (res.data[i].id == rex.data.goods_type_id) {
								this.typeIndex = i
							}
						}
					})
					this.$httpapi('Placeorder/delivery_type', 'GET', {}).then(res => {
						this.songList = res.data
						for (let i = 0; i < res.data.length; i++) {
							if (res.data[i].id == rex.data.delivery_type_id) {
								this.songIndex = i
							}
						}
					})
					this.$httpapi('Placeorder/other', 'GET', {}).then(res => {
						this.qitaList = res.data
					})
					this.$httpapi('Placeorder/receipt_type', 'GET', {}).then(res => {
						this.huiList = res.data
					})
					this.$httpapi('Placeorder/unpack', 'GET', {}).then(res => {
						this.chaiList = res.data
					})
				})
			}
		}
	}
</script>

<style lang="scss">
	.post {
		position: fixed;
		bottom: 0;
		left: 0;
		width: 750rpx;
		background-color: #FFF;
	}

	.buttons {
		width: 160rpx;
		height: 56rpx;
		background: linear-gradient(180deg, #4897FF 0%, #4878FF 100%);
		border-radius: 32rpx;
		font-size: 26rpx;
		color: #FFFFFF;
		line-height: 56rpx;
		text-align: center;
	}

	.buttonx {
		width: 160rpx;
		height: 56rpx;
		background: #FFFFFF;
		border-radius: 32rpx;
		border: 1rpx solid #4878FF;
		font-size: 26rpx;
		color: #4878FF;
		line-height: 56rpx;
		text-align: center;
	}
</style>

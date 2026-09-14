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
				<view>下单时间</view>
				<view>{{details.createtime}}</view>
			</view>
			<view class="flexJusp" style="align-items: center;height:104rpx;border-bottom:2rpx solid #F7F7F7;">
				<view>订单状态</view>
				<view style="color: #F29100;">
					<text v-if="details.pay_status==1">待付款</text>
					<text v-if="details.pay_status==2">服务中</text>
					<text v-if="details.pay_status==3">已付款</text>
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
				style="height: 100%px;background: #F7F7F7;border-radius: 20rpx;align-items: center;padding:0 20rpx;">
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
				style="height:  100%px;background: #F7F7F7;border-radius: 20rpx;align-items: center;padding:0 20rpx;margin-top:20rpx;">
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
				<view>{{details.goods_type_id}}</view>
			</view>
			
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
				<view>货物图片</view>
				<image style="width:120rpx;height:120rpx;" @click="yulan" :src="imgUrl+details.goods_image"></image>
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
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;padding:20rpx 0;">
				<view>尺寸</view>
				<view style="flex:1;margin-left:20rpx;">
					<!-- 多尺寸模式：后端返回 details.sizeList 数组 -->
					<view v-if="details.sizeList && details.sizeList.length">
						<view
							v-for="(item, index) in details.sizeList"
							:key="index"
							class="flex"
							style="align-items: center;margin-bottom:12rpx;"
						>
							<input
								placeholder="长"
								v-model="item.long"
								disabled="disabled"
								style="width: 120rpx;height: 60rpx;background: #F7F7F7;border-radius: 12rpx;text-align: center;"
							/>
							<view style="margin:0 12rpx;">x</view>
							<input
								placeholder="宽"
								v-model="item.wide"
								disabled="disabled"
								style="width: 120rpx;height: 60rpx;background: #F7F7F7;border-radius: 12rpx;text-align: center;"
							/>
							<view style="margin:0 12rpx;">x</view>
							<input
								placeholder="高"
								v-model="item.hige"
								disabled="disabled"
								style="width: 120rpx;height: 60rpx;background: #F7F7F7;border-radius: 12rpx;text-align: center;"
							/>
							<view style="margin-left:12rpx;">米</view>
						</view>
					</view>
					<!-- 单尺寸模式：兼容旧数据，只用 long / wide / hige 字段 -->
					<view v-else class="flex" style="align-items: center;">
						<input
							placeholder="长"
							v-model="details.long"
							disabled="disabled"
							style="width: 120rpx;height: 60rpx;background: #F7F7F7;border-radius: 12rpx;text-align: center;"
						/>
						<view style="margin:0 12rpx;">x</view>
						<input
							placeholder="宽"
							v-model="details.wide"
							disabled="disabled"
							style="width: 120rpx;height: 60rpx;background: #F7F7F7;border-radius: 12rpx;text-align: center;"
						/>
						<view style="margin:0 12rpx;">x</view>
						<input
							placeholder="高"
							v-model="details.hige"
							disabled="disabled"
							style="width: 120rpx;height: 60rpx;background: #F7F7F7;border-radius: 12rpx;text-align: center;"
						/>
						<view style="margin-left:12rpx;">米</view>
					</view>
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
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;padding:20rpx 0;">
				<view>包装方式</view>
				<view style="flex:1;margin-left:20rpx;">
					<!-- 多包装模式：后端返回 details.packaging_list 数组 -->
					<view v-if="details.packaging_list && details.packaging_list.length">
						<view
							v-for="(item, index) in details.packaging_list"
							:key="index"
							class="flex"
							style="align-items: center;margin-bottom:8rpx;"
						>
							<view>{{item.name}}</view>
							<view style="margin-left:12rpx;color:#888;">x {{item.quantity}}</view>
						</view>
					</view>
					<!-- 单包装模式：兼容旧数据，只用 packaging_id 字段 -->
					<view v-else>
						{{details.packaging_id}}
					</view>
				</view>
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
				<view v-if="details && details.delivery_type_id" class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>送货方式</view>
					<view>{{details.delivery_type_id}}</view>
				</view>
				<view v-if="details && details.receipt_type_id" class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>回单</view>
					<view>{{details.receipt_type_id}}</view>
				</view>
				<view v-if="details && details.details.service" class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>服务方式</view>
					<view>{{details.service}}</view>
				</view>
				<view v-if="details && details.details.unpack_id" class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
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

			<!-- 额外成本 -->
			<view style="background: #F7F7F7;border-radius: 20rpx;padding:0 24rpx;margin-top:20rpx;" v-if="details.cost_extra_price && details.cost_extra_price.length">
				<view style="padding:24rpx 0 16rpx;font-weight: bold;font-size: 30rpx;">额外成本</view>
				<view v-for="(item, idx) in details.cost_extra_price" :key="'cost_'+idx" style="border-bottom:2rpx solid #E5E5E5;padding:16rpx 0;">
					<view class="flexJusp" style="align-items: center;height:72rpx;">
						<view style="font-size: 28rpx;color:#666;">金额</view>
						<view style="color: #F29100;font-size: 28rpx;">{{ item.price || item.amount || '-' }}</view>
					</view>
					<view class="flexJusp" style="align-items: center;min-height:72rpx;">
						<view style="font-size: 28rpx;color:#666;">备注</view>
						<view style="font-size: 28rpx;flex:1;margin-left:20rpx;text-align:right;">{{ item.remarks || '-' }}</view>
					</view>
				</view>
			</view>
			<view style="background: #F7F7F7;border-radius: 20rpx;padding:0 24rpx;margin-top:20rpx;" v-if="details.cost_extra_price && details.cost_extra_price.length">
				<view style="padding:24rpx 0 16rpx;font-weight: bold;font-size: 30rpx;">专线额外成本</view>
				<view v-for="(item, idx) in details.logistics_extra_price" :key="'cost_'+idx" style="border-bottom:2rpx solid #E5E5E5;padding:16rpx 0;">
					<view class="flexJusp" style="align-items: center;height:72rpx;">
						<view style="font-size: 28rpx;color:#666;">金额</view>
						<view style="color: #F29100;font-size: 28rpx;">{{ item.price || item.amount || '-' }}</view>
					</view>
					<view class="flexJusp" style="align-items: center;min-height:72rpx;">
						<view style="font-size: 28rpx;color:#666;">备注</view>
						<view style="font-size: 28rpx;flex:1;margin-left:20rpx;text-align:right;">{{ item.remarks || '-' }}</view>
					</view>
				</view>
			</view>
			<!-- 司机额外费用（三项：类型、金额、备注） -->
			<view style="background: #F7F7F7;border-radius: 20rpx;padding:0 24rpx;margin-top:20rpx;" v-if="details.dirverother && details.dirverother.length">
				<view style="padding:24rpx 0 16rpx;font-weight: bold;font-size: 30rpx;">司机额外费用</view>
				<view v-for="(item, idx) in details.dirverother" :key="'driver_'+idx" style="border-bottom:2rpx solid #E5E5E5;padding:16rpx 0;">
					<view class="flexJusp" style="align-items: center;height:72rpx;">
						<view style="font-size: 28rpx;color:#666;">类型</view>
						<view style="font-size: 28rpx;">{{ item.type || '-' }}</view>
					</view>
					<view class="flexJusp" style="align-items: center;height:72rpx;">
						<view style="font-size: 28rpx;color:#666;">金额</view>
						<view style="color: #F29100;font-size: 28rpx;">{{ item.price || '-' }}</view>
					</view>
					<view class="flexJusp" style="align-items: center;min-height:72rpx;">
						<view style="font-size: 28rpx;color:#666;">备注</view>
						<view style="font-size: 28rpx;flex:1;margin-left:20rpx;text-align:right;">{{ item.remarks || '-' }}</view>
					</view>
				</view>
			</view>
			<!-- 运费额外成本 -->
			<view style="background: #F7F7F7;border-radius: 20rpx;padding:0 24rpx;margin-top:20rpx;" v-if="details.order_extra_price && details.order_extra_price.length">
				<view style="padding:24rpx 0 16rpx;font-weight: bold;font-size: 30rpx;">额外运费</view>
				<view v-for="(item, idx) in details.order_extra_price" :key="'order_'+idx" style="border-bottom:2rpx solid #E5E5E5;padding:16rpx 0;">
					<view class="flexJusp" style="align-items: center;height:72rpx;">
						<view style="font-size: 28rpx;color:#666;">金额</view>
						<view style="color: #F29100;font-size: 28rpx;">{{ item.price || item.amount || '-' }}</view>
					</view>
					<view class="flexJusp" style="align-items: center;min-height:72rpx;">
						<view style="font-size: 28rpx;color:#666;">备注</view>
						<view style="font-size: 28rpx;flex:1;margin-left:20rpx;text-align:right;">{{ item.remarks || '-' }}</view>
					</view>
				</view>
			</view>
		</view>
		<!-- 司机反馈图片板块 -->
		<view v-if="hasDriverImages" style="background: #FFFFFF;border-radius: 24rpx;padding:0 24rpx;margin:20rpx;">
			<view style="font-weight: bold;font-size: 32rpx;padding:24rpx 0;border-bottom:2rpx solid #F7F7F7;">司机反馈图片</view>

			<!-- 取货司机装货 -->
			<view v-if="pickupLoadImagesList && pickupLoadImagesList.length" style="padding:24rpx 0;border-bottom:2rpx solid #F7F7F7;">
				<view style="font-size: 28rpx;color: #333;margin-bottom:16rpx;">取货司机装货</view>
				<view class="flex" style="flex-wrap: wrap;">
					<image
						v-for="(img, index) in pickupLoadImagesList"
						:key="index"
						:src="imgUrl + img"
						@click="previewDriverImages('pickup_load', index)"
						style="width:160rpx;height:160rpx;border-radius: 12rpx;margin-right:16rpx;margin-bottom:16rpx;"
						mode="aspectFill"
					></image>
				</view>
			</view>

			<!-- 取货司机卸货 -->
			<view v-if="pickupUnloadImagesList && pickupUnloadImagesList.length" style="padding:24rpx 0;border-bottom:2rpx solid #F7F7F7;">
				<view style="font-size: 28rpx;color: #333;margin-bottom:16rpx;">取货司机卸货</view>
				<view class="flex" style="flex-wrap: wrap;">
					<image
						v-for="(img, index) in pickupUnloadImagesList"
						:key="index"
						:src="imgUrl + img"
						@click="previewDriverImages('pickup_unload', index)"
						style="width:160rpx;height:160rpx;border-radius: 12rpx;margin-right:16rpx;margin-bottom:16rpx;"
						mode="aspectFill"
					></image>
				</view>
			</view>

			<!-- 专线装货 -->
			<view v-if="lineLoadImagesList && lineLoadImagesList.length" style="padding:24rpx 0;border-bottom:2rpx solid #F7F7F7;">
				<view style="font-size: 28rpx;color: #333;margin-bottom:16rpx;">专线装货</view>
				<view class="flex" style="flex-wrap: wrap;">
					<image
						v-for="(img, index) in lineLoadImagesList"
						:key="index"
						:src="imgUrl + img"
						@click="previewDriverImages('line_load', index)"
						style="width:160rpx;height:160rpx;border-radius: 12rpx;margin-right:16rpx;margin-bottom:16rpx;"
						mode="aspectFill"
					></image>
				</view>
			</view>

			<!-- 专线卸货 -->
			<view v-if="lineUnloadImagesList && lineUnloadImagesList.length" style="padding:24rpx 0;border-bottom:2rpx solid #F7F7F7;">
				<view style="font-size: 28rpx;color: #333;margin-bottom:16rpx;">专线卸货</view>
				<view class="flex" style="flex-wrap: wrap;">
					<image
						v-for="(img, index) in lineUnloadImagesList"
						:key="index"
						:src="imgUrl + img"
						@click="previewDriverImages('line_unload', index)"
						style="width:160rpx;height:160rpx;border-radius: 12rpx;margin-right:16rpx;margin-bottom:16rpx;"
						mode="aspectFill"
					></image>
				</view>
			</view>

			<!-- 送货司机装货 -->
			<view v-if="deliveryLoadImagesList && deliveryLoadImagesList.length" style="padding:24rpx 0;border-bottom:2rpx solid #F7F7F7;">
				<view style="font-size: 28rpx;color: #333;margin-bottom:16rpx;">送货司机装货</view>
				<view class="flex" style="flex-wrap: wrap;">
					<image
						v-for="(img, index) in deliveryLoadImagesList"
						:key="index"
						:src="imgUrl + img"
						@click="previewDriverImages('delivery_load', index)"
						style="width:160rpx;height:160rpx;border-radius: 12rpx;margin-right:16rpx;margin-bottom:16rpx;"
						mode="aspectFill"
					></image>
				</view>
			</view>

			<!-- 送货司机卸货 -->
			<view v-if="deliveryUnloadImagesList && deliveryUnloadImagesList.length" style="padding:24rpx 0;border-bottom:2rpx solid #F7F7F7;">
				<view style="font-size: 28rpx;color: #333;margin-bottom:16rpx;">送货司机卸货</view>
				<view class="flex" style="flex-wrap: wrap;">
					<image
						v-for="(img, index) in deliveryUnloadImagesList"
						:key="index"
						:src="imgUrl + img"
						@click="previewDriverImages('delivery_unload', index)"
						style="width:160rpx;height:160rpx;border-radius: 12rpx;margin-right:16rpx;margin-bottom:16rpx;"
						mode="aspectFill"
					></image>
				</view>
			</view>

			<!-- 回单 -->
			<view v-if="receiptImagesList && receiptImagesList.length" style="padding:24rpx 0;border-bottom:2rpx solid #F7F7F7;">
				<view style="font-size: 28rpx;color: #333;margin-bottom:16rpx;">回单</view>
				<view class="flex" style="flex-wrap: wrap;">
					<image
						v-for="(img, index) in receiptImagesList"
						:key="index"
						:src="imgUrl + img"
						@click="previewDriverImages('receipt', index)"
						style="width:160rpx;height:160rpx;border-radius: 12rpx;margin-right:16rpx;margin-bottom:16rpx;"
						mode="aspectFill"
					></image>
				</view>
			</view>

			<!-- 物流单子（monad_image） -->
			<view v-if="userType != 1 && monadImagesList && monadImagesList.length" style="padding:24rpx 0;">
				<view style="font-size: 28rpx;color: #333;margin-bottom:16rpx;">物流单子</view>
				<view class="flex" style="flex-wrap: wrap;">
					<image
						v-for="(img, index) in monadImagesList"
						:key="index"
						:src="imgUrl + img"
						@click="previewDriverImages('monad', index)"
						style="width:160rpx;height:160rpx;border-radius: 12rpx;margin-right:16rpx;margin-bottom:16rpx;"
						mode="aspectFill"
					></image>
				</view>
			</view>
		</view>
		<view v-if="userType != 1" style="background: #FFFFFF;border-radius: 24rpx;padding:0 24rpx;margin:20rpx;">
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
				<view>取货司机总成本</view>
				<view style="color: #F29100;">{{details.pickup_driver_fee || '0'}}元</view>
			</view>
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
				<view>取货司机距离</view>
				<view>{{details.pickup_distance || '0'}}KM</view>
			</view>
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
				<view>送货司机总成本</view>
				<view style="color: #F29100;">{{details.shipment_driver_fee || '0'}}元</view>
			</view>
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
				<view>送货司机距离</view>
				<view>{{details.shipmenty_distance || '0'}}KM</view>
			</view>
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
				<view >物流专线成本</view>
				<view style="color: #F29100;">{{details.logistics_driver_cost || '0'}}元</view>
			</view>
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
				<view>物流专线距离</view>
				<view>{{details.logistics_distance || '0'}}KM</view>
			</view>
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
				<view >信息费</view>
				<view style="color: #F29100;">{{details.information || '0'}}元</view>
			</view>
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
				<view >不可退定金</view>
				<view style="color: #F29100;">{{details.deposit || '0'}}元</view>
			</view>
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
				<view >平台抽佣</view>
				<view style="color: #F29100;">{{details.platform_commission || '0'}}元</view>
			</view>
			<view class="flexJusp" style="align-items: center;height:104rpx;">
				<view >总成本</view>
				<view style="color: #F29100;">{{details.cost_cont}}元</view>
			</view>
			<view class="flexJusp" style="align-items: center;height:104rpx;">
				<view >总运费</view>
				<view style="color: #F29100;">{{details.pay_price}}元</view>
			</view>
			<view class="flexJusp" style="align-items: center;height:104rpx;">
				<view >利润</view>
				<view style="color: #F29100;">{{profit}}元</view>
			</view>
			<view class="flexJusp" style="align-items: center;height:104rpx;">
				<view >发货物流名称</view>
				<view>{{details.shipping_name}}</view>
			</view>
			<view class="flexJusp" style="align-items: center;height:104rpx;">
				<view >发货物流地址</view>
				<view>{{details.shipping_address}}</view>
			</view>
			<view class="flexJusp" style="align-items: center;height:104rpx;">
				<view>到货物流名称</view>
				<view>{{details.arrival_name}}</view>
			</view>
			<view class="flexJusp" style="align-items: center;height:104rpx;">
				<view>到货物流地址</view>
				<view>{{details.arrival_address}}</view>
			</view>
		</view>

		<!-- 司机 / 车辆 / 快递信息（deliveryinfo） -->
		<view
			v-if="deliveryinfo && Object.keys(deliveryinfo).length"
			style="background:#FFFFFF;border-radius:24rpx;padding:0 24rpx;margin:20rpx;"
		>
			<view style="font-weight:bold;font-size:32rpx;padding:24rpx 0;border-bottom:2rpx solid #F7F7F7;">
				司机及快递信息
			</view>

			<!-- 司机车牌号 -->
			<view
				v-if="deliveryinfo.car_num"
				class="flexJusp"
				style="align-items:center;border-bottom:2rpx solid #F7F7F7;height:104rpx;"
			>
				<view>司机车牌号</view>
				<view>{{deliveryinfo.car_num}}</view>
			</view>

			<!-- 司机姓名 -->
			<view
				v-if="deliveryinfo.driver_name"
				class="flexJusp"
				style="align-items:center;border-bottom:2rpx solid #F7F7F7;height:104rpx;"
			>
				<view>司机姓名</view>
				<view>{{deliveryinfo.driver_name}}</view>
			</view>

			<!-- 司机电话 -->
			<view
				v-if="deliveryinfo.driver_num"
				class="flexJusp"
				style="align-items:center;border-bottom:2rpx solid #F7F7F7;height:104rpx;"
			>
				<view>司机电话</view>
				<view>{{deliveryinfo.driver_num}}</view>
			</view>

			<!-- 行驶证照片 -->
			<view
				v-if="deliveryinfo.vehicle_img"
				class="flexJusp"
				style="align-items:center;border-bottom:2rpx solid #F7F7F7;height:150rpx;"
			>
				<view>行驶证照片</view>
				<image
					:src="imgUrl + deliveryinfo.vehicle_img"
					mode="aspectFill"
					style="width:120rpx;height:120rpx;border-radius:12rpx;"
					@click="previewSingleImage(imgUrl + deliveryinfo.vehicle_img)"
				></image>
			</view>

			<!-- 驾驶证照片 -->
			<view
				v-if="deliveryinfo.license_img"
				class="flexJusp"
				style="align-items:center;border-bottom:2rpx solid #F7F7F7;height:150rpx;"
			>
				<view>驾驶证照片</view>
				<image
					:src="imgUrl + deliveryinfo.license_img"
					mode="aspectFill"
					style="width:120rpx;height:120rpx;border-radius:12rpx;"
					@click="previewSingleImage(imgUrl + deliveryinfo.license_img)"
				></image>
			</view>
			<!-- 快递名称 -->
			<view
				v-if="deliveryinfo.delivery_name"
				class="flexJusp"
				style="align-items:center;border-bottom:2rpx solid #F7F7F7;height:104rpx;"
			>
				<view>快递名称</view>
				<view>{{deliveryinfo.delivery_name}}</view>
			</view>

			<!-- 快递单号 -->
			<view
				v-if="deliveryinfo.delivery_num"
				class="flexJusp"
				style="align-items:center;height:104rpx;"
			>
				<view>快递单号</view>
				<view>{{deliveryinfo.delivery_num}}</view>
			</view>
		</view>

		<!-- v-if="details.pay_status==1" -->
		<view class="post" >
			<view class="flexJusp" style="padding:20rpx 24rpx;">
				<view></view>
				<view class="flex">
					<view class="buttonx" v-if="details.pay_status==4" @click="chonglai">再来一单</view>
					<view class="buttonx" v-if="details.logistics_status==1 && details.pay_status==1" @click="quxiao">取消订单</view>
					<view class="buttons" v-if="details.pay_status==1" @click="zhifu" style="margin-left:16rpx;">立即付款</view>
					
					<view class="buttons" v-if="details.pay_status==1||details.pay_status==2||details.pay_status==3" @click="guiji" style="margin-left:16rpx;">物流轨迹</view>
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
				// 司机 / 车辆 / 快递 信息
				deliveryinfo: {},
				id: '',
				details: {},
				userType: null,
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
			this.getUserInfo()
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
			},
			totalCost() {
				const pickup = parseFloat(this.details.pickup_driver_fee) || 0
				const shipment = parseFloat(this.details.shipment_driver_fee) || 0
				const logistics = parseFloat(this.details.logistics_driver_cost) || 0
				return (pickup + shipment + logistics).toFixed(2)
			},
			profit() {
				const payPrice = parseFloat(this.details.pay_price) || 0
				const cost = parseFloat(this.details.cost_cont) || 0
				return (payPrice - cost).toFixed(2) 
			},
			// 取货司机装货图片列表
			pickupLoadImagesList() {
				if (!this.details.pickup_load_image) return []
				if (Array.isArray(this.details.pickup_load_image)) {
					return this.details.pickup_load_image.filter(img => img)
				}
				if (typeof this.details.pickup_load_image === 'string') {
					return this.details.pickup_load_image.split(',').filter(img => img.trim())
				}
				return []
			},
			// 取货司机卸货图片列表
			pickupUnloadImagesList() {
				if (!this.details.pickup_unload_image) return []
				if (Array.isArray(this.details.pickup_unload_image)) {
					return this.details.pickup_unload_image.filter(img => img)
				}
				if (typeof this.details.pickup_unload_image === 'string') {
					return this.details.pickup_unload_image.split(',').filter(img => img.trim())
				}
				return []
			},
			// 专线装货图片列表
			lineLoadImagesList() {
				if (!this.details.line_load_image) return []
				if (Array.isArray(this.details.line_load_image)) {
					return this.details.line_load_image.filter(img => img)
				}
				if (typeof this.details.line_load_image === 'string') {
					return this.details.line_load_image.split(',').filter(img => img.trim())
				}
				return []
			},
			// 专线卸货图片列表
			lineUnloadImagesList() {
				if (!this.details.line_unload_image) return []
				if (Array.isArray(this.details.line_unload_image)) {
					return this.details.line_unload_image.filter(img => img)
				}
				if (typeof this.details.line_unload_image === 'string') {
					return this.details.line_unload_image.split(',').filter(img => img.trim())
				}
				return []
			},
			// 送货司机装货图片列表
			deliveryLoadImagesList() {
				if (!this.details.delivery_load_image) return []
				if (Array.isArray(this.details.delivery_load_image)) {
					return this.details.delivery_load_image.filter(img => img)
				}
				if (typeof this.details.delivery_load_image === 'string') {
					return this.details.delivery_load_image.split(',').filter(img => img.trim())
				}
				return []
			},
			// 送货司机卸货图片列表
			deliveryUnloadImagesList() {
				if (!this.details.delivery_unload_image) return []
				if (Array.isArray(this.details.delivery_unload_image)) {
					return this.details.delivery_unload_image.filter(img => img)
				}
				if (typeof this.details.delivery_unload_image === 'string') {
					return this.details.delivery_unload_image.split(',').filter(img => img.trim())
				}
				return []
			},
			// 回单图片列表
			receiptImagesList() {
				const source = this.details.receipt_images || this.details.receipt_image || ''
				if (!source) return []
				if (Array.isArray(source)) {
					return source.filter(img => img)
				}
				if (typeof source === 'string') {
					return source.split(',').filter(img => img.trim())
				}
				return []
			},
			// 物流单子 monad_image 图片列表
			monadImagesList() {
				const source = this.details.monad_image || this.details.monad_images || ''
				if (!source) return []
				if (Array.isArray(source)) {
					return source.filter(img => img)
				}
				if (typeof source === 'string') {
					return source.split(',').filter(img => img.trim())
				}
				return []
			},
			// 兼容旧字段：装货图片列表
			loadingImagesList() {
				if (!this.details.loading_imges) return []
				if (Array.isArray(this.details.loading_imges)) {
					return this.details.loading_imges.filter(img => img)
				}
				if (typeof this.details.loading_imges === 'string') {
					return this.details.loading_imges.split(',').filter(img => img.trim())
				}
				return []
			},
			// 兼容旧字段：卸货图片列表
			unloadingImagesList() {
				if (!this.details.unloading_images) return []
				if (Array.isArray(this.details.unloading_images)) {
					return this.details.unloading_images.filter(img => img)
				}
				if (typeof this.details.unloading_images === 'string') {
					return this.details.unloading_images.split(',').filter(img => img.trim())
				}
				return []
			}, 
			// 判断是否有司机反馈图片（新老字段都算）
			hasDriverImages() {
				const lists = [
					this.pickupLoadImagesList,
					this.pickupUnloadImagesList,
					this.lineLoadImagesList,
					this.lineUnloadImagesList,
					this.deliveryLoadImagesList,
					this.deliveryUnloadImagesList,
					this.receiptImagesList,
					this.monadImagesList,
					this.loadingImagesList,
					this.unloadingImagesList
				]
				return lists.some(list => list && list.length > 0)
			}
		},
		methods: {
			getUserInfo() {
				this.$httpapi('Opinion/getuserinfo', 'GET', {}).then(res => {
					this.userType = res.data.membertype || res.data.user_type || null
				}).catch(() => {
					// 如果获取失败，尝试从storage获取
					this.userType = uni.getStorageSync('userType') || null
				})
			},
			yulan(){
				var list = []
				list.push(this.imgUrl + this.details.goods_image)
				console.log(list, '预览图片')
				uni.previewImage({
					current: 0,
					urls: list,
					longPressActions: {
						success: function(data) {
							console.log('选中了第' + (data.tapIndex + 1) + '个按钮,第' + (data.index + 1) + '张图片');
						},
						fail: function(err) {
							console.log(err.errMsg);
						}
					}
				});
			},
			// 预览单张图片（司机证件、快递单等）
			previewSingleImage(url) {
				if (!url) return
				uni.previewImage({
					current: 0,
					urls: [url]
				})
			},
			// 预览司机反馈图片
			previewDriverImages(type, index) {
				let imageList = []
				let currentIndex = index || 0

				// 新字段
				if (type === 'pickup_load' && this.pickupLoadImagesList.length > 0) {
					imageList = this.pickupLoadImagesList.map(img => this.imgUrl + img)
				} else if (type === 'pickup_unload' && this.pickupUnloadImagesList.length > 0) {
					imageList = this.pickupUnloadImagesList.map(img => this.imgUrl + img)
				} else if (type === 'line_load' && this.lineLoadImagesList.length > 0) {
					imageList = this.lineLoadImagesList.map(img => this.imgUrl + img)
				} else if (type === 'line_unload' && this.lineUnloadImagesList.length > 0) {
					imageList = this.lineUnloadImagesList.map(img => this.imgUrl + img)
				} else if (type === 'delivery_load' && this.deliveryLoadImagesList.length > 0) {
					imageList = this.deliveryLoadImagesList.map(img => this.imgUrl + img)
				} else if (type === 'delivery_unload' && this.deliveryUnloadImagesList.length > 0) {
					imageList = this.deliveryUnloadImagesList.map(img => this.imgUrl + img)
				} else if (type === 'receipt' && this.receiptImagesList.length > 0) {
					imageList = this.receiptImagesList.map(img => this.imgUrl + img)
				} else if (type === 'monad' && this.monadImagesList.length > 0) {
					imageList = this.monadImagesList.map(img => this.imgUrl + img)
				}

				// 兼容旧字段调用
				if (!imageList.length) {
					if (type === 'loading' && this.loadingImagesList.length > 0) {
						imageList = this.loadingImagesList.map(img => this.imgUrl + img)
					} else if (type === 'unloading' && this.unloadingImagesList.length > 0) {
						imageList = this.unloadingImagesList.map(img => this.imgUrl + img)
					}
				}

				if (imageList.length > 0) {
					uni.previewImage({
						current: currentIndex,
						urls: imageList
					})
				}
			},
			guiji(){
				uni.navigateTo({
					url:'/pages/order/orderDetail/guiji?code='+this.details.orderid+'&id='+this.details.id
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
					// 后端如果返回 deliveryinfo，就赋值给本地；否则用空对象避免报错
					this.deliveryinfo = rex.data.deliveryinfo || {}
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

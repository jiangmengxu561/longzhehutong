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

		<view v-if="pageLoading && !fromSubmit" class="page-loading">
			<view class="page-loading-text">加载中...</view>
		</view>
		<block v-if="!pageLoading">
		<view style="background: #FFFFFF;border-radius: 24rpx;padding:0 24rpx;margin:20rpx;">
			<view class="flexJusp" style="align-items: center;height:104rpx;border-bottom:2rpx solid #F7F7F7;">
				<view>预计到货时间</view>
				<view>{{details.arrivaltime}}天</view>
			</view>
			<view class="flexJusp"   style="align-items: center;height:104rpx;border-bottom:2rpx solid #F7F7F7;">
				<view>总运费</view>
				<view class="flex" style="align-items: center;">
					<!-- 仅内部员工可修改总运费 -->
					<input
						v-if="isInternalEmployee"
						v-model="editPrice"
						type="digit"
						:placeholder="details.pay_price || '请输入总运费'"
						@blur="updatePrice"
						style="width: 160rpx;height: 60rpx;background: #F7F7F7;border-radius: 12rpx;text-align: center;color: #F29100;font-size: 28rpx;"
					/>
					<view
						v-else
						style="margin-left:12rpx;color: #F29100;"
					>{{details.pay_price}}</view>
				</view> 
			</view>
			<view class="flexJusp" v-if="isPartTimeEmployee && isInternalEmployee" style="align-items: center;height:104rpx;border-bottom:2rpx solid #F7F7F7;">
				<view>平台抽佣{{commissionRate}}%</view>
				<view style="color: #F29100;">{{commissionAmount}}</view>
			</view>
			<!-- <view class="flexJusp" v-if="isPartTimeEmployee && isInternalEmployee" style="align-items: center;height:104rpx;border-bottom:2rpx solid #F7F7F7;">
				<view>含抽佣总成本</view>
				<view style="color: #F29100;">{{commissionCost}}</view>
			</view> -->
			<view class="flexJusp"  v-if="isInternalEmployee" style="align-items: center;height:104rpx;border-bottom:2rpx solid #F7F7F7;">
				<view>总成本</view>
				<view class="flex" style="align-items: center;">
					<view style="margin-left:12rpx;color: #F29100;">{{details.cost_cont}}</view>
				</view>
			</view>
			<view class="flexJusp" v-if="isInternalEmployee" style="align-items: center;height:104rpx;border-bottom:2rpx solid #F7F7F7;">
				<view>取货距离</view>
				<view style="color: #000000;">{{details.pickup_distance}}</view>
			</view>
			<view class="flexJusp" v-if="isInternalEmployee" style="align-items: center;height:104rpx;border-bottom:2rpx solid #F7F7F7;">
				<view>取货成本费用</view>
				<view style="color: #F29100;">{{details.pickup_driver_fee}}</view>
			</view>
			<view class="flexJusp" v-if="isInternalEmployee" style="align-items: center;height:104rpx;border-bottom:2rpx solid #F7F7F7;">
				<view>送货距离</view>
				<view style="color: #000000;">{{details.shipmenty_distance}}</view>
			</view>
			<view class="flexJusp" v-if="isInternalEmployee" style="align-items: center;height:104rpx;border-bottom:2rpx solid #F7F7F7;">
				<view>送货成本费用</view>
				<view style="color: #F29100;">{{details.shipment_driver_fee}}</view>
			</view>
			<view class="flexJusp" v-if="isInternalEmployee" style="align-items: center;height:104rpx;border-bottom:2rpx solid #F7F7F7;">
				<view>专线距离</view>
				<view style="color: #000000;">{{details.logistics_distance}}</view>
			</view>
			<view class="flexJusp" v-if="isInternalEmployee" style="align-items: center;height:104rpx;border-bottom:2rpx solid #F7F7F7;">
				<view>专线成本费用</view>
				<view style="color: #F29100;">{{details.logistics_driver_cost}}</view>
			</view>
			<view class="flexJusp" v-if="isInternalEmployee" style="align-items: center;height:104rpx;border-bottom:2rpx solid #F7F7F7;">
				<view>信息费</view>
				<view style="color: #F29100;">{{details.information}}</view>
			</view>
			<view class="flexJusp" v-if="isInternalEmployee" style="align-items: center;height:104rpx;border-bottom:2rpx solid #F7F7F7;">
				<view>不可退定金</view>
				<view style="color: #F29100;">{{details.deposit}}</view>
			</view>
			<view class="flexJusp" v-if="isInternalEmployee" style="align-items: center;height:104rpx;border-bottom:2rpx solid #F7F7F7;">
				<view>总距离</view>
				<view style="color: #000000;">
					{{
						((parseFloat(details.pickup_distance) || 0) + 
						 (parseFloat(details.shipmenty_distance) || 0) + 
						 (parseFloat(details.logistics_distance) || 0)).toFixed(2)
					}}
				</view>
			</view>
			<view class="flexJusp" v-if="isInternalEmployee" style="align-items: center;height:104rpx;border-bottom:2rpx solid #F7F7F7;">
				<view>发货物流名称</view>
				<view style="color: #F29100;">{{details.shipping_name}}</view>
			</view>
			<view class="flexJusp" v-if="isInternalEmployee" style="align-items: center;height:104rpx;border-bottom:2rpx solid #F7F7F7;">
				<view>发货物流地址</view>
				<view style="color: #F29100;">{{details.shipping_address}}</view>
			</view>
			<view
				class="flexJusp alt-field-row--phone"
				v-if="isInternalEmployee"
				style="min-height:104rpx;padding:20rpx 0;border-bottom:2rpx solid #F7F7F7;"
			>
				<view style="flex-shrink:0;margin-right:20rpx;">发货物流电话</view>
				<view class="alt-phone-list">
					<view
						v-for="(phone, phoneIdx) in parsePhoneList(details.shipping_mobile)"
						:key="phoneIdx"
						class="alt-phone-item"
						@click.stop="callLogisticsPhone(phone)"
					>{{ phone }}</view>
				</view>
			</view>
			<view class="flexJusp" v-if="isInternalEmployee" style="align-items: center;height:104rpx;border-bottom:2rpx solid #F7F7F7;">
				<view>到货物流名称</view>
				<view style="color: #F29100;">{{details.arrival_name}}</view>
			</view>
			<view class="flexJusp" v-if="isInternalEmployee" style="align-items: center;height:104rpx;border-bottom:2rpx solid #F7F7F7;">
				<view>到货物流地址</view>
				<view style="color: #F29100;">{{details.arrival_address}}</view>
			</view>
			<view class="flexJusp" v-if="isInternalEmployee && details.logistics_id" style="align-items: center;height:104rpx;border-bottom:2rpx solid #F7F7F7;">
				<view>星级</view>
				<view class="flex" style="align-items: center;">
					<view style="color: #F29100;font-size: 30rpx;">{{ getLevelStars(details.level) }}</view>
				</view>
			</view>
		</view>
		<!-- 备用专线：仅内部员工可见，列表默认收起 -->
		<view
			v-if="isInternalEmployee && alternativeLogisticsList.length"
			style="background: #FFFFFF;border-radius: 24rpx;padding:0 24rpx;margin:20rpx;overflow:hidden;"
		>
			<view
				class="flexJusp alt-logistics-header"
				style="align-items: center;min-height:104rpx;"
				@click="toggleAltListExpanded"
			>
				<view style="font-weight: bold;font-size: 30rpx;">备用专线</view>
				<view class="flex" style="align-items: center;">
					<view style="font-size: 26rpx;color: #888888;margin-right:12rpx;">共{{ alternativeLogisticsList.length }}条</view>
					<image
						class="alt-arrow"
						:class="{ 'alt-arrow--open': altListExpanded }"
						src="/static/images/icon_2jt.png"
					></image>
				</view>
			</view>
			<view v-if="altListExpanded" style="padding-bottom:24rpx;">
			<view
				v-for="(item, altIdx) in alternativeLogisticsList"
				:key="(item && item.logistics_id) || ('alt-' + altIdx)"
				style="background: #F7F7F7;border-radius: 20rpx;margin-bottom:20rpx;overflow:hidden;"
				:style="altIdx === alternativeLogisticsList.length - 1 ? 'margin-bottom:0' : ''"
			>
				<view
					class="flexJusp alt-logistics-header"
					style="align-items: center;min-height:104rpx;padding:0 24rpx;"
					@click="toggleAlternativeLogistics(altIdx)"
				>
					<view style="font-weight: bold;font-size: 28rpx;color: #F29100;flex:1;margin-right:20rpx;">
						{{ item.shipping_name || '未命名物流' }}
					</view>
					<view style="font-size: 26rpx;color: #F29100;margin-right:16rpx;flex-shrink:0;">{{ getLevelStars(item.level) }}</view>
					<image
						class="alt-arrow"
						:class="{ 'alt-arrow--open': isAlternativeExpanded(altIdx) }"
						src="/static/images/icon_2jt.png"
					></image>
				</view>
				<view v-if="isAlternativeExpanded(altIdx)" style="padding:0 24rpx 8rpx;">
					<view
						v-for="(field, fieldIdx) in getVisibleAlternativeDetailFields(item)"
						:key="field.key"
						class="flexJusp"
						:class="{ 'alt-field-row--phone': field.key === 'shipping_mobile' }"
						style="align-items: center;min-height:104rpx;padding:20rpx 0;"
						:style="fieldIdx < getVisibleAlternativeDetailFields(item).length - 1 ? 'border-top:2rpx solid #EEEEEE' : ''"
					>
						<view style="flex-shrink:0;margin-right:20rpx;">{{ field.label }}</view>
						<view v-if="field.key === 'shipping_mobile'" class="alt-phone-list">
							<view
								v-for="(phone, phoneIdx) in parsePhoneList(item.shipping_mobile)"
								:key="phoneIdx"
								class="alt-phone-item"
								@click.stop="callLogisticsPhone(phone)"
							>{{ phone }}</view>
						</view>
						<view
							v-else
							:style="field.highlight ? 'color:#F29100;text-align:right;flex:1;' : 'text-align:right;flex:1;'"
						>{{ item[field.key] }}</view>
					</view>
					<view v-if="canChangeLogistics" class="alt-logistics-action">
						<view v-if="isCurrentLogistics(item)" class="alt-logistics-current">当前使用</view>
						<view
							v-else
							class="alt-logistics-use-btn"
							@click.stop="changeLogistics(altIdx)"
						>使用此专线</view>
					</view>
				</view>
			</view>
			</view>
		</view>
		<view style="margin:20rpx;padding:24rpx 20rpx;background: #FFFFFF;border-radius: 24rpx;">
			<view class="flex address-row">
				<view class="address-icon address-icon--load">装</view>
				<view class="address-info" v-if="details.loading_address">
					<view class="address-text">
						{{details.loading_address.address}}{{details.loading_address.detailed_address}}
					</view>
					<view class="address-contact">
						{{details.loading_address.user_name}} {{details.loading_address.mobile}}
					</view>
				</view>
			</view>
			<view class="flex address-row address-row--unload">
				<view class="address-icon address-icon--unload">卸</view>
				<view class="address-info" v-if="details.unload_address">
					<view class="address-text">
						{{details.unload_address.address}}{{details.unload_address.detailed_address}}
					</view>
					<view class="address-contact">
						{{details.unload_address.user_name}} {{details.unload_address.mobile}}
					</view>
				</view>
			</view>
		</view>
		<view style="background: #FFFFFF;border-radius: 24rpx;padding:0 24rpx;margin:20rpx;">
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
				<view>车型类型</view>
				<view>{{details.car_type_id}}</view>
			</view>
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
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;" v-if="details.delivery_type_id">
					<view>送货方式</view>
					<view>{{details.delivery_type_id}}</view>
				</view>
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;" v-if="details.receipt_type_id">
					<view>回单</view>
					<view>{{details.receipt_type_id}}</view>
				</view>
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;" v-if="details.service">
					<view>服务方式</view>
					<view>{{details.service}}</view>
				</view>
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;" v-if="details.unpack_id">
					<view>拆包服务</view>
					<view>{{details.unpack_id}}</view>
				</view>
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;" v-if="details.control">
					<view>控货要求</view>
					<view>{{details.control}}</view>
				</view>
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;" v-if="details.other_id">
					<view>其他费用</view>
					<view>{{details.other_id}}</view>
				</view>
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;" v-if="details.text_message">
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
		<view class="buttons" @click="tijiao">立即提交</view>

		<view style="height:180rpx;"></view>
		</block>
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
				tijiaos:true,
				userType: 1,
				editPrice: '',
				originalPrice: '',
				commissionAmount: '0.00',
				commissionCost: '0.00',
				commissionRate: 0,
				isPartTimeEmployee: false,
				expandedAltIdx: -1,
				altListExpanded: false,
				changingLogistics: false,
				pageLoading: true,
				fromSubmit: false,
				information_image: ''
			}
		},
		computed: {
			// 税点列表：专票=6%/9%，普票=1%/2%
			array() {
				return this.$utils.taxArray(this.details && this.details.type)
			},
			// 判断是否为内部员工（membertype==2 或 membertype==3）
			isInternalEmployee() {
				const userType = Number(this.userType)
				const membertype = Number(this.details && this.details.membertype)
				return userType === 2 || userType === 3 || membertype === 2 || membertype === 3
			},
			isPartTimeEmployee() {
				const userType = Number(this.userType)
				const membertype = Number(this.details && this.details.membertype)
				return userType === 2 || membertype === 2
			},
			// 备用专线列表，兼容任意条数
			alternativeLogisticsList() {
				const list = this.details && this.details.alternative_logistics_list
				return Array.isArray(list) ? list : []
			},
			// 备用专线展示字段配置
			alternativeLogisticsFields() {
				return [
					{ label: '物流名称', key: 'shipping_name', highlight: true, always: true },
					{ label: '发货物流地址', key: 'shipping_address', highlight: true },
					{ label: '发货物流电话', key: 'shipping_mobile', highlight: true },
					{ label: '到货物流地址', key: 'arrival_address', highlight: true }
				]
			},
			// 配车订单且状态允许时，可更换物流专线
			canChangeLogistics() {
				const details = this.details || {}
				if (String(details.find_car_type || '').trim() !== '配车') return false
				const payStatus = Number(details.pay_status)
				return [1, 5, 6, 7, 8].includes(payStatus)
			}
		},
		onLoad(option) {
			uni.setStorageSync('zaiOrder','')
			this.id = option.id
			this.fromSubmit = option.fromSubmit === '1'
			this.getSystemInfo()
			this.getUserInfo()
			this.loads(this.fromSubmit)
		},
		onPageScroll(event) {
			const scrollTop = event.scrollTop // 获取当前页面滚动高度
			this.scrollTop = event.scrollTop
		},
		methods: {
			isAlternativeExpanded(altIdx) {
				return this.expandedAltIdx === Number(altIdx)
			},
			toggleAltListExpanded() {
				this.altListExpanded = !this.altListExpanded
				if (!this.altListExpanded) {
					this.expandedAltIdx = -1
				}
			},
			toggleAlternativeLogistics(altIdx) {
				const idx = Number(altIdx)
				this.expandedAltIdx = this.expandedAltIdx === idx ? -1 : idx
			},
			parsePhoneList(mobile) {
				if (!mobile) return []
				return String(mobile).split(/[,，、]/).map(s => s.trim()).filter(Boolean)
			},
			callLogisticsPhone(phone) {
				const phoneNumber = String(phone || '').replace(/\s/g, '')
				if (!phoneNumber) return
				uni.makePhoneCall({
					phoneNumber
				})
			},
			isCurrentLogistics(item) {
				if (!item || item.logistics_id == null) return false
				return Number(item.logistics_id) === Number(this.details.logistics_id)
			},
			// 星级：依据 logistics.level 渲染星级，空/0 显示未评级
			getLevelStars(level) {
				if (level === undefined || level === null || level === '') return '未评级'
				const num = Number(level)
				if (isNaN(num) || num <= 0) return '未评级'
				const filled = Math.min(5, Math.round(num))
				const empty = 5 - filled
				return '★'.repeat(filled) + '☆'.repeat(empty)
			},
			refreshCommission() {
				if (!this.isPartTimeEmployee) {
					this.commissionAmount = '0.00'
					this.commissionCost = String(Number(this.details.cost_cont || 0).toFixed(2))
					return
				}
				const price = Number(this.editPrice || this.details.pay_price || 0)
				if (isNaN(price)) {
					this.commissionAmount = '0.00'
					this.commissionCost = String(Number(this.details.cost_cont || 0).toFixed(2))
					return
				}
				const rate = Number(this.commissionRate || 0)
				this.commissionAmount = (price * rate / 100).toFixed(2)
				const cost = Number(this.details.cost_cont || 0)
				this.commissionCost = (cost + price * rate / 100).toFixed(2)
			},
			changeLogistics(altIdx) {
				if (this.changingLogistics || !this.canChangeLogistics) return
				const item = this.alternativeLogisticsList[Number(altIdx)]
				if (!item || !item.logistics_id || this.isCurrentLogistics(item)) return

				const name = item.shipping_name || '该专线'
				uni.showModal({
					title: '更换物流专线',
					content: '确定将订单专线更换为「' + name + '」吗？',
					success: (res) => {
						if (res.confirm) {
							this.doChangeLogistics(item.logistics_id)
						}
					}
				})
			},
			doChangeLogistics(logisticsId) {
				if (this.changingLogistics) return
				this.changingLogistics = true
				uni.showLoading({ title: '更换中...', mask: true })
				this.$httpapi('Placeorder/change_logistics', 'POST', {
					order_id: this.id,
					logistics_id: logisticsId
				}).then(res => {
					uni.hideLoading()
					this.changingLogistics = false
					if (res && res.code == 1) {
						uni.showToast({
							title: res.msg || '更换成功',
							duration: 2000,
							icon: 'success'
						})
						this.loads(true)
					} else {
						uni.showToast({
							title: (res && res.msg) ? res.msg : '更换失败',
							duration: 2000,
							icon: 'none'
						})
					}
				}).catch(() => {
					uni.hideLoading()
					this.changingLogistics = false
					uni.showToast({
						title: '网络异常，请重试',
						duration: 2000,
						icon: 'none'
					})
				})
			},
			// 过滤出有值的备用专线字段
			getVisibleAlternativeFields(item) {
				if (!item) return []
				return this.alternativeLogisticsFields.filter(field => {
					if (field.always) return true
					const val = item[field.key]
					return val !== undefined && val !== null && val !== ''
				})
			},
			// 展开时展示的字段（物流名称在标题行）
			getVisibleAlternativeDetailFields(item) {
				return this.getVisibleAlternativeFields(item).filter(field => field.key !== 'shipping_name')
			},
			yulan() {
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
			tijiao() {
				var that = this
				if (!this.tijiaos) return
				this.tijiaos = false
				const checkAndSubmit = () => {
					that.$httpapi('Index/bankcardAuthInfo', 'POST', {}).then(res => {
						const data = (res && res.data) || {}
						if (res && res.code == 1 && Number(data.is_auth) === 1) {
							that.doSubmitOrder()
						} else {
							that.tijiaos = true
							uni.showToast({
								title: '请先完成实名认证',
								duration: 1500,
								icon: 'none'
							})
							setTimeout(() => {
								uni.navigateTo({
									url: '/pages/user/shiming/shiming'
								})
							}, 500)
						}
					}).catch(() => {
						that.tijiaos = true
						uni.showToast({
							title: '实名状态查询失败，请重试',
							duration: 2000,
							icon: 'none'
						})
					})
				}
				if (!this.userType && !(this.details && this.details.membertype)) {
					this.$httpapi('Opinion/getuserinfo', 'GET', {}).then(res => {
						if (res.data && res.data.membertype !== undefined) {
							that.userType = res.data.membertype
						} else if (res.data && res.data.type !== undefined) {
							that.userType = res.data.type
						}
						checkAndSubmit()
					}).catch(() => {
						that.tijiaos = true
						uni.showToast({
							title: '获取用户信息失败，请重试',
							duration: 2000,
							icon: 'none'
						})
					})
					return
				}
				checkAndSubmit()
			},
			doSubmitOrder() {
				var that = this
				if (this.isPartTimeEmployee && !this.information_image) {
					this.tijiaos = true
					uni.showToast({
						title: '请在首页上传订单截图',
						duration: 1500,
						icon: 'none'
					})
					return
				}
				uni.setStorageSync('tijiao', 1)
				// 仅「现付」调起支付；月结、到付等只走确认订单，不调支付
				var payType = String(this.details.pay_type || '').trim()
				if (payType === '现付') {
					this.zhifu()
				} else {
					this.$httpapi('Placeorder/confirm_order', 'POST', {
						order_id: this.id
					}).then(res => {
						// 只有 code 为 1 才视为成功并跳转，其余全部弹错误（专车/小票快运/配车一致）
						
						if (res && res.code == 1) {
							uni.setStorageSync('zaiOrder', '')
							uni.removeStorageSync('index_other_requirements_backup')
							setTimeout(() => {
								that.tijiaos = true
								uni.switchTab({
									url: '/pages/order/order'
								})
							}, 1500)
						} else { 
							that.tijiaos = true
							uni.setStorageSync('zaiOrder', that.details)
							uni.showToast({
								title: (res && res.msg) ? res.msg : '提交失败，请重试',
								duration: 2000,
								icon: 'none'
							})
						}
					}).catch((err) => {
						that.tijiaos = true
						uni.setStorageSync('zaiOrder', that.details)
						uni.showToast({
							title: (err && err.msg) ? err.msg : '网络异常，请重试',
							duration: 2000,
							icon: 'none'
						})
					})
				}
			},
			chonglai() {
				uni.setStorageSync('zaiOrder', this.details)
				uni.switchTab({
					url: '/pages/index/index'
				})
			},
			zhifu() {
				var that = this
				this.$httpapi('Wechatpay/pay', 'POST', {
					order_id: this.id
				}).then(res => {
					// 只有 code 为 1 才调起支付，其余全部弹错误（专车/小票快运/配车一致）
					if (res && res.code == 1) {
						// 成功时调用支付
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
								// 二次提交成功后清空 index 页面数据，避免返回首页仍显示上一单
								uni.setStorageSync('zaiOrder', '')
								uni.removeStorageSync('index_other_requirements_backup')
								setTimeout(() => {
									that.tijiaos = true
									that.loads(true)
								}, 1500)
							},
							fail: function(err) {
								that.tijiaos = true
								// 用户取消支付时也保存订单数据
								uni.setStorageSync('zaiOrder', that.details)
							}
						});
					} else {
						that.tijiaos = true
						uni.setStorageSync('zaiOrder', that.details)
						uni.showToast({
							title: (res && res.msg) ? res.msg : '支付失败，请重试',
							duration: 2000,
							icon: 'none'
						})
					}
				}).catch((err) => {
					that.tijiaos = true
					uni.setStorageSync('zaiOrder', that.details)
					uni.showToast({
						title: (err && err.msg) ? err.msg : '网络异常，请重试',
						duration: 2000,
						icon: 'none'
					})
				})
			},
			quxiao() {
				this.$httpapi('Placeorder/CancelOrder', 'POST', {
					order_id: this.id
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
				// 返回时保存订单数据，以便在首页保留
				uni.setStorageSync('zaiOrder', this.details)
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
			getUserInfo() {
				// 获取用户信息，判断用户身份
				this.$httpapi('Opinion/getuserinfo', 'GET', {}).then(res => {
					if (res.data && res.data.type !== undefined) {
						this.userType = res.data.type
					} else if (res.data && res.data.membertype !== undefined) {
						this.userType = res.data.membertype
					}
				}).catch(err => {
					console.log('获取用户信息失败', err)
					console.log(this.details.pay_type)
				})
			},
			updatePrice() {
				var that = this
				if (!this.editPrice || this.editPrice === this.originalPrice) {
					this.refreshCommission()
					return
				}
				if (isNaN(this.editPrice) || parseFloat(this.editPrice) < 0) {
					uni.showToast({
						title: '请输入有效的总运费',
						duration: 2000,
						icon: 'none'
					})
					this.editPrice = this.originalPrice
					this.refreshCommission()
					return
				}
				this.refreshCommission()
				this.details.pay_price = this.editPrice
				this.refreshCommission()
				this.$httpapi('Employee/Updateorder', 'POST', {
					orderid: this.id,
					old_price: this.originalPrice,
					new_price: this.editPrice
				}).then(res => {
					if (res.code === 0) {
						uni.showToast({
							title: res.msg || '修改失败，请重试',
							duration: 2000,
							icon: 'none'
						})
						this.editPrice = this.originalPrice
						this.refreshCommission()
					} else {
						uni.showToast({
							title: res.msg || '修改成功',
							duration: 2000,
							icon: 'success'
						})
						this.originalPrice = this.editPrice
						this.details.pay_price = this.editPrice
						this.refreshCommission()
						setTimeout(() => {
							that.loads(true)
						}, 500)
					}
				}).catch(err => {
					console.log('修改总运费失败', err)
					uni.showToast({
						title: '修改失败，请重试',
						duration: 2000,
						icon: 'none'
					})
					this.editPrice = this.originalPrice
					this.refreshCommission()
				})
			},
			loads(silent) {
				if (!silent) {
					this.pageLoading = true
					this.altListExpanded = false
					this.expandedAltIdx = -1
				}
				this.$httpapi('Placeorder/orderdetaile', 'POST', {
					order_id: this.id
				}).then(rex => {
					if (!rex || !rex.data) {
						uni.showToast({
							title: '加载失败，请重试',
							duration: 2000,
							icon: 'none'
						})
						return
					}
					this.details = rex.data
					this.editPrice = rex.data.pay_price || ''
					this.originalPrice = rex.data.pay_price || ''
					this.information_image = rex.data.information_image || ''
					this.isPartTimeEmployee = Number(rex.data.membertype) === 2
					const commissionRate = Number(rex.data.platform_commission_rate || rex.data.platform_commission || 0)
					this.commissionRate = commissionRate > 0 ? commissionRate : 0
					this.refreshCommission()
					
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
				}).catch(() => {
					uni.showToast({
						title: '加载失败，请重试',
						duration: 2000,
						icon: 'none'
					})
				}).finally(() => {
					this.pageLoading = false
					if (this.fromSubmit) {
						this.fromSubmit = false
						this.$nextTick(() => {
							uni.hideLoading()
						})
					}
				})
			}
		}
	}
</script>

<style lang="scss">
	.page-loading {
		display: flex;
		align-items: center;
		justify-content: center;
		min-height: 60vh;
	}

	.page-loading-text {
		font-size: 28rpx;
		color: #888888;
	}

	.post {
		position: fixed;
		bottom: 0;
		left: 0;
		width: 750rpx;
		background-color: #FFF;
	}

	.buttons {
		margin: 24rpx auto 0;
		width: 690rpx;
		height: 88rpx;
		background: linear-gradient(180deg, #4897FF 0%, #4878FF 100%);
		border-radius: 46rpx;
		font-weight: bold;
		font-size: 30rpx;
		color: #FFFFFF;
		line-height: 88rpx;
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

	.alt-logistics-header {
		cursor: pointer;
	}

	.alt-arrow {
		width: 20rpx;
		height: 20rpx;
		flex-shrink: 0;
		transition: transform 0.2s ease;
	}

	.alt-arrow--open {
		transform: rotate(90deg);
	}

	.alt-field-row--phone {
		align-items: flex-start !important;
	}

	.alt-phone-list {
		flex: 1;
		display: flex;
		flex-direction: column;
		align-items: flex-end;
	}

	.alt-phone-item {
		font-size: 28rpx;
		color: #4878FF;
		line-height: 1.6;
		padding: 8rpx 0;
		text-align: right;
		word-break: break-all;
	}

	.alt-phone-item:first-child {
		padding-top: 0;
	}

	.alt-phone-item:last-child {
		padding-bottom: 0;
	}

	.alt-logistics-action {
		padding: 8rpx 0 24rpx;
		border-top: 2rpx solid #EEEEEE;
	}

	.alt-logistics-current {
		margin-top: 20rpx;
		text-align: center;
		font-size: 26rpx;
		color: #888888;
	}

	.alt-logistics-use-btn {
		margin-top: 20rpx;
		height: 72rpx;
		background: linear-gradient(180deg, #4897FF 0%, #4878FF 100%);
		border-radius: 36rpx;
		font-size: 28rpx;
		font-weight: bold;
		color: #FFFFFF;
		line-height: 72rpx;
		text-align: center;
	}

	.address-row {
		align-items: flex-start;
		min-height: 136rpx;
		padding: 24rpx 20rpx;
		background: #F7F7F7;
		border-radius: 20rpx;
	}

	.address-row--unload {
		margin-top: 20rpx;
	}

	.address-icon {
		flex-shrink: 0;
		width: 48rpx;
		height: 48rpx;
		border-radius: 12rpx;
		font-weight: bold;
		color: #FFFFFF;
		line-height: 48rpx;
		text-align: center;
	}

	.address-icon--load {
		background: #F29100;
	}

	.address-icon--unload {
		background: linear-gradient(180deg, #4897FF 0%, #4878FF 100%);
	}

	.address-info {
		flex: 1;
		min-width: 0;
		margin-left: 24rpx;
		overflow: hidden;
	}

	.address-text {
		font-weight: bold;
		font-size: 30rpx;
		line-height: 1.5;
		word-break: break-all;
	}

	.address-contact {
		margin-top: 8rpx;
		font-size: 26rpx;
		color: #888888;
		line-height: 1.4;
		word-break: break-all;
	}
</style>

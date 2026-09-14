<template>
	<view class="content">
		<!-- 自定义导航：返回 + 标题「修改订单」 -->
		<view class="nav-bar" :style="'padding-top:'+statusBarHeight+'px'">
			<view class="nav-bar-inner" :style="'height:'+navigationBarHeight+'px'">
				<view class="nav-back" @click="goBack">
					<image style="width:20rpx;height:34rpx;" src="/static/images/mv_tuichu.png"></image>
				</view>
				<view class="nav-title">修改订单</view>
				<view class="nav-placeholder"></view>
			</view>
		</view>
		<view :style="'height:'+navHeight+'px'"></view>
		<scroll-view scroll-y class="form-scroll">
		<view v-if="isRejectOnlyEdit" class="reject-tip">
			<text class="reject-tip-title">订单已驳回，请仅修改以下项后重新提交</text>
			<text v-if="rejectReason" class="reject-tip-reason">驳回原因：{{rejectReason}}</text>
		</view>
		<view style="margin:20rpx;padding:24rpx 20rpx;background: #FFFFFF;border-radius: 24rpx;">
			<view class="flex" style="height: 136rpx;background: #F7F7F7;border-radius: 20rpx;align-items: center;padding:0 20rpx;"
				:class="{ 'form-section-disabled': isRejectOnlyEdit && !isFieldEditable('loading_address','loading') }">
				<view style="width: 48rpx;height: 48rpx;background: #F29100;border-radius: 12rpx;font-weight: bold;color: #FFFFFF;line-height: 48rpx;text-align: center;">装</view>
				<view class="address-main" @click="zhuangAddressClick">
					<view v-if="zhuangAddress.address" style="padding-right:32rpx;">
						<view class="yihang" style="font-weight: bold;font-size: 30rpx;">{{zhuangAddress.address||''}}</view>
						<view style="font-size: 26rpx;color: #888888;margin-top:8rpx;">{{zhuangAddress.user_name||''}} {{zhuangAddress.mobile||''}}</view>
						<view style="position: absolute;top:0;right:0;width:32rpx;height:32rpx;display: flex;align-items: center;justify-content: center;z-index: 10;" @click.stop="clearZhuangAddress">
							<image style="width:24rpx;height:24rpx;" src="/static/images/icon_3qx.png"></image>
						</view>
					</view>
					<view v-else><view>点击选择地址</view></view>
				</view>
				<view class="address-actions">
					<view class="address-divider"></view>
					<view class="address-action address-action--stack" @click.stop="openQuickInput(1)"><view>地 址</view><view>粘 贴</view><view>识 别</view></view>
					<view class="address-action" @click.stop="zhuangxuan">地址簿</view>
				</view>
			</view>
			<view class="flex" style="justify-content: center;margin: 16rpx 0;" :class="{ 'form-section-disabled': isRejectOnlyEdit && !isFieldEditable('loading_address','loading','unload_address','unload') }">
				<view @click="swapAddress" style="width: 64rpx;height: 64rpx;background: linear-gradient(180deg, #4897FF 0%, #4878FF 100%);border-radius: 50%;display: flex;align-items: center;justify-content: center;box-shadow: 0 4rpx 12rpx rgba(72, 120, 255, 0.3);">
					<text style="color: #FFFFFF;font-size: 32rpx;font-weight: bold;">⇅</text>
				</view>
			</view>
			<view class="flex" style="height: 136rpx;background: #F7F7F7;border-radius: 20rpx;align-items: center;padding:0 20rpx;margin-top:20rpx;"
				:class="{ 'form-section-disabled': isRejectOnlyEdit && !isFieldEditable('unload_address','unload') }">
				<view style="width: 48rpx;height: 48rpx;background: linear-gradient( 180deg, #4897FF 0%, #4878FF 100%);border-radius: 12rpx;font-weight: bold;color: #FFFFFF;line-height: 48rpx;text-align: center;">卸</view>
				<view class="address-main" @click="xieAddressClick">
					<view v-if="xieAddress.address" style="padding-right:32rpx;">
						<view class="yihang" style="font-weight: bold;font-size: 30rpx;">{{xieAddress.address||''}}</view>
						<view style="font-size: 26rpx;color: #888888;margin-top:8rpx;">{{xieAddress.user_name||''}} {{xieAddress.mobile||''}}</view>
						<view style="position: absolute;top:0;right:0;width:32rpx;height:32rpx;display: flex;align-items: center;justify-content: center;z-index: 10;" @click.stop="clearXieAddress">
							<image style="width:24rpx;height:24rpx;" src="/static/images/icon_3qx.png"></image>
						</view>
					</view>
					<view v-else><view>点击选择地址</view></view>
				</view>
				<view class="address-actions">
					<view class="address-divider"></view>
					<view class="address-action address-action--stack" @click.stop="openQuickInput(2)"><view>地 址</view><view>粘 贴</view><view>识 别</view></view>
					<view class="address-action" @click.stop="xiexuan">地址簿</view>
				</view>
			</view>
		</view>
		<view style="background: #FFFFFF;border-radius: 24rpx;padding:0 24rpx;margin:20rpx;">
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;"
				:class="{ 'form-section-disabled': isRejectOnlyEdit && !isFieldEditable('find_car_type') }">
				<view>找车类型</view>
				<view class="flex" style="align-items: center;">
					<image v-if="carType==0" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan.png"></image>
					<image v-else @click="carType=0" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan1.png"></image>
					<view @click="carType=0" style="margin:0 32rpx 0 10rpx;">专车</view>
					<image v-if="carType==1" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan.png"></image>
					<image v-else @click="carType=1" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan1.png"></image>
					<view @click="carType=1" style="margin:0 32rpx 0 10rpx;">配车</view>
					<image v-if="carType==2" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan.png"></image>
					<image v-else @click="carType=2" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan1.png"></image>
					<view @click="carType=2" style="margin:0 0 0 10rpx;">小票快运</view>
				</view>
			</view>
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;"
				:class="{ 'form-section-disabled': isRejectOnlyEdit && !isFieldEditable('isinvoice','invoice') }">
				<view>发票</view>
				<view class="flex" style="align-items: center;">
					<image v-if="isinvoice==0" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan.png"></image>
					<image v-else @click="isinvoice=0" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan1.png"></image>
					<view @click="isinvoice=0" style="margin:0 32rpx 0 10rpx;">不开票</view>
					<image v-if="isinvoice==1" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan.png"></image>
					<image v-else @click="isinvoice=1" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan1.png"></image>
					<view @click="isinvoice=1" style="margin:0 0 0 10rpx;">开票</view>
				</view>
			</view>
			<view style="background: #F7F7F7;border-radius: 20rpx;padding:0 24rpx;" v-if="isinvoice==1"
				:class="{ 'form-section-disabled': isRejectOnlyEdit && !isFieldEditable('isinvoice','invoice','type','tax_point','company_letterhead','company_tax_id','company_email','company_mobile','bank_deposits') }">
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>发票类型</view>
					<view class="flex" style="align-items: center;">
						<image v-if="type==1" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan.png"></image>
						<image v-else @click="selectTaxType(1)" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan1.png"></image>
						<view @click="selectTaxType(1)" style="margin:0 32rpx 0 10rpx;">专票</view>
						<image v-if="type==2" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan.png"></image>
						<image v-else @click="selectTaxType(2)" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan1.png"></image>
						<view @click="selectTaxType(2)" style="margin:0 0 0 10rpx;">普票</view>
					</view>
				</view>
				<picker @change="bindPickerChange" :value="indexs" :range="array">
					<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
						<view>选择税点</view>
						<view class="flex" style="align-items: center;">
							<view v-if="array[indexs]">{{array[indexs]}}</view>
							<view v-else>请选择</view>
							<image style="width:20rpx;height:20rpx;margin-left:12rpx;" src="/static/images/icon_2jt.png"></image>
						</view>
					</view>
				</picker>
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>公司抬头<text style="color: #E50014;">*</text></view>
					<input v-model="company_letterhead" placeholder="请输入" style="text-align: right;" />
				</view>
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>公司税号<text style="color: #E50014;">*</text></view>
					<input v-model="company_tax_id" placeholder="请输入" style="text-align: right;" />
				</view>
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>接收邮箱</view>
					<input v-model="company_email" placeholder="请输入" style="text-align: right;" />
				</view>
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>公司电话</view>
					<input v-model="company_mobile" placeholder="请输入" style="text-align: right;" />
				</view>
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>开户银行</view>
					<input v-model="bank_deposits" placeholder="请输入" style="text-align: right;" />
				</view>
			</view>
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;"
				:class="{ 'form-section-disabled': isRejectOnlyEdit && !isFieldEditable('goods_name') }">
				<view>货物名称</view>
				<input v-model="goods_name" :disabled="isRejectOnlyEdit && !isFieldEditable('goods_name')" placeholder="请输入" style="text-align: right;" />
			</view>
			<picker @change="bindPickerChangeType" :value="typeIndex" :range="typeList" range-key="name" :disabled="isRejectOnlyEdit && !isFieldEditable('goods_type_id')">
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;"
					:class="{ 'form-section-disabled': isRejectOnlyEdit && !isFieldEditable('goods_type_id') }">
					<view>货物类型</view>
					<view class="flex" style="align-items: center;">
						<view v-if="typeList[typeIndex]">{{typeList[typeIndex].name}}</view>
						<view v-else>请选择</view>
						<image style="width:20rpx;height:20rpx;margin-left:12rpx;" src="/static/images/icon_2jt.png"></image>
					</view>
				</view>
			</picker>
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:156rpx;"
				:class="{ 'form-section-disabled': isRejectOnlyEdit && !isFieldEditable('goods_image') }">
				<view>货物图片</view>
				<image style="width:120rpx;height:120rpx;" @click="upImage" :src="goods_image?imgUrl+goods_image:'/static/images/icon_35sc.png'"></image>
			</view>
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;"
				:class="{ 'form-section-disabled': isRejectOnlyEdit && !isFieldEditable('quantity') }">
				<view>数量</view>
				<view class="flex" style="align-items: center;height: 42rpx;border-radius: 12rpx;border: 1rpx solid #CACACA;text-align: center;">
					<view @click="jianjian" style="color: #CACACA;width:40rpx;">-</view>
					<input type="number" :value="quantity" @input="onQuantityInput" @blur="onQuantityBlur" style="width:60rpx;height:42rpx;font-size:24rpx;text-align:center;border-left:2rpx solid #CACACA;border-right:2rpx solid #CACACA;box-sizing:border-box;" />
					<view @click="quantity++" style="width:40rpx;">+</view>
				</view>
			</view>
			<view v-if="!canAddMultipleSizes" class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;"
				:class="{ 'form-section-disabled': isRejectOnlyEdit && !isFieldEditable('long','wide','hige','size','sizeList') }">
				<view>尺寸</view>
				<view class="flex" style="align-items: center;">
					<input placeholder="长" :value="long" @input="onNumberInput('long', $event.detail.value, 0)" @blur="chang" type="number" style="width: 120rpx;height: 60rpx;background: #F7F7F7;border-radius: 12rpx;text-align: center;" />
					<view style="margin:0 12rpx;">x</view>
					<input placeholder="宽" :value="wide" @input="onNumberInput('wide', $event.detail.value, 0)" @blur="chang" type="number" style="width: 120rpx;height: 60rpx;background: #F7F7F7;border-radius: 12rpx;text-align: center;" />
					<view style="margin:0 12rpx;">x</view>
					<input placeholder="高" :value="hige" @input="onNumberInput('hige', $event.detail.value, 0)" @blur="chang" type="number" style="width: 120rpx;height: 60rpx;background: #F7F7F7;border-radius: 12rpx;text-align: center;" />
					<view style="margin-left:12rpx;">厘米</view>
				</view>
			</view>
			<view v-if="canAddMultipleSizes" style="border-bottom:2rpx solid #F7F7F7;padding:24rpx 0;"
				:class="{ 'form-section-disabled': isRejectOnlyEdit && !isFieldEditable('long','wide','hige','size','sizeList') }">
				<view class="flexJusp" style="align-items: center;margin-bottom:20rpx;">
					<view>尺寸</view>
					<view class="flex" style="align-items: center;color: #4878FF;font-size: 28rpx;" @click="addSize"><view style="margin-right:8rpx;">+</view><view>添加尺寸</view></view>
				</view>
				<view v-for="(item, index) in sizeList" :key="index" class="flexJusp" style="align-items: center;margin-bottom:20rpx;padding:20rpx;background: #F7F7F7;border-radius: 12rpx;">
					<view class="flex" style="align-items: center;flex:1;">
						<input placeholder="长" :value="item.long" @input="onSizeInput(index, 'long', $event.detail.value)" type="digit" @blur="changMultiple(index)" style="width: 100rpx;height: 60rpx;background: #FFFFFF;border-radius: 12rpx;text-align: center;" />
						<view style="margin:0 12rpx;">x</view>
						<input placeholder="宽" :value="item.wide" @input="onSizeInput(index, 'wide', $event.detail.value)" type="digit" @blur="changMultiple(index)" style="width: 100rpx;height: 60rpx;background: #FFFFFF;border-radius: 12rpx;text-align: center;" />
						<view style="margin:0 12rpx;">x</view>
						<input placeholder="高" :value="item.hige" @input="onSizeInput(index, 'hige', $event.detail.value)" type="digit" @blur="changMultiple(index)" style="width: 100rpx;height: 60rpx;background: #FFFFFF;border-radius: 12rpx;text-align: center;" />
						<view style="margin-left:12rpx;font-size: 24rpx;">厘米</view>
					</view>
					<view v-if="sizeList.length > 1" @click="removeSize(index)" style="margin-left:20rpx;color: #E50014;font-size: 28rpx;">删除</view>
				</view>
			</view>
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;"
				:class="{ 'form-section-disabled': isRejectOnlyEdit && !isFieldEditable('weight') }">
				<view>总重量</view>
				<view class="flex" style="align-items: center;">
					<input placeholder="0" :value="weight" @input="onNumberInput('weight', $event.detail.value, 2)" @blur="zhongliang" type="digit" style="width: 120rpx;height: 60rpx;background: #F7F7F7;border-radius: 12rpx;text-align: center;" />
					<view style="margin-left:12rpx;">吨</view>
				</view>
			</view>
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;"
				:class="{ 'form-section-disabled': isRejectOnlyEdit && !isFieldEditable('direction') }">
				<view>总方位</view>
				<view class="flex" style="align-items: center;">
					<input placeholder="0" :value="direction" @input="onNumberInput('direction', $event.detail.value, 2)" type="digit" style="width: 120rpx;height: 60rpx;background: #F7F7F7;border-radius: 12rpx;text-align: center;" />
					<view style="margin-left:12rpx;">立方米</view>
				</view>
			</view>
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;"
				:class="{ 'form-section-disabled': isRejectOnlyEdit && !isFieldEditable('pay_price') }">
				<view>总运费</view>
				<view class="flex" style="align-items: center;">
					<input placeholder="0" :value="pay_price" @input="onNumberInput('pay_price', $event.detail.value, 2)" type="digit" style="width: 160rpx;height: 60rpx;background: #F7F7F7;border-radius: 12rpx;text-align: center;" />
					<view style="margin-left:12rpx;">元</view>
				</view>
			</view>
			<view @click="togglePackagePanel" class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;"
				:class="{ 'form-section-disabled': isRejectOnlyEdit && !isFieldEditable('packaging_id','packaging') }">
				<view>包装方式</view>
				<view class="flex" style="align-items: center;">
					<view v-if="packageSummary">{{packageSummary}}</view>
					<view v-else>请选择</view>
					<image style="width:20rpx;height:20rpx;margin-left:12rpx;" src="/static/images/icon_2jt.png"></image>
				</view>
			</view>
			<view v-if="showPackagePanel" style="background:#F7F7F7;border-radius: 0 0 20rpx 20rpx;padding:20rpx 24rpx 12rpx 24rpx;border-bottom:2rpx solid #F7F7F7;">
				<view v-if="baoList && baoList.length">
					<view v-for="item in baoList" :key="item.id" class="flexJusp" style="align-items:center;margin-bottom:16rpx;">
						<view class="flex" style="align-items:center;">
							<image v-if="isPackageSelected(item.id)" @click.stop="togglePackage(item)" style="width:32rpx;height:32rpx;margin-right:12rpx;" src="/static/images/icon_2xuan.png"></image>
							<image v-else @click.stop="togglePackage(item)" style="width:32rpx;height:32rpx;margin-right:12rpx;" src="/static/images/icon_2xuan1.png"></image>
							<view @click.stop="togglePackage(item)">{{item.name}}</view>
						</view>
						<view v-if="isPackageSelected(item.id)" class="flex" style="align-items: center;height: 42rpx;border-radius: 12rpx;border: 1rpx solid #CACACA;text-align: center;">
							<view @click.stop="changePackageQuantity(item.id, -1)" style="color: #CACACA;width:40rpx;">-</view>
							<view style="width:60rpx;font-size:24rpx;border-left:2rpx solid #CACACA;border-right:2rpx solid #CACACA;">{{getPackageQuantity(item.id)}}</view>
							<view @click.stop="changePackageQuantity(item.id, 1)" style="width:40rpx;">+</view>
						</view>
					</view>
				</view>
				<view v-else style="font-size:26rpx;color:#999;">暂无可选包装方式</view>
			</view>
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;"
				:class="{ 'form-section-disabled': isRejectOnlyEdit && !isFieldEditable('pay_type','pay_party') }">
				<view>付款方式</view>
				<view class="flex" style="align-items: center;">
					<image v-if="pay_type==0" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan.png"></image>
					<image v-else @click="pay_type=0" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan1.png"></image>
					<view @click="pay_type=0" style="margin:0 32rpx 0 10rpx;">现付</view>
					<image v-if="pay_type==1" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan.png"></image>
					<image v-else @click="pay_type=1" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan1.png"></image>
					<view @click="pay_type=1" style="margin:0 32rpx 0 10rpx;">到付</view>
					<image v-if="pay_type==2" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan.png"></image>
					<image v-else @click="pay_type=2" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan1.png"></image>
					<view @click="pay_type=2" style="margin:0 0 0 10rpx;">月结</view>
				</view>
			</view>
			<view v-if="pay_type==1" class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;background:#F7F7F7;padding-left:24rpx;"
				:class="{ 'form-section-disabled': isRejectOnlyEdit && !isFieldEditable('pay_type','pay_party') }">
				<view>到付付款方</view>
				<view class="flex" style="align-items: center;margin-right:24rpx;">
					<image v-if="pay_party==0" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan.png"></image>
					<image v-else @click="pay_party=0" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan1.png"></image>
					<view @click="pay_party=0" style="margin:0 32rpx 0 10rpx;">寄货方付钱</view>
					<image v-if="pay_party==1" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan.png"></image>
					<image v-else @click="pay_party=1" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan1.png"></image>
					<view @click="pay_party=1" style="margin:0 0 0 10rpx;">收货方付钱</view>
				</view>
			</view>
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;"
				:class="{ 'form-section-disabled': isRejectOnlyEdit && !isFieldEditable('delivery','behalf_price') }">
				<view>代收货款</view>
				<view class="flex" style="align-items: center;">
					<image v-if="delivery==0" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan.png"></image>
					<image v-else @click="delivery=0" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan1.png"></image>
					<view @click="delivery=0" style="margin:0 32rpx 0 10rpx;">否</view>
					<image v-if="delivery==1" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan.png"></image>
					<image v-else @click="delivery=1" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan1.png"></image>
					<view @click="delivery=1" style="margin:0 0 0 10rpx;">是</view>
				</view>
			</view>
			<view style="background: #F7F7F7;border-radius: 20rpx;padding:0 24rpx;" v-if="delivery==1"
				:class="{ 'form-section-disabled': isRejectOnlyEdit && !isFieldEditable('delivery','behalf_price','bank_branch','open_number','bank_type','open_name','open_type') }">
				<view style="padding:32rpx 0;border-bottom:2rpx solid #F7F7F7;">
					<view class="flexJusp" style="align-items: center;"><view>代收金额</view><input v-model="behalf_price" placeholder="请输入" style="text-align: right;" /></view>
					<view style="font-size: 24rpx;color: #F29100;margin-top:8rpx;">按代收款总金额的0.3%,最低收费10元，最高收费100元</view>
				</view>
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;"><view>银行网点</view><input v-model="bank_branch" placeholder="请输入" style="text-align: right;" /></view>
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;"><view>开户账号</view><input v-model="open_number" placeholder="请输入" style="text-align: right;" /></view>
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;"><view>银行类型</view><input v-model="bank_type" placeholder="请输入" style="text-align: right;" /></view>
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;"><view>开户名</view><input v-model="open_name" placeholder="请输入" style="text-align: right;" /></view>
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
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;"><view>返款时效</view><view>(T+3)</view></view>
			</view>
			<view v-if="isInternalStaff" style="background: #F7F7F7;border-radius: 20rpx;padding:0 24rpx;"
				:class="{ 'form-section-disabled': isRejectOnlyEdit && !isFieldEditable('information','deposit','information_image') }">
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;"><view>信息费</view><input v-model="information" placeholder="请输入" style="text-align: right;" /></view>
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;"><view>不可退定金</view><input v-model="deposit" placeholder="请输入" style="text-align: right;" /></view>
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:156rpx;">
					<view>付款截图</view>
					<image style="width:120rpx;height:120rpx;" @click="upInformationImage" :src="information_image?imgUrl+information_image:'/static/images/icon_35sc.png'"></image>
				</view>
			</view>
			<picker @change="bindPickerChangeCar" :value="carIndex" :range="carList" range-key="name" :disabled="isRejectOnlyEdit && !isFieldEditable('car_type_id')">
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;"
					:class="{ 'form-section-disabled': isRejectOnlyEdit && !isFieldEditable('car_type_id') }">
					<view>选择车型</view>
					<view class="flex" style="align-items: center;">
						<view v-if="carList[carIndex]">{{carList[carIndex].name}}</view>
						<view v-else>请选择</view>
						<image style="width:20rpx;height:20rpx;margin-left:12rpx;" src="/static/images/icon_2jt.png"></image>
					</view>
				</view>
			</picker>
			<view v-if="carList[carIndex]">
				<image style="width: 662rpx;height: 300rpx;border-radius: 20rpx;" mode="aspectFit" :src="imgUrl+carList[carIndex].car_image" @click="previewCarImage"></image>
				<view class="flex" style="justify-content: center;margin-top:20rpx;" v-if="carList[carIndex]">厢长：{{carList[carIndex].car_long}}米 * 载重：{{carList[carIndex].car_wide}}吨 载方：{{carList[carIndex].car_high}}方</view>
			</view>
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;" @click="fahuo"
				:class="{ 'form-section-disabled': isRejectOnlyEdit && !isFieldEditable('earliest_time','latest_time') }">
				<view>发货时间</view>
				<view class="flex" style="align-items: center;">
					<view v-if="riqi">{{riqi||''}} {{zuizao||''}}-{{zuiwan||''}}</view>
					<view v-else>请选择</view>
					<image style="width:20rpx;height:20rpx;margin-left:12rpx;" src="/static/images/icon_2jt.png"></image>
				</view>
			</view>
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;"
				:class="{ 'form-section-disabled': isRejectOnlyEdit && !isFieldEditable('isrequirements') }">
				<view>其他运输要求</view>
				<view class="flex" style="align-items: center;">
					<image v-if="isrequirements==0" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan.png"></image>
					<image v-else @click="isrequirements=0" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan1.png"></image>
					<view @click="isrequirements=0" style="margin:0 32rpx 0 10rpx;">否</view>
					<image v-if="isrequirements==1" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan.png"></image>
					<image v-else @click="isrequirements=1" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan1.png"></image>
					<view @click="isrequirements=1" style="margin:0 0 0 10rpx;">是</view>
				</view>
			</view>
			<view style="background: #F7F7F7;border-radius: 20rpx;padding:0 24rpx;" v-if="isrequirements==1"
				:class="{ 'form-section-disabled': isRejectOnlyEdit && !isFieldEditable('deliveryrequirements','loadingrequirements','delivery_type_id','receipt_type_id','service','unpack_id','control','other_id','text_message') }">
				<view @click="toggleDeliveryPanel" class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>提货要求</view>
					<view class="flex" style="align-items: center;"><view v-if="deliverySummary">{{deliverySummary}}</view><view v-else>请选择</view><image style="width:20rpx;height:20rpx;margin-left:12rpx;" src="/static/images/icon_2jt.png"></image></view>
				</view>
				<view v-if="showDeliveryPanel" style="padding:20rpx 24rpx;">
					<view v-if="deliveryRequirementsList && deliveryRequirementsList.length">
						<view v-for="item in deliveryRequirementsList" :key="item.id" class="flexJusp" style="align-items:center;margin-bottom:16rpx;">
							<view class="flex" style="align-items:center;">
								<image v-if="selectedDeliveryRequirements.includes(item.id)" @click.stop="toggleDeliveryRequirement(item)" style="width:32rpx;height:32rpx;margin-right:12rpx;" src="/static/images/icon_2xuan.png"></image>
								<image v-else @click.stop="toggleDeliveryRequirement(item)" style="width:32rpx;height:32rpx;margin-right:12rpx;" src="/static/images/icon_2xuan1.png"></image>
								<view @click.stop="toggleDeliveryRequirement(item)">{{item.name}}</view>
							</view>
						</view>
					</view>
				</view>
				<view @click="toggleLoadingPanel" class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>专线装货要求</view>
					<view class="flex" style="align-items: center;"><view v-if="loadingSummary">{{loadingSummary}}</view><view v-else>请选择</view><image style="width:20rpx;height:20rpx;margin-left:12rpx;" src="/static/images/icon_2jt.png"></image></view>
				</view>
				<view v-if="showLoadingPanel" style="padding:20rpx 24rpx;">
					<view v-if="loadingRequirementsList && loadingRequirementsList.length">
						<view v-for="item in loadingRequirementsList" :key="item.id" class="flexJusp" style="align-items:center;margin-bottom:16rpx;">
							<view class="flex" style="align-items:center;">
								<image v-if="selectedLoadingRequirements.includes(item.id)" @click.stop="toggleLoadingRequirement(item)" style="width:32rpx;height:32rpx;margin-right:12rpx;" src="/static/images/icon_2xuan.png"></image>
								<image v-else @click.stop="toggleLoadingRequirement(item)" style="width:32rpx;height:32rpx;margin-right:12rpx;" src="/static/images/icon_2xuan1.png"></image>
								<view @click.stop="toggleLoadingRequirement(item)">{{item.name}}</view>
							</view>
						</view>
					</view>
				</view>
				<picker @change="bindPickerChangesonghuo" :value="songIndex" :range="songList" range-key="name">
					<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
						<view>送货方式</view>
						<view class="flex" style="align-items: center;"><view v-if="songList[songIndex]">{{songList[songIndex].name}}</view><view v-else>请选择</view><view v-if="songIndex >= 0" @click.stop="clearSonghuo" style="margin-left:16rpx;font-size:24rpx;color:#999;">取消选择</view><image style="width:20rpx;height:20rpx;margin-left:12rpx;" src="/static/images/icon_2jt.png"></image></view>
					</view>
				</picker>
				<picker mode="multiSelector" @change="bindPickerChangehui" :value="huiIndex" :range="huiList" @columnchange="columnchanges" range-key="name">
					<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
						<view>回单</view>
						<view class="flex" style="align-items: center;"><view v-if="huiList[0] && huiList[0].length > 0 && huiList[1] && huiList[1].length > 0 && huiList[0][huiIndex[0]] && huiList[1][huiIndex[1]]">{{huiList[0][huiIndex[0]].name}}-{{huiList[1][huiIndex[1]].name}}</view><view v-else>请选择</view><view v-if="huiIndex[0] >= 0 && huiIndex[1] >= 0" @click.stop="clearHui" style="margin-left:16rpx;font-size:24rpx;color:#999;">取消选择</view><image style="width:20rpx;height:20rpx;margin-left:12rpx;" src="/static/images/icon_2jt.png"></image></view>
					</view>
				</picker>
				<picker @change="bindPickerChangefuwu" :value="fuwuIndex" :range="fuwuList">
					<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
						<view>服务方式</view>
						<view class="flex" style="align-items: center;"><view v-if="fuwuList[fuwuIndex]">{{fuwuList[fuwuIndex]}}</view><view v-else>请选择</view><image style="width:20rpx;height:20rpx;margin-left:12rpx;" src="/static/images/icon_2jt.png"></image></view>
					</view>
				</picker>
				<picker mode="multiSelector" @change="bindPickerChangechai" :value="chaiIndex" :range="chaiList" range-key="name">
					<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
						<view>拆包服务</view>
						<view class="flex" style="align-items: center;"><view v-if="chaiList[0][chaiIndex[0]]">{{chaiList[0][chaiIndex[0]].name}}</view><view v-else>请选择</view><view v-if="chaiIndex[0] >= 0 && chaiIndex[1] >= 0" @click.stop="clearChai" style="margin-left:16rpx;font-size:24rpx;color:#999;">取消选择</view><image style="width:20rpx;height:20rpx;margin-left:12rpx;" src="/static/images/icon_2jt.png"></image></view>
					</view>
				</picker>
				<picker @change="bindPickerChangekong" :value="kongIndex" :range="kongType">
					<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
						<view>控货要求</view>
						<view class="flex" style="align-items: center;"><view v-if="kongType[kongIndex]">{{kongType[kongIndex]}}</view><view v-else>请选择</view><view v-if="kongIndex >= 0" @click.stop="clearKong" style="margin-left:16rpx;font-size:24rpx;color:#999;">取消选择</view><image style="width:20rpx;height:20rpx;margin-left:12rpx;" src="/static/images/icon_2jt.png"></image></view>
					</view>
				</picker>
				<view @click="toggleOtherPanel" class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>其他费用</view>
					<view class="flex" style="align-items: center;"><view v-if="otherSummary">{{otherSummary}}</view><view v-else>请选择</view><image style="width:20rpx;height:20rpx;margin-left:12rpx;" src="/static/images/icon_2jt.png"></image></view>
				</view>
				<view v-if="showOtherPanel" style="padding:20rpx 24rpx;">
					<view v-if="qitaList && qitaList.length">
						<view v-for="item in qitaList" :key="item.id" class="flexJusp" style="align-items:center;margin-bottom:16rpx;">
							<view class="flex" style="align-items:center;">
								<image v-if="isOtherSelected(item.id)" @click.stop="toggleOther(item)" style="width:32rpx;height:32rpx;margin-right:12rpx;" src="/static/images/icon_2xuan.png"></image>
								<image v-else @click.stop="toggleOther(item)" style="width:32rpx;height:32rpx;margin-right:12rpx;" src="/static/images/icon_2xuan1.png"></image>
								<view @click.stop="toggleOther(item)">{{item.name}}</view>
							</view>
							<view style="color:#F29100;">{{formatOtherPrice(item)}}</view>
						</view>
					</view>
				</view>
				<picker @change="bindPickerChangexin" :value="xinIndex" :range="xinType">
					<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
						<view>短信通知</view>
						<view class="flex" style="align-items: center;"><view v-if="xinType[xinIndex]">{{xinType[xinIndex]}}</view><view v-else>请选择</view><view v-if="xinIndex >= 0" @click.stop="clearXin" style="margin-left:16rpx;font-size:24rpx;color:#999;">取消选择</view><image style="width:20rpx;height:20rpx;margin-left:12rpx;" src="/static/images/icon_2jt.png"></image></view>
					</view>
				</picker>
				<view style="padding:24rpx 0;border-bottom:2rpx solid #F7F7F7;" :class="{ 'form-section-disabled': isRejectOnlyEdit && !isFieldEditable('remark','break') }">
					<view style="margin-bottom:16rpx;">备注</view>
					<textarea :value="remark" :disabled="isRejectOnlyEdit && !isFieldEditable('remark','break')" @input="remark = $event.detail.value" placeholder="请输入备注" style="width:100%;min-height:120rpx;background:#F7F7F7;border-radius:12rpx;padding:16rpx;font-size:28rpx;box-sizing:border-box;"></textarea>
				</view>
			</view>
		</view>
		<view class="flex" style="font-size: 26rpx;align-items: center;justify-content: center;color:#C0C0C0;">
			<image @click="check=!check" v-if="check" style="width:28rpx;height:28rpx;margin:0 10rpx 0 0;" src="/static/images/icon_1xuan1.png"></image>
			<image @click="check=!check" v-else style="width:28rpx;height:28rpx;margin:0 10rpx 0 0;" src="/static/images/icon_1xuan.png"></image>
			<view><text @click="check=!check">已阅读并同意</text><text @click="agreement('xiadan')" style="color:#4878FF;">《龙喆互通货物运输信息服务协议》</text></view>
		</view>
		<view class="buttons" @click="tijiao">立即提交</view>
		<view style="height:60rpx;"></view>
		</scroll-view>
		<uni-popup ref="quickInputPopup" type="center">
			<view style="width: 680rpx;background: #FFFFFF;border-radius: 24rpx;padding: 40rpx;">
				<view class="flexJusp" style="align-items: center;margin-bottom: 32rpx;">
					<view style="font-weight: bold;font-size: 32rpx;">地址粘贴识别</view>
					<view @click="closeQuickInput" style="width:40rpx;height:40rpx;display: flex;align-items: center;justify-content: center;"><image style="width:24rpx;height:24rpx;" src="/static/images/icon_3qx.png"></image></view>
				</view>
				<view style="margin-bottom: 24rpx;">
					<view style="font-size: 28rpx;color: #333;margin-bottom: 16rpx;">请输入地址信息</view>
					<textarea v-model="quickInputText" style="width:100%;min-height:200rpx;background:#F7F7F7;border-radius:12rpx;padding:20rpx;font-size:28rpx;box-sizing:border-box;" maxlength="200"></textarea>
					<view style="font-size: 24rpx;color: #999;margin-top: 12rpx;">支持格式：姓名 手机号 地址</view>
				</view>
				<view class="flex" style="gap: 20rpx;">
					<view @click="closeQuickInput" style="flex:1;height:80rpx;background:#F7F7F7;border-radius:12rpx;font-size:28rpx;color:#333;line-height:80rpx;text-align:center;">取消</view>
					<view @click="confirmQuickInput" style="flex:1;height:80rpx;background:linear-gradient(180deg, #4897FF 0%, #4878FF 100%);border-radius:12rpx;font-size:28rpx;color:#FFFFFF;line-height:80rpx;text-align:center;">确定</view>
				</view>
			</view>
		</uni-popup>
		<uni-popup ref="popup" type="bottom">
			<view style="width: 750rpx;background: #FFFFFF;border-radius: 32rpx 32rpx 0rpx 0rpx;">
				<view class="flexJusp" style="height:120rpx;align-items: center;">
					<view style="width:80rpx;"></view>
					<view style="font-weight: bold;font-size: 32rpx;">请选择用车时间</view>
					<view style="width:80rpx;text-align: center;" @click="guanbi"><image style="width:24rpx;height:24rpx;" src="/static/images/icon_3qx.png"></image></view>
				</view>
				<view style="margin:0 32rpx;" class="flexJusp">
					<view style="width:33.33%;">日期</view>
					<view style="width:33.33%;text-align: center;">最早装货</view>
					<view style="width:33.33%;text-align: right;">最晚装货</view>
				</view>
				<view style="margin:0 32rpx;padding:32rpx 0;" class="flexJusp">
					<view style="width:33.33%;"><l-picker indicatorStyle="font-size:28rpx;" itemFontSize="14px" itemHeight="36px" groupHeight="120px" @pick="onChange"><l-picker-item :options="cityOptions" v-model="riqi"></l-picker-item></l-picker></view>
					<view style="width:33.33%;text-align: center;"><l-picker indicatorStyle="font-size:28rpx;" itemFontSize="14px" itemHeight="36px" groupHeight="120px" @pick="onChange1"><l-picker-item :options="hourList" v-model="zuizao"></l-picker-item></l-picker></view>
					<view style="width:33.33%;text-align: right;"><l-picker indicatorStyle="font-size:28rpx;" itemFontSize="14px" itemHeight="36px" groupHeight="120px" @pick="onChange2"><l-picker-item :options="hourLists" v-model="zuiwan"></l-picker-item></l-picker></view>
				</view>
				<view style="font-weight: bold;font-size: 30rpx;color: #4878FF;text-align: center;">{{riqis||''}} {{zuizaos||''}}-{{zuiwans||''}} 装货</view>
				<view class="buttonx" @click="queding">确定</view>
				<view style="height:120rpx;"></view>
			</view>
		</uni-popup>
	</view>
</template>

<script>
	import { systemInfo } from '@/https/mixin.js'
	export default {
		mixins: [systemInfo],
		data() {
			return {
				imgUrl: this.$utils.imgUrl,
				editOrderId: null,
				_detailLoaded: false,
				// 驳回状态：仅可改 reject_field 对应项
				orderPayStatus: null,
				rejectField: '',
				rejectReason: '',
				riqi: '', zuizao: '', zuiwan: '', riqis: '', zuizaos: '', zuiwans: '',
				indexs: null,
				cityOptions: [{ value: '测试', label: '测试' }],
				carType: 1, isinvoice: 0, delivery: 0, check: false,
				zhuangAddress: {}, xieAddress: {},
				carList: [], carIndex: 0, typeList: [], typeIndex: 0,
				baoList: [], showPackagePanel: false, packageSelections: [], _pendingPackageList: null,
				songList: [], songIndex: -1, huiList: [[],[]], huiLists: [], huiIndex: [-1,-1],
				chaiList: [[],[]], chaiIndex: [-1,-1], qitaList: [], showOtherPanel: false, otherSelections: [],
				showDeliveryPanel: false, showLoadingPanel: false,
				deliveryRequirementsList: [], loadingRequirementsList: [],
				selectedDeliveryRequirements: [], selectedLoadingRequirements: [],
				kaiType: ['个人', '公司'], kaiIndex: 0,
				kongType: ['到站点等通知放货', '到收货地等通知放货'], kongIndex: -1,
				xinType: ['收件方', '发件方'], xinIndex: -1,
				fuwuList: ['派送', '自提'], fuwuIndex: 0,
				remark: '', pay_type: 1, pay_party: 0, type: 1, isrequirements: 0,
				quantity: 1, weight: '', long: '', wide: '', hige: '', direction: '',
				pay_price: '',
				sizeList: [{long: '', wide: '', hige: ''}],
				behalf_price: '', bank_branch: '', open_number: '', bank_type: '', open_name: '', open_type: '',
				charge: '', tax_point: '', company_letterhead: '', company_tax_id: '', company_email: '', company_mobile: '', bank_deposits: '',
				earliest_time: '', latest_time: '', goods_name: '', goods_image: '',
				hourList: [], hourLists: [], hourList1: [], hourLists1: [], hourList2: [],
				tijiaos: true, quickInputText: '', quickInputType: 1,
				userType: 1, information: '', deposit: '', information_image: ''
			}
		},
		onLoad(option) {
			this.getSystemInfo()
			this.editOrderId = option.order_id || option.id || null
			this.loadx()
			this.jinri()
		},
		onShow() {
			// 兼容从 editOrder 中转：若 storage 有数据则用，用完即清
			const editOrderData = uni.getStorageSync('editOrderData')
			const editOrderId = uni.getStorageSync('editOrderId')
			if (editOrderData && editOrderId) {
				uni.removeStorageSync('editOrderData') 
				uni.removeStorageSync('editOrderId')
				this.editOrderId = editOrderId
				this._detailLoaded = true
				this.fillForm(editOrderData)
				return
			}
			// 直接进入（带 order_id）：仅未加载过详情时请求一次，避免从地址簿返回时重复请求
			if (this.editOrderId && !this._detailLoaded) {
				this._detailLoaded = true
				this.$httpapi('Placeorder/orderdetaile', 'POST', { order_id: this.editOrderId }).then(rex => {
					if (rex && rex.data) this.fillForm(rex.data)
				}).catch(() => {
					uni.showToast({ title: '加载订单失败', icon: 'none' })
					setTimeout(() => uni.navigateBack(), 1500)
				})
				return
			}
			if (!this.editOrderId) {
				uni.showToast({ title: '缺少订单信息', icon: 'none' })
				setTimeout(() => uni.navigateBack(), 1500)
			}
		},
		computed: {
			// 税点列表：专票=6%/9%，普票=1%/2%
			array() {
				return this.$utils.taxArray(this.type)
			},
			// 是否为「驳回且仅可改某一项」模式
			isRejectOnlyEdit() {
				return Number(this.orderPayStatus) === 8 && (this.rejectField || '').trim() !== ''
			},
			isInternalStaff() { return Number(this.userType) === 2 || Number(this.userType) === 3 },
			canAddMultipleSizes() { return this.typeList && this.typeList[this.typeIndex] && this.typeList[this.typeIndex].switch == 1 },
			packageSummary() { return (this.packageSelections || []).filter(i => i.quantity > 0).map(i => `${i.name}x${i.quantity}`).join('，') || '' },
			otherSummary() { return (this.otherSelections || []).map(i => i.name).join('，') || '' },
			deliverySummary() { return (this.deliveryRequirementsList || []).filter(i => (this.selectedDeliveryRequirements || []).includes(i.id)).map(i => i.name).join('，') || '' },
			loadingSummary() { return (this.loadingRequirementsList || []).filter(i => (this.selectedLoadingRequirements || []).includes(i.id)).map(i => i.name).join('，') || '' }
		},
		methods: {
			selectTaxType(t) { this.type = t; this.syncTaxIndexs() },
			// 根据发票类型与已选税点校正索引，避免切换类型后索引越界或错位
			syncTaxIndexs() {
				const arr = this.array
				if (this.tax_point) {
					const idx = arr.indexOf(this.tax_point)
					this.indexs = idx >= 0 ? idx : 0
				} else if (this.indexs == null || this.indexs >= arr.length) {
					this.indexs = 0
				}
			},
			goBack() { uni.navigateBack({ fail: () => { uni.switchTab({ url: '/pages/order/order' }) } }) },
			applyPendingPackageSelection() {
				if (!this._pendingPackageList || this._pendingPackageList.length === 0 || !this.baoList || this.baoList.length === 0) return
				this.packageSelections = []
				this._pendingPackageList.forEach(pkgItem => {
					const pendingId = pkgItem && pkgItem.id != null ? String(pkgItem.id).trim() : ''
					const pendingName = pkgItem && pkgItem.name != null ? String(pkgItem.name).trim() : ''
					const matched = this.baoList.find(bao => {
						const baoId = bao && bao.id != null ? String(bao.id).trim() : ''
						const baoName = bao && bao.name != null ? String(bao.name).trim() : ''
						return (pendingId && baoId && pendingId === baoId) || (pendingName && baoName && pendingName === baoName)
					})
					if (matched) this.packageSelections.push({ id: matched.id, name: matched.name, quantity: pkgItem.quantity || 1 })
				})
				this._pendingPackageList = null
				this.calculateTotalPackageQuantity()
			},
			// 驳回仅改一项时：判断某区块是否可编辑。keys 为后端 reject_field 可能取值（如 'loading_address','loading'）
			isFieldEditable(...keys) {
				if (!this.isRejectOnlyEdit) return true
				const r = (this.rejectField || '').trim().toLowerCase()
				if (!r) return true
				return keys.some(k => k && r === String(k).toLowerCase())
			},
			_parseRequirementIds(val) {
				if (val == null || val === '') return []
				if (Array.isArray(val)) return val.map(id => (typeof id === 'number' ? id : (isNaN(Number(id)) ? id : Number(id))))
				const str = String(val).trim()
				if (!str) return []
				return str.split(',').map(s => {
					const n = Number(s.trim())
					return isNaN(n) ? s.trim() : n
				}).filter(v => v !== '' && v !== undefined)
			},
			fillForm(details) {
				if (!details) return
				this.orderPayStatus = details.pay_status != null ? details.pay_status : null
				this.rejectField = (details.reject_field != null && details.reject_field !== '') ? String(details.reject_field).trim() : ''
				this.rejectReason = details.reject || details.reject_reason || ''
				if (details.find_car_type == '专车') this.carType = 0
				else if (details.find_car_type == '配车') this.carType = 1
				else this.carType = 2
				this.quantity = details.quantity || 1
				this.weight = details.weight
				this.long = details.long
				this.wide = details.wide
				this.hige = details.hige
				if (details.sizeList && Array.isArray(details.sizeList) && details.sizeList.length > 0) {
					this.sizeList = details.sizeList.map(item => ({ long: item.long || '', wide: item.wide || '', hige: item.hige || '' }))
				} else {
					this.sizeList = [{ long: details.long || '', wide: details.wide || '', hige: details.hige || '' }]
				}
				this.direction = details.direction
				this.pay_price = details.pay_price != null && details.pay_price !== '' ? details.pay_price : ''
				this.isinvoice = details.isinvoice
				if (details.pay_type == '到付' || details.pay_type == '到付-寄货方' || details.pay_type == '到付-收货方') {
					this.pay_type = 1
					this.pay_party = (details.pay_type == '到付-收货方') ? 1 : 0
				} else if (details.pay_type == '现付' || details.pay_type == '寄付') { this.pay_type = 0; this.pay_party = 0 }
				else { this.pay_type = 2; this.pay_party = 0 }
				this.delivery = (details.delivery == '代收货款') ? 1 : 0
				this.earliest_time = details.earliest_time
				this.latest_time = details.latest_time
				this.isrequirements = details.isrequirements || 0
				this.behalf_price = details.behalf_price
				this.bank_branch = details.bank_branch
				this.open_number = details.open_number
				this.bank_type = details.bank_type
				this.open_name = details.open_name
				this.charge = details.charge
				this.type = details.type
				this.tax_point = details.tax_point
				this.syncTaxIndexs()
				this.company_letterhead = details.company_letterhead
				this.company_tax_id = details.company_tax_id
				this.company_email = details.company_email
				this.company_mobile = details.company_mobile
				this.bank_deposits = details.bank_deposits
				this.information = details.information || ''
				this.deposit = details.deposit || ''
				this.information_image = details.information_image || ''
				this.goods_name = details.goods_name || ''
				this.goods_image = details.goods_image || ''
				this.zhuangAddress = details.loading_address || {}
				this.xieAddress = details.unload_address || {}
				this.remark = details.break || details.remark || ''
				if (details.packaging_list && Array.isArray(details.packaging_list) && details.packaging_list.length > 0) {
					this._pendingPackageList = details.packaging_list
				} else {
					const rawPackaging = details.packaging_id || details.packaging || details.pack_type || details.pack_id
					if (rawPackaging) this._pendingPackageList = [{ id: rawPackaging, name: rawPackaging, quantity: 1 }]
				}
				this.applyPendingPackageSelection()
				// 提货要求、专线装货要求：数据库字段为 deliveryrequirements_id / loadingrequirements_id
				const deliveryIdsRaw = details.deliveryrequirements_id != null ? details.deliveryrequirements_id : details.deliveryrequirements
				const loadingIdsRaw = details.loadingrequirements_id != null ? details.loadingrequirements_id : details.loadingrequirements
				this.selectedDeliveryRequirements = this._parseRequirementIds(deliveryIdsRaw)
				this.selectedLoadingRequirements = this._parseRequirementIds(loadingIdsRaw)
				this.$nextTick(() => {
					const deliveryIds = this._parseRequirementIds(deliveryIdsRaw)
					const loadingIds = this._parseRequirementIds(loadingIdsRaw)
					if (this.deliveryRequirementsList && deliveryIds.length) {
						this.selectedDeliveryRequirements = this.deliveryRequirementsList
							.filter(item => deliveryIds.some(id => id == item.id || String(id) === String(item.id)))
							.map(item => item.id)
					}
					if (this.loadingRequirementsList && loadingIds.length) {
						this.selectedLoadingRequirements = this.loadingRequirementsList
							.filter(item => loadingIds.some(id => id == item.id || String(id) === String(item.id)))
							.map(item => item.id)
					}
					if (this.carList && details.car_type_id) { for (let i = 0; i < this.carList.length; i++) { if (details.car_type_id == this.carList[i].name) { this.carIndex = i; break } } }
					if (this.typeList && details.goods_type_id) { for (let i = 0; i < this.typeList.length; i++) { if (details.goods_type_id == this.typeList[i].name) { this.typeIndex = i; break } } }
					if (this.songList && details.delivery_type_id) { for (let i = 0; i < this.songList.length; i++) { if (details.delivery_type_id == this.songList[i].name) { this.songIndex = i; break } } }
					if (this.huiList[1] && details.receipt_type_id) { for (let i = 0; i < (this.huiList[1].length || 0); i++) { if (details.receipt_type_id == (this.huiList[1][i] && this.huiList[1][i].name)) { this.huiIndex = [this.huiIndex[0] >= 0 ? this.huiIndex[0] : 0, i]; break } } }
					if (this.fuwuList && details.service) { for (let i = 0; i < this.fuwuList.length; i++) { if (details.service == this.fuwuList[i]) { this.fuwuIndex = i; break } } }
					if (this.chaiList[0] && details.unpack_id) { for (let i = 0; i < (this.chaiList[0].length || 0); i++) { if (details.unpack_id == (this.chaiList[0][i] && this.chaiList[0][i].name)) { this.chaiIndex = [i, this.chaiIndex[1] >= 0 ? this.chaiIndex[1] : 0]; break } } }
					if (this.kongType && details.control) { for (let i = 0; i < this.kongType.length; i++) { if (details.control == this.kongType[i]) { this.kongIndex = i; break } } }
					if (this.qitaList && details.other_id) { for (let i = 0; i < this.qitaList.length; i++) { if (details.other_id == this.qitaList[i].name || details.other_id == this.qitaList[i].id) { this.otherSelections = [{ id: this.qitaList[i].id, name: this.qitaList[i].name, price: this.qitaList[i].price || this.qitaList[i].other_type_price }]; break } } }
					if (this.kaiType && details.open_type) { for (let i = 0; i < this.kaiType.length; i++) { if (details.open_type == this.kaiType[i]) { this.kaiIndex = i; break } } }
					if (details.text_message == '短信通知收件方') this.xinIndex = 0
					else this.xinIndex = 1
					if (details.earliest_time) { const p = details.earliest_time.split(' '); if (p[0]) this.riqi = p[0]; if (p[1]) this.zuizao = p[1].replace(/:00$/, '') }
					if (details.latest_time) { const p = details.latest_time.split(' '); if (p[0]) this.riqis = p[0]; if (p[1]) this.zuiwan = p[1].replace(/:00$/, '') }
				})
			},
			loadx() {
				this.$httpapi('Placeorder/car_type', 'POST', {}).then(res => { this.carList = res.data || [] })
				this.$httpapi('Placeorder/goods_type', 'GET', {}).then(res => { this.typeList = res.data || [] })
				this.$httpapi('Placeorder/packaging', 'GET', {}).then(res => {
					this.baoList = res.data || []
					this.applyPendingPackageSelection()
				})
				this.$httpapi('Placeorder/delivery_type', 'GET', {}).then(res => { this.songList = res.data || [] })
				this.$httpapi('Placeorder/other', 'GET', {}).then(res => { this.qitaList = res.data || [] })
				this.$httpapi('index/deliveryrequirements', 'GET', {}).then(res => { this.deliveryRequirementsList = res.data || [] })
				this.$httpapi('index/loadingrequirements', 'GET', {}).then(res => { this.loadingRequirementsList = res.data || [] })
				this.$httpapi('Placeorder/receipt_type', 'GET', {}).then(res => {
					this.huiList = [[],[]]; this.huiIndex = [-1,-1]; this.huiLists = []
					if (res.data && Array.isArray(res.data) && res.data.length > 0) {
						this.huiLists = res.data
						const typeSet = new Set(); res.data.forEach(item => { if (item.type) typeSet.add(item.type) })
						const typeArray = Array.from(typeSet).map(t => ({ name: t }))
						if (typeArray.length > 0) {
							const subOptions = res.data.filter(item => item.type === typeArray[0].name).map(item => ({ id: item.id, name: item.name, price: item.receipt_type_price, type: item.type }))
							this.huiList = [typeArray, subOptions]
						}
					}
				})
				this.$httpapi('Placeorder/unpack', 'GET', {}).then(res => {
					this.chaiList = [[],[]]; this.chaiIndex = [-1,-1]
					const numArray = []; for (let i = 1; i <= 9999; i++) numArray.push({ name: i })
					this.chaiList[0] = res.data || []; this.chaiList[1] = numArray
				})
				this.$httpapi('Opinion/getuserinfo', 'GET', {}).then(res => {
					if (res && res.data) { const mt = res.data.membertype ?? res.data.user_type; this.userType = mt !== undefined && mt !== null ? Number(mt) : 1 }
				})
			},
			jinri() {
				this.cityOptions = []; this.hourList = []; this.hourLists = []; this.hourList1 = []; this.hourLists1 = []; this.hourList2 = []
				const today = new Date(); const todayTimestamp = today.setHours(0, 0, 0, 0)
				for (let i = 0; i < 60; i++) {
					const currentDate = new Date(todayTimestamp); currentDate.setDate(currentDate.getDate() + i)
					const dateTimestamp = currentDate.setHours(0, 0, 0, 0)
					const diffDays = Math.floor((dateTimestamp - todayTimestamp) / (1000 * 60 * 60 * 24))
					let dateDesc = '', xiandate = ''
					const year = currentDate.getFullYear(), month = String(currentDate.getMonth() + 1).padStart(2, '0'), day = String(currentDate.getDate()).padStart(2, '0')
					xiandate = `${year}-${month}-${day}`
					if (diffDays === 0) dateDesc = '今天'
					else if (diffDays === 1) dateDesc = '明天'
					else dateDesc = `${month}-${day}`
					const weekMap = ['日', '一', '二', '三', '四', '五', '六']; const weekDesc = '星期' + weekMap[currentDate.getDay()]
					this.cityOptions.push({ label: dateDesc + ' ' + weekDesc, value: xiandate })
				}
				const now = new Date(); let totalMinutes = now.getHours() * 60 + now.getMinutes()
				const remainder = totalMinutes % 30; if (remainder !== 0) totalMinutes += (30 - remainder)
				for (; totalMinutes <= 24 * 60; totalMinutes += 30) {
					const hour = Math.floor(totalMinutes / 60), minute = totalMinutes % 60
					const timeStr = `${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}`
					const item = { label: timeStr, value: timeStr }
					this.hourList.push(item); this.hourLists.push(item); this.hourList1.push(item); this.hourLists1.push(item)
				}
				for (let hour = 1; hour <= 24; hour++) {
					for (let minute of [0, 30]) {
						const timeStr = `${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}`
						this.hourList2.push({ label: timeStr, value: timeStr })
					}
				}
				this.riqis = this.cityOptions[0] && this.cityOptions[0].label; this.zuizaos = this.hourList[0] && this.hourList[0].label
				this.riqi = this.cityOptions[0] && this.cityOptions[0].value; this.zuizao = this.hourList[0] && this.hourList[0].value
				this.setLatestTimeAfterEarliest()
			},
			setLatestTimeAfterEarliest() {
				if (!this.zuizao || !this.hourLists || !this.hourLists.length) return
				const [earliestHour, earliestMinute] = this.zuizao.split(':').map(Number)
				let latestMinute = earliestMinute + 30, latestHour = earliestHour
				if (latestMinute >= 60) { latestMinute = 0; latestHour += 1 }
				if (latestHour > 24) { latestHour = 24; latestMinute = 0 }
				const latestTimeStr = `${String(latestHour).padStart(2, '0')}:${String(latestMinute).padStart(2, '0')}`
				const idx = this.hourLists.findIndex(item => item.value === latestTimeStr)
				if (idx >= 0) { this.zuiwan = this.hourLists[idx].value; this.zuiwans = this.hourLists[idx].label }
				else { const found = this.hourLists.find(item => { const [h, m] = item.value.split(':').map(Number); return h > latestHour || (h === latestHour && m >= latestMinute) }); if (found) { this.zuiwan = found.value; this.zuiwans = found.label } }
				this.$forceUpdate()
			},
			onChange(e) { this.riqi = this.cityOptions[e.index].value; this.riqis = this.cityOptions[e.index].label; this.hourList = e.index > 0 ? this.hourList2 : this.hourLists1; this.hourLists = this.hourList; this.zuizao = this.hourList[0].value; this.zuizaos = this.hourList[0].label; this.$nextTick(() => this.setLatestTimeAfterEarliest()) },
			onChange1(e) { this.zuizao = this.hourList[e.index].value; this.zuizaos = this.hourList[e.index].label; this.setLatestTimeAfterEarliest() },
			onChange2(e) { this.zuiwan = this.hourLists[e.index].value; this.zuiwans = this.hourLists[e.index].label },
			queding() { this.earliest_time = this.riqi + ' ' + this.zuizao + ':00'; this.latest_time = this.riqi + ' ' + this.zuiwan + ':00'; this.$refs.popup.close() },
			guanbi() { this.$refs.popup.close() },
			fahuo() { this.$refs.popup.open('bottom') },
			sanitizeNumber(value, decimalPlaces) {
				value = String(value || '').replace(/[^\d.]/g, ''); const parts = value.split('.')
				if (parts.length > 1) value = parts[0] + '.' + parts.slice(1).join('')
				if (decimalPlaces === 0) return (parts[0] || '')
				if (decimalPlaces != null && parts.length > 1) return parts[0] + '.' + (parts[1] || '').slice(0, decimalPlaces)
				return value
			},
			onNumberInput(field, value, decimalPlaces) { this[field] = this.sanitizeNumber(value, decimalPlaces) },
			zhongliang() {},
			chang() { if (this.long && this.wide && this.hige) { const nums = this.long * this.wide * this.hige; this.direction = nums.toFixed(4) } },
			onSizeInput(index, key, value) { this.$set(this.sizeList[index], key, this.sanitizeNumber(value, 0)); this.canAddMultipleSizes ? this.calculateTotalDirection() : this.chang() },
			changMultiple(index) { this.calculateTotalDirection() },
			calculateTotalDirection() { let total = 0; this.sizeList.forEach(item => { if (item.long && item.wide && item.hige) total += parseFloat(item.long) * parseFloat(item.wide) * parseFloat(item.hige) }); this.direction = total.toFixed(4) },
			addSize() { this.sizeList.push({ long: '', wide: '', hige: '' }) },
			removeSize(index) { if (this.sizeList.length > 1) { this.sizeList.splice(index, 1); this.calculateTotalDirection() } },
			jianjian() { if (this.quantity > 1) this.quantity-- },
			onQuantityInput(e) { const num = parseInt(e.detail.value, 10); if (!isNaN(num) && num >= 1) this.quantity = num },
			onQuantityBlur(e) { const num = parseInt(e.detail.value, 10); this.quantity = (isNaN(num) || num < 1) ? 1 : num },
			togglePackagePanel() { this.showPackagePanel = !this.showPackagePanel },
			isPackageSelected(id) { return (this.packageSelections || []).some(item => item.id === id) },
			getPackageQuantity(id) { const t = (this.packageSelections || []).find(item => item.id === id); return t && t.quantity ? t.quantity : 1 },
			calculateTotalPackageQuantity() { const total = (this.packageSelections || []).reduce((sum, item) => sum + (item.quantity || 1), 0); this.quantity = total > 0 ? total : 1 },
			togglePackage(item) { if (!item || !item.id) return; const arr = this.packageSelections || []; const idx = arr.findIndex(p => p.id === item.id); if (idx > -1) arr.splice(idx, 1); else arr.push({ id: item.id, name: item.name, quantity: 1 }); this.packageSelections = arr; this.calculateTotalPackageQuantity() },
			changePackageQuantity(id, delta) { const t = (this.packageSelections || []).find(item => item.id === id); if (!t) return; t.quantity = (t.quantity || 1) + delta; if (t.quantity < 1) t.quantity = 1; this.calculateTotalPackageQuantity() },
			toggleDeliveryRequirement(item) { if (!item || !item.id) return; const idx = (this.selectedDeliveryRequirements || []).indexOf(item.id); if (idx > -1) this.selectedDeliveryRequirements.splice(idx, 1); else this.selectedDeliveryRequirements.push(item.id) },
			toggleLoadingRequirement(item) { if (!item || !item.id) return; const idx = (this.selectedLoadingRequirements || []).indexOf(item.id); if (idx > -1) this.selectedLoadingRequirements.splice(idx, 1); else this.selectedLoadingRequirements.push(item.id) },
			toggleDeliveryPanel() { this.showDeliveryPanel = !this.showDeliveryPanel },
			toggleLoadingPanel() { this.showLoadingPanel = !this.showLoadingPanel },
			toggleOtherPanel() { this.showOtherPanel = !this.showOtherPanel },
			isOtherSelected(id) { return (this.otherSelections || []).some(item => item.id === id) },
			toggleOther(item) { if (!item || !item.id) return; const arr = this.otherSelections || []; const idx = arr.findIndex(o => o.id === item.id); if (idx > -1) arr.splice(idx, 1); else arr.push({ id: item.id, name: item.name, price: item.price ?? item.other_type_price }); this.otherSelections = arr },
			formatOtherPrice(item) { const p = item && (item.price ?? item.other_type_price); return p === undefined || p === null || p === '' ? '—' : (Number(p) === 0 ? '免费' : `￥${p}`) },
			clearZhuangAddress() { this.zhuangAddress = {} },
			clearXieAddress() { this.xieAddress = {} },
			swapAddress() { const t = this.zhuangAddress; this.zhuangAddress = this.xieAddress; this.xieAddress = t; uni.showToast({ title: '地址已互换', duration: 1000, icon: 'none' }) },
			zhuangAddressClick() { if (this.zhuangAddress && this.zhuangAddress.address) this.showAddressDetail(this.zhuangAddress, '装货地址'); else this.chooseAndSaveAddress(1) },
			xieAddressClick() { if (this.xieAddress && this.xieAddress.address) this.showAddressDetail(this.xieAddress, '卸货地址'); else this.chooseAndSaveAddress(2) },
			zhuangxuan() { const that = this; uni.navigateTo({ url: '/pages/user/address/address?type=1', events: { acceptDataFromOpenedPages: (data) => { that.zhuangAddress = data } } }) },
			xiexuan() { const that = this; uni.navigateTo({ url: '/pages/user/address/address?type=2', events: { acceptDataFromOpenedPages: (data) => { that.xieAddress = data } } }) },
			showAddressDetail(address, title) { const addressData = { id: address.id, user_name: address.user_name, mobile: address.mobile, address: address.address, detailed_address: address.detailed_address, lat: address.lat || address.latitude, lng: address.lng || address.longitude, default: address.default || 0, type: address.type || (title === '装货地址' ? '1' : '2') }; uni.setStorageSync('addressData', addressData); uni.navigateTo({ url: '/pages/user/address/addAddress?type=' + addressData.type }) },
			chooseAndSaveAddress(type) {
				const that = this
				uni.chooseLocation({
					success(res) {
						const addressName = res.name || res.address, detailedAddress = res.address || ''
						that.$httpapi('Placeorder/address', 'POST', { user_name: '', company_name: '', mobile: '', address: addressName, detailed_address: detailedAddress, default: 0, lat: res.latitude, lng: res.longitude, type: type }).then(resp => {
							uni.showToast({ title: (resp && (resp.msg || resp.message)) || '操作成功', duration: 1500, icon: 'none' })
							if (resp.code == 1) {
								let addressId = typeof resp.data === 'number' || (typeof resp.data === 'string' && resp.data !== '') ? resp.data : (resp.data && typeof resp.data === 'object' && (resp.data.id !== undefined ? resp.data.id : resp.data.address_id))
								const addressData = { id: addressId, user_name: '', company_name: '', mobile: '', address: addressName, detailed_address: detailedAddress, lat: res.latitude, lng: res.longitude }
								if (type == 1) that.zhuangAddress = addressData
								else if (type == 2) that.xieAddress = addressData
							}
						})
					}
				})
			},
			agreement(type) { uni.navigateTo({ url: '/pages/user/agreement/agreement?type=' + type }) },
			bindPickerChange(e) { this.indexs = e.detail.value },
			bindPickerChangeType(e) { this.typeIndex = e.detail.value; if (this.typeList[this.typeIndex] && this.typeList[this.typeIndex].switch == 1 && (this.sizeList.length === 0 || (this.sizeList.length === 1 && !this.sizeList[0].long))) this.sizeList = [{ long: this.long || '', wide: this.wide || '', hige: this.hige || '' }] },
			bindPickerChangeCar(e) { this.carIndex = e.detail.value },
			bindPickerChangekai(e) { this.kaiIndex = e.detail.value },
			bindPickerChangehui(e) { this.huiIndex = e.detail.value },
			bindPickerChangesonghuo(e) { this.songIndex = e.detail.value },
			bindPickerChangefuwu(e) { this.fuwuIndex = e.detail.value },
			bindPickerChangechai(e) { this.chaiIndex = e.detail.value },
			bindPickerChangekong(e) { this.kongIndex = e.detail.value },
			bindPickerChangexin(e) { this.xinIndex = e.detail.value },
			clearSonghuo() { this.songIndex = -1 }, clearHui() { this.huiIndex = [-1, -1] }, clearChai() { this.chaiIndex = [-1, -1] }, clearKong() { this.kongIndex = -1 }, clearXin() { this.xinIndex = -1 },
			columnchanges(e) { if (e.detail.column == 0 && this.huiList[0] && this.huiList[0][e.detail.value]) { const selectedType = this.huiList[0][e.detail.value].name; const subOptions = (this.huiLists || []).filter(item => item.type === selectedType).map(item => ({ id: item.id, name: item.name, price: item.receipt_type_price, type: item.type })); this.huiList = [this.huiList[0], subOptions]; this.huiIndex = [e.detail.value, 0]; this.$forceUpdate() } },
			upImage() { const that = this; uni.chooseImage({ count: 1, sizeType: ['original', 'compressed'], sourceType: ['album', 'camera'], success(res) { that.$upShop('common/upload', res.tempFilePaths[0]).then(rex => { const arr = JSON.parse(rex.data); that.goods_image = arr.data.url }).catch(() => {}) } }) },
			upInformationImage() { const that = this; uni.chooseImage({ count: 1, sizeType: ['original', 'compressed'], sourceType: ['album', 'camera'], success(res) { that.$upShop('common/upload', res.tempFilePaths[0]).then(rex => { const arr = JSON.parse(rex.data); that.information_image = arr.data.url }).catch(() => {}) } }) },
			previewCarImage() { if (this.carList[this.carIndex] && this.carList[this.carIndex].car_image) uni.previewImage({ urls: [this.imgUrl + this.carList[this.carIndex].car_image], current: this.imgUrl + this.carList[this.carIndex].car_image }) },
			openQuickInput(type) { this.quickInputType = type; this.quickInputText = ''; this.$refs.quickInputPopup.open('center') },
			closeQuickInput() { this.$refs.quickInputPopup.close(); this.quickInputText = '' },
			confirmQuickInput() {
				if (!this.quickInputText || !this.quickInputText.trim()) { uni.showToast({ title: '请输入地址信息', duration: 1500, icon: 'none' }); return }
				const parsed = this.parseAddressText(this.quickInputText.trim())
				if (!parsed.address) { uni.showToast({ title: '无法识别地址，请检查格式', duration: 1500, icon: 'none' }); return }
				this.saveQuickInputAddress(parsed, this.quickInputType)
			},
			parseAddressText(text) {
				const result = { user_name: '', mobile: '', address: '' }; const originalText = text.trim()
				const mobileRegex = /1[3-9]\d[\s\-()]?\d{4}[\s\-()]?\d{4}/; const mobileMatch = originalText.match(mobileRegex)
				if (mobileMatch) { result.mobile = mobileMatch[0].replace(/[\s\-()]/g, ''); const mobileIndex = originalText.indexOf(mobileMatch[0]); const textAfter = originalText.substring(mobileIndex + mobileMatch[0].length).trim(); const textBefore = originalText.substring(0, mobileIndex).trim()
					if (textAfter.length <= 3) { const nameMatch = textBefore.match(/([\u4e00-\u9fa5]{2,4})\s*$/); if (nameMatch) { result.user_name = nameMatch[1]; result.address = textBefore.substring(0, textBefore.length - nameMatch[1].length).trim() } else result.address = textBefore }
					else { const nameMatch = textBefore.match(/^([\u4e00-\u9fa5]{2,4})(\s|$)/); if (nameMatch) { result.user_name = nameMatch[1]; result.address = textAfter } else result.address = textAfter }
				} else { const nameMatch = text.match(/^([\u4e00-\u9fa5]{2,4})(\s|$)/); if (nameMatch && (text.substring(nameMatch[1].length).length >= 8 || /省|市|区|县|街道|路|号/.test(text))) { result.user_name = nameMatch[1]; result.address = text.substring(nameMatch[1].length).trim() } else result.address = originalText }
				result.address = (result.address || '').replace(/\s+/g, ' ').trim() || originalText; return result
			},
			saveQuickInputAddress(parsed, type) {
				const that = this; uni.showLoading({ title: '正在保存地址...' })
				that.$httpapi('Placeorder/address', 'POST', { user_name: parsed.user_name || '', mobile: parsed.mobile || '', address: parsed.address, detailed_address: '', default: 0, lat: '', lng: '', type: type }).then(res => {
					uni.hideLoading(); uni.showToast({ title: (res && (res.msg || res.message)) || '保存成功', duration: 1500, icon: 'none' })
					if (res.code == 1) { let addressId = typeof res.data === 'number' || (typeof res.data === 'string' && res.data !== '') ? res.data : (res.data && typeof res.data === 'object' && res.data.id); const addressData = { id: addressId, user_name: parsed.user_name || '', mobile: parsed.mobile || '', address: parsed.address, detailed_address: '', lat: '', lng: '' }; if (type == 1) that.zhuangAddress = addressData; else if (type == 2) that.xieAddress = addressData; that.closeQuickInput() }
				}).catch(() => { uni.hideLoading(); uni.showToast({ title: '保存地址失败', duration: 1500, icon: 'none' }) })
			},
			tijiao() {
				if (!this.check) { uni.showToast({ title: '请阅读并同意下单须知', duration: 1500, icon: 'none' }); return }
				this.earliest_time = this.riqi + ' ' + this.zuizao + ':00'; this.latest_time = this.riqi + ' ' + this.zuiwan + ':00'
				const find_car_type = this.carType == 0 ? '专车' : (this.carType == 1 ? '配车' : '小票快运')
				if (!this.tijiaos) return
				this.tijiaos = false
				let canAddMultiple = this.typeList && this.typeList[this.typeIndex] && this.typeList[this.typeIndex].switch == 1
				let sizeData = canAddMultiple && this.sizeList && this.sizeList.length > 0
					? { long: this.sizeList[0].long || this.long, wide: this.sizeList[0].wide || this.wide, hige: this.sizeList[0].hige || this.hige, sizeList: this.sizeList }
					: { long: this.long, wide: this.wide, hige: this.hige }
				if (!this.typeList || !this.typeList[this.typeIndex]) { uni.showToast({ title: '请选择货物类型', duration: 1500, icon: 'none' }); this.tijiaos = true; return }
				if (!this.packageSelections || this.packageSelections.length === 0) { uni.showToast({ title: '请选择包装方式', duration: 1500, icon: 'none' }); this.tijiaos = true; return }
				if (!this.carList || !this.carList[this.carIndex]) { uni.showToast({ title: '请选择车型', duration: 1500, icon: 'none' }); this.tijiaos = true; return }
				const zhuangAddressId = this.zhuangAddress && this.zhuangAddress.id
				if (zhuangAddressId === undefined || zhuangAddressId === null || zhuangAddressId === '') { uni.showToast({ title: '请选择装货地址', duration: 1500, icon: 'none' }); this.tijiaos = true; return }
				const xieAddressId = this.xieAddress && this.xieAddress.id
				if (xieAddressId === undefined || xieAddressId === null || xieAddressId === '') { uni.showToast({ title: '请选择卸货地址', duration: 1500, icon: 'none' }); this.tijiaos = true; return }
				let requestData = {
					loading: zhuangAddressId, unload: xieAddressId, find_car_type, quantity: this.quantity, goods_name: this.goods_name, weight: this.weight,
					long: sizeData.long, wide: sizeData.wide, hige: sizeData.hige, direction: parseFloat(this.direction || 0).toFixed(2), pay_price: parseFloat(this.pay_price || 0).toFixed(2),
					isinvoice: this.isinvoice === '' || this.isinvoice === null || this.isinvoice === undefined ? 0 : this.isinvoice,
					goods_type_id: this.typeList[this.typeIndex].id, pay_type: this.pay_type, pay_party: this.pay_type === 1 ? this.pay_party : 0, delivery: this.delivery,
					car_type_id: this.carList[this.carIndex].id, earliest_time: this.earliest_time, latest_time: this.latest_time, isrequirements: this.isrequirements, goods_image: this.goods_image,
					service: (this.fuwuList && this.fuwuList[this.fuwuIndex]) ? this.fuwuList[this.fuwuIndex] : '派送', break: this.remark || '', remark: this.remark || '', order_id: this.editOrderId
				}
				if (sizeData.sizeList) requestData.sizeList = sizeData.sizeList
				if (this.packageSelections && this.packageSelections.length > 0) { requestData.packaging_id = this.packageSelections[0].id; requestData.packaging_list = this.packageSelections.map(item => ({ name: item.name, quantity: item.quantity || 1 })) }
				if (this.isrequirements == 1) {
					if (this.songList && this.songList[this.songIndex]) requestData.delivery_type_id = this.songList[this.songIndex].id
					if (this.huiList[1] && this.huiList[1][this.huiIndex[1]] && this.huiList[1][this.huiIndex[1]].id) requestData.receipt_type_id = this.huiList[1][this.huiIndex[1]].id
					if (this.chaiList[0] && this.chaiList[0][this.chaiIndex[0]] && this.chaiList[1] && this.chaiList[1][this.chaiIndex[1]]) { requestData.unpack_id = this.chaiList[0][this.chaiIndex[0]].id; requestData.unpack_num = this.chaiList[1][this.chaiIndex[1]].name }
					if (this.kongType[this.kongIndex]) requestData.control = this.kongType[this.kongIndex]
					if (this.otherSelections && this.otherSelections.length > 0) { requestData.other_id = this.otherSelections[0].id; requestData.other_list = this.otherSelections.map(o => o.id) }
					if (this.xinIndex >= 0 && this.xinType[this.xinIndex] !== undefined) requestData.text_message = parseInt(this.xinIndex) + 1
					if (this.selectedDeliveryRequirements && this.selectedDeliveryRequirements.length > 0) requestData.deliveryrequirements = this.selectedDeliveryRequirements.join(',')
					if (this.selectedLoadingRequirements && this.selectedLoadingRequirements.length > 0) requestData.loadingrequirements = this.selectedLoadingRequirements.join(',')
				}
				if (this.delivery == 1) { requestData.behalf_price = this.behalf_price; requestData.bank_branch = this.bank_branch; requestData.open_number = this.open_number; requestData.bank_type = this.bank_type; requestData.open_name = this.open_name; if (this.kaiType[this.kaiIndex]) requestData.open_type = this.kaiType[this.kaiIndex] }
				if (this.isinvoice == 1) { requestData.charge = this.charge; requestData.type = this.type; if (this.array && this.array[this.indexs]) requestData.tax_point = this.array[this.indexs]; requestData.company_letterhead = this.company_letterhead; requestData.company_tax_id = this.company_tax_id; requestData.company_email = this.company_email; requestData.company_mobile = this.company_mobile; requestData.bank_deposits = this.bank_deposits }
				if (this.isInternalStaff) { requestData.information = this.information || ''; requestData.deposit = this.deposit || ''; requestData.information_image = this.information_image || '' }
				this.$httpapi('Placeorder/update_order', 'POST', requestData).then(res => {
					uni.showToast({ title: (res && (res.msg || res.message)) || (res.code == 1 ? '修改成功' : '提交失败'), duration: 1500, icon: res.code == 1 ? 'success' : 'none' })
					this.tijiaos = true
					if (res.code == 1) setTimeout(() => { uni.switchTab({ url: '/pages/order/order' }) }, 1500)
				}).catch(err => { console.error('修改订单失败:', err); uni.showToast({ title: '修改失败，请重试', duration: 1500, icon: 'none' }); this.tijiaos = true })
			}
		}
	}
</script>

<style scoped>
	.nav-bar { position: fixed; top: 0; left: 0; right: 0; z-index: 99; background: #FFF; }
	.nav-bar-inner { height: 44px; display: flex; align-items: center; justify-content: space-between; padding: 0 24rpx; }
	.nav-back { width: 80rpx; display: flex; align-items: center; justify-content: center; }
	.nav-title { font-weight: bold; font-size: 36rpx; }
	.nav-placeholder { width: 80rpx; }
	.form-scroll { height: calc(100vh - 44px - env(safe-area-inset-top)); }
	.buttons { margin: 24rpx auto 0; width: 690rpx; height: 88rpx; background: linear-gradient(180deg, #4897FF 0%, #4878FF 100%); border-radius: 46rpx; font-weight: bold; font-size: 30rpx; color: #FFFFFF; line-height: 88rpx; text-align: center; }
	.buttonx { margin: 38rpx auto 0; width: 690rpx; height: 88rpx; background: linear-gradient(180deg, #4897FF 0%, #4878FF 100%); border-radius: 46rpx; font-weight: bold; font-size: 30rpx; color: #FFFFFF; line-height: 88rpx; text-align: center; }
	.address-main { margin: 0 24rpx; flex: 1; min-width: 0; position: relative; }
	.address-actions { display: flex; align-items: center; flex-shrink: 0; white-space: nowrap; }
	.address-divider { background-color: #DDDDDD; width: 2rpx; height: 36rpx; margin-right: 14rpx; flex-shrink: 0; }
	.address-action { font-size: 26rpx; color: #4878FF; margin-right: 14rpx; flex-shrink: 0; }
	.address-action--stack { display: flex; flex-direction: column; align-items: center; justify-content: center; line-height: 26rpx; font-size: 22rpx; margin-right: 14rpx; }
	.reject-tip { margin: 20rpx 20rpx 0; padding: 24rpx; background: #FFF8E6; border-radius: 16rpx; border-left: 6rpx solid #F29100; display: flex; flex-direction: column; gap: 12rpx; }
	.reject-tip-title { font-size: 28rpx; color: #333; font-weight: bold; }
	.reject-tip-reason { font-size: 26rpx; color: #666; }
	.form-section-disabled { pointer-events: none; opacity: 0.6; }
</style>

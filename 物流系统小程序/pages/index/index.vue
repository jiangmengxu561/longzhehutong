<template>
	<view class="content">
		<view style="width: 750rpx;height: 272rpx;background: linear-gradient( 180deg, #D8EEFC 0%, #FFFFFF 100%);"></view>
		<view style="margin-top:-184rpx;"></view>
		<view style="font-weight: bold;font-size: 36rpx;line-height: 88rpx;text-align: center;margin-top:-88rpx;">首页</view>
		<swiper class="banners" circular :autoplay="true" :interval="3000" :duration="1000">
			<swiper-item v-for="(item,index) in banner" :key="index">
				<image :src="imgUrl+item" mode="aspectFill" style="width:100%;height:100%;border-radius:20rpx;">
				</image>
			</swiper-item>
		</swiper>
		<view class="flex"
			style="height: 76rpx;background: #FFF;align-items: center;padding:0 24rpx;margin:20rpx;align-items: center;border-radius:24rpx;">
			<view
				style="width: 84rpx;height: 48rpx;background: linear-gradient( 180deg, #4897FF 0%, #4878FF 100%);border-radius: 8rpx;font-weight: bold;font-size: 26rpx;color: #FFFFFF;line-height: 48rpx;text-align: center;margin-right: 24rpx;">
				公告</view>
			<xzw-notice style="width: 560rpx;height: 70rpx;" :showIcon="false" :showMore="false" :list="notice" />
		</view>
		<view style="margin:20rpx;padding:24rpx 20rpx;background: #FFFFFF;border-radius: 24rpx;">
			<view class="ai-order-entry" @click="openAiOrderRecognize">
				<view class="ai-order-entry__icon">AI</view>
				<view class="ai-order-entry__content">
					<view class="ai-order-entry__title">AI识别订单</view>
					<view class="ai-order-entry__desc">拍照或从相册选择订单图片，自动识别填写</view>
				</view>
				<image class="ai-order-entry__arrow" src="/static/images/icon_2jt.png" mode="aspectFit"></image>
			</view>
			<view class="flex"
				style="height: 136rpx;background: #F7F7F7;border-radius: 20rpx;align-items: center;padding:0 20rpx;margin-top:20rpx;">
				<view
					style="width: 48rpx;height: 48rpx;background: #F29100;border-radius: 12rpx;font-weight: bold;color: #FFFFFF;line-height: 48rpx;text-align: center;">
					装</view>

				<view class="address-main" @click="zhuangAddressClick">
					<view v-if="zhuangAddress.address" style="padding-right:32rpx;">
						<view class="yihang" style="font-weight: bold;font-size: 30rpx;">
							{{zhuangAddress.address||''}}
						</view>
						<view style="font-size: 26rpx;color: #888888;margin-top:8rpx;">{{zhuangAddress.user_name||''}}
							{{zhuangAddress.mobile||''}}
						</view>
						<view style="position: absolute;top:0;right:0;width:32rpx;height:32rpx;display: flex;align-items: center;justify-content: center;z-index: 10;" @click.stop="clearZhuangAddress">
							<image style="width:24rpx;height:24rpx;" src="/static/images/icon_3qx.png"></image>
						</view>
					</view>
					<view v-else>
						<view>点击选择地址</view>
					</view>
				</view>
				<view class="address-actions">
					<view class="address-divider"></view>
					<view class="address-action address-action--stack" @click.stop="openQuickInput(1)">
						<view>地 址</view>
						<view>粘 贴</view>
						<view>识 别</view>
					</view>
					<view class="address-action" @click.stop="zhuangxuan">地址簿</view>
				</view>
			</view>
			<!-- 地址转换按钮 -->
			<view class="flex" style="justify-content: center;margin: 16rpx 0;">
				<view @click="swapAddress" style="width: 64rpx;height: 64rpx;background: linear-gradient(180deg, #4897FF 0%, #4878FF 100%);border-radius: 50%;display: flex;align-items: center;justify-content: center;box-shadow: 0 4rpx 12rpx rgba(72, 120, 255, 0.3);">
					<text style="color: #FFFFFF;font-size: 32rpx;font-weight: bold;">⇅</text>
				</view>
			</view>
			<view class="flex"
				style="height: 136rpx;background: #F7F7F7;border-radius: 20rpx;align-items: center;padding:0 20rpx;margin-top:20rpx;">
				<view
					style="width: 48rpx;height: 48rpx;background: linear-gradient( 180deg, #4897FF 0%, #4878FF 100%);border-radius: 12rpx;font-weight: bold;color: #FFFFFF;line-height: 48rpx;text-align: center;">
					卸</view>
				<view class="address-main" @click="xieAddressClick">
					<view v-if="xieAddress.address" style="padding-right:32rpx;">
						<view class="yihang" style="font-weight: bold;font-size: 30rpx;">
							{{xieAddress.address||''}}
						</view>
						<view style="font-size: 26rpx;color: #888888;margin-top:8rpx;">{{xieAddress.user_name||''}}
							{{xieAddress.mobile||''}}
						</view>
						<view style="position: absolute;top:0;right:0;width:32rpx;height:32rpx;display: flex;align-items: center;justify-content: center;z-index: 10;" @click.stop="clearXieAddress">
							<image style="width:24rpx;height:24rpx;" src="/static/images/icon_3qx.png"></image>
						</view>
					</view>
					<view v-else>
						<view>点击选择地址</view>
					</view>
				</view>
				<view class="address-actions">
					<view class="address-divider"></view>
					<view class="address-action address-action--stack" @click.stop="openQuickInput(2)">
						<view>地 址</view>
						<view>粘 贴</view>
						<view>识 别</view>
					</view>
					<view class="address-action" @click.stop="xiexuan">地址簿</view>
				</view>
			</view>
		</view>
		<view style="background: #FFFFFF;border-radius: 24rpx;padding:0 24rpx;margin:20rpx;">
			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
				<view>找车类型</view>
				<view class="car-type-options">
					<view class="car-type-option" @click="carType=0">
						<image class="car-type-radio" :src="carType==0?'/static/images/icon_2xuan.png':'/static/images/icon_2xuan1.png'"></image>
						<view class="car-type-label">专车</view>
					</view>
					<view class="car-type-option" @click="carType=1">
						<image class="car-type-radio" :src="carType==1?'/static/images/icon_2xuan.png':'/static/images/icon_2xuan1.png'"></image>
						<view class="car-type-label">配车</view>
					</view>
					<view class="car-type-option" @click="carType=2">
						<image class="car-type-radio" :src="carType==2?'/static/images/icon_2xuan.png':'/static/images/icon_2xuan1.png'"></image>
						<view class="car-type-label">小票快运</view>
					</view>
					<view class="car-type-option" @click="carType=3">
						<image class="car-type-radio" :src="carType==3?'/static/images/icon_2xuan.png':'/static/images/icon_2xuan1.png'"></image>
						<view class="car-type-label">快递</view>
					</view>
				</view>
			</view>
			<!-- 快递：选择快递公司 -->
			<view v-if="carType==3" @click="toggleExpressCompanyPanel"
				class="flexJusp"
				style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
				<view>快递公司</view>
				<view class="flex" style="align-items: center;">
					<view v-if="expressCompanySummary">{{expressCompanySummary}}</view>
					<view v-else>请选择</view>
					<image style="width:20rpx;height:20rpx;margin-left:12rpx;" src="/static/images/icon_2jt.png"></image>
				</view>
			</view>
			<view v-if="carType==3 && showExpressCompanyPanel"
				style="background:#F7F7F7;border-radius: 0 0 20rpx 20rpx;padding:20rpx 24rpx 12rpx 24rpx;border-bottom:2rpx solid #F7F7F7;">
				<view v-if="expressCompanyList && expressCompanyList.length">
					<view v-for="(item, idx) in expressCompanyList" :key="idx"
						class="flexJusp"
						style="align-items:center;margin-bottom:16rpx;">
						<view class="flex" style="align-items:center;flex:1;min-width:0;">
							<image
								@click.stop="selectExpressCompany(item)"
								style="width:32rpx;height:32rpx;margin-right:12rpx;flex-shrink:0;"
								:src="isExpressCompanySelected(item)?'/static/images/icon_2xuan.png':'/static/images/icon_2xuan1.png'"
							></image>
							<image v-if="item && item.company_image"
								:src="getCompanyImageSrc(item.company_image)"
								class="express-company-logo"
								mode="aspectFill"
								@click.stop="selectExpressCompany(item)"
							></image>
							<view class="express-company-name" @click.stop="selectExpressCompany(item)">
								{{item.company_name || '未命名'}}
							</view>
						</view>
					</view>
				</view>
				<view v-else style="font-size:26rpx;color:#999;">暂无可选快递公司</view>
			</view>
			<view v-if="carType!=3" class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
				<view>发票</view>
				<view class="flex" style="align-items: center;">
					<image v-if="isinvoice==0" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan.png"></image>
					<image v-else @click="isinvoice=0" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan1.png">
					</image>
					<view @click="isinvoice=0" style="margin:0 32rpx 0 10rpx;">不开票</view>
					<image v-if="isinvoice==1" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan.png"></image>
					<image v-else @click="isinvoice=1" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan1.png">
					</image>
					<view @click="isinvoice=1" style="margin:0 0 0 10rpx;">开票</view>
				</view>
			</view>
			<view style="background: #F7F7F7;border-radius: 20rpx;padding:0 24rpx;" v-if="carType!=3 && isinvoice==1">
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>发票类型</view>
					<view class="flex" style="align-items: center;">
						<image v-if="type==1" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan.png"></image>
						<image v-else @click="selectTaxType(1)" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan1.png">
						</image>
						<view @click="selectTaxType(1)" style="margin:0 32rpx 0 10rpx;">专票</view>
						<image v-if="type==2" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan.png"></image>
						<image v-else @click="selectTaxType(2)" style="width:32rpx;height:32rpx;"
							src="/static/images/icon_2xuan1.png">
						</image>
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
					<view>公司抬头<text style="color: #E50014;">*</text> </view>
					<input v-model="company_letterhead" placeholder="请输入" style="text-align: right;" />
				</view>
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>公司税号<text style="color: #E50014;">*</text> </view>
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
			<view class="flexJusp" @click="openGoodsInfoFromIndex"
				style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
				<view>物品信息</view>
				<view class="flex yihang" style="align-items: center;justify-content:flex-end;flex:1;min-width:0;">
					<view class="yihang" style="color:#888;max-width:520rpx;text-align:right;">{{indexGoodsInfoSummary}}</view>
					<image style="width:20rpx;height:20rpx;margin-left:12rpx;flex-shrink:0;" src="/static/images/icon_2jt.png"></image>
				</view>
			</view>
			<!-- 包装方式：改为多选 + 每种数量 -->
			<view v-if="carType!=3" @click="togglePackagePanel"
				class="flexJusp"
				style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
				<view>包装方式</view>
				<view class="flex" style="align-items: center;">
					<view v-if="packageSummary">{{packageSummary}}</view>
					<view v-else>请选择</view>
					<image style="width:20rpx;height:20rpx;margin-left:12rpx;" src="/static/images/icon_2jt.png"></image>
				</view>
			</view>
			<!-- 展开面板：列出所有包装方式，可多选并设置数量 -->
			<view v-if="carType!=3 && showPackagePanel"
				style="background:#F7F7F7;border-radius: 0 0 20rpx 20rpx;padding:20rpx 24rpx 12rpx 24rpx;border-bottom:2rpx solid #F7F7F7;">
				<view v-if="baoList && baoList.length">
					<view v-for="item in baoList" :key="item.id"
						class="flexJusp"
						style="align-items:center;margin-bottom:16rpx;">
						<view class="flex" style="align-items:center;">
							<image
								v-if="isPackageSelected(item.id)"
								@click.stop="togglePackage(item)"
								style="width:32rpx;height:32rpx;margin-right:12rpx;"
								src="/static/images/icon_2xuan.png"
							></image>
							<image
								v-else
								@click.stop="togglePackage(item)"
								style="width:32rpx;height:32rpx;margin-right:12rpx;"
								src="/static/images/icon_2xuan1.png"
							></image>
							<view @click.stop="togglePackage(item)">{{item.name}}</view>
						</view>
						<!-- 数量选择器，仅在已勾选时显示 -->
						<view v-if="isPackageSelected(item.id)"
							class="flex"
							style="align-items: center;height: 42rpx;border-radius: 12rpx;border: 1rpx solid #CACACA;text-align: center;">
							<view @click.stop="changePackageQuantity(item.id, -1)" style="color: #CACACA;width:40rpx;">-</view>
							<input
								type="number"
								:value="getPackageQuantity(item.id)"
								@tap.stop
								@input="onPackageQuantityInput(item.id, $event.detail.value)"
								@blur="onPackageQuantityBlur(item.id, $event.detail.value)"
								style="width:100rpx;height:40rpx;font-size:24rpx;border-left:2rpx solid #CACACA;border-right:2rpx solid #CACACA;text-align:center;box-sizing:border-box;"
							/>
							<view @click.stop="changePackageQuantity(item.id, 1)" style="width:40rpx;">+</view>
						</view>
					</view>
					<view style="font-size:22rpx;color:#999;margin-top:4rpx;">可选择多种包装方式，每种都可以设置数量</view>
				</view>
				<view v-else style="font-size:26rpx;color:#999;">暂无可选包装方式</view>
			</view>
			<view v-if="carType!=3" class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
				<view>付款方式</view>
				<view class="flex" style="align-items: center;">
					<image v-if="pay_type==0" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan.png"></image>
					<image v-else @click="pay_type=0" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan1.png">
					</image>
					<view @click="pay_type=0" style="margin:0 32rpx 0 10rpx;">现付</view>
					<image v-if="pay_type==1" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan.png"></image>
					<image v-else @click="pay_type=1" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan1.png">
					</image>
					<view @click="pay_type=1" style="margin:0 32rpx 0 10rpx;">到付</view>
					<image v-if="pay_type==2" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan.png"></image>
					<image v-else @click="pay_type=2" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan1.png">
					</image>
					<view @click="pay_type=2" style="margin:0 0 0 10rpx;">月结</view>
				</view>
			</view>
			<!-- 到付时选择：寄货方付钱 / 收货方付钱 -->
			<view v-if="carType!=3 && pay_type==1" class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;background:#F7F7F7;padding-left:24rpx;">
				<view>到付付款方</view>
				<view class="flex" style="align-items: center;margin-right:24rpx;">
					<image v-if="pay_party==0" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan.png"></image>
					<image v-else @click="pay_party=0" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan1.png">
					</image>
					<view @click="pay_party=0" style="margin:0 32rpx 0 10rpx;">寄货方付钱</view>
					<image v-if="pay_party==1" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan.png"></image>
					<image v-else @click="pay_party=1" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan1.png">
					</image>
					<view @click="pay_party=1" style="margin:0 0 0 10rpx;">收货方付钱</view>
				</view>
			</view>
			<view v-if="carType!=3" class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
				<view>代收货款</view>
				<view class="flex" style="align-items: center;">
					<image v-if="delivery==0" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan.png"></image>
					<image v-else @click="delivery=0" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan1.png">
					</image>
					<view @click="delivery=0" style="margin:0 32rpx 0 10rpx;">否</view>
					<image v-if="delivery==1" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan.png"></image>
					<image v-else @click="delivery=1" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan1.png">
					</image>
					<view @click="delivery=1" style="margin:0 0 0 10rpx;">是</view>
				</view>
			</view>
			<view style="background: #F7F7F7;border-radius: 20rpx;padding:0 24rpx;" v-if="carType!=3 && delivery==1">
				<view style="padding:32rpx 0;border-bottom:2rpx solid #F7F7F7;">
					<view class="flexJusp" style="align-items: center;">
						<view>代收金额</view>
						<input v-model="behalf_price" placeholder="请输入" style="text-align: right;" />
					</view>
					<view style="font-size: 24rpx;color: #F29100;margin-top:8rpx;">按代收款总金额的0.3%,最低收费10元，最高收费100元</view>
				</view>

				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>银行网点</view>
					<input v-model="bank_branch" placeholder="请输入" style="text-align: right;" />
				</view>
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>开户账号</view>
					<input v-model="open_number" placeholder="请输入" style="text-align: right;" />
				</view>
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>银行类型</view>
					<input v-model="bank_type" placeholder="请输入" style="text-align: right;" />
				</view>
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>开户名</view>
					<input v-model="open_name" placeholder="请输入" style="text-align: right;" />
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
			<!-- 仅内部员工可见：技术服务费、信息费(不可退)、订单截图 -->
			<view v-if="isInternalStaff && carType==1" style="background: #F7F7F7;border-radius: 20rpx;padding:0 24rpx;margin-top:0;">
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>技术服务费</view>
					<input v-model="information" placeholder="请输入" style="text-align: right;" />
				</view>
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>信息费(不可退)</view>
					<input v-model="deposit" placeholder="请输入" style="text-align: right;" />
				</view>
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:156rpx;">
					<view>订单截图</view>
					<image style="width:120rpx;height:120rpx;" @click="upInformationImage"
						:src="information_image?imgUrl+information_image:'/static/images/icon_35sc.png'"></image>
				</view>
			</view>
			<picker v-if="carType!=3" @change="bindPickerChangeCar" :value="carIndex" :range="carList" range-key="name">
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>选择车型</view>
					<view class="flex" style="align-items: center;">
						<view v-if="carList[carIndex]">{{carList[carIndex].name}}</view>
						<view v-else>请选择</view>
						<image style="width:20rpx;height:20rpx;margin-left:12rpx;" src="/static/images/icon_2jt.png"></image>
					</view>
				</view>
			</picker>
			<view v-if="carType!=3 && carList[carIndex]">
				<image style="width: 662rpx;height: 300rpx;border-radius: 20rpx;" mode="aspectFit"
					:src="imgUrl+carList[carIndex].car_image" @click="previewCarImage"></image>
				<view class="flex" style="justify-content: center;margin-top:20rpx;" v-if="carList[carIndex]">
					厢长：{{carList[carIndex].car_long}}米 * 载重：{{carList[carIndex].car_wide}}吨
					载方：{{carList[carIndex].car_high}}方
				</view>
			</view>

			<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;" @click="fahuo">
				<view>发货时间</view>
				<view class="flex" style="align-items: center;">
					<view v-if="riqi">{{riqi||''}} {{zuizao||''}}-{{zuiwan||''}}</view>
					<view v-else>请选择</view>
					<image style="width:20rpx;height:20rpx;margin-left:12rpx;" src="/static/images/icon_2jt.png"></image>
				</view>
			</view>
			<view v-if="carType!=3" class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
				<view>其他运输要求</view>
				<view class="flex" style="align-items: center;">
					<image v-if="isrequirements==0" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan.png"></image>
					<image v-else @click="isrequirements=0" style="width:32rpx;height:32rpx;"
						src="/static/images/icon_2xuan1.png">
					</image>
					<view @click="isrequirements=0" style="margin:0 32rpx 0 10rpx;">否</view>
					<image v-if="isrequirements==1" style="width:32rpx;height:32rpx;" src="/static/images/icon_2xuan.png"></image>
					<image v-else @click="isrequirements=1" style="width:32rpx;height:32rpx;"
						src="/static/images/icon_2xuan1.png">
					</image>
					<view @click="isrequirements=1" style="margin:0 0 0 10rpx;">是</view>
				</view>
			</view>
			<view style="background: #F7F7F7;border-radius: 20rpx;padding:0 24rpx;" v-if="carType!=3 && isrequirements==1">
				<!-- 提货要求：下拉多选，参考其他费用 -->
				<view @click="toggleDeliveryPanel" class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>提货要求</view>
					<view class="flex" style="align-items: center;">
						<view v-if="deliverySummary">{{deliverySummary}}</view>
						<view v-else>请选择</view>
						<image style="width:20rpx;height:20rpx;margin-left:12rpx;" src="/static/images/icon_2jt.png"></image>
					</view>
				</view>
				<view v-if="showDeliveryPanel" style="background:#F7F7F7;border-radius:0 0 20rpx 20rpx;padding:20rpx 24rpx 12rpx 24rpx;border-bottom:2rpx solid #F7F7F7;">
					<view v-if="deliveryRequirementsList && deliveryRequirementsList.length">
						<view v-for="item in deliveryRequirementsList" :key="item.id" class="flexJusp" style="align-items:center;margin-bottom:16rpx;">
							<view class="flex" style="align-items:center;">
								<image
									v-if="selectedDeliveryRequirements.includes(item.id)"
									@click.stop="toggleDeliveryRequirement(item)"
									style="width:32rpx;height:32rpx;margin-right:12rpx;"
									src="/static/images/icon_2xuan.png"
								></image>
								<image
									v-else
									@click.stop="toggleDeliveryRequirement(item)"
									style="width:32rpx;height:32rpx;margin-right:12rpx;"
									src="/static/images/icon_2xuan1.png"
								></image>
								<view @click.stop="toggleDeliveryRequirement(item)">{{item.name}}</view>
							</view>
						</view>
					</view>
					<view v-else style="font-size:26rpx;color:#999;">暂无提货要求</view>
				</view>

				<!-- 专线装货要求：下拉多选，参考其他费用 -->
				<view @click="toggleLoadingPanel" class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>专线装货要求</view>
					<view class="flex" style="align-items: center;">
						<view v-if="loadingSummary">{{loadingSummary}}</view>
						<view v-else>请选择</view>
						<image style="width:20rpx;height:20rpx;margin-left:12rpx;" src="/static/images/icon_2jt.png"></image>
					</view>
				</view>
				<view v-if="showLoadingPanel" style="background:#F7F7F7;border-radius:0 0 20rpx 20rpx;padding:20rpx 24rpx 12rpx 24rpx;border-bottom:2rpx solid #F7F7F7;">
					<view v-if="loadingRequirementsList && loadingRequirementsList.length">
						<view v-for="item in loadingRequirementsList" :key="item.id" class="flexJusp" style="align-items:center;margin-bottom:16rpx;">
							<view class="flex" style="align-items:center;">
								<image
									v-if="selectedLoadingRequirements.includes(item.id)"
									@click.stop="toggleLoadingRequirement(item)"
									style="width:32rpx;height:32rpx;margin-right:12rpx;"
									src="/static/images/icon_2xuan.png"
								></image>
								<image
									v-else
									@click.stop="toggleLoadingRequirement(item)"
									style="width:32rpx;height:32rpx;margin-right:12rpx;"
									src="/static/images/icon_2xuan1.png"
								></image>
								<view @click.stop="toggleLoadingRequirement(item)">{{item.name}}</view>
							</view>
						</view>
					</view>
					<view v-else style="font-size:26rpx;color:#999;">暂无专线装货要求</view>
				</view>

				<picker @change="bindPickerChangesonghuo" :value="songIndex" :range="songList" range-key="name">
					<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
						<view>送货方式</view>
						<view class="flex" style="align-items: center;">
							<view v-if="songList[songIndex]">{{songList[songIndex].name}}</view>
							<view v-else>请选择</view>
							<view v-if="songIndex >= 0" @click.stop="clearSonghuo" style="margin-left:16rpx;font-size:24rpx;color:#999;">取消选择</view>
							<image style="width:20rpx;height:20rpx;margin-left:12rpx;" src="/static/images/icon_2jt.png"></image>
						</view>
					</view>
				</picker>
				<view v-if="songIndex >= 0 && songList[songIndex]" class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>价格展示</view>
					<view>￥{{songList[songIndex].delivery_type_price}}</view>
				</view>
				<view v-if="isPartTimeStaff && songIndex >= 0 && songList[songIndex]" class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>平台抽佣</view>
					<view>￥{{formatCommissionPrice(songList[songIndex].delivery_type_price)}}</view>
				</view>
				<view v-if="isPartTimeStaff && songIndex >= 0 && songList[songIndex]" class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>含抽佣成本</view>
					<view>￥{{formatCommissionTotal(songList[songIndex].delivery_type_price)}}</view>
				</view>
				
				<picker mode="multiSelector" @change="bindPickerChangehui" :value="huiIndex" :range="huiList" @columnchange="columnchanges" range-key="name">
					<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
						<view>回单</view>
						<view class="flex" style="align-items: center;">
							<view v-if="huiList[0] && huiList[0].length > 0 && huiList[1] && huiList[1].length > 0 && huiList[0][huiIndex[0]] && huiList[1][huiIndex[1]]">
								{{huiList[0][huiIndex[0]].name}}-{{huiList[1][huiIndex[1]].name}}
							</view>
							<view v-else>请选择</view>
							<view v-if="huiIndex[0] >= 0 && huiIndex[1] >= 0" @click.stop="clearHui" style="margin-left:16rpx;font-size:24rpx;color:#999;">取消选择</view>
							<image style="width:20rpx;height:20rpx;margin-left:12rpx;" src="/static/images/icon_2jt.png"></image>
						</view>
					</view>
				</picker>
				<!-- 显示回单价格 -->
				<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;" v-if="huiList[1] && huiList[1][huiIndex[1]] && huiList[1][huiIndex[1]].price !== undefined">
					<view>价格展示</view>
					<view v-if="huiList[1][huiIndex[1]].price == '0' || huiList[1][huiIndex[1]].price == 0">免费</view>
					<view v-else style="color: #F29100;">￥{{huiList[1][huiIndex[1]].price}}</view>
				</view>
				
				<!-- <picker mode="multiSelector" @change="bindPickerChangechai" :value="chaiIndex" :range="chaiList" range-key="name">
					<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
						<view>拆包服务</view>
						<view class="flex" style="align-items: center;">
							<view v-if="chaiList[0][chaiIndex[0]]">{{chaiList[0][chaiIndex[0]].name}}</view>
							<view v-else>请选择</view>
							<image style="width:20rpx;height:20rpx;margin-left:12rpx;" src="/static/images/icon_2jt.png"></image>
						</view>
					</view>
				</picker> -->
				
				<picker @change="bindPickerChangefuwu" :value="fuwuIndex" :range="fuwuList">
					<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
						<view>服务方式</view>
						<view class="flex" style="align-items: center;">
							<view v-if="fuwuList[fuwuIndex]">{{fuwuList[fuwuIndex]}}</view>
							<view v-else>请选择</view>
							<image style="width:20rpx;height:20rpx;margin-left:12rpx;" src="/static/images/icon_2jt.png"></image>
						</view>
					</view>
				</picker>
				<picker mode="multiSelector" @change="bindPickerChangechai" :value="chaiIndex" :range="chaiList" range-key="name">
					<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
						<view>拆包服务</view>
						<view class="flex" style="align-items: center;">
							<view v-if="chaiList[0][chaiIndex[0]]">{{chaiList[0][chaiIndex[0]].name}}</view>
							<view v-else>请选择</view>
							<view v-if="chaiIndex[0] >= 0 && chaiIndex[1] >= 0" @click.stop="clearChai" style="margin-left:16rpx;font-size:24rpx;color:#999;">取消选择</view>
							<image style="width:20rpx;height:20rpx;margin-left:12rpx;" src="/static/images/icon_2jt.png"></image>
						</view>
					</view>
				</picker>
				<view v-if="chaiIndex[0] >= 0 && chaiIndex[1] >= 0 && chaiList[0][chaiIndex[0]] && chaiList[1][chaiIndex[1]]" class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>价格展示</view>
					<view>￥{{parseFloat(chaiList[0][chaiIndex[0]].unpack_type_price)*chaiList[1][chaiIndex[1]].name}}</view>
				</view>
				<picker @change="bindPickerChangekong" :value="kongIndex" :range="kongType">
					<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
						<view>控货要求</view>
						<view class="flex" style="align-items: center;">
							<view v-if="kongType[kongIndex]">{{kongType[kongIndex]}}</view>
							<view v-else>请选择</view>
							<view v-if="kongIndex >= 0" @click.stop="clearKong" style="margin-left:16rpx;font-size:24rpx;color:#999;">取消选择</view>
							<image style="width:20rpx;height:20rpx;margin-left:12rpx;" src="/static/images/icon_2jt.png"></image>
						</view>
					</view>
				</picker>
				<!-- <view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>价格展示</view>
					<view>￥10</view>
				</view> -->
				<!-- 其他费用：改为多选 -->
				<view @click="toggleOtherPanel" class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
					<view>其他费用</view>
					<view class="flex" style="align-items: center;">
						<view v-if="otherSummary">{{otherSummary}}</view>
						<view v-else>请选择</view>
						<image style="width:20rpx;height:20rpx;margin-left:12rpx;" src="/static/images/icon_2jt.png"></image>
					</view>
				</view>
				<view v-if="showOtherPanel" style="background:#F7F7F7;border-radius:0 0 20rpx 20rpx;padding:20rpx 24rpx 12rpx 24rpx;border-bottom:2rpx solid #F7F7F7;">
					<view v-if="qitaList && qitaList.length">
						<view v-for="item in qitaList" :key="item.id" class="flexJusp" style="align-items:center;margin-bottom:16rpx;">
							<view class="flex" style="align-items:center;">
								<image
									v-if="isOtherSelected(item.id)"
									@click.stop="toggleOther(item)"
									style="width:32rpx;height:32rpx;margin-right:12rpx;"
									src="/static/images/icon_2xuan.png"
								></image>
								<image
									v-else
									@click.stop="toggleOther(item)"
									style="width:32rpx;height:32rpx;margin-right:12rpx;"
									src="/static/images/icon_2xuan1.png"
								></image>
								<view @click.stop="toggleOther(item)">{{item.name}}</view>
							</view>
							<view style="color:#F29100;">{{formatOtherPrice(item)}}</view>
						</view>
					</view>
					<view v-else style="font-size:26rpx;color:#999;">暂无其他费用</view>
				</view>
				<picker @change="bindPickerChangexin" :value="xinIndex" :range="xinType">
					<view class="flexJusp" style="align-items: center;border-bottom:2rpx solid #F7F7F7;height:104rpx;">
						<view>短信通知</view>
						<view class="flex" style="align-items: center;">
							<view v-if="xinType[xinIndex]">{{xinType[xinIndex]}}</view>
							<view v-else>请选择</view>
							<view v-if="xinIndex >= 0" @click.stop="clearXin" style="margin-left:16rpx;font-size:24rpx;color:#999;">取消选择</view>
							<image style="width:20rpx;height:20rpx;margin-left:12rpx;" src="/static/images/icon_2jt.png"></image>
						</view>
					</view>
				</picker>
				<view style="padding:24rpx 0;border-bottom:2rpx solid #F7F7F7;">
					<view style="margin-bottom:16rpx;">备注</view>
					<textarea :value="this['break']" @input="this['break'] = $event.detail.value" placeholder="请输入备注" 
						style="width:100%;min-height:120rpx;background:#F7F7F7;border-radius:12rpx;padding:16rpx;font-size:28rpx;box-sizing:border-box;"></textarea>
				</view>
			</view>
		</view>
		<view class="flex" style="font-size: 26rpx;align-items: center;justify-content: center;color:#C0C0C0;">
			<image @click="check=!check" v-if="check" style="width:28rpx;height:28rpx;margin:0 10rpx 0 0;"
				src="/static/images/icon_1xuan1.png"></image>
			<image @click="check=!check" v-else style="width:28rpx;height:28rpx;margin:0 10rpx 0 0;"
				src="/static/images/icon_1xuan.png"></image>
			<view><text @click="check=!check">已阅读并同意</text><text @click="agreement('xiadan')"
					style="color:#4878FF;">《龙喆互通货物运输信息服务协议》</text>
			</view>
		</view>
		<view class="buttons" @click="tijiao">立即提交</view>
		<view style="height:60rpx;"></view>
		<!-- 快速输入地址弹窗 -->
		<uni-popup ref="quickInputPopup" type="center">
			<view style="width: 680rpx;background: #FFFFFF;border-radius: 24rpx;padding: 40rpx;">
				<view class="flexJusp" style="align-items: center;margin-bottom: 32rpx;">
					<view style="font-weight: bold;font-size: 32rpx;">地址粘贴识别</view>
					<view @click="closeQuickInput" style="width:40rpx;height:40rpx;display: flex;align-items: center;justify-content: center;">
						<image style="width:24rpx;height:24rpx;" src="/static/images/icon_3qx.png"></image>
					</view>
				</view>
				<view style="margin-bottom: 24rpx;">
					<view style="font-size: 28rpx;color: #333;margin-bottom: 16rpx;">请输入地址信息</view>
					<textarea 
						v-model="quickInputText" 
					
						style="width:100%;min-height:200rpx;background:#F7F7F7;border-radius:12rpx;padding:20rpx;font-size:28rpx;box-sizing:border-box;"
						maxlength="200"
					></textarea>
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
					<view style="width:80rpx;text-align: center;" @click="guanbi">
						<image style="width:24rpx;height:24rpx;" src="/static/images/icon_3qx.png"></image>
					</view>
				</view>
				<view style="margin:0 32rpx;" class="flexJusp">
					<view style="width:33.33%;">日期</view>
					<view style="width:33.33%;text-align: center;">最早装货</view>
					<view style="width:33.33%;text-align: right;">最晚装货</view>
				</view>
				<view style="margin:0 32rpx;padding:32rpx 0;" class="flexJusp">
					<view style="width:33.33%;">
						<l-picker indicatorStyle="font-size:28rpx;" itemFontSize="14px" itemHeight="36px" groupHeight="120px"
							@pick="onChange">
							<l-picker-item :options="cityOptions" v-model="riqi"></l-picker-item>
						</l-picker>

						<!-- <picker mode="date" @change="bindPickerChangeriqi">
							<view>{{riqi||'请选择'}}</view>
						</picker> -->
					</view>
					<view style="width:33.33%;text-align: center;">
						<l-picker indicatorStyle="font-size:28rpx;" itemFontSize="14px" itemHeight="36px" groupHeight="120px"
							@pick="onChange1">
							<l-picker-item :options="hourList" v-model="zuizao"></l-picker-item>
						</l-picker>

						<!-- <picker mode="time" @change="bindPickerChangeshijian">
							<view>{{zuizao||'请选择'}}</view>
						</picker> -->
					</view>
					<view style="width:33.33%;text-align: right;">
						<l-picker indicatorStyle="font-size:28rpx;" itemFontSize="14px" itemHeight="36px" groupHeight="120px"
							@pick="onChange2">
							<l-picker-item :options="hourLists" v-model="zuiwan"></l-picker-item>
						</l-picker>

						<!-- <picker mode="time" @change="bindPickerChangeshijians">
							<view>{{zuiwan||'请选择'}}</view>
						</picker> -->
					</view>
				</view>
				<view style="font-weight: bold;font-size: 30rpx;color: #4878FF;text-align: center;">{{riqis||''}}
					{{zuizaos||''}}-{{zuiwans||''}} 装货
				</view>
				<view class="buttonx" @click="queding">确定</view>
				<view style="height:120rpx;"></view>
			</view>
		</uni-popup>
	</view>
</template>

<script>
	const INDEX_GOODS_INFO_STORAGE_KEY = 'index_goodsInfo_v1'
	export default {
		data() {
			return {
				imgUrl: this.$utils.imgUrl,
				// 分享参数：从 Opinion/getuserinfo 接口里的 invitation 获取
				invitation: '',
				riqi: '',
				zuizao: '',
				zuiwan: '',
				riqis: '',
				zuizaos: '',
				zuiwans: '',
				cityOptions: [{
					value: '测试',
					label: '测试',
				}],
				indexs: null,
				banner: [],
				notice: [],
				carType: 1,
				isinvoice: 0,
				delivery: 0,
				num: 1,
				check: false,
				zhuangAddress: {},
				xieAddress: {},
				carList: [],
				carIndex: 0,
				typeList: [],
				typeIndex: 0,
				baoList: [],
				baoIndex: 0, // 兼容旧逻辑，不再用于选择，仅保留
				// 多包装方式选择
				showPackagePanel: false,
				packageSelections: [], // [{id,name,quantity}]
				_pendingPackageList: null, // 临时存储待恢复的包装方式数据
				_pendingAiCarId: null,
				_pendingAiGoodsTypeId: null,
				_aiRecognizeSession: 0,
				_lastAiOrderImageUrl: '',
				songList: [],
				songIndex: -1,
			huiList: [],
			huiLists:[],
			huiIndex: [-1,-1],
				chaiList: [[],[]],
				chaiIndex:[-1,-1] ,
				qitaList: [],
				qitaIndex: 0,
				showOtherPanel: false,
				otherSelections: [],
				showDeliveryPanel: false,
				// 快递公司选择
				showExpressCompanyPanel: false,
				expressCompanyList: [],
				selectedExpressCompany: null,
				showLoadingPanel: false,
				songhuoList: ['不上楼', '送货上楼', '送货进仓'],
				songhuoIndex: 0,
				fuwuList: ['派送', '自提'],
				fuwuIndex: 0,
				// 其他运输要求 - 提货 / 专线装货要求
				deliveryRequirementsList: [],
				loadingRequirementsList: [],
				selectedDeliveryRequirements: [],
				selectedLoadingRequirements: [],
				kaiType: ['个人', '公司'],
				kaiIndex: 0,
				kongType: ['到站点等通知放货', '到收货地等通知放货'],
				kongIndex: -1,
				xinType: ['收件方', '发件方'],
				xinIndex: -1,
				'break': '',
				pay_type: 1,
				pay_party: 0, // 到付时：0=寄货方付钱，1=收货方付钱
				type: 1,
				isrequirements: 0,
				find_car_type: '',
				quantity: 1,
				weight: '',
				long: '',
				wide: '',
				hige: '',
				direction: '',
				sizeList: [{long: '', wide: '', hige: ''}],
				isinvoice: '',
				packaging_id: '',
				behalf_price: '',
				bank_branch: '',
				open_number: '',
				bank_type: '',
				open_name: '',
				open_type: '',
				charge: '',
				tax_point: '',
				company_letterhead: '',
				company_tax_id: '',
				company_email: '',
				company_mobile: '',
				bank_deposits: '',
				earliest_time: '',
				latest_time: '',
				goods_name: '',
				goods_image: '',
				hourList: [],
				hourLists: [],
				hourList1: [],
				hourLists1: [],
				hourList2: [],
				tijiaos:true,
				// 快速输入相关
				quickInputText: '',
				quickInputType: 1, // 1-装货地址，2-卸货地址
				aiRecognizing: false,
				// 用户类型：1=普通用户，2=兼职员工，3=正式员工（用于内部员工权限）
				userType: 1,
				// 仅内部员工可见并填写
				information: '',
				deposit: '',
				information_image: '',
				orderScreenshotWarned: false
			}
		},
		onLoad(option) {
			// uni.setStorageSync('token', '671de9f3-6202-4a6d-99bd-b96fb55c5ff9')
			console.log('首页onLoad - option参数:', option)
			// 刷新首页后清空物品信息页草稿，避免残留上次填写数据
			this.clearGoodsInfoDraft()
			
			// 优先处理从朋友圈分享进入时的invitation参数（最重要，避免点击"前往小程序"后丢失）
			if(option && option.invitation){
				const invitation = decodeURIComponent(option.invitation)
				console.log('首页onLoad - 从朋友圈分享获取invitation参数:', invitation)
				uni.setStorageSync('qrcode_invitation', invitation)
			}
			// 处理从其他分享方式进入时的q参数
			else if(option && option.q){
				try {
					const qcode = decodeURIComponent(option.q).split('=')[1]
					console.log('首页onLoad - 从q参数获取invitation:', qcode)
					if(qcode){
						uni.setStorageSync('qrcode_invitation', qcode)
					}
				} catch(e) {
					console.error('解析q参数失败:', e)
				}
			}
			// 处理从二维码扫码进入时的scene参数
			else if(option && option.scene){
				const invitation = this.parseInvitationFromScene(option.scene)
				console.log('首页onLoad - 从scene参数解析invitation:', invitation)
				if(invitation){
					uni.setStorageSync('qrcode_invitation', invitation)
				}
			}
			// 如果都没有，尝试从存储中获取（可能是之前保存的）
			else {
				const storedInvitation = uni.getStorageSync('qrcode_invitation')
				if(storedInvitation){
					console.log('首页onLoad - 从存储获取invitation参数:', storedInvitation)
				} else {
					// 如果存储中也没有，尝试从App.vue的存储中获取scene并解析
					const qrcodeScene = uni.getStorageSync('qrcode_scene')
					if(qrcodeScene){
						console.log('首页onLoad - 从存储获取scene参数:', qrcodeScene)
						const invitation = this.parseInvitationFromScene(qrcodeScene)
						if(invitation){
							uni.setStorageSync('qrcode_invitation', invitation)
							console.log('首页onLoad - 解析并保存invitation参数:', invitation)
						}
					}
				}
			}
			
			this.loads()
			this.jinri()
		},
		onShow() {
			this.tijiaos = true
			this.loadx()
			this.syncIndexGoodsInfoFromStorage()
			uni.setNavigationBarTitle({ title: '首页' })
			// 地址编辑/新增后返回首页：用缓存兜底刷新当前显示的地址（避免仍显示旧地址）
			try {
				const edited = uni.getStorageSync('lastEditedAddress')
				const ts = uni.getStorageSync('lastEditedAddress_ts')
				// 10分钟内的更新认为有效
				const valid = edited && ts && (Date.now() - Number(ts) < 10 * 60 * 1000)
				if (valid) {
					// type: 1装货 2卸货（可能是字符串）
					const t = String(edited.type || '')
					// 优先：如果首页当前选中的id与编辑的id一致，则直接替换为最新
					if (this.zhuangAddress && this.zhuangAddress.id != null && edited.id != null && String(this.zhuangAddress.id) === String(edited.id)) {
						this.zhuangAddress = edited
					}
					if (this.xieAddress && this.xieAddress.id != null && edited.id != null && String(this.xieAddress.id) === String(edited.id)) {
						this.xieAddress = edited
					}
					// 兜底：如果按type能判断且当前该地址为空，也更新（避免首次编辑后仍空）
					if (t === '1' && (!this.zhuangAddress || !this.zhuangAddress.address)) {
						this.zhuangAddress = edited
					}
					if (t === '2' && (!this.xieAddress || !this.xieAddress.address)) {
						this.xieAddress = edited
					}
					// 用完即清，避免后续覆盖用户新选择
					uni.removeStorageSync('lastEditedAddress')
					uni.removeStorageSync('lastEditedAddress_ts')
				}
			} catch (e) {}
			// 延迟拉用户信息，避免与首屏接口争抢 iOS 连接池
			if (this._getUserInvitationTimer) {
				clearTimeout(this._getUserInvitationTimer)
			}
			this._getUserInvitationTimer = setTimeout(() => {
				this.getUserInvitation()
			}, 600)
			// 再次检查并保存invitation参数（防止从朋友圈进入时参数丢失）
			// 如果存储中没有invitation，尝试从页面参数获取（通过getCurrentPages）
			const currentPages = getCurrentPages()
			if(currentPages && currentPages.length > 0){
				const currentPage = currentPages[currentPages.length - 1]
				if(currentPage && currentPage.options && currentPage.options.invitation){
					const invitation = decodeURIComponent(currentPage.options.invitation)
					if(invitation && !uni.getStorageSync('qrcode_invitation')){
						console.log('onShow - 从页面参数获取并保存invitation:', invitation)
						uni.setStorageSync('qrcode_invitation', invitation)
					}
				}
			}
			if (uni.getStorageSync('tijiao') == 1) {
				this.zhuangAddress = {}
				this.xieAddress = {}
				this.quantity = 1
				this.weight = ''
				this.long = ''
				this.wide = ''
				this.hige = ''
				this.direction = ''
				this.sizeList = [{long: '', wide: '', hige: ''}]
				this.isinvoice = ''
				this.typeIndex = 0
				this.baoIndex = 0
				// 提交后返回首页时，付款方式默认到付
				this.pay_type = 1
				this.pay_party = 0
				this.delivery = 0
				this.carIndex = 0
				this.earliest_time = ''
				this.latest_time = ''
				this.isrequirements = 0
				this.songIndex = -1
				this.huiIndex = [-1, -1]
				this.fuwuIndex = 0
				this.chaiIndex = [-1, -1]
				this.kongIndex = -1
				this.qitaIndex = 0
				this.xinIndex = -1
				this.behalf_price = ''
				this.bank_branch = ''
				this.open_number = ''
				this.bank_type = ''
				this.open_name = ''
				this.kaiIndex = 0
				this.charge = ''
				this.type = ''
				this.tax_point = ''
				this.company_letterhead = ''
				this.company_tax_id = ''
				this.company_email = ''
				this.information = ''
				this.deposit = ''
				this.information_image = ''
				this.orderScreenshotWarned = false
				this.company_mobile = ''
				this.bank_deposits = ''
				this.goods_name = ''
				this.goods_image = ''
				uni.removeStorageSync(INDEX_GOODS_INFO_STORAGE_KEY)
				this.selectedDeliveryRequirements = []
				this.selectedLoadingRequirements = []
				this.showDeliveryPanel = false
				this.showLoadingPanel = false
				this['break'] = ''
				uni.setStorageSync('tijiao', 2)
				// 从 SubmitOrder 返回时恢复「其他运输要求」及发票信息
				try {
					const backup = uni.getStorageSync('index_other_requirements_backup')
					if (backup && typeof backup === 'object') {
						this.isrequirements = backup.isrequirements ?? 0
						this.selectedDeliveryRequirements = Array.isArray(backup.selectedDeliveryRequirements) ? backup.selectedDeliveryRequirements.slice() : []
						this.selectedLoadingRequirements = Array.isArray(backup.selectedLoadingRequirements) ? backup.selectedLoadingRequirements.slice() : []
						this.songIndex = typeof backup.songIndex === 'number' ? backup.songIndex : -1
						this.huiIndex = Array.isArray(backup.huiIndex) ? backup.huiIndex.slice() : [-1, -1]
						this.chaiIndex = Array.isArray(backup.chaiIndex) ? backup.chaiIndex.slice() : [-1, -1]
						this.fuwuIndex = typeof backup.fuwuIndex === 'number' ? backup.fuwuIndex : 0
						this.kongIndex = typeof backup.kongIndex === 'number' ? backup.kongIndex : -1
						this.xinIndex = typeof backup.xinIndex === 'number' ? backup.xinIndex : -1
						this.otherSelections = Array.isArray(backup.otherSelections) ? backup.otherSelections.map(o => ({ id: o.id, name: o.name || '' })) : []
						this['break'] = backup['break'] || ''
						// 恢复发票信息，避免再次提交时公司抬头和税号不传
						if (backup.hasOwnProperty('isinvoice')) this.isinvoice = backup.isinvoice
						if (backup.company_letterhead != null) this.company_letterhead = backup.company_letterhead || ''
						if (backup.company_tax_id != null) this.company_tax_id = backup.company_tax_id || ''
						if (backup.company_email != null) this.company_email = backup.company_email || ''
						if (backup.company_mobile != null) this.company_mobile = backup.company_mobile || ''
						if (backup.charge != null) this.charge = backup.charge || ''
						if (backup.type != null) this.type = backup.type || ''
						if (backup.tax_point != null) this.tax_point = backup.tax_point || ''
						if (backup.bank_deposits != null) this.bank_deposits = backup.bank_deposits || ''
						if (typeof backup.kaiIndex === 'number') this.kaiIndex = backup.kaiIndex
						if (backup.open_name != null) this.open_name = backup.open_name || ''
						if (backup.open_type != null) this.open_type = backup.open_type || ''
						if (backup.bank_branch != null) this.bank_branch = backup.bank_branch || ''
						if (backup.open_number != null) this.open_number = backup.open_number || ''
						if (backup.bank_type != null) this.bank_type = backup.bank_type || ''
						if (backup.indexs != null) this.indexs = backup.indexs
						this.syncTaxIndexs()
						// 恢复本单其他表单字段
						if (typeof backup.baoIndex === 'number') this.baoIndex = backup.baoIndex
						if (typeof backup.pay_type === 'number') this.pay_type = backup.pay_type
						if (typeof backup.pay_party === 'number') this.pay_party = backup.pay_party
						if (typeof backup.delivery === 'number') this.delivery = backup.delivery
						if (typeof backup.carIndex === 'number') this.carIndex = backup.carIndex
						if (backup.earliest_time != null) this.earliest_time = backup.earliest_time || ''
						if (backup.latest_time != null) this.latest_time = backup.latest_time || ''
						if (typeof backup.qitaIndex === 'number') this.qitaIndex = backup.qitaIndex
						if (backup.behalf_price != null) this.behalf_price = backup.behalf_price || ''
						if (backup.information != null) this.information = backup.information || ''
						if (backup.deposit != null) this.deposit = backup.deposit || ''
						if (backup.information_image != null) this.information_image = backup.information_image || ''
						if (Array.isArray(backup.packageSelections) && backup.packageSelections.length > 0) {
							this.packageSelections = backup.packageSelections.map(p => ({
								id: p.id,
								name: p.name || '',
								quantity: p.quantity || 1
							}))
						}
						// 恢复核心运单字段（不恢复货物信息页中的数量/尺寸/重量/体积）
						if (backup.zhuangAddress && typeof backup.zhuangAddress === 'object' && Object.keys(backup.zhuangAddress).length > 0) {
							this.zhuangAddress = { ...backup.zhuangAddress }
						}
						if (backup.xieAddress && typeof backup.xieAddress === 'object' && Object.keys(backup.xieAddress).length > 0) {
							this.xieAddress = { ...backup.xieAddress }
						}
						uni.removeStorageSync('index_other_requirements_backup')
					}
				} catch (e) {}
			}
			// 再来一单缓存数据处理：只在有有效数据时读取一次，并立即清理缓存，避免长期覆盖用户后来选择的地址
			const zaiOrder = uni.getStorageSync('zaiOrder')
			if (zaiOrder && zaiOrder.id) {
				var details = zaiOrder
				console.log(details,'----当前的再来一单的数据---2--')
				if(details.find_car_type=='专车'){
					this.carType = 0
				}else if(details.find_car_type=='配车'){
					this.carType = 1
				}else if(details.find_car_type=='快递'){
					this.carType = 3
				}else{
					this.carType = 2
				}
				this.quantity = details.quantity
				this.weight = details.weight
				this.long = details.long
				this.wide = details.wide
				this.hige = details.hige
				// 恢复多尺寸数据
				if(details.sizeList && Array.isArray(details.sizeList) && details.sizeList.length > 0){
					this.sizeList = details.sizeList.map(item => ({
						long: item.long || '',
						wide: item.wide || '',
						hige: item.hige || ''
					}))
				} else {
					// 如果没有sizeList，使用单个尺寸初始化sizeList
					this.sizeList = [{
						long: details.long || '',
						wide: details.wide || '',
						hige: details.hige || ''
					}]
				}
				this.direction = details.direction
				this.isinvoice = details.isinvoice
				// 再来一单时：现付/到付/月结；到付时恢复付款方
				if(details.pay_type == '到付' || details.pay_type == '到付-寄货方' || details.pay_type == '到付-收货方'){
					this.pay_type = 1
					this.pay_party = (details.pay_type == '到付-收货方') ? 1 : 0
				} else if(details.pay_type == '现付' || details.pay_type == '寄付'){
					this.pay_type = 0
					this.pay_party = 0
				} else {
					this.pay_type = 2
					this.pay_party = 0
				}
				if(details.delivery=='代收货款'){
					this.delivery = 1
				}else{
					this.delivery = 0
				}
				this.earliest_time = details.earliest_time
				this.latest_time = details.latest_time
				this.isrequirements = details.isrequirements
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
				this.orderScreenshotWarned = false
				this.goods_name = details.goods_name || ''
				this.goods_image = details.goods_image
				this.zhuangAddress = details.loading_address
				this.xieAddress = details.unload_address
				for(let i=0;i<this.typeList.length;i++){
					if(details.goods_type_id==this.typeList[i].name){
						this.typeIndex = i
					}
				}
				// 恢复多包装方式数据 - 需要在baoList加载后执行，所以先保存数据
				// 注意：这里先保存原始数据，等baoList加载完成后再匹配
				if(details.packaging_list && Array.isArray(details.packaging_list) && details.packaging_list.length > 0){
					// 先保存原始数据，等baoList加载后再匹配
					this._pendingPackageList = details.packaging_list
				} else if(details.packaging_id){
					// 如果没有packaging_list，使用单个包装方式
					this._pendingPackageList = [{
						name: details.packaging_id,
						quantity: 1
					}]
				}
				for(let i=0;i<this.carList.length;i++){
					if(details.car_type_id==this.carList[i].name){
						this.carIndex = i
					}
				}
				// 送货方式、控货要求、短信通知：不再从再来一单恢复，保持默认「请选择」
				// this.songIndex、this.kongIndex、this.xinIndex 保持 -1，不恢复
				for(let i=0;i<this.fuwuList.length;i++){
					if(details.service==this.fuwuList[i]){
						this.fuwuIndex = i
					}
				}
				// 回单、拆包、其他费用：不再从再来一单恢复，保持默认「请选择」
				// this.huiIndex、this.chaiIndex、this.otherSelections 保持未选，不恢复
				for(let i=0;i<this.kaiType.length;i++){
					if(details.open_type==this.kaiType[i]){
						this.kaiIndex = i
					}
				}
				console.log(this.details, '-----再来一单-----')
				// 关键：用完再来一单数据后立刻清除，防止后续每次返回首页又把旧地址覆盖当前选择
				uni.removeStorageSync('zaiOrder')
			}
		},
		onUnload() {
			if (this._loadxSecondaryTimer) {
				clearTimeout(this._loadxSecondaryTimer)
			}
			if (this._getUserInvitationTimer) {
				clearTimeout(this._getUserInvitationTimer)
			}
			uni.setStorageSync('zaiOrder','')
		},
		// 微信分享：聊天页
		onShareAppMessage() {
			const invitation = this.invitation || uni.getStorageSync('qrcode_invitation') || ''
			return {
				title: '龙喆互通物流，下单发货更便捷',
				path: `/pages/index/index?invitation=${encodeURIComponent(invitation)}`,
				// 使用固定的分享图片
				imageUrl: '/static/images/share_timeline.png'
			}
		},
		// 微信分享：朋友圈
		onShareTimeline() {
			const invitation = this.invitation || uni.getStorageSync('qrcode_invitation') || ''
			return {
				title: '龙喆互通物流，下单发货更便捷',
				query: `invitation=${encodeURIComponent(invitation)}`,
				// 使用固定的分享图片
				imageUrl: '/static/images/share_timeline.png'
			}
		},
		computed: {
			// 税点列表：专票=6%/9%，普票=1%/2%
			array() {
				return this.$utils.taxArray(this.type)
			},
			// 是否为内部员工（兼职或正式），仅内部员工可看到并填写技术服务费、信息费(不可退)
			isInternalStaff() {
				const ut = Number(this.userType)
				return ut === 2 || ut === 3
			},
			isPartTimeStaff() {
				return Number(this.userType) === 2
			},
			canAddMultipleSizes() {
				// 检查当前选择的货物类型的switch字段是否为1
				if (this.typeList && this.typeList[this.typeIndex] && this.typeList[this.typeIndex].switch == 1) {
					return true
				}
				return false
			},
			indexGoodsInfoSummary() {
				const nameText = this.goods_name ? this.goods_name : '未填名称'
				const typeText = (this.typeList && this.typeList[this.typeIndex] && this.typeList[this.typeIndex].name) ? this.typeList[this.typeIndex].name : '未选类型'
				const qtyText = this.quantity ? `${this.quantity}件` : '未填数量'
				const weightText = this.weight ? `${this.weight}吨` : '未填重量'
				const directionText = this.direction ? `${this.direction}方` : '未填方位'
				let sizeText = '未填尺寸'
				if (this.canAddMultipleSizes) {
					const filled = (this.sizeList || []).filter(it => it && it.long && it.wide && it.hige)
					if (filled.length) sizeText = `多尺寸${filled.length}组`
				} else {
					if (this.long && this.wide && this.hige) sizeText = `${this.long}×${this.wide}×${this.hige}m`
				}
				return `${nameText} / ${typeText} / ${qtyText} / ${sizeText} / ${weightText} / ${directionText}`
			},
			// 包装方式选中汇总显示
			packageSummary() {
				if (!this.packageSelections || this.packageSelections.length === 0) return ''
				return this.packageSelections
					.filter(item => item.quantity && item.quantity > 0)
					.map(item => `${item.name}x${item.quantity}`)
					.join('，')
			},
			// 其他费用选中汇总
			otherSummary() {
				if (!this.otherSelections || this.otherSelections.length === 0) return ''
				return this.otherSelections
					.map(item => item.name)
					.join('，')
			},
			// 提货要求选中汇总
			deliverySummary() {
				if (!this.deliveryRequirementsList || !this.selectedDeliveryRequirements || this.selectedDeliveryRequirements.length === 0) return ''
				return this.deliveryRequirementsList
					.filter(item => this.selectedDeliveryRequirements.includes(item.id))
					.map(item => item.name)
					.join('，')
			},
			// 专线装货要求选中汇总
			loadingSummary() {
				if (!this.loadingRequirementsList || !this.selectedLoadingRequirements || this.selectedLoadingRequirements.length === 0) return ''
				return this.loadingRequirementsList
					.filter(item => this.selectedLoadingRequirements.includes(item.id))
					.map(item => item.name)
					.join('，')
			},
			// 快递公司选中汇总
			expressCompanySummary() {
				if (!this.selectedExpressCompany) return ''
				return this.selectedExpressCompany.company_name || ''
			}
		},
		methods: {
			openGoodsInfoFromIndex() {
				this.saveIndexGoodsInfoToStorage()
				uni.navigateTo({
					url: '/pages/index/goodsInfo/goodsInfo'
				})
			},
			saveIndexGoodsInfoToStorage() {
				uni.setStorageSync(INDEX_GOODS_INFO_STORAGE_KEY, {
					goods_name: this.goods_name || '',
					goods_image: this.goods_image || '',
					typeList: this.typeList || [],
					typeIndex: Number(this.typeIndex || 0),
					quantity: Number(this.quantity || 1),
					long: this.long || '',
					wide: this.wide || '',
					hige: this.hige || '',
					sizeList: this.sizeList || [{ long: '', wide: '', hige: '' }],
					weight: this.weight || '',
					direction: this.direction || ''
				})
			},
			syncIndexGoodsInfoFromStorage() {
				try {
					const saved = uni.getStorageSync(INDEX_GOODS_INFO_STORAGE_KEY)
					if (!saved || typeof saved !== 'object') return
					if (saved.goods_name != null) this.goods_name = saved.goods_name || ''
					if (saved.goods_image != null) this.goods_image = saved.goods_image || ''
					if (Array.isArray(saved.typeList)) this.typeList = saved.typeList
					if (typeof saved.typeIndex === 'number') this.typeIndex = saved.typeIndex
					if (saved.quantity != null) this.quantity = Number(saved.quantity || 1)
					if (saved.long != null) this.long = saved.long || ''
					if (saved.wide != null) this.wide = saved.wide || ''
					if (saved.hige != null) this.hige = saved.hige || ''
					if (Array.isArray(saved.sizeList) && saved.sizeList.length) this.sizeList = saved.sizeList
					if (saved.weight != null) this.weight = saved.weight || ''
					if (saved.direction != null) this.direction = saved.direction || ''
				} catch (e) {}
			},
			clearGoodsInfoDraft() {
				this.goods_name = ''
				this.goods_image = ''
				this.quantity = 1
				this.long = ''
				this.wide = ''
				this.hige = ''
				this.weight = ''
				this.direction = ''
				this.typeIndex = 0
				this.sizeList = [{ long: '', wide: '', hige: '' }]
				uni.removeStorageSync(INDEX_GOODS_INFO_STORAGE_KEY)
			},
			// 获取用户邀请参数，用于分享携带
			getUserInvitation() {
				this.$httpapi('Opinion/getuserinfo', 'GET', {}).then(res => {
					if (res && res.data) {
						if (res.data.invitation) this.invitation = res.data.invitation
						// 用于内部员工权限：1=普通用户，2=兼职员工，3=正式员工
						const mt = res.data.membertype ?? res.data.user_type
						this.userType = mt !== undefined && mt !== null ? Number(mt) : 1
					}
				}).catch(err => {
					console.log('获取用户邀请参数失败', err)
				})
			},
			formatCommissionPrice(price) {
				const base = Number(price)
				if (isNaN(base)) return '0.00'
				return (base * 0.05).toFixed(2)
			},
			formatCommissionTotal(price) {
				const base = Number(price)
				if (isNaN(base)) return '0.00'
				return (base * 1.05).toFixed(2)
			},
			// 通用数字过滤（只允许数字和一个小数点，可限制小数位）
			sanitizeNumber(value, decimalPlaces = null) {
				value = String(value || '')
				// 只保留数字和小数点
				value = value.replace(/[^\d.]/g, '')
				const parts = value.split('.')
				if (parts.length > 1) {
					// 只保留第一个小数点
					value = parts[0] + '.' + parts.slice(1).join('')
				}
				if (decimalPlaces === 0) {
					// 只要整数部分
					return parts[0] || ''
				}
				if (decimalPlaces != null && parts.length > 1) {
					return parts[0] + '.' + (parts[1] || '').slice(0, decimalPlaces)
				}
				return value
			},
			// 单字段数字输入处理
			onNumberInput(field, value, decimalPlaces = null) {
				const cleaned = this.sanitizeNumber(value, decimalPlaces)
				this[field] = cleaned
			},
			// 多尺寸列表中的数字输入处理（长/宽/高单位已为米，支持小数）
			onSizeInput(index, key, value) {
				const cleaned = this.sanitizeNumber(value, 4)
				this.$set(this.sizeList[index], key, cleaned)
				// 输入时实时重新计算总方位
				if (this.canAddMultipleSizes) {
					this.calculateTotalDirection()
				} else {
					this.chang()
				}
			},
			upImage() {
				var that = this
				uni.chooseImage({
					count: 1,
					sizeType: ['original', 'compressed'],
					sourceType: ['album', 'camera'],
					success: function(res) {
						that.$upShop('common/upload', res.tempFilePaths[0]).then(rex => {
							let arr = JSON.parse(rex.data)
							that.goods_image = arr.data.url
						}).catch((e) => {});
					}
				})
			},
			// 仅内部员工：上传订单截图（information_image）
			upInformationImage() {
				const that = this
				uni.chooseImage({
					count: 1,
					sizeType: ['original', 'compressed'],
					sourceType: ['album', 'camera'],
					success(res) {
						that.$upShop('common/upload', res.tempFilePaths[0]).then(rex => {
							const arr = JSON.parse(rex.data)
							that.information_image = arr.data.url
							that.orderScreenshotWarned = false
						}).catch(() => {})
					}
				})
			},
			jinri() {
				this.cityOptions = []
				this.hourList = []
				this.hourLists = []
				this.hourList1 = []
				this.hourLists1 = []
				this.hourList2 = []

				const datesList = [];
				const today = new Date(); // 当前日期
				const todayTimestamp = today.setHours(0, 0, 0, 0); // 今天0点的时间戳（用于对比）

				// 循环生成最近60天的日期（覆盖两个月）
				for (let i = 0; i < 60; i++) {
					const currentDate = new Date(todayTimestamp);
					currentDate.setDate(currentDate.getDate() + i); // 往前推i天

					const dateTimestamp = currentDate.setHours(0, 0, 0, 0);
					const diffDays = Math.floor((dateTimestamp - todayTimestamp) / (1000 * 60 * 60 * 24)); // 与今天的天数差

					let dateDesc = '';
					let weekDesc = '';
					let xiandate = '';

					// 处理最近两天的描述
					if (diffDays === 0) {
						dateDesc = '今天';

						// 格式化日期：YYYY-MM-DD
						const year = currentDate.getFullYear();
						const month = String(currentDate.getMonth() + 1).padStart(2, '0');
						const day = String(currentDate.getDate()).padStart(2, '0');
						xiandate = `${year}-${month}-${day}`
					} else if (diffDays === 1) {
						dateDesc = '明天';

						// 格式化日期：YYYY-MM-DD
						const year = currentDate.getFullYear();
						const month = String(currentDate.getMonth() + 1).padStart(2, '0');
						const day = String(currentDate.getDate()).padStart(2, '0');
						xiandate = `${year}-${month}-${day}`
					} else {
						// 格式化日期：YYYY-MM-DD
						const year = currentDate.getFullYear();
						const month = String(currentDate.getMonth() + 1).padStart(2, '0');
						const day = String(currentDate.getDate()).padStart(2, '0');
						dateDesc = `${month}-${day}`;
						xiandate = `${year}-${month}-${day}`
					}


					// 转换星期为中文
					const weekNum = currentDate.getDay(); // 0(日)~6(六)
					const weekMap = ['日', '一', '二', '三', '四', '五', '六'];
					weekDesc = `星期${weekMap[weekNum]}`;

					datesList.push({
						date: dateDesc,
						week: weekDesc,
						timestamp: dateTimestamp, // 可选：保留时间戳用于排序/对比
						xiandate: xiandate
					});

					this.cityOptions.push({
						label: dateDesc + ' ' + weekDesc,
						value: xiandate
					})
				}
				const now = new Date();
				const currentHour = now.getHours();
				const currentMinute = now.getMinutes();
				// 最早时间永远比当前时间多半个小时：
				// 先在分钟上 +30，再向上取最近的半小时刻度
				// 如 10:00 -> 10:30，10:10 -> 10:40，10:31 -> 11:00
				let totalMinutes = currentHour * 60 + currentMinute;
				const remainder = totalMinutes % 30;
				if (remainder !== 0) {
					totalMinutes += (30 - remainder);
				}
				// 生成当天从该“当前时间+30分钟后”的半小时刻度开始，到 24:00 的所有时间点（步长30分钟）
				for (; totalMinutes <= 24 * 60; totalMinutes += 30) {
					let hour = Math.floor(totalMinutes / 60);
					let minute = totalMinutes % 60;
					if (hour > 24) {
						hour = 24;
						minute = 0;
					}
					const formattedHour = hour.toString().padStart(2, '0');
					const formattedMinute = minute.toString().padStart(2, '0');
					const timeStr = `${formattedHour}:${formattedMinute}`;
					const item = {
						label: timeStr,
						value: timeStr
					};
					this.hourList.push(item);
					this.hourLists.push(item);
					this.hourList1.push(item);
					this.hourLists1.push(item);
				}
				for (let hour = 1; hour <= 24; hour++) {
					for (let minute of [0, 30]) {
						// 补零格式化小时和分钟（如9→09，30→30）
						const formattedHour = hour.toString().padStart(2, '0');
						const formattedMinute = minute.toString().padStart(2, '0');
						const timeStr = `${formattedHour}:${formattedMinute}`;

						this.hourList2.push({
							label: timeStr,
							value: timeStr
						});
					}

					// 补零格式化（如9→09:00）
					//  const formattedHour = `${hour.toString().padStart(2, '0')}:00`;
					//  this.hourList2.push({
					// 	label: formattedHour,
					// 	value:formattedHour
					// });
				}
				this.riqis = this.cityOptions[0].label
				this.zuizaos = this.hourList[0].label

				this.riqi = this.cityOptions[0].value
				this.zuizao = this.hourList[0].value
				// 设置默认最晚时间比最早时间晚半个小时
				this.setLatestTimeAfterEarliest()
				console.log(this.hourList, '-剩余的小时---')
				console.log(datesList, '-----后60天')
			},
			onChange(e) {
				console.log(e, '----')
				this.riqi = this.cityOptions[e.index].value
				this.riqis = this.cityOptions[e.index].label
				if (e.index > 0) {
					// 其他日期：最早时间从凌晨01:00开始
					this.hourList = this.hourList2
					this.hourLists = this.hourList2
					// 最早时间默认为01:00
					if (this.hourList && this.hourList.length > 0) {
						this.zuizao = this.hourList[0].value
						this.zuizaos = this.hourList[0].label
						// 使用$nextTick确保hourLists更新后再设置最晚时间
						this.$nextTick(() => {
							this.setLatestTimeAfterEarliest()
						})
					}
				} else {
					// 今天：最早时间从下一个小时开始
					this.hourList = this.hourLists1
					this.hourLists = this.hourLists1
					// 今天：最早时间默认为当前时间的下一个小时
					if (this.hourList && this.hourList.length > 0) {
						this.zuizao = this.hourList[0].value
						this.zuizaos = this.hourList[0].label
						// 使用$nextTick确保hourLists更新后再设置最晚时间
						this.$nextTick(() => {
							this.setLatestTimeAfterEarliest()
						})
					}
				}
			},
			// 设置最晚时间比最早时间晚半个小时
			setLatestTimeAfterEarliest() {
				if (!this.zuizao || !this.hourLists || this.hourLists.length === 0) {
					return
				}
				// 解析最早时间
				const [earliestHour, earliestMinute] = this.zuizao.split(':').map(Number)
				// 计算最晚时间（晚半个小时）
				let latestMinute = earliestMinute + 30
				let latestHour = earliestHour
				if (latestMinute >= 60) {
					latestMinute = 0
					latestHour += 1
				}
				if (latestHour > 24) {
					latestHour = 24
					latestMinute = 0
				}
				// 格式化为字符串
				const formattedHour = latestHour.toString().padStart(2, '0')
				const formattedMinute = latestMinute.toString().padStart(2, '0')
				const latestTimeStr = `${formattedHour}:${formattedMinute}`
				console.log('计算最晚时间:', latestTimeStr, 'hourLists长度:', this.hourLists.length)
				// 在hourLists中查找匹配的时间
				const latestTimeIndex = this.hourLists.findIndex(item => item.value === latestTimeStr)
				if (latestTimeIndex >= 0) {
					this.zuiwan = this.hourLists[latestTimeIndex].value
					this.zuiwans = this.hourLists[latestTimeIndex].label
					console.log('找到匹配的最晚时间:', this.zuiwan, '索引:', latestTimeIndex)
				} else {
					// 如果找不到精确匹配，找最接近的（大于等于目标时间的第一个）
					const found = this.hourLists.find(item => {
						const [h, m] = item.value.split(':').map(Number)
						return h > latestHour || (h === latestHour && m >= latestMinute)
					})
					if (found) {
						this.zuiwan = found.value
						this.zuiwans = found.label
						console.log('找到最接近的最晚时间:', this.zuiwan)
					} else {
						// 如果找不到，使用列表的最后一个
						this.zuiwan = this.hourLists[this.hourLists.length - 1].value
						this.zuiwans = this.hourLists[this.hourLists.length - 1].label
						console.log('使用最后一个最晚时间:', this.zuiwan)
					}
				}
				// 强制更新组件，确保l-picker正确显示
				this.$forceUpdate()
			},
			onChange1(e) {
				console.log(e, '----')
				this.zuizao = this.hourList[e.index].value
				this.zuizaos = this.hourList[e.index].label
				// 最早时间改变后，最晚时间自动更新为比最早时间晚半个小时
				this.setLatestTimeAfterEarliest()
			},
			onChange2(e) {
				console.log(e, '----')
				this.zuiwan = this.hourLists[e.index].value
				this.zuiwans = this.hourLists[e.index].label
			},
			bindPickerChangekai(e){
				this.kaiIndex = e.detail.value
			},
			// zhongliang() {
			// 	if (this.weight) {
			// 		if (this.weight > 5000) {
			// 			uni.showToast({
			// 				title: '重量超出上限',
			// 				duration: 1500,
			// 				icon: 'none'
			// 			});
			// 			this.weight = 5000
			// 		}
			// 	}
			// },
			chang() {
				console.log(this.long, this.wide, this.hige, '-----122311111')
				if (this.long) {
					if (this.long > 10000) {
						uni.showToast({
							title: '长度超出上限',
							duration: 1500,
							icon: 'none'
						});
						this.long = 10000
					}
					if (this.wide) {
						if (this.wide > 10000) {
							uni.showToast({
								title: '宽度超出上限',
								duration: 1500,
								icon: 'none'
							});
							this.wide = 10000
						}
						if (this.hige) {
							if (this.hige > 10000) {
								uni.showToast({
									title: '高度超出上限',
									duration: 1500,
									icon: 'none'
								});
								this.hige = 10000
							}
							var nums = this.long * this.wide * this.hige
							this.direction = nums.toFixed(4)
						}
					}
				}
			},
			kuan() {
				if (this.long && this.wide && this.hige) {
					this.direction = (this.long * this.wide * this.hige).toFixed(4)
				}
			},
			gao() {
				if (this.long && this.wide && this.hige) {
					this.direction = (this.long * this.wide * this.hige).toFixed(4)
				}
			},
			addSize() {
				// 添加新的尺寸项
				this.sizeList.push({long: '', wide: '', hige: ''})
			},
			removeSize(index) {
				// 删除指定索引的尺寸项，至少保留一个
				if (this.sizeList.length > 1) { 
					this.sizeList.splice(index, 1)
					// 重新计算总方位
					this.calculateTotalDirection()
				}
			},
			changMultiple(index) {
				// 处理多个尺寸中的单个尺寸变化
				const item = this.sizeList[index]
				if (item.long) {
					if (item.long > 10000) {
						uni.showToast({
							title: '长度超出上限',
							duration: 1500,
							icon: 'none'
						});
						item.long = 10000
					}
				}
				if (item.wide) {
					if (item.wide > 10000) {
						uni.showToast({
							title: '宽度超出上限',
							duration: 1500,
							icon: 'none'
						});
						item.wide = 10000
					}
				}
				if (item.hige) {
					if (item.hige > 10000) {
						uni.showToast({
							title: '高度超出上限',
							duration: 1500,
							icon: 'none'
						});
						item.hige = 10000
					}
				}
				// 重新计算总方位
				this.calculateTotalDirection()
			}, 
			calculateTotalDirection() {
				// 计算所有尺寸的总方位（立方米），所填长宽高已为米，直接相乘即立方米
				let totalDirection = 0
				this.sizeList.forEach(item => {
					if (item.long && item.wide && item.hige) {
						totalDirection += parseFloat(item.long) * parseFloat(item.wide) * parseFloat(item.hige)
					}
				})
				this.direction = totalDirection.toFixed(4)
			},
			jianjian() {
				if (this.quantity < 2) {
					this.quantity = 1
				} else {
					this.quantity--
				}
			},
			onQuantityInput(e) {
				const val = e.detail.value
				if (val === '' || val === null || val === undefined) return
				const num = parseInt(val, 10)
				if (!isNaN(num) && num >= 1) {
					this.quantity = num
				}
			},
			onQuantityBlur(e) {
				const val = e.detail.value
				const num = parseInt(val, 10)
				if (isNaN(num) || num < 1) {
					this.quantity = 1
				} else {
					this.quantity = num
				}
			},
			// 打开 / 关闭 包装方式下拉面板
			togglePackagePanel() {
				this.showPackagePanel = !this.showPackagePanel
			},
			// 判断某个包装方式是否已被选择
			isPackageSelected(id) {
				if (!this.packageSelections) return false
				return this.packageSelections.some(item => item.id === id)
			},
			// 获取某个包装方式当前数量
			getPackageQuantity(id) {
				if (!this.packageSelections) return 1
				const target = this.packageSelections.find(item => item.id === id)
				return target && target.quantity ? target.quantity : 1
			},
			// 计算所有包装方式的总数量
			calculateTotalPackageQuantity() {
				if (!this.packageSelections || this.packageSelections.length === 0) {
					this.quantity = 1
					return
				}
				const total = this.packageSelections.reduce((sum, item) => {
					return sum + (item.quantity || 1)
				}, 0)
				this.quantity = total > 0 ? total : 1
			},
			// 勾选 / 取消勾选 某个包装方式
			togglePackage(item) {
				if (!item || !item.id) return
				if (!this.packageSelections) {
					this.packageSelections = []
				}
				const index = this.packageSelections.findIndex(p => p.id === item.id)
				if (index > -1) {
					// 已选中则取消
					this.packageSelections.splice(index, 1)
				} else {
					// 新选中，默认数量 1
					this.packageSelections.push({
						id: item.id,
						name: item.name,
						quantity: 1
					})
				}
				// 更新总数量
				this.calculateTotalPackageQuantity()
			},
			// 修改某个包装方式的数量
			changePackageQuantity(id, delta) {
				if (!this.packageSelections || !this.packageSelections.length) return
				const target = this.packageSelections.find(item => item.id === id)
				if (!target) return
				let qty = (target.quantity || 1) + delta
				if (qty < 1) qty = 1
				// 使用 this.$set 保证响应式
				target.quantity = qty
				// 更新总数量
				this.calculateTotalPackageQuantity()
			},
			onPackageQuantityInput(id, value) {
				if (!this.packageSelections || !this.packageSelections.length) return
				const target = this.packageSelections.find(item => item.id === id)
				if (!target) return
				if (value === '' || value === null || value === undefined) return
				const num = parseInt(String(value), 10)
				if (!isNaN(num) && num >= 1) {
					target.quantity = num
					this.calculateTotalPackageQuantity()
				}
			},
			onPackageQuantityBlur(id, value) {
				if (!this.packageSelections || !this.packageSelections.length) return
				const target = this.packageSelections.find(item => item.id === id)
				if (!target) return
				const num = parseInt(String(value), 10)
				if (isNaN(num) || num < 1) {
					target.quantity = 1
				} else {
					target.quantity = num
				}
				this.calculateTotalPackageQuantity()
			},
			// 切换“提货要求”选项
			toggleDeliveryRequirement(item) {
				if (!item || !item.id) return
				const idx = this.selectedDeliveryRequirements.indexOf(item.id)
				if (idx > -1) {
					this.selectedDeliveryRequirements.splice(idx, 1)
				} else {
					this.selectedDeliveryRequirements.push(item.id)
				}
			},
			// 切换“专线装货要求”选项
			toggleLoadingRequirement(item) {
				if (!item || !item.id) return
				const idx = this.selectedLoadingRequirements.indexOf(item.id)
				if (idx > -1) {
					this.selectedLoadingRequirements.splice(idx, 1)
				} else {
					this.selectedLoadingRequirements.push(item.id)
				}
			},
			toggleDeliveryPanel() {
				this.showDeliveryPanel = !this.showDeliveryPanel
			},
			toggleLoadingPanel() {
				this.showLoadingPanel = !this.showLoadingPanel
			},
			// 其他费用多选
			toggleOtherPanel() {
				this.showOtherPanel = !this.showOtherPanel
			},
			isOtherSelected(id) {
				if (!this.otherSelections) return false
				return this.otherSelections.some(item => item.id === id)
			},
			toggleOther(item) {
				if (!item || !item.id) return
				if (!this.otherSelections) {
					this.otherSelections = []
				}
				const idx = this.otherSelections.findIndex(o => o.id === item.id)
				if (idx > -1) {
					this.otherSelections.splice(idx, 1)
				} else {
					this.otherSelections.push({
						id: item.id,
						name: item.name,
						price: item.price ?? item.other_type_price ?? item.other_price ?? item.amount ?? ''
					})
				}
			},
			formatOtherPrice(item) {
				const price = item && (item.price ?? item.other_type_price ?? item.other_price ?? item.amount)
				if (price === undefined || price === null || price === '') return '—'
				return Number(price) === 0 ? '免费' : `￥${price}`
			},
			goSendExpressPage() {
				uni.navigateTo({
					url: '/pages/express/sendExpress/sendExpress'
				})
			},
			saveIndexOtherRequirementsBackup() {
				uni.setStorageSync('index_other_requirements_backup', {
					isrequirements: this.isrequirements,
					selectedDeliveryRequirements: (this.selectedDeliveryRequirements || []).slice(),
					selectedLoadingRequirements: (this.selectedLoadingRequirements || []).slice(),
					songIndex: this.songIndex,
					huiIndex: Array.isArray(this.huiIndex) ? this.huiIndex.slice() : [-1, -1],
					chaiIndex: Array.isArray(this.chaiIndex) ? this.chaiIndex.slice() : [-1, -1],
					fuwuIndex: this.fuwuIndex,
					kongIndex: this.kongIndex,
					xinIndex: this.xinIndex,
					otherSelections: (this.otherSelections || []).map(o => ({ id: o.id, name: o.name })),
					'break': this['break'] || '',
					isinvoice: this.isinvoice,
					company_letterhead: this.company_letterhead || '',
					company_tax_id: this.company_tax_id || '',
					company_email: this.company_email || '',
					company_mobile: this.company_mobile || '',
					charge: this.charge || '',
					type: this.type || '',
					tax_point: this.tax_point || '',
					bank_deposits: this.bank_deposits || '',
					kaiIndex: this.kaiIndex,
					open_name: this.open_name || '',
					open_type: this.open_type || '',
					bank_branch: this.bank_branch || '',
					open_number: this.open_number || '',
					bank_type: this.bank_type || '',
					indexs: this.indexs,
					baoIndex: this.baoIndex,
					pay_type: this.pay_type,
					pay_party: this.pay_party,
					delivery: this.delivery,
					carIndex: this.carIndex,
					earliest_time: this.earliest_time || '',
					latest_time: this.latest_time || '',
					qitaIndex: this.qitaIndex,
					behalf_price: this.behalf_price || '',
					information: this.information || '',
					deposit: this.deposit || '',
					information_image: this.information_image || '',
					packageSelections: (this.packageSelections || []).map(p => ({ id: p.id, name: p.name || '', quantity: p.quantity || 1 })),
					zhuangAddress: this.zhuangAddress && typeof this.zhuangAddress === 'object' ? { ...this.zhuangAddress } : {},
					xieAddress: this.xieAddress && typeof this.xieAddress === 'object' ? { ...this.xieAddress } : {}
				})
			},
		tijiao() {
			// 验证是否已阅读并同意下单须知
			if (!this.check) {
				uni.showToast({
					title: '请阅读并同意下单须知',
					duration: 1500,
					icon: 'none'
				});
				return
			}
			
			this.earliest_time = this.riqi + ' ' + this.zuizao + ':00'
			this.latest_time = this.riqi + ' ' + this.zuiwan + ':00'

			var find_car_type = '专车'
				if (this.carType == 1) {
					find_car_type = '配车'
				} else if (this.carType == 2) {
					find_car_type = '小票快运'
				} else if (this.carType == 3) {
					find_car_type = '快递'
				}
				console.log(this.huiList[1],this.huiIndex[1],'----')
				if(this.tijiaos){
					this.tijiaos = false
					// 处理尺寸数据：如果是多尺寸模式，使用sizeList；否则使用单个尺寸
					let sizeData = {}
					// 安全地检查是否可以添加多个尺寸
					let canAddMultiple = false
					if (this.typeList && this.typeList[this.typeIndex] && this.typeList[this.typeIndex].switch == 1) {
						canAddMultiple = true
					}
					
					if (canAddMultiple && this.sizeList && this.sizeList.length > 0) {
						// 多尺寸模式：使用第一个尺寸作为主要尺寸，同时传递所有尺寸列表
						sizeData = {
							long: this.sizeList[0].long || this.long,
							wide: this.sizeList[0].wide || this.wide,
							hige: this.sizeList[0].hige || this.hige,
							sizeList: this.sizeList // 传递所有尺寸列表
						}
					} else {
						// 单尺寸模式：使用原有的单个尺寸
						sizeData = {
							long: this.long,
							wide: this.wide,
							hige: this.hige
						}
					}
					
					// 安全检查所有必需的数组元素
					if (!this.typeList || !this.typeList[this.typeIndex]) {
						uni.showToast({
							title: '请选择货物类型',
							duration: 1500,
							icon: 'none'
						});
						this.tijiaos = true
						return
					}
					// 校验：至少选择一种包装方式
					if (!this.packageSelections || this.packageSelections.length === 0) {
						uni.showToast({
							title: '请选择包装方式',
							duration: 1500,
							icon: 'none'
						});
						this.tijiaos = true
						return
					}
					if (!this.carList || !this.carList[this.carIndex]) {
						uni.showToast({
							title: '请选择车型',
							duration: 1500,
							icon: 'none'
						});
						this.tijiaos = true
						return
					}
					
					// 验证装货地址
					console.log('装货地址数据:', JSON.stringify(this.zhuangAddress))
					if (!this.zhuangAddress) {
						uni.showToast({
							title: '请选择装货地址',
							duration: 1500,
							icon: 'none'
						});
						this.tijiaos = true
						return
					}
					// 检查 id 字段（允许 id 为 0）
					const zhuangAddressId = this.zhuangAddress.id
					if (zhuangAddressId === undefined || zhuangAddressId === null || zhuangAddressId === '') {
						console.error('装货地址缺少有效的 id:', zhuangAddressId)
						uni.showToast({
							title: '装货地址数据异常，请重新选择',
							duration: 1500,
							icon: 'none'
						});
						this.tijiaos = true
						return
					}
					
					// 验证卸货地址
					console.log('卸货地址数据:', JSON.stringify(this.xieAddress))
					if (!this.xieAddress) {
						uni.showToast({
							title: '请选择卸货地址',
							duration: 1500,
							icon: 'none'
						});
						this.tijiaos = true
						return
					}
					// 检查 id 字段（允许 id 为 0）
					const xieAddressId = this.xieAddress.id
					if (xieAddressId === undefined || xieAddressId === null || xieAddressId === '') {
						console.error('卸货地址缺少有效的 id:', xieAddressId)
						uni.showToast({
							title: '卸货地址数据异常，请重新选择',
							duration: 1500,
							icon: 'none'
						});
						this.tijiaos = true
						return
					}
					
					// 构建请求参数，安全地访问数组元素
					let requestData = {
						loading: zhuangAddressId,
						unload: xieAddressId,
						find_car_type: find_car_type,
						quantity: this.quantity,
						goods_name: this.goods_name,
						weight: this.weight,
						long: sizeData.long,
						wide: sizeData.wide,
						hige: sizeData.hige,
						direction: parseFloat(this.direction || 0).toFixed(2),
						isinvoice: this.isinvoice === '' || this.isinvoice === null || this.isinvoice === undefined ? 0 : this.isinvoice,
						goods_type_id: this.typeList[this.typeIndex].id,
						packaging_id: this.baoList[this.baoIndex].id,
						pay_type: this.pay_type,
						pay_party: this.pay_type === 1 ? this.pay_party : 0,
						delivery: this.delivery,
						car_type_id: this.carList[this.carIndex].id,
						earliest_time: this.earliest_time,
						latest_time: this.latest_time,
						isrequirements: this.isrequirements,
						goods_image: this.goods_image
					}
					// 快递：提交选中的快递公司信息
					if (this.carType === 3 && this.selectedExpressCompany) {
						requestData.company_name = this.selectedExpressCompany.company_name || ''
						requestData.company_image = this.selectedExpressCompany.company_image || ''
					}
					
					// 添加多尺寸列表（如果有）
					if (sizeData.sizeList) {
						requestData.sizeList = sizeData.sizeList
					}
					
					// 设置 service 默认值为"派送"
					if (this.fuwuList && this.fuwuList[this.fuwuIndex]) {
						requestData.service = this.fuwuList[this.fuwuIndex]
					} else {
						requestData.service = '派送'
					}
					// 备注：每次提交都传给接口，兼容两种字段名
					const remarkVal = this['break'] || ''
					requestData.break = remarkVal
					requestData.remark = remarkVal
					
					// 安全地添加可选字段
					if (this.isrequirements == 1) {
						if (this.songList && this.songList[this.songIndex]) {
							requestData.delivery_type_id = this.songList[this.songIndex].id
						}
					// 回单数据：提交选中的子选项的id，以及有价格时传回单价格
					if (this.huiList && this.huiList[1] && this.huiList[1][this.huiIndex[1]] && this.huiList[1][this.huiIndex[1]].id) {
						requestData.receipt_type_id = this.huiList[1][this.huiIndex[1]].id
						const receiptPrice = this.huiList[1][this.huiIndex[1]].price
						if (receiptPrice !== undefined && receiptPrice !== null && receiptPrice !== '' && (receiptPrice !== '0' && receiptPrice !== 0)) {
							requestData.receipt_type_price = parseFloat(receiptPrice)
						}
					}
						if (this.chaiList && this.chaiList[0] && this.chaiList[0][this.chaiIndex[0]] && this.chaiList[1] && this.chaiList[1][this.chaiIndex[1]]) {
							requestData.unpack_id = this.chaiList[0][this.chaiIndex[0]].id
							requestData.unpack_num = this.chaiList[1][this.chaiIndex[1]].name
							// 拆包服务价格：单价 * 数量，有价格时传给后台
							const unpackUnitPrice = parseFloat(this.chaiList[0][this.chaiIndex[0]].unpack_type_price)
							const unpackNum = parseInt(this.chaiList[1][this.chaiIndex[1]].name, 10)
							if (!isNaN(unpackUnitPrice) && !isNaN(unpackNum)) {
								requestData.unpack_price = unpackUnitPrice * unpackNum
							}
						}
						// 控货要求：仅在选择时提交，不选则传空
						if (this.kongType && this.kongIndex >= 0 && this.kongType[this.kongIndex]) {
							requestData.control = this.kongType[this.kongIndex]
						}
						// 其他费用：仅在有勾选时提交，不选则传空
					// 多选其他费用，兼容老字段：取第一项为 other_id，并传递列表
					if (this.otherSelections && this.otherSelections.length > 0) {
						requestData.other_id = this.otherSelections[0].id
						requestData.other_list = this.otherSelections.map(o => o.id)
					}
						// 短信通知：仅在选择时提交，不选则传空
						if (this.xinIndex >= 0 && this.xinType && this.xinType[this.xinIndex] !== undefined) {
							requestData.text_message = parseInt(this.xinIndex) + 1
						}

						// 其他运输要求 - 提货 / 专线装货要求（按 id 逗号分隔提交）
						if (this.selectedDeliveryRequirements && this.selectedDeliveryRequirements.length > 0) {
							requestData.deliveryrequirements = this.selectedDeliveryRequirements.join(',')
						}
						if (this.selectedLoadingRequirements && this.selectedLoadingRequirements.length > 0) {
							requestData.loadingrequirements = this.selectedLoadingRequirements.join(',')
						}
					}
					
					// 添加代收货款相关字段
					if (this.delivery == 1) {
						requestData.behalf_price = this.behalf_price
						requestData.bank_branch = this.bank_branch
						requestData.open_number = this.open_number
						requestData.bank_type = this.bank_type
						requestData.open_name = this.open_name
						if (this.kaiType && this.kaiType[this.kaiIndex]) {
							requestData.open_type = this.kaiType[this.kaiIndex]
						}
					}
					
					// 添加发票相关字段
					if (this.isinvoice == 1) {
						requestData.charge = this.charge
						requestData.type = this.type
						if (this.array && this.array[this.indexs]) {
							requestData.tax_point = this.array[this.indexs]
						}
						requestData.company_letterhead = this.company_letterhead
						requestData.company_tax_id = this.company_tax_id
						requestData.company_email = this.company_email
						requestData.company_mobile = this.company_mobile
						requestData.bank_deposits = this.bank_deposits
					}

					// 仅内部员工提交：技术服务费、信息费(不可退)、订单截图（键始终带上，避免后端 isset(information) 旧逻辑漏存定金；金额用 trim 便于 floatval）
					if (this.isInternalStaff) {
						requestData.information = (this.information != null && this.information !== undefined) ? String(this.information).trim() : ''
						requestData.deposit = (this.deposit != null && this.deposit !== undefined) ? String(this.deposit).trim() : ''
						requestData.information_image = this.information_image || ''
					}
					if (this.isPartTimeStaff) {
						let commissionBase = 0
						if (this.songIndex >= 0 && this.songList && this.songList[this.songIndex] && this.songList[this.songIndex].delivery_type_price !== undefined) {
							commissionBase = Number(this.songList[this.songIndex].delivery_type_price)
						}
						requestData.platform_commission = isNaN(commissionBase) ? 0 : Number((commissionBase * 0.05).toFixed(2))
					}

					// 包装方式：兼容老字段 + 新增多包装字段
					if (this.packageSelections && this.packageSelections.length > 0) {
						// 老字段：仍然传第一种包装的 id，兼容原有后端
						requestData.packaging_id = this.packageSelections[0].id
						// 新字段：传递完整的包装方式及数量列表，供后端按需使用
						requestData.packaging_list = this.packageSelections.map(item => ({
							name: item.name,
							quantity: item.quantity || 1
						}))
					}
					
					uni.showLoading({ title: '提交中...', mask: true })
					this.$httpapi('Placeorder/order', 'POST', requestData).then(res => {
						if (res && res.code == 1 && res.data && res.data.orderid) {
							try {
								this.saveIndexOtherRequirementsBackup()
							} catch (e) {}
							if (this.carType == 1) {
								uni.navigateTo({
									url: '/pages/index/SubmitOrder/SubmitOrder?id=' + res.data.orderid + '&fromSubmit=1'
								})
							} else {
								uni.navigateTo({
									url: '/pages/index/SubmitOrder/orderDetail?id=' + res.data.orderid + '&fromSubmit=1'
								})
							}
						} else {
							uni.hideLoading()
							uni.showToast({
								title: (res && (res.msg ?? res.message)) || '提交失败，请重试',
								duration: 1500,
								icon: 'none'
							})
						}
						this.tijiaos = true
					}).catch(err => {
						uni.hideLoading()
						console.error('提交订单失败:', err)
						uni.showToast({
							title: '提交失败，请重试',
							duration: 1500,
							icon: 'none'
						})
						this.tijiaos = true
					})
				}
				
			},
			loadx() {
				// 首屏核心数据立即加载；其余选项延迟加载，避免 iOS 同域名并发占满导致 uploadFile pending
				this._loadxCorePromise = this.loadxCore()
				if (this._loadxSecondaryTimer) {
					clearTimeout(this._loadxSecondaryTimer)
				}
				this._loadxSecondaryTimer = setTimeout(() => {
					this.loadxSecondary()
				}, 500)
			},
			loadxCore() {
				const carTypeReq = this.$httpapi('Placeorder/car_type', 'POST', {}).then(res => {
					this.carList = res.data
					this.applyPendingAiCarSize()
				})
				const goodsTypeReq = this.$httpapi('Placeorder/goods_type', 'GET', {}).then(res => {
					this.typeList = res.data
					this.applyPendingAiGoodsType()
					if (this.typeList[this.typeIndex] && this.typeList[this.typeIndex].switch == 1) {
						if (this.sizeList.length === 0 || (this.sizeList.length === 1 && !this.sizeList[0].long && !this.sizeList[0].wide && !this.sizeList[0].hige)) {
							this.sizeList = [{
								long: this.long || '',
								wide: this.wide || '',
								hige: this.hige || ''
							}]
						}
					}
				})
				const packagingReq = this.$httpapi('Placeorder/packaging', 'GET', {}).then(res => {
					this.baoList = res.data || []
					if (this._pendingPackageList && this._pendingPackageList.length > 0 && this.baoList.length > 0) {
						this.packageSelections = []
						this._pendingPackageList.forEach(pkgItem => {
							const matchedItem = this.baoList.find(baoItem =>
								baoItem.name === pkgItem.name ||
								baoItem.id == pkgItem.id ||
								baoItem.id == pkgItem.packaging_id
							)
							if (matchedItem) {
								this.packageSelections.push({
									id: matchedItem.id,
									name: matchedItem.name,
									quantity: pkgItem.quantity || 1
								})
							}
						})
						if (this.packageSelections.length > 0) {
							for (let i = 0; i < this.baoList.length; i++) {
								if (this.packageSelections[0].id == this.baoList[i].id) {
									this.baoIndex = i
									break
								}
							}
						}
						this._pendingPackageList = null
					} else if (this.baoList.length && this.packageSelections.length === 0 && this.baoIndex >= 0 && this.baoIndex < this.baoList.length) {
						const item = this.baoList[this.baoIndex]
						this.packageSelections = [{
							id: item.id,
							name: item.name,
							quantity: 1
						}]
						this.calculateTotalPackageQuantity()
					}
				})
				return Promise.all([carTypeReq, goodsTypeReq, packagingReq]).catch(err => {
					console.error('加载首页核心数据失败:', err)
				})
			},
			loadxSecondary() {
				this.$httpapi('Placeorder/delivery_type', 'GET', {}).then(res => {
					this.songList = res.data
				})
				this.$httpapi('Placeorder/other', 'GET', {}).then(res => {
					this.qitaList = res.data
				})
				this.$httpapi('index/deliveryrequirements', 'GET', {}).then(res => {
					this.deliveryRequirementsList = res.data || []
				})
				this.$httpapi('index/loadingrequirements', 'GET', {}).then(res => {
					this.loadingRequirementsList = res.data || []
				})
				this.$httpapi('Placeorder/receipt_type', 'GET', {}).then(res => {
					this.huiList = [[], []]
					this.huiIndex = [-1, -1]
					this.huiLists = []
					if (res.data && Array.isArray(res.data) && res.data.length > 0) {
						this.huiLists = res.data
						const typeSet = new Set()
						res.data.forEach(item => {
							if (item.type) {
								typeSet.add(item.type)
							}
						})
						const typeArray = Array.from(typeSet).map(type => ({
							name: type
						}))
						if (typeArray.length > 0) {
							const selectedType = typeArray[0].name
							const subOptions = res.data
								.filter(item => item.type === selectedType)
								.map(item => ({
									id: item.id,
									name: item.name,
									price: item.receipt_type_price,
									type: item.type
								}))
							this.huiList[0] = typeArray
							this.huiList[1] = subOptions
						}
					}
				}).catch(err => {
					console.error('加载回单类型失败:', err)
					this.huiLists = []
					this.huiList = [[], []]
					this.huiIndex = [-1, -1]
				})
				this.$httpapi('Placeorder/unpack', 'GET', {}).then(res => {
					this.chaiList = [[], []]
					this.chaiIndex = [-1, -1]
					const numArray = []
					for (let i = 1; i <= 9999; i++) {
						numArray.push({ name: i })
					}
					this.chaiList[0] = res.data
					this.chaiList[1] = numArray
				})
			},
			loadExpressCompanies() {
				// 接口：api/index/company
				this.$httpapi('index/company', 'GET', {}).then(res => {
					const list = (res && res.data) ? res.data : []
					this.expressCompanyList = Array.isArray(list) ? list : []
				}).catch(err => {
					console.error('加载快递公司失败:', err)
					this.expressCompanyList = []
					uni.showToast({
						title: '加载快递公司失败',
						duration: 1500,
						icon: 'none'
					})
				})
			},
			toggleExpressCompanyPanel() {
				this.showExpressCompanyPanel = !this.showExpressCompanyPanel
				// 兜底：用户点击展开时再拉一次，避免首次 onShow 未触发/被拦截
				if (this.showExpressCompanyPanel && (!this.expressCompanyList || this.expressCompanyList.length === 0)) {
					this.loadExpressCompanies()
				}
			},
			isExpressCompanySelected(item) {
				if (!item || !this.selectedExpressCompany) return false
				return (this.selectedExpressCompany.company_name || '') === (item.company_name || '') &&
					(this.selectedExpressCompany.company_image || '') === (item.company_image || '')
			},
			selectExpressCompany(item) {
				this.selectedExpressCompany = item || null
				this.showExpressCompanyPanel = false
			},
			getCompanyImageSrc(path) {
				if (!path) return ''
				const p = String(path)
				if (/^https?:\/\//i.test(p)) return p
				return (this.imgUrl || '') + p
			},
			xiexuan() {
				var that = this
				uni.navigateTo({
					url: '/pages/user/address/address?type=2',
					events: {
						acceptDataFromOpenedPages: (data) => {
							console.log('接收卸货地址数据（地址簿）:', data)
							that.xieAddress = data
							console.log('卸货地址已更新:', that.xieAddress)
						},
					},
				})
			},
			zhuangxuan() {
				var that = this
				uni.navigateTo({
					url: '/pages/user/address/address?type=1',
					events: {
						acceptDataFromOpenedPages: (data) => {
							console.log('接收装货地址数据（地址簿）:', data)
							that.zhuangAddress = data
							console.log('装货地址已更新:', that.zhuangAddress)
						},
					},
				})
			},
			xiexuanQuick() {
				var that = this
				uni.navigateTo({
					url: '/pages/user/address/quickAddAddress?type=2',
					events: {
						acceptDataFromOpenedPages: (data) => {
							console.log('接收卸货地址数据（快速添加）:', data)
							that.xieAddress = data
							console.log('卸货地址已更新:', that.xieAddress)
						},
					},
				})
			},
			zhuangxuanQuick() {
				var that = this
				uni.navigateTo({
					url: '/pages/user/address/quickAddAddress?type=1',
					events: {
						acceptDataFromOpenedPages: (data) => {
							console.log('接收装货地址数据（快速添加）:', data)
							that.zhuangAddress = data
							console.log('装货地址已更新:', that.zhuangAddress)
						},
					},
				})
			},
			// 交换装货地址和卸货地址
			swapAddress() {
				const temp = this.zhuangAddress
				this.zhuangAddress = this.xieAddress
				this.xieAddress = temp
				uni.showToast({
					title: '地址已互换',
					duration: 1000,
					icon: 'none'
				})
			},
			// 清除装货地址
			clearZhuangAddress() {
				this.zhuangAddress = {}
			},
			// 清除卸货地址
			clearXieAddress() {
				this.xieAddress = {}
			},
			// 装货地址点击事件
			zhuangAddressClick() {
				if (this.zhuangAddress && this.zhuangAddress.address) {
					// 如果有地址，显示详情
					this.showAddressDetail(this.zhuangAddress, '装货地址')
				} else {
					// 如果没有地址，直接选择并保存地址
					this.chooseAndSaveAddress(1)
				}
			},
			// 卸货地址点击事件
			xieAddressClick() {
				if (this.xieAddress && this.xieAddress.address) {
					// 如果有地址，显示详情
					this.showAddressDetail(this.xieAddress, '卸货地址')
				} else {
					// 如果没有地址，直接选择并保存地址
					this.chooseAndSaveAddress(2)
				}
			},
			// 直接选择地址并保存（不跳转页面）
			chooseAndSaveAddress(type) {
				const that = this
				uni.chooseLocation({
					success: function (res) {
						console.log(res, res.address, '-----选择地址成功')
						// address字段存储地点名称（如：历下大厦（解放东路北））
						// detailed_address字段存储完整地址（如：山东省济南市历下区解放东路99号）
						const addressName = res.name || res.address
						const detailedAddress = res.address || ''
						const latitude = res.latitude
						const longitude = res.longitude
						const parsed = {
							user_name: '',
							company_name: '',
							mobile: '',
							address: addressName,
							detailed_address: detailedAddress,
							lat: latitude,
							lng: longitude
						}
						
						// 直接调用保存接口
						that.$httpapi('Placeorder/address', 'POST', that.buildAddressSavePayload(parsed, type)).then(res => {
							uni.showToast({
								title: (res && (res.msg ?? res.message)) || '操作成功',
								duration: 1500,
								icon: 'none'
							});
							if (res.code == 1) {
								// 处理 id：优先处理数字/字符串（直接是id），然后处理对象
								let addressId = null
								
								// 如果 res.data 是数字或字符串，直接使用（后端可能直接返回id）
								if (typeof res.data === 'number' || (typeof res.data === 'string' && res.data !== '')) {
									addressId = res.data
								}
								// 如果 res.data 是对象且不是 null
								else if (res.data !== null && typeof res.data === 'object') {
									// 优先查找 id 字段，如果不存在则查找其他可能的字段
									addressId = res.data.id !== undefined ? res.data.id : 
									           (res.data.address_id !== undefined ? res.data.address_id : 
									           (res.data.ID !== undefined ? res.data.ID : null))
								}
								
								// 如果无法获取 id，尝试重新查询地址列表找到对应的地址
								if (addressId === null || addressId === undefined || addressId === '') {
									console.warn('无法从接口返回获取 id，尝试查询地址列表')
									// 重新查询地址列表，通过地址信息匹配
									that.$httpapi('Placeorder/address_list', 'GET', {
										type: type || ''
									}).then(listRes => {
										if (listRes.code == 1 && listRes.data && listRes.data.length > 0) {
											// 通过地址匹配找到对应的地址
											const matchedAddress = listRes.data.find(item => 
												item.address === addressName || item.detailed_address === detailedAddress
											)
											if (matchedAddress && matchedAddress.id) {
												addressId = matchedAddress.id
												console.log('通过地址列表匹配找到的ID:', addressId)
											}
										}
										
										// 构建地址数据并更新
										const addressData = that.buildSavedAddressData(parsed, addressId)
										
										// 根据type更新对应的地址
										if (type == 1) {
											that.zhuangAddress = addressData
										} else if (type == 2) {
											that.xieAddress = addressData
										}
									}).catch(err => {
										console.error('查询地址列表失败:', err)
										const addressData = that.buildSavedAddressData(parsed, addressId)
										// 根据type更新对应的地址
										if (type == 1) {
											that.zhuangAddress = addressData
										} else if (type == 2) {
											that.xieAddress = addressData
										}
									})
								} else {
									const addressData = that.buildSavedAddressData(parsed, addressId)
									console.log('准备更新的地址数据:', addressData)
									
									// 根据type更新对应的地址
									if (type == 1) {
										that.zhuangAddress = addressData
									} else if (type == 2) {
										that.xieAddress = addressData
									}
								}
							}
						}).catch(err => {
							console.error('保存地址失败:', err)
							uni.showToast({
								title: '保存地址失败，请重试',
								duration: 1500,
								icon: 'none'
							});
						})
					},
					fail: function (err) {
						// 用户取消选择时，不显示错误
						if (err.errMsg && err.errMsg.indexOf('cancel') !== -1) {
							console.log('用户取消选择地址')
						} else {
							console.error('选择地址失败:', err)
							uni.showToast({
								title: '选择地址失败',
								duration: 1500,
								icon: 'none'
							});
						}
					}
				});
			},
			// 显示地址详情 - 跳转到地址详情页
			showAddressDetail(address, title) {
				// 将地址数据存储到storage，供详情页使用
				// 确保数据格式与addAddress页面期望的格式一致
				const addressData = {
					id: address.id,
					user_name: address.user_name,
					mobile: address.mobile,
					address: address.address,
					detailed_address: address.detailed_address,
					lat: address.lat || address.latitude,
					lng: address.lng || address.longitude,
					default: address.default || 0,
					type: address.type || (title === '装货地址' ? '1' : '2')
				}
				uni.setStorageSync('addressData', addressData)
				// 构建跳转URL，传递type参数
				const url = '/pages/user/address/addAddress?type=' + addressData.type
				uni.navigateTo({
					url: url
				})
			},
			agreement(type) {
				uni.navigateTo({
					url: '/pages/user/agreement/agreement?type=' + type
				})
			},
			queding() {
				this.earliest_time = this.riqi + ' ' + this.zuizao + ':00'
				this.latest_time = this.riqi + ' ' + this.zuiwan + ':00'
				this.$refs.popup.close()
			},
			guanbi() {
				this.$refs.popup.close()
			},
			fahuo() {
				this.$refs.popup.open('bottom')
			},
			// 预览车型图片
			previewCarImage() {
				if (this.carList[this.carIndex] && this.carList[this.carIndex].car_image) {
					const imageUrl = this.imgUrl + this.carList[this.carIndex].car_image
					uni.previewImage({
						urls: [imageUrl],
						current: imageUrl
					})
				}
			},
			bindPickerChangeriqi(e) {
				this.riqi = e.detail.value
			},
			bindPickerChangeshijian(e) {
				this.zuizao = e.detail.value
			},
			bindPickerChangeshijians(e) {
				this.zuiwan = e.detail.value
			},
			bindPickerChange(e) {
				this.indexs = e.detail.value
			},
			selectTaxType(t) {
				this.type = t
				this.syncTaxIndexs()
			},
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
			bindPickerChangeCar(e) {
				this.carIndex = e.detail.value
			},
			bindPickerChangeType(e) {
				this.typeIndex = e.detail.value
				// 检查当前选择的货物类型是否有switch字段且为1
				if (this.typeList[this.typeIndex] && this.typeList[this.typeIndex].switch == 1) {
					// 如果sizeList为空，初始化一个，并将当前单个尺寸的值复制过去
					if (this.sizeList.length === 0 || (this.sizeList.length === 1 && !this.sizeList[0].long && !this.sizeList[0].wide && !this.sizeList[0].hige)) {
						this.sizeList = [{
							long: this.long || '', 
							wide: this.wide || '', 
							hige: this.hige || ''
						}]
					}
				} else {
					// 如果不是多尺寸模式，将sizeList的第一个尺寸同步到单个尺寸字段
					if (this.sizeList.length > 0 && this.sizeList[0]) {
						this.long = this.sizeList[0].long || ''
						this.wide = this.sizeList[0].wide || ''
						this.hige = this.sizeList[0].hige || ''
					}
					// 清空sizeList
					this.sizeList = [{long: '', wide: '', hige: ''}]
				}
			},
			bindPickerChangebao(e) {
				this.baoIndex = e.detail.value
			},
			bindPickerChangeqita(e) {
				this.qitaIndex = e.detail.value
			},
			bindPickerChangechai(e) {
				this.chaiIndex = e.detail.value
			},
			bindPickerChangehui(e) {
				this.huiIndex = e.detail.value
			},
			bindPickerChangexin(e) {
				this.xinIndex = e.detail.value
			},
			bindPickerChangekong(e) {
				this.kongIndex = e.detail.value
			},
			bindPickerChangefuwu(e) {
				this.fuwuIndex = e.detail.value
			},
			bindPickerChangesonghuo(e) {
				this.songIndex = e.detail.value
			},
			// 取消选择：送货方式、回单、拆包服务、控货要求、短信通知
			clearSonghuo() {
				this.songIndex = -1
			},
			clearHui() {
				this.huiIndex = [-1, -1]
			},
			clearChai() {
				this.chaiIndex = [-1, -1]
			},
			clearKong() {
				this.kongIndex = -1
			},
			clearXin() {
				this.xinIndex = -1
			},
		columnchanges(e){
			console.log('列变化事件:', e.detail.column, e.detail.value)
			// 当第一列（回单类型）改变时，根据接口返回的数据动态更新第二列
			// 不同回单类型的子选项可能不同：
			// - 纸质回单：可能有原单、拍照等多个选项
			// - 电子回单：可能只有原单一个选项
			if(e.detail.column == 0){
				// 获取选中的类型（完全来自接口数据）
				if (this.huiList[0] && this.huiList[0][e.detail.value]) {
					const selectedType = this.huiList[0][e.detail.value].name
					console.log('选中的类型:', selectedType)
					// 从接口返回的完整数据中，根据类型过滤出对应的子选项
					// 这样不同类型会显示不同的子选项列表
					if (this.huiLists && Array.isArray(this.huiLists) && this.huiLists.length > 0) {
						const subOptions = this.huiLists
							.filter(item => item.type === selectedType)
							.map(item => ({
								id: item.id,
								name: item.name,
								price: item.receipt_type_price,
								type: item.type
							}))
						console.log('过滤后的子选项:', subOptions)
						// 重新赋值整个数组，确保 picker 组件能检测到变化
						this.huiList = [
							this.huiList[0],
							subOptions
						]
						// 重置第二列索引为0，确保选择第一个选项
						this.huiIndex = [e.detail.value, 0]
						// 强制更新视图
						this.$forceUpdate()
					}
				}
			}
		},
			loads() {
				this.$httpapi('index/announcement', 'POST', {}).then(res => {
					this.notice = res.data
				})
				this.$httpapi('Placeorder/banner', 'GET', {}).then(res => {
					this.banner = res.data
				})
			},
			// 从scene参数中解析invitation值
			parseInvitationFromScene(scene) {
				if(!scene) return ''
				try {
					let url = String(scene)
					// 尝试URL解码
					try {
						url = decodeURIComponent(url)
					} catch(e) {
						// 解码失败，使用原值
					}
					
					// 如果包含invitation参数，提取它
					if(url.indexOf('invitation') !== -1){
						// 使用字符串解析方式提取参数
						const match = url.match(/[?&]invitation=([^&]*)/)
						if(match && match[1]){
							return decodeURIComponent(match[1])
						}
					}
					
					// 如果scene本身就是invitation值（纯数字或字符串）
					// 检查是否是有效的invitation格式
					if(/^[a-zA-Z0-9]+$/.test(scene)){
						return scene
					}
					
					return ''
				} catch(e) {
					console.error('解析invitation参数失败:', e)
					// 如果解析失败，尝试直接使用scene值
					return String(scene || '')
				}
			},
			// 打开 AI 识别订单：选择相册或拍照
			openAiOrderRecognize() {
				
				// this.$uploadImg()
				
				// return
				if (this.aiRecognizing) return
				const token = uni.getStorageSync('token') || ''
				if (!token) {
					uni.showModal({
						title: '请登录!',
						content: '暂未登录，无法进行下一步操作',
						confirmText: '去登录',
						success: (res) => {
							if (res.confirm) {
								uni.navigateTo({
									url: '/pages/login/login'
								})
							}
						}
					})
					return
				}
				uni.showActionSheet({
					itemList: ['从相册选择', '拍照'],
					success: (res) => {
						const sourceType = res.tapIndex === 0 ? ['album'] : ['camera']
						this.chooseAiOrderImage(sourceType)
					}
				})
			},
			chooseAiOrderImage(sourceType) {
				const that = this
				uni.chooseImage({
					count: 1,
					sizeType: ['original', 'compressed'],
					sourceType,
					success(res) {
						console.log("res",res);
						if (res.tempFilePaths && res.tempFilePaths[0]) {
							that.recognizeAiOrderImage(res.tempFilePaths[0])
						}
					}
				})
			},
			clearAiOrderRecognizeData() {
				this._aiRecognizeSession = (this._aiRecognizeSession || 0) + 1
				this.goods_name = ''
				this.goods_image = ''
				this.weight = ''
				this.direction = ''
				this.quantity = 1
				this['break'] = ''
				this.information = ''
				this.deposit = ''
				this.carIndex = 0
				this.typeIndex = 0
				this.long = ''
				this.wide = ''
				this.hige = ''
				this.sizeList = [{ long: '', wide: '', hige: '' }]
				this.zhuangAddress = {}
				this.xieAddress = {}
				this._pendingAiCarId = null
				this._pendingAiGoodsTypeId = null
				uni.removeStorageSync(INDEX_GOODS_INFO_STORAGE_KEY)
			},
			recognizeAiOrderImage(filePath) {
				const that = this
				if (that.aiRecognizing) return
				that.clearAiOrderRecognizeData()
				const aiSession = that._aiRecognizeSession
				that.aiRecognizing = true
				const finishRecognize = (toastTitle) => {
					uni.hideLoading()
					that.aiRecognizing = false
					if (toastTitle) {
						uni.showToast({
							title: toastTitle,
							duration: 1500,
							icon: 'none'
						})
					}
				}
				const startUpload = () => {
					uni.showLoading({
						title: '正在上传图片...',
						mask: true
					})
					that.$upShop('common/upload', filePath, { showLoading: false }).then(rex => {
						const arr = JSON.parse(rex.data)
						const imageUrl = arr && arr.data && arr.data.fullurl
						if (!imageUrl) {
							throw new Error('图片上传失败')
						}
						uni.showLoading({
							title: '正在识别订单...',
							mask: true
						})
						// 同一图片 URL 再次识别时带 force=1，跳过/覆盖后端缓存
						const force = that._lastAiOrderImageUrl === imageUrl ? 1 : 0
						that._lastAiOrderImageUrl = imageUrl
						return that.$httpapi('opinion/orderocr', 'POST', {
							image: imageUrl,
							force
						})
					}).then(res => {
						if (res.code == 1 && res.data) {
							that.applyAiOrderRecognizeResult(res.data, aiSession)
							finishRecognize(res.msg || '识别成功')
						} else {
							finishRecognize(res.msg || '订单识别失败，请重试')
						}
					}).catch(err => {
						console.error('AI识别订单失败:', err)
						const errMsg = (err && err.errMsg) || (err && err.message) || ''
						const isTimeout = /timeout|超时|time out/i.test(errMsg)
						finishRecognize(isTimeout ? '上传超时，请稍后重试' : '订单识别失败，请重试')
					})
				}
				// 等首页核心接口完成后再上传，减少 iOS 连接池被占满导致 pending
				const waitCore = that._loadxCorePromise || Promise.resolve()
				waitCore.finally(() => {
					setTimeout(startUpload, 150)
				})
			},
			applyAiOrderRecognizeResult(data, aiSession) {
				if (!data || typeof data !== 'object') return
				if (aiSession != null && aiSession !== this._aiRecognizeSession) return
				if (data.goods_name) this.goods_name = data.goods_name
				// 吨数、方位（方数）
				if (data.weight != null && data.weight !== '') {
					this.weight = String(data.weight)
				}
				const volumeVal = data.volume != null && data.volume !== ''
					? data.volume
					: (data.direction != null && data.direction !== '' ? data.direction : '')
				if (volumeVal !== '') {
					this.direction = String(volumeVal)
				}
				if (data.quantity != null && data.quantity !== '') this.quantity = Number(data.quantity) || 1
				if (data.remark || data.break) this['break'] = data.remark || data.break
				if (data.goods_image) this.goods_image = data.goods_image
				// 技术服务费(information)、信息费(不可退)(deposit)
				if (data.deposit != null && data.deposit !== '') {
					this.information = String(data.deposit)
				}
				if (data.service_fee != null && data.service_fee !== '') {
					this.deposit = String(data.service_fee)
				}
				// 车型(car_size 为车型 id)、货物类型(goods_type 为类型 id)
				if (data.car_size != null && data.car_size !== '' && Number(data.car_size) !== 0) {
					this.applyAiCarSizeFromRecognize(data.car_size)
				}
				if (data.goods_type != null && data.goods_type !== '') {
					this.applyAiGoodsTypeFromRecognize(data.goods_type)
				}
				// 同步物品信息到本地，物品信息页可回显吨数/方位/货物类型
				this.saveIndexGoodsInfoToStorage()
				// 装货/卸货地址：新格式为对象 { address, detailed_address/detail_address }，或顶层 address + detail_address 分开传
				this.saveAiRecognizeAddress(
					data.load_address || data.loading_address || data.loading || data.zhuang_address,
					data.load_detail_address || data.load_detailed_address,
					1,
					aiSession
				)
				this.saveAiRecognizeAddress(
					data.unload_address || data.unload || data.xie_address,
					data.unload_detail_address || data.unload_detailed_address,
					2,
					aiSession
				)
			},
			parseAiRecognizeAddress(addressData, detailAddress) {
				if (!addressData && !detailAddress) return null
				if (typeof addressData === 'object' && addressData !== null) {
					return {
						user_name: addressData.user_name || addressData.person || '',
						mobile: addressData.mobile || addressData.phonenum || '',
						address: addressData.address || addressData.detail || '',
						detailed_address: addressData.detailed_address || addressData.detail_address || ''
					}
				}
				const address = String(addressData || '').trim()
				const detail = String(detailAddress || '').trim()
				if (!address && !detail) return null
				return {
					user_name: '',
					mobile: '',
					address,
					detailed_address: detail
				}
			},
			saveAiRecognizeAddress(addressData, detailAddress, type, aiSession) {
				const parsed = this.parseAiRecognizeAddress(addressData, detailAddress)
				if (!parsed) return
				if (!parsed.address && parsed.detailed_address) {
					parsed.address = parsed.detailed_address
				}
				if (!parsed.address) return
				const aiOptions = { fromAi: true, aiSession }
				// 已有结构化 address + detailed_address 时直接保存，不再走文本识别
				if (parsed.detailed_address) {
					this.saveQuickInputAddress(parsed, type, aiOptions)
					return
				}
				this.fillAddressFromAiRecognize(parsed.address, type, aiOptions)
			},
			applyAiCarSizeFromRecognize(carId) {
				const id = Number(carId)
				if (!id || isNaN(id)) return
				if (!this.carList || !this.carList.length) {
					this._pendingAiCarId = id
					return
				}
				const idx = this.carList.findIndex(item => Number(item.id) === id)
				if (idx >= 0) {
					this.carIndex = idx
					this._pendingAiCarId = null
				}
			},
			applyPendingAiCarSize() {
				if (this._pendingAiCarId != null) {
					this.applyAiCarSizeFromRecognize(this._pendingAiCarId)
				}
			},
			applyAiGoodsTypeFromRecognize(goodsTypeId) {
				const id = Number(goodsTypeId)
				if (!id || isNaN(id)) return
				if (!this.typeList || !this.typeList.length) {
					this._pendingAiGoodsTypeId = id
					return
				}
				const idx = this.typeList.findIndex(item => Number(item.id) === id)
				if (idx >= 0) {
					this.typeIndex = idx
					this._pendingAiGoodsTypeId = null
					if (this.typeList[this.typeIndex] && this.typeList[this.typeIndex].switch == 1) {
						if (this.sizeList.length === 0 || (this.sizeList.length === 1 && !this.sizeList[0].long && !this.sizeList[0].wide && !this.sizeList[0].hige)) {
							this.sizeList = [{
								long: this.long || '',
								wide: this.wide || '',
								hige: this.hige || ''
							}]
						}
					}
				}
			},
			applyPendingAiGoodsType() {
				if (this._pendingAiGoodsTypeId != null) {
					this.applyAiGoodsTypeFromRecognize(this._pendingAiGoodsTypeId)
				}
			},
			fillAddressFromAiRecognize(addressData, type, options) {
				if (typeof addressData === 'string') {
					const text = addressData.trim()
					if (!text) return
					this.recognizeAddressTextAndSave(text, type, options)
					return
				}
				const parsed = this.parseAiRecognizeAddress(addressData)
				if (!parsed || !parsed.address) return
				if (!parsed.detailed_address) {
					this.recognizeAddressTextAndSave(parsed.address, type, options)
					return
				}
				this.saveQuickInputAddress(parsed, type, options)
			},
			recognizeAddressTextAndSave(addressText, type, options) {
				const that = this
				const fromAi = options && options.fromAi
				const aiSession = options && options.aiSession
				const isStaleAiSession = () => fromAi && aiSession != null && aiSession !== that._aiRecognizeSession
				uni.showLoading({
					title: fromAi ? '正在保存地址...' : '正在识别地址...'
				})
				that.$httpapi('index/adderss_recognize', 'POST', {
					address: addressText
				}).then(res => {
					uni.hideLoading()
					if (isStaleAiSession()) return
					if (res.code == 1 && res.data) {
						const parsed = {
							user_name: res.data.person || '',
							mobile: res.data.phonenum || '',
							address: res.data.detail || addressText,
							detailed_address: res.data.address || addressText
						}
						if (parsed.address) {
							that.saveQuickInputAddress(parsed, type, options)
							return
						}
					}
					// 识别接口无结构化结果时，仍用原文走添加地址
					if (fromAi && addressText) {
						that.saveQuickInputAddress({
							user_name: '',
							mobile: '',
							address: addressText,
							detailed_address: addressText
						}, type, options)
					}
				}).catch(() => {
					uni.hideLoading()
					if (isStaleAiSession()) return
					if (fromAi && addressText) {
						that.saveQuickInputAddress({
							user_name: '',
							mobile: '',
							address: addressText,
							detailed_address: addressText
						}, type, options)
					}
				})
			},
			// 打开快速输入弹窗
			openQuickInput(type) {
				this.quickInputType = type
				this.quickInputText = ''
				this.$refs.quickInputPopup.open('center')
			},
			// 关闭快速输入弹窗
			closeQuickInput() {
				this.$refs.quickInputPopup.close()
				this.quickInputText = ''
			},
			// 确认快速输入，解析并保存地址
			confirmQuickInput() {
				if (!this.quickInputText || !this.quickInputText.trim()) {
					uni.showToast({
						title: '请输入地址信息',
						duration: 1500,
						icon: 'none'
					})
					return
				}
				
				const that = this
				const addressText = this.quickInputText.trim()
				
				// 显示加载提示
				uni.showLoading({
					title: '正在识别地址...'
				})
				
				// 调用后台地址识别接口
				that.$httpapi('index/adderss_recognize', 'POST', {
					address: addressText
				}).then(res => {
					uni.hideLoading()
					
					if (res.code == 1 && res.data) {
						// 映射后台返回的数据到本地数据结构
						// 交换完整地址和详细地址：address使用detail的值，detailed_address使用address的值
						const parsed = {
							user_name: res.data.person || '',
							mobile: res.data.phonenum || '',
							address: res.data.detail || '',
							detailed_address: res.data.address || ''
						}
						
						if (!parsed.address) {
							uni.showToast({
								title: '无法识别地址，请检查格式',
								duration: 1500,
								icon: 'none'
							})
							return
						}
						
						// 调用地址保存接口
						that.saveQuickInputAddress(parsed, that.quickInputType)
					} else {
						uni.showToast({
							title: res.msg || '地址识别失败，请重试',
							duration: 1500,
							icon: 'none'
						})
					}
				}).catch(err => {
					uni.hideLoading()
					console.error('地址识别接口调用失败:', err)
					uni.showToast({
						title: '地址识别失败，请重试',
						duration: 1500,
						icon: 'none'
					})
				})
			},
			// 解析地址文本，提取姓名、手机号、地址
			parseAddressText(text) {
				const result = {
					user_name: '',
					mobile: '',
					address: '',
					detailed_address:''
				}
				
				const originalText = text.trim()
				
				// 匹配手机号（11位数字，可能包含空格、横线或括号）
				const mobileRegex = /1[3-9]\d[\s\-()]?\d{4}[\s\-()]?\d{4}/
				const mobileMatch = originalText.match(mobileRegex)
				
				if (mobileMatch) {
					// 提取手机号（去除空格、横线和括号）
					result.mobile = mobileMatch[0].replace(/[\s\-()]/g, '')
					
					// 找到手机号在原文本中的位置
					const mobileIndex = originalText.indexOf(mobileMatch[0])
					const textAfterMobile = originalText.substring(mobileIndex + mobileMatch[0].length).trim()
					const textBeforeMobile = originalText.substring(0, mobileIndex).trim()
					if (textAfterMobile.length <= 3) {
			
						// 从手机号前面提取姓名（最后2-4个汉字）
						const nameMatch = textBeforeMobile.match(/([\u4e00-\u9fa5]{2,4})\s*$/)
						if (nameMatch && nameMatch[1]) {
							result.user_name = nameMatch[1]
							// 姓名前面的所有内容都是地址
							result.address = textBeforeMobile.substring(0, textBeforeMobile.length - nameMatch[1].length).trim()
						} else {
							// 如果没有找到姓名，整个手机号前面的内容都是地址
							result.address = textBeforeMobile
						}
					} else {
						// 格式：姓名 手机号 地址（例如：张三 17610521368 山东省济南市...）
						// 从手机号前面提取姓名（开头的2-4个汉字）
						const nameMatch = textBeforeMobile.match(/^([\u4e00-\u9fa5]{2,4})(\s|$)/)
						if (nameMatch && nameMatch[1]) {
							result.user_name = nameMatch[1]
							// 手机号后面的内容都是地址
							result.address = textAfterMobile
						} else {
							// 如果没有找到姓名，手机号后面的内容作为地址
							result.address = textAfterMobile
						}
					}
				} else {
					// 没有手机号的情况
					// 尝试提取开头的2-4个汉字作为姓名
					const nameMatch = text.match(/^([\u4e00-\u9fa5]{2,4})(\s|$)/)
					if (nameMatch && nameMatch[1]) {
						// 检查后面是否还有地址内容（至少包含省市区等关键词或至少8个字符）
						const remaining = text.substring(nameMatch[1].length).trim()
						const hasAddressKeywords = /省|市|区|县|街道|路|号|村|镇|乡|物流|中心|栋|档/.test(remaining)
						
						if (remaining.length >= 8 || hasAddressKeywords) {
							result.user_name = nameMatch[1]
							result.address = remaining
						} else {
							// 如果后面内容不够，整个文本作为地址
							result.address = text
						}
					} else {
						// 没有找到姓名，整个文本作为地址
						result.address = text
					}
				}
				
				// 清理地址中的多余空格
				result.address = result.address.replace(/\s+/g, ' ').trim()
				
				// 如果没有识别出地址，使用整个文本作为地址
				if (!result.address) {
					result.address = originalText
				}
				
				// 验证：如果地址太短且姓名存在，可能是解析错误
				if (result.address.length < 5 && result.user_name) {
					// 检查是否整个文本都是地址（包含地址关键词）
					const hasAddressKeywords = /省|市|区|县|街道|路|号|村|镇|乡|物流|中心|栋|档/.test(originalText)
					if (hasAddressKeywords && originalText.length > result.user_name.length + 5) {
						// 可能是"地址 姓名"格式，重新解析
						const nameAtEnd = originalText.match(/([\u4e00-\u9fa5]{2,4})\s*$/)
						if (nameAtEnd && nameAtEnd[1]) {
							result.user_name = nameAtEnd[1]
							result.address = originalText.substring(0, originalText.length - nameAtEnd[1].length).trim()
						}
					}
				}
				
				console.log('解析结果:', result)
				return result
			},
			// 构建与地图选点保存一致的请求参数
			buildAddressSavePayload(parsed, type) {
				return {
					user_name: parsed.user_name || '',
					company_name: parsed.company_name || '',
					mobile: parsed.mobile || '',
					address: parsed.address,
					detailed_address: parsed.detailed_address || '',
					default: 0,
					lat: parsed.lat || '',
					lng: parsed.lng || '',
					type: type || ''
				}
			},
			// 构建与地图选点保存一致的页面地址数据
			buildSavedAddressData(parsed, addressId) {
				return {
					id: addressId,
					user_name: parsed.user_name || '',
					company_name: parsed.company_name || '',
					mobile: parsed.mobile || '',
					address: parsed.address,
					detailed_address: parsed.detailed_address || '',
					lat: parsed.lat || '',
					lng: parsed.lng || ''
				}
			},
			// 保存快速输入的地址（AI识别订单也会走此接口）
			saveQuickInputAddress(parsed, type, options) {
				const that = this
				const fromAi = options && options.fromAi
				const aiSession = options && options.aiSession
				const isStaleAiSession = () => fromAi && aiSession != null && aiSession !== that._aiRecognizeSession
				
				uni.showLoading({
					title: '正在保存地址...'
				})
				
				// 调用保存接口（参数与 chooseAndSaveAddress 保持一致）
				that.$httpapi('Placeorder/address', 'POST', this.buildAddressSavePayload(parsed, type)).then(res => {
					uni.hideLoading()
					if (isStaleAiSession()) return
					if (!fromAi) {
						uni.showToast({
							title: (res && (res.msg ?? res.message)) || '保存成功',
							duration: 1500,
							icon: 'none'
						})
					}
					
					if (res.code == 1) {
						// 处理返回的id
						let addressId = null
						
						if (typeof res.data === 'number' || (typeof res.data === 'string' && res.data !== '')) {
							addressId = res.data
						} else if (res.data !== null && typeof res.data === 'object') {
							addressId = res.data.id !== undefined ? res.data.id : 
							           (res.data.address_id !== undefined ? res.data.address_id : 
							           (res.data.ID !== undefined ? res.data.ID : null))
						}
						// 如果接口没有直接返回id，尝试从地址列表中匹配获取
						if (addressId === null || addressId === undefined || addressId === '') {
							console.warn('地址粘贴识别保存地址未返回有效id，尝试从地址列表匹配')
							that.$httpapi('Placeorder/address_list', 'GET', {
								type: type || ''
							}).then(listRes => {
								if (isStaleAiSession()) return
								if (listRes.code == 1 && listRes.data && listRes.data.length > 0) {
									// 通过地址匹配找到对应的地址
									const matchedAddress = listRes.data.find(item =>
										item.address === parsed.address ||
										item.detailed_address === parsed.detailed_address ||
										item.detailed_address === parsed.address
									)
									if (matchedAddress && matchedAddress.id !== undefined && matchedAddress.id !== null && matchedAddress.id !== '') {
										addressId = matchedAddress.id
										console.log('通过地址列表匹配到的地址粘贴识别地址ID:', addressId)
									}
								}
								
								if (isStaleAiSession()) return
								that.applySavedAddressToPage(that.buildSavedAddressData(parsed, addressId), type, fromAi)
							}).catch(err => {
								console.error('从地址列表匹配地址粘贴识别地址失败:', err)
								if (!isStaleAiSession()) {
									that.applySavedAddressToPage(that.buildSavedAddressData(parsed, addressId), type, fromAi)
								}
							})
						} else if (!isStaleAiSession()) {
							that.applySavedAddressToPage(that.buildSavedAddressData(parsed, addressId), type, fromAi)
						}
					}
				}).catch(err => {
					uni.hideLoading()
					console.error('保存地址失败:', err)
					uni.showToast({
						title: fromAi ? '地址保存失败' : '保存地址失败，请重试',
						duration: 1500,
						icon: 'none'
					})
				})
			},
			applySavedAddressToPage(addressData, type, fromAi) {
				if (type == 1) {
					this.zhuangAddress = addressData
				} else if (type == 2) {
					this.xieAddress = addressData
				}
				if (!fromAi) {
					this.closeQuickInput()
				}
			}
		}
	}
</script>

<style>
	.banners {
		width: 710rpx;
		height: 250rpx;
		margin: 20rpx;
		border-radius: 20rpx;
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
		margin: 38rpx auto 0;
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

	.express-entry-btn{
		min-width: 140rpx;
		height: 60rpx;
		padding: 0 20rpx;
		background: linear-gradient(180deg, #4897FF 0%, #4878FF 100%);
		border-radius: 30rpx;
		color: #FFFFFF;
		font-size: 26rpx;
		line-height: 60rpx;
		text-align: center;
		flex-shrink: 0;
	}

	.ai-order-entry{
		display: flex;
		align-items: center;
		height: 120rpx;
		padding: 0 20rpx;
		background: linear-gradient(135deg, #F0F7FF 0%, #E8F3FF 100%);
		border-radius: 20rpx;
		border: 2rpx solid #D8E8FF;
	}
	.ai-order-entry__icon{
		width: 72rpx;
		height: 72rpx;
		background: linear-gradient(180deg, #4897FF 0%, #4878FF 100%);
		border-radius: 16rpx;
		font-weight: bold;
		font-size: 28rpx;
		color: #FFFFFF;
		line-height: 72rpx;
		text-align: center;
		flex-shrink: 0;
	}
	.ai-order-entry__content{
		flex: 1;
		min-width: 0;
		margin: 0 16rpx;
	}
	.ai-order-entry__title{
		font-weight: bold;
		font-size: 30rpx;
		color: #333333;
	}
	.ai-order-entry__desc{
		font-size: 24rpx;
		color: #888888;
		margin-top: 8rpx;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}
	.ai-order-entry__arrow{
		width: 20rpx;
		height: 20rpx;
		flex-shrink: 0;
	}

	/* 地址行：防止长地址把右侧按钮挤压 */
	.address-main{
		margin: 0 24rpx;
		flex: 1;
		min-width: 0; /* 关键：允许在 flex 中收缩，ellipsis 才生效 */
		position: relative;
	}
	.address-actions{
		display: flex;
		align-items: center;
		flex-shrink: 0;     /* 关键：右侧按钮不被压缩 */
		white-space: nowrap; /* 关键：按钮不换行 */
	}
	.address-divider{
		background-color: #DDDDDD;
		width: 2rpx;
		height: 36rpx;
		margin-right: 14rpx;
		flex-shrink: 0;
	}
	.address-action{
		font-size: 26rpx;
		color: #4878FF;
		margin-right: 14rpx;
		flex-shrink: 0;
	}
	.address-action:last-child{
		margin-right: 0;
	}
	.address-action--stack{
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		line-height: 26rpx;
		font-size: 22rpx;
		margin-right: 14rpx; /* 覆盖 .address-action:last-child 的影响 */
	}
	.address-action--stack view{
		line-height: 26rpx;
	}

	.car-type-options{
		display: flex;
		align-items: center;
		flex-wrap: wrap;
		justify-content: flex-end;
		gap: 22rpx;
	}
	.car-type-option{
		display: flex;
		align-items: center;
	}
	.car-type-radio{
		width: 32rpx;
		height: 32rpx;
	}
	.car-type-label{
		margin-left: 10rpx;
	}
	.express-company-logo{
		width: 44rpx;
		height: 44rpx;
		border-radius: 10rpx;
		background: #FFFFFF;
		margin-right: 12rpx;
		flex-shrink: 0;
	}
	.express-company-name{
		flex: 1;
		min-width: 0;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}
</style>

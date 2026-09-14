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
				<view>{{isEdit ? '修改发货地' : '添加发货地'}}</view>
				<view style="width:100rpx;"></view>
			</view>
		</view>
		<view :style="'height:'+(statusBarHeight+navigationBarHeight)+'px'"></view>

		<!-- 发货信息（单一起点） -->
		<view style="background: #FFFFFF;border-radius: 24rpx;padding:0 24rpx;margin:20rpx;">
			<view style="font-weight: bold;font-size: 30rpx;padding: 28rpx 0 8rpx;">发货信息</view>
			<view class="form-row" @click="fahuodizhi">
				<view class="form-label"><text class="req">*</text> 发货地址</view>
				<view class="form-value">{{faAddress||'点击选择发货地址'}}</view>
			</view>
			<view class="form-row" @click="setFocus('ship_park')">
				<view class="form-label"><text class="req">*</text> 发货物流园</view>
				<input class="form-input" v-model="shipping_logistics_park" placeholder="请输入"
					confirm-type="next" hold-keyboard adjust-position :cursor-spacing="24"
					:focus="focusField === 'ship_park'"
					@confirm="setFocus('ship_name')"
					@click.stop @blur="onInputBlur('ship_park')" />
			</view>
			<view class="form-row" @click="setFocus('ship_name')">
				<view class="form-label"><text class="req">*</text> 物流名称</view>
				<input class="form-input" v-model="shipping_logistics_name" placeholder="请输入"
					confirm-type="done" hold-keyboard adjust-position :cursor-spacing="24"
					:focus="focusField === 'ship_name'"
					@click.stop @blur="onInputBlur('ship_name')" />
			</view>
			<picker mode="time" :value="dailytime" @change="onDailyTimeChange">
				<view class="form-row">
					<view class="form-label"><text class="req">*</text> 每日发货时间</view>
					<view class="form-value" :class="{'form-placeholder': !dailytime}">
						{{dailytime || '请选择发货时间'}}
					</view>
				</view>
			</picker>
			<view style="padding: 24rpx 0;border-bottom:2rpx solid #F7F7F7;">
				<view style="margin-bottom: 20rpx;"><text class="req">*</text> 营业执照</view>
				<image @click="upImage(1)" style="width: 166rpx;height:166rpx;border-radius:12rpx;"
					:src="business_license ? imgUrl + business_license : '/static/images/icon_26sc.png'"></image>
			</view>
			<view style="padding: 24rpx 0;">
				<view style="margin-bottom: 20rpx;"><text class="req">*</text> 门头照</view>
				<image @click="upImage(2)" style="width: 166rpx;height:166rpx;border-radius:12rpx;"
					:src="doorway_image ? imgUrl + doorway_image : '/static/images/icon_26sc.png'"></image>
			</view>
		</view>

		<!-- 落货信息（多个终点） -->
		<view v-for="(item, index) in arrivalList" :key="item._key"
			style="background: #FFFFFF;border-radius: 24rpx;padding:0 24rpx;margin:20rpx;">
			<view class="flexJusp" style="align-items: center;padding: 28rpx 0 8rpx;">
				<view style="font-weight: bold;font-size: 30rpx;">落货信息{{arrivalList.length > 1 ? (index + 1) : ''}}</view>
				<view class="arrival-actions">
					<view v-if="index > 0" class="copy-prev" @click="copyPrevArrival(index)">复制上一条</view>
					<view v-if="arrivalList.length > 1" class="del-arrival" @click="removeArrival(index)">删除</view>
				</view>
			</view>
			<view class="form-row" @click="luohuodizhi(index)">
				<view class="form-label"><text class="req">*</text> 落货地址</view>
				<view class="form-value">{{item.addressText||'点击选择落货地址'}}</view>
			</view>
			<view class="form-row" @click="setFocus(item._key + '_park')">
				<view class="form-label"><text class="req">*</text> 落货物流园名称</view>
				<input class="form-input" :value="item.arrival_logistics_park" placeholder="请输入"
					confirm-type="next" hold-keyboard adjust-position :cursor-spacing="24"
					:focus="focusField === item._key + '_park'"
					@input="onArrivalInput(index, 'arrival_logistics_park', $event)"
					@confirm="setFocus(item._key + '_sperson')"
					@click.stop @blur="onInputBlur(item._key + '_park')" />
			</view>
			<view class="form-row" @click="setFocus(item._key + '_sperson')">
				<view class="form-label"><text class="req">*</text> 发货联系人</view>
				<input class="form-input" :value="item.shipping_contact_person" placeholder="请输入"
					confirm-type="next" hold-keyboard adjust-position :cursor-spacing="24"
					:focus="focusField === item._key + '_sperson'"
					@input="onArrivalInput(index, 'shipping_contact_person', $event)"
					@confirm="setFocus(item._key + '_smobile')"
					@click.stop @blur="onInputBlur(item._key + '_sperson')" />
			</view>
			<view class="form-row" @click="setFocus(item._key + '_smobile')">
				<view class="form-label"><text class="req">*</text> 发货物流电话</view>
				<input class="form-input" :value="item.shipping_logistics_mobile" placeholder="请输入"
					type="number" maxlength="11" confirm-type="next" hold-keyboard adjust-position :cursor-spacing="24"
					:focus="focusField === item._key + '_smobile'"
					@input="onArrivalInput(index, 'shipping_logistics_mobile', $event)"
					@confirm="setFocus(item._key + '_amobile')"
					@click.stop @blur="onInputBlur(item._key + '_smobile')" />
			</view>
			<view class="form-row" @click="setFocus(item._key + '_amobile')">
				<view class="form-label"><text class="req">*</text> 落货物流电话</view>
				<input class="form-input" :value="item.arrival_logistics_mobile" placeholder="请输入"
					type="number" maxlength="11" confirm-type="next" hold-keyboard adjust-position :cursor-spacing="24"
					:focus="focusField === item._key + '_amobile'"
					@input="onArrivalInput(index, 'arrival_logistics_mobile', $event)"
					@confirm="setFocus(item._key + '_time')"
					@click.stop @blur="onInputBlur(item._key + '_amobile')" />
			</view>
			<view class="form-row" @click="setFocus(item._key + '_time')">
				<view class="form-label"><text class="req">*</text> 时效</view>
				<input class="form-input" :value="item.time_limit" placeholder="如：3天" 
					confirm-type="next" hold-keyboard adjust-position :cursor-spacing="24"
					:focus="focusField === item._key + '_time'"
					@input="onArrivalInput(index, 'time_limit', $event)"
					@confirm="setFocus(item._key + '_side')"
					@click.stop @blur="onInputBlur(item._key + '_time')" />
			</view>
			<!-- 运价四宫格：一屏看完，键盘下一步连跳 -->
			<view class="rate-grid">
				<view class="rate-cell" @click="setFocus(item._key + '_side')">
					<view class="rate-label"><text class="req">*</text> 每方</view>
					<input class="rate-input" :value="item.side" placeholder="元"
						type="digit" confirm-type="next" hold-keyboard adjust-position :cursor-spacing="24"
						:focus="focusField === item._key + '_side'"
						@input="onArrivalInput(index, 'side', $event)"
						@confirm="setFocus(item._key + '_perton')"
						@click.stop @blur="onInputBlur(item._key + '_side')" />
				</view>
				<view class="rate-cell" @click="setFocus(item._key + '_perton')">
					<view class="rate-label"><text class="req">*</text> 每吨</view>
					<input class="rate-input" :value="item.perton" placeholder="元"
						type="digit" confirm-type="next" hold-keyboard adjust-position :cursor-spacing="24"
						:focus="focusField === item._key + '_perton'"
						@input="onArrivalInput(index, 'perton', $event)"
						@confirm="setFocus(item._key + '_reflux')"
						@click.stop @blur="onInputBlur(item._key + '_perton')" />
				</view>
				<view class="rate-cell" @click="setFocus(item._key + '_reflux')">
					<view class="rate-label"><text class="req">*</text> 重泡</view>
					<input class="rate-input" :value="item.reflux" placeholder="请输入"
						type="digit" confirm-type="next" hold-keyboard adjust-position :cursor-spacing="24"
						:focus="focusField === item._key + '_reflux'"
						@input="onArrivalInput(index, 'reflux', $event)"
						@confirm="setFocus(item._key + '_bulky')"
						@click.stop @blur="onInputBlur(item._key + '_reflux')" />
				</view>
				<view class="rate-cell rate-cell-last" @click="setFocus(item._key + '_bulky')">
					<view class="rate-label"><text class="req">*</text> 轻泡</view>
					<input class="rate-input" :value="item.bulky" placeholder="请输入"
						type="digit" confirm-type="done" hold-keyboard adjust-position :cursor-spacing="24"
						:focus="focusField === item._key + '_bulky'"
						@input="onArrivalInput(index, 'bulky', $event)"
						@click.stop @blur="onInputBlur(item._key + '_bulky')" />
				</view>
			</view>
		</view>

		<view class="add-arrival" @click="addArrival">+ 添加落货地址</view>

		<view class="flexJusp" style="align-items: center;margin:40rpx 40rpx 20rpx;">
			<view class="buttonx" @click="kefu">联系客服</view>
			<view class="buttons" @click="tijiao">提交</view>
		</view>
		<view style="height:60rpx;"></view>
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
				scrollTop: 0,
				isEdit: false,
				editShippingKey: '',
				faAddress: '',
				shipping_province: '',
				origincity: '',
				shipping_area: '',
				shipping_logistics_park: '',
				shipping_logistics_name: '',
				dailytime: '',
				shipping_logistics_address: '',
				shipping_longitude: '',
				shipping_latitude: '',
				business_license: '',
				doorway_image: '',
				arrivalList: [],
				focusField: '',
				arrivalKeySeed: 1,
				choosingLocation: false
			}
		},
		onLoad(option) {
			this.getSystemInfo()
			this.resetForm()
			option = option || {}
			// 只有明确 mode=edit 且带 shipping_key 才是修改
			if (option.mode === 'edit' && option.shipping_key) {
				this.isEdit = true
				this.editShippingKey = decodeURIComponent(option.shipping_key)
			} else {
				this.isEdit = false
				this.editShippingKey = ''
			}
			this.loadDetail()
		},
		onPageScroll(event) {
			this.scrollTop = event.scrollTop
		},
		methods: {
			onDailyTimeChange(e) {
				this.dailytime = e.detail.value
			},
			resetForm() {
				this.isEdit = false
				this.editShippingKey = ''
				this.faAddress = ''
				this.shipping_province = ''
				this.origincity = ''
				this.shipping_area = ''
				this.shipping_logistics_park = ''
				this.shipping_logistics_name = ''
				this.dailytime = ''
				this.shipping_logistics_address = ''
				this.shipping_longitude = ''
				this.shipping_latitude = ''
				this.arrivalList = [this.createArrivalItem()]
			},
			createArrivalItem() {
				return {
					_key: 'arr_' + (this.arrivalKeySeed++),
					addressText: '',
					province: '',
					destination: '',
					arrival_area: '',
					arrival_logistics_park: '',
					arrival_logistics_address: '',
					shipping_contact_person: '',
					shipping_logistics_mobile: '',
					arrival_logistics_mobile: '',
					arrival_longitude: '',
					arrival_latitude: '',
					time_limit: '',
					side: '',
					perton: '',
					reflux: '',
					bulky: ''
				}
			},
			onArrivalInput(index, field, e) {
				if (!this.arrivalList[index]) return
				let val = ''
				if (e && e.detail && e.detail.value !== undefined) {
					val = e.detail.value
				} else if (typeof e === 'string' || typeof e === 'number') {
					val = e
				}
				this.$set(this.arrivalList[index], field, val)
			},
			addArrival() {
				let item = this.createArrivalItem()
				let prev = this.arrivalList[this.arrivalList.length - 1]
				// 仅带入运价；联系人/电话每条落货单独填
				if (prev) {
					item.time_limit = prev.time_limit || ''
					item.side = prev.side || ''
					item.perton = prev.perton || ''
					item.reflux = prev.reflux || ''
					item.bulky = prev.bulky || ''
				}
				this.arrivalList.push(item)
			},
			removeArrival(index) {
				if (this.arrivalList.length <= 1) return
				this.arrivalList.splice(index, 1)
			},
			copyPrevArrival(index) {
				if (index <= 0) return
				let prev = this.arrivalList[index - 1]
				let cur = this.arrivalList[index]
				if (!prev || !cur) return
				// 一键复制仅运价；联系人/电话保持各自独立
				cur.time_limit = prev.time_limit || ''
				cur.side = prev.side || ''
				cur.perton = prev.perton || ''
				cur.reflux = prev.reflux || ''
				cur.bulky = prev.bulky || ''
				this.$set(this.arrivalList, index, cur)
				uni.showToast({
					title: '已复制上一条运价',
					icon: 'none'
				})
			},
			setFocus(field) {
				if (!field) return
				// 先清空再聚焦，保证微信小程序能连续跳转下一个输入框
				this.focusField = ''
				this.$nextTick(() => {
					this.focusField = field
				})
			},
			onInputBlur(field) {
				if (this.focusField === field) {
					this.focusField = ''
				}
			},
			fillArrivalList(list) {
				this.arrivalList = (list || []).map(item => {
					let addressText = ''
					if (item.province && item.destination && item.arrival_area) {
						addressText = item.province + ' ' + item.destination + ' ' + item.arrival_area
					}
					return {
						_key: 'arr_' + (this.arrivalKeySeed++),
						addressText: addressText,
						province: item.province || '',
						destination: item.destination || '',
						arrival_area: item.arrival_area || '',
						arrival_logistics_park: item.arrival_logistics_park || '',
						arrival_logistics_address: item.arrival_logistics_address || '',
						shipping_contact_person: item.shipping_contact_person || '',
						shipping_logistics_mobile: item.shipping_logistics_mobile || '',
						arrival_logistics_mobile: item.arrival_logistics_mobile || '',
						arrival_longitude: item.arrival_longitude || '',
						arrival_latitude: item.arrival_latitude || '',
						time_limit: item.time_limit || '',
						side: item.side || '',
						perton: item.perton || '',
						reflux: item.reflux || '',
						bulky: item.bulky || ''
					}
				})
				if (!this.arrivalList.length) {
					this.arrivalList = [this.createArrivalItem()]
				}
			},
			fillShipping(item) {
				this.shipping_province = item.shipping_province || ''
				this.origincity = item.origincity || ''
				this.shipping_area = item.shipping_area || ''
				this.shipping_logistics_park = item.shipping_logistics_park || ''
				this.shipping_logistics_name = item.shipping_logistics_name || ''
				this.dailytime = item.dailytime || ''
				this.shipping_logistics_address = item.shipping_logistics_address || ''
				this.shipping_longitude = item.shipping_longitude || ''
				this.shipping_latitude = item.shipping_latitude || ''
				if (this.shipping_province && this.origincity && this.shipping_area) {
					this.faAddress = this.shipping_province + ' ' + this.origincity + ' ' + this.shipping_area
				}
				this.fillArrivalList(item.arrival_list || [])
			},
			loadDetail() {
				var that = this
				this.$httpapi('Dedicated/logistics_detail', 'POST', {}).then(res => {
					if (res.code != 1 || !res.data) return
					let data = res.data
					// 添加/修改都可回填已上传的执照和门头照
					that.business_license = data.business_license || data.postcard_front_image || ''
					that.doorway_image = data.doorway_image || ''

					// 继续添加：不回填发货地和落货地
					if (!that.isEdit) return

					let list = data.shipping_list || []
					let target = list.find(item => item.shipping_key === that.editShippingKey)
					if (!target && data.shipping_province) {
						target = {
							...data,
							arrival_list: data.arrival_list || [data]
						}
					}
					if (target) {
						that.fillShipping(target)
					}
				}).catch((e) => {
					console.log(e)
				})
			},
			fahuodizhi() {
				this.openChooseLocation(-1)
			},
			luohuodizhi(index) {
				this.openChooseLocation(index)
			},
			openChooseLocation(type) {
				var that = this
				if (this.choosingLocation) return
				this.choosingLocation = true
				uni.authorize({
					scope: 'scope.userLocation',
					success() {
						that.doChooseLocation(type)
					},
					fail() {
						that.choosingLocation = false
						uni.showModal({
							title: '需要定位权限',
							content: '请允许使用位置信息，以便在地图上选择地址',
							confirmText: '去开启',
							success(res) {
								if (res.confirm) {
									uni.openSetting({
										success(setting) {
											if (setting.authSetting && setting.authSetting['scope.userLocation']) {
												that.choosingLocation = true
												that.doChooseLocation(type)
											}
										}
									})
								}
							}
						})
					}
				})
			},
			doChooseLocation(type) {
				var that = this
				var finished = false
				var unlock = function() {
					if (finished) return
					finished = true
					that.choosingLocation = false
				}
				// 直接打开选点，避免 getLocation + chooseLocation 连开两次地图
				uni.chooseLocation({
					success(res) {
						unlock()
						var address = (res.name && res.address) ? (res.address + res.name) : (res.address || res.name || '')
						that.shengshiqu(res.longitude, res.latitude, type, address, res.name || '')
					},
					fail(err) {
						unlock()
						if (err && err.errMsg && err.errMsg.indexOf('cancel') !== -1) return
						uni.showToast({
							title: '选点失败，请重试',
							icon: 'none'
						})
					}
				})
				// 部分机型取消选点不回调 fail，超时兜底解锁
				setTimeout(unlock, 60000)
			},
			normalizeAmapField(val) {
				if (Array.isArray(val)) {
					return val.length ? String(val[0]) : ''
				}
				return val ? String(val) : ''
			},
			getCityName(component) {
				var province = this.normalizeAmapField(component.province)
				if (province == '上海市' || province == '北京市' || province == '天津市' || province == '重庆市') {
					return province
				}
				return this.normalizeAmapField(component.city) || province
			},
			// 从 chooseLocation 返回的中文地址解析省市区（不依赖高德，正式版也可用）
			parseRegionFromAddress(address) {
				var text = String(address || '').replace(/\s+/g, '').replace(/^中国/, '')
				var province = ''
				var city = ''
				var district = ''
				if (!text) {
					return {
						province: province,
						city: city,
						district: district
					}
				}
				var m = text.match(/^(北京市|天津市|上海市|重庆市|.*?省|.*?自治区|.*?特别行政区)/)
				if (m) {
					province = m[1]
					text = text.slice(province.length)
				}
				var municipalities = ['北京市', '天津市', '上海市', '重庆市']
				if (municipalities.indexOf(province) !== -1) {
					city = province
				} else {
					m = text.match(/^(.*?市|.*?自治州|.*?地区|.*?盟)/)
					if (m) {
						city = m[1]
						text = text.slice(city.length)
					}
				}
				// 微信常返回「深圳市南山区xxx」无省级前缀
				if (!province && city) {
					province = city
				}
				if (!city && !province) {
					m = text.match(/^(.*?市)(.*?区|.*?县|.*?市)/)
					if (m) {
						city = m[1]
						province = city
						district = m[2]
						return {
							province: province,
							city: city,
							district: district
						}
					}
				}
				m = text.match(/^(.*?区|.*?县|.*?市|.*?旗|.*?镇)/)
				if (m) {
					district = m[1]
				}
				return {
					province: province,
					city: city || province,
					district: district
				}
			},
			buildAddressText(province, city, district, placeName, detailAddress) {
				var region = [province, city, district].filter(Boolean).join(' ')
				var extra = placeName || ''
				// 详细地址与省市区重复时只展示省市区+地点名
				if (!extra && detailAddress) {
					var plain = String(detailAddress).replace(/\s+/g, '')
					var regionPlain = String(region).replace(/\s+/g, '')
					if (plain && plain !== regionPlain && plain.indexOf(regionPlain) === 0) {
						extra = plain.slice(regionPlain.length)
					} else if (plain && plain !== regionPlain) {
						extra = detailAddress
					}
				}
				if (extra && region && String(extra).indexOf(String(province)) === 0) {
					return extra
				}
				return [region, extra].filter(Boolean).join(' ')
			},
			applyChooseRegion(type, longitude, latitude, region, detailAddress, placeName) {
				var province = region.province || ''
				var city = region.city || ''
				var district = region.district || ''
				if (!province) {
					uni.showToast({
						title: '地址解析失败，请重试',
						icon: 'none'
					})
					return false
				}
				detailAddress = detailAddress || [province, city, district].filter(Boolean).join('')
				var showText = this.buildAddressText(province, city, district, placeName, detailAddress)
				if (type == -1) {
					this.shipping_province = province
					this.origincity = city
					this.shipping_area = district
					this.shipping_longitude = longitude
					this.shipping_latitude = latitude
					this.shipping_logistics_address = detailAddress
					// 先清空再赋值，保证同区换点时视图一定会刷新
					this.faAddress = ''
					this.$nextTick(() => {
						this.faAddress = showText
					})
					if (placeName) {
						this.shipping_logistics_park = placeName
					}
					this.setFocus('ship_park')
				} else {
					var item = this.arrivalList[type]
					if (!item) return false
					item.province = province
					item.destination = city
					item.arrival_area = district
					item.arrival_longitude = longitude
					item.arrival_latitude = latitude
					item.arrival_logistics_address = detailAddress
					item.addressText = ''
					if (placeName) {
						item.arrival_logistics_park = placeName
					}
					this.$set(this.arrivalList, type, Object.assign({}, item, {
						addressText: showText
					}))
					this.setFocus(item._key + '_park')
				}
				return true
			},
			shengshiqu(longitude, latitude, type, chooseAddress, placeName) {
				var that = this
				if (!longitude || !latitude) {
					uni.showToast({
						title: '未获取到选点坐标',
						icon: 'none'
					})
					return
				}
				// 优先本地解析：不依赖高德域名/Key，正式版关闭调试也能用
				var localRegion = that.parseRegionFromAddress(chooseAddress)
				if (localRegion.province) {
					that.applyChooseRegion(type, longitude, latitude, localRegion, chooseAddress, placeName)
					return
				}
				uni.request({
					url: 'https://restapi.amap.com/v3/geocode/regeo?output=JSON&location=' +
						longitude + ',' + latitude +
						'&key=b6a2663dc83e037455f2074be7ee0a48&radius=1000&extensions=base',
					success(rex) {
						var data = rex.data
						if (typeof data === 'string') {
							try {
								data = JSON.parse(data)
							} catch (e) {
								data = null
							}
						}
						if (rex.statusCode == 200 && data && String(data.status) === '1' && data.regeocode) {
							var component = data.regeocode.addressComponent || {}
							var province = that.normalizeAmapField(component.province)
							var city = that.getCityName(component)
							var district = that.normalizeAmapField(component.district) || that.normalizeAmapField(component.township)
							var detailAddress = chooseAddress || that.normalizeAmapField(data.regeocode.formatted_address) || ''
							if (province && that.applyChooseRegion(type, longitude, latitude, {
									province: province,
									city: city,
									district: district
								}, detailAddress, placeName)) {
								return
							}
						}
						uni.showToast({
							title: '地址解析失败，请重试',
							icon: 'none'
						})
					},
					fail() {
						uni.showToast({
							title: '地址解析失败，请重试',
							icon: 'none'
						})
					}
				});
			},
			upImage(type) {
				var that = this
				uni.chooseImage({
					count: 1,
					sizeType: ['original', 'compressed'],
					sourceType: ['album', 'camera'],
					success: function(res) {
						that.$upShop('common/upload', res.tempFilePaths[0]).then(rex => {
							let arr = JSON.parse(rex.data)
							if (type == 1) {
								that.business_license = arr.data.url
							} else {
								that.doorway_image = arr.data.url
							}
						}).catch((e) => {});
					}
				})
			},
			tuichu() {
				uni.navigateBack()
			},
			kefu() {
				this.$httpapi('Opinion/customer_mobile', 'POST', {}).then(res => {
					uni.makePhoneCall({
						phoneNumber: res.data
					});
				})
			},
			validateForm() {
				if (!this.faAddress || !this.shipping_province) {
					return '请选择发货地址'
				}
				if (!this.shipping_logistics_park) {
					return '请填写发货物流园'
				}
				if (!this.shipping_logistics_name) {
					return '请填写物流名称'
				}
				if (!this.dailytime) {
					return '请填写每日发货时间'
				}
				if (!this.business_license) {
					return '请上传营业执照'
				}
				if (!this.doorway_image) {
					return '请上传门头照'
				}
				for (let i = 0; i < this.arrivalList.length; i++) {
					let item = this.arrivalList[i]
					let label = this.arrivalList.length > 1 ? ('落货信息' + (i + 1)) : '落货信息'
					if (!item.addressText || !item.province) {
						return '请选择' + label + '的落货地址'
					}
					if (!item.arrival_logistics_park) {
						return '请填写' + label + '的落货物流园名称'
					}
					if (!item.shipping_contact_person) {
						return '请填写' + label + '的发货联系人'
					}
					if (!item.shipping_logistics_mobile) {
						return '请填写' + label + '的发货物流电话'
					}
					if (!item.arrival_logistics_mobile) {
						return '请填写' + label + '的落货物流电话'
					}
					if (!item.time_limit) {
						return '请填写' + label + '的时效'
					}
					if (!item.side) {
						return '请填写' + label + '的每方'
					}
					if (!item.perton) {
						return '请填写' + label + '的每吨'
					}
					if (!item.reflux) {
						return '请填写' + label + '的重泡'
					}
					if (!item.bulky) {
						return '请填写' + label + '的轻泡'
					}
				}
				return ''
			},
			tijiao() {
				let tip = this.validateForm()
				if (tip) {
					uni.showToast({
						title: tip,
						duration: 1500,
						icon: 'none'
					});
					return
				}
				let arrival_list = this.arrivalList.map(item => {
					return {
						province: item.province,
						destination: item.destination,
						arrival_area: item.arrival_area,
						arrival_logistics_park: item.arrival_logistics_park,
						arrival_logistics_address: item.arrival_logistics_address,
						shipping_contact_person: item.shipping_contact_person,
						shipping_logistics_mobile: item.shipping_logistics_mobile,
						arrival_logistics_mobile: item.arrival_logistics_mobile,
						arrival_longitude: item.arrival_longitude,
						arrival_latitude: item.arrival_latitude,
						time_limit: item.time_limit,
						side: item.side,
						perton: item.perton,
						reflux: item.reflux,
						bulky: item.bulky
					}
				})
				// 顶层不再传第一条联系人/电话，避免后端多条时串数据
				this.$httpapi('Dedicated/addsettle', 'POST', {
					shipping_province: this.shipping_province,
					origincity: this.origincity,
					shipping_area: this.shipping_area,
					shipping_logistics_park: this.shipping_logistics_park,
					shipping_logistics_name: this.shipping_logistics_name,
					dailytime: this.dailytime,
					shipping_logistics_address: this.shipping_logistics_address,
					shipping_longitude: this.shipping_longitude,
					shipping_latitude: this.shipping_latitude,
					business_license: this.business_license,
					doorway_image: this.doorway_image,
					edit_shipping_key: this.isEdit ? this.editShippingKey : '',
					mode: this.isEdit ? 'edit' : 'add',
					arrival_list: arrival_list
				}).then(res => {
					uni.showToast({
						title: res.msg,
						duration: 1500,
						icon: 'none'
					});
					if (res.code == 1) {
						setTimeout(() => {
							uni.navigateBack()
						}, 1500);
					}
				})
			},
		}
	}
</script>

<style>
	.req {
		color: #EC0000;
	}

	.form-row {
		display: flex;
		justify-content: space-between;
		align-items: center;
		border-bottom: 2rpx solid #F7F7F7;
		min-height: 104rpx;
	}

	.form-label {
		flex-shrink: 0;
		font-size: 28rpx;
		color: #333;
	}

	.form-value {
		text-align: right;
		width: 460rpx;
		color: #333;
		font-size: 28rpx;
	}

	.form-placeholder {
		color: #999;
	}

	.form-input {
		flex: 1;
		text-align: right;
		font-size: 28rpx;
		height: 104rpx;
		min-width: 0;
	}

	.arrival-actions {
		display: flex;
		align-items: center;
	}

	.copy-prev {
		color: #4878FF;
		font-size: 26rpx;
		margin-right: 24rpx;
	}

	.del-arrival {
		color: #EC0000;
		font-size: 26rpx;
	}

	.rate-grid {
		display: flex;
		flex-wrap: wrap;
		padding: 8rpx 0 16rpx;
	}

	.rate-cell {
		width: 50%;
		box-sizing: border-box;
		padding: 16rpx 12rpx;
		border-bottom: 2rpx solid #F7F7F7;
	}

	.rate-cell:nth-child(odd) {
		padding-right: 20rpx;
		border-right: 2rpx solid #F7F7F7;
	}

	.rate-cell:nth-child(even) {
		padding-left: 20rpx;
	}

	.rate-cell-last,
	.rate-cell:nth-child(3),
	.rate-cell:nth-child(4) {
		border-bottom: none;
	}

	.rate-label {
		font-size: 26rpx;
		color: #666;
		margin-bottom: 8rpx;
	}

	.rate-input {
		width: 100%;
		height: 64rpx;
		font-size: 30rpx;
		font-weight: bold;
		color: #333;
	}

	.add-arrival {
		margin: 0 40rpx;
		height: 80rpx;
		line-height: 80rpx;
		text-align: center;
		border: 2rpx dashed #4878FF;
		border-radius: 40rpx;
		color: #4878FF;
		font-size: 28rpx;
		font-weight: bold;
	}

	.buttons {
		width: 320rpx;
		height: 80rpx;
		background: linear-gradient(180deg, #4897FF 0%, #4878FF 100%);
		border-radius: 40rpx;
		font-weight: bold;
		font-size: 30rpx;
		color: #FFFFFF;
		line-height: 80rpx;
		text-align: center;
	}

	.buttonx {
		width: 320rpx;
		height: 80rpx;
		border: 2rpx solid #4878FF;
		border-radius: 40rpx;
		font-weight: bold;
		font-size: 30rpx;
		line-height: 80rpx;
		text-align: center;
		color: #4878FF;
	}
</style>

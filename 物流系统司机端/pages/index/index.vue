<template>
	<view class="content">
		<view style="width: 750rpx;height: 272rpx;background: linear-gradient( 180deg, #D8EEFC 0%, #F7F8FA 100%);"></view>
		<view style="margin-top:-272rpx;"></view>
		<view style="width: 750rpx;z-index: 9;position: fixed;top:0;left:0;color:#000;"
			:style="scrollTop>0?'background:#FFF;height:'+(statusBarHeight+navigationBarHeight)+'px':''">
			<view :style="'height:'+statusBarHeight+'px'"></view>
			<view class="flexJusp" style="font-weight: bold;font-size: 36rpx;align-items: center;"
				:style="'height:'+navigationBarHeight+'px;line-height:'+navigationBarHeight+'px'">
				<view style="width:100rpx;"></view>
				<view>接单大厅</view>
				<view style="width:100rpx;"></view>
			</view>
			<view class="flexJusp" style="margin:0 28rpx;">
				<!-- <picker mode="region" style="width:50%;text-align: center;">
					<view style="text-align: center;" @click="didian">
						<view>{{address||"地点"}}</view>
					</view>
				</picker> --> 
				<!-- RegionSelection -->
				<picker mode="multiSelector" style="text-align: center;width:50%;" :range="[shengshi,lists]" :value="indexx" @change="bindPickerChange" range-key="name" @columnchange="columnchanges">
					<!-- @click="chengshi" -->
					<view>{{city||'地点'}}</view>
				</picker>
				
			<!-- 	<view style="text-align: center;width:50%;" @click="RegionSelection">
					<view class="yihang">{{address||"地点"}}</view>
				</view> -->
				
				<picker @change="bindPickerChange" :value="indexs" :range="listZhuang" style="width:50%;text-align: center;" >
					<view v-if="indexs==0">装货时间</view>
					<view v-else>{{listZhuang[indexs]}}</view>
				</picker>
		
			</view>
		</view>
		 
		<!-- <yaor-address :show="addressShow" :selectlist="allArr" @transmit="sureAddress" @close="closeAddressShow"></yaor-address> -->
			
		<view :style="'height:'+(statusBarHeight+navigationBarHeight+28)+'px'"></view>
		<view v-for="(item,index) in list" :key="index" style="background: #FFFFFF;border-radius: 24rpx;padding:24rpx;margin:20rpx;">
			<view class="flexJusp" @click="details(item)">
				<view style="color: #666666;">装车时间：{{item.time_range}}</view>
				<view style="color: #666666;">{{item.distance}} KM</view>
				
				<view style="color: #F29100;">{{item.thistime}}</view>
			</view>
			
			<view class="flex" style="align-items: center;padding:24rpx 0;" @click="details(item)">
				<view
					style="width: 48rpx;height: 48rpx;background: #F29100;border-radius: 12rpx;font-weight: bold;color: #FFFFFF;line-height: 48rpx;text-align: center;margin-right:12rpx;">
					装</view>
					
				<!-- <view style="width: 16rpx;height: 16rpx;background: #F29100;border-radius:50%;margin-right: 12rpx;"></view> -->
				<view style="width:600rpx;font-weight: bold;font-size: 30rpx;">{{item.loading_address}}</view>
			</view>
			<view class="flex" style="align-items: center;" @click="details(item)">
				<view
					style="width: 48rpx;height: 48rpx;background: #4878FF;border-radius: 12rpx;font-weight: bold;color: #FFFFFF;line-height: 48rpx;text-align: center;margin-right:12rpx;">
					卸</view>
					
				<!-- <view style="width: 16rpx;height: 16rpx;background: #4878FF;border-radius:50%;margin-right: 12rpx;"></view> -->
				<view style="width:630rpx;font-weight: bold;font-size: 30rpx;">{{ item.unload_address}}</view>
			</view>
			
			<!-- <view class="flex" @click="details(item)" style="align-items: center;margin:20rpx 0;font-size:30rpx;font-weight: bold;">
				<view>{{item.loading}}</view>
				<image style="width:32rpx;height:32rpx;margin:0 20rpx;" src="/static/images/icon_36j.png"></image>
				<view>{{item.unload}}</view>
			</view> -->
			<view @click="details(item)" style="background: #F0F7FF;border-radius: 16rpx;padding:20rpx 24rpx;font-size: 24rpx;color: #666666;margin-top:20rpx;">
				<view>{{item.find_car_type}}  {{item.goods_type_id}}  {{item.packaging_id}}</view>
			</view>
			<view class="flexJusp" style="margin-top:20rpx;">
				
				<view style="font-weight: 800;font-size: 30rpx;color: #EC0000;" v-if="item.type==1">￥{{item.pickup_driver_fee||'未定义价格'}}</view>
				<view style="font-weight: 800;font-size: 30rpx;color: #EC0000;" v-if="item.type==2">￥{{item.logistics_driver_cost||'未定义价格'}}</view>
				<view style="font-weight: 800;font-size: 30rpx;color: #EC0000;" v-if="item.type==3">￥{{item.shipment_driver_fee||'未定义价格'}}</view>
				<view class="buttons" @click="qiangdan(item)">抢单</view>
			</view>
		</view>
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
				scrollTop:0,
				types:0,
				list:[],
				address:'',
				time:'',
				listZhuang:['全部','今天','明天','明天以后'],
				indexs:0,
				allArr: [],
				addressShow: false,
				nodeData:[[],[],[]],
				shengshi: [{
					name: '北京市',
					list: ['北京市']
				}, {
					name: '天津市',
					list: ['天津市']
				}, {
					name: '河北省',
					list: ['石家庄市', '唐山市', '秦皇岛市', '邯郸市', '邢台市', '保定市', '张家口市', '承德市', '沧州市', '廊坊市', '衡水市']
				}, {
					name: '山西省',
					list: ['太原市', '大同市', '阳泉市', '长治市', '晋城市', '朔州市', '晋中市', '运城市', '忻州市', '临汾市', '吕梁市']
				}, {
					name: '辽宁省',
					list: ['沈阳市', '大连市', '鞍山市', '抚顺市', '本溪市', '丹东市', '锦州市', '营口市', '阜新市', '辽阳市', '盘锦市', '铁岭市',
						'朝阳市', '葫芦岛市'
					]
				}, {
					name: '吉林省',
					list: ['长春市', '吉林市', '四平市', '辽源市', '通化市', '白山市', '松原市', '白城市', '延边朝鲜族自治州']
				}, {
					name: '黑龙江省',
					list: ['哈尔滨市', '齐齐哈尔市', '鸡西市', '鹤岗市', '双鸭山市', '大庆市', '伊春市', '佳木斯市', '七台河市', '牡丹江市', '黑河市',
						'绥化市', '大兴安岭地区'
					]
				}, {
					name: '上海市',
					list: ['上海市']
				}, {
					name: '江苏省',
					list: ['南京市', '无锡市', '徐州市', '常州市', '苏州市', '南通市', '连云港市', '淮安市', '盐城市', '扬州市', '镇江市', '泰州市',
						'宿迁市'
					]
				}, {
					name: '浙江省',
					list: ['杭州市', '宁波市', '温州市', '嘉兴市', '湖州市', '绍兴市', '金华市', '衢州市', '舟山市', '台州市', '丽水市']
				}, {
					name: '安徽省',
					list: ['合肥市', '芜湖市', '蚌埠市', '淮南市', '马鞍山市', '淮北市', '铜陵市', '安庆市', '黄山市', '滁州市', '阜阳市', '宿州市',
						'巢湖市', '六安市', '亳州市', '池州市', '宣城市'
					]
				}, {
					name: '福建省',
					list: ['福州市', '厦门市', '莆田市', '三明市', '泉州市', '漳州市', '南平市', '龙岩市', '宁德市']
				}, {
					name: '江西省',
					list: ['南昌市', '景德镇市', '萍乡市', '九江市', '新余市', '鹰潭市', '赣州市', '吉安市', '宜春市', '抚州市', '上饶市']
				}, {
					name: '山东省',
					list: ['济南市', '青岛市', '淄博市', '枣庄市', '东营市', '烟台市', '潍坊市', '济宁市', '泰安市', '威海市', '日照市', '临沂市',
						'德州市', '聊城市', '滨州市', '菏泽市'
					]
				}, {
					name: '河南省',
					list: ['郑州市', '开封市', '洛阳市', '平顶山市', '安阳市', '鹤壁市', '新乡市', '焦作市', '濮阳市', '许昌市', '漯河市', '三门峡市',
						'南阳市', '商丘市', '信阳市', '周口市', '驻马店市'
					]
				}, {
					name: '湖北省',
					list: ['武汉市', '黄石市', '十堰市', '宜昌市', '襄阳市', '鄂州市', '荆门市', '孝感市', '荆州市', '黄冈市', '咸宁市', '随州市',
						'恩施土家族苗族自治州'
					]
				}, {
					name: '湖南省',
					list: ['长沙市', '株洲市', '湘潭市', '衡阳市', '邵阳市', '岳阳市', '常德市', '张家界市', '益阳市', '郴州市', '永州市', '怀化市',
						'娄底市', '湘西土家族苗族自治州'
					]
				}, {
					name: '广东省',
					list: ['广州市', '深圳市', '珠海市', '汕头市', '佛山市', '韶关市', '湛江市', '肇庆市', '江门市', '茂名市', '惠州市', '梅州市',
						'汕尾市', '河源市', '阳江市', '清远市', '东莞市', '中山市', '潮州市', '揭阳市', '云浮市'
					]
				}, {
					name: '海南省',
					list: ['海口市', '三亚市', '三沙市', '儋州市']
				}, {
					name: '重庆市',
					list: ['重庆市']
				}, {
					name: '四川省',
					list: ['成都市', '自贡市', '攀枝花市', '泸州市', '德阳市', '绵阳市', '广元市', '遂宁市', '内江市', '乐山市', '资阳市', '宜宾市',
						'南充市', '达州市', '雅安市', '巴中市', '眉山市', '广安市', '阿坝藏族羌族自治州', '甘孜藏族自治州', '凉山彝族自治州'
					]
				}, {
					name: '贵州省',
					list: ['贵阳市', '六盘水市', '遵义市', '安顺市', '毕节地区', '铜仁地区', '黔西南布依族苗族自治州', '黔西南布依族苗族自治州', '黔南布依族苗族自治州']
				}, {
					name: '云南省',
					list: ['昆明市', '曲靖市', '玉溪市', '保山市', '昭通市', '丽江市', '普洱市', '临沧市', '楚雄彝族自治州', '红河哈尼族彝族自治州',
						'文山壮族苗族自治州', '西双版纳傣族自治州', '大理白族自治州', '德宏傣族景颇族自治州', '怒江傈僳族自治州', '迪庆藏族自治州'
					]
				}, {
					name: '陕西省',
					list: ['西安市', '铜川市', '宝鸡市', '咸阳市', '渭南市', '延安市', '汉中市', '榆林市', '安康市', '商洛市']
				}, {
					name: '甘肃省',
					list: ['兰州市', '嘉峪关市', '金昌市', '白银市', '天水市', '武威市', '张掖市', '平凉市', '酒泉市', '庆阳市', '定西市', '陇南市',
						'临夏回族自治州', '甘南藏族自治州'
					]
				}, {
					name: '青海省',
					list: ['西宁市', '海东地区', '海北藏族自治州', '黄南藏族自治州', '海南藏族自治州', '果洛藏族自治州', '玉树藏族自治州', '海西蒙古族藏族自治州']
				}, {
					name: '内蒙古自治区',
					list: ['呼和浩特市', '包头市', '乌海市', '赤峰市', '通辽市', '鄂尔多斯市', '呼伦贝尔市', '巴彦淖尔市', '乌兰察布市', '兴安盟', '锡林郭勒盟',
						'阿拉善盟'
					]
				}, {
					name: '广西壮族自治区',
					list: ['南宁市', '柳州市', '桂林市', '梧州市', '北海市', '防城港市', '钦州市', '贵港市', '玉林市', '百色市', '贺州市', '河池市',
						'来宾市', '崇左市'
					]
				}, {
					name: '西藏自治区',
					list: ['拉萨市', '日喀则地区', '昌都地区', '山南地区', '那曲地区', '阿里地区', '林芝地区']
				}, {
					name: '宁夏回族自治区',
					list: ['银川市', '石嘴山市', '吴忠市', '固原市', '中卫市']
				}, {
					name: '新疆维吾尔自治区',
					list: ['乌鲁木齐市', '克拉玛依市', '吐鲁番地区', '哈密地区', '阿克苏地区', '喀什地区', '和田地区', '塔城地区', '阿勒泰地区', '昌吉回族自治州',
						'博尔塔拉蒙古自治州', '巴音郭楞蒙古自治州', '克孜勒苏柯尔克孜自治州', '伊犁哈萨克自治州'
					]
				}],
				lists: [],
				indexx: [0, 0],
				latitude:'',
				longitude:'',
				city:''
			}
		},
		onLoad() {
			this.getSystemInfo()
		},
		onPullDownRefresh() {
			this.list = []
			this.loads()
		},
		onShow() {
			var that = this
			this.lists = []
			for (let i = 0; i < this.shengshi[0].list.length; i++) {
				this.lists.push({
					name: this.shengshi[0].list[i]
				})
			}
			uni.getLocation({
				type: 'gcj02', //返回可以用于uni.openLocation的经纬度
				isHighAccuracy: true, // 开启高精度模式
				highAccuracyExpireTime: 5000,
				success: (res) => {
					that.latitude = res.latitude
					that.longitude = res.longitude
					uni.request({
						header: {
							"Content-Type": "application/text"
						},
						url: 'https://restapi.amap.com/v3/geocode/regeo?output=JSON&location=' +
							res.longitude + ',' + res.latitude +
							'&key=b6a2663dc83e037455f2074be7ee0a48&radius=1000&extensions=base',
						success(rex) {
							if (rex.statusCode == 200) {
								if (rex.data.regeocode.addressComponent.province == '上海市') {
									that.city = '上海市'
								} else if (rex.data.regeocode.addressComponent.province == '北京市') {
									that.city = '北京市'
								} else if (rex.data.regeocode.addressComponent.province == '天津市') {
									that.city = '天津市'
								} else {
									that.city = rex.data.regeocode.addressComponent.city
								}
								for (let i = 0; i < that.shengshi.length; i++) {
									const hasMatch = that.shengshi[i].list.some(item => item.includes(that.city));
									const indexs = that.shengshi[i].list.findIndex(item => item.includes(that.city));
									if (hasMatch) {
										that.qiehuanShi(i)
										that.indexx = [i, indexs]
									}
								}
								that.loads()
								that.$forceUpdate()
							}else{
								that.loads()
							}
						}
					});
				},
				fail() {
					that.loads()
				}
			});
			
		},
		onPageScroll(event) {
			const scrollTop = event.scrollTop // 获取当前页面滚动高度
			this.scrollTop = event.scrollTop
		},
		methods: {
			qiehuanShi(index) {
				this.lists = []
				for (let i = 0; i < this.shengshi[index].list.length; i++) {
					this.lists.push({
						name: this.shengshi[index].list[i]
					})
				}
			},
			bindPickerChange(e) {
				var that = this
				that.indexx = e.detail.value
				that.city = that.lists[e.detail.value[1]].name
				this.loads()
			},
			columnchanges(e) {
				if (e.detail.column == 0) {
					this.lists = []
					for (let i = 0; i < this.shengshi[e.detail.value].list.length; i++) {
						this.lists.push({
							name: this.shengshi[e.detail.value].list[i]
						})
					}
					this.indexx = [e.detail.value, 0]
				}
				this.$forceUpdate()
				console.log(e, '----------')
			},
			huoqu(e){
				console.log(e,'000000获取')
			},
			RegionSelection(){
				var that = this
				uni.setStorageSync('address', this.address.split(','))
				uni.navigateTo({
					url:'/pages/index/RegionSelection/RegionSelection',
					events: {
						acceptDataFromOpenedPages: (data) => {
							console.log(data,'------;;;;;')
							if(data){
								that.address = data.toString()
								that.loads()
							}
						}
					}
				})
			},
			// bindPickerChange(e){
			// 	this.indexs = e.detail.value
			// 	this.loads()
			// },
			details(item){
				uni.navigateTo({
					url:'/pages/index/orderDetail/orderDetail?id='+item.orderid
				})
			},
			qiangdan(item){
				// 检查是否登录
				if (!uni.getStorageSync('token')) {
					uni.showModal({
						title: '请登录',
						content: '您需要登录后才能抢单',
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
				this.$httpapi('Dricer/addorder', 'POST', {
					order_id:item.orderid
				}).then(res => {
					uni.showToast({
						title: res.msg,
						duration: 1500,
						icon:'none'
					});
					uni.switchTab({
						url:'/pages/order/order'
					})
				})
			},
			quxiao(item){
				this.$httpapi('Placeorder/CancelOrder', 'POST', {order_id:item.orderid}).then(res => {
					uni.showToast({
						title: res.msg,
						duration: 1500,
						icon:'none'
					});
					this.loads()
				})
			},
			fukuan(item){
				
			},
			qiehuans(type){
				this.types = type
				this.loads()
			},
			loads() {
				// 首页不判断登录，如果未登录则不加载订单列表，避免弹出登录提示
				if (!uni.getStorageSync('token')) {
					this.list = []
					uni.stopPullDownRefresh()
					return
				}
				// this.address
				this.$httpapi('Dricer/OrderList', 'POST', {
					address:this.city,
					time:this.indexs
				}).then(res => {
					this.list = res.data
					uni.stopPullDownRefresh()
				}).catch(err => {
					// 首页加载订单列表时，如果出错则静默处理，不弹出登录提示
					this.list = []
					uni.stopPullDownRefresh()
				})
			}
		}
	}
</script>

<style>
	.xian {
		margin:4rpx auto 0;
		width: 28rpx;
		height: 6rpx;
		background: linear-gradient( 180deg, #4897FF 0%, #4878FF 100%);
		border-radius: 4rpx;
	}

	.buttons {
		width: 138rpx;
		height: 52rpx;
		background: linear-gradient( 180deg, #4897FF 0%, #4878FF 100%);
		border-radius: 26rpx;
		font-size: 26rpx;
		color: #FFFFFF;
		line-height: 52rpx;
		text-align: center;
	}
	.buttonx{
		width: 138rpx;
		height: 52rpx;
		background: #FFFFFF;
		border-radius: 26rpx;
		border: 1rpx solid #CCCCCC;
		font-size: 26rpx;
		color: #666666;
		line-height: 52rpx;
		text-align: center;
	}
</style>
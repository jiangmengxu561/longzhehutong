
var utils = {
	baseUrl: 'https://lzwl.longzhehutong.cn/api/', //域名 线上
	imgUrl:'https://lzwl.longzhehutong.cn',//图片
	// https://sc701.chuchuliuxin.top
	upload: 'https://lzwl.longzhehutong.cn/api/common/upload', //上传地址
	//正则
	isVideo: /(mp4|mp3|flv|wav)$/ig, //是否是视频
	isPic: /(gif|jpg|jpeg|png|GIF|JPG|PNG)$/ig, //是否是图片
	isUtf8: /^([u4E00-u9FA5]|[uFE30-uFFA0])*$/, //是否是中文
	isMobile: /^(((1[3456789][0-9]{1})|(15[0-9]{1}))+\d{8})$/, //是否是手机号
	isIdCard: /^[1-9][0-9]{5}(19|20)[0-9]{2}((01|03|05|07|08|10|12)(0[1-9]|[1-2][0-9]|31)|(04|06|09|11)(0[1-9]|[1-2][0-9]|30)|02(0[1-9]|[1-2][0-9]))[0-9]{3}([0-9]|x|X)$/, //是否是身份证号
	navBarHeight(){
		// #ifdef MP
		let {top,height,bottom} = uni.getMenuButtonBoundingClientRect();
		let wrap = bottom - height - uni.getSystemInfoSync().statusBarHeight;
		return bottom+wrap+'px';
		// #endif
		// #ifdef APP
		return uni.getSystemInfoSync().statusBarHeight + 44 + 'px';
		// #endif
	},
	//打电话
	callPhone(phone){
		uni.makePhoneCall({
			phoneNumber:phone,
			complete(e) {
			}
		})
	},
	//窗口高度 
	windowHeight(){
		return uni.getWindowInfo().windowHeight+'px';
	},
	checkFile(fileValue) {
	    var index = fileValue.lastIndexOf("."); //（考虑严谨用lastIndexOf(".")得到）得到"."在第几位
	    let fileValueSuffix = fileValue.substring(index); //截断"."之前的，得到后缀
	        if (/(.*)\.(mp4|avi|wmv|MP4|AVI|WMV|HEVC|hevc)$/.test(fileValueSuffix)) { //根据后缀，判断是否符合视频格式
	            return 'video';
	        }else  if(/(.*)\.(jpg|JPG|bmp|BMP|mpg|MPG|mpeg|MPEG|tis|TIS)$/.test(fileValueSuffix)) { //根据后缀，判断是否符合图片格式
	            return 'image';
	        }else if (/(.*)\.(xls|XLS|xlsx|XLSX|doc|DOC|docx|DOCX|pdf|PDF|PPT|PPTX|ppt|pptx)$/.test(fileValueSuffix)) { //根据后缀，判断是否符合OFFICE格式
	            return 'office';
	        }
	    return false;
	},
	goBacks() {
		if(getCurrentPages().length==1){
			uni.switchTab({
				url: '/pages/tabbar/home'
			})
		}else{
			uni.navigateBack({
				delta: 1
			})
		}
	},
	showtt: function(add1, add2) {
		uni.showToast({
			title: add1,
			duration: 1000,
			icon: add2 ? add2 : 'none'
		})
	},
	//预览图片
	previewImage(e) {
		//预览图片
		uni.previewImage({
			urls: Array(e)
		});

	},
	// 跳转下一页
	goNext(url) {
		uni.navigateTo({
			url: url
		})
	},
	// 返回上一页
	goBack(num) {
		uni.navigateBack({
			delta: num
		})
	},
	// 跳转tabar页面
	goSwitchTab(url) {
		uni.switchTab({
			url: url
		})
	},
	// 关闭所有页面，跳转到指定页面
	goReLaunch(url) {
		uni.reLaunch({
			url: url
		})
	},
	// 关闭当前页，跳转下一页
	goRedirectTo(url) {
		uni.redirectTo({
			url: url
		})
	},

	//判断是都有token
	//请求格式 method POST GET
	//请求地址 url
	//请求参数 data
	//加载中的框  showLoads
	axiosToken: function(method, url, data, showLoads) {
		if (!uni.getStorageSync('token')) {
			uni.clearStorageSync()
			uni.showToast({
				title: '请先登录',
				duration: 1000,
				icon: 'none'
			})
			setTimeout(() => {
				uni.navigateTo({
					url: '/pages/login/login'
				})
			}, 1000)
			return
		}
		if (showLoads) {
			uni.showLoading({
				'title': showLoads
			})
		}; //加载中的框
		return new Promise((resolve, reject) => {
			uni.request({
				url: this.baseUrl + url, //请求地址 url
				method: method, //请求格式 method POST GET
				data: data, //请求参数 data
				header: {
					'content-Type': 'application/x-www-form-urlencoded',
					'token': uni.getStorageSync('token')
				},
				success: (res) => {
					if (showLoads) {
						uni.hideLoading()
					}
					resolve(res.data)
				},
				fail: (err) => {
					reject(err)
				},
				complete(result) {
					if(result.data.code==401){
						uni.clearStorageSync()
						uni.showToast({
							title: '请重新登录',
							duration: 1000,
							icon: 'none'
						})
						setTimeout(() => {
							uni.navigateTo({
								url: '/pages/login/login'
							})
						}, 1000)
					}
				}
			})
		})
	},

	//请求格式 method POST GET
	//请求地址 url
	//请求参数 data
	//加载中的框  showLoads
	axios: function(method, url, data, showLoads) {
		if (showLoads) {
			uni.showLoading({
				'title': showLoads
			})
		}; //加载中的框
		return new Promise((resolve, reject) => {
			uni.request({
				url: this.baseUrl + url, //请求地址 url
				method: method, //请求格式 method POST GET
				data: data, //请求参数 data
				header: {
					'content-Type': 'application/x-www-form-urlencoded'
				},
				success: (res) => {
					if (showLoads) {
						uni.hideLoading()
					}
					resolve(res.data)
				},
				fail: (err) => {
					reject(err)
				},
				complete() {

				}
			})
		})
	},
	axiosImg: function(method, url, data, showLoads) {
		if (showLoads) {
			uni.showLoading({
				'title': showLoads
			})
		}; //加载中的框
		return new Promise((resolve, reject) => {
			uni.request({
				url: this.baseUrl + url, //请求地址 url
				method: method, //请求格式 method POST GET
				data: {
					file:data
				}, //请求参数 data
				header: {
					'content-Type': 'application/x-www-form-urlencoded'
				},
				success: (res) => {
					if (showLoads) {
						uni.hideLoading()
					}
					resolve(res.data)
				},
				fail: (err) => {
					reject(err)
				},
				complete() {

				}
			})
		})
	},

	// 判断邮箱
	isEmail(email) {
		if (email.search(/^\w+((-\w+)|(\.\w+))*\@[A-Za-z0-9]+((\.|-)[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/) != -1)
			return true;
		else
			return false;
	},
	// 时间戳转时间
	YMDHIS(timestamp) {
		var date = new Date(timestamp); //时间戳为10位需*1000，时间戳为13位的话不需乘1000
		var Y = date.getFullYear() + '-';
		var M = (date.getMonth() + 1 < 10 ? '0' + (date.getMonth() + 1) : date.getMonth() + 1) + '-';
		var D = (date.getDate() < 10 ? '0' + date.getDate() : date.getDate()) + ' ';
		var h = (date.getHours() < 10 ? '0' + date.getHours() : date.getHours()) + ':';
		var m = (date.getMinutes() < 10 ? '0' + date.getMinutes() : date.getMinutes()) + ':';
		var s = (date.getSeconds() < 10 ? '0' + date.getSeconds() : date.getSeconds());
		return Y + M + D + h + m + s;
	},
	// 时间戳转时间
	MDHI(timestamp) {
		var date = new Date(timestamp); //时间戳为10位需*1000，时间戳为13位的话不需乘1000
		var M = (date.getMonth() + 1 < 10 ? '0' + (date.getMonth() + 1) : date.getMonth() + 1) + '-';
		var D = (date.getDate() < 10 ? '0' + date.getDate() : date.getDate()) + ' ';
		var h = (date.getHours() < 10 ? '0' + date.getHours() : date.getHours()) + ':';
		var m = (date.getMinutes() < 10 ? '0' + date.getMinutes() : date.getMinutes())
		return M + D + h + m;
	},
	// 时间戳转时间
	YMD(timestamp) {
		var date = new Date(timestamp); //时间戳为10位需*1000，时间戳为13位的话不需乘1000
		var Y = date.getFullYear() + '-';
		var M = (date.getMonth() + 1 < 10 ? '0' + (date.getMonth() + 1) : date.getMonth() + 1) + '-';
		var D = (date.getDate() < 10 ? '0' + date.getDate() : date.getDate());
		return Y + M + D;
	},
	// 判断密码是否符合 至少6位,包括大小写字母、数字、特殊字符
	isPassword(val) {
		var reg = /^(?![0-9]+$)(?![a-z]+$)(?![A-Z]+$)(?!([^(0-9a-zA-Z)])+$)^.{8,16}$/;
		if (val === '') {
			uni.showToast({
				title: '请输入密码',
				icon: 'none'
			})
		} else if (!reg.test(val)) {
			uni.showToast({
				title: '至少6位,包括大小写字母、数字、特殊字符',
				icon: 'none'
			})
			return false
		} else {
			return true
		}
	},
}

export default utils;

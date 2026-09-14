const URL = "https://lzwl.longzhehutong.cn/index.php/api/";
const URLs = "https://lzwl.longzhehutong.cn/";
// const URL = "http://192.168.1.62:8061";

// https://lzwl.longzhehutong.cn/JdMbgLEDeK.php

// https://docs.apipost.net/docs/4f7dca227483000?locale=zh-cn

export function apiapi(url, method, data) {
	return new Promise((resolve, reject) => {
		data.token = uni.getStorageSync('token')
		uni.request({
			url: URL + url, //真实接口地址。
			method: method || "GET", //请求的方式
			data: data, //参数
			header: {
				// 'custom-header': tou//自定义请求头信息
				'Content-Type': 'application/json;charset=utf-8;',
				'Authori-zation': 'Bearer ' + uni.getStorageSync('token'),
				'token':uni.getStorageSync('token')
			},
			// 成功使用resolve
			success: (res) => {
				resolve(res.data)
				if (res.data.msg == '请登录' & res.data.code == 0 ) {
					uni.setStorageSync('token', '')
					uni.showModal({
						title: '请登录!',
						content: '暂未登录，无法进行下一步操作',
						confirmText: '去登录',
						success: function(res) {
							if (res.confirm) {
								uni.navigateTo({
									url: '/pages/login/login'
								})
							} else if (res.cancel) {
								console.log('用户点击取消');
							}
						}
					});
				}
				if (res.data.msg == '请登录后操作' & res.data.code == 500) {
					uni.setStorageSync('id', '')
					uni.showModal({
						title: '请登录!',
						content: '暂未登录，无法进行下一步操作',
						confirmText: '去登录',
						success: function(res) {
							if (res.confirm) {
								uni.navigateTo({
									url: '/pages/login/login'
								})
							} else if (res.cancel) {
								console.log('用户点击取消');
							}
						}
					});
				}
				if (res.data.code == 500) {
					uni.showToast({
						title: res.data.msg,
						icon: 'none',
						success: () => {}
					})
					return
				}
			},
			//时报调用reject
			fail: (err) => {
				reject(err)
			}
		});
		// } else {
		// 	uni.showToast({
		// 		title: '请重新登录',
		// 		icon: 'none',
		// 		success: () => {
		// 			setTimeout(function() {
		// 				uni.reLaunch({
		// 					url: '/pages/login/login'
		// 				})
		// 			}, 1500)
		// 		}
		// 	})
		// }
		// 		}
		// 	})
	})
}
export function istoken() {
	console.log('in istoken')
	return new Promise((resolve, reject) => {
		uni.getStorage({
			key: 'token',
			success(res) {
				console.log(res)
				if (res.data == '') {
					reject()
					console.log('res no')
					uni.navigateTo({
						url: '/pages/login/login'
					})
				} else {
					resolve(res)
					console.log('yes')
				}
			},
			fail(err) {
				console.log('err no')
				reject(err)
				uni.navigateTo({
					url: '/pages/login/login'
				})
			}
		})
	})
}
// 下载文件
export function down(name, src) {
	uni.showModal({
		title: '是否下载文件',
		content: name,
		cancelText: '确定',
		confirmText: '取消',
		success: res => {
			if (res.cancel == true) {
				// console.log(src);
				const downloadTask = uni.downloadFile({
					url: src,
					success: res => {
						// console.log(res);
						plus.nativeUI.closeWaiting();
						if (res.statusCode === 200) {
							uni.showModal({
								content: '下载成功',
								showCancel: false,
								success() {
									uni.hideLoading();
								}
							})
						} else {
							uni.showModal({
								content: '无文件或文件丢失',
								showCancel: false,
								success() {
									uni.hideLoading();
								}
							})
						}
						console.log(res)
						var tempFilePaths = res.tempFilePath;
						uni.saveFile({
							tempFilePath: tempFilePaths,
							success: function(res) {
								console.log(res)
								var savedFilePath = res.savedFilePath;
								uni.openDocument({
									filePath: savedFilePath,
									success(res) {
										console.log(res)
									}
								})
								uni.getSavedFileList({
									success(res) {
										console.log(res)
									}
								})
							},
							fail(err) {
								console.log(err)
							}
						});
					}
				});
				// uni.showLoading({
				// 	title:'正在下载:' + this.progress + '%',
				// 	mask:true
				// })
				var showLoading = plus.nativeUI.showWaiting("正在下载")
				// setInterval(
				downloadTask.onProgressUpdate((res) => {
					// this.progress = res.progress
					// console.log(1)
					// console.log('下载进度' + res.progress);
					showLoading.setTitle("  正在下载" + res.progress + "%  ");
					// console.log('已经下载的数据长度' + res.totalBytesWritten);
					// console.log('预期需要下载的数据总长度' + res.totalBytesExpectedToWrite);
				})
				// ),50000)

			}
		}
	});
}

export function upShop(url, file) {
	// 图片上传
	return new Promise((resolve, reject) => {
		let path = file;
		// #ifdef MP-WEIXIN
		uni.showLoading({
			title: '请稍后'
		})
		// #endif
		let that = this;
		// #ifdef APP-PLUS
		const uploadTask =
			// #endif
			// dir
			uni.uploadFile({
				url: URL + url,
				filePath: path,
				fileType: 'file',
				name: 'file',
				header: {
					// #ifdef MP
					"Content-Type": "multipart/form-data",
					// #endif
					'Authori-zation': 'Bearer ' + uni.getStorageSync('token')
				},
				formData: {
					'file': 'pics'
				},
				success: (uploadFileRes) => {
					// let imgSrc = JSON.parse(uploadFileRes.data).result.substring(1)
					if (uploadFileRes.statusCode == 200) {
						// 保存上传图片返回值
						// that.upLoadImg.push(imgSrc);
						var data = JSON.parse(uploadFileRes.data)
						resolve(uploadFileRes)
						if (data.status == 200) {
							uni.showToast({
								title: data.msg,
								icon: 'none',
								success: () => {}
							})
							return
						} else if (data.status == 410000 || data.status == 13001) {
							uni.setStorageSync('token', '')
							uni.showModal({
								title: '请登录!',
								content: '暂未登录，无法进行下一步操作',
								confirmText: '去登录',
								success: function(res) {
									if (res.confirm) {
										uni.navigateTo({
											url: '/pages/login/login/index'
										})
									} else if (res.cancel) {
										console.log('用户点击取消');
									}
								}
							});
						}
					} else {
						reject(uploadFileRes.data)
					}
					// #ifdef APP-PLUS
					plus.nativeUI.closeWaiting();
					// #endif
					// #ifdef MP-WEIXIN
					uni.hideLoading()
					// #endif
					resolve(uploadFileRes)
					// console.log(uploadFileRes)
				},
				error: (err) => {
					reject(err)
					// console.log(resa)
				}
			});

	})
}
// 上传文件
export function up(url, file, type, typex) {
	// 图片上传
	return new Promise((resolve, reject) => {
		uni.getStorage({
			key: 'token',
			success(res) {
				let path = file;
				// #ifdef MP-WEIXIN
				uni.showLoading({
					title: '请稍后'
				})
				// if(url!='videos'){
				// 	path = file.path;
				// }
				// #endif
				// console.log(file)
				let token = ""
				let types = ""
				if (type == 1) {
					token = uni.getStorageSync('seller_token')
					types = "goods_video"
				} else if (type == 2) {
					token = uni.getStorageSync('seller_token')
					types = "goods_body_image"
				} else if (type == 3) {
					token = uni.getStorageSync('seller_token')
					types = "articleshop_image"
				} else {
					token = uni.getStorageSync('token');
				}
				// console.log(file)
				let that = this;
				// that.upLoadImg = [];
				// let hd = this.getHeader();
				// #ifdef APP-PLUS
				const uploadTask =
					// #endif
					uni.uploadFile({
						url: URL + url,
						filePath: path,
						name: 'file',
						header: {
							"token": token,
							// "sign": hd.sign,
							// "requestTime": hd.time,
							"X-DS-KEY": token
						},
						formData: {
							// type_id: 2
							name: "file",
							type: types ? types : 'registerimage'
						},
						success: (uploadFileRes) => {
							// console.log('------',uploadFileRes)
							// let imgSrc = JSON.parse(uploadFileRes.data).result.substring(1)
							// console.log(JSON.parse(uploadFileRes.data));
							if (uploadFileRes.code == 200) {
								// 保存上传图片返回值
								// that.upLoadImg.push(imgSrc);
								resolve(JSON.parse(uploadFileRes.data))
							} // else {
							// 	reject()
							// }
							// #ifdef APP-PLUS
							plus.nativeUI.closeWaiting();
							// #endif
							// #ifdef MP-WEIXIN
							uni.hideLoading()
							// #endif
							resolve(uploadFileRes)
							// console.log(uploadFileRes)
						},
						error: (err) => {
							reject(err)
							// console.log(resa)
						}
					});
				// #ifdef APP-PLUS
				var showLoading = plus.nativeUI.showWaiting("正在上传");
				uploadTask.onProgressUpdate((res) => {
					// console.log('上传进度' + res.progress);
					// console.log('已经上传的数据长度' + res.totalBytesSent);
					// console.log('预期需要上传的数据总长度' + res.totalBytesExpectedToSend);
					showLoading.setTitle("  正在上传" + res.progress + "%  ");
					// 测试条件，取消上传任务。
					// if (res.progress > 50) {
					// uploadTask.abort();
					// }
				});
				// #endif


			}
		})

	})
}
export function uphead(url, file) {
	// 图片上传
	return new Promise((resolve, reject) => {
		uni.getStorage({
			key: 'token',
			success(res) {
				let path = file;
				let that = this;
				// #ifdef APP-PLUS
				const uploadTask =
					// #endif
					uni.uploadFile({
						url: URL + url,
						filePath: path,
						name: 'memberavatar',
						header: {
							"token": uni.getStorageSync('token'),
							"X-DS-KEY": uni.getStorageSync('token')
						},
						formData: {
							name: "memberavatar"
						},
						success: (uploadFileRes) => {
							// if (uploadFileRes.code == 200) {
							// 	// 保存上传图片返回值
							// 	resolve(JSON.parse(uploadFileRes.data))
							// }
							// #ifdef APP-PLUS
							plus.nativeUI.closeWaiting();
							// #endif
							// resolve(uploadFileRes)
							resolve(JSON.parse(uploadFileRes.data))
						},
						error: (err) => {
							reject(err)
						}
					});
				// #ifdef APP-PLUS
				var showLoading = plus.nativeUI.showWaiting("正在上传");
				uploadTask.onProgressUpdate((res) => {
					// console.log('上传进度' + res.progress);
					// console.log('已经上传的数据长度' + res.totalBytesSent);
					// console.log('预期需要上传的数据总长度' + res.totalBytesExpectedToSend);
					showLoading.setTitle("  正在上传" + res.progress + "%  ");
					// 测试条件，取消上传任务。
					// if (res.progress > 50) {
					// uploadTask.abort();
					// }
				});
				// #endif
			},
			fail: (err) => {
				console.log(err)
			}
		})

	})
}
export function ups(url, file, types) {
	// 图片上传
	return new Promise((resolve, reject) => {
		uni.getStorage({
			key: 'token',
			success(res) {
				let path = file;
				// #ifdef MP-WEIXIN
				uni.showLoading({
					title: '请稍后'
				})
				// if(url!='videos'){
				// 	path = file.path;
				// }
				// #endif
				// console.log(file)
				let that = this;
				// that.upLoadImg = [];
				// let hd = this.getHeader();
				// #ifdef APP-PLUS
				const uploadTask =
					// #endif
					uni.uploadFile({
						url: URL + url,
						filePath: path,
						name: 'file',
						header: {
							"token": uni.getStorageSync('token'),
							// "sign": hd.sign,
							// "requestTime": hd.time,
							"X-DS-KEY": uni.getStorageSync('token')
						},
						formData: {
							name: "file",
							type: types
						},
						success: (uploadFileRes) => {
							// console.log('------',uploadFileRes)
							// let imgSrc = JSON.parse(uploadFileRes.data).result.substring(1)
							// console.log(JSON.parse(uploadFileRes.data));
							if (uploadFileRes.code == 200) {
								// 保存上传图片返回值
								// that.upLoadImg.push(imgSrc);
								resolve(JSON.parse(uploadFileRes.data))
							} // else {
							// 	reject()
							// }
							// #ifdef APP-PLUS
							plus.nativeUI.closeWaiting();
							// #endif
							// #ifdef MP-WEIXIN
							uni.hideLoading()
							// #endif
							resolve(uploadFileRes)
							// console.log(uploadFileRes)
						},
						error: (err) => {
							reject(err)
							// console.log(resa)
						}
					});
				// #ifdef APP-PLUS
				var showLoading = plus.nativeUI.showWaiting("正在上传");
				uploadTask.onProgressUpdate((res) => {
					// console.log('上传进度' + res.progress);
					// console.log('已经上传的数据长度' + res.totalBytesSent);
					// console.log('预期需要上传的数据总长度' + res.totalBytesExpectedToSend);
					showLoading.setTitle("  正在上传" + res.progress + "%  ");
					// 测试条件，取消上传任务。
					// if (res.progress > 50) {
					// uploadTask.abort();
					// }
				});
				// #endif
			},
			fail: (err) => {
				console.log(err)
			}
		})

	})
}
export function rpx(url, file, types) {
	// 图片上传
	return new Promise((resolve, reject) => {
		let path = file;
		// #ifdef MP-WEIXIN
		uni.showLoading({
			title: '请稍后'
		})
		// if(url!='videos'){
		// 	path = file.path;
		// }
		// #endif
		// console.log(file)
		let that = this;
		// that.upLoadImg = [];
		// let hd = this.getHeader();
		// #ifdef APP-PLUS
		const uploadTask =
			// #endif
			uni.uploadFile({
				url: URL + url,
				filePath: path,
				name: 'file',
				header: {
					"token": uni.getStorageSync('token'),
					// "sign": hd.sign,
					// "requestTime": hd.time,
					"X-DS-KEY": uni.getStorageSync('token')
				},
				formData: {
					name: "file",
					type: types
				},
				success: (uploadFileRes) => {
					// console.log('------',uploadFileRes)
					// let imgSrc = JSON.parse(uploadFileRes.data).result.substring(1)
					// console.log(JSON.parse(uploadFileRes.data));
					if (uploadFileRes.code == 200) {
						// 保存上传图片返回值
						// that.upLoadImg.push(imgSrc);
						resolve(JSON.parse(uploadFileRes.data))
					} // else {
					// 	reject()
					// }
					// #ifdef APP-PLUS
					plus.nativeUI.closeWaiting();
					// #endif
					// #ifdef MP-WEIXIN
					uni.hideLoading()
					// #endif
					resolve(uploadFileRes)
					// console.log(uploadFileRes)
				},
				error: (err) => {
					reject(err)
					// console.log(resa)
				}
			});
		// #ifdef APP-PLUS
		var showLoading = plus.nativeUI.showWaiting("正在上传");
		uploadTask.onProgressUpdate((res) => {
			// console.log('上传进度' + res.progress);
			// console.log('已经上传的数据长度' + res.totalBytesSent);
			// console.log('预期需要上传的数据总长度' + res.totalBytesExpectedToSend);
			showLoading.setTitle("  正在上传" + res.progress + "%  ");
			// 测试条件，取消上传任务。
			// if (res.progress > 50) {
			// uploadTask.abort();
			// }
		});
		// #endif
	})
}
// 登录
export function login(data) {
	return new Promise((resolve, reject) => {
		uni.showLoading({
			title: '请稍后'
		})

		uni.request({
			url: URL + 'user/login-app/sign', //真实接口地址。
			method: "GET", //请求的方式
			data: data, //参数
			header: {
				'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;',
			},
			success: (res) => {
				uni.hideLoading();
				if (res.data.code == 200) {
					uni.setStorageSync('token', res.data.data.token);
					// let user = {
					// 	image: res.data.data.image,
					// 	username: res.data.data.username,
					// 	phone: res.data.data.phone
					// }
					// uni.setStorageSync('user', user);
					console.log(res.data.data.token)
					uni.showToast({
						title: '登录成功',
						icon: 'none',
						success() {
							// uni.showLoading({
							// 	title:'请稍后'
							// })
							uni.getStorage({
								key: 'token',
								success(res) {
									if (res.data != '') {
										setTimeout(function() {
											uni.navigateBack()
											// uni.switchTab({
											// 	url: '/pages/index/index',
											// 	success() {
											// 		// uni.hideLoading()
											// 	}
											// })
										}, 2000)
									}
								}
							})

						}
					})
				} else {
					uni.showToast({
						title: res.data.message,
						icon: 'none'
					})
				}
				resolve(res)
			},

			//时报调用reject
			fail: (err) => {
				reject(err)
			}
		})
	})
}
export function gps() {
	return new Promise((resolve, reject) => {
		let bool = false

		// android平台
		if (uni.getSystemInfoSync().platform == 'android') {
			var context = plus.android.importClass("android.content.Context");
			var locationManager = plus.android.importClass("android.location.LocationManager");
			var main = plus.android.runtimeMainActivity();
			var mainSvr = main.getSystemService(context.LOCATION_SERVICE);
			bool = mainSvr.isProviderEnabled(locationManager.GPS_PROVIDER)
		}

		// ios平台
		if (uni.getSystemInfoSync().platform == 'ios') {
			var cllocationManger = plus.ios.import("CLLocationManager");
			var enable = cllocationManger.locationServicesEnabled();
			var status = cllocationManger.authorizationStatus();
			plus.ios.deleteObject(cllocationManger);
			bool = enable && status != 2
		}

		// 未开启定位功能
		if (bool === false) {
			uni.showModal({
				title: '提示',
				content: '请打开定位服务',
				success: ({
					confirm,
					cancel
				}) => {

					if (confirm) {
						// android平台
						if (uni.getSystemInfoSync().platform == 'android') {
							var Intent = plus.android.importClass('android.content.Intent');
							var Settings = plus.android.importClass(
								'android.provider.Settings');
							var intent = new Intent(Settings.ACTION_LOCATION_SOURCE_SETTINGS);
							var main = plus.android.runtimeMainActivity();
							main.startActivity(intent); // 打开系统设置GPS服务页面
						}

						// ios平台
						if (uni.getSystemInfoSync().platform == 'ios') {
							var UIApplication = plus.ios.import("UIApplication");
							var application2 = UIApplication.sharedApplication();
							var NSURL2 = plus.ios.import("NSURL");
							var setting2 = NSURL2.URLWithString(
								"App-Prefs:root=Privacy&path=LOCATION");
							application2.openURL(setting2);
							plus.ios.deleteObject(setting2);
							plus.ios.deleteObject(NSURL2);
							plus.ios.deleteObject(application2);
						}
					}

					// 用户取消前往开启定位服务
					if (cancel) {
						// do sth...
						reject('用户取消开启定位')
					}
				}
			});
		} else if (bool === true) {
			console.log('nihao');
			uni.getLocation({
				success() {
					uni.chooseLocation({
						success: function(res) {
							console.log('已开启定位')
							console.log(res)
							console.log('位置名称：' + res.name);
							console.log('详细地址：' + res.address);
							console.log('纬度：' + res.latitude);
							console.log('经度：' + res.longitude);
							// t.up.position = res.name;
							// t.up.addresss = res.latitude + ',' + res.longitude;
							// this.mapcon = res.address
							// console.log(t.up.position)
							// console.log(this.address)
							resolve(res)
						},
						fail(err) {
							// console.log(未开启定位)
							reject(err)
						}
					});
				}
			})
		}


	})
}
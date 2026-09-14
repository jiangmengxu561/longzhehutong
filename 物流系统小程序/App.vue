<script>
	export default {
		globalData: {
			statusBarHeight: 0, // 状态导航栏高度
			navHeight: 0, // 总体高度
			navigationBarHeight: 0, // 导航栏高度(标题栏高度)
		},
		onLaunch: function(options) {
			console.log("App Launch", options)
			// 状态栏高度
			this.globalData.statusBarHeight = uni.getSystemInfoSync().statusBarHeight
			this.globalData.navigationBarHeight = 44
			this.globalData.navHeight = this.globalData.navigationBarHeight + this.globalData.statusBarHeight
		
			// #ifdef MP-WEIXIN
			// 获取微信胶囊的位置信息 width,height,top,right,left,bottom
			const custom = wx.getMenuButtonBoundingClientRect()
			// console.log(custom)
			// 导航栏高度(标题栏高度) = 胶囊高度 + (顶部距离 - 状态栏高度) * 2
			this.globalData.navigationBarHeight = custom.height + (custom.top - this.globalData.statusBarHeight) * 2
			// console.log("导航栏高度："+this.globalData.navigationBarHeight)
			// 总体高度 = 状态栏高度 + 导航栏高度
			this.globalData.navHeight = this.globalData.navigationBarHeight + this.globalData.statusBarHeight
			
			// 获取扫码进入小程序的scene参数（二维码参数）
			if(options && options.scene){
				console.log('App onLaunch - 扫码scene参数:', options.scene)
				console.log('App onLaunch - scene类型:', typeof options.scene)
				// 解析二维码URL中的invitation参数
				const invitation = this.parseInvitationFromScene(options.scene)
				console.log('App onLaunch - 解析出的invitation参数:', invitation)
				if(invitation){
					uni.setStorageSync('qrcode_invitation', invitation)
					console.log('App onLaunch - 已保存invitation到存储:', invitation)
				}
				// 同时保存原始scene，以防需要
				uni.setStorageSync('qrcode_scene', String(options.scene))
				console.log('App onLaunch - 已保存scene到存储:', options.scene)
			} else {
				console.log('App onLaunch - 没有scene参数')
			}
			// #endif
		},
		onShow: function() {
			console.log('App Show')
		},
		onHide: function() {
			console.log('App Hide')
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
		}
	}
</script>

<style>
	/*每个页面公共css */
	/* 设置整个项目的背景色 */
	page {
		background-color: #F7F6FA;
		font-size: 28rpx;
		color: #000;
	}
	
	input {
		font-size:28rpx;
		color: #333;
	}
	
	.flex {
		display: flex;
	}
	
	.flexJusp {
		display: flex;
		justify-content: space-between;
	}
	
	.yihang {
		overflow: hidden;
		white-space: nowrap;
		text-overflow: ellipsis;
	}
	.erhang{
		overflow: hidden;
		text-overflow: ellipsis;
		display: -webkit-box;
		-webkit-box-orient: vertical;
		-webkit-line-clamp: 2;
	}
</style>

<template>
	<u-popup :show="show" :customStyle="{marginTop: '0rpx'}" mode="bottom" :round='20' safeAreaInsetBottom
		closeOnClickOverlay @close="close" @open="show=true">
		<view class="home">
			<view class="content-view">
				<scroll-view scroll-y="true">
					<view v-for="(item,index) in list" :key="index" class="item-box"
						@click="provinceTextClick(item,index)" :class="selectPIndex===index?'selected-view':''">
						<image class="image-img" :src="getIcon(item, index)" @click="provinceImgClick(item,index)">
						</image>
						<text :class="selectPIndex===index?'selected-index':''">{{item.name}}</text>
						<!-- {{(item.sum?item.sum:'')}} -->
					</view>
				</scroll-view>
				<scroll-view scroll-y="true" v-if="list[selectPIndex] && list[selectPIndex].children">
					<view v-for="(item,index) in list[selectPIndex].children" :key="index" class="item-box left-gap"
						@click="cityTextClick(item,index)">
						<image class="image-img" :src="getIcon(item, index)" @click="cityImgClick(item,index)"></image>
						<text :class="selectCIndex===index?'selected-index':''">{{item.name}}</text>
					</view>
				</scroll-view>
				<scroll-view scroll-y="true"
					v-if="list[selectPIndex] && list[selectPIndex].children && list[selectPIndex].children[selectCIndex]">
					<view v-for="(item,index) in list[selectPIndex].children[selectCIndex].children" :key="index"
						class="item-box left-gap">
						<image class="image-img" :src="getIcon(item, index)" @click="regionImgClick(item,index)">
						</image>
						<text>{{item.name}}</text>
					</view>
				</scroll-view>
			</view>
			<view class="saveView">
				<u-row>
					<u-col span="4">
						<u-button type="primary" text="重置"
							customStyle="width:100%;height:80rpx;border-radius: 0;background: #DDDFFB;color:#2F6DFE;border:none;"
							@click="getListArr"></u-button>
					</u-col>
					<u-col span="8">
						<u-button type="primary" :text="saveText"
							customStyle="width:100%;height:80rpx;border-radius: 0;background: #2F6DFE;border:none;"
							@click="save"></u-button>
					</u-col>
				</u-row>


			</view>
		</view>
	</u-popup>
</template>

<script>
	import address from './addressData.js'
	export default {
		data() {
			return {
				list: [],
				selectPIndex: 0,
				selectCIndex: 0,
				saveText: '确认'
			};
		},
		props: {
			show: {
				type: Boolean,
				default: true
			}
		},
		methods: {
			// 保存
			close() {
				this.$emit('close')
			},
			deepCopy(obj) {
				return JSON.parse(JSON.stringify(obj));
			},
			getListArr() {
				this.list = this.deepCopy(address)
			},
			sumNumber(list) {
				return list.reduce((acc, curr) => curr.checkType === 2 ? acc + 1 : acc, 0);
			},
			getIcon(item) {
				let map = {
					0: 'noneSel', // 全部未选
					1: 'halfSel', // 部分选
					2: 'allSel' // 全选
				}
				let checkType = item.checkType ?? 0
				return `/static/address/${map[checkType]}.png`
			},

			// 省icon点击 省市区点击事件全部关联到区的选中取消事件 省市的选中状态根据区的选中状态处理
			provinceImgClick(item, index) {
				// 全选 点击设置成非全选 相反设置成空
				let bol = item.checkType === 2 ? 0 : 2
				item.checkType = bol
				item?.children?.forEach(m => {
					m.checkType = bol
					m?.children?.forEach(n => {
						n.checkType = bol
					})
				})
				if (bol) {
					item.sum = item?.children?.length
				} else {
					item.sum = ''
				}

				this.$set(this.list, index, item)
				this.getAllCodes()
			},

			// 市icon点击
			cityImgClick(item, index) {
				// 全选 点击设置成非全选 相反设置成空
				let bol = item.checkType != 2 ? 2 : 0
				item.checkType = bol
				item?.children?.forEach(m => {
					m.checkType = bol
				})
				const count = item?.children?.filter((itemObj) => {
					itemObj.checkType.toString() === 'true'
				}).length;
				this.list[this.selectPIndex].sum = count

				this.$set(this.list[this.selectPIndex].children, index, item)
				// 向上触发联动
				this.cityLinkEvent()
				this.getAllCodes()
			},

			// 区icon点击
			regionImgClick(item, index) {
				let bol = item.checkType != 2 ? 2 : 0
				item.checkType = bol
				this.$set(this.list[this.selectPIndex].children[this.selectCIndex].children, index, item)
				// 向上触发联动
				this.regionLinkEvent()
				this.getAllCodes()
			},

			// 区勾选改变修改父级数据
			regionLinkEvent() {
				// 当前市下面所有区
				let tempList = this.list[this.selectPIndex].children[this.selectCIndex].children || []
				// 所有区为选中，则当前市为全选
				let bol = tempList.every(o => {
					return o.checkType === 2
				})
				if (bol) {
					this.$set(this.list[this.selectPIndex].children[this.selectCIndex], 'checkType', 2)
					this.cityLinkEvent()
					return
				}
				// 所有的都为未选 则当前市未选
				let bol1 = tempList.every(o => {
					return o.checkType === 0
				})
				if (bol1) {
					this.$set(this.list[this.selectPIndex].children[this.selectCIndex], 'checkType', 0)
					this.cityLinkEvent()
					return
				}
				// 区半选 则市半选 省半选
				this.$set(this.list[this.selectPIndex].children[this.selectCIndex], 'checkType', 1)
				this.$set(this.list[this.selectPIndex], 'checkType', 1)
				this.getAllCodes()
			},

			// 市勾选改变修改父级数据
			cityLinkEvent() {
				// 当前省下面所有市
				let tempList = this.list[this.selectPIndex].children || []
				// 所有市为选中，则当前省为全选
				let bol = tempList.every(o => {
					return o.checkType === 2
				})
				if (bol) {
					this.$set(this.list[this.selectPIndex], 'checkType', 2)
					return
				}
				// 所有的都为未选 则当前市未选
				let bol1 = tempList.every(o => {
					return o.checkType === 0
				})
				if (bol1) {
					this.$set(this.list[this.selectPIndex], 'checkType', 0)
					return
				}
				this.$set(this.list[this.selectPIndex], 'checkType', 1)
				this.getAllCodes()
			},

			// 省文字点击
			provinceTextClick(item, index) {
				this.selectPIndex = index
				this.selectCIndex = 0
			},

			// 城市文字点击
			cityTextClick(item, index) {
				this.selectCIndex = index
			},

			/**
			 * 获取全选的省市区code
			 * 省全选 则下面的市区code不加入市区code数组
			 * 市全选 则下面的区code不全选
			 */
			getAllCodes() {
				let allArr = []
				let provinceArr = []
				let cityArr = []
				let regionArr = []
				let provinceNameArr = []
				let cityNameArr = []
				let regionNameArr = []
				for (let i = 0; i < this.list.length; i++) {
					let tempObj = this.list[i]
					// 全选
					if (tempObj?.checkType === 2) {
						allArr.push({
							name: tempObj.name,
							id: tempObj.id,
							checkType: tempObj.checkType
						})
						provinceArr.push(tempObj.id)
						provinceNameArr.push(tempObj.name)
						continue
						// 省份半选
					} else if (tempObj.checkType === 1) {
						allArr.push({
							name: tempObj.name,
							id: tempObj.id,
							checkType: tempObj.checkType
						})
						for (let j = 0; j < tempObj?.children.length; j++) {
							let tempObj1 = tempObj.children[j]
							// 市全选
							if (tempObj1?.checkType === 2) {
								allArr.push({
									name: tempObj1.name,
									id: tempObj1.id,
									checkType: tempObj1.checkType
								})
								cityArr.push(tempObj1.id)
								cityNameArr.push(tempObj1.name)
								continue
								// 市半选	
							} else if (tempObj1?.checkType === 1) {
								allArr.push({
									name: tempObj1.name,
									id: tempObj1.id,
									checkType: tempObj1.checkType
								})
								for (let k = 0; k < tempObj1.children.length; k++) {
									let tempObj2 = tempObj1.children[k]
									// 区选中
									if (tempObj2?.checkType === 2) {
										allArr.push({
											name: tempObj2.name,
											id: tempObj2.id,
											checkType: tempObj2.checkType
										})
										regionArr.push(tempObj2.id)
										regionNameArr.push(tempObj2.name)
									}
								}
							}
						}
					}
				}
				if (provinceNameArr.length > 0 || cityNameArr.length > 0 || regionNameArr.length > 0) {
					this.saveText = '确定 (已选' + (provinceNameArr.length > 0 ? `${provinceNameArr.length}个省` : '') +
						(cityNameArr.length > 0 ? `${cityNameArr.length}个市` : '') + (regionNameArr.length > 0 ?
							`${regionNameArr.length}个区` : '') + ')'
					this.provinceNameArr = provinceNameArr
					this.cityNameArr = cityNameArr
					this.regionNameArr = regionNameArr
					this.allArr = allArr
				} else {
					this.saveText = '确定'
					this.provinceNameArr = []
					this.cityNameArr = []
					this.regionNameArr = []
				}
				return {
					provinceArr,
					cityArr,
					regionArr,
					provinceNameArr,
					cityNameArr,
					regionNameArr
				}

			},
			getAllRegionCode(type) {
				let code = []
				let name = []
				for (let i = 0; i < this.list.length; i++) {
					let tempObj = this.list[i]
					// 为0或者空未选中状态跳过 空选状态
					if (!tempObj.checkType) {
						continue;
					}
					// 全选状态
					// 状态为2 并且没有子节点则直接加入code并跳过直接进入下一个循环
					if (tempObj.checkType === 2) {
						code.push(tempObj.id)
						name.push(tempObj.name)
						continue;
					}
					// 半选状态 不可能存在没有子节点
					for (let j = 0; j < tempObj.children.length; j++) {
						let tempObj1 = tempObj.children[j]
						if (!tempObj1.checkType) {
							continue;
						}
						if (tempObj1.checkType === 2) {
							code.push(tempObj1.id)
							name.push(tempObj1.name)
							continue;
						}
						for (let k = 0; k < tempObj1.children.length; k++) {
							let tempObj2 = tempObj1.children[k]
							if (tempObj2?.checkType === 2) {
								code.push(tempObj2.id)
								name.push(tempObj2.name)
							}
						}
					}
				}
				if (type === 'code') {
					return code
				} else {
					return name
				}

			},

			// 保存
			save() {
				let codes = this.getAllRegionCode('code')
				let names = this.getAllRegionCode('name')
				let codes0 = this.getAllRegionCode()
				this.getAllCodes()
				this.$emit('transmit', names, this.provinceNameArr, this.cityNameArr, this.regionNameArr, this.allArr)
				this.$emit('close')
			}
		},
		created() {
			this.getListArr()
		}
	}
</script>
<style lang="scss" scoped>
	.u-status-bar {
		height: 0;
	}

	.saveBtn {
		width: 200rpx;
		margin: 28rpx auto 20rpx auto;
	}

	scroll-view:first-child {
		width: 33.8%;
		// height: calc(100vh - 490rpx);
		flex-direction: row;
		// margin-bottom:100rpx;
	}

	scroll-view:nth-child(2) {
		width: 33%;
		// height: calc(100vh - 490rpx);
		flex-direction: row;
		background-color: #fff;
		// margin-bottom:100rpx;
	}

	scroll-view:nth-child(3) {
		width: 33.2%;
		// height: calc(100vh - 490rpx);
		flex-direction: row;
		background-color: #fff;
		// margin-bottom:100rpx;
	}

	.home {
		width: 100%;
		height: 72vh;
		border-radius: 20rpx;
		background-color: #f4f4f4;

		.content-view {
			padding: 0rpx 0rpx 0rpx 0rpx;
			border-radius: 20rpx;
			// margin-top: 110rpx;
			display: flex;
			overflow-y: hidden;
			height: 72vh;
			text-indent: 2rpx;
			flex-direction: row;

			.left-gap {
				margin-left: 6rpx;
			}

			.item-box {
				padding: 20rpx 0;
				flex: 1;
				padding-right: 10rpx;
				display: flex;
				align-items: center;
				flex-direction: row;

				text {
					font-size: 26rpx;
					margin-left: 8rpx;
				}
			}
		}

		.saveView {
			width: 100%;
			position: fixed;
			/* 固定位置 */
			bottom: 0;
			margin: auto;
			flex-direction: row;
			flex-wrap: nowrap;
			align-items: center;
			background: #EAEAEB;
			border-top: #EAEAEB;
			height: 90rpx;
			margin-bottom: 0rpx;
			z-index: 1000
		}
	}


	.image-img {
		width: 30rpx;
		height: 30rpx;
		flex-shrink: 0;
		margin-left: 16rpx;
	}

	.selected-view {
		background-color: #ffffff;
	}

	.selected-index {
		color: #2f6dfe;
	}

	::v-deep ::-webkit-scrollbar {
		// 滚动条整体样式
		display: block;
		width: 4rpx !important;
		height: 4rpx !important;
		overflow: auto !important;
	}

	::v-deep ::-webkit-scrollbar-thumb {
		// 滚动条里面小方块
		background-color: #90909070 !important;
	}

	::v-deep ::-webkit-scrollbar-track {
		// 滚动条
		background-color: #f0f0f0 !important;
	}
</style>
<template>
	<view class="privacy">
		<!-- 自定义导航栏 -->
		<view :style="'height:' + statusBarHeight + 'px;'"></view>
		<view class="navBar" :style="'height:' + navigationBarHeight + 'px;'">
			<view class="backBtn" @click="fanhui">
				<image style="width:20rpx;height:34rpx;" src="/static/images/mv_tuichu.png"></image>
			</view>
			<view class="navTitle">隐私政策</view>
			<view style="width: 100rpx;"></view>
		</view>

		<!-- 内容区 -->
		<scroll-view scroll-y class="content">
			<view class="section">
				<view class="h1">龙喆互通隐私政策</view>
				<view class="updateTime">更新日期：{{ updateTime }}</view>
				<view class="p">
					龙喆互通（以下简称"我们"）深知个人信息对您的重要性，并会尽全力保护您的个人信息安全可靠。我们致力于维持您对我们的信任，恪守以下原则保护您的个人信息：权责一致原则、目的明确原则、选择同意原则、最少够用原则、确保安全原则、主体参与原则等。同时，我们承诺将按业界成熟的安全标准采取相应的安全保护措施来保护您的个人信息。
				</view>
				<view class="p">
					请在使用我们的服务前，仔细阅读并了解本《隐私政策》。一旦您开始使用我们的服务，即视为您已充分理解并同意本政策。
				</view>
			</view>

			<view class="section">
				<view class="h2">一、我们收集的信息</view>
				<view class="p">为向您提供物流运输、下单、查询、支付等服务，我们可能收集以下信息：</view>
				<view class="li">1. 账号信息：手机号码、微信授权的昵称、头像等；</view>
				<view class="li">2. 实名信息：姓名、身份证号（仅用于实名认证及合规需要）；</view>
				<view class="li">3. 地址信息：寄件人、收件人姓名、电话、地址等；</view>
				<view class="li">4. 订单信息：下单、支付、物流轨迹、发票等相关信息；</view>
				<view class="li">5. 设备信息：设备型号、操作系统、网络状态、IP 地址等，用于安全风控与服务优化；</view>
				<view class="li">6. 位置信息：在您授权后，获取您的地理位置，用于寄件、收件地址的自动填充。</view>
			</view>

			<view class="section">
				<view class="h2">二、我们如何使用信息</view>
				<view class="p">我们仅会出于以下目的使用您的个人信息：</view>
				<view class="li">1. 为您提供物流下单、运输、查询、支付等核心服务；</view>
				<view class="li">2. 实名认证、风险控制、反欺诈及保障交易安全；</view>
				<view class="li">3. 与您沟通订单状态、物流信息、客服反馈等；</view>
				<view class="li">4. 改善和优化我们的产品功能、性能及用户体验；</view>
				<view class="li">5. 遵守法律法规及监管要求。</view>
			</view>

			<view class="section">
				<view class="h2">三、信息的共享、转让与披露</view>
				<view class="p">
					我们不会向任何第三方出售您的个人信息。在以下情形下，我们可能共享或披露您的部分信息：
				</view>
				<view class="li">1. 获得您的明确同意或授权后；</view>
				<view class="li">2. 为完成物流配送，需向司机、专线合作方披露必要的订单及地址信息；</view>
				<view class="li">3. 与授权合作伙伴共享：仅共享必要信息，且要求其遵守保密与数据安全义务；</view>
				<view class="li">4. 根据法律法规、诉讼、政府主管部门强制要求或为保障公共安全而披露。</view>
			</view>

			<view class="section">
				<view class="h2">四、信息的存储与保护</view>
				<view class="p">
					您的个人信息存储于中华人民共和国境内，存储期限不超过实现服务目的所必需的合理期限。我们采用业界通行的安全技术和管理措施保护您的信息，包括但不限于加密存储、访问控制、安全审计、数据备份等。
				</view>
			</view>

			<view class="section">
				<view class="h2">五、您的权利</view>
				<view class="li">1. 查阅、复制：您可在"我的—个人信息"中查阅和获取您的个人信息副本；</view>
				<view class="li">2. 更正、补充：您可修改账号信息、地址簿等；</view>
				<view class="li">3. 删除：在符合条件时，您可请求我们删除您的个人信息；</view>
				<view class="li">4. 撤回授权：您可通过微信设置撤回授权，或联系客服处理；</view>
				<view class="li">5. 注销账号：您可联系客服申请注销账号，我们将按法律法规处理。</view>
			</view>

			<view class="section">
				<view class="h2">六、未成年人保护</view>
				<view class="p">
					本服务主要面向成年人。若您是未满 14 周岁的未成年人，请在监护人陪同下阅读本政策，并在取得监护人同意后使用服务。我们会依法对未成年人信息进行特别保护。
				</view>
			</view>

			<view class="section">
				<view class="h2">七、Cookie 与同类技术</view>
				<view class="p">
					为改善服务体验，我们可能使用 Cookie、本地存储等同类技术收集和存储您的偏好信息，以便为您提供个性化服务。您可通过设备设置清除或拒绝此类技术，但可能影响部分功能的使用。
				</view>
			</view>

			<view class="section">
				<view class="h2">八、本政策的更新</view>
				<view class="p">
					我们可能适时修订本政策。当本政策的条款发生重大变更时，我们会通过弹窗、公告或其他便于您知晓的方式向您告知。未经您明确同意，我们不会削减您按照本政策所应享有的权利。
				</view>
			</view>

			<view class="section">
				<view class="h2">九、联系我们</view>
				<view class="p">如对本政策有任何疑问、意见或建议，可通过以下方式与我们联系：</view>
				<view class="li">运营方：龙喆互通</view>
				<view class="li">客服邮箱：service@longzhehutong.cn</view>
				<view class="li">官方网站：https://lzwl.longzhehutong.cn/</view>
			</view>

			<view style="height: 40rpx;"></view>
		</scroll-view>
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
				updateTime: '2026-08-01'
			};
		},
		methods: {
			fanhui() {
				const pages = getCurrentPages()
				if (pages.length > 1) {
					uni.navigateBack()
				} else {
					uni.switchTab({
						url: '/pages/index/index'
					})
				}
			}
		}
	}
</script>

<style lang="scss">
	.privacy {
		min-height: 100vh;
		background-color: #FFFFFF;
	}

	.navBar {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 0 20rpx;
	}

	.backBtn {
		width: 100rpx;
		text-align: center;
	}

	.navTitle {
		font-weight: bold;
		font-size: 36rpx;
		color: #333333;
		text-align: center;
		flex: 1;
	}

	.content {
		padding: 0 32rpx;
		box-sizing: border-box;
	}

	.section {
		margin-bottom: 32rpx;
	}

	.h1 {
		font-size: 40rpx;
		font-weight: bold;
		color: #333333;
		text-align: center;
		margin-top: 16rpx;
		margin-bottom: 12rpx;
	}

	.updateTime {
		font-size: 24rpx;
		color: #999999;
		text-align: center;
		margin-bottom: 24rpx;
	}

	.h2 {
		font-size: 32rpx;
		font-weight: bold;
		color: #333333;
		margin-bottom: 16rpx;
	}

	.p {
		font-size: 28rpx;
		color: #333333;
		line-height: 1.8;
		margin-bottom: 16rpx;
		text-align: justify;
	}

	.li {
		font-size: 28rpx;
		color: #333333;
		line-height: 1.8;
		margin-bottom: 10rpx;
		padding-left: 12rpx;
	}
</style>

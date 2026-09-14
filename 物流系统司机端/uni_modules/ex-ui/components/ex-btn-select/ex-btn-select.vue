<template>
	<view class="btn">
		<view class="btn-group" :style="{'--line-number':lineNumber,'--gap':gap}">
			<view v-for="(item, index) in arr" :key="index" class="btn-group-item" @click="btnItemClick(item,index)"
				:style="[getBtnStyle(item)]">
				<slot :item="item" :index="index">
					<text>{{item[labelKey] }}</text>
				</slot>
				<view class="btn-group-badge" :style="[getBadgePosition]" v-if="showBadge">
					<ex-icon :name="badgePosition === 'top'?'right-top-mark-check':'right-bot-mark-check'"
						:size="badgeSize" :color="getBadegColor(item)"></ex-icon>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	import props from './props.js'
	import {selectMixin,btnSelectMixin} from '../../libs/mixin/select.js'
	export default {
		name: 'ex-btn-select',
		props,
		mixins: [selectMixin,btnSelectMixin],
		data() {
			return {};
		}

	}
</script>

<style lang="scss" scoped>
	@import '../../libs/css/btn-select.scss';
</style>
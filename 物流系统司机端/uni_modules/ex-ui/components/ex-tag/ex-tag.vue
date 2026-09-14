<template>
	<view class="tag">
		<view :style="[tagStyles]" :class="['tag-base', `tag-${size}`, `tag-${shape}`]">
			<slot name="left">
				<image v-if="leftImg" :src="leftImg" :class="['tag-left-img', `tag-left-img-${size}`]" mode="heightFix">
				</image>
			</slot>
			<text>{{text}}</text>
			<slot name="right">
				<image v-if="rightImg" :src="rightImg" :class="['tag-right-img', `tag-right-img-${size}`]"
					mode="heightFix">
				</image>
			</slot>
		</view>
		<view class="tag-badge" :style="[badgeStyle]">
			<slot name="badge">
				<text></text>
			</slot>
		</view>
	</view>

</template>
<script>
	import props from './props.js';
	export default {
		name: 'ex-tag',
		components: {},
		props,
		data() {
			return {}
		},
		methods: {},
		computed: {
			tagStyles() {
				return {
					color: '#FFFFFF',
					borderColor: '#409eff',
					background: '#409eff',
					...this.tagStyle
				}
			},
			badgeStyle() {
				return {
					[this.horizontal]: this.horizontalOffset,
					[this.vertical]: this.verticalOffset
				}
			}
		}
	}
</script>
<style lang="scss" scoped>
	.tag {
		position: relative;
		overflow: hidden;

		&-base {
			display: flex;
			align-items: center;
			border-style: solid;
			border-width: 1px;
			justify-content: center;
		}

		&-square {
			border-radius: 3px;
		}

		&-circle {
			border-radius: 100px;
		}

		&-mini {
			font-size: 12px;
			padding: 2px 6px;
		}

		&-default {
			font-size: 13px;
			padding: 4px 9px;
		}

		&-large {
			font-size: 15px;
			padding: 6px 12px;
		}

		&-left-img,
		&-right-img {
			vertical-align: center;

			&-mini {
				height: 11px;
			}

			&-default {
				height: 12px;
			}

			&-large {
				height: 14px;
			}
		}

		&-left-img {
			margin-right: 8px;
		}

		&-right-img {
			margin-left: 8px;
		}

		&-badge {
			position: absolute;
			display: flex;
		}
	}
</style>
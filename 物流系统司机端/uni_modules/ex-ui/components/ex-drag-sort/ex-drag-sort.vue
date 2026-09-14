<template>
	<movable-area :style="[getAreaStyle]">
		<movable-view v-for="(item, index) in list" :animation="animation" :direction="direction" :key="item.key"
			:damping="damping" :x="item.x" :y="item.y" :disabled="longpress ? disabled : false"
			@longpress="handleLongpress" @touchstart="handleDragStart(index)" @change="handleMoving"
			@touchend="handleDragEnd" :style="[getViewStyle]" class="base-drag-wrapper"
			:class="{ active: activeIndex === index }">
			<!-- #ifdef MP-WEIXIN -->
			<view class="drag-item">{{ item[itemKey] }}</view>
			<!-- #endif -->

			<!-- #ifndef MP-WEIXIN -->
			<slot name="item" :item="item" :index="index"></slot>
			<!-- #endif -->
		</movable-view>
	</movable-area>
</template>

<script>
	import props from './props.js';
	export default {
		props,
		emits: ['update:modelValue', 'end'],
		data() {
			return {
				list: [],
				disabled: true,
				activeIndex: -1,
				moveToIndex: -1,
				oldIndex: -1,
				tempDragInfo: {
					x: '',
					y: ''
				},
				cloneList: []
			};
		},
		computed: {
			getAreaStyle() {
				const width = this.getRealWidth(this.width);
				return {
					width: width + 'px',
					height: this.height !== 'auto' ? this.height : Math.round(this.list.length / this.column) * this
						.getItemHeight + 'px'
				};
			},
			getViewStyle() {
				const {
					itemHeight,
					getItemWidth
				} = this;
				return {
					width: getItemWidth + 'px',
					height: itemHeight
				};
			},
			getItemHeight() {
				return parseFloat(this.itemHeight);
			},
			getItemWidth() {
				if (this.column === 0) return;
				const width = this.getRealWidth(this.width);
				return (parseFloat(width) / this.column).toFixed(2);
			}
		},
		methods: {
			//获取实际的宽度
			getRealWidth(width) {
				if (width.includes('%')) {
					const windowWidth = uni.getSystemInfoSync().windowWidth;
					width = windowWidth * (parseFloat(width) / 100);
				}
				return width;
			},

			initList(list = []) {
				const newList = this.deepCopy(list);
				this.list = newList.map((item, index) => {
					const [x, y] = this.getPosition(index);
					return {
						...item,
						x,
						y,
						key: Math.random() + index
					};
				});
				this.cloneList = this.deepCopy(this.list);
			},

			//长按
			handleLongpress() {
				this.disabled = false;
			},

			//拖拽开始
			handleDragStart(index) {
				this.activeIndex = index;
				this.oldIndex = index;
			},

			//拖拽中
			handleMoving(e) {
				if (e.detail.source !== 'touch') return;
				const {
					x,
					y
				} = e.detail;
				Object.assign(this.tempDragInfo, {
					x,
					y
				});
				const currentX = Math.floor((x + this.getItemWidth / 2) / this.getItemWidth);
				const currentY = Math.floor((y + this.getItemHeight / 2) / this.getItemHeight);

				this.moveToIndex = Math.min(currentY * this.column + currentX, this.list.length - 1);

				if (this.oldIndex !== this.moveToIndex && this.oldIndex !== -1 && this.moveToIndex !== -1) {
					const newList = this.deepCopy(this.cloneList);
					newList.splice(this.moveToIndex, 0, ...newList.splice(this.activeIndex, 1));

					this.list.forEach((item, index) => {
						if (index !== this.activeIndex) {
							const itemIndex = newList.findIndex(val => val[this.itemKey] === item[this.itemKey]);
							[item.x, item.y] = this.getPosition(itemIndex);
						}
					});
					this.oldIndex = this.moveToIndex;
				}
			},

			//获取当前的位置
			getPosition(index) {
				const x = (index % this.column) * this.getItemWidth;
				const y = Math.floor(index / this.column) * this.getItemHeight;
				return [x, y];
			},

			//拖拽结束
			handleDragEnd(e) {
				if (this.disabled) return;
				if (this.moveToIndex !== -1 && this.activeIndex !== -1 && this.moveToIndex !== this.activeIndex) {
					this.cloneList.splice(this.moveToIndex, 0, ...this.cloneList.splice(this.activeIndex, 1));
				} else {
					this.$set(this.list[this.activeIndex], 'x', this.tempDragInfo.x);
					this.$set(this.list[this.activeIndex], 'y', this.tempDragInfo.y);
				}
				this.initList(this.cloneList);
				const endList = this.list.map(item => this.omit(item, ['x', 'y', 'key']));
				// #ifndef VUE3
				this.$emit('input', endList)
				// #endif
				// #ifdef VUE3
				this.$emit('update:modelValue', endList)
				// #endif
				this.$emit('end', endList);

				this.activeIndex = -1;
				this.oldIndex = -1;
				this.moveToIndex = -1;
				this.disabled = true;
			},

			deepCopy(source) {
				return uni.$ex.utils.deepClone(source)
			},

			/**
			 * 排除掉obj里面的key值
			 * @param {object} obj
			 * @param {Array|string} args
			 * @returns {object}
			 */
			omit(obj, args) {
				if (!args) return obj;
				const newObj = {};
				const isString = typeof args === 'string';
				const keys = Object.keys(obj).filter(item => {
					if (isString) {
						return item !== args;
					}
					return !args.includes(item);
				});

				keys.forEach(key => {
					if (obj[key] !== undefined) newObj[key] = obj[key];
				});
				return newObj;
			}
		},
		watch: {
			value: {
				handler() {
					this.initList(this.value);
				},
				immediate: true,
				deep: true
			}
		}
	};
</script>

<style lang="scss" scoped>
	.base-drag-wrapper {
		opacity: 1;
		z-index: 1;

		&.active {
			opacity: 0.7;
			transform: scale(1.3);
			z-index: 99;
		}
	}
</style>
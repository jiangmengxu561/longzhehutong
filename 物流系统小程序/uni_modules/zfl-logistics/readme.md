# zfl-logistics
## 物流信息轨迹通用组件

## 组件使用示例

```vue
<template>
	<zfl-logistics :list="list"></zfl-logistics>
</template>

<script>
	export default {
		data() {
			return {
				list: [
					{status: '代取件', time: '2024-11-29 15:40:23', description: '快件已送达菜鸟驿站，凭取件码【10-2-1020】取件，请及时取件，如有疑问请联系150******'},
					{status: '派送中', time: '2024-11-29 15:40:23', description: '快件已到达投揽部，等待派件'},
					{status: '运输中', time: '2024-11-29 15:40:23', description: '快件离开仓库，已发往下一站'},
					{status: '已揽件', time: '2024-11-29 15:40:23', description: '邮政快递 已收取快件'},
					{status: '已发货', time: '2024-11-29 15:40:23', description: '等待揽收中'},
					{status: '已下单', time: '2024-11-29 15:40:23', description: '商品已经下单'},
				]
			}
		},
	}
</script>
```

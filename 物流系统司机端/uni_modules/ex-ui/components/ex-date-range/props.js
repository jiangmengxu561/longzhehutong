const dataList = [{
		type: 'day',
		nums: -2,
		label: '前天'
	},
	{
		type: 'day',
		nums: -1,
		label: '昨天'
	},
	{
		type: 'day',
		nums: 0,
		label: '今天'
	},
	{
		type: 'week',
		nums: 0,
		label: '本周'
	},
	{
		type: 'week',
		nums: -1,
		label: '上周'
	},
	{
		type: 'week',
		nums: 1,
		label: '下周'
	},
	{
		type: 'month',
		nums: 0,
		label: '本月'
	},
	{
		type: 'month',
		nums: 1,
		label: '下月'
	},
	{
		type: 'month',
		nums: -1,
		label: '上月'
	},
	{
		type: 'quarter',
		nums: 0,
		label: '本季度'
	},
	{
		type: 'quarter',
		nums: 1,
		label: '下季度'
	},
	{
		type: 'quarter',
		nums: -1,
		label: '上季度'
	},
	{
		type: 'year',
		nums: 0,
		label: '本年度'
	},
	{
		type: 'year',
		nums: 1,
		label: '下年度'
	},
	{
		type: 'year',
		nums: -1,
		label: '上年度'
	},
	{
		type: 'nextDay',
		nums: -2,
		label: '近3天'
	},
	{
		type: 'nextDay',
		nums: -6,
		label: '近7天'
	},
	{
		type: 'nextDay',
		nums: -14,
		label: '近15天'
	},
	{
		type: 'nextDay',
		nums: -29,
		label: '近30天'
	},
	{
		type: 'nextDay',
		nums: -59,
		label: '近60天'
	},
	{
		type: 'nextDay',
		nums: -89,
		label: '近90天'
	},
	{
		type: 'nextDay',
		nums: -20,
		label: '近3周'
	},
	{
		type: 'nextDay',
		nums: -34,
		label: '近5周'
	},
	{
		type: 'nextDay',
		nums: -48,
		label: '近7周'
	}
]

export default {
	// 双向绑定
	// #ifndef VUE3
	value: {
		type: Array,
		default: () => {
			return []
		}
	},
	// #endif
	// #ifdef VUE3
	modelValue: {
		type: Array,
		default: () => {
			return []
		}
	},
	// #endif
	// 页面显示的日期时间格式(参照dayjs配置即可)
	showFormat: {
		type: String,
		default: () => {
			return 'YYYY-MM-DD'
		}
	},
	// 提交后台的时间格式(参照dayjs配置即可)
	valueFormat: {
		type: String,
		default: () => {
			return 'YYYY-MM-DD HH:mm:ss'
		}
	},

	// 时间点时候默认的起止时间数组 ['00:00:00','23:59:59']
	defaultTime: {
		type: Array,
		default: () => {
			return ['00:00:00', '23:59:59']
		}
	},

	// 每行按钮个数
	lineNumber: {
		type: Number,
		default: 3
	},
	// 按钮间距
	gap: {
		type: String,
		default: '12px'
	},
	selectedStyle: {
		type: Object,
		default: () => {
			return {
				background: '#409eff',
				color: '#FFFFFF',
				borderColor: '#409eff'
			}
		}
	},
	// 未选中样式
	unSelectedStyle: {
		type: Object,
		default: () => {
			return {
				background: '#F5F5F5',
				color: '#4D4D4D',
				borderColor: '#F5F5F5'
			}
		}
	},
	// 按钮的选项
	list: {
		type: Array,
		default: () => {
			return dataList
		}
	},
	// 一周的起始时间  0:系统默认  1:周一   系统默认周天为一周的起始时间
	weekStart: {
		type: Number,
		default: 0
	},
	// 开始时间未选择时候的提示文字
	startPlaceholder: {
		type: String,
		default: '请选择开始时间'
	},
	// 结束时间未选择时候的提示文字
	endPlaceholder: {
		type: String,
		default: '请选择截止时间'
	}
}
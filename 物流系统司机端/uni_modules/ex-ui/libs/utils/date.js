import dayjs from 'dayjs'
import quarterOfYear from 'dayjs/plugin/quarterOfYear'
dayjs.extend(quarterOfYear);

/**
 * 默认起始时间为00:00:00
 * 默认结束时间为23:59:59
 * type day天 week周 month月 quarter季度 year年 nextDay近多少天
 * nums 根据type类型 0表示当(天,周,月,季度,年) -1表示上(天,周,月,季度,年), 1表示下(天,周,月,季度,年)
 * reg 表示返回的时间格式类型 起始时间默认时分秒是00:00:00 结束时间默认23:59:59
 * weekStart 1表示周一是一周的开始，0表示周日是一周的开始
 */
function getDaterange({
	type = 'day',
	nums = 0,
	date = new Date(),
	reg = 'YYYY-MM-DD',
	weekStart = 1, // 1表示周一是一周的开始，0表示周日是一周的开始
	defaultTime = ['00:00:00', '23:59:59']
}) {
	// 近多少天
	if (type == 'nextDay') {
		const startTime = dayjs(date).add(nums, 'day').format('YYYY-MM-DD') + ' ' + defaultTime[0]
		const endTime = dayjs(date).format('YYYY-MM-DD') + ' ' + defaultTime[1]
		return [dayjs(startTime).format(reg), dayjs(endTime).format(reg)]
	}
	// 周处理 系统默认每周的第一天为周天，weekStart为1则每周第一天为周一，0 则为周天
	if (type == 'week') {
		const startTime = dayjs(date).add(nums, type).startOf(type).add(weekStart, 'day').format('YYYY-MM-DD') + ' ' +
			defaultTime[0]
		const endTime = dayjs(date).add(nums, type).endOf(type).add(weekStart, 'day').format('YYYY-MM-DD') + ' ' +
			defaultTime[1]
		return [dayjs(startTime).format(reg), dayjs(endTime).format(reg)]
	}
	const startTime = dayjs(date).add(nums, type).startOf(type).format('YYYY-MM-DD') + ' ' + defaultTime[0]
	const endTime = dayjs(date).add(nums, type).endOf(type).format('YYYY-MM-DD') + ' ' + defaultTime[1]
	return [dayjs(startTime).format(reg), dayjs(endTime).format(reg)]
}

export default {
	getDaterange
}
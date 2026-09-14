/**
 * copy自uview,感谢大佬的辛勤付出
 * @description 样式转换
 * 对象转字符串，或者字符串转对象
 * @param {object | string} customStyle 需要转换的目标
 * @param {String} target 转换的目的，object-转为对象，string-转为字符串
 * @returns {object|string}
 */
function addStyle(customStyle, target = 'object') {
	// 字符串转字符串，对象转对象情形，直接返回
	if (test.empty(customStyle) || typeof(customStyle) === 'object' && target === 'object' || target === 'string' &&
		typeof(customStyle) === 'string') {
		return customStyle
	}
	// 字符串转对象
	if (target === 'object') {
		// 去除字符串样式中的两端空格(中间的空格不能去掉，比如padding: 20px 0如果去掉了就错了)，空格是无用的
		customStyle = trim(customStyle)
		// 根据";"将字符串转为数组形式
		const styleArray = customStyle.split(';')
		const style = {}
		// 历遍数组，拼接成对象
		for (let i = 0; i < styleArray.length; i++) {
			// 'font-size:20px;color:red;'，如此最后字符串有";"的话，会导致styleArray最后一个元素为空字符串，这里需要过滤
			if (styleArray[i]) {
				const item = styleArray[i].split(':')
				style[trim(item[0])] = trim(item[1])
			}
		}
		return style
	}
	// 这里为对象转字符串形式
	let string = ''
	for (const i in customStyle) {
		// 驼峰转为中划线的形式，否则css内联样式，无法识别驼峰样式属性名
		const key = i.replace(/([A-Z])/g, '-$1').toLowerCase()
		string += `${key}:${customStyle[i]};`
	}
	// 去除两端空格
	return trim(string)
}

/**
 * copy自uview,感谢大佬的辛勤付出
 * @description 添加单位，如果有rpx，upx，%，px等单位结尾或者值为auto，直接返回，否则加上px单位结尾
 * @param {string|number} value 需要添加单位的值
 * @param {string} unit 添加的单位名 比如px
 */
function addUnit(value = 'auto', unit = uni?.$u?.config?.unit ?? 'px') {
	value = String(value)
	// 用uView内置验证规则中的number判断是否为数值
	return test.number(value) ? `${value}${unit}` : value
}

/**
 * 深拷贝内容
 * @param originData 拷贝对象
 */
function deepClone(originData) {
	const type = Object.prototype.toString.call(originData)
	let data
	if (type === '[object Array]') {
		data = []
		for (let i = 0; i < originData.length; i++) {
			data.push(deepClone(originData[i]))
		}
	} else if (type === '[object Object]') {
		data = {}
		for (const prop in originData) {
			if (originData.hasOwnProperty(prop)) { // 非继承属性
				data[prop] = deepClone(originData[prop])
			}
		}
	} else {
		data = originData
	}
	return data
}

/**
 * 节流
 * @param {Function} func
 * @param {Number} wait
 */
function throttle(func, wait = 1000) {
	let timeout
	return function(event, data) {
		clearTimeout(timeout)
		timeout = setTimeout(() => {
			func.call(this, event, data)
		}, wait)
	}
}

/**
 * 深合并多个对象
 */
function deepMerge( /* obj1, obj2, obj3, ... */ ) {
	const result = {}
	function assignValue(val, key) {
		if (typeof result[key] === 'object' && typeof val === 'object') {
			result[key] = deepMerge(result[key], val)
		} else if (typeof val === 'object') {
			result[key] = deepMerge({}, val)
		} else {
			result[key] = val
		}
	}
	for (let i = 0, l = arguments.length; i < l; i++) {
		forEach(arguments[i], assignValue)
	}
	return result
}

// 生成domId(字母开头)
function generateDomId() {
	const chars = 'abcdefghijklmnopqrstuvwxyz';
	let uuid = '';
	for (let i = 0; i < 6; i++) { // 生成5个字符
		uuid += chars[Math.floor(Math.random() * chars.length)];
	}
	const numChars = '0123456789';
	for (let i = 0; i < 10; i++) { // 生成10个数字
		uuid += numChars[Math.floor(Math.random() * numChars.length)];
	}
	return uuid;
}

// 是否是函数
function isFunction(func) {
	return typeof func === 'function' && typeof func.nodeType !== 'number'
}

// 数组对象根据字段key去重
function arrayFilterByKey(array = [], key = 'id') {
	return array.reduce((acc, current) => {
		const x = acc.find((item) => item[key] === current[key]);
		if (!x) {
			return acc.concat([current]);
		} else {
			return acc;
		}
	}, []);
}

export default {
	addStyle,
	addUnit,
	deepClone,
	throttle,
	deepMerge,
	generateDomId,
	isFunction,
	arrayFilterByKey
}
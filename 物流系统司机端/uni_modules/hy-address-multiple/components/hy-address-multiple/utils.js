/**
 * 深拷贝内容
 * @param originData 拷贝对象
 */
export function deepClone(originData) {
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
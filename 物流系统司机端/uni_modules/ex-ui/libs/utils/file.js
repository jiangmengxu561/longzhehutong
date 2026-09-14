// 根据文件mimeType获取文件类型
function getFileTypeByMime(mimeType) {
	switch (mimeType) {
		case "image/jpg":
		case "image/bmp":
		case "image/gif":
		case "image/vnd.microsoft.icon":
		case "image/jpeg":
		case "image/png":
		case "image/svg+xml":
		case "image/tiff":
		case "image/webp":
			return "image";
		case "audio/aac":
		case "audio/midi audio/x-midi":
		case "audio/midi":
		case "audio/x-midi":
		case "audio/mpeg":
		case "audio/ogg":
		case "audio/wav":
		case "audio/webm":
		case "audio/3gpp":
		case "audio/3gpp2":
			return "audio";
		case "video/x-msvideo":
		case "video/mpeg":
		case "video/ogg":
		case "video/webm":
		case "video/3gpp":
		case "video/3gpp2":
			return "video";
		case "application/pdf":
			return "pdf";
		case "application/vnd.ms-excel":
		case "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet":
			return "excel";
		case "application/msword":
		case "application/vnd.openxmlformats-officedocument.wordprocessingml.document":
			return "word";
		case "application/vnd.ms-powerpoint":
		case "application/vnd.openxmlformats-officedocument.presentationml.presentation":
			return "ppt";
		case "text/plain":
			return "txt";
		case "application/vnd.ms-fontobject":
		case "font/otf":
		case "font/ttf":
		case "font/woff":
		case "font/woff2":
			return "font";
		case "text/html":
			return "html";
		case "application/xhtml+xml":
			return "xhtml";
		case "application/xml":
			return "xml";
		case "application/vnd.mozilla.xul+xml":
			return "xul";
		case "application/x-abiword":
			return "abw";
		case "application/x-freearc":
			return "arc";
		case "application/vnd.amazon.ebook":
			return "azw";
		case "application/octet-stream":
			return "bin";
		case "application/x-bzip":
			return "bz";
		case "application/x-bzip2":
			return "bz2";
		case "application/x-csh":
			return "csh";
		case "text/css":
			return "css";
		case "text/csv":
			return "csv";
		case "application/epub+zip":
			return "epub";
		case "text/calendar":
			return "ics";
		case "application/java-archive":
			return "jar";
		case "text/javascript":
			return "js";
		case "application/json":
			return "json";
		case "application/ld+json":
			return "jsonld";
		case "text/javascript":
			return "mjs";
		case "application/vnd.apple.installer+xml":
			return "mpkg";
		case "application/vnd.oasis.opendocument.presentation":
			return "odp";
		case "application/vnd.oasis.opendocument.spreadsheet":
			return "ods";
		case "application/vnd.oasis.opendocument.text":
			return "odt";
		case "application/ogg":
			return "ogx";
		case "application/x-rar-compressed":
			return "rar";
		case "application/rtf":
			return "rtf";
		case "application/x-sh":
			return "sh";
		case "application/x-shockwave-flash":
			return "swf";
		case "application/x-tar":
			return "tar";
		case "application/vnd.visio":
			return "vsd";
		case "application/zip":
			return "zip";
		case "application/x-7z-compressed":
			return "7z";
		default:
			return null;
	}
}

// 根据文件名获取文件类型
function getFileTypeByName(name = '') {
	let suffix = name.split('.')?.[1]
	if (!suffix) return '';
	suffix = suffix.toLocaleLowerCase();
	const image = ['png', 'jpg', 'jpeg', 'bmp', 'gif', 'webp', 'tif', 'tiff'];
	if (image.includes(suffix)) return 'image';
	const video = ['avi', 'wmv', 'mpg', 'mpeg', 'mov', 'mp4', 'flv', 'mkv', 'webm', 'rm', 'rmvb'];
	if (video.includes(suffix)) return 'video';
	const audio = ['mp3', 'wav', 'aac', 'flac', 'ogg', 'm4a', 'wma', 'aiff', 'ac3', 'dts'];
	if (audio.includes(suffix)) return 'audio';
	const pdf = ['pdf'];
	if (pdf.includes(suffix)) return 'pdf';
	const word = ['doc', 'docx'];
	if (word.includes(suffix)) return 'word';
	const excel = ['xls', 'xlsx'];
	if (excel.includes(suffix)) return 'word';
	const ppt = ['ppt', 'pptx'];
	if (ppt.includes(suffix)) return 'word';
	const txt = ['txt', 'plain'];
	if (txt.includes(suffix)) return 'txt';
	return null;
}


// 根据文件url获取文件类型
function getFileTypeByUrl(url = '') {
	let suffix = url.substring(url.lastIndexOf(".") + 1)
	if (!suffix) return '';
	const image = ['png', 'jpg', 'jpeg', 'bmp', 'gif', 'webp', 'tif', 'tiff'];
	if (image.includes(suffix)) return 'image';
	const video = ['avi', 'wmv', 'mpg', 'mpeg', 'mov', 'mp4', 'flv', 'mkv', 'webm', 'rm', 'rmvb'];
	if (video.includes(suffix)) return 'video';
	const audio = ['mp3', 'wav', 'aac', 'flac', 'ogg', 'm4a', 'wma', 'aiff', 'ac3', 'dts'];
	if (audio.includes(suffix)) return 'audio';
	const pdf = ['pdf'];
	if (pdf.includes(suffix)) return 'pdf';
	const word = ['doc', 'docx'];
	if (word.includes(suffix)) return 'word';
	const excel = ['xls', 'xlsx'];
	if (excel.includes(suffix)) return 'word';
	const ppt = ['ppt', 'pptx'];
	if (ppt.includes(suffix)) return 'word';
	const txt = ['txt', 'plain'];
	if (txt.includes(suffix)) return 'txt';
	return null;
}

// 获取文件后缀名
function getFileExtNameByFileName(name = '') {
	let filename = ''
	if (name?.lastIndexOf(".") > -1) {
		filename = name.slice(name.lastIndexOf(".") + 1);
	}
	return filename
}


// 文件大小转KB MB
function getFileSize(byte) {
	let kb = byte / 1024
	let mb = kb / 1024
	if (mb > 1) {
		return parseFloat(mb).toFixed(2) + 'M'
	} else {
		return parseFloat(kb).toFixed(1) + 'k'
	}
}

function pickExclude(obj, keys) {
	// 某些情况下，type可能会为
	if (!['[object Object]', '[object File]'].includes(Object.prototype.toString.call(obj))) {
		return {}
	}
	return Object.keys(obj).reduce((prev, key) => {
		if (!keys.includes(key)) {
			prev[key] = obj[key]
		}
		return prev
	}, {})
}

function formatImage(res) {
	return res.tempFiles.map((item) => ({
		...pickExclude(item, ['path']),
		type: 'image',
		url: item.path,
		thumb: item.path,
		size: item.size,
		// #ifdef H5
		name: item.name
		// #endif
	}))
}

function formatVideo(res) {
	return [{
		...pickExclude(res, ['tempFilePath', 'thumbTempFilePath', 'errMsg']),
		type: 'video',
		url: res.tempFilePath,
		thumb: res.thumbTempFilePath,
		size: res.size,
		// #ifdef H5
		name: res.name
		// #endif
	}]
}

function formatMedia(res) {
	return res.tempFiles.map((item) => ({
		...pickExclude(item, ['fileType', 'thumbTempFilePath', 'tempFilePath']),
		type: res.type,
		url: item.tempFilePath,
		thumb: res.type === 'video' ? item.thumbTempFilePath : item.tempFilePath,
		size: item.size
	}))
}

function formatFile(res) {
	return res.tempFiles.map((item) => ({
		...pickExclude(item, ['path']),
		url: item.path,
		size: item.size,
		// #ifdef H5
		name: item.name,
		type: item.type
		// #endif 
	}))
}



// 选取图片
function chooseImage({
	multiple,
	capture = ['album', 'camera'], // album 从相册选图，camera 使用相机
	compressed = true,
	sizeType = ['original', 'compressed'], // original 原图，compressed 压缩图，默认二者都有
	camera = ['back', 'front'], // 后置前置
	extension, //根据文件拓展名过滤 微信小程序无效
	maxCount
}) {
	// #ifdef MP-WEIXIN
	wx.chooseMedia({
		count: multiple ? Math.min(maxCount, 9) : 1,
		mediaType: ['image'],
		sourceType: capture, //  ['album','camera']
		sizeType,
		camera, // ['back','front']
		success: (res) => resolve(formatMedia(res)),
		fail: reject
	})
	// #endif
	// #ifndef MP-WEIXIN
	uni.chooseImage({
		count: multiple ? Math.min(maxCount, 9) : 1,
		sourceType: capture,
		sizeType,
		extension,
		success: (res) => resolve(formatImage(res)),
		fail: reject
	})
	// #endif
}

// 选取视频
function chooseVideo({
	extension, // 根据文件拓展名过滤
	capture, // ['album', 'camera'] ['相册', '相机']
	compressed = true, // 是否压缩
	camera = ['front', 'back'], // APP、微信小程序、京东小程序
	maxDuration = 60 // 最大拍摄时长 (iOS支持，Android取决于ROM的拍照组件是否实现此功能，如果没实现此功能则忽略此属性。) 
}) {
	uni.chooseVideo({
		sourceType: capture,
		compressed,
		maxDuration,
		extension,
		camera,
		success: (res) => resolve(formatVideo(res)),
		fail: reject
	})
}

// 选取文件
function chooseFile({
	accept,
	multiple = true,
	capture = ['album', 'camera'],
	compressed = true,
	maxDuration = 60,
	sizeType = ['original', 'compressed'],
	camera = ['back', 'front'],
	extension,
	maxCount
}) {
	return new Promise((resolve, reject) => {
		switch (accept) {
			case 'image':
				// #ifdef MP-WEIXIN
				wx.chooseMedia({
					count: multiple ? Math.min(maxCount, 9) : 1,
					mediaType: ['image'],
					sourceType: capture,
					sizeType,
					camera,
					success: (res) => resolve(formatMedia(res)),
					fail: reject
				})
				// #endif
				// #ifndef MP-WEIXIN
				uni.chooseImage({
					count: multiple ? Math.min(maxCount, 9) : 1,
					sourceType: capture,
					sizeType,
					success: (res) => resolve(formatImage(res)),
					fail: reject
				})
				// #endif
				break
				// #ifdef MP-WEIXIN
				// 只有微信小程序才支持chooseMedia接口
			case 'media':
				wx.chooseMedia({
					count: multiple ? Math.min(maxCount, 9) : 1,
					sourceType: capture,
					maxDuration,
					sizeType,
					camera,
					success: (res) => resolve(formatMedia(res)),
					fail: reject
				})
				break
				// #endif
			case 'video':
				uni.chooseVideo({
					sourceType: capture,
					compressed,
					maxDuration,
					camera,
					success: (res) => resolve(formatVideo(res)),
					fail: reject
				})
				break
			default:
				// 此为保底选项，在accept不为上面任意一项的时候选取全部文件
				// #ifdef MP-WEIXIN
				wx.chooseMessageFile({
					count: multiple ? maxCount : 1,
					type: accept,
					extension,
					success: (res) => resolve(formatFile(res)),
					fail: reject
				})
				// #endif
				// #ifdef H5
				// 需要hx2.9.9以上才支持uni.chooseFile
				uni.chooseFile({
					count: multiple ? maxCount : 1,
					type: accept,
					extension,
					success: (res) => resolve(formatFile(res)),
					fail: reject
				})
				// #endif
		}
	})
}

export default {
	getFileTypeByMime,
	getFileTypeByName,
	getFileTypeByUrl,
	getFileExtNameByFileName,
	getFileSize,
	chooseFile
}
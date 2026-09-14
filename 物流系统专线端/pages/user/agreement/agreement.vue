<template>
	<view class="agreement">
		<rich-text :nodes="content"></rich-text>
		<!-- <u-parse :html="content"></u-parse> -->
	</view>
</template>

<script>
	export default {
		data() {
			return {
				imgUrl:this.$utils.imgUrl,
				type:'',
				content:''
			};
		},
		onLoad(option) {
			this.type = option.type
			if(option.type=='yinsi'){
				uni.setNavigationBarTitle({
					title:'隐私协议'
				})
				this.yinsi()
			}else if(option.type=='yonghu'){
				uni.setNavigationBarTitle({
					title:'用户协议'
				})
				this.yonghu()
			}else if(option.type=='xiafan'){
				uni.setNavigationBarTitle({
					title:'下单须知'
				})
				this.xiadan()
			}else if(option.type=='hetong'){
				uni.setNavigationBarTitle({
					title:'合同'
				})
				this.hetong()
			}else if(option.type=='guanyu'){
				uni.setNavigationBarTitle({
					title:'关于我们'
				})
				this.guanyu()
			}else if(option.type=='bangzhu'){
				uni.setNavigationBarTitle({
					title:'帮助中心'
				})
				this.bangzhu()
			}else if(option.type=='jifen'){
				uni.setNavigationBarTitle({
					title:'积分协议'
				})
				this.jifens()
			}else if(option.type=='shiming'){
				uni.setNavigationBarTitle({
					title:'实名认证服务协议'
				})
				this.shimings()
			}
		},
		methods:{
			jifens(){
				this.$httpapi('index/system', 'POST',{}).then(res => {
					this.content = res.data.jifentext.replace(/<img[^>]*>/ig, function(match, capture) {
						// match 图片路径
						let isExist = match.includes('https://'); // 是否包含https://
					
						if (!isExist) { // 不包含
							match = match.replace(/\ src="/gi, ' src="' + that.imgUrl);
						}
						return match
					})
					// this.content = res.data.content.replace(/\ src="/gi, ' src="' + this.imgUrl);
					this.content = this.content.replace(/\<img/gi, '<img style="max-width:100%; display: block;"');
					this.content = this.content.replace(/\ style="/gi, ' style="max-width:100%;');
					// this.content = this.content.replace(/\ src="/gi, ' src="'+this.imgUrl);
				})
			},
			xiadan(){
				this.$httpapi('Opinion/agreement', 'POST',{id:4}).then(res => {
					this.content = res.data.replace(/<img[^>]*>/ig, function(match, capture) {
						// match 图片路径
						let isExist = match.includes('https://'); // 是否包含https://
					
						if (!isExist) { // 不包含
							match = match.replace(/\ src="/gi, ' src="' + that.imgUrl);
						}
						return match
					})
					// this.content = res.data.content.replace(/\ src="/gi, ' src="' + this.imgUrl);
					this.content = this.content.replace(/\<img/gi, '<img style="max-width:100%; display: block;"');
					this.content = this.content.replace(/\ style="/gi, ' style="max-width:100%;');
					// this.content = this.content.replace(/\ src="/gi, ' src="'+this.imgUrl);
				})
			},
			hetong(){
				this.$httpapi('Opinion/agreement', 'POST',{id:5}).then(res => {
					this.content = res.data.replace(/<img[^>]*>/ig, function(match, capture) {
						// match 图片路径
						let isExist = match.includes('https://'); // 是否包含https://
					
						if (!isExist) { // 不包含
							match = match.replace(/\ src="/gi, ' src="' + that.imgUrl);
						}
						return match
					})
					// this.content = res.data.content.replace(/\ src="/gi, ' src="' + this.imgUrl);
					this.content = this.content.replace(/\<img/gi, '<img style="max-width:100%; display: block;"');
					this.content = this.content.replace(/\ style="/gi, ' style="max-width:100%;');
					// this.content = this.content.replace(/\ src="/gi, ' src="'+this.imgUrl);
				})
			},
			guanyu(){
				this.$httpapi('Opinion/agreement', 'POST',{id:3}).then(res => {
					this.content = res.data.replace(/<img[^>]*>/ig, function(match, capture) {
						// match 图片路径
						let isExist = match.includes('http'); // 是否包含https://
					
						if (!isExist) { // 不包含
							match = match.replace(/\ src="/gi, ' src="' + that.imgUrl);
						}
						return match
					})
					// this.content = res.data.content.replace(/\ src="/gi, ' src="' + this.imgUrl);
					this.content = this.content.replace(/\<img/gi, '<img style="max-width:100%; display: block;"');
					this.content = this.content.replace(/\ style="/gi, ' style="max-width:100%;');
					// this.content = this.content.replace(/\ src="/gi, ' src="'+this.imgUrl);
				})
			},
			yinsi(){
				this.$httpapi('Opinion/agreement', 'POST',{id:2}).then(res => {
					this.content = res.data.replace(/<img[^>]*>/ig, function(match, capture) {
						// match 图片路径
						let isExist = match.includes('https://'); // 是否包含https://
					
						if (!isExist) { // 不包含
							match = match.replace(/\ src="/gi, ' src="' + that.imgUrl);
						}
						return match
					})
					// this.content = res.data.content.replace(/\ src="/gi, ' src="' + this.imgUrl);
					this.content = this.content.replace(/\<img/gi, '<img style="max-width:100%; display: block;"');
					this.content = this.content.replace(/\ style="/gi, ' style="max-width:100%;');
				})
			},
			yonghu(){
				this.$httpapi('Opinion/agreement', 'POST',{id:1}).then(res => {
					this.content = res.data.replace(/<img[^>]*>/ig, function(match, capture) {
						// match 图片路径
						let isExist = match.includes('https://'); // 是否包含https://
					
						if (!isExist) { // 不包含
							match = match.replace(/\ src="/gi, ' src="' + that.imgUrl);
						}
						return match
					})
					// this.content = res.data.content.replace(/\ src="/gi, ' src="' + this.imgUrl);
					this.content = this.content.replace(/\<img/gi, '<img style="max-width:100%; display: block;"');
					this.content = this.content.replace(/\ style="/gi, ' style="max-width:100%;');
				})
			},
			bangzhu(){
				this.$httpapi('Index/help', 'POST',{}).then(res => {
					this.content = res.data.replace(/<img[^>]*>/ig, function(match, capture) {
						// match 图片路径
						let isExist = match.includes('https://'); // 是否包含https://
					
						if (!isExist) { // 不包含
							match = match.replace(/\ src="/gi, ' src="' + that.imgUrl);
						}
						return match
					})
					// this.content = res.data.content.replace(/\ src="/gi, ' src="' + this.imgUrl);
					this.content = this.content.replace(/\<img/gi, '<img style="max-width:100%; display: block;"');
					this.content = this.content.replace(/\ style="/gi, ' style="max-width:100%;');
				})
			},
			shimings(){
				this.$httpapi('Index/shiming', 'POST',{}).then(res => {
					this.content = res.data.replace(/<img[^>]*>/ig, function(match, capture) {
						// match 图片路径
						let isExist = match.includes('https://'); // 是否包含https://
					
						if (!isExist) { // 不包含
							match = match.replace(/\ src="/gi, ' src="' + that.imgUrl);
						}
						return match
					})
					// this.content = res.data.content.replace(/\ src="/gi, ' src="' + this.imgUrl);
					this.content = this.content.replace(/\<img/gi, '<img style="max-width:100%; display: block;"');
					this.content = this.content.replace(/\ style="/gi, ' style="max-width:100%;');
				})
			},
		}
	}
</script>

<style lang="scss">
	.agreement{
		padding:20rpx;
		font-size: 28rpx;
		font-weight: 500;
		color: #000000;
	}
</style>

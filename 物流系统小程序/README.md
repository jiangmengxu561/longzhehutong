# 物流系统小程序 - 代码文档

## 项目简介

物流系统小程序是一个基于 uni-app 框架开发的微信小程序，主要用于物流订单管理、地址管理、用户管理等功能。系统支持用户通过手机号快捷登录，提供完整的物流服务流程。

## 技术栈

- **框架**: uni-app (Vue 2)
- **开发工具**: HBuilderX
- **目标平台**: 微信小程序
- **UI组件**: uni-ui、uv-ui、lime-ui 等
- **状态管理**: Vuex (通过 mixin 管理全局状态)
- **HTTP请求**: uni.request (封装在 https.js 中)

## 项目结构

```
物流系统小程序/
├── App.vue                 # 应用入口文件
├── main.js                  # 主入口文件
├── manifest.json            # 应用配置文件
├── pages.json               # 页面路由配置
├── uni.scss                 # 全局样式文件
├── https/                   # HTTP请求相关
│   ├── https.js            # API请求封装
│   ├── utils.js            # 工具函数
│   ├── mixin.js            # 混入文件
│   ├── pickFile.js         # 文件选择工具
│   ├── shareUrl.js         # 分享URL处理
│   └── qqmap-wx-jssdk.min.js  # 腾讯地图SDK
├── pages/                   # 页面目录
│   ├── index/              # 首页
│   │   ├── index.vue       # 首页主文件
│   │   └── SubmitOrder/    # 提交订单相关
│   ├── login/              # 登录页
│   ├── order/              # 订单相关
│   └── user/               # 用户中心
├── static/                  # 静态资源
│   └── images/             # 图片资源
└── uni_modules/            # uni-app插件模块
```

## 核心功能模块

### 1. 用户认证模块

**文件**: `pages/login/login.vue`

**功能**:
- 微信手机号快捷登录
- 推荐来源渠道选择
- 用户协议确认
- 二维码扫码邀请参数处理

**主要方法**:
- `decryptPhoneNumber()`: 解密手机号并登录
- `getChannelList()`: 获取推荐渠道列表
- `agreement()`: 查看用户协议

### 2. 首页模块

**文件**: `pages/index/index.vue`

**功能**:
- 轮播图展示
- 公告通知
- 装货/卸货地址选择
- 地址快速识别（粘贴识别）
- 地址簿管理
- 找车类型选择（专车/拼车）
- 提交订单

**主要方法**:
- `zhuangAddressClick()`: 选择装货地址
- `xieAddressClick()`: 选择卸货地址
- `swapAddress()`: 交换装货/卸货地址
- `openQuickInput()`: 打开快速输入（地址粘贴识别）
- `submitOrder()`: 提交订单

### 3. 订单管理模块

**文件**: `pages/order/order.vue`

**功能**:
- 订单列表展示
- 订单状态筛选
- 订单详情查看
- 订单支付
- 物流轨迹查询

### 4. 用户中心模块

**文件**: `pages/user/user.vue`

**功能**:
- 个人信息管理
- 地址簿管理
- 我的账单
- 我的发票
- 我的下级（客户管理）
- 价格比例设置
- 投诉意见
- 设置中心

## API 接口说明

### 基础配置

**API 基础地址**: `https://lzwl.longzhehutong.cn/index.php/api/`

**文件**: `https/https.js`

### 主要接口方法

#### 1. apiapi(url, method, data)

通用 API 请求方法

**参数**:
- `url`: 接口路径
- `method`: 请求方法 (GET/POST)
- `data`: 请求参数对象

**特性**:
- 自动添加 token 到请求头和参数中
- 自动处理登录失效（code=0 或 code=500）
- 统一错误提示

**使用示例**:
```javascript
this.$httpapi('order/list', 'GET', {
  page: 1,
  limit: 10
}).then(res => {
  console.log(res)
})
```

#### 2. upShop(url, file)

文件上传方法（用于店铺相关）

**参数**:
- `url`: 上传接口路径
- `file`: 文件路径

#### 3. up(url, file, type, typex)

通用文件上传方法

**参数**:
- `url`: 上传接口路径
- `file`: 文件路径
- `type`: 文件类型标识 (1:商品视频, 2:商品详情图, 3:文章图片)
- `typex`: 额外类型参数

#### 4. login(data)

用户登录方法

**参数**:
- `data`: 登录参数（包含手机号等信息）

**返回**: Promise，登录成功后会保存 token 到本地存储

## 工具函数说明

### utils.js

**文件**: `https/utils.js`

**主要方法**:

#### 1. 验证相关
- `isMobile`: 手机号验证正则
- `isIdCard`: 身份证号验证正则
- `isEmail(email)`: 邮箱验证
- `isPassword(val)`: 密码强度验证

#### 2. 时间格式化
- `YMDHIS(timestamp)`: 时间戳转 "年-月-日 时:分:秒"
- `YMD(timestamp)`: 时间戳转 "年-月-日"
- `MDHI(timestamp)`: 时间戳转 "月-日 时:分"

#### 3. 文件处理
- `checkFile(fileValue)`: 检查文件类型（视频/图片/Office文档）
- `previewImage(e)`: 预览图片

#### 4. 导航相关
- `goNext(url)`: 跳转到下一页
- `goBack(num)`: 返回上一页
- `goSwitchTab(url)`: 跳转 tabBar 页面
- `goReLaunch(url)`: 关闭所有页面并跳转
- `goRedirectTo(url)`: 关闭当前页并跳转

#### 5. HTTP 请求
- `axiosToken(method, url, data, showLoads)`: 带 token 的请求
- `axios(method, url, data, showLoads)`: 不带 token 的请求

#### 6. UI 相关
- `showtt(title, icon)`: 显示提示信息
- `callPhone(phone)`: 拨打电话
- `navBarHeight()`: 获取导航栏高度
- `windowHeight()`: 获取窗口高度

**使用示例**:
```javascript
// 在组件中使用
this.$utils.showtt('操作成功', 'success')
this.$utils.YMD(Date.now()) // 返回 "2024-01-01"
```

### mixin.js

**文件**: `https/mixin.js`

**功能**: 提供系统信息混入，用于统一管理状态栏、导航栏高度等

**使用方式**:
```javascript
import { systemInfo } from '@/https/mixin.js'
export default {
  mixins: [systemInfo],
  // ...
}
```

**提供的方法**:
- `getSystemInfo()`: 获取系统信息（状态栏高度、导航栏高度等）

## 全局配置

### App.vue

**全局数据**:
- `statusBarHeight`: 状态栏高度
- `navigationBarHeight`: 导航栏高度
- `navHeight`: 总导航高度

**生命周期**:
- `onLaunch`: 应用启动时执行
  - 计算导航栏高度
  - 处理二维码扫码参数（invitation）
- `onShow`: 应用显示时执行
- `onHide`: 应用隐藏时执行

**全局样式**:
- 页面背景色: `#F7F6FA`
- 基础字体大小: `28rpx`
- 提供常用 flex 布局类

### pages.json

**页面配置**:
- 首页: `pages/index/index`
- 订单页: `pages/order/order`
- 用户中心: `pages/user/user`
- 登录页: `pages/login/login`

**TabBar 配置**:
- 首页、订单、我的（三个底部导航）

**全局样式**:
- 导航栏文字颜色: 黑色
- 导航栏背景色: `#F8F8F8`
- 页面背景色: `#F8F8F8`

### manifest.json

**应用信息**:
- 应用名称: 物流系统小程序
- AppID: `__UNI__9C70BE2`
- 版本号: 1.0.0

**微信小程序配置**:
- AppID: `wx1e4884865032dd4f`
- 关闭 URL 校验: `urlCheck: false`
- 权限配置: 用户位置权限

## 数据存储

使用 `uni.setStorageSync()` 和 `uni.getStorageSync()` 进行本地数据存储

**主要存储项**:
- `token`: 用户登录 token
- `id`: 用户ID
- `qrcode_invitation`: 二维码邀请参数
- `qrcode_scene`: 二维码 scene 参数
- `zaiOrder`: 在途订单标识

## 页面路由

### 主要页面路径

| 页面 | 路径 | 说明 |
|------|------|------|
| 首页 | `/pages/index/index` | 主页面，提交订单入口 |
| 订单列表 | `/pages/order/order` | 订单管理 |
| 用户中心 | `/pages/user/user` | 个人中心 |
| 登录页 | `/pages/login/login` | 用户登录 |
| 提交订单 | `/pages/index/SubmitOrder/SubmitOrder` | 订单提交 |
| 订单详情 | `/pages/order/orderDetail/orderDetail` | 订单详情 |
| 地址簿 | `/pages/user/address/address` | 地址管理 |
| 个人信息 | `/pages/user/personalData/personalData` | 个人信息 |
| 我的账单 | `/pages/user/Mybill/Mybill` | 账单查询 |
| 我的发票 | `/pages/user/Myinvoice/Myinvoice` | 发票管理 |
| 我的下级 | `/pages/user/subordinate/subordinate` | 客户管理 |
| 设置中心 | `/pages/user/set/set` | 系统设置 |

## 开发规范

### 代码风格

1. **命名规范**:
   - 组件名使用 PascalCase
   - 方法名使用 camelCase
   - 常量使用 UPPER_SNAKE_CASE

2. **文件组织**:
   - 每个页面独立文件夹
   - 公共组件放在 `components` 目录
   - 工具函数放在 `https` 目录

3. **API 调用**:
   - 统一使用 `this.$httpapi()` 方法
   - 错误处理在 `https.js` 中统一处理
   - 登录失效自动跳转登录页

4. **样式规范**:
   - 使用 rpx 作为单位
   - 使用 flex 布局
   - 颜色值统一管理

### 注意事项

1. **Token 管理**:
   - 所有需要认证的接口会自动添加 token
   - Token 失效会自动清除并跳转登录页

2. **平台兼容**:
   - 使用条件编译处理不同平台差异
   - `#ifdef MP-WEIXIN`: 微信小程序特有代码
   - `#ifdef APP-PLUS`: App 特有代码

3. **图片资源**:
   - 静态图片放在 `static/images` 目录
   - 使用相对路径或绝对路径引用

4. **导航栏**:
   - 大部分页面使用自定义导航栏 (`navigationStyle: "custom"`)
   - 导航栏高度通过 `getSystemInfo()` 获取

## 常见问题

### 1. 登录失效处理

当接口返回 `code=0` 或 `code=500` 且 `msg='请登录'` 时，系统会：
- 清除本地 token
- 弹出提示框
- 跳转到登录页

### 2. 地址选择

支持三种方式选择地址：
1. 点击地址区域选择
2. 地址粘贴识别（快速输入）
3. 从地址簿选择

### 3. 二维码参数处理

扫码进入小程序时，会解析 scene 参数中的 invitation 值，并保存到本地存储，用于推荐关系绑定。

## 更新日志

### v1.0.0
- 初始版本发布
- 实现基础功能：登录、订单管理、地址管理、用户中心

## 联系方式

如有问题，请联系开发团队。

---

**文档生成时间**: 2024年
**最后更新**: 2024年

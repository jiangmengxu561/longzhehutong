# API 接口文档

## 基础信息

- **API 基础地址**: `https://lzwl.longzhehutong.cn/index.php/api/`
- **图片服务器地址**: `https://lzwl.longzhehutong.cn`
- **请求方式**: GET / POST
- **数据格式**: JSON
- **字符编码**: UTF-8

## 认证方式

所有需要认证的接口都需要在请求头中携带 token：

```
Header:
  Content-Type: application/json;charset=utf-8;
  Authori-zation: Bearer {token}
  token: {token}
```

或者在请求参数中添加 token：
```
{
  token: "{token}",
  ...其他参数
}
```

## 通用响应格式

### 成功响应
```json
{
  "code": 200,
  "msg": "操作成功",
  "data": {
    // 具体数据
  }
}
```

### 失败响应
```json
{
  "code": 500,
  "msg": "错误信息",
  "data": null
}
```

### 登录失效响应
```json
{
  "code": 0,
  "msg": "请登录",
  "data": null
}
```
或
```json
{
  "code": 500,
  "msg": "请登录后操作",
  "data": null
}
```

## 接口列表

### 1. 用户相关接口

#### 1.1 用户登录
- **接口地址**: `user/login-app/sign`
- **请求方式**: GET
- **是否需要认证**: 否

**请求参数**:
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| phone | string | 是 | 手机号 |
| code | string | 是 | 微信授权码 |
| encryptedData | string | 是 | 加密数据 |
| iv | string | 是 | 初始向量 |
| channel_id | string | 否 | 推荐渠道ID |
| invitation | string | 否 | 邀请码 |

**响应示例**:
```json
{
  "code": 200,
  "message": "登录成功",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "user_id": "123",
    "username": "用户昵称",
    "phone": "13800138000"
  }
}
```

#### 1.2 获取用户信息
- **接口地址**: `user/info`
- **请求方式**: GET
- **是否需要认证**: 是

**请求参数**: 无

**响应示例**:
```json
{
  "code": 200,
  "msg": "获取成功",
  "data": {
    "id": "123",
    "username": "用户昵称",
    "phone": "13800138000",
    "avatar": "头像URL",
    "balance": "1000.00"
  }
}
```

#### 1.3 更新用户信息
- **接口地址**: `user/update`
- **请求方式**: POST
- **是否需要认证**: 是

**请求参数**:
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| username | string | 否 | 用户名 |
| avatar | string | 否 | 头像URL |

### 2. 地址相关接口

#### 2.1 获取地址列表
- **接口地址**: `address/list`
- **请求方式**: GET
- **是否需要认证**: 是

**请求参数**:
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| page | int | 否 | 页码，默认1 |
| limit | int | 否 | 每页数量，默认10 |

**响应示例**:
```json
{
  "code": 200,
  "msg": "获取成功",
  "data": {
    "list": [
      {
        "id": "1",
        "user_name": "张三",
        "mobile": "13800138000",
        "address": "北京市朝阳区xxx街道xxx号",
        "latitude": "39.908823",
        "longitude": "116.397470",
        "is_default": 1
      }
    ],
    "total": 10
  }
}
```

#### 2.2 添加地址
- **接口地址**: `address/add`
- **请求方式**: POST
- **是否需要认证**: 是

**请求参数**:
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| user_name | string | 是 | 收货人姓名 |
| mobile | string | 是 | 手机号 |
| address | string | 是 | 详细地址 |
| latitude | string | 是 | 纬度 |
| longitude | string | 是 | 经度 |
| is_default | int | 否 | 是否默认地址，0否1是 |

#### 2.3 更新地址
- **接口地址**: `address/update`
- **请求方式**: POST
- **是否需要认证**: 是

**请求参数**: 同添加地址，需额外传递 `id` 参数

#### 2.4 删除地址
- **接口地址**: `address/delete`
- **请求方式**: POST
- **是否需要认证**: 是

**请求参数**:
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| id | int | 是 | 地址ID |

### 3. 订单相关接口

#### 3.1 获取订单列表
- **接口地址**: `order/list`
- **请求方式**: GET
- **是否需要认证**: 是

**请求参数**:
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| page | int | 否 | 页码，默认1 |
| limit | int | 否 | 每页数量，默认10 |
| status | int | 否 | 订单状态筛选 |

**订单状态说明**:
- 0: 待接单
- 1: 已接单
- 2: 运输中
- 3: 已完成
- 4: 已取消

**响应示例**:
```json
{
  "code": 200,
  "msg": "获取成功",
  "data": {
    "list": [
      {
        "id": "1001",
        "order_no": "WL202401010001",
        "start_address": "北京市朝阳区xxx",
        "end_address": "上海市浦东新区xxx",
        "status": 1,
        "status_text": "已接单",
        "price": "500.00",
        "create_time": "2024-01-01 10:00:00"
      }
    ],
    "total": 20
  }
}
```

#### 3.2 获取订单详情
- **接口地址**: `order/detail`
- **请求方式**: GET
- **是否需要认证**: 是

**请求参数**:
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| id | int | 是 | 订单ID |

**响应示例**:
```json
{
  "code": 200,
  "msg": "获取成功",
  "data": {
    "id": "1001",
    "order_no": "WL202401010001",
    "start_address": "北京市朝阳区xxx",
    "end_address": "上海市浦东新区xxx",
    "start_user_name": "张三",
    "start_mobile": "13800138000",
    "end_user_name": "李四",
    "end_mobile": "13900139000",
    "status": 1,
    "price": "500.00",
    "create_time": "2024-01-01 10:00:00",
    "driver_info": {
      "name": "王五",
      "mobile": "13700137000",
      "car_no": "京A12345"
    }
  }
}
```

#### 3.3 提交订单
- **接口地址**: `order/create`
- **请求方式**: POST
- **是否需要认证**: 是

**请求参数**:
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| start_address_id | int | 是 | 装货地址ID |
| end_address_id | int | 是 | 卸货地址ID |
| car_type | int | 是 | 找车类型，0专车1拼车 |
| goods_name | string | 否 | 货物名称 |
| goods_weight | string | 否 | 货物重量 |
| goods_volume | string | 否 | 货物体积 |
| remark | string | 否 | 备注 |

**响应示例**:
```json
{
  "code": 200,
  "msg": "订单提交成功",
  "data": {
    "order_id": "1001",
    "order_no": "WL202401010001"
  }
}
```

#### 3.4 取消订单
- **接口地址**: `order/cancel`
- **请求方式**: POST
- **是否需要认证**: 是

**请求参数**:
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| id | int | 是 | 订单ID |
| cancel_reason | string | 否 | 取消原因 |

#### 3.5 获取物流轨迹
- **接口地址**: `order/track`
- **请求方式**: GET
- **是否需要认证**: 是

**请求参数**:
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| order_id | int | 是 | 订单ID |

**响应示例**:
```json
{
  "code": 200,
  "msg": "获取成功",
  "data": {
    "list": [
      {
        "time": "2024-01-01 10:00:00",
        "status": "订单已创建",
        "location": "北京市朝阳区"
      },
      {
        "time": "2024-01-01 11:00:00",
        "status": "司机已接单",
        "location": "北京市朝阳区"
      }
    ]
  }
}
```

### 4. 支付相关接口

#### 4.1 订单支付
- **接口地址**: `order/pay`
- **请求方式**: POST
- **是否需要认证**: 是

**请求参数**:
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| order_id | int | 是 | 订单ID |
| pay_type | string | 是 | 支付方式：wxpay/alipay |

**响应示例**:
```json
{
  "code": 200,
  "msg": "支付成功",
  "data": {
    "pay_no": "PAY202401010001",
    "pay_time": "2024-01-01 12:00:00"
  }
}
```

### 5. 文件上传接口

#### 5.1 通用文件上传
- **接口地址**: `common/upload`
- **请求方式**: POST (multipart/form-data)
- **是否需要认证**: 是

**请求参数**:
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| file | file | 是 | 文件 |
| type | string | 否 | 文件类型：registerimage/goods_video/goods_body_image |

**响应示例**:
```json
{
  "code": 200,
  "msg": "上传成功",
  "data": {
    "url": "https://lzwl.longzhehutong.cn/uploads/2024/01/01/xxx.jpg"
  }
}
```

### 6. 其他接口

#### 6.1 获取轮播图
- **接口地址**: `banner/list`
- **请求方式**: GET
- **是否需要认证**: 否

**响应示例**:
```json
{
  "code": 200,
  "msg": "获取成功",
  "data": {
    "list": [
      "/uploads/banner/1.jpg",
      "/uploads/banner/2.jpg"
    ]
  }
}
```

#### 6.2 获取公告列表
- **接口地址**: `notice/list`
- **请求方式**: GET
- **是否需要认证**: 否

**请求参数**:
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| limit | int | 否 | 数量，默认5 |

**响应示例**:
```json
{
  "code": 200,
  "msg": "获取成功",
  "data": {
    "list": [
      {
        "id": "1",
        "title": "系统维护通知",
        "content": "系统将于...",
        "create_time": "2024-01-01 10:00:00"
      }
    ]
  }
}
```

#### 6.3 获取推荐渠道列表
- **接口地址**: `channel/list`
- **请求方式**: GET
- **是否需要认证**: 否

**响应示例**:
```json
{
  "code": 200,
  "msg": "获取成功",
  "data": {
    "list": [
      {
        "id": "1",
        "name": "朋友推荐"
      },
      {
        "id": "2",
        "name": "广告推广"
      }
    ]
  }
}
```

## 错误码说明

| 错误码 | 说明 | 处理方式 |
|--------|------|----------|
| 200 | 操作成功 | - |
| 0 | 请登录 | 清除token，跳转登录页 |
| 500 | 操作失败 | 显示错误信息 |
| 401 | 未授权 | 清除token，跳转登录页 |
| 410000 | Token失效 | 清除token，跳转登录页 |
| 13001 | Token失效 | 清除token，跳转登录页 |

## 使用示例

### JavaScript 调用示例

```javascript
// 在 Vue 组件中使用
export default {
  methods: {
    // 获取订单列表
    async getOrderList() {
      try {
        const res = await this.$httpapi('order/list', 'GET', {
          page: 1,
          limit: 10,
          status: 1
        })
        if (res.code === 200) {
          this.orderList = res.data.list
        }
      } catch (error) {
        console.error('获取订单列表失败:', error)
      }
    },
    
    // 提交订单
    async submitOrder() {
      try {
        const res = await this.$httpapi('order/create', 'POST', {
          start_address_id: this.startAddressId,
          end_address_id: this.endAddressId,
          car_type: this.carType,
          goods_name: this.goodsName,
          remark: this.remark
        })
        if (res.code === 200) {
          uni.showToast({
            title: '订单提交成功',
            icon: 'success'
          })
          // 跳转到订单详情
          uni.navigateTo({
            url: `/pages/order/orderDetail/orderDetail?id=${res.data.order_id}`
          })
        }
      } catch (error) {
        console.error('提交订单失败:', error)
      }
    },
    
    // 上传文件
    async uploadFile(filePath) {
      try {
        const res = await this.$upShop('common/upload', filePath)
        if (res.statusCode === 200) {
          const data = JSON.parse(res.data)
          if (data.code === 200) {
            return data.data.url
          }
        }
      } catch (error) {
        console.error('文件上传失败:', error)
      }
    }
  }
}
```

## 注意事项

1. **Token 管理**:
   - Token 会自动添加到所有请求中
   - Token 失效时会自动清除并跳转登录页
   - Token 存储在本地 `uni.getStorageSync('token')`

2. **请求超时**:
   - 默认请求超时时间由 uni-app 控制
   - 建议在网络较差时增加超时处理

3. **文件上传**:
   - 支持图片、视频等文件类型
   - 文件大小限制由服务器控制
   - 上传进度可通过 `uploadTask.onProgressUpdate()` 监听

4. **错误处理**:
   - 所有接口错误都会在 `https.js` 中统一处理
   - 登录失效会自动跳转登录页
   - 其他错误会通过 `uni.showToast` 提示用户

---

**文档版本**: v1.0.0  
**最后更新**: 2024年

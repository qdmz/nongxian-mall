import request from './request'

/** 个人信息 */
export function getProfile() {
  return request.get('/user/profile')
}

/** 更新个人信息 {nickname, avatar, gender, real_name, birthday} */
export function updateProfile(data) {
  return request.put('/user/profile', data)
}

/** 修改密码 */
export function changePassword(data) {
  return request.post('/user/change-password', data)
}

/** 地址列表 */
export function getAddresses() {
  return request.get('/user/addresses')
}

/** 新增地址 */
export function addAddress(data) {
  return request.post('/user/addresses', data)
}

/** 修改地址 */
export function updateAddress(id, data) {
  return request.put(`/user/addresses/${id}`, data)
}

/** 删除地址 */
export function deleteAddress(id) {
  return request.delete(`/user/addresses/${id}`)
}

/** 钱包 + 流水 */
export function getWallet(params) {
  return request.get('/user/wallet', { params })
}

/** 充值 {amount, pay_type} 返回 pay_url */
export function recharge(data) {
  return request.post('/user/recharge', data)
}

/** 充值记录 */
export function getRechargeOrders(params) {
  return request.get('/user/recharge-orders', { params })
}

/** 站内消息 */
export function getNotifications(params) {
  return request.get('/user/notifications', { params })
}

/** 全部已读 */
export function readAllNotifications() {
  return request.post('/user/notifications/read')
}

/** 绑定信息 */
export function getBindInfo() {
  return request.get('/user/bind-info')
}

/** 绑定手机 {phone, code} */
export function bindPhone(data) {
  return request.post('/user/bind-phone', data)
}

/** 绑定邮箱 {email, code} */
export function bindEmail(data) {
  return request.post('/user/bind-email', data)
}

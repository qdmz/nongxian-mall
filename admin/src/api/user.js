import request from './request'

// 用户列表
export function getUsers(params) {
  return request.get('/admin/users', { params })
}

// 用户详情（含近订单/流水）
export function getUserDetail(id) {
  return request.get(`/admin/users/${id}`)
}

// 编辑用户
export function updateUser(id, data) {
  return request.put(`/admin/users/${id}`, data)
}

// 调整余额
export function adjustBalance(id, data) {
  return request.post(`/admin/users/${id}/adjust-balance`, data)
}

// 发站内信
export function sendNotification(data) {
  return request.post('/admin/users/send-notification', data)
}

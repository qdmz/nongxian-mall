import request from './request'

// 登录
export function login(data) {
  return request.post('/admin/auth/login', data)
}

// 当前管理员信息
export function getProfile() {
  return request.get('/admin/auth/profile')
}

// 更新个人信息
export function updateProfile(data) {
  return request.put('/admin/auth/profile', data)
}

// 修改密码
export function changePassword(data) {
  return request.post('/admin/auth/change-password', data)
}

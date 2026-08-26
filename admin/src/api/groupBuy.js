import request from './request'

// 拼团活动列表
export function getActivities(params) {
  return request.get('/admin/group-buy/activities', { params })
}

// 创建拼团活动
export function createActivity(data) {
  return request.post('/admin/group-buy/activities', data)
}

// 修改拼团活动
export function updateActivity(id, data) {
  return request.put(`/admin/group-buy/activities/${id}`, data)
}

// 停用拼团活动
export function deleteActivity(id) {
  return request.delete(`/admin/group-buy/activities/${id}`)
}

// 拼团单列表
export function getGroups(params) {
  return request.get('/admin/group-buy/groups', { params })
}

// 拼团详情（含 members）
export function getGroupDetail(id) {
  return request.get(`/admin/group-buy/groups/${id}`)
}

import request from './request'

// 轮播图列表
export function getBanners() {
  return request.get('/admin/banners')
}

// 新增轮播图
export function createBanner(data) {
  return request.post('/admin/banners', data)
}

// 修改轮播图
export function updateBanner(id, data) {
  return request.put(`/admin/banners/${id}`, data)
}

// 删除轮播图
export function deleteBanner(id) {
  return request.delete(`/admin/banners/${id}`)
}

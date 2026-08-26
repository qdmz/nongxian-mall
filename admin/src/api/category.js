import request from './request'

// 分类列表（含 product_count）
export function getCategories() {
  return request.get('/admin/categories')
}

// 新增分类
export function createCategory(data) {
  return request.post('/admin/categories', data)
}

// 修改分类
export function updateCategory(id, data) {
  return request.put(`/admin/categories/${id}`, data)
}

// 删除分类
export function deleteCategory(id) {
  return request.delete(`/admin/categories/${id}`)
}

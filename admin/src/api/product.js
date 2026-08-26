import request from './request'

// 商品列表
export function getProducts(params) {
  return request.get('/admin/products', { params })
}

// 商品详情（含 skus）
export function getProductDetail(id) {
  return request.get(`/admin/products/${id}`)
}

// 新增商品
export function createProduct(data) {
  return request.post('/admin/products', data)
}

// 修改商品
export function updateProduct(id, data) {
  return request.put(`/admin/products/${id}`, data)
}

// 上下架切换
export function toggleProductStatus(id) {
  return request.post(`/admin/products/${id}/toggle-status`)
}

// 修改库存
export function updateProductStock(id, stock) {
  return request.post(`/admin/products/${id}/update-stock`, { stock })
}

// 删除商品
export function deleteProduct(id) {
  return request.delete(`/admin/products/${id}`)
}

import request from './request'

/** 购物车列表 */
export function getCart() {
  return request.get('/cart')
}

/** 加入购物车 {product_id, sku_id, quantity} */
export function addCart(data) {
  return request.post('/cart', data)
}

/** 更新购物车 {id, quantity, selected} */
export function updateCart(data) {
  return request.put('/cart', data)
}

/** 删除购物车项 */
export function deleteCart(id) {
  return request.delete(`/cart/${id}`)
}

/** 清空失效商品 */
export function clearInvalid() {
  return request.post('/cart/clear-invalid')
}

import request from './request'

/**
 * 创建订单
 * @param {Object} data {address_id, items: [{product_id, sku_id, quantity}], remark, use_balance, from_cart}
 */
export function createOrder(data) {
  return request.post('/orders', data)
}

/** 订单列表 status: 0待支付 1待发货 2已发货 3已完成 */
export function getOrders(params) {
  return request.get('/orders', { params })
}

/** 订单详情 */
export function getOrderDetail(id) {
  return request.get(`/orders/${id}`)
}

/** 取消订单（仅待支付） */
export function cancelOrder(id) {
  return request.post(`/orders/${id}/cancel`)
}

/** 确认收货 */
export function confirmOrder(id) {
  return request.post(`/orders/${id}/confirm`)
}

/** 申请退款 {reason} */
export function applyRefund(id, reason) {
  return request.post(`/orders/${id}/apply-refund`, { reason })
}

/** 发起支付 {pay_type: alipay/wxpay/qqpay}，返回 pay_url */
export function payOrder(id, payType) {
  return request.post(`/orders/${id}/pay`, { pay_type: payType })
}

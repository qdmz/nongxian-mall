import request from './request'

// 订单列表
export function getOrders(params) {
  return request.get('/admin/orders', { params })
}

// 订单详情
export function getOrderDetail(id) {
  return request.get(`/admin/orders/${id}`)
}

// 发货 {company, tracking_no} 或 {courier_name, courier_phone}
export function deliverOrder(id, data) {
  return request.post(`/admin/orders/${id}/deliver`, data)
}

// 完成订单
export function completeOrder(id) {
  return request.post(`/admin/orders/${id}/complete`)
}

// 取消订单 {reason}
export function cancelOrder(id, data) {
  return request.post(`/admin/orders/${id}/cancel`, data)
}

// 退款列表
export function getRefunds(params) {
  return request.get('/admin/refunds', { params })
}

// 处理退款 {action: approve/reject, reject_reason}
export function handleRefund(id, data) {
  return request.post(`/admin/refunds/${id}/handle`, data)
}

// 配送列表
export function getDeliveries(params) {
  return request.get('/admin/deliveries', { params })
}

// 添加配送轨迹
export function addDeliveryTrack(id, data) {
  return request.post(`/admin/deliveries/${id}/track`, data)
}

import axios from 'axios'
import request from './request'

/**
 * 创建订单
 * @param {Object} data {address_id, items: [{product_id, sku_id, quantity}], remark, use_balance, from_cart}
 */
export function createOrder(data) {
  return request.post('/orders', data)
}

/**
 * 运费预估（2026-10-14b，与下单时服务端计算一致）；静默失败，不弹 toast
 * @param {Object} data {address_id, items: [{product_id, sku_id, quantity}]}
 * @returns {Promise<number>} 运费金额，失败返回 0
 */
export async function getFreight(data) {
  try {
    const token = localStorage.getItem('token') || ''
    const res = await axios.post('/api/orders/freight', data, {
      headers: token ? { Authorization: 'Bearer ' + token } : {},
      timeout: 15000
    })
    const body = res.data
    return body && body.code === 0 ? Number(body.data?.freight) || 0 : 0
  } catch (e) {
    return 0
  }
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

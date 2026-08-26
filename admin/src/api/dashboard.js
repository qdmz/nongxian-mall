import request from './request'

// 概览（today/yesterday/month/total）
export function getDashboard() {
  return request.get('/admin/dashboard')
}

// 销售趋势
export function getSalesTrend(days = 30) {
  return request.get('/admin/dashboard/sales-trend', { params: { days } })
}

// 商品排行
export function getProductRank(days = 30, limit = 20) {
  return request.get('/admin/dashboard/product-rank', { params: { days, limit } })
}

// 分类销售占比
export function getCategorySales(days = 30) {
  return request.get('/admin/dashboard/category-sales', { params: { days } })
}

// 最新订单
export function getLatestOrders() {
  return request.get('/admin/dashboard/latest-orders')
}

// 库存预警
export function getLowStock() {
  return request.get('/admin/dashboard/low-stock')
}

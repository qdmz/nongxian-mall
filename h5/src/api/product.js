import request from './request'

/** 首页聚合数据 */
export function getHome() {
  return request.get('/home')
}

/**
 * 商品列表
 * @param {Object} params {category_id, keyword, is_red, is_hot, is_recommend, is_new, order_by, page, page_size}
 */
export function getProducts(params) {
  return request.get('/products', { params })
}

/** 商品详情 */
export function getProductDetail(id) {
  return request.get(`/products/${id}`)
}

/** 分类树 */
export function getCategories() {
  return request.get('/categories')
}

/** 搜索热词 */
export function getHotKeywords() {
  return request.get('/search/hot-keywords')
}

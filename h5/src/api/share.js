import request from './request'

/** 我的推广码/链接/统计 */
export function getShareCode() {
  return request.get('/share/code')
}

/** 商品分享卡片 */
export function getProductShare(id) {
  return request.get(`/share/product/${id}`)
}

/** 奖励明细 */
export function getRewards(params) {
  return request.get('/share/rewards', { params })
}

/** 我的团队（邀请的人） */
export function getMyTeam(params) {
  return request.get('/share/my-team', { params })
}

/** 点击追踪 {code} */
export function trackClick(code) {
  return request.post('/share/track', { code })
}

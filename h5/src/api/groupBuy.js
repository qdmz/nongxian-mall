import request from './request'

/** 拼团活动列表 */
export function getActivities(params) {
  return request.get('/group-buy/activities', { params })
}

/** 拼团活动详情（含进行中的团） */
export function getActivityDetail(id) {
  return request.get(`/group-buy/activities/${id}`)
}

/**
 * 拼团下单（开团或参团）
 * @param {Object} data {activity_id, group_buy_id(参团时传), quantity, address_id, remark}
 */
export function createGroupBuyOrder(data) {
  return request.post('/group-buy/orders', data)
}

/** 拼团详情（分享页） */
export function getGroupDetail(id) {
  return request.get(`/group-buy/groups/${id}`)
}

/** 我的拼团 */
export function getMyGroups(params) {
  return request.get('/group-buy/my-groups', { params })
}

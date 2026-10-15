import request from './request'

/** 秒杀活动列表（进行中 + 即将开始） */
export function getSeckillList(params) {
  return request.get('/seckill/activities', { params })
}

/** 秒杀活动详情 */
export function getSeckillDetail(id) {
  return request.get(`/seckill/activities/${id}`)
}

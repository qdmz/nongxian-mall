import request from './request'

/** 秒杀活动列表（2026-10-15） */
export function getSeckills(params) {
  return request.get('/admin/seckill/activities', { params })
}

export function createSeckill(data) {
  return request.post('/admin/seckill/activities', data)
}

export function updateSeckill(id, data) {
  return request.put(`/admin/seckill/activities/${id}`, data)
}

export function deleteSeckill(id) {
  return request.delete(`/admin/seckill/activities/${id}`)
}

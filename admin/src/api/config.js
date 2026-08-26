import request from './request'

// 读取分组配置
export function getConfig(group) {
  return request.get('/admin/config', { params: { group } })
}

// 保存分组配置
export function saveConfig(group, configs) {
  return request.post('/admin/config', { group, configs })
}

// 测试邮件
export function testSmtp(to) {
  return request.post('/admin/config/test-smtp', { to })
}

// 测试短信
export function testSms(phone) {
  return request.post('/admin/config/test-sms', { phone })
}

// 测试支付连通
export function testPay() {
  return request.post('/admin/config/test-pay')
}

// 支付记录
export function getPaymentRecords(params) {
  return request.get('/admin/config/payment-records', { params })
}

// 发送日志 type: sms/email
export function getConfigLogs(params) {
  return request.get('/admin/config/logs', { params })
}

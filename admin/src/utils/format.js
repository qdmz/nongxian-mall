import dayjs from 'dayjs'

// 时间戳（秒）格式化
export function formatTime(ts, fmt = 'YYYY-MM-DD HH:mm:ss') {
  if (!ts) return '-'
  return dayjs(Number(ts) * 1000).format(fmt)
}

// 金额格式化 ¥xx.xx
export function formatMoney(val) {
  const n = Number(val)
  if (isNaN(n)) return '¥0.00'
  return `¥${n.toFixed(2)}`
}

// 订单状态映射
export const ORDER_STATUS = {
  0: { text: '待支付', type: 'warning' },
  1: { text: '待发货', type: 'primary' },
  2: { text: '已发货', type: 'info' },
  3: { text: '已完成', type: 'success' },
  4: { text: '已取消', type: 'danger' },
  5: { text: '已关闭', type: 'danger' },
  6: { text: '退款中', type: 'warning' },
  7: { text: '已退款', type: 'danger' }
}

export function orderStatusText(status) {
  return (ORDER_STATUS[status] && ORDER_STATUS[status].text) || '未知'
}

export function orderStatusType(status) {
  return (ORDER_STATUS[status] && ORDER_STATUS[status].type) || 'info'
}

// 拼团状态映射
export const GROUP_STATUS = {
  0: { text: '拼团中', type: 'warning' },
  1: { text: '已成团', type: 'success' },
  2: { text: '拼团失败', type: 'danger' },
  3: { text: '已取消', type: 'info' }
}

export function groupStatusText(status) {
  return (GROUP_STATUS[status] && GROUP_STATUS[status].text) || '未知'
}

export function groupStatusType(status) {
  return (GROUP_STATUS[status] && GROUP_STATUS[status].type) || 'info'
}

// 支付方式映射
export const PAY_TYPES = {
  alipay: '支付宝',
  wxpay: '微信',
  qqpay: 'QQ钱包',
  balance: '余额'
}

export function payTypeText(t) {
  return PAY_TYPES[t] || t || '-'
}

// 订单类型
export function orderTypeText(t) {
  if (t === 'group_buy') return '拼团订单'
  return '普通订单'
}

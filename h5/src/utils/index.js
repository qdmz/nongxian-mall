import dayjs from 'dayjs'

/** 金额格式化：¥xx.xx */
export function formatPrice(value, withSymbol = true) {
  const n = Number(value || 0)
  const text = n.toFixed(2)
  return withSymbol ? '¥' + text : text
}

/** 时间戳（秒）格式化 */
export function formatTime(ts, fmt = 'YYYY-MM-DD HH:mm') {
  if (!ts) return '-'
  return dayjs.unix(Number(ts)).format(fmt)
}

/** 相对时间描述 */
export function timeAgo(ts) {
  if (!ts) return '-'
  const diff = dayjs().unix() - Number(ts)
  if (diff < 60) return '刚刚'
  if (diff < 3600) return Math.floor(diff / 60) + '分钟前'
  if (diff < 86400) return Math.floor(diff / 3600) + '小时前'
  if (diff < 86400 * 30) return Math.floor(diff / 86400) + '天前'
  return dayjs.unix(Number(ts)).format('YYYY-MM-DD')
}

/** 秒数 -> 倒计时文本，如 02:59 / 1天02:03:04 / 00:00 */
export function formatCountdown(seconds) {
  let s = Math.max(0, Math.floor(Number(seconds) || 0))
  const days = Math.floor(s / 86400)
  s -= days * 86400
  const hours = Math.floor(s / 3600)
  s -= hours * 3600
  const minutes = Math.floor(s / 60)
  s -= minutes * 60
  const pad = (n) => String(n).padStart(2, '0')
  if (days > 0) return `${days}天${pad(hours)}:${pad(minutes)}:${pad(s)}`
  return `${pad(hours)}:${pad(minutes)}:${pad(s)}`
}

/** 复制文本到剪贴板（降级方案） */
export async function copyText(text) {
  try {
    if (navigator.clipboard && window.isSecureContext) {
      await navigator.clipboard.writeText(text)
      return true
    }
  } catch (e) {
    /* fallback */
  }
  return new Promise((resolve) => {
    const input = document.createElement('textarea')
    input.value = text
    input.style.position = 'fixed'
    input.style.opacity = '0'
    document.body.appendChild(input)
    input.select()
    let ok = false
    try {
      ok = document.execCommand('copy')
    } catch (e) {
      ok = false
    }
    document.body.removeChild(input)
    resolve(ok)
  })
}

/** 订单状态 -> 文本 */
export const ORDER_STATUS_TEXT = {
  0: '待支付',
  1: '待发货',
  2: '已发货',
  3: '已完成',
  4: '已取消',
  5: '已关闭',
  6: '退款中',
  7: '已退款'
}

/** 订单状态 -> 颜色 */
export const ORDER_STATUS_COLOR = {
  0: '#e63946',
  1: '#ffb703',
  2: '#2a9d5c',
  3: '#7a7d86',
  4: '#b0b3ba',
  5: '#b0b3ba',
  6: '#ff8c00',
  7: '#b0b3ba'
}

/** 拼团状态 -> 文本 */
export const GROUP_STATUS_TEXT = {
  0: '拼团中',
  1: '已成团',
  2: '拼团失败',
  3: '已取消'
}

/** 钱包流水类型 -> 文本 */
export const WALLET_TYPE_TEXT = {
  recharge: '充值',
  consume: '消费',
  refund: '退款',
  reward: '奖励',
  adjust: '调整'
}

/** SKU specs JSON -> 可读文本，如 "5斤装" / "红色 / XL" */
export function skuSpecsText(specs) {
  if (!specs) return ''
  try {
    const obj = typeof specs === 'string' ? JSON.parse(specs) : specs
    if (obj && typeof obj === 'object') {
      return Object.values(obj).join(' / ')
    }
  } catch (e) {
    return String(specs)
  }
  return String(specs)
}

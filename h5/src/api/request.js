import axios from 'axios'
import { showToast } from 'vant'

const service = axios.create({
  baseURL: '/api',
  timeout: 15000
})

// 请求拦截：自动携带 token
service.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = 'Bearer ' + token
    }
    return config
  },
  (error) => Promise.reject(error)
)

// 401 统一处理：清 token 跳登录页（带 redirect）
function handleUnauthorized() {
  localStorage.removeItem('token')
  const current = window.location.hash.replace(/^#/, '') || '/'
  if (!current.startsWith('/login')) {
    const redirect = encodeURIComponent(current)
    window.location.href = window.location.pathname + window.location.search + '#/login?redirect=' + redirect
  }
}

// 响应拦截：统一处理业务码
service.interceptors.response.use(
  (response) => {
    const body = response.data
    if (!body || typeof body.code === 'undefined') {
      return body
    }
    if (body.code === 0) {
      return body.data
    }
    if (body.code === 401) {
      handleUnauthorized()
      return Promise.reject(new Error(body.msg || '请先登录'))
    }
    showToast(body.msg || '请求失败')
    return Promise.reject(new Error(body.msg || '请求失败'))
  },
  (error) => {
    const status = error.response?.status
    const msg = error.response?.data?.msg || error.message || '网络异常，请稍后重试'
    if (status === 401) {
      handleUnauthorized()
    } else {
      showToast(msg)
    }
    return Promise.reject(error)
  }
)

export default service

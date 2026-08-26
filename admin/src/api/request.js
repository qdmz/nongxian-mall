import axios from 'axios'
import { ElMessage } from 'element-plus'
import router from '../router'

const request = axios.create({
  baseURL: '',
  timeout: 30000
})

// 请求拦截器：自动带 Bearer token
request.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('admin_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => Promise.reject(error)
)

// 响应拦截器：统一处理 code
request.interceptors.response.use(
  (res) => {
    const body = res.data
    // 非标准结构（如文件流）直接返回
    if (body === null || body === undefined || typeof body.code === 'undefined') {
      return body
    }
    if (body.code === 0) {
      return body.data
    }
    if (body.code === 401) {
      localStorage.removeItem('admin_token')
      localStorage.removeItem('admin_info')
      ElMessage.error('登录已过期，请重新登录')
      if (router.currentRoute.value.path !== '/login') {
        router.push('/login')
      }
      return Promise.reject(new Error(body.msg || '未登录'))
    }
    ElMessage.error(body.msg || '操作失败')
    return Promise.reject(new Error(body.msg || '操作失败'))
  },
  (error) => {
    if (error.response && error.response.status === 401) {
      localStorage.removeItem('admin_token')
      localStorage.removeItem('admin_info')
      ElMessage.error('登录已过期，请重新登录')
      if (router.currentRoute.value.path !== '/login') {
        router.push('/login')
      }
    } else {
      const msg =
        (error.response && error.response.data && error.response.data.msg) ||
        error.message ||
        '网络错误'
      ElMessage.error(msg)
    }
    return Promise.reject(error)
  }
)

export default request

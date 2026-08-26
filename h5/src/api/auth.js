import request from './request'

/**
 * 登录
 * @param {Object} data {login_type: 'password'|'sms', account, password, phone, code}
 */
export function login(data) {
  return request.post('/auth/login', data)
}

/**
 * 注册
 * @param {Object} data {register_type: 'phone_code'|'email_code'|'password', phone, email, code, password, nickname, invite_code}
 */
export function register(data) {
  return request.post('/auth/register', data)
}

/**
 * 发送验证码
 * @param {Object} data {type: 'sms'|'email', target, scene: 'register'|'login'|'verify'}
 */
export function sendCode(data) {
  return request.post('/auth/send-code', data)
}

/** 校验 token */
export function checkToken() {
  return request.get('/auth/check-token')
}

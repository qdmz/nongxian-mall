import { defineStore } from 'pinia'
import { getProfile } from '../api/auth'

export const useAdminStore = defineStore('admin', {
  state: () => ({
    token: localStorage.getItem('admin_token') || '',
    adminInfo: JSON.parse(localStorage.getItem('admin_info') || 'null')
  }),
  getters: {
    isLoggedIn: (state) => !!state.token,
    displayName: (state) =>
      (state.adminInfo && (state.adminInfo.nickname || state.adminInfo.username)) || '管理员'
  },
  actions: {
    /** 是否拥有某权限（* 为超级管理员；未返回权限信息时不隐藏，兼容旧后端） */
    can(key) {
      const info = this.adminInfo || {}
      let perms = info.permissions || (info.admin && info.admin.permissions)
      if (!perms && info.role && info.role.permissions) {
        const raw = String(info.role.permissions).trim()
        if (raw === '*') perms = ['*']
        else {
          try {
            const j = JSON.parse(raw)
            perms = Array.isArray(j) ? j : null
          } catch (_) {
            perms = raw.split(',').map((q) => q.trim()).filter(Boolean)
          }
        }
      }
      return !Array.isArray(perms) || perms.includes('*') || perms.includes(key)
    },
    setToken(token) {
      this.token = token
      localStorage.setItem('admin_token', token)
    },
    setAdminInfo(info) {
      this.adminInfo = info
      localStorage.setItem('admin_info', JSON.stringify(info || null))
    },
    async fetchProfile() {
      const info = await getProfile()
      // 2026-10-14b：/admin/auth/profile 返回 { admin, permissions }，扁平化后供菜单按权限过滤
      const flat = info && info.admin
        ? Object.assign({}, info.admin, { permissions: info.permissions || info.admin.permissions || [] })
        : info
      this.setAdminInfo(flat)
      return info
    },
    logout() {
      this.token = ''
      this.adminInfo = null
      localStorage.removeItem('admin_token')
      localStorage.removeItem('admin_info')
    }
  }
})

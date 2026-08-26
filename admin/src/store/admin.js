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
      this.setAdminInfo(info)
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

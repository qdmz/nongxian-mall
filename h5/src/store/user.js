import { defineStore } from 'pinia'
import { getProfile } from '../api/user'
import { getCart } from '../api/cart'

export const useUserStore = defineStore('user', {
  state: () => ({
    token: localStorage.getItem('token') || '',
    user: null,
    cartCount: 0
  }),
  getters: {
    isLoggedIn: (state) => !!state.token,
    nickname: (state) => state.user?.nickname || '未登录',
    avatar: (state) => state.user?.avatar || '',
    balance: (state) => Number(state.user?.wallet_balance || 0)
  },
  actions: {
    setToken(token) {
      this.token = token
      if (token) {
        localStorage.setItem('token', token)
      } else {
        localStorage.removeItem('token')
      }
    },
    async fetchProfile() {
      this.user = await getProfile()
      return this.user
    },
    async fetchCartCount() {
      const data = await getCart()
      let count = 0
      const list = data?.list || []
      list.forEach((item) => {
        count += Number(item.quantity || 0)
      })
      this.cartCount = count
      return count
    },
    async logout() {
      this.setToken('')
      this.user = null
      this.cartCount = 0
    }
  }
})

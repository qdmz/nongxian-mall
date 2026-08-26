<template>
  <div class="app-root">
    <router-view />
    <van-tabbar route safe-area-inset-bottom v-if="showTabbar">
      <van-tabbar-item to="/" icon="wap-home-o">首页</van-tabbar-item>
      <van-tabbar-item to="/category" icon="apps-o">分类</van-tabbar-item>
      <van-tabbar-item to="/cart" icon="cart-o" :badge="cartBadge">购物车</van-tabbar-item>
      <van-tabbar-item to="/profile" icon="user-o">我的</van-tabbar-item>
    </van-tabbar>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useUserStore } from './store/user'

const route = useRoute()
const userStore = useUserStore()

const showTabbar = computed(() => !!route.meta.tabbar)
const cartBadge = computed(() => (userStore.cartCount > 0 ? String(userStore.cartCount) : ''))

onMounted(() => {
  if (userStore.token) {
    userStore.fetchProfile().catch(() => {})
    userStore.fetchCartCount().catch(() => {})
  }
})
</script>

<style scoped>
.app-root {
  min-height: 100vh;
  background: #f5f5f7;
}
</style>

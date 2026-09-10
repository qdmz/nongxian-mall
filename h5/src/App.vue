<template>
  <div class="app-root">
    <div v-if="isWide" class="app-shell-wide">
      <div class="app-shell">
        <router-view />
      </div>
    </div>
    <template v-else>
      <router-view />
    </template>
    <van-tabbar route safe-area-inset-bottom v-if="showTabbar">
      <van-tabbar-item to="/" icon="wap-home-o">首页</van-tabbar-item>
      <van-tabbar-item to="/category" icon="apps-o">分类</van-tabbar-item>
      <van-tabbar-item to="/cart" icon="cart-o" :badge="cartBadge">购物车</van-tabbar-item>
      <van-tabbar-item to="/profile" icon="user-o">我的</van-tabbar-item>
    </van-tabbar>
  </div>
</template>

<script setup>
import { computed, onMount, onUnmounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useUserStore } from './store/user'

const route = useRoute()
const userStore = useUserStore()

const showTabbar = computed(() => !!route.meta.tabbar)
const isWide = ref(false)
const cartBadge = computed(() => (userStore.cartCount > 0 ? String(userStore.cartCount) : ''))

function applyShell() {
  isWide.value = window.matchMedia('(min-width: 760px) and (orientation: landscape), (min-width: 1100px)').matches
}

onMount(() => {
  applyShell()
  const mq = window.matchMedia('(min-width: 760px) and (orientation: landscape), (min-width: 1100px)')
  mq.addEventListener('change', applyShell)
})

onUnmounted(() => {
  window.removeEventListener('resize', applyShell)
})

if (userStore.token) {
  userStore.fetchProfile().catch(() => {})
  userStore.fetchCartCount().catch(() => {})
}
</script>

<style scoped>
.app-root {
  min-height: 100vh;
  background: #f5f5f7;
}
</style>

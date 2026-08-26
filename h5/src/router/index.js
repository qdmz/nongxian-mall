import { createRouter, createWebHashHistory } from 'vue-router'
import { trackClick } from '../api/share'

const routes = [
  { path: '/', name: 'Home', component: () => import('../views/Home.vue'), meta: { tabbar: true, title: '田冲助农商城' } },
  { path: '/category', name: 'Category', component: () => import('../views/Category.vue'), meta: { tabbar: true, title: '分类' } },
  { path: '/search', name: 'Search', component: () => import('../views/Search.vue'), meta: { title: '搜索' } },
  { path: '/product/:id', name: 'ProductDetail', component: () => import('../views/ProductDetail.vue'), meta: { title: '商品详情' } },
  { path: '/cart', name: 'Cart', component: () => import('../views/Cart.vue'), meta: { tabbar: true, requiresAuth: true, title: '购物车' } },
  { path: '/confirm-order', name: 'ConfirmOrder', component: () => import('../views/ConfirmOrder.vue'), meta: { requiresAuth: true, title: '确认订单' } },
  { path: '/order/list', name: 'OrderList', component: () => import('../views/OrderList.vue'), meta: { requiresAuth: true, title: '我的订单' } },
  { path: '/order/detail/:id', name: 'OrderDetail', component: () => import('../views/OrderDetail.vue'), meta: { requiresAuth: true, title: '订单详情' } },
  // 兼容支付回调 /h5/#/order/detail?id=xx&paid=1
  { path: '/order/detail', name: 'OrderDetailQuery', component: () => import('../views/OrderDetail.vue'), meta: { requiresAuth: true, title: '订单详情' } },
  { path: '/pay/:orderId', name: 'Pay', component: () => import('../views/Pay.vue'), meta: { requiresAuth: true, title: '收银台' } },
  { path: '/group-buy', name: 'GroupBuy', component: () => import('../views/GroupBuy.vue'), meta: { title: '拼团专区' } },
  { path: '/group-buy/group/:id', name: 'GroupBuyGroup', component: () => import('../views/GroupBuyGroup.vue'), meta: { title: '拼团详情' } },
  { path: '/group-buy/:id', name: 'GroupBuyDetail', component: () => import('../views/GroupBuyDetail.vue'), meta: { title: '拼团活动' } },
  { path: '/login', name: 'Login', component: () => import('../views/Login.vue'), meta: { title: '登录' } },
  { path: '/register', name: 'Register', component: () => import('../views/Register.vue'), meta: { title: '注册' } },
  { path: '/profile', name: 'Profile', component: () => import('../views/Profile.vue'), meta: { tabbar: true, requiresAuth: true, title: '我的' } },
  { path: '/address', name: 'AddressList', component: () => import('../views/AddressList.vue'), meta: { requiresAuth: true, title: '收货地址' } },
  { path: '/address/edit/:id?', name: 'AddressEdit', component: () => import('../views/AddressEdit.vue'), meta: { requiresAuth: true, title: '编辑地址' } },
  { path: '/wallet', name: 'Wallet', component: () => import('../views/Wallet.vue'), meta: { requiresAuth: true, title: '我的钱包' } },
  // 兼容充值回调 /h5/#/user/wallet?recharged=1
  { path: '/user/wallet', redirect: (to) => ({ path: '/wallet', query: to.query }) },
  { path: '/recharge', name: 'Recharge', component: () => import('../views/Recharge.vue'), meta: { requiresAuth: true, title: '充值' } },
  { path: '/notifications', name: 'Notifications', component: () => import('../views/Notifications.vue'), meta: { requiresAuth: true, title: '站内消息' } },
  { path: '/share', name: 'ShareCenter', component: () => import('../views/ShareCenter.vue'), meta: { requiresAuth: true, title: '推广中心' } },
  { path: '/share/product/:id', name: 'ShareProduct', component: () => import('../views/ShareProduct.vue'), meta: { title: '商品分享' } },
  { path: '/my-groups', name: 'MyGroups', component: () => import('../views/MyGroups.vue'), meta: { requiresAuth: true, title: '我的拼团' } },
  { path: '/bind', name: 'Bind', component: () => import('../views/Bind.vue'), meta: { requiresAuth: true, title: '绑定账号' } },
  { path: '/about', name: 'About', component: () => import('../views/About.vue'), meta: { title: '关于我们' } },
  { path: '/:pathMatch(.*)*', redirect: '/' }
]

const router = createRouter({
  history: createWebHashHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 }
  }
})

// 邀请码点击追踪（同一会话只上报一次）
function trackInvite(to) {
  const code = to.query.invite
  if (!code) return
  const key = 'tracked_invite_' + code
  if (sessionStorage.getItem(key)) return
  sessionStorage.setItem(key, '1')
  trackClick(String(code)).catch(() => {})
}

router.beforeEach((to) => {
  trackInvite(to)

  if (to.meta.title) {
    document.title = String(to.meta.title) + ' - 田冲助农商城'
  }

  if (to.meta.requiresAuth && !localStorage.getItem('token')) {
    return {
      path: '/login',
      query: { redirect: to.fullPath }
    }
  }
  return true
})

export default router

import { createRouter, createWebHistory } from 'vue-router'
import { useAdminStore } from '../store/admin'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('../views/login/Login.vue'),
    meta: { title: '登录' }
  },
  {
    path: '/',
    component: () => import('../layout/AdminLayout.vue'),
    redirect: '/dashboard',
    children: [
      {
        path: 'dashboard',
        name: 'Dashboard',
        component: () => import('../views/dashboard/Dashboard.vue'),
        meta: { title: '仪表盘', icon: 'Odometer' }
      },
      {
        path: 'users',
        name: 'UserList',
        component: () => import('../views/users/UserList.vue'),
        meta: { title: '用户管理', icon: 'User' }
      },
      {
        path: 'categories',
        name: 'CategoryList',
        component: () => import('../views/categories/CategoryList.vue'),
        meta: { title: '分类管理', icon: 'Menu' }
      },
      {
        path: 'products',
        name: 'ProductList',
        component: () => import('../views/products/ProductList.vue'),
        meta: { title: '商品列表', icon: 'Goods' }
      },
      {
        path: 'products/create',
        name: 'ProductCreate',
        component: () => import('../views/products/ProductForm.vue'),
        meta: { title: '新建商品', icon: 'Goods', parent: 'ProductList' }
      },
      {
        path: 'products/edit/:id',
        name: 'ProductEdit',
        component: () => import('../views/products/ProductForm.vue'),
        meta: { title: '编辑商品', icon: 'Goods', parent: 'ProductList' }
      },
      {
        path: 'orders',
        name: 'OrderList',
        component: () => import('../views/orders/OrderList.vue'),
        meta: { title: '订单管理', icon: 'Document' }
      },
      {
        path: 'orders/:id',
        name: 'OrderDetail',
        component: () => import('../views/orders/OrderDetail.vue'),
        meta: { title: '订单详情', parent: 'OrderList' }
      },
      {
        path: 'refunds',
        name: 'RefundList',
        component: () => import('../views/refunds/RefundList.vue'),
        meta: { title: '退款管理', icon: 'RefreshLeft' }
      },
      {
        path: 'deliveries',
        name: 'DeliveryList',
        component: () => import('../views/deliveries/DeliveryList.vue'),
        meta: { title: '配送管理', icon: 'Van' }
      },
      {
        path: 'group-buy/activities',
        name: 'GroupBuyActivities',
        component: () => import('../views/group-buy/ActivityList.vue'),
        meta: { title: '拼团活动', icon: 'ShoppingCart' }
      },
      {
        path: 'group-buy/groups',
        name: 'GroupBuyGroups',
        component: () => import('../views/group-buy/GroupList.vue'),
        meta: { title: '拼团单', icon: 'UserFilled' }
      },
      {
        path: 'banners',
        name: 'BannerList',
        component: () => import('../views/banners/BannerList.vue'),
        meta: { title: '轮播图管理', icon: 'Picture' }
      },
      {
        path: 'config/pay',
        name: 'PayConfig',
        component: () => import('../views/config/PayConfig.vue'),
        meta: { title: '支付配置', icon: 'CreditCard', group: '系统配置' }
      },
      {
        path: 'config/smtp',
        name: 'SmtpConfig',
        component: () => import('../views/config/SmtpConfig.vue'),
        meta: { title: '邮件配置', icon: 'Message', group: '系统配置' }
      },
      {
        path: 'config/sms',
        name: 'SmsConfig',
        component: () => import('../views/config/SmsConfig.vue'),
        meta: { title: '短信配置', icon: 'Iphone', group: '系统配置' }
      },
      {
        path: 'config/app',
        name: 'AppConfig',
        component: () => import('../views/config/AppConfig.vue'),
        meta: { title: '应用配置', icon: 'Setting', group: '系统配置' }
      },
      {
        path: 'statistics',
        name: 'Statistics',
        component: () => import('../views/statistics/Statistics.vue'),
        meta: { title: '销量统计', icon: 'TrendCharts' }
      },
      {
        path: 'profile',
        name: 'Profile',
        component: () => import('../views/profile/Profile.vue'),
        meta: { title: '个人中心', icon: 'Avatar' }
      }
    ]
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/dashboard'
  }
]

const router = createRouter({
  history: createWebHistory('/manage/'),
  routes
})

// 路由守卫：无 token 一律跳登录
router.beforeEach((to, from, next) => {
  const store = useAdminStore()
  if (to.path === '/login') {
    if (store.isLoggedIn) {
      next('/dashboard')
    } else {
      next()
    }
    return
  }
  if (!store.isLoggedIn) {
    next('/login')
    return
  }
  next()
})

router.afterEach((to) => {
  document.title = to.meta.title
    ? `${to.meta.title} - 田冲助农商城管理后台`
    : '田冲助农商城 · 管理后台'
})

export default router

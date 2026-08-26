<template>
  <el-container class="admin-layout">
    <!-- 侧边栏 -->
    <el-aside :width="isCollapse ? '64px' : '220px'" class="sidebar">
      <div class="logo-area">
        <div class="logo-mark">田</div>
        <transition name="fade">
          <div v-if="!isCollapse" class="logo-text">
            <div class="logo-title">田冲助农商城</div>
            <div class="logo-sub">管理后台</div>
          </div>
        </transition>
      </div>
      <el-scrollbar class="menu-scroll">
        <el-menu
          :default-active="activeMenu"
          :collapse="isCollapse"
          :collapse-transition="false"
          background-color="#262626"
          text-color="#a6a6a6"
          active-text-color="#ffffff"
          router
        >
          <el-menu-item index="/dashboard">
            <el-icon><Odometer /></el-icon>
            <template #title>仪表盘</template>
          </el-menu-item>
          <el-menu-item index="/users">
            <el-icon><User /></el-icon>
            <template #title>用户管理</template>
          </el-menu-item>
          <el-menu-item index="/categories">
            <el-icon><Menu /></el-icon>
            <template #title>分类管理</template>
          </el-menu-item>
          <el-menu-item index="/products">
            <el-icon><Goods /></el-icon>
            <template #title>商品管理</template>
          </el-menu-item>
          <el-menu-item index="/orders">
            <el-icon><Document /></el-icon>
            <template #title>订单管理</template>
          </el-menu-item>
          <el-menu-item index="/refunds">
            <el-icon><RefreshLeft /></el-icon>
            <template #title>退款管理</template>
          </el-menu-item>
          <el-menu-item index="/deliveries">
            <el-icon><Van /></el-icon>
            <template #title>配送管理</template>
          </el-menu-item>

          <el-sub-menu index="group-buy">
            <template #title>
              <el-icon><ShoppingCart /></el-icon>
              <span>拼团管理</span>
            </template>
            <el-menu-item index="/group-buy/activities">拼团活动</el-menu-item>
            <el-menu-item index="/group-buy/groups">拼团单列表</el-menu-item>
          </el-sub-menu>

          <el-menu-item index="/banners">
            <el-icon><Picture /></el-icon>
            <template #title>轮播图管理</template>
          </el-menu-item>

          <el-sub-menu index="config">
            <template #title>
              <el-icon><Setting /></el-icon>
              <span>系统配置</span>
            </template>
            <el-menu-item index="/config/pay">支付配置</el-menu-item>
            <el-menu-item index="/config/smtp">邮件配置</el-menu-item>
            <el-menu-item index="/config/sms">短信配置</el-menu-item>
            <el-menu-item index="/config/app">应用配置</el-menu-item>
          </el-sub-menu>

          <el-menu-item index="/statistics">
            <el-icon><TrendCharts /></el-icon>
            <template #title>销量统计</template>
          </el-menu-item>
        </el-menu>
      </el-scrollbar>
    </el-aside>

    <el-container>
      <!-- 顶栏 -->
      <el-header class="header">
        <div class="header-left">
          <el-icon class="collapse-btn" @click="isCollapse = !isCollapse">
            <Expand v-if="isCollapse" />
            <Fold v-else />
          </el-icon>
          <el-breadcrumb separator="/">
            <el-breadcrumb-item :to="{ path: '/dashboard' }">首页</el-breadcrumb-item>
            <el-breadcrumb-item v-if="route.meta.group">{{ route.meta.group }}</el-breadcrumb-item>
            <el-breadcrumb-item v-if="route.meta.parent">
              <router-link :to="`/${route.meta.parent}`" class="crumb-link">
                {{ parentTitle }}
              </router-link>
            </el-breadcrumb-item>
            <el-breadcrumb-item v-if="!route.meta.parent">{{ route.meta.title }}</el-breadcrumb-item>
            <el-breadcrumb-item v-else>{{ route.meta.title }}</el-breadcrumb-item>
          </el-breadcrumb>
        </div>
        <div class="header-right">
          <el-dropdown @command="handleCommand">
            <span class="admin-dropdown">
              <el-avatar :size="30" class="admin-avatar">
                {{ displayName.charAt(0) }}
              </el-avatar>
              <span class="admin-name">{{ displayName }}</span>
              <el-icon><ArrowDown /></el-icon>
            </span>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item command="profile">
                  <el-icon><Avatar /></el-icon>个人中心
                </el-dropdown-item>
                <el-dropdown-item command="logout" divided>
                  <el-icon><SwitchButton /></el-icon>退出登录
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </div>
      </el-header>

      <!-- 主内容区 -->
      <el-main class="main">
        <router-view />
      </el-main>
    </el-container>
  </el-container>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessageBox, ElMessage } from 'element-plus'
import { useAdminStore } from '../store/admin'

const route = useRoute()
const router = useRouter()
const store = useAdminStore()
const isCollapse = ref(false)

const activeMenu = computed(() => {
  if (route.path.startsWith('/products')) return '/products'
  return route.path
})

const displayName = computed(() => store.displayName)

const parentTitle = computed(() => {
  const map = {
    ProductList: '商品列表',
    OrderList: '订单管理'
  }
  return map[route.meta.parent] || ''
})

onMounted(() => {
  if (store.isLoggedIn && !store.adminInfo) {
    store.fetchProfile().catch(() => {})
  }
})

function handleCommand(cmd) {
  if (cmd === 'profile') {
    router.push('/profile')
  } else if (cmd === 'logout') {
    ElMessageBox.confirm('确定要退出登录吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })
      .then(() => {
        store.logout()
        ElMessage.success('已退出登录')
        router.push('/login')
      })
      .catch(() => {})
  }
}
</script>

<style scoped>
.admin-layout {
  height: 100%;
}

.sidebar {
  background-color: #262626;
  display: flex;
  flex-direction: column;
  transition: width 0.2s;
  overflow: hidden;
}

.logo-area {
  height: var(--tc-header-height);
  display: flex;
  align-items: center;
  padding: 0 14px;
  background: linear-gradient(135deg, #a82508, #d4380d);
  flex-shrink: 0;
}

.logo-mark {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: #fff;
  color: #d4380d;
  font-weight: 700;
  font-size: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.logo-text {
  margin-left: 10px;
  white-space: nowrap;
}

.logo-title {
  color: #fff;
  font-size: 14px;
  font-weight: 600;
  line-height: 1.2;
}

.logo-sub {
  color: rgba(255, 255, 255, 0.75);
  font-size: 11px;
  line-height: 1.4;
}

.menu-scroll {
  flex: 1;
}

:deep(.el-menu) {
  border-right: none;
}

:deep(.el-menu-item:hover),
:deep(.el-sub-menu__title:hover) {
  background-color: #333 !important;
}

:deep(.el-menu-item.is-active) {
  background-color: #d4380d !important;
  color: #fff !important;
}

.header {
  height: var(--tc-header-height);
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
  z-index: 10;
}

.header-left {
  display: flex;
  align-items: center;
}

.collapse-btn {
  font-size: 20px;
  margin-right: 16px;
  cursor: pointer;
  color: #666;
}

.collapse-btn:hover {
  color: #d4380d;
}

.crumb-link {
  color: #606266;
  text-decoration: none;
}

.crumb-link:hover {
  color: #d4380d;
}

.header-right {
  display: flex;
  align-items: center;
}

.admin-dropdown {
  display: flex;
  align-items: center;
  cursor: pointer;
  gap: 8px;
  outline: none;
}

.admin-avatar {
  background-color: #d4380d;
  color: #fff;
  font-weight: 600;
}

.admin-name {
  font-size: 14px;
  color: #333;
}

.main {
  background-color: #f5f5f5;
  padding: 0;
  overflow-y: auto;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>

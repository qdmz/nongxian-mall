<template>
  <div class="profile-page page-with-tabbar">
    <!-- 用户信息卡片 -->
    <div class="pf-header">
      <div class="pf-user" @click="$router.push('/bind')">
        <img class="pf-avatar" :src="userStore.avatar || defaultAvatar" alt="" />
        <div class="pf-user-info">
          <div class="pf-nickname">{{ userStore.nickname }}</div>
          <div class="pf-meta" v-if="userStore.user">
            <span class="red-tag" v-if="userStore.user.referral_code">邀请码 {{ userStore.user.referral_code }}</span>
            <span class="pf-id">ID: {{ userStore.user.id }}</span>
          </div>
        </div>
        <van-icon name="setting-o" class="pf-setting" />
      </div>

      <!-- 钱包余额入口 -->
      <div class="pf-wallet" @click="$router.push('/wallet')">
        <div class="pf-wallet-left">
          <div class="pf-wallet-label">钱包余额（元）</div>
          <div class="pf-wallet-num">{{ balance.toFixed(2) }}</div>
        </div>
        <van-button size="small" round class="pf-recharge-btn" @click.stop="$router.push('/recharge')">
          充值
        </van-button>
        <van-icon name="arrow" class="pf-wallet-arrow" />
      </div>
    </div>

    <!-- 我的订单四宫格 -->
    <div class="section">
      <div class="section-head">
        <div class="section-title">我的订单</div>
        <div class="section-more" @click="$router.push('/order/list')">全部订单 &gt;</div>
      </div>
      <div class="pf-order-grid">
        <div class="pf-order-item" @click="goOrders(0)">
          <van-badge :content="orderCounts[0] || ''">
            <van-icon name="pending-payment" class="pf-order-icon" />
          </van-badge>
          <span>待支付</span>
        </div>
        <div class="pf-order-item" @click="goOrders(1)">
          <van-badge :content="orderCounts[1] || ''">
            <van-icon name="send-gift-o" class="pf-order-icon" />
          </van-badge>
          <span>待发货</span>
        </div>
        <div class="pf-order-item" @click="goOrders(2)">
          <van-badge :content="orderCounts[2] || ''">
            <van-icon name="logistics" class="pf-order-icon" />
          </van-badge>
          <span>已发货</span>
        </div>
        <div class="pf-order-item" @click="goOrders(3)">
          <van-badge :content="orderCounts[3] || ''">
            <van-icon name="passed" class="pf-order-icon" />
          </van-badge>
          <span>已完成</span>
        </div>
      </div>
    </div>

    <!-- 功能列表 -->
    <div class="section pf-cells">
      <van-cell title="收货地址管理" icon="location-o" is-link @click="$router.push('/address')" />
      <van-cell title="我的拼团" icon="friends-o" is-link @click="$router.push('/my-groups')" />
      <van-cell title="推广中心" icon="share-o" is-link @click="$router.push('/share')">
        <template #value>
          <span class="pf-cell-tip">邀请好友赚奖励</span>
        </template>
      </van-cell>
      <van-cell title="站内消息" icon="bell" is-link @click="$router.push('/notifications')">
        <template #value>
          <span class="pf-cell-tip" v-if="unreadCount">{{ unreadCount }}条未读</span>
        </template>
      </van-cell>
      <van-cell title="绑定手机/邮箱" icon="phone-o" is-link @click="$router.push('/bind')" />
      <van-cell title="关于我们" icon="info-o" is-link @click="$router.push('/about')" />
    </div>

    <div class="pf-logout">
      <van-button round block plain type="danger" @click="onLogout">退出登录</van-button>
    </div>

    <div class="pf-foot">贵州亿田农业 · 田冲红色美丽乡村强村富民工坊</div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { showConfirmDialog, showToast } from 'vant'
import { useUserStore } from '../store/user'
import { getOrders } from '../api/order'
import { getNotifications } from '../api/user'

const router = useRouter()
const userStore = useUserStore()

const defaultAvatar =
  'data:image/svg+xml,' +
  encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80"><rect width="80" height="80" fill="#ffe9ec"/><circle cx="40" cy="30" r="13" fill="#e63946"/><path d="M14 72c0-15 12-22 26-22s26 7 26 22" fill="#e63946"/></svg>'
  )

const orderCounts = ref({ 0: 0, 1: 0, 2: 0, 3: 0 })
const unreadCount = ref(0)

const balance = computed(() => userStore.balance)

async function loadCounts() {
  // 四种状态分别取 total（每页1条足够）
  const statuses = [0, 1, 2, 3]
  await Promise.all(
    statuses.map(async (s) => {
      try {
        const data = await getOrders({ status: s, page: 1, page_size: 1 })
        orderCounts.value[s] = Number(data?.total || 0)
      } catch (e) {
        orderCounts.value[s] = 0
      }
    })
  )
}

async function loadUnread() {
  try {
    const data = await getNotifications({ page: 1, page_size: 100 })
    unreadCount.value = (data?.list || []).filter((n) => !Number(n.is_read)).length
  } catch (e) {
    unreadCount.value = 0
  }
}

function goOrders(status) {
  router.push('/order/list?status=' + status)
}

function onLogout() {
  showConfirmDialog({ title: '提示', message: '确定退出登录吗？' })
    .then(async () => {
      await userStore.logout()
      showToast('已退出登录')
      router.replace('/login')
    })
    .catch(() => {})
}

onMounted(() => {
  userStore.fetchProfile().catch(() => {})
  userStore.fetchCartCount().catch(() => {})
  loadCounts()
  loadUnread()
})
</script>

<style scoped>
.pf-header {
  padding: 20px 14px 14px;
  background: linear-gradient(160deg, #e63946 0%, #c1121f 100%);
}
.pf-user {
  display: flex;
  align-items: center;
}
.pf-avatar {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  border: 2px solid rgba(255, 255, 255, 0.7);
  object-fit: cover;
  background: #fff;
}
.pf-user-info {
  flex: 1;
  margin-left: 12px;
  color: #fff;
}
.pf-nickname {
  font-size: 18px;
  font-weight: 700;
}
.pf-meta {
  margin-top: 6px;
  display: flex;
  align-items: center;
  gap: 6px;
}
.pf-meta .red-tag {
  background: rgba(255, 255, 255, 0.2);
  color: #fff;
}
.pf-id {
  font-size: 11px;
  opacity: 0.85;
}
.pf-setting {
  color: #fff;
  font-size: 20px;
}
.pf-wallet {
  margin-top: 16px;
  background: rgba(255, 255, 255, 0.14);
  border-radius: 10px;
  padding: 12px 14px;
  display: flex;
  align-items: center;
  color: #fff;
}
.pf-wallet-left {
  flex: 1;
}
.pf-wallet-label {
  font-size: 11px;
  opacity: 0.85;
}
.pf-wallet-num {
  font-size: 24px;
  font-weight: 800;
  margin-top: 2px;
}
.pf-recharge-btn {
  background: #ffd166;
  color: #a4161a;
  border: none;
  font-weight: 700;
}
.pf-wallet-arrow {
  margin-left: 10px;
  opacity: 0.8;
}
.pf-order-grid {
  display: flex;
  padding: 6px 0 14px;
}
.pf-order-item {
  flex: 1;
  text-align: center;
  font-size: 12px;
  color: #44464d;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
}
.pf-order-icon {
  font-size: 26px;
  color: #44464d;
}
.pf-cells {
  padding: 4px 0;
}
.pf-cell-tip {
  color: #e63946;
  font-size: 11px;
}
.pf-logout {
  margin: 14px 10px;
}
.pf-foot {
  text-align: center;
  color: #b0b3ba;
  font-size: 11px;
  padding: 6px 0 16px;
}
</style>

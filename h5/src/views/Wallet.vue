<template>
  <div class="wallet-page">
    <van-nav-bar title="我的钱包" left-arrow class="tc-nav" @click-left="$router.back()" />

    <div class="wallet-card">
      <div class="wallet-label">可用余额（元）</div>
      <div class="wallet-num">{{ balance.toFixed(2) }}</div>
      <div class="wallet-sub" v-if="totalRecharge > 0">累计充值 ¥{{ Number(totalRecharge).toFixed(2) }}</div>
      <div class="wallet-actions">
        <van-button round size="small" class="wallet-btn" @click="$router.push('/recharge')">充值</van-button>
        <van-button round size="small" class="wallet-btn wallet-btn-ghost" @click="$router.push('/order/list')">
          去消费
        </van-button>
      </div>
    </div>

    <div class="section">
      <div class="section-head">
        <div class="section-title">收支明细</div>
        <div class="section-more" @click="$router.push('/recharge')">充值记录 &gt;</div>
      </div>
      <van-pull-refresh v-model="refreshing" @refresh="onRefresh">
        <van-list
          v-model:loading="loading"
          :finished="finished"
          finished-text="没有更多了"
          @load="loadList"
        >
          <div class="tx-item" v-for="tx in list" :key="tx.id">
            <div class="tx-icon" :class="Number(tx.amount) >= 0 ? 'in' : 'out'">
              {{ Number(tx.amount) >= 0 ? '+' : '-' }}
            </div>
            <div class="tx-info">
              <div class="tx-title">
                {{ WALLET_TYPE_TEXT[tx.type] || '明细' }}
                <span class="tx-desc" v-if="tx.description">{{ tx.description }}</span>
              </div>
              <div class="tx-time">{{ formatTime(tx.created_at) }}</div>
            </div>
            <div class="tx-amount" :class="Number(tx.amount) >= 0 ? 'in' : 'out'">
              {{ Number(tx.amount) >= 0 ? '+' : '' }}{{ Number(tx.amount).toFixed(2) }}
            </div>
          </div>

          <div class="empty-wrap" v-if="finished && !list.length && !loading">
            <van-icon name="balance-o" />
            <p>暂无收支记录</p>
          </div>
        </van-list>
      </van-pull-refresh>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { showToast } from 'vant'
import { getWallet } from '../api/user'
import { formatTime, WALLET_TYPE_TEXT } from '../utils'
import { useUserStore } from '../store/user'

const route = useRoute()
const userStore = useUserStore()

const balance = ref(0)
const totalRecharge = ref(0)
const list = ref([])
const page = ref(1)
const loading = ref(false)
const finished = ref(false)
const refreshing = ref(false)

async function loadWalletInfo() {
  // 复用分页接口，第一页同时拿余额
  try {
    const data = await getWallet({ page: 1, page_size: 20 })
    balance.value = Number(data?.balance || 0)
    totalRecharge.value = Number(data?.total_recharge || 0)
  } catch (e) {
    /* 已统一 toast */
  }
  userStore.fetchProfile().catch(() => {})
}

async function loadList() {
  if (finished.value) return
  try {
    const data = await getWallet({ page: page.value })
    const items = data?.transactions?.list || []
    list.value.push(...items)
    balance.value = Number(data?.balance || balance.value)
    totalRecharge.value = Number(data?.total_recharge || totalRecharge.value)
    if (list.value.length >= Number(data?.transactions?.total || 0) || items.length === 0) {
      finished.value = true
    } else {
      page.value += 1
    }
    loading.value = false
  } catch (e) {
    loading.value = false
    finished.value = true
  }
}

function onRefresh() {
  list.value = []
  page.value = 1
  finished.value = false
  loading.value = true
  loadList()
  refreshing.value = false
}

onMounted(() => {
  if (route.query.recharged) {
    showToast('充值处理中，余额稍后更新')
  }
  loading.value = true
  loadList()
  loadWalletInfo()
})
</script>

<style scoped>
.wallet-card {
  margin: 12px;
  padding: 24px 18px;
  border-radius: 12px;
  background: linear-gradient(135deg, #e63946 0%, #c1121f 70%, #a4161a 100%);
  color: #fff;
  position: relative;
}
.wallet-label {
  font-size: 12px;
  opacity: 0.85;
}
.wallet-num {
  margin-top: 6px;
  font-size: 36px;
  font-weight: 800;
}
.wallet-sub {
  margin-top: 4px;
  font-size: 11px;
  opacity: 0.8;
}
.wallet-actions {
  margin-top: 16px;
  display: flex;
  gap: 10px;
}
.wallet-btn {
  background: #ffd166;
  color: #a4161a;
  border: none;
  font-weight: 700;
  min-width: 84px;
}
.wallet-btn-ghost {
  background: rgba(255, 255, 255, 0.2);
  color: #fff;
}
.tx-item {
  display: flex;
  align-items: center;
  padding: 12px;
  border-bottom: 1px solid #f5f5f7;
}
.tx-icon {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  flex-shrink: 0;
}
.tx-icon.in {
  background: #e8f6ee;
  color: #2a9d5c;
}
.tx-icon.out {
  background: #ffe9ec;
  color: #e63946;
}
.tx-info {
  flex: 1;
  width: 0;
  margin: 0 10px;
}
.tx-title {
  font-size: 14px;
  font-weight: 600;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.tx-desc {
  font-size: 11px;
  color: #9a9da5;
  font-weight: 400;
  margin-left: 6px;
}
.tx-time {
  margin-top: 2px;
  font-size: 11px;
  color: #b0b3ba;
}
.tx-amount {
  font-size: 16px;
  font-weight: 700;
}
.tx-amount.in {
  color: #2a9d5c;
}
.tx-amount.out {
  color: #e63946;
}
</style>

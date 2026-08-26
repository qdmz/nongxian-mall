<template>
  <div class="order-list">
    <van-nav-bar title="我的订单" left-arrow class="tc-nav" @click-left="$router.back()" fixed placeholder />

    <van-tabs v-model:active="activeTab" sticky offset-top="46px" class="ol-tabs">
      <van-tab title="全部" :name="-1" />
      <van-tab title="待支付" :name="0" />
      <van-tab title="待发货" :name="1" />
      <van-tab title="已发货" :name="2" />
      <van-tab title="已完成" :name="3" />
    </van-tabs>

    <van-pull-refresh v-model="refreshing" @refresh="onRefresh">
      <van-list
        v-model:loading="loading"
        :finished="finished"
        finished-text="没有更多了"
        @load="loadList"
      >
        <div class="ol-card" v-for="order in list" :key="order.id" @click="goDetail(order)">
          <div class="ol-head">
            <span class="ol-no">订单号 {{ order.order_no }}</span>
            <span class="ol-status" :style="{ color: statusColor(order.status) }">
              {{ order.status_text }}
            </span>
          </div>

          <!-- 待支付倒计时 -->
          <div class="ol-countdown" v-if="Number(order.status) === 0">
            <van-icon name="clock-o" />
            剩余
            <CountDown :end="Number(order.created_at) + 1800" @finish="onCountdownFinish(order)" />
            自动取消
          </div>

          <div class="ol-items">
            <div class="ol-item" v-for="item in order.items" :key="item.id">
              <img class="ol-img" :src="item.image" alt="" />
              <div class="ol-item-info">
                <div class="ellipsis-2 ol-item-name">{{ item.name }}</div>
                <div class="ol-item-spec" v-if="item.specs">{{ item.specs }}</div>
              </div>
              <div class="ol-item-right">
                <div class="ol-item-price">¥{{ Number(item.price).toFixed(2) }}</div>
                <div class="ol-item-qty">×{{ item.quantity }}</div>
              </div>
            </div>
          </div>

          <div class="ol-foot">
            <div class="ol-amount">
              共{{ order.product_count }}件 实付
              <span class="price">¥{{ Number(order.pay_amount).toFixed(2) }}</span>
              <span class="ol-balance" v-if="Number(order.use_balance) > 0">
                （含余额抵扣 ¥{{ Number(order.use_balance).toFixed(2) }}）
              </span>
            </div>
            <div class="ol-actions" @click.stop>
              <template v-if="Number(order.status) === 0">
                <van-button size="small" plain round @click="onCancel(order)">取消订单</van-button>
                <van-button size="small" round class="tc-btn" @click="onPay(order)">去支付</van-button>
              </template>
              <van-button
                v-if="Number(order.status) === 2"
                size="small"
                round
                class="tc-btn-green"
                @click="onConfirm(order)"
              >
                确认收货
              </van-button>
              <van-button size="small" plain round @click="goDetail(order)">查看详情</van-button>
            </div>
          </div>
        </div>

        <div class="empty-wrap" v-if="finished && !list.length && !loading">
          <van-icon name="orders-o" />
          <p>暂无相关订单</p>
        </div>
      </van-list>
    </van-pull-refresh>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { showConfirmDialog, showToast } from 'vant'
import { getOrders, cancelOrder, confirmOrder } from '../api/order'
import { ORDER_STATUS_COLOR } from '../utils'
import CountDown from '../components/CountDown.vue'

const route = useRoute()
const router = useRouter()

const activeTab = ref(-1)
const list = ref([])
const page = ref(1)
const loading = ref(false)
const finished = ref(false)
const refreshing = ref(false)

function statusColor(status) {
  return ORDER_STATUS_COLOR[status] || '#44464d'
}

function resetList() {
  list.value = []
  page.value = 1
  finished.value = false
  loading.value = true
  loadList()
}

async function loadList() {
  if (finished.value) return
  try {
    const params = { page: page.value }
    if (activeTab.value >= 0) params.status = activeTab.value
    const data = await getOrders(params)
    const items = data?.list || []
    list.value.push(...items)
    if (list.value.length >= Number(data?.total || 0) || items.length === 0) {
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
  resetList()
  refreshing.value = false
}

function onCountdownFinish(order) {
  order.status = 4
  order.status_text = '已取消'
  showToast('订单超时未支付，已自动取消')
}

function onCancel(order) {
  showConfirmDialog({ title: '取消订单', message: '确定要取消该订单吗？' })
    .then(async () => {
      await cancelOrder(order.id)
      showToast('订单已取消')
      onRefresh()
    })
    .catch(() => {})
}

async function onConfirm(order) {
  try {
    await showConfirmDialog({ title: '确认收货', message: '请确认已收到商品，确定收货吗？' })
    await confirmOrder(order.id)
    showToast('确认收货成功')
    onRefresh()
  } catch (e) {
    /* 取消或错误 */
  }
}

function onPay(order) {
  router.push('/pay/' + order.id)
}

function goDetail(order) {
  router.push('/order/detail/' + order.id)
}

watch(activeTab, resetList)

// 支持 /order/list?status=1 直达指定 tab
if (route.query.status !== undefined && route.query.status !== '') {
  const s = Number(route.query.status)
  activeTab.value = [0, 1, 2, 3].includes(s) ? s : -1
}
</script>

<style scoped>
.ol-tabs {
  position: sticky;
  top: 46px;
  z-index: 9;
}
.ol-card {
  margin: 10px;
  background: #fff;
  border-radius: 10px;
  padding: 12px;
}
.ol-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.ol-no {
  color: #9a9da5;
  font-size: 11px;
}
.ol-status {
  font-size: 13px;
  font-weight: 600;
}
.ol-countdown {
  margin-top: 6px;
  font-size: 12px;
  color: #e63946;
  display: flex;
  align-items: center;
}
.ol-countdown .van-icon {
  margin-right: 4px;
}
.ol-items {
  margin-top: 10px;
}
.ol-item {
  display: flex;
  padding: 6px 0;
}
.ol-img {
  width: 60px;
  height: 60px;
  border-radius: 6px;
  object-fit: cover;
  background: #f2f3f5;
  flex-shrink: 0;
}
.ol-item-info {
  flex: 1;
  width: 0;
  margin: 0 10px;
}
.ol-item-name {
  font-size: 12px;
  line-height: 17px;
  color: #22242a;
}
.ol-item-spec {
  margin-top: 3px;
  font-size: 11px;
  color: #9a9da5;
}
.ol-item-right {
  text-align: right;
}
.ol-item-price {
  font-size: 13px;
  color: #22242a;
}
.ol-item-qty {
  font-size: 11px;
  color: #9a9da5;
  margin-top: 4px;
}
.ol-foot {
  margin-top: 8px;
  border-top: 1px solid #f5f5f7;
  padding-top: 10px;
}
.ol-amount {
  font-size: 12px;
  color: #44464d;
  text-align: right;
}
.ol-amount .price {
  font-size: 16px;
}
.ol-balance {
  color: #9a9da5;
  font-size: 11px;
}
.ol-actions {
  margin-top: 8px;
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}
</style>

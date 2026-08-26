<template>
  <div class="od page-with-footerbar">
    <van-nav-bar title="订单详情" left-arrow class="tc-nav" @click-left="$router.back()" />

    <van-loading v-if="loading" class="page-loading" size="28" vertical>加载中...</van-loading>

    <template v-if="order">
      <!-- 状态头 -->
      <div class="od-status-head" :style="{ background: statusBg }">
        <div class="od-status-text">{{ order.status_text }}</div>
        <div class="od-countdown" v-if="Number(order.status) === 0">
          <van-icon name="clock-o" /> 剩余 <CountDown :end="Number(order.created_at) + 1800" @finish="loadOrder" /> 自动取消
        </div>
        <div class="od-status-desc" v-if="Number(order.status) === 6">退款申请处理中，请耐心等待</div>
        <div class="od-status-desc" v-if="Number(order.status) === 4 && order.cancel_reason">
          取消原因：{{ order.cancel_reason }}
        </div>
      </div>

      <!-- 状态步骤条 -->
      <div class="section" style="padding: 16px 6px" v-if="[0, 1, 2, 3].includes(Number(order.status))">
        <van-steps :active="stepActive" active-color="#e63946">
          <van-step>提交订单</van-step>
          <van-step>付款成功</van-step>
          <van-step>商品发货</van-step>
          <van-step>确认收货</van-step>
        </van-steps>
      </div>

      <!-- 拼团信息 -->
      <div class="section od-group" v-if="order.type == 2 && order.group_buy" @click="goGroup">
        <van-icon name="friends-o" class="od-group-icon" />
        <div class="od-group-info">
          <div>
            拼团 {{ order.group_buy.required_count }} 人团 · 已加入 {{ order.group_buy.joined_count }} 人
          </div>
          <div class="od-group-no" v-if="order.group_buy.is_leader">我是团长，快邀请好友参团吧</div>
        </div>
        <van-button size="small" round class="tc-btn">查看拼团</van-button>
      </div>

      <!-- 收货信息 -->
      <div class="section od-addr">
        <div class="od-addr-icon"><van-icon name="location-o" /></div>
        <div>
          <div class="od-addr-top">
            <span class="od-addr-name">{{ order.consignee }}</span>
            <span class="od-addr-phone">{{ order.phone }}</span>
          </div>
          <div class="od-addr-detail">{{ order.address }}</div>
        </div>
      </div>

      <!-- 商品明细 -->
      <div class="section">
        <div class="section-head"><div class="section-title">商品明细</div></div>
        <div class="od-item" v-for="item in order.items" :key="item.id" @click="goProduct(item.product_id)">
          <img class="od-img" :src="item.image" alt="" />
          <div class="od-item-info">
            <div class="ellipsis-2 od-item-name">{{ item.name }}</div>
            <div class="od-item-spec" v-if="item.specs">{{ item.specs }}</div>
            <div class="od-item-line">
              <span class="price">¥{{ Number(item.price).toFixed(2) }}</span>
              <span class="od-item-qty">×{{ item.quantity }}</span>
            </div>
          </div>
        </div>

        <!-- 配送跟踪 -->
        <template v-if="order.delivery">
          <div class="od-delivery-title">配送信息</div>
          <div class="od-delivery-meta">
            {{ order.delivery.company || '快递' }}
            <span v-if="order.delivery.tracking_no">单号 {{ order.delivery.tracking_no }}</span>
          </div>
          <div class="od-tracks" v-if="order.delivery.tracks && order.delivery.tracks.length">
            <div class="od-track" v-for="(t, i) in order.delivery.tracks" :key="t.id">
              <div class="od-track-dot" :class="{ first: i === 0 }"></div>
              <div class="od-track-body">
                <div class="od-track-desc" :class="{ first: i === 0 }">{{ t.description }}</div>
                <div class="od-track-time">{{ formatTime(t.created_at) }}</div>
              </div>
            </div>
          </div>
        </template>

        <!-- 金额明细 -->
        <div class="od-amounts">
          <div class="od-amount-row"><span>商品总额</span><span>¥{{ Number(order.total_amount).toFixed(2) }}</span></div>
          <div class="od-amount-row" v-if="Number(order.use_balance) > 0">
            <span>余额抵扣</span><span class="od-deduct">-¥{{ Number(order.use_balance).toFixed(2) }}</span>
          </div>
          <div class="od-amount-row"><span>实付款</span><span class="price">¥{{ Number(order.pay_amount).toFixed(2) }}</span></div>
        </div>
      </div>

      <!-- 订单信息 -->
      <div class="section od-info">
        <div class="od-info-row"><span class="label">订单编号</span><span>{{ order.order_no }}</span></div>
        <div class="od-info-row"><span class="label">创建时间</span><span>{{ formatTime(order.created_at) }}</span></div>
        <div class="od-info-row" v-if="order.paid_at"><span class="label">支付时间</span><span>{{ formatTime(order.paid_at) }}</span></div>
        <div class="od-info-row" v-if="order.delivered_at"><span class="label">发货时间</span><span>{{ formatTime(order.delivered_at) }}</span></div>
        <div class="od-info-row" v-if="order.completed_at"><span class="label">完成时间</span><span>{{ formatTime(order.completed_at) }}</span></div>
        <div class="od-info-row" v-if="order.remark"><span class="label">订单备注</span><span>{{ order.remark }}</span></div>
      </div>

      <!-- 订单日志 -->
      <div class="section" v-if="order.logs && order.logs.length">
        <div class="section-head"><div class="section-title">订单动态</div></div>
        <div class="od-logs">
          <div class="od-log" v-for="log in order.logs" :key="log.id">
            <span>{{ log.description || '状态更新' }}</span>
            <span class="od-log-time">{{ formatTime(log.created_at) }}</span>
          </div>
        </div>
      </div>

      <!-- 底部操作 -->
      <div class="footer-bar">
        <div class="od-footer-actions">
          <van-button
            v-if="Number(order.status) === 0"
            size="small"
            plain
            round
            @click="onCancel"
          >取消订单</van-button>
          <van-button
            v-if="[1, 2].includes(Number(order.status))"
            size="small"
            plain
            round
            @click="refundVisible = true"
          >申请退款</van-button>
          <van-button
            v-if="Number(order.status) === 2"
            size="small"
            round
            class="tc-btn-green"
            @click="onConfirm"
          >确认收货</van-button>
          <van-button
            v-if="Number(order.status) === 0"
            size="small"
            round
            class="tc-btn"
            @click="$router.push('/pay/' + order.id)"
          >去支付</van-button>
        </div>
      </div>
    </template>

    <div v-if="!loading && !order" class="empty-wrap">
      <van-icon name="warning-o" />
      <p>订单不存在</p>
    </div>

    <!-- 退款原因弹窗 -->
    <van-dialog
      v-model:show="refundVisible"
      title="申请退款"
      show-cancel-button
      :before-close="onRefundConfirm"
    >
      <div class="refund-body">
        <van-field
          v-model="refundReason"
          type="textarea"
          rows="3"
          maxlength="100"
          show-word-limit
          placeholder="请填写退款原因（必填）"
        />
      </div>
    </van-dialog>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { showConfirmDialog, showToast } from 'vant'
import { getOrderDetail, cancelOrder, confirmOrder, applyRefund } from '../api/order'
import { formatTime } from '../utils'
import CountDown from '../components/CountDown.vue'

const route = useRoute()
const router = useRouter()

const order = ref(null)
const loading = ref(false)
const refundVisible = ref(false)
const refundReason = ref('')

const stepActive = computed(() => {
  const s = Number(order.value?.status)
  if (s === 0) return 0
  if (s === 1) return 1
  if (s === 2) return 2
  if (s === 3) return 3
  return 0
})

const statusBg = computed(() => {
  const s = Number(order.value?.status)
  if (s === 0) return 'linear-gradient(135deg, #e63946, #c1121f)'
  if (s === 1) return 'linear-gradient(135deg, #ffb703, #f08c00)'
  if (s === 2) return 'linear-gradient(135deg, #2a9d5c, #1f7a46)'
  if (s === 6) return 'linear-gradient(135deg, #ff8c00, #e07b00)'
  return 'linear-gradient(135deg, #8d919c, #6b6f7a)'
})

async function loadOrder() {
  const id = route.params.id || route.query.id
  if (!id) return
  loading.value = true
  try {
    order.value = await getOrderDetail(id)
  } catch (e) {
    order.value = null
  } finally {
    loading.value = false
  }
}

function onCancel() {
  showConfirmDialog({ title: '取消订单', message: '确定要取消该订单吗？' })
    .then(async () => {
      await cancelOrder(order.value.id)
      showToast('订单已取消')
      loadOrder()
    })
    .catch(() => {})
}

async function onConfirm() {
  try {
    await showConfirmDialog({ title: '确认收货', message: '请确认已收到商品，确定收货吗？' })
    await confirmOrder(order.value.id)
    showToast('确认收货成功')
    loadOrder()
  } catch (e) {
    /* 取消或错误 */
  }
}

async function onRefundConfirm(action) {
  if (action !== 'confirm') return true
  if (!refundReason.value.trim()) {
    showToast('请填写退款原因')
    return false
  }
  try {
    await applyRefund(order.value.id, refundReason.value.trim())
    showToast('退款申请已提交')
    refundReason.value = ''
    refundVisible.value = false
    loadOrder()
    return true
  } catch (e) {
    return false
  }
}

function goGroup() {
  router.push('/my-groups')
}

function goProduct(id) {
  router.push('/product/' + id)
}

onMounted(() => {
  if (route.query.paid) {
    showToast('支付处理中，如未到账请稍后刷新')
  }
  loadOrder()
})
</script>

<style scoped>
.page-loading {
  padding: 80px 0;
}
.od-status-head {
  padding: 18px 16px;
  color: #fff;
}
.od-status-text {
  font-size: 20px;
  font-weight: 700;
}
.od-countdown {
  margin-top: 8px;
  font-size: 12px;
  display: flex;
  align-items: center;
}
.od-status-desc {
  margin-top: 6px;
  font-size: 12px;
  opacity: 0.9;
}
.od-group {
  display: flex;
  align-items: center;
  padding: 12px;
}
.od-group-icon {
  font-size: 26px;
  color: #e63946;
}
.od-group-info {
  flex: 1;
  margin: 0 10px;
  font-size: 13px;
}
.od-group-no {
  color: #9a9da5;
  font-size: 11px;
  margin-top: 2px;
}
.od-addr {
  display: flex;
  padding: 14px 12px;
}
.od-addr-icon {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: #ffe9ec;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #e63946;
  font-size: 18px;
  margin-right: 10px;
  flex-shrink: 0;
}
.od-addr-name {
  font-size: 15px;
  font-weight: 600;
}
.od-addr-phone {
  margin-left: 8px;
  color: #7a7d86;
  font-size: 13px;
}
.od-addr-detail {
  margin-top: 4px;
  font-size: 13px;
  color: #44464d;
  line-height: 18px;
}
.od-item {
  display: flex;
  padding: 8px 12px;
}
.od-img {
  width: 72px;
  height: 72px;
  border-radius: 8px;
  object-fit: cover;
  background: #f2f3f5;
  flex-shrink: 0;
}
.od-item-info {
  flex: 1;
  width: 0;
  margin-left: 10px;
}
.od-item-name {
  font-size: 13px;
  line-height: 18px;
}
.od-item-spec {
  margin-top: 3px;
  font-size: 11px;
  color: #9a9da5;
}
.od-item-line {
  margin-top: 6px;
  display: flex;
  justify-content: space-between;
}
.od-item-qty {
  color: #9a9da5;
  font-size: 12px;
}
.od-delivery-title {
  padding: 10px 12px 4px;
  font-weight: 600;
  font-size: 14px;
}
.od-delivery-meta {
  padding: 0 12px;
  color: #7a7d86;
  font-size: 12px;
}
.od-tracks {
  padding: 8px 12px 12px 18px;
}
.od-track {
  position: relative;
  padding: 0 0 14px 16px;
}
.od-track::before {
  content: '';
  position: absolute;
  left: 4px;
  top: 10px;
  bottom: -4px;
  width: 1px;
  background: #eee;
}
.od-track:last-child::before {
  display: none;
}
.od-track-dot {
  position: absolute;
  left: 0;
  top: 5px;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #d8dae0;
}
.od-track-dot.first {
  background: #e63946;
}
.od-track-desc {
  font-size: 13px;
  color: #7a7d86;
}
.od-track-desc.first {
  color: #22242a;
  font-weight: 600;
}
.od-track-time {
  font-size: 11px;
  color: #b0b3ba;
  margin-top: 2px;
}
.od-amounts {
  border-top: 1px solid #f5f5f7;
  margin: 0 12px;
  padding: 10px 0 12px;
}
.od-amount-row {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
  color: #44464d;
  padding: 4px 0;
}
.od-amount-row .price {
  font-size: 16px;
}
.od-deduct {
  color: #2a9d5c;
}
.od-info {
  padding: 12px;
}
.od-info-row {
  display: flex;
  font-size: 12px;
  color: #44464d;
  padding: 4px 0;
}
.od-info-row .label {
  width: 72px;
  color: #9a9da5;
  flex-shrink: 0;
}
.od-logs {
  padding: 0 12px 12px;
}
.od-log {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  color: #7a7d86;
  padding: 4px 0;
}
.od-log-time {
  color: #b0b3ba;
}
.od-footer-actions {
  flex: 1;
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}
.refund-body {
  padding: 12px 4px 4px;
}
</style>

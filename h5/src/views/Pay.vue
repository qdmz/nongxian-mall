<template>
  <div class="pay-page">
    <van-nav-bar title="收银台" left-arrow class="tc-nav" @click-left="$router.back()">
      <template #right>
        <van-icon name="home-o" size="18" @click="$router.push('/')" />
      </template>
    </van-nav-bar>

    <van-loading v-if="loading" class="page-loading" size="28" vertical>加载中...</van-loading>

    <template v-if="order">
      <div class="pay-amount-card">
        <div class="pay-label">订单金额</div>
        <div class="pay-amount">
          <span class="pay-symbol">¥</span>
          <span class="pay-num">{{ Number(order.pay_amount).toFixed(2) }}</span>
        </div>
        <div class="pay-order-no">订单号 {{ order.order_no }}</div>
        <div class="pay-status-tip" v-if="Number(order.status) !== 0">
          当前订单状态：{{ order.status_text }}，无需支付
        </div>
      </div>

      <!-- 余额信息 -->
      <div class="section">
        <van-cell center>
          <template #title>
            <span>钱包余额</span>
            <span class="pay-balance">¥{{ balance.toFixed(2) }}</span>
          </template>
          <template #right-icon>
            <van-button size="small" plain round @click="$router.push('/recharge')">
              去充值
            </van-button>
          </template>
        </van-cell>
      </div>

      <!-- 支付方式 -->
      <div class="section">
        <div class="section-head"><div class="section-title">选择支付方式</div></div>
        <van-radio-group v-model="payType">
          <!-- 余额支付（有余额时显示） -->
          <van-cell clickable @click="payType = 'balance'" v-if="balance > 0">
            <template #title>
              <div class="pay-method">
                <span class="pay-method-icon wallet">¥</span>
                <span>余额支付</span>
                <span v-if="balance < order.pay_amount" class="pay-method-tag">余额不足</span>
              </div>
            </template>
            <template #right-icon>
              <van-radio name="balance" :disabled="balance < order.pay_amount" />
            </template>
          </van-cell>
          <van-cell clickable @click="payType = 'wxpay'">
            <template #title>
              <div class="pay-method">
                <span class="pay-method-icon wechat">￥</span>
                <span>微信支付</span>
              </div>
            </template>
            <template #right-icon>
              <van-radio name="wxpay" />
            </template>
          </van-cell>
          <van-cell clickable @click="payType = 'alipay'">
            <template #title>
              <div class="pay-method">
                <span class="pay-method-icon alipay">支</span>
                <span>支付宝</span>
              </div>
            </template>
            <template #right-icon>
              <van-radio name="alipay" />
            </template>
          </van-cell>
          <van-cell clickable @click="payType = 'qqpay'">
            <template #title>
              <div class="pay-method">
                <span class="pay-method-icon qq">Q</span>
                <span>QQ钱包</span>
              </div>
            </template>
            <template #right-icon>
              <van-radio name="qqpay" />
            </template>
          </van-cell>
        </van-radio-group>
      </div>

      <div class="pay-tip">支付遇到问题？请联系工坊客服协助处理</div>

      <div class="footer-bar">
        <van-button
          round
          block
          class="tc-btn"
          :loading="paying"
          :disabled="Number(order.status) !== 0"
          @click="onPay"
        >
          确认支付 ¥{{ Number(order.pay_amount).toFixed(2) }}
        </van-button>
      </div>
    </template>

    <div v-if="!loading && !order" class="empty-wrap">
      <van-icon name="warning-o" />
      <p>订单不存在</p>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { showToast } from 'vant'
import { getOrderDetail, payOrder } from '../api/order'
import { useUserStore } from '../store/user'

const route = useRoute()
const router = useRouter()
const userStore = useUserStore()

const order = ref(null)
const loading = ref(false)
const paying = ref(false)
const payType = ref('wxpay')

const balance = computed(() => userStore.balance)

async function loadOrder() {
  loading.value = true
  try {
    order.value = await getOrderDetail(route.params.orderId)
  } catch (e) {
    order.value = null
  } finally {
    loading.value = false
  }
}

async function onPay() {
  if (!payType.value) {
    showToast('请选择支付方式')
    return
  }
  paying.value = true
  try {
    // 余额支付
    if (payType.value === 'balance') {
      const data = await payOrder(order.value.id, 'balance')
      if (data && data.success) {
        showToast('支付成功')
        router.replace('/order/detail/' + order.value.id)
      } else {
        showToast('支付失败，请稍后重试')
      }
      paying.value = false
      return
    }
    // 第三方支付
    const data = await payOrder(order.value.id, payType.value)
    if (data && data.pay_url) {
      showToast('正在跳转支付...')
      // 跳转第三方支付页面，支付完成后由回调跳回订单详情
      window.location.href = data.pay_url
    } else {
      showToast('未获取到支付链接，请稍后重试')
      paying.value = false
    }
  } catch (e) {
    paying.value = false
  }
}

onMounted(async () => {
  await loadOrder()
  if (userStore.token) {
    await userStore.fetchProfile().catch(() => {})
  }
  // 余额充足时默认选中余额支付
  if (balance.value >= order.value.pay_amount) {
    payType.value = 'balance'
  }
})
</script>

<style scoped>
.page-loading {
  padding: 80px 0;
}
.pay-amount-card {
  margin: 12px;
  padding: 28px 16px;
  border-radius: 10px;
  background: linear-gradient(135deg, #e63946 0%, #c1121f 100%);
  color: #fff;
  text-align: center;
}
.pay-label {
  font-size: 12px;
  opacity: 0.85;
}
.pay-amount {
  margin-top: 8px;
  font-weight: 800;
}
.pay-num {
  font-size: 38px;
}
.pay-symbol {
  font-size: 20px;
}
.pay-order-no {
  margin-top: 8px;
  font-size: 11px;
  opacity: 0.85;
}
.pay-status-tip {
  margin-top: 10px;
  font-size: 12px;
  background: rgba(255, 255, 255, 0.18);
  border-radius: 12px;
  display: inline-block;
  padding: 3px 12px;
}
.pay-balance {
  margin-left: 8px;
  color: #2a9d5c;
  font-weight: 700;
}
.pay-method {
  display: flex;
  align-items: center;
  font-size: 14px;
}
.pay-method-icon {
  width: 26px;
  height: 26px;
  border-radius: 6px;
  color: #fff;
  font-size: 14px;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-right: 10px;
}
.pay-method-icon.wechat {
  background: #07c160;
}
.pay-method-icon.alipay {
  background: #1677ff;
}
.pay-method-icon.qq {
  background: #12b7f5;
}
.pay-method-icon.wallet {
  background: #f59e0b;
}
.pay-method-tag {
  margin-left: 8px;
  font-size: 10px;
  color: #f59e0b;
  background: #fffbe6;
  padding: 1px 6px;
  border-radius: 3px;
  border: 1px solid #ffe58f;
}
.pay-tip {
  text-align: center;
  color: #b0b3ba;
  font-size: 11px;
  padding: 12px 0 90px;
}
</style>

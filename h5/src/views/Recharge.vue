<template>
  <div class="recharge-page">
    <van-nav-bar title="钱包充值" left-arrow class="tc-nav" @click-left="$router.back()" />

    <div class="section">
      <div class="rc-balance-row">
        <span>当前余额</span>
        <span class="rc-balance">¥{{ balance.toFixed(2) }}</span>
      </div>

      <van-field
        v-model="amount"
        type="number"
        label="充值金额"
        placeholder="请输入充值金额（元）"
        :rules="[{ validator: amountValidator, message: '充值金额需在 0.01 - 100000 元之间' }]"
      >
        <template #left-icon><span class="rc-yen">¥</span></template>
      </van-field>

      <div class="rc-quick">
        <span
          class="rc-quick-item"
          :class="{ active: Number(amount) === Number(q) }"
          v-for="q in quickAmounts"
          :key="q"
          @click="amount = String(q)"
        >¥{{ q }}</span>
      </div>
    </div>

    <div class="section">
      <div class="section-head"><div class="section-title">支付方式</div></div>
      <van-radio-group v-model="payType">
        <van-cell clickable @click="payType = 'wxpay'">
          <template #title>
            <div class="rc-method"><span class="rc-method-icon wechat">￥</span>微信支付</div>
          </template>
          <template #right-icon><van-radio name="wxpay" /></template>
        </van-cell>
        <van-cell clickable @click="payType = 'alipay'">
          <template #title>
            <div class="rc-method"><span class="rc-method-icon alipay">支</span>支付宝</div>
          </template>
          <template #right-icon><van-radio name="alipay" /></template>
        </van-cell>
        <van-cell clickable @click="payType = 'qqpay'">
          <template #title>
            <div class="rc-method"><span class="rc-method-icon qq">Q</span>QQ钱包</div>
          </template>
          <template #right-icon><van-radio name="qqpay" /></template>
        </van-cell>
      </van-radio-group>
    </div>

    <div class="rc-tip">充值成功后余额实时到账，可用于下单抵扣</div>

    <div class="footer-bar">
      <van-button round block class="tc-btn" :loading="submitting" @click="onSubmit">
        立即充值 {{ amountText }}
      </van-button>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { showToast } from 'vant'
import { recharge } from '../api/user'
import { useUserStore } from '../store/user'

const userStore = useUserStore()

const amount = ref('')
const payType = ref('wxpay')
const submitting = ref(false)
const quickAmounts = [50, 100, 200, 500]

const balance = computed(() => userStore.balance)
const amountText = computed(() => {
  const n = Number(amount.value)
  return n > 0 ? '¥' + n.toFixed(2) : ''
})

function amountValidator(v) {
  const n = Number(v)
  return n >= 0.01 && n <= 100000
}

async function onSubmit() {
  if (!amountValidator(amount.value)) {
    showToast('请输入正确的充值金额')
    return
  }
  submitting.value = true
  try {
    const data = await recharge({ amount: Number(amount.value), pay_type: payType.value })
    if (data && data.pay_url) {
      showToast('正在跳转支付...')
      window.location.href = data.pay_url
    } else {
      showToast('未获取到支付链接，请稍后重试')
    }
  } catch (e) {
    /* 已统一 toast */
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  userStore.fetchProfile().catch(() => {})
})
</script>

<style scoped>
.rc-balance-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 12px;
  font-size: 14px;
}
.rc-balance {
  color: #e63946;
  font-size: 20px;
  font-weight: 800;
}
.rc-yen {
  font-size: 18px;
  font-weight: 700;
  color: #44464d;
  margin-right: 2px;
}
.rc-quick {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  padding: 12px;
}
.rc-quick-item {
  width: calc(25% - 8px);
  text-align: center;
  padding: 10px 0;
  border-radius: 8px;
  background: #f5f5f7;
  color: #44464d;
  font-size: 14px;
  font-weight: 600;
}
.rc-quick-item.active {
  background: #ffe9ec;
  color: #e63946;
  border: 1px solid #e63946;
}
.rc-method {
  display: flex;
  align-items: center;
  font-size: 14px;
}
.rc-method-icon {
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
.rc-method-icon.wechat {
  background: #07c160;
}
.rc-method-icon.alipay {
  background: #1677ff;
}
.rc-method-icon.qq {
  background: #12b7f5;
}
.rc-tip {
  text-align: center;
  color: #b0b3ba;
  font-size: 11px;
  padding: 12px 0 90px;
}
</style>

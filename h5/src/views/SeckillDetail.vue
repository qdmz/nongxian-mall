<template>
  <div class="skd page-with-footerbar">
    <van-nav-bar title="限时秒杀" left-arrow class="tc-nav" @click-left="$router.back()">
      <template #right>
        <van-icon name="home-o" size="18" @click="$router.push('/')" />
      </template>
    </van-nav-bar>

    <van-loading v-if="loading" class="page-loading" size="28" vertical>加载中...</van-loading>

    <template v-if="activity">
      <img class="skd-cover" :src="activity.cover_image" :alt="activity.name" @click="goProduct" />

      <!-- 秒杀价条 -->
      <div class="skd-bar">
        <div class="skd-bar-left">
          <div class="skd-bar-price">
            <span class="cny">¥</span><em>{{ Number(activity.seckill_price).toFixed(2) }}</em>
            <span class="skd-bar-old">¥{{ Number(activity.original_price).toFixed(2) }}</span>
          </div>
          <div class="skd-bar-sub">已抢 {{ activity.sold }} {{ activity.unit || '件' }} · 剩余 {{ activity.stock }}</div>
        </div>
        <div class="skd-bar-right">
          <div class="skd-bar-label">{{ activity.state === 'upcoming' ? '距开始' : activity.state === 'ended' ? '已结束' : '距结束' }}</div>
          <CountDown v-if="endAt && activity.state !== 'ended'" class="skd-cd" :end="endAt" @finish="loadDetail" />
        </div>
      </div>

      <div class="section skd-info">
        <div class="skd-name" @click="goProduct">{{ activity.title || activity.name }}</div>
        <div class="skd-sub" v-if="activity.subtitle">{{ activity.subtitle }}</div>
        <div class="skd-tags">
          <span class="skd-tag">{{ activity.discount }}折</span>
          <span class="skd-tag" v-if="Number(activity.limit_per_user) > 0">每人限购 {{ activity.limit_per_user }}</span>
          <span class="skd-tag" v-if="activity.sku_specs">规格：{{ specsText }}</span>
        </div>
        <van-progress class="skd-progress" :percentage="activity.sold_percent" :pivot-text="'已抢' + activity.sold_percent + '%'" color="#e63946" track-color="#ffe3e6" />
      </div>

      <div class="section skd-rules">
        <div class="section-head"><div class="section-title">秒杀说明</div></div>
        <div class="skd-rule">1. 秒杀价仅限活动时间内、库存售完即止；</div>
        <div class="skd-rule" v-if="Number(activity.limit_per_user) > 0">2. 每人限购 {{ activity.limit_per_user }} {{ activity.unit || '件' }}（未付款取消的不计入）；</div>
        <div class="skd-rule">3. 下单后请在 30 分钟内完成支付，超时自动取消并释放库存；</div>
        <div class="skd-rule">4. 运费按平台运费模板计算，可使用钱包余额抵扣。</div>
      </div>

      <div class="footer-bar">
        <div class="skd-footer-left">
          <van-stepper v-model="quantity" :min="1" :max="maxQuantity" integer :disabled="activity.state !== 'ongoing'" />
        </div>
        <van-button round class="tc-btn skd-footer-btn" :disabled="activity.state !== 'ongoing' || maxQuantity < 1" @click="buyNow">
          {{ buttonText }}
        </van-button>
      </div>
    </template>

    <div v-if="!loading && !activity" class="empty-wrap">
      <van-icon name="warning-o" />
      <p>秒杀活动不存在或已结束</p>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { showToast } from 'vant'
import { getSeckillDetail } from '../api/seckill'
import { useUserStore } from '../store/user'
import { skuSpecsText } from '../utils'
import CountDown from '../components/CountDown.vue'

const route = useRoute()
const router = useRouter()
const userStore = useUserStore()

const loading = ref(true)
const activity = ref(null)
const quantity = ref(1)
const endAt = ref(0)

const specsText = computed(() => (activity.value?.sku_specs ? skuSpecsText(activity.value.sku_specs) : ''))
const maxQuantity = computed(() => {
  const a = activity.value
  if (!a) return 1
  let max = Math.min(99, Number(a.stock) || 0)
  const limit = Number(a.limit_per_user) || 0
  if (limit > 0) max = Math.min(max, limit - (Number(a.my_bought) || 0))
  return Math.max(0, max)
})
const buttonText = computed(() => {
  const a = activity.value
  if (!a) return ''
  if (a.state === 'upcoming') return '即将开始'
  if (a.state === 'ended') return '已结束'
  if (a.state === 'sold_out') return '已抢光'
  if (maxQuantity.value < 1) return '已达限购'
  return '立即抢购'
})

async function loadDetail() {
  try {
    const data = await getSeckillDetail(route.params.id)
    activity.value = data
    // 用服务端剩余秒数换算本地结束时刻，避免手机时间不准
    endAt.value = data && data.remaining_seconds > 0 ? Math.floor(Date.now() / 1000) + Number(data.remaining_seconds) : 0
    if (quantity.value > maxQuantity.value) quantity.value = Math.max(1, maxQuantity.value)
  } catch (e) {
    activity.value = null
  } finally {
    loading.value = false
  }
}

function goProduct() {
  if (activity.value) router.push('/product/' + activity.value.product_id)
}

function buyNow() {
  if (!userStore.token) {
    router.push({ path: '/login', query: { redirect: route.fullPath } })
    return
  }
  if (maxQuantity.value < 1) {
    showToast('已达每人限购数量')
    return
  }
  router.push({ path: '/confirm-order', query: { seckill_id: activity.value.id, quantity: quantity.value } })
}

onMounted(loadDetail)
</script>

<style scoped>
.page-loading {
  padding: 80px 0;
}
.skd-cover {
  display: block;
  width: 100%;
  max-height: 420px;
  aspect-ratio: 1 / 1;
  object-fit: cover;
  background: #f2f3f5;
}
.skd-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 12px;
  background: linear-gradient(135deg, #e63946, #ff7b54);
  color: #fff;
}
.skd-bar-price .cny {
  font-size: 14px;
  font-weight: 700;
}
.skd-bar-price em {
  font-style: normal;
  font-size: 26px;
  font-weight: 800;
}
.skd-bar-old {
  margin-left: 8px;
  font-size: 12px;
  opacity: 0.8;
  text-decoration: line-through;
}
.skd-bar-sub {
  font-size: 11px;
  opacity: 0.9;
  margin-top: 2px;
}
.skd-bar-right {
  text-align: right;
}
.skd-bar-label {
  font-size: 11px;
  opacity: 0.9;
}
.skd-cd {
  display: inline-block;
  margin-top: 2px;
  padding: 1px 6px;
  border-radius: 4px;
  background: rgba(255, 255, 255, 0.95);
  color: #e63946;
  font-weight: 700;
  font-size: 13px;
}
.skd-info {
  padding: 12px;
}
.skd-name {
  font-size: 16px;
  font-weight: 700;
  line-height: 22px;
}
.skd-sub {
  margin-top: 4px;
  font-size: 12px;
  color: #7a7d86;
}
.skd-tags {
  margin-top: 8px;
}
.skd-tag {
  display: inline-block;
  font-size: 11px;
  color: #e63946;
  background: #ffe9ec;
  border-radius: 3px;
  padding: 1px 6px;
  margin: 0 6px 4px 0;
}
.skd-progress {
  margin-top: 10px;
}
.skd-rules {
  padding-bottom: 10px;
}
.skd-rule {
  padding: 2px 12px;
  font-size: 12px;
  line-height: 20px;
  color: #5c5f66;
}
.skd-footer-left {
  flex: 1;
}
.skd-footer-btn {
  min-width: 140px;
}
</style>

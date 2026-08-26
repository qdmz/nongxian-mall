<template>
  <div class="gbd page-with-footerbar">
    <van-nav-bar title="拼团活动" left-arrow class="tc-nav" @click-left="$router.back()" />

    <van-loading v-if="loading" class="page-loading" size="28" vertical>加载中...</van-loading>

    <template v-if="activity">
      <!-- 商品卡片 -->
      <div class="section gbd-product">
        <img class="gbd-img" :src="activity.cover_image" :alt="activity.name" @click="goProduct" />
        <div class="gbd-info">
          <div class="gbd-name ellipsis-2" @click="goProduct">{{ activity.name }}</div>
          <div class="gbd-sub ellipsis" v-if="activity.subtitle">{{ activity.subtitle }}</div>
          <div class="gbd-price-row">
            <span class="gbd-price">¥{{ Number(activity.group_price).toFixed(2) }}</span>
            <span class="gbd-old">单买价 ¥{{ Number(activity.original_price).toFixed(2) }}</span>
          </div>
        </div>
      </div>

      <div class="gbd-stats">
        <div class="gbd-stat">
          <em>{{ activity.required_count }}</em>
          <span>人成团</span>
        </div>
        <div class="gbd-stat">
          <em>{{ discountText }}</em>
          <span>折优惠</span>
        </div>
        <div class="gbd-stat">
          <em>{{ activity.valid_hours }}</em>
          <span>小时限时成团</span>
        </div>
        <div class="gbd-stat">
          <em>{{ activity.stock }}</em>
          <span>剩余库存</span>
        </div>
      </div>

      <!-- 进行中的团 -->
      <div class="section">
        <div class="section-head">
          <div class="section-title">{{ (activity.groups || []).length }} 个团正在拼，可直接参团</div>
        </div>
        <div class="gbd-groups" v-if="activity.groups && activity.groups.length">
          <div class="gbd-group" v-for="group in activity.groups" :key="group.id">
            <img class="gbd-avatar" :src="group.leader_avatar || defaultAvatar" alt="" />
            <div class="gbd-group-info">
              <div class="gbd-group-name">{{ group.leader_nickname || '农户好友' }}</div>
              <div class="gbd-group-meta">
                还差 <em>{{ group.remaining_count }}</em> 人，
                剩余 <CountDown :end="groupEnd(group)" @finish="loadActivity" />
              </div>
            </div>
            <van-button size="small" round class="tc-btn" @click="openJoin(group.id)">去参团</van-button>
          </div>
        </div>
        <div class="gbd-no-group" v-else>
          <van-icon name="friends-o" />
          <p>还没有进行中的团，快来当第一个团长吧！</p>
        </div>
      </div>

      <!-- 规则说明 -->
      <div class="section gbd-rules">
        <div class="section-head"><div class="section-title">拼团规则</div></div>
        <div class="gbd-rule">1. 选择「单独开团」或加入进行中的团，按拼团价下单；</div>
        <div class="gbd-rule">2. 支付成功后即刻占位，并在 {{ activity.valid_hours }} 小时内邀请好友参团；</div>
        <div class="gbd-rule">3. 成团人数满 {{ activity.required_count }} 人即拼团成功，商家安排发货；</div>
        <div class="gbd-rule">4. 超时未成团将自动全额退款（含余额部分原路退回）。</div>
      </div>

      <!-- 底部开团 -->
      <div class="footer-bar">
        <div class="gbd-footer-price">
          拼团价 <span class="price">¥{{ Number(activity.group_price).toFixed(2) }}</span>
        </div>
        <van-button round class="tc-btn gbd-footer-btn" @click="openJoin(0)">单独开团</van-button>
      </div>
    </template>

    <div v-if="!loading && !activity" class="empty-wrap">
      <van-icon name="warning-o" />
      <p>拼团活动不存在或已结束</p>
    </div>

    <!-- 下单弹层 -->
    <van-popup v-model:show="joinVisible" position="bottom" round>
      <div class="join-body">
        <div class="join-title">{{ joinGroupId ? '加入拼团' : '单独开团' }}</div>

        <div class="join-product" v-if="activity">
          <img class="join-img" :src="activity.cover_image" alt="" />
          <div class="join-info">
            <div class="ellipsis-2 join-name">{{ activity.name }}</div>
            <div class="join-price">¥{{ Number(activity.group_price).toFixed(2) }}</div>
          </div>
        </div>

        <van-cell
          title="收货地址"
          is-link
          :value="address ? address.consignee + ' ' + address.phone : '请选择'"
          @click="showAddrPicker = true"
        />

        <div class="join-row">
          <span class="join-label">数量</span>
          <van-stepper v-model="quantity" :max="maxQuantity" integer />
        </div>

        <van-button round block class="tc-btn" style="margin-top: 16px" :loading="submitting" @click="submitJoin">
          {{ joinGroupId ? '立即参团' : '立即开团' }}
        </van-button>
      </div>
    </van-popup>

    <!-- 地址选择 -->
    <van-popup v-model:show="showAddrPicker" position="bottom" round>
      <div class="addr-pop">
        <div class="addr-pop-title">选择收货地址</div>
        <div v-if="addresses.length">
          <div
            class="addr-pop-item"
            :class="{ active: address && address.id === addr.id }"
            v-for="addr in addresses"
            :key="addr.id"
            @click="chooseAddr(addr)"
          >
            <div>
              <span class="addr-pop-name">{{ addr.consignee }}</span>
              <span class="addr-pop-phone">{{ addr.phone }}</span>
              <span class="addr-pop-default" v-if="addr.is_default">默认</span>
            </div>
            <div class="addr-pop-detail">{{ addr.province }}{{ addr.city }}{{ addr.district }}{{ addr.detail }}</div>
          </div>
        </div>
        <div class="empty-wrap" v-else>
          <van-icon name="location-o" />
          <p>还没有收货地址</p>
        </div>
        <van-button round block plain type="primary" style="margin: 12px" @click="goAddAddress">
          新增收货地址
        </van-button>
      </div>
    </van-popup>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { showToast } from 'vant'
import { getActivityDetail, createGroupBuyOrder } from '../api/groupBuy'
import { getAddresses } from '../api/user'
import { useUserStore } from '../store/user'
import CountDown from '../components/CountDown.vue'

const route = useRoute()
const router = useRouter()
const userStore = useUserStore()

const defaultAvatar =
  'data:image/svg+xml,' +
  encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64"><rect width="64" height="64" fill="#ffe9ec"/><circle cx="32" cy="24" r="10" fill="#e63946"/><path d="M12 58c0-12 9-18 20-18s20 6 20 18" fill="#e63946"/></svg>'
  )

const activity = ref(null)
const loading = ref(false)
const submitting = ref(false)

const joinVisible = ref(false)
const joinGroupId = ref(0)
const quantity = ref(1)

const addresses = ref([])
const address = ref(null)
const showAddrPicker = ref(false)

const maxQuantity = computed(() => {
  const a = activity.value
  if (!a) return 1
  const stock = Number(a.stock || 0)
  const maxCount = Number(a.max_count || 0)
  return Math.max(1, Math.min(stock || 99, maxCount || 99))
})

const discountText = computed(() => {
  const a = activity.value
  if (!a || !Number(a.original_price)) return '--'
  return (Number(a.group_price) / Number(a.original_price) * 10).toFixed(1)
})

function groupEnd(group) {
  return Math.floor(Date.now() / 1000) + Number(group.remaining_seconds || 0)
}

async function loadActivity() {
  loading.value = true
  try {
    activity.value = await getActivityDetail(route.params.id)
  } catch (e) {
    activity.value = null
  } finally {
    loading.value = false
  }
}

async function loadAddresses() {
  addresses.value = await getAddresses()
  address.value = addresses.value.find((a) => a.is_default) || addresses.value[0] || null
}

function chooseAddr(addr) {
  address.value = addr
  showAddrPicker.value = false
}

function goAddAddress() {
  showAddrPicker.value = false
  router.push('/address/edit')
}

function openJoin(groupId) {
  if (!userStore.token) {
    router.push({ path: '/login', query: { redirect: route.fullPath } })
    return
  }
  joinGroupId.value = groupId
  quantity.value = 1
  joinVisible.value = true
}

async function submitJoin() {
  if (!address.value) {
    showToast('请先选择收货地址')
    showAddrPicker.value = true
    return
  }
  submitting.value = true
  try {
    const order = await createGroupBuyOrder({
      activity_id: activity.value.id,
      group_buy_id: joinGroupId.value || undefined,
      quantity: quantity.value,
      address_id: address.value.id
    })
    joinVisible.value = false
    showToast(joinGroupId.value ? '参团成功，请完成支付' : '开团成功，请完成支付')
    if (Number(order.pay_amount) > 0) {
      router.replace('/pay/' + order.id)
    } else {
      router.replace('/order/detail/' + order.id)
    }
  } catch (e) {
    /* 已统一 toast */
  } finally {
    submitting.value = false
  }
}

function goProduct() {
  if (activity.value?.product_id) {
    router.push('/product/' + activity.value.product_id)
  }
}

onMounted(async () => {
  await loadActivity()
  if (userStore.token) {
    loadAddresses().catch(() => {})
  }
})
</script>

<style scoped>
.page-loading {
  padding: 80px 0;
}
.gbd-product {
  display: flex;
  padding: 12px;
}
.gbd-img {
  width: 110px;
  height: 110px;
  border-radius: 8px;
  object-fit: cover;
  background: #f2f3f5;
  flex-shrink: 0;
}
.gbd-info {
  flex: 1;
  width: 0;
  margin-left: 10px;
  display: flex;
  flex-direction: column;
}
.gbd-name {
  font-size: 15px;
  font-weight: 600;
  line-height: 20px;
}
.gbd-sub {
  margin-top: 4px;
  font-size: 11px;
  color: #9a9da5;
}
.gbd-price-row {
  margin-top: auto;
  display: flex;
  align-items: baseline;
  gap: 8px;
}
.gbd-price {
  color: #e63946;
  font-size: 22px;
  font-weight: 800;
}
.gbd-old {
  color: #b0b3ba;
  font-size: 12px;
  text-decoration: line-through;
}

.gbd-stats {
  display: flex;
  margin: 0 10px 10px;
  background: #fff;
  border-radius: 10px;
  padding: 12px 0;
}
.gbd-stat {
  flex: 1;
  text-align: center;
}
.gbd-stat em {
  font-style: normal;
  font-size: 20px;
  font-weight: 800;
  color: #e63946;
  display: block;
}
.gbd-stat span {
  font-size: 11px;
  color: #7a7d86;
}

.gbd-groups {
  padding: 0 12px 8px;
}
.gbd-group {
  display: flex;
  align-items: center;
  padding: 10px 0;
  border-bottom: 1px solid #f5f5f7;
}
.gbd-group:last-child {
  border-bottom: none;
}
.gbd-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
  background: #f2f3f5;
  flex-shrink: 0;
}
.gbd-group-info {
  flex: 1;
  width: 0;
  margin: 0 10px;
}
.gbd-group-name {
  font-size: 13px;
  font-weight: 600;
}
.gbd-group-meta {
  margin-top: 2px;
  font-size: 12px;
  color: #7a7d86;
}
.gbd-group-meta em {
  font-style: normal;
  color: #e63946;
  font-weight: 700;
}
.gbd-no-group {
  padding: 0 12px 14px;
  text-align: center;
  color: #9a9da5;
  font-size: 12px;
}
.gbd-no-group .van-icon {
  font-size: 34px;
  color: #d8dae0;
}
.gbd-no-group p {
  margin: 8px 0 0;
}

.gbd-rules {
  padding-bottom: 14px;
}
.gbd-rule {
  padding: 3px 12px;
  font-size: 12px;
  color: #7a7d86;
  line-height: 18px;
}

.gbd-footer-price {
  flex: 1;
  font-size: 13px;
}
.gbd-footer-price .price {
  font-size: 22px;
}
.gbd-footer-btn {
  min-width: 140px;
  font-weight: 700;
}

.join-body {
  padding: 16px;
  padding-bottom: calc(16px + env(safe-area-inset-bottom));
}
.join-title {
  text-align: center;
  font-size: 16px;
  font-weight: 700;
  margin-bottom: 12px;
}
.join-product {
  display: flex;
  padding: 10px;
  background: #f9f9fa;
  border-radius: 8px;
}
.join-img {
  width: 70px;
  height: 70px;
  border-radius: 6px;
  object-fit: cover;
  background: #f2f3f5;
  flex-shrink: 0;
}
.join-info {
  flex: 1;
  width: 0;
  margin-left: 10px;
}
.join-name {
  font-size: 13px;
  line-height: 18px;
}
.join-price {
  margin-top: 6px;
  color: #e63946;
  font-size: 18px;
  font-weight: 800;
}
.join-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 4px 0;
}
.join-label {
  color: #44464d;
  font-size: 14px;
}

.addr-pop {
  padding: 0 12px 12px;
  max-height: 70vh;
  overflow-y: auto;
}
.addr-pop-title {
  text-align: center;
  font-size: 15px;
  font-weight: 600;
  padding: 14px 0 8px;
}
.addr-pop-item {
  padding: 10px;
  border: 1px solid #eee;
  border-radius: 8px;
  margin-bottom: 8px;
}
.addr-pop-item.active {
  border-color: #e63946;
  background: #fff7f8;
}
.addr-pop-name {
  font-size: 14px;
  font-weight: 600;
}
.addr-pop-phone {
  margin-left: 8px;
  color: #7a7d86;
  font-size: 12px;
}
.addr-pop-default {
  margin-left: 6px;
  background: #ffe9ec;
  color: #e63946;
  font-size: 10px;
  padding: 0 4px;
  border-radius: 3px;
}
.addr-pop-detail {
  margin-top: 4px;
  font-size: 12px;
  color: #7a7d86;
}
</style>

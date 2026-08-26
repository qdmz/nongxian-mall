<template>
  <div class="gbg page-with-footerbar">
    <van-nav-bar title="拼团详情" left-arrow class="tc-nav" @click-left="$router.back()" />

    <van-loading v-if="loading" class="page-loading" size="28" vertical>加载中...</van-loading>

    <template v-if="group">
      <!-- 团状态 -->
      <div class="gbg-head">
        <div class="gbg-status" v-if="Number(group.status) === 0">
          <template v-if="remainingCount > 0">
            还差 <em>{{ remainingCount }}</em> 人成团
            <div class="gbg-cd">剩余 <CountDown :end="group.expire_at" @finish="loadGroup" /></div>
          </template>
          <template v-else>人已满，等待成团</template>
        </div>
        <div class="gbg-status done" v-else-if="Number(group.status) === 1">🎉 拼团成功，感谢助力</div>
        <div class="gbg-status fail" v-else-if="Number(group.status) === 2">拼团失败，已自动退款</div>
        <div class="gbg-status fail" v-else>该团已取消</div>

        <!-- 成员头像 -->
        <div class="gbg-members">
          <div class="gbg-slot" v-for="i in Number(group.required_count || 2)" :key="i">
            <template v-if="members[i - 1]">
              <img class="gbg-avatar" :src="members[i - 1].avatar || defaultAvatar" alt="" />
              <span class="gbg-leader" v-if="members[i - 1].is_leader">团长</span>
            </template>
            <template v-else>
              <div class="gbg-avatar empty">?</div>
            </template>
          </div>
        </div>

        <div class="gbg-joined" v-if="members.length">
          <span v-for="(m, i) in members" :key="i">{{ m.nickname || '好友' }}{{ i < members.length - 1 ? '、' : '' }}已参团</span>
        </div>
      </div>

      <!-- 商品信息 -->
      <div class="section gbg-product" @click="goProduct" v-if="group.activity">
        <div class="gbg-product-info">
          <div class="gbg-product-name ellipsis-2">{{ group.activity.product_name || '拼团商品' }}</div>
          <div class="gbg-price-row">
            <span class="gbg-price">¥{{ Number(group.group_price).toFixed(2) }}</span>
            <span class="gbg-people">{{ group.required_count }}人团 · 已加入{{ group.joined_count }}人</span>
          </div>
        </div>
        <van-icon name="arrow" color="#c8c9ce" />
      </div>

      <!-- 拼团号与时间 -->
      <div class="section gbg-info">
        <div class="gbg-info-row"><span class="label">团号</span><span>{{ group.group_no }}</span></div>
        <div class="gbg-info-row"><span class="label">开团时间</span><span>{{ formatTime(group.created_at) }}</span></div>
        <div class="gbg-info-row" v-if="group.success_at"><span class="label">成团时间</span><span>{{ formatTime(group.success_at) }}</span></div>
      </div>

      <!-- 底部操作 -->
      <div class="footer-bar" v-if="Number(group.status) === 0">
        <van-button round plain type="primary" class="gbg-btn-invite" @click="invite">邀请好友参团</van-button>
        <van-button round class="tc-btn gbg-btn-join" @click="openJoin" v-if="remainingCount > 0">立即参团</van-button>
      </div>
    </template>

    <div v-if="!loading && !group" class="empty-wrap">
      <van-icon name="warning-o" />
      <p>拼团不存在</p>
    </div>

    <!-- 参团弹层 -->
    <van-popup v-model:show="joinVisible" position="bottom" round>
      <div class="join-body">
        <div class="join-title">加入拼团</div>
        <div class="join-price-line" v-if="group">
          拼团价 <em>¥{{ Number(group.group_price).toFixed(2) }}</em>
        </div>
        <van-cell
          title="收货地址"
          is-link
          :value="address ? address.consignee + ' ' + address.phone : '请选择'"
          @click="showAddrPicker = true"
        />
        <div class="join-row">
          <span class="join-label">数量</span>
          <van-stepper v-model="quantity" :max="99" integer />
        </div>
        <van-button round block class="tc-btn" style="margin-top: 16px" :loading="submitting" @click="submitJoin">
          立即参团
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
import { getGroupDetail, createGroupBuyOrder } from '../api/groupBuy'
import { getAddresses } from '../api/user'
import { useUserStore } from '../store/user'
import { formatTime, copyText } from '../utils'
import CountDown from '../components/CountDown.vue'

const route = useRoute()
const router = useRouter()
const userStore = useUserStore()

const defaultAvatar =
  'data:image/svg+xml,' +
  encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64"><rect width="64" height="64" fill="#e8f6ee"/><circle cx="32" cy="24" r="10" fill="#2a9d5c"/><path d="M12 58c0-12 9-18 20-18s20 6 20 18" fill="#2a9d5c"/></svg>'
  )

const group = ref(null)
const loading = ref(false)
const submitting = ref(false)

const joinVisible = ref(false)
const quantity = ref(1)
const addresses = ref([])
const address = ref(null)
const showAddrPicker = ref(false)

const members = computed(() => group.value?.members || [])
const remainingCount = computed(() =>
  Math.max(0, Number(group.value?.required_count || 0) - Number(group.value?.joined_count || 0))
)

async function loadGroup() {
  loading.value = true
  try {
    group.value = await getGroupDetail(route.params.id)
  } catch (e) {
    group.value = null
  } finally {
    loading.value = false
  }
}

async function invite() {
  const link = window.location.href
  const ok = await copyText(link)
  showToast(ok ? '链接已复制，快去分享给好友吧' : '复制失败，请手动复制地址栏链接')
}

function openJoin() {
  if (!userStore.token) {
    router.push({ path: '/login', query: { redirect: route.fullPath } })
    return
  }
  if (remainingCount.value <= 0) {
    showToast('该团已满')
    return
  }
  quantity.value = 1
  joinVisible.value = true
}

function chooseAddr(addr) {
  address.value = addr
  showAddrPicker.value = false
}

function goAddAddress() {
  showAddrPicker.value = false
  router.push('/address/edit')
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
      activity_id: group.value.activity_id,
      group_buy_id: group.value.id,
      quantity: quantity.value,
      address_id: address.value.id
    })
    joinVisible.value = false
    showToast('参团成功，请完成支付')
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
  if (group.value?.activity?.product_id) {
    router.push('/product/' + group.value.activity.product_id)
  }
}

onMounted(async () => {
  await loadGroup()
  if (userStore.token) {
    getAddresses()
      .then((list) => {
        addresses.value = list || []
        address.value = addresses.value.find((a) => a.is_default) || addresses.value[0] || null
      })
      .catch(() => {})
  }
})
</script>

<style scoped>
.page-loading {
  padding: 80px 0;
}
.gbg-head {
  margin: 12px 10px;
  padding: 24px 16px;
  border-radius: 10px;
  background: linear-gradient(135deg, #e63946 0%, #c1121f 100%);
  color: #fff;
  text-align: center;
}
.gbg-status {
  font-size: 18px;
  font-weight: 800;
}
.gbg-status em {
  font-style: normal;
  font-size: 28px;
  color: #ffd166;
  padding: 0 4px;
}
.gbg-status.done {
  color: #ffe9a8;
}
.gbg-status.fail {
  opacity: 0.9;
  font-size: 16px;
}
.gbg-cd {
  margin-top: 8px;
  font-size: 12px;
  font-weight: 400;
  opacity: 0.95;
}
.gbg-members {
  margin-top: 20px;
  display: flex;
  justify-content: center;
  gap: 14px;
}
.gbg-slot {
  position: relative;
}
.gbg-avatar {
  width: 52px;
  height: 52px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid rgba(255, 255, 255, 0.8);
  background: #f2f3f5;
}
.gbg-avatar.empty {
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.3);
  color: #fff;
  font-size: 22px;
  border-style: dashed;
}
.gbg-leader {
  position: absolute;
  bottom: -6px;
  left: 50%;
  transform: translateX(-50%);
  background: #ffd166;
  color: #a4161a;
  font-size: 10px;
  border-radius: 6px;
  padding: 0 6px;
  white-space: nowrap;
}
.gbg-joined {
  margin-top: 18px;
  font-size: 12px;
  opacity: 0.9;
  line-height: 18px;
}
.gbg-product {
  display: flex;
  align-items: center;
  padding: 12px;
}
.gbg-product-info {
  flex: 1;
  width: 0;
  margin-right: 10px;
}
.gbg-product-name {
  font-size: 14px;
  font-weight: 600;
  line-height: 19px;
}
.gbg-price-row {
  margin-top: 6px;
  display: flex;
  align-items: baseline;
  gap: 8px;
}
.gbg-price {
  color: #e63946;
  font-size: 20px;
  font-weight: 800;
}
.gbg-people {
  font-size: 11px;
  color: #9a9da5;
}
.gbg-info {
  padding: 12px;
}
.gbg-info-row {
  display: flex;
  font-size: 12px;
  color: #44464d;
  padding: 4px 0;
}
.gbg-info-row .label {
  width: 72px;
  color: #9a9da5;
}
.gbg-btn-invite {
  flex: 1;
  margin-right: 10px;
  border-color: #e63946;
  color: #e63946;
  font-weight: 600;
}
.gbg-btn-join {
  flex: 1;
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
.join-price-line {
  text-align: center;
  color: #44464d;
  font-size: 13px;
  margin-bottom: 12px;
}
.join-price-line em {
  font-style: normal;
  color: #e63946;
  font-size: 22px;
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
.addr-pop-detail {
  margin-top: 4px;
  font-size: 12px;
  color: #7a7d86;
}
</style>

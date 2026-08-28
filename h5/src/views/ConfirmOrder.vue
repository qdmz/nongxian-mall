<template>
  <div class="co page-with-footerbar">
    <van-nav-bar title="确认订单" left-arrow class="tc-nav" @click-left="$router.back()">
      <template #right>
        <van-icon name="home-o" size="18" @click="$router.push('/')" />
      </template>
    </van-nav-bar>

    <van-loading v-if="loading" class="page-loading" size="28" vertical>加载中...</van-loading>

    <template v-if="!loading">
      <!-- 收货地址 -->
      <div class="section addr-card" @click="showAddressPicker = true">
        <template v-if="address">
          <div class="addr-top">
            <span class="addr-name">{{ address.consignee }}</span>
            <span class="addr-phone">{{ address.phone }}</span>
            <span class="addr-default" v-if="address.is_default">默认</span>
          </div>
          <div class="addr-detail">{{ fullAddress(address) }}</div>
          <van-icon name="arrow" class="addr-arrow" />
        </template>
        <div v-else class="addr-empty">
          <van-icon name="add-o" />
          <span>暂无收货地址，点击添加</span>
        </div>
      </div>

      <!-- 商品清单 -->
      <div class="section">
        <div class="section-head"><div class="section-title">商品清单</div></div>
        <div class="co-item" v-for="(item, i) in items" :key="i">
          <img class="co-img" :src="item.image" alt="" />
          <div class="co-info">
            <div class="co-name ellipsis-2">{{ item.name }}</div>
            <div class="co-spec" v-if="item.specs">{{ item.specs }}</div>
            <div class="co-line">
              <span class="price">¥{{ Number(item.price).toFixed(2) }}</span>
              <span class="co-qty">×{{ item.quantity }}</span>
            </div>
          </div>
        </div>
        <van-cell-group :border="false">
          <van-field
            v-model="remark"
            label="订单备注"
            placeholder="选填，给农户留言（50字以内）"
            maxlength="50"
            class="co-remark"
          />
        </van-cell-group>
      </div>

      <!-- 余额抵扣 -->
      <div class="section">
        <van-cell center>
          <template #title>
            <span class="co-balance-label">余额抵扣</span>
            <span class="co-balance-val">可用余额 ¥{{ balance.toFixed(2) }}</span>
          </template>
          <template #right-icon>
            <van-switch v-model="useBalance" size="20" :disabled="balance <= 0" />
          </template>
        </van-cell>
        <div class="co-amounts">
          <div class="co-amount-row">
            <span>商品总额</span>
            <span>¥{{ totalAmount.toFixed(2) }}</span>
          </div>
          <div class="co-amount-row" v-if="useBalance && balanceDeduct > 0">
            <span>余额抵扣</span>
            <span class="co-deduct">-¥{{ balanceDeduct.toFixed(2) }}</span>
          </div>
          <div class="co-amount-row co-amount-total">
            <span>预计实付</span>
            <span class="price">¥{{ payAmount.toFixed(2) }}</span>
          </div>
        </div>
      </div>

      <div class="co-tip">下单即表示支持乡村振兴，感谢您的每一份助力 🚩</div>

      <!-- 底部提交 -->
      <div class="footer-bar">
        <div class="co-footer-total">
          合计 <span class="price">¥{{ payAmount.toFixed(2) }}</span>
        </div>
        <van-button round class="tc-btn co-submit" :loading="submitting" @click="onSubmit">
          提交订单
        </van-button>
      </div>
    </template>

    <!-- 地址选择弹层 -->
    <van-popup v-model:show="showAddressPicker" position="bottom" round>
      <div class="addr-popup">
        <div class="addr-popup-title">选择收货地址</div>
        <div class="addr-popup-list" v-if="addresses.length">
          <div
            class="addr-popup-item"
            :class="{ active: address && address.id === addr.id }"
            v-for="addr in addresses"
            :key="addr.id"
            @click="chooseAddress(addr)"
          >
            <div class="addr-top">
              <span class="addr-name">{{ addr.consignee }}</span>
              <span class="addr-phone">{{ addr.phone }}</span>
            </div>
            <div class="addr-detail">{{ fullAddress(addr) }}</div>
          </div>
        </div>
        <div class="empty-wrap" v-else>
          <van-icon name="location-o" />
          <p>还没有收货地址</p>
        </div>
        <van-button round block class="tc-btn" style="margin: 12px" @click="goAddAddress">
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
import { getCart } from '../api/cart'
import { getProductDetail } from '../api/product'
import { getAddresses } from '../api/user'
import { createOrder } from '../api/order'
import { useUserStore } from '../store/user'
import { skuSpecsText } from '../utils'

const route = useRoute()
const router = useRouter()
const userStore = useUserStore()

const loading = ref(true)
const submitting = ref(false)

const items = ref([])
const addresses = ref([])
const address = ref(null)
const remark = ref('')
const useBalance = ref(false)
const showAddressPicker = ref(false)

const fromCart = computed(() => route.query.from === 'cart')

const totalAmount = computed(() =>
  items.value.reduce((s, i) => s + Number(i.price) * Number(i.quantity), 0)
)
const balance = computed(() => userStore.balance)
const balanceDeduct = computed(() => {
  if (!useBalance.value) return 0
  return Math.min(userStore.balance, totalAmount.value)
})
const payAmount = computed(() => Math.max(0, totalAmount.value - balanceDeduct.value))

function fullAddress(addr) {
  return `${addr.province}${addr.city}${addr.district}${addr.detail}`
}

async function loadItems() {
  if (fromCart.value) {
    const data = await getCart()
    const selected = (data?.list || []).filter((i) => !i.invalid && i.selected)
    if (!selected.length) {
      showToast('请先在购物车勾选商品')
      router.replace('/cart')
      return
    }
    items.value = selected.map((i) => ({
      product_id: i.product_id,
      sku_id: i.sku_id || undefined,
      quantity: Number(i.quantity),
      name: i.name,
      image: i.cover_image,
      specs: i.sku_specs ? skuSpecsText(i.sku_specs) : '',
      price: Number(i.real_price)
    }))
  } else {
    const productId = Number(route.query.product_id)
    const skuId = Number(route.query.sku_id || 0)
    const quantity = Math.max(1, Number(route.query.quantity || 1))
    if (!productId) {
      showToast('参数错误')
      router.replace('/')
      return
    }
    const product = await getProductDetail(productId)
    let price = Number(product.price)
    let specs = ''
    let image = product.cover_image
    if (skuId && product.skus?.length) {
      const sku = product.skus.find((s) => s.id === skuId)
      if (sku) {
        price = Number(sku.price)
        specs = skuSpecsText(sku.specs)
      }
    }
    items.value = [
      {
        product_id: productId,
        sku_id: skuId || undefined,
        quantity,
        name: product.name,
        image,
        specs,
        price
      }
    ]
  }
}

async function loadAddresses() {
  addresses.value = await getAddresses()
  address.value =
    addresses.value.find((a) => a.is_default) || addresses.value[0] || null
}

function chooseAddress(addr) {
  address.value = addr
  showAddressPicker.value = false
}

function goAddAddress() {
  showAddressPicker.value = false
  router.push('/address/edit')
}

async function onSubmit() {
  if (!address.value) {
    showToast('请先选择收货地址')
    showAddressPicker.value = true
    return
  }
  if (!items.value.length) {
    showToast('没有可下单的商品')
    return
  }
  submitting.value = true
  try {
    const order = await createOrder({
      address_id: address.value.id,
      items: items.value.map((i) => ({
        product_id: i.product_id,
        sku_id: i.sku_id || undefined,
        quantity: i.quantity
      })),
      remark: remark.value,
      use_balance: useBalance.value && userStore.balance > 0 ? 1 : 0,
      from_cart: fromCart.value ? 1 : 0
    })
    userStore.fetchCartCount().catch(() => {})
    if (Number(order.pay_amount) > 0) {
      router.replace('/pay/' + order.id)
    } else {
      showToast('支付成功，余额已抵扣')
      router.replace('/order/detail/' + order.id)
    }
  } catch (e) {
    /* 已统一 toast */
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  try {
    await Promise.all([loadItems(), loadAddresses()])
    if (userStore.token) {
      await userStore.fetchProfile()
    }
  } catch (e) {
    /* 已统一 toast */
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.page-loading {
  padding: 80px 0;
}
.addr-card {
  position: relative;
  padding: 14px 12px;
}
.addr-top {
  display: flex;
  align-items: center;
}
.addr-name {
  font-size: 15px;
  font-weight: 600;
}
.addr-phone {
  margin-left: 10px;
  color: #7a7d86;
  font-size: 13px;
}
.addr-default {
  margin-left: 8px;
  background: #ffe9ec;
  color: #e63946;
  font-size: 10px;
  padding: 0 5px;
  border-radius: 3px;
  line-height: 16px;
}
.addr-detail {
  margin-top: 6px;
  color: #44464d;
  font-size: 13px;
  line-height: 18px;
  padding-right: 20px;
}
.addr-arrow {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #c8c9ce;
}
.addr-empty {
  display: flex;
  align-items: center;
  color: #7a7d86;
  font-size: 14px;
}
.addr-empty .van-icon {
  font-size: 18px;
  margin-right: 6px;
}

.co-item {
  display: flex;
  padding: 10px 12px;
}
.co-img {
  width: 72px;
  height: 72px;
  border-radius: 8px;
  object-fit: cover;
  background: #f2f3f5;
  flex-shrink: 0;
}
.co-info {
  flex: 1;
  width: 0;
  margin-left: 10px;
}
.co-name {
  font-size: 13px;
  line-height: 18px;
}
.co-spec {
  margin-top: 3px;
  font-size: 11px;
  color: #9a9da5;
}
.co-line {
  margin-top: 6px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.co-qty {
  color: #9a9da5;
  font-size: 12px;
}
.co-remark {
  padding: 4px 0 8px;
}

.co-balance-label {
  font-size: 14px;
}
.co-balance-val {
  margin-left: 8px;
  font-size: 11px;
  color: #9a9da5;
}
.co-amounts {
  padding: 4px 12px 12px;
}
.co-amount-row {
  display: flex;
  justify-content: space-between;
  padding: 5px 0;
  font-size: 13px;
  color: #44464d;
}
.co-deduct {
  color: #2a9d5c;
}
.co-amount-total {
  border-top: 1px dashed #eee;
  margin-top: 4px;
  padding-top: 10px;
  font-weight: 600;
}
.co-amount-total .price {
  font-size: 18px;
}
.co-tip {
  text-align: center;
  color: #d48806;
  font-size: 11px;
  padding: 10px 0 16px;
}
.co-footer-total {
  flex: 1;
  font-size: 14px;
}
.co-footer-total .price {
  font-size: 20px;
}
.co-submit {
  min-width: 130px;
  font-weight: 700;
}

.addr-popup {
  padding-bottom: 12px;
  max-height: 70vh;
  overflow-y: auto;
}
.addr-popup-title {
  text-align: center;
  font-size: 15px;
  font-weight: 600;
  padding: 14px 0 8px;
}
.addr-popup-list {
  max-height: 46vh;
  overflow-y: auto;
  padding: 0 12px;
}
.addr-popup-item {
  padding: 10px;
  border-radius: 8px;
  border: 1px solid #eee;
  margin-bottom: 8px;
}
.addr-popup-item.active {
  border-color: #e63946;
  background: #fff7f8;
}
</style>

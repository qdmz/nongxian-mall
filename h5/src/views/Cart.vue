<template>
  <div class="cart-page page-with-footerbar">
    <van-nav-bar title="购物车" left-arrow class="tc-nav" @click-left="$router.back()">
      <template #right>
        <span v-if="invalidItems.length" class="nav-clear" @click="onClearInvalid">清失效</span>
        <van-icon name="home-o" size="18" @click="$router.push('/')" />
      </template>
    </van-nav-bar>

    <van-pull-refresh v-model="refreshing" @refresh="loadCart">
      <van-loading v-if="loading && !loaded" class="page-loading" size="28" vertical>加载中...</van-loading>

      <template v-if="loaded">
        <!-- 失效商品 -->
        <div class="invalid-block" v-if="invalidItems.length">
          <div class="invalid-title">失效商品</div>
          <div class="cart-item invalid" v-for="item in invalidItems" :key="item.id">
            <img class="cart-img" :src="item.cover_image" alt="" />
            <div class="cart-info">
              <div class="cart-name ellipsis-2">{{ item.name }}</div>
              <div class="cart-spec" v-if="item.sku_specs">{{ skuText(item.sku_specs) }}</div>
              <div class="cart-invalid-reason">已下架或库存不足</div>
              <div class="cart-line">
                <span class="price">¥{{ Number(item.real_price).toFixed(2) }}</span>
                <van-stepper :model-value="item.quantity" disabled />
              </div>
            </div>
            <van-icon name="delete-o" class="cart-delete" @click="onDelete(item)" />
          </div>
        </div>

        <!-- 有效商品 -->
        <div class="cart-list" v-if="validItems.length">
          <van-swipe-cell v-for="item in validItems" :key="item.id">
            <div class="cart-item">
              <van-checkbox
                :model-value="!!item.selected"
                @update:model-value="(v) => toggleSelect(item, v)"
              />
              <img class="cart-img" :src="item.cover_image" alt="" @click="goProduct(item.product_id)" />
              <div class="cart-info">
                <div class="cart-name ellipsis-2" @click="goProduct(item.product_id)">{{ item.name }}</div>
                <div class="cart-spec" v-if="item.sku_specs">{{ skuText(item.sku_specs) }}</div>
                <div class="cart-line">
                  <span class="price">¥{{ Number(item.real_price).toFixed(2) }}</span>
                  <van-stepper
                    :model-value="item.quantity"
                    :max="Math.max(1, item.real_stock)"
                    integer
                    async-change
                    @change="(v) => onQuantityChange(item, v)"
                  />
                </div>
              </div>
            </div>
            <template #right>
              <van-button square type="danger" text="删除" class="cart-delete-btn" @click="onDelete(item)" />
            </template>
          </van-swipe-cell>
        </div>

        <div class="empty-wrap" v-if="!validItems.length && !invalidItems.length">
          <van-icon name="cart-o" />
          <p>购物车还是空的</p>
          <van-button round class="tc-btn" size="small" style="margin-top: 12px" @click="$router.push('/')">
            去逛逛
          </van-button>
        </div>
      </template>
    </van-pull-refresh>

    <!-- 底部结算栏 -->
    <div class="footer-bar" v-if="validItems.length">
      <van-checkbox :model-value="allSelected" @update:model-value="toggleSelectAll">全选</van-checkbox>
      <div class="cart-total">
        合计 <span class="price">¥{{ totalAmount.toFixed(2) }}</span>
        <span class="cart-count">共{{ selectedCount }}件</span>
      </div>
      <van-button round class="tc-btn cart-submit" :disabled="!selectedCount" @click="goCheckout">
        去结算({{ selectedCount }})
      </van-button>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { showConfirmDialog, showToast } from 'vant'
import { getCart, updateCart, deleteCart, clearInvalid } from '../api/cart'
import { useUserStore } from '../store/user'
import { skuSpecsText } from '../utils'

const router = useRouter()
const userStore = useUserStore()

const list = ref([])
const loading = ref(false)
const loaded = ref(false)
const refreshing = ref(false)

const validItems = computed(() => (list.value || []).filter((i) => !i.invalid))
const invalidItems = computed(() => (list.value || []).filter((i) => !!i.invalid))

const selectedItems = computed(() => validItems.value.filter((i) => i.selected))
const selectedCount = computed(() => selectedItems.value.reduce((s, i) => s + Number(i.quantity), 0))
const totalAmount = computed(() =>
  selectedItems.value.reduce((s, i) => s + Number(i.real_price) * Number(i.quantity), 0)
)
const allSelected = computed(
  () => validItems.value.length > 0 && validItems.value.every((i) => i.selected)
)

function skuText(specs) {
  return skuSpecsText(specs)
}

async function loadCart() {
  loading.value = true
  try {
    const data = await getCart()
    list.value = data?.list || []
    let count = 0
    list.value.forEach((i) => {
      count += Number(i.quantity || 0)
    })
    userStore.cartCount = count
    loaded.value = true
  } catch (e) {
    /* 已统一 toast */
  } finally {
    loading.value = false
    refreshing.value = false
  }
}

async function toggleSelect(item, checked) {
  try {
    await updateCart({ id: item.id, selected: checked ? 1 : 0 })
    item.selected = checked ? 1 : 0
  } catch (e) {
    /* 已统一 toast */
  }
}

async function toggleSelectAll(checked) {
  try {
    await Promise.all(
      validItems.value.map((item) => updateCart({ id: item.id, selected: checked ? 1 : 0 }))
    )
    validItems.value.forEach((item) => {
      item.selected = checked ? 1 : 0
    })
  } catch (e) {
    /* 已统一 toast */
  }
}

async function onQuantityChange(item, value) {
  try {
    await updateCart({ id: item.id, quantity: value })
    item.quantity = value
    userStore.cartCount = list.value.reduce((s, i) => s + Number(i.quantity || 0), 0)
  } catch (e) {
    /* 已统一 toast；失败时刷新列表恢复 */
    loadCart()
  }
}

function onDelete(item) {
  showConfirmDialog({ title: '提示', message: '确定删除该商品吗？' })
    .then(async () => {
      await deleteCart(item.id)
      showToast('已删除')
      loadCart()
    })
    .catch(() => {})
}

function onClearInvalid() {
  showConfirmDialog({ title: '提示', message: '确定清空失效商品吗？' })
    .then(async () => {
      await clearInvalid()
      showToast('已清空')
      loadCart()
    })
    .catch(() => {})
}

function goCheckout() {
  if (!selectedCount.value) {
    showToast('请先勾选要结算的商品')
    return
  }
  router.push('/confirm-order?from=cart')
}

function goProduct(id) {
  router.push('/product/' + id)
}

onMounted(loadCart)
</script>

<style scoped>
.page-loading {
  padding: 80px 0;
}
.nav-clear {
  font-size: 13px;
}
.invalid-block {
  margin: 10px;
  background: #fff;
  border-radius: 10px;
  overflow: hidden;
}
.invalid-title {
  padding: 10px 12px 4px;
  font-size: 12px;
  color: #9a9da5;
}
.cart-list {
  margin: 10px;
  border-radius: 10px;
  overflow: hidden;
}
.cart-item {
  display: flex;
  align-items: center;
  padding: 10px 12px;
  background: #fff;
}
.cart-item.invalid {
  opacity: 0.6;
}
.cart-img {
  width: 78px;
  height: 78px;
  border-radius: 8px;
  object-fit: cover;
  margin-left: 10px;
  background: #f2f3f5;
  flex-shrink: 0;
}
.cart-info {
  flex: 1;
  width: 0;
  margin-left: 10px;
}
.cart-name {
  font-size: 13px;
  line-height: 18px;
}
.cart-spec {
  margin-top: 3px;
  font-size: 11px;
  color: #9a9da5;
  background: #f5f5f7;
  display: inline-block;
  padding: 1px 6px;
  border-radius: 3px;
}
.cart-invalid-reason {
  margin-top: 4px;
  font-size: 11px;
  color: #b0b3ba;
}
.cart-line {
  margin-top: 6px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.cart-delete {
  margin-left: 8px;
  font-size: 18px;
  color: #b0b3ba;
}
.cart-delete-btn {
  height: 100%;
}
.cart-total {
  flex: 1;
  margin-left: 12px;
  font-size: 13px;
}
.cart-total .price {
  font-size: 17px;
}
.cart-count {
  color: #9a9da5;
  font-size: 11px;
  margin-left: 6px;
}
.cart-submit {
  min-width: 110px;
  font-weight: 600;
}
</style>

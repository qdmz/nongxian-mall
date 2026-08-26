<template>
  <div class="pd page-with-footerbar">
    <van-nav-bar title="商品详情" left-arrow class="tc-nav" @click-left="$router.back()" fixed placeholder>
      <template #right>
        <van-icon name="share-o" size="18" @click="goShare" />
      </template>
    </van-nav-bar>

    <van-loading v-if="loading && !product" class="page-loading" size="28" vertical>加载中...</van-loading>

    <template v-if="product">
      <!-- 轮播图 -->
      <van-swipe class="pd-swipe" :autoplay="4000" indicator-color="#fff">
        <van-swipe-item v-for="(img, i) in images" :key="i">
          <img class="pd-img" :src="img" :alt="product.name" />
        </van-swipe-item>
      </van-swipe>

      <!-- 价格与基本信息 -->
      <div class="pd-main section">
        <div class="pd-price-row">
          <div class="pd-price">
            <span class="price-symbol">¥</span>
            <span class="pd-price-num">{{ currentPrice }}</span>
            <span v-if="product.unit" class="pd-unit">/{{ product.unit }}</span>
          </div>
          <div class="pd-sales">已售{{ displaySales }}件</div>
        </div>
        <div class="pd-old" v-if="product.original_price && Number(product.original_price) > Number(product.price)">
          门市价 <span>¥{{ Number(product.original_price).toFixed(2) }}</span>
        </div>
        <div class="pd-name">{{ product.name }}</div>
        <div class="pd-subtitle" v-if="product.subtitle">{{ product.subtitle }}</div>
        <div class="pd-flags">
          <span class="red-tag" v-if="product.is_red">🚩 红色助农</span>
          <span class="gold-tag" v-if="product.is_recommend">党员推荐</span>
          <span class="green-tag" v-if="product.is_new">新品</span>
          <span class="green-tag" v-if="product.is_hot">热销</span>
        </div>
      </div>

      <!-- 助农信息 -->
      <div class="section pd-farm" v-if="product.origin || product.farmer">
        <div class="pd-farm-title">🚩 助农档案</div>
        <div class="pd-farm-row" v-if="product.origin">
          <span class="label">产地</span>
          <span>{{ product.origin }}</span>
        </div>
        <div class="pd-farm-row" v-if="product.farmer">
          <span class="label">农户/合作社</span>
          <span>{{ product.farmer }}</span>
        </div>
        <div class="pd-farm-tip">每一笔订单都是对乡村振兴的支持</div>
      </div>

      <!-- 拼团活动条 -->
      <div class="pd-group-bar" v-if="activity" @click="goGroupBuy">
        <div class="pd-group-left">
          <div class="pd-group-price">
            <span>拼团价</span>
            <em>¥{{ Number(activity.group_price).toFixed(2) }}</em>
          </div>
          <div class="pd-group-info">{{ activity.required_count }}人成团 · {{ groupBuyingCount }}个团进行中</div>
        </div>
        <van-button size="small" round class="tc-btn">发起拼团 &gt;</van-button>
      </div>

      <!-- 规格选择入口 -->
      <van-cell
        class="pd-spec-cell"
        title="已选"
        :value="selectedText + ' × ' + quantity"
        is-link
        @click="openSku('cart')"
      />

      <!-- 商品详情 -->
      <div class="section">
        <div class="section-head"><div class="section-title">商品详情</div></div>
        <div class="rich-content" v-html="descriptionHtml"></div>
      </div>

      <!-- 相关推荐 -->
      <div class="section" v-if="product.related && product.related.length">
        <div class="section-head"><div class="section-title">同类推荐</div></div>
        <div class="pd-related">
          <div class="pd-related-item" v-for="p in product.related" :key="p.id" @click="goRelated(p.id)">
            <img :src="p.cover_image" :alt="p.name" />
            <div class="ellipsis pd-related-name">{{ p.name }}</div>
            <div class="pd-related-price">¥{{ Number(p.price).toFixed(2) }}</div>
          </div>
        </div>
      </div>

      <!-- 底部操作栏 -->
      <div class="footer-bar">
        <div class="pd-action-icons">
          <div class="pd-action-icon" @click="$router.push('/')">
            <van-icon name="wap-home-o" />
            <span>首页</span>
          </div>
          <div class="pd-action-icon" @click="$router.push('/cart')">
            <van-icon name="cart-o" />
            <span>购物车</span>
            <em v-if="userStore.cartCount" class="pd-cart-badge">{{ userStore.cartCount }}</em>
          </div>
        </div>
        <van-button round class="pd-btn-add" @click="openSku('cart')">加入购物车</van-button>
        <van-button round class="tc-btn pd-btn-buy" @click="openSku('buy')">立即购买</van-button>
      </div>
    </template>

    <div v-if="!loading && !product" class="empty-wrap">
      <van-icon name="warning-o" />
      <p>商品不存在或已下架</p>
    </div>

    <!-- SKU 选择弹层 -->
    <van-popup v-model:show="skuVisible" position="bottom" round class="sku-popup">
      <div class="sku-body">
        <div class="sku-head">
          <img class="sku-img" :src="images[0]" alt="" />
          <div class="sku-head-info">
            <div class="sku-price">¥{{ currentPrice }}</div>
            <div class="sku-stock">库存 {{ currentStock }} {{ product?.unit || '件' }}</div>
          </div>
          <van-icon name="close" class="sku-close" @click="skuVisible = false" />
        </div>

        <div class="sku-row" v-if="skuList.length">
          <div class="sku-label">规格</div>
          <div class="sku-options">
            <span
              class="sku-option"
              :class="{ active: selectedSkuId === sku.id, disabled: Number(sku.stock) <= 0 }"
              v-for="sku in skuList"
              :key="sku.id"
              @click="selectSku(sku)"
            >{{ skuLabel(sku) }}</span>
          </div>
        </div>

        <div class="sku-row">
          <div class="sku-label">数量</div>
          <van-stepper v-model="quantity" :max="Math.max(1, currentStock)" integer />
        </div>

        <van-button round block class="tc-btn sku-submit" @click="submitSku">
          {{ skuMode === 'cart' ? '加入购物车' : '立即购买' }}
        </van-button>
      </div>
    </van-popup>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { showToast } from 'vant'
import { getProductDetail } from '../api/product'
import { addCart } from '../api/cart'
import { useUserStore } from '../store/user'
import { skuSpecsText } from '../utils'

const route = useRoute()
const router = useRouter()
const userStore = useUserStore()

const product = ref(null)
const loading = ref(false)

const skuVisible = ref(false)
const skuMode = ref('cart')
const selectedSkuId = ref(0)
const quantity = ref(1)

const images = computed(() => {
  const arr = product.value?.images_arr?.filter(Boolean) || []
  if (arr.length) return arr
  return product.value?.cover_image ? [product.value.cover_image] : []
})

const activity = computed(() => product.value?.group_buy_activity || null)
const groupBuyingCount = computed(() => product.value?.group_buying_count || 0)
const displaySales = computed(() => Number(product.value?.display_sales ?? product.value?.sales ?? 0))

const skuList = computed(() => product.value?.skus || [])

const currentPrice = computed(() => {
  const p = product.value
  if (!p) return '0.00'
  if (skuList.value.length && selectedSkuId.value) {
    const sku = skuList.value.find((s) => s.id === selectedSkuId.value)
    if (sku) return Number(sku.price).toFixed(2)
  }
  return Number(p.price || 0).toFixed(2)
})

const currentStock = computed(() => {
  const p = product.value
  if (!p) return 0
  if (skuList.value.length && selectedSkuId.value) {
    const sku = skuList.value.find((s) => s.id === selectedSkuId.value)
    if (sku) return Number(sku.stock)
  }
  return Number(p.stock || 0)
})

const selectedText = computed(() => {
  if (!skuList.value.length) return '默认'
  const sku = skuList.value.find((s) => s.id === selectedSkuId.value)
  return sku ? skuLabel(sku) : '请选择'
})

const descriptionHtml = computed(() => {
  const desc = product.value?.description
  if (!desc) return '<p style="color:#999;text-align:center;padding:20px 0;">暂无详细介绍</p>'
  return desc
})

function skuLabel(sku) {
  return skuSpecsText(sku.specs) || '默认规格'
}

async function loadProduct() {
  loading.value = true
  try {
    product.value = await getProductDetail(route.params.id)
    const skus = product.value.skus || []
    if (skus.length) {
      const first = skus.find((s) => Number(s.stock) > 0) || skus[0]
      selectedSkuId.value = first.id
    }
  } catch (e) {
    product.value = null
  } finally {
    loading.value = false
  }
}

function selectSku(sku) {
  if (Number(sku.stock) <= 0) {
    showToast('该规格已售罄')
    return
  }
  selectedSkuId.value = sku.id
  if (quantity.value > Number(sku.stock)) quantity.value = Number(sku.stock)
}

function openSku(mode) {
  if (Number(product.value?.stock || 0) <= 0 && !skuList.value.length) {
    showToast('商品已售罄')
    return
  }
  skuMode.value = mode
  skuVisible.value = true
}

async function submitSku() {
  const stock = currentStock.value
  if (stock <= 0) {
    showToast('库存不足')
    return
  }
  if (skuList.value.length && !selectedSkuId.value) {
    showToast('请选择规格')
    return
  }
  const skuId = skuList.value.length ? selectedSkuId.value : 0

  if (skuMode.value === 'cart') {
    try {
      await addCart({
        product_id: product.value.id,
        sku_id: skuId || undefined,
        quantity: quantity.value
      })
      showToast('已加入购物车')
      skuVisible.value = false
      userStore.fetchCartCount().catch(() => {})
    } catch (e) {
      /* 错误已统一 toast */
    }
  } else {
    skuVisible.value = false
    router.push({
      path: '/confirm-order',
      query: {
        product_id: product.value.id,
        sku_id: skuId || undefined,
        quantity: quantity.value
      }
    })
  }
}

function goGroupBuy() {
  if (activity.value) {
    router.push('/group-buy/' + activity.value.id)
  }
}

function goShare() {
  router.push('/share/product/' + route.params.id)
}

function goRelated(id) {
  router.push('/product/' + id)
}

onMounted(loadProduct)
</script>

<style scoped>
.page-loading {
  padding: 80px 0;
}
.pd-swipe {
  background: #fff;
}
.pd-img {
  width: 100%;
  height: 375px;
  object-fit: cover;
  background: #f2f3f5;
}
.pd-main {
  padding-bottom: 12px;
}
.pd-price-row {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  padding: 12px 12px 0;
}
.pd-price {
  color: #e63946;
  font-weight: 800;
}
.pd-price-num {
  font-size: 26px;
}
.pd-unit {
  color: #9a9da5;
  font-size: 12px;
  font-weight: 400;
}
.pd-sales {
  color: #9a9da5;
  font-size: 12px;
}
.pd-old {
  padding: 2px 12px 0;
  color: #b0b3ba;
  font-size: 12px;
}
.pd-old span {
  text-decoration: line-through;
}
.pd-name {
  padding: 8px 12px 0;
  font-size: 16px;
  font-weight: 600;
  line-height: 22px;
}
.pd-subtitle {
  padding: 4px 12px 0;
  color: #7a7d86;
  font-size: 12px;
}
.pd-flags {
  padding: 8px 12px 0;
}

.pd-farm {
  padding: 12px;
  background: linear-gradient(135deg, #fff7f2, #fffbf5);
}
.pd-farm-title {
  font-weight: 700;
  font-size: 14px;
  color: #c1121f;
  margin-bottom: 8px;
}
.pd-farm-row {
  display: flex;
  font-size: 13px;
  line-height: 24px;
}
.pd-farm-row .label {
  color: #7a7d86;
  width: 90px;
  flex-shrink: 0;
}
.pd-farm-tip {
  margin-top: 8px;
  font-size: 11px;
  color: #d48806;
}

.pd-group-bar {
  margin: 10px;
  padding: 10px 12px;
  border-radius: 10px;
  background: linear-gradient(135deg, #e63946, #ff7b54);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.pd-group-price span {
  font-size: 12px;
  opacity: 0.9;
}
.pd-group-price em {
  font-style: normal;
  font-size: 20px;
  font-weight: 800;
  margin-left: 4px;
}
.pd-group-info {
  font-size: 11px;
  opacity: 0.9;
  margin-top: 2px;
}
.pd-group-bar .van-button {
  background: #fff;
  color: #e63946;
  font-weight: 700;
  border: none;
}

.pd-spec-cell {
  margin: 0 10px 10px;
  border-radius: 10px;
}

.pd-related {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
  padding: 0 12px 12px;
}
.pd-related-item img {
  width: 100%;
  aspect-ratio: 1 / 1;
  object-fit: cover;
  border-radius: 8px;
  background: #f2f3f5;
}
.pd-related-name {
  font-size: 11px;
  color: #44464d;
  margin-top: 4px;
}
.pd-related-price {
  color: #e63946;
  font-weight: 700;
  font-size: 13px;
  margin-top: 2px;
}

.pd-action-icons {
  display: flex;
}
.pd-action-icon {
  position: relative;
  width: 44px;
  text-align: center;
  color: #44464d;
  font-size: 10px;
}
.pd-action-icon .van-icon {
  font-size: 20px;
}
.pd-action-icon + .pd-action-icon {
  margin-left: 8px;
}
.pd-cart-badge {
  position: absolute;
  top: -4px;
  right: 2px;
  min-width: 14px;
  height: 14px;
  line-height: 14px;
  padding: 0 3px;
  border-radius: 7px;
  background: #e63946;
  color: #fff;
  font-size: 9px;
  font-style: normal;
}
.pd-btn-add {
  flex: 1;
  margin-left: 12px;
  border: 1px solid #e63946;
  color: #e63946;
  font-weight: 600;
}
.pd-btn-buy {
  flex: 1;
  margin-left: 10px;
  font-weight: 600;
}

/* SKU 弹层 */
.sku-popup {
  padding-bottom: env(safe-area-inset-bottom);
}
.sku-body {
  padding: 16px;
}
.sku-head {
  display: flex;
  align-items: center;
  position: relative;
}
.sku-img {
  width: 90px;
  height: 90px;
  border-radius: 8px;
  object-fit: cover;
  background: #f2f3f5;
}
.sku-head-info {
  margin-left: 12px;
}
.sku-price {
  color: #e63946;
  font-size: 20px;
  font-weight: 800;
}
.sku-stock {
  color: #9a9da5;
  font-size: 12px;
  margin-top: 6px;
}
.sku-close {
  position: absolute;
  right: 0;
  top: 0;
  font-size: 20px;
  color: #c8c9ce;
}
.sku-row {
  display: flex;
  align-items: flex-start;
  margin-top: 18px;
}
.sku-label {
  width: 50px;
  flex-shrink: 0;
  color: #7a7d86;
  font-size: 13px;
  line-height: 28px;
}
.sku-options {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  flex: 1;
}
.sku-option {
  padding: 4px 14px;
  border-radius: 15px;
  background: #f5f5f7;
  font-size: 13px;
  color: #44464d;
}
.sku-option.active {
  background: #ffe9ec;
  color: #e63946;
  font-weight: 600;
  border: 1px solid #e63946;
}
.sku-option.disabled {
  color: #c8c9ce;
  background: #f7f7f8;
}
.sku-submit {
  margin-top: 24px;
  font-weight: 600;
}
</style>

<template>
  <div class="product-card" @click="goDetail">
    <div class="pic">
      <img :src="product.cover_image || placeholder" :alt="product.name" loading="lazy" />
      <span v-if="product.is_red" class="flag-red">🚩 助农</span>
    </div>
    <div class="info">
      <div class="name ellipsis-2">
        <span v-if="product.is_recommend" class="tag tag-recommend">党员推荐</span>
        <span v-if="product.is_new" class="tag tag-new">新品</span>
        {{ product.name }}
      </div>
      <div class="meta">
        <span v-if="product.origin" class="origin ellipsis">产地：{{ product.origin }}</span>
      </div>
      <div class="bottom">
        <div class="price">
          <span class="price-symbol">¥</span><span class="price-num">{{ priceText }}</span>
          <span v-if="product.unit" class="unit">/{{ product.unit }}</span>
        </div>
        <div class="sales">已售{{ salesText }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'

const props = defineProps({
  product: { type: Object, required: true }
})

const router = useRouter()
const placeholder =
  'data:image/svg+xml,' +
  encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200"><rect width="200" height="200" fill="#f2f3f5"/><text x="100" y="105" font-size="16" fill="#c8c9ce" text-anchor="middle">暂无图片</text></svg>'
  )

const priceText = computed(() => Number(props.product.price || 0).toFixed(2))
const salesText = computed(() => {
  const sales = Number(props.product.display_sales ?? props.product.sales ?? 0)
  if (sales >= 10000) return (sales / 10000).toFixed(1) + '万'
  return String(sales)
})

function goDetail() {
  router.push('/product/' + props.product.id)
}
</script>

<style scoped>
.product-card {
  background: #fff;
  border-radius: 10px;
  overflow: hidden;
}
.pic {
  position: relative;
  aspect-ratio: 1 / 1;
  background: #f2f3f5;
}
.pic img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.flag-red {
  position: absolute;
  top: 6px;
  left: 6px;
  padding: 1px 6px;
  border-radius: 3px;
  background: rgba(230, 57, 70, 0.92);
  color: #fff;
  font-size: 10px;
}
.info {
  padding: 8px;
}
.name {
  font-size: 13px;
  line-height: 18px;
  min-height: 36px;
  color: #22242a;
}
.tag {
  display: inline-block;
  padding: 0 4px;
  margin-right: 2px;
  border-radius: 3px;
  font-size: 10px;
  line-height: 15px;
  vertical-align: 1px;
}
.tag-recommend {
  background: #ffe9ec;
  color: #e63946;
}
.tag-new {
  background: #e8f6ee;
  color: #2a9d5c;
}
.meta {
  margin-top: 4px;
  color: #7a7d86;
  font-size: 11px;
}
.bottom {
  margin-top: 6px;
  display: flex;
  align-items: baseline;
  justify-content: space-between;
}
.price {
  color: #e63946;
  font-weight: 700;
}
.price-num {
  font-size: 17px;
}
.unit {
  color: #9a9da5;
  font-size: 11px;
  font-weight: 400;
}
.sales {
  color: #b0b3ba;
  font-size: 11px;
  flex-shrink: 0;
}
</style>

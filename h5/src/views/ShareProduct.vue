<template>
  <div class="share-product">
    <van-nav-bar title="商品分享" left-arrow class="tc-nav" @click-left="$router.back()" />

    <van-loading v-if="loading" class="page-loading" size="28" vertical>加载中...</van-loading>

    <template v-if="data">
      <!-- 分享海报卡片 -->
      <div class="poster" id="poster">
        <div class="poster-head">
          <div class="poster-brand">🚩 田冲助农商城</div>
          <div class="poster-slogan">党建引领 · 强村富民</div>
        </div>

        <img class="poster-img" :src="data.product.cover_image" :alt="data.product.name" />

        <div class="poster-body">
          <div class="poster-name">{{ data.product.name }}</div>
          <div class="poster-desc ellipsis-2" v-if="data.product.subtitle">{{ data.product.subtitle }}</div>
          <div class="poster-price-row">
            <div class="poster-price">
              <span class="poster-symbol">¥</span>{{ Number(data.product.price).toFixed(2) }}
              <span class="poster-unit" v-if="data.product.unit">/{{ data.product.unit }}</span>
            </div>
            <div class="poster-old" v-if="Number(data.product.original_price) > Number(data.product.price)">
              ¥{{ Number(data.product.original_price).toFixed(2) }}
            </div>
          </div>
        </div>

        <div class="poster-foot">
          <div class="poster-qrcode">
            <div class="qrcode-placeholder">
              <van-icon name="qr" />
              <span>二维码</span>
            </div>
          </div>
          <div class="poster-cta">
            <div class="poster-cta-title">扫码直达商品</div>
            <div class="poster-cta-sub">产地直发 · 新鲜到家</div>
            <div class="poster-cta-sub">长按/截图保存分享给好友</div>
          </div>
        </div>
      </div>

      <!-- 分享文案 -->
      <div class="section">
        <div class="section-head"><div class="section-title">分享文案</div></div>
        <div class="sp-copy-text">{{ data.share_title }}</div>
        <div class="sp-copy-text sub">{{ data.share_desc }}</div>
        <div class="sp-copy-text url ellipsis">{{ data.share_url }}</div>
      </div>

      <div class="footer-bar">
        <van-button round plain type="primary" class="sp-btn" @click="copyText_">复制文案</van-button>
        <van-button round class="tc-btn sp-btn" @click="copyLink">复制链接</van-button>
      </div>
    </template>

    <div v-if="!loading && !data" class="empty-wrap">
      <van-icon name="warning-o" />
      <p>分享信息加载失败</p>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { showToast } from 'vant'
import { getProductShare } from '../api/share'
import { copyText } from '../utils'

const route = useRoute()

const data = ref(null)
const loading = ref(false)

async function loadShare() {
  loading.value = true
  try {
    data.value = await getProductShare(route.params.id)
  } catch (e) {
    data.value = null
  } finally {
    loading.value = false
  }
}

async function copyText_() {
  if (!data.value) return
  const text = `${data.value.share_title}\n${data.value.share_desc}`
  const ok = await copyText(text)
  showToast(ok ? '文案已复制' : '复制失败')
}

async function copyLink() {
  if (!data.value) return
  const ok = await copyText(data.value.share_url)
  showToast(ok ? '链接已复制' : '复制失败')
}

onMounted(loadShare)
</script>

<style scoped>
.page-loading {
  padding: 80px 0;
}
.poster {
  margin: 12px;
  border-radius: 12px;
  background: #fff;
  overflow: hidden;
}
.poster-head {
  padding: 14px 16px;
  background: linear-gradient(135deg, #e63946, #c1121f);
  color: #fff;
}
.poster-brand {
  font-size: 15px;
  font-weight: 800;
}
.poster-slogan {
  margin-top: 2px;
  font-size: 11px;
  opacity: 0.9;
}
.poster-img {
  width: 100%;
  height: 300px;
  object-fit: cover;
  background: #f2f3f5;
}
.poster-body {
  padding: 12px 16px;
}
.poster-name {
  font-size: 16px;
  font-weight: 600;
  line-height: 22px;
}
.poster-desc {
  margin-top: 4px;
  font-size: 12px;
  color: #7a7d86;
}
.poster-price-row {
  margin-top: 10px;
  display: flex;
  align-items: baseline;
  gap: 8px;
}
.poster-price {
  color: #e63946;
  font-size: 26px;
  font-weight: 800;
}
.poster-symbol {
  font-size: 15px;
}
.poster-unit {
  font-size: 12px;
  font-weight: 400;
  color: #9a9da5;
}
.poster-old {
  color: #b0b3ba;
  font-size: 13px;
  text-decoration: line-through;
}
.poster-foot {
  display: flex;
  align-items: center;
  padding: 14px 16px;
  border-top: 1px dashed #eee;
}
.qrcode-placeholder {
  width: 92px;
  height: 92px;
  border-radius: 8px;
  border: 1px solid #eee;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #b0b3ba;
  gap: 4px;
}
.qrcode-placeholder .van-icon {
  font-size: 40px;
}
.qrcode-placeholder span {
  font-size: 10px;
}
.poster-cta {
  margin-left: 14px;
}
.poster-cta-title {
  font-size: 15px;
  font-weight: 700;
  color: #e63946;
}
.poster-cta-sub {
  margin-top: 4px;
  font-size: 11px;
  color: #9a9da5;
}
.sp-copy-text {
  padding: 4px 12px;
  font-size: 13px;
  color: #22242a;
  word-break: break-all;
}
.sp-copy-text.sub {
  color: #7a7d86;
}
.sp-copy-text.url {
  color: #1677ff;
  font-size: 11px;
  padding-bottom: 12px;
}
.sp-btn {
  flex: 1;
}
.sp-btn + .sp-btn {
  margin-left: 10px;
}
</style>

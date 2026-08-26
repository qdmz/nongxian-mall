<template>
  <div class="home page-with-tabbar">
    <!-- 顶部红金头部：标语 + 搜索框 -->
    <div class="home-header">
      <div class="slogan">
        <div class="slogan-main">党建引领 · 强村富民</div>
        <div class="slogan-sub">贵州亿田农业 · 田冲红色美丽乡村强村富民工坊</div>
      </div>
      <div class="search-box" @click="$router.push('/search')">
        <van-icon name="search" />
        <span class="search-ph">搜索新鲜农产品 / 助农好物</span>
      </div>
    </div>

    <van-pull-refresh v-model="refreshing" @refresh="onRefresh" success-text="刷新成功">
      <div v-if="loading && !home" class="loading-wrap">
        <van-loading size="28" vertical>加载中...</van-loading>
      </div>

      <template v-if="home">
        <!-- 轮播图 -->
        <van-swipe v-if="home.banners && home.banners.length" class="banner-swipe" :autoplay="4000" indicator-color="#fff">
          <van-swipe-item v-for="banner in home.banners" :key="banner.id" @click="onBanner(banner)">
            <img class="banner-img" :src="banner.image" :alt="banner.title" />
          </van-swipe-item>
        </van-swipe>

        <!-- 金刚区分类入口 -->
        <div class="kingkong section" v-if="home.categories && home.categories.length">
          <div class="kingkong-grid">
            <div class="kingkong-item" v-for="(cat, i) in home.categories" :key="cat.id" @click="goCategory(cat)">
              <div class="kingkong-icon" :style="{ background: kingkongColors[i % kingkongColors.length] }">
                <img v-if="cat.image" :src="cat.image" :alt="cat.name" />
                <span v-else>{{ kingkongEmojis[i % kingkongEmojis.length] }}</span>
              </div>
              <div class="kingkong-name ellipsis">{{ cat.name }}</div>
            </div>
          </div>
        </div>

        <!-- 党员推荐横幅 -->
        <div class="recommend-banner" v-if="home.recommend && home.recommend.length">
          <div class="rb-head">
            <span class="rb-title">🚩 党员推荐 · 甄选好物</span>
            <span class="rb-sub">品质放心 · 产地直发</span>
          </div>
          <div class="rb-scroll">
            <div class="rb-card" v-for="p in home.recommend" :key="p.id" @click="goProduct(p.id)">
              <img :src="p.cover_image" :alt="p.name" />
              <div class="rb-name ellipsis">{{ p.name }}</div>
              <div class="rb-price">¥{{ Number(p.price).toFixed(2) }}</div>
            </div>
          </div>
        </div>

        <!-- 拼团专区 -->
        <div class="section" v-if="home.group_buy && home.group_buy.length">
          <div class="section-head">
            <div class="section-title">拼团专区</div>
            <div class="section-more" @click="$router.push('/group-buy')">更多拼团 &gt;</div>
          </div>
          <div class="gb-scroll">
            <div class="gb-card" v-for="g in home.group_buy" :key="g.id" @click="$router.push('/group-buy/' + g.id)">
              <img class="gb-img" :src="g.cover_image" :alt="g.name" />
              <div class="gb-name ellipsis-2">{{ g.name }}</div>
              <div class="gb-row">
                <span class="gb-price">¥{{ Number(g.group_price).toFixed(2) }}</span>
                <span class="gb-count">{{ g.required_count }}人团</span>
              </div>
              <div class="gb-ongoing">{{ g.group_count || 0 }}个团进行中</div>
            </div>
          </div>
        </div>

        <!-- 热销 -->
        <div class="section" v-if="home.hot && home.hot.length">
          <div class="section-head">
            <div class="section-title">热销爆款</div>
            <div class="section-more" @click="goSearch">查看更多 &gt;</div>
          </div>
          <div class="grid2">
            <ProductCard v-for="p in home.hot" :key="p.id" :product="p" />
          </div>
        </div>

        <!-- 红色助农专区 -->
        <div class="red-zone" v-if="home.red && home.red.length">
          <div class="red-zone-head">
            <div class="red-zone-title">🚩 红色助农专区</div>
            <div class="red-zone-sub">消费帮扶 · 强村富民</div>
          </div>
          <div class="grid2 red-zone-grid">
            <ProductCard v-for="p in home.red" :key="p.id" :product="p" />
          </div>
        </div>

        <!-- 新品 -->
        <div class="section" v-if="home.new && home.new.length">
          <div class="section-head">
            <div class="section-title">新品上架</div>
          </div>
          <div class="grid2">
            <ProductCard v-for="p in home.new" :key="p.id" :product="p" />
          </div>
        </div>

        <div class="home-foot">— 田冲助农 · 乡村振兴 —</div>
      </template>

      <div v-if="!loading && !home" class="empty-wrap">
        <van-icon name="warning-o" />
        <p>首页加载失败，请下拉刷新重试</p>
      </div>
    </van-pull-refresh>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { getHome } from '../api/product'
import ProductCard from '../components/ProductCard.vue'

const router = useRouter()
const home = ref(null)
const loading = ref(false)
const refreshing = ref(false)

const kingkongEmojis = ['🌾', '🥬', '🍓', '🐔', '🥚', '🍯', '🍵', '🍄', '🌽', '🍠']
const kingkongColors = [
  '#ffe9ec', '#e8f6ee', '#fff5dd', '#eef3ff', '#f3ecff',
  '#ffeef0', '#e9f8ef', '#fff8e6', '#eaf4ff', '#f6efff'
]

async function loadHome() {
  loading.value = true
  try {
    home.value = await getHome()
  } catch (e) {
    /* 错误已统一 toast */
  } finally {
    loading.value = false
  }
}

function onRefresh() {
  loadHome().finally(() => {
    refreshing.value = false
  })
}

function onBanner(banner) {
  const value = banner.link_value
  if (!value) return
  switch (Number(banner.link_type)) {
    case 1:
      router.push('/product/' + value)
      break
    case 2:
      router.push({ path: '/category', query: { category_id: value } })
      break
    case 4:
      router.push('/group-buy/' + value)
      break
    case 3:
      window.location.href = value
      break
    default:
      break
  }
}

function goCategory(cat) {
  router.push({ path: '/category', query: { category_id: cat.id } })
}

function goProduct(id) {
  router.push('/product/' + id)
}

function goSearch() {
  router.push('/search?is_hot=1')
}

onMounted(loadHome)
</script>

<style scoped>
.home-header {
  padding: 14px 14px 44px;
  background: linear-gradient(160deg, #e63946 0%, #c1121f 55%, #a4161a 100%);
  color: #fff;
}
.slogan-main {
  font-size: 20px;
  font-weight: 800;
  letter-spacing: 2px;
  display: flex;
  align-items: center;
}
.slogan-sub {
  margin-top: 4px;
  font-size: 11px;
  color: rgba(255, 255, 255, 0.85);
}
.search-box {
  margin-top: 12px;
  height: 36px;
  border-radius: 18px;
  background: #fff;
  display: flex;
  align-items: center;
  padding: 0 14px;
  color: #b0b3ba;
  font-size: 13px;
}
.search-box .van-icon {
  margin-right: 6px;
  font-size: 16px;
}

.banner-swipe {
  margin: -32px 10px 0;
  border-radius: 10px;
  overflow: hidden;
}
.banner-img {
  width: 100%;
  height: 150px;
  object-fit: cover;
  background: #f2f3f5;
}

.kingkong {
  padding: 12px 6px;
}
.kingkong-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  row-gap: 12px;
}
.kingkong-item {
  text-align: center;
}
.kingkong-icon {
  width: 46px;
  height: 46px;
  margin: 0 auto;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  overflow: hidden;
}
.kingkong-icon img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.kingkong-name {
  margin-top: 6px;
  font-size: 12px;
  color: #44464d;
}

.recommend-banner {
  margin: 10px;
  padding: 12px;
  border-radius: 10px;
  background: linear-gradient(135deg, #e63946 0%, #d62839 60%, #ffb703 160%);
  color: #fff;
}
.rb-head {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
}
.rb-title {
  font-size: 16px;
  font-weight: 700;
}
.rb-sub {
  font-size: 11px;
  color: rgba(255, 255, 255, 0.85);
}
.rb-scroll {
  margin-top: 10px;
  display: flex;
  overflow-x: auto;
  scrollbar-width: none;
}
.rb-scroll::-webkit-scrollbar {
  display: none;
}
.rb-card {
  width: 88px;
  flex-shrink: 0;
  margin-right: 10px;
  background: #fff;
  border-radius: 8px;
  padding: 6px;
}
.rb-card img {
  width: 76px;
  height: 76px;
  border-radius: 6px;
  object-fit: cover;
  background: #f2f3f5;
}
.rb-name {
  margin-top: 5px;
  font-size: 11px;
  color: #22242a;
}
.rb-price {
  color: #e63946;
  font-weight: 700;
  font-size: 13px;
}

.gb-scroll {
  display: flex;
  overflow-x: auto;
  padding: 0 12px 12px;
  scrollbar-width: none;
}
.gb-scroll::-webkit-scrollbar {
  display: none;
}
.gb-card {
  width: 120px;
  flex-shrink: 0;
  margin-right: 10px;
}
.gb-img {
  width: 120px;
  height: 120px;
  border-radius: 8px;
  object-fit: cover;
  background: #f2f3f5;
}
.gb-name {
  margin-top: 6px;
  font-size: 12px;
  line-height: 16px;
  height: 32px;
  color: #22242a;
}
.gb-row {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  margin-top: 2px;
}
.gb-price {
  color: #e63946;
  font-weight: 700;
  font-size: 15px;
}
.gb-count {
  font-size: 10px;
  color: #fff;
  background: #2a9d5c;
  border-radius: 8px;
  padding: 0 6px;
}
.gb-ongoing {
  font-size: 10px;
  color: #7a7d86;
  margin-top: 2px;
}

.grid2 {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 8px;
  padding: 0 12px 12px;
}

.red-zone {
  margin: 10px;
  border-radius: 10px;
  background: linear-gradient(180deg, #e63946 0%, #fdeeee 120px, #fff 240px);
  padding-bottom: 2px;
}
.red-zone-head {
  padding: 14px 12px 10px;
  color: #fff;
  display: flex;
  align-items: baseline;
  justify-content: space-between;
}
.red-zone-title {
  font-size: 16px;
  font-weight: 800;
}
.red-zone-sub {
  font-size: 11px;
  color: rgba(255, 255, 255, 0.9);
}
.red-zone-grid {
  padding-bottom: 12px;
}

.home-foot {
  text-align: center;
  color: #b0b3ba;
  font-size: 12px;
  padding: 16px 0 20px;
}
.loading-wrap {
  padding: 60px 0;
  text-align: center;
  color: #7a7d86;
}
</style>

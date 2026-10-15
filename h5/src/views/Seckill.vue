<template>
  <div class="sk-list">
    <van-nav-bar title="限时秒杀" left-arrow class="tc-nav" @click-left="$router.back()" fixed placeholder>
      <template #right>
        <van-icon name="home-o" size="18" @click="$router.push('/')" />
      </template>
    </van-nav-bar>

    <div class="sk-banner">
      <div class="sk-banner-title">⚡ 限时秒杀 · 产地直供</div>
      <div class="sk-banner-sub">数量有限，抢完即止，每人限购</div>
    </div>

    <van-loading v-if="loading" class="page-loading" size="28" vertical>加载中...</van-loading>

    <div class="sk-card" v-for="item in list" :key="item.id" @click="goDetail(item)">
      <img class="sk-card-img" :src="item.cover_image" :alt="item.name" />
      <div class="sk-card-body">
        <div class="sk-card-name ellipsis-2">{{ item.title || item.name }}</div>
        <div class="sk-card-tags">
          <span class="sk-tag" v-if="Number(item.limit_per_user) > 0">每人限购{{ item.limit_per_user }}{{ item.unit || '件' }}</span>
          <span class="sk-tag orange">{{ item.discount }}折</span>
        </div>
        <div class="sk-progress">
          <van-progress :percentage="item.sold_percent" :show-pivot="false" stroke-width="6" color="#e63946" track-color="#ffe3e6" />
          <span>已抢{{ item.sold_percent }}%</span>
        </div>
        <div class="sk-card-bottom">
          <div class="sk-price-wrap">
            <span class="sk-price">¥{{ Number(item.seckill_price).toFixed(2) }}</span>
            <span class="sk-old">¥{{ Number(item.original_price).toFixed(2) }}</span>
          </div>
          <van-button size="small" round class="tc-btn" :disabled="item.state !== 'ongoing'">{{ stateText(item) }}</van-button>
        </div>
      </div>
    </div>

    <div class="empty-wrap" v-if="!loading && !list.length">
      <van-icon name="clock-o" />
      <p>暂无秒杀活动，敬请期待</p>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { getSeckillList } from '../api/seckill'

const router = useRouter()
const list = ref([])
const loading = ref(true)

function stateText(item) {
  return { ongoing: '马上抢', upcoming: '即将开始', sold_out: '已抢光', ended: '已结束' }[item.state] || '查看'
}

function goDetail(item) {
  router.push('/seckill/' + item.id)
}

onMounted(async () => {
  try {
    const data = await getSeckillList({ page: 1, page_size: 50 })
    list.value = data?.list || []
  } catch (e) {
    /* 已统一 toast */
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.page-loading {
  padding: 60px 0;
}
.sk-banner {
  margin: 10px;
  padding: 18px 16px;
  border-radius: 10px;
  background: linear-gradient(135deg, #e63946 0%, #ff7b54 100%);
  color: #fff;
}
.sk-banner-title {
  font-size: 18px;
  font-weight: 800;
}
.sk-banner-sub {
  margin-top: 4px;
  font-size: 12px;
  opacity: 0.9;
}
.sk-card {
  margin: 0 10px 10px;
  background: #fff;
  border-radius: 10px;
  display: flex;
  padding: 10px;
}
.sk-card-img {
  width: 110px;
  height: 110px;
  border-radius: 8px;
  object-fit: cover;
  background: #f2f3f5;
  flex-shrink: 0;
}
.sk-card-body {
  flex: 1;
  width: 0;
  margin-left: 10px;
  display: flex;
  flex-direction: column;
}
.sk-card-name {
  font-size: 14px;
  line-height: 19px;
  font-weight: 600;
}
.sk-card-tags {
  margin-top: 6px;
}
.sk-tag {
  font-size: 10px;
  color: #e63946;
  background: #ffe9ec;
  border-radius: 3px;
  padding: 1px 5px;
  margin-right: 6px;
}
.sk-tag.orange {
  color: #d46b08;
  background: #fff3e0;
}
.sk-progress {
  margin-top: 6px;
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 10px;
  color: #e63946;
}
.sk-progress .van-progress {
  flex: 1;
}
.sk-card-bottom {
  margin-top: auto;
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
}
.sk-price-wrap {
  display: flex;
  align-items: baseline;
  gap: 6px;
}
.sk-price {
  color: #e63946;
  font-size: 20px;
  font-weight: 800;
}
.sk-old {
  color: #b0b3ba;
  font-size: 11px;
  text-decoration: line-through;
}
</style>

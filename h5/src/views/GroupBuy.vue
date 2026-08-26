<template>
  <div class="gb-list">
    <van-nav-bar title="拼团专区" left-arrow class="tc-nav" @click-left="$router.back()" fixed placeholder />

    <div class="gb-banner">
      <div class="gb-banner-title">邻里拼团 · 产地好价</div>
      <div class="gb-banner-sub">和朋友一起拼，新鲜又实惠</div>
    </div>

    <van-list
      v-model:loading="loading"
      :finished="finished"
      finished-text="没有更多了"
      @load="loadList"
    >
      <div class="gb-card" v-for="item in list" :key="item.id" @click="goDetail(item)">
        <img class="gb-card-img" :src="item.cover_image" :alt="item.name" />
        <div class="gb-card-body">
          <div class="gb-card-name ellipsis-2">{{ item.title || item.name }}</div>
          <div class="gb-card-tags">
            <span class="gb-tag">{{ item.required_count }}人成团</span>
            <span class="gb-tag green" v-if="item.valid_hours">{{ item.valid_hours }}小时内成团</span>
          </div>
          <div class="gb-card-bottom">
            <div class="gb-price-wrap">
              <span class="gb-price">¥{{ Number(item.group_price).toFixed(2) }}</span>
              <span class="gb-old">单买 ¥{{ Number(item.original_price).toFixed(2) }}</span>
            </div>
            <div class="gb-right">
              <div class="gb-discount">{{ item.discount }}折</div>
              <van-button size="small" round class="tc-btn">去拼团</van-button>
            </div>
          </div>
          <div class="gb-ongoing">{{ item.group_count || 0 }} 个团正在进行中</div>
        </div>
      </div>

      <div class="empty-wrap" v-if="finished && !list.length && !loading">
        <van-icon name="friends-o" />
        <p>暂无进行中的拼团活动</p>
      </div>
    </van-list>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { getActivities } from '../api/groupBuy'

const router = useRouter()

const list = ref([])
const page = ref(1)
const loading = ref(false)
const finished = ref(false)

async function loadList() {
  if (finished.value) return
  try {
    const data = await getActivities({ page: page.value })
    const items = data?.list || []
    list.value.push(...items)
    if (list.value.length >= Number(data?.total || 0) || items.length === 0) {
      finished.value = true
    } else {
      page.value += 1
    }
    loading.value = false
  } catch (e) {
    loading.value = false
    finished.value = true
  }
}

function goDetail(item) {
  router.push('/group-buy/' + item.id)
}

onMounted(() => {
  loading.value = true
  loadList()
})
</script>

<style scoped>
.gb-banner {
  margin: 10px;
  padding: 18px 16px;
  border-radius: 10px;
  background: linear-gradient(135deg, #2a9d5c 0%, #1f7a46 100%);
  color: #fff;
}
.gb-banner-title {
  font-size: 18px;
  font-weight: 800;
}
.gb-banner-sub {
  margin-top: 4px;
  font-size: 12px;
  opacity: 0.9;
}
.gb-card {
  margin: 0 10px 10px;
  background: #fff;
  border-radius: 10px;
  display: flex;
  padding: 10px;
}
.gb-card-img {
  width: 110px;
  height: 110px;
  border-radius: 8px;
  object-fit: cover;
  background: #f2f3f5;
  flex-shrink: 0;
}
.gb-card-body {
  flex: 1;
  width: 0;
  margin-left: 10px;
  display: flex;
  flex-direction: column;
}
.gb-card-name {
  font-size: 14px;
  line-height: 19px;
  font-weight: 600;
}
.gb-card-tags {
  margin-top: 6px;
}
.gb-tag {
  font-size: 10px;
  color: #e63946;
  background: #ffe9ec;
  border-radius: 3px;
  padding: 1px 5px;
  margin-right: 6px;
}
.gb-tag.green {
  color: #2a9d5c;
  background: #e8f6ee;
}
.gb-card-bottom {
  margin-top: auto;
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
}
.gb-price-wrap {
  display: flex;
  align-items: baseline;
  gap: 6px;
}
.gb-price {
  color: #e63946;
  font-size: 20px;
  font-weight: 800;
}
.gb-old {
  color: #b0b3ba;
  font-size: 11px;
  text-decoration: line-through;
}
.gb-right {
  display: flex;
  align-items: center;
  gap: 8px;
}
.gb-discount {
  color: #fff;
  background: #e63946;
  border-radius: 4px;
  font-size: 11px;
  padding: 1px 5px;
  font-weight: 600;
}
.gb-ongoing {
  margin-top: 6px;
  font-size: 11px;
  color: #7a7d86;
}
</style>

<template>
  <div class="my-groups">
    <van-nav-bar title="我的拼团" left-arrow class="tc-nav" @click-left="$router.back()" fixed placeholder>
      <template #right>
        <van-icon name="home-o" size="18" @click="$router.push('/')" />
      </template>
    </van-nav-bar>

    <van-pull-refresh v-model="refreshing" @refresh="onRefresh">
      <van-list
        v-model:loading="loading"
        :finished="finished"
        finished-text="没有更多了"
        @load="loadList"
      >
        <div class="mg-card" v-for="g in list" :key="g.id" @click="goGroup(g)">
          <div class="mg-head">
            <van-tag :type="statusTagType(g.status)">{{ GROUP_STATUS_TEXT[g.status] || '未知' }}</van-tag>
            <span class="mg-role" v-if="g.is_leader">我是团长</span>
            <span class="mg-no">团号 {{ g.group_no }}</span>
          </div>
          <div class="mg-body">
            <img class="mg-img" :src="g.cover_image" alt="" />
            <div class="mg-info">
              <div class="mg-name ellipsis-2">{{ g.product_name }}</div>
              <div class="mg-meta">
                ¥{{ Number(g.group_price).toFixed(2) }} · {{ g.required_count }}人团 · 已加入{{ g.joined_count }}人
              </div>
              <div class="mg-cd" v-if="Number(g.status) === 0 && g.remaining_seconds > 0">
                <van-icon name="clock-o" />
                剩余 <CountDown :end="groupEnd(g)" @finish="onCountdownFinish(g)" />
              </div>
            </div>
          </div>
          <div class="mg-foot">
            <span class="mg-order">我的订单 {{ g.my_order_no }}</span>
            <div class="mg-actions" @click.stop>
              <van-button
                v-if="Number(g.status) === 0"
                size="small"
                round
                class="tc-btn"
                @click="goShareGroup(g)"
              >邀请好友</van-button>
              <van-button size="small" plain round @click="goOrder(g)">查看订单</van-button>
            </div>
          </div>
        </div>

        <div class="empty-wrap" v-if="finished && !list.length && !loading">
          <van-icon name="friends-o" />
          <p>还没有参与拼团，去拼团专区看看吧</p>
          <van-button round size="small" class="tc-btn" style="margin-top: 12px" @click="$router.push('/group-buy')">
            去拼团
          </van-button>
        </div>
      </van-list>
    </van-pull-refresh>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { showToast } from 'vant'
import { getMyGroups } from '../api/groupBuy'
import { GROUP_STATUS_TEXT } from '../utils'
import CountDown from '../components/CountDown.vue'

const router = useRouter()

const list = ref([])
const page = ref(1)
const loading = ref(false)
const finished = ref(false)
const refreshing = ref(false)

function statusTagType(status) {
  const s = Number(status)
  if (s === 1) return 'success'
  if (s === 0) return 'danger'
  return 'default'
}

function groupEnd(g) {
  return Math.floor(Date.now() / 1000) + Number(g.remaining_seconds || 0)
}

function onCountdownFinish(g) {
  g.status = 2
  showToast('有拼团超时未成团，已自动退款')
}

async function loadList() {
  if (finished.value) return
  try {
    const data = await getMyGroups({ page: page.value })
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

function onRefresh() {
  list.value = []
  page.value = 1
  finished.value = false
  loading.value = true
  loadList()
  refreshing.value = false
}

function goGroup(g) {
  router.push('/group-buy/group/' + g.id)
}

function goShareGroup(g) {
  router.push('/group-buy/group/' + g.id)
}

function goOrder(g) {
  if (g.my_order_no) {
    router.push('/order/list')
  }
}

onMounted(() => {
  loading.value = true
  loadList()
})
</script>

<style scoped>
.mg-card {
  margin: 10px;
  background: #fff;
  border-radius: 10px;
  padding: 12px;
}
.mg-head {
  display: flex;
  align-items: center;
  gap: 8px;
}
.mg-role {
  font-size: 11px;
  color: #d48806;
  background: #fff5dd;
  padding: 1px 6px;
  border-radius: 3px;
}
.mg-no {
  margin-left: auto;
  font-size: 11px;
  color: #b0b3ba;
}
.mg-body {
  display: flex;
  margin-top: 10px;
}
.mg-img {
  width: 76px;
  height: 76px;
  border-radius: 8px;
  object-fit: cover;
  background: #f2f3f5;
  flex-shrink: 0;
}
.mg-info {
  flex: 1;
  width: 0;
  margin-left: 10px;
}
.mg-name {
  font-size: 13px;
  line-height: 18px;
}
.mg-meta {
  margin-top: 4px;
  font-size: 12px;
  color: #e63946;
}
.mg-cd {
  margin-top: 6px;
  font-size: 12px;
  color: #7a7d86;
  display: flex;
  align-items: center;
}
.mg-foot {
  margin-top: 10px;
  padding-top: 10px;
  border-top: 1px solid #f5f5f7;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.mg-order {
  font-size: 11px;
  color: #b0b3ba;
}
.mg-actions {
  display: flex;
  gap: 8px;
}
</style>

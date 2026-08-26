<template>
  <div class="notify-page">
    <van-nav-bar title="站内消息" left-arrow class="tc-nav" @click-left="$router.back()">
      <template #right>
        <span class="notify-read-all" @click="onReadAll">全部已读</span>
      </template>
    </van-nav-bar>

    <van-pull-refresh v-model="refreshing" @refresh="onRefresh">
      <van-list
        v-model:loading="loading"
        :finished="finished"
        finished-text="没有更多了"
        @load="loadList"
      >
        <div
          class="nt-item"
          :class="{ unread: !Number(n.is_read) }"
          v-for="n in list"
          :key="n.id"
          @click="onRead(n)"
        >
          <div class="nt-icon" :class="n.type">
            <van-icon :name="typeIcon(n.type)" />
          </div>
          <div class="nt-body">
            <div class="nt-head">
              <span class="nt-title">{{ n.title }}</span>
              <span class="nt-dot" v-if="!Number(n.is_read)"></span>
            </div>
            <div class="nt-content">{{ n.content }}</div>
            <div class="nt-time">{{ formatTime(n.created_at) }}</div>
          </div>
        </div>

        <div class="empty-wrap" v-if="finished && !list.length && !loading">
          <van-icon name="bell" />
          <p>暂无消息</p>
        </div>
      </van-list>
    </van-pull-refresh>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { showToast } from 'vant'
import { getNotifications, readAllNotifications } from '../api/user'
import { formatTime } from '../utils'

const list = ref([])
const page = ref(1)
const loading = ref(false)
const finished = ref(false)
const refreshing = ref(false)

function typeIcon(type) {
  if (type === 'order') return 'orders-o'
  if (type === 'promotion') return 'gift-o'
  return 'volume-o'
}

async function loadList() {
  if (finished.value) return
  try {
    const data = await getNotifications({ page: page.value })
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

function onRead(n) {
  n.is_read = 1
}

async function onReadAll() {
  try {
    await readAllNotifications()
    list.value.forEach((n) => {
      n.is_read = 1
    })
    showToast('已全部标记为已读')
  } catch (e) {
    /* 已统一 toast */
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

onMounted(() => {
  loading.value = true
  loadList()
})
</script>

<style scoped>
.notify-read-all {
  font-size: 13px;
}
.nt-item {
  display: flex;
  padding: 14px 12px;
  background: #fff;
  border-bottom: 1px solid #f5f5f7;
}
.nt-item.unread {
  background: #fffdfa;
}
.nt-icon {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 19px;
  flex-shrink: 0;
  background: #eef3ff;
  color: #1677ff;
}
.nt-icon.order {
  background: #ffe9ec;
  color: #e63946;
}
.nt-icon.promotion {
  background: #fff5dd;
  color: #d48806;
}
.nt-body {
  flex: 1;
  width: 0;
  margin-left: 10px;
}
.nt-head {
  display: flex;
  align-items: center;
}
.nt-title {
  font-size: 14px;
  font-weight: 600;
  flex: 1;
  width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.nt-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #e63946;
  flex-shrink: 0;
}
.nt-content {
  margin-top: 4px;
  font-size: 13px;
  color: #7a7d86;
  line-height: 19px;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 3;
  overflow: hidden;
}
.nt-time {
  margin-top: 4px;
  font-size: 11px;
  color: #b0b3ba;
}
</style>

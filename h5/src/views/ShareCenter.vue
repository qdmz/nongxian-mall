<template>
  <div class="share-page">
    <van-nav-bar title="推广中心" left-arrow class="tc-nav" @click-left="$router.back()" />

    <!-- 推广码卡片 -->
    <div class="sc-card">
      <div class="sc-head">
        <div class="sc-title">我的推广码</div>
        <div class="sc-rate" v-if="info && info.reward_enabled">
          好友下单返 <em>{{ info.reward_rate }}%</em>
        </div>
      </div>
      <div class="sc-code" v-if="info">{{ info.code }}</div>
      <div class="sc-url ellipsis" v-if="info">{{ info.url }}</div>
      <div class="sc-actions">
        <van-button round size="small" class="sc-btn" @click="copyCode">复制推广码</van-button>
        <van-button round size="small" class="sc-btn sc-btn-ghost" @click="copyUrl">复制推广链接</van-button>
      </div>

      <div class="sc-stats" v-if="info">
        <div class="sc-stat">
          <em>{{ info.click_count }}</em>
          <span>推广点击</span>
        </div>
        <div class="sc-stat">
          <em>{{ info.order_count }}</em>
          <span>带来订单</span>
        </div>
        <div class="sc-stat">
          <em>¥{{ Number(info.earnings).toFixed(2) }}</em>
          <span>累计收益</span>
        </div>
      </div>
    </div>

    <!-- 团队与奖励 -->
    <div class="section">
      <van-tabs v-model:active="activeTab" @change="onTabChange">
        <van-tab title="我的团队" name="team">
          <van-list
            v-model:loading="teamLoading"
            :finished="teamFinished"
            finished-text="没有更多了"
            @load="loadTeam"
          >
            <div class="sc-member" v-for="(m, i) in teamList" :key="i">
              <img class="sc-avatar" :src="m.avatar || defaultAvatar" alt="" />
              <div class="sc-member-info">
                <div class="sc-member-name">{{ m.nickname || '好友' }}</div>
                <div class="sc-member-time">{{ timeAgo(m.created_at) }}加入</div>
              </div>
              <div class="sc-member-amount">
                消费 ¥{{ Number(m.total_consumption).toFixed(2) }}
              </div>
            </div>
            <div class="empty-wrap" v-if="teamFinished && !teamList.length && !teamLoading">
              <van-icon name="friends-o" />
              <p>还没有好友通过你的邀请注册</p>
            </div>
          </van-list>
        </van-tab>

        <van-tab title="奖励明细" name="rewards">
          <van-list
            v-model:loading="rewardLoading"
            :finished="rewardFinished"
            finished-text="没有更多了"
            @load="loadRewards"
          >
            <div class="sc-reward" v-for="r in rewardList" :key="r.id">
              <div class="sc-reward-info">
                <div class="sc-reward-title">
                  好友下单返佣
                  <span class="sc-reward-order">{{ r.order_no }}</span>
                </div>
                <div class="sc-reward-time">{{ formatTime(r.created_at) }}</div>
              </div>
              <div class="sc-reward-right">
                <div class="sc-reward-amount">+¥{{ Number(r.amount).toFixed(2) }}</div>
                <div class="sc-reward-status" :class="{ done: Number(r.status) === 1 }">
                  {{ rewardStatusText(r.status) }}
                </div>
              </div>
            </div>
            <div class="empty-wrap" v-if="rewardFinished && !rewardList.length && !rewardLoading">
              <van-icon name="balance-list-o" />
              <p>暂无奖励明细，快去邀请好友吧</p>
            </div>
          </van-list>
        </van-tab>
      </van-tabs>
    </div>

    <div class="sc-tip">把推广链接分享给好友，好友注册并下单后即可获得返佣奖励</div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { showToast } from 'vant'
import { getShareCode, getMyTeam, getRewards } from '../api/share'
import { formatTime, timeAgo, copyText } from '../utils'

const defaultAvatar =
  'data:image/svg+xml,' +
  encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64"><rect width="64" height="64" fill="#eef3ff"/><circle cx="32" cy="24" r="10" fill="#1677ff"/><path d="M12 58c0-12 9-18 20-18s20 6 20 18" fill="#1677ff"/></svg>'
  )

const info = ref(null)
const activeTab = ref('team')

const teamList = ref([])
const teamPage = ref(1)
const teamLoading = ref(false)
const teamFinished = ref(false)

const rewardList = ref([])
const rewardPage = ref(1)
const rewardLoading = ref(false)
const rewardFinished = ref(false)

function rewardStatusText(status) {
  const s = Number(status)
  if (s === 1) return '已发放'
  if (s === 2) return '已取消'
  return '待发放'
}

async function loadInfo() {
  try {
    info.value = await getShareCode()
  } catch (e) {
    /* 已统一 toast */
  }
}

async function loadTeam() {
  if (teamFinished.value) return
  try {
    const data = await getMyTeam({ page: teamPage.value })
    const items = data?.list || []
    teamList.value.push(...items)
    if (teamList.value.length >= Number(data?.total || 0) || items.length === 0) {
      teamFinished.value = true
    } else {
      teamPage.value += 1
    }
    teamLoading.value = false
  } catch (e) {
    teamLoading.value = false
    teamFinished.value = true
  }
}

async function loadRewards() {
  if (rewardFinished.value) return
  try {
    const data = await getRewards({ page: rewardPage.value })
    const items = data?.list || []
    rewardList.value.push(...items)
    if (rewardList.value.length >= Number(data?.total || 0) || items.length === 0) {
      rewardFinished.value = true
    } else {
      rewardPage.value += 1
    }
    rewardLoading.value = false
  } catch (e) {
    rewardLoading.value = false
    rewardFinished.value = true
  }
}

function onTabChange() {
  // 切换 tab 时按需触发首次加载
  if (activeTab.value === 'team' && !teamList.value.length && !teamFinished.value) {
    teamLoading.value = true
    loadTeam()
  }
  if (activeTab.value === 'rewards' && !rewardList.value.length && !rewardFinished.value) {
    rewardLoading.value = true
    loadRewards()
  }
}

async function copyCode() {
  if (!info.value) return
  const ok = await copyText(info.value.code)
  showToast(ok ? '推广码已复制' : '复制失败，请手动复制')
}

async function copyUrl() {
  if (!info.value) return
  const ok = await copyText(info.value.url)
  showToast(ok ? '推广链接已复制，快去分享吧' : '复制失败，请手动复制')
}

onMounted(() => {
  loadInfo()
  teamLoading.value = true
  loadTeam()
})
</script>

<style scoped>
.sc-card {
  margin: 12px;
  padding: 18px 16px;
  border-radius: 12px;
  background: linear-gradient(135deg, #e63946 0%, #c1121f 60%, #a4161a 100%);
  color: #fff;
}
.sc-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.sc-title {
  font-size: 15px;
  font-weight: 700;
}
.sc-rate {
  font-size: 11px;
  background: rgba(255, 255, 255, 0.2);
  padding: 2px 8px;
  border-radius: 10px;
}
.sc-rate em {
  font-style: normal;
  color: #ffd166;
  font-weight: 700;
}
.sc-code {
  margin-top: 12px;
  font-size: 30px;
  font-weight: 800;
  letter-spacing: 4px;
}
.sc-url {
  margin-top: 6px;
  font-size: 11px;
  opacity: 0.85;
}
.sc-actions {
  margin-top: 14px;
  display: flex;
  gap: 10px;
}
.sc-btn {
  background: #ffd166;
  color: #a4161a;
  border: none;
  font-weight: 700;
}
.sc-btn-ghost {
  background: rgba(255, 255, 255, 0.2);
  color: #fff;
}
.sc-stats {
  margin-top: 18px;
  display: flex;
  background: rgba(255, 255, 255, 0.12);
  border-radius: 10px;
  padding: 12px 0;
}
.sc-stat {
  flex: 1;
  text-align: center;
}
.sc-stat em {
  font-style: normal;
  font-size: 18px;
  font-weight: 800;
  display: block;
}
.sc-stat span {
  font-size: 11px;
  opacity: 0.85;
}
.sc-member {
  display: flex;
  align-items: center;
  padding: 12px;
  border-bottom: 1px solid #f5f5f7;
}
.sc-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
  background: #f2f3f5;
  flex-shrink: 0;
}
.sc-member-info {
  flex: 1;
  width: 0;
  margin: 0 10px;
}
.sc-member-name {
  font-size: 14px;
  font-weight: 600;
}
.sc-member-time {
  margin-top: 2px;
  font-size: 11px;
  color: #b0b3ba;
}
.sc-member-amount {
  font-size: 13px;
  color: #44464d;
}
.sc-reward {
  display: flex;
  align-items: center;
  padding: 12px;
  border-bottom: 1px solid #f5f5f7;
}
.sc-reward-info {
  flex: 1;
  width: 0;
}
.sc-reward-title {
  font-size: 14px;
  font-weight: 600;
}
.sc-reward-order {
  margin-left: 6px;
  font-size: 11px;
  color: #9a9da5;
  font-weight: 400;
}
.sc-reward-time {
  margin-top: 2px;
  font-size: 11px;
  color: #b0b3ba;
}
.sc-reward-right {
  text-align: right;
}
.sc-reward-amount {
  color: #2a9d5c;
  font-weight: 700;
  font-size: 15px;
}
.sc-reward-status {
  margin-top: 2px;
  font-size: 11px;
  color: #ff8c00;
}
.sc-reward-status.done {
  color: #2a9d5c;
}
.sc-tip {
  text-align: center;
  color: #b0b3ba;
  font-size: 11px;
  padding: 14px 16px 24px;
}
</style>

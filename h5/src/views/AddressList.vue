<template>
  <div class="addr-list">
    <van-nav-bar title="收货地址" left-arrow class="tc-nav" @click-left="$router.back()" fixed placeholder>
      <template #right>
        <van-icon name="home-o" size="18" @click="$router.push('/')" />
      </template>
    </van-nav-bar>

    <van-pull-refresh v-model="refreshing" @refresh="loadList">
      <van-loading v-if="loading && !loaded" class="page-loading" size="28" vertical>加载中...</van-loading>

      <div class="addr-list-body" v-if="loaded">
        <van-swipe-cell v-for="addr in list" :key="addr.id">
          <div class="addr-item" @click="onSelect(addr)">
            <div class="addr-item-top">
              <span class="addr-item-name">{{ addr.consignee }}</span>
              <span class="addr-item-phone">{{ addr.phone }}</span>
              <span class="addr-item-default" v-if="addr.is_default">默认</span>
            </div>
            <div class="addr-item-detail">
              {{ addr.province }}{{ addr.city }}{{ addr.district }}{{ addr.detail }}
            </div>
          </div>
          <template #right>
            <van-button square type="primary" text="编辑" class="addr-op-btn" @click="onEdit(addr)" />
            <van-button square type="danger" text="删除" class="addr-op-btn" @click="onDelete(addr)" />
          </template>
        </van-swipe-cell>

        <div class="empty-wrap" v-if="!list.length">
          <van-icon name="location-o" />
          <p>还没有收货地址</p>
        </div>
      </div>
    </van-pull-refresh>

    <div class="footer-bar">
      <van-button round block class="tc-btn" @click="$router.push('/address/edit')">新增收货地址</van-button>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { showConfirmDialog, showToast } from 'vant'
import { getAddresses, deleteAddress } from '../api/user'

const router = useRouter()

const list = ref([])
const loading = ref(false)
const loaded = ref(false)
const refreshing = ref(false)

async function loadList() {
  loading.value = true
  try {
    list.value = (await getAddresses()) || []
    loaded.value = true
  } catch (e) {
    /* 已统一 toast */
  } finally {
    loading.value = false
    refreshing.value = false
  }
}

function onEdit(addr) {
  router.push('/address/edit/' + addr.id)
}

function onDelete(addr) {
  showConfirmDialog({ title: '提示', message: `确定删除「${addr.consignee}」的地址吗？` })
    .then(async () => {
      await deleteAddress(addr.id)
      showToast('已删除')
      loadList()
    })
    .catch(() => {})
}

function onSelect(addr) {
  // 从确认订单等页面进入时可点击选中并返回
  if (window.history.length > 1) {
    sessionStorage.setItem('selected_address_id', String(addr.id))
    router.back()
  }
}

onMounted(loadList)
</script>

<style scoped>
.page-loading {
  padding: 80px 0;
}
.addr-list-body {
  padding: 10px 10px 80px;
}
.addr-item {
  background: #fff;
  border-radius: 10px;
  padding: 14px 12px;
  margin-bottom: 10px;
}
.addr-item-top {
  display: flex;
  align-items: center;
}
.addr-item-name {
  font-size: 15px;
  font-weight: 600;
}
.addr-item-phone {
  margin-left: 10px;
  color: #7a7d86;
  font-size: 13px;
}
.addr-item-default {
  margin-left: 8px;
  background: #ffe9ec;
  color: #e63946;
  font-size: 10px;
  padding: 0 5px;
  border-radius: 3px;
  line-height: 16px;
}
.addr-item-detail {
  margin-top: 6px;
  font-size: 13px;
  color: #44464d;
  line-height: 18px;
}
.addr-op-btn {
  height: 100%;
}
</style>

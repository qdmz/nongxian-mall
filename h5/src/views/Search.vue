<template>
  <div class="search-page">
    <div class="search-header">
      <div class="search-input-wrap">
        <van-icon name="search" class="search-icon" />
        <input
          ref="inputRef"
          v-model="keyword"
          class="search-input"
          type="search"
          placeholder="搜索新鲜农产品"
          @keyup.enter="doSearch"
        />
        <van-icon v-if="keyword" name="clear" class="clear-icon" @click="clearKeyword" />
      </div>
      <span class="search-btn" @click="doSearch">搜索</span>
    </div>

    <!-- 未搜索：历史 + 热门 -->
    <template v-if="!searched">
      <div class="section" v-if="history.length">
        <div class="section-head">
          <div class="section-title">搜索历史</div>
          <van-icon name="delete-o" class="section-more" @click="clearHistory" />
        </div>
        <div class="tags-wrap">
          <span class="tag-item" v-for="(h, i) in history" :key="i" @click="quickSearch(h)">{{ h }}</span>
        </div>
      </div>

      <div class="section">
        <div class="section-head">
          <div class="section-title">热门搜索</div>
        </div>
        <div class="tags-wrap" v-if="hotKeywords.length">
          <span
            class="tag-item hot"
            v-for="(k, i) in hotKeywords"
            :key="i"
            @click="quickSearch(k)"
          >
            <em class="hot-rank" :class="'rank-' + (i + 1)">{{ i + 1 }}</em>{{ k }}
          </span>
        </div>
        <van-loading v-else-if="hotLoading" class="page-loading" size="20" />
        <div class="empty-wrap" v-else>
          <van-icon name="fire-o" />
          <p>暂无热门搜索</p>
        </div>
      </div>
    </template>

    <!-- 搜索结果 -->
    <template v-else>
      <van-dropdown-menu class="sort-bar">
        <van-dropdown-item v-model="orderBy" :options="sortOptions" @change="onSortChange" />
      </van-dropdown-menu>

      <van-list
        v-model:loading="loading"
        :finished="finished"
        finished-text="没有更多了"
        @load="loadList"
      >
        <div class="grid2" v-if="list.length">
          <ProductCard v-for="p in list" :key="p.id" :product="p" />
        </div>
        <div class="empty-wrap" v-if="finished && !list.length && !loading">
          <van-icon name="search" />
          <p>没有找到“{{ searchedKeyword }}”相关的商品</p>
        </div>
      </van-list>
    </template>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { showConfirmDialog, showToast } from 'vant'
import { getProducts, getHotKeywords } from '../api/product'
import ProductCard from '../components/ProductCard.vue'

const route = useRoute()
const inputRef = ref(null)

const keyword = ref('')
const searchedKeyword = ref('')
const searched = ref(false)

const history = ref(JSON.parse(localStorage.getItem('search_history') || '[]'))
const hotKeywords = ref([])
const hotLoading = ref(false)

const list = ref([])
const page = ref(1)
const loading = ref(false)
const finished = ref(false)

const orderBy = ref('')
const sortOptions = [
  { text: '综合排序', value: '' },
  { text: '销量优先', value: 'sales' },
  { text: '价格从低到高', value: 'price_asc' },
  { text: '价格从高到低', value: 'price_desc' },
  { text: '最新上架', value: 'newest' }
]

function saveHistory(word) {
  if (!word) return
  const arr = history.value.filter((h) => h !== word)
  arr.unshift(word)
  history.value = arr.slice(0, 10)
  localStorage.setItem('search_history', JSON.stringify(history.value))
}

function clearHistory() {
  showConfirmDialog({
    title: '提示',
    message: '确定清空搜索历史吗？'
  })
    .then(() => {
      history.value = []
      localStorage.removeItem('search_history')
      showToast('已清空')
    })
    .catch(() => {})
}

function clearKeyword() {
  keyword.value = ''
  inputRef.value?.focus()
}

function quickSearch(word) {
  keyword.value = word
  doSearch()
}

function doSearch() {
  const word = (keyword.value || '').trim()
  if (!word) {
    showToast('请输入搜索关键词')
    return
  }
  saveHistory(word)
  searchedKeyword.value = word
  searched.value = true
  resetList()
}

function onSortChange() {
  resetList()
}

function resetList() {
  list.value = []
  page.value = 1
  finished.value = false
  loading.value = true
  loadList()
}

async function loadList() {
  if (finished.value) return
  try {
    const params = {
      keyword: searchedKeyword.value,
      page: page.value,
      page_size: 20
    }
    if (route.query.is_hot) params.is_hot = 1
    if (orderBy.value) params.order_by = orderBy.value
    const data = await getProducts(params)
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

onMounted(async () => {
  if (route.query.keyword) {
    keyword.value = String(route.query.keyword)
    doSearch()
  } else {
    inputRef.value?.focus()
  }
  hotLoading.value = true
  try {
    const words = await getHotKeywords()
    hotKeywords.value = (words || []).slice(0, 10)
  } catch (e) {
    hotKeywords.value = []
  } finally {
    hotLoading.value = false
  }
})
</script>

<style scoped>
.search-header {
  display: flex;
  align-items: center;
  padding: 8px 12px;
  background: linear-gradient(135deg, #e63946 0%, #c1121f 100%);
  position: sticky;
  top: 0;
  z-index: 10;
}
.search-input-wrap {
  flex: 1;
  display: flex;
  align-items: center;
  height: 34px;
  border-radius: 17px;
  background: #fff;
  padding: 0 12px;
}
.search-icon {
  color: #b0b3ba;
  margin-right: 6px;
}
.search-input {
  flex: 1;
  border: none;
  outline: none;
  font-size: 14px;
  background: transparent;
}
.clear-icon {
  color: #c8c9ce;
  padding: 4px;
}
.search-btn {
  color: #fff;
  font-size: 14px;
  margin-left: 12px;
}
.tags-wrap {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  padding: 0 12px 14px;
}
.tag-item {
  padding: 5px 12px;
  border-radius: 15px;
  background: #f5f5f7;
  color: #44464d;
  font-size: 12px;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.tag-item.hot {
  background: #fff5dd;
}
.hot-rank {
  font-style: normal;
  font-weight: 700;
  margin-right: 4px;
  color: #b0b3ba;
}
.hot-rank.rank-1 {
  color: #e63946;
}
.hot-rank.rank-2 {
  color: #ff8c00;
}
.hot-rank.rank-3 {
  color: #ffb703;
}
.sort-bar {
  position: sticky;
  top: 50px;
  z-index: 9;
}
.grid2 {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 8px;
  padding: 10px 10px 20px;
}
.page-loading {
  display: flex;
  justify-content: center;
  padding: 20px 0;
}
</style>

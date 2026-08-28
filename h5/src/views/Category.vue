<template>
  <div class="category page-with-tabbar">
    <van-nav-bar title="全部分类" class="tc-nav">
      <template #right>
        <van-icon name="home-o" size="18" @click="$router.push('/')" />
      </template>
    </van-nav-bar>

    <div class="cat-body">
      <!-- 左侧分类列表 -->
      <van-sidebar v-model="activeTop" class="cat-side">
        <van-sidebar-item
          v-for="cat in topCategories"
          :key="cat.id"
          :title="cat.name"
          :badge="cat.is_red ? '🚩' : ''"
          @click="onTopClick(cat)"
        />
      </van-sidebar>

      <!-- 右侧商品网格 -->
      <div class="cat-right">
        <van-pull-refresh v-model="refreshing" @refresh="onRefresh">
          <div class="sub-chips" v-if="currentChildren.length">
            <span
              class="sub-chip"
              :class="{ active: activeSub === 0 }"
              @click="onSubClick(0)"
            >全部</span>
            <span
              class="sub-chip"
              :class="{ active: activeSub === child.id }"
              v-for="child in currentChildren"
              :key="child.id"
              @click="onSubClick(child.id)"
            >{{ child.name }}</span>
          </div>

          <van-list
            v-model:loading="loading"
            :finished="finished"
            finished-text="没有更多了"
            @load="loadProducts"
          >
            <div class="grid2" v-if="list.length">
              <ProductCard v-for="p in list" :key="p.id" :product="p" />
            </div>
            <div class="empty-wrap" v-if="finished && !list.length && !loading">
              <van-icon name="goods-collect-o" />
              <p>该分类下暂无商品</p>
            </div>
          </van-list>
        </van-pull-refresh>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { getCategories, getProducts } from '../api/product'
import ProductCard from '../components/ProductCard.vue'

const route = useRoute()

const categories = ref([])
const activeTop = ref(0)
const activeSub = ref(0)
const list = ref([])
const page = ref(1)
const loading = ref(false)
const finished = ref(false)
const refreshing = ref(false)

const topCategories = computed(() =>
  (categories.value || []).filter((c) => Number(c.parent_id) === 0)
)

const currentTop = computed(() => topCategories.value[activeTop.value] || null)
const currentChildren = computed(() => {
  const top = currentTop.value
  if (!top) return []
  return (categories.value || []).filter((c) => Number(c.parent_id) === Number(top.id))
})

const currentCategoryId = computed(() => {
  if (activeSub.value) return activeSub.value
  const children = currentChildren.value
  return children.length ? children[0].id : currentTop.value?.id || 0
})

async function loadCategories() {
  categories.value = await getCategories()
  // 初始化选中：URL 指定的分类（可能是顶级或子级）
  const queryId = Number(route.query.category_id || 0)
  if (queryId) {
    const idx = topCategories.value.findIndex((c) => Number(c.id) === queryId)
    if (idx >= 0) {
      activeTop.value = idx
    } else {
      const child = categories.value.find((c) => Number(c.id) === queryId)
      if (child) {
        const pIdx = topCategories.value.findIndex((c) => Number(c.id) === Number(child.parent_id))
        if (pIdx >= 0) activeTop.value = pIdx
        activeSub.value = queryId
      }
    }
  }
  resetList()
}

function resetList() {
  list.value = []
  page.value = 1
  finished.value = false
  loading.value = true
  loadProducts()
}

async function loadProducts() {
  if (finished.value) return
  try {
    const data = await getProducts({
      category_id: currentCategoryId.value,
      page: page.value,
      page_size: 20
    })
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

function onTopClick() {
  activeSub.value = 0
  resetList()
}

function onSubClick(id) {
  activeSub.value = id
  resetList()
}

function onRefresh() {
  resetList()
  refreshing.value = false
}

watch(
  () => route.query.category_id,
  () => {
    if (route.path === '/category') loadCategories()
  }
)

onMounted(loadCategories)
</script>

<style scoped>
.cat-body {
  display: flex;
  min-height: calc(100vh - 96px);
}
.cat-side {
  width: 90px;
  flex-shrink: 0;
  height: calc(100vh - 106px);
  overflow-y: auto;
}
.cat-right {
  flex: 1;
  width: 0;
  background: #fff;
  min-height: calc(100vh - 106px);
}
.sub-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  padding: 10px;
}
.sub-chip {
  padding: 4px 12px;
  border-radius: 14px;
  background: #f5f5f7;
  color: #44464d;
  font-size: 12px;
}
.sub-chip.active {
  background: #ffe9ec;
  color: #e63946;
  font-weight: 600;
}
.grid2 {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 8px;
  padding: 0 10px 12px;
}
</style>

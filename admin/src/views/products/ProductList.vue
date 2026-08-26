<template>
  <div class="page-container">
    <!-- 搜索表单 -->
    <el-card shadow="never" class="filter-card">
      <el-form inline @submit.prevent>
        <el-form-item label="关键词">
          <el-input
            v-model="query.keyword"
            placeholder="商品名称"
            clearable
            style="width: 200px"
            @keyup.enter="handleSearch"
          />
        </el-form-item>
        <el-form-item label="分类">
          <el-cascader
            v-model="query.categoryPath"
            :options="categoryOptions"
            :props="{ value: 'id', label: 'name', checkStrictly: true, emitPath: false }"
            placeholder="全部分类"
            clearable
            style="width: 180px"
          />
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="query.status" placeholder="全部" clearable style="width: 120px">
            <el-option label="上架" :value="1" />
            <el-option label="下架" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" :icon="Search" @click="handleSearch">搜索</el-button>
          <el-button :icon="Refresh" @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card shadow="never">
      <div class="table-toolbar">
        <div class="toolbar-title">商品列表</div>
        <el-button type="primary" :icon="Plus" @click="$router.push('/products/create')">
          新建商品
        </el-button>
      </div>

      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column label="商品" min-width="260">
          <template #default="{ row }">
            <div class="product-cell">
              <img :src="row.image || row.cover" class="table-img" alt="" />
              <div class="product-info">
                <div class="product-name">{{ row.name }}</div>
                <div class="product-sub">{{ row.subtitle || row.category_name || '-' }}</div>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="价格" width="100">
          <template #default="{ row }">
            <span class="money">{{ money(row.price) }}</span>
          </template>
        </el-table-column>
        <el-table-column label="库存" width="90">
          <template #default="{ row }">
            <span :class="{ 'money': row.stock <= (row.stock_warning || 0) }">{{ row.stock }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="sales" label="销量" width="80" />
        <el-table-column label="标签" width="200">
          <template #default="{ row }">
            <el-tag v-if="row.is_hot == 1" type="danger" size="small">热销</el-tag>
            <el-tag v-if="row.is_new == 1" type="success" size="small" class="tag-gap">新品</el-tag>
            <el-tag v-if="row.is_recommend == 1" type="warning" size="small" class="tag-gap">
              党员推荐
            </el-tag>
            <el-tag v-if="row.is_red == 1" type="danger" size="small" effect="plain" class="tag-gap">
              红色助农
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="80">
          <template #default="{ row }">
            <el-tag :type="row.status == 1 ? 'success' : 'info'" size="small">
              {{ row.status == 1 ? '上架' : '下架' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="250" fixed="right">
          <template #default="{ row }">
            <el-button
              type="primary"
              link
              size="small"
              @click="$router.push(`/products/edit/${row.id}`)"
            >
              编辑
            </el-button>
            <el-button
              :type="row.status == 1 ? 'warning' : 'success'"
              link
              size="small"
              @click="handleToggle(row)"
            >
              {{ row.status == 1 ? '下架' : '上架' }}
            </el-button>
            <el-button type="info" link size="small" @click="openStock(row)">改库存</el-button>
            <el-button type="danger" link size="small" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination-wrap">
        <el-pagination
          v-model:current-page="query.page"
          v-model:page-size="query.page_size"
          :total="total"
          :page-sizes="[10, 15, 30, 50]"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="loadList"
          @current-change="loadList"
        />
      </div>
    </el-card>

    <!-- 快速改库存弹窗 -->
    <el-dialog v-model="stockVisible" title="快速修改库存" width="400px">
      <el-form label-width="90px">
        <el-form-item label="商品">
          <span>{{ stockForm.name }}</span>
        </el-form-item>
        <el-form-item label="当前库存">
          <span>{{ stockForm.oldStock }}</span>
        </el-form-item>
        <el-form-item label="新库存">
          <el-input-number v-model="stockForm.stock" :min="0" :max="999999" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="stockVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitting" @click="submitStock">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Search, Refresh, Plus } from '@element-plus/icons-vue'
import { getProducts, toggleProductStatus, updateProductStock, deleteProduct } from '../../api/product'
import { getCategories } from '../../api/category'
import { formatMoney } from '../../utils/format'

const money = (v) => formatMoney(v)

const loading = ref(false)
const submitting = ref(false)
const list = ref([])
const total = ref(0)
const categoryOptions = ref([])

const query = reactive({
  keyword: '',
  categoryPath: null,
  status: null,
  page: 1,
  page_size: 15
})

const stockVisible = ref(false)
const stockForm = reactive({
  id: null,
  name: '',
  oldStock: 0,
  stock: 0
})

async function loadCategories() {
  try {
    const data = await getCategories()
    const raw = Array.isArray(data) ? data : (data && data.list) || []
    const tops = raw.filter((c) => !c.parent_id || c.parent_id === 0)
    categoryOptions.value = tops.map((t) => ({
      id: t.id,
      name: t.name,
      children: raw
        .filter((c) => c.parent_id === t.id)
        .map((c) => ({ id: c.id, name: c.name }))
    }))
  } catch (e) {
    categoryOptions.value = []
  }
}

async function loadList() {
  loading.value = true
  try {
    const params = { page: query.page, page_size: query.page_size }
    if (query.keyword) params.keyword = query.keyword
    if (query.categoryPath) params.category_id = query.categoryPath
    if (query.status !== null && query.status !== '') params.status = query.status
    const data = await getProducts(params)
    list.value = (data && data.list) || []
    total.value = (data && data.total) || 0
  } finally {
    loading.value = false
  }
}

function handleSearch() {
  query.page = 1
  loadList()
}

function handleReset() {
  query.keyword = ''
  query.categoryPath = null
  query.status = null
  query.page = 1
  loadList()
}

async function handleToggle(row) {
  const actionText = row.status == 1 ? '下架' : '上架'
  try {
    await ElMessageBox.confirm(`确定要${actionText}商品「${row.name}」吗？`, '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })
  } catch (e) {
    return
  }
  await toggleProductStatus(row.id)
  ElMessage.success(`${actionText}成功`)
  loadList()
}

function openStock(row) {
  stockForm.id = row.id
  stockForm.name = row.name
  stockForm.oldStock = row.stock
  stockForm.stock = row.stock
  stockVisible.value = true
}

async function submitStock() {
  submitting.value = true
  try {
    await updateProductStock(stockForm.id, stockForm.stock)
    ElMessage.success('库存修改成功')
    stockVisible.value = false
    loadList()
  } finally {
    submitting.value = false
  }
}

async function handleDelete(row) {
  try {
    await ElMessageBox.confirm(
      `确定要删除商品「${row.name}」吗？删除后不可恢复！`,
      '警告',
      { confirmButtonText: '删除', cancelButtonText: '取消', type: 'warning' }
    )
  } catch (e) {
    return
  }
  await deleteProduct(row.id)
  ElMessage.success('删除成功')
  loadList()
}

onMounted(() => {
  loadCategories()
  loadList()
})
</script>

<style scoped>
.table-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}

.toolbar-title {
  font-size: 16px;
  font-weight: 600;
  color: #262626;
}

.product-cell {
  display: flex;
  align-items: center;
  gap: 10px;
}

.product-info {
  overflow: hidden;
}

.product-name {
  font-size: 14px;
  color: #262626;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.product-sub {
  font-size: 12px;
  color: #999;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.tag-gap {
  margin-left: 4px;
}
</style>

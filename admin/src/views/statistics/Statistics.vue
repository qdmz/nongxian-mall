<template>
  <div class="page-container">
    <!-- 筛选 -->
    <el-card shadow="never" class="filter-card">
      <el-form inline @submit.prevent>
        <el-form-item label="统计周期">
          <el-radio-group v-model="days" @change="loadRank">
            <el-radio-button :value="7">近7天</el-radio-button>
            <el-radio-button :value="30">近30天</el-radio-button>
            <el-radio-button :value="90">近90天</el-radio-button>
          </el-radio-group>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" :icon="Download" @click="handleExport">导出 CSV</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card shadow="never">
      <template #header>
        <div class="card-header">
          <span>商品销量排行（近 {{ days }} 天 · TOP {{ rank.length }}）</span>
        </div>
      </template>

      <el-table :data="rank" v-loading="loading" stripe>
        <el-table-column label="排名" width="80" align="center">
          <template #default="{ $index }">
            <span
              class="rank-badge"
              :class="`rank-${$index + 1}`"
              v-if="$index < 3"
            >
              {{ $index + 1 }}
            </span>
            <span v-else class="rank-num">{{ $index + 1 }}</span>
          </template>
        </el-table-column>
        <el-table-column label="商品" min-width="280">
          <template #default="{ row }">
            <div class="product-cell">
              <img v-if="row.image" :src="row.image" class="table-img" alt="" />
              <div>
                <div class="product-name">{{ row.product_name || row.name }}</div>
                <div class="sub-text">{{ row.category_name || '' }}</div>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="销量" width="120" align="center" sortable :sort-by="(row) => Number(row.sales || row.sales_count || 0)">
          <template #default="{ row }">
            <span class="money">{{ row.sales || row.sales_count || 0 }}</span>
          </template>
        </el-table-column>
        <el-table-column label="销售额" width="140" align="center" sortable :sort-by="(row) => Number(row.amount || row.sales_amount || 0)">
          <template #default="{ row }">
            <span class="money">{{ money(row.amount || row.sales_amount) }}</span>
          </template>
        </el-table-column>
        <el-table-column label="销量占比" min-width="200">
          <template #default="{ row }">
            <el-progress
              :percentage="salesPercent(row)"
              :color="'#d4380d'"
              :stroke-width="12"
            />
          </template>
        </el-table-column>
      </el-table>
      <el-empty v-if="!loading && !rank.length" description="暂无数据" />
    </el-card>

    <el-alert
      class="export-tip"
      type="info"
      :closable="false"
      show-icon
      title="导出说明：点击「导出 CSV」按钮可将当前排行的全部字段下载为 CSV 文件，可用 Excel / WPS 直接打开。"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Download } from '@element-plus/icons-vue'
import { getProductRank } from '../../api/dashboard'
import { formatMoney } from '../../utils/format'

const money = (v) => formatMoney(v)

const loading = ref(false)
const days = ref(30)
const rank = ref([])

async function loadRank() {
  loading.value = true
  try {
    const data = await getProductRank(days.value, 20)
    rank.value = (data && data.list) || (Array.isArray(data) ? data : [])
  } finally {
    loading.value = false
  }
}

function totalSales() {
  return rank.value.reduce(
    (sum, r) => sum + Number(r.sales || r.sales_count || 0),
    0
  )
}

function salesPercent(row) {
  const total = totalSales()
  if (!total) return 0
  const val = (Number(row.sales || row.sales_count || 0) / total) * 100
  return Math.round(val * 10) / 10
}

function handleExport() {
  if (!rank.value.length) {
    ElMessage.warning('暂无数据可导出')
    return
  }
  const header = ['排名', '商品名称', '分类', '销量', '销售额(元)']
  const rows = rank.value.map((r, i) => [
    i + 1,
    (r.product_name || r.name || '').replace(/"/g, '""'),
    (r.category_name || '').replace(/"/g, '""'),
    r.sales || r.sales_count || 0,
    Number(r.amount || r.sales_amount || 0).toFixed(2)
  ])
  const csvContent = [header, ...rows]
    .map((r) => r.map((c) => `"${c}"`).join(','))
    .join('\n')
  // BOM 保证 Excel 正确识别 UTF-8 中文
  const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `商品销量排行_近${days.value}天_${new Date().toISOString().slice(0, 10)}.csv`
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)
  ElMessage.success('导出成功')
}

onMounted(loadRank)
</script>

<style scoped>
.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.product-cell {
  display: flex;
  align-items: center;
  gap: 10px;
}

.product-name {
  font-size: 14px;
  color: #262626;
}

.sub-text {
  font-size: 12px;
  color: #999;
}

.rank-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 26px;
  height: 26px;
  border-radius: 50%;
  color: #fff;
  font-weight: 700;
  font-size: 13px;
}

.rank-1 {
  background: #d4380d;
}

.rank-2 {
  background: #e06a45;
}

.rank-3 {
  background: #e98f70;
}

.rank-num {
  color: #999;
}

.export-tip {
  margin-top: 16px;
}
</style>

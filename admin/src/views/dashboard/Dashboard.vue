<template>
  <div class="page-container" v-loading="loading">
    <!-- 统计卡片 -->
    <el-row :gutter="16" class="stat-row">
      <el-col :xs="12" :sm="12" :md="6">
        <div class="stat-card">
          <div class="stat-icon" style="background: linear-gradient(135deg, #ff9a44, #fc6076)">
            <el-icon><Coin /></el-icon>
          </div>
          <div class="stat-info">
            <div class="stat-label">今日销售额</div>
            <div class="stat-value">{{ money(stat.today && stat.today.amount) }}</div>
            <div class="stat-sub">订单 {{ (stat.today && stat.today.orders) || 0 }} 单</div>
          </div>
        </div>
      </el-col>
      <el-col :xs="12" :sm="12" :md="6">
        <div class="stat-card">
          <div class="stat-icon" style="background: linear-gradient(135deg, #36d1dc, #5b86e5)">
            <el-icon><Calendar /></el-icon>
          </div>
          <div class="stat-info">
            <div class="stat-label">昨日销售额</div>
            <div class="stat-value">{{ money(stat.yesterday && stat.yesterday.amount) }}</div>
            <div class="stat-sub">订单 {{ (stat.yesterday && stat.yesterday.orders) || 0 }} 单</div>
          </div>
        </div>
      </el-col>
      <el-col :xs="12" :sm="12" :md="6">
        <div class="stat-card">
          <div class="stat-icon" style="background: linear-gradient(135deg, #f7971e, #d4380d)">
            <el-icon><DataLine /></el-icon>
          </div>
          <div class="stat-info">
            <div class="stat-label">本月销售额</div>
            <div class="stat-value">{{ money(stat.month && stat.month.amount) }}</div>
            <div class="stat-sub">订单 {{ (stat.month && stat.month.orders) || 0 }} 单</div>
          </div>
        </div>
      </el-col>
      <el-col :xs="12" :sm="12" :md="6">
        <div class="stat-card">
          <div class="stat-icon" style="background: linear-gradient(135deg, #11998e, #38ef7d)">
            <el-icon><Trophy /></el-icon>
          </div>
          <div class="stat-info">
            <div class="stat-label">累计销售额</div>
            <div class="stat-value">{{ money(stat.total && stat.total.amount) }}</div>
            <div class="stat-sub">订单 {{ (stat.total && stat.total.orders) || 0 }} 单</div>
          </div>
        </div>
      </el-col>
    </el-row>

    <!-- 图表区 -->
    <el-row :gutter="16" class="chart-row">
      <el-col :xs="24" :md="16">
        <el-card shadow="never">
          <template #header>
            <div class="card-header">
              <span>销售趋势（近 30 天）</span>
              <el-radio-group v-model="trendDays" size="small" @change="loadTrend">
                <el-radio-button :value="7">近7天</el-radio-button>
                <el-radio-button :value="30">近30天</el-radio-button>
              </el-radio-group>
            </div>
          </template>
          <div ref="trendChartRef" class="chart-box" />
        </el-card>
      </el-col>
      <el-col :xs="24" :md="8">
        <el-card shadow="never">
          <template #header>
            <div class="card-header"><span>分类销售占比（近 30 天）</span></div>
          </template>
          <div ref="pieChartRef" class="chart-box" />
        </el-card>
      </el-col>
    </el-row>

    <!-- 最新订单 + 库存预警 -->
    <el-row :gutter="16">
      <el-col :xs="24" :md="14">
        <el-card shadow="never">
          <template #header>
            <div class="card-header">
              <span>最新订单</span>
              <el-button type="primary" link @click="$router.push('/orders')">
                查看全部
              </el-button>
            </div>
          </template>
          <el-table :data="latestOrders" size="small" stripe>
            <el-table-column prop="order_no" label="订单号" min-width="170" show-overflow-tooltip />
            <el-table-column label="金额" width="100">
              <template #default="{ row }">
                <span class="money">{{ money(row.pay_amount || row.total_amount) }}</span>
              </template>
            </el-table-column>
            <el-table-column label="状态" width="90">
              <template #default="{ row }">
                <el-tag :type="orderStatusType(row.status)" size="small">
                  {{ orderStatusText(row.status) }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column label="下单时间" min-width="150">
              <template #default="{ row }">{{ formatTime(row.create_time) }}</template>
            </el-table-column>
          </el-table>
        </el-card>
      </el-col>
      <el-col :xs="24" :md="10">
        <el-card shadow="never">
          <template #header>
            <div class="card-header">
              <span>库存预警</span>
              <el-button type="primary" link @click="$router.push('/products')">去处理</el-button>
            </div>
          </template>
          <el-table :data="lowStock" size="small" stripe>
            <el-table-column label="商品" min-width="140">
              <template #default="{ row }">
                <div class="low-stock-item">
                  <img v-if="row.image" :src="row.image" class="table-img" alt="" />
                  <span class="low-stock-name">{{ row.name }}</span>
                </div>
              </template>
            </el-table-column>
            <el-table-column prop="stock" label="库存" width="80">
              <template #default="{ row }">
                <span class="money">{{ row.stock }}</span>
              </template>
            </el-table-column>
            <el-table-column prop="stock_warning" label="预警值" width="70" />
          </el-table>
          <el-empty v-if="!lowStock.length" description="库存充足" :image-size="60" />
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue'
import * as echarts from 'echarts'
import {
  getDashboard,
  getSalesTrend,
  getCategorySales,
  getLatestOrders,
  getLowStock
} from '../../api/dashboard'
import {
  formatTime,
  formatMoney,
  orderStatusText,
  orderStatusType
} from '../../utils/format'

const loading = ref(false)
const stat = ref({})
const latestOrders = ref([])
const lowStock = ref([])
const trendDays = ref(30)

const trendChartRef = ref(null)
const pieChartRef = ref(null)
let trendChart = null
let pieChart = null

const money = (v) => formatMoney(v)

async function loadStat() {
  stat.value = (await getDashboard()) || {}
}

async function loadTrend() {
  const data = (await getSalesTrend(trendDays.value)) || []
  const dates = data.map((d) => d.date)
  const orders = data.map((d) => Number(d.order_count || 0))
  const amounts = data.map((d) => Number(d.amount || 0))
  await nextTick()
  if (!trendChart && trendChartRef.value) {
    trendChart = echarts.init(trendChartRef.value)
  }
  if (!trendChart) return
  trendChart.setOption({
    tooltip: { trigger: 'axis', axisPointer: { type: 'cross' } },
    legend: { data: ['订单数', '销售额'] },
    grid: { left: 50, right: 60, top: 40, bottom: 30 },
    xAxis: { type: 'category', data: dates, boundaryGap: true },
    yAxis: [
      { type: 'value', name: '订单数', minInterval: 1 },
      { type: 'value', name: '销售额(元)', axisLabel: { formatter: '{value}' } }
    ],
    series: [
      {
        name: '订单数',
        type: 'bar',
        barMaxWidth: 22,
        itemStyle: { color: '#f5a623' },
        data: orders
      },
      {
        name: '销售额',
        type: 'line',
        smooth: true,
        yAxisIndex: 1,
        itemStyle: { color: '#d4380d' },
        lineStyle: { width: 2.5, color: '#d4380d' },
        areaStyle: {
          color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
            { offset: 0, color: 'rgba(212,56,13,0.25)' },
            { offset: 1, color: 'rgba(212,56,13,0.02)' }
          ])
        },
        data: amounts
      }
    ]
  })
}

async function loadPie() {
  const data = (await getCategorySales(30)) || []
  await nextTick()
  if (!pieChart && pieChartRef.value) {
    pieChart = echarts.init(pieChartRef.value)
  }
  if (!pieChart) return
  pieChart.setOption({
    tooltip: { trigger: 'item', formatter: '{b}: ¥{c} ({d}%)' },
    legend: { orient: 'vertical', right: 0, top: 'center', type: 'scroll' },
    series: [
      {
        name: '分类销售',
        type: 'pie',
        radius: ['40%', '68%'],
        center: ['38%', '50%'],
        avoidLabelOverlap: true,
        itemStyle: { borderRadius: 6, borderColor: '#fff', borderWidth: 2 },
        label: { show: false },
        emphasis: {
          label: { show: true, fontSize: 14, fontWeight: 'bold' }
        },
        data: data.map((d) => ({ name: d.name || d.category_name, value: Number(d.amount || 0) }))
      }
    ]
  })
}

function handleResize() {
  trendChart && trendChart.resize()
  pieChart && pieChart.resize()
}

onMounted(async () => {
  loading.value = true
  try {
    await Promise.all([
      loadStat(),
      loadTrend(),
      loadPie(),
      getLatestOrders().then((d) => {
        latestOrders.value = d || []
      }),
      getLowStock().then((d) => {
        lowStock.value = d || []
      })
    ])
  } finally {
    loading.value = false
  }
  window.addEventListener('resize', handleResize)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', handleResize)
  if (trendChart) {
    trendChart.dispose()
    trendChart = null
  }
  if (pieChart) {
    pieChart.dispose()
    pieChart = null
  }
})
</script>

<style scoped>
.stat-row {
  margin-bottom: 16px;
}

.chart-row {
  margin-bottom: 16px;
}

.chart-box {
  width: 100%;
  height: 340px;
}

.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.low-stock-item {
  display: flex;
  align-items: center;
  gap: 8px;
}

.low-stock-item .table-img {
  width: 36px;
  height: 36px;
}

.low-stock-name {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>

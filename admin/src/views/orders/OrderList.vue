<template>
  <div class="page-container">
    <!-- 筛选区 -->
    <el-card shadow="never" class="filter-card">
      <el-tabs v-model="query.status" @tab-change="handleTabChange">
        <el-tab-pane label="全部" name="all" />
        <el-tab-pane label="待支付" name="0" />
        <el-tab-pane label="待发货" name="1" />
        <el-tab-pane label="已发货" name="2" />
        <el-tab-pane label="已完成" name="3" />
        <el-tab-pane label="已取消" name="4" />
        <el-tab-pane label="退款中" name="6" />
        <el-tab-pane label="已退款" name="7" />
      </el-tabs>
      <el-form inline @submit.prevent>
        <el-form-item label="订单号">
          <el-input
            v-model="query.order_no"
            placeholder="输入订单号"
            clearable
            style="width: 200px"
            @keyup.enter="handleSearch"
          />
        </el-form-item>
        <el-form-item label="订单类型">
          <el-select v-model="query.type" placeholder="全部" clearable style="width: 130px">
            <el-option label="普通订单" value="normal" />
            <el-option label="拼团订单" value="group_buy" />
          </el-select>
        </el-form-item>
        <el-form-item label="日期">
          <el-date-picker
            v-model="dateRange"
            type="daterange"
            range-separator="至"
            start-placeholder="开始日期"
            end-placeholder="结束日期"
            value-format="YYYY-MM-DD"
            style="width: 260px"
          />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" :icon="Search" @click="handleSearch">搜索</el-button>
          <el-button :icon="Refresh" @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 汇总条 -->
    <el-card shadow="never" class="summary-card">
      <div class="summary-bar">
        <div class="summary-item">
          <span class="summary-label">订单数</span>
          <span class="summary-value">{{ summary.order_count || 0 }}</span>
        </div>
        <el-divider direction="vertical" />
        <div class="summary-item">
          <span class="summary-label">订单总额</span>
          <span class="summary-value money">{{ money(summary.total_amount) }}</span>
        </div>
        <el-divider direction="vertical" />
        <div class="summary-item">
          <span class="summary-label">实付金额</span>
          <span class="summary-value money">{{ money(summary.pay_amount) }}</span>
        </div>
      </div>
    </el-card>

    <!-- 订单表格 -->
    <el-card shadow="never">
      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column prop="order_no" label="订单号" min-width="180" show-overflow-tooltip>
          <template #default="{ row }">
            <el-link type="primary" @click="$router.push(`/orders/${row.id}`)">
              {{ row.order_no }}
            </el-link>
          </template>
        </el-table-column>
        <el-table-column label="用户" min-width="140">
          <template #default="{ row }">
            <div>{{ row.user_nickname || row.nickname || '-' }}</div>
            <div class="sub-text">{{ row.user_phone || row.phone || '' }}</div>
          </template>
        </el-table-column>
        <el-table-column label="类型" width="90">
          <template #default="{ row }">
            <el-tag v-if="row.type === 'group_buy'" type="warning" size="small">拼团</el-tag>
            <el-tag v-else type="info" size="small" effect="plain">普通</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="金额" width="110">
          <template #default="{ row }">
            <div class="money">{{ money(row.pay_amount || row.total_amount) }}</div>
            <div v-if="row.total_amount && row.pay_amount && row.total_amount != row.pay_amount" class="sub-text strike">
              {{ money(row.total_amount) }}
            </div>
          </template>
        </el-table-column>
        <el-table-column label="支付方式" width="90">
          <template #default="{ row }">{{ payTypeText(row.pay_type) }}</template>
        </el-table-column>
        <el-table-column label="状态" width="90">
          <template #default="{ row }">
            <el-tag :type="orderStatusType(row.status)" size="small">
              {{ orderStatusText(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="下单时间" min-width="160">
          <template #default="{ row }">{{ formatTime(row.create_time) }}</template>
        </el-table-column>
        <el-table-column label="操作" width="180" fixed="right">
          <template #default="{ row }">
            <el-button
              type="primary"
              link
              size="small"
              @click="$router.push(`/orders/${row.id}`)"
            >
              详情
            </el-button>
            <el-button
              v-if="row.status == 1"
              type="success"
              link
              size="small"
              @click="openDeliver(row)"
            >
              发货
            </el-button>
            <el-button
              v-if="row.status == 0 || row.status == 1"
              type="danger"
              link
              size="small"
              @click="handleCancel(row)"
            >
              取消
            </el-button>
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

    <!-- 发货弹窗 -->
    <el-dialog v-model="deliverVisible" title="订单发货" width="480px">
      <el-form ref="deliverFormRef" :model="deliverForm" :rules="deliverRules" label-width="100px">
        <el-form-item label="订单号">
          <span>{{ deliverForm.orderNo }}</span>
        </el-form-item>
        <el-form-item label="发货方式">
          <el-radio-group v-model="deliverForm.mode">
            <el-radio value="express">快递配送</el-radio>
            <el-radio value="self">自配送</el-radio>
          </el-radio-group>
        </el-form-item>
        <template v-if="deliverForm.mode === 'express'">
          <el-form-item label="快递公司" prop="company">
            <el-select v-model="deliverForm.company" filterable allow-create style="width: 100%">
              <el-option label="顺丰速运" value="顺丰速运" />
              <el-option label="中通快递" value="中通快递" />
              <el-option label="圆通速递" value="圆通速递" />
              <el-option label="申通快递" value="申通快递" />
              <el-option label="韵达快递" value="韵达快递" />
              <el-option label="邮政EMS" value="邮政EMS" />
              <el-option label="京东物流" value="京东物流" />
            </el-select>
          </el-form-item>
          <el-form-item label="快递单号" prop="tracking_no">
            <el-input v-model="deliverForm.tracking_no" placeholder="请输入快递单号" />
          </el-form-item>
        </template>
        <template v-else>
          <el-form-item label="配送员姓名" prop="courier_name">
            <el-input v-model="deliverForm.courier_name" placeholder="请输入配送员姓名" />
          </el-form-item>
          <el-form-item label="配送员电话" prop="courier_phone">
            <el-input v-model="deliverForm.courier_phone" placeholder="请输入配送员电话" />
          </el-form-item>
        </template>
      </el-form>
      <template #footer>
        <el-button @click="deliverVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitting" @click="submitDeliver">确认发货</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Search, Refresh } from '@element-plus/icons-vue'
import { getOrders, deliverOrder, cancelOrder } from '../../api/order'
import {
  formatTime,
  formatMoney,
  orderStatusText,
  orderStatusType,
  payTypeText
} from '../../utils/format'

const money = (v) => formatMoney(v)

const loading = ref(false)
const submitting = ref(false)
const list = ref([])
const total = ref(0)
const summary = ref({})
const dateRange = ref(null)

const query = reactive({
  status: 'all',
  order_no: '',
  type: '',
  page: 1,
  page_size: 15
})

const deliverVisible = ref(false)
const deliverFormRef = ref(null)
const deliverForm = reactive({
  orderId: null,
  orderNo: '',
  mode: 'express',
  company: '',
  tracking_no: '',
  courier_name: '',
  courier_phone: ''
})

const deliverRules = {
  company: [{ required: true, message: '请选择或输入快递公司', trigger: 'change' }],
  tracking_no: [{ required: true, message: '请输入快递单号', trigger: 'blur' }],
  courier_name: [{ required: true, message: '请输入配送员姓名', trigger: 'blur' }],
  courier_phone: [
    { required: true, message: '请输入配送员电话', trigger: 'blur' },
    { pattern: /^1[3-9]\d{9}$/, message: '手机号格式不正确', trigger: 'blur' }
  ]
}

async function loadList() {
  loading.value = true
  try {
    const params = { page: query.page, page_size: query.page_size }
    if (query.status !== 'all') params.status = query.status
    if (query.order_no) params.order_no = query.order_no
    if (query.type) params.type = query.type
    if (dateRange.value && dateRange.value.length === 2) {
      params.start_date = dateRange.value[0]
      params.end_date = dateRange.value[1]
    }
    const data = await getOrders(params)
    list.value = (data && data.list) || []
    total.value = (data && data.total) || 0
    summary.value = (data && data.summary) || {}
  } finally {
    loading.value = false
  }
}

function handleTabChange() {
  query.page = 1
  loadList()
}

function handleSearch() {
  query.page = 1
  loadList()
}

function handleReset() {
  query.order_no = ''
  query.type = ''
  dateRange.value = null
  query.page = 1
  loadList()
}

function openDeliver(row) {
  deliverForm.orderId = row.id
  deliverForm.orderNo = row.order_no
  deliverForm.mode = 'express'
  deliverForm.company = ''
  deliverForm.tracking_no = ''
  deliverForm.courier_name = ''
  deliverForm.courier_phone = ''
  deliverVisible.value = true
}

async function submitDeliver() {
  if (!deliverFormRef.value) return
  await deliverFormRef.value.validate(async (valid) => {
    if (!valid) return
    submitting.value = true
    try {
      const payload =
        deliverForm.mode === 'express'
          ? { company: deliverForm.company, tracking_no: deliverForm.tracking_no }
          : {
              courier_name: deliverForm.courier_name,
              courier_phone: deliverForm.courier_phone
            }
      await deliverOrder(deliverForm.orderId, payload)
      ElMessage.success('发货成功')
      deliverVisible.value = false
      loadList()
    } finally {
      submitting.value = false
    }
  })
}

async function handleCancel(row) {
  let reason = ''
  try {
    const { value } = await ElMessageBox.prompt('请输入取消原因', '取消订单', {
      confirmButtonText: '确定取消',
      cancelButtonText: '返回',
      inputPlaceholder: '取消原因（必填）',
      inputValidator: (v) => !!(v && v.trim()) || '请输入取消原因'
    })
    reason = (value || '').trim()
  } catch (e) {
    return
  }
  await cancelOrder(row.id, { reason })
  ElMessage.success('订单已取消')
  loadList()
}

onMounted(loadList)
</script>

<style scoped>
.summary-card {
  margin-bottom: 16px;
}

.summary-bar {
  display: flex;
  align-items: center;
  gap: 24px;
}

.summary-item {
  display: flex;
  align-items: baseline;
  gap: 10px;
}

.summary-label {
  font-size: 13px;
  color: #999;
}

.summary-value {
  font-size: 20px;
  font-weight: 600;
  color: #262626;
}

.sub-text {
  font-size: 12px;
  color: #999;
}

.strike {
  text-decoration: line-through;
}
</style>

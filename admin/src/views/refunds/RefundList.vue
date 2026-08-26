<template>
  <div class="page-container">
    <!-- 筛选 -->
    <el-card shadow="never" class="filter-card">
      <el-form inline @submit.prevent>
        <el-form-item label="状态">
          <el-radio-group v-model="query.status" @change="handleSearch">
            <el-radio-button value="">全部</el-radio-button>
            <el-radio-button value="0">待处理</el-radio-button>
            <el-radio-button value="1">已同意</el-radio-button>
            <el-radio-button value="2">已拒绝</el-radio-button>
          </el-radio-group>
        </el-form-item>
        <el-form-item>
          <el-button :icon="Refresh" @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card shadow="never">
      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column prop="order_no" label="订单号" min-width="180" show-overflow-tooltip>
          <template #default="{ row }">
            <el-link type="primary" @click="$router.push(`/orders/${row.order_id}`)">
              {{ row.order_no }}
            </el-link>
          </template>
        </el-table-column>
        <el-table-column label="用户" min-width="120">
          <template #default="{ row }">
            {{ row.user_nickname || row.nickname || row.user_id }}
          </template>
        </el-table-column>
        <el-table-column label="退款金额" width="110">
          <template #default="{ row }">
            <span class="money">{{ money(row.amount) }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="reason" label="退款原因" min-width="160" show-overflow-tooltip />
        <el-table-column label="状态" width="90">
          <template #default="{ row }">
            <el-tag :type="refundStatusType(row.status)" size="small">
              {{ refundStatusText(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="申请时间" min-width="160">
          <template #default="{ row }">{{ formatTime(row.create_time) }}</template>
        </el-table-column>
        <el-table-column label="处理时间" min-width="160">
          <template #default="{ row }">{{ formatTime(row.handle_time) }}</template>
        </el-table-column>
        <el-table-column label="操作" width="140" fixed="right">
          <template #default="{ row }">
            <template v-if="row.status == 0">
              <el-button type="success" link size="small" @click="openHandle(row, 'approve')">
                同意
              </el-button>
              <el-button type="danger" link size="small" @click="openHandle(row, 'reject')">
                拒绝
              </el-button>
            </template>
            <span v-else class="sub-text">已处理</span>
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

    <!-- 处理弹窗 -->
    <el-dialog
      v-model="handleVisible"
      :title="handleForm.action === 'approve' ? '同意退款' : '拒绝退款'"
      width="460px"
    >
      <div class="handle-tip">
        <template v-if="handleForm.action === 'approve'">
          同意后，退款金额
          <span class="money">{{ money(handleForm.amount) }}</span>
          将原路退回用户，订单将变为「已退款」状态。
        </template>
        <template v-else> 拒绝退款后，订单将恢复原状态继续履约。请填写拒绝原因。 </template>
      </div>
      <el-form
        v-if="handleForm.action === 'reject'"
        ref="rejectFormRef"
        :model="handleForm"
        :rules="rejectRules"
        label-width="90px"
      >
        <el-form-item label="拒绝原因" prop="reject_reason">
          <el-input
            v-model="handleForm.reject_reason"
            type="textarea"
            :rows="3"
            placeholder="请输入拒绝原因（必填）"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="handleVisible = false">取消</el-button>
        <el-button
          :type="handleForm.action === 'approve' ? 'success' : 'danger'"
          :loading="submitting"
          @click="submitHandle"
        >
          {{ handleForm.action === 'approve' ? '确认同意' : '确认拒绝' }}
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Refresh } from '@element-plus/icons-vue'
import { getRefunds, handleRefund } from '../../api/order'
import { formatTime, formatMoney } from '../../utils/format'

const money = (v) => formatMoney(v)

const loading = ref(false)
const submitting = ref(false)
const list = ref([])
const total = ref(0)

const query = reactive({
  status: '',
  page: 1,
  page_size: 15
})

const handleVisible = ref(false)
const rejectFormRef = ref(null)
const handleForm = reactive({
  id: null,
  amount: 0,
  action: 'approve',
  reject_reason: ''
})

const rejectRules = {
  reject_reason: [{ required: true, message: '请输入拒绝原因', trigger: 'blur' }]
}

function refundStatusText(s) {
  const map = { 0: '待处理', 1: '已同意', 2: '已拒绝' }
  return map[s] || '未知'
}

function refundStatusType(s) {
  const map = { 0: 'warning', 1: 'success', 2: 'danger' }
  return map[s] || 'info'
}

async function loadList() {
  loading.value = true
  try {
    const params = { page: query.page, page_size: query.page_size }
    if (query.status !== '') params.status = query.status
    const data = await getRefunds(params)
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
  query.status = ''
  query.page = 1
  loadList()
}

function openHandle(row, action) {
  handleForm.id = row.id
  handleForm.amount = row.amount
  handleForm.action = action
  handleForm.reject_reason = ''
  handleVisible.value = true
}

async function submitHandle() {
  if (handleForm.action === 'reject') {
    if (!rejectFormRef.value) return
    const valid = await rejectFormRef.value.validate().catch(() => false)
    if (!valid) return
  }
  submitting.value = true
  try {
    const payload =
      handleForm.action === 'approve'
        ? { action: 'approve' }
        : { action: 'reject', reject_reason: handleForm.reject_reason }
    await handleRefund(handleForm.id, payload)
    ElMessage.success(handleForm.action === 'approve' ? '已同意退款' : '已拒绝退款')
    handleVisible.value = false
    loadList()
  } finally {
    submitting.value = false
  }
}

onMounted(loadList)
</script>

<style scoped>
.sub-text {
  font-size: 12px;
  color: #999;
}

.handle-tip {
  font-size: 14px;
  color: #555;
  line-height: 1.8;
  margin-bottom: 16px;
}
</style>

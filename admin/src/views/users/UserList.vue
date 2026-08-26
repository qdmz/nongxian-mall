<template>
  <div class="page-container">
    <!-- 搜索表单 -->
    <el-card shadow="never" class="filter-card">
      <el-form inline @submit.prevent>
        <el-form-item label="关键词">
          <el-input
            v-model="query.keyword"
            placeholder="昵称/手机号/邮箱"
            clearable
            style="width: 220px"
            @keyup.enter="handleSearch"
          />
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="query.status" placeholder="全部" clearable style="width: 120px">
            <el-option label="正常" :value="1" />
            <el-option label="禁用" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" :icon="Search" @click="handleSearch">搜索</el-button>
          <el-button :icon="Refresh" @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 用户表格 -->
    <el-card shadow="never">
      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column label="用户" min-width="200">
          <template #default="{ row }">
            <div class="user-cell">
              <el-avatar :size="36" :src="row.avatar || undefined" class="user-avatar">
                {{ (row.nickname || row.phone || '用户').charAt(0) }}
              </el-avatar>
              <div>
                <div class="user-nickname">{{ row.nickname || '-' }}</div>
                <div class="user-phone">{{ row.phone || row.email || '-' }}</div>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="余额" width="110">
          <template #default="{ row }">
            <span class="money">{{ money(row.balance) }}</span>
          </template>
        </el-table-column>
        <el-table-column label="订单数" prop="order_count" width="90" />
        <el-table-column label="状态" width="90">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'danger'" size="small">
              {{ row.status === 1 ? '正常' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="注册时间" min-width="160">
          <template #default="{ row }">{{ formatTime(row.create_time) }}</template>
        </el-table-column>
        <el-table-column label="操作" width="240" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="openDetail(row)">详情</el-button>
            <el-button
              :type="row.status === 1 ? 'danger' : 'success'"
              link
              size="small"
              @click="toggleStatus(row)"
            >
              {{ row.status === 1 ? '禁用' : '启用' }}
            </el-button>
            <el-button type="warning" link size="small" @click="openBalance(row)">调余额</el-button>
            <el-button type="info" link size="small" @click="openNotify(row)">发站内信</el-button>
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

    <!-- 用户详情抽屉 -->
    <el-drawer v-model="detailVisible" title="用户详情" size="560px">
      <div v-loading="detailLoading">
        <template v-if="detail">
          <el-descriptions :column="2" border size="small" title="基本信息">
            <el-descriptions-item label="ID">{{ detail.id }}</el-descriptions-item>
            <el-descriptions-item label="昵称">{{ detail.nickname || '-' }}</el-descriptions-item>
            <el-descriptions-item label="真实姓名">
              {{ detail.real_name || '-' }}
            </el-descriptions-item>
            <el-descriptions-item label="手机号">{{ detail.phone || '-' }}</el-descriptions-item>
            <el-descriptions-item label="邮箱">{{ detail.email || '-' }}</el-descriptions-item>
            <el-descriptions-item label="余额">
              <span class="money">{{ money(detail.balance) }}</span>
            </el-descriptions-item>
            <el-descriptions-item label="状态">
              <el-tag :type="detail.status === 1 ? 'success' : 'danger'" size="small">
                {{ detail.status === 1 ? '正常' : '禁用' }}
              </el-tag>
            </el-descriptions-item>
            <el-descriptions-item label="注册时间">
              {{ formatTime(detail.create_time) }}
            </el-descriptions-item>
          </el-descriptions>

          <h4 class="drawer-section-title">近期订单</h4>
          <el-table :data="detail.orders || detail.recent_orders || []" size="small" border>
            <el-table-column prop="order_no" label="订单号" min-width="160" show-overflow-tooltip />
            <el-table-column label="金额" width="90">
              <template #default="{ row }">{{ money(row.pay_amount || row.total_amount) }}</template>
            </el-table-column>
            <el-table-column label="状态" width="80">
              <template #default="{ row }">{{ orderStatusText(row.status) }}</template>
            </el-table-column>
          </el-table>
          <el-empty
            v-if="!(detail.orders || detail.recent_orders || []).length"
            description="暂无订单"
            :image-size="60"
          />

          <h4 class="drawer-section-title">钱包流水</h4>
          <el-table :data="detail.wallet_logs || detail.transactions || []" size="small" border>
            <el-table-column label="时间" min-width="150">
              <template #default="{ row }">{{ formatTime(row.create_time) }}</template>
            </el-table-column>
            <el-table-column label="金额" width="100">
              <template #default="{ row }">
                <span :class="Number(row.amount) >= 0 ? 'money' : 'text-green'">
                  {{ Number(row.amount) >= 0 ? '+' : '' }}{{ money(row.amount) }}
                </span>
              </template>
            </el-table-column>
            <el-table-column
              prop="remark"
              label="备注"
              min-width="120"
              show-overflow-tooltip
            />
          </el-table>
          <el-empty
            v-if="!(detail.wallet_logs || detail.transactions || []).length"
            description="暂无流水"
            :image-size="60"
          />
        </template>
      </div>
    </el-drawer>

    <!-- 调整余额弹窗 -->
    <el-dialog v-model="balanceVisible" title="调整余额" width="440px">
      <el-form ref="balanceFormRef" :model="balanceForm" :rules="balanceRules" label-width="90px">
        <el-form-item label="当前余额">
          <span class="money">{{ money(balanceForm.current) }}</span>
        </el-form-item>
        <el-form-item label="调整金额" prop="amount">
          <el-input-number
            v-model="balanceForm.amount"
            :precision="2"
            :step="1"
            placeholder="正数加钱，负数扣钱"
            style="width: 100%"
          />
          <div class="form-tip">正数为加钱，负数为扣钱</div>
        </el-form-item>
        <el-form-item label="备注" prop="remark">
          <el-input
            v-model="balanceForm.remark"
            type="textarea"
            :rows="2"
            placeholder="请输入调整原因（必填）"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="balanceVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitting" @click="submitBalance">确定</el-button>
      </template>
    </el-dialog>

    <!-- 发站内信弹窗 -->
    <el-dialog v-model="notifyVisible" title="发送站内信" width="460px">
      <el-form ref="notifyFormRef" :model="notifyForm" :rules="notifyRules" label-width="70px">
        <el-form-item label="接收人">
          <span>{{ notifyForm.userLabel }}</span>
        </el-form-item>
        <el-form-item label="标题" prop="title">
          <el-input v-model="notifyForm.title" placeholder="消息标题" maxlength="50" />
        </el-form-item>
        <el-form-item label="内容" prop="content">
          <el-input
            v-model="notifyForm.content"
            type="textarea"
            :rows="5"
            placeholder="消息内容"
            maxlength="500"
            show-word-limit
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="notifyVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitting" @click="submitNotify">发送</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Search, Refresh } from '@element-plus/icons-vue'
import { getUsers, getUserDetail, updateUser, adjustBalance, sendNotification } from '../../api/user'
import { formatTime, formatMoney, orderStatusText } from '../../utils/format'

const money = (v) => formatMoney(v)

const loading = ref(false)
const submitting = ref(false)
const list = ref([])
const total = ref(0)

const query = reactive({
  keyword: '',
  status: null,
  page: 1,
  page_size: 15
})

// 详情抽屉
const detailVisible = ref(false)
const detailLoading = ref(false)
const detail = ref(null)

// 调余额
const balanceVisible = ref(false)
const balanceFormRef = ref(null)
const balanceForm = reactive({
  userId: null,
  current: 0,
  amount: 0,
  remark: ''
})
const balanceRules = {
  amount: [{ required: true, message: '请输入调整金额', trigger: 'blur' }],
  remark: [{ required: true, message: '请填写备注', trigger: 'blur' }]
}

// 站内信
const notifyVisible = ref(false)
const notifyFormRef = ref(null)
const notifyForm = reactive({
  userId: null,
  userLabel: '',
  title: '',
  content: ''
})
const notifyRules = {
  title: [{ required: true, message: '请输入标题', trigger: 'blur' }],
  content: [{ required: true, message: '请输入内容', trigger: 'blur' }]
}

async function loadList() {
  loading.value = true
  try {
    const params = { page: query.page, page_size: query.page_size }
    if (query.keyword) params.keyword = query.keyword
    if (query.status !== null && query.status !== '') params.status = query.status
    const data = await getUsers(params)
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
  query.status = null
  query.page = 1
  loadList()
}

async function openDetail(row) {
  detailVisible.value = true
  detailLoading.value = true
  detail.value = null
  try {
    detail.value = await getUserDetail(row.id)
  } finally {
    detailLoading.value = false
  }
}

async function toggleStatus(row) {
  const nextStatus = row.status === 1 ? 0 : 1
  const actionText = nextStatus === 1 ? '启用' : '禁用'
  try {
    await ElMessageBox.confirm(
      `确定要${actionText}用户「${row.nickname || row.phone || row.id}」吗？`,
      '提示',
      { confirmButtonText: '确定', cancelButtonText: '取消', type: 'warning' }
    )
  } catch (e) {
    return
  }
  await updateUser(row.id, { status: nextStatus })
  ElMessage.success(`${actionText}成功`)
  loadList()
}

function openBalance(row) {
  balanceForm.userId = row.id
  balanceForm.current = Number(row.balance || 0)
  balanceForm.amount = 0
  balanceForm.remark = ''
  balanceVisible.value = true
}

async function submitBalance() {
  if (!balanceFormRef.value) return
  await balanceFormRef.value.validate(async (valid) => {
    if (!valid) return
    submitting.value = true
    try {
      await adjustBalance(balanceForm.userId, {
        amount: balanceForm.amount,
        remark: balanceForm.remark
      })
      ElMessage.success('余额调整成功')
      balanceVisible.value = false
      loadList()
    } finally {
      submitting.value = false
    }
  })
}

function openNotify(row) {
  notifyForm.userId = row.id
  notifyForm.userLabel = row.nickname || row.phone || `用户#${row.id}`
  notifyForm.title = ''
  notifyForm.content = ''
  notifyVisible.value = true
}

async function submitNotify() {
  if (!notifyFormRef.value) return
  await notifyFormRef.value.validate(async (valid) => {
    if (!valid) return
    submitting.value = true
    try {
      await sendNotification({
        user_id: notifyForm.userId,
        title: notifyForm.title,
        content: notifyForm.content
      })
      ElMessage.success('站内信发送成功')
      notifyVisible.value = false
    } finally {
      submitting.value = false
    }
  })
}

onMounted(loadList)
</script>

<style scoped>
.user-cell {
  display: flex;
  align-items: center;
  gap: 10px;
}

.user-avatar {
  background-color: #d4380d;
  color: #fff;
  flex-shrink: 0;
}

.user-nickname {
  font-size: 14px;
  color: #262626;
}

.user-phone {
  font-size: 12px;
  color: #999;
}

.drawer-section-title {
  margin: 20px 0 10px;
  font-size: 14px;
  color: #262626;
  border-left: 3px solid #d4380d;
  padding-left: 8px;
}

.form-tip {
  font-size: 12px;
  color: #999;
  line-height: 1.6;
}

.text-green {
  color: #52c41a;
  font-weight: 600;
}
</style>

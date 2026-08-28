<template>
  <div class="page-container">
    <!-- 支付配置表单 -->
    <el-card shadow="never" class="filter-card">
      <template #header>
        <div class="card-header">
          <span>支付配置（易支付）</span>
          <el-button type="primary" :loading="testing" @click="handleTestPay">
            测试连通
          </el-button>
        </div>
      </template>

      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="130px"
        style="max-width: 640px"
        v-loading="loading"
      >
        <el-form-item label="启用在线支付">
          <el-switch v-model="form.pay_enabled" :active-value="1" :inactive-value="0" />
        </el-form-item>
        <el-form-item label="网关地址" prop="pay_gateway">
          <el-input v-model="form.pay_gateway" placeholder="如 https://pay.example.com" />
        </el-form-item>
        <el-form-item label="商户PID" prop="pay_pid">
          <el-input v-model="form.pay_pid" placeholder="易支付商户 ID" />
        </el-form-item>
        <el-form-item label="商户密钥" prop="pay_key">
          <el-input
            v-model="form.pay_key"
            type="password"
            show-password
            :placeholder="keyIsSet ? '已设置，留空表示不修改' : '未设置'"
          />
          <div class="form-tip">
            <el-tag :type="keyIsSet ? 'success' : 'info'" size="small">
              {{ keyIsSet ? '已设置' : '未设置' }}
            </el-tag>
            留空提交表示不修改原有密钥
          </div>
        </el-form-item>
        <el-form-item label="默认支付方式">
          <el-select v-model="form.pay_default_type" style="width: 240px">
            <el-option label="支付宝" value="alipay" />
            <el-option label="微信" value="wxpay" />
            <el-option label="QQ钱包" value="qqpay" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" :loading="saving" @click="handleSave">保存配置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 支付记录 -->
    <el-card shadow="never">
      <template #header>
        <div class="card-header">
          <span>支付记录</span>
          <el-radio-group v-model="recordStatus" size="small" @change="loadRecords">
            <el-radio-button value="">全部</el-radio-button>
            <el-radio-button value="success">成功</el-radio-button>
            <el-radio-button value="pending">待支付</el-radio-button>
            <el-radio-button value="failed">失败</el-radio-button>
          </el-radio-group>
        </div>
      </template>
      <el-table :data="records" v-loading="recordLoading" stripe size="small">
        <el-table-column prop="trade_no" label="支付单号" min-width="180" show-overflow-tooltip />
        <el-table-column prop="order_no" label="订单号" min-width="180" show-overflow-tooltip />
        <el-table-column label="金额" width="100">
          <template #default="{ row }">
            <span class="money">{{ money(row.amount) }}</span>
          </template>
        </el-table-column>
        <el-table-column label="支付方式" width="90">
          <template #default="{ row }">{{ payTypeText(row.pay_type) }}</template>
        </el-table-column>
        <el-table-column label="状态" width="90">
          <template #default="{ row }">
            <el-tag :type="recordStatusType(row.status)" size="small">
              {{ recordStatusText(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="支付时间" min-width="160">
          <template #default="{ row }">{{ formatTime(row.pay_time || row.create_time) }}</template>
        </el-table-column>
      </el-table>
      <div class="pagination-wrap">
        <el-pagination
          v-model:current-page="recordQuery.page"
          v-model:page-size="recordQuery.page_size"
          :total="recordTotal"
          :page-sizes="[10, 15, 30]"
          layout="total, prev, pager, next"
          @current-change="loadRecords"
        />
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getConfig, saveConfig, testPay, getPaymentRecords } from '../../api/config'
import { formatTime, formatMoney, payTypeText } from '../../utils/format'

const money = (v) => formatMoney(v)

const loading = ref(false)
const saving = ref(false)
const testing = ref(false)
const keyIsSet = ref(false)
const formRef = ref(null)

const form = reactive({
  pay_enabled: 0,
  pay_gateway: '',
  pay_pid: '',
  pay_key: '',
  pay_default_type: 'alipay'
})

const rules = {
  pay_gateway: [{ required: true, message: '请输入网关地址', trigger: 'blur' }],
  pay_pid: [{ required: true, message: '请输入商户PID', trigger: 'blur' }]
}

// 支付记录
const records = ref([])
const recordTotal = ref(0)
const recordLoading = ref(false)
const recordStatus = ref('')
const recordQuery = reactive({ page: 1, page_size: 15 })

function recordStatusText(s) {
  const map = { success: '成功', pending: '待支付', failed: '失败', refunded: '已退款' }
  return map[s] || (s === 1 ? '成功' : s === 0 ? '待支付' : s) || '未知'
}

function recordStatusType(s) {
  const map = { success: 'success', pending: 'warning', failed: 'danger', refunded: 'info' }
  return map[s] || 'info'
}

async function loadConfig() {
  loading.value = true
  try {
    const data = await getConfig('pay')
    const groupArr = (data && data.pay) || []
    const cfg = {}
    let keyIsSet = false
    groupArr.forEach(item => {
      cfg[item.key] = item.value
      if (item.type === 'password') keyIsSet = !!item.is_set
    })
    // 数据库key → 前端key 映射
    form.pay_enabled = Number(cfg.pay_enabled || 0)
    form.pay_gateway = cfg.epay_api_url || ''
    form.pay_pid = cfg.epay_pid || ''
    form.pay_key = ''
    form.pay_default_type = cfg.epay_pay_type || 'alipay'
    keyIsSet.value = keyIsSet
  } finally {
    loading.value = false
  }
}

async function handleSave() {
  if (!formRef.value) return
  await formRef.value.validate(async (valid) => {
    if (!valid) return
    saving.value = true
    try {
      const configs = {
        pay_enabled: form.pay_enabled,
        epay_api_url: form.pay_gateway,
        epay_pid: form.pay_pid,
        epay_pay_type: form.pay_default_type
      }
      if (form.pay_key) {
        configs.epay_key = form.pay_key
      }
      await saveConfig('pay', configs)
      ElMessage.success('支付配置保存成功')
      form.pay_key = ''
      loadConfig()
    } finally {
      saving.value = false
    }
  })
}

async function handleTestPay() {
  testing.value = true
  try {
    const data = await testPay()
    ElMessage.success((data && data.msg) || '支付网关连通正常')
  } finally {
    testing.value = false
  }
}

async function loadRecords() {
  recordLoading.value = true
  try {
    const params = { page: recordQuery.page, page_size: recordQuery.page_size }
    if (recordStatus.value) params.status = recordStatus.value
    const data = await getPaymentRecords(params)
    records.value = (data && data.list) || []
    recordTotal.value = (data && data.total) || 0
  } finally {
    recordLoading.value = false
  }
}

onMounted(() => {
  loadConfig()
  loadRecords()
})
</script>

<style scoped>
.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.form-tip {
  font-size: 12px;
  color: #999;
  margin-top: 4px;
  display: flex;
  align-items: center;
  gap: 6px;
}
</style>

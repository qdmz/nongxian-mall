<template>
  <div class="page-container">
    <!-- SMTP 配置表单 -->
    <el-card shadow="never" class="filter-card">
      <template #header>
        <div class="card-header">
          <span>邮件配置（SMTP）</span>
          <el-button type="primary" :loading="testing" @click="openTest">发送测试邮件</el-button>
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
        <el-form-item label="启用邮件服务">
          <el-switch v-model="form.smtp_enabled" :active-value="1" :inactive-value="0" />
        </el-form-item>
        <el-form-item label="SMTP 服务器" prop="smtp_host">
          <el-input v-model="form.smtp_host" placeholder="如 smtp.qq.com" />
        </el-form-item>
        <el-row :gutter="12">
          <el-col :span="12">
            <el-form-item label="端口" prop="smtp_port">
              <el-input-number v-model="form.smtp_port" :min="1" :max="65535" style="width: 100%" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="SSL 加密">
              <el-switch v-model="form.smtp_ssl" :active-value="1" :inactive-value="0" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="SMTP 账号" prop="smtp_username">
          <el-input v-model="form.smtp_username" placeholder="如 service@example.com" />
        </el-form-item>
        <el-form-item label="SMTP 授权码">
          <el-input
            v-model="form.smtp_password"
            type="password"
            show-password
            :placeholder="pwdIsSet ? '已设置，留空表示不修改' : '未设置'"
          />
          <div class="form-tip">
            <el-tag :type="pwdIsSet ? 'success' : 'info'" size="small">
              {{ pwdIsSet ? '已设置' : '未设置' }}
            </el-tag>
            留空提交表示不修改原有授权码
          </div>
        </el-form-item>
        <el-form-item label="发件人名称">
          <el-input v-model="form.smtp_from_name" placeholder="如 田冲助农商城" />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" :loading="saving" @click="handleSave">保存配置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 发送日志 -->
    <el-card shadow="never">
      <template #header>
        <div class="card-header"><span>邮件发送日志</span></div>
      </template>
      <el-table :data="logs" v-loading="logLoading" stripe size="small">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="to" label="收件邮箱" min-width="180" show-overflow-tooltip />
        <el-table-column prop="subject" label="主题" min-width="160" show-overflow-tooltip />
        <el-table-column label="状态" width="90">
          <template #default="{ row }">
            <el-tag :type="Number(row.status) === 1 || row.status === 'success' ? 'success' : 'danger'" size="small">
              {{ Number(row.status) === 1 || row.status === 'success' ? '成功' : '失败' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="error" label="错误信息" min-width="160" show-overflow-tooltip>
          <template #default="{ row }">{{ row.error || '-' }}</template>
        </el-table-column>
        <el-table-column label="发送时间" min-width="160">
          <template #default="{ row }">{{ formatTime(row.create_time) }}</template>
        </el-table-column>
      </el-table>
      <div class="pagination-wrap">
        <el-pagination
          v-model:current-page="logQuery.page"
          v-model:page-size="logQuery.page_size"
          :total="logTotal"
          :page-sizes="[10, 15, 30]"
          layout="total, prev, pager, next"
          @current-change="loadLogs"
        />
      </div>
    </el-card>

    <!-- 测试邮件弹窗 -->
    <el-dialog v-model="testVisible" title="发送测试邮件" width="440px">
      <el-form label-width="90px">
        <el-form-item label="收件邮箱">
          <el-input v-model="testTo" placeholder="请输入接收测试邮件的邮箱" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="testVisible = false">取消</el-button>
        <el-button type="primary" :loading="testing" @click="submitTest">发送</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getConfig, saveConfig, testSmtp, getConfigLogs } from '../../api/config'
import { formatTime } from '../../utils/format'

const loading = ref(false)
const saving = ref(false)
const testing = ref(false)
const pwdIsSet = ref(false)
const formRef = ref(null)

const form = reactive({
  smtp_enabled: 0,
  smtp_host: '',
  smtp_port: 465,
  smtp_ssl: 1,
  smtp_username: '',
  smtp_password: '',
  smtp_from_name: ''
})

const rules = {
  smtp_host: [{ required: true, message: '请输入 SMTP 服务器', trigger: 'blur' }],
  smtp_port: [{ required: true, message: '请输入端口', trigger: 'blur' }],
  smtp_username: [{ required: true, message: '请输入 SMTP 账号', trigger: 'blur' }]
}

// 日志
const logs = ref([])
const logTotal = ref(0)
const logLoading = ref(false)
const logQuery = reactive({ page: 1, page_size: 15 })

// 测试邮件
const testVisible = ref(false)
const testTo = ref('')

async function loadConfig() {
  loading.value = true
  try {
    const data = await getConfig('smtp')
    // data = {smtp: [{key, value, type, is_set}, ...]}
    const groupArr = (data && data.smtp) || []
    const cfg = {}
    let passwordIsSet = false
    groupArr.forEach(item => {
      cfg[item.key] = item.value
      if (item.type === 'password') {
        passwordIsSet = !!item.is_set
      }
    })
    form.smtp_enabled = Number(cfg.smtp_enabled || 0)
    form.smtp_host = cfg.smtp_host || ''
    form.smtp_port = Number(cfg.smtp_port || 465)
    form.smtp_ssl = Number(cfg.smtp_ssl === undefined ? 1 : cfg.smtp_ssl)
    form.smtp_username = cfg.smtp_username || ''
    form.smtp_password = ''
    form.smtp_from_name = cfg.smtp_from_name || ''
    pwdIsSet.value = passwordIsSet
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
        smtp_enabled: form.smtp_enabled,
        smtp_host: form.smtp_host,
        smtp_port: form.smtp_port,
        smtp_ssl: form.smtp_ssl,
        smtp_username: form.smtp_username,
        smtp_from_name: form.smtp_from_name
      }
      if (form.smtp_password) {
        configs.smtp_password = form.smtp_password
      }
      await saveConfig('smtp', configs)
      ElMessage.success('邮件配置保存成功')
      form.smtp_password = ''
      loadConfig()
    } finally {
      saving.value = false
    }
  })
}

function openTest() {
  testTo.value = ''
  testVisible.value = true
}

async function submitTest() {
  if (!testTo.value || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(testTo.value)) {
    ElMessage.warning('请输入正确的邮箱地址')
    return
  }
  testing.value = true
  try {
    await testSmtp(testTo.value)
    ElMessage.success('测试邮件已发送，请查收')
    testVisible.value = false
    loadLogs()
  } finally {
    testing.value = false
  }
}

async function loadLogs() {
  logLoading.value = true
  try {
    const data = await getConfigLogs({ type: 'email', page: logQuery.page, page_size: logQuery.page_size })
    logs.value = (data && data.list) || []
    logTotal.value = (data && data.total) || 0
  } finally {
    logLoading.value = false
  }
}

onMounted(() => {
  loadConfig()
  loadLogs()
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

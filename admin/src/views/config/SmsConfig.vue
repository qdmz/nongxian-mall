<template>
  <div class="page-container">
    <!-- 短信配置表单 -->
    <el-card shadow="never" class="filter-card">
      <template #header>
        <div class="card-header">
          <span>短信配置</span>
          <el-button type="primary" :loading="testing" @click="openTest">发送测试短信</el-button>
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
        <el-form-item label="启用短信服务">
          <el-switch v-model="form.sms_enabled" :active-value="1" :inactive-value="0" />
        </el-form-item>
        <el-form-item label="短信服务商">
          <el-select v-model="form.sms_provider" style="width: 240px">
            <el-option label="阿里云短信" value="aliyun" />
            <el-option label="腾讯云短信" value="tencent" />
          </el-select>
        </el-form-item>
        <el-form-item label="AccessKey ID" prop="sms_access_key">
          <el-input v-model="form.sms_access_key" placeholder="服务商 AccessKey ID" />
        </el-form-item>
        <el-form-item label="AccessKey Secret">
          <el-input
            v-model="form.sms_secret"
            type="password"
            show-password
            :placeholder="secretIsSet ? '已设置，留空表示不修改' : '未设置'"
          />
          <div class="form-tip">
            <el-tag :type="secretIsSet ? 'success' : 'info'" size="small">
              {{ secretIsSet ? '已设置' : '未设置' }}
            </el-tag>
            留空提交表示不修改原有 Secret
          </div>
        </el-form-item>
        <el-form-item label="短信签名" prop="sms_sign">
          <el-input v-model="form.sms_sign" placeholder="如 田冲助农商城" />
        </el-form-item>
        <el-form-item label="模板CODE" prop="sms_template_code">
          <el-input v-model="form.sms_template_code" placeholder="如 SMS_12345678" />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" :loading="saving" @click="handleSave">保存配置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 发送日志 -->
    <el-card shadow="never">
      <template #header>
        <div class="card-header"><span>短信发送日志</span></div>
      </template>
      <el-table :data="logs" v-loading="logLoading" stripe size="small">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="phone" label="手机号" min-width="130" />
        <el-table-column prop="content" label="内容" min-width="220" show-overflow-tooltip />
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

    <!-- 测试短信弹窗 -->
    <el-dialog v-model="testVisible" title="发送测试短信" width="440px">
      <el-form label-width="90px">
        <el-form-item label="手机号">
          <el-input v-model="testPhone" placeholder="请输入接收测试短信的手机号" maxlength="11" />
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
import { getConfig, saveConfig, testSms, getConfigLogs } from '../../api/config'
import { formatTime } from '../../utils/format'

const loading = ref(false)
const saving = ref(false)
const testing = ref(false)
const secretIsSet = ref(false)
const formRef = ref(null)

const form = reactive({
  sms_enabled: 0,
  sms_provider: 'aliyun',
  sms_access_key: '',
  sms_secret: '',
  sms_sign: '',
  sms_template_code: ''
})

const rules = {
  sms_access_key: [{ required: true, message: '请输入 AccessKey ID', trigger: 'blur' }],
  sms_sign: [{ required: true, message: '请输入短信签名', trigger: 'blur' }],
  sms_template_code: [{ required: true, message: '请输入模板 CODE', trigger: 'blur' }]
}

// 日志
const logs = ref([])
const logTotal = ref(0)
const logLoading = ref(false)
const logQuery = reactive({ page: 1, page_size: 15 })

// 测试短信
const testVisible = ref(false)
const testPhone = ref('')

async function loadConfig() {
  loading.value = true
  try {
    const data = await getConfig('sms')
    // data = {sms: [{key, value, type, is_set}, ...]}
    const groupArr = (data && data.sms) || []
    const cfg = {}
    let secretIsSet = false
    groupArr.forEach(item => {
      cfg[item.key] = item.value
      if (item.type === 'password') secretIsSet = !!item.is_set
    })
    form.sms_enabled = Number(cfg.sms_enabled || 0)
    form.sms_provider = cfg.sms_provider || 'aliyun'
    form.sms_access_key = cfg.sms_access_key || ''
    form.sms_secret = ''
    form.sms_sign = cfg.sms_sign || ''
    form.sms_template_code = cfg.sms_template_code || ''
    secretIsSet.value = secretIsSet
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
        sms_enabled: form.sms_enabled,
        sms_provider: form.sms_provider,
        sms_access_key: form.sms_access_key,
        sms_sign: form.sms_sign,
        sms_template_code: form.sms_template_code
      }
      if (form.sms_secret) {
        configs.sms_secret = form.sms_secret
      }
      await saveConfig('sms', configs)
      ElMessage.success('短信配置保存成功')
      form.sms_secret = ''
      loadConfig()
    } finally {
      saving.value = false
    }
  })
}

function openTest() {
  testPhone.value = ''
  testVisible.value = true
}

async function submitTest() {
  if (!/^1[3-9]\d{9}$/.test(testPhone.value)) {
    ElMessage.warning('请输入正确的手机号')
    return
  }
  testing.value = true
  try {
    await testSms(testPhone.value)
    ElMessage.success('测试短信已发送，请查收')
    testVisible.value = false
    loadLogs()
  } finally {
    testing.value = false
  }
}

async function loadLogs() {
  logLoading.value = true
  try {
    const data = await getConfigLogs({ type: 'sms', page: logQuery.page, page_size: logQuery.page_size })
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

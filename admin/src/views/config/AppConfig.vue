<template>
  <div class="page-container">
    <el-card shadow="never">
      <template #header>
        <div class="card-header"><span>应用配置</span></div>
      </template>

      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="140px"
        style="max-width: 680px"
        v-loading="loading"
      >
        <el-divider content-position="left">基础信息</el-divider>
        <el-form-item label="应用名称" prop="app_name">
          <el-input v-model="form.app_name" placeholder="如 田冲助农商城" maxlength="30" />
        </el-form-item>
        <el-form-item label="应用 LOGO">
          <div class="upload-area">
            <img v-if="form.app_logo" :src="form.app_logo" class="logo-preview" alt="LOGO" />
            <el-button
              v-else
              :icon="Upload"
              :loading="uploading"
              @click="fileInputRef && fileInputRef.click()"
            >
              上传 LOGO
            </el-button>
            <el-button v-if="form.app_logo" type="danger" link @click="form.app_logo = ''">
              删除
            </el-button>
          </div>
        </el-form-item>
        <el-form-item label="应用描述">
          <el-input
            v-model="form.app_description"
            type="textarea"
            :rows="3"
            placeholder="贵州亿田农业·田冲红色美丽乡村强村富民工坊，党建引领乡村振兴助农电商平台"
            maxlength="200"
            show-word-limit
          />
        </el-form-item>

        <el-divider content-position="left">客服联系</el-divider>
        <el-form-item label="客服电话">
          <el-input v-model="form.service_phone" placeholder="如 400-000-0000" maxlength="20" />
        </el-form-item>
        <el-form-item label="客服微信">
          <el-input v-model="form.service_wechat" placeholder="客服微信号" maxlength="50" />
        </el-form-item>

        <el-divider content-position="left">订单自动化</el-divider>
        <el-form-item label="自动取消(分钟)">
          <el-input-number v-model="form.auto_cancel_minutes" :min="5" :max="1440" />
          <div class="form-tip">待支付订单超过该时长后自动取消</div>
        </el-form-item>
        <el-form-item label="自动确认(天)">
          <el-input-number v-model="form.auto_confirm_days" :min="1" :max="60" />
          <div class="form-tip">已发货订单超过该天数后自动确认收货</div>
        </el-form-item>

        <el-divider content-position="left">推荐奖励</el-divider>
        <el-form-item label="推荐奖励">
          <el-switch
            v-model="form.share_reward_enabled"
            :active-value="1"
            :inactive-value="0"
            active-text="开启"
            inactive-text="关闭"
          />
        </el-form-item>
        <template v-if="form.share_reward_enabled">
          <el-form-item label="奖励比例(%)">
            <el-input-number v-model="form.share_reward_rate" :min="0" :max="100" :precision="1" />
            <div class="form-tip">按被推荐人订单金额的比例奖励推荐人</div>
          </el-form-item>
          <el-form-item label="奖励上限(元)">
            <el-input-number
              v-model="form.share_reward_max"
              :min="0"
              :precision="2"
              :step="1"
            />
            <div class="form-tip">单笔订单奖励的最高金额，0 为不限制</div>
          </el-form-item>
        </template>

        <el-form-item>
          <el-button type="primary" :loading="saving" @click="handleSave">保存配置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <input
      ref="fileInputRef"
      type="file"
      accept="image/*"
      style="display: none"
      @change="handleFileChange"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Upload } from '@element-plus/icons-vue'
import { getConfig, saveConfig } from '../../api/config'
import { uploadImage } from '../../api/upload'

const loading = ref(false)
const saving = ref(false)
const uploading = ref(false)
const formRef = ref(null)
const fileInputRef = ref(null)

const form = reactive({
  app_name: '',
  app_logo: '',
  app_description: '',
  service_phone: '',
  service_wechat: '',
  auto_cancel_minutes: 30,
  auto_confirm_days: 7,
  share_reward_enabled: 0,
  share_reward_rate: 5,
  share_reward_max: 0
})

const rules = {
  app_name: [{ required: true, message: '请输入应用名称', trigger: 'blur' }]
}

async function loadConfig() {
  loading.value = true
  try {
    const data = await getConfig('app')
    // data = {app: [{key, value, type}, ...]}
    const groupArr = (data && data.app) || []
    const cfg = {}
    groupArr.forEach(item => { cfg[item.key] = item.value })
    form.app_name = cfg.app_name || ''
    form.app_logo = cfg.app_logo || ''
    form.app_description = cfg.app_description || ''
    form.service_phone = cfg.service_phone || ''
    form.service_wechat = cfg.service_wechat || ''
    form.auto_cancel_minutes = Number(cfg.auto_cancel_minutes || 30)
    form.auto_confirm_days = Number(cfg.auto_confirm_days || 7)
    form.share_reward_enabled = Number(cfg.share_reward_enabled || 0)
    form.share_reward_rate = Number(cfg.share_reward_rate || 5)
    form.share_reward_max = Number(cfg.share_reward_max || 0)
  } finally {
    loading.value = false
  }
}

async function handleFileChange(e) {
  const file = e.target.files && e.target.files[0]
  if (!file) return
  uploading.value = true
  try {
    const data = await uploadImage(file)
    form.app_logo = data.url
    ElMessage.success('LOGO 上传成功')
  } finally {
    uploading.value = false
    e.target.value = ''
  }
}

async function handleSave() {
  if (!formRef.value) return
  await formRef.value.validate(async (valid) => {
    if (!valid) return
    saving.value = true
    try {
      await saveConfig('app', {
        app_name: form.app_name,
        app_logo: form.app_logo,
        app_description: form.app_description,
        service_phone: form.service_phone,
        service_wechat: form.service_wechat,
        auto_cancel_minutes: form.auto_cancel_minutes,
        auto_confirm_days: form.auto_confirm_days,
        share_reward_enabled: form.share_reward_enabled,
        share_reward_rate: form.share_reward_rate,
        share_reward_max: form.share_reward_max
      })
      ElMessage.success('应用配置保存成功')
    } finally {
      saving.value = false
    }
  })
}

onMounted(loadConfig)
</script>

<style scoped>
.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.upload-area {
  display: flex;
  align-items: center;
  gap: 12px;
}

.logo-preview {
  width: 80px;
  height: 80px;
  object-fit: cover;
  border-radius: 8px;
  border: 1px solid #eee;
}

.form-tip {
  font-size: 12px;
  color: #999;
  line-height: 1.6;
}
</style>

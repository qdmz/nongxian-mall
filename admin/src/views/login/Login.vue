<template>
  <div class="login-page">
    <div class="login-panel">
      <div class="login-left">
        <div class="brand">
          <div class="brand-logo">田</div>
          <h1 class="brand-title">田冲助农商城</h1>
          <p class="brand-sub">贵州亿田农业 · 田冲红色美丽乡村强村富民工坊</p>
        </div>
        <div class="slogan">
          <div class="slogan-item">
            <el-icon><Flag /></el-icon>
            <span>党建引领 · 乡村振兴</span>
          </div>
          <div class="slogan-item">
            <el-icon><Grape /></el-icon>
            <span>红色助农 · 山货出山</span>
          </div>
          <div class="slogan-item">
            <el-icon><Sunny /></el-icon>
            <span>强村富民 · 共同富裕</span>
          </div>
        </div>
      </div>
      <div class="login-right">
        <h2 class="login-title">管理后台登录</h2>
        <p class="login-desc">欢迎回来，请登录管理员账号</p>
        <el-form
          ref="formRef"
          :model="form"
          :rules="rules"
          size="large"
          @keyup.enter="handleLogin"
        >
          <el-form-item prop="username">
            <el-input
              v-model="form.username"
              placeholder="请输入管理员账号"
              :prefix-icon="User"
              clearable
            />
          </el-form-item>
          <el-form-item prop="password">
            <el-input
              v-model="form.password"
              type="password"
              placeholder="请输入密码"
              :prefix-icon="Lock"
              show-password
              clearable
            />
          </el-form-item>
          <el-form-item>
            <el-button
              type="primary"
              class="login-btn"
              :loading="loading"
              @click="handleLogin"
            >
              登 录
            </el-button>
          </el-form-item>
        </el-form>
        <div class="login-footer">田冲红色美丽乡村 · 助农电商平台</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { User, Lock } from '@element-plus/icons-vue'
import { login } from '../../api/auth'
import { useAdminStore } from '../../store/admin'

const router = useRouter()
const store = useAdminStore()
const formRef = ref(null)
const loading = ref(false)

const form = reactive({
  username: '',
  password: ''
})

const rules = {
  username: [{ required: true, message: '请输入管理员账号', trigger: 'blur' }],
  password: [{ required: true, message: '请输入密码', trigger: 'blur' }]
}

async function handleLogin() {
  if (!formRef.value) return
  await formRef.value.validate(async (valid) => {
    if (!valid) return
    loading.value = true
    try {
      const data = await login({
        username: form.username,
        password: form.password
      })
      store.setToken(data.token)
      if (data.admin) {
        store.setAdminInfo(data.admin)
      } else {
        store.fetchProfile().catch(() => {})
      }
      ElMessage.success('登录成功')
      router.push('/dashboard')
    } catch (e) {
      // 错误已在拦截器统一提示
    } finally {
      loading.value = false
    }
  })
}
</script>

<style scoped>
.login-page {
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #7a1a05 0%, #a82508 40%, #d4380d 100%);
  position: relative;
  overflow: hidden;
}

.login-page::before {
  content: '';
  position: absolute;
  width: 600px;
  height: 600px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.04);
  top: -200px;
  right: -100px;
}

.login-page::after {
  content: '';
  position: absolute;
  width: 400px;
  height: 400px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.05);
  bottom: -150px;
  left: -100px;
}

.login-panel {
  width: 860px;
  max-width: 94vw;
  min-height: 480px;
  display: flex;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.35);
  position: relative;
  z-index: 1;
}

.login-left {
  flex: 1.1;
  background: linear-gradient(160deg, rgba(0, 0, 0, 0.28), rgba(0, 0, 0, 0.08));
  padding: 48px 40px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  color: #fff;
}

.brand-logo {
  width: 56px;
  height: 56px;
  border-radius: 14px;
  background: #fff;
  color: #d4380d;
  font-size: 30px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 18px;
}

.brand-title {
  font-size: 26px;
  font-weight: 700;
  letter-spacing: 2px;
  margin-bottom: 10px;
}

.brand-sub {
  font-size: 13px;
  opacity: 0.85;
  line-height: 1.6;
  margin-bottom: 36px;
}

.slogan {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.slogan-item {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 15px;
  background: rgba(255, 255, 255, 0.12);
  padding: 10px 16px;
  border-radius: 8px;
}

.login-right {
  flex: 1;
  background: #fff;
  padding: 56px 48px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.login-title {
  font-size: 22px;
  color: #262626;
  margin-bottom: 8px;
}

.login-desc {
  font-size: 13px;
  color: #999;
  margin-bottom: 32px;
}

.login-btn {
  width: 100%;
  letter-spacing: 6px;
}

.login-footer {
  text-align: center;
  font-size: 12px;
  color: #bbb;
  margin-top: 24px;
}

@media (max-width: 768px) {
  .login-left {
    display: none;
  }
}
</style>

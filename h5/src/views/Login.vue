<template>
  <div class="login-page">
    <div class="login-hero">
      <div class="login-logo">🚩</div>
      <div class="login-title">田冲助农商城</div>
      <div class="login-sub">党建引领 · 强村富民 · 乡村振兴</div>
    </div>

    <van-tabs v-model:active="activeTab" class="login-tabs">
      <van-tab title="账号密码登录" name="password">
        <van-form @submit="onLogin">
          <van-field
            v-model="form.account"
            name="account"
            label="账号"
            placeholder="手机号 / 邮箱"
            :rules="[{ required: true, message: '请输入账号' }]"
          />
          <van-field
            v-model="form.password"
            type="password"
            name="password"
            label="密码"
            placeholder="请输入密码"
            :rules="[{ required: true, message: '请输入密码' }]"
          />
          <div class="login-submit">
            <van-button round block type="primary" native-type="submit" :loading="submitting">
              登 录
            </van-button>
          </div>
        </van-form>
      </van-tab>

      <van-tab title="短信验证码登录" name="sms">
        <van-form @submit="onLogin">
          <van-field
            v-model="form.phone"
            name="phone"
            label="手机号"
            type="tel"
            maxlength="11"
            placeholder="请输入手机号"
            :rules="[{ required: true, message: '请输入手机号' }, { pattern: /^1\d{10}$/, message: '手机号格式不正确' }]"
          />
          <van-field
            v-model="form.code"
            name="code"
            label="验证码"
            type="digit"
            maxlength="6"
            placeholder="请输入验证码"
            :rules="[{ required: true, message: '请输入验证码' }]"
          >
            <template #button>
              <van-button size="small" round plain type="primary" :disabled="smsCooling > 0" @click="onSendCode('sms', 'login')">
                {{ smsCooling > 0 ? smsCooling + 's后重发' : '获取验证码' }}
              </van-button>
            </template>
          </van-field>
          <div class="login-tip">未注册的手机号验证后将自动注册</div>
          <div class="login-submit">
            <van-button round block type="primary" native-type="submit" :loading="submitting">
              登 录
            </van-button>
          </div>
        </van-form>
      </van-tab>
    </van-tabs>

    <div class="login-links">
      <span @click="$router.push('/register')">没有账号？立即注册</span>
    </div>
  </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { showToast } from 'vant'
import { login, sendCode } from '../api/auth'
import { useUserStore } from '../store/user'

const route = useRoute()
const router = useRouter()
const userStore = useUserStore()

const activeTab = ref('password')
const submitting = ref(false)
const smsCooling = ref(0)
let smsTimer = null

const form = ref({
  account: '',
  password: '',
  phone: '',
  code: ''
})

async function onSendCode(type, scene) {
  const target = form.value.phone
  if (!/^1\d{10}$/.test(target)) {
    showToast('请输入正确的手机号')
    return
  }
  try {
    await sendCode({ type, target, scene })
    showToast('验证码已发送')
    smsCooling.value = 60
    smsTimer = setInterval(() => {
      smsCooling.value -= 1
      if (smsCooling.value <= 0) {
        clearInterval(smsTimer)
        smsTimer = null
      }
    }, 1000)
  } catch (e) {
    /* 已统一 toast */
  }
}

async function onLogin() {
  submitting.value = true
  try {
    let data
    if (activeTab.value === 'sms') {
      data = await login({
        login_type: 'sms',
        phone: form.value.phone,
        code: form.value.code
      })
    } else {
      data = await login({
        login_type: 'password',
        account: form.value.account,
        password: form.value.password
      })
    }
    userStore.setToken(data.token)
    userStore.user = data.user
    showToast('登录成功')
    userStore.fetchCartCount().catch(() => {})
    const redirect = route.query.redirect
    router.replace(typeof redirect === 'string' && redirect.startsWith('/') ? redirect : '/')
  } catch (e) {
    /* 已统一 toast */
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  if (smsTimer) clearInterval(smsTimer)
})

onBeforeUnmount(() => {
  if (smsTimer) clearInterval(smsTimer)
})
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  background: #fff;
}
.login-hero {
  padding: 50px 20px 30px;
  background: linear-gradient(160deg, #e63946 0%, #c1121f 100%);
  color: #fff;
  text-align: center;
}
.login-logo {
  font-size: 46px;
}
.login-title {
  margin-top: 8px;
  font-size: 22px;
  font-weight: 800;
  letter-spacing: 2px;
}
.login-sub {
  margin-top: 6px;
  font-size: 12px;
  opacity: 0.9;
}
.login-tabs {
  padding: 0 16px;
}
.login-submit {
  margin: 26px 16px 10px;
}
.login-tip {
  padding: 8px 16px 0;
  font-size: 11px;
  color: #b0b3ba;
}
.login-links {
  display: flex;
  justify-content: center;
  margin-top: 16px;
  font-size: 13px;
  color: #e63946;
}
</style>

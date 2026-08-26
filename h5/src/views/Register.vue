<template>
  <div class="reg-page">
    <div class="reg-hero">
      <div class="reg-title">注册田冲助农商城</div>
      <div class="reg-sub">一起助力乡村振兴 · 强村富民</div>
    </div>

    <van-tabs v-model:active="activeTab" class="reg-tabs">
      <!-- 手机验证码注册 -->
      <van-tab title="手机验证码" name="phone_code">
        <van-form @submit="onRegister">
          <van-field
            v-model="form.phone"
            label="手机号"
            type="tel"
            maxlength="11"
            placeholder="请输入手机号"
            :rules="[{ required: true, message: '请输入手机号' }, { pattern: /^1\d{10}$/, message: '手机号格式不正确' }]"
          />
          <van-field
            v-model="form.code"
            label="验证码"
            type="digit"
            maxlength="6"
            placeholder="请输入短信验证码"
            :rules="[{ required: true, message: '请输入验证码' }]"
          >
            <template #button>
              <van-button size="small" round plain type="primary" :disabled="smsCooling > 0" @click="onSendCode('sms')">
                {{ smsCooling > 0 ? smsCooling + 's后重发' : '获取验证码' }}
              </van-button>
            </template>
          </van-field>
          <van-field v-model="form.nickname" label="昵称" placeholder="选填，请输入昵称" maxlength="20" />
          <van-field v-model="form.invite_code" label="邀请码" placeholder="选填，推荐人邀请码" maxlength="20" />
          <div class="reg-submit">
            <van-button round block type="primary" native-type="submit" :loading="submitting">注 册</van-button>
          </div>
        </van-form>
      </van-tab>

      <!-- 邮箱验证码注册 -->
      <van-tab title="邮箱验证码" name="email_code">
        <van-form @submit="onRegister">
          <van-field
            v-model="form.email"
            label="邮箱"
            placeholder="请输入邮箱地址"
            :rules="[{ required: true, message: '请输入邮箱' }, { pattern: /^\S+@\S+\.\S+$/, message: '邮箱格式不正确' }]"
          />
          <van-field
            v-model="form.code"
            label="验证码"
            type="digit"
            maxlength="6"
            placeholder="请输入邮箱验证码"
            :rules="[{ required: true, message: '请输入验证码' }]"
          >
            <template #button>
              <van-button size="small" round plain type="primary" :disabled="emailCooling > 0" @click="onSendCode('email')">
                {{ emailCooling > 0 ? emailCooling + 's后重发' : '获取验证码' }}
              </van-button>
            </template>
          </van-field>
          <van-field v-model="form.nickname" label="昵称" placeholder="选填，请输入昵称" maxlength="20" />
          <van-field v-model="form.invite_code" label="邀请码" placeholder="选填，推荐人邀请码" maxlength="20" />
          <div class="reg-submit">
            <van-button round block type="primary" native-type="submit" :loading="submitting">注 册</van-button>
          </div>
        </van-form>
      </van-tab>

      <!-- 手机密码注册 -->
      <van-tab title="手机密码" name="password">
        <van-form @submit="onRegister">
          <van-field
            v-model="form.phone"
            label="手机号"
            type="tel"
            maxlength="11"
            placeholder="请输入手机号"
            :rules="[{ required: true, message: '请输入手机号' }, { pattern: /^1\d{10}$/, message: '手机号格式不正确' }]"
          />
          <van-field
            v-model="form.password"
            label="密码"
            type="password"
            placeholder="请设置密码（至少6位）"
            :rules="[{ required: true, message: '请输入密码' }, { validator: pwdValidator, message: '密码至少6位' }]"
          />
          <van-field
            v-model="form.confirmPassword"
            label="确认密码"
            type="password"
            placeholder="请再次输入密码"
            :rules="[{ required: true, message: '请再次输入密码' }, { validator: confirmValidator, message: '两次密码不一致' }]"
          />
          <van-field v-model="form.nickname" label="昵称" placeholder="选填，请输入昵称" maxlength="20" />
          <van-field v-model="form.invite_code" label="邀请码" placeholder="选填，推荐人邀请码" maxlength="20" />
          <div class="reg-submit">
            <van-button round block type="primary" native-type="submit" :loading="submitting">注 册</van-button>
          </div>
        </van-form>
      </van-tab>
    </van-tabs>

    <div class="reg-links">
      <span @click="$router.push('/login')">已有账号？去登录</span>
    </div>
  </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { showToast } from 'vant'
import { register, sendCode } from '../api/auth'
import { useUserStore } from '../store/user'

const route = useRoute()
const router = useRouter()
const userStore = useUserStore()

const activeTab = ref('phone_code')
const submitting = ref(false)
const smsCooling = ref(0)
const emailCooling = ref(0)
let smsTimer = null
let emailTimer = null

const form = ref({
  phone: '',
  email: '',
  code: '',
  password: '',
  confirmPassword: '',
  nickname: '',
  invite_code: ''
})

function pwdValidator(v) {
  return String(v || '').length >= 6
}

function confirmValidator(v) {
  return v === form.value.password
}

async function onSendCode(type) {
  let target
  if (type === 'sms') {
    target = form.value.phone
    if (!/^1\d{10}$/.test(target)) {
      showToast('请输入正确的手机号')
      return
    }
  } else {
    target = form.value.email
    if (!/^\S+@\S+\.\S+$/.test(target)) {
      showToast('请输入正确的邮箱')
      return
    }
  }
  try {
    await sendCode({ type, target, scene: 'register' })
    showToast(type === 'sms' ? '短信验证码已发送' : '邮件验证码已发送')
    if (type === 'sms') {
      smsCooling.value = 60
      smsTimer = setInterval(() => {
        smsCooling.value -= 1
        if (smsCooling.value <= 0 && smsTimer) {
          clearInterval(smsTimer)
          smsTimer = null
        }
      }, 1000)
    } else {
      emailCooling.value = 60
      emailTimer = setInterval(() => {
        emailCooling.value -= 1
        if (emailCooling.value <= 0 && emailTimer) {
          clearInterval(emailTimer)
          emailTimer = null
        }
      }, 1000)
    }
  } catch (e) {
    /* 已统一 toast */
  }
}

async function onRegister() {
  submitting.value = true
  try {
    const type = activeTab.value
    const payload = { register_type: type }
    if (type === 'phone_code') {
      payload.phone = form.value.phone
      payload.code = form.value.code
    } else if (type === 'email_code') {
      payload.email = form.value.email
      payload.code = form.value.code
    } else {
      payload.phone = form.value.phone
      payload.password = form.value.password
    }
    if (form.value.nickname) payload.nickname = form.value.nickname
    if (form.value.invite_code) payload.invite_code = form.value.invite_code

    const data = await register(payload)
    userStore.setToken(data.token)
    userStore.user = data.user
    showToast('注册成功，欢迎加入助农大家庭')
    router.replace('/')
  } catch (e) {
    /* 已统一 toast */
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  // 支持邀请码参数自动填入：/register?invite=xxx
  if (route.query.invite) {
    form.value.invite_code = String(route.query.invite)
  }
})

onBeforeUnmount(() => {
  if (smsTimer) clearInterval(smsTimer)
  if (emailTimer) clearInterval(emailTimer)
})
</script>

<style scoped>
.reg-page {
  min-height: 100vh;
  background: #fff;
}
.reg-hero {
  padding: 36px 20px 24px;
  background: linear-gradient(160deg, #e63946 0%, #c1121f 100%);
  color: #fff;
  text-align: center;
}
.reg-title {
  font-size: 20px;
  font-weight: 800;
}
.reg-sub {
  margin-top: 6px;
  font-size: 12px;
  opacity: 0.9;
}
.reg-tabs {
  padding: 0 16px;
}
.reg-submit {
  margin: 26px 0 10px;
}
.reg-links {
  display: flex;
  justify-content: center;
  margin-top: 16px;
  font-size: 13px;
  color: #e63946;
}
</style>

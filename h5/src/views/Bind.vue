<template>
  <div class="bind-page">
    <van-nav-bar title="绑定账号" left-arrow class="tc-nav" @click-left="$router.back()">
      <template #right>
        <van-icon name="home-o" size="18" @click="$router.push('/')" />
      </template>
    </van-nav-bar>

    <div class="section">
      <div class="bind-status">
        <span>当前绑定状态</span>
        <span class="bind-status-val">
          手机：{{ bindInfo.phone ? bindInfo.phone : '未绑定' }}
          <em v-if="bindInfo.phone_verified" class="ok">已验证</em>
          <em v-else-if="bindInfo.phone" class="warn">未验证</em>
          <em v-else class="none">-</em>
        </span>
        <span class="bind-status-val">
          邮箱：{{ bindInfo.email ? bindInfo.email : '未绑定' }}
          <em v-if="bindInfo.email_verified" class="ok">已验证</em>
          <em v-else-if="bindInfo.email" class="warn">未验证</em>
          <em v-else class="none">-</em>
        </span>
      </div>
    </div>

    <!-- 绑定手机 -->
    <div class="section">
      <div class="section-head"><div class="section-title">绑定手机号</div></div>
      <van-form @submit="onBindPhone">
        <van-field
          v-model="phoneForm.phone"
          label="手机号"
          type="tel"
          maxlength="11"
          placeholder="请输入要绑定的手机号"
          :rules="[{ required: true, message: '请输入手机号' }, { pattern: /^1\d{10}$/, message: '手机号格式不正确' }]"
        />
        <van-field
          v-model="phoneForm.code"
          label="验证码"
          type="digit"
          maxlength="6"
          placeholder="请输入短信验证码"
          :rules="[{ required: true, message: '请输入验证码' }]"
        >
          <template #button>
            <van-button size="small" round plain type="primary" :disabled="phoneCooling > 0" @click="sendBindCode('phone')">
              {{ phoneCooling > 0 ? phoneCooling + 's后重发' : '获取验证码' }}
            </van-button>
          </template>
        </van-field>
        <div class="bind-submit">
          <van-button round block type="primary" native-type="submit" :loading="phoneSubmitting">
            {{ bindInfo.phone ? '更换绑定手机' : '绑定手机' }}
          </van-button>
        </div>
      </van-form>
    </div>

    <!-- 绑定邮箱 -->
    <div class="section">
      <div class="section-head"><div class="section-title">绑定邮箱</div></div>
      <van-form @submit="onBindEmail">
        <van-field
          v-model="emailForm.email"
          label="邮箱"
          placeholder="请输入要绑定的邮箱"
          :rules="[{ required: true, message: '请输入邮箱' }, { pattern: /^\S+@\S+\.\S+$/, message: '邮箱格式不正确' }]"
        />
        <van-field
          v-model="emailForm.code"
          label="验证码"
          type="digit"
          maxlength="6"
          placeholder="请输入邮箱验证码"
          :rules="[{ required: true, message: '请输入验证码' }]"
        >
          <template #button>
            <van-button size="small" round plain type="primary" :disabled="emailCooling > 0" @click="sendBindCode('email')">
              {{ emailCooling > 0 ? emailCooling + 's后重发' : '获取验证码' }}
            </van-button>
          </template>
        </van-field>
        <div class="bind-submit">
          <van-button round block type="primary" native-type="submit" :loading="emailSubmitting">
            {{ bindInfo.email ? '更换绑定邮箱' : '绑定邮箱' }}
          </van-button>
        </div>
      </van-form>
    </div>
  </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { showToast } from 'vant'
import { getBindInfo, bindPhone, bindEmail } from '../api/user'
import { sendCode } from '../api/auth'

const bindInfo = ref({ phone: '', phone_verified: 0, email: '', email_verified: 0 })

const phoneForm = ref({ phone: '', code: '' })
const emailForm = ref({ email: '', code: '' })

const phoneCooling = ref(0)
const emailCooling = ref(0)
const phoneSubmitting = ref(false)
const emailSubmitting = ref(false)
let phoneTimer = null
let emailTimer = null

async function loadBindInfo() {
  try {
    bindInfo.value = await getBindInfo()
  } catch (e) {
    /* 已统一 toast */
  }
}

async function sendBindCode(type) {
  if (type === 'phone') {
    if (!/^1\d{10}$/.test(phoneForm.value.phone)) {
      showToast('请输入正确的手机号')
      return
    }
    try {
      await sendCode({ type: 'sms', target: phoneForm.value.phone, scene: 'verify' })
      showToast('短信验证码已发送')
      phoneCooling.value = 60
      phoneTimer = setInterval(() => {
        phoneCooling.value -= 1
        if (phoneCooling.value <= 0 && phoneTimer) {
          clearInterval(phoneTimer)
          phoneTimer = null
        }
      }, 1000)
    } catch (e) {
      /* 已统一 toast */
    }
  } else {
    if (!/^\S+@\S+\.\S+$/.test(emailForm.value.email)) {
      showToast('请输入正确的邮箱')
      return
    }
    try {
      await sendCode({ type: 'email', target: emailForm.value.email, scene: 'verify' })
      showToast('邮件验证码已发送')
      emailCooling.value = 60
      emailTimer = setInterval(() => {
        emailCooling.value -= 1
        if (emailCooling.value <= 0 && emailTimer) {
          clearInterval(emailTimer)
          emailTimer = null
        }
      }, 1000)
    } catch (e) {
      /* 已统一 toast */
    }
  }
}

async function onBindPhone() {
  phoneSubmitting.value = true
  try {
    await bindPhone({ phone: phoneForm.value.phone, code: phoneForm.value.code })
    showToast('手机号绑定成功')
    phoneForm.value = { phone: '', code: '' }
    loadBindInfo()
  } catch (e) {
    /* 已统一 toast */
  } finally {
    phoneSubmitting.value = false
  }
}

async function onBindEmail() {
  emailSubmitting.value = true
  try {
    await bindEmail({ email: emailForm.value.email, code: emailForm.value.code })
    showToast('邮箱绑定成功')
    emailForm.value = { email: '', code: '' }
    loadBindInfo()
  } catch (e) {
    /* 已统一 toast */
  } finally {
    emailSubmitting.value = false
  }
}

onMounted(loadBindInfo)

onBeforeUnmount(() => {
  if (phoneTimer) clearInterval(phoneTimer)
  if (emailTimer) clearInterval(emailTimer)
})
</script>

<style scoped>
.bind-status {
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  font-size: 13px;
  color: #44464d;
}
.bind-status-val em {
  font-style: normal;
  font-size: 11px;
  margin-left: 6px;
  padding: 0 5px;
  border-radius: 3px;
}
.bind-status-val em.ok {
  color: #2a9d5c;
  background: #e8f6ee;
}
.bind-status-val em.warn {
  color: #ff8c00;
  background: #fff5dd;
}
.bind-status-val em.none {
  color: #b0b3ba;
}
.bind-submit {
  margin: 20px 16px 16px;
}
</style>

<template>
  <div class="addr-edit">
    <van-nav-bar :title="isEdit ? '编辑地址' : '新增地址'" left-arrow class="tc-nav" @click-left="$router.back()">
      <template #right>
        <van-icon name="home-o" size="18" @click="$router.push('/')" />
      </template>
    </van-nav-bar>

    <van-form @submit="onSave">
      <van-cell-group inset>
        <van-field
          v-model="form.consignee"
          label="收货人"
          placeholder="请输入收货人姓名"
          maxlength="20"
          :rules="[{ required: true, message: '请输入收货人' }]"
        />
        <van-field
          v-model="form.phone"
          label="手机号"
          type="tel"
          maxlength="11"
          placeholder="请输入手机号"
          :rules="[{ required: true, message: '请输入手机号' }, { pattern: /^1\d{10}$/, message: '手机号格式不正确' }]"
        />
        <van-field
          v-model="areaText"
          is-link
          readonly
          label="所在地区"
          placeholder="请选择省 / 市 / 区"
          :rules="[{ required: true, message: '请选择所在地区' }]"
          @click="showArea = true"
        />
        <van-field
          v-model="form.detail"
          label="详细地址"
          type="textarea"
          rows="2"
          autosize
          maxlength="200"
          placeholder="街道、小区、门牌号等"
          :rules="[{ required: true, message: '请输入详细地址' }]"
        />
        <van-cell center title="设为默认地址">
          <template #right-icon>
            <van-switch v-model="form.is_default" size="20" />
          </template>
        </van-cell>
      </van-cell-group>

      <div class="addr-edit-submit">
        <van-button v-if="isEdit" round block plain type="danger" @click="onDelete">删除该地址</van-button>
        <van-button round block type="primary" native-type="submit" :loading="saving" class="addr-edit-save">
          保存
        </van-button>
      </div>
    </van-form>

    <!-- 省市区选择 -->
    <van-popup v-model:show="showArea" position="bottom" round>
      <van-area
        :area-list="areaList"
        :value="areaCode"
        title="选择地区"
        @confirm="onAreaConfirm"
        @cancel="showArea = false"
      />
    </van-popup>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { showConfirmDialog, showToast } from 'vant'
import { areaList } from '@vant/area-data'
import { getAddresses, addAddress, updateAddress, deleteAddress } from '../api/user'

const route = useRoute()
const router = useRouter()

const form = ref({
  consignee: '',
  phone: '',
  province: '',
  city: '',
  district: '',
  detail: '',
  is_default: false
})

const showArea = ref(false)
const areaCode = ref('')
const saving = ref(false)

const isEdit = computed(() => !!route.params.id)

const areaText = computed(() =>
  [form.value.province, form.value.city, form.value.district].filter(Boolean).join(' ')
)

// 根据省市区名称反查 area code（用于回显）
function findAreaCode(province, city, district) {
  const provinces = areaList.province_list || {}
  const cities = areaList.city_list || {}
  const districts = areaList.county_list || {}
  let pCode = ''
  for (const [code, name] of Object.entries(provinces)) {
    if (name === province) {
      pCode = code
      break
    }
  }
  if (!pCode) return ''
  let cCode = ''
  for (const [code, name] of Object.entries(cities)) {
    if (name === city && String(code).startsWith(String(pCode).slice(0, 2))) {
      cCode = code
      break
    }
  }
  if (!cCode) return pCode
  for (const [code, name] of Object.entries(districts)) {
    if (name === district && String(code).startsWith(String(cCode).slice(0, 4))) {
      return code
    }
  }
  return cCode
}

function onAreaConfirm(values) {
  const [province, city, district] = values
  if (province) form.value.province = province.name
  if (city) form.value.city = city.name
  if (district) form.value.district = district.name
  showArea.value = false
}

async function loadAddress() {
  const id = route.params.id
  if (!id) return
  try {
    const list = await getAddresses()
    const addr = (list || []).find((a) => Number(a.id) === Number(id))
    if (addr) {
      form.value.consignee = addr.consignee
      form.value.phone = addr.phone
      form.value.province = addr.province
      form.value.city = addr.city
      form.value.district = addr.district
      form.value.detail = addr.detail
      form.value.is_default = !!addr.is_default
      areaCode.value = findAreaCode(addr.province, addr.city, addr.district)
    } else {
      showToast('地址不存在')
      router.back()
    }
  } catch (e) {
    /* 已统一 toast */
  }
}

async function onSave() {
  saving.value = true
  try {
    const payload = {
      consignee: form.value.consignee,
      phone: form.value.phone,
      province: form.value.province,
      city: form.value.city,
      district: form.value.district,
      detail: form.value.detail,
      is_default: form.value.is_default ? 1 : 0
    }
    if (isEdit.value) {
      await updateAddress(route.params.id, payload)
      showToast('地址已更新')
    } else {
      await addAddress(payload)
      showToast('地址已添加')
    }
    router.back()
  } catch (e) {
    /* 已统一 toast */
  } finally {
    saving.value = false
  }
}

function onDelete() {
  showConfirmDialog({ title: '提示', message: '确定删除该地址吗？' })
    .then(async () => {
      await deleteAddress(route.params.id)
      showToast('已删除')
      router.back()
    })
    .catch(() => {})
}

onMounted(loadAddress)
</script>

<style scoped>
.addr-edit {
  min-height: 100vh;
  background: #f5f5f7;
  padding-top: 6px;
}
.addr-edit-submit {
  display: flex;
  gap: 10px;
  margin: 20px 16px;
}
.addr-edit-save {
  flex: 1;
}
</style>

<template>
  <div class="page-container">
    <!-- 筛选 -->
    <el-card shadow="never" class="filter-card">
      <el-form inline @submit.prevent>
        <el-form-item label="状态">
          <el-radio-group v-model="query.status" @change="handleSearch">
            <el-radio-button value="">全部</el-radio-button>
            <el-radio-button value="1">进行中</el-radio-button>
            <el-radio-button value="0">未开始</el-radio-button>
            <el-radio-button value="2">已结束</el-radio-button>
          </el-radio-group>
        </el-form-item>
        <el-form-item>
          <el-button :icon="Refresh" @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card shadow="never">
      <div class="table-toolbar">
        <div class="toolbar-title">拼团活动</div>
        <el-button type="primary" :icon="Plus" @click="openForm(null)">新建活动</el-button>
      </div>

      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column label="商品" min-width="200">
          <template #default="{ row }">
            <div class="product-cell">
              <img v-if="row.product_image || row.image" :src="row.product_image || row.image" class="table-img" alt="" />
              <div>
                <div class="product-name">{{ row.product_name || row.name || `商品#${row.product_id}` }}</div>
                <div class="sub-text">原价 {{ money(row.product_price || row.original_price) }}</div>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="拼团价" width="100">
          <template #default="{ row }">
            <span class="money">{{ money(row.group_price) }}</span>
          </template>
        </el-table-column>
        <el-table-column label="成团人数" width="90" align="center">
          <template #default="{ row }">{{ row.required_count }} 人</template>
        </el-table-column>
        <el-table-column label="已拼/库存" width="100" align="center">
          <template #default="{ row }">
            {{ row.joined_count || row.sold_count || 0 }} / {{ row.stock }}
          </template>
        </el-table-column>
        <el-table-column label="有效时长" width="90" align="center">
          <template #default="{ row }">{{ row.valid_hours }} 小时</template>
        </el-table-column>
        <el-table-column label="活动时间" min-width="300">
          <template #default="{ row }">
            <div class="sub-text">开始：{{ formatTime(row.start_time) }}</div>
            <div class="sub-text">结束：{{ formatTime(row.end_time) }}</div>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="90">
          <template #default="{ row }">
            <el-tag :type="activityStatusType(row)" size="small">
              {{ activityStatusText(row) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="140" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="openForm(row)">编辑</el-button>
            <el-button type="danger" link size="small" @click="handleDelete(row)">停用</el-button>
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

    <!-- 新建/编辑弹窗 -->
    <el-dialog
      v-model="formVisible"
      :title="form.id ? '编辑拼团活动' : '新建拼团活动'"
      width="560px"
      destroy-on-close
    >
      <el-form ref="formRef" :model="form" :rules="rules" label-width="110px">
        <el-form-item label="商品ID" prop="product_id">
          <el-input-number
            v-model="form.product_id"
            :min="1"
            :disabled="!!form.id"
            style="width: 100%"
          />
          <div class="form-tip">填写参加拼团的商品 ID（创建后不可修改）</div>
        </el-form-item>
        <el-row :gutter="12">
          <el-col :span="12">
            <el-form-item label="拼团价(元)" prop="group_price">
              <el-input-number
                v-model="form.group_price"
                :precision="2"
                :min="0"
                style="width: 100%"
              />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="成团人数" prop="required_count">
              <el-input-number v-model="form.required_count" :min="2" :max="100" style="width: 100%" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="12">
          <el-col :span="12">
            <el-form-item label="有效时长(h)">
              <el-input-number v-model="form.valid_hours" :min="1" :max="720" style="width: 100%" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="限购件数">
              <el-input-number v-model="form.max_count" :min="0" style="width: 100%" />
              <div class="form-tip">0 为不限购</div>
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="拼团库存">
          <el-input-number v-model="form.stock" :min="1" :max="999999" style="width: 100%" />
        </el-form-item>
        <el-form-item label="开始时间" prop="start_time">
          <el-date-picker
            v-model="form.start_time"
            type="datetime"
            placeholder="选择开始时间"
            value-format="YYYY-MM-DD HH:mm:ss"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="结束时间" prop="end_time">
          <el-date-picker
            v-model="form.end_time"
            type="datetime"
            placeholder="选择结束时间"
            value-format="YYYY-MM-DD HH:mm:ss"
            style="width: 100%"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="formVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitting" @click="submitForm">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus, Refresh } from '@element-plus/icons-vue'
import dayjs from 'dayjs'
import { getActivities, createActivity, updateActivity, deleteActivity } from '../../api/groupBuy'
import { formatTime, formatMoney } from '../../utils/format'

const money = (v) => formatMoney(v)

const loading = ref(false)
const submitting = ref(false)
const list = ref([])
const total = ref(0)

const query = reactive({
  status: '',
  page: 1,
  page_size: 15
})

const formVisible = ref(false)
const formRef = ref(null)
const form = reactive({
  id: null,
  product_id: null,
  group_price: 0,
  required_count: 2,
  valid_hours: 24,
  max_count: 0,
  stock: 100,
  start_time: '',
  end_time: ''
})

const rules = {
  product_id: [{ required: true, message: '请输入商品 ID', trigger: 'blur' }],
  group_price: [{ required: true, message: '请输入拼团价', trigger: 'blur' }],
  required_count: [{ required: true, message: '请输入成团人数', trigger: 'blur' }],
  start_time: [{ required: true, message: '请选择开始时间', trigger: 'change' }],
  end_time: [{ required: true, message: '请选择结束时间', trigger: 'change' }]
}

function activityStatusText(row) {
  const now = Math.floor(Date.now() / 1000)
  const start = row.start_time ? Number(row.start_time) : 0
  const end = row.end_time ? Number(row.end_time) : 0
  if (row.status === 2) return '已结束'
  if (end && now > end) return '已结束'
  if (start && now < start) return '未开始'
  return '进行中'
}

function activityStatusType(row) {
  const text = activityStatusText(row)
  if (text === '进行中') return 'success'
  if (text === '未开始') return 'info'
  return 'danger'
}

async function loadList() {
  loading.value = true
  try {
    const params = { page: query.page, page_size: query.page_size }
    if (query.status !== '') params.status = query.status
    const data = await getActivities(params)
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
  query.status = ''
  query.page = 1
  loadList()
}

function openForm(row) {
  if (row) {
    Object.assign(form, {
      id: row.id,
      product_id: row.product_id,
      group_price: Number(row.group_price || 0),
      required_count: Number(row.required_count || 2),
      valid_hours: Number(row.valid_hours || 24),
      max_count: Number(row.max_count || 0),
      stock: Number(row.stock || 0),
      start_time: row.start_time ? dayjs(Number(row.start_time) * 1000).format('YYYY-MM-DD HH:mm:ss') : '',
      end_time: row.end_time ? dayjs(Number(row.end_time) * 1000).format('YYYY-MM-DD HH:mm:ss') : ''
    })
  } else {
    Object.assign(form, {
      id: null,
      product_id: null,
      group_price: 0,
      required_count: 2,
      valid_hours: 24,
      max_count: 0,
      stock: 100,
      start_time: '',
      end_time: ''
    })
  }
  formVisible.value = true
}

async function submitForm() {
  if (!formRef.value) return
  await formRef.value.validate(async (valid) => {
    if (!valid) return
    submitting.value = true
    try {
      const payload = {
        product_id: form.product_id,
        group_price: form.group_price,
        required_count: form.required_count,
        valid_hours: form.valid_hours,
        max_count: form.max_count,
        stock: form.stock,
        start_time: form.start_time,
        end_time: form.end_time
      }
      if (form.id) {
        await updateActivity(form.id, payload)
        ElMessage.success('修改成功')
      } else {
        await createActivity(payload)
        ElMessage.success('创建成功')
      }
      formVisible.value = false
      loadList()
    } finally {
      submitting.value = false
    }
  })
}

async function handleDelete(row) {
  try {
    await ElMessageBox.confirm(
      `确定要停用该拼团活动吗？停用后用户将无法继续参团。`,
      '警告',
      { confirmButtonText: '停用', cancelButtonText: '取消', type: 'warning' }
    )
  } catch (e) {
    return
  }
  await deleteActivity(row.id)
  ElMessage.success('已停用')
  loadList()
}

onMounted(loadList)
</script>

<style scoped>
.table-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}

.toolbar-title {
  font-size: 16px;
  font-weight: 600;
  color: #262626;
}

.product-cell {
  display: flex;
  align-items: center;
  gap: 10px;
}

.product-name {
  font-size: 14px;
  color: #262626;
}

.sub-text {
  font-size: 12px;
  color: #999;
}

.form-tip {
  font-size: 12px;
  color: #999;
  line-height: 1.6;
}
</style>

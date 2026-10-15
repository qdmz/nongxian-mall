<template>
  <div class="page-container">
    <!-- 筛选 -->
    <el-card shadow="never" class="filter-card">
      <el-form inline @submit.prevent>
        <el-form-item label="状态">
          <el-radio-group v-model="query.status" @change="handleSearch">
            <el-radio-button value="">全部</el-radio-button>
            <el-radio-button value="1">启用</el-radio-button>
            <el-radio-button value="0">已停用</el-radio-button>
          </el-radio-group>
        </el-form-item>
        <el-form-item>
          <el-button :icon="Refresh" @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card shadow="never">
      <div class="table-toolbar">
        <div class="toolbar-title">秒杀活动</div>
        <el-button type="primary" :icon="Plus" @click="openForm(null)">新建活动</el-button>
      </div>

      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column label="商品" min-width="200">
          <template #default="{ row }">
            <div class="product-cell">
              <img v-if="row.cover_image" :src="row.cover_image" class="table-img" alt="" />
              <div>
                <div class="product-name">{{ row.title || row.product_name || `商品#${row.product_id}` }}</div>
                <div class="sub-text">商品#{{ row.product_id }}<template v-if="row.sku_id > 0"> · 规格#{{ row.sku_id }}</template> · 单买价 {{ money(row.sku_id > 0 ? row.sku_price : row.product_price) }}</div>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="秒杀价" width="100">
          <template #default="{ row }">
            <span class="money">{{ money(row.seckill_price) }}</span>
          </template>
        </el-table-column>
        <el-table-column label="已售/剩余" width="100" align="center">
          <template #default="{ row }">{{ row.sold }} / {{ row.stock }}</template>
        </el-table-column>
        <el-table-column label="每人限购" width="90" align="center">
          <template #default="{ row }">{{ Number(row.limit_per_user) > 0 ? row.limit_per_user : '不限' }}</template>
        </el-table-column>
        <el-table-column prop="sort" label="排序" width="70" align="center" />
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
            <el-button v-if="Number(row.status) === 1" type="danger" link size="small" @click="handleDelete(row)">停用</el-button>
            <el-button v-else type="success" link size="small" @click="handleEnable(row)">启用</el-button>
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
      :title="form.id ? '编辑秒杀活动' : '新建秒杀活动'"
      width="560px"
      destroy-on-close
    >
      <el-form ref="formRef" :model="form" :rules="rules" label-width="110px">
        <el-row :gutter="12">
          <el-col :span="12">
            <el-form-item label="商品ID" prop="product_id">
              <el-input-number v-model="form.product_id" :min="1" style="width: 100%" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="规格ID">
              <el-input-number v-model="form.sku_id" :min="0" style="width: 100%" />
              <div class="form-tip">0 = 商品默认规格</div>
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="活动标题">
          <el-input v-model="form.title" maxlength="120" placeholder="留空则为「商品名 限时秒杀」" />
        </el-form-item>
        <el-row :gutter="12">
          <el-col :span="12">
            <el-form-item label="秒杀价(元)" prop="seckill_price">
              <el-input-number v-model="form.seckill_price" :precision="2" :min="0.01" style="width: 100%" />
              <div class="form-tip">需低于单买价</div>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="秒杀库存" prop="stock">
              <el-input-number v-model="form.stock" :min="0" :max="999999" style="width: 100%" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="12">
          <el-col :span="12">
            <el-form-item label="每人限购">
              <el-input-number v-model="form.limit_per_user" :min="0" :max="999" style="width: 100%" />
              <div class="form-tip">0 为不限购</div>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="排序">
              <el-input-number v-model="form.sort" :min="0" style="width: 100%" />
              <div class="form-tip">越大越靠前</div>
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="开始时间" prop="start_time">
          <el-date-picker v-model="form.start_time" type="datetime" placeholder="选择开始时间" value-format="YYYY-MM-DD HH:mm:ss" style="width: 100%" />
        </el-form-item>
        <el-form-item label="结束时间" prop="end_time">
          <el-date-picker v-model="form.end_time" type="datetime" placeholder="选择结束时间" value-format="YYYY-MM-DD HH:mm:ss" style="width: 100%" />
        </el-form-item>
        <el-form-item label="启用">
          <el-switch v-model="form.status" :active-value="1" :inactive-value="0" />
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
import { getSeckills, createSeckill, updateSeckill, deleteSeckill } from '../../api/seckill'
import { formatTime, formatMoney } from '../../utils/format'

const money = (v) => formatMoney(v)

const loading = ref(false)
const submitting = ref(false)
const list = ref([])
const total = ref(0)

const query = reactive({ status: '', page: 1, page_size: 15 })

const emptyForm = () => ({
  id: null,
  product_id: null,
  sku_id: 0,
  title: '',
  seckill_price: 0,
  stock: 50,
  limit_per_user: 1,
  sort: 0,
  status: 1,
  start_time: '',
  end_time: ''
})
const formVisible = ref(false)
const formRef = ref(null)
const form = reactive(emptyForm())

const rules = {
  product_id: [{ required: true, message: '请输入商品 ID', trigger: 'blur' }],
  seckill_price: [{ required: true, message: '请输入秒杀价', trigger: 'blur' }],
  stock: [{ required: true, message: '请输入秒杀库存', trigger: 'blur' }],
  start_time: [{ required: true, message: '请选择开始时间', trigger: 'change' }],
  end_time: [{ required: true, message: '请选择结束时间', trigger: 'change' }]
}

function activityStatusText(row) {
  const now = Math.floor(Date.now() / 1000)
  const start = Number(row.start_time || 0)
  const end = Number(row.end_time || 0)
  if (Number(row.status) !== 1) return '已停用'
  if (end && now > end) return '已结束'
  if (start && now < start) return '未开始'
  if (Number(row.stock) <= 0) return '已抢光'
  return '进行中'
}

function activityStatusType(row) {
  const text = activityStatusText(row)
  if (text === '进行中') return 'success'
  if (text === '未开始') return 'info'
  if (text === '已抢光') return 'warning'
  return 'danger'
}

async function loadList() {
  loading.value = true
  try {
    const params = { page: query.page, page_size: query.page_size }
    if (query.status !== '') params.status = query.status
    const data = await getSeckills(params)
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

const fmt = (ts) => (Number(ts) ? dayjs(Number(ts) * 1000).format('YYYY-MM-DD HH:mm:ss') : '')

function openForm(row) {
  if (row) {
    Object.assign(form, {
      id: row.id,
      product_id: Number(row.product_id),
      sku_id: Number(row.sku_id || 0),
      title: row.title || '',
      seckill_price: Number(row.seckill_price || 0),
      stock: Number(row.stock || 0),
      limit_per_user: Number(row.limit_per_user || 0),
      sort: Number(row.sort || 0),
      status: Number(row.status) === 1 ? 1 : 0,
      start_time: fmt(row.start_time),
      end_time: fmt(row.end_time)
    })
  } else {
    Object.assign(form, emptyForm())
  }
  formVisible.value = true
}

async function submitForm() {
  if (!formRef.value) return
  await formRef.value.validate(async (valid) => {
    if (!valid) return
    submitting.value = true
    try {
      const { id, ...payload } = form
      if (id) {
        await updateSeckill(id, payload)
        ElMessage.success('修改成功')
      } else {
        await createSeckill(payload)
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
    await ElMessageBox.confirm('确定要停用该秒杀活动吗？停用后前台不再展示、无法下单。', '警告', {
      confirmButtonText: '停用',
      cancelButtonText: '取消',
      type: 'warning'
    })
  } catch (e) {
    return
  }
  await deleteSeckill(row.id)
  ElMessage.success('已停用')
  loadList()
}

async function handleEnable(row) {
  await updateSeckill(row.id, { status: 1 })
  ElMessage.success('已启用')
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

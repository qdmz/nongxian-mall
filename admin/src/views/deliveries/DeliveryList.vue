<template>
  <div class="page-container">
    <!-- 筛选 -->
    <el-card shadow="never" class="filter-card">
      <el-form inline @submit.prevent>
        <el-form-item label="状态">
          <el-radio-group v-model="query.status" @change="handleSearch">
            <el-radio-button value="">全部</el-radio-button>
            <el-radio-button value="pending">待配送</el-radio-button>
            <el-radio-button value="delivering">配送中</el-radio-button>
            <el-radio-button value="done">已送达</el-radio-button>
            <el-radio-button value="canceled">已取消</el-radio-button>
          </el-radio-group>
        </el-form-item>
        <el-form-item>
          <el-button :icon="Refresh" @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card shadow="never">
      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column prop="order_no" label="订单号" min-width="180" show-overflow-tooltip>
          <template #default="{ row }">
            <el-link type="primary" @click="$router.push(`/orders/${row.order_id}`)">
              {{ row.order_no }}
            </el-link>
          </template>
        </el-table-column>
        <el-table-column label="收货人" min-width="120">
          <template #default="{ row }">
            <div>{{ row.consignee || '-' }}</div>
            <div class="sub-text">{{ row.phone || '' }}</div>
          </template>
        </el-table-column>
        <el-table-column prop="address" label="收货地址" min-width="200" show-overflow-tooltip>
          <template #default="{ row }">{{ row.address || row.address_text || '-' }}</template>
        </el-table-column>
        <el-table-column label="配送方式" width="100">
          <template #default="{ row }">
            <el-tag v-if="row.type === 'self'" type="warning" size="small">自配送</el-tag>
            <el-tag v-else type="info" size="small" effect="plain">快递</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="快递信息" min-width="150">
          <template #default="{ row }">
            <span v-if="row.company">{{ row.company }} {{ row.tracking_no }}</span>
            <span v-else-if="row.courier_name">{{ row.courier_name }} {{ row.courier_phone }}</span>
            <span v-else>-</span>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="90">
          <template #default="{ row }">
            <el-tag :type="deliveryStatusType(row.status)" size="small">
              {{ deliveryStatusText(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="创建时间" min-width="160">
          <template #default="{ row }">{{ formatTime(row.create_time) }}</template>
        </el-table-column>
        <el-table-column label="操作" width="110" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="openTrack(row)">
              添加轨迹
            </el-button>
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

    <!-- 添加轨迹弹窗 -->
    <el-dialog v-model="trackVisible" title="添加配送轨迹" width="460px">
      <el-form ref="trackFormRef" :model="trackForm" :rules="trackRules" label-width="90px">
        <el-form-item label="订单号">
          <span>{{ trackForm.orderNo }}</span>
        </el-form-item>
        <el-form-item label="轨迹描述" prop="description">
          <el-input
            v-model="trackForm.description"
            type="textarea"
            :rows="3"
            placeholder="如：包裹已到达遵义转运中心"
          />
        </el-form-item>
        <el-form-item label="当前位置">
          <el-input v-model="trackForm.location" placeholder="如：贵州遵义" />
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="trackForm.status" style="width: 100%">
            <el-option label="运输中" value="运输中" />
            <el-option label="已到达" value="已到达" />
            <el-option label="派送中" value="派送中" />
            <el-option label="已签收" value="已签收" />
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="trackVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitting" @click="submitTrack">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Refresh } from '@element-plus/icons-vue'
import { getDeliveries, addDeliveryTrack } from '../../api/order'
import { formatTime } from '../../utils/format'

const loading = ref(false)
const submitting = ref(false)
const list = ref([])
const total = ref(0)

const query = reactive({
  status: '',
  page: 1,
  page_size: 15
})

const trackVisible = ref(false)
const trackFormRef = ref(null)
const trackForm = reactive({
  id: null,
  orderNo: '',
  description: '',
  location: '',
  status: '运输中'
})

const trackRules = {
  description: [{ required: true, message: '请输入轨迹描述', trigger: 'blur' }]
}

const STATUS_MAP = {
  pending: { text: '待配送', type: 'warning' },
  delivering: { text: '配送中', type: 'primary' },
  shipping: { text: '配送中', type: 'primary' },
  done: { text: '已送达', type: 'success' },
  finished: { text: '已送达', type: 'success' },
  completed: { text: '已送达', type: 'success' },
  canceled: { text: '已取消', type: 'danger' },
  cancelled: { text: '已取消', type: 'danger' }
}

function deliveryStatusText(s) {
  return (STATUS_MAP[s] && STATUS_MAP[s].text) || (s === 0 ? '待配送' : s === 1 ? '配送中' : s === 2 ? '已送达' : '未知')
}

function deliveryStatusType(s) {
  return (STATUS_MAP[s] && STATUS_MAP[s].type) || 'info'
}

async function loadList() {
  loading.value = true
  try {
    const params = { page: query.page, page_size: query.page_size }
    if (query.status !== '') params.status = query.status
    const data = await getDeliveries(params)
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

function openTrack(row) {
  trackForm.id = row.id
  trackForm.orderNo = row.order_no
  trackForm.description = ''
  trackForm.location = ''
  trackForm.status = '运输中'
  trackVisible.value = true
}

async function submitTrack() {
  if (!trackFormRef.value) return
  await trackFormRef.value.validate(async (valid) => {
    if (!valid) return
    submitting.value = true
    try {
      await addDeliveryTrack(trackForm.id, {
        description: trackForm.description,
        location: trackForm.location,
        status: trackForm.status
      })
      ElMessage.success('轨迹添加成功')
      trackVisible.value = false
      loadList()
    } finally {
      submitting.value = false
    }
  })
}

onMounted(loadList)
</script>

<style scoped>
.sub-text {
  font-size: 12px;
  color: #999;
}
</style>

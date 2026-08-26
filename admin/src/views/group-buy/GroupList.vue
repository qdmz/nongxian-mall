<template>
  <div class="page-container">
    <!-- 筛选 -->
    <el-card shadow="never" class="filter-card">
      <el-form inline @submit.prevent>
        <el-form-item label="状态">
          <el-radio-group v-model="query.status" @change="handleSearch">
            <el-radio-button value="">全部</el-radio-button>
            <el-radio-button value="0">拼团中</el-radio-button>
            <el-radio-button value="1">已成团</el-radio-button>
            <el-radio-button value="2">拼团失败</el-radio-button>
            <el-radio-button value="3">已取消</el-radio-button>
          </el-radio-group>
        </el-form-item>
        <el-form-item>
          <el-button :icon="Refresh" @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card shadow="never">
      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column prop="id" label="拼团单ID" width="100" />
        <el-table-column label="活动/商品" min-width="200">
          <template #default="{ row }">
            <div class="sub-text">活动 #{{ row.activity_id }}</div>
            <div>{{ row.product_name || `商品#${row.product_id}` }}</div>
          </template>
        </el-table-column>
        <el-table-column label="团长" min-width="130">
          <template #default="{ row }">
            {{ row.leader_nickname || row.leader_name || (row.leader_id ? `用户#${row.leader_id}` : '-') }}
          </template>
        </el-table-column>
        <el-table-column label="参团进度" width="120" align="center">
          <template #default="{ row }">
            <span :class="{ 'money': row.current_count >= row.required_count }">
              {{ row.current_count || row.joined_count || 0 }} / {{ row.required_count }}
            </span>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="groupStatusType(row.status)" size="small">
              {{ groupStatusText(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="开团时间" min-width="160">
          <template #default="{ row }">{{ formatTime(row.create_time) }}</template>
        </el-table-column>
        <el-table-column label="截止时间" min-width="160">
          <template #default="{ row }">{{ formatTime(row.expire_time || row.end_time) }}</template>
        </el-table-column>
        <el-table-column label="操作" width="100" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="openDetail(row)">
              查看成员
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

    <!-- 成员抽屉 -->
    <el-drawer v-model="detailVisible" title="拼团成员" size="560px">
      <div v-loading="detailLoading">
        <el-descriptions v-if="detail" :column="2" border size="small" title="拼团信息">
          <el-descriptions-item label="拼团单ID">{{ detail.id }}</el-descriptions-item>
          <el-descriptions-item label="状态">
            <el-tag :type="groupStatusType(detail.status)" size="small">
              {{ groupStatusText(detail.status) }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="成团人数">
            {{ detail.required_count }} 人
          </el-descriptions-item>
          <el-descriptions-item label="已参团">
            {{ detail.current_count || (detail.members && detail.members.length) || 0 }} 人
          </el-descriptions-item>
        </el-descriptions>

        <h4 class="drawer-section-title">参团成员</h4>
        <el-table :data="(detail && detail.members) || []" size="small" border>
          <el-table-column prop="user_id" label="用户ID" width="80" />
          <el-table-column label="昵称" min-width="120">
            <template #default="{ row }">{{ row.nickname || row.user_nickname || '-' }}</template>
          </el-table-column>
          <el-table-column label="是否团长" width="90" align="center">
            <template #default="{ row }">
              <el-tag v-if="row.is_leader == 1 || row.is_leader === true" type="danger" size="small">
                团长
              </el-tag>
              <span v-else class="sub-text">成员</span>
            </template>
          </el-table-column>
          <el-table-column label="参团时间" min-width="150">
            <template #default="{ row }">{{ formatTime(row.create_time || row.join_time) }}</template>
          </el-table-column>
        </el-table>
        <el-empty
          v-if="detail && !(detail.members || []).length"
          description="暂无成员"
          :image-size="60"
        />
      </div>
    </el-drawer>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { Refresh } from '@element-plus/icons-vue'
import { getGroups, getGroupDetail } from '../../api/groupBuy'
import { formatTime, groupStatusText, groupStatusType } from '../../utils/format'

const loading = ref(false)
const detailLoading = ref(false)
const list = ref([])
const total = ref(0)
const detailVisible = ref(false)
const detail = ref(null)

const query = reactive({
  status: '',
  page: 1,
  page_size: 15
})

async function loadList() {
  loading.value = true
  try {
    const params = { page: query.page, page_size: query.page_size }
    if (query.status !== '') params.status = query.status
    const data = await getGroups(params)
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

async function openDetail(row) {
  detailVisible.value = true
  detailLoading.value = true
  detail.value = null
  try {
    detail.value = await getGroupDetail(row.id)
  } finally {
    detailLoading.value = false
  }
}

onMounted(loadList)
</script>

<style scoped>
.sub-text {
  font-size: 12px;
  color: #999;
}

.drawer-section-title {
  margin: 20px 0 10px;
  font-size: 14px;
  color: #262626;
  border-left: 3px solid #d4380d;
  padding-left: 8px;
}
</style>

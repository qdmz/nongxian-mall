<template>
  <div class="page-container" v-loading="loading">
    <el-card shadow="never">
      <template #header>
        <div class="card-header">
          <span>订单详情</span>
          <div>
            <el-button
              v-if="detail && detail.status == 1"
              type="success"
              @click="openDeliver"
            >
              发货
            </el-button>
            <el-button
              v-if="detail && detail.status == 2"
              type="warning"
              @click="handleComplete"
            >
              完成订单
            </el-button>
            <el-button
              v-if="detail && (detail.status == 0 || detail.status == 1)"
              type="danger"
              plain
              @click="handleCancel"
            >
              取消订单
            </el-button>
            <el-button @click="$router.back()">返回</el-button>
          </div>
        </div>
      </template>

      <template v-if="detail">
        <!-- 订单信息 -->
        <el-descriptions title="订单信息" :column="3" border size="small" class="section">
          <el-descriptions-item label="订单号">{{ detail.order_no }}</el-descriptions-item>
          <el-descriptions-item label="订单状态">
            <el-tag :type="orderStatusType(detail.status)" size="small">
              {{ orderStatusText(detail.status) }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="订单类型">
            {{ orderTypeText(detail.type) }}
          </el-descriptions-item>
          <el-descriptions-item label="商品总额">
            {{ money(detail.total_amount) }}
          </el-descriptions-item>
          <el-descriptions-item label="实付金额">
            <span class="money">{{ money(detail.pay_amount) }}</span>
          </el-descriptions-item>
          <el-descriptions-item label="支付方式">
            {{ payTypeText(detail.pay_type) }}
          </el-descriptions-item>
          <el-descriptions-item label="下单时间">
            {{ formatTime(detail.create_time) }}
          </el-descriptions-item>
          <el-descriptions-item label="支付时间">
            {{ formatTime(detail.pay_time) }}
          </el-descriptions-item>
          <el-descriptions-item label="完成时间">
            {{ formatTime(detail.finish_time) }}
          </el-descriptions-item>
          <el-descriptions-item label="买家留言" :span="3">
            {{ detail.remark || '-' }}
          </el-descriptions-item>
        </el-descriptions>

        <!-- 买家信息 -->
        <el-descriptions title="买家信息" :column="3" border size="small" class="section">
          <el-descriptions-item label="用户ID">{{ detail.user_id }}</el-descriptions-item>
          <el-descriptions-item label="昵称">
            {{ detail.user_nickname || detail.nickname || '-' }}
          </el-descriptions-item>
          <el-descriptions-item label="手机号">
            {{ detail.user_phone || detail.phone || '-' }}
          </el-descriptions-item>
        </el-descriptions>

        <!-- 收货信息 -->
        <el-descriptions title="收货信息" :column="3" border size="small" class="section">
          <el-descriptions-item label="收货人">
            {{ (detail.address && detail.address.consignee) || detail.consignee || '-' }}
          </el-descriptions-item>
          <el-descriptions-item label="联系电话">
            {{ (detail.address && detail.address.phone) || detail.phone || '-' }}
          </el-descriptions-item>
          <el-descriptions-item label="收货地址" :span="2">
            {{
              (detail.address && detail.address.full) ||
              (detail.address &&
                [detail.address.province, detail.address.city, detail.address.district, detail.address.detail]
                  .filter(Boolean)
                  .join('')) ||
              detail.address_text ||
              '-'
            }}
          </el-descriptions-item>
        </el-descriptions>

        <!-- 商品明细 -->
        <h4 class="section-title">商品明细</h4>
        <el-table :data="detail.items || []" border size="small">
          <el-table-column label="商品" min-width="240">
            <template #default="{ row }">
              <div class="item-cell">
                <img v-if="row.image" :src="row.image" class="table-img" alt="" />
                <div>
                  <div>{{ row.product_name || row.name }}</div>
                  <div class="sub-text" v-if="row.spec_name || row.sku_name">
                    规格：{{ row.spec_name || row.sku_name }}
                  </div>
                </div>
              </div>
            </template>
          </el-table-column>
          <el-table-column label="单价" width="110">
            <template #default="{ row }">{{ money(row.price) }}</template>
          </el-table-column>
          <el-table-column label="数量" width="80" align="center">
            <template #default="{ row }">x{{ row.quantity || row.num }}</template>
          </el-table-column>
          <el-table-column label="小计" width="110">
            <template #default="{ row }">
              <span class="money">{{ money((Number(row.price) || 0) * (Number(row.quantity || row.num) || 0)) }}</span>
            </template>
          </el-table-column>
        </el-table>

        <!-- 配送信息 -->
        <template v-if="detail.delivery">
          <h4 class="section-title">配送信息</h4>
          <el-descriptions :column="3" border size="small" class="section">
            <el-descriptions-item label="配送方式">
              {{ detail.delivery.type === 'self' ? '自配送' : '快递配送' }}
            </el-descriptions-item>
            <el-descriptions-item label="快递公司">
              {{ detail.delivery.company || '-' }}
            </el-descriptions-item>
            <el-descriptions-item label="快递单号">
              {{ detail.delivery.tracking_no || '-' }}
            </el-descriptions-item>
            <el-descriptions-item label="配送员">
              {{ detail.delivery.courier_name || '-' }}
            </el-descriptions-item>
            <el-descriptions-item label="配送员电话">
              {{ detail.delivery.courier_phone || '-' }}
            </el-descriptions-item>
            <el-descriptions-item label="发货时间">
              {{ formatTime(detail.delivery.create_time) }}
            </el-descriptions-item>
          </el-descriptions>
          <template v-if="(detail.delivery.tracks || []).length">
            <h4 class="section-title">配送轨迹</h4>
            <el-timeline class="track-timeline">
              <el-timeline-item
                v-for="(t, i) in detail.delivery.tracks"
                :key="i"
                :timestamp="formatTime(t.create_time)"
                :type="i === 0 ? 'primary' : ''"
              >
                <div class="track-desc">{{ t.description }}</div>
                <div class="sub-text">{{ t.location || '' }} <el-tag v-if="t.status" size="small" class="track-status">{{ t.status }}</el-tag></div>
              </el-timeline-item>
            </el-timeline>
          </template>
        </template>

        <!-- 订单日志时间线 -->
        <h4 class="section-title">订单日志</h4>
        <el-timeline class="track-timeline">
          <el-timeline-item
            v-for="(log, i) in detail.logs || []"
            :key="i"
            :timestamp="formatTime(log.create_time)"
            :type="i === 0 ? 'primary' : ''"
          >
            {{ log.description || log.content || log.action }}
            <span v-if="log.operator" class="sub-text">（操作人：{{ log.operator }}）</span>
          </el-timeline-item>
        </el-timeline>
        <el-empty v-if="!(detail.logs || []).length" description="暂无日志" :image-size="60" />

        <!-- 退款处理 -->
        <template v-if="detail.refund && detail.status == 6">
          <h4 class="section-title">退款申请</h4>
          <el-descriptions :column="2" border size="small" class="section">
            <el-descriptions-item label="退款原因">
              {{ detail.refund.reason }}
            </el-descriptions-item>
            <el-descriptions-item label="申请时间">
              {{ formatTime(detail.refund.create_time) }}
            </el-descriptions-item>
            <el-descriptions-item label="退款金额">
              <span class="money">{{ money(detail.refund.amount || detail.pay_amount) }}</span>
            </el-descriptions-item>
            <el-descriptions-item label="操作">
              <el-button type="success" size="small" @click="handleRefund('approve')">
                同意退款
              </el-button>
              <el-button type="danger" size="small" @click="handleRefund('reject')">
                拒绝退款
              </el-button>
            </el-descriptions-item>
          </el-descriptions>
        </template>
      </template>
    </el-card>

    <!-- 发货弹窗（与列表页一致） -->
    <el-dialog v-model="deliverVisible" title="订单发货" width="480px">
      <el-form ref="deliverFormRef" :model="deliverForm" :rules="deliverRules" label-width="100px">
        <el-form-item label="发货方式">
          <el-radio-group v-model="deliverForm.mode">
            <el-radio value="express">快递配送</el-radio>
            <el-radio value="self">自配送</el-radio>
          </el-radio-group>
        </el-form-item>
        <template v-if="deliverForm.mode === 'express'">
          <el-form-item label="快递公司" prop="company">
            <el-select v-model="deliverForm.company" filterable allow-create style="width: 100%">
              <el-option label="顺丰速运" value="顺丰速运" />
              <el-option label="中通快递" value="中通快递" />
              <el-option label="圆通速递" value="圆通速递" />
              <el-option label="申通快递" value="申通快递" />
              <el-option label="韵达快递" value="韵达快递" />
              <el-option label="邮政EMS" value="邮政EMS" />
              <el-option label="京东物流" value="京东物流" />
            </el-select>
          </el-form-item>
          <el-form-item label="快递单号" prop="tracking_no">
            <el-input v-model="deliverForm.tracking_no" placeholder="请输入快递单号" />
          </el-form-item>
        </template>
        <template v-else>
          <el-form-item label="配送员姓名" prop="courier_name">
            <el-input v-model="deliverForm.courier_name" placeholder="请输入配送员姓名" />
          </el-form-item>
          <el-form-item label="配送员电话" prop="courier_phone">
            <el-input v-model="deliverForm.courier_phone" placeholder="请输入配送员电话" />
          </el-form-item>
        </template>
      </el-form>
      <template #footer>
        <el-button @click="deliverVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitting" @click="submitDeliver">确认发货</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getOrderDetail, deliverOrder, completeOrder, cancelOrder, handleRefund as handleRefundApi } from '../../api/order'
import {
  formatTime,
  formatMoney,
  orderStatusText,
  orderStatusType,
  payTypeText,
  orderTypeText
} from '../../utils/format'

const money = (v) => formatMoney(v)

const route = useRoute()
const loading = ref(false)
const submitting = ref(false)
const detail = ref(null)

const deliverVisible = ref(false)
const deliverFormRef = ref(null)
const deliverForm = reactive({
  mode: 'express',
  company: '',
  tracking_no: '',
  courier_name: '',
  courier_phone: ''
})

const deliverRules = {
  company: [{ required: true, message: '请选择或输入快递公司', trigger: 'change' }],
  tracking_no: [{ required: true, message: '请输入快递单号', trigger: 'blur' }],
  courier_name: [{ required: true, message: '请输入配送员姓名', trigger: 'blur' }],
  courier_phone: [
    { required: true, message: '请输入配送员电话', trigger: 'blur' },
    { pattern: /^1[3-9]\d{9}$/, message: '手机号格式不正确', trigger: 'blur' }
  ]
}

async function loadDetail() {
  loading.value = true
  try {
    detail.value = await getOrderDetail(route.params.id)
  } finally {
    loading.value = false
  }
}

function openDeliver() {
  deliverForm.mode = 'express'
  deliverForm.company = ''
  deliverForm.tracking_no = ''
  deliverForm.courier_name = ''
  deliverForm.courier_phone = ''
  deliverVisible.value = true
}

async function submitDeliver() {
  if (!deliverFormRef.value) return
  await deliverFormRef.value.validate(async (valid) => {
    if (!valid) return
    submitting.value = true
    try {
      const payload =
        deliverForm.mode === 'express'
          ? { company: deliverForm.company, tracking_no: deliverForm.tracking_no }
          : { courier_name: deliverForm.courier_name, courier_phone: deliverForm.courier_phone }
      await deliverOrder(route.params.id, payload)
      ElMessage.success('发货成功')
      deliverVisible.value = false
      loadDetail()
    } finally {
      submitting.value = false
    }
  })
}

async function handleComplete() {
  try {
    await ElMessageBox.confirm('确定要将该订单标记为已完成吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })
  } catch (e) {
    return
  }
  await completeOrder(route.params.id)
  ElMessage.success('订单已完成')
  loadDetail()
}

async function handleCancel() {
  let reason = ''
  try {
    const { value } = await ElMessageBox.prompt('请输入取消原因', '取消订单', {
      confirmButtonText: '确定取消',
      cancelButtonText: '返回',
      inputPlaceholder: '取消原因（必填）',
      inputValidator: (v) => !!(v && v.trim()) || '请输入取消原因'
    })
    reason = (value || '').trim()
  } catch (e) {
    return
  }
  await cancelOrder(route.params.id, { reason })
  ElMessage.success('订单已取消')
  loadDetail()
}

async function handleRefund(action) {
  const refundId = detail.value.refund.id || detail.value.refund_id
  if (action === 'approve') {
    try {
      await ElMessageBox.confirm('确定同意退款吗？退款金额将原路退回用户。', '同意退款', {
        confirmButtonText: '同意',
        cancelButtonText: '取消',
        type: 'warning'
      })
    } catch (e) {
      return
    }
    await handleRefundApi(refundId, { action: 'approve' })
    ElMessage.success('已同意退款')
  } else {
    let reason = ''
    try {
      const { value } = await ElMessageBox.prompt('请输入拒绝原因', '拒绝退款', {
        confirmButtonText: '确定拒绝',
        cancelButtonText: '取消',
        inputPlaceholder: '拒绝原因（必填）',
        inputValidator: (v) => !!(v && v.trim()) || '请输入拒绝原因'
      })
      reason = (value || '').trim()
    } catch (e) {
      return
    }
    await handleRefundApi(refundId, { action: 'reject', reject_reason: reason })
    ElMessage.success('已拒绝退款')
  }
  loadDetail()
}

onMounted(loadDetail)
</script>

<style scoped>
.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.section {
  margin-bottom: 20px;
}

.section-title {
  margin: 20px 0 12px;
  font-size: 15px;
  color: #262626;
  border-left: 3px solid #d4380d;
  padding-left: 8px;
}

.item-cell {
  display: flex;
  align-items: center;
  gap: 10px;
}

.sub-text {
  font-size: 12px;
  color: #999;
}

.track-timeline {
  padding: 10px 0 10px 6px;
  max-width: 640px;
}

.track-desc {
  font-size: 14px;
  color: #262626;
}

.track-status {
  margin-left: 6px;
}
</style>

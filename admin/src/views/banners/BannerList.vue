<template>
  <div class="page-container">
    <el-card shadow="never">
      <div class="table-toolbar">
        <div class="toolbar-title">轮播图管理</div>
        <el-button type="primary" :icon="Plus" @click="openForm(null)">新增轮播图</el-button>
      </div>

      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column label="图片" width="160">
          <template #default="{ row }">
            <img :src="row.image" class="banner-img" alt="" />
          </template>
        </el-table-column>
        <el-table-column prop="title" label="标题" min-width="140" show-overflow-tooltip>
          <template #default="{ row }">{{ row.title || '-' }}</template>
        </el-table-column>
        <el-table-column label="跳转类型" width="110">
          <template #default="{ row }">{{ linkTypeText(row.link_type) }}</template>
        </el-table-column>
        <el-table-column prop="link_value" label="跳转目标" min-width="140" show-overflow-tooltip>
          <template #default="{ row }">{{ row.link_value || '-' }}</template>
        </el-table-column>
        <el-table-column prop="position" label="位置" width="90" align="center">
          <template #default="{ row }">{{ row.position || '首页' }}</template>
        </el-table-column>
        <el-table-column prop="sort" label="排序" width="80" align="center" />
        <el-table-column label="状态" width="90" align="center">
          <template #default="{ row }">
            <el-tag :type="row.status == 1 ? 'success' : 'info'" size="small">
              {{ row.status == 1 ? '启用' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="140" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="openForm(row)">编辑</el-button>
            <el-button type="danger" link size="small" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <!-- 新增/编辑弹窗 -->
    <el-dialog
      v-model="formVisible"
      :title="form.id ? '编辑轮播图' : '新增轮播图'"
      width="520px"
      destroy-on-close
    >
      <el-form ref="formRef" :model="form" :rules="rules" label-width="90px">
        <el-form-item label="轮播图片" prop="image">
          <div class="upload-area">
            <img v-if="form.image" :src="form.image" class="upload-preview" alt="" />
            <el-button
              v-else
              :icon="Upload"
              :loading="uploading"
              @click="fileInputRef && fileInputRef.click()"
            >
              上传图片
            </el-button>
            <el-button v-if="form.image" type="danger" link @click="form.image = ''">删除</el-button>
          </div>
          <div class="form-tip">建议 750x340，支持 jpg/png/webp</div>
        </el-form-item>
        <el-form-item label="标题">
          <el-input v-model="form.title" placeholder="轮播图标题（选填）" maxlength="50" />
        </el-form-item>
        <el-form-item label="跳转类型">
          <el-select v-model="form.link_type" style="width: 100%">
            <el-option label="无跳转" value="none" />
            <el-option label="商品详情" value="product" />
            <el-option label="分类页" value="category" />
            <el-option label="拼团活动" value="group_buy" />
            <el-option label="外部链接" value="url" />
          </el-select>
        </el-form-item>
        <el-form-item v-if="form.link_type !== 'none'" label="跳转目标">
          <el-input
            v-model="form.link_value"
            :placeholder="linkValuePlaceholder"
          />
        </el-form-item>
        <el-form-item label="显示位置">
          <el-select v-model="form.position" style="width: 100%">
            <el-option label="首页" value="home" />
          </el-select>
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="form.sort" :min="0" :max="9999" />
          <div class="form-tip">数值越小越靠前</div>
        </el-form-item>
        <el-form-item label="状态">
          <el-switch v-model="form.status" :active-value="1" :inactive-value="0" active-text="启用" inactive-text="禁用" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="formVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitting" @click="submitForm">保存</el-button>
      </template>
    </el-dialog>

    <input
      ref="fileInputRef"
      type="file"
      accept="image/*"
      style="display: none"
      @change="handleFileChange"
    />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus, Upload } from '@element-plus/icons-vue'
import { getBanners, createBanner, updateBanner, deleteBanner } from '../../api/banner'
import { uploadImage } from '../../api/upload'

const loading = ref(false)
const submitting = ref(false)
const uploading = ref(false)
const list = ref([])
const fileInputRef = ref(null)
const formRef = ref(null)

const formVisible = ref(false)
const form = reactive({
  id: null,
  image: '',
  title: '',
  link_type: 'none',
  link_value: '',
  position: 'home',
  sort: 0,
  status: 1
})

const rules = {
  image: [{ required: true, message: '请上传轮播图片', trigger: 'change' }]
}

const linkValuePlaceholder = computed(() => {
  const map = {
    product: '请输入商品 ID',
    category: '请输入分类 ID',
    group_buy: '请输入拼团活动 ID',
    url: '请输入完整链接 https://'
  }
  return map[form.link_type] || ''
})

function linkTypeText(t) {
  const map = {
    none: '无跳转',
    product: '商品详情',
    category: '分类页',
    group_buy: '拼团活动',
    url: '外部链接'
  }
  return map[t] || t || '无跳转'
}

async function loadList() {
  loading.value = true
  try {
    const data = await getBanners()
    list.value = Array.isArray(data) ? data : (data && data.list) || []
  } finally {
    loading.value = false
  }
}

function openForm(row) {
  if (row) {
    Object.assign(form, {
      id: row.id,
      image: row.image || '',
      title: row.title || '',
      link_type: row.link_type || 'none',
      link_value: row.link_value || '',
      position: row.position || 'home',
      sort: row.sort || 0,
      status: row.status == 1 ? 1 : 0
    })
  } else {
    Object.assign(form, {
      id: null,
      image: '',
      title: '',
      link_type: 'none',
      link_value: '',
      position: 'home',
      sort: 0,
      status: 1
    })
  }
  formVisible.value = true
}

async function handleFileChange(e) {
  const file = e.target.files && e.target.files[0]
  if (!file) return
  uploading.value = true
  try {
    const data = await uploadImage(file)
    form.image = data.url
    ElMessage.success('上传成功')
  } finally {
    uploading.value = false
    e.target.value = ''
  }
}

async function submitForm() {
  if (!formRef.value) return
  await formRef.value.validate(async (valid) => {
    if (!valid) return
    submitting.value = true
    try {
      const payload = {
        image: form.image,
        title: form.title,
        link_type: form.link_type,
        link_value: form.link_value,
        position: form.position,
        sort: form.sort,
        status: form.status
      }
      if (form.id) {
        await updateBanner(form.id, payload)
        ElMessage.success('修改成功')
      } else {
        await createBanner(payload)
        ElMessage.success('新增成功')
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
    await ElMessageBox.confirm('确定要删除该轮播图吗？', '警告', {
      confirmButtonText: '删除',
      cancelButtonText: '取消',
      type: 'warning'
    })
  } catch (e) {
    return
  }
  await deleteBanner(row.id)
  ElMessage.success('删除成功')
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

.banner-img {
  width: 120px;
  height: 54px;
  object-fit: cover;
  border-radius: 4px;
  border: 1px solid #eee;
}

.upload-area {
  display: flex;
  align-items: center;
  gap: 12px;
}

.upload-preview {
  width: 160px;
  height: 72px;
  object-fit: cover;
  border-radius: 6px;
  border: 1px solid #eee;
}

.form-tip {
  font-size: 12px;
  color: #999;
  line-height: 1.6;
}
</style>

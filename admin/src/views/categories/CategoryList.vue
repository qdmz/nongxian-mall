<template>
  <div class="page-container">
    <el-card shadow="never">
      <div class="table-toolbar">
        <div class="toolbar-title">分类列表</div>
        <el-button type="primary" :icon="Plus" @click="openForm(null)">新增分类</el-button>
      </div>

      <el-table
        :data="treeData"
        v-loading="loading"
        row-key="id"
        :tree-props="{ children: 'children' }"
        default-expand-all
        stripe
      >
        <el-table-column prop="name" label="分类名称" min-width="220">
          <template #default="{ row }">
            <span class="cat-name">
              {{ row.name }}
              <el-tag v-if="row.is_red == 1" type="danger" size="small" effect="plain">
                红色助农
              </el-tag>
            </span>
          </template>
        </el-table-column>
        <el-table-column prop="product_count" label="商品数" width="90" align="center" />
        <el-table-column prop="sort" label="排序" width="80" align="center" />
        <el-table-column label="状态" width="90" align="center">
          <template #default="{ row }">
            <el-tag :type="row.status == 1 ? 'success' : 'info'" size="small">
              {{ row.status == 1 ? '显示' : '隐藏' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="图标/图片" width="110" align="center">
          <template #default="{ row }">
            <img v-if="row.image" :src="row.image" class="table-img" alt="" />
            <img v-else-if="row.icon" :src="row.icon" class="table-img" alt="" />
            <span v-else>-</span>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="openForm(row)">编辑</el-button>
            <el-button
              v-if="!row.parent_id"
              type="success"
              link
              size="small"
              @click="openForm(null, row.id)"
            >
              加子分类
            </el-button>
            <el-button type="danger" link size="small" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <!-- 新增/编辑弹窗 -->
    <el-dialog
      v-model="formVisible"
      :title="form.id ? '编辑分类' : '新增分类'"
      width="500px"
      destroy-on-close
    >
      <el-form ref="formRef" :model="form" :rules="rules" label-width="90px">
        <el-form-item label="上级分类">
          <el-select v-model="form.parent_id" placeholder="顶级分类" clearable style="width: 100%">
            <el-option
              v-for="item in topCategories"
              :key="item.id"
              :label="item.name"
              :value="item.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="分类名称" prop="name">
          <el-input v-model="form.name" placeholder="请输入分类名称" maxlength="30" />
        </el-form-item>
        <el-form-item label="图片">
          <div class="upload-area">
            <img v-if="form.image" :src="form.image" class="upload-preview" alt="" />
            <el-button
              v-else
              :icon="Upload"
              :loading="uploading"
              size="small"
              @click="triggerUpload"
            >
              上传图片
            </el-button>
            <el-button
              v-if="form.image"
              type="danger"
              link
              size="small"
              @click="form.image = ''"
            >
              删除
            </el-button>
          </div>
          <input
            ref="fileInputRef"
            type="file"
            accept="image/*"
            style="display: none"
            @change="handleFileChange"
          />
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="form.sort" :min="0" :max="9999" />
          <div class="form-tip">数值越小越靠前</div>
        </el-form-item>
        <el-form-item label="状态">
          <el-switch
            v-model="form.status"
            :active-value="1"
            :inactive-value="0"
            active-text="显示"
            inactive-text="隐藏"
          />
        </el-form-item>
        <el-form-item label="红色助农">
          <el-switch v-model="form.is_red" :active-value="1" :inactive-value="0" />
          <div class="form-tip">开启后归入红色助农专区</div>
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
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus, Upload } from '@element-plus/icons-vue'
import {
  getCategories,
  createCategory,
  updateCategory,
  deleteCategory
} from '../../api/category'
import { uploadImage } from '../../api/upload'

const loading = ref(false)
const submitting = ref(false)
const uploading = ref(false)
const rawList = ref([])
const fileInputRef = ref(null)
const formRef = ref(null)

const formVisible = ref(false)
const form = reactive({
  id: null,
  name: '',
  parent_id: null,
  icon: '',
  image: '',
  sort: 0,
  status: 1,
  is_red: 0
})

const rules = {
  name: [{ required: true, message: '请输入分类名称', trigger: 'blur' }]
}

// 组装树形结构
const treeData = computed(() => {
  const tops = rawList.value.filter((c) => !c.parent_id || c.parent_id === 0)
  const build = (parent) => {
    const children = rawList.value.filter((c) => c.parent_id === parent.id)
    if (children.length) {
      return children.map((c) => ({ ...c, children: build(c) }))
    }
    return []
  }
  return tops.map((t) => ({ ...t, children: build(t) }))
})

const topCategories = computed(() =>
  rawList.value.filter((c) => !c.parent_id || c.parent_id === 0)
)

async function loadList() {
  loading.value = true
  try {
    const data = await getCategories()
    rawList.value = Array.isArray(data) ? data : (data && data.list) || []
  } finally {
    loading.value = false
  }
}

function openForm(row, parentId = null) {
  if (row) {
    Object.assign(form, {
      id: row.id,
      name: row.name,
      parent_id: row.parent_id || null,
      icon: row.icon || '',
      image: row.image || '',
      sort: row.sort || 0,
      status: row.status === 0 ? 0 : 1,
      is_red: row.is_red === 1 ? 1 : 0
    })
  } else {
    Object.assign(form, {
      id: null,
      name: '',
      parent_id: parentId,
      icon: '',
      image: '',
      sort: 0,
      status: 1,
      is_red: 0
    })
  }
  formVisible.value = true
}

function triggerUpload() {
  fileInputRef.value && fileInputRef.value.click()
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
        name: form.name,
        parent_id: form.parent_id || 0,
        icon: form.icon || '',
        image: form.image || '',
        sort: form.sort,
        status: form.status,
        is_red: form.is_red
      }
      if (form.id) {
        await updateCategory(form.id, payload)
        ElMessage.success('修改成功')
      } else {
        await createCategory(payload)
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
    await ElMessageBox.confirm(
      `确定要删除分类「${row.name}」吗？删除后不可恢复！`,
      '警告',
      { confirmButtonText: '删除', cancelButtonText: '取消', type: 'warning' }
    )
  } catch (e) {
    return
  }
  await deleteCategory(row.id)
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

.cat-name {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.upload-area {
  display: flex;
  align-items: center;
  gap: 12px;
}

.upload-preview {
  width: 72px;
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

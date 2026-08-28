<template>
  <div class="page-container" v-loading="pageLoading">
    <el-card shadow="never">
      <template #header>
        <div class="card-header">
          <span>{{ isEdit ? '编辑商品' : '新建商品' }}</span>
          <el-button @click="$router.push('/products')">返回列表</el-button>
        </div>
      </template>

      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="110px"
        style="max-width: 900px"
      >
        <el-divider content-position="left">基本信息</el-divider>
        <el-form-item label="商品名称" prop="name">
          <el-input v-model="form.name" placeholder="请输入商品名称" maxlength="100" show-word-limit />
        </el-form-item>
        <el-form-item label="副标题">
          <el-input v-model="form.subtitle" placeholder="一句话卖点" maxlength="200" />
        </el-form-item>
        <el-form-item label="商品分类" prop="category_id">
          <el-cascader
            v-model="form.category_id"
            :options="categoryOptions"
            :props="{ value: 'id', label: 'name', checkStrictly: true, emitPath: false }"
            placeholder="请选择分类"
            clearable
            style="width: 320px"
          />
        </el-form-item>
        <el-row :gutter="16">
          <el-col :span="8">
            <el-form-item label="单位" prop="unit">
              <el-select v-model="form.unit" style="width: 100%">
                <el-option label="斤" value="斤" />
                <el-option label="盒" value="盒" />
                <el-option label="箱" value="箱" />
                <el-option label="件" value="件" />
                <el-option label="袋" value="袋" />
                <el-option label="瓶" value="瓶" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="售价(元)" prop="price">
              <el-input-number
                v-model="form.price"
                :precision="2"
                :min="0"
                style="width: 100%"
              />
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="原价(元)">
              <el-input-number
                v-model="form.original_price"
                :precision="2"
                :min="0"
                style="width: 100%"
              />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="16">
          <el-col :span="8">
            <el-form-item label="成本价(元)">
              <el-input-number
                v-model="form.cost_price"
                :precision="2"
                :min="0"
                style="width: 100%"
              />
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="库存" prop="stock">
              <el-input-number v-model="form.stock" :min="0" :max="999999" style="width: 100%" />
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="库存预警值">
              <el-input-number
                v-model="form.stock_warning"
                :min="0"
                :max="99999"
                style="width: 100%"
              />
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="虚拟销量">
          <el-input-number v-model="form.virtual_sales" :min="0" :max="999999" />
          <span class="form-tip-inline">展示销量 = 真实销量 + 虚拟销量</span>
        </el-form-item>

        <el-divider content-position="left">商品标签</el-divider>
        <el-form-item label="标签开关">
          <div class="flag-group">
            <div class="flag-item">
              <el-switch v-model="form.is_hot" :active-value="1" :inactive-value="0" />
              <span class="flag-label">热销</span>
            </div>
            <div class="flag-item">
              <el-switch v-model="form.is_new" :active-value="1" :inactive-value="0" />
              <span class="flag-label">新品</span>
            </div>
            <div class="flag-item">
              <el-switch v-model="form.is_recommend" :active-value="1" :inactive-value="0" />
              <span class="flag-label">党员推荐</span>
            </div>
            <div class="flag-item">
              <el-switch v-model="form.is_red" :active-value="1" :inactive-value="0" />
              <span class="flag-label">红色助农专区</span>
            </div>
          </div>
        </el-form-item>

        <el-divider content-position="left">助农信息</el-divider>
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="产地">
              <el-input v-model="form.origin" placeholder="如：贵州遵义田冲村" maxlength="60" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="农户/合作社">
              <el-input
                v-model="form.farmer"
                placeholder="如：田冲村种植合作社"
                maxlength="60"
              />
            </el-form-item>
          </el-col>
        </el-row>

        <el-divider content-position="left">商品图片</el-divider>
        <el-form-item label="封面图">
          <div class="img-uploader" @click="triggerUpload('cover')">
            <img v-if="form.cover_image" :src="form.cover_image" class="cover-preview" alt="封面图" />
            <el-icon v-else class="uploader-icon"><Plus /></el-icon>
          </div>
          <div class="form-tip">建议 800x800，支持 jpg/png/webp</div>
        </el-form-item>
        <el-form-item label="轮播图">
          <div class="banner-list">
            <div v-for="(img, index) in form.images" :key="index" class="banner-item">
              <img :src="img" class="banner-preview" alt="" />
              <el-icon class="banner-remove" @click.stop="removeBanner(index)"><CircleClose /></el-icon>
            </div>
            <div v-if="form.images.length < 9" class="img-uploader" @click="triggerUpload('banner')">
              <el-icon class="uploader-icon"><Plus /></el-icon>
            </div>
          </div>
          <div class="form-tip">最多 9 张，点击已上传图片右上角可删除</div>
        </el-form-item>

        <el-divider content-position="left">商品详情</el-divider>
        <el-form-item label="图文详情">
          <el-input
            v-model="form.detail"
            type="textarea"
            :rows="8"
            placeholder="支持纯文本或简单 HTML"
          />
        </el-form-item>

        <el-divider content-position="left">SKU 规格</el-divider>
        <el-form-item label="规格列表">
          <div class="sku-toolbar">
            <el-button type="primary" plain size="small" :icon="Plus" @click="addSku">
              添加规格
            </el-button>
            <span class="form-tip-inline">无规格商品可不填；填写后按 SKU 价格/库存销售</span>
          </div>
          <el-table :data="form.skus" size="small" border class="sku-table">
            <el-table-column label="规格名" min-width="160">
              <template #default="{ row }">
                <el-input v-model="row.spec_name" placeholder="如：5斤装" />
              </template>
            </el-table-column>
            <el-table-column label="价格(元)" width="180">
              <template #default="{ row }">
                <el-input-number v-model="row.price" :precision="2" :min="0" size="small" style="width: 100%" />
              </template>
            </el-table-column>
            <el-table-column label="库存" width="180">
              <template #default="{ row }">
                <el-input-number v-model="row.stock" :min="0" :max="999999" size="small" style="width: 100%" />
              </template>
            </el-table-column>
            <el-table-column label="操作" width="80" align="center">
              <template #default="{ $index }">
                <el-button type="danger" link size="small" @click="removeSku($index)">删除</el-button>
              </template>
            </el-table-column>
          </el-table>
        </el-form-item>

        <el-divider content-position="left">上架状态</el-divider>
        <el-form-item label="状态">
          <el-radio-group v-model="form.status">
            <el-radio :value="1">上架</el-radio>
            <el-radio :value="0">下架</el-radio>
            <el-radio :value="2">草稿</el-radio>
          </el-radio-group>
        </el-form-item>

        <el-form-item>
          <el-button type="primary" :loading="submitting" @click="submitForm">
            {{ isEdit ? '保存修改' : '立即创建' }}
          </el-button>
          <el-button @click="$router.push('/products')">取消</el-button>
        </el-form-item>
      </el-form>
    </el-card>

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
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import { getProductDetail, createProduct, updateProduct } from '../../api/product'
import { getCategories } from '../../api/category'
import { uploadImage } from '../../api/upload'

const route = useRoute()
const router = useRouter()

const isEdit = computed(() => !!route.params.id)

const pageLoading = ref(false)
const submitting = ref(false)
const uploading = ref(false)
const uploadTarget = ref('cover')
const fileInputRef = ref(null)
const formRef = ref(null)
const categoryOptions = ref([])

const form = reactive({
  name: '',
  subtitle: '',
  category_id: null,
  unit: '斤',
  price: 0,
  original_price: 0,
  cost_price: 0,
  stock: 0,
  stock_warning: 10,
  virtual_sales: 0,
  is_hot: 0,
  is_new: 0,
  is_recommend: 0,
  is_red: 0,
  origin: '',
  farmer: '',
  cover_image: '',
  images: [],
  detail: '',
  skus: [],
  status: 1
})

const rules = {
  name: [{ required: true, message: '请输入商品名称', trigger: 'blur' }],
  category_id: [{ required: true, message: '请选择分类', trigger: 'change' }],
  unit: [{ required: true, message: '请选择单位', trigger: 'change' }],
  price: [{ required: true, message: '请输入售价', trigger: 'blur' }]
}

async function loadCategories() {
  try {
    const data = await getCategories()
    const raw = Array.isArray(data) ? data : (data && data.list) || []
    const tops = raw.filter((c) => !c.parent_id || c.parent_id === 0)
    categoryOptions.value = tops.map((t) => ({
      id: t.id,
      name: t.name,
      children: raw
        .filter((c) => c.parent_id === t.id)
        .map((c) => ({ id: c.id, name: c.name }))
    }))
  } catch (e) {
    categoryOptions.value = []
  }
}

async function loadDetail() {
  if (!isEdit.value) return
  pageLoading.value = true
  try {
    const d = await getProductDetail(route.params.id)
    Object.assign(form, {
      name: d.name || '',
      subtitle: d.subtitle || '',
      category_id: d.category_id || null,
      unit: d.unit || '斤',
      price: Number(d.price || 0),
      original_price: Number(d.original_price || 0),
      cost_price: Number(d.cost_price || 0),
      stock: Number(d.stock || 0),
      stock_warning: Number(d.stock_warning || 0),
      virtual_sales: Number(d.virtual_sales || 0),
      is_hot: d.is_hot == 1 ? 1 : 0,
      is_new: d.is_new == 1 ? 1 : 0,
      is_recommend: d.is_recommend == 1 ? 1 : 0,
      is_red: d.is_red == 1 ? 1 : 0,
      origin: d.origin || '',
      farmer: d.farmer || d.coop || '',
      cover_image: d.cover_image || d.image || d.cover || '',
      images: Array.isArray(d.images) ? d.images : (d.images_arr || []),
      detail: d.detail || '',
      skus: Array.isArray(d.skus)
        ? d.skus.map((s) => ({
            spec_name: s.spec_name || s.name || '',
            price: Number(s.price || 0),
            stock: Number(s.stock || 0)
          }))
        : [],
      status: d.status === undefined || d.status === null ? 1 : Number(d.status)
    })
  } finally {
    pageLoading.value = false
  }
}

function triggerUpload(target) {
  uploadTarget.value = target
  fileInputRef.value && fileInputRef.value.click()
}

async function handleFileChange(e) {
  const file = e.target.files && e.target.files[0]
  if (!file) return
  uploading.value = true
  try {
    const data = await uploadImage(file)
    if (uploadTarget.value === 'cover') {
      form.cover_image = data.url
    } else {
      form.images.push(data.url)
    }
    ElMessage.success('上传成功')
  } finally {
    uploading.value = false
    e.target.value = ''
  }
}

function removeBanner(index) {
  form.images.splice(index, 1)
}

function addSku() {
  form.skus.push({ spec_name: '', price: 0, stock: 0 })
}

function removeSku(index) {
  form.skus.splice(index, 1)
}

function buildPayload() {
  const skus = form.skus
    .filter((s) => s.spec_name && s.spec_name.trim())
    .map((s) => ({
      spec_name: s.spec_name.trim(),
      price: s.price,
      stock: s.stock
    }))
  return {
    name: form.name,
    subtitle: form.subtitle,
    category_id: form.category_id,
    unit: form.unit,
    price: form.price,
    original_price: form.original_price,
    cost_price: form.cost_price,
    stock: form.stock,
    stock_warning: form.stock_warning,
    virtual_sales: form.virtual_sales,
    is_hot: form.is_hot,
    is_new: form.is_new,
    is_recommend: form.is_recommend,
    is_red: form.is_red,
    origin: form.origin,
    farmer: form.farmer,
    cover_image: form.cover_image,
    images: form.images,
    detail: form.detail,
    skus,
    status: form.status
  }
}

async function submitForm() {
  if (!formRef.value) return
  await formRef.value.validate(async (valid) => {
    if (!valid) return
    submitting.value = true
    try {
      if (isEdit.value) {
        await updateProduct(route.params.id, buildPayload())
        ElMessage.success('保存成功')
      } else {
        await createProduct(buildPayload())
        ElMessage.success('创建成功')
      }
      router.push('/products')
    } finally {
      submitting.value = false
    }
  })
}

onMounted(() => {
  loadCategories()
  loadDetail()
})
</script>

<style scoped>
.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.flag-group {
  display: flex;
  gap: 32px;
  flex-wrap: wrap;
}

.flag-item {
  display: flex;
  align-items: center;
  gap: 8px;
}

.flag-label {
  font-size: 13px;
  color: #555;
}

.img-uploader {
  width: 100px;
  height: 100px;
  border: 1px dashed #ccc;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: border-color 0.2s;
}

.img-uploader:hover {
  border-color: #d4380d;
}

.uploader-icon {
  font-size: 26px;
  color: #bbb;
}

.cover-preview {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 6px;
}

.banner-list {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.banner-item {
  position: relative;
  width: 100px;
  height: 100px;
}

.banner-preview {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 6px;
  border: 1px solid #eee;
}

.banner-remove {
  position: absolute;
  top: -8px;
  right: -8px;
  font-size: 20px;
  color: #d4380d;
  background: #fff;
  border-radius: 50%;
  cursor: pointer;
}

.sku-toolbar {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 10px;
}

.sku-table {
  width: 100%;
}

.form-tip {
  font-size: 12px;
  color: #999;
  line-height: 1.8;
}

.form-tip-inline {
  font-size: 12px;
  color: #999;
  margin-left: 10px;
}
</style>

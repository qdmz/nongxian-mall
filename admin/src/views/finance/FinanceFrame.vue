<template>
  <!-- 财务管理页面由独立静态页 /manage/finance.html 提供（admin/public/finance.html），此处 iframe 嵌入 -->
  <iframe :key="tab" :src="`/manage/finance.html#${tab}`" class="finance-frame" />
</template>

<script setup>
defineProps({ tab: { type: String, default: 'withdrawals' } })
</script>

<style scoped>
.finance-frame {
  width: 100%;
  height: calc(100vh - 130px);
  border: 0;
  background: #fff;
  border-radius: 4px;
  display: block;
}
</style>

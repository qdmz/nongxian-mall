<template>
  <span class="countdown-text" :class="{ expired: finished }">{{ text }}</span>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { formatCountdown } from '../utils'

const props = defineProps({
  /** 目标时间（Unix 秒级时间戳） */
  end: { type: [Number, String], default: 0 }
})

const emit = defineEmits(['finish'])

const now = ref(Math.floor(Date.now() / 1000))
let timer = null

const remain = computed(() => Math.max(0, Number(props.end || 0) - now.value))
const finished = computed(() => remain.value <= 0)
const text = computed(() => (finished.value ? '00:00:00' : formatCountdown(remain.value)))

onMounted(() => {
  timer = setInterval(() => {
    now.value = Math.floor(Date.now() / 1000)
    if (finished.value) {
      clearInterval(timer)
      timer = null
      emit('finish')
    }
  }, 1000)
})

onBeforeUnmount(() => {
  if (timer) clearInterval(timer)
})
</script>

<style scoped>
.expired {
  color: #b0b3ba;
}
</style>

<script setup lang="ts">
import { computed, defineProps, defineEmits } from 'vue'

const props = defineProps<{
  checked?: boolean
  disabled?: boolean
}>()

const emit = defineEmits<{
  (e: 'update:checked', value: boolean): void
}>()

const isChecked = computed({
  get: () => props.checked ?? false,
  set: (val: boolean) => emit('update:checked', val),
})
</script>

<template>
  <button
    type="button"
    role="switch"
    :aria-checked="isChecked"
    :class="[
      'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
      isChecked ? 'bg-indigo-600' : 'bg-gray-300',
      disabled && 'opacity-50 cursor-not-allowed',
    ]"
    @click="!disabled && (isChecked = !isChecked)"
  >
    <span
      :class="[
        'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
        isChecked ? 'translate-x-6' : 'translate-x-1',
      ]"
    />
  </button>
</template>

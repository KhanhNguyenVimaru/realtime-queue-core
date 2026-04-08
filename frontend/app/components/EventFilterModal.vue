<script setup lang="ts">
const open = defineModel<boolean>('open', { default: false })

const props = defineProps<{
  search: string
  sortBy: 'latest' | 'oldest'
  endDate: string
}>()

const emit = defineEmits<{
  update: [payload: { search: string, sortBy: 'latest' | 'oldest', endDate: string }]
  apply: []
}>()

const form = reactive({
  search: props.search,
  sortBy: props.sortBy,
  endDate: props.endDate,
})

watch(
  () => props,
  () => {
    form.search = props.search
    form.sortBy = props.sortBy
    form.endDate = props.endDate
  },
  { deep: true }
)

function apply() {
  emit('update', {
    search: form.search,
    sortBy: form.sortBy,
    endDate: form.endDate,
  })
  emit('apply')
}
</script>

<template>
  <UModal v-model:open="open" title="Filter events" class="w-full max-w-lg">
    <template #body>
      <div class="space-y-4">
        <UFormField label="Search" name="filter-search">
          <UInput v-model="form.search" placeholder="Search by title or description" class="w-full" />
        </UFormField>

        <UFormField label="Sort by" name="filter-sort">
          <USelect v-model="form.sortBy" class="w-full" :items="[
            { label: 'Newest', value: 'latest' },
            { label: 'Oldest', value: 'oldest' },
          ]" value-key="value" />
        </UFormField>

        <UFormField label="End date" name="filter-end-date">
          <UInput v-model="form.endDate" type="date" class="w-full" />
        </UFormField>

        <div class="flex justify-end gap-2">
          <UButton color="neutral" variant="ghost" @click="open = false">
            Cancel
          </UButton>
          <UButton color="primary" variant="solid" @click="apply">
            Apply
          </UButton>
        </div>
      </div>
    </template>
  </UModal>
</template>

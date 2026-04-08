<script setup lang="ts">
import VuePictureCropper, { type CropperInstance, type VuePictureCropperRefValue } from 'vue-picture-cropper'
import 'vue-picture-cropper/style.css'
import 'cropperjs/dist/cropper.css'

type EventRow = {
  id: number
  title: string
  description: string | null
  img?: string | null
  limit?: number | null
  starts_at: string | null
  ends_at: string | null
}

type EventPayload = {
  title: string
  description: string
  img?: string | null
  limit: number | null
  starts_at: string
  ends_at: string
}

type EventForm = {
  title: string
  description: string
  limit: string
  starts_at: string
  ends_at: string
}

const open = defineModel<boolean>('open', { default: false })

const props = defineProps<{
  event: EventRow | null
  pending?: boolean
  error?: string
}>()

const emit = defineEmits<{
  submit: [payload: EventPayload]
}>()

const form = reactive<EventForm>({
  title: '',
  description: '',
  limit: '',
  starts_at: '',
  ends_at: '',
})

const uploadFile = ref<File | null>(null)
const cropperRef = ref<VuePictureCropperRefValue | null>(null)
const imageSource = ref('')
const imagePreview = ref('')
const imageError = ref('')

const cropperOptions: CropperInstance.Options = {
  aspectRatio: 4 / 3,
  viewMode: 1,
  autoCropArea: 1,
  background: false,
  responsive: true,
}

function toLocalInput(value: string | null) {
  if (!value) {
    return ''
  }

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return ''
  }

  const pad = (unit: number) => unit.toString().padStart(2, '0')
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`
}

watch(
  () => props.event,
  (event: EventRow | null) => {
    form.title = event?.title ?? ''
    form.description = event?.description ?? ''
    form.limit = event?.limit != null ? String(event.limit) : ''
    form.starts_at = toLocalInput(event?.starts_at ?? null)
    form.ends_at = toLocalInput(event?.ends_at ?? null)
    uploadFile.value = null
    imageSource.value = ''
    imagePreview.value = ''
    imageError.value = ''
  },
  { immediate: true }
)

function readFileAsDataUrl(file: File) {
  return new Promise<string>((resolve, reject) => {
    const reader = new FileReader()
    reader.onload = () => resolve(String(reader.result || ''))
    reader.onerror = () => reject(new Error('Unable to read file.'))
    reader.readAsDataURL(file)
  })
}

async function handleImageChange(file: File | null) {
  imageError.value = ''
  imagePreview.value = ''

  if (!file) {
    imageSource.value = ''
    return
  }

  if (!file.type.startsWith('image/')) {
    imageError.value = 'Please choose an image file.'
    imageSource.value = ''
    return
  }

  try {
    imageSource.value = await readFileAsDataUrl(file)
  } catch {
    imageError.value = 'Unable to load image.'
    imageSource.value = ''
  }
}

watch(uploadFile, (file: File | null) => {
  handleImageChange(file ?? null)
})

function applyCrop() {
  imageError.value = ''

  const cropper = cropperRef.value?.cropper
  if (!cropper) {
    imageError.value = 'Please select an image first.'
    return
  }

  imagePreview.value = cropper.getDataURL({
    width: 800,
    height: 600,
    imageSmoothingQuality: 'high',
  })
}

function submitForm() {
  const rawLimit = String(form.limit ?? '')
  const payload: EventPayload = {
    title: form.title,
    description: form.description,
    limit: rawLimit.trim() === '' ? null : Number(rawLimit),
    starts_at: form.starts_at,
    ends_at: form.ends_at,
  }

  if (imageSource.value && !imagePreview.value) {
    applyCrop()
  }

  if (imagePreview.value) {
    payload.img = imagePreview.value
  }

  emit('submit', payload)
}
</script>

<template>
  <UModal v-model:open="open" title="Edit event" class="w-full max-w-3xl">
    <template #body>
      <form class="space-y-4" @submit.prevent="submitForm">
        <div class="grid gap-6 md:grid-cols-2">
          <div class="space-y-4">
            <UFormField label="Title" name="event-title">
              <UInput v-model="form.title" placeholder="Event title" class="w-full" />
            </UFormField>

            <UFormField label="Description" name="event-description">
              <UTextarea
                v-model="form.description"
                placeholder="Description"
                class="w-full min-h-[160px] resize-y"
                :rows="6"
              />
            </UFormField>

            <UFormField label="Starts at" name="event-start">
              <UInput v-model="form.starts_at" type="datetime-local" class="w-full" />
            </UFormField>

            <UFormField label="Ends at" name="event-end">
              <UInput v-model="form.ends_at" type="datetime-local" class="w-full" />
            </UFormField>

            <UFormField label="Participant limit" name="event-limit">
              <UInput
                v-model="form.limit"
                type="number"
                min="1"
                placeholder="Unlimited"
                class="w-full"
              />
              <template #hint>
                Leave empty for unlimited.
              </template>
            </UFormField>
          </div>

          <div class="space-y-3">
            <UFormField label="Event image" name="event-image">
              <UFileUpload
                v-model="uploadFile"
                accept="image/*"
                icon="i-lucide-upload"
                label="Replace image"
                variant="button"
                color="neutral"
                :dropzone="false"
                :preview="false"
                :file-image="false"
              />
            </UFormField>

            <UAlert
              v-if="imageError"
              color="error"
              variant="subtle"
              title="Image error"
              :description="imageError"
            />

            <div class="space-y-3">
              <div class="overflow-hidden rounded-lg border border-default bg-muted/10">
                <img
                  v-if="imagePreview"
                  :src="imagePreview"
                  alt="New event image"
                  class="h-60 w-full object-cover"
                />
                <img
                  v-else-if="props.event?.img"
                  :src="props.event.img"
                  alt="Event image"
                  class="h-60 w-full object-cover"
                />
                <div v-else class="flex h-60 items-center justify-center text-sm text-muted">
                  No image uploaded.
                </div>
              </div>

              <div v-if="imageSource" class="rounded-lg border border-default bg-muted/20 p-2">
                <ClientOnly>
                  <VuePictureCropper
                    ref="cropperRef"
                    :img="imageSource"
                    :options="cropperOptions"
                    class="h-60 w-full"
                  />
                </ClientOnly>
              </div>

              <UButton
                v-if="imageSource"
                type="button"
                color="neutral"
                variant="soft"
                size="sm"
                class="w-full"
                icon="i-lucide-scissors"
                @click="applyCrop"
              >
                Crop image
              </UButton>
            </div>
          </div>
        </div>

        <UAlert
          v-if="props.error"
          color="error"
          variant="subtle"
          title="Update failed"
          :description="props.error"
        />

        <div class="flex justify-end gap-2">
          <UButton color="neutral" variant="ghost" @click="open = false">
            Cancel
          </UButton>
          <UButton type="submit" :loading="props.pending">
            Save
          </UButton>
        </div>
      </form>
    </template>
  </UModal>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import UserAvatar from '@/components/widgets/UserAvatar.vue';
import { useImagePreview } from '@/composables/useImagePreview';

const props = defineProps<{
    currentPicture: string | null;
    name: string;
}>();

const emit = defineEmits<{ 'update:picture': [file: File | null] }>();

const { previewUrl, handleSingleImage, removeSinglePreview } = useImagePreview();
const fileInputRef = ref<HTMLInputElement | null>(null);

function handleFileChange(event: Event) {
    const file = handleSingleImage(event);
    emit('update:picture', file ?? null);
}

function removePicture() {
    removeSinglePreview();
    emit('update:picture', null);
    if (fileInputRef.value) fileInputRef.value.value = '';
}
</script>

<template>
    <div class="shrink-0 self-start">
        <div class="relative">
            <UserAvatar
                :picture="currentPicture"
                :name="name"
                :preview-url="previewUrl"
                image-size="md"
                sizes="(max-width: 640px) 112px, 176px"
                class="size-28 text-3xl sm:size-44 sm:text-4xl"
            />

            <button
                v-if="previewUrl"
                type="button"
                aria-label="Supprimer la photo de profil"
                class="absolute right-1 top-1 flex size-6 items-center justify-center rounded-full bg-red-500 text-xs text-white hover:bg-red-600"
                @click="removePicture"
            >
                <span aria-hidden="true">×</span>
            </button>
        </div>

        <input
            ref="fileInputRef"
            type="file"
            accept="image/png,image/jpeg,image/jpg,image/webp"
            aria-label="Choisir une photo de profil"
            class="hidden"
            @change="handleFileChange"
        />
        <button
            type="button"
            class="mt-3 w-full rounded-xl border border-neutral-200 px-3 py-1.5 text-xs font-semibold text-text-base transition-colors hover:bg-gray-50"
            @click="fileInputRef?.click()"
        >
            Changer la photo
        </button>
    </div>
</template>
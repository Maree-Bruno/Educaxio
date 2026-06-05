<script setup lang="ts">
import { computed } from 'vue';
import { getInitials, useUserHelpers } from '@/composables/useUserHelpers';

const props = defineProps<{
    name: string;
    picture: string | null;
    previewUrl?: string | null;
    imageSize?: 'xs' | 'sm' | 'md' | 'lg';
    sizes?: string;
    type?: 'user' | 'student';
}>();

const { getUserImageUrl, getUserImageSrcset, getStudentImageUrl, getStudentImageSrcset } = useUserHelpers();

const sizeMap = { xs: 36, sm: 64, md: 80, lg: 128 } as const;
const avatarPx = computed(() => sizeMap[props.imageSize ?? 'sm']);

const isStudent = computed(() => props.type === 'student');
const displayInitials = computed(() => getInitials(props.name));
const hasImage = computed(() => !!(props.previewUrl || props.picture));
const imageSrc = computed(() => {
    if (props.previewUrl) return props.previewUrl;
    if (!props.picture) return '';
    return isStudent.value
        ? getStudentImageUrl(props.picture, props.imageSize ?? 'sm')
        : getUserImageUrl(props.picture, props.imageSize ?? 'sm');
});
const imageSrcset = computed(() => {
    if (props.previewUrl || !props.picture) return '';
    return isStudent.value
        ? getStudentImageSrcset(props.picture)
        : getUserImageSrcset(props.picture);
});
</script>

<template>
    <span class="flex shrink-0 items-center justify-center rounded-full bg-blue-dark font-semibold text-white">
        <img
            v-if="hasImage"
            :src="imageSrc"
            :srcset="imageSrcset || undefined"
            :sizes="sizes"
            :alt="`Photo de ${name}`"
            :width="avatarPx"
            :height="avatarPx"
            class="h-full w-full rounded-full object-cover"
        />
        <span v-else aria-hidden="true">{{ displayInitials }}</span>
    </span>
</template>
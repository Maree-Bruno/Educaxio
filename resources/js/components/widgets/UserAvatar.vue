<script setup lang="ts">
import { computed } from 'vue';
import { getInitials, useUserHelpers } from '@/composables/useUserHelpers';

const props = defineProps<{
    name: string;
    picture: string | null;
    previewUrl?: string | null;
    imageSize?: 'xs' | 'sm' | 'md' | 'lg';
    sizes?: string;
}>();

const { getUserImageUrl, getUserImageSrcset } = useUserHelpers();

const displayInitials = computed(() => getInitials(props.name));
const hasImage = computed(() => !!(props.previewUrl || props.picture));
const imageSrc = computed(() => props.previewUrl ?? (props.picture ? getUserImageUrl(props.picture, props.imageSize ?? 'sm') : ''));
const imageSrcset = computed(() => props.previewUrl || !props.picture ? '' : getUserImageSrcset(props.picture));
</script>

<template>
    <span class="flex shrink-0 items-center justify-center rounded-full bg-blue-dark font-semibold text-white">
        <img
            v-if="hasImage"
            :src="imageSrc"
            :srcset="imageSrcset || undefined"
            :sizes="sizes"
            :alt="`Photo de ${name}`"
            class="h-full w-full rounded-full object-cover"
        />
        <span v-else aria-hidden="true">{{ displayInitials }}</span>
    </span>
</template>
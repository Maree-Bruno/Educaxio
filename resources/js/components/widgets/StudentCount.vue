<script setup lang="ts">
defineProps<{
    total:     number;
    filtered?: number;
}>();
</script>

<template>
    <span class="text-border-figma">
        <template v-if="filtered !== undefined && filtered !== total">
            ({{ filtered }}&thinsp;/&thinsp;{{ total }})
        </template>
        <template v-else>({{ total }})</template>
    </span>
</template>

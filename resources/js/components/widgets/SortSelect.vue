<script setup lang="ts">
import SelectField from '@/components/widgets/SelectField.vue';
import type { SelectOption } from '@/components/widgets/SelectField.vue';
import ArrowUpDown from '@/components/widgets/svg/ArrowUpDown.vue';

withDefaults(defineProps<{
    id?:        string;
    label?:     string;
    modelValue: string | null;
    direction:  'asc' | 'desc';
    options:    SelectOption[];
}>(), {
    id:    undefined,
    label: 'Trier',
});

const emit = defineEmits<{
    'update:modelValue': [value: string | null];
    'update:direction':  [dir: 'asc' | 'desc'];
}>();
</script>

<template>
    <div class="flex flex-col gap-2">
        <label v-if="label" :for="id" class="font-manrope text-xs leading-4 font-bold tracking-wide text-border-figma uppercase">
            {{ label }}
        </label>
        <div class="flex items-center gap-1">
            <SelectField
                :id="id"
                :model-value="modelValue"
                :options="options"
                placeholder="Par défaut"
                class="flex-1"
                @update:model-value="emit('update:modelValue', $event as string | null)"
            />
            <button
                type="button"
                :disabled="!modelValue"
                class="flex h-11.5 w-10 shrink-0 items-center justify-center rounded-2xl bg-white outline-1 -outline-offset-1 outline-border-figma transition-all hover:bg-gray-50"
                :class="modelValue ? 'text-text-base' : 'pointer-events-none opacity-30'"
                :title="direction === 'asc' ? 'Ordre ascendant' : 'Ordre descendant'"
                @click="emit('update:direction', direction === 'asc' ? 'desc' : 'asc')"
            >
                <ArrowUpDown
                    :size="16"
                    :stroke-width="2"
                    class="transition-transform duration-200"
                    :class="{ 'rotate-180': direction === 'desc' }"
                    aria-hidden="true"
                />
            </button>
        </div>
    </div>
</template>

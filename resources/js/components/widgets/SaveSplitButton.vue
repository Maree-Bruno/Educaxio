<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';
import ChevronDown from '@/components/widgets/svg/ChevronDown.vue';

export type AfterSave = 'stay' | 'journal';

const STORAGE_KEY = 'after-save';

const OPTIONS: { value: AfterSave; label: string }[] = [
    { value: 'stay',    label: 'Rester sur la page' },
    { value: 'journal', label: 'Aller au journal'   },
];

withDefaults(defineProps<{
    loading?:  boolean;
    disabled?: boolean;
}>(), {
    loading:  false,
    disabled: false,
});

const emit = defineEmits<{
    save: [afterSave: AfterSave];
}>();

const afterSave    = ref<AfterSave>((localStorage.getItem(STORAGE_KEY) as AfterSave) ?? 'stay');
const dropdownOpen = ref(false);
const containerRef = ref<HTMLElement | null>(null);

function select(option: AfterSave) {
    afterSave.value = option;
    localStorage.setItem(STORAGE_KEY, option);
    dropdownOpen.value = false;
}

function onMousedown(e: MouseEvent) {
    if (containerRef.value && !containerRef.value.contains(e.target as Node)) {
        dropdownOpen.value = false;
    }
}

onMounted(() => document.addEventListener('mousedown', onMousedown));
onUnmounted(() => document.removeEventListener('mousedown', onMousedown));

defineExpose({ afterSave });
</script>

<template>
    <div ref="containerRef" class="relative inline-flex">
        <button
            type="button"
            :disabled="disabled || loading"
            :title="afterSave === 'journal' ? 'Enregistrer et aller au journal (⌘S)' : 'Enregistrer (⌘S)'"
            class="inline-flex items-center gap-2 rounded-l-xl bg-blue px-3 py-1.5 font-manrope text-sm font-medium text-white transition-all duration-300 hover:opacity-85 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue focus-visible:ring-offset-2 sm:px-4 sm:py-2 sm:text-base"
            @click="emit('save', afterSave)"
        >
            <svg
                v-if="loading"
                class="h-4 w-4 animate-spin shrink-0"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                aria-hidden="true"
            >
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
            </svg>
            Enregistrer
        </button>

        <span class="w-px bg-white/20" aria-hidden="true" />

        <button
            type="button"
            :disabled="disabled || loading"
            :aria-expanded="dropdownOpen"
            aria-label="Options après enregistrement"
            class="inline-flex items-center rounded-r-xl bg-blue px-2 py-1.5 text-white transition-all duration-300 hover:opacity-85 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue focus-visible:ring-offset-2 sm:py-2"
            @click.stop="dropdownOpen = !dropdownOpen"
        >
            <ChevronDown :size="16" :stroke-width="2.5" />
        </button>

        <Transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="scale-95 opacity-0"
            enter-to-class="scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="scale-100 opacity-100"
            leave-to-class="scale-95 opacity-0"
        >
            <div
                v-if="dropdownOpen"
                class="absolute right-0 top-full z-50 mt-1.5 min-w-48 origin-top-right rounded-xl border border-neutral-200 bg-white p-1.5 shadow-lg"
            >
                <p class="mb-1 px-2 pt-1 text-[10px] font-bold uppercase tracking-wider text-stone-400">
                    Après l'enregistrement
                </p>
                <button
                    v-for="opt in OPTIONS"
                    :key="opt.value"
                    type="button"
                    class="flex w-full items-center gap-2.5 rounded-lg px-2 py-2 text-sm text-stone-700 transition-colors hover:bg-stone-50"
                    @click="select(opt.value)"
                >
                    <span
                        class="flex h-3.5 w-3.5 shrink-0 items-center justify-center rounded-full border-2 transition-colors"
                        :class="afterSave === opt.value ? 'border-blue' : 'border-stone-300'"
                    >
                        <span v-if="afterSave === opt.value" class="h-1.5 w-1.5 rounded-full bg-blue" />
                    </span>
                    {{ opt.label }}
                </button>
            </div>
        </Transition>
    </div>
</template>

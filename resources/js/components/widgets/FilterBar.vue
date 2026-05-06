<script setup lang="ts">
import { ref } from 'vue';
import ChevronDown from '@/components/widgets/svg/ChevronDown.vue';

withDefaults(
    defineProps<{
        activeCount?: number;
    }>(),
    { activeCount: 0 },
);

const isOpen = ref(false);
</script>

<template>
    <div
        class="sticky top-16 z-20 -mx-2 -mt-2 bg-bg-primary px-2 py-6 shadow-[0px_1px_2px_0px_rgba(48,48,48,0.05)] lg:-mx-8 lg:-mt-8 lg:px-8"
    >
        <div class="rounded-2xl bg-white p-6 outline-1 -outline-offset-1 outline-neutral-300/10">

            <!-- Ligne toggle (mobile) + action (toujours visible) -->
            <div class="flex items-center justify-between gap-4 lg:hidden">
                <button
                    type="button"
                    class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-bold text-text-base transition-colors hover:bg-gray-50"
                    :aria-expanded="isOpen"
                    @click="isOpen = !isOpen"
                >
                    <span>Filtres</span>
                    <span
                        v-if="activeCount"
                        class="flex h-5 w-5 items-center justify-center rounded-full bg-blue text-xs font-bold text-white"
                    >
                        {{ activeCount }}
                    </span>
                    <ChevronDown
                        :size="16"
                        :stroke-width="2"
                        class="shrink-0 text-text-base transition-transform duration-200"
                        :class="{ 'rotate-180': isOpen }"
                        aria-hidden="true"
                    />
                </button>

                <!-- Action slot : visible sur mobile dans la barre de toggle -->
                <div class="shrink-0">
                    <slot name="action" />
                </div>
            </div>

            <!-- Champs de filtre : collapsible sur mobile, toujours visible desktop -->
            <Transition
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div
                    v-show="isOpen"
                    class="mt-4 flex flex-col gap-4 lg:hidden"
                >
                    <slot name="filters" />
                </div>
            </Transition>

            <!-- Desktop : ligne unique toujours visible -->
            <div
                class="hidden lg:flex lg:flex-row lg:flex-wrap lg:items-end lg:justify-between lg:gap-4"
            >
                <slot name="filters" />

                <!-- Action slot : visible sur desktop dans la ligne des filtres -->
                <div class="shrink-0">
                    <slot name="action" />
                </div>
            </div>
        </div>
    </div>
</template>

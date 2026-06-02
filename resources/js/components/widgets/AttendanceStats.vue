<script setup lang="ts">
export interface AttendanceStats {
    sessions: number;
    absences: number;
    lates: number;
    exclusions: number;
    rate: number | null;
}

withDefaults(
    defineProps<{
        stats: AttendanceStats;
        title?: string;
        description?: string;
    }>(),
    {
        title: 'Taux de présence',
    },
);

function rateColor(rate: number | null): string {
    if (rate === null) {
        return 'text-border-figma';
    }

    if (rate >= 90) {
        return 'text-blue';
    }

    if (rate >= 75) {
        return 'text-orange';
    }

    return 'text-pink';
}

function barColor(rate: number | null): string {
    if (rate === null) {
return 'bg-border-figma';
}

    if (rate >= 90) {
return 'bg-blue';
}

    if (rate >= 75) {
return 'bg-orange';
}

    return 'bg-pink';
}
</script>

<template>
    <div class="overflow-hidden rounded-2xl bg-white">
        <div class="border-b border-neutral-300/10 px-6 py-4">
            <p class="text-sm font-bold text-text-base">{{ title }}</p>
            <p v-if="description" class="mt-0.5 text-xs text-stone-400">
                {{ description }}
            </p>
            <p
                v-else-if="stats.sessions > 0"
                class="mt-0.5 text-xs text-stone-400"
            >
                {{ stats.sessions }} séance{{
                    stats.sessions > 1 ? 's' : ''
                }}
                enregistrée{{ stats.sessions > 1 ? 's' : '' }}
            </p>
        </div>

        <div class="flex flex-col gap-4 px-6 py-5">
            <div class="flex items-end gap-2">
                <span
                    class="text-4xl leading-none font-extrabold"
                    :class="rateColor(stats.rate)"
                >
                    {{ stats.rate !== null ? stats.rate + '%' : '—' }}
                </span>
                <span class="pb-0.5 text-sm text-stone-400">
                    {{ stats.rate !== null ? 'de présence' : 'Aucune séance' }}
                </span>
            </div>

            <div class="h-1.5 overflow-hidden rounded-full bg-neutral-100">
                <div
                    class="h-1.5 rounded-full transition-all duration-500"
                    :class="barColor(stats.rate)"
                    :style="{ width: (stats.rate ?? 0) + '%' }"
                />
            </div>

            <div class="flex gap-6">
                <div>
                    <p class="text-xl font-bold text-red-600">
                        {{ stats.absences }}
                    </p>
                    <p class="text-xs text-stone-400">
                        absent{{ stats.absences !== 1 ? 's' : '' }}
                    </p>
                </div>
                <div>
                    <p class="text-xl font-bold text-yellow-500">
                        {{ stats.lates }}
                    </p>
                    <p class="text-xs text-stone-400">
                        retard{{ stats.lates !== 1 ? 's' : '' }}
                    </p>
                </div>
                <div>
                    <p class="text-xl font-bold text-stone-400">
                        {{ stats.exclusions }}
                    </p>
                    <p class="text-xs text-stone-400">
                        exclusion{{ stats.exclusions !== 1 ? 's' : '' }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

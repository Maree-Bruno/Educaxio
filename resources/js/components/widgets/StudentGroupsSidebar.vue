<script setup lang="ts">
import EmptyState from '@/components/widgets/EmptyState.vue';
import LinkButton from '@/components/widgets/LinkButton.vue';
import Eye from '@/components/widgets/svg/Eye.vue';
import { show as showClasslist } from '@/routes/classlist';

interface Group {
    id: number;
    slug: string;
    grade: string;
    name: string;
    school: { name: string };
}

defineProps<{
    groups: Group[];
}>();
</script>

<template>
    <section class="flex w-full shrink-0 flex-col gap-4 xl:w-80">
        <div class="rounded-2xl bg-white">
            <div class="border-b border-neutral-300/10 px-6 py-5">
                <h3 class="text-base font-bold text-text-base">Classes</h3>
            </div>
            <ul class="divide-y divide-neutral-100">
                <li
                    v-for="group in groups"
                    :key="group.id"
                    class="flex items-center justify-between px-6 py-4"
                >
                    <div>
                        <p class="text-sm font-semibold text-text-base">{{ group.grade }}{{ group.name }}</p>
                        <p class="text-xs text-stone-500">{{ group.school.name }}</p>
                    </div>
                    <LinkButton
                        :href="showClasslist.url({ group: group.slug })"
                        variant="secondary"
                        size="sm"
                        :icon-only="true"
                        title="Voir la classe"
                    >
                        <template #icon>
                            <Eye :size="16" :stroke-width="2" aria-hidden="true" />
                        </template>
                    </LinkButton>
                </li>
                <li v-if="!groups.length"><EmptyState message="Aucune classe" size="sm" /></li>
            </ul>
        </div>
    </section>
</template>
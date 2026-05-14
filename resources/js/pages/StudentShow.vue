<script setup lang="ts">
import { computed } from 'vue';
import Breadcrumb from '@/components/widgets/Breadcrumb.vue';
import LinkButton from '@/components/widgets/LinkButton.vue';
import Eye from '@/components/widgets/svg/Eye.vue';
import { setPageTitle } from '@/composables/usePageTitle';
import type { Student } from '@/types';

const props = defineProps<{
    student: Student;
}>();

const fullName = computed(() => `${props.student.lastname} ${props.student.firstname}`);

setPageTitle(fullName.value);

const firstGroup = computed(() => props.student.groups?.[0] ?? null);

const breadcrumbItems = computed(() => {
    const items: { label: string; href?: string }[] = [
        { label: 'Liste de classe', href: '/classlist' },
    ];

    if (firstGroup.value) {
        items.push({
            label: `${firstGroup.value.grade}${firstGroup.value.name} — ${firstGroup.value.school.name}`,
            href: `/classlist/${firstGroup.value.slug}`,
        });
    }

    items.push({ label: fullName.value });

    return items;
});
</script>

<template>
    <Breadcrumb :items="breadcrumbItems" />

    <div class="flex flex-col gap-6 xl:flex-row xl:items-start">

        <!-- Infos élève -->
        <div class="min-w-0 flex-1 overflow-hidden rounded-2xl bg-white">
            <div class="border-b border-neutral-300/10 px-6 py-5">
                <h2 class="text-xl font-bold text-text-base">{{ fullName }}</h2>
            </div>

            <dl class="divide-y divide-neutral-100 px-6">
                <div class="flex items-center justify-between py-4">
                    <dt class="text-xs font-bold uppercase tracking-wider text-stone-500">Nom</dt>
                    <dd class="text-sm font-medium text-text-base">{{ student.lastname }}</dd>
                </div>
                <div class="flex items-center justify-between py-4">
                    <dt class="text-xs font-bold uppercase tracking-wider text-stone-500">Prénom</dt>
                    <dd class="text-sm font-medium text-text-base">{{ student.firstname }}</dd>
                </div>
                <div class="flex items-center justify-between py-4">
                    <dt class="text-xs font-bold uppercase tracking-wider text-stone-500">Email</dt>
                    <dd class="text-sm text-text-base">
                        <a
                            v-if="student.email"
                            :href="`mailto:${student.email}`"
                            class="text-blue hover:underline"
                        >
                            {{ student.email }}
                        </a>
                        <span v-else class="text-border-figma">—</span>
                    </dd>
                </div>
            </dl>
        </div>

        <!-- Classes de l'élève -->
        <div class="flex w-full shrink-0 flex-col gap-4 xl:w-80">
            <div class="rounded-2xl bg-white">
                <div class="border-b border-neutral-300/10 px-6 py-5">
                    <h3 class="text-base font-bold text-text-base">Classes</h3>
                </div>

                <ul class="divide-y divide-neutral-100">
                    <li
                        v-for="group in student.groups"
                        :key="group.id"
                        class="flex items-center justify-between px-6 py-4"
                    >
                        <div>
                            <p class="text-sm font-semibold text-text-base">
                                {{ group.grade }}{{ group.name }}
                            </p>
                            <p class="text-xs text-stone-500">{{ group.school.name }}</p>
                        </div>
                        <LinkButton
                            :href="`/classlist/${group.slug}`"
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

                    <li
                        v-if="!student.groups?.length"
                        class="px-6 py-8 text-center text-sm font-bold text-border-figma"
                    >
                        Aucune classe
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

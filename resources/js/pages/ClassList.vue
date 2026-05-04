<script setup lang="ts">
import LinkButton from '@/components/widgets/LinkButton.vue';
import ClipboardCheck from '@/components/widgets/svg/ClipboardCheck.vue';
import Edit from '@/components/widgets/svg/Edit.vue';
import Eye from '@/components/widgets/svg/Eye.vue';
import { setPageTitle } from '@/composables/usePageTitle';
import Trash from '@/components/widgets/svg/Trash.vue';

setPageTitle('Liste des classes');

interface AcademicYear {
    id: number;
    year: string;
}

interface Group {
    id: number;
    name: string;
    grade: string;
    academic_year_id: number;
    academic_year: AcademicYear;
    students_count: number;
}

defineProps<{
    groups: Group[];
}>();
</script>

<template>
    <div class="p-section">
        <p
            v-if="groups.length === 0"
            class="flex flex-col items-center justify-center gap-3 rounded-3xl bg-white py-20 text-center font-bold text-text-base"
        >
            Aucune classe pour le moment
        </p>

        <ul v-else class="flex list-none flex-wrap gap-4">
            <li
                v-for="group in groups"
                :key="group.id"
                class="flex w-72 flex-col items-start justify-start gap-6 rounded-3xl bg-white px-8 py-6"
            >
                <!-- Infos principales -->
                <dl class="flex flex-col gap-5 self-stretch">
                    <div
                        class="flex items-center justify-start gap-3 self-stretch"
                    >
                        <div class="flex flex-1 flex-col gap-1">
                            <dt
                                class="text-xs leading-5 font-bold text-border-figma uppercase"
                            >
                                Classe
                            </dt>
                            <dd
                                class="line-clamp-1 text-base leading-5 font-bold text-text-base"
                            >
                                {{ group.grade }}{{ group.name }}
                            </dd>
                        </div>
                        <div class="flex flex-1 flex-col gap-1">
                            <dt
                                class="text-xs leading-5 font-bold text-border-figma uppercase"
                            >
                                Établissement
                            </dt>
                            <dd
                                class="text-base leading-5 font-bold text-text-base"
                            >
                                —
                            </dd>
                        </div>
                    </div>
                </dl>

                <!-- Année + Effectifs -->
                <dl class="flex items-center justify-start gap-3 self-stretch">
                    <div class="flex flex-1 flex-col gap-1">
                        <dt
                            class="text-xs leading-5 font-bold text-border-figma uppercase"
                        >
                            Année
                        </dt>
                        <dd class="text-sm leading-5 font-bold text-text-base">
                            {{ group.academic_year.year }}
                        </dd>
                    </div>
                    <div class="flex flex-1 flex-col gap-1">
                        <dt
                            class="text-xs leading-5 font-bold text-border-figma uppercase"
                        >
                            Effectifs
                        </dt>
                        <dd class="text-sm leading-5 font-bold text-text-base">
                            {{ group.students_count }}
                        </dd>
                    </div>
                </dl>

                <!-- Actions -->
                <div
                    class="flex items-center justify-between self-stretch"
                    :aria-label="`Actions pour ${group.name}`"
                >
                    <LinkButton
                        href="#"
                        variant="primary"
                        size="sm"
                        :icon-only="true"
                        label="Voir la classe"
                        title="Voir la classe"
                    >
                        <template #icon>
                            <Eye
                                :size="16"
                                :stroke-width="2"
                                aria-hidden="true"
                            />
                        </template>
                    </LinkButton>
                    <LinkButton
                        href="#"
                        variant="secondary"
                        size="sm"
                        :icon-only="true"
                        label="Cahier de côte"
                        title="Présences"
                    >
                        <template #icon>
                            <ClipboardCheck
                                :size="16"
                                :stroke-width="2"
                                aria-hidden="true"
                            />
                        </template>
                    </LinkButton>
                    <LinkButton
                        href="#"
                        variant="danger"
                        size="sm"
                        :icon-only="true"
                        label="Supprimer la classe"
                        title="Supprimer la classe"
                    >
                        <template #icon>
                            <Trash
                                :size="16"
                                :stroke-width="2"
                                aria-hidden="true"
                            />
                        </template>
                    </LinkButton>
                </div>
            </li>
        </ul>
    </div>
</template>

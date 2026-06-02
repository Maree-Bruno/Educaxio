<script setup lang="ts">
import Badge from '@/components/widgets/Badge.vue';
import Button from '@/components/widgets/Button.vue';
import LinkButton from '@/components/widgets/LinkButton.vue';
import { resolveSubjectLabel } from '@/composables/useSubjectLabel';
import AttendanceIcon from '@/components/widgets/svg/Attendance.vue';
import Eye from '@/components/widgets/svg/Eye.vue';
import Trash from '@/components/widgets/svg/Trash.vue';
import { attendances } from '@/routes';
import type { Group, Lesson } from '@/types';

const props = defineProps<{
    id: number;
    name: string;
    grade: string;
    slug: string;
    school_id: number;
    school: Group['school'];
    academic_year_id: number;
    academic_year: Group['academic_year'];
    students_count: number;
    lesson: Lesson | null;
    canDelete?: boolean;
    viewHref?: string;
    gradesHref?: string;
}>();

const emit = defineEmits<{
    delete: [id: number, slug: string];
}>();
</script>

<template>
    <article
        class="flex w-full flex-col items-start justify-start gap-6 rounded-3xl bg-white px-8 py-6"
    >
        <h3 class="sr-only">{{slug}} - {{school.name}}</h3>
        <!-- Infos principales -->
        <dl class="flex flex-col gap-5 self-stretch">
            <div class="flex items-start justify-start gap-3 self-stretch">
                <div class="flex flex-1 flex-col gap-1">
                    <dt
                        class="text-xs leading-5 font-bold text-border-figma uppercase"
                    >
                        Classe
                    </dt>
                    <dd>
                        <Badge variant="blue" size="md">{{ grade }}{{ name }}</Badge>
                    </dd>
                </div>
                <div class="flex flex-1 flex-col gap-1">
                    <dt
                        class="text-xs leading-5 font-bold text-border-figma uppercase"
                    >
                        Établissement
                    </dt>
                    <dd class="text-base leading-5 font-bold text-text-base">
                        {{ school.name }}
                    </dd>
                </div>
            </div>
        </dl>

        <!-- Cours -->
        <dl class="flex flex-col gap-5 self-stretch">
            <div class="flex items-center justify-start gap-3 self-stretch">
                <div class="flex flex-1 flex-col gap-1">
                    <dt
                        class="text-xs leading-5 font-bold text-border-figma uppercase"
                    >
                        Cours
                    </dt>
                    <dd
                        class="line-clamp-1 text-base leading-5 font-bold text-text-base"
                    >
                        {{ lesson?.subject ? resolveSubjectLabel(lesson.subject.name, lesson.lm_level) : '—' }}
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
                    {{ academic_year.year }}
                </dd>
            </div>
            <div class="flex flex-1 flex-col gap-1">
                <dt
                    class="text-xs leading-5 font-bold text-border-figma uppercase"
                >
                    Nombre d'élèves
                </dt>
                <dd class="text-sm leading-5 font-bold text-text-base">
                    {{ students_count }}
                </dd>
            </div>
        </dl>

        <!-- Actions -->
        <div
            class="flex items-center justify-between self-stretch"
            :aria-label="`Actions pour ${name}`"
        >
            <LinkButton
                v-if="viewHref"
                :href="viewHref"
                variant="primary"
                size="sm"
                :icon-only="true"
                label="Voir la classe"
                title="Voir la classe"
            >
                <template #icon>
                    <Eye :size="16" :stroke-width="2" aria-hidden="true" />
                </template>
            </LinkButton>

            <LinkButton
                :href="attendances.url({ query: { group: slug } })"
                variant="secondary"
                size="sm"
                :icon-only="true"
                label="Présences"
                title="Aller aux présences"
            >
                <template #icon>
                    <AttendanceIcon :size="16" :stroke-width="2" aria-hidden="true" />
                </template>
            </LinkButton>

<!--            <LinkButton
                v-if="gradesHref"
                :href="gradesHref"
                variant="secondary"
                size="sm"
                :icon-only="true"
                label="Cahier de côte"
                title="Cahier de côte"
            >
                <template #icon>
                    <ClipboardCheck
                        :size="16"
                        :stroke-width="2"
                        aria-hidden="true"
                    />
                </template>
            </LinkButton>-->

            <Button
                v-if="canDelete"
                variant="danger"
                size="sm"
                :icon-only="true"
                title="Supprimer la classe"
                @click="emit('delete', props.id, props.slug)"
            >
                <template #icon>
                    <Trash :size="16" :stroke-width="2" aria-hidden="true" />
                </template>
            </Button>
        </div>
    </article>
</template>

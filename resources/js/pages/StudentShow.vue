<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';
import { classlist } from '@/routes';
import { show as showClasslist } from '@/routes/classlist';
import { update as updateStudent } from '@/routes/students';
import { index as adminStudentsIndex } from '@/routes/admin/students';
import BaseModal from '@/components/widgets/BaseModal.vue';
import Breadcrumb from '@/components/widgets/Breadcrumb.vue';
import EmptyState from '@/components/widgets/EmptyState.vue';
import Button from '@/components/widgets/Button.vue';
import InputLabel from '@/components/widgets/form/InputLabel.vue';
import LinkButton from '@/components/widgets/LinkButton.vue';
import Edit from '@/components/widgets/svg/Edit.vue';
import Eye from '@/components/widgets/svg/Eye.vue';
import { formatDate } from '@/composables/useFormatter';
import { setPageTitle } from '@/composables/usePageTitle';
import { ATTENDANCE_STATUS_COLORS, ATTENDANCE_STATUS_LABELS, type AttendanceStatus, type Student } from '@/types';

interface AbsenceRecord {
    date: string | null;
    type: AttendanceStatus;
    subject: string | null;
    group: string | null;
    time: string | null;
}

type StudentWithSchool = Student & { school?: { id: number; name: string; slug: string } };

const props = defineProps<{
    student: StudentWithSchool;
    isAdmin: boolean;
    absenceHistory: AbsenceRecord[] | null;
}>();

const fullName = computed(
    () => `${props.student.lastname} ${props.student.firstname}`,
);

setPageTitle(fullName.value);

const firstGroup = computed(() => props.student.groups?.[0] ?? null);

const breadcrumbItems = computed(() => {
    const items: { label: string; href?: string }[] = [];

    if (props.isAdmin && props.student.school) {
        items.push({ label: 'Élèves', href: adminStudentsIndex.url({ school: props.student.school.slug }) });
    } else {
        items.push({ label: 'Liste de classe', href: classlist.url() });
        if (firstGroup.value) {
            items.push({
                label: `${firstGroup.value.grade}${firstGroup.value.name} — ${firstGroup.value.school.name}`,
                href: showClasslist.url({ group: firstGroup.value.slug }),
            });
        }
    }

    items.push({ label: fullName.value });

    return items;
});

const modalRef = ref<InstanceType<typeof BaseModal> | null>(null);
const form = useForm({ lastname: props.student.lastname, firstname: props.student.firstname, email: props.student.email ?? '' });

function openEdit() {
    form.lastname = props.student.lastname;
    form.firstname = props.student.firstname;
    form.email = props.student.email ?? '';
    form.clearErrors();
    nextTick(() => modalRef.value?.open());
}

function save() {
    form.transform((data) => ({ ...data, email: data.email || null }))
        .patch(updateStudent.url({ student: props.student.slug }), {
            preserveScroll: true,
            onSuccess: () => modalRef.value?.close(),
        });
}
</script>

<template>
    <Breadcrumb :items="breadcrumbItems" />

    <section class="flex flex-col gap-6 xl:flex-row xl:items-start">
        <div class="min-w-0 flex-1 overflow-hidden rounded-2xl bg-white">
            <div class="flex items-center justify-between border-b border-neutral-300/10 px-6 py-5">
                <h2 class="text-xl font-bold text-text-base">{{ fullName }}</h2>
                <Button
                    v-if="isAdmin"
                    variant="secondary"
                    size="sm"
                    :icon-only="true"
                    title="Modifier l'élève"
                    @click="openEdit"
                >
                    <template #icon>
                        <Edit :size="16" :stroke-width="2" aria-hidden="true" />
                    </template>
                </Button>
            </div>

            <dl class="divide-y divide-neutral-100 px-6">
                <div class="flex items-center justify-between py-4">
                    <dt class="text-xs font-bold tracking-wider text-stone-500 uppercase">Nom</dt>
                    <dd class="text-sm font-medium text-text-base">{{ student.lastname }}</dd>
                </div>
                <div class="flex items-center justify-between py-4">
                    <dt class="text-xs font-bold tracking-wider text-stone-500 uppercase">Prénom</dt>
                    <dd class="text-sm font-medium text-text-base">{{ student.firstname }}</dd>
                </div>
                <div class="flex items-center justify-between py-4">
                    <dt class="text-xs font-bold tracking-wider text-stone-500 uppercase">Email</dt>
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

        <section class="flex w-full shrink-0 flex-col gap-4 xl:w-80">
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
                            <p class="text-xs text-stone-500">
                                {{ group.school.name }}
                            </p>
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

                    <li v-if="!student.groups?.length"><EmptyState message="Aucune classe" size="sm" /></li>
                </ul>
            </div>
        </section>
    </section>

    <div v-if="isAdmin" class="min-w-0 flex-1 overflow-hidden rounded-2xl bg-white">
        <div class="border-b border-neutral-300/10 px-6 py-5">
            <h3 class="text-xl font-bold text-text-base">
                Historique des présences
                <span v-if="absenceHistory?.length" class="text-border-figma">({{ absenceHistory.length }})</span>
            </h3>
        </div>

        <ul v-if="absenceHistory?.length" class="sm:hidden divide-y divide-neutral-100">
            <li
                v-for="(record, i) in absenceHistory"
                :key="i"
                class="flex items-start justify-between gap-3 px-4 py-4"
            >
                <div class="flex min-w-0 flex-col gap-1">
                    <span class="text-sm font-medium text-text-base">
                        {{ formatDate(record.date) }}
                        <span v-if="record.time" class="text-stone-400"> · {{ record.time }}</span>
                    </span>
                    <span class="text-xs text-stone-500">
                        {{ record.subject ?? '—' }}
                        <span v-if="record.group"> · {{ record.group }}</span>
                    </span>
                </div>
                <span
                    class="shrink-0 rounded-lg px-2 py-1 text-xs font-bold"
                    :class="ATTENDANCE_STATUS_COLORS[record.type].join(' ')"
                >
                    {{ ATTENDANCE_STATUS_LABELS[record.type] ?? record.type }}
                </span>
            </li>
        </ul>

        <div v-if="absenceHistory?.length" class="hidden sm:block overflow-x-auto">
            <table class="w-full border-collapse text-left">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-stone-500">Date</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-stone-500">Heure de
                            cours</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-stone-500">Cours</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-stone-500">Classe</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-stone-500">Type</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    <tr
                        v-for="(record, i) in absenceHistory"
                        :key="i"
                        class="transition-colors hover:bg-gray-50"
                    >
                        <td class="px-6 py-4 text-sm text-text-base">{{ formatDate(record.date) }}</td>
                        <td class="px-6 py-4 text-sm text-text-base">{{ record.time ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm text-text-base">{{ record.subject ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm text-text-base">{{ record.group ?? '—' }}</td>
                        <td class="px-6 py-4">
                            <span
                                class="rounded-lg px-2 py-1 text-xs font-bold"
                                :class="ATTENDANCE_STATUS_COLORS[record.type].join(' ')"
                            >
                                {{ ATTENDANCE_STATUS_LABELS[record.type] ?? record.type }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <EmptyState v-else message="Aucune absence enregistrée" size="sm" />
    </div>

    <div v-else class="min-w-0 flex-1 overflow-hidden rounded-2xl bg-white">
        <div class="border-b border-neutral-300/10 px-6 py-5">
            <h3 class="text-xl font-bold text-text-base">Évaluations</h3>
        </div>
        <div class="flex flex-col items-center gap-3 px-6 py-16 text-center">
            <svg class="size-10 text-stone-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <p class="text-sm font-bold text-stone-400">Fonctionnalité à venir</p>
            <p class="text-xs text-stone-300">Le suivi des évaluations sera disponible prochainement.</p>
        </div>
    </div>

    <BaseModal ref="modalRef">
        <form class="flex flex-col gap-5" @submit.prevent="save">
            <h2 class="text-xl font-bold text-black">Modifier l'élève</h2>

            <InputLabel v-model="form.lastname" label="Nom" placeholder="Dupont" :error="form.errors.lastname" />
            <InputLabel v-model="form.firstname" label="Prénom" placeholder="Marie" :error="form.errors.firstname" />
            <InputLabel v-model="form.email" type="email" label="Email (optionnel)" placeholder="marie@exemple.be" :error="form.errors.email" />

            <div class="flex gap-3">
                <Button
                    type="submit"
                    variant="primary"
                    size="sm"
                    label="Enregistrer"
                    class="flex-1"
                    :disabled="!form.lastname || !form.firstname"
                    :loading="form.processing"
                />
                <Button
                    type="button"
                    variant="danger"
                    size="sm"
                    label="Annuler"
                    class="flex-1"
                    @click="modalRef?.close()"
                />
            </div>
        </form>
    </BaseModal>
</template>

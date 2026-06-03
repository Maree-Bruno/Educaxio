<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import ProfileSection from '@/components/settings/ProfileSection.vue';
import Button from '@/components/widgets/Button.vue';
import DateField from '@/components/widgets/DateField.vue';
import InputLabel from '@/components/widgets/form/InputLabel.vue';
import { setPageTitle } from '@/composables/usePageTitle';
import { store, update } from '@/routes/admin/academic-years';
import { useToasterStore } from '@/stores/toaster';

interface School { id: number; name: string; slug: string }

interface AcademicYear {
    id: number;
    year: string;
    start_date: string | null;
    end_date: string | null;
    is_current: boolean;
    is_archived: boolean;
}

const props = defineProps<{
    school: School;
    years: AcademicYear[];
}>();

setPageTitle('Années scolaires');

const toaster = useToasterStore();

function defaultDates(year: string): { start_date: string; end_date: string } | null {
    const match = year.match(/^(\d{4})-(\d{4})$/);

    if (!match) {
        return null;
    }

    return { start_date: `${match[1]}-09-01`, end_date: `${match[2]}-06-30` };
}

// ── Edit existing years ─────────────────────────────────────────────────────

interface EditState {
    start_date: string;
    end_date: string;
    processing: boolean;
}

const editStates = ref<Record<number, EditState>>(
    Object.fromEntries(
        props.years.map((y) => [
            y.id,
            { start_date: y.start_date ?? '', end_date: y.end_date ?? '', processing: false },
        ]),
    ),
);

function saveYear(year: AcademicYear) {
    const state = editStates.value[year.id];

    if (!state) {
        return;
    }

    state.processing = true;

    router.patch(
        update.url({ school: props.school.slug, academicYear: year.id }),
        { start_date: state.start_date, end_date: state.end_date },
        {
            preserveScroll: true,
            onSuccess: () => toaster.success('Dates mises à jour'),
            onFinish: () => (state.processing = false),
        },
    );
}

// ── Add new year ────────────────────────────────────────────────────────────

const newYear = reactive({ year: '', start_date: '', end_date: '' });
const newYearProcessing = ref(false);

const yearError = computed(() => {
    if (!newYear.year) {
        return '';
    }

    return /^\d{4}-\d{4}$/.test(newYear.year) ? '' : 'Format attendu : AAAA-AAAA (ex: 2026-2027)';
});

function onYearChange(val: string) {
    newYear.year = val;
    const dates = defaultDates(val);

    if (dates) {
        newYear.start_date = dates.start_date;
        newYear.end_date   = dates.end_date;
    }
}

function submitNewYear() {
    newYearProcessing.value = true;

    router.post(
        store.url({ school: props.school.slug }),
        { year: newYear.year, start_date: newYear.start_date, end_date: newYear.end_date },
        {
            preserveScroll: true,
            onSuccess: () => {
                newYear.year       = '';
                newYear.start_date = '';
                newYear.end_date   = '';
                toaster.success('Année ajoutée');
            },
            onFinish: () => (newYearProcessing.value = false),
        },
    );
}
</script>

<template>
    <div class="flex flex-col gap-6">
        <ProfileSection
            title="Années configurées"
            description="Ajustez les dates de début et de fin pour chaque année."
        >
            <p v-if="years.length === 0" class="text-sm italic text-stone-400">
                Aucune année configurée
            </p>

            <ul v-else class="flex flex-col gap-4">
                <li v-for="year in years" :key="year.id">
                    <form
                        class="flex flex-col gap-4 rounded-xl border border-neutral-200 p-4"
                        :class="{ 'opacity-60': year.is_archived }"
                        @submit.prevent="saveYear(year)"
                    >
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-bold text-text-base">{{ year.year }}</span>
                            <span
                                v-if="year.is_current"
                                class="rounded-full bg-blue/10 px-2 py-0.5 text-xs font-bold text-blue"
                            >en cours</span>
                            <span
                                v-if="year.is_archived"
                                class="rounded-full bg-stone-100 px-2 py-0.5 text-xs font-bold text-stone-400"
                            >archivée</span>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                            <DateField
                                label="Début"
                                :model-value="editStates[year.id].start_date"
                                :disabled="year.is_archived"
                                @update:model-value="editStates[year.id].start_date = $event"
                            />
                            <DateField
                                label="Fin"
                                :model-value="editStates[year.id].end_date"
                                :disabled="year.is_archived"
                                @update:model-value="editStates[year.id].end_date = $event"
                            />
                            <Button
                                type="submit"
                                variant="primary"
                                size="sm"
                                :loading="editStates[year.id].processing"
                                :disabled="year.is_archived"
                                class="shrink-0"
                            >
                                Enregistrer
                            </Button>
                        </div>
                    </form>
                </li>
            </ul>
        </ProfileSection>

        <ProfileSection
            title="Ajouter une année"
            description="Les dates sont pré-remplies selon la convention FWB (1 sept → 30 juin)."
        >
            <form @submit.prevent="submitNewYear">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
                    <InputLabel
                        :model-value="newYear.year"
                        label="Année"
                        placeholder="2026-2027"
                        :fluid="false"
                        @update:model-value="onYearChange"
                    />
                    <DateField
                        label="Début"
                        :model-value="newYear.start_date"
                        @update:model-value="newYear.start_date = $event"
                    />
                    <DateField
                        label="Fin"
                        :model-value="newYear.end_date"
                        @update:model-value="newYear.end_date = $event"
                    />
                    <Button
                        type="submit"
                        variant="primary"
                        size="sm"
                        :loading="newYearProcessing"
                        :disabled="!!yearError || !newYear.year"
                        class="shrink-0"
                    >
                        Ajouter
                    </Button>
                </div>
                <p
                    aria-live="polite"
                    class="pt-1.5 font-manrope text-sm font-medium text-pink"
                    :class="{ invisible: !yearError }"
                >{{ yearError }}</p>
            </form>
        </ProfileSection>
    </div>
</template>

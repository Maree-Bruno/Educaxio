<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import Button from '@/components/widgets/Button.vue';
import SelectField from '@/components/widgets/SelectField.vue';
import type { AcademicYear, Subject } from '@/types';

const props = defineProps<{
    academicYears: Pick<AcademicYear, 'id' | 'year'>[];
    subjects: Pick<Subject, 'id' | 'name'>[];
    initialData?: {
        grade: string;
        name: string;
        school_id: number | null;
        academic_year_id: number | null;
        subject_id: number | null;
    };
    mode: 'create' | 'edit';
    action: string;
}>();

const yearOptions = props.academicYears.map((y) => ({ value: y.id, label: String(y.year) }));
const subjectOptions = props.subjects.map((s) => ({ value: s.id, label: s.name }));

const form = useForm({
    grade: props.initialData?.grade ?? '',
    name: props.initialData?.name ?? '',
    school_id: props.initialData?.school_id ?? null,
    academic_year_id: props.initialData?.academic_year_id ?? null,
    subject_id: props.initialData?.subject_id ?? null,
});

watch(
    () => props.initialData,
    (data) => {
        if (!data) return;
        form.grade = data.grade;
        form.name = data.name;
        form.school_id = data.school_id;
        form.academic_year_id = data.academic_year_id;
        form.subject_id = data.subject_id;
    },
    { deep: true },
);

function submit() {
    if (props.mode === 'create') {
        form.post(props.action);
    } else {
        form.patch(props.action);
    }
}

function cancel() {
    if (props.mode === 'create') {
        router.visit('/classlist');
    } else {
        form.reset();
    }
}
</script>

<template>
    <form class="flex flex-col gap-4 rounded-3xl bg-white p-6" @submit.prevent="submit">
        <h3 class="text-xl font-semibold text-text-base">
            {{ mode === 'create' ? 'Nouvelle classe' : 'Modifier la classe' }}
        </h3>

        <!-- Classe -->
        <div class="flex flex-col gap-2">
            <span class="text-xs font-bold uppercase leading-4 tracking-wide text-border-figma">
                Classe
            </span>
            <div class="flex gap-2">
                <input
                    id="grade"
                    v-model="form.grade"
                    type="text"
                    placeholder="Ex: 3"
                    class="w-20 shrink-0 rounded-2xl bg-white px-3 py-3 font-manrope text-sm font-bold text-text-base outline-1 -outline-offset-1 outline-border-figma transition-colors focus:outline-2 focus:outline-border-figma"
                />
                <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    placeholder="Ex: A"
                    class="min-w-0 flex-1 rounded-2xl bg-white px-3 py-3 font-manrope text-sm font-bold text-text-base outline-1 -outline-offset-1 outline-border-figma transition-colors focus:outline-2 focus:outline-border-figma"
                />
            </div>
            <p v-if="form.errors.grade || form.errors.name" class="text-xs text-pink">
                {{ form.errors.grade || form.errors.name }}
            </p>
        </div>

        <!-- Année scolaire -->
        <div class="flex flex-col gap-1">
            <SelectField
                id="year"
                v-model="form.academic_year_id"
                label="Année scolaire"
                :options="yearOptions"
            />
            <p v-if="form.errors.academic_year_id" class="text-xs text-pink">
                {{ form.errors.academic_year_id }}
            </p>
        </div>
        <!-- Actions -->
        <Button
            type="submit"
            variant="primary"
            size="sm"
            :label="mode === 'create' ? 'Créer la classe' : 'Enregistrer'"
            :loading="form.processing"
            class="w-full"
        />
        <Button
            type="button"
            variant="danger"
            size="sm"
            label="Annuler"
            :disabled="form.processing"
            class="w-full"
            @click="cancel"
        />
    </form>
</template>

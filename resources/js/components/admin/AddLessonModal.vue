<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import BaseModal from '@/components/widgets/BaseModal.vue';
import Button from '@/components/widgets/Button.vue';
import SelectField from '@/components/widgets/SelectField.vue';
import { store as adminLessonsStore } from '@/routes/admin/lessons';
import { useToasterStore } from '@/stores/toaster';

interface Subject { id: number; name: string; is_language: boolean }
interface Teacher { id: number; name: string; subject_ids: number[] }
interface Lesson  { id: number; group_id: number; subject_id: number }
interface Group   { id: number; grade: string; name: string }

const props = defineProps<{
    school:   { slug: string };
    subjects: Subject[];
    teachers: Teacher[];
    lessons:  Lesson[];
}>();

const modalRef      = ref<InstanceType<typeof BaseModal> | null>(null);
const addingToGroup = ref<Group | null>(null);
const form          = useForm<{ subject_id: string | number | null; lm_level: string; teacher_ids: number[] }>({
    subject_id: null,
    lm_level:   '',
    teacher_ids: [],
});
const toaster = useToasterStore();

const subjectOptions = computed(() =>
    addingToGroup.value
        ? availableSubjectsFor(addingToGroup.value).map((s) => ({ value: s.id, label: s.name }))
        : [],
);

const selectedSubject = computed(() =>
    props.subjects.find((s) => s.id === Number(form.subject_id)) ?? null,
);

const teacherOptions = computed(() =>
    form.subject_id
        ? props.teachers.filter((t) => t.subject_ids.includes(Number(form.subject_id)))
        : [],
);

watch(() => form.subject_id, () => {
    form.teacher_ids = [];
    form.lm_level    = '';
});

function availableSubjectsFor(group: Group): Subject[] {
    const assigned = props.lessons.filter((l) => l.group_id === group.id).map((l) => l.subject_id);
    return props.subjects.filter((s) => !assigned.includes(s.id));
}

function open(group: Group) {
    addingToGroup.value = group;
    form.reset();
    nextTick(() => modalRef.value?.open());
}

function close() {
    modalRef.value?.close();
}

function toggleTeacher(id: number) {
    const idx = form.teacher_ids.indexOf(id);

    if (idx === -1) {
        form.teacher_ids.push(id);
    } else {
        form.teacher_ids.splice(idx, 1);
    }
}

function submit() {
    if (!addingToGroup.value) {
        return;
    }

    const groupId = addingToGroup.value.id;
    form.transform((data) => ({
        ...data,
        group_id: groupId,
        lm_level: data.lm_level === '' ? null : Number(data.lm_level),
    })).post(adminLessonsStore.url({ school: props.school.slug }), {
        preserveScroll: true,
        onSuccess: () => {
            close();
            toaster.success('Cours ajouté');
        },
    });
}

defineExpose({ open, availableSubjectsFor });
</script>

<template>
    <BaseModal ref="modalRef">
        <form v-if="addingToGroup" class="flex flex-col gap-5" @submit.prevent="submit">
            <div>
                <h2 class="text-xl font-bold text-black">Ajouter une matière</h2>
                <p class="mt-1 text-sm font-bold text-border-figma">
                    {{ addingToGroup.grade }}{{ addingToGroup.name }}
                </p>
            </div>

            <SelectField
                v-model="form.subject_id"
                label="Matière"
                placeholder="Choisir une matière"
                :options="subjectOptions"
            />

            <SelectField
                v-if="selectedSubject?.is_language"
                v-model="form.lm_level"
                label="Niveau LM (langue étrangère)"
                placeholder="Sans niveau LM"
                :options="[{ value: '1', label: 'LM1' }, { value: '2', label: 'LM2' }, { value: '3', label: 'LM3' }]"
            />

            <fieldset v-if="form.subject_id" class="flex flex-col gap-2 border-0 p-0 m-0">
                <legend class="text-xs font-bold tracking-wider text-border-figma uppercase">
                    Profs <span class="font-normal normal-case">(optionnel)</span>
                </legend>
                <div class="flex flex-col gap-1.5 mt-2">
                    <label
                        v-for="teacher in teacherOptions"
                        :key="teacher.id"
                        class="flex cursor-pointer items-center gap-3 rounded-xl bg-white px-3 py-2.5 outline outline-1 -outline-offset-1"
                        :class="form.teacher_ids.includes(teacher.id) ? 'outline-blue' : 'outline-border-figma'"
                    >
                        <input
                            type="checkbox"
                            class="accent-blue"
                            :checked="form.teacher_ids.includes(teacher.id)"
                            @change="toggleTeacher(teacher.id)"
                        />
                        <span class="text-sm font-bold text-text-base">{{ teacher.name }}</span>
                    </label>
                    <p v-if="teacherOptions.length === 0" class="text-xs text-border-figma italic">
                        Aucun prof rattaché à cette matière
                    </p>
                </div>
            </fieldset>

            <div class="flex gap-3">
                <Button
                    type="submit"
                    variant="primary"
                    size="sm"
                    label="Enregistrer"
                    class="flex-1"
                    :disabled="!form.subject_id"
                    :loading="form.processing"
                />
                <Button type="button" variant="danger" size="sm" label="Annuler" class="flex-1" @click="close" />
            </div>
        </form>
    </BaseModal>
</template>

<script setup lang="ts">
import { computed, nextTick, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import BaseModal from '@/components/widgets/BaseModal.vue';
import Button from '@/components/widgets/Button.vue';
import SearchInput from '@/components/widgets/SearchInput.vue';
import { resolveSubjectLabel } from '@/composables/useSubjectLabel';
import { sync as syncLessonTeachers } from '@/routes/admin/lessons/teachers';
import { useToasterStore } from '@/stores/toaster';

interface Subject { id: number; name: string }
interface Group   { id: number; grade: string; name: string }
interface Teacher { id: number; name: string; subject_ids: number[] }
interface Lesson  { id: number; lm_level: number | null; group: Group; subject: Subject; users: Teacher[] }

const props = defineProps<{
    school:   { slug: string };
    teachers: Teacher[];
}>();

const modalRef      = ref<InstanceType<typeof BaseModal> | null>(null);
const editingLesson = ref<Lesson | null>(null);
const form          = useForm({ teacher_ids: [] as number[] });
const toaster       = useToasterStore();
const search        = ref('');

const filteredTeachers = computed(() => {
    const subjectId = editingLesson.value?.subject.id;
    const q = search.value.trim().toLowerCase();

    return props.teachers
        .filter((t) => !subjectId || t.subject_ids.includes(subjectId))
        .filter((t) => !q || t.name.toLowerCase().includes(q));
});

function open(lesson: Lesson) {
    editingLesson.value = lesson;
    form.teacher_ids = lesson.users.map((u) => u.id);
    form.clearErrors();
    search.value = '';
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

function save() {
    if (!editingLesson.value) {
        return;
    }

    form.put(syncLessonTeachers.url({ school: props.school.slug, lesson: editingLesson.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            close();
            toaster.success('Profs enregistrés');
        },
    });
}

defineExpose({ open });
</script>

<template>
    <BaseModal ref="modalRef">
        <form v-if="editingLesson" class="flex flex-col gap-5" @submit.prevent="save">
            <div>
                <h2 class="text-xl font-bold text-black">Profs assignés</h2>
                <p class="mt-1 text-sm font-bold text-border-figma">
                    {{ resolveSubjectLabel(editingLesson.subject.name, editingLesson.lm_level) }} · {{ editingLesson.group.grade }}{{ editingLesson.group.name }}
                </p>
            </div>

            <SearchInput v-model="search" placeholder="Rechercher un professeur…" />

            <div class="flex max-h-72 flex-col gap-1.5 overflow-y-auto">
                <label
                    v-for="teacher in filteredTeachers"
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
                <p v-if="filteredTeachers.length === 0" class="text-xs italic text-border-figma">
                    {{ search ? 'Aucun résultat' : 'Aucun prof dans cette école' }}
                </p>
            </div>

            <div class="flex gap-3">
                <Button type="submit" variant="primary" size="sm" label="Enregistrer" class="flex-1" :loading="form.processing" />
                <Button type="button" variant="danger" size="sm" label="Annuler" class="flex-1" @click="close" />
            </div>
        </form>
    </BaseModal>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Badge from '@/components/widgets/Badge.vue';
import BaseModal from '@/components/widgets/BaseModal.vue';
import Button from '@/components/widgets/Button.vue';
import EmptyState from '@/components/widgets/EmptyState.vue';
import InputLabel from '@/components/widgets/form/InputLabel.vue';
import SearchInput from '@/components/widgets/SearchInput.vue';
import KbdShortcut from '@/components/widgets/KbdShortcut.vue';
import { useSaveShortcut } from '@/composables/useSaveShortcut';
import { attach } from '@/routes/classlist/students';

const props = defineProps<{
    groupSlug: string;
    schoolStudents: { id: number; lastname: string; firstname: string; groups: { id: number; grade: string; name: string }[] }[];
}>();

const modalRef = ref<InstanceType<typeof BaseModal> | null>(null);
const isOpen   = ref(false);
const addTab = ref<'new' | 'existing'>('existing');
const addForm = useForm({ lastname: '', firstname: '', email: '' });
const attachForm = useForm({ student_ids: [] as number[] });
const studentSearch = ref('');
const selectedStudentIds = ref<number[]>([]);

watch(addTab, () => {
    studentSearch.value = '';
    selectedStudentIds.value = [];
});

const filteredSchoolStudents = computed(() => {
    const term = studentSearch.value.trim().toLowerCase();

    if (!term) {
        return props.schoolStudents;
    }

    return props.schoolStudents.filter(
        (s) => s.lastname.toLowerCase().includes(term) || s.firstname.toLowerCase().includes(term),
    );
});

function open() {
    isOpen.value = true;
    addTab.value = 'existing';
    addForm.reset();
    studentSearch.value = '';
    selectedStudentIds.value = [];
    modalRef.value?.open();
}

function close() {
    isOpen.value = false;
    modalRef.value?.close();
}

function submitNew() {
    addForm.transform((data) => ({ ...data, email: data.email || null }))
        .post(attach.url({ group: props.groupSlug }), {
            preserveScroll: true,
            onSuccess: close,
        });
}

function attachSelected() {
    if (!selectedStudentIds.value.length) {
        return;
    }

    attachForm.student_ids = [...selectedStudentIds.value];
    attachForm.post(attach.url({ group: props.groupSlug }), {
        preserveScroll: true,
        onSuccess: close,
    });
}

useSaveShortcut(() => {
    if (!isOpen.value) return;
    if (addTab.value === 'new') submitNew();
    else attachSelected();
});

defineExpose({ open });
</script>

<template>
    <BaseModal ref="modalRef">
        <div class="flex flex-col gap-5">
            <h2 class="text-xl font-bold text-black">Ajouter un élève</h2>

            <div class="flex gap-1 rounded-xl bg-gray-100 p-1">
                <button
                    type="button"
                    class="flex-1 rounded-lg py-1.5 text-sm font-bold transition-colors"
                    :class="addTab === 'new' ? 'bg-white text-text-base shadow-sm' : 'text-border-figma hover:text-text-base'"
                    @click="addTab = 'new'"
                >
                    Nouvel élève
                </button>
                <button
                    type="button"
                    class="flex-1 rounded-lg py-1.5 text-sm font-bold transition-colors"
                    :class="addTab === 'existing' ? 'bg-white text-text-base shadow-sm' : 'text-border-figma hover:text-text-base'"
                    @click="addTab = 'existing'"
                >
                    Élève existant
                </button>
            </div>

            <form v-if="addTab === 'new'" class="flex flex-col gap-4" @submit.prevent="submitNew">
                <InputLabel v-model="addForm.lastname" label="Nom" placeholder="Dupont" :error="addForm.errors.lastname" />
                <InputLabel v-model="addForm.firstname" label="Prénom" placeholder="Marie" :error="addForm.errors.firstname" />
                <InputLabel v-model="addForm.email" type="email" label="Email (optionnel)" placeholder="marie@exemple.be" :error="addForm.errors.email" />
                <div class="flex gap-3">
                    <Button type="submit" variant="primary" size="md" class="flex-1"
                        :disabled="!addForm.lastname || !addForm.firstname" :loading="addForm.processing">
                        Ajouter <KbdShortcut keys="⌘S" size="md" />
                    </Button>
                    <Button type="button" variant="danger" size="md" label="Annuler" class="flex-1" @click="close" />
                </div>
            </form>

            <form v-else class="flex flex-col gap-4" @submit.prevent="attachSelected">
                <SearchInput id="student-search" v-model="studentSearch" placeholder="Rechercher un élève…" />
                <div class="flex flex-col divide-y divide-neutral-100 rounded-2xl bg-white overflow-hidden max-h-64 overflow-y-auto">
                    <EmptyState
                        v-if="filteredSchoolStudents.length === 0"
                        :message="schoolStudents.length === 0 ? 'Tous les élèves de l\'école sont déjà dans ce groupe.' : 'Aucun résultat'"
                        size="sm"
                    />
                    <label
                        v-for="s in filteredSchoolStudents"
                        :key="s.id"
                        class="flex cursor-pointer items-center gap-3 px-4 py-3 transition-colors hover:bg-gray-50"
                    >
                        <input
                            type="checkbox"
                            :value="s.id"
                            v-model="selectedStudentIds"
                            class="h-4 w-4 shrink-0 rounded accent-blue"
                        />
                        <div class="flex min-w-0 flex-col gap-0.5">
                            <span class="text-sm font-medium text-text-base">{{ s.lastname }} {{ s.firstname }}</span>
                            <div v-if="s.groups.length" class="flex flex-wrap gap-1">
                                <Badge v-for="g in s.groups" :key="g.id" variant="neutral">
                                    {{ g.grade }}{{ g.name }}
                                </Badge>
                            </div>
                        </div>
                    </label>
                </div>
                <div class="flex gap-3">
                    <Button
                        type="submit"
                        variant="primary"
                        size="md"
                        class="flex-1"
                        :disabled="!selectedStudentIds.length"
                        :loading="attachForm.processing"
                    >
                        {{ selectedStudentIds.length ? `Ajouter (${selectedStudentIds.length})` : 'Ajouter' }}
                        <KbdShortcut keys="⌘S" size="md" />
                    </Button>
                    <Button type="button" variant="danger" size="md" label="Annuler" class="flex-1" @click="close" />
                </div>
            </form>
        </div>
    </BaseModal>
</template>

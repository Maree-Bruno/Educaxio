<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';
import ProfileAvatarPicker from '@/components/settings/ProfileAvatarPicker.vue';
import Badge from '@/components/widgets/Badge.vue';
import BaseModal from '@/components/widgets/BaseModal.vue';
import Button from '@/components/widgets/Button.vue';
import InputLabel from '@/components/widgets/form/InputLabel.vue';
import SelectField from '@/components/widgets/SelectField.vue';
import KbdShortcut from '@/components/widgets/KbdShortcut.vue';
import { useSaveShortcut } from '@/composables/useSaveShortcut';
import { store as adminStudentsStore } from '@/routes/admin/students';
import { useToasterStore } from '@/stores/toaster';

interface Group {
    id: number;
    grade: string;
    name: string;
}

const props = defineProps<{
    school: { slug: string };
    groups: Group[];
}>();

const modalRef = ref<InstanceType<typeof BaseModal> | null>(null);
const isOpen   = ref(false);
const form = useForm({
    lastname: '',
    firstname: '',
    email: '',
    group_ids: [] as string[],
    picture: null as File | null,
});
const toaster = useToasterStore();

const availableGroupOptions = computed(() =>
    props.groups
        .filter((g) => !form.group_ids.includes(String(g.id)))
        .map((g) => ({ value: String(g.id), label: `${g.grade}${g.name}` })),
);

function addGroup(id: string | number | null) {
    if (id == null) {
        return;
    }

    const strId = String(id);

    if (!form.group_ids.includes(strId)) {
        form.group_ids.push(strId);
    }
}

function removeGroup(id: string) {
    form.group_ids = form.group_ids.filter((gid) => gid !== id);
}

function open() {
    isOpen.value = true;
    form.reset();
    nextTick(() => modalRef.value?.open());
}

function close() {
    isOpen.value = false;
    modalRef.value?.close();
}

function save() {
    form.transform((data) => ({
        ...data,
        email: data.email || null,
        group_ids: data.group_ids.map(Number),
    })).post(adminStudentsStore.url({ school: props.school.slug }), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            close();
            toaster.success('Élève créé');
        },
    });
}

useSaveShortcut(() => {
    if (isOpen.value) {
        save();
    }
});

defineExpose({ open });
</script>

<template>
    <BaseModal ref="modalRef">
        <form class="flex flex-col gap-5" @submit.prevent="save">
            <h2 class="text-xl font-bold text-black">Nouvel élève</h2>
            <div class="flex justify-center">
                <ProfileAvatarPicker
                    :current-picture="null"
                    :name="
                        form.firstname || form.lastname
                            ? `${form.firstname} ${form.lastname}`.trim()
                            : 'Élève'
                    "
                    @update:picture="form.picture = $event"
                />
            </div>
            <InputLabel
                v-model="form.lastname"
                label="Nom"
                placeholder="Dupont"
                autocomplete="family-name"
                required
                :error="form.errors.lastname"
            />
            <InputLabel
                v-model="form.firstname"
                label="Prénom"
                placeholder="Marie"
                autocomplete="given-name"
                required
                :error="form.errors.firstname"
            />
            <InputLabel
                v-model="form.email"
                type="email"
                label="Email (optionnel)"
                placeholder="marie@exemple.be"
                autocomplete="email"
                :error="form.errors.email"
            />

            <div class="flex flex-col gap-2">
                <label
                    for="add-group-select"
                    class="text-xs font-bold tracking-wider text-border-figma uppercase"
                >
                    Groupes
                    <span class="font-normal normal-case">(optionnel)</span>
                </label>
                <div
                    v-if="form.group_ids.length"
                    class="flex flex-wrap gap-1.5"
                >
                    <Badge
                        v-for="id in form.group_ids"
                        :key="id"
                        removable
                        @remove="removeGroup(id)"
                    >
                        {{ groups.find((g) => String(g.id) === id)?.grade
                        }}{{ groups.find((g) => String(g.id) === id)?.name }}
                    </Badge>
                </div>
                <SelectField
                    v-if="availableGroupOptions.length"
                    :model-value="null"
                    placeholder="Ajouter un groupe…"
                    :options="availableGroupOptions"
                    @update:model-value="addGroup"
                />
                <span
                    v-else-if="groups.length"
                    class="text-xs text-border-figma"
                >
                    Tous les groupes sont assignés.
                </span>
            </div>

            <div class="flex gap-3">
                <Button
                    type="submit"
                    variant="primary"
                    size="sm"
                    class="flex-1"
                    :disabled="!form.lastname || !form.firstname"
                    :loading="form.processing"
                >
                    Enregistrer <KbdShortcut keys="⌘S" />
                </Button>
                <Button
                    type="button"
                    variant="danger"
                    size="sm"
                    label="Annuler"
                    class="flex-1"
                    @click="close"
                />
            </div>
        </form>
    </BaseModal>
</template>

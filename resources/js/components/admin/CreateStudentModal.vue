<script setup lang="ts">
import { computed, nextTick, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Badge from '@/components/widgets/Badge.vue';
import BaseModal from '@/components/widgets/BaseModal.vue';
import Button from '@/components/widgets/Button.vue';
import InputLabel from '@/components/widgets/form/InputLabel.vue';
import { store as adminStudentsStore } from '@/routes/admin/students';
import { useToasterStore } from '@/stores/toaster';

interface Group { id: number; grade: string; name: string }

const props = defineProps<{
    school:  { slug: string };
    groups:  Group[];
}>();

const modalRef = ref<InstanceType<typeof BaseModal> | null>(null);
const form     = useForm({ lastname: '', firstname: '', email: '', group_ids: [] as string[] });
const toaster  = useToasterStore();

const availableGroups = computed(() =>
    props.groups.filter((g) => !form.group_ids.includes(String(g.id))),
);

function addGroup(e: Event) {
    const id = (e.target as HTMLSelectElement).value;

    if (id && !form.group_ids.includes(id)) {
        form.group_ids.push(id);
    }

    (e.target as HTMLSelectElement).value = '';
}

function removeGroup(id: string) {
    form.group_ids = form.group_ids.filter((gid) => gid !== id);
}

function open() {
    form.reset();
    nextTick(() => modalRef.value?.open());
}

function close() {
    modalRef.value?.close();
}

function save() {
    form.transform((data) => ({
        ...data,
        email:     data.email || null,
        group_ids: data.group_ids.map(Number),
    })).post(adminStudentsStore.url({ school: props.school.slug }), {
        preserveScroll: true,
        onSuccess: () => {
            close();
            toaster.success('Élève créé');
        },
    });
}

defineExpose({ open });
</script>

<template>
    <BaseModal ref="modalRef">
        <form class="flex flex-col gap-5" @submit.prevent="save">
            <h2 class="text-xl font-bold text-black">Nouvel élève</h2>
            <InputLabel v-model="form.lastname" label="Nom" placeholder="Dupont" :error="form.errors.lastname" />
            <InputLabel v-model="form.firstname" label="Prénom" placeholder="Marie" :error="form.errors.firstname" />
            <InputLabel v-model="form.email" type="email" label="Email (optionnel)" placeholder="marie@exemple.be" :error="form.errors.email" />

            <div class="flex flex-col gap-2">
                <label class="text-xs font-bold uppercase tracking-wider text-border-figma">
                    Groupes <span class="normal-case font-normal">(optionnel)</span>
                </label>
                <div v-if="form.group_ids.length" class="flex flex-wrap gap-1.5">
                    <Badge
                        v-for="id in form.group_ids"
                        :key="id"
                        removable
                        @remove="removeGroup(id)"
                    >
                        {{ groups.find((g) => String(g.id) === id)?.grade }}{{ groups.find((g) => String(g.id) === id)?.name }}
                    </Badge>
                </div>
                <select
                    v-if="availableGroups.length"
                    class="w-full rounded-2xl bg-white px-3 py-3 text-sm text-text-base outline outline-1 -outline-offset-1 outline-border-figma"
                    @change="addGroup"
                >
                    <option value="">Ajouter un groupe…</option>
                    <option v-for="g in availableGroups" :key="g.id" :value="String(g.id)">
                        {{ g.grade }}{{ g.name }}
                    </option>
                </select>
                <span v-else-if="groups.length && !availableGroups.length" class="text-xs text-border-figma">
                    Tous les groupes sont assignés.
                </span>
            </div>

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
                <Button type="button" variant="danger" size="sm" label="Annuler" class="flex-1" @click="close" />
            </div>
        </form>
    </BaseModal>
</template>

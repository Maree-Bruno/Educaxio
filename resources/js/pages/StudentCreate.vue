<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import Breadcrumb from '@/components/widgets/Breadcrumb.vue';
import Button from '@/components/widgets/Button.vue';
import InputLabel from '@/components/widgets/form/InputLabel.vue';
import SelectField from '@/components/widgets/SelectField.vue';
import { setPageTitle } from '@/composables/usePageTitle';
import type { Group } from '@/types';

setPageTitle('Nouvel élève');

const props = defineProps<{
    groups: (Pick<Group, 'id' | 'grade' | 'name' | 'slug' | 'school_id'> & {
        school: { id: number; name: string };
    })[];
    preselectedGroup: number | null;
}>();

const selectedGroup = computed(() =>
    props.preselectedGroup
        ? props.groups.find((g) => g.id === props.preselectedGroup)
        : null,
);

const breadcrumbItems = computed(() => {
    const items: { label: string; href?: string }[] = [
        { label: 'Liste de classe', href: '/classlist' },
    ];

    if (selectedGroup.value) {
        items.push({
            label: `${selectedGroup.value.grade}${selectedGroup.value.name} — ${selectedGroup.value.school.name}`,
            href: `/classlist/${selectedGroup.value.slug}`,
        });
    }

    items.push({ label: 'Nouvel élève' });

    return items;
});

const groupOptions = props.groups.map((g) => ({
    value: g.id,
    label: `${g.grade}${g.name} — ${g.school.name}`,
}));

const form = useForm({
    lastname: '',
    firstname: '',
    email: '',
    group_id: props.preselectedGroup,
});

function submit() {
    form.post('/students');
}
</script>

<template>
    <Breadcrumb :items="breadcrumbItems" />

    <div class="mx-auto max-w-lg">
        <form class="flex flex-col gap-4 rounded-3xl bg-white p-6" @submit.prevent="submit">
            <h2 class="text-xl font-semibold text-text-base">Nouvel élève</h2>

            <div class="flex gap-3">
                <InputLabel
                    v-model="form.lastname"
                    label="Nom"
                    placeholder="Ex: Dupont"
                    size="sm"
                    :error="form.errors.lastname"
                    class="flex-1"
                />
                <InputLabel
                    v-model="form.firstname"
                    label="Prénom"
                    placeholder="Ex: Marie"
                    size="sm"
                    :error="form.errors.firstname"
                    class="flex-1"
                />
            </div>

            <InputLabel
                v-model="form.email"
                label="Email"
                type="email"
                placeholder="Ex: marie.dupont@ecole.be"
                size="sm"
                :error="form.errors.email"
            />

            <div class="flex flex-col gap-1">
                <SelectField
                    id="group"
                    v-model="form.group_id"
                    label="Classe"
                    placeholder="Aucune classe"
                    :options="groupOptions"
                />
                <p v-if="form.errors.group_id" class="text-xs text-pink">
                    {{ form.errors.group_id }}
                </p>
            </div>

            <Button
                type="submit"
                variant="primary"
                size="sm"
                label="Créer l'élève"
                :loading="form.processing"
                class="mt-2 w-full"
            />
        </form>
    </div>
</template>

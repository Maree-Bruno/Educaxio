<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Button from './Button.vue';
import SearchInput from './SearchInput.vue';

const props = defineProps<{
    schools: { id: number; name: string }[];
}>();

const emit = defineEmits<{
    cancel:  [];
    success: [];
}>();

const search     = ref('');
const selectedId = ref<number | null>(null);
const form       = useForm({ school_id: null as number | null });

const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();

    return q ? props.schools.filter((s) => s.name.toLowerCase().includes(q)) : props.schools;
});

function submit() {
    if (!selectedId.value) {
        return;
    }

    form.school_id = selectedId.value;
    form.post('/pending/join-requests', {
        onSuccess: () => {
            selectedId.value = null;
            search.value = '';
            form.reset();
            emit('success');
        },
    });
}

function cancel() {
    selectedId.value = null;
    search.value = '';
    emit('cancel');
}
</script>

<template>
    <form class="flex flex-col gap-3" @submit.prevent="submit">
        <SearchInput v-model="search" placeholder="Rechercher un établissement…" />
        <div class="flex max-h-48 flex-col gap-1.5 overflow-y-auto">
            <button
                v-for="school in filtered"
                :key="school.id"
                type="button"
                :class="[
                    'flex items-center gap-3 rounded-xl border px-4 py-2.5 text-left transition-all',
                    selectedId === school.id ? 'border-blue bg-blue/5' : 'border-border-figma hover:border-blue',
                ]"
                @click="selectedId = school.id"
            >
                <span
                    :class="[
                        'size-3.5 shrink-0 rounded-sm border-2 transition-colors',
                        selectedId === school.id ? 'border-blue bg-blue' : 'border-gray-300',
                    ]"
                />
                <span class="text-sm font-medium text-text-base">{{ school.name }}</span>
            </button>
            <p v-if="filtered.length === 0" class="py-2 text-center text-sm text-gray-400">Aucun résultat</p>
        </div>
        <div class="flex gap-2">
            <Button type="button" variant="ghost" size="sm" label="Annuler" @click="cancel" />
            <Button type="submit" variant="primary" size="sm" label="Envoyer la demande" :disabled="!selectedId" :loading="form.processing" />
        </div>
    </form>
</template>

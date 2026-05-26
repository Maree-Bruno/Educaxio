<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Button from './Button.vue';

const props = defineProps<{
    schools: { id: number; name: string }[];
}>();

const emit = defineEmits<{
    cancel:  [];
    success: [];
}>();

const search     = ref('');
const selectedId = ref<number | null>(null);

const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();

    return q ? props.schools.filter((s) => s.name.toLowerCase().includes(q)) : props.schools;
});

function submit() {
    if (!selectedId.value) {
        return;
    }

    router.post('/pending/join-requests', { school_id: selectedId.value }, {
        onSuccess: () => {
            selectedId.value = null;
            search.value = '';
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
    <div class="flex flex-col gap-3">
        <input
            v-model="search"
            type="search"
            placeholder="Rechercher un établissement…"
            class="w-full rounded-2xl border border-border-figma bg-white px-3 py-2.5 font-manrope text-sm outline-none transition-all placeholder:text-gray-400 focus:border-blue focus:ring-2 focus:ring-blue/20"
        />
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
            <Button variant="ghost" size="sm" label="Annuler" @click="cancel" />
            <Button variant="primary" size="sm" label="Envoyer la demande" :disabled="!selectedId" @click="submit" />
        </div>
    </div>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { ref, watch } from 'vue';

const props = defineProps<{
    classSession: { id: number; notes: string | null } | null;
    lessonId: number | null;
    date: string;
    selectedEntry: number | null;
    lastSavedAt: string | null;
}>();

const journalNotes = ref(props.classSession?.notes ?? '');
watch(() => props.classSession, (s) => { journalNotes.value = s?.notes ?? ''; });

const form = useForm({ lesson_id: 0, date: '', notes: '' });

const save = useDebounceFn(() => {
    if (!props.lessonId) return;
    form.lesson_id = props.lessonId;
    form.date = props.date;
    form.notes = journalNotes.value;
    form.post('/class-sessions/notes', { preserveState: true, preserveScroll: true });
}, 800);
</script>

<template>
    <div class="flex flex-col gap-3 rounded-2xl bg-white p-6">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-bold text-stone-800">Journal de classe</h3>
            <span v-if="form.processing" class="text-[10px] text-stone-400">Sauvegarde…</span>
            <span v-else-if="lastSavedAt" class="text-[10px] text-stone-400">{{ lastSavedAt }}</span>
        </div>
        <textarea
            v-model="journalNotes"
            :disabled="!selectedEntry"
            rows="6"
            placeholder="Notes du cours…"
            class="w-full resize-none rounded-xl bg-bg-primary p-4 text-sm text-text-base placeholder:text-border-figma focus:outline-none disabled:cursor-not-allowed disabled:opacity-40"
            @input="save"
        />
    </div>
</template>
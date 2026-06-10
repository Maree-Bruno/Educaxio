<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { ref, watch } from 'vue';
import SaveSplitButton from '@/components/widgets/SaveSplitButton.vue';
import type { AfterSave } from '@/components/widgets/SaveSplitButton.vue';
import { store } from '@/routes/lesson-notes';

const props = defineProps<{
    lessonNote: {
        id: number;
        notes: string | null;
        savedAt: string | null;
    } | null;
    lessonId: number | null;
    date: string;
    selectedEntry: string | null;
}>();

const emit = defineEmits<{
    saved: [afterSave: AfterSave];
}>();

const journalNotes = ref(props.lessonNote?.notes ?? '');
watch(
    () => props.lessonNote,
    (n) => {
        journalNotes.value = n?.notes ?? '';
    },
);

const form = useForm({ lesson_id: 0, date: '', notes: '' });

const autoSave = ref(localStorage.getItem('journal-autosave') !== 'false');
watch(autoSave, (v) => localStorage.setItem('journal-autosave', String(v)));

function doSave(afterSave: AfterSave = 'stay') {
    if (!props.lessonId) {
        return;
    }

    form.lesson_id = props.lessonId;
    form.date = props.date;
    form.notes = journalNotes.value;
    form.post(store.url(), {
        preserveState: afterSave === 'stay',
        preserveScroll: afterSave === 'stay',
        onSuccess: () => emit('saved', afterSave),
    });
}

const save = useDebounceFn(() => {
    if (autoSave.value) {
        doSave();
    }
}, 1000);
</script>

<template>
    <div class="flex flex-col gap-3 rounded-2xl bg-white p-6">
        <div>
            <h3 class="text-base font-bold text-stone-800">
                Journal de classe
            </h3>
            <p class="mt-0.5 text-xs text-stone-400">
                Notez le déroulement du cours, les points abordés et toute
                remarque utile.
            </p>
        </div>
        <textarea
            v-model="journalNotes"
            :disabled="!selectedEntry"
            rows="6"
            aria-label="Notes du journal de classe"
            placeholder="Notes du cours…"
            class="w-full resize-none rounded-xl bg-bg-primary p-4 text-sm text-text-base placeholder:text-border-figma focus:outline-none disabled:cursor-not-allowed disabled:opacity-40"
            @input="save"
        />
        <div class="flex items-center justify-between gap-3">
            <div class="flex min-w-0 flex-col gap-0.5">
                <label
                    class="flex cursor-pointer items-center gap-1.5"
                    title="Sauvegarder automatiquement à chaque frappe"
                >
                    <input
                        v-model="autoSave"
                        type="checkbox"
                        class="h-3.5 w-3.5 cursor-pointer accent-blue"
                    />
                    <span class="text-[11px] text-stone-400">
                        <abbr title="Sauvegarde automatique"
                            >Save auto</abbr
                        ></span
                    >
                </label>
                <span v-if="form.processing" class="text-[10px] text-stone-400"
                    >Sauvegarde…</span
                >
                <span
                    v-else-if="lessonNote?.savedAt"
                    class="truncate text-[10px] text-stone-400"
                    >{{ lessonNote.savedAt }}</span
                >
            </div>
            <SaveSplitButton
                :loading="form.processing"
                :disabled="!selectedEntry"
                @save="doSave"
            />
        </div>
    </div>
</template>

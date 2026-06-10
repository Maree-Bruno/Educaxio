<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import Badge from '@/components/widgets/Badge.vue';
import BaseModal from '@/components/widgets/BaseModal.vue';
import Button from '@/components/widgets/Button.vue';
import { approve, reject } from '@/routes/admin/join-requests';
import { index as lessonsIndex } from '@/routes/admin/lessons';
import { useToasterStore } from '@/stores/toaster';

interface Subject     { id: number; name: string }
interface JoinRequest { id: number; user: { id: number; name: string; email: string }; subjects: Subject[] }

const props = defineProps<{
    joinRequests: JoinRequest[];
    school:       { slug: string };
}>();

const modal = ref<InstanceType<typeof BaseModal> | null>(null);
const toaster = useToasterStore();

function approveRequest(id: number, teacherName: string) {
    router.patch(approve.url({ school: props.school.slug, joinRequest: id }), {}, {
        onSuccess: () => {
            toaster.actionable(
                `${teacherName} a été approuvé(e)`,
                'Attribuer des cours →',
                () => router.visit(lessonsIndex.url({ school: props.school.slug })),
            );
        },
    });
}

function rejectRequest(id: number) {
    router.patch(reject.url({ school: props.school.slug, joinRequest: id }));
}
</script>

<template>
    <button
        type="button"
        class="flex shrink-0 items-center gap-2 rounded-xl border border-neutral-200 bg-white px-3 py-2 text-sm font-medium transition-colors"
        :class="joinRequests.length > 0 ? 'text-text-base hover:bg-gray-50' : 'cursor-not-allowed text-border-figma opacity-50'"
        :disabled="joinRequests.length === 0"
        @click="modal?.open()"
    >
        Demandes d'adhésion
        <span
            v-if="joinRequests.length > 0"
            class="flex size-5 items-center justify-center rounded-full bg-blue text-xs font-bold text-white"
        >
            {{ joinRequests.length }}
        </span>
    </button>

    <BaseModal ref="modal" class="max-w-lg!">
        <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-bold text-text-base">Demandes d'adhésion</h3>
                <Button variant="ghost" size="sm" label="Fermer" :icon-only="true" @click="modal?.close()">
                    <template #icon>
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </template>
                </Button>
            </div>

            <ul class="flex flex-col gap-3">
                <li
                    v-for="req in joinRequests"
                    :key="req.id"
                    class="flex flex-col gap-3 rounded-2xl bg-white p-4 outline outline-1 -outline-offset-1 outline-border-figma"
                >
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-neutral-100 text-sm font-bold text-neutral-500">
                            {{ req.user.name.charAt(0).toUpperCase() }}
                        </div>
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-text-base">{{ req.user.name }}</p>
                            <p class="truncate text-sm text-stone-400">{{ req.user.email }}</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <Badge v-for="s in req.subjects" :key="s.id" variant="neutral">{{ s.name }}</Badge>
                        <span v-if="req.subjects.length === 0" class="text-xs text-border-figma">Aucune matière renseignée</span>
                    </div>
                    <div class="flex justify-end gap-2">
                        <Button variant="danger" size="sm" label="Refuser" @click="rejectRequest(req.id)" />
                        <Button variant="primary" size="sm" label="Accepter" @click="approveRequest(req.id, req.user.name)" />
                    </div>
                </li>
            </ul>
        </div>
    </BaseModal>
</template>

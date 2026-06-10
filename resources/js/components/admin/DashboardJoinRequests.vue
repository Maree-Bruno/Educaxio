<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import Button from '@/components/widgets/Button.vue';
import DashboardCard from '@/components/admin/DashboardCard.vue';
import EmptyState from '@/components/widgets/EmptyState.vue';
import { approve, reject } from '@/routes/admin/join-requests';
import { index as lessonsIndex } from '@/routes/admin/lessons';
import { useToasterStore } from '@/stores/toaster';
import type { School, Subject, UserSummary } from '@/types';

interface JoinRequest { id: number; user: UserSummary; subjects: Subject[] }

const props = defineProps<{
    joinRequests: JoinRequest[];
    school:       School;
    pendingCount: number;
}>();

const toaster = useToasterStore();
const loadingApprove = ref<Record<number, boolean>>({});
const loadingReject  = ref<Record<number, boolean>>({});

function approveRequest(req: JoinRequest) {
    loadingApprove.value[req.id] = true;
    router.patch(approve.url({ school: props.school, joinRequest: req }), {}, {
        preserveScroll: true,
        onSuccess: () => {
            toaster.actionable(
                `${req.user.name} a été approuvé(e)`,
                'Attribuer des cours →',
                () => router.visit(lessonsIndex.url({ school: props.school.slug })),
            );
        },
        onFinish: () => { loadingApprove.value[req.id] = false; },
    });
}

function rejectRequest(req: JoinRequest) {
    loadingReject.value[req.id] = true;
    router.patch(reject.url({ school: props.school, joinRequest: req }), {}, {
        preserveScroll: true,
        onFinish: () => { loadingReject.value[req.id] = false; },
    });
}
</script>

<template>
    <DashboardCard description="Approuvez ou refusez les demandes de professeurs souhaitant rejoindre l'établissement.">
        <template #title>
            Demandes d'adhésion
            <span v-if="pendingCount > 0" class="ml-1 rounded-full bg-orange px-2 py-0.5 text-xs font-bold text-white">
                {{ pendingCount }}
            </span>
        </template>

        <EmptyState v-if="joinRequests.length === 0" message="Aucune demande en attente" class="bg-white" />
        <ul v-else class="divide-y divide-neutral-100 bg-white">
            <li
                v-for="req in joinRequests"
                :key="req.id"
                class="flex flex-col gap-2 px-6 py-4 sm:flex-row sm:items-center sm:gap-4"
            >
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-bold text-text-base">{{ req.user.name }}</p>
                    <p class="truncate text-xs text-border-figma">{{ req.user.email }}</p>
                    <p v-if="req.subjects.length" class="mt-1 truncate text-xs text-stone-400">
                        {{ req.subjects.map((s) => s.name).join(', ') }}
                    </p>
                </div>
                <div class="flex shrink-0 gap-2">
                    <Button variant="primary" size="sm" label="Approuver" :loading="loadingApprove[req.id]" @click="approveRequest(req)" />
                    <Button variant="danger"  size="sm" label="Refuser"   :loading="loadingReject[req.id]"  @click="rejectRequest(req)" />
                </div>
            </li>
        </ul>
    </DashboardCard>
</template>

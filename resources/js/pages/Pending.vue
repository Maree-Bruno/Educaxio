<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Badge from '@/components/widgets/Badge.vue';
import SchoolJoinForm from '@/components/widgets/SchoolJoinForm.vue';
import SubjectPicker from '@/components/widgets/SubjectPicker.vue';
import { setPageTitle } from '@/composables/usePageTitle';

interface School   { id: number; name: string }
interface Request_ { id: number; school: School; status: 'pending' | 'approved' | 'rejected' }

defineProps<{
    requests:     Request_[];
    schools:      School[];
    allSubjects:  { id: number; name: string }[];
    userSubjects: number[];
}>();

setPageTitle('En attente de validation');

const addingSchool = ref(false);

const statusLabel: Record<string, string> = {
    pending:  'En attente',
    approved: 'Approuvée',
    rejected: 'Refusée',
};

const statusVariant: Record<string, 'neutral' | 'blue'> = {
    pending:  'neutral',
    approved: 'blue',
    rejected: 'neutral',
};

function cancelRequest(id: number) {
    router.delete(`/pending/join-requests/${id}`);
}
</script>

<template>
    <div class="mx-auto flex flex-col gap-6 px-4 py-8">
        <div class="flex flex-col gap-1">
            <h1 class="text-2xl font-bold text-text-base">Compte en attente</h1>
            <p class="text-base text-stone-500">
                Votre compte est créé mais n'est pas encore rattaché à un établissement validé.
                Vous aurez accès à l'application dès qu'un administrateur approuvera votre demande.
            </p>
        </div>

        <div class="overflow-hidden rounded-2xl bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-neutral-100 px-6 py-4">
                <h2 class="text-base font-bold text-text-base">Établissements</h2>
                <button
                    v-if="!addingSchool && schools.length > 0"
                    type="button"
                    class="text-sm font-medium text-blue hover:underline"
                    @click="addingSchool = true"
                >
                    + Ajouter
                </button>
            </div>

            <ul v-if="requests.length > 0" class="divide-y divide-neutral-100">
                <li
                    v-for="req in requests"
                    :key="req.id"
                    class="flex items-center justify-between gap-3 px-6 py-4"
                >
                    <div class="flex items-center gap-3">
                        <Badge :variant="statusVariant[req.status]">{{ statusLabel[req.status] }}</Badge>
                        <span class="text-sm font-medium text-text-base">{{ req.school.name }}</span>
                    </div>
                    <button
                        v-if="req.status === 'pending'"
                        type="button"
                        class="text-xs text-stone-400 transition-colors hover:text-pink"
                        @click="cancelRequest(req.id)"
                    >
                        Annuler
                    </button>
                </li>
            </ul>
            <p v-else-if="!addingSchool" class="px-6 py-5 text-sm text-border-figma">
                Aucune demande envoyée.
            </p>

            <div v-if="addingSchool" class="border-t border-neutral-100 px-6 py-4">
                <SchoolJoinForm
                    :schools="schools"
                    @cancel="addingSchool = false"
                    @success="addingSchool = false"
                />
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl bg-white shadow-sm">
            <div class="border-b border-neutral-100 px-6 py-4">
                <h2 class="text-base font-bold text-text-base">Matières enseignées</h2>
                <p class="mt-0.5 text-sm text-stone-400">Sélectionnez les matières que vous pouvez enseigner.</p>
            </div>
            <div class="px-6 py-4">
                <SubjectPicker :subjects="allSubjects" :model-value="userSubjects" />
            </div>
        </div>
    </div>
</template>
